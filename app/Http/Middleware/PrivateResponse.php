<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivateResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy',
            "default-src 'none'; style-src 'self'; font-src 'self'; img-src 'self' data:; ".
            "script-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
        return $response;
    }
}
