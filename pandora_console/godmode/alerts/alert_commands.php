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

require_once ($config['homedir'] . "/include/functions_alerts.php");
enterprise_include_once ('meta/include/functions_alerts_meta.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

if (is_metaconsole())
	$sec = 'advanced';
else
	$sec = 'galertas';

$pure = (int)get_parameter('pure', 0);
$update_command = (bool) get_parameter ('update_command');
$create_command = (bool) get_parameter ('create_command');
$delete_command = (bool) get_parameter ('delete_command');

if (is_ajax ()) {
	$get_alert_command = (bool) get_parameter ('get_alert_command');
	if ($get_alert_command) {
		$id = (int) get_parameter ('id', 0);
		$get_recovery_fields = (int)  get_parameter('get_recovery_fields', 1);
		
		// If command ID is not provided, check for action id
		if ($id == 0) {
			$id_action = (int) get_parameter ('id_action');
			$id = alerts_get_alert_action_alert_command_id($id_action);
		}
		
		$command = alerts_get_alert_command ($id);
		
		// If is setted a description, we change the carriage return by <br> tags
		if (isset($command['description'])) {
			$command['description'] = io_safe_input(str_replace("\r\n","<br>", io_safe_output($command['description'])));
		}
		
		// Get the html rows of the fields form
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":
				// Descriptions are stored in json
				$fields_descriptions = empty($command['fields_descriptions']) ?
					'' : json_decode(io_safe_output($command['fields_descriptions']), true);
				
				// Fields values are stored in json
				$fields_values = empty($command['fields_values']) ?
					'' : io_safe_output(json_decode($command['fields_values'], true));
				break;
			case "oracle":
				// Descriptions are stored in json
				$description_field = str_replace("\\\"","\"",$command['fields_descriptions']);
				$description_field = str_replace("\\","",$description_field);
				
				$fields_descriptions = empty($command['fields_descriptions']) ?
					'' : json_decode(io_safe_output($description_field), true);
				
				// Fields values are stored in json
				$values_fields = str_replace("\\\"","\"",$command['fields_values']);
				$values_fields = str_replace("\\","",$values_fields);
				
				$fields_values = empty($command['fields_values']) ?
					'' : io_safe_output(json_decode($values_fields, true));
				
				break;		
		}
		
		$fields_rows = array();
		for ($i = 1; $i <= 10; $i++) {

			if (($i == 5) && ($command['id'] == 3)){
				continue;
			}

			$field_description = $fields_descriptions[$i - 1];
			$field_value = $fields_values[$i - 1];
			
			if (!empty($field_description)) {
				//If the value is 5,  this because severity in snmp alerts is not permit to show
				if (($i > 5) && ($command['id'] == 3)){
					$fdesc = $field_description .
						' <br><span style="font-size:xx-small; font-weight:normal;">' . sprintf(__('Field %s'), $i - 1) . '</span>';
				}
				else{
					$fdesc = $field_description .
						' <br><span style="font-size:xx-small; font-weight:normal;">' . sprintf(__('Field %s'), $i) . '</span>';
				}
				//If the field is the number one, print the help message
				if ($i == 1) {
					// If our context is snmpconsole, show snmp_alert helps
					if ((isset ($_SERVER["HTTP_REFERER"])) && ( preg_match ("/snmp_alert/", $_SERVER["HTTP_REFERER"]) > 0 )){
                        $fdesc .= ui_print_help_icon ('snmp_alert_field1',true);
                    }
                    else {
                        $fdesc .= ui_print_help_icon ('alert_config', true);
                    }

				}
			}
			else {
				// If the macro hasn't description and doesnt appear in command, set with empty description to dont show it
				if (($i > 5) && ($command['id'] == 3)){
					if (substr_count($command['command'], "_field" . $i - 1 . "_") > 0) {
						$fdesc = sprintf(__('Field %s'), $i - 1);
					}
					else {
						$fdesc = '';
					}
				}
				else{
					if (substr_count($command['command'], "_field" . $i . "_") > 0) {
						$fdesc = sprintf(__('Field %s'), $i);
					}
					else {
						$fdesc = '';
					}
				}
			}
			
			if (!empty($field_value)) {
				$field_value = io_safe_output($field_value);
				// HTML type
				if (preg_match ("/^_html_editor_$/i", $field_value)) {
					
					$editor_type_chkbx = "<div style=\"padding: 4px 0px;\"><b><small>";
					$editor_type_chkbx .= __('Basic') . "&nbsp;&nbsp;";
					$editor_type_chkbx .= html_print_radio_button_extended ('editor_type_value_'.$i, 0, '', false, false, "removeTinyMCE('textarea_field".$i."_value')", '', true);
					$editor_type_chkbx .= "&nbsp;&nbsp;&nbsp;&nbsp;";
					$editor_type_chkbx .= __('Advanced') . "&nbsp;&nbsp;";
					$editor_type_chkbx .= html_print_radio_button_extended ('editor_type_value_'.$i, 0, '', true, false, "addTinyMCE('textarea_field".$i."_value')", '', true);
					$editor_type_chkbx .= "</small></b></div>";
					$ffield = $editor_type_chkbx;
					$ffield .= html_print_textarea ('field'.$i.'_value', 1, 1, '', 'class="fields"', true);
					
					$editor_type_chkbx = "<div style=\"padding: 4px 0px;\"><b><small>";
					$editor_type_chkbx .= __('Basic') . "&nbsp;&nbsp;";
					$editor_type_chkbx .= html_print_radio_button_extended ('editor_type_recovery_value_'.$i, 0, '', false, false, "removeTinyMCE('textarea_field".$i."_recovery_value')", '', true);
					$editor_type_chkbx .= "&nbsp;&nbsp;&nbsp;&nbsp;";
					$editor_type_chkbx .= __('Advanced') . "&nbsp;&nbsp;";
					$editor_type_chkbx .= html_print_radio_button_extended ('editor_type_recovery_value_'.$i, 0, '', true, false, "addTinyMCE('textarea_field".$i."_recovery_value')", '', true);
					$editor_type_chkbx .= "</small></b></div>";
					$rfield = $editor_type_chkbx;
					$rfield .= html_print_textarea ('field'.$i.'_recovery_value', 1, 1, '', 'class="fields_recovery"', true);
				}
				// Select type
				else {
					$fields_value_select = array();
					$fv = explode(';', $field_value);
					
					if (count($fv) > 1) {
						if (!empty($fv)) {
							foreach ($fv as $fv_option) {
								$fv_option = explode(',', $fv_option);
								
								if (empty($fv_option))
									continue;
								
								if (!isset($fv_option[1]))
									$fv_option[1] = $fv_option[0];
								
								$fields_value_select[$fv_option[0]] = $fv_option[1];
							}
						}
					
						$ffield = html_print_select($fields_value_select,
							'field'.$i.'_value', '', '', '', 0, true, false, false, 'fields');
						$rfield = html_print_select($fields_value_select,
							'field'.$i.'_recovery_value', '', '', '', 0, true, false, false, 'fields_recovery');
					}
					else {
						$ffield = html_print_textarea ('field' . $i . '_value',1, 1, $fv[0],
											'style="min-height:40px" class="fields"', true);
						$rfield = html_print_textarea ('field' . $i . '_recovery_value', 1, 1, $fv[0],
											'style="min-height:40px" class="fields_recovery"', true);
					}
				}
			}
			else {
				$ffield = html_print_textarea ('field' . $i . '_value',
					1, 1, '', 'style="min-height:40px" class="fields"', true);
				$rfield = html_print_textarea (
					'field' . $i . '_recovery_value', 1, 1, '',
					'style="min-height:40px" class="fields_recovery"', true);
			}
			
			
			// The empty descriptions will be ignored
			if ($fdesc == '') {
				$fields_rows[$i] = '';
			}
			else {
				$fields_rows[$i] =
					'<tr id="table_macros-field' . $i . '" class="datos">';
				$fields_rows[$i] .=	'<td style="font-weight:bold;width:20%" class="datos">' . $fdesc . '</td>';
				$fields_rows[$i] .=	'<td class="datos">' . $ffield . '</td>';
				if ($get_recovery_fields) {
					$fields_rows[$i] .=	'<td class="datos recovery_col">' . $rfield . '</td>';
				}
				$fields_rows[$i] .=	'</tr>';
			}
		}

		//If command is PandoraFMS event, field 5 must be empty because "severity" must be set by the alert
		if ($command['id'] == 3){
			$fields_rows[5] = '';
		}

		$command['fields_rows'] = $fields_rows;
		
		echo json_encode ($command);
	}
	return;
}

enterprise_hook('open_meta_frame');

if ($update_command) {
	require_once("configure_alert_command.php");
	return;
}

// Header
if (defined('METACONSOLE'))
	alerts_meta_print_header();
else
	ui_print_page_header (__('Alerts').' &raquo; '.__('Alert commands'), "images/gm_alerts.png", false, "alerts_config", true);




if ($create_command) {
	$name = (string) get_parameter ('name');
	$command = (string) get_parameter ('command');
	$description = (string) get_parameter ('description');
	
	$fields_descriptions = array();
	$fields_values = array();
	$info_fields = '';
	$values = array();
	for ($i=1;$i<=10;$i++) {
		$fields_descriptions[] = (string) get_parameter ('field'.$i.'_description');
		$fields_values[] = (string) get_parameter ('field'.$i.'_values');
		$info_fields .= ' Field'.$i.': ' . $fields_values[$i - 1];
	}
	
	$values['fields_values'] = io_json_mb_encode($fields_values);
	$values['fields_descriptions'] = io_json_mb_encode($fields_descriptions);
	$values['description'] = $description;
	
	$name_check = db_get_value ('name', 'talert_commands', 'name', $name);
	
	if (!$name_check) {
		$result = alerts_create_alert_command ($name, $command,
			$values);
		
		$info = 'Name: ' . $name . ' Command: ' . $command . ' Description: ' . $description. ' ' .$info_fields;
	}
	else {
		$result = '';
	}
	
	if ($result) {
		db_pandora_audit("Command management", "Create alert command #" . $result, false, false, $info);
	}
	else {
		db_pandora_audit("Command management", "Fail try to create alert command", false, false);
	}
	
	ui_print_result_message ($result, 
		__('Successfully created'),
		__('Could not be created'));
}


if ($delete_command) {
	$id = (int) get_parameter ('id');
	
	// Internal commands cannot be deleted
	if (alerts_get_alert_command_internal ($id)) {
		db_pandora_audit("ACL Violation",
			"Trying to access Alert Management");
		require ("general/noaccess.php");
		return;
	}
	
	$result = alerts_delete_alert_command ($id);
	
	if ($result) {
		db_pandora_audit("Command management", "Delete alert command #" . $id);
	}
	else {
		db_pandora_audit("Command management", "Fail try to delete alert command #" . $id);
	}
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
	
	
}

$table->width = '100%';
$table->class = 'databox data';

$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('ID');
$table->head[2] = __('Description');
$table->head[3] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[3] = '40px';
$table->align = array ();
$table->align[3] = 'left';

$commands = db_get_all_rows_in_table ('talert_commands');
if ($commands === false)
	$commands = array ();

foreach ($commands as $command) {
	$data = array ();
	
	$data[0] = '<span style="font-size: 7.5pt">';
	if (! $command['internal'])
		$data[0] .= '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_command&id='.$command['id'].'&pure='.$pure.'">'.
			$command['name'].'</a>';
	else
		$data[0] .= $command['name'];
	$data[0] .= '</span>';
	$data[1] = $command['id'];
	$data[2] = str_replace("\r\n","<br>",
		io_safe_output($command['description']));
	$data[3] = '';
	if (! $command['internal']) {
		$data[3] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_commands&delete_command=1&id='.$command['id'].'&pure='.$pure.'"
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
			html_print_image("images/cross.png", true) . '</a>';
	}
	
	array_push ($table->data, $data);
}

if (isset($data)) {
	html_print_table ($table);
}
else {
	ui_print_info_message ( array('no_close'=>true, 'message'=>  __('No alert commands configured') ) );
}

echo '<div class="action-buttons" style="width: ' . $table->width . '">';
echo '<form method="post" action="index.php?sec=' . $sec . '&sec2=godmode/alerts/configure_alert_command&pure='.$pure.'">';
html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
html_print_input_hidden ('create_alert', 1);
echo '</form>';
echo '</div>';

enterprise_hook('close_meta_frame');

?>
