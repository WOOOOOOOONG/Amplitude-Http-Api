<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Drivers;

use App\Services\Marketing\Amplitude\Dtos\AmplitudeIdentifyEventDto;
use App\Services\Marketing\Amplitude\Dtos\AmplitudeResponseDto;
use App\Services\Marketing\Amplitude\Exceptions\AmplitudeException;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * AmplitudeIdentifyDriver는 UserProperty만 전송하기 위한 Identify API 드라이버입니다.
 */
class AmplitudeIdentifyDriver
{
    /** @var string API 키 */
    private $apiKey;

    /** @var string Identify API 엔드포인트 URL */
    private $endpoint;

    /** @var array 드라이버 옵션 */
    private $options;

    /** @var Client HTTP 클라이언트 */
    private $client;

    /**
     * AmplitudeIdentifyDriver 생성자.
     *
     * @param string $apiKey API 키
     * @param string|null $endpoint Identify API 엔드포인트
     * @param array $options 옵션
     */
    public function __construct(string $apiKey, string $endpoint = null, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint ?: 'https://api2.amplitude.com/identify';
        $this->options = array_merge([
            'timeout' => 5,
        ], $options);

        $this->client = new Client([
            'timeout' => $this->options['timeout'],
            'verify' => false,
        ]);
    }

    /**
     * Identify 요청을 Amplitude로 전송합니다.
     *
     * @param AmplitudeIdentifyEventDto $identify
     * @return AmplitudeResponseDto
     * @throws AmplitudeException
     */
    public function sendIdentify(AmplitudeIdentifyEventDto $identify): AmplitudeResponseDto
    {
        // Identify 데이터를 Amplitude 형식으로 변환
        $identification = $this->formatIdentify($identify);

        // 요청 페이로드 생성
        $payload = [
            'api_key' => $this->apiKey,
            'identification' => json_encode($identification)
        ];

        try {
            $response = $this->client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => $payload
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            // 성공적인 응답 처리
            if ($statusCode === 200) {
                // Identify API는 단순한 응답을 반환
                return new AmplitudeResponseDto([
                    'code' => 200,
                    'eventsIngested' => 1, // identify는 1개로 간주
                    'payloadSizeBytes' => strlen(json_encode($payload)),
                    'serverUploadTime' => time() * 1000
                ]);
            }

            throw new AmplitudeException(
                'Unexpected response status',
                $statusCode,
                ['response_body' => $body]
            );

        } catch (\Exception $e) {
            return new AmplitudeResponseDto([
                'code' => $e->getCode(),
                'error' => $e->getMessage(),
                'eventsIngested' => 0,
                'payloadSizeBytes' => 0,
                'serverUploadTime' => time() * 1000
            ]);
        }
    }

    /**
     * Identify를 Amplitude API 형식으로 포맷합니다.
     *
     * @param AmplitudeIdentifyEventDto $identify
     * @return array
     */
    private function formatIdentify(AmplitudeIdentifyEventDto $identify): array
    {
        $formatted = [
            'user_id' => $identify->userId,
            'device_id' => $identify->deviceId,
            'user_properties' => $identify->userProperties,
            'time' => $identify->time ?: Carbon::now()->timestamp * 1000,
        ];

        // Optional fields
        $this->addIfNotNull($formatted, 'groups', $identify->groups);
        $this->addIfNotNull($formatted, 'app_version', $identify->appVersion);
        $this->addIfNotNull($formatted, 'platform', $identify->platform);
        $this->addIfNotNull($formatted, 'os_name', $identify->osName);
        $this->addIfNotNull($formatted, 'os_version', $identify->osVersion);
        $this->addIfNotNull($formatted, 'device_brand', $identify->deviceBrand);
        $this->addIfNotNull($formatted, 'device_model', $identify->deviceModel);
        $this->addIfNotNull($formatted, 'carrier', $identify->carrier);
        $this->addIfNotNull($formatted, 'country', $identify->country);
        $this->addIfNotNull($formatted, 'language', $identify->language);
        $this->addIfNotNull($formatted, 'ip', $identify->ip);

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
}