<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CSV 내보내기 기능 테스트
 */
class CSVExportTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): void
    {
        config(['app.admin_password' => 'test-password']);
        $this->post('/admin/login', ['password' => 'test-password']);
    }

    // ----------------------------------------
    // CSV 다운로드
    // ----------------------------------------

    /** @test CSV_파일이_다운로드될_것 */
    public function test_CSV_파일이_다운로드될_것(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get('/admin/contacts/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload();
    }

    /** @test 관리자만_사용할_수_있을_것 */
    public function test_관리자만_사용할_수_있을_것(): void
    {
        $response = $this->get('/admin/contacts/export');

        $response->assertRedirect(route('admin.login'));
    }

    /** @test CSV에_헤더_행이_포함될_것 */
    public function test_CSV에_헤더_행이_포함될_것(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts/export');

        $response->assertStatus(200);

        $content = $response->streamedContent();
        $lines = explode("\n", $content);
        // BOM을 제거하고 헤더 행을 검증
        $header = ltrim($lines[0], "\xEF\xBB\xBF");
        $columns = str_getcsv($header);

        $this->assertEquals('ID', $columns[0]);
        $this->assertEquals('이름', $columns[1]);
        $this->assertEquals('이메일 주소', $columns[2]);
        $this->assertEquals('제목', $columns[3]);
        $this->assertEquals('상태', $columns[4]);
        $this->assertEquals('수신 일시', $columns[5]);
    }

    /** @test 상태로_필터링할_수_있을_것 */
    public function test_상태로_필터링할_수_있을_것(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '신규 사용자',
            'email' => 'new@example.com',
            'subject' => '제목A',
            'body' => '본문A',
            'status' => '신규',
        ]);

        Contact::create([
            'name' => '대응 중 사용자',
            'email' => 'progress@example.com',
            'subject' => '제목B',
            'body' => '본문B',
            'status' => '대응 중',
        ]);

        Contact::create([
            'name' => '해결 완료 사용자',
            'email' => 'done@example.com',
            'subject' => '제목C',
            'body' => '본문C',
            'status' => '해결 완료',
        ]);

        $response = $this->get('/admin/contacts/export?status=대응 중');

        $response->assertStatus(200);

        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", $content), fn ($line) => $line !== '');
        // 헤더 행 + 데이터 1행 = 2행
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('대응 중 사용자', $content);
        $this->assertStringNotContainsString('신규 사용자', $content);
        $this->assertStringNotContainsString('해결 완료 사용자', $content);
    }

    /** @test 문자_코드가_UTF8의_BOM_포함일_것 */
    public function test_문자_코드가_UTF8의_BOM_포함일_것(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts/export');

        $response->assertStatus(200);

        $content = $response->streamedContent();
        // UTF-8 BOM: 0xEF 0xBB 0xBF
        $this->assertTrue(
            str_starts_with($content, "\xEF\xBB\xBF"),
            'CSV가 UTF-8 BOM 포함이 아닙니다'
        );
    }

    // ----------------------------------------
    // CSV 다운로드 UI(관리 화면 목록)
    // ----------------------------------------

    /** @test 목록_화면에_CSV_다운로드_버튼이_표시된다 */
    public function test_목록_화면에_CSV_다운로드_버튼이_표시된다(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        $response->assertSee('CSV 다운로드');
    }

    /** @test CSV_다운로드_버튼의_링크가_내보내기_URL이다 */
    public function test_CSV_다운로드_버튼의_링크가_내보내기_URL이다(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        $response->assertSee(route('admin.contacts.export'), false);
    }

    /** @test CSV_다운로드란에_상태_선택_드롭다운이_있다 */
    public function test_CSV_다운로드란에_상태_선택_드롭다운이_있다(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        $content = $response->getContent();
        // CSV 내보내기 폼 안에 select가 있다
        $this->assertMatchesRegularExpression(
            '/<form[^>]*contacts\/export[^>]*>.*?<select[^>]*name=["\']status["\'][^>]*>.*?<\/form>/s',
            $content
        );
    }

    /** @test CSV_다운로드_드롭다운에_상태_선택지가_있다 */
    public function test_CSV_다운로드_드롭다운에_상태_선택지가_있다(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        $content = $response->getContent();
        // CSV 내보내기 폼 안에 각 상태의 option이 있다
        $this->assertMatchesRegularExpression(
            '/<form[^>]*contacts\/export[^>]*>.*?<option[^>]*>신규<\/option>.*?<\/form>/s',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/<form[^>]*contacts\/export[^>]*>.*?<option[^>]*>대응 중<\/option>.*?<\/form>/s',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/<form[^>]*contacts\/export[^>]*>.*?<option[^>]*>해결 완료<\/option>.*?<\/form>/s',
            $content
        );
    }

    /** @test CSV_다운로드_드롭다운에_전체_선택지가_있다 */
    public function test_CSV_다운로드_드롭다운에_전체_선택지가_있다(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<form[^>]*contacts\/export[^>]*>.*?<option[^>]*value=["\']["\'][^>]*>.*?전체.*?<\/option>.*?<\/form>/s',
            $content
        );
    }

    // ----------------------------------------
    // CSV 다운로드(데이터 검증)
    // ----------------------------------------

    /** @test 데이터베이스의_레코드가_CSV에_포함될_것 */
    public function test_데이터베이스의_레코드가_CSV에_포함될_것(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '김민준',
            'email' => 'minjun@example.com',
            'subject' => '문의 건',
            'body' => '본문입니다',
            'status' => '신규',
        ]);

        $response = $this->get('/admin/contacts/export');

        $response->assertStatus(200);

        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", $content), fn ($line) => $line !== '');
        // 헤더 행 + 데이터 1행 = 2행
        $this->assertCount(2, $lines);

        // BOM을 제거하고 파싱
        $cleanContent = ltrim($content, "\xEF\xBB\xBF");
        $rows = array_filter(explode("\n", $cleanContent), fn ($line) => $line !== '');
        $data = str_getcsv($rows[1]);

        $this->assertEquals($contact->id, $data[0]);
        $this->assertEquals('김민준', $data[1]);
        $this->assertEquals('minjun@example.com', $data[2]);
        $this->assertEquals('문의 건', $data[3]);
        $this->assertEquals('신규', $data[4]);
        $this->assertEquals($contact->created_at, $data[5]);
    }
}
