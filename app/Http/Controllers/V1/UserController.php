<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Helper;
use App\Services\SmsService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

/**
 * Class UserController
 * @package App\Http\Controllers\V1
 */
class UserController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signin(Request $request)
    {
        $phoneNumber = $request->input('mobile', null);
        $password    = $request->input('password', null);

        if (UserService::isTestAccount($phoneNumber, $password)) {
            return UserService::fakeSignIn();
        }

        if (empty($phoneNumber) || empty($password)) {
            return Helper::responseInvalidParameters();
        }

        if (!($user = User::where('mobile', $phoneNumber)->first())) {
            return Helper::response(['message' => 'mobile not found'], 1002);
        }

        if (!Hash::check($password, $user->password)) {
            return Helper::response(['message' => 'Wrong password '], 1001);
        }

        return UserService::signin($user);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->only(array_keys(User::$storeRules)), User::$storeRules);

        if ($validator->fails()) {
            return Helper::responseInvalidParameters();
        }

        $phoneNumber = $request->input('mobile');
        $nickname    = $request->input('nickname');

        if (!SmsService::validatePhoneCode($phoneNumber, $request->input('code'))) {
            return Helper::response(['message' => 'mobile code invalid.'], 1007);
        }

        if (User::where('mobile', $phoneNumber)->orWhere('nickname', $nickname)->first()) {
            return Helper::response(['message' => 'Info duplicated'], 1005);
        }

        return UserService::signUp($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signinWechat(Request $request)
    {
        $validator = Validator::make(
            $request->only(array_keys(User::$wechatRegisterRules)),
            User::$wechatRegisterRules
        );

        if ($validator->fails()) {
            return Helper::responseInvalidParameters();
        }

        $unionId = $request->input('unionid');
        if ($userInfo = User::where('unionid', $unionId)->first()) {
            User::where('unionid', $unionId)->update(['openid' => $request->input('openid')]);
            return UserService::signin($userInfo);
        }

        $user = User::create([
            'unionid'  => $unionId,
            'openid'   => $request->input('openid'),
            'nickname' => $request->input('nickname'),
            'avatar'   => $request->input('avatar'),
            'sex'      => $request->input('sex'),
        ]);

        return UserService::signin($user);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function signout(Request $request)
    {
        return UserService::signout($request->get('access_token'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sms(Request $request)
    {
        if (!($phoneNumber = $request->input('mobile'))) {
            return Helper::response(['message' => 'phone number can not be empty'], 400);
        }

        if (User::where('mobile', $phoneNumber)->first()) {
            return Helper::response(['message' => 'mobile is used'], 1005);
        }

        $smsCodeKey = SmsService::getCacheKey($phoneNumber);
        if (($ttl = Redis::ttl('laravel:' . $smsCodeKey) >= 840)) {
            return Helper::response(['message' => 'do not repeat the request'], 1006);
        }

        $code = rand(1000, 9999);
        $body = SmsService::send($phoneNumber, '【ToSee】您的注册验证码是 ' . $code);
        if ($body === false) {
            return Helper::response(['message' => 'sms send failed'], 1003);
        }

        $body = json_decode($body);
        if ($body->code != 0) {
            return Helper::response(['message' => 'sms send failed'], 1003);
        }

        Cache::put($smsCodeKey, $code, 15);

        return Helper::response(['code' => $code,]);
    }

    /**
     * Bind the aliyun push token.
     * @param Request $request
     * @param         $userId
     * @return \Illuminate\Http\JsonResponse
     * @internal param User $user
     */
    public function bindPushToken(Request $request, $userId)
    {
        $user = User::find($userId);

        $user->update(['aliyun_token' => $request->input('aliyun_token')]);

        return Helper::response();
    }
}
