@extends('layouts.app')

@section('title', '관리 페이지 - 로그인')
@section('header_title', '관리 페이지 - 로그인')

@section('content')
    <div class="card">
        <h2>로그인</h2>

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf

            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" autofocus>
                @error('password')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">로그인</button>
        </form>
    </div>
@endsection
