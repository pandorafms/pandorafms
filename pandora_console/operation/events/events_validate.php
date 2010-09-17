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

require_once ("include/functions_events.php"); //Event processing functions
require_once ("include/functions_alerts.php"); //Alerts processing functions

check_login ();

if (! give_acl ($config["id_user"], 0, "IR")) {
	audit_db ($config["id_user"], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}
	
$ids = (array) get_parameter ("eventid", -1);

$url_val = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;search=" .
	rawurlencode($search) . "&amp;event_type=" . $event_type .
	"&amp;severity=" . $severity . "&amp;status=" . $status . "&amp;ev_group=" .
	$ev_group . "&amp;refr=" . $config["refr"] . "&amp;id_agent=" .
	$id_agent . "&amp;id_event=" . $id_event . "&amp;pagination=" .
	$pagination . "&amp;group_rep=" . $group_rep . "&amp;event_view_hr=" .
	$event_view_hr . "&amp;id_user_ack=" . $id_user_ack . "&amp;offset=" . $offset . "&amp;validate=1";

$event_list = "<b>".__('Events to validate').":</b>";

$event_list .= '';
$event_list .= "<ul>";
$any_alert = false;
$any_inprocess = false;
foreach($ids as $key => $id) {
	$event = get_event($id);
	$event_list .= "<il>".$event['evento']."</il><br>";
	if($event['id_alert_am'] != 0) {
		$any_alert = true;
	}
	if($event['estado'] == 2) {
		$any_inprocess = true;
	}
}
$event_list .= "</ul>";

//Hiden row with description form
$string = '<form  method="post" action="'.$url_val.'">';
$string .= '<table border="0" style="width:80%; margin-left: 10%;"><tr><td align="left" valign="top" width="30px">';
$string .=  '<td align="right"><b>' . __('Comment:') . '</b></td>';
$string .= print_input_hidden('eventid', implode(',',$ids), true);
$string .=  '<td align="left" width="450px"><b>' . print_textarea("comment", 2, 10, '', 'style="min-height: 10px; width: 250px;"', true) . '</b></td>';
$string .= '<td align="left" width="200px">'; 
$string .= '<div style="text-align:center;">';
if(!$any_inprocess) {
	$string .= print_select(array('1' => __('Validate'), '2' => __('Set in process')), 'select_validate', '', '', '', 0, true, false, false, 'select_validate').'<br><br>';
	$string .= print_submit_button (__('Change status'), 'validate', false, 'class="sub ok validate_event" id="validate"', true).'</div>';
}else {
	$string .= print_submit_button (__('Validate'), 'validate', false, 'class="sub ok validate_event" id="validate"', true).'</div>';
}
$string .= '</td><td width="400px">';
if($any_alert) {
	$string .= '<div class="standby_alert_checkbox" style="display: none">'.__('Set alert on standby').'<br>'.print_checkbox('standby-alert', 'ff2', false, true).'</div>';
}
$string .= '</td></tr></table></form>';	

echo $string;

echo "<br><br>".$event_list;

?>
