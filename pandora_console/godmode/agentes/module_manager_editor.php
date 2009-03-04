<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

if (is_ajax ()) {
	$get_network_component = (bool) get_parameter ('get_network_component');
	$snmp_walk = (bool) get_parameter ('snmp_walk');
	$get_module_component = (bool) get_parameter ('get_module_component');
	$get_module_components = (bool) get_parameter ('get_module_components');
	
	if ($get_module_component) {
		$id_component = (int) get_parameter ('id_module_component');
		
		$component = get_db_row ('tnetwork_component', 'id_nc', $id_component);
		
		echo json_encode ($component);
		return;
	}
	
	if ($get_module_components) {
		require_once ('include/functions_modules.php');
		$id_module_group = (int) get_parameter ('id_module_component_group');
		$id_module_component = (int) get_parameter ('id_module_component_type');
		
		$components = get_network_components ($id_module_component,
			array ('id_group' => $id_module_group,
				'order' => 'name ASC'),
			array ('id_nc', 'name'));
		
		echo json_encode ($components);
		return;
	}
	
	if ($snmp_walk) {
		$ip_target = (string) get_parameter ('ip_target');
		$snmp_community = (string) get_parameter ('snmp_community');
		
		snmp_set_quick_print (1);
		$snmpwalk = @snmprealwalk ($ip_target, $snmp_community, NULL);
		if ($snmpwalk === false) {
			echo json_encode ($snmpwalk);
			return;
		}
		
		$result = array ();
		foreach ($snmpwalk as $id => $value) {
			$value = substr ($id, 0, 35)." - ".substr ($value, 0, 20);
			$result[$id] = substr ($value, 0, 55);
		}
		asort ($result);
		echo json_encode ($result);
		return;
	}
	
	return;
}

if (!isset ($id_agente)) {
	die ("Not Authorized");
}

require_once ("include/functions_exportserver.php");

// Using network component to fill some fields
if ($id_agent_module) {
	$module = get_agentmodule ($id_agent_module);
	$moduletype = $module['id_modulo'];
	$name = $module['nombre'];
	$description = $module['descripcion'];
	$id_module_group = $module['id_module_group'];
	$id_module_type = $module['id_tipo_modulo'];
	$max = $module['max'];
	$min = $module['min'];
	$interval = $module['module_interval'];
	if ($interval == 0) {
		$interval = get_agent_interval ($id_agente);
	}
	$tcp_port = $module['tcp_port'];
	$tcp_send = $module['tcp_send'];
	$tcp_rcv = $module['tcp_rcv'];
	$snmp_community = $module['snmp_community'];
	$snmp_oid = $module['snmp_oid'];
	$ip_target = $module['ip_target'];
	if (empty ($ip_target)) {
		$ip_target = get_agent_address ($id_agente);
	}
	$disabled = $module['disabled'];
	$id_export = $module['id_export'];
	$plugin_user = $module['plugin_user'];
	$plugin_pass = $module['plugin_pass'];
	$plugin_parameter = $module['plugin_parameter'];
	$id_plugin = $module['id_plugin'];
	$post_process = $module['post_process'];
	$prediction_module = $module['prediction_module'];
	$max_timeout = $module['max_timeout'];
	$custom_id = $module['custom_id'];
	$history_data = $module['history_data'];
	$min_warning = $module['min_warning'];
	$max_warning = $module['max_warning'];
	$min_critical = $module['min_critical'];
	$max_critical = $module['max_critical'];
	$ff_event = $module['min_ff_event'];
} else {
	$moduletype = (string) get_parameter ('moduletype');
	
	// Clean up specific network modules fields
	$name = '';
	$description = '';
	$id_module_group = 1;
	$id_module_type = 1;
	$post_process = '';
	$max_timeout = '';
	$min = '';
	$max = '';
	$interval = '';
	$prediction_module = '';
	$id_plugin = '';
	$id_export = '';
	$disabled = "0";
	$tcp_send = '';
	$tcp_rcv = '';
	$tcp_port = '';
	
	if ($moduletype == "wmiserver")
		$snmp_community = '';
	else
		$snmp_community = "public";
	$snmp_oid = '';
	$ip_target = get_agent_address ($id_agente);
	$plugin_user = '';
	$plugin_pass = '';
	$plugin_parameter = '';
	$custom_id = '';
	$history_data = 1;
	$min_warning = 0;
	$max_warning = 0;
	$min_critical = 0;
	$max_critical = 0;
	$ff_event = 0;
}

switch ($moduletype) {
case "dataserver":
	$moduletype = 1;
case 1:
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_data.php');
	break;
case "networkserver":
	$moduletype = 2;
case 2:
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_network.php');
	break;
case "pluginserver":
	$moduletype = 3;
case 3:
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_plugin.php');
	break;
case "predictionserver":
	$moduletype = 4;
case 4:
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_prediction.php');
	break;
case "wmiserver":
	$moduletype = 5;
case 5:
	require ('module_manager_editor_common.php');
	require ('module_manager_editor_wmi.php');
	break;
default:
	echo '<h3 class="error">DEBUG: Invalid module type specified in '.__FILE__.':'.__LINE__.'</h3>';
	echo 'Most likely you have recently upgraded from an earlier version of Pandora and either <br />
		1) forgot to use the database converter<br />
		2) used a bad version of the database converter (see Bugreport #2124706 for the solution)<br />
		3) found a new bug - please report a way to duplicate this error';
	return; //We return control to the invoking script so the page finishes rendering
}

echo '<h3>'.__('Module assignment');
if (isset ($extra_title))
	echo ' - '.$extra_title;
echo '</h3>';

echo '<h3 id="message" class="error invisible"></h3>';

echo '<form method="post" id="module_form">';
print_table ($table_simple);

echo '<a href="#" id="show_advanced">'.__('Advanced options').' &raquo; </a>';

echo '<div id="advanced" style="display: none">';
print_table ($table_advanced);
echo '</div>';

// Submit
echo '<div class="action-buttons" style="width: '.$table_simple->width.'">';
if ($id_agent_module) {
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	print_input_hidden ('update_module', 1);
	print_input_hidden ('id_agent_module', $id_agent_module);
} else {
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
	print_input_hidden ('id_module', $moduletype);
	print_input_hidden ('create_module', 1);
}
echo '</div>';
echo '</form>';

require_jquery_file ('ui');
require_jquery_file ('form');
?>

<script language="javascript">
/* Modules ids to check types */
var icmp = Array (6, 7);
var tcp = Array (8, 9, 10, 11);
var snmp = Array (15, 16, 17, 18);

$(document).ready (function () {
	$("#id_module_type").change (function () {
		if (icmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2, tr#advanced-tcp_send, tr#advanced-tcp_receive").hide ();
			$("#text-tcp_port").attr ("disabled", "1");
		} else if (snmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").show ();
			$("tr#advanced-tcp_send, tr#advanced-tcp_receive").hide ();
			$("#text-tcp_port").removeAttr ("disabled");
		} else if (tcp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").hide ();
			$("tr#advanced-tcp_send, tr#advanced-tcp_receive").show ();
			$("#text-tcp_port").removeAttr ("disabled");
		}
	});
	
	$("#network_component_group").change (function () {
		var $select = $("#network_component").hide ();
		$("#component").hide ();
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error, #no_component").hide ();
		$("option[value!=0]", $select).remove ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_components" : 1,
			"id_module_component_group" : this.value,
			"id_module_component_type" : $("#hidden-id_module_component_type").attr ("value")
			},
			function (data, status) {
				if (data == false) {
					$("#component_loading").hide ();
					$("span#no_component").show ();
					return;
				}
				jQuery.each (data, function (i, val) {
					option = $("<option></option>")
						.attr ("value", val['id_nc'])
						.append (val['name']);
					$select.append (option);
				});
				$("#component_loading").hide ();
				$select.show ();
				$("#component").show ();
			},
			"json"
		);
	});
	
	$("#network_component").change (function () {
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error").hide ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_component" : 1,
			"id_module_component" : this.value
			},
			function (data, status) {
				$("#text-name").attr ("value", data["name"]);
				$("#textarea_description").attr ("value", data["description"]);
				$("#id_module_type option[value="+data["type"]+"]").select (1);
				$("#text-max").attr ("value", data["max"]);
				$("#text-min").attr ("value", data["min"]);
				$("#text-module_interval").attr ("value", data["module_interval"]);
				$("#text-tcp_port").attr ("value", data["tcp_port"]);
				$("#textarea_tcp_send").attr ("value", data["tcp_send"]);
				$("#textarea_tcp_rcv").attr ("value", data["tcp_rcv"]);
				$("#text-snmp_community").attr ("value", data["snmp_community"]);
				$("#text-snmp_oid").attr ("value", data["snmp_oid"]).show ();
				$("#oid, img#edit_oid").hide ();
				$("#id_module_group option["+data["id_group"]+"]").select (1);
				//$("#id_module_group").attr ("value", data["id_module_group"]);
				//$("#text_plugin_user").attr ("value", data["plugin_user"]);
				//$("#text_plugin_pass").attr ("value", data["plugin_pass"]);
				//$("#text_plugin_parameter").attr ("value", data["plugin_parameter"]);
				$("#max_timeout").attr ("value", data["max_timeout"]);
				if (data["history_data"])
					$("#checkbox-history_data").check ();
				else
					$("#checkbox-history_data").uncheck ();
				$("#text-min_warning").attr ("value", (data["min_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-max_warning").attr ("value", (data["max_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-min_critical").attr ("value", (data["min_critical"] == 0) ? 0 : data["min_critical"]);
				$("#text-max_critical").attr ("value", (data["max_critical"] == 0) ? 0 : data["max_critical"]);
				$("#text-ff_threshold").attr ("value", (data["min_ff_event"] == 0) ? 0 : data["min_ff_event"]);
				$("#component_loading").hide ();
				$("#id_module_type").change ();
			},
			"json"
		);
	});
	
	$("#text-ip_target").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		} else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#button-snmp_walk").click (function () {
		$(this).disable ();
		$("#oid_loading").show ();
		$("span.error").hide ();
		$("#select_snmp_oid").empty ().hide ();
		$("#text-snmp_oid").hide ().attr ("value", "");
		$("span#oid").show ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"snmp_walk" : 1,
			"ip_target" : $("#text-ip_target").fieldValue (),
			"snmp_community" : $("#text-snmp_community").fieldValue ()
			},
			function (data, status) {
				if (data == false) {
					$("span#no_snmp").show ();
					$("#oid_loading").hide ();
					$("#edit_oid").hide ();
					return false;
				}
				jQuery.each (data, function (id, value) {
					opt = $("<option></option>").attr ("value", id).html (value);
					$("#select_snmp_oid").append (opt);
				});
				$("#select_snmp_oid").show ();
				$("#oid_loading").hide ();
				$("#button-snmp_walk").enable ();
				$("#edit_oid").show ();
				$("#button-snmp_walk").enable ();
			},
			"json"
		);
	});
	
	$("img#edit_oid").click (function () {
		$("#oid").hide ();
		$("#text-snmp_oid").show ()
			.attr ("value", $("#select_snmp_oid").fieldValue ());
		$(this).hide ();
	});
	
	$("form#module_form").submit (function () {
		if ($("#text-name").attr ("value") == "") {
			$("#text-name").pulsate ().focus ();
			$("#message").showMessage ("<?php echo __('No module name provided') ?>");
			return false;
		}
		
		module = $("#id_module_type").attr ("value");
		
		if (icmp.in_array (module) || tcp.in_array (module) || snmp.in_array (module)) {
			/* Network module */
			if ($("#text-ip_target").attr ("value") == "") {
				$("#text-ip_target").pulsate ().focus ();
				$("#message").showMessage ("<?php echo __('No target IP provided') ?>");
				return false;
			}
		}
		
		if (snmp.in_array (module)) {
			if ($("#text-snmp_oid").attr ("value") == "") {
				if ($("#select_snmp_oid").attr ("value") == "") {
					$("#message").showMessage ("<?php echo __('No SNMP OID provided') ?>");
					return false;
				}
			}
		}
		
		$("#message").hide ();
		return true;
	});
	
	$("a#show_advanced").click (function () {
		$("div#advanced").show ();
		$(this).remove ();
		return false;
	});
});
</script>
