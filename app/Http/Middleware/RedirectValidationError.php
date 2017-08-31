<?php

namespace App\Http\Middleware;

use App\Services\Helper;
use Closure;

class RedirectValidationError
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            return Helper::response(['message' => 'invalid parameter'], 400);
        }
    }
}
