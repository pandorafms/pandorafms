<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Warning: This file may be required into the metaconsole's setup

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

include_once($config['homedir'] . "/include/functions_profile.php");

// Load enterprise extensions
enterprise_include ('godmode/setup/setup_auth.php');

$table = new StdClass();
$table->data = array ();
$table->width = '100%';
$table->class = 'databox filters';
$table->size['name'] = '30%';
$table->style['name'] = "font-weight: bold";

// Auth methods added to the table (doesn't take in account mysql)
$auth_methods_added = array();

// Remote options row names
// Fill this array for every matched row
$remote_rows = array();

// Autocreate options row names
// Fill this array for every matched row
$autocreate_rows = array();

// LDAP data row names
// Fill this array for every matched row
$ldap_rows = array();

// Method
$auth_methods = array ('mysql' => __('Local Pandora FMS'), 'ldap' => __('ldap'));
if (enterprise_installed()) {
	add_enterprise_auth_methods($auth_methods);
}
$row = array();
$row['name'] = __('Authentication method');
$row['control'] = html_print_select($auth_methods, 'auth', $config['auth'], '', '', 0, true);
$table->data['auth'] = $row;

// Fallback to local authentication
$row = array();
$row['name'] = __('Fallback to local authentication')
	. ui_print_help_tip(__("Enable this option if you want to fallback to local authentication when remote (ldap etc...) authentication failed."), true);
$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button('fallback_local_auth', 1, '', $config['fallback_local_auth'], true).'&nbsp;&nbsp;';
$row['control'] .= __('No').'&nbsp;'.html_print_radio_button('fallback_local_auth', 0, '', $config['fallback_local_auth'], true);
$table->data['fallback_local_auth'] = $row;
$remote_rows[] = 'fallback_local_auth';

// Autocreate remote users
$row = array();
$row['name'] = __('Autocreate remote users');
$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button_extended('autocreate_remote_users', 1, '', $config['autocreate_remote_users'], false, '', '', true).'&nbsp;&nbsp;';
$row['control'] .= __('No').'&nbsp;'.html_print_radio_button_extended('autocreate_remote_users', 0, '', $config['autocreate_remote_users'], false, '', '', true);
$table->data['autocreate_remote_users'] = $row;
$remote_rows[] = 'autocreate_remote_users';

// Autocreate profile
$profile_list = profile_get_profiles ();
if ($profile_list === false) {
	$profile_list = array ();
}
$row = array();
$row['name'] = __('Autocreate profile');
$row['control'] = html_print_select($profile_list, 'default_remote_profile', $config['default_remote_profile'], '', '', '', true, false, true, '', $config['autocreate_remote_users'] == 0);
$table->data['default_remote_profile'] = $row;

// Autocreate profile group
$row = array();
$row['name'] = __('Autocreate profile group');
$row['control'] = html_print_select_groups($config['id_user'], "AR", true, 'default_remote_group', $config['default_remote_group'], '', '', '', true, false, true, '', $config['autocreate_remote_users'] == 0);
$table->data['default_remote_group'] = $row;

// Autocreate profile tags
$tags = tags_get_all_tags();
$row = array();
$row['name'] = __('Autocreate profile tags');
$row['control'] = html_print_select($tags, 'default_assign_tags[]', explode(',', $config['default_assign_tags']), '', __('Any'), '', true, true);
$table->data['default_assign_tags'] = $row;

if (((int)$config['autocreate_remote_users'] === 1) && ((int)$config['ad_advanced_config'] === 1)) {
	$table->rowstyle['default_remote_profile'] = 'display:none;';
	$table->rowstyle['default_remote_group'] = 'display:none;';
	$table->rowstyle['default_assign_tags'] = 'display:none;';
}
else {
	$autocreate_rows[] = 'default_remote_profile';
	$autocreate_rows[] = 'default_remote_group';
	$autocreate_rows[] = 'default_assign_tags';
	$remote_rows[] = 'default_remote_group';
	$remote_rows[] = 'default_remote_profile';
	$remote_rows[] = 'default_assign_tags';
}

// Autocreate blacklist
$row = array();
$row['name'] = __('Autocreate blacklist') . ui_print_help_icon ('autocreate_blacklist', true);
$row['control'] = html_print_input_text('autocreate_blacklist', $config['autocreate_blacklist'], '', 60, 100, true);
$table->data['autocreate_blacklist'] = $row;
$remote_rows[] = 'autocreate_blacklist';
$autocreate_rows[] = 'autocreate_blacklist';

// Add the remote class to the remote rows
foreach ($remote_rows as $name) {
	if (!isset($table->rowclass[$name]))
		$table->rowclass[$name] = '';
	$table->rowclass[$name] .= ' ' . 'remote';
}

// Add the autocreate class to the autocreate rows
foreach ($autocreate_rows as $name) {
	if (!isset($table->rowclass[$name]))
		$table->rowclass[$name] = '';
	$table->rowclass[$name] .= ' ' . 'autocreate';
}


/* ------ LDAP ------ */

// LDAP server
$row = array();
$row['name'] = __('LDAP server');
$row['control'] = html_print_input_text('ldap_server', $config['ldap_server'], '', 30, 100, true);
$table->data['ldap_server'] = $row;
$ldap_rows[] = 'ldap_server';

// LDAP port
$row = array();
$row['name'] = __('LDAP port');
$row['control'] = html_print_input_text('ldap_port', $config['ldap_port'], '', 10, 100, true);
$table->data['ldap_port'] = $row;
$ldap_rows[] = 'ldap_port';

// LDAP version
$ldap_versions = array (1 => 'LDAPv1', 2 => 'LDAPv2', 3 => 'LDAPv3');
$row = array();
$row['name'] = __('LDAP version');
$row['control'] = html_print_select($ldap_versions, 'ldap_version', $config['ldap_version'], '', '', 0, true);
$table->data['ldap_version'] = $row;
$ldap_rows[] = 'ldap_version';

// Start TLS
$row = array();
$row['name'] = __('Start TLS');
$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button ('ldap_start_tls', 1, '', $config['ldap_start_tls'], true).'&nbsp;&nbsp;';
$row['control'] .= __('No').'&nbsp;'.html_print_radio_button ('ldap_start_tls', 0, '', $config['ldap_start_tls'], true);
$table->data['ldap_start_tls'] = $row;
$ldap_rows[] = 'ldap_start_tls';

// Base DN
$row = array();
$row['name'] = __('Base DN');
$row['control'] = html_print_input_text ('ldap_base_dn', $config['ldap_base_dn'], '', 60, 100, true);
$table->data['ldap_base_dn'] = $row;
$ldap_rows[] = 'ldap_base_dn';

// Login attribute
$row = array();
$row['name'] = __('Login attribute');
$row['control'] = html_print_input_text ('ldap_login_attr', $config['ldap_login_attr'], '', 60, 100, true);
$table->data['ldap_login_attr'] = $row;
$ldap_rows[] = 'ldap_login_attr';

// Add the ldap class to the LDAP rows
foreach ($ldap_rows as $name) {
	if (!isset($table->rowclass[$name]))
		$table->rowclass[$name] = '';
	$table->rowclass[$name] = ' ' . 'ldap';
}

$auth_methods_added[] = 'ldap';

// Add enterprise authentication options
if (enterprise_installed()) {
	$enterprise_auth_options_added = add_enterprise_auth_options($table);
	
	array_merge($auth_methods_added, $enterprise_auth_options_added);
}

// Enable double authentication
// Set default value
set_unless_defined($config['double_auth_enabled'], false);
$row = array();
$row['name'] = __('Double authentication')
	. ui_print_help_tip(__("If this option is enabled, the users can use double authentication with their accounts"), true);
$row['control'] = __('Yes') . '&nbsp;';
$row['control'] .= html_print_radio_button('double_auth_enabled', 1, '', $config['double_auth_enabled'], true);
$row['control'] .= '&nbsp;&nbsp;';
$row['control'] .= __('No') .'&nbsp;';
$row['control'] .= html_print_radio_button('double_auth_enabled', 0, '', $config['double_auth_enabled'], true);
$table->data['double_auth_enabled'] = $row;

// Session timeout
// Default session timeout
set_when_empty ($config["session_timeout"], 90);
$row = array();
$row['name'] = __('Session timeout (mins)')
	. ui_print_help_tip(__("This is defined in minutes"), true);
$row['control'] = html_print_input_text ('session_timeout', $config["session_timeout"], '', 10, 10, true);
$table->data['session_timeout'] = $row;

// Form
echo '<form id="form_setup" method="post">';

if (!is_metaconsole()) {
	html_print_input_hidden ('update_config', 1);
}
else {
	// To use it in the metasetup
	html_print_input_hidden ('action', 'save');
	html_print_input_hidden ('hash_save_config', md5('save' . $config['dbpass']));
}

html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<script type="text/javascript">
	// Get 
	var auth_methods = $.map($('select#auth option'), function(option) {
		return option.value;
	});
	
	// Add the click event and perform it once
	// for process the action on the section load
	$('input[name="autocreate_remote_users"]').change(show_autocreate_options).change();
	
	// Add the auth select change event and perform it once
	// for process the action on the section load
	$('select#auth').change(show_selected_rows).change();
	
	// Event callback for the auth select
	function show_selected_rows (event) {
		var auth_method = $(this).val();

		if (auth_method !== 'mysql') {
			$('tr.remote').show();
			if (auth_method == 'saml') {
				$('tr#table2-autocreate_remote_users').hide();
			}
			show_autocreate_options(null);
		}
		else {
			$('tr.remote').hide();
		}	
		// Hide all the auth methods (except mysql)
		_.each(auth_methods, function(value, key) {
			if (value !== 'mysql')
				$('tr.' + value).hide();
		});

		// Show the selected auth method
		$('tr.' + auth_method).show();

	}
	
	// Event callback for the autocreate remote users radio buttons
	function show_autocreate_options (event) {
		var remote_auto = $('input:radio[name=autocreate_remote_users]:checked').val();
		var disabled = false;
		
		if (remote_auto == 0)
			disabled = true;
		
		$('select#default_remote_profile').prop('disabled', disabled);
		$('select#default_remote_group').prop('disabled', disabled);
		$('select#default_assign_tags').prop('disabled', disabled);
		$('input#text-autocreate_blacklist').prop('disabled', disabled);
		
		// Show when disabled = false and hide when disabled = true
		if (disabled)
			$('tr.autocreate').hide();
		else
			$('tr.autocreate').show();
	}
</script>
