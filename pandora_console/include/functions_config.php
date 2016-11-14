<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Config
 */

/**
 * Creates a single config value in the database.
 * 
 * @param string Config token to create.
 * @param string Value to set.
 *
 * @return bool Config id if success. False on failure.
 */
function config_create_value ($token, $value) {
	return db_process_sql_insert ('tconfig',
		array ('value' => $value,
			'token' => $token));
}

/**
 * Update a single config value in the database.
 * 
 * If the config token doesn't exists, it's created.
 * 
 * @param string Config token to update.
 * @param string New value to set.
 *
 * @return bool True if success. False on failure.
 */
function config_update_value ($token, $value) {
	global $config;
	
	// Include functions_io to can call __() function
	include_once($config['homedir'] . '/include/functions_io.php');
	
	if ($token == 'list_ACL_IPs_for_API') {
		$value = str_replace(array("\r\n", "\r", "\n"), ";",
			io_safe_output($value));
	}
	
	if ($token == 'default_assign_tags') {
		$value = ($value);
	}
	
	if (!isset ($config[$token])) {
		$config[$token] = $value;
		return (bool) config_create_value ($token, io_safe_input($value));
	}
	
	/* If it has not changed */
	if ($config[$token] == $value)
		return true;
	
	$config[$token] = $value;
	$value = io_safe_output($value);
	
	$result = db_process_sql_update ('tconfig', 
		array ('value' => io_safe_input($value)),
		array ('token' => $token));
	
	if ($result === 0)
		return true;
	else
		return (bool) $result;
}

/**
 * Updates all config values in case setup page was invoked 
 */
function config_update_config () {
	global $config;
	
	// Include functions_io to can call __() function
	include_once($config['homedir'] . '/include/functions_io.php');
	
	/* If user is not even log it, don't try this */
	if (! isset ($config['id_user'])) {
		$config['error_config_update_config'] = array();
		$config['error_config_update_config']['correct'] = false;
		$config['error_config_update_config']['message'] = __('Failed updated: User did not login.');
		
		return false;
	}
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		$config['error_config_update_config'] = array();
		$config['error_config_update_config']['correct'] = false;
		$config['error_config_update_config']['message'] = __('Failed updated: User is not admin.');
		
		return false;
	}
	
	$update_config = (bool) get_parameter ('update_config');
	
	if ($update_config) {
		db_pandora_audit("Setup", "Setup has changed");
	}
	else {
		//Do none
		
		return false;
	}
	
	$error_update = array();
	
	$sec2 = get_parameter_get('sec2');
	switch ($sec2) {
		case 'godmode/setup/setup':
			$section_setup = get_parameter ('section');
			//////// MAIN SETUP
			// Setup now is divided in different tabs
			switch ($section_setup) {
				case 'general':
					if (!config_update_value ('language', (string) get_parameter ('language')))
						$error_update[] = __('Language code for Pandora');
					if (!config_update_value ('remote_config', (string) get_parameter ('remote_config')))
						$error_update[] = __('Remote config directory');
					if (!config_update_value ('loginhash_pwd', io_input_password((string) get_parameter ('loginhash_pwd'))))
						$error_update[] = __('Auto login (hash) password');
					
					if (!config_update_value ('timesource', (string) get_parameter ('timesource')))
						$error_update[] = __('Time source');
					if (!config_update_value ('autoupdate', (bool) get_parameter ('autoupdate')))
						$error_update[] = __('Automatic check for updates');
					if (!config_update_value ('cert_path', (bool) get_parameter ('cert_path')))
						$error_update[] = __('SSL cert path');
					if (!config_update_value ('https', (bool) get_parameter ('https')))
						$error_update[] = __('Enforce https');
					if (!config_update_value ('use_cert', (bool) get_parameter ('use_cert')))
						$error_update[] = __('Use cert.');
					if (!config_update_value ('attachment_store', (string) get_parameter ('attachment_store')))
						$error_update[] = __('Attachment store');
					if (!config_update_value ('list_ACL_IPs_for_API', (string) get_parameter('list_ACL_IPs_for_API')))
						$error_update[] = __('IP list with API access');
					if (!config_update_value ('api_password', io_input_password(get_parameter('api_password'))))
						$error_update[] = __('API password');
					if (!config_update_value ('activate_gis', (bool) get_parameter ('activate_gis')))
						$error_update[] = __('Enable GIS features in Pandora Console');
					if (!config_update_value ('integria_enabled', get_parameter ('integria_enabled')))
						$error_update[] = __('Enable Integria incidents in Pandora Console');
					if (!config_update_value ('integria_inventory', get_parameter ('integria_inventory')))
						$error_update[] = __('Integria inventory');
					if (!config_update_value ('integria_api_password', io_input_password(get_parameter ('integria_api_password'))))
						$error_update[] = __('Integria API password');
					if (!config_update_value ('integria_url', get_parameter ('integria_url')))
						$error_update[] = __('Integria URL');
					if (!config_update_value ('activate_netflow', (bool) get_parameter ('activate_netflow')))
						$error_update[] = __('Enable Netflow');
					$timezone = (string) get_parameter ('timezone');
					if ($timezone != "") {
						if (!config_update_value ('timezone', $timezone))
							$error_update[] = __('Timezone setup');
					}
					if (!config_update_value ('sound_alert', get_parameter('sound_alert')))
						$error_update[] = __('Sound for Alert fired');
					if (!config_update_value ('sound_critical', get_parameter('sound_critical')))
						$error_update[] = __('Sound for Monitor critical');
					if (!config_update_value ('sound_warning', get_parameter('sound_warning')))
						$error_update[] = __('Sound for Monitor warning');
					# Update of Pandora FMS license 
					$update_manager_installed = db_get_value('value', 'tconfig', 'token', 'update_manager_installed');
					
					if ($update_manager_installed == 1) {
						$license_info_key = get_parameter('license_info_key', '');
						if (!empty($license_info_key)) {
							$values = array(db_escape_key_identifier('value') => $license_info_key);
							$where = array(db_escape_key_identifier('key') => 'customer_key');
							$update_manage_settings_result = db_process_sql_update('tupdate_settings', $values, $where);
							if ($update_manage_settings_result === false)
								$error_update[] = __('License information');
						}
					}
					if (!config_update_value ('public_url', get_parameter('public_url')))
						$error_update[] = __('Public URL');
					if (!config_update_value ('referer_security', get_parameter('referer_security')))
						$error_update[] = __('Referer security');
					if (!config_update_value ('event_storm_protection', get_parameter('event_storm_protection')))
						$error_update[] = __('Event storm protection');
					if (!config_update_value ('command_snapshot', get_parameter('command_snapshot')))
						$error_update[] = __('Command Snapshot');
					if (!config_update_value ('server_log_dir', get_parameter('server_log_dir')))
						$error_update[] = __('Server logs directory');
					if (!config_update_value ('tutorial_mode', get_parameter('tutorial_mode')))
						$error_update[] = __('Tutorial mode');
					if (!config_update_value ('past_planned_downtimes', get_parameter('past_planned_downtimes')))
						$error_update[] = __('Allow create planned downtimes in the past');
					if (!config_update_value ('limit_parameters_massive', get_parameter('limit_parameters_massive')))
						$error_update[] = __('Limit parameters massive');
					if (!config_update_value ('identification_reminder', get_parameter('identification_reminder')))
						$error_update[] = __('Identification_reminder');
					if (!config_update_value ('include_agents', (bool)get_parameter('include_agents')))
						$error_update[] = __('Include_agents');
					if (!config_update_value ('auditdir', get_parameter('auditdir')))
						$error_update[] = __('Audit log directory');
					break;
				case 'enterprise':
					if (isset($config['enterprise_installed']) && $config['enterprise_installed'] == 1) {
						if (!config_update_value ('trap2agent', (string) get_parameter ('trap2agent')))
							$error_update[] = __('Forward SNMP traps to agent (if exist)');
						if (!config_update_value ('acl_enterprise', get_parameter ('acl_enterprise')))
							$error_update[] = __('Use Enterprise ACL System');
						if (!config_update_value ('metaconsole', get_parameter ('metaconsole')))
							$error_update[] = __('Activate Metaconsole');
						if (!config_update_value ('collection_max_size', get_parameter('collection_max_size')))
							$error_update[] = __('Size of collection');
						if (!config_update_value ('event_replication', (int)get_parameter('event_replication')))
							$error_update[] = __('Events replication');
						if ((int)get_parameter('event_replication') == 1) {
							if (!config_update_value ('replication_interval', (int)get_parameter('replication_interval')))
								$error_update[] = __('Replication interval');
							if (!config_update_value ('replication_limit', (int)get_parameter('replication_limit')))
								$error_update[] = __('Replication limit');
							if (!config_update_value ('replication_mode', (string)get_parameter('replication_mode')))
								$error_update[] = __('Replication mode');
							if (!config_update_value ('show_events_in_local', (string)get_parameter('show_events_in_local')))
								$error_update[] = __('Show events list in local console (read only)');
						}
						if (!config_update_value ('replication_dbengine', (string)get_parameter('replication_dbengine')))
							$error_update[] = __('Replication DB engine');
						if (!config_update_value ('replication_dbhost', (string)get_parameter('replication_dbhost')))
							$error_update[] = __('Replication DB host');
						if (!config_update_value ('replication_dbname', (string)get_parameter('replication_dbname')))
							$error_update[] = __('Replication DB database');
						if (!config_update_value ('replication_dbuser', (string)get_parameter('replication_dbuser')))
							$error_update[] = __('Replication DB user');
						if (!config_update_value ('replication_dbpass', io_input_password((string)get_parameter('replication_dbpass'))))
							$error_update[] = __('Replication DB password');
						if (!config_update_value ('replication_dbport', (string)get_parameter('replication_dbport')))
							$error_update[] = __('Replication DB port');
						if (!config_update_value ('metaconsole_agent_cache', (int)get_parameter('metaconsole_agent_cache')))
							$error_update[] = __('Metaconsole agent cache');
						if (!config_update_value ('log_collector', (bool)get_parameter('log_collector')))
							$error_update[] = __('Activate Log Collector');
						
						$inventory_changes_blacklist = get_parameter('inventory_changes_blacklist', array());
						if (!config_update_value ('inventory_changes_blacklist', implode(',',$inventory_changes_blacklist)))
							$error_update[] = __('Inventory changes blacklist');
						
					}
					break;
				case 'pass':
					if (isset($config['enterprise_installed']) && $config['enterprise_installed'] == 1) {
						if (!config_update_value ('enable_pass_policy', get_parameter('enable_pass_policy')))
							$error_update[] = __('Enable password policy');
						
						if (!config_update_value ('pass_size', get_parameter('pass_size')))
							$error_update[] = __('Min. size password');
						if (!config_update_value ('pass_expire', get_parameter('pass_expire')))
							$error_update[] = __('Password expiration');
						if (!config_update_value ('first_login',  get_parameter('first_login')))
							$error_update[] = __('Force change password on first login');
						if (!config_update_value ('mins_fail_pass', get_parameter('mins_fail_pass')))
							$error_update[] = __('User blocked if login fails');
						if (!config_update_value ('number_attempts', get_parameter('number_attempts')))
							$error_update[] = __('Number of failed login attempts');
						if (!config_update_value ('pass_needs_numbers', get_parameter('pass_needs_numbers')))
							$error_update[] = __('Password must have numbers');
						if (!config_update_value ('pass_needs_symbols', get_parameter('pass_needs_symbols')))
							$error_update[] = __('Password must have symbols');
						if (!config_update_value ('enable_pass_policy_admin', get_parameter('enable_pass_policy_admin')))
							$error_update[] = __('Apply password policy to admin users');
						if (!config_update_value ('enable_pass_history', get_parameter('enable_pass_history')))
							$error_update[] = __('Enable password history');
						if (!config_update_value ('compare_pass', get_parameter('compare_pass')))
							$error_update[] = __('Compare previous password');
					}
					break;
				case 'auth':
					//////// AUTHENTICATION SETUP
					if (!config_update_value ('auth', get_parameter ('auth')))
						$error_update[] = __('Authentication method');
					if (!config_update_value ('autocreate_remote_users', get_parameter ('autocreate_remote_users')))
						$error_update[] = __('Autocreate remote users');
					if (!config_update_value ('default_remote_profile', get_parameter ('default_remote_profile')))
						$error_update[] = __('Autocreate profile');
					if (!config_update_value ('default_remote_group', get_parameter ('default_remote_group')))
						$error_update[] = __('Autocreate profile group');
					if (!config_update_value ('default_assign_tags', implode(",",get_parameter ('default_assign_tags'))))
						$error_update[] = __('Autocreate profile tags');
					if (!config_update_value ('autocreate_blacklist', get_parameter ('autocreate_blacklist')))
						$error_update[] = __('Autocreate blacklist');
					
					if (!config_update_value ('ad_server', get_parameter ('ad_server')))
						$error_update[] = __('Active directory server');
					if (!config_update_value ('ad_port', get_parameter ('ad_port')))
						$error_update[] = __('Active directory port');
					if (!config_update_value ('ad_start_tls', get_parameter ('ad_start_tls')))
						$error_update[] = __('Start TLS');
					if (!config_update_value ('ad_advanced_config', get_parameter ('ad_advanced_config')))
						$error_update[] = __('Advance Config AD');
					if (!config_update_value ('ad_domain', get_parameter ('ad_domain')))
						$error_update[] = __('Domain');
					if (!config_update_value ('ad_adv_perms', get_parameter ('ad_adv_perms')))
						$error_update[] = __('Advanced Permisions AD');
					
					if (!config_update_value ('ldap_server', get_parameter ('ldap_server')))
						$error_update[] = __('LDAP server');
					if (!config_update_value ('ldap_port', get_parameter ('ldap_port')))
						$error_update[] = __('LDAP port');
					if (!config_update_value ('ldap_version', get_parameter ('ldap_version')))
						$error_update[] = __('LDAP version');
					if (!config_update_value ('ldap_start_tls', get_parameter ('ldap_start_tls')))
						$error_update[] = __('Start TLS');
					if (!config_update_value ('ldap_base_dn', get_parameter ('ldap_base_dn')))
						$error_update[] = __('Base DN');
					if (!config_update_value ('ldap_login_attr', get_parameter ('ldap_login_attr')))
						$error_update[] = __('Login attribute');
					if (!config_update_value ('fallback_local_auth', get_parameter ('fallback_local_auth')))
						$error_update[] = __('Fallback to local authentication');
					
					if (!config_update_value ('rpandora_server', get_parameter ('rpandora_server')))
						$error_update[] = __('MySQL host');
					if (!config_update_value ('rpandora_port', get_parameter ('rpandora_port')))
						$error_update[] = __('MySQL port');
					if (!config_update_value ('rpandora_dbname', get_parameter ('rpandora_dbname')))
						$error_update[] = __('Database name');
					if (!config_update_value ('rpandora_user', get_parameter ('rpandora_user')))
						$error_update[] = __('User');
					if (!config_update_value ('rpandora_pass', io_input_password(get_parameter ('rpandora_pass'))))
						$error_update[] = __('Password');
					
					if (!config_update_value ('rbabel_server', get_parameter ('rbabel_server')))
						$error_update[] = __('Babel Enterprise host');
					if (!config_update_value ('rbabel_port', get_parameter ('rbabel_port')))
						$error_update[] = __('MySQL port');
					if (!config_update_value ('rbabel_dbname', get_parameter ('rbabel_dbname')))
						$error_update[] = __('Database name');
					if (!config_update_value ('rbabel_user', get_parameter ('rbabel_user')))
						$error_update[] = __('User');
					if (!config_update_value ('rbabel_pass', io_input_password(get_parameter ('rbabel_pass'))))
						$error_update[] = __('Password');
					if (!config_update_value ('rintegria_server', get_parameter ('rintegria_server')))
						$error_update[] = __('Integria host');
					if (!config_update_value ('rintegria_port', get_parameter ('rintegria_port')))
						$error_update[] = __('MySQL port');
					if (!config_update_value ('rintegria_dbname', get_parameter ('rintegria_dbname')))
						$error_update[] = __('Database name');
					if (!config_update_value ('rintegria_user', get_parameter ('rintegria_user')))
						$error_update[] = __('User');
					if (!config_update_value ('rintegria_pass', io_input_password(get_parameter ('rintegria_pass'))))
						$error_update[] = __('Password');
					if (!config_update_value ('saml_path', get_parameter ('saml_path')))
						$error_update[] = __('Saml path');
					if (!config_update_value ('double_auth_enabled', get_parameter ('double_auth_enabled')))
						$error_update[] = __('Double authentication');
					if (!config_update_value ('session_timeout', get_parameter ('session_timeout')))
						$error_update[] = __('Session timeout');
					/////////////
					break;
				case 'perf':
					//////// PERFORMANCE SETUP
					if (!config_update_value ('event_purge', get_parameter ('event_purge')))
						$error_update[] = 
					$check_metaconsole_events_history = get_parameter ('metaconsole_events_history', -1);
					if ($check_metaconsole_events_history != -1)
						if (!config_update_value ('metaconsole_events_history', get_parameter ('metaconsole_events_history')))
							$error_update[] = __('Max. days before delete events');
					if (!config_update_value ('trap_purge', get_parameter ('trap_purge')))
						$error_update[] = __('Max. days before delete traps');
					if (!config_update_value ('string_purge', get_parameter ('string_purge')))
						$error_update[] = __('Max. days before delete string data');
					if (!config_update_value ('audit_purge', get_parameter ('audit_purge')))
						$error_update[] = __('Max. days before delete audit events');
					if (!config_update_value ('gis_purge', get_parameter ('gis_purge')))
						$error_update[] = __('Max. days before delete GIS data');
					if (!config_update_value ('days_purge', (int) get_parameter ('days_purge')))
						$error_update[] = __('Max. days before purge');
					if (!config_update_value ('days_delete_unknown', (int) get_parameter ('days_delete_unknown')))
						$error_update[] = __('Max. days before delete unknown modules');
					if (!config_update_value ('days_compact', (int) get_parameter ('days_compact')))
						$error_update[] = __('Max. days before compact data');
					if (!config_update_value ('report_limit', (int) get_parameter ('report_limit')))
						$error_update[] = __('Item limit for realtime reports)');
					if (!config_update_value ('step_compact', (int) get_parameter ('step_compact')))
						$error_update[] = __('Compact interpolation in hours (1 Fine-20 bad)');
					if (!config_update_value ('event_view_hr', (int) get_parameter ('event_view_hr')))
						$error_update[] = __('Default hours for event view');
					if (!config_update_value ('realtimestats', get_parameter ('realtimestats')))
						$error_update[] = __('Use realtime statistics');
					if (!config_update_value ('stats_interval', get_parameter ('stats_interval')))
						$error_update[] = __('Batch statistics period (secs)');
					if (!config_update_value ('agentaccess', (int) get_parameter ('agentaccess')))
						$error_update[] = __('Use agent access graph');
					if (!config_update_value ('num_files_attachment', (int) get_parameter ('num_files_attachment')))
						$error_update[] = __('Max. recommended number of files in attachment directory');
					if (!config_update_value ('delete_notinit', get_parameter ('delete_notinit')))
						$error_update[] = __('Delete not init modules');
					/////////////
					break;
					
				case 'vis':
					//////// VISUAL STYLES SETUP
					if (!config_update_value ('date_format', (string) get_parameter ('date_format')))
						$error_update[] = __('Date format string');
					if (!config_update_value ('prominent_time', (string) get_parameter ('prominent_time')))
						$error_update[] = __('Timestamp or time comparation');
					if (!config_update_value ('graph_color1', (string) get_parameter ('graph_color1')))
						$error_update[] = __('Graph color (min)');
					if (!config_update_value ('graph_color2', (string) get_parameter ('graph_color2')))
						$error_update[] = __('Graph color (avg)');
					if (!config_update_value ('graph_color3', (string) get_parameter ('graph_color3')))
						$error_update[] = __('Graph color (max)');
					if (!config_update_value ('graph_color4', (string) get_parameter ('graph_color4')))
						$error_update[] = __('Graph color #4');
					if (!config_update_value ('graph_color5', (string) get_parameter ('graph_color5')))
						$error_update[] = __('Graph color #5');
					if (!config_update_value ('graph_color6', (string) get_parameter ('graph_color6')))
						$error_update[] = __('Graph color #6');
					if (!config_update_value ('graph_color7', (string) get_parameter ('graph_color7')))
						$error_update[] = __('Graph color #7');
					if (!config_update_value ('graph_color8', (string) get_parameter ('graph_color8')))
						$error_update[] = __('Graph color #8');
					if (!config_update_value ('graph_color9', (string) get_parameter ('graph_color9')))
						$error_update[] = __('Graph color #9');
					if (!config_update_value ('graph_color10', (string) get_parameter ('graph_color10')))
						$error_update[] = __('Graph color #10');
					if (!config_update_value ('graph_res', (int) get_parameter ('graph_res')))
						$error_update[] = __('Graphic resolution (1-low, 5-high)');
					if (!config_update_value ('interface_unit', (string) get_parameter ('interface_unit', __('Bytes') )))
						$error_update[] = __('Value to interface graphics');
					if (!config_update_value ('graph_precision', (string) get_parameter ('graph_precision', 1)))
						$error_update[] = __('Data precision for reports');
					$style = (string) get_parameter ('style');
					if ($style != $config['style'])
						$style = substr ($style, 0, strlen ($style) - 4);
					if (!config_update_value ('style', $style))
						$error_update[] = __('Style template');
					if (!config_update_value ('block_size', (int) get_parameter ('block_size')))
						$error_update[] = __('Block size for pagination');
					if (!config_update_value ('round_corner', (bool) get_parameter ('round_corner')))
						$error_update[] = __('Use round corners');
					if (!config_update_value ('show_qr_code_header', (bool) get_parameter ('show_qr_code_header')))
						$error_update[] = __('Show QR code header');
					if (!config_update_value ('status_images_set', (string) get_parameter ('status_images_set')))
						$error_update[] = __('Status icon set');
					if (!config_update_value ('fontpath', (string) get_parameter ('fontpath')))
						$error_update[] = __('Font path');
					if (!config_update_value ('font_size', get_parameter('font_size')))
						$error_update[] = __('Font size');
					if (!config_update_value ('flash_charts', (bool) get_parameter ('flash_charts')))
						$error_update[] = __('Interactive charts');
					
						
						if (!config_update_value ('custom_logo', (string) get_parameter ('custom_logo')))
							$error_update[] = __('Custom logo');
							if (!config_update_value ('custom_logo_login', (string) get_parameter ('custom_logo_login')))
							$error_update[] = __('Custom logo login');
	
						
						
					if (!config_update_value ('login_background', (string) get_parameter ('login_background')))
						$error_update[] = __('Login background');
					if (!config_update_value ('vc_refr', get_parameter('vc_refr')))
						$error_update[] = __('Default interval for refresh on Visual Console');
					if (!config_update_value ('vc_line_thickness', (int) get_parameter('vc_line_thickness')))
						$error_update[] = __('Default line thickness for the Visual Console');
					if (!config_update_value ('agent_size_text_small', get_parameter('agent_size_text_small')))
						$error_update[] = __('Agent size text');
					if (!config_update_value ('agent_size_text_medium', get_parameter('agent_size_text_medium')))
						$error_update[] = __('Agent size text');
					if (!config_update_value ('module_size_text_small', get_parameter('module_size_text_small')))
						$error_update[] = __('Module size text');
					if (!config_update_value ('module_size_text_medium', get_parameter('module_size_text_medium')))
						$error_update[] = __('Description size text');
					if (!config_update_value ('description_size_text', get_parameter('description_size_text')))
						$error_update[] = __('Description size text');
					if (!config_update_value ('item_title_size_text', get_parameter('item_title_size_text')))
						$error_update[] = __('Item title size text');
					if (!config_update_value ('gis_label', get_parameter ('gis_label')))
						$error_update[] = __('GIS Labels');
					if (!config_update_value ('gis_default_icon', get_parameter ('gis_default_icon')))
						$error_update[] = __('Default icon in GIS');
					if (!config_update_value ('autohidden_menu', get_parameter('autohidden_menu')))
						$error_update[] = __('Autohidden menu');
					if (!config_update_value ('fixed_graph', get_parameter('fixed_graph')))
							$error_update[] = __('Fixed graph');
					if (!config_update_value ('fixed_header', get_parameter('fixed_header')))
						$error_update[] = __('Fixed header');
					if (!config_update_value ('fixed_menu', get_parameter('fixed_menu')))
						$error_update[] = __('Fixed menu');
					if (!config_update_value ('paginate_module', get_parameter('paginate_module')))
						$error_update[] = __('Paginate module');
					if (!config_update_value ('graphviz_bin_dir', get_parameter('graphviz_bin_dir')))
						$error_update[] = __('Custom graphviz directory');
					if (!config_update_value ('networkmap_max_width', get_parameter('networkmap_max_width')))
						$error_update[] = __('Networkmap max width');
					if (!config_update_value ('short_module_graph_data', get_parameter('short_module_graph_data')))
						$error_update[] = __('Shortened module graph data');
					if (!config_update_value ('show_group_name', get_parameter('show_group_name')))
						$error_update[] = __('Show the group name instead the group icon.');
					if (!config_update_value ('custom_graph_width', (int) get_parameter('custom_graph_width', 1)))
						$error_update[] = __('Default line thickness for the Custom Graph.');
					if (!config_update_value ('type_module_charts', (string) get_parameter('type_module_charts', 'area')))
						$error_update[] = __('Default type of module charts.');
					if (!config_update_value ('only_average', (bool) get_parameter('only_average', false)))
						$error_update[] = __('Default show only average or min and max');
					if (!config_update_value ('render_proc', (bool) get_parameter('render_proc', false)))
						$error_update[] = __('Display data of proc modules in other format');
					if (!config_update_value ('render_proc_ok', (string) get_parameter('render_proc_ok', __('Ok') )))
						$error_update[] = __('Display text proc modules have state is ok');
					if (!config_update_value ('render_proc_fail', (string) get_parameter('render_proc_fail', __('Fail') )))
						$error_update[] = __('Display text when proc modules have state critical');
					//Daniel maya 02/06/2016 Display menu with click --INI
					if (!config_update_value ('click_display', (bool) get_parameter('click_display', false)))
						$error_update[] = __('Display lateral menus with left click');
					//Daniel maya 02/06/2016 Display menu with click --END


					//--------------------------------------------------
					// CUSTOM VALUES POST PROCESS
					//--------------------------------------------------
					$custom_value =  get_parameter('custom_value');
					$custom_text = get_parameter('custom_text');
					$custom_value_add = (bool)get_parameter('custom_value_add', 0);
					$custom_value_to_delete = get_parameter('custom_value_to_delete', 0);
					
					$custom_value = str_replace(',', '.', $custom_value);
					
					if ($custom_value_add) {
						require_once("include/functions_post_process.php");
						
						if (!post_process_add_custom_value(
							$custom_text, (string)$custom_value))
								$error_update[] = __('Add the custom post process');
					}
					
					if ($custom_value_to_delete > 0) {
						require_once("include/functions_post_process.php");
						
						if (!post_process_delete_custom_value($custom_value_to_delete)) {
							$error_update[] = __('Delete the custom post process');
						}
					}
					//--------------------------------------------------
					
					
					
					
					//--------------------------------------------------
					// CUSTOM INTERVAL VALUES
					//--------------------------------------------------
					$interval_values = get_parameter ('interval_values');
					
					// Add new interval value if is provided
					$interval_value = (float) get_parameter ('interval_value', 0);
					
					if ($interval_value > 0) {
						$interval_unit = (int) get_parameter ('interval_unit');
						$new_interval = $interval_value * $interval_unit;
						
						if ($interval_values === '') {
							$interval_values = $new_interval;
						}
						else {
							$interval_values_array = explode(',',$interval_values);
							if(!in_array($new_interval, $interval_values_array)) {
								$interval_values_array[] = $new_interval;
								$interval_values = implode(',',$interval_values_array);
							}
						}
					}
					
					// Delete interval value if is required
					$interval_to_delete = (float) get_parameter('interval_to_delete');
					if ($interval_to_delete > 0) {
						$interval_values_array = explode(',',$interval_values);
						foreach ($interval_values_array as $k => $iva) {
							if ($interval_to_delete == $iva) {
								unset($interval_values_array[$k]);
							}
						}
						$interval_values = implode(',',$interval_values_array);
					}
					
					if (!config_update_value ('interval_values', $interval_values))
						$error_update[] = __('Delete interval');
					//--------------------------------------------------
				
				
				
				
				// Juanma (06/05/2014) New feature: Custom front page for reports  	
				if (!config_update_value ('custom_report_front', get_parameter('custom_report_front')))
					$error_update[] = __('Custom report front');
				
				if (!config_update_value ('custom_report_front_font', get_parameter('custom_report_front_font')))
					$error_update[] = __('Custom report front') . ' - ' . __('Font family');
				
				if (!config_update_value ('custom_report_front_logo', get_parameter('custom_report_front_logo')))
					$error_update[] = __('Custom report front') . ' - ' . __('Custom logo');
				
				if (!config_update_value ('custom_report_front_header', get_parameter('custom_report_front_header')))
					$error_update[] = __('Custom report front') . ' - ' . __('Header');
				
				if (!config_update_value ('custom_report_front_firstpage', get_parameter('custom_report_front_firstpage')))
					$error_update[] = __('Custom report front') . ' - ' . __('First page');				
				
				if (!config_update_value ('custom_report_front_footer', get_parameter('custom_report_front_footer')))
					$error_update[] = __('Custom report front') . ' - ' . __('Footer');				
				
				break;
			case 'net':
				if (!config_update_value ('netflow_path', get_parameter ('netflow_path')))
					$error_update[] = __('Data storage path');
				if (!config_update_value ('netflow_interval', (int)get_parameter ('netflow_interval')))
					$error_update[] = __('Daemon interval');
				if (!config_update_value ('netflow_daemon', get_parameter ('netflow_daemon')))
					$error_update[] = __('Daemon binary path');
				if (!config_update_value ('netflow_nfdump', get_parameter ('netflow_nfdump')))
					$error_update[] = __('Nfdump binary path');
				if (!config_update_value ('netflow_nfexpire', get_parameter ('netflow_nfexpire')))
					$error_update[] = __('Nfexpire binary path');
				if (!config_update_value ('netflow_max_resolution', (int)get_parameter ('netflow_max_resolution')))
					$error_update[] = __('Maximum chart resolution');
				if (!config_update_value ('netflow_disable_custom_lvfilters', get_parameter ('netflow_disable_custom_lvfilters')))
					$error_update[] = __('Disable custom live view filters');
				if (!config_update_value ('netflow_max_lifetime', (int) get_parameter ('netflow_max_lifetime')))
					$error_update[] = __('Netflow max lifetime');
				if (!config_update_value ('netflow_get_ip_hostname', (int) get_parameter ('netflow_get_ip_hostname')))
					$error_update[] = __('Name resolution for IP address');
				break;
			case 'log':
				if (!config_update_value ('log_dir', get_parameter('log_dir')))
					$error_update[] = __('Netflow max lifetime');
				if (!config_update_value ('log_max_lifetime', (int)get_parameter('log_max_lifetime')))
					$error_update[] = __('Log max lifetime');
				break;
			case 'hist_db':
				if (!config_update_value ('history_db_enabled', get_parameter ('history_db_enabled')))
					$error_update[] = __('Enable history database');
				if (!config_update_value ('history_event_enabled', get_parameter ('history_event_enabled')))
					$error_update[] = __('Enable history event');
				if (!config_update_value ('history_db_host', get_parameter ('history_db_host')))
					$error_update[] = __('Host');
				if (!config_update_value ('history_db_port', get_parameter ('history_db_port')))
					$error_update[] = __('Port');
				if (!config_update_value ('history_db_name', get_parameter ('history_db_name')))
					$error_update[] = __('Database name');
				if (!config_update_value ('history_db_user', get_parameter ('history_db_user')))
					$error_update[] = __('Database user');
				if (!config_update_value ('history_db_pass', io_input_password(get_parameter ('history_db_pass'))))
					$error_update[] = __('Database password');
				if (!config_update_value ('history_db_days', get_parameter ('history_db_days')))
					$error_update[] = __('Days');
				if (!config_update_value ('history_event_days', get_parameter ('history_event_days')))
					$error_update[] = __('Event Days');
				if (!config_update_value ('history_db_step', get_parameter ('history_db_step')))
					$error_update[] = __('Step');
				if (!config_update_value ('history_db_delay', get_parameter ('history_db_delay')))
					$error_update[] = __('Delay');
				break;
			case 'ehorus':
				if (!config_update_value('ehorus_enabled', (int) get_parameter('ehorus_enabled', $config['ehorus_enabled'])))
					$error_update[] = __('Enable eHorus');
				if (!config_update_value('ehorus_user', (string) get_parameter('ehorus_user', $config['ehorus_user'])))
					$error_update[] = __('eHorus user');
				if (!config_update_value('ehorus_pass', io_input_password((string) get_parameter('ehorus_pass', $config['ehorus_pass']))))
					$error_update[] = __('eHorus password');
				if (!config_update_value('ehorus_hostname', (string) get_parameter('ehorus_hostname', $config['ehorus_hostname'])))
					$error_update[] = __('eHorus API hostname');
				if (!config_update_value('ehorus_port', (int) get_parameter('ehorus_port', $config['ehorus_port'])))
					$error_update[] = __('eHorus API port');
				if (!config_update_value('ehorus_req_timeout', (int) get_parameter('ehorus_req_timeout', $config['ehorus_req_timeout'])))
					$error_update[] = __('eHorus request timeout');
				if (!config_update_value('ehorus_custom_field', (string) get_parameter('ehorus_custom_field', $config['ehorus_custom_field'])))
					$error_update[] = __('eHorus id custom field');
				break;
			
		}
		
		
	}
	
	if (count($error_update) > 0) {
		$config['error_config_update_config'] = array();
		$config['error_config_update_config']['correct'] = false;
		$values = implode(', ', $error_update);
		$config['error_config_update_config']['message'] = sprintf(__('Failed updated: the next values cannot update: %s'), $values);
	}
	else {
		$config['error_config_update_config'] = array();
		$config['error_config_update_config']['correct'] = true;
	}
	
	enterprise_include_once('include/functions_policies.php');
	$enterprise = enterprise_include_once ('include/functions_skins.php');
	if ($enterprise !== ENTERPRISE_NOT_HOOK) {
		$config['relative_path'] = get_parameter('relative_path', $config['relative_path']);
	}
}

/**
 * Process config variables
 */
function config_process_config () {
	global $config;
	
	$configs = db_get_all_rows_in_table ('tconfig');
	
	if (empty ($configs)) {
		include ($config["homedir"]."/general/error_emptyconfig.php");
		exit;
	}
	
	$is_windows = false;
	if (substr(strtolower(PHP_OS), 0, 3) === 'win') {
		$is_windows = true;
	}
	
	/* Compatibility fix */
	foreach ($configs as $c) {
		$config[$c['token']] = $c['value'];
	}
	
	if (!isset ($config['language'])) {
		config_update_value ('language', 'en');
	}
	
	if (isset ($config['homeurl']) && (strlen($config['homeurl']) > 0)) {
		if ($config['homeurl'][0] != '/') {
			$config['homeurl'] = '/'.$config['homeurl'];
		}
	}
	
	if (!isset ($config['remote_config'])) {
		if ($is_windows)
			$default = 'C:\\PandoraFMS\\Pandora_Server\\data_in';
		else
			$default = '/var/spool/pandora/data_in';
	
		config_update_value ('remote_config', $default);
	}
	
	if (!isset ($config['date_format'])) {
		config_update_value ('date_format', 'F j, Y, g:i a');
	}
	
	if (!isset ($config['event_view_hr'])) {
		config_update_value ('event_view_hr', 8);
	}
	
	if (!isset ($config['report_limit'])) {
		config_update_value ('report_limit', 100);
	}
	
	if (!isset ($config['loginhash_pwd'])) {
		config_update_value ('loginhash_pwd', io_input_password(rand (0, 1000) * rand (0, 1000)."pandorahash"));
	}
	
	if (!isset ($config["trap2agent"])) {
		config_update_value ('trap2agent', 0);
	}
	
	if (!isset ($config["prominent_time"])) {
		// Prominent time tells us what to show prominently when a timestamp is
		// displayed. The comparation (... days ago) or the timestamp (full date)
		config_update_value ('prominent_time', 'comparation');
	}
	
	if (!isset ($config["timesource"])) {
		// Timesource says where time comes from (system or mysql)
		config_update_value ('timesource', 'system');
	}
	
	if (!isset ($config["https"])) {
		// Sets whether or not we want to enforce https. We don't want to go to a
		// potentially unexisting config by default
		config_update_value ('https', false);
	}
	if (!isset ($config["use_cert"])) {
		config_update_value ('use_cert', false);
	}
	
	if (!isset ($config['cert_path'])) {
		// Sets name and path of ssl path for use in application
		config_update_value ('cert_path', '/etc/ssl/certs/pandorafms.pem');
	}
	
	if (!isset ($config["num_files_attachment"])) {
		config_update_value ('num_files_attachment', 100);
	}
	
	if (!isset ($config['status_images_set'])) {
		config_update_value ('status_images_set', 'default');
	}
	
	// Load user session
	if (isset ($_SESSION['id_usuario']))
		$config["id_user"] = $_SESSION["id_usuario"];
	
	if (!isset ($config["round_corner"])) {
		config_update_value ('round_corner', false);
	}
	
	if (!isset ($config["show_qr_code_header"])) {
		config_update_value ('show_qr_code_header', false);
	}
	
	if (!isset ($config["agentaccess"])) {
		config_update_value ('agentaccess', true);
	}
	
	if (!isset ($config["timezone"])) {
		config_update_value ('timezone', "Europe/Berlin");
	}
	
	if (!isset ($config["stats_interval"])) {
		config_update_value ('stats_interval', SECONDS_5MINUTES);
	}
	
	if (!isset ($config["realtimestats"])) {
		config_update_value ('realtimestats', 1);
	}

	if (!isset ($config["delete_notinit"])) {
		config_update_value ('delete_notinit', 0);
	}
	
	if (!isset ($config["event_purge"])) {
		config_update_value ('event_purge', 15);
	}
	
	if (!isset ($config["metaconsole_events_history"])) {
		config_update_value ('metaconsole_events_history', 0);
	}
	
	if (!isset ($config["trap_purge"])) {
		config_update_value ('trap_purge', 7);
	}
	
	if (!isset ($config["string_purge"])) {
		config_update_value ('string_purge', 14);
	}
	
	if (!isset ($config["audit_purge"])) {
		config_update_value ('audit_purge', 30);
	}
	
	if (!isset ($config["acl_enterprise"])) {
		config_update_value ('acl_enterprise', 0);
	}
	
	if (!isset ($config["metaconsole"])) {
		config_update_value ('metaconsole', 0);
	}
	
	if (!isset ($config["gis_purge"])) {
		config_update_value ('gis_purge', 7);
	}
	
	if (!isset ($config["collection_max_size"])) {
		config_update_value ('collection_max_size', 1000000);
	}
	
	if (!isset ($config["event_replication"])) {
		config_update_value ('event_replication', 0);
	}
	
	if (!isset ($config["replication_interval"])) {
		config_update_value ('replication_interval', 120);
	}
	
	if (!isset ($config["replication_limit"])) {
		config_update_value ('replication_limit', 1000);
	}
	
	if (!isset ($config["replication_dbengine"])) {
		config_update_value ('replication_dbengine', 'mysql');
	}
	
	if (!isset ($config["replication_dbhost"])) {
		config_update_value ('replication_dbhost', "");
	}
	
	if (!isset ($config["replication_dbname"])) {
		config_update_value ('replication_dbname', "");
	}
	
	if (!isset ($config["replication_dbuser"])) {
		config_update_value ('replication_dbuser', "");
	}
	
	if (!isset ($config["replication_dbpass"])) {
		config_update_value ('replication_dbpass', "");
	}
	
	if (!isset ($config["replication_dbport"])) {
		config_update_value ('replication_dbport', "");
	}
	
	if (!isset ($config["replication_mode"])) {
		config_update_value ('replication_mode', "only_validated");
	}
	
	if (!isset ($config["metaconsole_agent_cache"])) {
		config_update_value ('metaconsole_agent_cache', 0);
	}

	if (!isset ($config["show_events_in_local"])) {
		config_update_value ('show_events_in_local', 0);
	}
	
	if (!isset ($config["log_collector"])) {
		config_update_value ('log_collector', 0);
	}
	if (!isset ($config["include_agents"])) {
		config_update_value ('include_agents', 0);
	}

	if (!isset ($config["auditdir"])) {
		config_update_value ('auditdir',"/var/www/html/pandora_console");
	}
	if (!isset ($config["log_dir"])) {
		if ($is_windows)
			$default = 'C:\\PandoraFMS\\Pandora_Server\\data_in\\log';
		else
			$default = '/var/spool/pandora/data_in/log';
			
		config_update_value ('log_dir', $default);
	}
	
	if (!isset ($config["log_max_lifetime"])) {
		config_update_value ('log_max_lifetime', 15);
	}
	
	if (!isset ($config["font_size"])) {
		config_update_value ('font_size', 6);
	}
	
	if (!isset ($config["limit_parameters_massive"])) {
		config_update_value ('limit_parameters_massive', ini_get("max_input_vars") / 2);
	}
	
	/* 
	 *Parse the ACL IP list for access API
	 */
	$temp_list_ACL_IPs_for_API = array();
	if (isset($config['list_ACL_IPs_for_API'])) {
		if (!empty($config['list_ACL_IPs_for_API'])) {
			$temp_list_ACL_IPs_for_API = explode(';', $config['list_ACL_IPs_for_API']);
		}
	}
	$config['list_ACL_IPs_for_API'] = $temp_list_ACL_IPs_for_API;
	$keysConfig = array_keys($config);
	
	
	// This is not set here. The first time, when no
	// setup is done, update_manager extension manage it
	// the first time make a conenction and disable itself
	// Not Managed here !
	
	// if (!isset ($config["autoupdate"])) {
	// 	config_update_value ('autoupdate', true);
	// }
	
	require_once ($config["homedir"] . "/include/auth/mysql.php");
	require_once ($config["homedir"] . "/include/functions_io.php");
	
	
	// Next is the directory where "/attachment" directory is placed,
	// to upload files stores. This MUST be writtable by http server
	// user, and should be in pandora root. By default, Pandora adds
	// /attachment to this, so by default is the pandora console home
	// dir.
	if (!isset ($config['attachment_store'])) {
		config_update_value('attachment_store',
			io_safe_input($config['homedir']) . '/attachment');
	}
	else {
		//Fixed when the user moves the pandora console to another dir
		//after the first uses.
		if (!is_dir($config['attachment_store'])) {
			config_update_value('attachment_store',
				$config['homedir'] . '/attachment');
		}
	}
	
	
	if (!isset ($config['fontpath'])) {
		$home = str_replace('\\', '/', $config['homedir'] );
		config_update_value('fontpath',
			$home . '/include/fonts/smallfont.ttf');
	}
	
	if (!isset ($config['style'])) {
		config_update_value ( 'style', 'pandora');
	}
	
	if (!isset ($config['flash_charts'])) {
		config_update_value ( 'flash_charts', true);
	}
	
	if (!isset ($config["login_background"])) {
		config_update_value ('login_background', '');
	}
	
	if (!isset ($config["paginate_module"])) {
		config_update_value ('paginate_module', false);
	}
	
	if (!isset ($config["graphviz_bin_dir"])) {
		config_update_value ('graphviz_bin_dir', "");
	}
	
	if (!isset ($config["fixed_header"])) {
		config_update_value ('fixed_header', false);
	}
	
	if (!isset ($config["fixed_graph"])) {
		config_update_value ('fixed_graph', false);
	}
	
	if (!isset ($config["fixed_menu"])) {
		config_update_value ('fixed_menu', false);
	}
	

	
	if (!isset ($config["custom_logo"])) {
		config_update_value ('custom_logo', 'pandora_logo_head_4.png');

	}
	if (!isset ($config["custom_logo_login"])) {
		config_update_value ('custom_logo_login', 'login_logo.png');
	}
	
	
	if (!isset ($config['history_db_enabled'])) {
		config_update_value ( 'history_db_enabled', false);
	}
	
	if (!isset ($config['history_event_enabled'])) {
		config_update_value ( 'history_event_enabled', false);
	}
	
	if (!isset ($config['history_db_host'])) {
		config_update_value ( 'history_db_host', '');
	}
	
	if (!isset ($config['history_db_port'])) {
		config_update_value ( 'history_db_port', 3306);
	}
	
	if (!isset ($config['history_db_name'])) {
		config_update_value ( 'history_db_name', 'pandora');
	}
	
	if (!isset ($config['history_db_user'])) {
		config_update_value ( 'history_db_user', 'pandora');
	}
	
	if (!isset ($config['history_db_pass'])) {
		config_update_value ( 'history_db_pass', '');
	}
	
	if (!isset ($config['history_db_days'])) {
		config_update_value ( 'history_db_days', 0);
	}
	
	if (!isset ($config['history_event_days'])) {
		config_update_value ('history_event_days', 90);
	}
	
	if (!isset ($config['history_db_step'])) {
		config_update_value ( 'history_db_step', 0);
	}
	
	if (!isset ($config['history_db_delay'])) {
		config_update_value ( 'history_db_delay', 0);
	}
	
	if (!isset ($config['activate_gis'])) {
		config_update_value ( 'activate_gis', 0);
	}
	
	if (!isset ($config['activate_netflow'])) {
		config_update_value ( 'activate_netflow', 0);
	}
	
	if (!isset ($config['netflow_path'])) {
		if ($is_windows)
			$default = 'C:\\PandoraFMS\\Pandora_Server\\data_in\\netflow';
		else
			$default = '/var/spool/pandora/data_in/netflow';
	
		config_update_value ( 'netflow_path', $default);
	}
	
	if (!isset ($config['netflow_interval'])) {
		config_update_value ( 'netflow_interval', SECONDS_10MINUTES);
	}
	
	if (!isset ($config['netflow_daemon'])) {
		config_update_value ( 'netflow_daemon', '/usr/bin/nfcapd');
	}
	
	if (!isset ($config['netflow_nfdump'])) {
		config_update_value ( 'netflow_nfdump', '/usr/bin/nfdump');
	}
	
	if (!isset ($config['netflow_nfexpire'])) {
		config_update_value ( 'netflow_nfexpire', '/usr/bin/nfexpire');
	}
	
	if (!isset ($config['netflow_max_resolution'])) {
		config_update_value ( 'netflow_max_resolution', '50');
	}
	
	if (!isset ($config['netflow_disable_custom_lvfilters'])) {
		config_update_value ( 'netflow_disable_custom_lvfilters', 0);
	}
	
	if (!isset ($config['netflow_max_lifetime'])) {
		config_update_value ( 'netflow_max_lifetime', '5');
	}
	
	if (!isset ($config['auth'])) {
		config_update_value ( 'auth', 'mysql');
	}
	
	if (!isset ($config['autocreate_remote_users'])) {
		config_update_value ('autocreate_remote_users', 0);
	}
	
	if (!isset ($config['autocreate_blacklist'])) {
		config_update_value ('autocreate_blacklist', '');
	}
	
	if (!isset ($config['default_remote_profile'])) {
		config_update_value ('default_remote_profile', 0);
	}
	
	if (!isset ($config['default_remote_group'])) {
		config_update_value ('default_remote_group', 0);
	}
	
	if (!isset ($config['default_assign_tags'])) {
		config_update_value ( 'default_assign_tags', '');
	}
	
	if (!isset ($config['ldap_server'])) {
		config_update_value ( 'ldap_server', 'localhost');
	}
	
	if (!isset ($config['ldap_port'])) {
		config_update_value ( 'ldap_port', 389);
	}
	
	if (!isset ($config['ldap_version'])) {
		config_update_value ( 'ldap_version', '3');
	}
	
	if (!isset ($config['ldap_start_tls'])) {
		config_update_value ( 'ldap_start_tls', 0);
	}
	
	if (!isset ($config['ldap_base_dn'])) {
		config_update_value('ldap_base_dn',
			'ou=People,dc=edu,dc=example,dc=org');
	}
	
	if (!isset ($config['ldap_login_attr'])) {
		config_update_value ( 'ldap_login_attr', 'uid');
	}
	
	if (!isset ($config['fallback_local_auth'])) {
		config_update_value ( 'fallback_local_auth', '0');
	}
	
	if (!isset ($config['ad_server'])) {
		config_update_value ( 'ad_server', 'localhost');
	}
	
	if (!isset ($config['ad_port'])) {
		config_update_value ( 'ad_port', 389);
	}
	
	if (!isset ($config['ad_start_tls'])) {
		config_update_value ( 'ad_start_tls', 0);
	}
	
	if (!isset ($config['ad_advanced_config'])) {
		config_update_value ( 'ad_advanced_config', 0);
	}
	
	if (!isset ($config['ad_domain'])) {
		config_update_value ( 'ad_domain', '');
	}
	
	if (!isset ($config['ad_adv_perms'])) {
		config_update_value ('ad_adv_perms', '');
	}
	else {
		if (!json_decode(io_safe_output($config['ad_adv_perms']))) {
			$temp_ad_adv_perms = array();
			if ($config['ad_adv_perms'] != '') {
				$perms = explode(';', io_safe_output($config['ad_adv_perms']));
				foreach ($perms as $ad_adv_perm) {
					if (preg_match('/[\[\]]/',$ad_adv_perm)) {
						$all_data =  explode (",", io_safe_output($ad_adv_perm));
						$profile = $all_data[0];
						$group_pnd = $all_data[1];
						$groups_ad = str_replace(array("[","]"), "", $all_data[2]);
						$tags = str_replace(array("[","]"), "", $all_data[3]);
						$groups_ad = explode('|', $groups_ad);
						$tags_name = explode('|', $tags);
						$tags_ids = array();
						foreach ($tags_name as $tag) {
							$tags_ids[] = tags_get_id($tag);
						}
						$profile = profile_get_profiles(
						array(
							"name" => io_safe_input($profile)));
						if (!$profile)
							continue;
						$profile_id = array_keys($profile);
						$id_grupo = groups_get_id (io_safe_input($group_pnd), false);
						$new_ad_adv_perms[] =
							array('profile' => $profile_id[0],
								'group' => array($id_grupo),
								'tags' => $tags_ids,
								'groups_ad' => $groups_ad);
					}
					else {
						$all_data =  explode (",", io_safe_output($ad_adv_perm));
						$profile = $all_data[0];
						$group_pnd = $all_data[1];
						$groups_ad = $all_data[2];
						$tags = $all_data[3];
						$profile = profile_get_profiles(
						array(
							"name" => io_safe_input($profile)));
						if (!$profile)
							continue;
						$profile_id = array_keys($profile);
						$id_grupo = groups_get_id (io_safe_input($group_pnd), false);
						
						$new_ad_adv_perms[] =
							array('profile' => $profile_id[0],
								'group' => array($id_grupo),
								'tags' => array($tags),
								'groups_ad' => array($groups_ad));
					}
				}
				
				if (!empty($new_ad_adv_perms)) {
					$temp_ad_adv_perms = json_encode($new_ad_adv_perms);
				}
			}
			config_update_value ('ad_adv_perms', $temp_ad_adv_perms);
		}
	}
	
	if (!isset ($config['rpandora_server'])) {
		config_update_value ( 'rpandora_server', 'localhost');
	}
	
	if (!isset ($config['rpandora_port'])) {
		config_update_value ( 'rpandora_port', 3306);
	}
	
	if (!isset ($config['rpandora_dbname'])) {
		config_update_value ( 'rpandora_dbname', 'pandora');
	}
	
	if (!isset ($config['rpandora_user'])) {
		config_update_value ( 'rpandora_user', 'pandora');
	}
	
	if (!isset ($config['rpandora_pass'])) {
		config_update_value ( 'rpandora_pass', '');
	}
	
	if (!isset ($config['rbabel_server'])) {
		config_update_value ( 'rbabel_server', 'localhost');
	}
	
	if (!isset ($config['rbabel_port'])) {
		config_update_value ( 'rbabel_port', 3306);
	}
	
	if (!isset ($config['rbabel_dbname'])) {
		config_update_value ( 'rbabel_dbname', 'babel');
	}
	
	if (!isset ($config['rbabel_user'])) {
		config_update_value ( 'rbabel_user', 'babel');
	}
	
	if (!isset ($config['rbabel_pass'])) {
		config_update_value ( 'rbabel_pass', '');
	}
	
	if (!isset ($config['rintegria_server'])) {
		config_update_value ( 'rintegria_server', 'localhost');
	}
	
	if (!isset ($config['rintegria_port'])) {
		config_update_value ( 'rintegria_port', 3306);
	}
	
	if (!isset ($config['rintegria_dbname'])) {
		config_update_value ( 'rintegria_dbname', 'integria');
	}
	
	if (!isset ($config['rintegria_user'])) {
		config_update_value ( 'rintegria_user', 'integria');
	}
	
	if (!isset ($config['rintegria_pass'])) {
		config_update_value ( 'rintegria_pass', '');
	}
	
	if (!isset ($config['saml_path'])) {
		config_update_value ('saml_path', '/opt/');
	}
	
	if (!isset ($config['integria_enabled'])) {
		config_update_value ( 'integria_enabled', '0');
	}
	
	if (!isset ($config['integria_api_password'])) {
		config_update_value ( 'integria_api_password', '');
	}
	
	if (!isset ($config['integria_inventory'])) {
		config_update_value ( 'integria_inventory', '0');
	}
	
	if (!isset ($config['integria_url'])) {
		config_update_value ( 'integria_url', '');
	}
	
	if (!isset ($config['autoupdate'])) {
		config_update_value ( 'autoupdate', 1);
	}
	
	if (!isset ($config['api_password'])) {
		config_update_value( 'api_password', '');
	}
	
	if (defined('METACONSOLE')) {
		// Customizable sections (Metaconsole)
		enterprise_include_once ('include/functions_enterprise.php');
		$customizable_sections = enterprise_hook('enterprise_get_customizable_sections');
		
		if($customizable_sections != ENTERPRISE_NOT_HOOK) {
			foreach($customizable_sections as $k => $v) {
				if (!isset ($config[$k])) {
					config_update_value($k, $v['default']);
				}
			}
		}
		
		if (!isset ($config['meta_num_elements'])) {
			config_update_value('meta_num_elements', 100);
		}
	}
	
	if (!isset ($config['relative_path']) && (isset ($_POST['nick'])
		|| isset ($config['id_user'])) && isset($config['enterprise_installed'])) {
		
		$isFunctionSkins = enterprise_include_once ('include/functions_skins.php');
		if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
			
			// Try to update user table in order to refresh skin inmediatly
			$is_user_updating = get_parameter("sec2", "");
			
			if ($is_user_updating == 'operation/users/user_edit') {
				$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
				$user_info = get_user_info ($id);
				 
				//If current user is editing himself or if the user has UM (User Management) rights on any groups the user is part of AND the authorization scheme allows for users/admins to update info
				if (($config["id_user"] == $id || check_acl ($config["id_user"], users_get_groups ($id), "UM")) && $config["user_can_update_info"]) {
					$view_mode = false;
				}
				else {
					$view_mode = true;
				}
				
				if (isset ($_GET["modified"]) && !$view_mode) { 
					$upd_info["id_skin"] = get_parameter ("skin", $user_info["id_skin"]);
					$return_update_skin = update_user ($id, $upd_info);
				}
			}
			
			if (isset($config['id_user']))
				$relative_path = enterprise_hook('skins_set_image_skin_path',array($config['id_user']));
			else
				$relative_path = enterprise_hook('skins_set_image_skin_path',array(get_parameter('nick')));
			$config['relative_path'] = $relative_path;
		}
	}
	
	if (!isset ($config['dbtype'])) {
		config_update_value ('dbtype', 'mysql');
	}
	
	if (!isset ($config['vc_refr'])) {
		config_update_value ('vc_refr', 300);
	}
	
	if (!isset($config['agent_size_text_small'])) {
		config_update_value ('agent_size_text_small', 18);
	}
	
	if (!isset($config['agent_size_text_medium'])) {
		config_update_value ('agent_size_text_medium', 50);
	}
	
	if (!isset($config['module_size_text_small'])) {
		config_update_value ('module_size_text_small', 25);
	}
	
	if (!isset($config['module_size_text_medium'])) {
		config_update_value ('module_size_text_medium', 50);
	}
	
	if (!isset($config['description_size_text'])) {
		config_update_value ('description_size_text', 60);
	}
	
	if (!isset($config['item_title_size_text'])) {
		config_update_value ('item_title_size_text', 45);
	}
	
	if (!isset($config['gis_label'])) {
		config_update_value ('gis_label', 0);
	}

	if (!isset($config['interface_unit'])) {
		config_update_value ('interface_unit',  __('Bytes'));
	}
	
	if (!isset($config['graph_precision'])) {
		config_update_value ('graph_precision',  1);
	}
	else {
		if (!isset($config['enterprise_installed'])) {
			config_update_value ('graph_precision',  1);
		}
	}

	if (!isset($config['gis_default_icon'])) {
		config_update_value ('gis_default_icon', "marker");
	}
	
	if (!isset($config['interval_values'])) {
		config_update_value ('interval_values', "");
	}
	
	if (!isset($config['public_url'])) {
		config_update_value ('public_url', "");
	}
	
	if (!isset($config['referer_security'])) {
		config_update_value ('referer_security', 0);
	}
	
	if (!isset($config['event_storm_protection'])) {
		config_update_value ('event_storm_protection', 0);
	}
	
	if (!isset($config['server_log_dir'])) {
		config_update_value ('server_log_dir', "");
	}
	
	if (!isset($config['show_group_name'])) {
		config_update_value ('show_group_name', 0);
	}
	
	if (!isset($config['custom_graph_width'])) {
		config_update_value ('custom_graph_width', 1);
	}
	
	if (!isset($config['type_module_charts'])) {
		config_update_value ('type_module_charts', 'area');
	}
	
	if (!isset($config['render_proc'])) {
		config_update_value ('render_proc', 0);
	}
	
	if (!isset($config["render_proc_ok"])) {
		config_update_value ('render_proc_ok', __('Ok') );
	}
	if (!isset($config["render_proc_fail"])) {
		config_update_value ('render_proc_fail', __('Fail') );
	}
	//Daniel maya 02/06/2016 Display menu with click --INI
	if (!isset($config["click_display"])) {
		config_update_value ('click_display', 1);
	}
	//Daniel maya 02/06/2016 Display menu with click --END
	if (!isset($config['command_snapshot'])) {
		config_update_value ('command_snapshot', 1);
	}
	
	// Juanma (06/05/2014) New feature: Custom front page for reports  
	if (!isset($config['custom_report_front'])) {
		config_update_value ('custom_report_front', 0);
	}
	
	if (!isset($config['custom_report_front_font'])) {
		config_update_value ('custom_report_front_font', 'FreeSans.ttf');
	}
	
	if (!isset($config['custom_report_front_logo'])) {
		config_update_value ('custom_report_front_logo',
			'images/pandora_logo_white.jpg');
	}
	
	if (!isset($config['custom_report_front_header'])) {
		config_update_value ('custom_report_front_header', '');
	}
	
	if (!isset($config['custom_report_front_firstpage'])) {
		config_update_value ('custom_report_front_firstpage',
			"&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;img&#x20;src=&quot;&#40;_URLIMAGE_&#41;/images/pandora_report_logo.png&quot;&#x20;alt=&quot;&quot;&#x20;width=&quot;800&quot;&#x20;/&gt;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;span&#x20;style=&quot;font-size:&#x20;xx-large;&quot;&gt;&#40;_REPORT_NAME_&#41;&lt;/span&gt;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;span&#x20;style=&quot;font-size:&#x20;large;&quot;&gt;&#40;_DATETIME_&#41;&lt;/span&gt;&lt;/p&gt;");
	}
	
	if (!isset($config['custom_report_front_footer'])) {
		config_update_value ('custom_report_front_footer', '');
	}
	
	if (!isset($config['autohidden_menu'])) {
		config_update_value ('autohidden_menu', 0);
	}
	
	if (!isset($config['networkmap_max_width'])) {
		config_update_value ('networkmap_max_width', 900);
	}
	
	if (!isset($config['tutorial_mode'])) {
		config_update_value ('tutorial_mode', 'full');
	}
	
	if (!isset($config['post_process_custom_values'])) {
		config_update_value ('post_process_custom_values',
			json_encode(array()));
	}
	
	if (!isset($config['update_manager_proxy_server'])) {
		config_update_value ('update_manager_proxy_server',
			"");
	}
	if (!isset($config['update_manager_proxy_port'])) {
		config_update_value ('update_manager_proxy_port',
			"");
	}
	if (!isset($config['update_manager_proxy_user'])) {
		config_update_value ('update_manager_proxy_user',
			"");
	}
	if (!isset($config['update_manager_proxy_password'])) {
		config_update_value ('update_manager_proxy_password',
			"");
	}
	
	if (!isset ($config["session_timeout"])) {
		config_update_value ('session_timeout', 90);
	}
	
	if (!isset ($config["max_file_size"])) {
		config_update_value ('max_file_size', "2M");
	}
	
	if (!isset ($config["initial_wizard"])) {
		config_update_value ('initial_wizard', 0);
	}
	
	if (!isset ($config["identification_reminder"])) {
		config_update_value ('identification_reminder', 1);
	}
	
	if (!isset ($config["identification_reminder_timestamp"])) {
		config_update_value ('identification_reminder_timestamp', 0);
	}
	
	if (!isset ($config["instance_registered"])) {
		config_update_value ('instance_registered', 0);
	}
	
	// eHorus
	if (!isset($config['ehorus_enabled'])) {
		config_update_value('ehorus_enabled', 0);
	}
	if (!isset($config['ehorus_custom_field'])) {
		config_update_value('ehorus_custom_field', 'eHorusID');
	}
	if (!isset($config['ehorus_hostname'])) {
		config_update_value('ehorus_hostname', 'switch.ehorus.com');
	}
	if (!isset($config['ehorus_port'])) {
		config_update_value('ehorus_port', 18080);
	}
	if (!isset($config['ehorus_req_timeout'])) {
		config_update_value('ehorus_req_timeout', 5);
	}
	
	/* Finally, check if any value was overwritten in a form */
	config_update_config();
}

function config_check () {
	global $config;
	
	// At this first version I'm passing errors using session variables, because the error management
	// is done by an AJAX request. Better solutions could be implemented in the future :-)
	
	if (license_free() && users_is_admin($config['id_user'])) {
		
		$login = get_parameter ('login', false);
		//Registration advice
		if ((!isset ($config['instance_registered']) || ($config['instance_registered'] != 1)) && ($login === false)) {
			set_pandora_error_for_header(  
				__('Click <a style="font-weight:bold; text-decoration:underline" href="javascript: force_run_register();"> here</a> to start the registration process'),
				__("This instance is not registered in the Update manager"));
		}
		
		//Newsletter advice
		$newsletter = db_get_value ('middlename', 'tusuario', 'id_user', $config['id_user']);
		if ($newsletter != 1 && $login === false) {
			set_pandora_error_for_header(  
				__('Click <a style="font-weight:bold; text-decoration:underline" href="javascript: force_run_newsletter();"> here</a> to start the newsletter subscription process'),
				__("Not subscribed to the newsletter"));
		}
	}
	
	// Check default password for "admin"
	$is_admin = db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);
	if ($is_admin) {
		$hashpass = db_get_sql ("SELECT password
			FROM tusuario WHERE id_user = 'admin'");
		if ($hashpass == "1da7ee7d45b96d0e1f45ee4ee23da560") {
			set_pandora_error_for_header(
				__('Default password for "Admin" user has not been changed.'),
				__('Please change the default password because is a common vulnerability reported.'));
		}
	}
	
	if (isset ($config['license_expired'])) {
		set_pandora_error_for_header(
			__('You can not get updates until you renew the license.'),
			__('This license has expired.'));
	}
	
	if (!is_writable ("attachment")) {
		set_pandora_error_for_header(
			__('Please check that the web server has write rights on the {HOMEDIR}/attachment directory'),
			__('Attachment directory is not writable by HTTP Server'));
	}
	
	// Get remote file dir.
	$remote_config = io_safe_output(db_get_value_filter('value',
		'tconfig', array('token' => 'remote_config')));
	
	
	if (enterprise_installed()) {
		
		if (!is_readable ($remote_config)) {
			set_pandora_error_for_header(
				__('Remote configuration directory is not readble for the console') .
				' - ' . $remote_config);
		}
		
		$remote_config_conf = $remote_config . "/conf";
		if (!is_writable ($remote_config_conf)) {
			set_pandora_error_for_header(__('Remote configuration directory is not writtable for the console') .
				' - ' . $remote_config . '/conf');
		}
		
		$remote_config_col = $remote_config . "/collections";
		if (!is_writable ($remote_config_col)) {
			set_pandora_error_for_header(
				__('Remote configuration directory is not writtable for the console') .
				' - ' . $remote_config . '/collections');
		}
	}
	
	// Check attachment directory (too much files?)
	
	$filecount = count(glob($config["homedir"]."/attachment/*"));
	// N temporal files of trash should be enough for most people.
	if ($filecount > $config['num_files_attachment']) {
		set_pandora_error_for_header(
			__("There are too much files in attachment directory. This is not fatal, but you should consider cleaning up your attachment directory manually"). " ( $filecount ". __("files") . " )",
			__('Too much files in your tempora/attachment directory'));
	}
	
	// Check database maintance
	$db_maintance = db_get_value_filter('value', 'tconfig',
		array('token' => 'db_maintance')); 
	
	// If never was executed, it means we are in the first Pandora FMS execution. Set current timestamp
	if (empty($db_maintance)) {
		config_update_value ('db_maintance', date("U"));
	}
	
	$last_maintance = date("U") - $db_maintance;

	// ~ about 50 hr
	if ($last_maintance > 190000) {
		set_pandora_error_for_header(
			__('Your database is not well maintained. Seems that it have more than 48hr without a proper maintance. Please review Pandora FMS documentation about how to execute this maintance process (pandora_db.pl) and enable it as soon as possible'),
			__("Database maintance problem"));
	}
	
	$fontpath = io_safe_output(db_get_value_filter('value', 'tconfig', array('token' => 'fontpath')));
	if (($fontpath == "") OR (!file_exists ($fontpath))) {
		set_pandora_error_for_header(
			__('Your defined font doesnt exist or is not defined. Please check font parameters in your config'),
			__("Default font doesnt exist"));
	}
	
	if ($config['event_storm_protection']) {
		set_pandora_error_for_header(
			__('You need to restart server after altering this configuration setting.'),
			__('Event storm protection is activated. No events will be generated during this mode.'));
	}
	
	global $develop_bypass;
	
	if ($develop_bypass == 1) {
		set_pandora_error_for_header(
			__('Your Pandora FMS has the "develop_bypass" mode enabled. This is a developer mode and should be disabled in a production system. This value is written in the main index.php file'),
			__("Developer mode is enabled"));
	}
	
	if (isset($_SESSION['new_update'])) {
		if (!empty($_SESSION['return_installation_open'])) {
			if (!$_SESSION['return_installation_open']['return']) {
				foreach ($_SESSION['return_installation_open']['text'] as $message) {
					set_pandora_error_for_header(
						$message,
						__("Error first setup Open update"));
				}
			}
		}
		if ($_SESSION['new_update'] == 'new') {
			set_pandora_error_for_header(
				__('There is a new update available. Please<a style="font-weight:bold;" href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=online"> go to Administration:Setup:Update Manager</a> for more details.'),
				__("New update of Pandora Console"));
		}
	}
	
	// PHP configuration values
	$PHPupload_max_filesize = config_return_in_bytes(ini_get('upload_max_filesize'));
	$PHPmax_input_time = ini_get('max_input_time');
	$PHPmemory_limit = config_return_in_bytes(ini_get('memory_limit'));
	$PHPmax_execution_time = ini_get('max_execution_time');
	$PHPsafe_mode = ini_get('safe_mode');
	$PHPdisable_functions = ini_get('disable_functions');
	
	if ($PHPsafe_mode === '1') {
		set_pandora_error_for_header(
			__('To disable, change it on your PHP configuration file (php.ini) and put safe_mode = Off (Dont forget restart apache process after changes)'),
			sprintf(__("PHP safe mode is enabled. Some features may not properly work.")));
	}
	
	if ($PHPmax_input_time !== '-1') {
		set_pandora_error_for_header(
			sprintf(__('Recommended value is %s'), '-1 (' . __('Unlimited') . ')') . '<br><br>' . __('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Dont forget restart apache process after changes)'),
			sprintf(__("Not recommended '%s' value in PHP configuration"), 'max_input_time'));
	}
	
	if ($PHPmax_execution_time !== '0') {
		set_pandora_error_for_header(
			sprintf(__('Recommended value is: %s'), '0 (' . __('Unlimited') . ')') . '<br><br>' . __('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Dont forget restart apache process after changes)'),
			sprintf(__("Not recommended '%s' value in PHP configuration"), 'max_execution_time'));
	}
	
	$PHPupload_max_filesize_min = config_return_in_bytes('800M');
	
	if ($PHPupload_max_filesize < $PHPupload_max_filesize_min) {
		set_pandora_error_for_header(
			sprintf(__('Recommended value is: %s'), sprintf(__('%s or greater'), '800M')) . '<br><br>' . __('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Dont forget restart apache process after changes)'),
			sprintf(__("Not recommended '%s' value in PHP configuration"), 'upload_max_filesize'));
	}
	
	$PHPmemory_limit_min = config_return_in_bytes('500M');
	
	if ($PHPmemory_limit < $PHPmemory_limit_min && $PHPmemory_limit !== '-1') {
		set_pandora_error_for_header(
			sprintf(__('Recommended value is: %s'), sprintf(__('%s or greater'), '500M')) . '<br><br>' . __('Please, change it on your PHP configuration file (php.ini) or contact with administrator'),
			sprintf(__("Not recommended '%s' value in PHP configuration"), 'memory_limit'));
	}
	
	if (preg_match("/system/", $PHPdisable_functions) or preg_match("/exec/", $PHPdisable_functions)) {
		set_pandora_error_for_header( 
			__("Variable disable_functions containts functions system() or exec(), in PHP configuration file (php.ini)"). '<br /><br />' . 
			__('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Dont forget restart apache process after changes)'), __("Problems with disable functions in PHP.INI"));
	}
	
}

function config_return_in_bytes($val) {
	$val = trim($val);
	$last = strtolower($val[strlen($val) - 1]);
	switch ($last) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	
	return $val;
}

function config_user_set_custom_config() {
	global $config;
	
	$userinfo = get_user_info ($config['id_user']);
	
	// Refresh the last_connect info in the user table 
	// if last update was more than 5 minutes ago
	if ($userinfo['last_connect'] < (time()-SECONDS_1MINUTE)) {
		update_user($config['id_user'], array('last_connect' => time()));
	}
	
	// If block_size or flash_chart are provided then override global settings
	if (!empty($userinfo["block_size"]) && ($userinfo["block_size"] != 0))
		$config["block_size"] = $userinfo["block_size"];
	
	if ($userinfo["flash_chart"] != -1)
		$config["flash_charts"] = $userinfo["flash_chart"];
	
	// Each user could have it's own timezone)
	if (isset($userinfo["timezone"])) {
		if ($userinfo["timezone"] != "") {
			date_default_timezone_set($userinfo["timezone"]);
		}
	}
	
	if (defined('METACONSOLE')) {
		$config['metaconsole_access'] = $userinfo["metaconsole_access"];
	}
}

function config_prepare_session() {
	global $config;
	
	// Change the session timeout value to session_timeout minutes  // 8*60*60 = 8 hours
	$sessionCookieExpireTime = $config["session_timeout"];
	
	if ($sessionCookieExpireTime <= 0)
		$sessionCookieExpireTime = 10 * 365 * 24 * 60 * 60;
	else
		$sessionCookieExpireTime *= 60;
	
	ini_set('session.gc_maxlifetime', $sessionCookieExpireTime);
	session_set_cookie_params ($sessionCookieExpireTime);
	
	// Reset the expiration time upon page load //session_name() is default name of session PHPSESSID
	
	if (isset($_COOKIE[session_name()]))
		setcookie(session_name(), $_COOKIE[session_name()], time() + $sessionCookieExpireTime, "/");
	
	ini_set("post_max_size", $config["max_file_size"]);
	ini_set("upload_max_filesize", $config["max_file_size"]);
}
?>
