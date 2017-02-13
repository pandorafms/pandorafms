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

if (is_ajax()) {
	
	$save_identification = get_parameter ('save_required_wizard', 0);
	$change_language = get_parameter ('change_language', 0);
	$cancel_wizard = get_parameter ('cancel_wizard', 0);
	
	// Updates the values get on the identification wizard
	if ($save_identification) {
		$email = get_parameter ('email', false);
		$timezone = get_parameter ('timezone', false);
		$language = get_parameter ('language', false);
		
		if ($email !== false) config_update_value ('language', $language);
		if ($timezone !== false) config_update_value ('timezone', $timezone);
		if ($email !== false) db_process_sql_update ('tusuario', 
								array ('email' => $email), array('id_user' => $config['id_user']));
		
		// Update the alert action Mail to XXX/Administrator if it is set to default
		$mail_check = 'yourmail@domain.es';
		$mail_alert = alerts_get_alert_action_field1(1);
		if ($mail_check === $mail_alert && $email !== false) {
			alerts_update_alert_action (1, array('field1' => $email, 
							     'field1_recovery' => $email));
		}
		
		config_update_value ('initial_wizard', 1);	
	}
	
	//Change the language if is change in checkbox
	if ($change_language !== 0) {
		config_update_value ('language', $change_language);
	}
	
	if ($cancel_wizard !== 0) {
		config_update_value ('initial_wizard', 1);
	}
	
	return;
}

$email = db_get_value ('email', 'tusuario', 'id_user', $config['id_user']);
//Avoid to show default email
if ($email == 'admin@example.com') $email = '';

// Prints first step pandora registration
echo '<div id="login_id_dialog" title="' .
	__('Pandora FMS instance identification wizard') . '" style="display: none;">';
	
	echo '<div style="font-size: 10pt; margin: 20px;">';
	echo __('Please fill the following information in order to configure your Pandora FMS instance successfully') . '.';
	echo '</div>';
	
	echo '<div style="">';
		$table = new StdClass();
		$table->class = 'databox filters';
		$table->width = '100%';
		$table->data = array ();
		$table->size = array();
		$table->size[0] = '40%';
		$table->style[0] = 'font-weight:bold';
		$table->size[1] = '60%';
		$table->border = '5px solid';
		
		$table->data[0][0] = __('Language code for Pandora');
		$table->data[0][1] = html_print_select_from_sql (
			'SELECT id_language, name FROM tlanguage',
			'language', $config['language'] , '', '', '', true);
		
		$zone_name = array('Africa' => __('Africa'), 'America' => __('America'), 'Antarctica' => __('Antarctica'), 'Arctic' => __('Arctic'), 'Asia' => __('Asia'), 'Atlantic' => __('Atlantic'), 'Australia' => __('Australia'), 'Europe' => __('Europe'), 'Indian' => __('Indian'), 'Pacific' => __('Pacific'), 'UTC' => __('UTC'));

		if ($zone_selected == "") {
			if ($config["timezone"] != "") {
				list($zone) = explode("/", $config["timezone"]);
				$zone_selected = $zone;
			}
			else {
				$zone_selected = 'Europe';
			}
		}

		$timezones = timezone_identifiers_list();
		foreach ($timezones as $timezone) {
			if (strpos($timezone, $zone_selected) !== false) { 
				$timezone_country = preg_replace('/^.*\//', '', $timezone);
				$timezone_n[$timezone] = $timezone_country;
			}
		}			
		
		$table->data[2][0] = __('Timezone setup'). ' ' . ui_print_help_tip(
			__('Must have the same time zone as the system or database to avoid mismatches of time.'), true);
		$table->data[2][1] = html_print_select($zone_name, 'zone', $zone_selected, 'show_timezone()', '', '', true);
		$table->data[2][1] .= "&nbsp;&nbsp;". html_print_select($timezone_n, 'timezone', $config["timezone"], '', '', '', true);
		
		$table->data[4][0] = __('E-mail for receiving alerts');
		$table->data[4][1] = html_print_input_text ('email', $email, '', 50, 255, true);
		
		html_print_table ($table);
	echo '</div>';
	
	echo '<div style="position:absolute; margin: 0 auto; bottom: 0px; right: 10px; border: 1px solid #FFF; width: 570px">';
		echo '<div style="float: right; width: 20%;">';
		html_print_submit_button(__("Register"), 'id_dialog_button', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
		echo '</div>';
		echo '<div style="float: right; width: 20%;">';
			html_print_button(__("Cancel"), 'cancel', false, '', 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');  
		echo '</div>';
		echo '<div id="all-required" style="float: right; margin-right: 30px; display: none; color: red;">';
			echo __("All fields required");
		echo '</div>';
	echo '</div>';
	
echo '</div>';

?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

var default_language_displayed;

////////////////////////////////////////////////////////////////////////
//HELPER FUNCTIONS
function show_timezone () {
	zone = $("#zone").val();
	
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=godmode/setup/setup&select_timezone=1&zone=" + zone,
		dataType: "json",
		success: function(data) {
			$("#timezone").empty();
			jQuery.each (data, function (id, value) {
				timezone = value;
				var timezone_country = timezone.replace (/^.*\//g, "");
				$("select[name='timezone']").append($("<option>").val(timezone).html(timezone_country));
			});
		}
	});
}

////////////////////////////////////////////////////////////////////////
//EVENT FUNCTIONS
$("#submit-id_dialog_button").click (function () {
	
	//All fields required
	if ($("#text-email").val() == '') {
		$("#all-required").show();
	} else {
		var timezone = $("#timezone").val();
		var language = $("#language").val();
		var email_identification = $("#text-email").val();
		
		jQuery.post ("ajax.php",
			{"page": "general/login_required",
			"save_required_wizard": 1,
			"email": email_identification,
			"language": language,
			"timezone": timezone},
			function (data) {}
		);
					
		$("#login_id_dialog").dialog('close');
		first_time_identification ();
	}
});

$("#language").click(function () {
	var change_language = $("#language").val();
	
	if (change_language === default_language_displayed) return;
	jQuery.post ("ajax.php",
			{"page": "general/login_required",
			"change_language": change_language},
			function (data) {}
		);
	location.reload();
});

////////////////////////////////////////////////////////////////////////
//DISPLAY
$(document).ready (function () {
	
	$("#login_id_dialog").dialog({
		resizable: true,
		draggable: true,
		modal: true,
		height: 280,
		width: 630,
		overlay: {
				opacity: 0.5,
				background: "black"
			},
		closeOnEscape: false,
		open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
	});
	
	default_language_displayed = $("#language").val();
	
	$(".ui-widget-overlay").css("background", "#000");
	$(".ui-widget-overlay").css("opacity", 0.6);
	$(".ui-draggable").css("cursor", "inherit");
	
	$("#button-cancel").click (function () {
		jQuery.post ("ajax.php",
			{"page": "general/login_required",
			"cancel_wizard": 1},
			function (data) {}
		);
		
		$("#login_id_dialog" ).dialog('close');
	});
	
});

/* ]]> */
</script>
