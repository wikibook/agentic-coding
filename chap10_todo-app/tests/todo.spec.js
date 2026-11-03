// @ts-check
const { test, expect } = require('@playwright/test');
const path = require('path');

// index.html의 파일 URL
const APP_URL = `file://${path.resolve(__dirname, '../index.html')}`;

// localStorage 키
const STORAGE_KEY = 'todo-app-items';

/**
 * 헬퍼: localStorage를 비우고 페이지를 새로고침
 */
async function resetApp(page) {
  await page.goto(APP_URL);
  await page.evaluate((key) => localStorage.removeItem(key), STORAGE_KEY);
  await page.reload();
  await page.waitForLoadState('domcontentloaded');
}

/**
 * 헬퍼: localStorage에 데이터를 설정하고 새로고침
 */
async function setStorageAndReload(page, data) {
  await page.goto(APP_URL);
  await page.evaluate(({ key, value }) => {
    localStorage.setItem(key, JSON.stringify(value));
  }, { key: STORAGE_KEY, value: data });
  await page.reload();
  await page.waitForLoadState('domcontentloaded');
}

/**
 * 헬퍼: localStorage에서 데이터를 가져온다
 */
async function getStorageData(page) {
  return page.evaluate((key) => {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : [];
  }, STORAGE_KEY);
}

/**
 * 헬퍼: 오늘 날짜 문자열 (YYYY-MM-DD)
 */
function todayStr() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/**
 * 헬퍼: 내일 날짜 문자열 (YYYY-MM-DD)
 */
function tomorrowStr() {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/**
 * 헬퍼: 모레 날짜 문자열 (YYYY-MM-DD)
 */
function dayAfterTomorrowStr() {
  const d = new Date();
  d.setDate(d.getDate() + 2);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/**
 * 헬퍼: 새 항목을 만들어 저장
 */
async function createItem(page, { title, dateType = 'none', dateValue = '', detail = '' }) {
  await page.getByRole('button', { name: '신규' }).click();

  // 제목 입력
  await page.getByPlaceholder('제목을 입력').fill(title);

  // 일시 선택
  if (dateType === 'none') {
    await page.getByLabel('없음').check();
  } else if (dateType === 'date') {
    await page.getByLabel('날짜만').check();
    await page.locator('input[type="date"]').fill(dateValue);
  } else if (dateType === 'datetime') {
    await page.getByLabel('시각까지').check();
    await page.locator('input[type="datetime-local"]').fill(dateValue);
  }

  // 상세 입력(Quill 에디터)
  if (detail) {
    await page.locator('.ql-editor').fill(detail);
  }

  await page.getByRole('button', { name: '저장' }).click();
}

// =============================================================
// 1. 초기 표시
// =============================================================
test.describe('1. 초기 표시', () => {
  test('TC-1.1: 초기 상태의 화면 표시', async ({ page }) => {
    await resetApp(page);

    // 제목 'ToDo 리스트'가 표시된다
    await expect(page.getByText('ToDo 리스트')).toBeVisible();

    // [신규] 버튼이 표시된다
    await expect(page.getByRole('button', { name: '신규' })).toBeVisible();

    // 탭 '전체' '미완료' '완료됨'이 표시된다
    await expect(page.getByText('전체')).toBeVisible();
    await expect(page.getByText('미완료')).toBeVisible();
    await expect(page.getByText('완료됨', { exact: true })).toBeVisible();

    // '전체' 탭이 활성 상태
    const allTab = page.getByText('전체');
    await expect(allTab).toHaveClass(/active/);

    // [완료됨을 삭제] 버튼이 표시된다
    await expect(page.getByRole('button', { name: '완료됨을 삭제' })).toBeVisible();

    // 섹션 제목이 표시되지 않는다(항목이 없으므로)
    await expect(page.getByText('오늘 중')).not.toBeVisible();
    await expect(page.getByText('내일까지')).not.toBeVisible();
    await expect(page.getByText('그 외')).not.toBeVisible();
  });

  test('TC-1.2: 기존 데이터 읽어 들이기', async ({ page }) => {
    const data = [{
      id: 'test-1',
      title: '테스트 작업',
      detail: '',
      dateType: 'none',
      deadline: null,
      completed: false,
      createdAt: '2026-03-14T00:00:00',
    }];
    await setStorageAndReload(page, data);

    // '테스트 작업'이 '그 외' 섹션에 표시된다
    await expect(page.getByText('그 외')).toBeVisible();
    await expect(page.getByText('테스트 작업')).toBeVisible();
  });
});

// =============================================================
// 2. 상세 화면(모달) - 신규 등록
// =============================================================
test.describe('2. 상세 화면(모달)', () => {
  test.beforeEach(async ({ page }) => {
    await resetApp(page);
  });

  test('TC-2.1: 모달 표시', async ({ page }) => {
    await page.getByRole('button', { name: '신규' }).click();

    // 모달이 표시된다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).toBeVisible();

    // 배경 오버레이가 있다
    const overlay = page.locator('.overlay, .modal-overlay, .modal-backdrop');
    await expect(overlay).toBeVisible();

    // 제목 입력란이 비어 있고 자리표시자 '제목을 입력'
    const titleInput = page.getByPlaceholder('제목을 입력');
    await expect(titleInput).toBeVisible();
    await expect(titleInput).toHaveValue('');

    // 일시 라디오 버튼 '없음'이 선택되어 있다
    await expect(page.getByLabel('없음')).toBeChecked();

    // 날짜 입력란은 표시되지 않음
    await expect(page.locator('input[type="date"]')).not.toBeVisible();
    await expect(page.locator('input[type="datetime-local"]')).not.toBeVisible();

    // Quill 에디터가 표시되고 내용은 비어 있음
    const editor = page.locator('.ql-editor');
    await expect(editor).toBeVisible();
    const editorText = await editor.textContent();
    expect(editorText?.trim()).toBe('');

    // [저장]과 [취소] 버튼
    await expect(page.getByRole('button', { name: '저장' })).toBeVisible();
    await expect(page.getByRole('button', { name: '취소' })).toBeVisible();
  });

  test('TC-2.2: 모달 밖 클릭으로 닫히지 않음', async ({ page }) => {
    await page.getByRole('button', { name: '신규' }).click();
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).toBeVisible();

    // 오버레이 부분을 클릭
    const overlay = page.locator('.overlay, .modal-overlay, .modal-backdrop');
    await overlay.click({ position: { x: 5, y: 5 } });

    // 모달이 닫히지 않는다
    await expect(modal).toBeVisible();
  });

  test('TC-2.3: ESC 키로 닫기', async ({ page }) => {
    await page.getByRole('button', { name: '신규' }).click();
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).toBeVisible();

    await page.keyboard.press('Escape');

    await expect(modal).not.toBeVisible();
  });

  test('TC-2.4: [취소] 버튼', async ({ page }) => {
    await page.getByRole('button', { name: '신규' }).click();
    await page.getByPlaceholder('제목을 입력').fill('테스트');
    await page.getByRole('button', { name: '취소' }).click();

    // 모달이 닫힌다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).not.toBeVisible();

    // 항목이 추가되지 않았다
    await expect(page.getByText('테스트')).not.toBeVisible();
  });
});

// =============================================================
// 3. 일시 선택
// =============================================================
test.describe('3. 일시 선택', () => {
  test.beforeEach(async ({ page }) => {
    await resetApp(page);
    await page.getByRole('button', { name: '신규' }).click();
  });

  test('TC-3.1: 라디오 버튼 "없음" 선택', async ({ page }) => {
    await page.getByLabel('없음').check();
    await expect(page.locator('input[type="date"]')).not.toBeVisible();
    await expect(page.locator('input[type="datetime-local"]')).not.toBeVisible();
  });

  test('TC-3.2: 라디오 버튼 "날짜만" 선택', async ({ page }) => {
    await page.getByLabel('날짜만').check();
    await expect(page.locator('input[type="date"]')).toBeVisible();
  });

  test('TC-3.3: 라디오 버튼 "시각까지" 선택', async ({ page }) => {
    await page.getByLabel('시각까지').check();
    await expect(page.locator('input[type="datetime-local"]')).toBeVisible();
  });

  test('TC-3.4: 라디오 버튼 전환', async ({ page }) => {
    // '시각까지'를 선택해 일시 입력
    await page.getByLabel('시각까지').check();
    await expect(page.locator('input[type="datetime-local"]')).toBeVisible();

    // '없음'으로 전환
    await page.getByLabel('없음').check();
    await expect(page.locator('input[type="date"]')).not.toBeVisible();
    await expect(page.locator('input[type="datetime-local"]')).not.toBeVisible();

    // '날짜만'으로 전환
    await page.getByLabel('날짜만').check();
    await expect(page.locator('input[type="date"]')).toBeVisible();
    await expect(page.locator('input[type="datetime-local"]')).not.toBeVisible();
  });
});

// =============================================================
// 4. 유효성 검사
// =============================================================
test.describe('4. 유효성 검사', () => {
  test.beforeEach(async ({ page }) => {
    await resetApp(page);
    await page.getByRole('button', { name: '신규' }).click();
  });

  test('TC-4.1: 제목 미입력으로 저장', async ({ page }) => {
    await page.getByRole('button', { name: '저장' }).click();
    await expect(page.getByText('제목을 입력해 주세요')).toBeVisible();
  });

  test('TC-4.2: 제목이 공백만인 상태로 저장', async ({ page }) => {
    await page.getByPlaceholder('제목을 입력').fill('   ');
    await page.getByRole('button', { name: '저장' }).click();
    await expect(page.getByText('제목을 입력해 주세요')).toBeVisible();
  });

  test('TC-4.3: 제목 101자로 저장', async ({ page }) => {
    const longTitle = '가'.repeat(101);
    await page.getByPlaceholder('제목을 입력').fill(longTitle);
    await page.getByRole('button', { name: '저장' }).click();
    await expect(page.getByText('제목은 100자 이내로 입력해 주세요')).toBeVisible();
  });

  test('TC-4.4: 제목 100자로 저장(정상)', async ({ page }) => {
    const title100 = '가'.repeat(100);
    await page.getByPlaceholder('제목을 입력').fill(title100);
    await page.getByRole('button', { name: '저장' }).click();

    // 유효성 검사 오류 없음 → 모달이 닫힌다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).not.toBeVisible();
  });

  test('TC-4.5: 상세 2001자로 저장', async ({ page }) => {
    await page.getByPlaceholder('제목을 입력').fill('테스트');
    const longDetail = '가'.repeat(2001);
    await page.locator('.ql-editor').fill(longDetail);
    await page.getByRole('button', { name: '저장' }).click();
    await expect(page.getByText('상세는 2000자 이내로 입력해 주세요')).toBeVisible();
  });

  test('TC-4.6: 상세 2000자로 저장(정상)', async ({ page }) => {
    await page.getByPlaceholder('제목을 입력').fill('테스트');
    const detail2000 = '가'.repeat(2000);
    await page.locator('.ql-editor').fill(detail2000);
    await page.getByRole('button', { name: '저장' }).click();

    // 유효성 검사 오류 없음 → 모달이 닫힌다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).not.toBeVisible();
  });

  test('TC-4.7: 일시 "날짜만"인데 날짜 미입력', async ({ page }) => {
    await page.getByPlaceholder('제목을 입력').fill('테스트');
    await page.getByLabel('날짜만').check();
    // 날짜를 입력하지 않고 저장
    await page.getByRole('button', { name: '저장' }).click();
    await expect(page.getByText('일시를 입력해 주세요')).toBeVisible();
  });

  test('TC-4.8: 일시 "시각까지"인데 일시 미입력', async ({ page }) => {
    await page.getByPlaceholder('제목을 입력').fill('테스트');
    await page.getByLabel('시각까지').check();
    // 일시를 입력하지 않고 저장
    await page.getByRole('button', { name: '저장' }).click();
    await expect(page.getByText('일시를 입력해 주세요')).toBeVisible();
  });
});

// =============================================================
// 5. 신규 등록(정상 케이스)
// =============================================================
test.describe('5. 신규 등록(정상 케이스)', () => {
  test.beforeEach(async ({ page }) => {
    await resetApp(page);
  });

  test('TC-5.1: 최소 구성으로 저장(제목만)', async ({ page }) => {
    await createItem(page, { title: '장보기 목록' });

    // 모달이 닫힌다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).not.toBeVisible();

    // '그 외' 섹션에 표시
    await expect(page.getByText('그 외')).toBeVisible();
    await expect(page.getByText('장보기 목록')).toBeVisible();

    // [완료] 버튼이 표시
    await expect(page.getByRole('button', { name: '완료', exact: true }).first()).toBeVisible();

    // localStorage 확인
    const data = await getStorageData(page);
    expect(data).toHaveLength(1);
    expect(data[0].title).toBe('장보기 목록');
    expect(data[0].completed).toBe(false);
    expect(data[0].dateType).toBe('none');
    expect(data[0].deadline).toBeNull();
    // UUID 형식 확인
    expect(data[0].id).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i);
  });

  test('TC-5.2: 날짜를 넣어 저장', async ({ page }) => {
    const today = todayStr();
    await createItem(page, { title: '보고서 제출', dateType: 'date', dateValue: today });

    // '오늘 중' 섹션에 표시
    await expect(page.getByText('오늘 중')).toBeVisible();
    await expect(page.getByText('보고서 제출')).toBeVisible();

    // 일시가 '○월 ○일' 형식으로 표시(월과 일을 포함)
    const now = new Date();
    const expectedDate = `${now.getMonth() + 1}월 ${now.getDate()}일`;
    await expect(page.getByText(expectedDate)).toBeVisible();

    // localStorage
    const data = await getStorageData(page);
    expect(data[0].dateType).toBe('date');
  });

  test('TC-5.3: 일시를 넣어 저장', async ({ page }) => {
    const todayDatetime = `${todayStr()}T14:30`;
    await createItem(page, { title: '회의', dateType: 'datetime', dateValue: todayDatetime });

    // '오늘 중' 섹션에 표시
    await expect(page.getByText('오늘 중')).toBeVisible();
    await expect(page.getByText('회의')).toBeVisible();

    // 일시가 '○월 ○일 ○시 ○분' 형식으로 표시
    const now = new Date();
    const expectedDateTime = `${now.getMonth() + 1}월 ${now.getDate()}일 14시 30분`;
    await expect(page.getByText(expectedDateTime)).toBeVisible();

    // localStorage
    const data = await getStorageData(page);
    expect(data[0].dateType).toBe('datetime');
  });

  test('TC-5.4: 상세를 넣어 저장', async ({ page }) => {
    await page.getByRole('button', { name: '신규' }).click();
    await page.getByPlaceholder('제목을 입력').fill('기획서 작성');

    // Quill 에디터에서 굵은 텍스트를 입력
    const editor = page.locator('.ql-editor');
    await editor.click();
    // 툴바의 굵게 버튼을 클릭
    await page.locator('.ql-bold').click();
    await editor.pressSequentially('중요한 내용');

    await page.getByRole('button', { name: '저장' }).click();

    // localStorage의 detail에 HTML이 들어 있다
    const data = await getStorageData(page);
    expect(data[0].detail).toContain('<strong>');
  });
});

// =============================================================
// 6. 편집
// =============================================================
test.describe('6. 편집', () => {
  test.beforeEach(async ({ page }) => {
    await resetApp(page);
  });

  test('TC-6.1: 편집 모드로 열기', async ({ page }) => {
    // 항목을 만든다
    await createItem(page, { title: '장보기 목록' });

    // 제목을 클릭
    await page.getByText('장보기 목록').click();

    // 모달이 열린다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).toBeVisible();

    // 제목에 '장보기 목록'이 들어 있다
    await expect(page.getByPlaceholder('제목을 입력')).toHaveValue('장보기 목록');

    // 일시 라디오 버튼이 등록 당시의 선택 상태(없음)
    await expect(page.getByLabel('없음')).toBeChecked();
  });

  test('TC-6.2: 제목을 편집해 저장', async ({ page }) => {
    await createItem(page, { title: '장보기 목록' });

    // 원래 ID를 가져온다
    const dataBefore = await getStorageData(page);
    const originalId = dataBefore[0].id;

    // 편집
    await page.getByText('장보기 목록').click();
    await page.getByPlaceholder('제목을 입력').fill('장보기 목록(수정)');
    await page.getByRole('button', { name: '저장' }).click();

    // 모달이 닫힌다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).not.toBeVisible();

    // 갱신 후의 표시
    await expect(page.getByText('장보기 목록(수정)')).toBeVisible();

    // id가 바뀌지 않았다
    const dataAfter = await getStorageData(page);
    expect(dataAfter[0].id).toBe(originalId);
  });

  test('TC-6.3: 일시를 바꿔 저장', async ({ page }) => {
    await createItem(page, { title: '작업X' });

    // '그 외'에 있다
    await expect(page.getByText('그 외')).toBeVisible();

    // 편집해 내일 날짜로 바꾼다
    await page.getByText('작업X').click();
    await page.getByLabel('날짜만').check();
    await page.locator('input[type="date"]').fill(tomorrowStr());
    await page.getByRole('button', { name: '저장' }).click();

    // '내일까지' 섹션으로 이동
    await expect(page.getByText('내일까지')).toBeVisible();
    await expect(page.getByText('작업X')).toBeVisible();
  });

  test('TC-6.4: 상세를 편집해 저장', async ({ page }) => {
    // 상세가 있는 항목을 localStorage에 직접 설정
    const data = [{
      id: 'edit-test-1',
      title: '상세가 있는 작업',
      detail: '<p>원래 상세 내용</p>',
      dateType: 'none',
      deadline: null,
      completed: false,
      createdAt: '2026-03-14T00:00:00',
    }];
    await setStorageAndReload(page, data);

    // 편집
    await page.getByText('상세가 있는 작업').click();
    const editor = page.locator('.ql-editor');
    await editor.fill('수정된 상세');
    await page.getByRole('button', { name: '저장' }).click();

    // localStorage가 갱신되어 있다
    const updatedData = await getStorageData(page);
    expect(updatedData[0].detail).toContain('수정된 상세');
  });

  test('TC-6.5: 편집을 취소', async ({ page }) => {
    await createItem(page, { title: '장보기 목록' });

    // 편집 모드로 열어 제목을 변경
    await page.getByText('장보기 목록').click();
    await page.getByPlaceholder('제목을 입력').fill('변경 후 제목');
    await page.getByRole('button', { name: '취소' }).click();

    // 원래 제목 그대로
    await expect(page.getByText('장보기 목록')).toBeVisible();
    await expect(page.getByText('변경 후 제목')).not.toBeVisible();
  });
});

// =============================================================
// 7. 완료/미완료 토글
// =============================================================
test.describe('7. 완료/미완료 토글', () => {
  test.beforeEach(async ({ page }) => {
    await resetApp(page);
  });

  test('TC-7.1: 미완료 → 완료', async ({ page }) => {
    await createItem(page, { title: '작업A' });

    // [완료] 버튼을 클릭
    await page.getByRole('button', { name: '완료', exact: true }).first().click();

    // 완료 상태 표시로 바뀐다(취소선이나 회색 처리 등)
    const item = page.getByText('작업A');
    await expect(item).toBeVisible();

    // localStorage의 completed가 true
    const data = await getStorageData(page);
    expect(data[0].completed).toBe(true);
  });

  test('TC-7.2: 완료 → 미완료', async ({ page }) => {
    // 완료 항목을 직접 설정
    const data = [{
      id: 'toggle-test-1',
      title: '작업A',
      detail: '',
      dateType: 'none',
      deadline: null,
      completed: true,
      createdAt: '2026-03-14T00:00:00',
    }];
    await setStorageAndReload(page, data);

    // 완료 버튼(토글)을 클릭
    await page.getByRole('button', { name: '완료', exact: true }).first().click();

    // localStorage의 completed가 false
    const updated = await getStorageData(page);
    expect(updated[0].completed).toBe(false);
  });
});

// =============================================================
// 8. 탭(필터)
// =============================================================
test.describe('8. 탭(필터)', () => {
  const testData = [
    { id: 'tab-1', title: '미완료A', detail: '', dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T01:00:00' },
    { id: 'tab-2', title: '미완료B', detail: '', dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T02:00:00' },
    { id: 'tab-3', title: '완료C', detail: '', dateType: 'none', deadline: null, completed: true, createdAt: '2026-03-14T03:00:00' },
  ];

  test.beforeEach(async ({ page }) => {
    await setStorageAndReload(page, testData);
  });

  test('TC-8.1: "전체" 탭', async ({ page }) => {
    await page.getByText('전체').click();
    await expect(page.getByText('미완료A')).toBeVisible();
    await expect(page.getByText('미완료B')).toBeVisible();
    await expect(page.getByText('완료C')).toBeVisible();
  });

  test('TC-8.2: "미완료" 탭', async ({ page }) => {
    await page.getByText('미완료', { exact: true }).click();
    await expect(page.getByText('미완료A')).toBeVisible();
    await expect(page.getByText('미완료B')).toBeVisible();
    await expect(page.getByText('완료C')).not.toBeVisible();

    // 탭이 활성 상태
    await expect(page.getByText('미완료', { exact: true })).toHaveClass(/active/);
  });

  test('TC-8.3: "완료" 탭', async ({ page }) => {
    await page.getByText('완료됨', { exact: true }).click();
    await expect(page.getByText('미완료A')).not.toBeVisible();
    await expect(page.getByText('미완료B')).not.toBeVisible();
    await expect(page.getByText('완료C')).toBeVisible();

    // 탭이 활성 상태
    await expect(page.getByText('완료됨', { exact: true })).toHaveClass(/active/);
  });

  test('TC-8.4: 탭 전환 후의 완료 토글', async ({ page }) => {
    await page.getByText('미완료', { exact: true }).click();

    // 미완료 2건이 표시
    await expect(page.getByText('미완료A')).toBeVisible();
    await expect(page.getByText('미완료B')).toBeVisible();

    // 1건을 완료로 만든다
    const buttons = page.getByRole('button', { name: '완료', exact: true });
    await buttons.first().click();

    // 완료된 항목이 목록에서 사라진다(미완료 필터이므로)
    const visibleItems = await page.getByText(/미완료[AB]/).count();
    expect(visibleItems).toBe(1);
  });

  test('TC-8.5: 탭 상태 유지', async ({ page }) => {
    await page.getByText('완료됨', { exact: true }).click();
    await expect(page.getByText('완료C')).toBeVisible();

    // 완료 토글을 클릭(미완료로 되돌린다)
    await page.getByRole('button', { name: '완료', exact: true }).first().click();

    // '완료됨' 탭 그대로일 것
    await expect(page.getByText('완료됨', { exact: true })).toHaveClass(/active/);
  });
});

// =============================================================
// 9. [완료됨을 삭제]
// =============================================================
test.describe('9. [완료됨을 삭제]', () => {
  test('TC-9.1: 확인 대화상자에서 OK', async ({ page }) => {
    const data = [
      { id: 'del-1', title: '완료A', detail: '', dateType: 'none', deadline: null, completed: true, createdAt: '2026-03-14T01:00:00' },
      { id: 'del-2', title: '완료B', detail: '', dateType: 'none', deadline: null, completed: true, createdAt: '2026-03-14T02:00:00' },
      { id: 'del-3', title: '미완료C', detail: '', dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T03:00:00' },
    ];
    await setStorageAndReload(page, data);

    // 대화상자에서 OK를 누른다
    page.on('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: '완료됨을 삭제' }).click();

    // 완료는 삭제되고 미완료는 남는다
    await expect(page.getByText('완료A')).not.toBeVisible();
    await expect(page.getByText('완료B')).not.toBeVisible();
    await expect(page.getByText('미완료C')).toBeVisible();

    // localStorage 확인
    const updated = await getStorageData(page);
    expect(updated).toHaveLength(1);
    expect(updated[0].title).toBe('미완료C');
  });

  test('TC-9.2: 확인 대화상자에서 취소', async ({ page }) => {
    const data = [
      { id: 'del-1', title: '완료A', detail: '', dateType: 'none', deadline: null, completed: true, createdAt: '2026-03-14T01:00:00' },
    ];
    await setStorageAndReload(page, data);

    // 대화상자에서 취소를 누른다
    page.on('dialog', dialog => dialog.dismiss());
    await page.getByRole('button', { name: '완료됨을 삭제' }).click();

    // 삭제되지 않았다
    await expect(page.getByText('완료A')).toBeVisible();
    const updated = await getStorageData(page);
    expect(updated).toHaveLength(1);
  });

  test('TC-9.3: 완료 항목이 없는 상태로 삭제', async ({ page }) => {
    const data = [
      { id: 'del-1', title: '미완료A', detail: '', dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T01:00:00' },
    ];
    await setStorageAndReload(page, data);

    page.on('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: '완료됨을 삭제' }).click();

    // 미완료는 삭제되지 않는다
    await expect(page.getByText('미완료A')).toBeVisible();
    const updated = await getStorageData(page);
    expect(updated).toHaveLength(1);
  });
});

// =============================================================
// 10. 섹션 나누기
// =============================================================
test.describe('10. 섹션 나누기', () => {
  test('TC-10.1: "오늘 중" 섹션', async ({ page }) => {
    await resetApp(page);
    const today = todayStr();
    await createItem(page, { title: '오늘 작업', dateType: 'date', dateValue: today });
    await expect(page.getByText('오늘 중')).toBeVisible();
    await expect(page.getByText('오늘 작업')).toBeVisible();
  });

  test('TC-10.2: "내일까지" 섹션', async ({ page }) => {
    await resetApp(page);
    const tomorrow = tomorrowStr();
    await createItem(page, { title: '내일 작업', dateType: 'date', dateValue: tomorrow });
    await expect(page.getByText('내일까지')).toBeVisible();
    await expect(page.getByText('내일 작업')).toBeVisible();
  });

  test('TC-10.3: "그 외" 섹션 - 모레 이후', async ({ page }) => {
    await resetApp(page);
    const dayAfter = dayAfterTomorrowStr();
    await createItem(page, { title: '미래 작업', dateType: 'date', dateValue: dayAfter });
    await expect(page.getByText('그 외')).toBeVisible();
    await expect(page.getByText('미래 작업')).toBeVisible();
  });

  test('TC-10.4: "그 외" 섹션 - 일시 없음', async ({ page }) => {
    await resetApp(page);
    await createItem(page, { title: '기한 없는 작업' });
    await expect(page.getByText('그 외')).toBeVisible();
    await expect(page.getByText('기한 없는 작업')).toBeVisible();
  });

  test('TC-10.5: 빈 섹션 숨기기', async ({ page }) => {
    await resetApp(page);
    // 내일 작업만 등록 → '오늘 중'은 표시되지 않음
    const tomorrow = tomorrowStr();
    await createItem(page, { title: '내일만', dateType: 'date', dateValue: tomorrow });
    await expect(page.getByText('오늘 중')).not.toBeVisible();
    await expect(page.getByText('내일까지')).toBeVisible();
  });

  test('TC-10.6: dateType "date"의 경계 판정', async ({ page }) => {
    // 내일 날짜를 '날짜만'으로 설정 → deadline은 내일 00:00:00
    // → 오늘 시점에는 '내일까지'로 분류된다
    await resetApp(page);
    const tomorrow = tomorrowStr();
    await createItem(page, { title: '경계 테스트', dateType: 'date', dateValue: tomorrow });

    // '내일까지'에 표시될 것
    await expect(page.getByText('내일까지')).toBeVisible();
    await expect(page.getByText('경계 테스트')).toBeVisible();
  });
});

// =============================================================
// 11. 정렬 순서
// =============================================================
test.describe('11. 정렬 순서', () => {
  test('TC-11.1: deadline 있음 → 없음 순서', async ({ page }) => {
    const today = todayStr();
    const data = [
      { id: 'sort-1', title: '기한 없음', detail: '', dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T01:00:00' },
      { id: 'sort-2', title: '기한 있음', detail: '', dateType: 'date', deadline: `${today}T00:00:00`, completed: false, createdAt: '2026-03-14T02:00:00' },
    ];
    await setStorageAndReload(page, data);

    // '그 외' 섹션 안에서 '기한 있음' → '기한 없음' 순서(다만 기한이 있으면 '오늘 중'으로 갈 수 있음)
    // 전체 표시로 확인: 기한이 있는 쪽이 먼저 오는 섹션이 있어야 한다
    const allItems = page.locator('[class*="todo-item"], [class*="item"], li, tr').filter({ hasText: /기한/ });
    const texts = await allItems.allTextContents();
    const idxWithDeadline = texts.findIndex(t => t.includes('기한 있음'));
    const idxWithout = texts.findIndex(t => t.includes('기한 없음'));
    // 같은 섹션에 있으면 deadline이 있는 쪽이 먼저
    // 섹션이 다르면 오늘 중 > 그 외이므로 어느 쪽이든 먼저 온다
    expect(idxWithDeadline).toBeLessThan(idxWithout);
  });

  test('TC-11.2: deadline 오름차순', async ({ page }) => {
    const today = todayStr();
    const data = [
      { id: 'sort-1', title: '늦은 쪽', detail: '', dateType: 'datetime', deadline: `${today}T18:00:00`, completed: false, createdAt: '2026-03-14T01:00:00' },
      { id: 'sort-2', title: '이른 쪽', detail: '', dateType: 'datetime', deadline: `${today}T09:00:00`, completed: false, createdAt: '2026-03-14T02:00:00' },
    ];
    await setStorageAndReload(page, data);

    // '오늘 중' 섹션 안에서 '이른 쪽' → '늦은 쪽' 순서
    const section = page.getByText('오늘 중').locator('..');
    const items = page.locator('[class*="todo-item"], [class*="item"], li, tr').filter({ hasText: /쪽/ });
    const texts = await items.allTextContents();
    const idxEarly = texts.findIndex(t => t.includes('이른 쪽'));
    const idxLate = texts.findIndex(t => t.includes('늦은 쪽'));
    expect(idxEarly).toBeLessThan(idxLate);
  });

  test('TC-11.3: deadline이 같으면 createdAt 오름차순', async ({ page }) => {
    const today = todayStr();
    const data = [
      { id: 'sort-1', title: '새로 생성', detail: '', dateType: 'datetime', deadline: `${today}T12:00:00`, completed: false, createdAt: '2026-03-14T10:00:00' },
      { id: 'sort-2', title: '예전 생성', detail: '', dateType: 'datetime', deadline: `${today}T12:00:00`, completed: false, createdAt: '2026-03-14T08:00:00' },
    ];
    await setStorageAndReload(page, data);

    const items = page.locator('[class*="todo-item"], [class*="item"], li, tr').filter({ hasText: /생성/ });
    const texts = await items.allTextContents();
    const idxOld = texts.findIndex(t => t.includes('예전 생성'));
    const idxNew = texts.findIndex(t => t.includes('새로 생성'));
    expect(idxOld).toBeLessThan(idxNew);
  });

  test('TC-11.4: deadline이 없는 것끼리는 createdAt 오름차순', async ({ page }) => {
    const data = [
      { id: 'sort-1', title: '새 작업', detail: '', dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T10:00:00' },
      { id: 'sort-2', title: '오래된 작업', detail: '', dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T08:00:00' },
    ];
    await setStorageAndReload(page, data);

    const items = page.locator('[class*="todo-item"], [class*="item"], li, tr').filter({ hasText: /작업/ });
    const texts = await items.allTextContents();
    const idxOld = texts.findIndex(t => t.includes('오래된 작업'));
    const idxNew = texts.findIndex(t => t.includes('새 작업'));
    expect(idxOld).toBeLessThan(idxNew);
  });
});

// =============================================================
// 12. 일시의 표시 형식
// =============================================================
test.describe('12. 일시의 표시 형식', () => {
  test('TC-12.1: dateType "none"의 표시', async ({ page }) => {
    const data = [{
      id: 'fmt-1', title: '일시 없는 작업', detail: '', dateType: 'none',
      deadline: null, completed: false, createdAt: '2026-03-14T00:00:00',
    }];
    await setStorageAndReload(page, data);

    // '일시 없는 작업' 행에 일시 표시가 없다
    await expect(page.getByText('일시 없는 작업')).toBeVisible();
    // 월일이 들어 있지 않은지 확인(같은 행 안에서)
    const itemRow = page.getByText('일시 없는 작업').locator('..');
    const rowText = await itemRow.textContent();
    expect(rowText).not.toMatch(/월.*일/);
  });

  test('TC-12.2: dateType "date"의 표시', async ({ page }) => {
    const data = [{
      id: 'fmt-2', title: '날짜 작업', detail: '', dateType: 'date',
      deadline: '2026-03-15T00:00:00', completed: false, createdAt: '2026-03-14T00:00:00',
    }];
    await setStorageAndReload(page, data);

    // '3월 15일'로 표시된다
    await expect(page.getByText('3월 15일')).toBeVisible();
    // 시분은 표시되지 않는다
    await expect(page.getByText(/3월 15일 \d+시/)).not.toBeVisible();
  });

  test('TC-12.3: dateType "datetime"의 표시', async ({ page }) => {
    const data = [{
      id: 'fmt-3', title: '일시 작업', detail: '', dateType: 'datetime',
      deadline: '2026-03-15T14:30:00', completed: false, createdAt: '2026-03-14T00:00:00',
    }];
    await setStorageAndReload(page, data);

    // '3월 15일 14시 30분'으로 표시된다
    await expect(page.getByText('3월 15일 14시 30분')).toBeVisible();
  });
});

// =============================================================
// 13. Quill 에디터
// =============================================================
test.describe('13. Quill 에디터', () => {
  test.beforeEach(async ({ page }) => {
    await resetApp(page);
  });

  test('TC-13.1: 툴바 구성', async ({ page }) => {
    await page.getByRole('button', { name: '신규' }).click();

    // 툴바 버튼 확인
    await expect(page.locator('.ql-bold')).toBeVisible();
    await expect(page.locator('.ql-italic')).toBeVisible();
    await expect(page.locator('.ql-underline')).toBeVisible();
    await expect(page.locator('.ql-list[value="bullet"]')).toBeVisible();
    await expect(page.locator('.ql-list[value="ordered"]')).toBeVisible();
  });

  test('TC-13.2: 리치 텍스트 입력', async ({ page }) => {
    await page.getByRole('button', { name: '신규' }).click();
    await page.getByPlaceholder('제목을 입력').fill('리치 테스트');

    const editor = page.locator('.ql-editor');
    await editor.click();

    // 굵게로 입력
    await page.locator('.ql-bold').click();
    await editor.pressSequentially('굵은 텍스트');
    await page.locator('.ql-bold').click(); // 굵게 해제

    // 글머리 기호
    await page.keyboard.press('Enter');
    await page.locator('.ql-list[value="bullet"]').click();
    await editor.pressSequentially('목록 항목');

    await page.getByRole('button', { name: '저장' }).click();

    // localStorage 확인
    const data = await getStorageData(page);
    expect(data[0].detail).toContain('<strong>');
    expect(data[0].detail).toMatch(/<li/);
  });

  test('TC-13.3: 편집할 때 리치 텍스트 복원', async ({ page }) => {
    // 리치 텍스트가 들어간 데이터를 설정
    const data = [{
      id: 'quill-1', title: '리치 복원 테스트',
      detail: '<p><strong>굵게</strong></p><ul><li>목록 항목</li></ul>',
      dateType: 'none', deadline: null, completed: false, createdAt: '2026-03-14T00:00:00',
    }];
    await setStorageAndReload(page, data);

    // 편집 모드로 연다
    await page.getByText('리치 복원 테스트').click();

    // Quill 에디터 안에 굵게와 목록이 복원되어 있다
    const editor = page.locator('.ql-editor');
    await expect(editor.locator('strong')).toHaveText('굵게');
    await expect(editor.locator('li')).toHaveText('목록 항목');
  });
});

// =============================================================
// 14. 데이터 영속화(localStorage)
// =============================================================
test.describe('14. 데이터 영속화', () => {
  test('TC-14.1: 저장 후 새로고침', async ({ page }) => {
    await resetApp(page);
    await createItem(page, { title: '항목1' });
    await createItem(page, { title: '항목2' });
    await createItem(page, { title: '항목3' });

    // 새로고침
    await page.reload();
    await page.waitForLoadState('domcontentloaded');

    // 3건 모두 표시된다
    await expect(page.getByText('항목1')).toBeVisible();
    await expect(page.getByText('항목2')).toBeVisible();
    await expect(page.getByText('항목3')).toBeVisible();
  });

  test('TC-14.2: 완료 상태의 새로고침', async ({ page }) => {
    await resetApp(page);
    await createItem(page, { title: '완료 테스트' });

    // 완료로 만든다
    await page.getByRole('button', { name: '완료', exact: true }).first().click();

    // 새로고침
    await page.reload();
    await page.waitForLoadState('domcontentloaded');

    // 완료 상태가 유지
    const data = await getStorageData(page);
    expect(data[0].completed).toBe(true);
  });

  test('TC-14.3: 삭제 후 새로고침', async ({ page }) => {
    const data = [
      { id: 'persist-1', title: '삭제 대상', detail: '', dateType: 'none', deadline: null, completed: true, createdAt: '2026-03-14T00:00:00' },
    ];
    await setStorageAndReload(page, data);

    // 완료됨을 삭제
    page.on('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: '완료됨을 삭제' }).click();

    // 새로고침
    await page.reload();
    await page.waitForLoadState('domcontentloaded');

    // 되살아나지 않는다
    await expect(page.getByText('삭제 대상')).not.toBeVisible();
    const updated = await getStorageData(page);
    expect(updated).toHaveLength(0);
  });

  test('TC-14.4: 데이터 형식 확인', async ({ page }) => {
    await resetApp(page);
    await createItem(page, { title: '형식 테스트', dateType: 'date', dateValue: todayStr() });

    const data = await getStorageData(page);
    expect(data).toHaveLength(1);

    const item = data[0];
    // 모든 필드가 있다
    expect(item).toHaveProperty('id');
    expect(item).toHaveProperty('title');
    expect(item).toHaveProperty('detail');
    expect(item).toHaveProperty('dateType');
    expect(item).toHaveProperty('deadline');
    expect(item).toHaveProperty('completed');
    expect(item).toHaveProperty('createdAt');

    // UUID v4 형식
    expect(item.id).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i);

    // ISO 8601 형식
    expect(new Date(item.createdAt).toISOString()).toBeTruthy();
  });
});

// =============================================================
// 15. 복합 시나리오
// =============================================================
test.describe('15. 복합 시나리오', () => {
  test('TC-15.1: 전체 기능을 거치는 E2E 테스트', async ({ page }) => {
    await resetApp(page);

    // 1. 초기 상태 → 항목 없음
    await expect(page.getByText('오늘 중')).not.toBeVisible();
    await expect(page.getByText('내일까지')).not.toBeVisible();
    await expect(page.getByText('그 외')).not.toBeVisible();

    // 2. 항목A(오늘, 시각까지)를 등록
    const todayDatetime = `${todayStr()}T14:00`;
    await createItem(page, { title: '항목A', dateType: 'datetime', dateValue: todayDatetime });
    await expect(page.getByText('오늘 중')).toBeVisible();
    await expect(page.getByText('항목A')).toBeVisible();

    // 3. 항목B(내일, 날짜만)를 등록
    await createItem(page, { title: '항목B', dateType: 'date', dateValue: tomorrowStr() });
    await expect(page.getByText('내일까지')).toBeVisible();
    await expect(page.getByText('항목B')).toBeVisible();

    // 4. 항목C(일시 없음)를 등록
    await createItem(page, { title: '항목C' });
    await expect(page.getByText('그 외')).toBeVisible();
    await expect(page.getByText('항목C')).toBeVisible();

    // 5. '미완료' 탭 → 3건 표시
    await page.getByText('미완료', { exact: true }).click();
    await expect(page.getByText('항목A')).toBeVisible();
    await expect(page.getByText('항목B')).toBeVisible();
    await expect(page.getByText('항목C')).toBeVisible();

    // 6. 항목A를 완료로 만든다
    // 항목A 행에 있는 완료 버튼을 클릭
    const itemARow = page.getByText('항목A').locator('..');
    await itemARow.getByRole('button', { name: '완료', exact: true }).click();

    // 7. '완료됨' 탭 → 항목A만
    await page.getByText('완료됨', { exact: true }).click();
    await expect(page.getByText('항목A')).toBeVisible();
    await expect(page.getByText('항목B')).not.toBeVisible();
    await expect(page.getByText('항목C')).not.toBeVisible();

    // 8. '미완료' 탭 → B, C만
    await page.getByText('미완료', { exact: true }).click();
    await expect(page.getByText('항목A')).not.toBeVisible();
    await expect(page.getByText('항목B')).toBeVisible();
    await expect(page.getByText('항목C')).toBeVisible();

    // 9. 항목B를 편집
    await page.getByText('전체').click();
    await page.getByText('항목B').click();
    await page.getByPlaceholder('제목을 입력').fill('항목B(수정)');
    await page.getByRole('button', { name: '저장' }).click();
    await expect(page.getByText('항목B(수정)')).toBeVisible();

    // 10. [완료됨을 삭제] → 항목A가 삭제
    page.on('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: '완료됨을 삭제' }).click();
    await expect(page.getByText('항목A')).not.toBeVisible();

    // 11. '전체' 탭 → B(수정), C 2건
    await page.getByText('전체').click();
    await expect(page.getByText('항목B(수정)')).toBeVisible();
    await expect(page.getByText('항목C')).toBeVisible();
    const data = await getStorageData(page);
    expect(data).toHaveLength(2);

    // 12. 새로고침 → 같은 상태
    await page.reload();
    await page.waitForLoadState('domcontentloaded');
    await expect(page.getByText('항목B(수정)')).toBeVisible();
    await expect(page.getByText('항목C')).toBeVisible();
  });

  test('TC-15.2: 대량 데이터', async ({ page }) => {
    const today = todayStr();
    const tomorrow = tomorrowStr();
    const dayAfter = dayAfterTomorrowStr();
    const items = [];

    for (let i = 0; i < 50; i++) {
      let dateType, deadline;
      if (i % 3 === 0) {
        dateType = 'datetime';
        deadline = `${today}T${String(10 + (i % 12)).padStart(2, '0')}:00:00`;
      } else if (i % 3 === 1) {
        dateType = 'date';
        deadline = `${tomorrow}T00:00:00`;
      } else {
        dateType = 'none';
        deadline = null;
      }
      items.push({
        id: `bulk-${i}`,
        title: `작업${String(i).padStart(3, '0')}`,
        detail: '',
        dateType,
        deadline,
        completed: i % 5 === 0,
        createdAt: `2026-03-14T${String(i % 24).padStart(2, '0')}:00:00`,
      });
    }
    await setStorageAndReload(page, items);

    // 각 섹션이 표시된다
    await expect(page.getByText('오늘 중')).toBeVisible();
    await expect(page.getByText('내일까지')).toBeVisible();
    await expect(page.getByText('그 외')).toBeVisible();

    // '전체' 탭에서 50건 표시
    const allItems = await page.locator('[class*="todo-item"], [class*="item"]').filter({ hasText: '작업' }).count();
    expect(allItems).toBe(50);
  });
});

// =============================================================
// 16. 오류 케이스·경곗값
// =============================================================
test.describe('16. 오류 케이스·경곗값', () => {
  test('TC-16.1: localStorage가 비어 있는 경우', async ({ page }) => {
    await page.goto(APP_URL);
    await page.evaluate((key) => localStorage.removeItem(key), STORAGE_KEY);
    await page.reload();
    await page.waitForLoadState('domcontentloaded');

    // 오류 없이 표시된다
    await expect(page.getByText('ToDo 리스트')).toBeVisible();
    await expect(page.getByText('오늘 중')).not.toBeVisible();
  });

  test('TC-16.2: localStorage에 잘못된 데이터', async ({ page }) => {
    await page.goto(APP_URL);
    await page.evaluate((key) => {
      localStorage.setItem(key, '이것은 잘못된 JSON입니다{{{');
    }, STORAGE_KEY);
    await page.reload();
    await page.waitForLoadState('domcontentloaded');

    // 죽지 않고 표시된다
    await expect(page.getByText('ToDo 리스트')).toBeVisible();
  });

  test('TC-16.3: 제목의 경곗값(1자)', async ({ page }) => {
    await resetApp(page);
    await createItem(page, { title: '가' });

    // 정상적으로 저장된다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).not.toBeVisible();
    await expect(page.getByText('가')).toBeVisible();
  });

  test('TC-16.4: 상세가 비어 있는 채로 저장', async ({ page }) => {
    await resetApp(page);
    await createItem(page, { title: '상세 없는 작업' });

    // 정상적으로 저장된다
    const modal = page.locator('#modal, .modal, [role="dialog"]');
    await expect(modal).not.toBeVisible();
    await expect(page.getByText('상세 없는 작업')).toBeVisible();
  });
});
