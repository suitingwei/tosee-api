<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Log;
use Qiniu;
use Qiniu\Auth;
use Qiniu\Processing\PersistentFop;

/**
 * Class QiniuService
 * @package App\Services
 */
class QiniuService
{
    const PERSISTENT_Id_KEY = 'qn-watermark-key';
    const STICKER_Id_KEY    = 'qn-sticker-key';

    //Should append the last number. -vframe-000001~000006
    const FRAME_SUFFIX      = '-vframe-00000';
    const FORMAT_SQUARE_PNG = 'imageView2/1/w/158/h/158/format/png|imageslim';
    const FORMAT_WEBP       = 'imageView2/0/format/webp';
    const FORMAT_PNG        = 'imageView2/0/format/png';
    const FORMAT_JPG        = 'imageView2/0/foramt/jpeg';

    /**
     * Generate url from the qiniu key.
     *
     * @param $key
     *
     * @return string
     */
    public static function generateVideoUrlFromKey($key)
    {
        return Helper::url($key, env('QINIU_VIDEO_DOMAIN'));
    }

    public static function getTextShareThumbnailFops()
    {
        return 'vframe/jpg/w/720/h/1280/offset/1|imageMogr2/thumbnail/90x/crop/x90|watermark/3/image/aHR0cDovL3MudG9zZWVhcHAuY29tL2xvZ28vcGxheV90aHVtYi5wbmc=/gravity/Center/dx/0/dy/0';
    }

    public static function getHttpsVideoUrl($videoKey)
    {
        return Helper::url($videoKey, env('QINIU_APP_HTTPS_DOMAIN'), 'https');
    }

    public static function getTextShareThumbnailUrl($videoKey)
    {
        $videoUrl = self::getHttpsVideoUrl($videoKey);
        $fops     = self::getTextShareThumbnailFops();
        return $videoUrl . '?' . $fops;
    }

    public static function getThumbnailShareUrl($videoKey, $textShareUrl, $title)
    {
        $qrcodeUrl          = Helper::url('qiniu/qrcode/' . Qiniu\base64_urlSafeEncode($textShareUrl));
        $titleWartermarkUrl = Helper::url('qiniu/watermark/' . Qiniu\base64_urlSafeEncode($title));
        $videoUrl           = self::getHttpsVideoUrl($videoKey);

        return implode([
            $videoUrl . '?vframe/jpg/offset/1/w/720/h/1280|watermark/3/',
            'image/' . Qiniu\base64_urlSafeEncode('http://v0.toseeapp.com/logo/qrcode_tip_v3.png') . '/gravity/Center/dx/0/dy/100/',
            'image/' . Qiniu\base64_urlSafeEncode('http://s0.toseeapp.com/logo/watermark_bottom.png') . '/gravity/South/dx/0/dy/26/',
            'image/aHR0cDovL3MudG9zZWVhcHAuY29tL2xvZ28vcGxheS5wbmc=/gravity/Center/dx/0/dy/0/',
            'image/aHR0cDovL3MudG9zZWVhcHAuY29tL2xvZ28vcXJjb2RlLWJhY2tncm91bmQucG5n/gravity/NorthEast/dx/10/dy/10/',
            'image/' . Qiniu\base64_urlSafeEncode($qrcodeUrl) . '/gravity/NorthEast/dx/15/dy/15/',
            'image/aHR0cDovL3MudG9zZWVhcHAuY29tL2xvZ28vaWNvbng0Ni5wbmc=/gravity/NorthEast/dx/67/dy/67/',
            'image/' . Qiniu\base64_urlSafeEncode($titleWartermarkUrl) . '/gravity/South/dx/0/dy/100/',
        ]);
    }

    public static function makeVideoWatermark($videoKey, $notifyUrl = '')
    {
        $accessKey = env('QINIU_ACCESS_KEY');
        $secretKey = env('QINIU_SECRET_KEY');
        $bucket    = env('QINIU_APP_BUCKET');

        $auth = new Auth($accessKey, $secretKey);
        $key  = $videoKey;

        //转码是使用的队列名称。 https://portal.qiniu.com/mps/pipeline
        $pipeline = 'video-watermark';
        //转码完成后通知到你的业务服务器。
        $pfop = new PersistentFop($auth, $bucket, $pipeline, $notifyUrl);
        //水印参数
        $saveAs = Qiniu\base64_urlSafeEncode($bucket . ':watermark/' . $key);
        $fops   = 'avthumb/mp4/wmImage/aHR0cDovL3MudG9zZWVhcHAuY29tL2xvZ28vdmlkZW8td2F0ZXJtYXJrLnBuZz92Mg==/wmGravity/NorthWest/wmOffsetX/22/wmOffsetY/22|saveas/' . $saveAs;
        list($id, $err) = $pfop->execute($key, $fops);
        Redis::sadd(self::PERSISTENT_Id_KEY, $videoKey);

        Log::info("[video avthumb] $key $bucket:watermark/$key $notifyUrl");
        return;
    }

    public static function makeShareThumbnail($videoKey)
    {
        $accessKey = env('QINIU_ACCESS_KEY');
        $secretKey = env('QINIU_SECRET_KEY');
        $bucket    = env('QINIU_APP_BUCKET');

        $auth = new Auth($accessKey, $secretKey);
        $key  = $videoKey;

        //转码是使用的队列名称。 https://portal.qiniu.com/mps/pipeline
        $pipeline = 'video-watermark';
        $pfop     = new PersistentFop($auth, $bucket, $pipeline);
        $saveAs   = Qiniu\base64_urlSafeEncode($bucket . ':sharethumbnail/' . $key . '.jpg');
        $fops     = self::getTextShareThumbnailFops() . '|saveas/' . $saveAs;
        list($id, $err) = $pfop->execute($key, $fops);

        Log::info("[video vframe] $key $bucket:sharethumbnail/$key.jpg");
    }

    public static function getVideoDuration($videoKey)
    {
        if (empty($videoKey)) {
            return 0;
        }
        $url = self::getHttpsVideoUrl($videoKey) . '?avinfo';
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $result   = json_decode($response);
        if (isset($result->code)) {
            return 0;
        }
        return $result->format->duration;
    }

    public static function getGifDuration($gifUrl)
    {
        return $gifUrl . '?imageInfo';
    }

    /**
     * @param $video_key
     *
     * @return array
     */
    public static function getVideoSampleFrames($video_key)
    {
        $frameUrls = [];
        for ($i = 1; $i <= 4; $i++) {
            array_push($frameUrls, self::getHttpsVideoUrl($video_key) . self::FRAME_SUFFIX . $i . '?' . self::FORMAT_SQUARE_PNG);
        }
        return $frameUrls;
    }

    /**
     * @param     $videoKey
     * @param int $number
     *
     * @return string
     */
    public static function getVideoFrameUrl($videoKey, $number = 1)
    {
        return self::getHttpsVideoUrl($videoKey) . self::FRAME_SUFFIX . $number;
    }

    public static function getVideoFrameUrlImmedidate($videoKey)
    {
       return self::getHttpsVideoUrl($videoKey) .'?vframe/jpg/offset/1';
    }

    public static function getWebpFromGif($gifKey)
    {
        return self::getHttpsVideoUrl($gifKey) . '?' . self::FORMAT_WEBP;
    }

    public static function getPngFromGif($gifKey)
    {
        return self::getHttpsVideoUrl($gifKey) . '?' . self::FORMAT_PNG;
    }

    public static function getJpgFromGif($gifKey)
    {
        return self::getHttpsVideoUrl($gifKey) . '?' . self::FORMAT_JPG;
    }

    public static function getSquareWebpFromGif($gifKey)
    {
        return self::getHttpsVideoUrl($gifKey) . '?' . self::FORMAT_SQUARE_PNG;
    }

}
