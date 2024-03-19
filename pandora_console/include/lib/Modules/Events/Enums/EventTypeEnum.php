<?php

namespace PandoraFMS\Modules\Events\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum EventTypeEnum: string
{
    use EnumTrait;

    case GOING_UNKNOWN = 'going_unknown';
    case UNKNOWN = 'unknown';
    case ALERT_FIRED = 'alert_fired';
    case ALERT_RECOVERED = 'alert_recovered';
    case ALERT_CEASED = 'alert_ceased';
    case ALERT_MANUAL_VALIDATION = 'alert_manual_validation';
    case RECON_HOST_DETECTED = 'recon_host_detected';
    case SYSTEM = 'system';
    case ERROR = 'error';
    case NEW_AGENT = 'new_agent';
    case GOING_UP_WARNING = 'going_up_warning';
    case GOING_DOWN_WARNING = 'going_down_warning';
    case GOING_UP_CRITICAL = 'going_up_critical';
    case GOING_DOWN_CRITICAL = 'going_down_critical';
    case GOING_UP_NORMAL = 'going_up_normal';
    case GOING_DOWN_NORMAL = 'going_down_normal';
    case CONFIGURATION_CHANGE = 'configuration_change';
    case NCM = 'ncm';
}
