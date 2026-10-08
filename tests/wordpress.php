<?php
// Run inside a disposable WordPress installation; mocked HTTP transport, real WP queue/database/hooks.
declare(strict_types=1);
require '/var/www/html/wp-load.php';
if (getenv('MLK_TEST_ALLOW_DB_WRITE')!=='1') { throw new RuntimeException('Disposable WordPress required.'); }
require_once '/var/www/html/wp-admin/includes/plugin.php';
$plugin='masterlocknkey-crm/masterlocknkey-crm.php';
$result=activate_plugin($plugin);
if (is_wp_error($result)) { throw new RuntimeException('Plugin activation failed.'); }
function check(bool $ok,string $name): void { if (!$ok) { throw new RuntimeException($name); }echo 'PASS '.$name.PHP_EOL; }
check(wp_next_scheduled('mlk_crm_send_queue')!==false,'WordPress cron registered');
check(str_contains(do_shortcode('[masterlocknkey_request]'),'mlk_crm_submit'),'shortcode form renders');
$id=wp_generate_uuid4();$payload=['event_id'=>$id,'full_name'=>'WordPress fixture','phone'=>'202-555-0123'];
check(MLK_CRM_Bridge::enqueue($payload),'WordPress durable outbox accepts inquiry');
$calls=0;$captured=[];
add_filter('pre_http_request',function($pre,$args,$url)use(&$calls,&$captured){
    $calls++;$captured=$args;$ts=$args['headers']['X-CRM-Timestamp'];
    check(hash_equals(hash_hmac('sha256',$ts.'.'.$args['body'],MLK_CRM_SECRET),$args['headers']['X-CRM-Signature']),'WordPress signs exact payload');
    check($args['sslverify']===true && $args['redirection']===0,'TLS verification preserved; redirects disabled');
    if ($calls===1) { return new WP_Error('fixture_unavailable','fixture'); }
    return ['headers'=>[],'body'=>'{"accepted":true}','response'=>['code'=>202,'message'=>'Accepted'],'cookies'=>[]];
},10,3);
MLK_CRM_Bridge::send();global $wpdb;$table=MLK_CRM_Bridge::table();
$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE event_id=%s",$id));
check($row->status==='pending' && (int)$row->attempts===1,'delivery failure retained for retry');
$wpdb->update($table,['retry_at'=>gmdate('Y-m-d H:i:s',time()-1)],['event_id'=>$id]);
MLK_CRM_Bridge::send();$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE event_id=%s",$id));
check($row->status==='sent' && $row->payload==='{}','successful delivery clears contact payload');
check(json_decode($captured['body'],true)['event_id']===$id,'retry preserves event ID');
MLK_CRM_Bridge::send();check($calls===2,'sent inquiry is not delivered twice');
