<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude;

use App\Services\Marketing\Amplitude\Drivers\AmplitudeBackfillDriver;
use App\Services\Marketing\Amplitude\Drivers\AmplitudeDriver;
use App\Services\Marketing\Amplitude\Drivers\AmplitudeIdentifyDriver;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeIdentifyEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeResponseDto;
use App\Services\Marketing\Amplitude\Exceptions\AmplitudeException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Manager;

/**
 * AmplitudeManager 클래스는 Amplitude API와의 통신을 관리합니다.
 * Manager 패턴을 사용하여 HTTP, Backfill, Identify 드라이버를 지원합니다.
 */
class AmplitudeManager extends Manager
{
    /** @var Application 라라벨 애플리케이션 인스턴스 */
    protected $app;

    /** @var array 설정 캐시 */
    private $config;

    /** @var AmplitudeIdentifyDriver Identify 드라이버 인스턴스 */
    private $identifyDriver;

    /**
     * AmplitudeManager 생성자.
     *
     * @param Application $app 라라벨 애플리케이션 인스턴스
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app['config']['amplitude'] ?? [];
    }

    /**
     * 기본 드라이버 이름을 반환합니다.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config['default'] ?? 'http';
    }

    /**
     * HTTP 드라이버를 생성합니다.
     *
     * @return AmplitudeDriver
     */
    protected function createHttpDriver(): AmplitudeDriver
    {
        return new AmplitudeDriver(
            $this->config['api_key'] ?? '',
            $this->config['endpoints']['http'] ?? 'https://api2.amplitude.com/2/httpapi',
            $this->config['options'] ?? []
        );
    }

    /**
     * Backfill 드라이버를 생성합니다.
     *
     * @return AmplitudeBackfillDriver
     */
    protected function createBackfillDriver(): AmplitudeBackfillDriver
    {
        return new AmplitudeBackfillDriver(
            $this->config['api_key'] ?? '',
            $this->config['endpoints']['backfill'] ?? 'https://api2.amplitude.com/batch',
            $this->config['options'] ?? []
        );
    }

    /**
     * Identify 드라이버를 반환합니다.
     *
     * @return AmplitudeIdentifyDriver
     */
    protected function getIdentifyDriver(): AmplitudeIdentifyDriver
    {
        if (!$this->identifyDriver) {
            $this->identifyDriver = new AmplitudeIdentifyDriver(
                $this->config['api_key'] ?? '',
                $this->config['endpoints']['identify'] ?? 'https://api2.amplitude.com/identify',
                $this->config['options'] ?? []
            );
        }

        return $this->identifyDriver;
    }

    /**
     * 단일 이벤트를 전송합니다.
     *
     * @param AmplitudeEventDto $event 전송할 이벤트
     * @return AmplitudeResponseDto
     * @throws AmplitudeException
     */
    public function sendEvent(AmplitudeEventDto $event): AmplitudeResponseDto
    {
        return $this->driver()->sendEvents([$event]);
    }

    /**
     * 여러 이벤트를 전송합니다.
     *
     * @param array $events AmplitudeEventDto 배열
     * @return AmplitudeResponseDto
     * @throws AmplitudeException
     */
    public function sendEvents(array $events): AmplitudeResponseDto
    {
        // 이벤트 유효성 검증
        foreach ($events as $event) {
            if (!$event instanceof AmplitudeEventDto) {
                throw new AmplitudeException('All events must be instance of AmplitudeEventDto');
            }
        }

        return $this->driver()->sendEvents($events);
    }

    /**
     * Identify 요청을 전송합니다 (UserProperty만 업데이트).
     *
     * @param AmplitudeIdentifyEventDto $identify
     * @return AmplitudeResponseDto
     * @throws AmplitudeException
     */
    public function sendIdentify(AmplitudeIdentifyEventDto $identify): AmplitudeResponseDto
    {
        return $this->getIdentifyDriver()->sendIdentify($identify);
    }

    /**
     * 이벤트를 빌드합니다.
     *
     * @param array $data 이벤트 데이터
     * @return AmplitudeEventDto
     */
    public function buildEvent(array $data): AmplitudeEventDto
    {
        return $this->app->make(AmplitudeEventDto::class, $data);
    }

    /**
     * Identify DTO를 빌드합니다.
     *
     * @param array $data Identify 데이터
     * @return AmplitudeIdentifyEventDto
     */
    public function buildIdentify(array $data): AmplitudeIdentifyEventDto
    {
        return $this->app->make(AmplitudeIdentifyEventDto::class, $data);
    }

    /**
     * 특정 드라이버를 사용하여 이벤트를 전송합니다.
     *
     * @param string $driver 드라이버 이름 ('http' 또는 'backfill')
     * @param array $events 전송할 이벤트 배열
     * @return AmplitudeResponseDto
     */
    public function using(string $driver, array $events): AmplitudeResponseDto
    {
        return $this->driver($driver)->sendEvents($events);
    }
}
