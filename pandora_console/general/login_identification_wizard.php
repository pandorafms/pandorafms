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

/**
 * @package General
 */

global $config;

function display_register ($data) {
	if ($data['instance_registered'] == 1) return false;
	if ($data['force_register'] == 1) return true;
	if ($data['force_register'] == 0) return false;
	if ($data['identification_reminder'] == 0) return false;
	if (!isset ($data['identification_reminder_timestamp'])) return true;
	if ($data['identification_reminder_timestamp'] < time()) return true;
	return false;
}

function display_newsletter ($data) {
	if ($data['newsletter_subscribed'] == 1) return false;
	if ($data['force_newsletter'] == 1) return true;
	if ($data['force_newsletter'] == 0) return false;
	if ($data['newsletter_reminder'] == 0) return false;
	if (!isset ($data['newsletter_reminder_timestamp'])) return true;
	if (!is_numeric ($data['newsletter_reminder_timestamp'])) return true;
	if ($data['newsletter_reminder_timestamp'] < time()) return true;
	else {html_debug ($data['newsletter_reminder_timestamp'] . "<<-data", true);}
	return false;
}

if (is_ajax()) {
	
	$open_wizard = get_parameter ('open_wizard', 0);
	$not_return = get_parameter ('not_return', 0);
	
	if ($open_wizard) {
		
		$register_pandora = get_parameter ('register_pandora', 0);
		$newsletter = get_parameter ('newsletter', 0);
		$forced = get_parameter ('forced', 0);
		$future_8_days = time() + 8 * SECONDS_1DAY;
		
		if ($register_pandora) {
			//TODO: Pandora registration
			config_update_value ('instance_registered', 1);
		} elseif (!$forced)  {
			config_update_value ('identification_reminder_timestamp', $future_8_days);
		}
		
		if ($newsletter) {
			//TODO: Newsletter subscribe
			db_process_sql_update ('tusuario', array ('middlename' => 1), array('id_user' => $config['id_user']));
		} elseif (!$forced) {
			html_debug ('future days');
			db_process_sql_update ('tusuario', array ('lastname' => $future_8_days), array('id_user' => $config['id_user']));
		}
	}
	
	if (!$not_return) {
		return;
	}
}

//Check if user is admin
if (!license_free()) return;
if (!users_is_admin ($config['id_user'])) return;

// Get data to display properly the wizard
$wizard_data = array ();

$wizard_data['newsletter_subscribed'] = db_get_value ('middlename', 'tusuario', 'id_user', $config['id_user']);
// force_* = 1 -> force show
// force_* = 0 -> force hide
// force_* = -1 -> show or hide depends reminder and timestamp
$wizard_data['force_newsletter'] = get_parameter ('force_newsletter', -1);
$wizard_data['newsletter_reminder'] = db_get_value ('firstname', 'tusuario', 'id_user', $config['id_user']);
$wizard_data['newsletter_reminder_timestamp'] = db_get_value ('lastname', 'tusuario', 'id_user', $config['id_user']);


$wizard_data['instance_register'] = $config['instance_registered'];
$wizard_data['force_register'] = get_parameter ('force_register', -1);
$wizard_data['identification_reminder'] = $config['identification_reminder'];
$wizard_data['identification_reminder_timestamp'] = $config['identification_reminder_timestamp'];

$display_newsletter = display_newsletter ($wizard_data);
$display_register = display_register ($wizard_data);
$display_forced = ($wizard_data['force_newsletter'] != -1) || ($wizard_data['force_register'] != -1);

// Return if it is fully completed
if ((!$display_register) && (!$display_newsletter)) return;
html_debug ($wizard_data, true);

$return_button = get_parameter ('return_button', 0) == 1;

$email = db_get_value ('email', 'tusuario', 'id_user', $config['id_user']);
//Avoid to show default email
if ($email == 'admin@example.com') $email = '';

// Prints accept register license
echo '<div id="login_accept_register" title="' .
	__('Pandora FMS instance identification wizard') . '" style="">';
	echo '<div style="font-size: 15pt; margin: 20px; float: left; padding-left: 150px;">';
		echo html_print_image ('images/support.png', true);
	echo '</div>';
	echo '<div style="font-size: 15pt; margin: 20px; float: left;">';
		echo __('KEEP UPDATED!');
	echo '</div>';
	
	echo '<div style="">';
		$license_text = file('license.lic');
		$license_text = implode ($license_text);
		html_print_textarea ("text-license", 1, 65, $license_text,
			'readonly="readonly"; ', false);
	echo '</div>';
	
	echo '<div style="position:absolute; margin: 0 auto; bottom: 0px; right: 10px; border: 1px solid #FFF; width: 570px">';
		echo '<div style="float: right; width: 20%;">';
			html_print_submit_button("Finish", 'finish_dialog_button', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
		echo '</div>';
		echo '<div style="float: left; width: 20%; display: none;">';
			html_print_submit_button("Return", 'return_dialog_button', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
		echo '</div>';
		echo '<div style="float: left; margin-left: 0px; width: 50%; text-align: left;">';
			html_print_checkbox('register', 1, false, false, false, 'cursor: \'pointer\'');
			echo '&nbsp;<span style="font-size: 12px;" id="label-register">' .__("Accept register Pandora") . '</span><br>';
			html_print_checkbox('newsletter', 1, false, false, false, 'cursor: \'pointer\'');
			echo '&nbsp;<span style="font-size: 12px;" id="label-newsletter">' .__("Subscribe to newsletter") . '</span>';
			echo "<br>";
			echo '&nbsp;<span id="label-email-newsletter"style="font-size: 12px; display: none">' .__("Email") . ': </span>';
			//html_print_input_text ('email-newsletter', '', '', 30, 255, false);
			html_print_input_text_extended ('email-newsletter', $email, 'text-email-newsletter', '', 30, 255, false, '', array ("style" => "display:none; ")); echo '&nbsp;<span id="label-email-newsletter"style="font-size: 12px; display: none">' .__("Email") . ': </span>';
			echo '&nbsp;<span id="required-email-newsletter" style="font-size: 9px; display: none; color: red;">'.__("*Required") .' </span>';
		echo '</div>';
	echo '</div>';
echo '</div>';

// Print yes or not dialog
echo '<div id="login_registration_yesno" title="' .
	__('Pandora FMS instance identification wizard') . '" style="">';
	echo '<div style="font-size: 15pt; margin: 20px;">';
		echo __("Do you want to continue without any registration") . "?";
	echo '</div>';
	echo '<div style="float: left; width: 50%;">';
		html_print_submit_button("No", 'no_registration', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
	echo '</div>';
	echo '<div style="float: left; width: 50%;">';
		html_print_submit_button("Yes", 'yes_registration', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
	echo '</div>';
echo '</div>';
?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

//Show newsletter and register checkboxes
var display_register = <?php echo json_encode($display_register); ?>;
var display_newsletter = <?php echo json_encode($display_newsletter); ?>;
var display_forced = <?php echo json_encode($display_forced); ?>;
var return_button = <?php echo json_encode($return_button); ?>;


console.log (display_forced + ".");
////////////////////////////////////////////////////////////////////////
//HELPER FUNCTIONS

function submit_open_wizard (register, newsletter, email, forced) {
	
	register = register;
	newsletter = newsletter ? 1 : 0;
	forced = forced ? 1 : 0;
	
	jQuery.post ("ajax.php",
				{"page": "general/login_identification_wizard",
				"open_wizard": 1,
				"register_pandora": register,
				"newsletter": newsletter,
				"email": email,
				"forced": forced},
				function (data) {}
			);
}

////////////////////////////////////////////////////////////////////////
//EVENT FUNCTIONS
$("#submit-return_dialog_button").click (function () {
	$("#login_accept_register" ).dialog('close');
	$("#login_id_dialog" ).dialog('open');
});

$("#submit-finish_dialog_button").click (function () {
	
	var newsletter = $("#checkbox-newsletter").is(':checked') ? 1 : 0;
	var register = $("#checkbox-register").is(':checked');
	var email = $("#text-email-newsletter").val();
	
	if (email == '' && newsletter) {
		$("#label-email-newsletter").show();
		$("#text-email-newsletter").show();
		$("#required-email-newsletter").show();
		return;
	}
	
	if (!register && display_register && !display_forced) {
		$("#login_registration_yesno").dialog('open');
	} else {
		submit_open_wizard (1, newsletter, email, display_forced);
		$("#login_accept_register" ).dialog('close');
	}
});

$("#submit-no_registration").click (function () {
	$("#login_registration_yesno").dialog('close');
});

$("#submit-yes_registration").click (function () {
	var newsletter = $("#checkbox-newsletter").is(':checked') ? 1 : 0;
	var email = $("#text-email-newsletter").val();
	submit_open_wizard (0, newsletter, email, display_forced);
	
	$("#login_registration_yesno").dialog('close');
	$("#login_accept_register" ).dialog('close');
});

$("#checkbox-newsletter").click (function () {
	if (!return_button) {
		$("#label-email-newsletter").show();
		$("#text-email-newsletter").show();
	}
});

////////////////////////////////////////////////////////////////////////
//DISPLAY
$(document).ready (function () {
	
	$("#login_accept_register").dialog({
		resizable: true,
		draggable: true,
		modal: true,
		height: 350,
		width: 630,
		overlay: {
				opacity: 0.5,
				background: "black"
			},
	});
	
	$("#login_registration_yesno").dialog({
		resizable: true,
		draggable: true,
		modal: true,
		height: 250,
		width: 350,
		overlay: {
				opacity: 1,
				background: "black"
			},
		autoOpen: false
	});
	
	//Display return button if required
	if (return_button) {
		$("#submit-return_dialog_button").show ();
	}
	// Remove the completed parts
	if (!display_register) {
		$("#checkbox-register").attr ('style', 'display: none !important');
		$("#label-register").hide ();
	}
	if (!display_newsletter) {
		$("#checkbox-newsletter").attr ('style', 'display: none !important');
		$("#label-newsletter").hide ();
	}
});

/* ]]> */
</script>
