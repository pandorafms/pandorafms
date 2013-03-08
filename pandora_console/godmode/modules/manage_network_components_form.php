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

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

$create_network_from_module = get_parameter('create_network_from_module');
$pure = get_parameter('pure', 0);

if ($create_network_from_module) {

	$id_agentmodule = get_parameter('create_module_from');
	$data_module = db_get_row_filter('tagente_modulo', array ('id_agente_modulo' => $id_agentmodule));
	
	$name = $data_module["nombre"];
	$description = $data_module["descripcion"];
	$type = $data_module["id_tipo_modulo"];
	$max = $data_module["max"];
	$min = $data_module["min"];
	$module_interval = $data_module["module_interval"];
	$tcp_port = $data_module["tcp_port"];
	$tcp_rcv = $data_module["tcp_rcv"];
	$tcp_send = $data_module["tcp_send"];
	$snmp_community = $data_module["snmp_community"];
	$snmp_oid = $data_module["snmp_oid"];
	$id_module_group = $data_module["id_module_group"];
	$id_plugin = $data_module["id_plugin"];
	$plugin_user = $data_module["plugin_user"];
	$plugin_pass = $data_module["plugin_pass"];
	$plugin_parameter = $data_module["plugin_parameter"];
	$macros = $data_module["macros"];
	$max_timeout = $data_module["max_timeout"];
	$max_retries = $data_module["max_retries"];
	$min_warning = $data_module["min_warning"];
	$max_warning = $data_module["max_warning"];
	$str_warning = $data_module["str_warning"];
	$max_critical = $data_module["max_critical"];
	$min_critical = $data_module["min_critical"];
	$str_critical = $data_module["str_critical"];
	$ff_event = $data_module["min_ff_event"];
	$history_data = $data_module["history_data"];
	$post_process = $data_module["post_process"];
	$unit = $data_module["unit"];
	$wizard_level = $data_module["wizard_level"];
	$critical_instructions = $data_module["critical_instructions"];
	$warning_instructions = $data_module["warning_instructions"];
	$unknown_instructions = $data_module["unknown_instructions"];
	$critical_inverse = $data_module["critical_inverse"];
	$warning_inverse = $data_module["warning_inverse"];
	$id_category = $data_module["id_category"];
}

$id_component_type = (int) get_parameter ('id_component_type');
if ($create_network_from_module) {
	$id_component_type = 2;
}

if (isset ($id)) {
	$component = network_components_get_network_component ((int) $id);
	if ($component !== false) {
		$id_component_type = $component['id_modulo'];
		$name = $component["name"];
		$type = $component["type"];
		$description = $component["description"];
		$max = $component["max"];
		$min = $component["min"];
		$module_interval = $component["module_interval"];
		$tcp_port = $component["tcp_port"];
		$tcp_rcv = $component["tcp_rcv"];
		$tcp_send = $component["tcp_send"];
		$snmp_community = $component["snmp_community"];
		$snmp_oid = $component["snmp_oid"];
		$id_module_group = $component["id_module_group"];
		$id_group = $component["id_group"];
		$id_plugin = $component['id_plugin'];
		$plugin_user = $component["plugin_user"];
		$plugin_pass = $component["plugin_pass"];
		$plugin_parameter = $component["plugin_parameter"];
		$macros = $component["macros"];
		$max_timeout = $component["max_timeout"];
		$max_retries = $component["max_retries"];
		$min_warning = $component["min_warning"];
		$max_warning = $component["max_warning"];
		$str_warning = $component["str_warning"];
		$max_critical = $component["max_critical"];
		$min_critical = $component["min_critical"];
		$str_critical = $component["str_critical"];
		$ff_event = $component["min_ff_event"];
		$history_data = $component["history_data"];
		$post_process = $component["post_process"];
		$unit = $component["unit"];
		$wizard_level = $component["wizard_level"];
		$critical_instructions = $component["critical_instructions"];
		$warning_instructions = $component["warning_instructions"];
		$unknown_instructions = $component["unknown_instructions"];
		$critical_inverse = $component["critical_inverse"];
		$warning_inverse = $component["warning_inverse"];
		$id_category = $component["id_category"];
		$tags = $component["tags"];
		
		if ($type >= 15 && $type <= 18) {
			// New support for snmp v3
			$snmp_version = $component["tcp_send"];
			$snmp3_auth_user = $component["plugin_user"];
			$snmp3_auth_pass = $component["plugin_pass"];
			$snmp3_auth_method = $component["plugin_parameter"];
			$snmp3_privacy_method = $component["custom_string_1"];
			$snmp3_privacy_pass = $component["custom_string_2"];
			$snmp3_security_level = $component["custom_string_3"];
		}
	}
	elseif (isset ($new_component) && $new_component) {
		$name = "";
		$snmp_oid = "";
		$description = "";
		$id_group = 1;
		$oid = "";
		$max = "0";
		$min = "0";
		$module_interval = "300";
		$tcp_port = "";
		$tcp_rcv = "";
		$tcp_send = "";
		$snmp_community = "";
		$id_module_group = "";
		if ($id_component_type == 6) $id_group = 14;
		else $id_group = "";
		$type = 0;
		$min_warning = 0;
		$max_warning = 0;
		$str_warning = '';
		$max_critical = 0;
		$min_critical = 0;
		$str_critical = '';
		$ff_event = 0;
		$history_data = true;
		$post_process = 0;
		$unit = '';
		$wizard_level = 'nowizard';
		$critical_instructions = '';
		$warning_instructions = '';
		$unknown_instructions = '';
		$critical_inverse = 0;
		$warning_inverse = 0;
		$id_category = 0;
		$tags = '';
		
		$snmp_version = 1;
		$snmp3_auth_user = '';
		$snmp3_auth_pass = '';
		$snmp3_privacy_method = '';
		$snmp3_privacy_pass = '';
		$snmp3_auth_method = '';
		$snmp3_security_level = '';
		
	}
}

/**
 * $id_component_type has these values:
 * 6 - Module WMI
 * 4 - Plugin component
 * 2 - network component
 * 
 * You can see this values in file godmode/modules/manage_network_components.php
 * in the last lines (in the call function "html_print_select").
 */

if ($id_component_type == 6) {
	$categories = array (0, 1, 2);
	require ($config['homedir'] . "/godmode/modules/manage_network_components_form_common.php");
	require ($config['homedir'] . "/godmode/modules/manage_network_components_form_wmi.php");
}
else if ($id_component_type == 4) {
	$categories = array (0, 1, 2);
	require ($config['homedir'] . "/godmode/modules/manage_network_components_form_common.php");
	require ($config['homedir'] . "/godmode/modules/manage_network_components_form_plugin.php");
}
else if ($id_component_type == 2 || $create_network_from_module) {
	$categories = array (3, 4, 5);
	require ($config['homedir'] . "/godmode/modules/manage_network_components_form_common.php");
	require ($config['homedir'] . "/godmode/modules/manage_network_components_form_network.php");
}
else {
	return;
}

echo '<form name="component" method="post">';

/* $table came from manage_network_components_form_common.php */
$table->colspan['description'][1] = 3;
$data = array ();
$data[0] = __('Description');
$data[1] = html_print_textarea ('description', 2, 65, $description, '', true);
push_table_row ($data, 'description');

html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('id_component_type', $id_component_type);
if ($id) {
	html_print_input_hidden ('update_component', 1);
	html_print_input_hidden ('id', $id);
	html_print_submit_button (__('Update'), 'upd', false, 'class="sub upd"');
}
else {
	html_print_input_hidden ('create_component', 1);
	html_print_input_hidden ('create_network_from_module', 0);
	html_print_submit_button (__('Create'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';

ui_require_javascript_file('pandora_modules');
?>
<script language="JavaScript" type="text/javascript">
<!--
function type_change () {
	// type 1-4 - Generic_xxxxxx
	if ((document.component.type.value > 0) && (document.component.type.value < 5)) {
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;
		document.component.tcp_send.style.background="#ddd";
		document.component.tcp_send.disabled=true;
		document.component.tcp_rcv.style.background="#ddd";
		document.component.tcp_rcv.disabled=true;
		document.component.tcp_port.style.background="#ddd";
		document.component.tcp_port.disabled=true;

		document.component.snmp_version.style.background="#ddd";
		document.component.snmp_version.disabled=true;
		document.component.snmp3_auth_user.style.background="#ddd";
		document.component.snmp3_auth_user.disabled=true;
		document.component.snmp3_auth_pass.background="#ddd";
		document.component.snmp3_auth_pass.disabled=true;
		document.component.snmp3_privacy_method.style.background="#ddd";
		document.component.snmp3_privacy_method.disabled=true;
		document.component.snmp3_privacy_pass.style.background="#ddd";
		document.component.snmp3_privacy_pass.disabled=true;
		document.component.snmp3_auth_method.style.background="#ddd";
		document.component.snmp3_auth_method.disabled=true;
		document.component.snmp3_security_level.style.background="#ddd";
		document.component.snmp3_security_level.disabled=true;
	}
	// type 15-18- SNMP
	if ((document.component.type.value > 14) && (document.component.type.value < 19 )) { 
		document.component.snmp_oid.style.background="#fff";
		document.component.snmp_oid.style.disabled=false;
		document.component.snmp_community.style.background="#fff";
		document.component.snmp_community.disabled=false;
		document.component.snmp_oid.style.background="#fff";
		document.component.snmp_oid.disabled=false;
		document.component.tcp_send.style.background="#ddd";
		document.component.tcp_send.disabled=true;
		document.component.tcp_rcv.style.background="#ddd";
		document.component.tcp_rcv.disabled=true;
		document.component.tcp_port.style.background="#fff";
		document.component.tcp_port.disabled=false;

		document.component.snmp_version.style.background="#fff";
		document.component.snmp_version.disabled=false;
		document.component.snmp3_auth_user.style.background="#fff";
		document.component.snmp3_auth_user.disabled=false;
		document.component.snmp3_auth_pass.background="#fff";
		document.component.snmp3_auth_pass.disabled=false;
		document.component.snmp3_privacy_method.style.background="#fff";
		document.component.snmp3_privacy_method.disabled=false;
		document.component.snmp3_privacy_pass.style.background="#fff";
		document.component.snmp3_privacy_pass.disabled=false;
		document.component.snmp3_auth_method.style.background="#fff";
		document.component.snmp3_auth_method.disabled=false;
		document.component.snmp3_security_level.style.background="#fff";
		document.component.snmp3_security_level.disabled=false;
	}
	// type 6-7 - ICMP
	if ((document.component.type.value == 6) || (document.component.type.value == 7)) {
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.tcp_send.style.background="#ddd";
		document.component.tcp_send.disabled=true;
		document.component.tcp_rcv.style.background="#ddd";
		document.component.tcp_rcv.disabled=true;
		document.component.tcp_port.style.background="#ddd";
		document.component.tcp_port.disabled=true;
		
		document.component.snmp_version.style.background="#ddd";
		document.component.snmp_version.disabled=true;
		document.component.snmp3_auth_user.style.background="#ddd";
		document.component.snmp3_auth_user.disabled=true;
		document.component.snmp3_auth_pass.background="#ddd";
		document.component.snmp3_auth_pass.disabled=true;
		document.component.snmp3_privacy_method.style.background="#ddd";
		document.component.snmp3_privacy_method.disabled=true;
		document.component.snmp3_privacy_pass.style.background="#ddd";
		document.component.snmp3_privacy_pass.disabled=true;
		document.component.snmp3_auth_method.style.background="#ddd";
		document.component.snmp3_auth_method.disabled=true;
		document.component.snmp3_security_level.style.background="#ddd";
		document.component.snmp3_security_level.disabled=true;
	}
	// type 8-11 - TCP
	if ((document.component.type.value > 7) && (document.component.type.value < 12)) {
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;
		document.component.tcp_send.style.background="#fff";
		document.component.tcp_send.disabled=false;
		document.component.tcp_rcv.style.background="#fff";
		document.component.tcp_rcv.disabled=false;
		document.component.tcp_port.style.background="#fff";
		document.component.tcp_port.disabled=false;
		
		document.component.snmp_version.style.background="#ddd";
		document.component.snmp_version.disabled=true;
		document.component.snmp3_auth_user.style.background="#ddd";
		document.component.snmp3_auth_user.disabled=true;
		document.component.snmp3_auth_pass.background="#ddd";
		document.component.snmp3_auth_pass.disabled=true;
		document.component.snmp3_privacy_method.style.background="#ddd";
		document.component.snmp3_privacy_method.disabled=true;
		document.component.snmp3_privacy_pass.style.background="#ddd";
		document.component.snmp3_privacy_pass.disabled=true;
		document.component.snmp3_auth_method.style.background="#ddd";
		document.component.snmp3_auth_method.disabled=true;
		document.component.snmp3_security_level.style.background="#ddd";
		document.component.snmp3_security_level.disabled=true;
	}
	// type 12 - UDP
	if (document.component.type.value == 12) {
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;
		document.component.tcp_send.style.background="#fff";
		document.component.tcp_send.disabled=false;
		document.component.tcp_rcv.style.background="#fff";
		document.component.tcp_rcv.disabled=false;
		document.component.tcp_port.style.background="#fff";
		document.component.tcp_port.disabled=false;
		
		document.component.snmp_version.style.background="#ddd";
		document.component.snmp_version.disabled=true;
		document.component.snmp3_auth_user.style.background="#ddd";
		document.component.snmp3_auth_user.disabled=true;
		document.component.snmp3_auth_pass.background="#ddd";
		document.component.snmp3_auth_pass.disabled=true;
		document.component.snmp3_privacy_method.style.background="#ddd";
		document.component.snmp3_privacy_method.disabled=true;
		document.component.snmp3_privacy_pass.style.background="#ddd";
		document.component.snmp3_privacy_pass.disabled=true;
		document.component.snmp3_auth_method.style.background="#ddd";
		document.component.snmp3_auth_method.disabled=true;
		document.component.snmp3_security_level.style.background="#ddd";
		document.component.snmp3_security_level.disabled=true;
	}
}

$(document).ready (function () {
	$("#right").click (function () {
		jQuery.each($("select[name='id_tag_available[]'] option:selected"), function (key, value) {
			tag_name = $(value).html();
			if (tag_name != <?php echo "'".__('None')."'"; ?>) {
				id_tag = $(value).attr('value');
				$("select[name='id_tag_selected[]']").append($("<option></option>").val(id_tag).html('<i>' + tag_name + '</i>'));
				$("#id_tag_available").find("option[value='" + id_tag + "']").remove();
				$("#id_tag_selected").find("option[value='']").remove();
				if($("#id_tag_available option").length == 0) {
					$("select[name='id_tag_available[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
				}
			}
		});
	});
	$("#left").click (function () {
		jQuery.each($("select[name='id_tag_selected[]'] option:selected"), function (key, value) {
				tag_name = $(value).html();
				if (tag_name != <?php echo "'".__('None')."'"; ?>) {
					id_tag = $(value).attr('value');
					$("select[name='id_tag_available[]']").append($("<option>").val(id_tag).html('<i>' + tag_name + '</i>'));
					$("#id_tag_selected").find("option[value='" + id_tag + "']").remove();
					$("#id_tag_available").find("option[value='']").remove();
					if($("#id_tag_selected option").length == 0) {
						$("select[name='id_tag_selected[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
					}
				}
		});
	});
	$("#submit-crt").click(function () {
		$('#id_tag_selected option').map(function(){
			$(this).attr('selected','selected');
		});
	});
	$("#submit-upd").click(function () {
		$('#id_tag_selected option').map(function(){
			$(this).attr('selected','selected');
		});
	});
	if ($("#snmp_version").value == "3"){
		$("input[name=snmp3_auth_user]").css({backgroundColor: '#fff'});
		$("input[name=snmp3_auth_user]").removeAttr('disabled');
		
		$("input[name=snmp3_auth_pass]").css({backgroundColor: '#fff'});
		$("input[name=snmp3_auth_pass]").removeAttr('disabled');
		
		$("#snmp3_privacy_method").css({backgroundColor: '#fff'});
		$("#snmp3_privacy_method").removeAttr('disabled');
		
		$("input[name=snmp3_privacy_pass]").css({backgroundColor: '#fff'});
		$("input[name=snmp3_privacy_pass]").removeAttr('disabled');
		
		$("#snmp3_auth_method").css({backgroundColor: '#fff'});	
		$("#snmp3_auth_method").removeAttr('disabled');
		
		$("#snmp3_security_level").css({backgroundColor: '#fff'});	
		$("#snmp3_security_level").removeAttr('disabled');
		
		$("input[name=active_snmp_v3]").val(1);
		$("input[name=snmp_community]").css({backgroundColor: '#ddd'});
		$("input[name=snmp_community]").attr("disabled",true);			
	}
	else{
		$("input[name=snmp3_auth_user]").val("");				
		$("input[name=snmp3_auth_user]").css({backgroundColor: '#ddd'});			
		$("input[name=snmp3_auth_user]").attr("disabled", true);	
		
		$("input[name=snmp3_auth_pass]").val("");
		$("input[name=snmp3_auth_pass]").css({backgroundColor: '#ddd'});			
		$("input[name=snmp3_auth_pass]").attr("disabled", true);				
		
		$("#snmp3_privacy_method").css({backgroundColor: '#ddd'});	
		$("#snmp3_privacy_method").attr("disabled", true);
		
		$("input[name=snmp3_privacy_pass]").val("");
		$("input[name=snmp3_privacy_pass]").css({backgroundColor: '#ddd'});			
		$("input[name=snmp3_privacy_pass]").attr("disabled", true);			
		
		$("#snmp3_auth_method").css({backgroundColor: '#ddd'});	
		$("#snmp3_auth_method").attr("disabled", true);
		
		$("#snmp3_security_level").css({backgroundColor: '#ddd'});	
		$("#snmp3_security_level").attr("disabled", true);
		
		$("input[name=active_snmp_v3]").val(0);
		$("input[name=snmp_community]").css({backgroundColor: '#fff'});			
		$("input[name=snmp_community]").removeAttr('disabled');
	}
	
	$("#snmp_version").change(function () {
		if (this.value == "3") {
			$("input[name=snmp3_auth_user]").css({backgroundColor: '#fff'});			
			$("input[name=snmp3_auth_user]").removeAttr('disabled');
			
			$("input[name=snmp3_auth_pass]").css({backgroundColor: '#fff'});			
			$("input[name=snmp3_auth_pass]").removeAttr('disabled');
			
			$("#snmp3_privacy_method").css({backgroundColor: '#fff'});	
			$("#snmp3_privacy_method").removeAttr('disabled');
		
			$("input[name=snmp3_privacy_pass]").css({backgroundColor: '#fff'});			
			$("input[name=snmp3_privacy_pass]").removeAttr('disabled');
			
			$("#snmp3_auth_method").css({backgroundColor: '#fff'});	
			$("#snmp3_auth_method").removeAttr('disabled');
			
			$("#snmp3_security_level").css({backgroundColor: '#fff'});	
			$("#snmp3_security_level").removeAttr('disabled');
			
			$("input[name=active_snmp_v3]").val(1);
			$("input[name=snmp_community]").css({backgroundColor: '#ddd'});
			$("input[name=snmp_community]").attr("disabled",true);	
		}
		else {
			$("input[name=snmp3_auth_user]").val("");				
			$("input[name=snmp3_auth_user]").css({backgroundColor: '#ddd'});			
			$("input[name=snmp3_auth_user]").attr("disabled", true);	
			
			$("input[name=snmp3_auth_pass]").val("");
			$("input[name=snmp3_auth_pass]").css({backgroundColor: '#ddd'});			
			$("input[name=snmp3_auth_pass]").attr("disabled", true);				
			
			$("#snmp3_privacy_method").css({backgroundColor: '#ddd'});	
			$("#snmp3_privacy_method").attr("disabled", true);
			
			$("input[name=snmp3_privacy_pass]").val("");
			$("input[name=snmp3_privacy_pass]").css({backgroundColor: '#ddd'});			
			$("input[name=snmp3_privacy_pass]").attr("disabled", true);			
			
			$("#snmp3_auth_method").css({backgroundColor: '#ddd'});	
			$("#snmp3_auth_method").attr("disabled", true);
			
			$("#snmp3_security_level").css({backgroundColor: '#ddd'});	
			$("#snmp3_security_level").attr("disabled", true);
			
			$("input[name=active_snmp_v3]").val(0);
			$("input[name=snmp_community]").css({backgroundColor: '#fff'});			
			$("input[name=snmp_community]").removeAttr('disabled');
		}
	});	

	$("#type"). change(function () {
		if ($("#snmp_version").value == "3") {
			$("input[name=snmp3_auth_user]").css({backgroundColor: '#fff'});			
			$("input[name=snmp3_auth_user]").removeAttr('disabled');
			
			$("input[name=snmp3_auth_pass]").css({backgroundColor: '#fff'});			
			$("input[name=snmp3_auth_pass]").removeAttr('disabled');
			
			$("#snmp3_privacy_method").css({backgroundColor: '#fff'});	
			$("#snmp3_privacy_method").removeAttr('disabled');
			
			$("input[name=snmp3_privacy_pass]").css({backgroundColor: '#fff'});			
			$("input[name=snmp3_privacy_pass]").removeAttr('disabled');
			
			$("#snmp3_auth_method").css({backgroundColor: '#fff'});	
			$("#snmp3_auth_method").removeAttr('disabled');
			
			$("#snmp3_security_level").css({backgroundColor: '#fff'});	
			$("#snmp3_security_level").removeAttr('disabled');
			
			$("input[name=active_snmp_v3]").val(1);
			$("input[name=snmp_community]").css({backgroundColor: '#ddd'});
			$("input[name=snmp_community]").attr("disabled",true);	
		}
		else {
			$("input[name=snmp3_auth_user]").val("");				
			$("input[name=snmp3_auth_user]").css({backgroundColor: '#ddd'});			
			$("input[name=snmp3_auth_user]").attr("disabled", true);	
			
			$("input[name=snmp3_auth_pass]").val("");
			$("input[name=snmp3_auth_pass]").css({backgroundColor: '#ddd'});			
			$("input[name=snmp3_auth_pass]").attr("disabled", true);				
			
			$("#snmp3_privacy_method").css({backgroundColor: '#ddd'});	
			$("#snmp3_privacy_method").attr("disabled", true);
			
			$("input[name=snmp3_privacy_pass]").val("");
			$("input[name=snmp3_privacy_pass]").css({backgroundColor: '#ddd'});			
			$("input[name=snmp3_privacy_pass]").attr("disabled", true);			
			
			$("#snmp3_auth_method").css({backgroundColor: '#ddd'});	
			$("#snmp3_auth_method").attr("disabled", true);
			
			$("#snmp3_security_level").css({backgroundColor: '#ddd'});	
			$("#snmp3_security_level").attr("disabled", true);
			
			$("input[name=active_snmp_v3]").val(0);
			$("input[name=snmp_community]").css({backgroundColor: '#fff'});			
			$("input[name=snmp_community]").removeAttr('disabled');
		}
	});
});

<?php if ($id_component_type == 2) :?>
type_change ();
<?php endif; ?>
//-->
</script>
