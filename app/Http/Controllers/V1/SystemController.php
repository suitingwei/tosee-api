<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\Helper;

class SystemController extends Controller
{
    public function index()
    {
        $systems = [
            'drution'                => 15,
            'custom_topic_tip'       => '举起你的手机，拍摄你所见到的，便可制作成互动竞猜游戏！',
            'show_wechat_login'      => 1,
            'groupshoot_count_new'   => 100,
            'groupshoot_count_merge' => 10,
            'phone_login'            => 1, //whether allow user use phone number to login
        ];

        return Helper::response($systems);
    }

    /**
     * User agrement protocol.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function agreement()
    {
        return view('agreement');
    }
}
