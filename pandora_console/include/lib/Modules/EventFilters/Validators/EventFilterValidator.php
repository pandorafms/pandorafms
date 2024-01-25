<?php

namespace PandoraFMS\Modules\EventFilters\Validators;

use PandoraFMS\Modules\EventFilters\Enums\EventFilterAlertEnum;
use PandoraFMS\Modules\EventFilters\Enums\EventFilterCustomDataEnum;
use PandoraFMS\Modules\EventFilters\Enums\EventFilterGroupByEnum;
use PandoraFMS\Modules\EventFilters\Enums\EventFilterStatusEnum;
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
