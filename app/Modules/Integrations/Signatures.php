<?php
declare(strict_types=1);
namespace App\Modules\Integrations;

final class Signatures
{
    public static function website(string $body, string $secret, string $timestamp, string $digest, ?int $now=null): bool
    {
        return strlen($secret)>=32 && preg_match('/^\d{10}$/D',$timestamp) === 1
            && abs(($now ?? time())-(int)$timestamp)<=300 && preg_match('/^[a-f0-9]{64}$/D',$digest) === 1
            && hash_equals(hash_hmac('sha256',$timestamp.'.'.$body,$secret),$digest);
    }
    // Retell SDK 6.2.4: HMAC-SHA256(exact raw body + millisecond timestamp), 5-minute tolerance.
    // https://github.com/RetellAI/retell-typescript-sdk/blob/main/src/lib/webhook_auth.ts
    public static function retell(string $body, string $secret, string $signature, ?int $nowMs=null): bool
    {
        if ($secret === '' || !preg_match('/^v=(\d{13}),d=([a-f0-9]{64})$/iD',$signature,$m)) { return false; }
        return abs(($nowMs ?? (int)floor(microtime(true)*1000))-(int)$m[1])<=300000
            && hash_equals(hash_hmac('sha256',$body.$m[1],$secret),strtolower($m[2]));
    }
}
