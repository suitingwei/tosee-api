<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Helper;
use App\Services\UserService;
use Closure;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $sign      = $request->get('sign');
        $timestamp = $request->get('timestamp');
        $token     = $request->get('token');
        $appSecret = env('APP_SECRET');


        if (empty($token)) {
            return Helper::response(['message' => 'token required'], 403);
        }

        #if ($sign != md5($timestamp.$appSecret.$token)) {
        #    return Helper::response(['message' => 'forbidden'], 403);
        #}

        if ($userId = UserService::checkToken($token)) {
            if (!User::find($userId)) {
                return Helper::response(['message' => 'user not existed'], 403);
            }

            $request->uid = $userId;
        } else {
            return Helper::response(['message' => 'token error'], 403);
        }

        return $next($request);
    }
}
