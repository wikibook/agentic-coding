<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    /**
     * 문의 목록 표시
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        // 성명으로 부분 일치 검색
        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }

        // 시작일로 필터링
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        // 종료일로 필터링
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // 상태로 필터링
        if ($request->filled('status')) {
            $query->whereIn('status', $request->input('status'));
        }

        $contacts = $query->latest()->paginate(10)->appends($request->query());

        return view('admin.contacts.index', compact('contacts'));
    }

    /**
     * CSV 내보내기
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Contact::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $contacts = $query->latest()->get();

        $callback = function () use ($contacts) {
            // UTF-8 BOM
            echo "\xEF\xBB\xBF";

            $handle = fopen('php://output', 'w');

            // 헤더 행
            fputcsv($handle, ['ID', '이름', '이메일 주소', '제목', '상태', '수신 일시']);

            // 데이터 행
            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->id,
                    $contact->name,
                    $contact->email,
                    $contact->subject,
                    $contact->status,
                    $contact->created_at,
                ]);
            }

            fclose($handle);
        };

        $filename = 'contacts_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 문의 상세 표시
     */
    public function show(Contact $contact)
    {
        return view('admin.contacts.show', compact('contact'));
    }

    /**
     * 상태 업데이트
     */
    public function update(Request $request, Contact $contact)
    {
        $request->validate([
            'status' => ['required', 'in:신규,대응 중,해결 완료'],
        ]);

        $contact->update([
            'status' => $request->input('status'),
        ]);

        return redirect()
            ->route('admin.contacts.show', $contact)
            ->with('success', '상태를 업데이트했습니다.');
    }
}
