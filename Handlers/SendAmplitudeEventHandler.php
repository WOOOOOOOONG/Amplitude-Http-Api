<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Handlers;

use App\Services\Marketing\Amplitude\AmplitudeManager;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeIdentifyEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeResponseDto;
use App\Services\Marketing\Amplitude\Exceptions\AmplitudeException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;

/**
 * SendAmplitudeEventHandler 클래스는 Amplitude로 이벤트 또는 Identify를 전송하는 핸들러입니다.
 */
class SendAmplitudeEventHandler
{
    /** @var Application 애플리케이션 인스턴스 */
    private $app;

    /** @var AmplitudeManager Amplitude 매니저 */
    private $amplitudeManager;

    /** @var AmplitudeEventDto|AmplitudeIdentifyEventDto 처리할 데이터 */
    private $dto;

    /** @var AmplitudeResponseDto 응답 데이터 */
    private $response;

    /**
     * SendAmplitudeEventHandler 생성자.
     *
     * @param Application $app
     * @param AmplitudeManager $amplitudeManager
     */
    public function __construct(Application $app, AmplitudeManager $amplitudeManager)
    {
        $this->app = $app;
        $this->amplitudeManager = $amplitudeManager;
    }

    /**
     * AmplitudeEventDto를 설정합니다 (기존 호환성 유지).
     *
     * @param AmplitudeEventDto $eventDto
     * @return self
     */
    public function setEventItemDto(AmplitudeEventDto $eventDto): self
    {
        $this->dto = $eventDto;
        return $this;
    }

    /**
     * AmplitudeIdentifyDto를 설정합니다.
     *
     * @param AmplitudeIdentifyEventDto $identifyDto
     * @return self
     */
    public function setIdentifyDto(AmplitudeIdentifyEventDto $identifyDto): self
    {
        $this->dto = $identifyDto;
        return $this;
    }

    /**
     * Amplitude로 데이터를 전송합니다.
     *
     * @return self
     * @throws AmplitudeException
     */
    public function handle(): self
    {
        try {
            if ($this->dto instanceof AmplitudeIdentifyEventDto) {
                // Identify 전송
                $this->response = $this->amplitudeManager->sendIdentify($this->dto);

                // 로깅
                Log::debug('[Amplitude] identify sent', [
                    'user_id' => $this->dto->userId,
                    'device_id' => $this->dto->deviceId,
                    'response' => $this->response->toArray()
                ]);

            } elseif ($this->dto instanceof AmplitudeEventDto) {
                // Event 전송 (AmplitudeEventDto 직접 전송)
                $this->response = $this->amplitudeManager->sendEvent($this->dto);

                // 로깅
                Log::debug('[Amplitude] event sent', [
                    'event_type' => $this->dto->eventType,
                    'user_id' => $this->dto->userId,
                    'response' => $this->response->toArray()
                ]);

            } else {
                Log::warning('Unsupported DTO type: ', [
                    'dto_class' => get_class($this->dto)
                ]);
            }

            // 실패한 경우 로깅
            if (!$this->response->isSuccess()) {
                Log::warning('Amplitude request failed', [
                    'error' => $this->response->getErrorDetails(),
                    'response' => $this->response->toArray()
                ]);
            }

        } catch (AmplitudeException $e) {
            Log::error('Amplitude error', [
                'message' => $e->getMessage(),
                'response_data' => $e->getResponseData()
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unexpected error sending to Amplitude', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new AmplitudeException(
                'Failed to send to Amplitude: ' . $e->getMessage(),
                0,
                null,
                $e
            );
        }

        return $this;
    }

    /**
     * 응답을 반환합니다.
     *
     * @return AmplitudeResponseDto|null
     */
    public function get(): ?AmplitudeResponseDto
    {
        return $this->response;
    }
}
