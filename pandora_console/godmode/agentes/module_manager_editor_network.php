<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

$isFunctionPolicies = enterprise_include_once('include/functions_policies.php');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$page = get_parameter('page', '');
if (strstr($page, "policy_modules") === false) {
	if ($config['enterprise_installed']) {
		$disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
	}
	else
		$disabledBecauseInPolicy = false;
	if ($disabledBecauseInPolicy)
		$disabledTextBecauseInPolicy = 'disabled = "disabled"';
}

define ('ID_NETWORK_COMPONENT_TYPE', 2);

if (empty ($update_module_id)) {
	/* Function in module_manager_editor_common.php */
	add_component_selection (ID_NETWORK_COMPONENT_TYPE);
}
else {
	/* TODO: Print network component if available */
}

$extra_title = __('Network server module');

$data = array ();
$data[0] = __('Target IP');
$data[1] = html_print_input_text ('ip_target', $ip_target, '', 15, 60, true);
$data[2] = __('Port');
$data[3] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 20, true, $disabledBecauseInPolicy);

push_table_simple ($data, 'target_ip');

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$data = array ();
$data[0] = __('SNMP community');
$adopt = false;
if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK && isset($id_agent_module)) {
	$adopt = policies_is_module_adopt($id_agent_module);
}
if (!$adopt) {
	$data[1] = html_print_input_text ('snmp_community', $snmp_community, '', 15, 60, true, $disabledBecauseInPolicy);
}
else {
	$data[1] = html_print_input_text ('snmp_community', $snmp_community, '', 15, 60, true, false);
}

$data[2] = _('SNMP version');

if ($id_module_type >= 15 && $id_module_type <= 18) {
	$data[3] = html_print_select ($snmp_versions, 'snmp_version', $snmp_version,
		'', '', '', true, false, false, '', $disabledBecauseInPolicy);
}
else {
	$data[3] = html_print_select ($snmp_versions, 'snmp_version', 0, '', '',
		'', true, false, false, '', $disabledBecauseInPolicy);
}

push_table_simple ($data, 'snmp_1');

$data = array ();
$data[0] = __('SNMP OID');
$data[1] = '<span class="left"; style="width: 50%">';
$data[1] .= html_print_input_text ('snmp_oid', $snmp_oid, '', 30, 255, true, $disabledBecauseInPolicy);
$data[1] .= '<span class="invisible" id="oid">';
$data[1] .= html_print_select (array (), 'select_snmp_oid', $snmp_oid, '', '', 0, true, false, false, '', $disabledBecauseInPolicy);
$data[1] .= html_print_image("images/edit.png", true, array("class" => "invisible clickable", "id" => "edit_oid"));
$data[1] .= '</span>';
$data[1] .= '<span id="no_snmp" class="error invisible">'.__('Unable to do SNMP walk').'</span>';
$data[1] .= '</span> <span class="right" style="width: 50%; text-align: right"><span id="oid_loading" class="invisible">';
$data[1] .= html_print_image('images/spinner.gif', true);
$data[1] .= '</span>';
if ($disabledBecauseInPolicy)
	$disableButton = true;
else
	if ($ip_target == '')
		$disableButton = true;
	else
		$disableButton = false;

$data[1] .= html_print_button (__('SNMP walk'), 'snmp_walk', $disableButton, '',
	'class="sub next"', true);
$data[1] .= ui_print_help_icon ('snmpwalk', true);
$data[1] .= '</span>';
$table_simple->colspan['snmp_2'][1] = 3;

push_table_simple ($data, 'snmp_2');

/* Advanced stuff */
$data = array ();
$data[0] = __('TCP send') . ' ' . ui_print_help_icon ("tcp_send", true);
$data[1] = html_print_textarea ('tcp_send', 2, 65, $tcp_send, $disabledTextBecauseInPolicy, true);
$table_advanced->colspan['tcp_send'][1] = 3;

push_table_advanced ($data, 'tcp_send');

$data[0] = __('TCP receive');
$data[1] = html_print_textarea ('tcp_rcv', 2, 65, $tcp_rcv, $disabledTextBecauseInPolicy, true);
$table_advanced->colspan['tcp_receive'][1] = 3;

push_table_advanced ($data, 'tcp_receive');

if ($id_module_type >= 15 && $id_module_type <= 18) {
	/* SNMP */
	$table_advanced->rowstyle['tcp_send'] = 'display: none';
	$table_advanced->rowstyle['tcp_receive'] = 'display: none';
}
elseif ($id_module_type >= 8 && $id_module_type <= 11) {
	/* TCP or ICMP */
	$table_simple->rowstyle['snmp_1'] = 'display: none';
	$table_simple->rowstyle['snmp_2'] = 'display: none';
}
elseif (empty ($update_module_id)) {
	$table_advanced->rowstyle['tcp_send'] = 'display: none';
	$table_advanced->rowstyle['tcp_receive'] = 'display: none';
	$table_simple->rowstyle['snmp_1'] = 'display: none';
	$table_simple->rowstyle['snmp_2'] = 'display: none';
}

//For a policy
if (!isset($id_agent_module)) {
	$snmp3_auth_user = '';
	$snmp3_auth_pass = '';
	$snmp_version = 1;
	$snmp3_privacy_method = '';
	$snmp3_privacy_pass = '';
	$snmp3_auth_method = '';
	$snmp3_security_level = '';
}
else if ($id_agent_module === false) {
	$snmp3_auth_user = '';
	$snmp3_auth_pass = '';
	$snmp_version = 1;
	$snmp3_privacy_method = '';
	$snmp3_privacy_pass = '';
	$snmp3_auth_method = '';
	$snmp3_security_level = '';
}

$data = array();
$data[0] = __('Auth user');
$data[1] = html_print_input_text ('snmp3_auth_user', $snmp3_auth_user, '', 15, 60, true);
$data[2] = __('Auth password') . ui_print_help_tip(__("The pass length must be eight character minimum."), true);
$data[3] = html_print_input_text ('snmp3_auth_pass', $snmp3_auth_pass, '', 15, 60, true);
$data[3] .= html_print_input_hidden('active_snmp_v3', 0, true);
if ($snmp_version != 3) $table_simple->rowstyle['field_snmpv3_row1'] = 'display: none;';
push_table_simple($data, 'field_snmpv3_row1');

$data = array();
$data[0] = __('Privacy method');
$data[1] = html_print_select(array('DES' => __('DES'), 'AES' => __('AES')), 'snmp3_privacy_method', $snmp3_privacy_method, '', '', '', true);
$data[2] = __('Privacy pass') . ui_print_help_tip(__("The pass length must be eight character minimum."), true);
$data[3] = html_print_input_text ('snmp3_privacy_pass', $snmp3_privacy_pass, '', 15, 60, true);
if ($snmp_version != 3) $table_simple->rowstyle['field_snmpv3_row2'] = 'display: none;';
push_table_simple($data, 'field_snmpv3_row2');

$data = array();
$data[0] = __('Auth method');
$data[1] = html_print_select(array('MD5' => __('MD5'), 'SHA' => __('SHA')), 'snmp3_auth_method', $snmp3_auth_method, '', '', '', true);
$data[2] = __('Security level');
$data[3] = html_print_select(array('noAuthNoPriv' => __('Not auth and not privacy method'),
	'authNoPriv' => __('Auth and not privacy method'), 'authPriv' => __('Auth and privacy method')), 'snmp3_security_level', $snmp3_security_level, '', '', '', true);
if ($snmp_version != 3) $table_simple->rowstyle['field_snmpv3_row3'] = 'display: none;';
push_table_simple($data, 'field_snmpv3_row3');

?>
<script type="text/javascript">
$(document).ready (function () {
	$("#snmp_version").change(function () {
		if (this.value == "3") {
			$("#simple-field_snmpv3_row1").attr("style", "");
			$("#simple-field_snmpv3_row2").attr("style", "");
			$("#simple-field_snmpv3_row3").attr("style", "");
			$("input[name=active_snmp_v3]").val(1);
			$("input[name=snmp_community]").attr("disabled", true);
		}
		else {
			$("#simple-field_snmpv3_row1").css("display", "none");
			$("#simple-field_snmpv3_row2").css("display", "none");
			$("#simple-field_snmpv3_row3").css("display", "none");
			$("input[name=active_snmp_v3]").val(0);
			$("input[name=snmp_community]").removeAttr('disabled');
		}
	});
	
	$("#select_snmp_oid").click (
	function () {
		$(this).css ("width", "auto"); 
		$(this).css ("min-width", "180px"); 
	});	
	
	$("#select_snmp_oid").blur (function () {
		$(this).css ("width", "180px"); 
	});
	
	$("#id_module_type").click (
	function () {
		$(this).css ("width", "auto"); 
		$(this).css ("min-width", "180px"); 
	});
	
	$("#id_module_type").blur (function () {
		$(this).css ("width", "180px"); 
	});
	
});
</script>