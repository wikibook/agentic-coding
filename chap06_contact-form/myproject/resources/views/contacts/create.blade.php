@extends('layouts.app')

@section('title', '문의')
@section('header_title', '문의 폼')

@section('content')
<div class="card">
    <h2>문의</h2>

    <form action="{{ route('contact.confirm') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">이름 <span style="color: #e74c3c;">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $contact['name'] ?? '') }}">
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">이메일 주소 <span style="color: #e74c3c;">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $contact['email'] ?? '') }}">
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="subject">제목 <span style="color: #e74c3c;">*</span></label>
            <input type="text" id="subject" name="subject" value="{{ old('subject', $contact['subject'] ?? '') }}">
            @error('subject')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="body">본문 <span style="color: #e74c3c;">*</span></label>
            <textarea id="body" name="body">{{ old('body', $contact['body'] ?? '') }}</textarea>
            @error('body')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">확인 화면으로</button>
        </div>
    </form>
</div>
@endsection
