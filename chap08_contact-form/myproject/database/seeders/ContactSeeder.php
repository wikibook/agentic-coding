<?php

namespace Database\Seeders;

use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * 문의 데이터를 100건 투입한다
     */
    public function run(): void
    {
        // 한국어 성(50종)
        $lastNames = [
            '김', '이', '박', '최', '정',
            '강', '조', '윤', '장', '임',
            '한', '오', '서', '신', '권',
            '황', '안', '송', '전', '홍',
            '유', '고', '문', '양', '손',
            '배', '백', '허', '남', '심',
            '노', '하', '곽', '성', '차',
            '주', '우', '구', '민', '류',
            '나', '진', '지', '엄', '채',
            '원', '천', '방', '공', '현',
        ];

        // 남성 이름(25종)
        $maleFirstNames = [
            '민준', '서준', '도윤', '예준', '시우',
            '지호', '하준', '주원', '건우', '현우',
            '지후', '준우', '유준', '은우', '선우',
            '정우', '연우', '서진', '이준', '시윤',
            '준서', '도현', '승우', '지훈', '민재',
        ];

        // 여성 이름(25종)
        $femaleFirstNames = [
            '서연', '서윤', '지우', '하은', '하윤',
            '지민', '수아', '예은', '채원', '민서',
            '지아', '지유', '다은', '은서', '소율',
            '예린', '수빈', '시은', '유나', '채은',
            '지윤', '아린', '나윤', '유진', '다인',
        ];

        // 쇼핑 사이트를 가정한 제목과 본문 템플릿
        $templates = [
            [
                'subject' => '상품이 도착하지 않습니다',
                'body' => '주문번호 %s로 주문한 상품인데 발송 예정일이 지나도 도착하지 않았습니다. 현재 배송 상황을 알려주실 수 있을까요?',
            ],
            [
                'subject' => '도착한 상품이 파손되어 있었습니다',
                'body' => '오늘 도착한 상품을 확인해 보니 겉상자가 찌그러져 있고 내용물에도 흠집이 있었습니다. 교환이나 반품 처리를 부탁드립니다.',
            ],
            [
                'subject' => '주문을 취소하고 싶습니다',
                'body' => '조금 전 주문번호 %s로 주문했는데 사이즈를 잘못 골랐습니다. 취소가 가능할까요? 아직 발송 전이라면 취소를 부탁드립니다.',
            ],
            [
                'subject' => '반품·교환에 관하여',
                'body' => '구매한 상품의 사이즈가 맞지 않아 교환을 부탁드리고 싶습니다. 반품·교환 절차를 알려주세요.',
            ],
            [
                'subject' => '상품 재고에 관하여',
                'body' => '상품번호 %s 상품을 구매하고 싶은데 품절 상태입니다. 재입고 예정이 있을까요?',
            ],
            [
                'subject' => '도착한 상품이 주문과 다릅니다',
                'body' => '주문번호 %s 상품이 도착했는데 주문한 상품과 다른 것이 왔습니다. 올바른 상품으로 교환을 부탁드립니다.',
            ],
            [
                'subject' => '쿠폰이 적용되지 않습니다',
                'body' => '메일로 받은 쿠폰 코드 \'%s\'를 입력했는데 할인이 적용되지 않습니다. 사용 조건을 확인해 주실 수 있을까요?',
            ],
            [
                'subject' => '영수증 발행을 부탁드립니다',
                'body' => '주문번호 %s 구매 건에 대해 영수증 발행을 부탁드립니다. 수신인은 \'%s\'로 부탁드립니다.',
            ],
            [
                'subject' => '포인트가 지급되지 않았습니다',
                'body' => '며칠 전 구매한 주문번호 %s 건의 포인트가 아직 지급되지 않은 것 같습니다. 확인을 부탁드립니다.',
            ],
            [
                'subject' => '배송지 변경을 부탁드립니다',
                'body' => '주문번호 %s 상품인데 배송지 주소를 변경하고 싶습니다. 아직 발송 전이라면 변경이 가능할까요?',
            ],
            [
                'subject' => '비밀번호를 잊어버렸습니다',
                'body' => '로그인 비밀번호를 잊어버려 로그인할 수 없게 되었습니다. 비밀번호 재설정 방법을 알려주실 수 있을까요?',
            ],
            [
                'subject' => '회원 정보 변경에 관하여',
                'body' => '이사를 해서 등록된 주소와 전화번호를 변경하고 싶습니다. 마이페이지에서 변경이 되지 않는데 방법을 알려주세요.',
            ],
            [
                'subject' => '결제 수단 변경',
                'body' => '주문번호 %s의 결제 수단을 신용카드에서 무통장 입금으로 변경하고 싶은데 가능할까요?',
            ],
            [
                'subject' => '배송비를 알려주세요',
                'body' => '도서 지역 배송을 생각하고 있습니다. 배송비는 얼마인가요? 그리고 무료 배송 조건이 있다면 알려주세요.',
            ],
            [
                'subject' => '선물 포장에 관하여',
                'body' => '선물용으로 구매하고 싶은데 선물 포장 서비스가 있나요? 가능하다면 추가 요금도 알려주세요.',
            ],
            [
                'subject' => '상품 리뷰를 등록할 수 없습니다',
                'body' => '구매한 상품의 리뷰를 쓰려고 했는데 오류가 나서 등록할 수 없습니다. 브라우저는 Chrome을 쓰고 있습니다.',
            ],
            [
                'subject' => '회원 탈퇴 절차에 관하여',
                'body' => '탈퇴를 생각하고 있습니다. 탈퇴 절차와 보유한 포인트가 어떻게 되는지 알려주세요.',
            ],
            [
                'subject' => '상품 사용법에 관한 질문',
                'body' => '상품번호 %s를 구매했는데 설명서 내용이 이해하기 어려워 사용법을 잘 모르겠습니다. 자세한 사용 방법을 알려주실 수 있을까요?',
            ],
            [
                'subject' => '세일 정보에 관하여',
                'body' => '다음 세일은 언제쯤 열리나요? 그리고 세일 대상 상품을 미리 알 수 있는 방법이 있나요?',
            ],
            [
                'subject' => '주문 확인 메일이 오지 않습니다',
                'body' => '주문을 완료했는데 확인 메일이 오지 않았습니다. 주문이 정상적으로 완료되었는지 확인해 주실 수 있을까요? 이메일 주소는 %s입니다.',
            ],
            [
                'subject' => '배송 일시 지정에 관하여',
                'body' => '주문번호 %s 상품인데 배송 일시를 지정하고 싶습니다. 평일 오전 중에 받을 수 있을까요?',
            ],
            [
                'subject' => '상품 보증에 관하여',
                'body' => '구매한 상품의 보증 기간과 보증 내용을 알려주세요. 그리고 보증서가 함께 들어 있나요?',
            ],
            [
                'subject' => '청구 금액이 잘못되었습니다',
                'body' => '주문번호 %s의 신용카드 명세를 확인해 보니 사이트에 표시된 금액과 청구액이 다릅니다. 확인을 부탁드립니다.',
            ],
            [
                'subject' => '상품 페이지 정보에 오류가 있습니다',
                'body' => '상품번호 %s 페이지인데 상품 설명과 스펙 표의 내용이 일치하지 않는 것 같습니다. 올바른 정보를 알려주세요.',
            ],
            [
                'subject' => '대량 구매 할인이 있나요',
                'body' => '같은 상품을 5개 이상 구매하고 싶은데 대량 구매 할인이 있나요? 법인 구매도 생각하고 있습니다.',
            ],
        ];

        $statuses = ['신규', '대응 중', '해결 완료'];
        $now = Carbon::now();
        $threeMonthsAgo = $now->copy()->subMonths(3);

        $records = [];

        for ($i = 0; $i < 100; $i++) {
            $lastName = $lastNames[array_rand($lastNames)];

            // 남녀 절반씩(짝수=남성, 홀수=여성)
            if ($i % 2 === 0) {
                $firstName = $maleFirstNames[array_rand($maleFirstNames)];
            } else {
                $firstName = $femaleFirstNames[array_rand($femaleFirstNames)];
            }

            $name = $lastName . $firstName;

            // 이메일 주소 생성(로마자 이름 + 일련번호)
            $emailName = 'user' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $email = $emailName . '@example.com';

            $template = $templates[array_rand($templates)];
            $subject = $template['subject'];

            // 본문의 자리표시자를 채운다
            $orderNumber = 'ORD-' . rand(100000, 999999);
            $couponCode = 'SAVE' . rand(10, 50);
            $productNumber = 'PRD-' . rand(1000, 9999);
            $body = sprintf(
                $template['body'],
                $orderNumber,
                $couponCode,
                $productNumber
            );

            $status = $statuses[array_rand($statuses)];

            // 최근 3개월 범위의 임의 일시
            $randomTimestamp = rand($threeMonthsAgo->timestamp, $now->timestamp);
            $createdAt = Carbon::createFromTimestamp($randomTimestamp);

            $records[] = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'body' => $body,
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        // created_at 순으로 정렬해 투입
        usort($records, fn ($a, $b) => $a['created_at'] <=> $b['created_at']);

        foreach ($records as $record) {
            Contact::create($record);
        }
    }
}
