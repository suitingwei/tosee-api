<?php

namespace App\Services;

require env('APP_PATH') . "/Sdks/aliyun-php-sdk-core/Config.php";

use App\Models\User;
use DefaultAcsClient;
use DefaultProfile;
use Push\Request\V20150827 as Push;

/**
 * Class PushService
 * @package App\Services
 */
class PushService
{
    const ACCESSKEY_ID  = "vpCnE4RdoPSKCcOo";
    const ACCESS_SECRET = "ipBG1DI7Dpz1w8nzV934vNdSuSY70q";
    const APP_KEY       = '23708878';

    const TARGET_SEPECFIC_DEVICE = 'device';
    const TARGET_ALL             = 'all';

    const DEVICE_TYPE_IOS     = 0;
    const DEVICE_TYPE_ANDROID = 1;
    const DEVICE_TYPE_ALL     = 3;

    const NOT_STORE_OFFLINE = "false";
    const STORE_OFFLINE     = "true";

    const PUSH_TYPE_NOTIFICATION = 1;
    const PUSH_TYPE_MESSAGE      = 0;

    /**
     * @var PushService
     */
    public static $instance;

    /**
     * @var DefaultAcsClient
     */
    private $client = null;
    /**
     * @var Push\PushRequest
     */
    private $request = null;

    /**
     * PushService constructor.
     */
    public function __construct()
    {
        $this->initClient();

        $this->initRequest();
    }

    /**
     *
     */
    private function initRequest()
    {
        $this->request = new Push\PushRequest();
        $this->request->setAppKey(self::APP_KEY);
        $this->request->setStoreOffline(self::NOT_STORE_OFFLINE);
        $this->request->setDeviceType(self::DEVICE_TYPE_IOS);
        $this->request->setType(self::PUSH_TYPE_NOTIFICATION);

        //sepecific settings for ios
//        $this->request->setiOSBadge("1");
        $this->request->setiOSMusic("default");

        //Sepecific settings for android.
        // 点击通知后动作,1:打开应用 2: 打开应用Activity 3:打开 url 4 : 无跳转逻辑
        //$this->request->setAndroidOpenType("3");
        // Android收到推送后打开对应的url,仅仅当androidOpenType=3有效
        //$this->request->setAndroidOpenUrl('');
    }

    /**
     * Get pusher instance.
     * @return PushService
     */
    public static function getPusher()
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new self;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function chooseReceiver(User $user)
    {
        $this->request->setTarget(self::TARGET_SEPECFIC_DEVICE);
        $this->request->setTargetValue($user->aliyun_token);
        return $this;
    }

    /**
     * Choose all users.
     */
    public function chooseAll()
    {
        $this->request->setTarget(self::TARGET_ALL);
        $this->request->setTargetValue(self::TARGET_ALL);
        $this->request->setDeviceType(self::DEVICE_TYPE_ALL);
        return $this;
    }

    /**
     * @param string $env
     * @return $this
     */
    public function setApnsEnv($env = 'DEV')
    {
        $this->request->setApnsEnv($env);
        return $this;
    }

    /**
     * @return bool
     */
    public function setStoreOffLine()
    {
        $this->request->setStoreOffline("false"); // 离线消息是否保存,若保存, 在推送时候，用户即使不在线，下一次上线则会收到
        return true;
    }

    /**
     * @param $title
     */
    public function setTitle($title)
    {
        $this->request->setTitle($title);
        return $this;
    }

    /**
     * @param $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->request->setBody($body); // 消息的内容
        $this->request->setSummary($body); // 通知的摘要
        return $this;
    }

    /**
     * @param array $extra
     * @return $this
     */
    public function setExtra($extra = [])
    {
        $extra = json_encode($extra);
        $this->request->setiOSExtParameters($extra);
        //$this->request->setAndroidExtParameters($extra);
        return $this;
    }

    /**
     * @return \SimpleXMLElement|boolean
     */
    public function push()
    {
        try {
            $response = $this->client->getAcsResponse($this->request);
        } catch (\Exception $exception) {
            return false;
        }
        return $response;
    }

    /**
     * Init the client.
     */
    private function initClient()
    {
        $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", self::ACCESSKEY_ID, self::ACCESS_SECRET);
        $this->client   = new DefaultAcsClient($iClientProfile);
    }

    /**
     * @param User $user
     * @return mixed|\SimpleXMLElement
     */
    public static function pushUserGroupShootMoneyPaid(User $user)
    {
        $title = '群拍参与奖励已入账微信账户,请查收';
        return self::getPusher()->chooseReceiver($user)->setBody($title)->setTitle($title)->push();
    }

    /**
     * @param $users
     * @return $this
     */
    public function chooseReceivers($users)
    {
        if (is_array($users)) {
            $users = collect($users);
        }

        $targetValueTokens = $users->filter(function (User $user) {
            return $user->aliyun_token;
        })->implode('aliyun_token', ',');

        $this->request->setTarget(self::TARGET_SEPECFIC_DEVICE);
        $this->request->setTargetValue($targetValueTokens);
        return $this;
    }

}

