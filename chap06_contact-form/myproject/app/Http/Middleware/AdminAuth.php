<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 관리 페이지의 인증 미들웨어
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 세션의 인증 플래그를 확인
        if (!$request->session()->get('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
