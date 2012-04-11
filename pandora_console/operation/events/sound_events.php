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

if ((! file_exists("../../include/config.php")) || (! is_readable("../../include/config.php"))) {
	exit;
}

require_once ('../../include/config.php');
require_once ('../../include/functions.php');
require_once ('../../include/functions_db.php');
require_once ('../../include/auth/mysql.php');

global $config;

// Real start
session_start ();

// Check user
check_login ();
$config["id_user"] = $_SESSION["id_usuario"];


if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation","Trying to access event viewer");
	require ("general/noaccess.php");
	
	return;
}

echo "<html>";
echo "<head>";
echo "<title>" . __("Sound Events") . "</title>";
echo "<style type='text/css'>";
?>
*
{
	margin: 0;
	padding: 0;
}

img {
	border: 0;
}
<?php
echo "</style>";
echo '<link rel="icon" href="../../images/pandora.ico" type="image/ico" />';
echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css" />';
echo "</head>";
echo "<body>";

echo "<h1 style='background: #66AA44; color: #fff;'>" . html_print_image('images/pandora_header_logo.png', true) . __("Sound Events") . "</h1>";

$table = null;
$table->width = '100%';

$table->size[0] = '10%';
$table->size[1] = '90%';
$table->style[0] = 'font-weight: bold; vertical-align: top;';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(false, "AR", true, 'group', '', 'changeGroup();', '', 0, true);
$table->data[1][0] = __('Type');
$table->data[1][1] = html_print_checkbox('alert_fired', 'alert_fired', true, true, false, 'changeType();') . __('Alert fired') . '<br />' .
	html_print_checkbox('critical', 'critical', true, true, false, 'changeType();') . __('Monitor critical') . '<br />' .
	html_print_checkbox('warning', 'warning', true, true, false, 'changeType();') . __('Monitor warning') . '<br />' .
$table->data[2][0] = '';
$table->data[2][1] = '<a href="javascript: toggleButton();">' . html_print_image("images/play.button.png", true, array("id" => "button")) . '</a>';
$table->data[2][1] .= '<a href="javascript: ok();">' . html_print_image("images/ok.button.png", true, array("style" => "margin-left: 10px;")) . '</a>';

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

function changeGroup() {
	group = $("#group").val();
}

function changeType() {
	alert_fired = $("input[name=alert_fired]").attr('checked');
	critical = $("input[name=critical]").attr('checked');
	warning = $("input[name=warning]").attr('checked');
}

function toggleButton() {
	if ($("#button").attr('src') == '../../images/pause.button.png') {
		$("#button").attr('src', '../../images/play.button.png');
		stopSound();
	}
	else {
		$("#button").attr('src', '../../images/pause.button.png');
		forgetPreviousEvents();
		startSound();
	}
}

function ok() {
	fired = false;
	$('body').css('background', 'white');
	$('embed').remove();
}

function stopSound() {
	$('embed').remove();

	$('body').css('background', 'white');
	redBackground = false;
	
	running = false;
	fired = false;
}

function startSound() {
	$('embed').remove();
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
						$('embed').remove();
						$('body').append("<embed src='../../" + data['sound'] + "' autostart='true' hidden='true' loop='true'>");
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
			window.moveBy(5, 0);
		}
		else {
			redBackground = true;
			$('body').css('background-color', 'red');
			window.moveBy(-5, 0);
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
