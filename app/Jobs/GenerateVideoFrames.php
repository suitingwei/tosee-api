<?php

namespace App\Jobs;

use App\Models\GroupShoot;
use App\Services\QiniuService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Qiniu\Auth;
use Qiniu\Processing\PersistentFop;
use function Qiniu\base64_urlSafeEncode;

class GenerateVideoFrames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $groupShoot;

    /**
     * Create a new job instance.
     *
     * @param GroupShoot $groupShoot
     */
    public function __construct(GroupShoot $groupShoot)
    {
        $this->groupShoot = $groupShoot;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $accessKey = env('QINIU_ACCESS_KEY');
        $secretKey = env('QINIU_SECRET_KEY');
        $bucket    = env('QINIU_APP_BUCKET');

        $auth     = new Auth($accessKey, $secretKey);
        $key      = $this->groupShoot->original_video_key;
        $pipeline = 'video-watermark';
        $pfop     = new PersistentFop($auth, $bucket, $pipeline);

        $fops = $this->getSampleConfig();
        list($id, $err) = $pfop->execute($key, $fops);

        if ($err) {
            Log::info('frames' . $err);
        }
        Log::info('generate video sample frames' . $this->groupShoot->id);
    }

    private function getSampleConfig()
    {
        $videoDurationSeconds = QiniuService::getVideoDuration($this->groupShoot->original_video_key);
        $step                 = floor((($videoDurationSeconds - 1.3) / 4));
        $videoDurationSeconds = floor($videoDurationSeconds);

        return "vsample/jpg/ss/1/t/{$videoDurationSeconds}/interval/{$step}/pattern/" . base64_urlSafeEncode($this->groupShoot->original_video_key. '-vframe-$(count)');
    }
}
