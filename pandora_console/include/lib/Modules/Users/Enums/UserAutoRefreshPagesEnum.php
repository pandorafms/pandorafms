<?php

namespace PandoraFMS\Modules\Users\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum UserAutoRefreshPagesEnum: string
{
    use EnumTrait;

    case AGENT_DETAIL = 'operation/agentes/estado_agente';
    case ALERT_DETAIL = 'operation/agentes/alerts_status';
    case CLUSTER_VIEW = 'enterprise/operation/cluster/cluster';
    case GIS_MAP = 'operation/gis_maps/render_view';
    case GRAPH_VIEWER = 'operation/reporting/graph_viewer';
    case SNMP_CONSOLE = 'operation/snmpconsole/snmp_view';
    case SAP_VIEW = 'general/sap_view';
    case TACTICAL_VIEW = 'operation/agentes/tactical';
    case GROUP_VIEW = 'operation/agentes/group_view';
    case MONITOR_DETAIL = 'operation/agentes/status_monitor';
    case SERVICES = 'enterprise/operation/services/services';
    case DASHBOARD = 'operation/dashboard/dashboard';
    case VISUAL_CONSOLE = 'operation/visual_console/render_view';
    case EVENTS = 'operation/events/events';
}
