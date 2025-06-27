<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Dtos;

use App\Services\Contracts\Dtos\BaseDto;
use App\Services\Marketing\Exceptions\UnprocessableEntityException;
use Carbon\Carbon;

/**
 * AmplitudeIdentifyDto 클래스는 Amplitude Identify API를 위한 데이터 전송 객체입니다.
 * UserProperty만 전송할 때 사용합니다.
 *
 * @property string|null $userId 사용자 ID
 * @property string|null $deviceId 디바이스 ID
 * @property array $userProperties 사용자 속성 (set, add, remove 등)
 * @property array|null $groups 그룹 정보
 * @property string|null $appVersion 앱 버전
 * @property string|null $platform 플랫폼
 * @property string|null $osName OS 이름
 * @property string|null $osVersion OS 버전
 * @property string|null $deviceBrand 디바이스 브랜드
 * @property string|null $deviceModel 디바이스 모델
 * @property string|null $carrier 통신사
 * @property string|null $country 국가
 * @property string|null $language 언어
 * @property string|null $ip IP 주소
 * @property int|null $time 시간 (밀리초)
 */
class AmplitudeIdentifyEventDto extends BaseDto
{
    /** @var string|null 사용자 ID */
    public $userId;

    /** @var string|null 디바이스 ID */
    public $deviceId;

    /** @var array 사용자 속성 */
    public $userProperties;

    /** @var array|null 그룹 정보 */
    public $groups;

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

    /** @var string|null 디바이스 모델 */
    public $deviceModel;

    /** @var string|null 통신사 */
    public $carrier;

    /** @var string|null 국가 */
    public $country;

    /** @var string|null 언어 */
    public $language;

    /** @var string|null IP 주소 */
    public $ip;

    /** @var int|null 시간 (밀리초) */
    public $time;

    /**
     * AmplitudeIdentifyDto 생성자.
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

        // user_properties는 필수
        if (empty($parameters['userProperties'])) {
            throw new UnprocessableEntityException(
                'user_properties is required for identify',
                null,
                $parameters
            );
        }

        // time이 없으면 현재 시간 설정
        if (empty($parameters['time'])) {
            $parameters['time'] = Carbon::now()->timestamp * 1000;
        }

        parent::__construct($parameters);
    }

    /**
     * 사용자 속성 설정 (기존 값 덮어쓰기)
     *
     * @param string $property
     * @param mixed $value
     * @return self
     */
    public function set(string $property, $value): self
    {
        if (!isset($this->userProperties['$set'])) {
            $this->userProperties['$set'] = [];
        }
        $this->userProperties['$set'][$property] = $value;
        return $this;
    }

    /**
     * 사용자 속성 추가 (숫자형 속성에 값 더하기)
     *
     * @param string $property
     * @param int|float $value
     * @return self
     */
    public function add(string $property, $value): self
    {
        if (!isset($this->userProperties['$add'])) {
            $this->userProperties['$add'] = [];
        }
        $this->userProperties['$add'][$property] = $value;
        return $this;
    }

    /**
     * 사용자 속성 삭제
     *
     * @param string $property
     * @return self
     */
    public function unset(string $property): self
    {
        if (!isset($this->userProperties['$unset'])) {
            $this->userProperties['$unset'] = [];
        }
        $this->userProperties['$unset'][$property] = '-';
        return $this;
    }

    /**
     * 배열 속성에 값 추가
     *
     * @param string $property
     * @param mixed $value
     * @return self
     */
    public function append(string $property, $value): self
    {
        if (!isset($this->userProperties['$append'])) {
            $this->userProperties['$append'] = [];
        }
        $this->userProperties['$append'][$property] = $value;
        return $this;
    }

    /**
     * 유효성 검증
     *
     * @return bool
     * @throws UnprocessableEntityException
     */
    public function validate(): bool
    {
        if (empty($this->userId) && empty($this->deviceId)) {
            throw new UnprocessableEntityException('Either user_id or device_id is required');
        }

        if (empty($this->userProperties)) {
            throw new UnprocessableEntityException('user_properties is required');
        }

        return true;
    }
}
