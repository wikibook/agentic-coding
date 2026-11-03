@extends('layouts.app')

@section('title', '문의 관리')
@section('header_title', '관리 페이지')

@section('content')
<div class="card">
    <h2>문의 목록</h2>

    <!-- 필터링 폼 -->
    <form method="GET" action="{{ route('admin.contacts.index') }}" class="search-form">
        <div class="search-row">
            <div class="search-item">
                <label for="name">성명</label>
                <input type="text" id="name" name="name" value="{{ request('name') }}" placeholder="성명으로 검색">
            </div>
            <div class="search-item">
                <label for="date_from">시작일</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="search-item">
                <label for="date_to">종료일</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
        </div>
        <div class="search-row">
            <div class="search-item">
                <label>상태</label>
                <div class="checkbox-group">
                    @foreach(['신규', '대응 중', '해결 완료'] as $status)
                        <label class="checkbox-label">
                            <input type="checkbox" name="status[]" value="{{ $status }}"
                                {{ in_array($status, request('status', [])) ? 'checked' : '' }}>
                            {{ $status }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="search-actions">
                <button type="submit" class="btn btn-primary">검색</button>
                <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">클리어</a>
            </div>
        </div>
    </form>

    <!-- CSV 다운로드 -->
    <form method="GET" action="{{ route('admin.contacts.export') }}" class="csv-export-form">
        <div class="search-row">
            <div class="search-item">
                <label for="csv_status">상태</label>
                <select id="csv_status" name="status">
                    <option value="">전체</option>
                    <option value="신규">신규</option>
                    <option value="대응 중">대응 중</option>
                    <option value="해결 완료">해결 완료</option>
                </select>
            </div>
            <div class="search-actions">
                <button type="submit" class="btn btn-primary">CSV 다운로드</button>
            </div>
        </div>
    </form>

    @if($contacts->isEmpty())
        <p>문의가 아직 없습니다.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>이름</th>
                    <th>제목</th>
                    <th>상태</th>
                    <th>수신 일시</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contacts as $contact)
                    <tr>
                        <td>{{ $contact->id }}</td>
                        <td>
                            <a href="{{ route('admin.contacts.show', $contact) }}">
                                {{ $contact->name }}
                            </a>
                        </td>
                        <td>{{ $contact->subject }}</td>
                        <td>
                            @if($contact->status === '신규')
                                <span class="badge badge-new">{{ $contact->status }}</span>
                            @elseif($contact->status === '대응 중')
                                <span class="badge badge-progress">{{ $contact->status }}</span>
                            @else
                                <span class="badge badge-resolved">{{ $contact->status }}</span>
                            @endif
                        </td>
                        <td>{{ $contact->created_at->format('Y/m/d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $contacts->links() }}
    @endif
</div>
@endsection
