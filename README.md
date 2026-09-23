# sendgo/symfony

> **Symfony에서 카카오 알림톡, 브랜드메시지, SMS를 가장 쉽게 발송하는 공식 Symfony 번들**

[![Packagist](https://img.shields.io/packagist/v/sendgo/symfony)](https://packagist.org/packages/sendgo/symfony)
[![Symfony](https://img.shields.io/badge/Symfony-6.4%20%7C%207-000000?logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

`sendgo/symfony`는 [`sendgo/php`](https://github.com/send-go/php) 코어를 확장한 **Symfony 전용 번들**입니다.
DI 컨테이너 서비스 자동 등록, 설정(config) 통합, 오토와이어링을 완벽하게 제공합니다.

---

## 목차

- [설치](#설치)
- [빠른 시작](#빠른-시작)
- [오토와이어링 사용법](#오토와이어링-사용법)
- [상세 사용법](#상세-사용법)
  - [알림톡](#알림톡)
  - [친구톡](#친구톡)
  - [SMS / LMS / MMS](#sms--lms--mms)
- [서비스 클래스 패턴](#서비스-클래스-패턴)
- [Messenger 비동기 발송](#messenger-비동기-발송)
- [예외 처리](#예외-처리)
- [설정 옵션](#설정-옵션)
- [자주 묻는 질문](#자주-묻는-질문-faq)

---

## 설치

```bash
composer require sendgo/symfony
```

### 번들 등록

[Symfony Flex](https://symfony.com/doc/current/setup/flex.html)를 사용하는 경우 번들이 자동으로 등록됩니다.
Flex를 사용하지 않는다면 `config/bundles.php`에 직접 추가하세요.

```php
<?php
// config/bundles.php

return [
    // ...
    Sendgo\Symfony\SendgoBundle::class => ['all' => true],
];
```

---

## 빠른 시작

### 1단계 — 환경변수 설정 (`.env`)

```env
SENDGO_ACCESS_KEY=your_access_key
SENDGO_SECRET_KEY=your_secret_key
SENDGO_KAKAO_SENDER_KEY=your_kakao_key
SENDGO_SMS_SENDER_KEY=your_sms_key
SENDGO_API_VERSION=v2
```

### 2단계 — 번들 설정 파일 (`config/packages/sendgo.yaml`)

```yaml
# config/packages/sendgo.yaml
sendgo:
    access_key:       '%env(SENDGO_ACCESS_KEY)%'
    secret_key:       '%env(SENDGO_SECRET_KEY)%'
    kakao_sender_key: '%env(SENDGO_KAKAO_SENDER_KEY)%'
    sms_sender_key:   '%env(SENDGO_SMS_SENDER_KEY)%'
    api_version:      '%env(SENDGO_API_VERSION)%'
    url:              'https://sendgo.io'
```

### 3단계 — 알림톡 전송

```php
<?php
// src/Controller/OrderController.php

namespace App\Controller;

use App\Entity\Order;
use Sendgo\Php\Sendgo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    public function __construct(private Sendgo $sendgo) {}

    #[Route('/orders/{id}/confirm', methods: ['POST'])]
    public function confirm(Order $order): JsonResponse
    {
        $this->sendgo->alimtalk->send([
            'templateCode' => 'ORDER_CONFIRM_001',
            'contacts'     => [
                [
                    'contact' => $order->getUser()->getPhone(),
                    'name'    => $order->getUser()->getName(),
                    'var1'    => $order->getNumber(),
                    'var2'    => number_format($order->getTotal()) . '원',
                ],
            ],
        ]);

        return $this->json(['success' => true]);
    }
}
```

---

## 오토와이어링 사용법

번들이 `Sendgo\Php\Sendgo` 서비스를 컨테이너에 등록하므로, 생성자에 타입힌트만 하면
자동으로 주입됩니다. 별도의 서비스 정의는 필요하지 않습니다.

```php
<?php

use Sendgo\Php\Sendgo;

class MyService
{
    // 생성자 오토와이어링으로 자동 주입
    public function __construct(private Sendgo $sendgo) {}
}
```

`sendgo` 서비스 ID로 직접 컨테이너에서 가져올 수도 있습니다.

```php
$sendgo = $container->get('sendgo'); // Sendgo\Php\Sendgo 인스턴스
```

---

## 상세 사용법

### 알림톡

```php
<?php

use Sendgo\Php\Sendgo;

// 다건 발송
$sendgo->alimtalk->send([
    'templateCode' => 'ORDER_CONFIRM_001',
    'contacts'     => [
        ['contact' => '01011111111', 'name' => '홍길동', 'var1' => 'ORD-001', 'var2' => '29,000원'],
        ['contact' => '01022222222', 'name' => '김철수', 'var1' => 'ORD-002', 'var2' => '15,000원'],
        ['contact' => '01033333333', 'name' => '이영희', 'var1' => 'ORD-003', 'var2' => '52,000원'],
    ],
]);

// 예약 발송
$sendgo->alimtalk->send([
    'templateCode' => 'PROMO_SUMMER_2026',
    'scheduleType' => 'SCHEDULED',
    'at'           => '2026-07-28 09:00:00',
    'contacts'     => [['contact' => '01012345678', 'var1' => '여름 한정 50% 할인']],
]);

// SMS 자동 대체 발송
$sendgo->alimtalk->send([
    'templateCode' => 'DELIVERY_START_001',
    'replaceSms'   => 'Y',
    'smsSubject'   => '[배송 시작 안내]',
    'smsContent'   => "주문하신 상품이 출고되었습니다.\n송장번호: #{var2}",
    'contacts'     => [['contact' => '01012345678', 'var1' => 'ORD-001', 'var2' => '1234567890']],
]);
```

### 친구톡

> ⚠️ **Deprecated — 친구톡은 카카오 정책에 따라 2025-12-31 종료되었습니다.**
> 2026-01-01 부터 친구톡 발송 요청은 카카오 측에서 **브랜드메시지(자유형)** 로 자동 대체 발송됩니다.
> 호출은 계속 성공하며, 자유 본문 타입(`FT`/`FI`/`FW`)을 개별 수신자에게 보내는 경로는
> 현재 이것뿐이므로 기존 코드를 당장 바꿀 필요는 없습니다.
>
> 다음의 경우에는 **브랜드메시지**를 사용하세요.
> - 템플릿 기반 리치 타입 (`FL`/`FC`/`FM`/`FP`/`FA`)
> - 채널 친구가 **아닌** 수신자 (`targeting` = `N` / `I`)
> - 수신 동의한 전체 채널 친구 동보 (`targeting` = `F`)
>
> 메시지 타입은 1:1 대응되며 변환은 서버가 처리합니다 — `FT`→`BT`, `FI`→`BI`, `FW`→`BW`,
> `FL`→`BL`, `FC`→`BC`, `FM`→`BM`, `FP`→`BP`, `FA`→`BA`.

```php
<?php

// 텍스트형
$sendgo->friendtalk->send([
    'content'  => '안녕하세요! 7월 한정 특가 이벤트를 확인해보세요.',
    'contacts' => [['contact' => '01012345678']],
]);

// 이미지형
$sendgo->friendtalk->send([
    'messageType' => 'FI',
    'content'     => '이번 주 특가 상품을 확인하세요!',
    'imageUrl'    => 'https://cdn.example.com/banner.jpg',
    'imageLink'   => 'https://example.com/event',
    'contacts'    => [['contact' => '01012345678']],
]);

// 버튼 포함
$sendgo->friendtalk->send([
    'content'  => '7월 쿠폰이 도착했습니다! 지금 바로 사용하세요.',
    'buttons'  => [
        ['name' => '쿠폰 받기', 'type' => 'WL', 'linkMo' => 'https://example.com/coupon'],
        ['name' => '고객센터', 'type' => 'WL', 'linkMo' => 'https://example.com/cs'],
    ],
    'contacts' => [['contact' => '01012345678']],
]);
```

### SMS / LMS / MMS

```php
<?php

// SMS (90자 이하)
$sendgo->sms->sendSms([
    'content'  => '[Sendgo] 인증번호: 123456 (5분 이내 입력)',
    'contacts' => [['contact' => '01012345678']],
]);

// LMS (장문, 2,000자 이하)
$sendgo->sms->sendLms([
    'subject'  => '[중요] 서비스 점검 안내',
    'content'  => "안녕하세요. 서비스 점검이 예정되어 있습니다.\n\n■ 일시: 2026-07-25 02:00 ~ 06:00\n■ 영향: 전체 서비스",
    'contacts' => [['contact' => '01012345678']],
]);

// MMS (이미지 포함)
$sendgo->sms->sendMms([
    'subject'  => '[이벤트] 7월 특가',
    'content'  => '이번 달 특가 상품을 확인하세요!',
    'contacts' => [['contact' => '01011111111'], ['contact' => '01022222222']],
]);
```

---

## 서비스 클래스 패턴

```php
<?php
// src/Service/NotificationService.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Sendgo\Php\Sendgo;
use Sendgo\Php\Exception\SendgoException;

class NotificationService
{
    public function __construct(
        private Sendgo $sendgo,
        private LoggerInterface $logger,
    ) {}

    public function sendOrderConfirm(string $phone, string $orderNo, int $amount): void
    {
        $this->sendgo->alimtalk->send([
            'templateCode' => 'ORDER_CONFIRM_001',
            'contacts'     => [
                ['contact' => $phone, 'var1' => $orderNo, 'var2' => number_format($amount) . '원'],
            ],
        ]);
    }

    public function sendVerificationCode(string $phone, string $code): void
    {
        try {
            // 알림톡 우선, 실패 시 SMS 대체
            $this->sendgo->alimtalk->send([
                'templateCode' => 'VERIFY_CODE_001',
                'replaceSms'   => 'Y',
                'smsContent'   => "[인증] 인증번호: {$code} (5분 이내 입력)",
                'contacts'     => [['contact' => $phone, 'var1' => $code]],
            ]);
        } catch (SendgoException $e) {
            $this->logger->error('Sendgo 인증번호 발송 실패', [
                'phone'      => $phone,
                'error_code' => $e->getErrorCode(),
                'status'     => $e->getStatusCode(),
            ]);
            throw $e;
        }
    }
}
```

`services.yaml`에서 `autowire: true`가 설정되어 있으면 `Sendgo\Php\Sendgo`가 자동 주입됩니다.

---

## Messenger 비동기 발송

[Symfony Messenger](https://symfony.com/doc/current/messenger.html)로 발송을 비동기 처리할 수 있습니다.

```php
<?php
// src/Message/SendAlimtalkMessage.php

namespace App\Message;

class SendAlimtalkMessage
{
    public function __construct(
        public readonly string $templateCode,
        public readonly array $contacts,
    ) {}
}
```

```php
<?php
// src/MessageHandler/SendAlimtalkHandler.php

namespace App\MessageHandler;

use App\Message\SendAlimtalkMessage;
use Sendgo\Php\Sendgo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendAlimtalkHandler
{
    public function __construct(private Sendgo $sendgo) {}

    public function __invoke(SendAlimtalkMessage $message): void
    {
        $this->sendgo->alimtalk->send([
            'templateCode' => $message->templateCode,
            'contacts'     => $message->contacts,
        ]);
    }
}
```

```php
// 디스패치 예시
$bus->dispatch(new SendAlimtalkMessage('ORDER_CONFIRM_001', [
    ['contact' => '01012345678', 'var1' => 'ORD-001'],
]));
```

---

## 예외 처리

```php
<?php

use Sendgo\Php\Exception\SendgoException;

try {
    $sendgo->alimtalk->send([...]);
} catch (SendgoException $e) {
    $logger->error('Sendgo 발송 실패', [
        'status'     => $e->getStatusCode(),
        'error_code' => $e->getErrorCode(),
        'endpoint'   => $e->getEndpoint(),
    ]);

    match ($e->getErrorCode()) {
        'INVALID_ACCESS_KEY',
        'INVALID_SECRET_KEY'    => $this->alertOps('Sendgo 인증키 오류'),
        'INVALID_TEMPLATE_CODE' => $logger->warning('존재하지 않는 템플릿'),
        'PAYMENT_REQUIRED'      => $this->alertOps('Sendgo 크레딧 부족'),
        'IP_NOT_ALLOWED'        => $this->alertOps('허용되지 않은 IP'),
        default                 => null,
    };
}
```

---

## 설정 옵션

`config/packages/sendgo.yaml` 에서 설정합니다:

| 키 | 환경변수 | 기본값 | 설명 |
|----|---------|--------|------|
| `access_key` | `SENDGO_ACCESS_KEY` | — (필수) | Sendgo 액세스 키 |
| `secret_key` | `SENDGO_SECRET_KEY` | — (필수) | Sendgo 시크릿 키 |
| `kakao_sender_key` | `SENDGO_KAKAO_SENDER_KEY` | `null` | 카카오 발신프로필 키 |
| `sms_sender_key` | `SENDGO_SMS_SENDER_KEY` | `null` | SMS 발신자 키 |
| `api_version` | `SENDGO_API_VERSION` | `'v2'` | API 버전 |
| `url` | `SENDGO_URL` | `'https://sendgo.io'` | API 기본 URL |

---

## 자주 묻는 질문 (FAQ)

**Q. `sendgo/php`와의 차이는 무엇인가요?**
A. `sendgo/php`는 프레임워크 독립적인 순수 PHP 코어 패키지입니다. `sendgo/symfony`는 이를 확장해 번들 자동 등록, DI 컨테이너 서비스 등록, config 바인딩, 오토와이어링 등 Symfony 통합을 추가합니다.

**Q. Symfony 6.4, 7 모두 지원하나요?**
A. 네, `symfony/config`, `symfony/dependency-injection`, `symfony/http-kernel` 모두 `^6.4|^7.0`을 지원합니다.

**Q. 오토와이어링 없이 서비스 ID로 접근할 수 있나요?**
A. 네, `sendgo` 별칭이 등록되어 있어 `$container->get('sendgo')`로 접근할 수 있습니다.

**Q. 테스트 시 Sendgo를 Mock 처리하려면?**
A. 테스트 컨테이너에서 `Sendgo\Php\Sendgo` 서비스를 PHPUnit Mock으로 교체하면 됩니다.

---

## 관련 패키지

| 언어/프레임워크 | 패키지 | GitHub |
|----------------|--------|--------|
| PHP (순수) | `sendgo/php` | [php](https://github.com/send-go/php) |
| Laravel | `sendgo/laravel` | [laravel](https://github.com/send-go/laravel) |
| Spring Boot | `io.sendgo:sendgo-spring` | [spring](https://github.com/send-go/spring) |
| Node.js | `@sendgo/node` | [node](https://github.com/send-go/node) |
| 전체 목록 | — | [send-go GitHub 조직](https://github.com/send-go) |

---

## 브랜드메시지 · 짧은 URL

이 패키지는 코어(`sendgo/php`)의 클라이언트를 그대로 노출하므로, 코어에 있는 채널이
모두 그대로 쓸 수 있습니다. 두 기능 모두 **v2 전용**입니다.

| 기능 | 접근 |
|------|------|
| 카카오 브랜드메시지 (친구톡의 후속 채널) | `$sendgo->brandMessage` |
| 짧은 URL (단축 + 클릭 반응 분석) | `$sendgo->shortUrl` |

브랜드메시지는 채널 친구가 아닌 수신자에게도 보낼 수 있고(`targeting` = `N`),
수신 동의한 전체 채널 친구에게 동보 발송할 수도 있습니다(`targeting` = `F`).

짧은 URL 은 메시지 본문의 링크를 줄이고 클릭 반응(일별 추이·디바이스·유입경로·국가)을
집계합니다.

사용 예시와 파라미터는 [코어 README](https://github.com/send-go) 와
[SDK 가이드](https://sendgo.io/ko/sdk) 를 참고하세요.

## 관리 API — 채널·템플릿·발신번호 등록 (v2 전용)

콘솔에서만 되던 등록·심사를 코드로 처리합니다. 이 번들은 코어(`sendgo/php`)의
클라이언트를 그대로 노출하므로, 주입받은 `Sendgo` 서비스에서 바로 쓸 수 있습니다.

| 서비스 | 하는 일 | 계정 |
| --- | --- | --- |
| `$sendgo->kakaoSenders` | 카카오 채널 인증·등록·동기화, 브랜드메시지 M/N 신청 | 기업 |
| `$sendgo->noticeTemplates` | 알림톡 템플릿 CRUD, 검수 요청·취소, 승인 취소, 휴면 해제 | 기업 |
| `$sendgo->brandTemplates` | 브랜드메시지 템플릿 CRUD, 동기화, 가져오기 | 기업 |
| `$sendgo->senderRegistration` | 발신번호 등록 신청, 중복 확인, 유형 안내 | 개인·기업 |
| `$sendgo->messageTemplates` | 문자 상용구 템플릿 CRUD | 개인·기업 |
| `$sendgo->kakaoImages` | 카카오 이미지 업로드 — 템플릿용 URL 발급 | 기업 |
| `$sendgo->rejectedNumbers` | 수신거부(080) 번호 조회 | 개인·기업 |
| `$sendgo->webhook` | 이벤트 웹훅 구독 — 심사 결과 수신 | 개인·기업 |

> **sendgo.io 콘솔에 들어올 일이 없습니다.** 휴대폰 발신번호는 PASS 대신
> 신분증 사본을 받아 sendgo 운영자가 대신 심사합니다. 사람이 개입하는 지점은
> 카카오 채널 인증번호 하나뿐이고, 그것도 여러분 화면에서 입력받으면 됩니다.
> 심사가 붙는 것들은 비동기라 웹훅으로 결과를 받으세요.

```php
<?php

namespace App\Controller;

use Sendgo\Php\Sendgo;
use Symfony\Component\HttpFoundation\JsonResponse;

class OnboardingController
{
    public function __construct(private Sendgo $sendgo) {}

    public function registerChannel(string $yellowId, string $phone): JsonResponse
    {
        // 1단계 — 카카오가 관리자 휴대폰으로 인증번호를 SMS 발송한다
        $this->sendgo->kakaoSenders->requestToken($yellowId, $phone);

        return new JsonResponse(['message' => '인증번호를 발송했습니다.']);
    }

    public function completeChannel(string $yellowId, string $phone, string $code): JsonResponse
    {
        $created = $this->sendgo->kakaoSenders->create([
            'token'        => $code,
            'yellowId'     => $yellowId,
            'phoneNumber'  => $phone,
            'categoryCode' => '001001',
        ]);

        return new JsonResponse($created['data']['sender']);
    }
}
```

전체 파라미터는 [코어 README](https://github.com/send-go/php) 와
[API 문서](https://sendgo.io/ko/applications/guide/v2) 를 참고하세요.

---

## 변경 사항

### 1.3.0 (2026-09-11)

- **관리 API 노출** — 코어 1.3.0 의 `kakaoSenders` · `noticeTemplates` ·
  `brandTemplates` · `senderRegistration` · `messageTemplates` 를 주입받은
  `Sendgo` 서비스에서 그대로 쓸 수 있습니다. 콘솔에서만 되던 채널 등록,
  알림톡 템플릿 검수 요청, 발신번호 심사 접수를 코드로 처리합니다.
- **이벤트 웹훅** 추가 — 발신번호 승인, 알림톡 검수 결과, 채널 차단,
  브랜드메시지 타겟팅 결과를 구독해 받습니다. 서명은 받은 원본 바이트로
  검증합니다(SDK 에 검증 헬퍼 포함).
- **카카오 이미지 업로드** 추가 — 브랜드메시지 템플릿의 `imageUrl` 은 카카오가
  호스팅하는 URL 이어야 하는데, 그 URL 을 얻는 길이 콘솔에만 있었습니다.
- **수신거부(080) 조회** 추가 — 자기 DB 의 수신 상태를 맞출 수 있습니다.

### 1.2.1 (2026-08-14)

- 레지스트리 목록에 노출되는 패키지 설명에서 친구톡을 브랜드메시지로 교체했습니다.
  npm/PyPI/Packagist/Maven/NuGet/RubyGems 검색 결과에 그대로 찍히는 문자열이라
  종료된 채널을 계속 홍보하고 있었습니다.
- 검색 키워드에 `brand-message` 를 추가했습니다 (`friendtalk` 은 유입 검색어라 유지).

### 1.2.0 (2026-08-14)

- **친구톡 Deprecated 표기** — 친구톡은 카카오 정책에 따라 2025-12-31 종료되었고,
  2026-01-01 부터 발송 요청이 브랜드메시지(자유형)로 자동 대체 발송됩니다.
  관련 API 에 각 언어의 표준 deprecation 표기를 달았습니다.
- 자유 본문 타입(`FT`/`FI`/`FW`)의 개별 발송 경로는 아직 친구톡 API 뿐이라는 점을
  문서에 명시했습니다 — 브랜드메시지 API 는 그 조합에 `NOT_A_BRAND_MESSAGE` 를 반환합니다.
- 브랜드메시지 전환 안내와 메시지 타입 1:1 대응표를 README 에 추가했습니다.
- 짧은 URL 지원 (1.1.0 릴리스 누락분 포함).

### 1.1.0 (2026-08-11)

- 브랜드메시지·짧은 URL 접근 방법 문서화 (코어를 그대로 노출)

## 라이선스

MIT License © 2026 [Sendgo](https://sendgo.io)

---

*키워드: 카카오 알림톡 Symfony, 카카오 친구톡 Symfony, SMS 발송 Symfony, 알림톡 Symfony 번들, Symfony 카카오 API 연동, Symfony Messenger 알림톡, Sendgo Symfony SDK*

## 계정 API (1.5.0)

코어 1.5.0의 계정·조직·API 키·허용 IP 관리 12개 API를 사용할 수 있습니다.
발송용 키 없이 에이전트 토큰만으로 구성할 수 있습니다.

발송용 `accessKey`/`secretKey`가 없는 단계에서 사용하는 **별도 계정 클라이언트**입니다.
콘솔에서 발급받은 에이전트 토큰(`SENDGO_AGENT_TOKEN`)으로 `/api/v2/account`를 호출합니다.
계정 조회에는 `account:read`, 키·허용 IP 변경에는 `keys:write` 권한이 필요합니다.
토큰 만료나 권한 부족(401/403)은 그대로 예외로 반환하며 자동 갱신·재시도하지 않습니다.

조직 선택은 서버에 저장되는 **사용자 계정의 현재 조직**을 바꿉니다. 같은 사용자로
여러 조직의 설정을 동시에 변경하지 마세요. 개인 계정으로 돌아가려면 조직 ID에
`null`(Python `None`, Ruby `nil`, Go `nil`) 또는 `personal`을 전달합니다.
키 발급 응답의 `data.apiKey.secretKey`는 한 번만 반환되므로 서버의 비밀 저장소에 보관하세요.
허용 IP가 하나라도 등록되면 목록 밖의 IP는 차단됩니다.
에이전트 토큰과 키는 브라우저·모바일 앱에 포함하거나 응답·로그에 출력하지 않습니다.

```yaml
# config/packages/sendgo.yaml
sendgo:
  agent_token: '%env(SENDGO_AGENT_TOKEN)%'
# Sendgo\Php\AccountClient를 오토와이어링할 수 있습니다.
```

## 템플릿 폴더 (1.5.0)

기업 계정의 발송용 API 키와 `apiVersion=v2` 설정으로 사용하는 서버 전용 API입니다.
폴더는 알림톡·브랜드메시지가 공유하며, 목록의 `templateType`은 `notice` 또는 `brand`입니다.
목록은 `data.folders` 트리와 `total`, `uncategorised` 개수를 반환합니다.
`templateCount`는 하위 폴더를 제외한 해당 폴더의 템플릿 수입니다.

- 생성: `name`, 선택 `parentUuid`. 최대 5단계이며 같은 부모 아래 이름 중복은 409입니다.
- 이동: 동일 발신프로필의 `templateCodes` 1~100개. `folderUuid`는 필수이며 `null`이면 미분류로 이동합니다.
- 템플릿 목록: `folderUuid=none`은 미분류, UUID는 해당 폴더, 생략은 전체입니다.
- 템플릿 등록: 선택 필드 `folderUuid`로 폴더를 지정합니다. 기존 템플릿 수정 API 대신 폴더 이동 API를 사용하세요.

승인되지 않은 키의 `403 ACCESS_KEY_NOT_APPROVED`는 토큰 재발급·재시도 없이 반환합니다.
계정 API의 `autoApprove`는 서버 설정의 실제 승인 정책을 나타냅니다.

```php
// 오토와이어링한 Sendgo\Php\Sendgo 인스턴스를 사용합니다.
$sendgo->templateFolders->list(['templateType' => 'notice']);
```

코어 1.5.0 이상이 필요합니다. 전체 메서드는 [코어 문서](https://github.com/send-go/php#템플릿-폴더-150)를 참고하세요.
