<?php

namespace PandoraFMS\Modules\Events\Filters\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventFilterCustomDataEnum: int
{
    use EnumTrait;

    case NAME = 0;
    case VALUE = 1;
}
