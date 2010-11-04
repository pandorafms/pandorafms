<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

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
function create_config_value ($token, $value) {
	return process_sql_insert ('tconfig',
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
function update_config_value ($token, $value) {
	global $config;
	
	switch ($token) {
		case 'list_ACL_IPs_for_API':
			$rows = get_db_all_rows_sql('SELECT id_config
				FROM tconfig 
				WHERE token LIKE "%list_ACL_IPs_for_API_%"');
			
			if ($rows !== false) {
				foreach ($rows as $row)
					$idListACLofIP[] = $row['id_config'];
				
				process_sql_delete('tconfig', 'id_config IN (' . implode(',', $idListACLofIP) . ')' );
			}
			
			if (strpos($value, "\r\n") !== false)
				$ips = explode("\r\n", $value);
			else
				$ips = explode("\n", $value);

			$valueDB = '';
			$count = 0;
			$lastInsert = false;
			foreach ($ips as $ip) {
				$ip = trim($ip);
				
				$lastInsert = false;
				if (strlen($valueDB . ';' . $ip) < 100) {
					//100 is the size of field 'value' in tconfig.
					if (strlen($valueDB) == 0)
						$valueDB .= $ip;
					else
						$valueDB .= ';' . $ip;
				}
				else {
					if (strlen($ip) > 100)
						return false;
						
					process_sql_insert('tconfig',
						array('token' => 'list_ACL_IPs_for_API_' . $count , 'value' => $valueDB));
					$valueDB = $ip;
					$count++;
					$lastInsert = true;
				}
			}
			if (!$lastInsert)
				process_sql_insert('tconfig',
					array('token' => 'list_ACL_IPs_for_API_' . $count , 'value' => $valueDB));
			
			break;
		default:
			if (!isset ($config[$token])){
			$config[$token] = $value;
				return (bool) create_config_value ($token, $value);
			}
			
			/* If it has not changed */
			if ($config[$token] == $value)
				return true;
			
			$config[$token] = $value;
			
			return (bool) process_sql_update ('tconfig', 
				array ('value' => $value),
				array ('token' => $token));
			break;
	}
}

/**
 * Updates all config values in case setup page was invoked 
 */
function update_config () {
	global $config;
	
	/* If user is not even log it, don't try this */
	if (! isset ($config['id_user']))
		return false;
	
	if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user']))
		return false;
	
	$update_config = (bool) get_parameter ('update_config');
	if (! $update_config)
		return false;
	
	$style = (string) get_parameter ('style', $config["style"]);
	if ($style != $config['style'])
		$style = substr ($style, 0, strlen ($style) - 4);

	update_config_value ('language', (string) get_parameter ('language', $config["language"]));	
	update_config_value ('remote_config', (string) get_parameter ('remote_config', $config["remote_config"]));
	update_config_value ('block_size', (int) get_parameter ('block_size', $config["block_size"]));
	update_config_value ('days_purge', (int) get_parameter ('days_purge', $config["days_purge"]));
	update_config_value ('days_compact', (int) get_parameter ('days_compact', $config["days_compact"]));
	update_config_value ('graph_res', (int) get_parameter ('graph_res', $config["graph_res"]));
	update_config_value ('step_compact', (int) get_parameter ('step_compact', $config["step_compact"]));
	update_config_value ('style', $style);
	update_config_value ('graph_color1', (string) get_parameter ('graph_color1', $config["graph_color1"]));
	update_config_value ('graph_color2', (string) get_parameter ('graph_color2', $config["graph_color2"]));
	update_config_value ('graph_color3', (string) get_parameter ('graph_color3', $config["graph_color3"]));
	update_config_value ('sla_period', (int) get_parameter ('sla_period', $config["sla_period"]));
	update_config_value ('date_format', (string) get_parameter ('date_format', $config["date_format"]));
	update_config_value ('trap2agent', (string) get_parameter ('trap2agent', $config["trap2agent"]));
	update_config_value ('autoupdate', (bool) get_parameter ('autoupdate', $config["autoupdate"]));
	update_config_value ('prominent_time', (string) get_parameter ('prominent_time', $config["prominent_time"]));
	update_config_value ('timesource', (string) get_parameter ('timesource', $config["timesource"]));
	update_config_value ('event_view_hr', (int) get_parameter ('event_view_hr', $config["event_view_hr"]));
	update_config_value ('loginhash_pwd', (string) get_parameter ('loginhash_pwd', $config["loginhash_pwd"]));
	update_config_value ('https', (bool) get_parameter ('https', $config["https"]));
	update_config_value ('compact_header', (bool) get_parameter ('compact_header', $config["compact_header"]));
	update_config_value ('fontpath', (string) get_parameter ('fontpath', $config["fontpath"]));
	update_config_value ('round_corner', (bool) get_parameter ('round_corner', $config["round_corner"]));
	update_config_value ('status_images_set', (string) get_parameter ('status_images_set', $config["status_images_set"]));
	update_config_value ('agentaccess', (int) get_parameter ('agentaccess', $config['agentaccess']));
	update_config_value ('flash_charts', (bool) get_parameter ('flash_charts', $config["flash_charts"]));
	update_config_value ('attachment_store', (string) get_parameter ('attachment_store', $config["attachment_store"]));
	update_config_value ('list_ACL_IPs_for_API', (string) get_parameter('list_ACL_IPs_for_API', implode("\n", $config['list_ACL_IPs_for_API'])));

	update_config_value ('custom_logo', (string) get_parameter ('custom_logo', $config["custom_logo"]));
	update_config_value ('history_db_enabled', (bool) get_parameter ('history_db_enabled', $config['history_db_enabled']));
	update_config_value ('history_db_host', (string) get_parameter ('history_db_host', $config['history_db_host']));
	update_config_value ('history_db_port', (int) get_parameter ('history_db_port', $config['history_db_port']));
	update_config_value ('history_db_name', (string) get_parameter ('history_db_name', $config['history_db_name']));
	update_config_value ('history_db_user', (string) get_parameter ('history_db_user', $config['history_db_user']));
	update_config_value ('history_db_pass', (string) get_parameter ('history_db_pass', $config['history_db_pass']));
	update_config_value ('history_db_days', (string) get_parameter ('history_db_days', $config['history_db_days']));
	update_config_value ('history_db_step', (string) get_parameter ('history_db_step', $config['history_db_step']));
	update_config_value ('history_db_delay', (string) get_parameter ('history_db_delay', $config['history_db_delay']));
	update_config_value ('timezone', (string) get_parameter ('timezone', $config['timezone']));
	update_config_value ('activate_gis', (bool) get_parameter ('activate_gis', $config['activate_gis']));
	update_config_value ('stats_interval', get_parameter ('stats_interval', $config['stats_interval']));
	update_config_value ('realtimestats', get_parameter ('realtimestats', $config['realtimestats']));
	update_config_value ('event_purge', get_parameter ('event_purge', $config['event_purge']));
	update_config_value ('trap_purge', get_parameter ('trap_purge', $config['trap_purge']));
	update_config_value ('string_purge', get_parameter ('string_purge', $config['string_purge']));
	update_config_value ('audit_purge', get_parameter ('audit_purge', $config['audit_purge']));
	update_config_value ('acl_enterprise', get_parameter ('acl_enterprise', $config['acl_enterprise']));
	update_config_value ('metaconsole', get_parameter ('metaconsole', $config['metaconsole']));
	update_config_value ('gis_purge', get_parameter ('gis_purge', $config['gis_purge']));
	update_config_value ('auth', get_parameter ('auth', $config['auth']));
	update_config_value ('autocreate_remote_users', get_parameter ('autocreate_remote_users', $config['autocreate_remote_users']));
	update_config_value ('autocreate_blacklist', get_parameter ('autocreate_blacklist', $config['autocreate_blacklist']));
	update_config_value ('default_remote_profile', get_parameter ('default_remote_profile', $config['default_remote_profile']));
	update_config_value ('default_remote_group', get_parameter ('default_remote_group', $config['default_remote_group']));

	update_config_value ('ldap_server', get_parameter ('ldap_server', $config['ldap_server']));
	update_config_value ('ldap_port', get_parameter ('ldap_port', $config['ldap_port']));
	update_config_value ('ldap_version', get_parameter ('ldap_version', $config['ldap_version']));
	update_config_value ('ldap_start_tls', get_parameter ('ldap_start_tls', $config['ldap_start_tls']));
	update_config_value ('ldap_base_dn', get_parameter ('ldap_base_dn', $config['ldap_base_dn']));
	update_config_value ('ldap_login_attr', get_parameter ('ldap_login_attr', $config['ldap_login_attr']));

	update_config_value ('ad_server', get_parameter ('ad_server', $config['ad_server']));
	update_config_value ('ad_port', get_parameter ('ad_port', $config['ad_port']));
	update_config_value ('ad_start_tls', get_parameter ('ad_start_tls', $config['ad_start_tls']));
	update_config_value ('ad_domain', get_parameter ('ad_domain', $config['ad_domain']));

	update_config_value ('rpandora_server', get_parameter ('rpandora_server', $config['rpandora_server']));
	update_config_value ('rpandora_port', get_parameter ('rpandora_port', $config['rpandora_port']));
	update_config_value ('rpandora_dbname', get_parameter ('rpandora_dbname', $config['rpandora_dbname']));
	update_config_value ('rpandora_user', get_parameter ('rpandora_user', $config['rpandora_user']));
	update_config_value ('rpandora_pass', get_parameter ('rpandora_pass', $config['rpandora_pass']));

	update_config_value ('rbabel_server', get_parameter ('rbabel_server', $config['rbabel_server']));
	update_config_value ('rbabel_port', get_parameter ('rbabel_port', $config['rbabel_port']));
	update_config_value ('rbabel_dbname', get_parameter ('rbabel_dbname', $config['rbabel_dbname']));
	update_config_value ('rbabel_user', get_parameter ('rbabel_user', $config['rbabel_user']));
	update_config_value ('rbabel_pass', get_parameter ('rbabel_pass', $config['rbabel_pass']));

	update_config_value ('rintegria_server', get_parameter ('rintegria_server', $config['rintegria_server']));
	update_config_value ('rintegria_port', get_parameter ('rintegria_port', $config['rintegria_port']));
	update_config_value ('rintegria_dbname', get_parameter ('rintegria_dbname', $config['rintegria_dbname']));
	update_config_value ('rintegria_user', get_parameter ('rintegria_user', $config['rintegria_user']));
	update_config_value ('rintegria_pass', get_parameter ('rintegria_pass', $config['rintegria_pass']));
	
	update_config_value ('sound_alert', get_parameter('sound_alert', $config['sound_alert']));
	update_config_value ('sound_critical', get_parameter('sound_critical', $config['sound_critical']));
	update_config_value ('sound_warning', get_parameter('sound_warning', $config['sound_warning']));
	
	$enterprise = enterprise_include_once('include/functions_policies.php');
	if ($enterprise !== ENTERPRISE_NOT_HOOK) {
		$locked = enterprise_hook('semaphore_policy_test_and_set');
		if ($locked) {
			pandora_audit("Policy management", "BLOCK policies for change tconfig['can_block_policies'] by " . $config['id_user']);

			update_config_value ('can_block_policies', get_parameter('can_block_policies', $config['can_block_policies']));
			
			pandora_audit("Policy management", "UNBLOCK policies for change tconfig['can_block_policies'] by " . $config['id_user']);
			enterprise_hook('semaphore_policy_unlock');
		}
		else {
			pandora_audit("Policy management", "Try to BLOCK policies for change tconfig['can_block_policies'] by " . $config['id_user']);
		}
	}
	else {
		update_config_value ('can_block_policies', get_parameter('can_block_policies', $config['can_block_policies']));
	}
	
}

/**
 * Process config variables
 */
function process_config () {
	global $config;
	
	$configs = get_db_all_rows_in_table ('tconfig');
	
	if (empty ($configs)) {
		include ($config["homedir"]."/general/error_emptyconfig.php");
		exit;
	}
	
	/* Compatibility fix */
	foreach ($configs as $c) {
			$config[$c['token']] = $c['value'];
	}
	
	if (!isset ($config['language'])) {
		update_config_value ('language', 'en');
	}

	if (isset ($config['homeurl']) && $config['homeurl'][0] != '/') {
		$config['homeurl'] = '/'.$config['homeurl'];
	}
	
	if (!isset ($config['date_format'])) {
		update_config_value ('date_format', 'F j, Y, g:i a');
	}
	
	if (!isset ($config['event_view_hr'])) {
		update_config_value ('event_view_hr', 8);
	}
	
	if (!isset ($config['loginhash_pwd'])) {
		update_config_value ('loginhash_pwd', rand (0, 1000) * rand (0, 1000)."pandorahash");
	}
	
	if (!isset ($config["trap2agent"])) {
		update_config_value ('trap2agent', 0);
	}
	
	if (!isset ($config["sla_period"]) || empty ($config["sla_period"])) {
		update_config_value ('sla_period', 604800);
	}
	
	if (!isset ($config["prominent_time"])) {
		// Prominent time tells us what to show prominently when a timestamp is
		// displayed. The comparation (... days ago) or the timestamp (full date)
		update_config_value ('prominent_time', 'comparation');
	}
	
	if (!isset ($config["timesource"])) {
		// Timesource says where time comes from (system or mysql)
		update_config_value ('timesource', 'system');
	}
	
	if (!isset ($config["https"])) {
		// Sets whether or not we want to enforce https. We don't want to go to a
		// potentially unexisting config by default
		update_config_value ('https', false);
	}
	
	if (!isset ($config["compact_header"])) {
		update_config_value ('compact_header', false);
	}
	
	if (!isset ($config['status_images_set'])) {
		update_config_value ('status_images_set', 'default');
	}
	
	// Load user session
	if (isset ($_SESSION['id_usuario']))
		$config["id_user"] = $_SESSION["id_usuario"];

	if (!isset ($config["round_corner"])) {
		update_config_value ('round_corner', false);
	}

	if (!isset ($config["agentaccess"])){
		update_config_value ('agentaccess', true);
	}
	
	if (!isset ($config["timezone"])){
		update_config_value ('timezone', "Europe/Berlin");
	}

	if (!isset ($config["stats_interval"])){
		update_config_value ('stats_interval', 300);
	}

	if (!isset ($config["realtimestats"])){
		update_config_value ('realtimestats', 1);
	}

	if (!isset ($config["event_purge"])){
		update_config_value ('event_purge', 15);
	}

	if (!isset ($config["trap_purge"])){
		update_config_value ('trap_purge', 7);
	}

	if (!isset ($config["string_purge"])){
		update_config_value ('string_purge', 14);
	}

	if (!isset ($config["audit_purge"])){
		update_config_value ('audit_purge', 30);
	}

	if (!isset ($config["acl_enterprise"])){
		update_config_value ('acl_enterprise', 0);
	}

	if (!isset ($config["metaconsole"])){
		update_config_value ('metaconsole', 0);
	}

	if (!isset ($config["gis_purge"])){
		update_config_value ('gis_purge', 7);
	}
	
	if (!isset ($config["collection_max_size"])){
		update_config_value ('collection_max_size', 1000000);
	}

	/* 
	 *Parse the ACL IP list for access API that it's save in chunks as
	 *list_ACL_IPs_for_API_<num>, because the value has a limit of 100
	 *characters.
	 */
	
	$config['list_ACL_IPs_for_API'] = array();
	$keysConfig = array_keys($config);
	foreach($keysConfig as $keyConfig)
		if (strpos($keyConfig, 'list_ACL_IPs_for_API_') !== false) {
			$ips = explode(';',$config[$keyConfig]);
			$config['list_ACL_IPs_for_API'] =
				array_merge($config['list_ACL_IPs_for_API'], $ips);
			
			unset($config[$keyConfig]);
		}
	

	// This is not set here. The first time, when no
	// setup is done, update_manager extension manage it
	// the first time make a conenction and disable itself
	// Not Managed here !
	
	// if (!isset ($config["autoupdate"])){
	// 	update_config_value ('autoupdate', true);
	// }
	
	require_once ($config["homedir"]."/include/auth/mysql.php");
	
	// Next is the directory where "/attachment" directory is placed, to upload files stores. 
	// This MUST be writtable by http server user, and should be in pandora root. 
	// By default, Pandora adds /attachment to this, so by default is the pandora console home dir
	if (!isset ($config['attachment_store'])) {
		update_config_value ( 'attachment_store', $config['homedir'].'/attachment');
	}
	
	if (!isset ($config['fontpath'])) {
		update_config_value ( 'fontpath', $config['homedir'].'/include/FreeSans.ttf');
	}

	if (!isset ($config['style'])) {
		update_config_value ( 'style', 'pandora');
	}

	if (!isset ($config['flash_charts'])) {
		update_config_value ( 'flash_charts', true);
	}
			
	if (!isset ($config["custom_logo"])){
		update_config_value ('custom_logo', 'none.png');
	}

	if (!isset ($config['history_db_enabled'])) {
		update_config_value ( 'history_db_enabled', false);
	}

	if (!isset ($config['history_db_host'])) {
		update_config_value ( 'history_db_host', '');
	}

	if (!isset ($config['history_db_port'])) {
		update_config_value ( 'history_db_port', 3306);
	}

	if (!isset ($config['history_db_name'])) {
		update_config_value ( 'history_db_name', 'pandora');
	}

	if (!isset ($config['history_db_user'])) {
		update_config_value ( 'history_db_user', 'pandora');
	}

	if (!isset ($config['history_db_pass'])) {
		update_config_value ( 'history_db_pass', '');
	}

	if (!isset ($config['history_db_days'])) {
		update_config_value ( 'history_db_days', 0);
	}

	if (!isset ($config['history_db_step'])) {
		update_config_value ( 'history_db_step', 0);
	}

	if (!isset ($config['history_db_delay'])) {
		update_config_value ( 'history_db_delay', 0);
	}

	if (!isset ($config['activate_gis'])) {
		update_config_value ( 'activate_gis', 0);
	}

	if (!isset ($config['auth'])) {
		update_config_value ( 'auth', 'mysql');
	}

	if (!isset ($config['autocreate_remote_users'])) {
		update_config_value ('autocreate_remote_users', 0);
	}

	if (!isset ($config['autocreate_blacklist'])) {
		update_config_value ('autocreate_blacklist', '');
	}

	if (!isset ($config['default_remote_profile'])) {
		update_config_value ('default_remote_profile', 0);
	}

	if (!isset ($config['default_remote_group'])) {
		update_config_value ('default_remote_group', 0);
	}

	if (!isset ($config['ldap_server'])) {
		update_config_value ( 'ldap_server', 'localhost');
	}

	if (!isset ($config['ldap_port'])) {
		update_config_value ( 'ldap_port', 389);
	}

	if (!isset ($config['ldap_version'])) {
		update_config_value ( 'ldap_version', '3');
	}

	if (!isset ($config['ldap_start_tls'])) {
		update_config_value ( 'ldap_start_tls', 0);
	}

	if (!isset ($config['ldap_base_dn'])) {
		update_config_value ( 'ldap_base_dn', 'ou=People,dc=edu,dc=example,dc=org');
	}

	if (!isset ($config['ldap_login_attr'])) {
		update_config_value ( 'ldap_login_attr', 'uid');
	}

	if (!isset ($config['ad_server'])) {
		update_config_value ( 'ad_server', 'localhost');
	}

	if (!isset ($config['ad_port'])) {
		update_config_value ( 'ad_port', 389);
	}

	if (!isset ($config['ad_start_tls'])) {
		update_config_value ( 'ad_start_tls', 0);
	}

	if (!isset ($config['ad_domain'])) {
		update_config_value ( 'ad_domain', '');
	}

	if (!isset ($config['rpandora_server'])) {
		update_config_value ( 'rpandora_server', 'localhost');
	}

	if (!isset ($config['rpandora_port'])) {
		update_config_value ( 'rpandora_port', 3306);
	}

	if (!isset ($config['rpandora_dbname'])) {
		update_config_value ( 'rpandora_dbname', 'pandora');
	}

	if (!isset ($config['rpandora_user'])) {
		update_config_value ( 'rpandora_user', 'pandora');
	}

	if (!isset ($config['rpandora_pass'])) {
		update_config_value ( 'rpandora_pass', '');
	}

	if (!isset ($config['rbabel_server'])) {
		update_config_value ( 'rbabel_server', 'localhost');
	}

	if (!isset ($config['rbabel_port'])) {
		update_config_value ( 'rbabel_port', 3306);
	}

	if (!isset ($config['rbabel_dbname'])) {
		update_config_value ( 'rbabel_dbname', 'babel');
	}

	if (!isset ($config['rbabel_user'])) {
		update_config_value ( 'rbabel_user', 'babel');
	}

	if (!isset ($config['rbabel_pass'])) {
		update_config_value ( 'rbabel_pass', '');
	}

	if (!isset ($config['rintegria_server'])) {
		update_config_value ( 'rintegria_server', 'localhost');
	}

	if (!isset ($config['rintegria_port'])) {
		update_config_value ( 'rintegria_port', 3306);
	}

	if (!isset ($config['rintegria_dbname'])) {
		update_config_value ( 'rintegria_dbname', 'integria');
	}

	if (!isset ($config['rintegria_user'])) {
		update_config_value ( 'rintegria_user', 'integria');
	}

	if (!isset ($config['rintegria_pass'])) {
		update_config_value ( 'rintegria_pass', '');
	}
	
	if (!isset ($config['autoupdate'])) {
		update_config_value ( 'autoupdate', 0);
	}
	
	if (!isset ($config['can_block_policies'])) {
		update_config_value ( 'can_block_policies', 0);
	}

	/* Finally, check if any value was overwritten in a form */
	update_config ();
}
?>
