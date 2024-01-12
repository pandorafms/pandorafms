<?php

namespace PandoraFMS\Modules\Users\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum UserMetaconsoleAccessEnum: string
{
    use EnumTrait;

case BASIC = 'basic';
case ADVANCED = 'advanced';
    }
