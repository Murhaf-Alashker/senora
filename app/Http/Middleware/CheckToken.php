<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return $next($request);
        }

        if(!auth()->guard('api')->check()) {
            return response()->json(['message' => 'لا تملك الصلاحية للقيام بهذا الفعل'],401);
        }

        return $next($request);
    }
}
