<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Dtos;

use App\Services\Contracts\Dtos\BaseDto;
use App\Services\Marketing\Exceptions\UnprocessableEntityException;
use Carbon\Carbon;

/**
 * AmplitudeEventDto 클래스는 Amplitude 이벤트 데이터를 위한 데이터 전송 객체입니다.
 *
 * @property int|string|null $userId 사용자 ID
 * @property string|null $deviceId 디바이스 ID
 * @property string $eventType 이벤트 타입
 * @property int|null $time 이벤트 발생 시간 (밀리초)
 * @property array|null $eventProperties 이벤트 속성
 * @property array|null $userProperties 사용자 속성
 * @property array|null $groups 그룹 정보
 * @property array|null $groupProperties 그룹 속성
 * @property string|null $appVersion 앱 버전
 * @property string|null $platform 플랫폼
 * @property string|null $osName OS 이름
 * @property string|null $osVersion OS 버전
 * @property string|null $deviceBrand 디바이스 브랜드
 * @property string|null $deviceManufacturer 디바이스 제조사
 * @property string|null $deviceModel 디바이스 모델
 * @property string|null $carrier 통신사
 * @property string|null $country 국가
 * @property string|null $region 지역
 * @property string|null $city 도시
 * @property string|null $dma DMA
 * @property string|null $language 언어
 * @property float|null $price 가격
 * @property int|null $quantity 수량
 * @property float|null $revenue 수익
 * @property string|null $productId 제품 ID
 * @property string|null $revenueType 수익 타입
 * @property float|null $locationLat 위도
 * @property float|null $locationLng 경도
 * @property string|null $ip IP 주소
 * @property string|null $idfa IDFA
 * @property string|null $idfv IDFV
 * @property string|null $adid ADID
 * @property string|null $androidId Android ID
 * @property int|null $eventId 이벤트 ID
 * @property int|null $sessionId 세션 ID
 * @property string|null $insertId 중복 제거용 ID
 * @property string|null $userAgent 사용자 에이전트
 * @property array|null $plan 트래킹 플랜 정보
 */
class AmplitudeEventDto extends BaseDto
{
    /** @var int|string|null 사용자 ID */
    public $userId;

    /** @var string|null 디바이스 ID */
    public $deviceId;

    /** @var string 이벤트 타입 */
    public $eventType;

    /** @var int|null 이벤트 발생 시간 (밀리초) */
    public $time;

    /** @var array|null 이벤트 속성 */
    public $eventProperties;

    /** @var array|null 사용자 속성 */
    public $userProperties;

    /** @var array|null 그룹 정보 */
    public $groups;

    /** @var array|null 그룹 속성 */
    public $groupProperties;

    /** @var string|null 앱 버전 */
    public $appVersion;

    /** @var string|null 플랫폼 */
    public $platform;

    /** @var string|null OS 이름 */
    public $osName;

    /** @var string|null OS 버전 */
    public $osVersion;

    /** @var string|null 디바이스 브랜드 */
    public $deviceBrand;

    /** @var string|null 디바이스 제조사 */
    public $deviceManufacturer;

    /** @var string|null 디바이스 모델 */
    public $deviceModel;

    /** @var string|null 통신사 */
    public $carrier;

    /** @var string|null 국가 */
    public $country;

    /** @var string|null 지역 */
    public $region;

    /** @var string|null 도시 */
    public $city;

    /** @var string|null DMA */
    public $dma;

    /** @var string|null 언어 */
    public $language;

    /** @var float|null 가격 */
    public $price;

    /** @var int|null 수량 */
    public $quantity;

    /** @var float|null 수익 */
    public $revenue;

    /** @var string|null 제품 ID */
    public $productId;

    /** @var string|null 수익 타입 */
    public $revenueType;

    /** @var float|null 위도 */
    public $locationLat;

    /** @var float|null 경도 */
    public $locationLng;

    /** @var string|null IP 주소 */
    public $ip;

    /** @var string|null IDFA */
    public $idfa;

    /** @var string|null IDFV */
    public $idfv;

    /** @var string|null ADID */
    public $adid;

    /** @var string|null Android ID */
    public $androidId;

    /** @var int|null 이벤트 ID */
    public $eventId;

    /** @var int|null 세션 ID */
    public $sessionId;

    /** @var string|null 중복 제거용 ID */
    public $insertId;

    /** @var string|null 사용자 에이전트 */
    public $userAgent;

    /** @var array|null 트래킹 플랜 정보 */
    public $plan;

    /**
     * AmplitudeEventDto 생성자.
     *
     * @param array $parameters DTO를 초기화할 매개변수 배열
     * @throws UnprocessableEntityException 필수 값이 없을 경우 예외를 던집니다.
     */
    public function __construct(array $parameters = [])
    {
        // user_id와 device_id 중 하나는 필수
        if (empty($parameters['userId']) && empty($parameters['deviceId'])) {
            throw new UnprocessableEntityException(
                'Either user_id or device_id is required',
                null,
                $parameters
            );
        }

        // event_type은 필수
        if (empty($parameters['eventType'])) {
            throw new UnprocessableEntityException(
                'event_type is required',
                null,
                $parameters
            );
        }

        // insertId가 없으면 자동 생성
        if (empty($parameters['insertId'])) {
            $parameters['insertId'] = $this->generateInsertId($parameters);
        }

        // time이 없으면 현재 시간 설정
        if (empty($parameters['time'])) {
            $parameters['time'] = Carbon::now()->timestamp * 1000;
        }

        parent::__construct($parameters);
    }

    /**
     * Insert ID를 생성합니다.
     *
     * @param array $parameters
     * @return string
     */
    private function generateInsertId(array $parameters): string
    {
        $components = [
            $parameters['userId'] ?? '',
            $parameters['deviceId'] ?? '',
            $parameters['eventType'] ?? '',
            $parameters['time'] ?? Carbon::now()->timestamp * 1000,
            uniqid('', true)
        ];

        return md5(implode('_', $components));
    }
}
