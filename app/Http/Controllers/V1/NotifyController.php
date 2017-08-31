<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Jobs\MergeMusicVideo2webp;
use App\Jobs\MergeVideo2webp;
use App\Models\MoneyGift;
use App\Models\MoneyTransfer;
use App\Services\Helper;
use App\Services\PingPPService;
use App\Services\PushService;
use App\Services\QiniuService;
use App\Services\RedBagServices\RedBagService;
use App\Services\WechatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Qiniu\Auth;

/**
 * Class NotifyController
 * @package App\Http\Controllers\V1
 */
class NotifyController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function qiniuPfop(Request $request)
    {
        $notifyBody = file_get_contents('php://input');
        Log::info('[qiniu pfop notify] ' . $notifyBody);

        $notifyData = json_decode($notifyBody);

        if (Redis::srem(QiniuService::PERSISTENT_Id_KEY, $notifyData->inputKey)) {
            Log::info('[qiniu pfop notify] del ' . $notifyData->inputKey);
        }

        return Helper::response();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function qiniu_merge_upload()
    {
        $notifyBody = file_get_contents('php://input');
        Log::info('[qiniu upload notify] ' . $notifyBody);

        $accessKey = env('QINIU_ACCESS_KEY');
        $secretKey = env('QINIU_SECRET_KEY');

        $auth = new Auth($accessKey, $secretKey);
        //回调的contentType
        $contentType = 'application/json';
        //回调的签名信息，可以验证该回调是否来自七牛
        //$authorization = $_SERVER['HTTP_AUTHORIZATION'];
        //七牛回调的url，具体可以参考：http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
        // $url = Helper::url('notify/qiniu/upload', env('API_HOST'));
        // $isQiniuCallback = $auth->verifyCallback($contentType, $authorization, $url, $notifyBody);

        // Log::info('[qiniu upload notify] verify callback '.$isQiniuCallback);
        // if ($isQiniuCallback) {
        if (true) {
            \Log::info('---------------notify');
            dispatch(new MergeVideo2webp($notifyBody));
            dispatch(new MergeMusicVideo2webp($notifyBody, "merge"));
            $resp = array('ret' => 'success');
        }
        else {
            $resp = array('ret' => 'failed');
        }

        return response()->json($resp);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function qiniuUpload()
    {
        $notifyBody = file_get_contents('php://input');
        Log::info('[qiniu upload notify] ' . $notifyBody);

        $accessKey = env('QINIU_ACCESS_KEY');
        $secretKey = env('QINIU_SECRET_KEY');

        $auth            = new Auth($accessKey, $secretKey);
        $contentType     = 'application/json';
        $authorization   = $_SERVER['HTTP_AUTHORIZATION'];
        $url             = Helper::url('notify/qiniu/upload', env('API_HOST'));
        $isQiniuCallback = $auth->verifyCallback($contentType, $authorization, $url, $notifyBody);

        Log::info('[qiniu upload notify] verify callback ' . $isQiniuCallback);
        if ($isQiniuCallback) {
            Log::info('---------------notify');
            //dispatch(new Video2webp($notifyBody));
            $resp = array('ret' => 'success');
        }
        else {
            $resp = array('ret' => 'failed');
        }

        return response()->json($resp);
    }

    /**
     * @return string
     */
    public function moneyGiftWechat()
    {
        Log::debug('pay notify content: ' . file_get_contents('php://input'));
        $result = WechatService::signVerify();

        if (!$result) {
            return '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        }

        if ($result['result_code'] != 'SUCCESS') {
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        }

        list($queryResult, $statusCode) = WechatService::queryOrderInfo($result['out_trade_no']);

        if ($queryResult['trade_state'] != 'success') {
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        }

        if (!($moneyGift = MoneyGift::where('out_trade_no', $result['out_trade_no'])->where('status', MoneyGift::STATUS_SHARE_GIFT_CREATED)->first())) {
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        }

        $moneyGifts = RedBagService::createRedBagsForMoneyGift($moneyGift);
        \Log::info('creating groupshoot moneygifts array:' . json_encode($moneyGifts));

        $moneyGiftKey = 'moneygifts:' . $moneyGift->id;
        if (!Redis::lpush($moneyGiftKey, $moneyGifts)) {
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        }

        MoneyGift::where('out_trade_no', $result['out_trade_no'])
                 ->where('status', MoneyGift::STATUS_SHARE_GIFT_CREATED)
                 ->update(['status' => MoneyGift::STATUS_SHARE_GIFT_PAID]);

        return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * Handle the pingpp charge success event.
     */
    public function pingppChargeSuccess()
    {
        $event = json_decode(file_get_contents("php://input"));

        if (!isset($event->type) || $event->type != 'charge.succeeded') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            exit("fail");
        }

        \Log::info('充值ping++回调Charge对象' . json_encode($event));
        $moneyGift = MoneyGift::where('out_trade_no', $event->data->object->order_no)
                              ->where('status', MoneyGift::STATUS_SHARE_GIFT_CREATED)
                              ->first();

        \Log::info('充值订单PING++回调MoneyGift对象:' . $moneyGift);
        if (!$moneyGift) {
            \Log::alert('充值订单PING++回调MoneyGift对象不合法');
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            exit("fail");
        }

        \Log::info('充值订单' . $moneyGift . '修改支付状态为成功,创建红包');
        $moneyGift->createRedBags();
    }

    /**
     * Enterpraise transfer money to account.
     */
    public function pingppTransferSuccess()
    {
        PingPPService::handleTransferSuccessNotifyEvent();
    }

    /**
     * Enterpraise transfer money to account.
     */
    public function pingppRefundSuccess()
    {
        $event = json_decode(file_get_contents("php://input"));

        if (!isset($event->type) || $event->type != 'refund.succeeded') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            exit("fail");
        }

        \Log::info('ping++企业付款回调Refund对象:' . json_encode($event));
        $moneyGift = MoneyGift::where('pingpp_charge_id', $event->data->object->charge)
                              ->where('status', MoneyGift::STATUS_SHARE_GIFT_PAID)
                              ->where('refunded', 0)
                              ->first();

        \Log::info('我方应用服务器充值记录MoneyGift:' . $moneyGift);
        if (!$moneyGift) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            exit("fail");
        }

        \Log::info('对用户' . $moneyGift->id . '的退款成功,现在修改其状态为已经退款');

        $moneyGift->setToRefunded();
    }



}
