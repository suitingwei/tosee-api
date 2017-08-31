<?php

namespace App\Http\Middleware;

use App\Services\Helper;
use Closure;

class VerifyAccess
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
        $sign = $request->get('sign');
        $timestamp = $request->get('timestamp');
        $appSecret = env('APP_SECRET');

        if (empty($sign) || empty($timestamp)) {
            return Helper::response(['message' => 'forbidden'], 403);
        }

        if ($sign != md5($timestamp.$appSecret)) {
            return Helper::response(['message' => 'forbidden'], 403);
        }

        return $next($request);
    }
}
