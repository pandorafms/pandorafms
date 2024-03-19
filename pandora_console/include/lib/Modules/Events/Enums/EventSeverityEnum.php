<?php

namespace PandoraFMS\Modules\Events\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventSeverityEnum: int
{
    use EnumTrait;

    case MAINTENANCE = 0;
    case INFORMATIONAL = 1;
    case NORMAL = 2;
    case WARNING = 3;
    case CRITICAL = 4;
    case MINOR = 5;
    case MAJOR = 6;
}
