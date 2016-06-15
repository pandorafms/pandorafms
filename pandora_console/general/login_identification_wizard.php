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
	if ($data['newsletter_reminder'] === 0) return false;
	if (!isset ($data['newsletter_reminder_timestamp'])) return true;
	if (!is_numeric ($data['newsletter_reminder_timestamp'])) return true;
	if ($data['newsletter_reminder_timestamp'] < time()) return true;
	return false;
}

if (is_ajax()) {
	
	include_once($config['homedir'] . "/include/functions_update_manager.php");
	
	$open_wizard = get_parameter ('open_wizard', 0);
	$not_return = get_parameter ('not_return', 0);
	
	if ($open_wizard) {
		
		$register_pandora = get_parameter ('register_pandora', 0);
		$newsletter = get_parameter ('newsletter', 0);
		$forced = get_parameter ('forced', 0);
		$future_8_days = time() + 8 * SECONDS_1DAY;
		$ui_feedback = array('status' => true, 'message' => '');
		
		if ($register_pandora) {
			
			// Pandora register update
			$um_message = update_manager_register_instance ();
			$ui_feedback['message'] .= $um_message['message'] . '<br><br>';
			if ($um_message['success']) {
				config_update_value ('instance_registered', 1);
				$ui_feedback['status'] = true && $ui_feedback['status'];
			} else {
				$ui_feedback['status'] = false;
			}
		} elseif (!$forced)  {
			config_update_value ('identification_reminder_timestamp', $future_8_days);
		}
		
		if ($newsletter) {
			
			// Pandora newsletter update
			$email = get_parameter ('email', '');
			$um_message = update_manager_insert_newsletter ($email);
			$ui_feedback['message'] .= $um_message['message'];
			if ($um_message['success']) {
				db_process_sql_update ('tusuario', array ('middlename' => 1), array('id_user' => $config['id_user']));
				$ui_feedback['status'] = true && $ui_feedback['status'];
			} else {
				$ui_feedback['status'] = false;
			}
		} elseif (!$forced) {
			db_process_sql_update ('tusuario', array ('lastname' => $future_8_days), array('id_user' => $config['id_user']));
		}
		
		// Form answer JSON
		$ui_feedback['status'] = $ui_feedback['status'] ? 1 : 0;
		echo io_json_mb_encode($ui_feedback);
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


$wizard_data['instance_registered'] = $config['instance_registered'];
$wizard_data['force_register'] = get_parameter ('force_register', -1);
$wizard_data['identification_reminder'] = $config['identification_reminder'];
$wizard_data['identification_reminder_timestamp'] = $config['identification_reminder_timestamp'];

$display_newsletter = display_newsletter ($wizard_data);
$display_register = display_register ($wizard_data);
$display_forced = ($wizard_data['force_newsletter'] != -1) || ($wizard_data['force_register'] != -1);

// Return if it is fully completed
if ((!$display_register) && (!$display_newsletter)) return false;

$return_button = get_parameter ('return_button', 0) == 1;

$email = db_get_value ('email', 'tusuario', 'id_user', $config['id_user']);
//Avoid to show default email
if ($email == 'admin@example.com') $email = '';

// Prints accept register license
echo '<div id="login_accept_register" title="' .
	__('The Pandora FMS community wizard') . '" style="">';
	echo '<div style="margin: 5px 0 10px; float: left; padding-left: 15px;">';
		echo html_print_image ('images/pandora_circle_big.png', true);
	echo '</div>';
	echo '<div style="font-size: 12pt; margin: 5px 20px; float: left; padding-top: 23px;">';
		echo __('Stay up to date with the Pandora FMS community') . ".";
	echo '</div>';
	
	echo '<div id="license_newsletter">';
		echo '<p>' . __("When you subscribe to the Pandora FMS Update Manager service, you accept that we register your Pandora instance as an identifier on the database owned by Artica TS. This data will solely be used to provide you with information about Pandora FMS and will not be conceded to third parties. You'll be able to unregister from said database at any time from the Update Manager options") . '.</p>';
		echo '<p>' . __("In the same fashion, when subscribed to the newsletter you accept that your email will pass on to a database property of Artica TS. This data will solely be used to provide you with information about Pandora FMS and will not be conceded to third parties. You'll be able to unregister from said database at any time from the newsletter subscription options") . '.</p>';
	echo '</div>';
	
	echo '<div style="position:absolute; margin: 0 auto; bottom: 0px; padding-top:10px; position:relative; border: 1px solid #FFF;">';
		echo '<div style="float: right;">';
			html_print_submit_button("Finish", 'finish_dialog_button', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
		echo '</div>';
		$display_status_return = $return_button ? 'block' : 'none';
		echo '<div style="float: right; width: 20%; display: ' . $display_status_return . ';">';
			html_print_submit_button("Return", 'return_dialog_button', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd" style="width:100px;"');  
		echo '</div>';
		echo '<div style="float: left; margin-left: 0px; width: 50%; text-align: left;">';
			html_print_checkbox('register', 1, false, false, false, 'cursor: \'pointer\'');
			echo '&nbsp;<span style="font-size: 12px;" id="label-register">' .__("Join the Pandora FMS community") . '!</span><br>';
			html_print_checkbox('newsletter', 1, false, false, false, 'cursor: \'pointer\'');
			echo '&nbsp;<span style="font-size: 12px;" id="label-newsletter">' .__("Subscribe to our newsletter") . '</span>';
			echo "<br>";
			echo '<div id="email_container">';
				echo '&nbsp;<span id="label-email-newsletter"style="font-size: 12px; display: none">' .__("Email") . ': </span>';
				html_print_input_text_extended ('email-newsletter', $email, 'text-email-newsletter', '', 30, 255, false, '', array ("style" => "display:none; width: 180px;")); echo '&nbsp;<span id="label-email-newsletter"style="font-size: 12px; display: none">' .__("Email") . ': </span>';
				echo '&nbsp;<span id="required-email-newsletter">*'.__("Required") .' </span>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

// Print yes or not dialog
echo '<div id="login_registration_yesno" title="' .
	__('Pandora FMS instance identification wizard') . '" style="">';
	echo '<div style="font-size: 12pt; margin: 20px;">';
		echo __("Do you want to continue without any registration") . "?";
	echo '</div>';
	echo '<div style="float: left;  padding-left: 15px; padding-top: 20px;">';
		html_print_submit_button("No", 'no_registration', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub cancel" style="width:100px;"');  
	echo '</div>';
	echo '<div style="float: right;  padding-right: 15px; padding-top: 20px;">';
		html_print_submit_button("Yes", 'yes_registration', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd" style="width:100px;"');  
	echo '</div>';
echo '</div>';

// Print feedback user dialog
echo '<div id="ui_messages_feedback" style="">';
	echo '<div style="float: left;  margin: 15px; margin-left: 5px;">';
		echo html_print_image ('images/success_circle_big.png', true);
	echo '</div>';
	echo '<div id="feedback_message" style="font-size: 13pt; margin: 15px 20px; padding-left:80px;"></div>';
echo '</div>';
?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

//Show newsletter and register checkboxes
var display_register = <?php echo json_encode($display_register); ?>;
var display_newsletter = <?php echo json_encode($display_newsletter); ?>;
var display_forced = <?php echo json_encode($display_forced); ?>;
var return_button = <?php echo json_encode($return_button); ?>;
////////////////////////////////////////////////////////////////////////
//HELPER FUNCTIONS

function submit_open_wizard (register, newsletter, email, forced) {
	
	register = register;
	newsletter = newsletter ? 1 : 0;
	forced = forced ? 1 : 0;
	
	var feedback_message = '';
	var feedback_status = 1;
	
	jQuery.post ("ajax.php",
				{"page": "general/login_identification_wizard",
				"open_wizard": 1,
				"register_pandora": register,
				"newsletter": newsletter,
				"email": email,
				"forced": forced},
				function (data) {
					var feedback_message = '';
					var feedback_status = 1;
					
					jQuery.each (data, function (i, val) {
						if (i == 'message') feedback_message = val;
						if (i == 'status') feedback_status = val;
					});
					if (feedback_status == 0) {
						$("#ui_messages_feedback img").attr("src", "images/fail_circle_big.png");
					} else {
						$("#ui_messages_feedback img").attr("src", "images/success_circle_big.png");
					}
					$("#feedback_message").html(feedback_message);	
				},
				"json"
			);
}

////////////////////////////////////////////////////////////////////////
//EVENT FUNCTIONS
$("#submit-return_dialog_button").click (function () {
	$("#login_accept_register" ).dialog('close');
	$("#all-required").hide();
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
		var register_forced = register ? 1 : 0;
		submit_open_wizard (register_forced, newsletter, email, display_forced);
		$("#login_accept_register" ).dialog('close');
		if (register || newsletter) {
			$("#ui_messages_feedback").dialog('open');
		}
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
	var newsletter = $("#checkbox-newsletter").is(':checked') ? 1 : 0;
	if (!return_button && newsletter) {
		$("#label-email-newsletter").show();
		$("#text-email-newsletter").show();
	}
	
	if (!newsletter) {
		$("#label-email-newsletter").hide();
		$("#text-email-newsletter").hide();
		$("#required-email-newsletter").hide();
	}
});

////////////////////////////////////////////////////////////////////////
//DISPLAY
$(document).ready (function () {
	
	$("#login_accept_register").dialog({
		resizable: false,
		draggable: true,
		modal: true,
		height: 320,
		width: 570
	});
	
	$("#login_registration_yesno").dialog({
		resizable: false,
		draggable: true,
		modal: true,
		width: 320,
		overlay: {
				opacity: 1,
				background: "black"
			},
		autoOpen: false
	});
	
	
	$("#ui_messages_feedback").dialog({
		resizable: false,
		draggable: true,
		modal: true,
		width: 300,
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


<style type="text/css">

	#required-email-newsletter{
		font-size : 9px;
		color: red;
		float:right;
		left: 5px;
		top: -17px;
		position: relative;
		display: none;
	}
	
	#email_container{
		margin-top: 3px;
	}
	
	#license_newsletter {
		height: 100px;
		width: 100%;
		overflow-y: scroll;
		border: 1px solid #E4E4E4;
		border-radius: 3px; 
	}
	
	#license_newsletter p{
		padding: 0 3px;
	}
	
	.ui-widget-overlay {
		background: #000;
		opacity: .6;
	}
	
	.ui-draggable {
		cursor: inherit;
	}
	

</style>
