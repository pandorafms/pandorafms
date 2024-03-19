<?php

namespace PandoraFMS\Modules\Events\Filters\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventFilterStatusEnum: int
{
    use EnumTrait;

    case ALL = -1;
    case NEW = 0;
    case VALIDATED = 1;
    case IN_PROCESS = 2;
    case NOT_VALIDATED = 3;
    case NOT_IN_PROCESS = 4;
}
