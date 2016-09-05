<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;
include_once($config['homedir'] . "/include/functions_agents.php");
require_once ('include/functions_modules.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_reporting.php');
require_once ('include/functions_network_components.php');
require_once ('include/functions_wmi.php');
require_once ('include/graphs/functions_utils.php');

check_login ();

$ip_target = (string) get_parameter ('ip_target', $ipAgent); // Host
$plugin_user = (string) get_parameter ('plugin_user', 'Administrator'); // Username
$plugin_pass = io_safe_output(get_parameter('plugin_pass', '')); // Password
$tcp_send = (string) get_parameter ('tcp_send'); // Namespace

//See if id_agente is set (either POST or GET, otherwise -1
$id_agent = $idAgent;

// Get passed variables
$wmiexplore = (int) get_parameter("wmiexplore", 0);
$create_modules = (int) get_parameter("create_modules", 0);

$interfaces = array();

$wmi_client = 'wmic';

if ($wmiexplore) {
	$wmi_command = wmi_compose_query($wmi_client, $plugin_user, $plugin_pass, $ip_target, $tcp_send);
	
	$processes = array();
	$services = array();
	$disks = array();
	$network_component_groups = array();
	
	// Processes
	$wmi_processes = $wmi_command . ' "select Name from Win32_Process"';
	$processes_name_field = 1;
	
	exec($wmi_processes, $output);
	
	$fail = false;
	if (preg_match('/^Failed/', $output[0])) {
		$fail = true;
	}
	
	if (!$fail) {
		foreach ($output as $index => $row) {
			// First and second rows are Class and column names, ignore it
			if ($index < 2) {
				continue;
			}
			$row_exploded = explode('|', $row);
			
			if (!in_array($row_exploded[$processes_name_field], $processes)) {
				$processes[$row_exploded[$processes_name_field]] = $row_exploded[$processes_name_field];
			}
		}
		unset($output);
		
		// Services
		$wmi_services = $wmi_command . ' "select Name from Win32_Service"';
		$services_name_field = 0;
		$services_check_field = 1;
		
		exec($wmi_services, $output);
		
		foreach ($output as $index => $row) {
			// First and second rows are Class and column names, ignore it
			if ($index < 2) {
				continue;
			}
			$row_exploded = explode('|', $row);
			
			if (!in_array($row_exploded[$services_name_field], $services)) {
				$services[$row_exploded[$services_name_field]] = $row_exploded[$services_name_field];
			}
		}
		unset($output);
		
		// Disks
		$wmi_disks = $wmi_command . ' "Select DeviceID from Win32_LogicalDisk"';
		$disks_name_field = 0;
		
		exec($wmi_disks, $output);
		
		foreach ($output as $index => $row) {
			// First and second rows are Class and column names, ignore it
			if ($index < 2) {
				continue;
			}
			$row_exploded = explode('|', $row);
			
			if (!in_array($row_exploded[$disks_name_field], $services)) {
				$disk_string = sprintf(__('Free space on %s'), $row_exploded[$disks_name_field]);
				$disks[$row_exploded[$disks_name_field]] = $disk_string;
			}
		}
		unset($output);
	
		// WMI Components
		$network_component_groups = network_components_get_groups(MODULE_WMI);
	}
}

if ($create_modules) {
	$modules = get_parameter("module", array());
	
	$services = array();
	$processes = array();
	$disks = array();
	$components = array();
	
	foreach ($modules as $module) {
		// Split module data to get type
		$module_exploded = explode('_', $module);
		$type = $module_exploded[0];
		
		// Delete type from module data
		unset($module_exploded[0]);
		
		// Rebuild module data
		$module = implode('_', $module_exploded);
		
		switch($type) {
			case 'service':
				$services[] = $module;
				break;
			case 'process':
				$processes[] = $module;
				break;
			case 'disk':
				$disks[] = $module;
				break;
			case 'component':
				$components[] = $module;
				break;
		}
	}
	
	// Common values for WMI modules
	$values = array(
				'ip_target' => $ip_target,
				'tcp_send' => $tcp_send,
				'plugin_user' => $plugin_user,
				'plugin_pass' => $plugin_pass,
				'id_modulo' => MODULE_WMI);
	
	// Create Service modules
	$services_values = $values;
	
	$services_values['snmp_community'] = 'Running'; // Key string
	$services_values['tcp_port'] = 1; // Field number (Running/Stopped)
	$services_values['id_tipo_modulo'] = 2; // Generic boolean
	
	$services_result = wmi_create_wizard_modules($id_agent, $services, 'services', $services_values);
	
	// Create Process modules
	$processes_values = $values;
	
	$processes_values['tcp_port'] = 0; // Field number (OID)
	$processes_values['id_tipo_modulo'] = 2; // Generic boolean
	
	$processes_result = wmi_create_wizard_modules($id_agent, $processes, 'processes', $processes_values);
	
	// Create Space on disk modules
	$disks_values = $values;
	
	$disks_values['tcp_port'] = 1; // Free space in bytes
	$disks_values['id_tipo_modulo'] = 1; // Generic numeric
	$disks_values['unit'] = 'Bytes'; // Unit
	
	$disks_result = wmi_create_wizard_modules($id_agent, $disks, 'disks', $disks_values);
	
	// Create modules from component
	$components_values = $values;
	
	$components_values['id_agente'] = $id_agent;

	$components_result = wmi_create_module_from_components($components, $components_values);
	
	
	// Errors/Success messages
	$success_message = '';
	$error_message = '';
	if (!empty($services_result)) {
		if (count($services_result[NOERR]) > 0) {
			$success_message .= sprintf(__('%s service modules created succesfully'), count($services_result[NOERR])) . '<br>';
		}
		if (count($services_result[ERR_GENERIC]) > 0) {
			$error_message .= sprintf(__('Error creating %s service modules'), count($services_result[ERR_GENERIC])) . '<br>';
		}
	}
	if (!empty($processes_result)) {
		if (count($processes_result[NOERR]) > 0) {
			$success_message .= sprintf(__('%s process modules created succesfully'), count($processes_result[NOERR])) . '<br>';
		}
		if (count($processes_result[ERR_GENERIC]) > 0) {
			$error_message .= sprintf(__('Error creating %s process modules'), count($processes_result[ERR_GENERIC])) . '<br>';
		}
	}
	if (!empty($disks_result)) {
		if (count($disks_result[NOERR]) > 0) {
			$success_message .= sprintf(__('%s disk space modules created succesfully'), count($disks_result[NOERR])) . '<br>';
		}
		if (count($disks_result[ERR_GENERIC]) > 0) {
			$error_message .= sprintf(__('Error creating %s disk space modules'), count($disks_result[ERR_GENERIC])) . '<br>';
		}
	}
	if (!empty($components_result)) {
		if (count($components_result[NOERR]) > 0) {
			$success_message .= sprintf(__('%s modules created from components succesfully'), count($components_result[NOERR])) . '<br>';
		}
		if (count($components_result[ERR_GENERIC]) > 0) {
			$error_message .= sprintf(__('Error creating %s modules from components'), count($components_result[ERR_GENERIC])) . '<br>';
		}
		if (count($components_result[ERR_EXIST]) > 0) {
			$error_message .= sprintf(__('%s modules already exist'), count($components_result[ERR_EXIST])) . '<br>';
		}
	}
	
	if (!empty($success_message)) {
		ui_print_success_message($success_message);
	}
	if (!empty($error_message)) {
		ui_print_error_message($error_message);
	}
}

echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
echo "<form method='post' id='wmi_form' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=wmi_explorer&id_agente=$id_agent'>";

$table->width = '100%';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->class = 'databox filters';

$table->data[0][0] = '<b>' . __('Target IP') . '</b>';
$table->data[0][1] = html_print_input_text ('ip_target', $ip_target, '', 15, 60, true);

$table->data[0][2] = '<b>' . __('Namespace') . '</b>';
$table->data[0][2] .= ui_print_help_icon ('wminamespace', true);
$table->data[0][3] = html_print_input_text ('tcp_send', $tcp_send, '', 15, 60, true);

$table->data[1][0] = '<b>' . __('Username') . '</b>';
$table->data[1][1] = html_print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);

$table->data[1][2] = '<b>' . __('Password') . '</b>';
$table->data[1][3] = html_print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

$table->data[1][3] .= '<div id="spinner_modules" style="float: left; display: none;">' . html_print_image("images/spinner.gif", true) . '</div>';
html_print_input_hidden('wmiexplore', 1);

html_print_table($table);

echo "<div style='text-align:right; width:".$table->width."'>";
echo '<span id="oid_loading" class="invisible">' . html_print_image("images/spinner.gif", true) . '</span>';
html_print_submit_button(__('WMI Explore'), 'wmi_explore', false, array('class' => 'sub next'));
echo "</div>";

if ($wmiexplore && $fail) {
	ui_print_error_message(__('Unable to do WMI explorer'));
}

unset($table);

echo "</form>";

if ($wmiexplore && !$fail) {
	echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
	echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=wmi_explorer&id_agente=$id_agent'>";
	echo '<span id="form_interfaces">';
		
	html_print_input_hidden('create_modules', 1);
	html_print_input_hidden('ip_target', $ip_target); // Host
	html_print_input_hidden('plugin_user', $plugin_user); // User
	html_print_input_hidden('plugin_pass', $plugin_pass); // Password
	html_print_input_hidden('tcp_send', $tcp_send); // Namespace
	
	$table->width = '98%';
	
	// Mode selector
	$modes = array();
	$modes['services'] = __('Services');
	$modes['processes'] = __('Processes');
	$modes['disks'] = __('Free space on disk');
	$modes['components'] = __('WMI components');
	
	$table->data[1][0] = __('Wizard mode') . ': ';
	$table->data[1][0] .= html_print_select ($modes,
		'wmi_wizard_modes', '', '', '', '', true, false, false);
	$table->cellstyle[1][0] = 'vertical-align: middle;';
	
	$table->colspan[1][0] = 2;
	$table->data[1][2] = '<b>'.__('Modules').'</b>';
	$table->cellstyle[1][2] = 'vertical-align: middle;';

	// Components list
	$table->data[2][0] = '<div class="wizard_mode_form wizard_mode_components">';
	$table->data[2][0] .= __('Filter by group') . '<br>';
	$table->data[2][0] .= html_print_select ($network_component_groups,
		'network_component_group', '', '', '', '',
		true, false, false, '', false, 'width: 300px;') . '<br>';
	$table->data[2][0] .= html_print_select (array (), 'network_component', '', '',
		'', '', true, true, true, '', false, 'width: 300px;');
	$table->data[2][0] .= '</div>';
	
	// Services list
	$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_services">';
	$table->data[2][0] .= html_print_select ($services, 'services', '', '',
		'', '', true, true, true, '', false, 'width: 300px;');
	$table->data[2][0] .= '</div>';
	
	// Processes list
	$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_processes">';
	$table->data[2][0] .= html_print_select ($processes, 'processes', '', '',
		'', '', true, true, true, '', false, 'width: 300px;');
	$table->data[2][0] .= '</div>';
	$table->data[2][0] .= '<span id="no_component" class="invisible error wizard_mode_form wizard_mode_components">';
	$table->data[2][0] .= __('No component was found');
	$table->data[2][0] .= '</span>';
	
	// Disks list
	$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_disks">';
	$table->data[2][0] .= html_print_select ($disks, 'disks', '', '',
		'', '', true, true, true, '', false, 'width: 300px;');
	$table->data[2][0] .= '</div>';
	$table->cellstyle[2][0] = 'vertical-align: top; text-align: center;';
	
	
	// Components arrow
	$table->data[2][1] = '<div class="wizard_mode_form wizard_mode_components wizard_mode_components_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	// Services arrow
	$table->data[2][1] .= '<div class="wizard_mode_form wizard_mode_services wizard_mode_services_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	// Processes arrow
	$table->data[2][1] .= '<div class="wizard_mode_form wizard_mode_processes wizard_mode_processes_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	// Disks arrow
	$table->data[2][1] .= '<div class="wizard_mode_form wizard_mode_disks wizard_mode_disks_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	
	
	$table->data[2][1] .= '<br><br><div class="wizard_mode_delete_arrow clickable">' .
		html_print_image('images/cross.png', true, array('title' => __('Remove from modules list'))) .
		'</div>';
	$table->cellstyle[2][1] = 'vertical-align: middle; text-align: center;';
	
	$table->data[2][2] = html_print_select (array (), 'module[]', 0, false, '', 0, true, true, true, '', false, 'width:300px; height: 100%;');
	$table->data[2][2] .= html_print_input_hidden('agent', $id_agent, true);
	$table->cellstyle[2][2] = 'vertical-align: top; text-align: center;';
	
	html_print_table($table);
	
	echo "<div style='text-align:right; width:".$table->width."'>";
	html_print_submit_button(__('Create modules'), 'create_modules_btn', false, array('class' => 'sub add'));
	echo "</div>";
	unset($table);
	
	echo "</span>";
	echo "</form>";
	echo '</div>';
}

ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('ajaxqueue');
ui_require_jquery_file ('bgiframe');
ui_require_javascript_file ('pandora_modules');

?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */

$(document).ready (function () {
	$("#wmi_form").submit(function() {
		$("#oid_loading").show ();
	});
	
	network_component_group_change_event();
	$('#network_component_group').trigger('change');
	
	$("#wmi_wizard_modes").change(function() {
		$(".wizard_mode_form").hide();
		var selected_mode = $("#wmi_wizard_modes").val();
		$(".wizard_mode_" + selected_mode).show();
		$('#form_interfaces').show();
	});
	
	$("#wmi_wizard_modes").trigger('change');
	
	<?php 
		if (!$wmiexplore || $fail) {
	?>
			$('#form_interfaces').hide();
	<?php
		}
	?>
	
	$('.wizard_mode_services_arrow').click(function() {
		jQuery.each($("select[name='services'] option:selected"), function (key, value) {
			var id = 'service_' + $(value).attr('value');
			var name = $(value).html() + ' (<?php echo __('Service'); ?>)';
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_processes_arrow').click(function() {
		jQuery.each($("select[name='processes'] option:selected"), function (key, value) {
			var id = 'process_' + $(value).attr('value');
			var name = $(value).html() + ' (<?php echo __('Process'); ?>)';
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_disks_arrow').click(function() {
		jQuery.each($("select[name='disks'] option:selected"), function (key, value) {
			var id = 'disk_' + $(value).attr('value');
			var name = $(value).html();
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_components_arrow').click(function() {
		jQuery.each($("select[name='network_component'] option:selected"), function (key, value) {
			var id = 'component_' + $(value).attr('value');
			var name = $(value).html();
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_delete_arrow').click(function() {
		jQuery.each($("select[name='module[]'] option:selected"), function (key, value) {
			var name = $(value).html();
			if (name != <?php echo "'".__('None')."'"; ?>) {
				$(value).remove();
			}
		});
		
		if($("#module option").length == 0) {
			$("select[name='module[]']").append($("<option></option>").val(0).html(<?php echo "'".__('None')."'"; ?>));
		}
	});
	
	$("#submit-create_modules_btn").click(function () {
		if($("#module option").length == 0 || ($("#module option").length == 1 && $("#module option").eq(0).val() == 0)) {
			alert('<?php echo __('Modules list is empty'); ?>');
			return false;
		}
		$('#module option').map(function() {
			$(this).prop('selected', true);
		});
	});
});

/* ]]> */
</script>

