<?php

/**
 * Constants definitions.
 *
 * @category   Library
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Enterprise hook constant.
define('ENTERPRISE_NOT_HOOK', -1);

// Others.
define('GROUP_ALL', 0);

// Date and time formats.
define('DATE_FORMAT', 'Y/m/d');
define('DATE_FORMAT_JS', 'yy/mm/dd');
define('TIME_FORMAT', 'H:i:s');
define('TIME_FORMAT_JS', 'HH:mm:ss');

// Events state constants.
define('EVENT_ALL', -1);
define('EVENT_NEW', 0);
define('EVENT_VALIDATE', 1);
define('EVENT_PROCESS', 2);
define('EVENT_NO_VALIDATED', 3);

// Events group by constants.
define('EVENT_GROUP_REP_ALL', 0);
define('EVENT_GROUP_REP_EVENTS', 1);
define('EVENT_GROUP_REP_AGENTS', 2);
define('EVENT_GROUP_REP_EXTRAIDS', 3);

// Agents disabled status.
define('AGENT_ENABLED', 0);
define('AGENT_DISABLED', 1);

// Module disabled status.
define('MODULE_ENABLED', 0);
define('MODULE_DISABLED', 1);

// Error report codes.
define('NOERR', 11111);
define('ERR_GENERIC', -10000);
define('ERR_EXIST', -20000);
define('ERR_INCOMPLETE', -30000);
define('ERR_DB', -40000);
define('ERR_DB_HOST', -40001);
define('ERR_DB_DB', -40002);
define('ERR_FILE', -50000);
define('ERR_NOCHANGES', -60000);
define('ERR_NODATA', -70000);
define('ERR_CONNECTION', -80000);
define('ERR_DISABLED', -90000);
define('ERR_WRONG', -100000);
define('ERR_WRONG_MR', -100001);
define('ERR_WRONG_PARAMETERS', -100002);
define('ERR_ACL', -110000);
define('ERR_AUTH', -120000);
define('ERR_COULDNT_RESOLVE_HOST', -130000);

// Event status code.
define('EVENT_STATUS_NEW', 0);
define('EVENT_STATUS_INPROCESS', 2);
define('EVENT_STATUS_VALIDATED', 1);

// Seconds in a time unit constants.
define('SECONDS_1MINUTE', 60);
define('SECONDS_2MINUTES', 120);
define('SECONDS_5MINUTES', 300);
define('SECONDS_10MINUTES', 600);
define('SECONDS_15MINUTES', 900);
define('SECONDS_30MINUTES', 1800);
define('SECONDS_1HOUR', 3600);
define('SECONDS_2HOUR', 7200);
define('SECONDS_3HOUR', 10800);
define('SECONDS_5HOUR', 18000);
define('SECONDS_6HOURS', 21600);
define('SECONDS_12HOURS', 43200);
define('SECONDS_1DAY', 86400);
define('SECONDS_2DAY', 172800);
define('SECONDS_4DAY', 345600);
define('SECONDS_5DAY', 432000);
define('SECONDS_1WEEK', 604800);
define('SECONDS_10DAY', 864000);
define('SECONDS_2WEEK', 1209600);
define('SECONDS_15DAYS', 1296000);
define('SECONDS_1MONTH', 2592000);
define('SECONDS_2MONTHS', 5184000);
define('SECONDS_3MONTHS', 7776000);
define('SECONDS_6MONTHS', 15552000);
define('SECONDS_1YEAR', 31536000);
define('SECONDS_2YEARS', 63072000);
define('SECONDS_3YEARS', 94608000);

// Separator constats.
define('SEPARATOR_COLUMN', ';');
define('SEPARATOR_ROW', chr(10));
define('SEPARATOR_META_MODULE', '|-|-|-|');
// Chr(10) is \n.
define('SEPARATOR_COLUMN_CSV', '#');
define('SEPARATOR_ROW_CSV', "@\n");



// Backup paths.
switch ($config['dbtype']) {
    case 'mysql':
    case 'postgresql':
        define('BACKUP_DIR', 'attachment/backups');
        define('BACKUP_FULLPATH', $config['homedir'].'/'.BACKUP_DIR);
    break;

    case 'oracle':
        define('BACKUP_DIR', 'DATA_PUMP_DIR');
        define('BACKUP_FULLPATH', 'DATA_PUMP_DIR');
    break;

    default:
        // Ignore.
    break;
}



// Color constants.
define('COL_CRITICAL', '#e63c52');
define('COL_WARNING', '#f3b200');
define('COL_WARNING_DARK', '#FFB900');
define('COL_NORMAL', '#82b92e');
define('COL_NOTINIT', '#4a83f3');
define('COL_UNKNOWN', '#B2B2B2');
define('COL_DOWNTIME', '#976DB1');
define('COL_IGNORED', '#DDD');
define('COL_ALERTFIRED', '#F36201');
define('COL_MINOR', '#F099A2');
define('COL_MAJOR', '#C97A4A');
define('COL_INFORMATIONAL', '#4a83f3');
define('COL_MAINTENANCE', '#E4E4E4');
define('COL_QUIET', '#5AB7E5');

define('COL_GRAPH1', '#C397F2');
define('COL_GRAPH2', '#FFE66C');
define('COL_GRAPH3', '#92CCA3');
define('COL_GRAPH4', '#EA6D5B');
define('COL_GRAPH5', '#6BD8DD');
define('COL_GRAPH6', '#F49B31');
define('COL_GRAPH7', '#999999');
define('COL_GRAPH8', '#F2B8C1');
define('COL_GRAPH9', '#C4E8C1');
define('COL_GRAPH10', '#C1DBE5');
define('COL_GRAPH11', '#C9C1e0');
define('COL_GRAPH12', '#F45B95');
define('COL_GRAPH13', '#E83128');


// Styles.
// Size of text in characters for truncate.
define('GENERIC_SIZE_TEXT', 50);
define('MENU_SIZE_TEXT', 20);



// Agent module status.
define('AGENT_MODULE_STATUS_ALL', -1);
define('AGENT_MODULE_STATUS_CRITICAL_BAD', 1);
define('AGENT_MODULE_STATUS_CRITICAL_ALERT', 100);
define('AGENT_MODULE_STATUS_NO_DATA', 4);
define('AGENT_MODULE_STATUS_NORMAL', 0);
define('AGENT_MODULE_STATUS_NORMAL_ALERT', 300);
define('AGENT_MODULE_STATUS_NOT_NORMAL', 6);
define('AGENT_MODULE_STATUS_WARNING', 2);
define('AGENT_MODULE_STATUS_WARNING_ALERT', 200);
define('AGENT_MODULE_STATUS_UNKNOWN', 3);
define('AGENT_MODULE_STATUS_NOT_INIT', 5);

// Agent status.
define('AGENT_STATUS_ALL', -1);
define('AGENT_STATUS_CRITICAL', 1);
define('AGENT_STATUS_NORMAL', 0);
define('AGENT_STATUS_NOT_INIT', 5);
define('AGENT_STATUS_NOT_NORMAL', 6);
define('AGENT_STATUS_UNKNOWN', 3);
define('AGENT_STATUS_ALERT_FIRED', 4);
define('AGENT_STATUS_WARNING', 2);

// Pseudo criticity analysis.
define('NO_CRIT', -1);
define('CRIT_0', 0);
define('CRIT_1', 1);
define('CRIT_2', 2);
define('CRIT_3', 3);
define('CRIT_4', 4);
define('CRIT_5', 5);

// Visual maps contants.
// The items kind.
define('STATIC_GRAPH', 0);
define('PERCENTILE_BAR', 3);
define('MODULE_GRAPH', 1);
define('AUTO_SLA_GRAPH', 14);
define('SIMPLE_VALUE', 2);
define('LABEL', 4);
define('ICON', 5);
define('SIMPLE_VALUE_MAX', 6);
define('SIMPLE_VALUE_MIN', 7);
define('SIMPLE_VALUE_AVG', 8);
define('PERCENTILE_BUBBLE', 9);
define('SERVICE', 10);
// Enterprise Item.
define('GROUP_ITEM', 11);
define('BOX_ITEM', 12);
define('LINE_ITEM', 13);
define('CIRCULAR_PROGRESS_BAR', 15);
define('CIRCULAR_INTERIOR_PROGRESS_BAR', 16);
define('DONUT_GRAPH', 17);
define('BARS_GRAPH', 18);
define('CLOCK', 19);
define('COLOR_CLOUD', 20);
define('NETWORK_LINK', 21);
define('ODOMETER', 22);
define('BASIC_CHART', 23);
// Some styles.
define('MIN_WIDTH', 300);
define('MIN_HEIGHT', 120);
define('MIN_WIDTH_CAPTION', 420);
// The process for simple value.
define('PROCESS_VALUE_NONE', 0);
define('PROCESS_VALUE_MIN', 1);
define('PROCESS_VALUE_MAX', 2);
define('PROCESS_VALUE_AVG', 3);
// Status.
define('VISUAL_MAP_STATUS_CRITICAL_BAD', 1);
define('VISUAL_MAP_STATUS_CRITICAL_ALERT', 4);
define('VISUAL_MAP_STATUS_NORMAL', 0);
define('VISUAL_MAP_STATUS_WARNING', 2);
define('VISUAL_MAP_STATUS_UNKNOWN', 3);
define('VISUAL_MAP_STATUS_WARNING_ALERT', 10);
// Wizard.
define('VISUAL_MAP_WIZARD_PARENTS_NONE', 0);
define('VISUAL_MAP_WIZARD_PARENTS_ITEM_MAP', 1);
define('VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP', 2);



// Service constants.
// Status.
define('SERVICE_STATUS_UNKNOWN', -1);
define('SERVICE_STATUS_NORMAL', 0);
define('SERVICE_STATUS_CRITICAL', 1);
define('SERVICE_STATUS_WARNING', 2);
define('SERVICE_STATUS_ALERT', 4);
// Default service weights.
define('SERVICE_WEIGHT_CRITICAL', 1);
define('SERVICE_WEIGHT_WARNING', 0.5);
define('SERVICE_SMART_WEIGHT_CRITICAL', 50);
define('SERVICE_SMART_WEIGHT_WARNING', 30);
// Default service element weights.
define('SERVICE_ELEMENT_WEIGHT_CRITICAL', 1);
define('SERVICE_ELEMENT_WEIGHT_WARNING', 0.5);
define('SERVICE_ELEMENT_WEIGHT_OK', 0);
define('SERVICE_ELEMENT_WEIGHT_UNKNOWN', 0);
define('SERVICE_ELEMENT_SMART_CRITICAL', 100);
define('SERVICE_ELEMENT_SMART_WARNING', 50);
// Service element types.
define('SERVICE_ELEMENT_AGENT', 'agent');
define('SERVICE_ELEMENT_MODULE', 'module');
define('SERVICE_ELEMENT_SERVICE', 'service');
define('SERVICE_ELEMENT_DYNAMIC', 'dynamic');

// Modes.
define('SERVICE_MODE_MANUAL', 0);
define('SERVICE_MODE_SMART', 1);

// New installation Product Logo.
define('HEADER_LOGO_DEFAULT_CLASSIC', 'logo-default-pandorafms.png');
define('HEADER_LOGO_DEFAULT_COLLAPSED', 'logo-default-pandorafms-collapsed.png');
define('HEADER_LOGO_BLACK_CLASSIC', 'logo-black-pandorafms.png');
define('HEADER_LOGO_BLACK_COLLAPSED', 'logo-default-pandorafms-collapsed.png');

// Status images.
// For modules.
define('STATUS_MODULE_OK', 'module_ok.png');
define('STATUS_MODULE_CRITICAL', 'module_critical.png');
define('STATUS_MODULE_WARNING', 'module_warning.png');
define('STATUS_MODULE_NO_DATA', 'module_no_data.png');
define('STATUS_MODULE_UNKNOWN', 'module_unknown.png');
define('STATUS_MODULE_ALERT_TRIGGERED', 'module_alertsfired.png');
// For agents.
define('STATUS_AGENT_CRITICAL', 'agent_critical.png');
define('STATUS_AGENT_WARNING', 'agent_warning.png');
define('STATUS_AGENT_DOWN', 'agent_down.png');
define('STATUS_AGENT_UNKNOWN', 'agent_unknown.png');
define('STATUS_AGENT_OK', 'agent_ok.png');
define('STATUS_AGENT_NO_DATA', 'agent_no_data.png');
define('STATUS_AGENT_NO_MONITORS', 'agent_no_monitors.png');
define('STATUS_AGENT_NOT_INIT', 'agent_notinit.png');
// For alerts.
define('STATUS_ALERT_FIRED', 'alert_fired.png');
define('STATUS_ALERT_NOT_FIRED', 'alert_not_fired.png');
define('STATUS_ALERT_DISABLED', 'alert_disabled.png');
// For servers.
define('STATUS_SERVER_OK', 'server_ok.png');
define('STATUS_SERVER_DOWN', 'server_down.png');
define('STATUS_SERVER_CRASH', 'server_crash.png');


// Status images (ball).
// For modules.
define('STATUS_MODULE_OK_BALL', 'module_ok_ball.png');
define('STATUS_MODULE_CRITICAL_BALL', 'module_critical_ball.png');
define('STATUS_MODULE_WARNING_BALL', 'module_warning_ball.png');
define('STATUS_MODULE_NO_DATA_BALL', 'module_no_data_ball.png');
define('STATUS_MODULE_UNKNOWN_BALL', 'module_unknown_ball.png');
// For agents.
define('STATUS_AGENT_CRITICAL_BALL', 'agent_critical_ball.png');
define('STATUS_AGENT_WARNING_BALL', 'agent_warning_ball.png');
define('STATUS_AGENT_DOWN_BALL', 'agent_down_ball.png');
define('STATUS_AGENT_UNKNOWN_BALL', 'agent_unknown_ball.png');
define('STATUS_AGENT_OK_BALL', 'agent_ok_ball.png');
define('STATUS_AGENT_NO_DATA_BALL', 'agent_no_data_ball.png');
define('STATUS_AGENT_NO_MONITORS_BALL', 'agent_no_monitors_ball.png');
define('STATUS_AGENT_NOT_INIT_BALL', 'agent_notinit_ball.png');
// For alerts.
define('STATUS_ALERT_FIRED_BALL', 'alert_fired_ball.png');
define('STATUS_ALERT_NOT_FIRED_BALL', 'alert_not_fired_ball.png');
define('STATUS_ALERT_DISABLED_BALL', 'alert_disabled_ball.png');
// For servers.
define('STATUS_SERVER_OK_BALL', 'server_ok_ball.png');
define('STATUS_SERVER_DOWN_BALL', 'server_down_ball.png');
define('STATUS_SERVER_CRASH_BALL', 'server_crash_ball.png');



// Events criticity.
define('EVENT_CRIT_MAINTENANCE', 0);
define('EVENT_CRIT_INFORMATIONAL', 1);
define('EVENT_CRIT_NORMAL', 2);
define('EVENT_CRIT_MINOR', 5);
define('EVENT_CRIT_WARNING', 3);
define('EVENT_CRIT_MAJOR', 6);
define('EVENT_CRIT_CRITICAL', 4);
define('EVENT_CRIT_WARNING_OR_CRITICAL', 34);
define('EVENT_CRIT_NOT_NORMAL', 20);
define('EVENT_CRIT_OR_NORMAL', 21);

// Id Module (more use in component).
define('MODULE_DATA', 1);
define('MODULE_NETWORK', 2);
define('MODULE_SNMP', 2);
define('MODULE_PLUGIN', 4);
define('MODULE_PREDICTION', 5);
define('MODULE_WMI', 6);
define('MODULE_WEB', 7);
define('MODULE_WUX', 8);
define('MODULE_WIZARD', 9);

// Type of Modules of Prediction.
define('MODULE_PREDICTION_PLANNING', 1);
define('MODULE_PREDICTION_SERVICE', 2);
define('MODULE_PREDICTION_SYNTHETIC', 3);
define('MODULE_PREDICTION_NETFLOW', 4);
define('MODULE_PREDICTION_CLUSTER', 5);
define('MODULE_PREDICTION_CLUSTER_AA', 6);
define('MODULE_PREDICTION_CLUSTER_AP', 7);
define('MODULE_PREDICTION_TRENDING', 8);


// Forced agent OS ID for cluster agents.
define('CLUSTER_OS_ID', 100);

// Forced agent OS ID for satellite agents.
define('SATELLITE_OS_ID', 19);

// Type of Webserver Modules.
define('MODULE_WEBSERVER_CHECK_LATENCY', 30);
define('MODULE_WEBSERVER_CHECK_SERVER_RESPONSE', 31);
define('MODULE_WEBSERVER_RETRIEVE_NUMERIC_DATA', 32);
define('MODULE_WEBSERVER_RETRIEVE_STRING_DATA', 33);

// SNMP CONSTANTS.
define('SNMP_DIR_MIBS', 'attachment/mibs');

define('SNMP_TRAP_TYPE_NONE', -1);
define('SNMP_TRAP_TYPE_COLD_START', 0);
define('SNMP_TRAP_TYPE_WARM_START', 1);
define('SNMP_TRAP_TYPE_LINK_DOWN', 2);
define('SNMP_TRAP_TYPE_LINK_UP', 3);
define('SNMP_TRAP_TYPE_AUTHENTICATION_FAILURE', 4);
define('SNMP_TRAP_TYPE_OTHER', 5);

// PASSWORD POLICIES.
define('PASSSWORD_POLICIES_OK', 0);
define('PASSSWORD_POLICIES_FIRST_CHANGE', 1);
define('PASSSWORD_POLICIES_EXPIRED', 2);

// SERVER TYPES.
define('SERVER_TYPE_DATA', 0);
define('SERVER_TYPE_NETWORK', 1);
define('SERVER_TYPE_SNMP', 2);
define('SERVER_TYPE_DISCOVERY', 3);
define('SERVER_TYPE_PLUGIN', 4);
define('SERVER_TYPE_PREDICTION', 5);
define('SERVER_TYPE_WMI', 6);
define('SERVER_TYPE_EXPORT', 7);
define('SERVER_TYPE_INVENTORY', 8);
define('SERVER_TYPE_WEB', 9);
define('SERVER_TYPE_EVENT', 10);
define('SERVER_TYPE_ENTERPRISE_ICMP', 11);
define('SERVER_TYPE_ENTERPRISE_SNMP', 12);
define('SERVER_TYPE_ENTERPRISE_SATELLITE', 13);
define('SERVER_TYPE_ENTERPRISE_TRANSACTIONAL', 14);
define('SERVER_TYPE_MAINFRAME', 15);
define('SERVER_TYPE_SYNC', 16);
define('SERVER_TYPE_WUX', 17);
define('SERVER_TYPE_SYSLOG', 18);
define('SERVER_TYPE_AUTOPROVISION', 19);
define('SERVER_TYPE_MIGRATION', 20);
define('SERVER_TYPE_ALERT', 21);
define('SERVER_TYPE_CORRELATION', 22);
define('SERVER_TYPE_NCM', 23);
define('SERVER_TYPE_NETFLOW', 24);

// REPORTS.
define('REPORT_TOP_N_MAX', 1);
define('REPORT_TOP_N_MIN', 2);
define('REPORT_TOP_N_AVG', 0);

define('REPORT_TOP_N_ONLY_GRAPHS', 2);
define('REPORT_TOP_N_SHOW_TABLE_GRAPS', 1);
define('REPORT_TOP_N_ONLY_TABLE', 0);

define('REPORT_EXCEPTION_CONDITION_EVERYTHING', 0);
define('REPORT_EXCEPTION_CONDITION_GE', 1);
define('REPORT_EXCEPTION_CONDITION_LE', 5);
define('REPORT_EXCEPTION_CONDITION_L', 2);
define('REPORT_EXCEPTION_CONDITION_G', 6);
define('REPORT_EXCEPTION_CONDITION_E', 7);
define('REPORT_EXCEPTION_CONDITION_NE', 8);
define('REPORT_EXCEPTION_CONDITION_OK', 3);
define('REPORT_EXCEPTION_CONDITION_NOT_OK', 4);

define('REPORT_ITEM_ORDER_BY_AGENT_NAME', 3);
define('REPORT_ITEM_ORDER_BY_ASCENDING', 2);
define('REPORT_ITEM_ORDER_BY_DESCENDING', 1);
define('REPORT_ITEM_ORDER_BY_UNSORT', 0);

define('REPORT_ITEM_DYNAMIC_HEIGHT', 230);

define('REPORT_OLD_TYPE_SIMPLE_GRAPH', 1);
define('REPORT_OLD_TYPE_CUSTOM_GRAPH', 2);
define('REPORT_OLD_TYPE_SLA', 3);
define('REPORT_OLD_TYPE_MONITOR_REPORT', 6);
define('REPORT_OLD_TYPE_AVG_VALUE', 7);
define('REPORT_OLD_TYPE_MAX_VALUE', 8);
define('REPORT_OLD_TYPE_MIN_VALUE', 9);
define('REPORT_OLD_TYPE_SUMATORY', 10);

define('REPORT_GENERAL_NOT_GROUP_BY_AGENT', 0);
define('REPORT_GENERAL_GROUP_BY_AGENT', 1);

define('REPORT_PERMISSIONS_NOT_GROUP_BY_GROUP', 0);
define('REPORT_PERMISSIONS_GROUP_BY_GROUP', 1);

define('REPORTING_CUSTOM_GRAPH_LEGEND_EACH_MODULE_VERTICAL_SIZE', 15);

// POLICIES.
define('POLICY_UPDATED', 0);
define('POLICY_PENDING_DATABASE', 1);
define('POLICY_PENDING_ALL', 2);

define('STATUS_IN_QUEUE_OUT', 0);
define('STATUS_IN_QUEUE_IN', 1);
define('STATUS_IN_QUEUE_APPLYING', 2);

define('MODULE_UNLINKED', 0);
define('MODULE_LINKED', 1);
define('MODULE_PENDING_UNLINK', 10);
define('MODULE_PENDING_LINK', 11);

// EVENTS.
define('EVENTS_GOING_UNKNOWN', 'going_unknown');
define('EVENTS_UNKNOWN', 'unknown');
define('EVENTS_ALERT_FIRED', 'alert_fired');
define('EVENTS_ALERT_RECOVERED', 'alert_recovered');
define('EVENTS_ALERT_CEASED', 'alert_ceased');
define('EVENTS_ALERT_MANUAL_VALIDATION', 'alert_manual_validation');
define('EVENTS_RECON_HOST_DETECTED', 'recon_host_detected');
define('EVENTS_SYSTEM', 'system');
define('EVENTS_ERROR', 'error');
define('EVENTS_NEW_AGENT', 'new_agent');
define('EVENTS_GOING_UP_WARNING', 'going_up_warning');
define('EVENTS_GOING_UP_CRITICAL', 'going_up_critical');
define('EVENTS_GOING_DOWN_WARNING', 'going_down_warning');
define('EVENTS_GOING_DOWN_NORMAL', 'going_down_normal');
define('EVENTS_GOING_DOWN_CRITICAL', 'going_down_critical');
define('EVENTS_GOING_UP_NORMAL', 'going_up_normal');
define('EVENTS_CONFIGURATION_CHANGE', 'configuration_change');

// CUSTOM GRAPHS.
define('CUSTOM_GRAPH_AREA', 0);
define('CUSTOM_GRAPH_STACKED_AREA', 1);
define('CUSTOM_GRAPH_LINE', 2);
define('CUSTOM_GRAPH_STACKED_LINE', 3);
define('CUSTOM_GRAPH_BULLET_CHART', 4);
define('CUSTOM_GRAPH_GAUGE', 5);
define('CUSTOM_GRAPH_HBARS', 6);
define('CUSTOM_GRAPH_VBARS', 7);
define('CUSTOM_GRAPH_PIE', 8);
define('CUSTOM_GRAPH_BULLET_CHART_THRESHOLD', 9);

// COLLECTIONS.
define('COLLECTION_PENDING_APPLY', 0);
define('COLLECTION_CORRECT', 1);
define('COLLECTION_ERROR_LOST_DIRECTORY', 2);
define('COLLECTION_UNSAVED', 3);

// PAGINATION.
define('PAGINATION_BLOCKS_LIMIT', 15);

// CHARTS.
define('CHART_DEFAULT_WIDTH', 150);
define('CHART_DEFAULT_HEIGHT', 110);

define('CHART_DEFAULT_ALPHA', 50);

// Statwin.
define('STATWIN_DEFAULT_CHART_WIDTH', 555);
define('STATWIN_DEFAULT_CHART_HEIGHT', 245);

// Dashboard.
define('DASHBOARD_DEFAULT_COUNT_CELLS', 1);

define('OPTION_TEXT', 1);
define('OPTION_SINGLE_SELECT', 2);
define('OPTION_MULTIPLE_SELECT', 3);
define('OPTION_BOOLEAN', 4);
define('OPTION_TEXTAREA', 5);
define('OPTION_TREE_GROUP_SELECT', 6);
define('OPTION_SINGLE_SELECT_TIME', 7);
define('OPTION_CUSTOM_INPUT', 8);
define('OPTION_AGENT_AUTOCOMPLETE', 9);
define('OPTION_SELECT_MULTISELECTION', 10);
define('OPTION_COLOR_PICKER', 11);

// Transactional map constants.
define('NODE_TYPE', 0);
define('ARROW_TYPE', 1);

// Discovery task steps.
define('STEP_SCANNING', 1);
define('STEP_CAPABILITIES', 7);
define('STEP_AFT', 2);
define('STEP_TRACEROUTE', 3);
define('STEP_GATEWAY', 4);
define('STEP_MONITORING', 5);
define('STEP_PROCESSING', 6);
define('STEP_STATISTICS', 1);
define('STEP_APP_SCAN', 2);
define('STEP_CUSTOM_QUERIES', 3);

// Networkmap node types.
define('NODE_AGENT', 0);
define('NODE_MODULE', 1);
define('NODE_PANDORA', 2);
define('NODE_GENERIC', 3);

// Other constants.
define('STATUS_OK', 0);
define('STATUS_ERROR', 1);

// Maps new networkmaps and  new visualmaps.
define('MAP_TYPE_NETWORKMAP', 0);
define('MAP_TYPE_VISUALMAP', 1);

define('MAP_REFRESH_TIME', SECONDS_5MINUTES);

define('MAP_SUBTYPE_TOPOLOGY', 0);
define('MAP_SUBTYPE_POLICIES', 1);
define('MAP_SUBTYPE_GROUPS', 2);
define('MAP_SUBTYPE_RADIAL_DYNAMIC', 3);

define('MAP_GENERATION_CIRCULAR', 0);
define('MAP_GENERATION_PLANO', 1);
define('MAP_GENERATION_RADIAL', 2);
define('MAP_GENERATION_SPRING1', 3);
define('MAP_GENERATION_SPRING2', 4);

// Algorithm: Circo.
define('LAYOUT_CIRCULAR', 0);
// Algorithm: Dot.
define('LAYOUT_FLAT', 1);
// Algorithm: Twopi.
define('LAYOUT_RADIAL', 2);
// Algorithm: Neato.
define('LAYOUT_SPRING1', 3);
// Algorithm: Fdp.
define('LAYOUT_SPRING2', 4);
// Extra: radial dynamic.
define('LAYOUT_RADIAL_DYNAMIC', 6);

// Map sources.
define('SOURCE_GROUP', 0);
define('SOURCE_TASK', 1);
define('SOURCE_NETWORK', 2);

// Backward compatibility ~ Migration.
define('MAP_SOURCE_GROUP', 0);
define('MAP_SOURCE_IP_MASK', 1);

define('NETWORKMAP_DEFAULT_WIDTH', 800);
define('NETWORKMAP_DEFAULT_HEIGHT', 800);

// Discovery task types.
define('DISCOVERY_HOSTDEVICES', 0);
define('DISCOVERY_HOSTDEVICES_CUSTOM', 1);
define('DISCOVERY_CLOUD_AWS', 2);
define('DISCOVERY_APP_VMWARE', 3);
define('DISCOVERY_APP_MYSQL', 4);
define('DISCOVERY_APP_ORACLE', 5);
define('DISCOVERY_CLOUD_AWS_EC2', 6);
define('DISCOVERY_CLOUD_AWS_RDS', 7);
define('DISCOVERY_CLOUD_AZURE_COMPUTE', 8);
define('DISCOVERY_DEPLOY_AGENTS', 9);
define('DISCOVERY_APP_SAP', 10);
define('DISCOVERY_APP_DB2', 11);
define('DISCOVERY_APP_MICROSOFT_SQL_SERVER', 12);
define('DISCOVERY_CLOUD_GCP_COMPUTE_ENGINE', 13);
define('DISCOVERY_CLOUD_AWS_S3', 14);

// Force task build tmp results.
define('DISCOVERY_REVIEW', 0);
define('DISCOVERY_STANDARD', 1);
define('DISCOVERY_RESULTS', 2);

// Discovery types matching definition.
define('DISCOVERY_SCRIPT_HOSTDEVICES_CUSTOM', 0);
// Standard applications.
define('DISCOVERY_SCRIPT_APP_VMWARE', 1);
// Cloud environments.
define('DISCOVERY_SCRIPT_CLOUD_AWS', 2);
define('DISCOVERY_SCRIPT_IPAM_RECON', 3);
define('DISCOVERY_SCRIPT_IPMI_RECON', 4);

// Discovery task descriptions.
define('CLOUDWIZARD_AZURE_DESCRIPTION', 'Discovery.Cloud.Azure.Compute');
define('CLOUDWIZARD_AWS_DESCRIPTION', 'Discovery.Cloud.AWS.EC2');
define('CLOUDWIZARD_GOOGLE_DESCRIPTION', 'Discovery.Cloud.GCP');
define('CLOUDWIZARD_VMWARE_DESCRIPTION', 'Discovery.App.VMware');

// Background options.
define('CENTER', 0);
define('MOSAIC', 1);
define('STRECH', 2);
define('FIT_WIDTH', 3);
define('FIT_HEIGH', 4);

// Items of maps.
define('ITEM_TYPE_AGENT_NETWORKMAP', 0);
define('ITEM_TYPE_MODULE_NETWORKMAP', 1);
define('ITEM_TYPE_EDGE_NETWORKMAP', 2);
define('ITEM_TYPE_FICTIONAL_NODE', 3);
define('ITEM_TYPE_MODULEGROUP_NETWORKMAP', 4);
define('ITEM_TYPE_GROUP_NETWORKMAP', 5);
define('ITEM_TYPE_POLICY_NETWORKMAP', 6);

// Another constants new networkmap.
define('DEFAULT_NODE_WIDTH', 30);
define('DEFAULT_NODE_HEIGHT', 30);
define('DEFAULT_NODE_SHAPE', 'circle');
define('DEFAULT_NODE_COLOR', COL_NOTINIT);
define('DEFAULT_NODE_IMAGE', 'images/networkmap/unknown.png');

define('NODE_IMAGE_PADDING', 5);

// Pandora Database HA constants.
define('HA_ACTION_NONE', 0);
define('HA_ACTION_DEPLOY', 1);
define('HA_ACTION_RECOVER', 2);
define('HA_ACTION_PROMOTE', 3);
define('HA_ACTION_DEMOTE', 4);
define('HA_ACTION_DISABLE', 5);
define('HA_ACTION_ENABLE', 6);
define('HA_ACTION_CLEANUP', 7);
define('HA_ACTION_RESYNC', 8);

define('HA_RESYNC', 1);
define('HA_DISABLE', 5);
define('HA_ENABLE', 6);


define('HA_UNINITIALIZED', 0);
define('HA_ONLINE', 1);
define('HA_PENDING', 2);
define('HA_PROCESSING', 3);
define('HA_DISABLED', 4);
define('HA_FAILED', 5);


define('WELCOME_STARTED', 1);
define('W_CONFIGURE_MAIL', 1);
define('W_CREATE_AGENT', 2);
define('W_CREATE_MODULE', 3);
define('W_CREATE_ALERT', 4);
define('W_CREATE_TASK', 5);
define('WELCOME_FINISHED', -1);

// Fixed tnetwork_component values.
define('MODULE_TYPE_NUMERIC', 1);
define('MODULE_TYPE_INCREMENTAL', 2);
define('MODULE_TYPE_BOOLEAN', 3);
define('MODULE_TYPE_ALPHANUMERIC', 4);
define('SCAN_TYPE_FIXED', 1);
define('SCAN_TYPE_DYNAMIC', 2);
define('EXECUTION_TYPE_NETWORK', 1);
define('EXECUTION_TYPE_PLUGIN', 2);

// Id of component type.
define('COMPONENT_TYPE_NETWORK', 2);
define('COMPONENT_TYPE_PLUGIN', 4);
define('COMPONENT_TYPE_WMI', 6);
define('COMPONENT_TYPE_WIZARD', 9);

// Wizard Internal Plugins.
define('PLUGIN_WIZARD_SNMP_MODULE', 1);
define('PLUGIN_WIZARD_SNMP_PROCESS', 2);
define('PLUGIN_WIZARD_WMI_MODULE', 3);

// Module Types.
define('MODULE_TYPE_GENERIC_DATA', 1);
define('MODULE_TYPE_GENERIC_PROC', 2);
define('MODULE_TYPE_GENERIC_DATA_STRING', 3);
define('MODULE_TYPE_GENERIC_DATA_INC', 4);
define('MODULE_TYPE_GENERIC_DATA_INC_ABS', 5);
define('MODULE_TYPE_REMOTE_ICMP_PROC', 6);
define('MODULE_TYPE_REMOTE_ICMP', 7);
define('MODULE_TYPE_REMOTE_TCP', 8);
define('MODULE_TYPE_REMOTE_TCP_PROC', 9);
define('MODULE_TYPE_REMOTE_TCP_STRING', 10);
define('MODULE_TYPE_REMOTE_TCP_INC', 11);
define('MODULE_TYPE_REMOTE_SNMP', 15);
define('MODULE_TYPE_REMOTE_SNMP_INC', 16);
define('MODULE_TYPE_REMOTE_SNMP_STRING', 17);
define('MODULE_TYPE_REMOTE_SNMP_PROC', 18);
define('MODULE_TYPE_ASYNC_PROC', 21);
define('MODULE_TYPE_ASYNC_DATA', 22);
define('MODULE_TYPE_ASYNC_STRING', 23);
define('MODULE_TYPE_WEB_ANALYSIS', 25);
define('MODULE_TYPE_WEB_DATA', 30);
define('MODULE_TYPE_WEB_PROC', 31);
define('MODULE_TYPE_WEB_CONTENT_DATA', 32);
define('MODULE_TYPE_WEB_CONTENT_STRING', 33);
define('MODULE_TYPE_REMOTE_CMD', 34);
define('MODULE_TYPE_REMOTE_CMD_PROC', 35);
define('MODULE_TYPE_REMOTE_CMD_STRING', 36);
define('MODULE_TYPE_REMOTE_CMD_INC', 37);
define('MODULE_TYPE_KEEP_ALIVE', 100);

// Commands basics for external tools.
define('COMMAND_TRACEROUTE', 1);
define('COMMAND_PING', 2);
define('COMMAND_SNMP', 3);
define('COMMAND_NMAP', 4);
define('COMMAND_DIGWHOIS', 5);

// Audit logs.
define('AUDIT_LOG_SETUP', 'Setup');
define('AUDIT_LOG_SYSTEM', 'System');
define('AUDIT_LOG_HACK_ATTEMPT', 'HACK Attempt');
define('AUDIT_LOG_ACL_VIOLATION', 'ACL Violation');
define('AUDIT_LOG_METACONSOLE_NODE', 'Metaconsole node');
define('AUDIT_LOG_USER_REGISTRATION', 'Console user registration');
define('AUDIT_LOG_EXTENSION_MANAGER', 'Extension manager');
define('AUDIT_LOG_WEB_SOCKETS', 'WebSockets engine');
define('AUDIT_LOG_USER_MANAGEMENT', 'User management');
define('AUDIT_LOG_AGENT_MANAGEMENT', 'Agent management');
define('AUDIT_LOG_MODULE_MANAGEMENT', 'Module management');
define('AUDIT_LOG_CATEGORY_MANAGEMENT', 'Category management');
define('AUDIT_LOG_REPORT_MANAGEMENT', 'Report management');
define('AUDIT_LOG_MASSIVE_MANAGEMENT', 'Massive operation management');
define('AUDIT_LOG_POLICY_MANAGEMENT', 'Policy management');
define('AUDIT_LOG_AGENT_REMOTE_MANAGEMENT', 'Agent remote configuration');
define('AUDIT_LOG_FILE_COLLECTION', 'File collection');
define('AUDIT_LOG_FILE_MANAGER', 'File manager');
define('AUDIT_LOG_ALERT_MANAGEMENT', 'Alert management');
define('AUDIT_LOG_ALERT_CORRELATION_MANAGEMENT', 'Alert correlation management');
define('AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT', 'Visual Console Management');
define('AUDIT_LOG_TAG_MANAGEMENT', 'Tag management');
define('AUDIT_LOG_SNMP_MANAGEMENT', 'SNMP management');
define('AUDIT_LOG_DASHBOARD_MANAGEMENT', 'Dashboard management');
define('AUDIT_LOG_SERVICE_MANAGEMENT', 'Service management');
define('AUDIT_LOG_INCIDENT_MANAGEMENT', 'Incident management');
define('AUDIT_LOG_UMC', 'Warp Manager');
define('AUDIT_LOG_NMS_VIOLATION', 'NMS Violation');
define('AUDIT_LOG_ENTERPRISE_VIOLATION', 'Enterprise Violation');

// MIMEs.
define(
    'MIME_TYPES',
    [
        'txt'  => 'text/plain',
        'htm'  => 'text/html',
        'html' => 'text/html',
        'php'  => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'swf'  => 'application/x-shockwave-flash',
        'flv'  => 'video/x-flv',
        // Images.
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'svg'  => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        // Archives.
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        'exe'  => 'application/x-msdownload',
        'msi'  => 'application/x-msdownload',
        'cab'  => 'application/vnd.ms-cab-compressed',
        'gz'   => 'application/x-gzip',
        'gz'   => 'application/x-bzip2',
        // Audio/Video.
        'mp3'  => 'audio/mpeg',
        'qt'   => 'video/quicktime',
        'mov'  => 'video/quicktime',
        // Adobe.
        'pdf'  => 'application/pdf',
        'psd'  => 'image/vnd.adobe.photoshop',
        'ai'   => 'application/postscript',
        'eps'  => 'application/postscript',
        'ps'   => 'application/postscript',
        // MS Office.
        'doc'  => 'application/msword',
        'rtf'  => 'application/rtf',
        'xls'  => 'application/vnd.ms-excel',
        'ppt'  => 'application/vnd.ms-powerpoint',
        // Open Source Office files.
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
    ]
);

// Pandora FMS Enterprise license.
define('LICENSE_FILE', 'customer_key');
// Pandora HA database list.
define('PANDORA_HA_FILE', 'pandora_ha_hosts.conf');

// Home screen values for user definition.
define('HOME_SCREEN_DEFAULT', 'default');
define('HOME_SCREEN_VISUAL_CONSOLE', 'visual_console');
define('HOME_SCREEN_EVENT_LIST', 'event_list');
define('HOME_SCREEN_GROUP_VIEW', 'group_view');
define('HOME_SCREEN_TACTICAL_VIEW', 'tactical_view');
define('HOME_SCREEN_ALERT_DETAIL', 'alert_detail');
define('HOME_SCREEN_EXTERNAL_LINK', 'external_link');
define('HOME_SCREEN_OTHER', 'other');
define('HOME_SCREEN_DASHBOARD', 'dashboard');
