@extends('layouts.app')

@section('title', '입력 내용 확인')
@section('header_title', '문의 폼')

@section('content')
<div class="card">
    <h2>입력 내용 확인</h2>

    <dl>
        <div class="confirm-item">
            <dt>이름</dt>
            <dd>{{ $contact['name'] }}</dd>
        </div>

        <div class="confirm-item">
            <dt>이메일 주소</dt>
            <dd>{{ $contact['email'] }}</dd>
        </div>

        <div class="confirm-item">
            <dt>제목</dt>
            <dd>{{ $contact['subject'] }}</dd>
        </div>

        <div class="confirm-item">
            <dt>본문</dt>
            <dd>{!! nl2br(e($contact['body'])) !!}</dd>
        </div>
    </dl>

    <form action="{{ route('contact.store') }}" method="POST">
        @csrf
        <div class="btn-group">
            <button type="submit" name="back" value="1" class="btn btn-secondary">뒤로</button>
            <button type="submit" class="btn btn-primary">전송</button>
        </div>
    </form>
</div>
@endsection
