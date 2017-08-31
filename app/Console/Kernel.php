<?php

namespace App\Console;

use App\Console\Commands\AddResources;
use App\Console\Commands\PayGroupShootMoneyToUser;
use App\Console\Commands\QiniuFileManage;
use App\Console\Commands\RefundGroupShootMoneyToUser;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        QiniuFileManage::class,
        PayGroupShootMoneyToUser::class,
        RefundGroupShootMoneyToUser::class,
        AddResources::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('groupshoot:refund')->weekly()->appendOutputTo(env('GROUPSHOOT_REFUND_LOG_PATH'));
        #$schedule->command('groupshoot:pay')->everyFiveMinutes()->appendOutputTo(env('GROUPSHOOT_PAY_LOG_PATH'));
    }
}
