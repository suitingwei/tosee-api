<?php

namespace App\Console\Commands;

use App\Models\MoneyGift;
use App\Services\PingPPService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Class RefundGroupShootMoneyToUser
 * @package App\Console\Commands
 */
class RefundGroupShootMoneyToUser extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'groupshoot:refund';
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Refund money to users those who joined red bag group shoots';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $now = Carbon::now();
        $this->info("-----------------{$now->toDateTimeString()}定时检测退款未领取的群拍红包(注：只检测从创建到现在超过7天未领取的红包)--------------");

        $moneyGifts = MoneyGift::where([
            'status'    => MoneyGift::STATUS_SHARE_GIFT_PAID,
            'parent_id' => 0,
            'refunded'  => 0,
        ])->where('created_at', '>=', $now->subDays(7))->where('pingpp_charge_id', '!=', '')->get();

        if ($moneyGifts->count() == 0) {
            $this->info('无需要退款红包用户');
            return;
        }
        foreach ($moneyGifts as $moneyGift) {
            if ($moneyGift->left_money == 0) {
                $this->warn("群拍{$moneyGift->group_shoot_id}未领取金额为{$moneyGift->left_money},不予发放");
                continue;
            }
            try {
                $this->info("群拍{$moneyGift->group_shoot_id}未领取金额为{$moneyGift->left_money},开始退款");
                PingPPService::refundMoneyGift($moneyGift);
            } catch (\Exception $e) {
                $this->warn("群拍{$moneyGift->group_shoot_id}未领取金额为{$moneyGift->left_money},退款失败，原因是[PingPP调用失败:{$e->getMessage()}]");
                continue;
            }
        }
    }
}
