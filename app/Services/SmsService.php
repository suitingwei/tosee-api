<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Log;

class SmsService
{
    const SMS_CODE_KEY = 'SmsCode';

    public static function send($mobile, $message)
    {
        $smsApiKey = env('SMS_API_KEY');
        $smsApiUrl = env('SMS_API_URL');

        $data = [
            'apikey' => $smsApiKey,
            'mobile' => $mobile,
            'text'   => $message,
        ];

        $client = new Client();
        try {
            $r = $client->request('POST', $smsApiUrl, [
                'body' => http_build_query($data),
            ]);

            Log::debug($r->getStatusCode() . ' - ' . $r->getBody() . ' - ' . $data['text']);
            if ($r->getStatusCode() == 200) {
                return $r->getBody();
            }
        } catch (Exception $e) {
            Log::error("Sending invite-sms to {$mobile} error: {$e->getMessage()}");
        }

        return false;
    }

    public static function getCacheKey($mobile)
    {
        return self::SMS_CODE_KEY . ':' . $mobile;
    }

    public static function validatePhoneCode($mobile, $code)
    {
        return Cache::get(self::getCacheKey($mobile)) == $code;
    }
}
