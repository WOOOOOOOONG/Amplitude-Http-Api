<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Listeners;

use App\ContactLog;
use App\HouseContact;
use App\Services\Marketing\Amplitude\Consts\AmplitudeConst;
use App\Services\Marketing\Amplitude\Events\SendAmplitudeEvent;
use App\Services\Marketing\Amplitude\Handlers\SendAmplitudeEventHandler;
use Duse\Peterpanz\DuseLog\DuseLogFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * SendAmplitudeEventListener 클래스는 이벤트 또는 Identify를 Amplitude로 전송하는 리스너입니다.
 */
class SendAmplitudeEventListener implements ShouldQueue
{
    // 비동기 실행
    use InteractsWithQueue;

    /** @var SendAmplitudeEventHandler Amplitude 전송 핸들러 */
    private $sendAmplitudeEventHandler;

    /** @var DuseLogFacade $logger */
    private $logger;

    /**
     * SendAmplitudeEventListener 생성자.
     *
     * @param SendAmplitudeEventHandler $sendAmplitudeEventHandler
     */
    public function __construct(SendAmplitudeEventHandler $sendAmplitudeEventHandler)
    {
        $this->sendAmplitudeEventHandler = $sendAmplitudeEventHandler;
        $this->logger = DuseLogFacade::channel('marketing');
    }

    /**
     * 이벤트를 처리합니다.
     *
     * @param SendAmplitudeEvent $event
     * @return void
     */
    public function handle(SendAmplitudeEvent $event): void
    {
        try {
            // 요청 로깅
            $this->logger->debug("[Amplitude] 이벤트 실행", [
                'event' => $event
            ]);

            // DTO 타입에 따라 처리
            if ($event->isEvent()) {
                // 특정 이벤트 여부에 따라 사용자 속성을 추가
                $this->addUserProperties($event);

                // Event 처리
                $response = $this->sendAmplitudeEventHandler
                    ->setEventItemDto($event->getEventDto())
                    ->handle()
                    ->get();

            } elseif ($event->isIdentify()) {
                // Identify 처리
                $response = $this->sendAmplitudeEventHandler
                    ->setIdentifyDto($event->getIdentifyDto())
                    ->handle()
                    ->get();
            } else {
                $this->logger->warning('[Amplitude] 존재하지 않는 DTO type입니다.', [
                    'dto_class' => get_class($event->amplitudeDto)
                ]);

                return;
            }

            $this->logger->info('[Amplitude] event 전송 성공', [
                'response' => $response
            ]);

        } catch (\Throwable $e) {
            // 에러 로깅 (Amplitude 전송 실패는 메인 플로우를 중단시키지 않음)
            $this->logger->error('[Amplitude] Failed to send to Amplitude', [
                'error' => $e->getMessage(),
                'type' => $event->isEvent() ? 'event' : ($event->isIdentify() ? 'identify' : 'unknown'),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 특정 이벤트 여부에 따라 사용자 속성을 추가합니다
     *.
     * @param SendAmplitudeEvent $event
     * @return void
     */
    private function addUserProperties(SendAmplitudeEvent $event): void
    {
        if ($event->isEvent() && $event->getEventDto()->eventType == AmplitudeConst::INQUIRY_SUBMITTED && !in_array($event->getEventDto()->userId, [0, null])) {
            // 총 문의 횟수 추가
            $uidx = $event->getEventDto()->userId;

            $contactLogCount = ContactLog::where('uidx', $uidx)->whereIn('type', ['call', 'sms', 'chatt', 'chatting'])->count();
            $contactCount = HouseContact::where('uidx', $uidx)->count();

            $event->getEventDto()->userProperties['inquiry_count_total'] = $contactLogCount + $contactCount;
        }
    }

    /**
     * 실패한 작업을 처리합니다.
     *
     * @param SendAmplitudeEvent $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(SendAmplitudeEvent $event, \Throwable $exception): void
    {
        $this->logger->error('[Amplitude] SendAmplitudeEventListener failed', [
            'error' => $exception->getMessage(),
            'type' => $event->isEvent() ? 'event' : ($event->isIdentify() ? 'identify' : 'unknown'),
            'data' => $event->amplitudeDto ? $event->amplitudeDto->toArray() : null
        ]);
    }
}
