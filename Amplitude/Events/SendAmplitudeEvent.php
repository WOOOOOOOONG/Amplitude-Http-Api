<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Events;

use App\Services\Marketing\Amplitude\Dtos\AmplitudeEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeIdentifyEventDto;

class SendAmplitudeEvent
{
    /**
     * @var AmplitudeEventDto|AmplitudeIdentifyEventDto $amplitudeDto
     */
    public $amplitudeDto;

    /**
     * SendAmplitudeEvent 생성자
     *
     * @param AmplitudeEventDto|AmplitudeIdentifyEventDto $amplitudeDto
     */
    public function __construct($amplitudeDto)
    {
        $this->amplitudeDto = $amplitudeDto;
    }

    /**
     * 이벤트 DTO인지 확인
     *
     * @return bool
     */
    public function isEvent(): bool
    {
        return $this->amplitudeDto instanceof AmplitudeEventDto;
    }

    /**
     * Identify DTO인지 확인
     *
     * @return bool
     */
    public function isIdentify(): bool
    {
        return $this->amplitudeDto instanceof AmplitudeIdentifyEventDto;
    }

    /**
     * Event DTO 반환 (타입 체크 후)
     *
     * @return AmplitudeEventDto|null
     */
    public function getEventDto(): ?AmplitudeEventDto
    {
        return $this->isEvent() ? $this->amplitudeDto : null;
    }

    /**
     * Identify DTO 반환 (타입 체크 후)
     *
     * @return AmplitudeIdentifyEventDto|null
     */
    public function getIdentifyDto(): ?AmplitudeIdentifyEventDto
    {
        return $this->isIdentify() ? $this->amplitudeDto : null;
    }
}
