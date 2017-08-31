<?php

namespace App\Services;

class ChargeService
{
    /**
     * Create charge for the money gift.
     * @param      $moneyGift
     * @param bool $viaPingPP
     * @return array|\Illuminate\Http\JsonResponse
     */
    public static function createChargeForMoneyGift($moneyGift, $viaPingPP = true)
    {
        if ($viaPingPP) {
            return ['charge' => PingPPService::createChargeForMoneyGift($moneyGift)];
        }
        return WechatService::createUnifiedOrder($moneyGift);
    }
}
