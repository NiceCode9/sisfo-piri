<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCbtFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('cbt.cookie debug', [
            'path' => $request->path(),
            'has_cookie' => $request->cookies->has('cbt_auth_token'),
            'cookie' => $request->cookies->get('cbt_auth_token'),
            'bearer' => $request->bearerToken(),
        ]);

        if (! $request->bearerToken() && $request->cookies->has('cbt_auth_token')) {
            $request->headers->set('Authorization', 'Bearer '.$request->cookies->get('cbt_auth_token'));
        }

        return $next($request);
    }
}
