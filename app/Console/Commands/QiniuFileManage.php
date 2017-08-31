<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class QiniuFileManage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'qiniu:file';
     /**
     * The console command description.
     *
     * @var string
     */
     protected $description = 'qiniu file manage';
     /**
     * Create a new command instance.
     */
     public function __construct()
     {
         parent::__construct();
     }
     /**
     * Execute the console command.
     *
     * @return mixed
     */
     public function handle()
     {
         $accessKey = env('QINIU_ACCESS_KEY');
         $secretKey = env('QINIU_SECRET_KEY');
         $auth = new Auth($accessKey, $secretKey);
         $bucketMgr = new BucketManager($auth);
         // http://developer.qiniu.com/docs/v6/api/reference/rs/list.html#list-description
         // 要列取的空间名称
         $bucket = 'toseeapp-prod';
         // 要列取文件的公共前缀
         $prefix = '';
         // 上次列举返回的位置标记，作为本次列举的起点信息。
         $marker = '';
         // 本次列举的条目数
         $limit = 1000;
         // 列举文件
         list($iterms, $marker, $err) = $bucketMgr->listFiles($bucket, $prefix, $marker, $limit);
         if ($err !== null) {
             echo "\n====> list file err: \n";
             var_dump($err);
         } else {
             foreach( $iterms as $item ) {
                 $key = $item['key'];

            //     $bucketMgr->delete($bucket, $key);
                 echo $key;
             }
             var_dump($iterms);
         }
     }
 }
