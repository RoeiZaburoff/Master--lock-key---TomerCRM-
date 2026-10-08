<?php
declare(strict_types=1);
namespace App\Modules\Integrations;
use App\Core\RuntimeConfig as Config;
use App\Core\Request;
use App\Core\Response;

final class WebhookController
{
    public function handle(Request $request): never
    {
        if ($request->method()!=='POST') { header('Allow: POST'); Response::json(['error'=>'method_not_allowed'],405); }
        $website=$request->uri()==='/api/v1/website-inquiries';
        if (!Config::enabled($website?'WEBSITE_ENABLED':'RETELL_ENABLED')) { Response::json(['error'=>'integration_disabled'],503); }
        if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''),'application/json')) { Response::json(['error'=>'json_required'],415); }
        $body=file_get_contents('php://input',false,null,0,262145);
        if ($body === false || strlen($body)>262144) { Response::json(['error'=>'payload_too_large'],413); }
        try {
            if ($website) {
                $id=$_SERVER['HTTP_X_CRM_INTEGRATION'] ?? '';
                if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id)) { Response::json(['error'=>'invalid_signature'],401); }
                $keys=Config::get('WEBSITE_KEYS',[]); $key=$keys[$id] ?? null;
                if (!is_array($key) || !Signatures::website($body,(string)($key['secret'] ?? ''),$_SERVER['HTTP_X_CRM_TIMESTAMP'] ?? '',$_SERVER['HTTP_X_CRM_SIGNATURE'] ?? '')) { Response::json(['error'=>'invalid_signature'],401); }
                $data=json_decode($body,true,32,JSON_THROW_ON_ERROR);
                if (!is_array($data) || !preg_match('/^[a-zA-Z0-9-]{16,80}$/D',(string)($data['event_id'] ?? ''))) { throw new \InvalidArgumentException('Invalid event.'); }
                $contact=IntakePayload::contact($data);
                if ($contact['full_name']==='' || $contact['phone']==='') { throw new \InvalidArgumentException('Name and phone are required.'); }
                $result=(new EventInbox())->accept('wordpress',$id.':'.$data['event_id'],(int)$key['company_id'],['contact'=>$contact,'priority_hint'=>'normal']);
            } else {
                if (!Signatures::retell($body,(string)Config::get('RETELL_API_KEY',''),$_SERVER['HTTP_X_RETELL_SIGNATURE'] ?? '')) { Response::json(['error'=>'invalid_signature'],401); }
                $data=json_decode($body,true,32,JSON_THROW_ON_ERROR);
                if (!is_array($data)) { throw new \InvalidArgumentException('Invalid event.'); }
                $type=$data['event'] ?? '';
                if (!in_array($type,['call_started','call_ended','call_analyzed'],true)) { Response::json(['accepted'=>true,'ignored'=>true]); }
                $call=$data['call'] ?? null;
                if (!is_array($call) || !is_string($call['call_id'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{1,120}$/D',$call['call_id'])) { throw new \InvalidArgumentException('Invalid call.'); }
                $agents=Config::get('RETELL_AGENT_COMPANIES',[]); $agent=$call['agent_id'] ?? '';
                if (!is_string($agent) || !isset($agents[$agent])) { Response::json(['error'=>'unmapped_agent'],403); }
                $analysis=$call['call_analysis'] ?? [];
                if (!is_array($analysis)) { throw new \InvalidArgumentException('Invalid analysis.'); }
                foreach (['from_number','to_number','transcript'] as $field) {
                    if (isset($call[$field]) && !is_string($call[$field])) { throw new \InvalidArgumentException('Invalid call field.'); }
                }
                if (isset($analysis['call_summary']) && !is_string($analysis['call_summary'])) { throw new \InvalidArgumentException('Invalid summary.'); }
                $fields=$analysis['custom_analysis_data'] ?? [];
                if (!is_array($fields)) { $fields=[]; }
                $contact=IntakePayload::contact(array_intersect_key($fields,array_flip(['full_name','phone','email','address','city','state','zip','description'])) + ['phone'=>$call['from_number'] ?? '', 'description'=>mb_substr((string)($analysis['call_summary'] ?? ''),0,4000)]);
                $priority=$fields['priority_hint'] ?? 'normal';
                $payload=['type'=>$type,'call_id'=>$call['call_id'],'agent_id'=>$agent,
                    'from_number'=>mb_substr((string)($call['from_number'] ?? ''),0,40),
                    'to_number'=>mb_substr((string)($call['to_number'] ?? ''),0,40),
                    'summary'=>mb_substr((string)($analysis['call_summary'] ?? ''),0,8000),
                    'transcript'=>Config::enabled('RETELL_STORE_TRANSCRIPT')?mb_substr((string)($call['transcript'] ?? ''),0,200000):null,
                    'contact'=>$contact,'priority_hint'=>in_array($priority,['low','normal','high','urgent'],true)?$priority:'normal'];
                $result=(new EventInbox())->accept('retell',$call['call_id'].':'.$type,(int)$agents[$agent],$payload);
            }
            Response::json($result,$result['duplicate']?200:202);
        } catch (\JsonException|\InvalidArgumentException $e) { Response::json(['error'=>'invalid_payload'],422);
        } catch (\DomainException $e) { Response::json(['error'=>'event_conflict'],409); }
    }
}
