<?php

namespace PandoraFMS\Modules\EventFilters\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventFilterCustomDataEnum: int
{
    use EnumTrait;

    case NAME = 0;
    case VALUE = 1;
}
