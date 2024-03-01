<?php

namespace PandoraFMS\Modules\Events\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventStatusEnum: int
{
    use EnumTrait;

    case NEW = 0;
    case VALIDATED = 1;
    case INPROCESS = 2;
}
