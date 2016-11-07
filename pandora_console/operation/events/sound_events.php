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

// Real start
session_start ();

// Load global vars
if ((! file_exists("../../include/config.php")) || (! is_readable("../../include/config.php"))) {
	exit;
}

require_once ('../../include/config.php');
require_once ('../../include/functions.php');
require_once ('../../include/functions_db.php');
require_once ('../../include/auth/mysql.php');

global $config;

// Check user
check_login ();
$config["id_user"] = $_SESSION[$config['homeurl_static']]["id_usuario"];

$event_a = check_acl ($config['id_user'], 0, "ER");
$event_w = check_acl ($config['id_user'], 0, "EW");
$event_m = check_acl ($config['id_user'], 0, "EM");
$access = ($event_a == true) ? 'ER' : (($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'ER'));

if (! check_acl ($config['id_user'], 0, "ER") && ! check_acl ($config['id_user'], 0, "EW") && ! check_acl ($config['id_user'], 0, "EM")) {
	db_pandora_audit("ACL Violation","Trying to access event viewer");
	require ("general/noaccess.php");
	
	return;
}

echo "<html>";
echo "<head>";
echo "<title>" . __("Sound Events") . "</title>";
?>
<style type='text/css'>
	* {
		margin: 0;
		padding: 0;
	}
	
	img {
		border: 0;
	}
</style>
<?php
echo '<link rel="icon" href="../../images/pandora.ico" type="image/ico" />';
echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css" />';
echo "</head>";
echo "<body>";
echo html_print_image('images/pandora_logo_head.png', true);
echo "<h1 style='background: #66AA44; color: #fff;'>" . __("Sound console"). "</h1>";

$table = null;
$table->width = '100%';

$table->size[0] = '10%';
$table->size[1] = '90%';
$table->style[0] = 'font-weight: bold; vertical-align: top;';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, $access, true, 'group', '', 'changeGroup();', '', 0, true);
$table->data[1][0] = __('Type');
$table->data[1][1] = html_print_checkbox('alert_fired', 'alert_fired', true, true, false, 'changeType();') . __('Alert fired') . '<br />' .
	html_print_checkbox('critical', 'critical', true, true, false, 'changeType();') . __('Monitor critical') . '<br />' .
	html_print_checkbox('warning', 'warning', true, true, false, 'changeType();') . __('Monitor warning') . '<br />' .
$table->data[2][0] = '';
$table->data[2][1] = '<a href="javascript: toggleButton();">' .
	html_print_image("images/play.button.png", true, array("id" => "button")) .
	'</a>';
$table->data[2][1] .= '<a href="javascript: ok();">' .
	html_print_image("images/ok.button.png", true,
		array("style" => "margin-left: 10px;")) . '</a>';

html_print_table($table);
?>
<script src="../../include/javascript/jquery.js" type="text/javascript"></script>
<script type="text/javascript">
var group = 0;
var alert_fired = true;
var critical = true;
var warning = true;

var running = false;
var fired = false;

var id_row = 0;

var redBackground = false;

var button_play_status = "play";

function changeGroup() {
	group = $("#group").val();
}

function changeType() {
	alert_fired = $("input[name=alert_fired]").attr('checked');
	critical = $("input[name=critical]").attr('checked');
	warning = $("input[name=warning]").attr('checked');
}

function toggleButton() {
	
	if (button_play_status == 'pause') {
	//~ if ($("#button").attr('src') == '../../images/pause.button.png') {
		
		$("#button").attr('src', '../../images/play.button.png');
		stopSound();
		
		button_play_status = 'play';
	}
	else {
		
		$("#button").attr('src', '../../images/pause.button.png');
		forgetPreviousEvents();
		startSound();
		
		button_play_status = 'pause';
	}
}

function ok() {
	fired = false;
	$('body').css('background', 'white');
	$('audio').remove();
}

function stopSound() {
	$('audio').remove();
	
	$('body').css('background', 'white');
	redBackground = false;
	
	running = false;
	fired = false;
}

function startSound() {
	//running = true;
}

function forgetPreviousEvents() {
	jQuery.post ("../../ajax.php",
		{"page" : "operation/events/events",
			"get_events_fired": 1,
			"id_group": group,
			"alert_fired": alert_fired,
			"critical": critical,
			"warning": warning,
			"id_row": id_row
		},
		function (data) {
			firedId = parseInt(data['fired']);
			if (firedId != 0) {
				id_row = firedId;
			}
			running = true;
		},
		"json"
	);
}

function check_event() {
	if (running) {
		if (!fired) {
			jQuery.post ("../../ajax.php",
				{"page" : "operation/events/events",
					"get_events_fired": 1,
					"id_group": group,
					"alert_fired": alert_fired,
					"critical": critical,
					"warning": warning,
					"id_row": id_row
				},
				function (data) {
					firedId = parseInt(data['fired']);
					if (firedId != 0) {
						id_row = firedId;
						fired = true;
						$('body').append("<audio src='../../" + data['sound'] + "' autoplay='true' hidden='true' loop='true'>");
					}
				},
				"json"
			);
		}
	}
}

function animation() {
	if (fired) {
		window.focus();
		if (redBackground) {
			redBackground = false;
			$('body').css('background', 'white');
			//~ window.moveBy(5, 0);
		}
		else {
			redBackground = true;
			$('body').css('background-color', 'red');
			//~ window.moveBy(-5, 0);
		}
	}
}

$(document).ready (function () {
	setInterval("check_event()", (10 * 1000)); //10 seconds between ajax request
	setInterval("animation()", (0.1 * 1000)); 
});
</script>
<?php
echo "</body>";
echo "</html>";
?>
