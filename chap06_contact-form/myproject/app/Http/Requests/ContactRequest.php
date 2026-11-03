<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * 요청 인가
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 유효성 검사 규칙
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }

    /**
     * 유효성 검사 메시지(한국어)
     */
    public function messages(): array
    {
        return [
            'name.required' => '이름을 입력해 주세요.',
            'name.max' => '이름은 255자 이내로 입력해 주세요.',
            'email.required' => '이메일 주소를 입력해 주세요.',
            'email.email' => '올바른 이메일 주소 형식으로 입력해 주세요.',
            'email.max' => '이메일 주소는 255자 이내로 입력해 주세요.',
            'subject.required' => '제목을 입력해 주세요.',
            'subject.max' => '제목은 255자 이내로 입력해 주세요.',
            'body.required' => '본문을 입력해 주세요.',
        ];
    }
}
