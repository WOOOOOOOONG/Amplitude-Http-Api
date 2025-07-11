<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Drivers;

use App\Services\Marketing\Amplitude\Dtos\AmplitudeEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeResponseDto;
use App\Services\Marketing\Amplitude\Exceptions\AmplitudeException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * AmplitudeDriver는 실시간 이벤트 전송을 위한 HTTP API 드라이버입니다.
 */
class AmplitudeDriver implements AmplitudeDriverInterface
{
    /** @var string API 키 */
    private $apiKey;

    /** @var string API 엔드포인트 URL */
    private $endpoint;

    /** @var array 드라이버 옵션 */
    private $options;

    /** @var Client HTTP 클라이언트 */
    private $client;

    /**
     * AmplitudeDriver 생성자.
     *
     * @param string $apiKey API 키
     * @param string $endpoint API 엔드포인트
     * @param array $options 옵션
     */
    public function __construct(string $apiKey, string $endpoint, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->options = array_merge([
            'min_id_length' => 5,
            'timeout' => 5,
        ], $options);

        $this->client = new Client([
            'timeout' => $this->options['timeout'],
            'verify' => false, // SSL 검증 비활성화 (프로덕션에서는 true로 설정)
        ]);
    }

    /**
     * 이벤트들을 Amplitude로 전송합니다.
     *
     * @param array $events AmplitudeEventDto 배열
     * @return AmplitudeResponseDto
     */
    public function sendEvents(array $events): AmplitudeResponseDto
    {
        try {
            if (empty($events)) {
                throw new AmplitudeException('No events to send');
            }

            // 이벤트 배열을 Amplitude 형식으로 변환
            $formattedEvents = array_map(function (AmplitudeEventDto $event) {
                return $this->formatEvent($event);
            }, $events);

            // 요청 페이로드 생성
            $payload = [
                'api_key' => $this->apiKey,
                'events' => $formattedEvents
            ];

            // min_id_length 옵션 추가
            if (isset($this->options['min_id_length'])) {
                $payload['options'] = [
                    'min_id_length' => $this->options['min_id_length']
                ];
            }

            $response = $this->client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            // 성공적인 응답 처리
            if ($statusCode === 200) {
                return new AmplitudeResponseDto([
                    'code' => $body['code'] ?? 200,
                    'eventsIngested' => $body['events_ingested'] ?? count($events),
                    'payloadSizeBytes' => $body['payload_size_bytes'] ?? 0,
                    'serverUploadTime' => $body['server_upload_time'] ?? time() * 1000
                ]);
            }

            throw new AmplitudeException(
                'Unexpected response status',
                $statusCode,
                $body
            );

        } catch (\Exception $e) {
            return new AmplitudeResponseDto([
                'code' => $e instanceof GuzzleException ? $e->getCode() : 500,
                'error' => $e->getMessage(),
                'eventsIngested' => 0,
                'payloadSizeBytes' => 0,
                'serverUploadTime' => Carbon::now()->timestamp * 1000
            ]);
        }
    }

    /**
     * 이벤트를 Amplitude API 형식으로 포맷합니다.
     *
     * @param AmplitudeEventDto $event
     * @return array
     */
    private function formatEvent(AmplitudeEventDto $event): array
    {
        $formatted = [
            'user_id' => $event->userId,
            'device_id' => $event->deviceId,
            'event_type' => $event->eventType,
            'time' => $event->time ?: Carbon::now()->timestamp * 1000,
        ];

        // Optional fields
        if ($event->eventProperties) {
            $formatted['event_properties'] = $event->eventProperties;
        }

        if ($event->userProperties) {
            $formatted['user_properties'] = $event->userProperties;
        }

        if ($event->groups) {
            $formatted['groups'] = $event->groups;
        }

        if ($event->groupProperties) {
            $formatted['group_properties'] = $event->groupProperties;
        }

        // Device & App Info
        $this->addIfNotNull($formatted, 'app_version', $event->appVersion);
        $this->addIfNotNull($formatted, 'platform', $event->platform);
        $this->addIfNotNull($formatted, 'os_name', $event->osName);
        $this->addIfNotNull($formatted, 'os_version', $event->osVersion);
        $this->addIfNotNull($formatted, 'device_brand', $event->deviceBrand);
        $this->addIfNotNull($formatted, 'device_manufacturer', $event->deviceManufacturer);
        $this->addIfNotNull($formatted, 'device_model', $event->deviceModel);
        $this->addIfNotNull($formatted, 'carrier', $event->carrier);

        // Location Info
        $this->addIfNotNull($formatted, 'country', $event->country);
        $this->addIfNotNull($formatted, 'region', $event->region);
        $this->addIfNotNull($formatted, 'city', $event->city);
        $this->addIfNotNull($formatted, 'dma', $event->dma);
        $this->addIfNotNull($formatted, 'language', $event->language);

        // Revenue Info
        $this->addIfNotNull($formatted, 'price', $event->price);
        $this->addIfNotNull($formatted, 'quantity', $event->quantity);
        $this->addIfNotNull($formatted, 'revenue', $event->revenue);
        $this->addIfNotNull($formatted, 'productId', $event->productId);
        $this->addIfNotNull($formatted, 'revenueType', $event->revenueType);

        // Location Coordinates
        $this->addIfNotNull($formatted, 'location_lat', $event->locationLat);
        $this->addIfNotNull($formatted, 'location_lng', $event->locationLng);

        // Other Info
        $this->addIfNotNull($formatted, 'ip', $event->ip);
        $this->addIfNotNull($formatted, 'idfa', $event->idfa);
        $this->addIfNotNull($formatted, 'idfv', $event->idfv);
        $this->addIfNotNull($formatted, 'adid', $event->adid);
        $this->addIfNotNull($formatted, 'android_id', $event->androidId);
        $this->addIfNotNull($formatted, 'event_id', $event->eventId);
        $this->addIfNotNull($formatted, 'session_id', $event->sessionId);
        $this->addIfNotNull($formatted, 'insert_id', $event->insertId);
        $this->addIfNotNull($formatted, 'user_agent', $event->userAgent);

        // Plan Info
        if ($event->plan) {
            $formatted['plan'] = $event->plan;
        }

        return $formatted;
    }

    /**
     * null이 아닌 값만 배열에 추가합니다.
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     */
    private function addIfNotNull(array &$array, string $key, $value): void
    {
        if ($value !== null) {
            $array[$key] = $value;
        }
    }

    /**
     * 드라이버 옵션을 설정합니다.
     *
     * @param array $options
     * @return self
     */
    public function setOptions(array $options): AmplitudeDriverInterface
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * API 키를 설정합니다.
     *
     * @param string $apiKey
     * @return self
     */
    public function setApiKey(string $apiKey): AmplitudeDriverInterface
    {
        $this->apiKey = $apiKey;
        return $this;
    }
}
