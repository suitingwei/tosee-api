<?php

namespace App\Services;

require public_path('/jssdk.php');
use App\Models\MoneyGift;
use Cache;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Log;

/**
 * Class WechatService
 * @package App\Services
 */
class WechatService
{
    const TRADE_TYPE_APP = 'APP';

    const UNIFIED_ORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    const QUERY_ORDER_URL   = 'https://api.mch.weixin.qq.com/pay/orderquery';
    const REFUND_USRE_URL   = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    const WECHAT_JS_TICKET_CACHE_KEY = 'wechatJsApiTicket';

    /**
     * @return bool|mixed|\SimpleXMLElement|string
     */
    public static function signVerify()
    {
        $key  = env('WECHAT_MCH_KEY');
        $data = file_get_contents('php://input');
        Log::debug('wechat notify data:' . $data);

        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = @json_decode(@json_encode($data), 1);
        if (!isset($data['sign'])) {
            return false;
        }
        $sign = $data['sign'];
        unset($data['sign']);

        ksort($data);
        $query = urldecode(http_build_query($data, '&'));

        if (strtoupper(md5($query . '&key=' . $key)) == $sign) {
            return $data;
        }

        return false;
    }

    /**
     * @param $url
     * @param $data
     * @return mixed
     */
    public static function postXML($url, $data)
    {
        $key            = env('WECHAT_MCH_KEY');
        $appid          = env('WECHAT_APP_ID');
        $mchId          = env('WECHAT_MCH_ID');
        $data['appid']  = $appid;
        $data['mch_id'] = $mchId;

        ksort($data);
        $stringA        = urldecode(http_build_query($data, '&'));
        $stringSignTemp = $stringA . '&key=' . $key;
        $sign           = strtoupper(md5($stringSignTemp));
        $data['sign']   = $sign;
        $xmlData        = '<xml>';
        foreach ($data as $key => $value) {
            $xmlData .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $xmlData .= '</xml>';

        $client = new Client();
        $r      = $client->request('POST', $url, [
            'body' => $xmlData,
        ]);
        $body   = $r->getBody();

        $result = @json_decode(@json_encode(simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA)), 1);

        return $result;
    }

    /**
     * @param $data
     * @return string
     */
    public static function sign($data)
    {
        $key = env('WECHAT_MCH_KEY');

        ksort($data);
        $stringA        = urldecode(http_build_query($data, '&'));
        $stringSignTemp = $stringA . '&key=' . $key;

        return strtoupper(md5($stringSignTemp));
    }

    /**
     * @param $openid
     * @param $money
     * @return bool
     */
    public static function payUser($openid, $money)
    {
        $appid = env('WECHAT_APP_ID');
        $mchId = env('WECHAT_MCH_ID');
        $key   = env('WECHAT_MCH_KEY');

        $data = [
            'mch_appid'        => $appid,
            'mchid'            => $mchId,
            'nonce_str'        => md5(uniqid() . time()),
            'partner_trade_no' => Str::random(32),
            'openid'           => $openid,
            'check_name'       => 'NO_CHECK',
            'amount'           => $money,
            'desc'             => '群拍红包',
            'spbill_create_ip' => '127.0.0.1',
        ];

        ksort($data);
        $stringA        = urldecode(http_build_query($data, '&'));
        $stringSignTemp = $stringA . '&key=' . $key;
        $sign           = strtoupper(md5($stringSignTemp));
        $data['sign']   = $sign;
        $xmlData        = '<xml>';
        foreach ($data as $key => $value) {
            $xmlData .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $xmlData .= '</xml>';

        $sslCert = base_path() . '/app/cert/apiclient_cert.pem';
        $sslKey  = base_path() . '/app/cert/apiclient_key.pem';
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);

        $res = curl_exec($ch);
        if ($res !== false) {
            $result = @json_decode(@json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), 1);
            if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                return true;
            }
        }
        else {
            $error = curl_error($ch);
            \Log::info('wechat pay failed ' . $error);
            return false;
        }

        \Log::info('wechat pay failed ' . $res);
        return false;
    }

    /**
     * @param MoneyGift $parentMoneyGift
     * @return bool
     * @internal param $openId
     */
    public static function refundGroupShootMoney(MoneyGift $parentMoneyGift)
    {
        if ($parentMoneyGift->left_money == 0) {
            return false;
        }

        $key = env('WECHAT_MCH_KEY');

        $data = [
            'appid'         => env('WECHAT_APP_ID'),
            'mch_id'        => env('WECHAT_MCH_ID'),
            'nonce_str'     => md5(uniqid() . time()),
            'out_trade_no'  => $parentMoneyGift->out_trade_no,
            'out_refund_no' => $parentMoneyGift->generateOutRefundNo(),
            'total_fee'     => $parentMoneyGift->money,
            'refund_fee'    => $parentMoneyGift->left_money,
            'op_user_id'    => env('WECHAT_MCH_ID'),
        ];

        ksort($data);
        $stringA        = urldecode(http_build_query($data, '&'));
        $stringSignTemp = $stringA . '&key=' . $key;
        $sign           = strtoupper(md5($stringSignTemp));
        $data['sign']   = $sign;
        $xmlData        = '<xml>';
        foreach ($data as $key => $value) {
            $xmlData .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $xmlData .= '</xml>';

        $sslCert = base_path() . '/app/cert/apiclient_cert.pem';
        $sslKey  = base_path() . '/app/cert/apiclient_key.pem';
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::REFUND_USRE_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);

        $res = curl_exec($ch);
        if ($res !== false) {
            $result = @json_decode(@json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), 1);
            if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                return true;
            }
        }
        else {
            \Log::info('refund wechat failed' . json_encode($result));
            return false;
        }

        \Log::info('refund wechat failed' . json_encode($result));
        return false;
    }

    /**
     * @param MoneyGift $moneyGift
     * @return array|\Illuminate\Http\JsonResponse
     */
    public static function createUnifiedOrder(MoneyGift $moneyGift)
    {
        $data = [
            'body'             => '红包',
            'out_trade_no'     => $moneyGift->out_trade_no,
            'total_fee'        => $moneyGift->money,
            'spbill_create_ip' => app('request')->ip(),
            'notify_url'       => self::getUnifiedOrderNotifyUrl(),
            'nonce_str'        => self::getNonceStr(),
            'trade_type'       => self::TRADE_TYPE_APP,
        ];

        $result = WechatService::postXML(self::UNIFIED_ORDER_URL, $data);

        if ($result['return_code'] !== 'SUCCESS') {
            return Helper::response(['message' => 'get prepay_id failed'], 500);
        }

        $credential = [
            'appid'     => $result['appid'],
            'partnerid' => $result['mch_id'],
            'prepayid'  => $result['prepay_id'],
            'package'   => 'Sign=WXPay',
            'noncestr'  => self::getNonceStr(),
            'timestamp' => time(),
        ];

        $credential['sign'] = WechatService::sign($credential);

        return ['credential' => $credential, 'out_trade_no' => $moneyGift['out_trade_no']];
    }

    /**
     * Get the unifed notify url.
     * @return string
     */
    public static function getUnifiedOrderNotifyUrl()
    {
        return url('notify/moneygift/wechat', [], false);
    }

    /**
     * Get the nonce str.
     * @return string
     */
    public static function getNonceStr()
    {
        return md5(uniqid() . time());
    }

    /**
     * Get wechat pay order info.
     * @param $outTradeId
     * @return array
     */
    public static function queryOrderInfo($outTradeId)
    {
        $result = self::postXML(self::QUERY_ORDER_URL, [
            'nonce_str'    => self::getNonceStr(),
            'out_trade_no' => $outTradeId,
        ]);

        if ($result['return_code'] === 'SUCCESS' && $result['trade_state'] === 'SUCCESS') {
            return [['trade_state' => 'success'], 200];
        }

        return [['message' => 'query request failed'], 500];
    }

    /**
     * @return array
     */
    public static function getWechatJsApiSignPackage($url)
    {
        $appid = env('MP_APP_ID');

        $ticket    = self::getWechatJsApiTicket();
        $timestamp = time();
        $nonceStr  = md5(time() . uniqid());
        $url       = urldecode($url);
        $string    = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);

        $signPackage = [
            'appId'     => $appid,
            'nonceStr'  => $nonceStr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        return $signPackage;
    }

    /**
     * @return mixed|null
     */
    protected static function getWechatJsApiTicket()
    {
        if ($ticket = Cache::get(self::WECHAT_JS_TICKET_CACHE_KEY)) {
            return $ticket;
        }
        $token  = self::getWechatMpClientAccessToken();
        $url    = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $token . '&type=jsapi';
        $client = new Client();
        $r      = $client->request('GET', $url);
        $body   = $r->getBody();
        $data   = json_decode($body);

        if (!isset($data->ticket)) {
            return null;
        }

        Cache::put(self::WECHAT_JS_TICKET_CACHE_KEY, $data->ticket, 100);
        return $data->ticket;
    }

    /**
     * @return null
     */
    protected static function getWechatMpClientAccessToken()
    {
        $appid  = env('MP_APP_ID');
        $secret = env('MP_APP_SECRET');

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;

        $client = new Client();
        $r      = $client->request('GET', $url);
        $body   = $r->getBody();
        $data   = json_decode($body, true);
        if ($data && isset($data['access_token'])) {
            $cacheKey = 'wechatMpAccessToken';
            Cache::put($cacheKey, $data['access_token'], 100);
            return $data['access_token'];
        }

        Log::error(sprintf('try to get wechat token failed ' . $body));
        return null;
    }

}
