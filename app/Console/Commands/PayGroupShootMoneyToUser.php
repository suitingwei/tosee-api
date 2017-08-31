<?php

namespace App\Console\Commands;

use App\Http\Controllers\V1\NotifyController;
use App\Models\MoneyGift;
use App\Services\PingPPService;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PayGroupShootMoneyToUser extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'groupshoot:pay';
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Pay money to users those who joined red bag group shoots';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @see NotifyController pingPPTransferSuccess
     * @return void
     */
    public function handle()
    {
        $moneyGifts = $this->getNeedToTransferMoneyGifts();

        if ($moneyGifts->count() == 0) {
            $this->warn('无需要发送红包用户' . PHP_EOL);
            return;
        }

        foreach ($moneyGifts as $moneyGift) {
            $this->info('向用户' . $moneyGift->owner_id . '发送金币共计:' . $moneyGift->money_total);
            try {
                PingPPService::transferMoneyToUser($moneyGift->owner, $moneyGift->money_total);
            } catch (\Exception $e) {
                $this->warn('向用户' . $moneyGift->owner_id . '发送金币共计:' . $moneyGift->money_total . ',失败,原因是' . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Get money gifts which should be transfer.
     * @return Collection
     */
    private function getNeedToTransferMoneyGifts()
    {
        $this->info('----------' . Carbon::now()->toDateTimeString() . '定时检测发送群拍红包(注:发放根据用户为单位,会一次发送其在app里收到的所有货币,1元起步)----------');
        return MoneyGift::with('owner')
                        ->where('status', MoneyGift::STATUS_TAKE_MONEY_CREATED)
                        ->selectRaw(DB::raw('sum(money) as money_total, owner_id'))
                        ->groupBy('owner_id')
                        ->havingRaw(DB::raw('money_total >= 100'))
                        ->get()
                        ->filter(function (MoneyGift $moneyGift) {
                            return $moneyGift->owner->canTransfer();
                        });
    }
}
