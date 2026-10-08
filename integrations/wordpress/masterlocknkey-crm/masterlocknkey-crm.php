<?php
/**
 * Plugin Name: MasterLocknKey CRM Bridge
 * Description: Signed, queued website inquiries for the separate MasterLocknKey CRM.
 * Version: 1.0.0
 * Requires PHP: 8.3
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;

final class MLK_CRM_Bridge
{
    public static function boot(): void
    {
        add_shortcode('masterlocknkey_request',[self::class,'form']);
        add_action('admin_post_nopriv_mlk_crm_submit',[self::class,'submit']);
        add_action('admin_post_mlk_crm_submit',[self::class,'submit']);
        add_filter('cron_schedules',static function(array $s): array { $s['mlk_five_minutes']=['interval'=>300,'display'=>'Every five minutes'];return $s; });
        add_action('mlk_crm_send_queue',[self::class,'send']);
        add_action('admin_menu',static function(): void { add_management_page('MasterLocknKey CRM','MasterLocknKey CRM','manage_options','mlk-crm',[self::class,'admin']); });
    }
    public static function table(): string { global $wpdb;return $wpdb->prefix.'mlk_crm_outbox'; }
    public static function activate(): void
    {
        global $wpdb;
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $t=self::table();$charset=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $t (
            event_id varchar(80) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int unsigned NOT NULL DEFAULT 0,
            retry_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (event_id),
            KEY queue_status (status,retry_at)
        ) $charset;");
        if (!wp_next_scheduled('mlk_crm_send_queue')) { wp_schedule_event(time()+60,'mlk_five_minutes','mlk_crm_send_queue'); }
    }
    public static function configured(): bool
    {
        return defined('MLK_CRM_ENABLED') && MLK_CRM_ENABLED === true
            && defined('MLK_CRM_URL') && wp_parse_url(MLK_CRM_URL,PHP_URL_SCHEME)==='https'
            && defined('MLK_CRM_SECRET') && strlen(MLK_CRM_SECRET)>=32
            && defined('MLK_CRM_INTEGRATION_ID') && preg_match('/^[A-Za-z0-9_-]{1,64}$/D',MLK_CRM_INTEGRATION_ID)===1;
    }
    public static function form(): string
    {
        if (!self::configured()) { return '<p>The request form is not available yet. Please use the contact details on this website.</p>'; }
        ob_start();
        $result=sanitize_key($_GET['mlk_result'] ?? '');
        if ($result==='received') { echo '<p role="status">Your request has been received for review. This does not confirm a booking.</p>'; }
        elseif ($result==='error') { echo '<p role="alert">We could not receive your request. Please contact the business directly.</p>'; }
        ?>
        <form action="<?= esc_url(admin_url('admin-post.php')) ?>" method="post" class="mlk-request-form">
            <input type="hidden" name="action" value="mlk_crm_submit">
            <?php wp_nonce_field('mlk_crm_submit','mlk_nonce'); ?>
            <div style="display:none" aria-hidden="true"><label>Leave this field empty<input name="mlk_website" tabindex="-1" autocomplete="off"></label></div>
            <?php foreach (['full_name'=>'Full name','phone'=>'Phone','email'=>'Email','address'=>'Service address','city'=>'City','state'=>'State','zip'=>'ZIP / postal code'] as $field=>$label): ?>
            <p><label><?= esc_html($label) ?><br><input name="<?= esc_attr($field) ?>" type="<?= $field==='email'?'email':($field==='phone'?'tel':'text') ?>" maxlength="<?= $field==='address'?255:150 ?>" <?= in_array($field,['full_name','phone'],true)?'required':'' ?>></label></p>
            <?php endforeach; ?>
            <p><label>How can we help?<br><textarea name="description" maxlength="4000" rows="4"></textarea></label></p>
            <p><label><input type="checkbox" name="mlk_consent" value="1" required> I agree to be contacted about this request and have read the <a href="<?= esc_url(get_privacy_policy_url()) ?>">privacy policy</a>.</label></p>
            <button type="submit">Send service request</button>
        </form>
        <?php return (string)ob_get_clean();
    }
    public static function submit(): void
    {
        if (!self::configured() || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mlk_nonce'] ?? '')),'mlk_crm_submit')
            || !empty($_POST['mlk_website']) || ($_POST['mlk_consent'] ?? '')!=='1') { self::redirect(false); }
        $rate='mlk_rate_'.hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').wp_salt('nonce'));
        $count=(int)get_transient($rate);
        if ($count>=5) { self::redirect(false); }
        set_transient($rate,$count+1,15*MINUTE_IN_SECONDS);
        $data=['event_id'=>wp_generate_uuid4()];
        foreach (['full_name'=>150,'phone'=>40,'email'=>150,'address'=>255,'city'=>100,'state'=>50,'zip'=>20,'description'=>4000] as $key=>$max) {
            $raw=wp_unslash($_POST[$key] ?? '');
            if (!is_string($raw) || mb_strlen($raw)>$max) { self::redirect(false); }
            $data[$key]=$key==='description'?sanitize_textarea_field($raw):sanitize_text_field($raw);
        }
        if ($data['full_name']==='' || $data['phone']==='' || ($data['email']!=='' && !is_email($data['email']))) { self::redirect(false); }
        if (!self::enqueue($data)) { self::redirect(false); }
        // Durable receipt in WordPress; the cron job handles delivery independently.
        self::redirect(true);
    }
    public static function enqueue(array $data): bool
    {
        global $wpdb;$now=gmdate('Y-m-d H:i:s');
        return $wpdb->insert(self::table(),['event_id'=>$data['event_id'],'payload'=>wp_json_encode($data,JSON_UNESCAPED_UNICODE),
            'status'=>'pending','attempts'=>0,'retry_at'=>$now,'updated_at'=>$now])!==false;
    }
    private static function redirect(bool $success): never
    {
        $target=wp_get_referer() ?: home_url('/');
        wp_safe_redirect(add_query_arg('mlk_result',$success?'received':'error',remove_query_arg('mlk_result',$target)));
        exit;
    }
    public static function send(): void
    {
        if (!self::configured()) { return; }
        global $wpdb;$t=self::table();$now=gmdate('Y-m-d H:i:s');
        $wpdb->query("UPDATE $t SET status='pending' WHERE status='sending' AND updated_at<UTC_TIMESTAMP()-INTERVAL 10 MINUTE AND attempts<10");
        $wpdb->query("UPDATE $t SET status='failed' WHERE status='sending' AND updated_at<UTC_TIMESTAMP()-INTERVAL 10 MINUTE AND attempts>=10");
        $rows=$wpdb->get_results("SELECT * FROM $t WHERE status='pending' AND retry_at<=UTC_TIMESTAMP() AND attempts<10 ORDER BY retry_at LIMIT 10");
        foreach ($rows as $row) {
            $claimed=$wpdb->query($wpdb->prepare("UPDATE $t SET status='sending',attempts=attempts+1,updated_at=%s WHERE event_id=%s AND status='pending' AND retry_at<=UTC_TIMESTAMP()",$now,$row->event_id));
            if ($claimed!==1) { continue; }
            $stamp=(string)time();$body=$row->payload;
            $response=wp_safe_remote_post(rtrim(MLK_CRM_URL,'/').'/api/v1/website-inquiries',[
                'timeout'=>15,'redirection'=>0,'sslverify'=>true,
                'headers'=>['Content-Type'=>'application/json','X-CRM-Integration'=>MLK_CRM_INTEGRATION_ID,
                    'X-CRM-Timestamp'=>$stamp,'X-CRM-Signature'=>hash_hmac('sha256',$stamp.'.'.$body,MLK_CRM_SECRET)],
                'body'=>$body,
            ]);
            $code=is_wp_error($response)?0:wp_remote_retrieve_response_code($response);
            $answer=is_wp_error($response)?null:json_decode(wp_remote_retrieve_body($response),true);
            if (in_array($code,[200,202],true) && is_array($answer) && ($answer['accepted'] ?? false)===true) {
                $wpdb->update($t,['status'=>'sent','payload'=>'{}','updated_at'=>gmdate('Y-m-d H:i:s')],['event_id'=>$row->event_id]);
            } else {
                $attempts=(int)$row->attempts+1;
                $wpdb->update($t,['status'=>$attempts>=10?'failed':'pending','retry_at'=>gmdate('Y-m-d H:i:s',time()+min(3600,60*(2**$attempts))),
                    'updated_at'=>gmdate('Y-m-d H:i:s')],['event_id'=>$row->event_id]);
            }
        }
        $wpdb->query("DELETE FROM $t WHERE status='sent' AND updated_at<UTC_TIMESTAMP()-INTERVAL 7 DAY");
    }
    public static function admin(): void
    {
        if (!current_user_can('manage_options')) { return; }
        global $wpdb;$rows=$wpdb->get_results('SELECT status,COUNT(*) AS total FROM '.self::table().' GROUP BY status');
        echo '<div class="wrap"><h1>MasterLocknKey CRM</h1><p>Bridge: '.(self::configured()?'configured':'disabled or missing private configuration').'</p><p>Add [masterlocknkey_request] to a page. Configure credentials in wp-config.php, never in browser JavaScript. Queue counters:</p><ul>';
        foreach ($rows as $row) { echo '<li>'.esc_html($row->status).': '.(int)$row->total.'</li>'; }
        echo '</ul><p>Failed submissions require administrator review. Records and keys are not shown here.</p></div>';
    }
}
MLK_CRM_Bridge::boot();
register_activation_hook(__FILE__,[MLK_CRM_Bridge::class,'activate']);
register_deactivation_hook(__FILE__,static function(): void { wp_clear_scheduled_hook('mlk_crm_send_queue'); });
