<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * 관리 페이지 인증 컨트롤러
 */
class LoginController extends Controller
{
    /**
     * 로그인 화면 표시
     */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * 로그인 처리
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => ['required'],
        ], [
            'password.required' => '비밀번호를 입력해 주세요.',
        ]);

        if ($request->input('password') === config('app.admin_password')) {
            $request->session()->put('admin_authenticated', true);

            return redirect()->route('admin.contacts.index');
        }

        return back()->withErrors([
            'password' => '비밀번호가 올바르지 않습니다.',
        ]);
    }

    /**
     * 로그아웃 처리
     */
    public function logout(Request $request)
    {
        $request->session()->forget('admin_authenticated');

        return redirect()->route('admin.login');
    }
}
