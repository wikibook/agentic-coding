# CLAUDE.md - ToDo 앱 개발 가이드

## 기술 스택·제약

- **단일 HTML 파일**(`index.html`): CSS·JavaScript를 모두 인라인으로 작성
- **외부 라이브러리**: Quill(리치 텍스트 에디터)을 CDN에서 읽어 들임
- **데이터 영속화**: localStorage(키: `todo-app-items`)
- **서버 불필요**: 브라우저에서 직접 열어 동작

## 파일 구성

```
todo-app/
├── index.html          # 앱 본체(HTML + CSS + JS 모두 포함)
├── docs/
│   └── spec.md         # 명세서
├── main.jpg            # 메인 화면 와이어프레임
├── detail.jpg          # 상세 화면 와이어프레임
└── CLAUDE.md           # 이 파일
```

## 코딩 규약

- **UI 언어**: 모두 한국어
- **주석**: 한국어로 작성
- **변수명·함수명**: 영어(카멜 케이스)
- **CSS**: `<style>` 태그 안에 작성
- **JavaScript**: `<script>` 태그 안에 작성(`</body>` 직전)
- **ID 생성**: `crypto.randomUUID()` 사용

## Quill CDN

```html
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
```

툴바 구성: 굵게, 기울임, 밑줄, 글머리 기호 목록, 번호 매기기 목록

## localStorage

- 키: `todo-app-items`
- 값: 할 일 항목의 JSON 배열
- 읽고 쓰기는 `JSON.parse` / `JSON.stringify`로 한다

## 명세서

자세한 사양은 `docs/spec.md`를 참조.
