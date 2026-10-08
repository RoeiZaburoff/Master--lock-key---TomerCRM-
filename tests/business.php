<?php
declare(strict_types=1);
require dirname(__DIR__).'/bin/bootstrap.php';
if (getenv('TEST_ALLOW_DB_WRITE') !== '1' || App\Core\RuntimeConfig::enabled('REQUIRE_HTTPS',true)) { throw new RuntimeException('Use a disposable local database and TEST_ALLOW_DB_WRITE=1.'); }
function check(bool $ok,string $message): void { if (!$ok) { throw new RuntimeException($message); } echo 'PASS '.$message.PHP_EOL; }
$suffix=bin2hex(random_bytes(4));$pdo=App\Core\Database::getInstance();
$companyService=new App\Modules\Settings\CompanyService();
$a=$companyService->create(['name'=>'Company A '.$suffix,'email'=>'a@example.test']);
$b=$companyService->create(['name'=>'Company B '.$suffix]);
check($a['success'] && $b['success'],'company profiles created');
$customerService=new App\Modules\Customers\CustomerService();
$customer=$customerService->create(['full_name'=>'Customer '.$suffix,'phone'=>'202-555-0100','email'=>'customer@example.test','address_line1'=>'100 Billing Road','city'=>'Chicago','state'=>'IL','zip'=>'60601']);
$sourceService=new App\Modules\LeadSources\LeadSourceService();$source=$sourceService->create(['name'=>'Source '.$suffix,'fee_type'=>'percent','fee_value'=>0.1,'is_active'=>1]);
$contractorService=new App\Modules\Contractors\ContractorService();$contractor=$contractorService->create(['full_name'=>'Technician '.$suffix,'phone'=>'202-555-0101','split_type'=>'percent','split_value'=>0.2,'is_active'=>1]);
$pdo->exec("UPDATE system_meta SET meta_value='3' WHERE meta_key='cc_fee_percentage'");
$leadService=new App\Modules\Leads\LeadService();
$lead=$leadService->create(['customer_id'=>$customer['id'],'company_id'=>$a['id'],'lead_source_id'=>$source['id'],'contractor_id'=>$contractor['id'],'status'=>'scheduled','scheduled_date'=>'2026-10-09','scheduled_time'=>'10:00','address'=>'200 Service Road','city'=>'Chicago','state'=>'IL','zip'=>'60602','description'=>'Lock installation','job_total'=>1000]);
check($lead['success'],'scheduled lead created');
$id=$lead['id'];$inventory=new App\Modules\Inventory\InventoryService();
$item=$inventory->create(['name'=>'Lock '.$suffix,'cost'=>60,'initial_qty'=>10]);
$input=['job_total'=>1000,'payment_method'=>'credit_card','materials'=>[
    ['inventory_id'=>$item['id'],'location_id'=>1,'ownership'=>'company','qty'=>1,'unit_cost'=>60],
    ['inventory_id'=>null,'ownership'=>'technician','unit_cost'=>40]
]];
$complete=$leadService->complete($id,$input);check($complete['success'],'completion with split materials and credit card');
$detail=$leadService->getDetail($id);$fin=$detail['financials'];
check((float)$fin['lead_fee']===90.0 && (float)$fin['contractor_share']===220.0 && (float)$fin['cc_fee']===30.0 && (float)$fin['net_profit']===660.0,'financial calculation matches agreed example');
check((new App\Modules\Inventory\InventoryRepository())->getStock($item['id'],1)===9.0,'inventory deducted once');
$sourceService->update($source['id'],['name'=>'Source '.$suffix,'fee_type'=>'percent','fee_value'=>0.5,'is_active'=>1]);
$contractorService->update($contractor['id'],['full_name'=>'Technician '.$suffix,'phone'=>'202-555-0101','split_type'=>'percent','split_value'=>0.5,'is_active'=>1]);
check($leadService->getDetail($id)['financials']===$fin,'fee profile changes do not rewrite completed jobs');
check($leadService->complete($id,$input)['success'],'completion repeat supported');
$after=$leadService->getDetail($id)['financials'];check((float)$after['net_profit']===660.0,'editing completion keeps original fee policy');
check((new App\Modules\Inventory\InventoryRepository())->getStock($item['id'],1)===9.0,'completion repeat does not double-deduct inventory');
$location=(new App\Modules\Inventory\LocationService())->create(['name'=>'Vehicle '.$suffix,'type'=>'vehicle']);
check($inventory->transferStock($item['id'],1,$location['id'],2)['success'],'inventory transferred to vehicle');
check(!(bool)$inventory->transferStock($item['id'],1,$location['id'],100)['success'],'insufficient stock transfer rejected');
file_put_contents(STORAGE_PATH.'/logos/company_test_'.$suffix.'.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5p0AAAAASUVORK5CYII='));
$q=$pdo->prepare('UPDATE companies SET logo_path=? WHERE company_id=?');$q->execute(['company_test_'.$suffix.'.png',$a['id']]);
$invoiceService=new App\Modules\Invoices\InvoiceService();$invoice=$invoiceService->create(['job_id'=>$id,'issued_date'=>'2026-10-08','items'=>[['description'=>'Lock installation','amount'=>1000]],'tax_rate'=>0]);
$snapshot=$invoiceService->getById($invoice['id']);
$companyService->update($a['id'],['name'=>'Renamed '.$suffix,'is_active'=>1,'delete_logo'=>1]);
$q=$pdo->prepare('UPDATE customers SET full_name=?,address_line1=? WHERE customer_id=?');$q->execute(['Changed customer','Changed address',$customer['id']]);
$q=$pdo->prepare('UPDATE jobs SET company_id=? WHERE job_id=?');$q->execute([$b['id'],$id]);
$frozen=$invoiceService->getById($invoice['id']);
check($frozen['company_name']==='Company A '.$suffix && $frozen['customer_name']==='Customer '.$suffix && $frozen['address_line1']==='100 Billing Road','invoice issuer and customer details are frozen');
check(is_file(STORAGE_PATH.'/logos/'.$frozen['company_logo']),'frozen invoice logo survives company logo deletion');
$rejected=false;try { $invoiceService->create(['job_id'=>$id,'subtotal'=>1]); } catch (RuntimeException $e) { $rejected=true; }check($rejected,'duplicate invoice rejected');
$calc=new App\Services\ProfitCalculator();$special=$calc->calculate(500,null,null,40,60,3,'credit_card',true,200);
check($special['net_profit']===100.0 && $special['lead_fee']===300.0 && $special['cc_fee']===0.0,'special closing matches agreed example');
file_put_contents('/tmp/mlk-test-ids.json',json_encode(['company_a'=>$a['id'],'company_b'=>$b['id'],'lead'=>$id,'invoice'=>$invoice['id']]));
