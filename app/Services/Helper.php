<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Validator;

/**
 * Class Helper
 * @package App\Services
 */
class Helper
{
    /**
     * @param array $data
     * @param int   $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function response($data = [], $code = 200)
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        if ($code == 200) {
            $response = ['code' => $code] + ['data' => $data];
        } else {
            $response = ['code' => $code] + $data;
        }

        return response()->json($response, 200, $headers);
    }

    /**
     * @param $input
     */
    public static function extendMobileValidator($input)
    {
        Validator::extend('mobile', function ($attribute, $value, $parameters) use ($input) {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            try {
                $swissNumberStr   = $input['code'] . ' ' . $input['mobile'];
                $swissNumberProto = $phoneUtil->parse($swissNumberStr, $input['country']);

                return $phoneUtil->isValidNumber($swissNumberProto);
            } catch (\libphonenumber\NumberParseException $e) {
                return false;
            }
        });
    }

    /**
     * @param        $uri
     * @param string $host
     * @param string $scheme
     *
     * @return string
     */
    public static function url($uri, $host = '', $scheme = 'http')
    {
        $host = empty($host) ? env('API_HOST') : $host;

        return $scheme . '://' . $host . '/' . $uri;
    }


    /**
     * @param     $videoKey
     * @param int $type
     *
     * @return string
     */
    public static function videoUrl($videoKey, $type = 0)
    {
        if ($type == 1) {
            //$path = 'watermark/' . $videoKey;
            return self::url($videoKey, env('QINIU_VIDEO_DOMAIN'));
        }

        if (Redis::sismember(QiniuService::STICKER_Id_KEY, $videoKey)) {
            $path = 'sticker/' . $videoKey;
        } elseif (!Redis::sismember(QiniuService::PERSISTENT_Id_KEY, $videoKey)) {
            $path = 'watermark/' . $videoKey;
        } else {
            $path = $videoKey;
        }

        return self::url($path, env('QINIU_VIDEO_DOMAIN'));
    }

    /**
     * Response client with invalid parameters.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function responseInvalidParameters()
    {
        return self::response(['message' => 'Invalid parameters'], 400);
    }
}
