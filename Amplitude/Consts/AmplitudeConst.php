<?php declare(strict_types=1);

namespace App\Services\Marketing\Amplitude\Consts;

class AmplitudeConst
{
    // 문의 완료 이벤트
    public const INQUIRY_SUBMITTED = 'inquiry_submitted';

    // Z회원 등업 이벤트
    public const Z_MEMBER_LEVEL_UP = 'z_member_level_up';

    // 중개회원 가입 완료 이벤트
    public const AGENT_SIGNUP_COMPLETE = 'agent_signup_complete';

    // 찜하기 이벤트
    public const FAVORITES_ADD = 'favorites_add';
}
