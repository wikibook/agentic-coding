@extends('layouts.app')

@section('title', '전송 완료')
@section('header_title', '문의 폼')

@section('content')
<div class="card" style="text-align: center;">
    <h2>문의해 주셔서 감사합니다</h2>

    <p style="margin: 20px 0;">문의가 접수되었습니다.<br>내용을 확인한 뒤 연락드리겠습니다.</p>

    <a href="{{ route('contact.create') }}" class="btn btn-primary">처음으로 돌아가기</a>
</div>
@endsection
