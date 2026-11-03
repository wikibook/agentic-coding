<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * 입력 폼 표시
     */
    public function create()
    {
        // 세션에 확인 화면에서 돌아온 데이터가 있으면 가져옴
        $contact = session('contact_data', []);

        return view('contacts.create', compact('contact'));
    }

    /**
     * 확인 화면 표시
     */
    public function confirm(ContactRequest $request)
    {
        $data = $request->validated();

        // 세션에 유효성 검사를 마친 데이터를 저장
        session(['contact_data' => $data]);

        return view('contacts.confirm', ['contact' => $data]);
    }

    /**
     * 데이터 저장
     */
    public function store(Request $request)
    {
        // '뒤로' 버튼이 눌린 경우
        if ($request->has('back')) {
            return redirect()->route('contact.create');
        }

        // 세션에서 데이터 가져오기
        $data = session('contact_data');

        // 세션이 끊긴 경우에는 폼으로 리다이렉트
        if (!$data) {
            return redirect()->route('contact.create');
        }

        // DB 저장
        Contact::create($data);

        // 세션에서 확인 데이터를 삭제
        session()->forget('contact_data');

        return redirect()->route('contact.thanks');
    }

    /**
     * 완료 화면 표시
     */
    public function thanks()
    {
        return view('contacts.thanks');
    }
}
