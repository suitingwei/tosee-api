<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\MoneyGift;
use App\Services\ChargeService;
use App\Services\Helper;
use App\Services\PingPPService;
use App\Services\WechatService;
use Illuminate\Http\Request;
use Validator;

class MoneyGiftController extends Controller
{
    /**
     * Get money gift order info.
     * @param $outTradeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($outTradeId, Request $request)
    {
        if ($request->input('pay_via_pingpp') == 1) {
            $chargeResult = PingPPService::getChargeResultFromOutTradeNo($outTradeId);
            return Helper::response(['charge' => $chargeResult]);
        }

        list($data, $statusCode) = WechatService::queryOrderInfo($outTradeId);

        return Helper::response($data, $statusCode);
    }

    /**
     * Create new money gift.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $input = $request->only(array_keys(MoneyGift::$storeRules)),
            MoneyGift::$storeRules
        );

        if ($validator->fails()) {
            return Helper::response(['message' => 'invalid parameter'], 400);
        }

        if ($input['money'] / $input['numbers'] < 1) {
            return Helper::response(['message' => 'Money not enough to split.'], 400);
        }

        $moneyGift = MoneyGift::create([
            'owner_id'       => $request->input('user_id'),
            'group_shoot_id' => $input['group_shoot_id'],
            'money'          => $input['money'],
            'numbers'        => $input['numbers'],
            'out_trade_no'   => $request->input('user_id') . date('YmdHis') . 'mg',
            'status'         => MoneyGift::STATUS_SHARE_GIFT_CREATED,
            'type'           => $input['type'],
            'channel'        => $request->input('channel'),
        ]);

        return Helper::response(ChargeService::createChargeForMoneyGift($moneyGift, (boolean)$request->input('pay_via_pingpp', 0)));
    }
}
