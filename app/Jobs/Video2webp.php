<?php

namespace App\Jobs;

use App\Services\Helper;
use Log;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Video2webp extends Job
{
    protected $callbackBody;

    /**
     * Create a new job instance.
     */
    public function __construct($body)
    {
        //
        $this->callbackBody = $body;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        //todo
        Log::info('[qiniu video2webp] find job: ' . $this->callbackBody);
        if (preg_match('/key="(.*?)"/', $this->callbackBody, $matchs) !== false) {
            $callbackKey = $matchs[1];

            $videoUrl = Helper::url($callbackKey, env('QINIU_VIDEO_DOMAIN'));
            $tmpDir   = '/tmp/' . md5($videoUrl);

            $cmd = dirname(__FILE__) . '/video2webp.sh ' . $videoUrl . ' ' . $tmpDir;
            Log::info('gif处理');

            Log::info('[qiniu video2webp] make cmd: ' . $cmd);

            exec($cmd, $output, $return);
            if ($return == 0) {
                // 需要填写你的 Access Key 和 Secret Key
                $accessKey = env('QINIU_ACCESS_KEY');
                $secretKey = env('QINIU_SECRET_KEY');
                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);
                // 要上传的空间
                $bucket = env('QINIU_APP_BUCKET');
                // 生成上传 Token
                $token = $auth->uploadToken($bucket);
                // 要上传文件的本地路径
                $filePath = $tmpDir . '/a.webp';
                // 上传到七牛后保存的文件名s
                $key = 'webp/' . $callbackKey;
                // 初始化 UploadManager 对象并进行文件的上传。
                $uploadMgr = new UploadManager();
                // 调用 UploadManager 的 putFile 方法进行文件的上传。
                list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
                if ($err !== null) {
                    Log::info('[qiniu webp upload] upload failed');
                }
                else {
                    Log::info('[qiniu webp upload] ' . $key . ' upload success');

                    // 生成上传 Token
                    $token = $auth->uploadToken($bucket);
                    // 要上传文件的本地路径
                    $filePath = $tmpDir . '/a.gif';
                    // 上传到七牛后保存的文件名s
                    $key = 'gif/' . $callbackKey;
                    // 初始化 UploadManager 对象并进行文件的上传。
                    $uploadMgr = new UploadManager();
                    // 调用 UploadManager 的 putFile 方法进行文件的上传。
                    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

                    if ($err !== null) {
                        Log::info('[qiniu gif upload] upload failed');
                    }
                    else {
                        Log::info('[qiniu gif upload] ' . $key . ' upload success');
                        unlink($tmpDir . '/a.gif');
                        unlink($tmpDir . '/a.webp');
                        rmdir($tmpDir);
                    }
                }
            }
            else {
                Log::info('[qiniu webp upload] cmd error: ' . json_encode($output));
            }
        }
    }
}
