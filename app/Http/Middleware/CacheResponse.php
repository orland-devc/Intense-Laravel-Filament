<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheResponse
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET')) {
            $cacheKey = 'page_' . sha1($request->fullUrl());

            if (Cache::has($cacheKey)) {
                return response(Cache::get($cacheKey));
            }

            $response = $next($request);

            Cache::put($cacheKey, $response->getContent(), 3600);

            return $response;
        }

        return $next($request);
    }
}