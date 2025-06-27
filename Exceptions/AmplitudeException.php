<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Exceptions;

use Exception;
use Throwable;

/**
 * AmplitudeException 클래스는 Amplitude API 관련 예외를 처리합니다.
 */
class AmplitudeException extends Exception
{
    /** @var array|null 응답 데이터 */
    private $responseData;

    /**
     * AmplitudeException 생성자.
     *
     * @param string $message 예외 메시지
     * @param int $code 예외 코드
     * @param array|null $responseData API 응답 데이터
     * @param Throwable|null $previous 이전 예외
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?array $responseData = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    /**
     * 응답 데이터를 반환합니다.
     *
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
