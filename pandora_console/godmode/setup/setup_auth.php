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

// Load enterprise extensions
enterprise_include ('godmode/setup/setup_auth.php');

if (is_ajax ()) {
	$change_auth_metod = (bool) get_parameter ('change_auth_metod');
	
	if($change_auth_metod){
		$table = new StdClass();
		$table->data = array ();
		$table->width = '100%';
		$table->class = 'databox filters table_result_auth';
		$table->size['name'] = '30%';
		$table->style['name'] = "font-weight: bold";

		$type_auth = (string) get_parameter ('type_auth', '');

		//field for all types except mysql
		if($type_auth != 'mysql'){
			// Fallback to local authentication
			$row = array();
			$row['name'] = __('Fallback to local authentication')
				. ui_print_help_tip(__("Enable this option if you want to fallback to local authentication when remote (ldap etc...) authentication failed."), true);
			$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button('fallback_local_auth', 1, '', $config['fallback_local_auth'], true).'&nbsp;&nbsp;';
			$row['control'] .= __('No').'&nbsp;'.html_print_radio_button('fallback_local_auth', 0, '', $config['fallback_local_auth'], true);
			$table->data['fallback_local_auth'] = $row;

			// Autocreate remote users
			$row = array();
			$row['name'] = __('Autocreate remote users');
			$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button_extended('autocreate_remote_users', 1, '', $config['autocreate_remote_users'], false, '', '', true).'&nbsp;&nbsp;';
			$row['control'] .= __('No').'&nbsp;'.html_print_radio_button_extended('autocreate_remote_users', 0, '', $config['autocreate_remote_users'], false, '', '', true);
			$table->data['autocreate_remote_users'] = $row;
		}

		switch ($type_auth) {
			case "mysql":
			break;

			case "ldap":
				// LDAP server
				$row = array();
				$row['name'] = __('LDAP server');
				$row['control'] = html_print_input_text('ldap_server', $config['ldap_server'], '', 30, 100, true);
				$table->data['ldap_server'] = $row;

				// LDAP port
				$row = array();
				$row['name'] = __('LDAP port');
				$row['control'] = html_print_input_text('ldap_port', $config['ldap_port'], '', 10, 100, true);
				$table->data['ldap_port'] = $row;

				// LDAP version
				$ldap_versions = array (1 => 'LDAPv1', 2 => 'LDAPv2', 3 => 'LDAPv3');
				$row = array();
				$row['name'] = __('LDAP version');
				$row['control'] = html_print_select($ldap_versions, 'ldap_version', $config['ldap_version'], '', '', 0, true);
				$table->data['ldap_version'] = $row;

				// Start TLS
				$row = array();
				$row['name'] = __('Start TLS');
				$row['control'] = __('Yes').'&nbsp;'.html_print_radio_button ('ldap_start_tls', 1, '', $config['ldap_start_tls'], true).'&nbsp;&nbsp;';
				$row['control'] .= __('No').'&nbsp;'.html_print_radio_button ('ldap_start_tls', 0, '', $config['ldap_start_tls'], true);
				$table->data['ldap_start_tls'] = $row;

				// Base DN
				$row = array();
				$row['name'] = __('Base DN');
				$row['control'] = html_print_input_text ('ldap_base_dn', $config['ldap_base_dn'], '', 60, 100, true);
				$table->data['ldap_base_dn'] = $row;

				// Login attribute
				$row = array();
				$row['name'] = __('Login attribute');
				$row['control'] = html_print_input_text ('ldap_login_attr', $config['ldap_login_attr'], '', 60, 100, true);
				$table->data['ldap_login_attr'] = $row;
			break;

			case 'pandora':
			case 'babel':
			case 'ad':
			case 'saml':
			case 'integria':
				// Add enterprise authentication options
				if (enterprise_installed()) {
					add_enterprise_auth_options($table, $type_auth);
				}
			break;
		}

		// field for all types
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
			. ui_print_help_tip(__("This is defined in minutes, If you wish a permanent session should putting -1 in this field."), true);
		$row['control'] = html_print_input_text ('session_timeout', $config["session_timeout"], '', 10, 10, true);
		$table->data['session_timeout'] = $row;

	html_print_table($table);
	return;
	}
}

include_once($config['homedir'] . "/include/functions_profile.php");

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
$no_autocreate_rows = array();

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
echo '<div id="table_auth_result"></div>';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<script type="text/javascript">
	$('#auth').on('change', function(){
		type_auth = $('#auth').val();
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: "page=godmode/setup/setup_auth&change_auth_metod=1&type_auth=" + type_auth,
			dataType: "html",
			success: function(data) {
				$('.table_result_auth').remove();
				$('#table_auth_result').append(data);
			}
		});
	}).change();
</script>
