<?php
declare(strict_types=1);
namespace App\Modules\Integrations;
use App\Core\Database;
use App\Modules\Customers\CustomerService;
use App\Modules\Leads\LeadService;

final class IntakeService
{
    public function promote(int $id, array $data, int $userId): array
    {
        $pdo=Database::getInstance(); $pdo->beginTransaction();
        try {
            $q=$pdo->prepare('SELECT * FROM inquiries WHERE inquiry_id=? FOR UPDATE');$q->execute([$id]);$inquiry=$q->fetch();
            if (!$inquiry) { throw new \InvalidArgumentException('Inquiry not found.'); }
            if ($inquiry['status']==='promoted') { $pdo->commit(); return ['success'=>true,'id'=>(int)$inquiry['job_id']]; }
            $v=new \App\Core\Validator();
            if (!$v->validate($data,['address'=>'required|max:255','city'=>'required|max:100','state'=>'required|max:50','zip'=>'required|max:20'])) { $pdo->rollBack(); return ['success'=>false,'errors'=>$v->errors()]; }
            $q=$pdo->prepare('SELECT company_id FROM companies WHERE company_id=? AND is_active=1');$q->execute([$inquiry['company_id']]);
            if (!$q->fetchColumn()) { throw new \InvalidArgumentException('Company is inactive.'); }
            $customers=new CustomerService(); $customerId=(int)($data['existing_customer_id'] ?? 0);
            if ($customerId>0) {
                if (!$customers->getById($customerId)) { throw new \InvalidArgumentException('Customer not found.'); }
            } else {
                $result=$customers->create(['full_name'=>$data['full_name'] ?? '', 'phone'=>$data['phone'] ?? '', 'email'=>$data['email'] ?? '',
                    'address_line1'=>$data['address'],'city'=>$data['city'],'state'=>$data['state'],'zip'=>$data['zip']]);
                if (!$result['success']) { $pdo->rollBack(); return $result; }
                $customerId=$result['id'];
            }
            $lead=(new LeadService())->create(['customer_id'=>$customerId,'company_id'=>(int)$inquiry['company_id'],
                'status'=>'progress','description'=>$data['description'] ?? '', 'address'=>$data['address'],
                'city'=>$data['city'],'state'=>$data['state'],'zip'=>$data['zip']]);
            if (!$lead['success']) { $pdo->rollBack(); return $lead; }
            $q=$pdo->prepare("INSERT INTO job_meta(job_id,meta_key,meta_value) VALUES(?,'intake_inquiry_id',?),(?,'dispatch_priority',?)");
            $priority=in_array($data['priority'] ?? '',['low','normal','high','urgent'],true)?$data['priority']:'normal';
            $q->execute([$lead['id'],(string)$id,$lead['id'],$priority]);
            $q=$pdo->prepare("UPDATE inquiries SET status='promoted',job_id=?,promoted_by=? WHERE inquiry_id=?");$q->execute([$lead['id'],$userId,$id]);
            $pdo->commit();return $lead;
        } catch (\Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    }
}
