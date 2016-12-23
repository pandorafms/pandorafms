<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Constants
 */

/* Enterprise hook constant */
define ('ENTERPRISE_NOT_HOOK',	-1);

/* Others */
define('GROUP_ALL', 0);

/* Date and time formats */
define('DATE_FORMAT',		'Y/m/d');
define('DATE_FORMAT_JS',	'yy/mm/dd');
define('TIME_FORMAT',		'H:i:s');
define('TIME_FORMAT_JS',	'HH:mm:ss');

/* Events state constants */
define ('EVENT_NEW',		0);
define ('EVENT_VALIDATE',	1);
define ('EVENT_PROCESS',	2);



/* Agents disabled status */
define ('AGENT_ENABLED',	0);
define ('AGENT_DISABLED',	1);



/* Error report codes */
define ('NOERR',					11111);
define ('ERR_GENERIC',				-10000);
define ('ERR_EXIST',				-20000);
define ('ERR_INCOMPLETE',			-30000);
define ('ERR_DB', 					-40000);
define ('ERR_DB_HOST', 				-40001);
define ('ERR_DB_DB', 				-40002);
define ('ERR_FILE', 				-50000);
define ('ERR_NOCHANGES',			-60000);
define ('ERR_NODATA',				-70000);
define ('ERR_CONNECTION',			-80000);
define ('ERR_DISABLED',				-90000);
define ('ERR_WRONG',				-100000);
define ('ERR_WRONG_NAME',			-100001);
define ('ERR_WRONG_PARAMETERS',		-100002);
define ('ERR_ACL',					-110000);
define ('ERR_AUTH',					-120000);
define ('ERR_COULDNT_RESOLVE_HOST',	-130000);

/* Event status code */
define ('EVENT_STATUS_NEW',			0);
define ('EVENT_STATUS_INPROCESS',	2);
define ('EVENT_STATUS_VALIDATED',	1);

/* Seconds in a time unit constants */
define('SECONDS_1MINUTE',	60);
define('SECONDS_2MINUTES',	120);
define('SECONDS_5MINUTES',	300);
define('SECONDS_10MINUTES',	600);
define('SECONDS_15MINUTES',	900);
define('SECONDS_30MINUTES',	1800);
define('SECONDS_1HOUR',		3600);
define('SECONDS_2HOUR',		7200);
define('SECONDS_3HOUR',		10800);
define('SECONDS_5HOUR',		18000);
define('SECONDS_6HOURS',	21600);
define('SECONDS_12HOURS',	43200);
define('SECONDS_1DAY',		86400);
define('SECONDS_2DAY',		172800);
define('SECONDS_4DAY',		345600);
define('SECONDS_5DAY',		432000);
define('SECONDS_1WEEK',		604800);
define('SECONDS_10DAY',		864000);
define('SECONDS_2WEEK',		1209600);
define('SECONDS_15DAYS',	1296000);
define('SECONDS_1MONTH',	2592000);
define('SECONDS_2MONTHS',	5184000);
define('SECONDS_3MONTHS',	7776000);
define('SECONDS_6MONTHS',	15552000);
define('SECONDS_1YEAR',		31104000);
define('SECONDS_2YEARS',	62208000);
define('SECONDS_3YEARS',	93312000);



/* Separator constats */
define('SEPARATOR_COLUMN',		';');
define('SEPARATOR_ROW',			chr(10)); //chr(10) = '\n'
define('SEPARATOR_COLUMN_CSV',	"#");
define('SEPARATOR_ROW_CSV',		"@\n");



/* Backup paths */
switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		define ('BACKUP_DIR',		'attachment/backups');
		define ('BACKUP_FULLPATH',	$config['homedir'] . '/' . BACKUP_DIR);
		break;
	case "oracle":
		define ('BACKUP_DIR',		'DATA_PUMP_DIR');
		define ('BACKUP_FULLPATH',	'DATA_PUMP_DIR');
		break;
}



/* Color constants */
define('COL_CRITICAL',		'#FC4444');
define('COL_WARNING',		'#FAD403');
define('COL_WARNING_DARK',	'#FFB900');
define('COL_NORMAL',		'#80BA27');
define('COL_NOTINIT',		'#3BA0FF');
define('COL_UNKNOWN',		'#B2B2B2');
define('COL_ALERTFIRED',	'#FFA631');
define('COL_MINOR',			'#F099A2');
define('COL_MAJOR',			'#C97A4A');
define('COL_INFORMATIONAL',	'#E4E4E4');
define('COL_MAINTENANCE',	'#3BA0FF');

define('COL_GRAPH1',	'#C397F2');
define('COL_GRAPH2',	'#FFE66C');
define('COL_GRAPH3',	'#92CCA3');
define('COL_GRAPH4',	'#EA6D5B');
define('COL_GRAPH5',	'#6BD8DD');
define('COL_GRAPH6',	'#F49B31');
define('COL_GRAPH7',	'#999999');
define('COL_GRAPH8',	'#F2B8C1');
define('COL_GRAPH9',	'#C4E8C1');
define('COL_GRAPH10',	'#C1DBE5');
define('COL_GRAPH11',	'#C9C1e0');
define('COL_GRAPH12',	'#F45B95');
define('COL_GRAPH13',	'#E83128');


/* The styles */
/* Size of text in characters for truncate */
define('GENERIC_SIZE_TEXT',	25);



/* Agent module status */
define('AGENT_MODULE_STATUS_ALL',				-1);
define('AGENT_MODULE_STATUS_CRITICAL_BAD',		1);
define('AGENT_MODULE_STATUS_CRITICAL_ALERT',	100);
define('AGENT_MODULE_STATUS_NO_DATA',			4);
define('AGENT_MODULE_STATUS_NORMAL',			0);
define('AGENT_MODULE_STATUS_NORMAL_ALERT',		300);
define('AGENT_MODULE_STATUS_NOT_NORMAL',		6);
define('AGENT_MODULE_STATUS_WARNING',			2);
define('AGENT_MODULE_STATUS_WARNING_ALERT',		200);
define('AGENT_MODULE_STATUS_UNKNOWN',			3);
define('AGENT_MODULE_STATUS_NOT_INIT',			5);

/* Agent status */
define('AGENT_STATUS_ALL',			-1);
define('AGENT_STATUS_CRITICAL',		1);
define('AGENT_STATUS_NORMAL',		0);
define('AGENT_STATUS_NOT_INIT',		5);
define('AGENT_STATUS_NOT_NORMAL',	6);
define('AGENT_STATUS_UNKNOWN',		3);
define('AGENT_STATUS_ALERT_FIRED',	4);
define('AGENT_STATUS_WARNING',		2);


/* Visual maps contants */
//The items kind
define('STATIC_GRAPH',		0);
define('PERCENTILE_BAR',	3);
define('MODULE_GRAPH',		1);
define('SIMPLE_VALUE',		2);
define('LABEL',				4);
define('ICON',				5);
define('SIMPLE_VALUE_MAX',	6);
define('SIMPLE_VALUE_MIN',	7);
define('SIMPLE_VALUE_AVG',	8);
define('PERCENTILE_BUBBLE',	9);
define('SERVICE',			10); //Enterprise Item.
define('GROUP_ITEM',		11);
define('BOX_ITEM',			12);
define('LINE_ITEM',			13);
//Some styles
define('MIN_WIDTH',			300);
define('MIN_HEIGHT',		120);
define('MIN_WIDTH_CAPTION',	420);
//The process for simple value
define('PROCESS_VALUE_NONE',	0);
define('PROCESS_VALUE_MIN',		1);
define('PROCESS_VALUE_MAX',		2);
define('PROCESS_VALUE_AVG',		3);
//Status
define('VISUAL_MAP_STATUS_CRITICAL_BAD',	1);
define('VISUAL_MAP_STATUS_CRITICAL_ALERT',	4);
define('VISUAL_MAP_STATUS_NORMAL',			0);
define('VISUAL_MAP_STATUS_WARNING',			2);
define('VISUAL_MAP_STATUS_UNKNOWN',			3);
define('VISUAL_MAP_STATUS_WARNING_ALERT',	10);
//Wizard
define('VISUAL_MAP_WIZARD_PARENTS_NONE',				0);
define('VISUAL_MAP_WIZARD_PARENTS_ITEM_MAP',			1);
define('VISUAL_MAP_WIZARD_PARENTS_AGENT_RELANTIONSHIP',	2);



/* Service constants */
//Status
define('SERVICE_STATUS_UNKNOWN',			-1);
define('SERVICE_STATUS_NORMAL',				0);
define('SERVICE_STATUS_CRITICAL',			1);
define('SERVICE_STATUS_WARNING',			2);
define('SERVICE_STATUS_ALERT',				4);
//Default weights
define('SERVICE_WEIGHT_CRITICAL',			1);
define('SERVICE_WEIGHT_WARNING',			0.5);
define('SERVICE_ELEMENT_WEIGHT_CRITICAL',	1);
define('SERVICE_ELEMENT_WEIGHT_WARNING',	0.5);
define('SERVICE_ELEMENT_WEIGHT_OK',			0);
define('SERVICE_ELEMENT_WEIGHT_UNKNOWN',	0);
//Modes
define('SERVICE_MODE_MANUAL',				0);
define('SERVICE_MODE_AUTO',					1);
define('SERVICE_MODE_SIMPLE',				2);


/* Status images */
//For modules
define ('STATUS_MODULE_OK',			'module_ok.png');
define ('STATUS_MODULE_CRITICAL',	'module_critical.png');
define ('STATUS_MODULE_WARNING',	'module_warning.png');
define ('STATUS_MODULE_NO_DATA',	'module_no_data.png');
define ('STATUS_MODULE_UNKNOWN',	'module_unknown.png');
//For agents
define ('STATUS_AGENT_CRITICAL',	'agent_critical.png');
define ('STATUS_AGENT_WARNING',		'agent_warning.png');
define ('STATUS_AGENT_DOWN',		'agent_down.png');
define ('STATUS_AGENT_UNKNOWN',		'agent_unknown.png');
define ('STATUS_AGENT_OK',			'agent_ok.png');
define ('STATUS_AGENT_NO_DATA',		'agent_no_data.png');
define ('STATUS_AGENT_NO_MONITORS',	'agent_no_monitors.png');
define ('STATUS_AGENT_NOT_INIT',	'agent_notinit.png');
//For alerts
define ('STATUS_ALERT_FIRED',		'alert_fired.png');
define ('STATUS_ALERT_NOT_FIRED',	'alert_not_fired.png');
define ('STATUS_ALERT_DISABLED',	'alert_disabled.png');
//For servers
define ('STATUS_SERVER_OK',			'server_ok.png');
define ('STATUS_SERVER_DOWN',		'server_down.png');


/* Status images (ball) */
//For modules
define ('STATUS_MODULE_OK_BALL',			'module_ok_ball.png');
define ('STATUS_MODULE_CRITICAL_BALL',		'module_critical_ball.png');
define ('STATUS_MODULE_WARNING_BALL',		'module_warning_ball.png');
define ('STATUS_MODULE_NO_DATA_BALL',		'module_no_data_ball.png');
define ('STATUS_MODULE_UNKNOWN_BALL',		'module_unknown_ball.png');
//For agents
define ('STATUS_AGENT_CRITICAL_BALL',		'agent_critical_ball.png');
define ('STATUS_AGENT_WARNING_BALL',		'agent_warning_ball.png');
define ('STATUS_AGENT_DOWN_BALL',			'agent_down_ball.png');
define ('STATUS_AGENT_UNKNOWN_BALL',		'agent_unknown_ball.png');
define ('STATUS_AGENT_OK_BALL',				'agent_ok_ball.png');
define ('STATUS_AGENT_NO_DATA_BALL',		'agent_no_data_ball.png');
define ('STATUS_AGENT_NO_MONITORS_BALL',	'agent_no_monitors_ball.png');
define ('STATUS_AGENT_NOT_INIT_BALL',		'agent_notinit_ball.png');
//For alerts
define ('STATUS_ALERT_FIRED_BALL',			'alert_fired_ball.png');
define ('STATUS_ALERT_NOT_FIRED_BALL',		'alert_not_fired_ball.png');
define ('STATUS_ALERT_DISABLED_BALL',		'alert_disabled_ball.png');
//For servers
define ('STATUS_SERVER_OK_BALL',			'server_ok_ball.png');
define ('STATUS_SERVER_DOWN_BALL',			'server_down_ball.png');



/* Events criticity */
define ('EVENT_CRIT_MAINTENANCE',			0);
define ('EVENT_CRIT_INFORMATIONAL',			1);
define ('EVENT_CRIT_NORMAL',				2);
define ('EVENT_CRIT_MINOR',					5);
define ('EVENT_CRIT_WARNING',				3);
define ('EVENT_CRIT_MAJOR',					6);
define ('EVENT_CRIT_CRITICAL',				4);
define ('EVENT_CRIT_WARNING_OR_CRITICAL',	34);
define ('EVENT_CRIT_NOT_NORMAL',			20);
define ('EVENT_CRIT_OR_NORMAL',				21);

/* Id Module (more use in component)*/
define ('MODULE_DATA',			1);
define ('MODULE_NETWORK',		2);
define ('MODULE_SNMP',			2);
define ('MODULE_PLUGIN',		4);
define ('MODULE_PREDICTION',	5);
define ('MODULE_WMI',			6);
define ('MODULE_WEB',			7);

/* Type of Modules of Prediction */
define ('MODULE_PREDICTION_SERVICE',	2);
define ('MODULE_PREDICTION_SYNTHETIC',	3);
define ('MODULE_PREDICTION_NETFLOW',	4);

/* SNMP CONSTANTS */
define('SNMP_DIR_MIBS',		"attachment/mibs");

define('SNMP_TRAP_TYPE_NONE',					-1);
define('SNMP_TRAP_TYPE_COLD_START',				0);
define('SNMP_TRAP_TYPE_WARM_START',				1);
define('SNMP_TRAP_TYPE_LINK_DOWN',				2);
define('SNMP_TRAP_TYPE_LINK_UP',				3);
define('SNMP_TRAP_TYPE_AUTHENTICATION_FAILURE',	4);
define('SNMP_TRAP_TYPE_OTHER',					5);

/* PASSWORD POLICIES */
define('PASSSWORD_POLICIES_OK',				0);
define('PASSSWORD_POLICIES_FIRST_CHANGE',	1);
define('PASSSWORD_POLICIES_EXPIRED',		2);

/* SERVER TYPES */
define('SERVER_TYPE_DATA',					0);
define('SERVER_TYPE_NETWORK',				1);
define('SERVER_TYPE_SNMP',					2);
define('SERVER_TYPE_RECON',					3);
define('SERVER_TYPE_PLUGIN',				4);
define('SERVER_TYPE_PREDICTION',			5);
define('SERVER_TYPE_WMI',					6);
define('SERVER_TYPE_EXPORT',				7);
define('SERVER_TYPE_INVENTORY',				8);
define('SERVER_TYPE_WEB',					9);
define('SERVER_TYPE_EVENT',					10);
define('SERVER_TYPE_ENTERPRISE_ICMP',		11);
define('SERVER_TYPE_ENTERPRISE_SNMP',		12);
define('SERVER_TYPE_ENTERPRISE_SATELLITE',	13);

/* REPORTS */
define('REPORT_TOP_N_MAX',	1);
define('REPORT_TOP_N_MIN',	2);
define('REPORT_TOP_N_AVG',	0);

define('REPORT_TOP_N_ONLY_GRAPHS',		2);
define('REPORT_TOP_N_SHOW_TABLE_GRAPS',	1);
define('REPORT_TOP_N_ONLY_TABLE',		0);

define('REPORT_EXCEPTION_CONDITION_EVERYTHING',	0);
define('REPORT_EXCEPTION_CONDITION_GE',			1);
define('REPORT_EXCEPTION_CONDITION_LE',			5);
define('REPORT_EXCEPTION_CONDITION_L',			2);
define('REPORT_EXCEPTION_CONDITION_G',			6);
define('REPORT_EXCEPTION_CONDITION_E',			7);
define('REPORT_EXCEPTION_CONDITION_NE',			8);
define('REPORT_EXCEPTION_CONDITION_OK',			3);
define('REPORT_EXCEPTION_CONDITION_NOT_OK',		4);

define('REPORT_ITEM_ORDER_BY_AGENT_NAME',	3);
define('REPORT_ITEM_ORDER_BY_ASCENDING',	2);
define('REPORT_ITEM_ORDER_BY_DESCENDING',	1);
define('REPORT_ITEM_ORDER_BY_UNSORT',		0);

define('REPORT_OLD_TYPE_SIMPLE_GRAPH',		1);
define('REPORT_OLD_TYPE_CUSTOM_GRAPH',		2);
define('REPORT_OLD_TYPE_SLA',				3);
define('REPORT_OLD_TYPE_MONITOR_REPORT',	6);
define('REPORT_OLD_TYPE_AVG_VALUE',			7);
define('REPORT_OLD_TYPE_MAX_VALUE',			8);
define('REPORT_OLD_TYPE_MIN_VALUE',			9);
define('REPORT_OLD_TYPE_SUMATORY',			10);

define('REPORT_GENERAL_NOT_GROUP_BY_AGENT',	0);
define('REPORT_GENERAL_GROUP_BY_AGENT',		1);

define('REPORTING_CUSTOM_GRAPH_LEGEND_EACH_MODULE_VERTICAL_SIZE',	15);

/* POLICIES */

define("POLICY_UPDATED",			0);
define("POLICY_PENDING_DATABASE",	1);
define("POLICY_PENDING_ALL",		2);

define("STATUS_IN_QUEUE_OUT",		0);
define("STATUS_IN_QUEUE_IN",		1);
define("STATUS_IN_QUEUE_APPLYING",	2);

define("MODULE_UNLINKED",		0);
define("MODULE_LINKED",			1);
define("MODULE_PENDING_UNLINK",	10);
define("MODULE_PENDING_LINK",	11);

/* EVENTS */
define("EVENTS_GOING_UNKNOWN" ,				'going_unknown');
define("EVENTS_UNKNOWN",					'unknown');
define("EVENTS_ALERT_FIRED",				'alert_fired');
define("EVENTS_ALERT_RECOVERED",			'alert_recovered');
define("EVENTS_ALERT_CEASED",				'alert_ceased');
define("EVENTS_ALERT_MANUAL_VALIDATION",	'alert_manual_validation');
define("EVENTS_RECON_HOST_DETECTED",		'recon_host_detected');
define("EVENTS_SYSTEM",						'system');
define("EVENTS_ERROR",						'error');
define("EVENTS_NEW_AGENT",					'new_agent');
define("EVENTS_GOING_UP_WARNING",			'going_up_warning');
define("EVENTS_GOING_UP_CRITICAL",			'going_up_critical');
define("EVENTS_GOING_DOWN_WARNING",			'going_down_warning');
define("EVENTS_GOING_DOWN_NORMAL",			'going_down_normal');
define("EVENTS_GOING_DOWN_CRITICAL",		'going_down_critical');
define("EVENTS_GOING_UP_NORMAL",			'going_up_normal');
define("EVENTS_CONFIGURATION_CHANGE",		'configuration_change');

/* CUSTOM GRAPHS */
define("CUSTOM_GRAPH_AREA",			0);
define("CUSTOM_GRAPH_STACKED_AREA",	1);
define("CUSTOM_GRAPH_LINE",			2);
define("CUSTOM_GRAPH_STACKED_LINE",	3);
define("CUSTOM_GRAPH_BULLET_CHART",	4);
define("CUSTOM_GRAPH_GAUGE",		5);
define("CUSTOM_GRAPH_HBARS",		6);
define("CUSTOM_GRAPH_VBARS",		7);
define("CUSTOM_GRAPH_PIE",			8);
define("CUSTOM_GRAPH_BULLET_CHART_THRESHOLD",			9);

/* COLLECTIONS */
define("COLLECTION_PENDING_APPLY",			0);
define("COLLECTION_CORRECT",				1);
define("COLLECTION_ERROR_LOST_DIRECTORY",	2);
define("COLLECTION_UNSAVED",				3);

/* PAGINATION */
define("PAGINATION_BLOCKS_LIMIT",	15);

/* CHARTS */
define("CHART_DEFAULT_WIDTH",	150);
define("CHART_DEFAULT_HEIGHT",	110);

define("CHART_DEFAULT_ALPHA", 50);

/* Statwin */
define("STATWIN_DEFAULT_CHART_WIDTH",	555);
define("STATWIN_DEFAULT_CHART_HEIGHT",	245);

/* Dashboard */
define("DASHBOARD_DEFAULT_COUNT_CELLS",	4);

define("OPTION_TEXT",					1);
define("OPTION_SINGLE_SELECT",			2);
define("OPTION_MULTIPLE_SELECT",		3);
define("OPTION_BOOLEAN",				4);
define("OPTION_TEXTAREA",				5);
define("OPTION_TREE_GROUP_SELECT",		6);
define("OPTION_SINGLE_SELECT_TIME",		7);
define("OPTION_CUSTOM_INPUT",			8);
define("OPTION_AGENT_AUTOCOMPLETE",		9);
define("OPTION_SELECT_MULTISELECTION",	10);

/* SAML attributes constants */

define("SAML_ROLE_AND_TAG", "eduPersonEntitlement");
define("SAML_USER_DESC", "commonName");
define("SAML_ID_USER_IN_PANDORA", "eduPersonTargetedId");
define("SAML_GROUP_IN_PANDORA", "schacHomeOrganization");
define("SAML_MAIL_IN_PANDORA", "mail");
define("SAML_DEFAULT_PROFILES_AND_TAGS_FORM", "urn:mace:rediris.es:entitlement:monitoring:");

/* Other constants */
define("STATUS_OK", 0);
define("STATUS_ERROR", 1);

/* Maps (new networkmaps and  new visualmaps) */
define("MAP_TYPE_NETWORKMAP",	0);
define("MAP_TYPE_VISUALMAP",	1);

define("MAP_REFRESH_TIME",	SECONDS_5MINUTES);

define("MAP_SUBTYPE_TOPOLOGY",			0);
define("MAP_SUBTYPE_POLICIES",			1);
define("MAP_SUBTYPE_GROUPS",			2);
define("MAP_SUBTYPE_RADIAL_DYNAMIC",	3);

define("MAP_GENERATION_CIRCULAR",	0);
define("MAP_GENERATION_PLANO",		1);
define("MAP_GENERATION_RADIAL",		2);
define("MAP_GENERATION_SPRING1",	3);
define("MAP_GENERATION_SPRING2",	4);

define("MAP_SOURCE_GROUP",		0);
define("MAP_SOURCE_IP_MASK", 	1);

define("NETWORKMAP_DEFAULT_WIDTH", 800);
define("NETWORKMAP_DEFAULT_HEIGHT", 800);

/* Background options */
define("CENTER",		0);
define("MOSAIC",		1);
define("STRECH",		2);
define("FIT_WIDTH",		3);
define("FIT_HEIGH",		4);

/* Items of maps */
define("ITEM_TYPE_AGENT_NETWORKMAP",		0);
define("ITEM_TYPE_MODULE_NETWORKMAP",		1);
define("ITEM_TYPE_EDGE_NETWORKMAP",			2);
define("ITEM_TYPE_FICTIONAL_NODE",			3);
define("ITEM_TYPE_MODULEGROUP_NETWORKMAP",	4);
define("ITEM_TYPE_GROUP_NETWORKMAP",		5);
define("ITEM_TYPE_POLICY_NETWORKMAP",		6);

/* Another constants new networkmap */
define("DEFAULT_NODE_WIDTH", 30);
define("DEFAULT_NODE_HEIGHT", 30);
define("DEFAULT_NODE_SHAPE", "circle");
define("DEFAULT_NODE_COLOR", COL_NOTINIT);
define("DEFAULT_NODE_IMAGE", "images/networkmap/unknown.png");

define("NODE_IMAGE_PADDING", 5);
?>
