<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


/**
 * Class UserService
 * @package App\Services
 */
class UserService
{
    const TEST_PHONE    = 13718139810;
    const TEST_PASSWORD = 1234;

    /** * @param $user
     * @return \Illuminate\Http\JsonResponse
     */
    public static function signin($user)
    {
        $token = self::getToken($user);

        return Helper::response([
            'user_id'  => $user->id,
            'token'    => base64_encode($token),
            'nickname' => $user->nickname,
            'sex'      => $user->sex,
            'avatar'   => $user->avatar,
        ]);
    }

    /**
     * @param $user
     * @return string
     */
    public static function getToken($user)
    {
        $appSecret = env('APP_SECRET');
        $userId    = $user->id;
        $time      = time();
        $hash      = md5($user->id . $time . 'toseeapp' . $appSecret);

        return "$hash:$userId@$time";
    }

    /**
     * @param $token
     * @return bool
     */
    public static function checkToken($token)
    {
        $appSecret = env('APP_SECRET');
        $token     = base64_decode($token);

        $tokenData = explode(':', $token);
        $userData  = explode('@', $tokenData[1]);

        if (md5($userData[0] . $userData[1] . 'toseeapp' . $appSecret) == $tokenData[0]) {
            return $userData[0];
        }
        else {
            return false;
        }
    }

    /**
     * @param Request $request
     * @return User
     */
    public static function registerNewUser(Request $request)
    {
        return User::create([
            'mobile'   => $request->input('mobile'),
            'password' => Hash::make($request->input('password')),
            'nickname' => $request->input('nickname'),
            'avatar'   => $request->input('avatar'),
            'sex'      => $request->input('sex'),
        ]);
    }

    /**
     * Sign up a user.
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function signUp($request)
    {
        return self::signin(self::registerNewUser($request));
    }

    public static function fakeSignIn()
    {
        $fakeUser = User::where('mobile', self::TEST_PHONE)->first();
        $token    = self::getToken($fakeUser);

        return Helper::response([
            'user_id'  => $fakeUser->id,
            'token'    => base64_encode($token),
            'nickname' => $fakeUser->nickname,
            'sex'      => $fakeUser->sex,
            'avatar'   => $fakeUser->avatar,
        ]);
    }

    public static function isTestAccount($phone, $password)
    {
        return ($phone == self::TEST_PHONE) && ($password == self::TEST_PASSWORD);
    }
}
