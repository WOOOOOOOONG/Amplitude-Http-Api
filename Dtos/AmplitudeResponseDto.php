<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Dtos;

use App\Services\Contracts\Dtos\BaseDto;

/**
 * AmplitudeResponseDto 클래스는 Amplitude API 응답 데이터를 위한 데이터 전송 객체입니다.
 *
 * @property int $code 응답 코드
 * @property int $eventsIngested 수집된 이벤트 수
 * @property int $payloadSizeBytes 페이로드 크기 (바이트)
 * @property int $serverUploadTime 서버 업로드 시간 (밀리초)
 * @property string|null $error 에러 메시지
 * @property array|null $eventsWithInvalidFields 유효하지 않은 필드를 가진 이벤트
 * @property array|null $eventsWithMissingFields 누락된 필드를 가진 이벤트
 * @property array|null $silencedDevices 차단된 디바이스
 * @property array|null $silencedEvents 차단된 이벤트
 * @property array|null $throttledDevices 제한된 디바이스
 * @property array|null $throttledUsers 제한된 사용자
 * @property array|null $throttledEvents 제한된 이벤트
 * @property int|null $epsThreshold EPS 임계값
 */
class AmplitudeResponseDto extends BaseDto
{
    /** @var int 응답 코드 */
    public $code;

    /** @var int 수집된 이벤트 수 */
    public $eventsIngested;

    /** @var int 페이로드 크기 (바이트) */
    public $payloadSizeBytes;

    /** @var int 서버 업로드 시간 (밀리초) */
    public $serverUploadTime;

    /** @var string|null 에러 메시지 */
    public $error;

    /** @var array|null 유효하지 않은 필드를 가진 이벤트 */
    public $eventsWithInvalidFields;

    /** @var array|null 누락된 필드를 가진 이벤트 */
    public $eventsWithMissingFields;

    /** @var array|null 차단된 디바이스 */
    public $silencedDevices;

    /** @var array|null 차단된 이벤트 */
    public $silencedEvents;

    /** @var array|null 제한된 디바이스 */
    public $throttledDevices;

    /** @var array|null 제한된 사용자 */
    public $throttledUsers;

    /** @var array|null 제한된 이벤트 */
    public $throttledEvents;

    /** @var int|null EPS 임계값 */
    public $epsThreshold;

    /**
     * 응답이 성공적인지 확인합니다.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->code === 200;
    }

    /**
     * 제한(throttle) 관련 에러인지 확인합니다.
     *
     * @return bool
     */
    public function isThrottled(): bool
    {
        return $this->code === 429 ||
            !empty($this->throttledDevices) ||
            !empty($this->throttledUsers) ||
            !empty($this->throttledEvents);
    }

    /**
     * 차단(silence) 관련 에러인지 확인합니다.
     *
     * @return bool
     */
    public function isSilenced(): bool
    {
        return !empty($this->silencedDevices) ||
            !empty($this->silencedEvents);
    }

    /**
     * 재시도 가능한 에러인지 확인합니다.
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        return in_array($this->code, [429, 500, 502, 503, 504], true);
    }

    /**
     * 에러 상세 정보를 문자열로 반환합니다.
     *
     * @return string
     */
    public function getErrorDetails(): string
    {
        $details = [];

        if ($this->error) {
            $details[] = "Error: {$this->error}";
        }

        if ($this->eventsWithInvalidFields) {
            $details[] = "Invalid fields: " . json_encode($this->eventsWithInvalidFields);
        }

        if ($this->eventsWithMissingFields) {
            $details[] = "Missing fields: " . json_encode($this->eventsWithMissingFields);
        }

        if ($this->silencedDevices) {
            $details[] = "Silenced devices: " . implode(', ', $this->silencedDevices);
        }

        if ($this->throttledDevices) {
            $details[] = "Throttled devices: " . json_encode($this->throttledDevices);
        }

        if ($this->throttledUsers) {
            $details[] = "Throttled users: " . json_encode($this->throttledUsers);
        }

        return implode('; ', $details);
    }
}
