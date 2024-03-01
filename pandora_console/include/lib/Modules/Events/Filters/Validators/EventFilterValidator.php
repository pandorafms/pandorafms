<?php

namespace PandoraFMS\Modules\Events\Filters\Validators;

use PandoraFMS\Modules\Events\Filters\Enums\EventFilterAlertEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterCustomDataEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterGroupByEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterStatusEnum;
use PandoraFMS\Modules\Events\Enums\EventTypeEnum;
use PandoraFMS\Modules\Shared\Validators\Validator;

class EventFilterValidator extends Validator
{
    public const VALIDFILTERALERT = 'ValidFilterAlert';
    public const VALIDFILTERCUSTOMDATA = 'ValidFilterCustomData';
    public const VALIDFILTERGROUPBY = 'ValidFilterGroupBy';
    public const VALIDFILTERSTATUS = 'ValidFilterStatus';
    public const VALIDFILTERTYPE = 'ValidFilterType';

    protected function isValidFilterAlert($section): bool
    {
        $result = EventFilterAlertEnum::get(strtoupper($section));
        return empty($result) === true ? false : true;
    }

    protected function isValidFilterCustomData($status): bool
    {
        $result = EventFilterCustomDataEnum::get(strtoupper($status));
        return empty($result) === true ? false : true;
    }

    protected function isValidFilterGroupBy($type): bool
    {
        $result = EventFilterGroupByEnum::get(strtoupper($type));
        return empty($result) === true ? false : true;
    }

    protected function isValidFilterStatus($status): bool
    {
        $result = EventFilterStatusEnum::get(strtoupper($status));
        return empty($result) === true ? false : true;
    }

    protected function isValidFilterType($type): bool
    {
        $result = EventTypeEnum::get(strtoupper($type));
        return empty($result) === true ? false : true;
    }
}
