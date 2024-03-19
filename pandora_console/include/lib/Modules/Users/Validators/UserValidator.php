<?php

namespace PandoraFMS\Modules\Users\Validators;

use PandoraFMS\Modules\Shared\Validators\Validator;
use PandoraFMS\Modules\Users\Enums\UserHomeScreenEnum;
use PandoraFMS\Modules\Users\Enums\UserMetaconsoleAccessEnum;

class UserValidator extends Validator
{
    public const VALIDSECTION = 'ValidSection';
    public const VALIDMETACONSOLEACCESS = 'ValidMetaconsoleAccess';

    protected function isValidSection($section): bool
    {
        $result = UserHomeScreenEnum::get(strtoupper($section));
        return empty($result) === true ? false : true;
    }

    protected function isValidMetaconsoleAccess($metaconsoleAccess): bool
    {
        $result = UserMetaconsoleAccessEnum::get(strtoupper($metaconsoleAccess));
        return empty($result) === true ? false : true;
    }
}
