<?php declare(strict_types=1);

namespace App\Services\Marketing\TrackStation\Facades;

use App\Services\Marketing\Amplitude\Dtos\AmplitudeEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeResponseDto;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AmplitudeResponseDto sendEvent(AmplitudeEventDto $event)
 * @method static AmplitudeResponseDto sendEvents(array $events)
 * @method static AmplitudeEventDto buildEvent(array $data)
 * @method static AmplitudeResponseDto using(string $driver, array $events)
 *
 * @see \App\Services\Marketing\AmplitudeManager
 */
class Amplitude extends Facade
{
    /**
     * 파사드가 접근할 서비스 컨테이너 바인딩 이름을 반환합니다.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'amplitude';
    }
}