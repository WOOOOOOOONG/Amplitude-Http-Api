<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Drivers;


use App\Services\Marketing\Amplitude\Dtos\AmplitudeResponseDto;

/**
 * AmplitudeDriverInterface는 모든 Amplitude 드라이버가 구현해야 하는 인터페이스입니다.
 */
interface AmplitudeDriverInterface
{
    /**
     * 이벤트들을 Amplitude로 전송합니다.
     *
     * @param array $events 전송할 이벤트 배열
     * @return AmplitudeResponseDto 응답 데이터
     */
    public function sendEvents(array $events): AmplitudeResponseDto;

    /**
     * 드라이버 옵션을 설정합니다.
     *
     * @param array $options 옵션 배열
     * @return self
     */
    public function setOptions(array $options): self;

    /**
     * API 키를 설정합니다.
     *
     * @param string $apiKey API 키
     * @return self
     */
    public function setApiKey(string $apiKey): self;
}
