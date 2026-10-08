<?php
declare(strict_types=1);
namespace App\Modules\Integrations;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\View;
use App\Core\Response;

final class IntakeController extends \App\Core\BaseController
{
    protected array $adminActions=['*'];
    public function index(Request $request,array $params=[]): void
    {
        $pdo=Database::getInstance();
        View::render('intake/index',[
            'inquiries'=>$pdo->query('SELECT i.*,c.name AS company_name FROM inquiries i JOIN companies c ON c.company_id=i.company_id ORDER BY inquiry_id DESC LIMIT 100')->fetchAll(),
            'calls'=>$pdo->query('SELECT v.*,c.name AS company_name FROM voice_calls v JOIN companies c ON c.company_id=v.company_id ORDER BY voice_call_id DESC LIMIT 50')->fetchAll(),
            'failed'=>(int)$pdo->query("SELECT COUNT(*) FROM integration_events WHERE status='failed'")->fetchColumn(),
        ]);
    }
    private function detail(int $id,array $errors=[]): void
    {
        $q=Database::getInstance()->prepare('SELECT i.*,c.name AS company_name FROM inquiries i JOIN companies c ON c.company_id=i.company_id WHERE inquiry_id=?');$q->execute([$id]);$inquiry=$q->fetch();
        if (!$inquiry) { Response::abort(404); }
        View::render('intake/show',['inquiry'=>$inquiry,'contact'=>json_decode($inquiry['contact'],true),
            'customers'=>(new \App\Modules\Customers\CustomerService())->getAll(),'errors'=>$errors]);
    }
    public function show(Request $request,array $params=[]): void { $this->detail((int)$params['id']); }
    public function promote(Request $request,array $params=[]): void
    {
        try { $result=(new IntakeService())->promote((int)$params['id'],$request->all(),(int)Auth::userId()); }
        catch (\InvalidArgumentException $e) { $result=['success'=>false,'errors'=>['inquiry'=>[$e->getMessage()]]]; }
        if ($result['success']) { $this->redirectWithMessage('/leads/'.$result['id'],'Inquiry converted to a lead.'); }
        $this->detail((int)$params['id'],$result['errors']);
    }
}
