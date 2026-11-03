<?php

namespace Tests\Feature;

use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 문의 폼 기능 테스트
 *
 * 테스트케이스.md 에 기반한 전체 63건의 기능 테스트
 */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 1. 문의 폼 쪽
    // ========================================

    // ----------------------------------------
    // 1.1 입력 화면(GET /)
    // ----------------------------------------

    /** @test 1-1-1 입력_폼이_표시된다 */
    public function test_1_1_1_입력_폼이_표시된다(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('이름');
        $response->assertSee('이메일 주소');
        $response->assertSee('제목');
        $response->assertSee('본문');
    }

    /** @test 1-1-2 각_항목에_필수_표시가_있다 */
    public function test_1_1_2_각_항목에_필수_표시가_있다(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // 필수 표시(*)가 HTML에 포함되어 있는지 확인
        $response->assertSee('*', false);
    }

    // ----------------------------------------
    // 1.2 유효성 검사(POST /contact/confirm)
    // ----------------------------------------

    // --- 필수 검사 ---

    /** @test 1-2-1 전체_항목_미입력이면_오류_메시지가_표시된다 */
    public function test_1_2_1_전체_항목_미입력이면_오류_메시지가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '',
            'email' => '',
            'subject' => '',
            'body' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'body']);
    }

    /** @test 1-2-2 이름_미입력_시_오류_메시지가_표시된다 */
    public function test_1_2_2_이름_미입력_시_오류_메시지가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(
            '이름을 입력해 주세요.',
            session('errors')->get('name')[0]
        );
    }

    /** @test 1-2-3 이메일_주소_미입력이면_오류_메시지가_표시된다 */
    public function test_1_2_3_이메일_주소_미입력이면_오류_메시지가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => '',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            '이메일 주소를 입력해 주세요.',
            session('errors')->get('email')[0]
        );
    }

    /** @test 1-2-4 제목_미입력이면_오류_메시지가_표시된다 */
    public function test_1_2_4_제목_미입력이면_오류_메시지가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['subject']);
        $this->assertEquals(
            '제목을 입력해 주세요.',
            session('errors')->get('subject')[0]
        );
    }

    /** @test 1-2-5 본문_미입력이면_오류_메시지가_표시된다 */
    public function test_1_2_5_본문_미입력이면_오류_메시지가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '',
        ]);

        $response->assertSessionHasErrors(['body']);
        $this->assertEquals(
            '본문을 입력해 주세요.',
            session('errors')->get('body')[0]
        );
    }

    // --- 형식 검사 ---

    /** @test 1-2-6 이메일_주소_형식_부정(abc)이면_오류가_표시된다 */
    public function test_1_2_6_이메일_주소_형식_부정이면_오류가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'abc',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            '올바른 이메일 주소 형식으로 입력해 주세요.',
            session('errors')->get('email')[0]
        );
    }

    /** @test 1-2-7 이메일_주소_형식_부정(@없음)이면_오류가_표시된다 */
    public function test_1_2_7_이메일_주소_형식_부정_골뱅이없음_이면_오류가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test.example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test 1-2-8 이메일_주소가_정상_형식이면_유효성_검사_오류가_나지_않는다 */
    public function test_1_2_8_이메일_주소가_정상_형식이면_유효성_검사_오류가_나지_않는다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionDoesntHaveErrors(['email']);
    }

    // --- 글자 수 검사 ---

    /** @test 1-2-9 이름_255자면_오류_없이_확인_화면으로_이동한다 */
    public function test_1_2_9_이름_255자면_오류_없이_확인_화면으로_이동한다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => str_repeat('가', 255),
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionDoesntHaveErrors(['name']);
    }

    /** @test 1-2-10 이름_256자면_오류가_표시된다 */
    public function test_1_2_10_이름_256자면_오류가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => str_repeat('가', 256),
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(
            '이름은 255자 이내로 입력해 주세요.',
            session('errors')->get('name')[0]
        );
    }

    /** @test 1-2-11 이메일_주소_255자면_오류_없이_확인_화면으로_이동한다 */
    public function test_1_2_11_이메일_주소_255자면_오류_없이_확인_화면으로_이동한다(): void
    {
        // 255자 이메일 주소를 생성(user...@example.com 형식)
        $email = str_repeat('a', 243) . '@example.com';

        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => $email,
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionDoesntHaveErrors(['email']);
    }

    /** @test 1-2-12 이메일_주소_256자면_오류가_표시된다 */
    public function test_1_2_12_이메일_주소_256자면_오류가_표시된다(): void
    {
        $email = str_repeat('a', 244) . '@example.com';

        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => $email,
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            '이메일 주소는 255자 이내로 입력해 주세요.',
            session('errors')->get('email')[0]
        );
    }

    /** @test 1-2-13 제목_255자면_오류_없이_확인_화면으로_이동한다 */
    public function test_1_2_13_제목_255자면_오류_없이_확인_화면으로_이동한다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => str_repeat('가', 255),
            'body' => '테스트 본문',
        ]);

        $response->assertSessionDoesntHaveErrors(['subject']);
    }

    /** @test 1-2-14 제목_256자면_오류가_표시된다 */
    public function test_1_2_14_제목_256자면_오류가_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => str_repeat('가', 256),
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['subject']);
        $this->assertEquals(
            '제목은 255자 이내로 입력해 주세요.',
            session('errors')->get('subject')[0]
        );
    }

    // --- 입력값 복원 ---

    /** @test 1-2-15 유효성_검사_오류_뒤_입력값이_복원된다 */
    public function test_1_2_15_유효성_검사_오류_뒤_입력값이_복원된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSessionHasErrors(['name']);
        // old 입력값이 세션에 저장되어 있는지 확인
        $this->assertEquals('test@example.com', session()->getOldInput('email'));
        $this->assertEquals('테스트 제목', session()->getOldInput('subject'));
        $this->assertEquals('테스트 본문', session()->getOldInput('body'));
    }

    // ----------------------------------------
    // 1.3 확인 화면(POST /contact/confirm → 확인 화면 표시)
    // ----------------------------------------

    /** @test 1-3-1 확인_화면에_입력_내용이_표시된다 */
    public function test_1_3_1_확인_화면에_입력_내용이_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertStatus(200);
        $response->assertSee('입력 내용 확인');
    }

    /** @test 1-3-2 확인_화면에_이름이_올바르게_표시된다 */
    public function test_1_3_2_확인_화면에_이름이_올바르게_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSee('홍길동');
    }

    /** @test 1-3-3 확인_화면에_이메일_주소가_올바르게_표시된다 */
    public function test_1_3_3_확인_화면에_이메일_주소가_올바르게_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSee('test@example.com');
    }

    /** @test 1-3-4 확인_화면에_제목이_올바르게_표시된다 */
    public function test_1_3_4_확인_화면에_제목이_올바르게_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertSee('테스트 제목');
    }

    /** @test 1-3-5 확인_화면에_본문이_올바르게_표시된다 */
    public function test_1_3_5_확인_화면에_본문이_올바르게_표시된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문입니다.',
        ]);

        $response->assertSee('테스트 본문입니다.');
    }

    /** @test 1-3-6 확인_화면에서_본문의_줄바꿈이_유지된다 */
    public function test_1_3_6_확인_화면에서_본문의_줄바꿈이_유지된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => "1행\n2행",
        ]);

        $response->assertStatus(200);
        // nl2br로 줄바꿈이 <br />로 변환되는지 확인
        $response->assertSee('1행<br />' . "\n" . '2행', false);
    }

    /** @test 1-3-7 확인_화면에서_HTML이_이스케이프된다 */
    public function test_1_3_7_확인_화면에서_HTML이_이스케이프된다(): void
    {
        $response = $this->post('/contact/confirm', [
            'name' => "<script>alert('XSS')</script>",
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response->assertStatus(200);
        // 스크립트 태그가 그대로 출력되지 않는지 확인
        $response->assertDontSee("<script>alert('XSS')</script>", false);
        // 이스케이프된 문자열이 포함되어 있는지 확인
        $response->assertSee('&lt;script&gt;', false);
    }

    /** @test 1-3-8 확인_화면에서_뒤로_버튼을_누르면_입력_화면으로_돌아간다 */
    public function test_1_3_8_확인_화면에서_뒤로_버튼을_누르면_입력_화면으로_돌아간다(): void
    {
        // 먼저 확인 화면을 표시해 세션에 데이터를 저장
        $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        // '뒤로' 버튼(back 파라미터 포함)으로 전송
        $response = $this->post('/contact', [
            'back' => '1',
        ]);

        $response->assertRedirect(route('contact.create'));
    }

    /** @test 1-3-9 확인_화면에서_전송_버튼을_누르면_완료_화면으로_리다이렉트된다 */
    public function test_1_3_9_확인_화면에서_전송_버튼을_누르면_완료_화면으로_리다이렉트된다(): void
    {
        // 확인 화면을 표시해 세션에 데이터를 저장
        $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        // '전송' 버튼으로 전송
        $response = $this->post('/contact');

        $response->assertRedirect(route('contact.thanks'));
    }

    // ----------------------------------------
    // 1.4 전송 처리·완료 화면(POST /contact → GET /contact/thanks)
    // ----------------------------------------

    /** @test 1-4-1 contacts_테이블에_입력_데이터가_저장된다 */
    public function test_1_4_1_contacts_테이블에_입력_데이터가_저장된다(): void
    {
        // 세션에 데이터를 저장
        $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $this->post('/contact');

        $this->assertDatabaseHas('contacts', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);
    }

    /** @test 1-4-2 저장된_데이터의_상태가_신규다 */
    public function test_1_4_2_저장된_데이터의_상태가_신규다(): void
    {
        $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $this->post('/contact');

        $this->assertDatabaseHas('contacts', [
            'name' => '홍길동',
            'status' => '신규',
        ]);
    }

    /** @test 1-4-3 완료_화면에_문의해_주셔서_감사합니다가_표시된다 */
    public function test_1_4_3_완료_화면에_문의해_주셔서_감사합니다가_표시된다(): void
    {
        $response = $this->get('/contact/thanks');

        $response->assertStatus(200);
        $response->assertSee('문의해 주셔서 감사합니다');
    }

    /** @test 1-4-4 완료_화면에_문의_폼_링크가_표시된다 */
    public function test_1_4_4_완료_화면에_폼_링크가_표시된다(): void
    {
        $response = $this->get('/contact/thanks');

        $response->assertStatus(200);
        $response->assertSee(route('contact.create'), false);
    }

    /** @test 1-4-5 전송_완료_뒤_세션_데이터가_비워진다 */
    public function test_1_4_5_전송_완료_뒤_세션_데이터가_비워진다(): void
    {
        $this->post('/contact/confirm', [
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $this->post('/contact');

        // 세션에서 contact_data가 삭제되었는지 확인
        $this->assertNull(session('contact_data'));
    }

    // ----------------------------------------
    // 1.5 CSRF 보호
    // ----------------------------------------

    /** @test 1-5-1 CSRF_토큰_없이_POST /contact/confirm하면_419_오류가_반환된다 */
    public function test_1_5_1_CSRF_토큰_없이_확인_요청하면_419_오류가_반환된다(): void
    {
        // 테스트 환경에서는 CSRF 검증이 우회되므로 미들웨어를 바꿔 끼워 검증한다
        $this->app->bind(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            function ($app) {
                return new class ($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
                    protected function runningUnitTests()
                    {
                        return false;
                    }
                };
            }
        );

        $response = $this->call('POST', '/contact/confirm', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response->assertStatus(419);
    }

    /** @test 1-5-2 CSRF_토큰_없이_POST /contact하면_419_오류가_반환된다 */
    public function test_1_5_2_CSRF_토큰_없이_전송_요청하면_419_오류가_반환된다(): void
    {
        // 테스트 환경에서는 CSRF 검증이 우회되므로 미들웨어를 바꿔 끼워 검증한다
        $this->app->bind(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            function ($app) {
                return new class ($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
                    protected function runningUnitTests()
                    {
                        return false;
                    }
                };
            }
        );

        $response = $this->call('POST', '/contact', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response->assertStatus(419);
    }

    // ----------------------------------------
    // 1.6 부정 접근
    // ----------------------------------------

    /** @test 1-6-1 완료_화면에_직접_접근해도_오류가_나지_않는다 */
    public function test_1_6_1_완료_화면에_직접_접근해도_오류가_나지_않는다(): void
    {
        $response = $this->get('/contact/thanks');

        $response->assertStatus(200);
    }

    /** @test 1-6-2 세션_없이_전송해도_오류_없이_적절히_처리된다 */
    public function test_1_6_2_세션_없이_전송해도_오류_없이_적절히_처리된다(): void
    {
        // 세션에 데이터가 없는 상태로 POST /contact
        $response = $this->post('/contact');

        // 폼으로 리다이렉트된다
        $response->assertRedirect(route('contact.create'));
    }

    // ========================================
    // 2. 관리 페이지 쪽
    // ========================================

    /**
     * 관리자로 로그인하는 헬퍼
     */
    private function loginAsAdmin(): void
    {
        config(['app.admin_password' => 'test-password']);
        $this->post('/admin/login', ['password' => 'test-password']);
    }

    // ----------------------------------------
    // 2.1 로그인 화면(GET /admin/login)
    // ----------------------------------------

    /** @test 2-1-1 로그인_화면에_비밀번호_입력란과_로그인_버튼이_표시된다 */
    public function test_2_1_1_로그인_화면이_표시된다(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('비밀번호');
        $response->assertSee('로그인');
    }

    /** @test 2-1-2 올바른_비밀번호로_로그인하면_관리_화면으로_리다이렉트된다 */
    public function test_2_1_2_올바른_비밀번호로_로그인할_수_있다(): void
    {
        config(['app.admin_password' => 'test-password']);

        $response = $this->post('/admin/login', [
            'password' => 'test-password',
        ]);

        $response->assertRedirect(route('admin.contacts.index'));
    }

    /** @test 2-1-3 잘못된_비밀번호로_로그인하면_오류가_표시된다 */
    public function test_2_1_3_잘못된_비밀번호로_로그인하면_오류가_표시된다(): void
    {
        config(['app.admin_password' => 'test-password']);

        $response = $this->post('/admin/login', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test 2-1-4 비밀번호_미입력으로_로그인하면_유효성_검사_오류가_표시된다 */
    public function test_2_1_4_비밀번호_미입력이면_유효성_검사_오류가_표시된다(): void
    {
        $response = $this->post('/admin/login', [
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ----------------------------------------
    // 2.2 인증·접근 제어
    // ----------------------------------------

    /** @test 2-2-1 미로그인_상태로_목록에_접근하면_로그인_화면으로_리다이렉트된다 */
    public function test_2_2_1_미로그인_상태로_목록에_접근하면_리다이렉트된다(): void
    {
        $response = $this->get('/admin/contacts');

        $response->assertRedirect(route('admin.login'));
    }

    /** @test 2-2-2 미로그인_상태로_상세에_접근하면_로그인_화면으로_리다이렉트된다 */
    public function test_2_2_2_미로그인_상태로_상세에_접근하면_리다이렉트된다(): void
    {
        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        $response->assertRedirect(route('admin.login'));
    }

    /** @test 2-2-3 미로그인_상태로_상태를_업데이트하면_로그인_화면으로_리다이렉트된다 */
    public function test_2_2_3_미로그인_상태로_상태를_업데이트하면_리다이렉트된다(): void
    {
        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->patch("/admin/contacts/{$contact->id}", [
            'status' => '대응 중',
        ]);

        $response->assertRedirect(route('admin.login'));
    }

    /** @test 2-2-4 로그아웃하면_로그인_화면으로_리다이렉트된다 */
    public function test_2_2_4_로그아웃하면_로그인_화면으로_리다이렉트된다(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/admin/logout');

        $response->assertRedirect(route('admin.login'));
    }

    /** @test 2-2-5 로그아웃_뒤_관리_화면에_접근하면_로그인_화면으로_리다이렉트된다 */
    public function test_2_2_5_로그아웃_뒤_관리_화면에_접근하면_리다이렉트된다(): void
    {
        $this->loginAsAdmin();
        $this->post('/admin/logout');

        $response = $this->get('/admin/contacts');

        $response->assertRedirect(route('admin.login'));
    }

    // ----------------------------------------
    // 2.3 문의 목록(GET /admin/contacts)
    // ----------------------------------------

    // --- 목록 표시 ---

    /** @test 2-3-1 로그인_뒤_목록_화면이_표시된다 */
    public function test_2_3_1_목록_화면이_표시된다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        $response->assertSee('문의 목록');
    }

    /** @test 2-3-2 목록에_ID·이름·제목·상태·수신_일시가_표시된다 */
    public function test_2_3_2_목록의_표시_항목이_올바르다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get('/admin/contacts');

        $response->assertSee((string) $contact->id);
        $response->assertSee('홍길동');
        $response->assertSee('테스트 제목');
        $response->assertSee('신규');
        $response->assertSee($contact->created_at->format('Y/m/d H:i'));
    }

    /** @test 2-3-3 상태_신규가_빨강_배지로_표시된다 */
    public function test_2_3_3_상태_신규가_빨강_배지로_표시된다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '신규',
        ]);

        $response = $this->get('/admin/contacts');

        $response->assertSee('badge-new', false);
    }

    /** @test 2-3-4 상태_대응_중이_주황_배지로_표시된다 */
    public function test_2_3_4_상태_대응_중이_주황_배지로_표시된다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '대응 중',
        ]);

        $response = $this->get('/admin/contacts');

        $response->assertSee('badge-progress', false);
    }

    /** @test 2-3-5 상태_해결_완료가_초록_배지로_표시된다 */
    public function test_2_3_5_상태_해결_완료가_초록_배지로_표시된다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '해결 완료',
        ]);

        $response = $this->get('/admin/contacts');

        $response->assertSee('badge-resolved', false);
    }

    /** @test 2-3-6 목록의_이름을_클릭하면_상세_화면으로_가는_링크가_있다 */
    public function test_2_3_6_상세_링크가_표시된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get('/admin/contacts');

        $response->assertSee(route('admin.contacts.show', $contact), false);
    }

    /** @test 2-3-7 데이터가_0건이어도_오류_없이_적절히_표시된다 */
    public function test_2_3_7_데이터가_없어도_적절히_표시된다(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        $response->assertSee('문의가 아직 없습니다');
    }

    // --- 페이지네이션 ---

    /** @test 2-3-8 11건_이상이면_페이지네이션이_표시된다 */
    public function test_2_3_8_페이지네이션이_표시된다(): void
    {
        $this->loginAsAdmin();

        // 11건의 데이터를 생성
        for ($i = 0; $i < 11; $i++) {
            Contact::create([
                'name' => "테스트{$i}",
                'email' => "test{$i}@example.com",
                'subject' => "제목{$i}",
                'body' => "본문{$i}",
            ]);
        }

        $response = $this->get('/admin/contacts');

        $response->assertStatus(200);
        // 페이지네이션 링크가 있는지 확인
        $response->assertSee('page=2', false);
    }

    /** @test 2-3-9 한_페이지에_10건이_표시된다 */
    public function test_2_3_9_페이지당_10건이_표시된다(): void
    {
        $this->loginAsAdmin();

        for ($i = 0; $i < 15; $i++) {
            Contact::create([
                'name' => "테스트{$i}",
                'email' => "test{$i}@example.com",
                'subject' => "제목{$i}",
                'body' => "본문{$i}",
            ]);
        }

        $response = $this->get('/admin/contacts');

        // tbody 안 <tr> 수로 페이지당 표시 건수를 확인
        $content = $response->getContent();
        preg_match('/<tbody>(.*?)<\/tbody>/s', $content, $match);
        $rowCount = substr_count($match[1] ?? '', '<tr>');
        $this->assertEquals(10, $rowCount);
    }

    /** @test 2-3-10 2페이지로_이동할_수_있다 */
    public function test_2_3_10_2페이지로_이동할_수_있다(): void
    {
        $this->loginAsAdmin();

        for ($i = 0; $i < 15; $i++) {
            Contact::create([
                'name' => "테스트{$i}",
                'email' => "test{$i}@example.com",
                'subject' => "제목{$i}",
                'body' => "본문{$i}",
            ]);
        }

        $response = $this->get('/admin/contacts?page=2');

        $response->assertStatus(200);
        // 2페이지의 tbody 안 <tr> 수를 확인(남은 5건)
        $content = $response->getContent();
        preg_match('/<tbody>(.*?)<\/tbody>/s', $content, $match);
        $rowCount = substr_count($match[1] ?? '', '<tr>');
        $this->assertEquals(5, $rowCount);
    }

    /** @test 2-3-11 검색_조건이_페이지네이션_이동에서_유지된다 */
    public function test_2_3_11_검색_조건이_페이지네이션에서_유지된다(): void
    {
        $this->loginAsAdmin();

        // '민준'을 포함하는 데이터를 11건 이상 생성
        for ($i = 0; $i < 15; $i++) {
            Contact::create([
                'name' => "민준{$i}",
                'email' => "minjun{$i}@example.com",
                'subject' => "제목{$i}",
                'body' => "본문{$i}",
            ]);
        }

        $response = $this->get('/admin/contacts?name=민준&page=2');

        $response->assertStatus(200);
        // 페이지네이션 링크에 검색 조건이 포함되어 있는지 확인
        $response->assertSee('name=', false);
    }

    // --- 검색·필터 ---

    /** @test 2-3-12 이름으로_검색할_수_있다 */
    public function test_2_3_12_이름으로_검색할_수_있다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '김민준',
            'email' => 'minjun@example.com',
            'subject' => '제목A',
            'body' => '본문A',
        ]);
        Contact::create([
            'name' => '박서연',
            'email' => 'seoyeon@example.com',
            'subject' => '제목B',
            'body' => '본문B',
        ]);

        $response = $this->get('/admin/contacts?name=민준');

        $response->assertSee('김민준');
        $response->assertDontSee('박서연');
    }

    /** @test 2-3-13 이름의_부분_일치로_검색할_수_있다 */
    public function test_2_3_13_이름의_부분_일치로_검색할_수_있다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '김민준',
            'email' => 'minjun@example.com',
            'subject' => '제목A',
            'body' => '본문A',
        ]);
        Contact::create([
            'name' => '이민서',
            'email' => 'minseo@example.com',
            'subject' => '제목B',
            'body' => '본문B',
        ]);
        Contact::create([
            'name' => '박준호',
            'email' => 'junho@example.com',
            'subject' => '제목C',
            'body' => '본문C',
        ]);

        $response = $this->get('/admin/contacts?name=민');

        $response->assertSee('김민준');
        $response->assertSee('이민서');
        $response->assertDontSee('박준호');
    }

    /** @test 2-3-14 시작일로_필터링할_수_있다 */
    public function test_2_3_14_시작일로_필터링할_수_있다(): void
    {
        $this->loginAsAdmin();

        $this->travelTo(Carbon::parse('2026-01-01'));
        Contact::create([
            'name' => '오래된 데이터',
            'email' => 'old@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);

        $this->travelTo(Carbon::parse('2026-02-15'));
        Contact::create([
            'name' => '새 데이터',
            'email' => 'new@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);
        $this->travelBack();

        $response = $this->get('/admin/contacts?date_from=2026-02-01');

        $response->assertSee('새 데이터');
        $response->assertDontSee('오래된 데이터');
    }

    /** @test 2-3-15 종료일로_필터링할_수_있다 */
    public function test_2_3_15_종료일로_필터링할_수_있다(): void
    {
        $this->loginAsAdmin();

        $this->travelTo(Carbon::parse('2026-01-15'));
        Contact::create([
            'name' => '오래된 데이터',
            'email' => 'old@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);

        $this->travelTo(Carbon::parse('2026-02-15'));
        Contact::create([
            'name' => '새 데이터',
            'email' => 'new@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);
        $this->travelBack();

        $response = $this->get('/admin/contacts?date_to=2026-01-31');

        $response->assertSee('오래된 데이터');
        $response->assertDontSee('새 데이터');
    }

    /** @test 2-3-16 날짜_범위로_필터링할_수_있다 */
    public function test_2_3_16_날짜_범위로_필터링할_수_있다(): void
    {
        $this->loginAsAdmin();

        $this->travelTo(Carbon::parse('2026-01-01'));
        Contact::create([
            'name' => '범위 전',
            'email' => 'before@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);

        $this->travelTo(Carbon::parse('2026-01-15'));
        Contact::create([
            'name' => '범위 내',
            'email' => 'within@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);

        $this->travelTo(Carbon::parse('2026-02-15'));
        Contact::create([
            'name' => '범위 후',
            'email' => 'after@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);
        $this->travelBack();

        $response = $this->get('/admin/contacts?date_from=2026-01-10&date_to=2026-01-31');

        $response->assertSee('범위 내');
        $response->assertDontSee('범위 전');
        $response->assertDontSee('범위 후');
    }

    /** @test 2-3-17 상태(단일)로_필터링할_수_있다 */
    public function test_2_3_17_상태_단일로_필터링할_수_있다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '신규 데이터',
            'email' => 'new@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '신규',
        ]);
        Contact::create([
            'name' => '대응 중 데이터',
            'email' => 'progress@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '대응 중',
        ]);

        $response = $this->get('/admin/contacts?' . http_build_query(['status' => ['신규']]));

        $response->assertSee('신규 데이터');
        $response->assertDontSee('대응 중 데이터');
    }

    /** @test 2-3-18 상태(복수)로_필터링할_수_있다 */
    public function test_2_3_18_상태_복수로_필터링할_수_있다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '신규 데이터',
            'email' => 'new@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '신규',
        ]);
        Contact::create([
            'name' => '대응 중 데이터',
            'email' => 'progress@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '대응 중',
        ]);
        Contact::create([
            'name' => '해결 완료 데이터',
            'email' => 'resolved@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '해결 완료',
        ]);

        $response = $this->get('/admin/contacts?' . http_build_query(['status' => ['신규', '대응 중']]));

        $response->assertSee('신규 데이터');
        $response->assertSee('대응 중 데이터');
        $response->assertDontSee('해결 완료 데이터');
    }

    /** @test 2-3-19 복합_검색을_할_수_있다 */
    public function test_2_3_19_복합_검색을_할_수_있다(): void
    {
        $this->loginAsAdmin();

        $this->travelTo(Carbon::parse('2026-01-15'));
        Contact::create([
            'name' => '김민준',
            'email' => 'minjun@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '신규',
        ]);
        Contact::create([
            'name' => '이민준',
            'email' => 'minjun2@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '해결 완료',
        ]);
        Contact::create([
            'name' => '박준호',
            'email' => 'junho@example.com',
            'subject' => '제목',
            'body' => '본문',
            'status' => '신규',
        ]);
        $this->travelBack();

        $query = http_build_query([
            'name' => '민준',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'status' => ['신규'],
        ]);

        $response = $this->get("/admin/contacts?{$query}");

        $response->assertSee('김민준');
        $response->assertDontSee('이민준');
        $response->assertDontSee('박준호');
    }

    /** @test 2-3-20 검색_조건_클리어로_전체가_표시된다 */
    public function test_2_3_20_검색_조건_클리어로_전체가_표시된다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);
        Contact::create([
            'name' => '성춘향',
            'email' => 'test2@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);

        // 클리어 버튼은 조건 없이 목록에 접근하는 것과 같다
        $response = $this->get('/admin/contacts');

        $response->assertSee('홍길동');
        $response->assertSee('성춘향');
    }

    /** @test 2-3-21 검색_결과가_0건이어도_오류가_나지_않는다 */
    public function test_2_3_21_해당_없는_검색에서_오류가_나지_않는다(): void
    {
        $this->loginAsAdmin();

        Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '제목',
            'body' => '본문',
        ]);

        $response = $this->get('/admin/contacts?name=존재하지 않는 이름');

        $response->assertStatus(200);
        $response->assertSee('문의가 아직 없습니다');
    }

    // ----------------------------------------
    // 2.4 문의 상세(GET /admin/contacts/{id})
    // ----------------------------------------

    /** @test 2-4-1 상세_화면이_표시된다 */
    public function test_2_4_1_상세_화면이_표시된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertSee('문의 상세');
    }

    /** @test 2-4-2 상세_화면에_ID·이름·이메일_주소·제목·본문·수신_일시가_표시된다 */
    public function test_2_4_2_상세_화면의_표시_항목이_올바르다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        $response->assertSee((string) $contact->id);
        $response->assertSee('홍길동');
        $response->assertSee('test@example.com');
        $response->assertSee('테스트 제목');
        $response->assertSee('테스트 본문');
        $response->assertSee($contact->created_at->format('Y/m/d H:i'));
    }

    /** @test 2-4-3 상세_화면에서_본문의_줄바꿈이_반영된다 */
    public function test_2_4_3_상세_화면에서_본문의_줄바꿈이_표시된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => "1행\n2행",
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        // nl2br로 줄바꿈이 <br />로 변환되는지 확인
        $response->assertSee('1행<br />' . "\n" . '2행', false);
    }

    /** @test 2-4-4 상세_화면에서_HTML_태그가_이스케이프된다 */
    public function test_2_4_4_상세_화면에서_HTML이_이스케이프된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => "<script>alert('XSS')</script>",
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        $response->assertDontSee("<script>alert('XSS')</script>", false);
        $response->assertSee('&lt;script&gt;', false);
    }

    /** @test 2-4-5 상태_드롭다운에_세_가지_선택지가_표시된다 */
    public function test_2_4_5_상태_드롭다운이_표시된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        $response->assertSee('<option', false);
        $response->assertSee('신규');
        $response->assertSee('대응 중');
        $response->assertSee('해결 완료');
    }

    /** @test 2-4-6 현재_상태가_선택된_상태다 */
    public function test_2_4_6_현재_상태가_선택된_상태다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '대응 중',
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        // '대응 중' option에 selected가 붙어 있는지 확인
        $response->assertSee('selected', false);
    }

    /** @test 2-4-7 목록으로_돌아가기_링크가_표시된다 */
    public function test_2_4_7_목록으로_돌아가기_링크가_표시된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
        ]);

        $response = $this->get("/admin/contacts/{$contact->id}");

        $response->assertSee(route('admin.contacts.index'), false);
        $response->assertSee('목록으로 돌아가기');
    }

    /** @test 2-4-8 존재하지_않는_ID에_접근하면_404_오류가_반환된다 */
    public function test_2_4_8_존재하지_않는_ID에_접근하면_404가_반환된다(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/contacts/99999');

        $response->assertStatus(404);
    }

    // ----------------------------------------
    // 2.5 상태 업데이트(PATCH /admin/contacts/{id})
    // ----------------------------------------

    /** @test 2-5-1 신규에서_대응_중으로_바꿀_수_있다 */
    public function test_2_5_1_신규에서_대응_중으로_바꿀_수_있다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '신규',
        ]);

        $response = $this->patch("/admin/contacts/{$contact->id}", [
            'status' => '대응 중',
        ]);

        $response->assertRedirect(route('admin.contacts.show', $contact));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => '대응 중',
        ]);
    }

    /** @test 2-5-2 대응_중에서_해결_완료로_바꿀_수_있다 */
    public function test_2_5_2_대응_중에서_해결_완료로_바꿀_수_있다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '대응 중',
        ]);

        $response = $this->patch("/admin/contacts/{$contact->id}", [
            'status' => '해결 완료',
        ]);

        $response->assertRedirect(route('admin.contacts.show', $contact));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => '해결 완료',
        ]);
    }

    /** @test 2-5-3 해결_완료에서_신규로_바꿀_수_있다 */
    public function test_2_5_3_해결_완료에서_신규로_바꿀_수_있다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '해결 완료',
        ]);

        $response = $this->patch("/admin/contacts/{$contact->id}", [
            'status' => '신규',
        ]);

        $response->assertRedirect(route('admin.contacts.show', $contact));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => '신규',
        ]);
    }

    /** @test 2-5-4 상태_업데이트_뒤_성공_메시지가_표시된다 */
    public function test_2_5_4_상태_업데이트_뒤_성공_메시지가_표시된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '신규',
        ]);

        $response = $this->patch("/admin/contacts/{$contact->id}", [
            'status' => '대응 중',
        ]);

        $response->assertSessionHas('success', '상태를 업데이트했습니다.');
    }

    /** @test 2-5-5 상태_업데이트가_목록_화면에도_반영된다 */
    public function test_2_5_5_상태_업데이트가_DB와_목록에_반영된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '신규',
        ]);

        $this->patch("/admin/contacts/{$contact->id}", [
            'status' => '해결 완료',
        ]);

        // 목록 화면에서 확인
        $response = $this->get('/admin/contacts');
        $response->assertSee('badge-resolved', false);
    }

    /** @test 2-5-6 잘못된_상태_값이면_유효성_검사_오류가_반환된다 */
    public function test_2_5_6_잘못된_상태_값이면_유효성_검사_오류가_반환된다(): void
    {
        $this->loginAsAdmin();

        $contact = Contact::create([
            'name' => '홍길동',
            'email' => 'test@example.com',
            'subject' => '테스트 제목',
            'body' => '테스트 본문',
            'status' => '신규',
        ]);

        $response = $this->patch("/admin/contacts/{$contact->id}", [
            'status' => '잘못된 값',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    // ----------------------------------------
    // 2.6 로그아웃(POST /admin/logout)
    // ----------------------------------------

    /** @test 2-6-1 로그아웃하면_로그인_화면으로_리다이렉트된다 */
    public function test_2_6_1_로그아웃하면_로그인_화면으로_리다이렉트된다(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/admin/logout');

        $response->assertRedirect(route('admin.login'));
    }

    /** @test 2-6-2 로그아웃_뒤_관리_화면_URL에_접근하면_로그인_화면으로_리다이렉트된다 */
    public function test_2_6_2_로그아웃_뒤_세션이_무효가_된다(): void
    {
        $this->loginAsAdmin();
        $this->post('/admin/logout');

        $response = $this->get('/admin/contacts');

        $response->assertRedirect(route('admin.login'));
    }
}
