<?php

namespace App\Http\Middleware;

use Closure;

class Etag
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if ($request->isMethod('GET')) {
            $etag = md5($response->getContent());
            $requestETag = str_replace('"', '', $request->getETags());
            if ($requestETag && $requestETag[0] == $etag) {
                $response->setNotModified();
            }
            $response->setETag($etag);
        }

    //    $response->header('Content-type', 'application/json; charset=utf-8');
        return $response;
    }
}
