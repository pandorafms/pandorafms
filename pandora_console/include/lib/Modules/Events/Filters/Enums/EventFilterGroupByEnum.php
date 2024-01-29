<?php

namespace PandoraFMS\Modules\Events\Filters\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventFilterGroupByEnum: int
{
    use EnumTrait;

    case ALL = 0;
    case EVENTS = 1;
    case AGENTS = 2;
    case EXTRA_IDS = 3;
}
