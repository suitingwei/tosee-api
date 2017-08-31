<?php

namespace App\Models;

use App\Services\PushService;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MoneyTransfer
 * @property mixed open_id
 * @property mixed amount
 * @property mixed user_id
 * @property User  user
 * @package App\Models
 * @mixin \Eloquent
 */
class MoneyTransfer extends Model
{
    const TRANSFER_WAITING = -1;
    const TRANSFER_FAILED  = 0;
    const TRANSFER_SUCCESS = 1;

    public $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @param $pingppEventObject
     * @return mixed
     */
    public static function findByPingPPNotifyEvent($pingppEventObject)
    {
        return self::where('transfer_order_no', $pingppEventObject->data->object->order_no)->first();
    }

    /**
     * Set this money transfer record to success.
     */
    public function setTransferToSuccess()
    {
        $this->update(['status' => self::TRANSFER_SUCCESS]);

        MoneyGift::where(['owner_id' => $this->user_id, 'status' => MoneyGift::STATUS_TAKE_MONEY_CREATED])->update(['status' => MoneyGift::STATUS_TAKE_MONEY_PAID]);

        PushService::pushUserGroupShootMoneyPaid($this->user);
    }
}
