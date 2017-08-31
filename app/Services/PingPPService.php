<?php

namespace App\Services;

use App\Mail\MoneyTransferError;
use App\Models\MoneyGift;
use App\Models\MoneyTransfer;
use App\Models\User;
use Mail;
use Pingpp\Charge;
use Pingpp\Pingpp;
use Pingpp\Transfer;

class PingPPService
{
    const CHANNEL_ALIPAY          = 'alipay';
    const CHANNEL_WECHAT          = 'wx';
    const TRANSFER_CHANNEL_WX_PUB = 'wx_pub';
    const TRANSFER_TYPE           = 'b2c';

    /**
     * @param MoneyGift $moneyGift
     * @param string    $channel
     * @return mixed
     */
    public static function createChargeForMoneyGift(MoneyGift $moneyGift)
    {
        Pingpp::setApiKey(env('PINGPP_KEY'));

        $charge = Charge::create([
            'order_no'  => $moneyGift->out_trade_no,
            'app'       => ['id' => env('PINGPP_APP_ID')],
            'channel'   => $moneyGift->channel,
            'amount'    => $moneyGift->money,
            'client_ip' => '127.0.0.1',
            'currency'  => 'cny',
            'subject'   => 'TOSEE红包',
            'body'      => 'TOSEE红包',
            'extra'     => [],
        ]);
        $moneyGift->update(['pingpp_charge_id' => $charge->id]);
        return $charge;
    }

    /**
     * @param MoneyGift $moneyGift
     */
    public static function refundMoneyGift(MoneyGift $moneyGift)
    {
        Pingpp::setApiKey(env('PINGPP_KEY'));

        $charge = Charge::retrieve($moneyGift->pingpp_charge_id);

        return $charge->refunds->create([
            'description' => '超时红包退款',
            'amount'      => $moneyGift->left_money,
        ]);
    }

    /**
     * Transfor user owned money gift.
     * @param User $user
     * @param      $money
     * @return mixed
     */
    public static function transferMoneyToUser(User $user, $money)
    {
        if ($user->hadUnFinishedMoneyTransfer()) {
            self::checkTooManyAttempts($user);
            return null;
        }

        return self::createMoneyTransferToUser($user, $money);
    }

    /**
     * get the charge result.
     * @param MoneyGift $moneyGift
     * @return Charge
     */
    public static function getChargeResultFromMoneyGift(MoneyGift $moneyGift)
    {
    }

    /**
     * Get the charge result from our own out trade no.
     * @param $outTradeId
     * @return Charge|\stdClass
     */
    public static function getChargeResultFromOutTradeNo($outTradeId)
    {
        Pingpp::setApiKey(env('PINGPP_KEY'));

        $moneyGift = MoneyGift::where('out_trade_no', $outTradeId)->first();

        if ($moneyGift && $moneyGift->pingpp_charge_id) {
            return Charge::retrieve($moneyGift->pingpp_charge_id);
        }
        return new \stdClass();
    }

    /**
     * Receive the transfer success event.
     */
    public static function handleTransferSuccessNotifyEvent()
    {
        $moneyTransfer = self::findMoneyTransferByNotifyEvent();

        $moneyTransfer->setTransferToSuccess();
    }

    /**
     * @return MoneyTransfer
     */
    private static function findMoneyTransferByNotifyEvent()
    {
        $moneyTransfer = MoneyTransfer::findByPingPPNotifyEvent(self::getPingPPNotifyEventObject());
        if (!$moneyTransfer) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            exit("fail");
        }

        \Log::info("[PingPPService-Transfer-Success]对用户{$moneyTransfer->open_id}的{$moneyTransfer->amount}转账成功,现在修改其所有领取红包的状态为一支付");
        return $moneyTransfer;
    }

    /**
     * Get pingpp notify event object.
     * @return \stdClass
     */
    private static function getPingPPNotifyEventObject()
    {
        $event = json_decode(file_get_contents("php://input"));
        if (!isset($event->type) || $event->type != 'transfer.succeeded') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            exit("fail");
        }

        \Log::info('ping++企业付款回调Transfer对象:' . json_encode($event));
        return $event;
    }

    private static function createMoneyTransferToUser(User $user, $money)
    {
        Pingpp::setApiKey(env('PINGPP_KEY'));

        $orderNumber = 'tr' . time() . $user->id;

        MoneyTransfer::create([
            'transfer_order_no' => $orderNumber,
            'amount'            => $money,
            'channel'           => self::TRANSFER_CHANNEL_WX_PUB,
            'type'              => self::TRANSFER_TYPE,
            'open_id'           => $user->openid,
            'user_id'           => $user->id,
        ]);

        return Transfer::create([
            'order_no'    => $orderNumber,
            'app'         => ['id' => env('PINGPP_APP_ID')],
            'recipient'   => $user->openid,
            'amount'      => $money,
            'currency'    => 'cny',
            'type'        => self::TRANSFER_TYPE,
            'channel'     => self::TRANSFER_CHANNEL_WX_PUB,
            'description' => 'TOSEE红包'
        ]);
    }

    /**
     * @param User $user
     */
    private static function checkTooManyAttempts(User $user)
    {
        $lastUnfinishedMoneyTransfer = $user->moneyTransfers()->where('status', MoneyTransfer::TRANSFER_WAITING)->first();

        $lastUnfinishedMoneyTransfer->increment('attempt_times');

        if ($lastUnfinishedMoneyTransfer->attempt_times > 3) {
            Mail::to('ufoddd001@gmail.com')->send(new MoneyTransferError());
        }
    }
}
