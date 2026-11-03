@extends('layouts.app')

@section('title', '문의 상세')
@section('header_title', '관리 페이지')

@section('content')
<div class="card">
    <h2>문의 상세</h2>

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <dl>
        <div class="confirm-item">
            <dt>ID</dt>
            <dd>{{ $contact->id }}</dd>
        </div>

        <div class="confirm-item">
            <dt>이름</dt>
            <dd>{{ $contact->name }}</dd>
        </div>

        <div class="confirm-item">
            <dt>이메일 주소</dt>
            <dd>{{ $contact->email }}</dd>
        </div>

        <div class="confirm-item">
            <dt>제목</dt>
            <dd>{{ $contact->subject }}</dd>
        </div>

        <div class="confirm-item">
            <dt>본문</dt>
            <dd>{!! nl2br(e($contact->body)) !!}</dd>
        </div>

        <div class="confirm-item">
            <dt>수신 일시</dt>
            <dd>{{ $contact->created_at->format('Y/m/d H:i') }}</dd>
        </div>
    </dl>

    <form action="{{ route('admin.contacts.update', $contact) }}" method="POST" style="margin-top: 20px;">
        @csrf
        @method('PATCH')

        <div class="form-group">
            <label for="status">상태</label>
            <select id="status" name="status" style="width: auto;">
                @foreach(['신규', '대응 중', '해결 완료'] as $status)
                    <option value="{{ $status }}" @selected($contact->status === $status)>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-success">상태 업데이트</button>
            <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">목록으로 돌아가기</a>
        </div>
    </form>
</div>
@endsection
