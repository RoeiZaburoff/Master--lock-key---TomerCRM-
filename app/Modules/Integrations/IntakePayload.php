<?php
declare(strict_types=1);
namespace App\Modules\Integrations;

final class IntakePayload
{
    public static function contact(array $input): array
    {
        $result=[];
        foreach (['full_name'=>150,'phone'=>40,'email'=>150,'address'=>255,'city'=>100,'state'=>50,'zip'=>20,'description'=>4000] as $key=>$max) {
            if (isset($input[$key]) && !is_string($input[$key])) { throw new \InvalidArgumentException('Invalid contact field.'); }
            $value=trim($input[$key] ?? '');
            if (mb_strlen($value)>$max) { throw new \InvalidArgumentException('Contact field too long.'); }
            $result[$key]=$value;
        }
        if ($result['email'] !== '' && !filter_var($result['email'],FILTER_VALIDATE_EMAIL)) { throw new \InvalidArgumentException('Invalid email.'); }
        return $result;
    }
}
