<?php

namespace App\Services\RedBagServices;

use App\Models\MoneyGift;

/**
 * Class RedBagService
 * @package App\Services\RedBagService
 */
class RedBagService
{
    /**
     * @param $total
     * @param $num
     *
     * @return array
     */
    public static function generateRandomRedBags($total, $num)
    {
        $max = $total / 3;
        $min = 0.01;

        #总共要发的红包金额，留出一个最大值;
        $total        = $total - $max;
        $reward       = new BasicLuckRedBagAlgorithmService();
        $result_merge = $reward->splitReward($total, $num, $max, $min);
        sort($result_merge);
        $result_merge[1] = $result_merge[1] + $result_merge[0];
        $result_merge[0] = $max * 100;
        foreach ($result_merge as &$v) {
            $v = floor($v) / 100;
        }
        Shuffle($result_merge);
        return $result_merge;
    }

    /**
     * @param $totalInCent
     * @param $number
     * @linke http://www.helloweba.com/view-blog-313.html
     *
     * @return array
     * @return array
     */
    public static function generateRandomRedBagsUseRand($totalInCent, $number)
    {
        $min    = 1;
        $moneys = [];
        for ($i = 1; $i < $number; $i++) {
            $safe_total  = ($totalInCent - ($number - $i) * $min) / ($number - $i);//随机安全上限
            $safe_total  = $safe_total < 1 ? 1 : $safe_total;
            $money       = mt_rand($min, $safe_total);
            $totalInCent = $totalInCent - $money;

            $moneys[] = $money;
            echo '第' . $i . '个红包：' . $money . ' 分 ，余额：' . $totalInCent . ' 分 ';
        }
        $moneys [] = $totalInCent;
        echo '第' . $number . '个红包：' . $totalInCent . ' 分,余额：' . ' 0分 ';
        return $moneys;
    }

    public static function createRedBagsForMoneyGift(MoneyGift $moneyGift)
    {
        if ($moneyGift->isLucky()) {
            return self::generateRandomRedBagsUseRand($moneyGift->money, $moneyGift->numbers);
        }

        return array_fill(0, $moneyGift->numbers, $moneyGift->money / $moneyGift->numbers);
    }
}
