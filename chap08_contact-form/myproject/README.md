# 문의 폼(도서 샘플)

Laravel로 구현한 '문의 폼 + 관리 페이지' 샘플 프로젝트입니다. 사양 상세는 [`docs/명세서.md`](docs/명세서.md), 개발 시 규칙은 [`CLAUDE.md`](CLAUDE.md)를 참조하세요.

## 필요 환경

- PHP 8.2 이상
- Composer
- Node.js / npm
- SQLite(PHP의 `pdo_sqlite` 확장)

저장소에 함께 들어 있는 `.devcontainer`를 쓰면 VS Code의 Dev Containers 확장으로 위 환경이 갖춰진 상태로 바로 기동합니다.

## 설치

저장소를 받았다면 `myproject/` 디렉터리로 이동해 다음을 실행합니다.

```bash
composer setup
```

`composer.json`에 정의된 스크립트로 다음이 한꺼번에 실행됩니다.

1. `composer install` — PHP 의존 패키지 설치
2. `.env.example`에서 `.env`로 복사
3. `php artisan key:generate` — 애플리케이션 키 생성
4. `php artisan migrate --force` — SQLite에 테이블 생성
5. `npm install` — 프런트 의존 패키지 설치
6. `npm run build` — CSS/JS 빌드

수동으로 할 경우는 다음과 같습니다.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## 개발 서버 기동

```bash
composer dev
```

`php artisan serve`, 큐 워커, 로그 표시(`pail`), Vite를 동시에 기동합니다.

기동했다면 브라우저에서 다음에 접근해 동작을 확인할 수 있습니다.

| URL | 내용 |
| --- | --- |
| http://localhost:8000/ | 문의 폼(입력 → 확인 → 전송 완료) |
| http://localhost:8000/admin/login | 관리 페이지 로그인 |
| http://localhost:8000/admin/contacts | 문의 목록(로그인 필요) |

관리 페이지의 로그인 비밀번호는 `.env`의 `ADMIN_PASSWORD`로 설정합니다(초깃값은 `password`).

서버만 단독으로 돌리고 싶을 때는 다음 명령으로도 가능합니다.

```bash
php artisan serve
```

## 테스트

```bash
php artisan test
```

## 라이선스

이 샘플이 사용하는 Laravel 프레임워크는 [MIT License](https://opensource.org/licenses/MIT)입니다.
