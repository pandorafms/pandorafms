<?php

namespace PandoraFMS\Modules\Users\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum UserHomeScreenEnum: string
{
    use EnumTrait;

    case default = 'default';
case VISUAL_CONSOLE = 'visual_console';
case EVENT_LIST = 'event_list';
case GROUP_VIEW = 'group_view';
case TACTICAL_VIEW = 'tactical_view';
case ALERT_DETAIL = 'alert_detail';
case EXTERNAL_LINK = 'external_link';
case OTHER = 'other';
case DASHBOARD = 'dashboard';
    }
