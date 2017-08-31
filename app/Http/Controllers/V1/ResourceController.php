<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Qiniu\Auth;

class ResourceController extends Controller
{
    public function token(Request $request)
    {
        $rule = ['return_body' => 'required|json',];

        $validator = Validator::make(
            $request->only(array_keys($rule)),
            $rule
        );
        if ($validator->fails()) {
            return Helper::responseInvalidParameters();
        }

        $qiniuTokenKey = 'qiniu:token_upload';
        if (!$token = Redis::get($qiniuTokenKey)) {
            $accessKey = env('QINIU_ACCESS_KEY');
            $secretKey = env('QINIU_SECRET_KEY');
            $bucket    = env('QINIU_APP_BUCKET');

            $auth = new Auth($accessKey, $secretKey);

            $ttlSec = 3600;
            $token  = $auth->uploadToken($bucket, null, $ttlSec, [
                'returnBody'       => $request->input('return_body'),
                'callbackUrl'      => Helper::url('notify/qiniu/upload', env('API_HOST')),
                'callbackBody'     => 'key=$(key)',
                'callbackBodyType' => 'application/json',
            ]);

            if ($token) {
                Redis::set('qiniu:token_upload', $token);
                Redis::expire('qiniu:token_upload', 3000);
            }
        }

        return Helper::response(['token' => $token, ]);
    }
}
