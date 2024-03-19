<?php

namespace PandoraFMS\Modules\Events\Filters\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventFilterAlertEnum: int
{
    use EnumTrait;

    case ALL = -1;
    case FILTER_ALERT_EVENTS = 0;
    case ONLY_ALERT_EVENTS = 1;
}
