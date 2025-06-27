# Amplitude Laravel Service

Laravel 애플리케이션에서 Amplitude 이벤트 트래킹을 위한 서비스입니다.

## 주요 기능

- **실시간 이벤트 전송** (HTTP API)
- **대량 데이터 전송** (Batch API)
- **사용자 속성 업데이트** (Identify API)
- **비동기 큐 처리**
- **자동 재시도 로직**
- **포괄적인 에러 핸들링**

## 아키텍처

### 1. Events & Listeners
- `SendAmplitudeEvent`: 이벤트 클래스
- `SendAmplitudeEventListener`: 큐를 통한 비동기 처리

### 2. Handlers
- `SendAmplitudeEventHandler`: Amplitude API 전송 처리

### 3. DTOs (Data Transfer Objects)
- `AmplitudeEventDto`: 일반 이벤트 데이터
- `AmplitudeIdentifyEventDto`: 사용자 속성 업데이트용
- `AmplitudeResponseDto`: API 응답 데이터

### 4. Drivers
- `AmplitudeDriver`: 실시간 HTTP API
- `AmplitudeBackfillDriver`: 과거 데이터 Batch API
- `AmplitudeIdentifyDriver`: Identify API 전용

### 5. Manager & Facade
- `AmplitudeManager`: 드라이버 관리 및 통합 인터페이스
- `Amplitude`: 파사드로 간편한 접근

## 설정

### config/amplitude.php
```php
<?php

return [
    'default' => 'http',
    
    'api_key' => env('AMPLITUDE_API_KEY'),
    
    'endpoints' => [
        'http' => 'https://api2.amplitude.com/2/httpapi',
        'backfill' => 'https://api2.amplitude.com/batch',
        'identify' => 'https://api2.amplitude.com/identify',
    ],
    
    'options' => [
        'min_id_length' => 5,
        'timeout' => 5,
        'batch_size' => 1000,
        'retry_count' => 3,
        'retry_delay' => 2000,
    ],
];
```

### .env
```
AMPLITUDE_API_KEY=your_amplitude_api_key_here
```

## 기본 사용법

### 1. 단일 이벤트 전송
```php
use App\Services\Marketing\TrackStation\Facades\Amplitude;

// 파사드 사용
$response = Amplitude::sendEvent(new AmplitudeEventDto([
    'userId' => 12345,
    'eventType' => 'button_clicked',
    'eventProperties' => [
        'button_name' => 'signup',
        'page' => 'landing'
    ]
]));
```

### 2. 여러 이벤트 일괄 전송
```php
$events = [
    new AmplitudeEventDto(['userId' => 1, 'eventType' => 'page_view']),
    new AmplitudeEventDto(['userId' => 2, 'eventType' => 'button_click']),
];

$response = Amplitude::sendEvents($events);
```

### 3. 사용자 속성 업데이트 (Identify)
```php
use App\Services\Marketing\Amplitude\Dtos\AmplitudeIdentifyEventDto;

$identify = new AmplitudeIdentifyEventDto([
    'userId' => 12345,
    'userProperties' => [
        '$set' => [
            'plan' => 'premium',
            'last_login' => '2025-01-15'
        ]
    ]
]);

// 이벤트로 전송 (큐 처리)
event(new SendAmplitudeEvent($identify));
```

### 4. 특정 드라이버 사용
```php
// Backfill API로 과거 데이터 전송
$response = Amplitude::using('backfill', $events);
```

## 이벤트 리스너를 통한 비동기 처리

### 이벤트 발생
```php
use App\Services\Marketing\Amplitude\Events\SendAmplitudeEvent;

// 일반 이벤트
$event = new AmplitudeEventDto([
    'userId' => 12345,
    'eventType' => AmplitudeConst::INQUIRY_SUBMITTED,
    'eventProperties' => ['source' => 'web']
]);

event(new SendAmplitudeEvent($event));

// Identify 이벤트
$identify = new AmplitudeIdentifyEventDto([
    'userId' => 12345,
    'userProperties' => ['$set' => ['premium' => true]]
]);

event(new SendAmplitudeEvent($identify));
```

### 큐 설정
```php
// config/queue.php에서 큐 설정 후
php artisan queue:work
```

## 고급 사용법

### 1. AmplitudeIdentifyEventDto 메서드 체이닝
```php
$identify = new AmplitudeIdentifyEventDto(['userId' => 12345])
    ->set('plan', 'premium')
    ->add('login_count', 1)
    ->unset('temp_data')
    ->append('tags', 'vip');

event(new SendAmplitudeEvent($identify));
```

### 2. 매니저 직접 사용
```php
use App\Services\Marketing\Amplitude\AmplitudeManager;

$manager = app(AmplitudeManager::class);

// 단일 이벤트
$response = $manager->sendEvent($eventDto);

// Identify
$response = $manager->sendIdentify($identifyDto);

// 특정 드라이버
$response = $manager->using('backfill', $events);
```

### 3. 응답 처리
```php
$response = Amplitude::sendEvent($event);

if ($response->isSuccess()) {
    echo "전송 성공: {$response->eventsIngested}개 이벤트 처리됨";
} else {
    echo "전송 실패: {$response->getErrorDetails()}";
}

// 재시도 가능한 에러인지 확인
if ($response->isRetryable()) {
    // 재시도 로직
}
```

## 이벤트 상수

```php
use App\Services\Marketing\Amplitude\Consts\AmplitudeConst;

AmplitudeConst::INQUIRY_SUBMITTED        // 문의 완료
AmplitudeConst::Z_MEMBER_LEVEL_UP        // Z회원 등업
AmplitudeConst::AGENT_SIGNUP_COMPLETE    // 중개회원 가입 완료
AmplitudeConst::FAVORITES_ADD            // 찜하기
```

## 에러 처리

### AmplitudeException
```php
use App\Services\Marketing\Amplitude\Exceptions\AmplitudeException;

try {
    $response = Amplitude::sendEvent($event);
} catch (AmplitudeException $e) {
    // Amplitude 관련 에러
    $responseData = $e->getResponseData();
    Log::error('Amplitude 전송 실패', ['error' => $e->getMessage()]);
}
```

### 응답 상태 확인
```php
$response = Amplitude::sendEvent($event);

// 성공 여부
$response->isSuccess()      // 200 응답
$response->isThrottled()    // 429 또는 throttle 관련
$response->isSilenced()     // 차단된 디바이스/이벤트
$response->isRetryable()    // 재시도 가능한 에러
```

## 로깅

모든 Amplitude 요청과 응답은 `marketing` 채널에 로깅됩니다:

```php
// config/logging.php
'channels' => [
    'marketing' => [
        'driver' => 'daily',
        'path' => storage_path('logs/marketing.log'),
        'level' => 'debug',
    ],
];
```

## 필수 필드

### AmplitudeEventDto
- `userId` 또는 `deviceId` 중 하나 필수
- `eventType` 필수

### AmplitudeIdentifyEventDto
- `userId` 또는 `deviceId` 중 하나 필수
- `userProperties` 필수

## 자동 생성 필드

- `insertId`: 중복 제거용 ID (자동 생성)
- `time`: 이벤트 발생 시간 (자동 설정)

## 주의사항

1. **큐 워커 실행**: 이벤트 리스너가 `ShouldQueue`를 구현하므로 큐 워커가 실행되어야 함
2. **API 키 보안**: `.env` 파일에 API 키를 안전하게 저장
3. **타임아웃 설정**: 네트워크 상황에 맞게 타임아웃 조정
4. **배치 크기**: 대량 데이터 전송 시 배치 크기 조정

## 문제 해결

### 이벤트가 전송되지 않는 경우
1. 큐 워커가 실행되고 있는지 확인
2. API 키가 올바른지 확인
3. 필수 필드가 제대로 설정되었는지 확인
4. 로그 파일에서 에러 메시지 확인

### 성능 최적화
1. 배치 전송 사용
2. 큐 워커 여러 개 실행
3. Backfill API 사용 (과거 데이터)

## 라이센스

이 서비스는 내부 사용을 위한 코드입니다.
