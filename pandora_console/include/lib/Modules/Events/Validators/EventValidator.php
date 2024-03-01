<?php

namespace PandoraFMS\Modules\Events\Validators;

use PandoraFMS\Modules\Events\Enums\EventSeverityEnum;
use PandoraFMS\Modules\Events\Enums\EventStatusEnum;
use PandoraFMS\Modules\Events\Enums\EventTypeEnum;
use PandoraFMS\Modules\Shared\Validators\Validator;

class EventValidator extends Validator
{
    public const VALIDSEVERITY = 'ValidSeverity';
    public const VALIDSTATUS = 'ValidStatus';
    public const VALIDTYPE = 'ValidType';

    protected function isValidSeverity($section): bool
    {
        $result = EventSeverityEnum::get(strtoupper($section));
        return empty($result) === true ? false : true;
    }

    protected function isValidStatus($status): bool
    {
        $result = EventStatusEnum::get(strtoupper($status));
        return empty($result) === true ? false : true;
    }

    protected function isValidType($type): bool
    {
        $result = EventTypeEnum::get(strtoupper($type));
        return empty($result) === true ? false : true;
    }
}
