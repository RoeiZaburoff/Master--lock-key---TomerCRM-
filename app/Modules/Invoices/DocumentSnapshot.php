<?php
declare(strict_types=1);
namespace App\Modules\Invoices;

final class DocumentSnapshot
{
    public static function capture(array $job): string
    {
        $keys=['company_name','company_phone','company_email','company_address','company_logo',
            'customer_name','customer_phone','customer_email','address_line1','address_line2','city','state','zip','job_type','job_description'];
        $snapshot=[];
        foreach ($keys as $key) { $snapshot[$key]=$job[$key] ?? ''; }
        $snapshot['company_name']=$job['company_name'] ?: APP_NAME;
        $snapshot['customer_phone']=$job['phone'] ?? $job['customer_phone'] ?? '';
        $snapshot['customer_email']=$job['email'] ?? $job['customer_email'] ?? '';
        $snapshot['job_description']=$job['description'] ?? $job['job_description'] ?? '';
        $snapshot['company_logo']='';
        if (!empty($job['company_logo'])) {
            $src=STORAGE_PATH.'/logos/'.basename($job['company_logo']);
            if (is_file($src)) {
                $dest='invoice_'.bin2hex(random_bytes(16)).'.'.pathinfo($src,PATHINFO_EXTENSION);
                if (!copy($src,STORAGE_PATH.'/logos/'.$dest)) { throw new \RuntimeException('Could not freeze invoice logo.'); }
                $snapshot['company_logo']=$dest;
            }
        }
        return json_encode($snapshot, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    }
    public static function apply(array $invoice): array
    {
        if (empty($invoice['document_snapshot'])) { throw new \RuntimeException('Invoice snapshot missing.'); }
        return array_replace($invoice,json_decode($invoice['document_snapshot'],true,32,JSON_THROW_ON_ERROR));
    }
}
