# ToDo 앱(도서 샘플)

단일 HTML 파일만으로 동작하는 브라우저 완결형 ToDo 앱 샘플 프로젝트입니다. CSS·JavaScript는 모두 `index.html`에 인라인으로 작성되어 있고 데이터는 `localStorage`에 저장됩니다. 사양 상세는 [`docs/spec.md`](docs/spec.md), 테스트 케이스는 [`docs/test-case.md`](docs/test-case.md), 개발 시 규칙은 [`CLAUDE.md`](CLAUDE.md)를 참조하세요.

와이어프레임(`main.jpg`·`detail.jpg`)은 Claude Code에 화면을 만들게 할 때 프롬프트에서 쓰는 것을 상정하고 있습니다.

## 필요 환경

- 최신 웹 브라우저(Chrome / Edge / Firefox 등)
- Node.js / npm(Playwright로 테스트를 실행하는 경우에만)

저장소에 함께 들어 있는 `.devcontainer`를 쓰면 VS Code의 Dev Containers 확장으로 위 환경이 갖춰진 상태로 바로 기동합니다.

## 앱 기동

서버는 필요 없습니다. `index.html`을 브라우저로 직접 열기만 하면 동작합니다.

```bash
# 예: macOS
open index.html

# 예: Linux
xdg-open index.html
```

리치 텍스트 에디터인 [Quill](https://quilljs.com/)은 CDN에서 읽어 들이므로 처음 기동할 때는 인터넷 연결이 필요합니다.

브라우저로 열었다면 다음과 같은 조작으로 동작을 확인할 수 있습니다.

| 조작 | 내용 |
| --- | --- |
| [신규] | 상세 화면(모달)을 열어 할 일을 추가 |
| 제목 클릭 | 기존 할 일을 편집 |
| [완료] | 완료 / 미완료를 토글 |
| [완료됨을 삭제] | 완료된 할 일을 일괄 삭제 |
| 탭 전환 | '전체' '미완료' '완료됨'으로 표시를 필터링 |

데이터는 브라우저의 `localStorage`(키: `todo-app-items`)에 저장되어 브라우저를 닫아도 유지됩니다.

## 테스트

Playwright로 만든 E2E 테스트가 함께 들어 있습니다. 처음 한 번만 의존 패키지와 브라우저를 설치하세요.

```bash
npm install
npx playwright install
```

테스트 실행은 다음 명령입니다.

```bash
npm test
```

## 라이선스

이 샘플이 사용하는 [Quill](https://github.com/slab/quill)과 [Playwright](https://github.com/microsoft/playwright) 은 모두 MIT License로 배포됩니다.
