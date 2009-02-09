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

// Load global vars
require_once ('include/config.php');
require_once ('include/functions_alerts.php');

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

function print_alert_template_steps ($step, $id) {
	echo '<div style="margin-bottom: 15px">';
	
	/* Step 1 */
	if ($step == 1)
		echo '<strong>';
	if ($id) {
		echo '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&id='.$id.'">';
		echo __('Step').' 1 : '.__('Conditions');
		echo '</a>';
	} else {
		echo __('Step').' 1 : '.__('Conditions');
	}
	if ($step == 1)
		echo '</strong>';
	
	/* Step 2 */
	echo ' &raquo; ';
	
	if ($step == 2)
		echo '<strong>';
	if ($id) {
		echo '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=2">';
		echo __('Step').' 2 : '.__('Firing');
		echo '</a>';
	} else {
		echo __('Step').' 2 : '.__('Firing');
	}
	if ($step == 2)
		echo '</strong>';
	
	/* Step 3 */
	echo ' &raquo; ';
	
	if ($step == 3)
		echo '<strong>';
	if ($id) {
		echo '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=3">';
		echo __('Step').' 3 : '.__('Recovery');
		echo '</a>';
	} else {
		echo __('Step').' 3 : '.__('Recovery');
	}
	if ($step == 3)
		echo '</strong>';
	
	echo '</div>';
}

function update_template ($step) {
	$id = (int) get_parameter ('id');
	
	if (empty ($id))
		return false;
	
	if ($step == 1) {
		$type = (string) get_parameter ('type');
		$name = (string) get_parameter ('name');
		$description = (string) get_parameter ('description');
		$type = (string) get_parameter ('type');
		$value = (string) html_entity_decode (get_parameter ('value'));
		$max = (float) get_parameter ('max');
		$min = (float) get_parameter ('min');
		$matches = (bool) get_parameter ('matches_value');
		
		$result = update_alert_template ($id,
			array ('type' => $type,
				'description' => $description,
				'value' => $value,
				'max_value' => $max,
				'min_value' => $min,
				'matches_value' => $matches));
	} elseif ($step == 2) {
		$monday = (bool) get_parameter ('monday');
		$tuesday = (bool) get_parameter ('tuesday');
		$wednesday = (bool) get_parameter ('wednesday');
		$thursday = (bool) get_parameter ('thursday');
		$friday = (bool) get_parameter ('friday');
		$saturday = (bool) get_parameter ('saturday');
		$sunday = (bool) get_parameter ('sunday');
		$time_from = (string) get_parameter ('time_from');
		$time_from = date ("H:s:00", strtotime ($time_from));
		$time_to = (string) get_parameter ('time_to');
		$time_to = date ("H:s:00", strtotime ($time_to));
		$threshold = (int) get_parameter ('threshold');
		$max_alerts = (int) get_parameter ('max_alerts');
		$min_alerts = (int) get_parameter ('min_alerts');
		if ($threshold == -1)
			$threshold = (int) get_parameter ('other_threshold');
		$field1 = (string) get_parameter ('field1');
		$field2 = (string) get_parameter ('field2');
		$field3 = (string) get_parameter ('field3');
		$default_action = (int) get_parameter ('default_action');
		if (empty ($default_action)) {
			$default_action = NULL;
			$field1 = '';
			$field2 = '';
			$field3 = '';
		}
		
		$values = array ('monday' => $monday,
			'tuesday' => $tuesday,
			'wednesday' => $wednesday,
			'thursday' => $thursday,
			'friday' => $friday,
			'saturday' => $saturday,
			'sunday' => $sunday,
			'time_from' => $time_from,
			'time_to' => $time_to,
			'time_threshold' => $threshold,
			'default_action' => $default_action,
			'field1' => $field1,
			'field2' => $field2,
			'field3' => $field3,
			'max_alerts' => $max_alerts,
			'min_alerts' => $min_alerts
			);
		
		if ($default_action) {
			$values['id_alert_action'] = $default_action;
			$values['field1'] = $field1;
			$values['field2'] = $field2;
			$values['field3'] = $field3;
		}
		
		$result = update_alert_template ($id, $values);
	} elseif ($step == 3) {
		$recovery_notify = (bool) get_parameter ('recovery_notify');
		$field2_recovery = (bool) get_parameter ('field2_recovery');
		$field3_recovery = (bool) get_parameter ('field3_recovery');
	
		$result = update_alert_template ($id,
			array ('recovery_notify' => $recovery_notify,
				'field2_recovery' => $field2_recovery,
				'field3_recovery' => $field3_recovery));
	} else {
		return false;
	}
	
	return $result;
}

$id = (int) get_parameter ('id');

/* We set here the number of steps */
define ('LAST_STEP', 3);

$step = (int) get_parameter ('step', 1);

$create_template = (bool) get_parameter ('create_template');
$update_template = (bool) get_parameter ('update_template');

$name = '';
$description = '';
$type = '';
$value = '';
$max = '';
$min = '';
$time_from = '12:00';
$time_to = '12:00';
$monday = true;
$tuesday = true;
$wednesday = true;
$thursday = true;
$friday = true;
$saturday = true;
$sunday = true;
$default_action = 0;
$field1 = '';
$field2 = '';
$field3 = '';
$min_alerts = 0;
$max_alerts = 1;
$threshold = 300;
$recovery_notify = false;
$field2_recovery = '';
$field3_recovery = '';
$matches = true;

if ($create_template) {
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$type = (string) get_parameter ('type');
	$value = (string) get_parameter ('value');
	$max = (float) get_parameter ('max');
	$min = (float) get_parameter ('min');
	$matches = (bool) get_parameter ('matches_value');
	
	$result = create_alert_template ($name, $type,
		array ('description' => $description,
			'value' => $value,
			'max_value' => $max,
			'min_value' => $min,
			'matches_value' => $matches));
	
	print_error_message ($result, __('Successfully created'),
		__('Could not be created'));
	/* Go to previous step in case of error */
	if ($result === false)
		$step = $step - 1;
	else
		$id = $result;
}

if ($update_template) {
	$result = update_template ($step - 1);
	
	print_error_message ($result, __('Successfully updated'),
		__('Could not be updated'));
	/* Go to previous step in case of error */
	if ($result === false) {
		$step = $step - 1;
	}
}

if ($id && ! $create_template) {
	$template = get_alert_template ($id);
	$name = $template['name'];
	$description = $template['description'];
	$type = $template['type'];
	$value = $template['value'];
	$max = $template['max_value'];
	$min = $template['min_value'];
	$matches = $template['matches_value'];
	$time_from = $template['time_from'];
	$time_to = $template['time_to'];
	$monday = (bool) $template['monday'];
	$tuesday = (bool) $template['tuesday'];
	$wednesday = (bool) $template['wednesday'];
	$thursday = (bool) $template['thursday'];
	$friday = (bool) $template['friday'];
	$saturday = (bool) $template['saturday'];
	$sunday = (bool) $template['sunday'];
	$max_alerts = $template['max_alerts'];
	$min_alerts = $template['min_alerts'];
	$threshold = $template['time_threshold'];
	$recovery_notify = $template['recovery_notify'];
	$field2_recovery = $template['field2_recovery'];
	$field3_recovery = $template['field3_recovery'];
	$default_action = $template['id_alert_action'];
	$field1 = $template['field1'];
	$field2 = $template['field2'];
	$field3 = $template['field3'];
}

echo '<h1>'.__('Configure alert template').'</h1>';

print_alert_template_steps ($step, $id);

$table->id = 'template';
$table->width = '90%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->size[2] = '20%';

if ($step == 2) {
	/* Firing conditions and events */
	$threshold_values = get_alert_template_threshold_values ();
	if (in_array ($threshold, array_keys ($threshold_values))) {
		$table->style['other_label'] = 'display:none; font-weight: bold';
		$table->style['other_input'] = 'display:none';
		$threshold_selected = $threshold;
	} else {
		$table->style['other_label'] = 'font-weight: bold';
		$threshold_selected = -1;
	}
	
	if ($default_action == 0) {
		$table->rowstyle = array ();
		$table->rowstyle['field1'] = 'display: none';
		$table->rowstyle['field2'] = 'display: none';
		$table->rowstyle['field3'] = 'display: none';
		$table->rowstyle['preview'] = 'display: none';
	}
	$table->colspan = array ();
	$table->colspan[0][1] = 3;
	$table->colspan[4][1] = 3;
	$table->colspan['field1'][1] = 3;
	$table->colspan['field2'][1] = 3;
	$table->colspan['field3'][1] = 3;
	$table->colspan['preview'][1] = 3;
	
	$table->data[0][0] = __('Days of week');
	$table->data[0][1] = __('Mon');
	$table->data[0][1] .= print_checkbox ('monday', 1, $monday, true);
	$table->data[0][1] .= __('Tue');
	$table->data[0][1] .= print_checkbox ('tuesday', 1, $tuesday, true);
	$table->data[0][1] .= __('Wed');
	$table->data[0][1] .= print_checkbox ('wednesday', 1, $wednesday, true);
	$table->data[0][1] .= __('Thu');
	$table->data[0][1] .= print_checkbox ('thursday', 1, $thursday, true);
	$table->data[0][1] .= __('Fri');
	$table->data[0][1] .= print_checkbox ('friday', 1, $friday, true);
	$table->data[0][1] .= __('Sat');
	$table->data[0][1] .= print_checkbox ('saturday', 1, $saturday, true);
	$table->data[0][1] .= __('Sun');
	$table->data[0][1] .= print_checkbox ('sunday', 1, $sunday, true);
	
	$table->data[1][0] = __('Time from');
	$table->data[1][1] = print_input_text ('time_from', $time_from, '', 7, 7,
		true);
	$table->data[1][2] = __('Time to');
	$table->data[1][3] = print_input_text ('time_to', $time_to, '', 7, 7,
		true);
	
	$table->data['threshold'][0] = __('Time threshold');
	$table->data['threshold'][1] = print_select ($threshold_values,
		'threshold', $threshold_selected, '', '', '', true, false, false);
	$table->data['threshold']['other_label'] = __('Other value');
	$table->data['threshold']['other_input'] = print_input_text ('other_threshold',
		$threshold, '', 5, 7, true);
	$table->data['threshold']['other_input'] .= ' '.__('seconds');
	
	$table->data[3][0] = __('Min. number of alerts');
	$table->data[3][1] = print_input_text ('min_alerts', $min_alerts, '',
		5, 7, true);
	$table->data[3][2] = __('Max. number of alerts');
	$table->data[3][3] = print_input_text ('max_alerts', $max_alerts, '',
		5, 7, true);
	
	$table->data[4][0] = __('Default action');
	$table->data[4][1] = print_select_from_sql ('SELECT id, name FROM talert_actions ORDER BY name',
		'default_action', $default_action, '', __('None'), 0,
		true, false, false);
	
	$table->data['field1'][0] = __('Field 1');
	$table->data['field1'][1] = print_input_text ('field1', $field1, '', 35, 255, true);
	
	$table->data['field2'][0] = __('Field 2');
	$table->data['field2'][1] = print_input_text ('field2', $field2, '', 35, 255, true);
	
	$table->data['field3'][0] = __('Field 3');
	$table->data['field3'][1] = print_textarea ('field3', 30, 30, $field3, '', true);
	
	$table->data['preview'][0] = __('Command preview');
	$table->data['preview'][1] = print_textarea ('command_preview', 30, 30,
		'', 'disabled="disabled"', true);
} else if ($step == 3) {
	/* Alert recover */
	if (! $recovery_notify) {
		$table->rowstyle = array ();
		$table->rowstyle['field2'] = 'display:none;';
		$table->rowstyle['field3'] = 'display:none';
	}
	$table->data[0][0] = __('Alert recovery');
	$values = array (false => __('Disabled'), true => __('Enabled'));
	$table->data[0][1] = print_select ($values,
		'recovery_notify', $recovery_notify, '', '', '', true, false,
		false);
	
	$table->data['field2'][0] = __('Field 2');
	$table->data['field2'][1] = print_input_text ('field2_recovery',
		$field2_recovery, '', 35, 255, true);
	
	$table->data['field3'][0] = __('Field 3');
	$table->data['field3'][1] = print_textarea ('field3_recovery', 30, 30,
		$field3_recovery, '', true);
} else {
	/* Step 1 by default */
	$table->size = array ();
	$table->size[0] = '20%';
	$table->data = array ();
	$table->rowstyle = array ();
	$table->rowstyle['value'] = 'display: none';
	$table->rowstyle['max'] = 'display: none';
	$table->rowstyle['min'] = 'display: none';
	$table->rowstyle['example'] = 'display: none';
	
	$show_matches = false;
	if ($id) {
		$table->rowstyle['example'] = '';
		switch ($type) {
		case "equal":
		case "not_equal":
		case "regex":
			$show_matches = true;
			$table->rowstyle['value'] = '';
			break;
		case "max_min":
			$show_matches = true;
		case "max":
			$table->rowstyle['max'] = '';
			if ($type == 'max')
				break;
		case "min":
			$table->rowstyle['min'] = '';
			break;
		}
	}

	$table->data[0][0] = __('Name');
	$table->data[0][1] = print_input_text ('name', $name, '', 35, 255, true);

	$table->data[1][0] = __('Description');
	$table->data[1][1] =  print_textarea ('description', 30, 30,
		$description, '', true);

	$table->data[2][0] = __('Condition type');
	$table->data[2][1] = print_select (get_alert_templates_types (), 'type',
		$type, '', __('Select'), 0, true, false, false);
	$table->data[2][1] .= '<span id="matches_value" '.($show_matches ? '' : 'style="display: none"').'>';
	$table->data[2][1] .= '&nbsp;'.print_checkbox ('matches_value', 1, $matches, true);
	$table->data[2][1] .= print_label (__('Trigger when matches the value'),
		'checkbox-matches_value', true);
	$table->data[2][1] .= '</span>';

	$table->data['value'][0] = __('Value');
	$table->data['value'][1] = print_input_text ('value', $value, '',
		35, 255, true);
	$table->data['value'][1] .= '&nbsp;<span id="regex_ok">';
	$table->data['value'][1] .= print_image ('images/suc.png', true,
		array ('style' => 'display:none',
			'id' => 'regex_good',
			'title' => __('The regular expression is valid')));
	$table->data['value'][1] .= print_image ('images/err.png', true,
		array ('style' => 'display:none',
			'id' => 'regex_bad',
			'title' => __('The regular expression is not valid')));
	$table->data['value'][1] .= '</span>';

	//Min first, then max, that's more logical
	$table->data['min'][0] = __('Min.');
	$table->data['min'][1] = print_input_text ('min', $min, '', 5, 255, true);

	$table->data['max'][0] = __('Max.');
	$table->data['max'][1] = print_input_text ('max', $max, '', 5, 255, true);
	
	$table->data['example'][1] = print_alert_template_example ($id, true, false);
	$table->colspan['example'][1] = 2;
}

/* If it's the last step it will redirect to template lists */
if ($step >= LAST_STEP) {
	echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_templates">';
} else {
	echo '<form method="post">';
}
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	print_input_hidden ('id', $id);
	print_input_hidden ('update_template', 1);
} else {
	print_input_hidden ('create_template', 1);
}

if ($step >= LAST_STEP) {
	print_submit_button (__('Finish'), 'finish', false, 'class="sub upd"');
} else {
	print_input_hidden ('step', $step + 1);
	print_submit_button (__('Next'), 'next', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';

$config['jquery'][] = 'ui.core';
$config['jquery'][] = 'timeentry';
$config['jquery'][] = 'ui.core';
$config['css'][] = 'timeentry';
$config['js'][] = 'pandora_alerts';

?>
<script type="text/javascript">

var matches = "<?php echo __('The alert would fire when the value matches <span id=\"value\"></span>');?>";
var matches_not = "<?php echo __('The alert would fire when the value doesn\'t match <span id=\"value\"></span>');?>";
var is = "<?php echo __('The alert would fire when the value is <span id=\"value\"></span>');?>";
var is_not = "<?php echo __('The alert would fire when the value is not <span id=\"value\"></span>');?>";
var between = "<?php echo __('The alert would fire when the value is between <span id=\"min\"></span> and <span id=\"max\"></span>');?>";
var between_not = "<?php echo __('The alert would fire when the value is not between <span id=\"min\"></span> and <span id=\"max\"></span>');?>";
var under = "<?php echo __('The alert would fire when the value is under <span id=\"min\"></span>');?>";
var over = "<?php echo __('The alert would fire when the value is over <span id=\"max\"></span>');?>";

function check_regex () {
	if ($("#type").attr ('value') != 'regex') {
		$("img#regex_good, img#regex_bad").hide ();
		return;
	}
	
	try {
		re = new RegExp ($("#text-value").attr ("value"));
	} catch (error) {
		$("img#regex_good").hide ();
		$("img#regex_bad").show ();
		return;
	}
	$("img#regex_bad").hide ();
	$("img#regex_good").show ();
}

function render_example () {
	/* Set max */
	max = parseInt ($("input#text-max").attr ("value"));
	if (isNaN (max) || max == '') {
		$("span#max").empty ().append ("0");
	} else {
		$("span#max").empty ().append (max);
	}
	
	/* Set min */
	min = parseInt ($("input#text-min").attr ("value"));
	if (isNaN (min) || min == '') {
		$("span#min").empty ().append ("0");
	} else {
		$("span#min").empty ().append (min);
	}
	
	/* Set value */
	value = $("input#text-value").attr ("value");
	if (value == '') {
		$("span#value").empty ().append ("<em><?php echo __('Empty');?></em>");
	} else {
		$("span#value").empty ().append (value);
	}
}

$(document).ready (function () {
	render_example ();
	$("#text-time_from, #text-time_to").timeEntry ({
		spinnerImage: 'images/time-entry.png',
		spinnerSize: [20, 20, 0]
		}
	);
	
	$("input#text-value").keyup (render_example);
	$("input#text-max").keyup (render_example);
	$("input#text-min").keyup (render_example);
	
	$("#type").change (function () {
		switch (this.value) {
		case "equal":
		case "not_equal":
			$("img#regex_good, img#regex_bad, span#matches_value").hide ();
			$("#template-max, #template-min").hide ();
			$("#template-value, #template-example").show ();
			
			/* Show example */
			if (this.value == "equal")
				$("span#example").empty ().append (is);
			else
				$("span#example").empty ().append (is_not);
			
			break;
		case "regex":
			$("#template-max, #template-min").hide ();
			$("#template-value, #template-example, span#matches_value").show ();
			check_regex ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (matches);
			else
				$("span#example").empty ().append (matches_not);
			
			break;
		case "max_min":
			$("#template-value").hide ();
			$("#template-max, #template-min, #template-example, span#matches_value").show ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (between);
			else
				$("span#example").empty ().append (between_not);
			
			break;
		case "max":
			$("#template-value, #template-min, span#matches_value").hide ();
			$("#template-max, #template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (over);
			break;
		case "min":
			$("#template-value, #template-max, span#matches_value").hide ();
			$("#template-min, #template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (under);
			break;
		default:
			$("#template-value, #template-max, #template-min, #template-example, span#matches_value").hide ();
			break;
		}
		
		render_example ();
	});
	
	$("#checkbox-matches_value").click (function () {
		enabled = this.checked;
		type = $("#type").attr ("value");
		if (type == "regex") {
			if (enabled) {
				$("span#example").empty ().append (matches);
			} else {
				$("span#example").empty ().append (matches_not);
			}
		} else if (type == "max_min") {
			if (enabled) {
				$("span#example").empty ().append (between);
			} else {
				$("span#example").empty ().append (between_not);
			}
		}
		render_example ();
	});
	
	$("#text-value").keyup (check_regex);
	$("#threshold").change (function () {
		if (this.value == -1) {
			$("#text-other_threshold").attr ("value", "");
			$("#template-threshold-other_label").show ();
			$("#template-threshold-other_input").show ();
		} else {
			$("#template-threshold-other_label").hide ();
			$("#template-threshold-other_input").hide ();
		}
	});
	
	$("#recovery_notify").change (function () {
		if (this.value == 1) {
			$("#template-field2, #template-field3").show ();
		} else {
			$("#template-field2, #template-field3").hide ();
		}
	});
	
	$("#default_action").change (function () {
		if (this.value != 0) {
			values = Array ();
			values.push ({name: "page",
				value: "godmode/alerts/alert_actions"});
			values.push ({name: "get_alert_action",
				value: "1"});
			values.push ({name: "id",
				value: this.value});
			jQuery.get ("ajax.php",
				values,
				function (data) {
					$("#text-field1").attr ("value", data["field1"]);
					$("#text-field2").attr ("value", data["field2"]);
					$("#text-field3").attr ("value", data["field3"]);
					original_command = html_entity_decode (data["command"]["command"]);
					render_command_preview ();
					
					$("#template-field1, #template-field2, #template-field3, #template-example")
						.show ();
				},
				"json"
			);
		} else {
			$("#template-field1, #template-field2, #template-field3").hide ();
		}
	});
	
	$("#text-field1").keyup (render_command_preview);
	$("#text-field2").keyup (render_command_preview);
	$("#text-field3").keyup (render_command_preview);
})
</script>
