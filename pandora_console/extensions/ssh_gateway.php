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

$id_agente = get_parameter ("id_agente");
	
// This extension is usefull only if the agent has associated IP
$address = agents_get_address($id_agente);

if (!empty($address) || empty($id_agente)) {
	extensions_add_opemode_tab_agent ('ssh_gateway','SSH Gateway','extensions/ssh_gateway/secure_console.png',"ssh_gateway", "v1r1");
}

function ssh_gateway () {
	
	$SERVER_ADDR = $_SERVER['SERVER_ADDR'];
	
	$HOST = get_parameter ("host", "");
	$USER = get_parameter ("user", "");
	$PORT = get_parameter ("port", 0);
	
	// TODO: Put aditional filtering for ";" and "&" characters in user & host for security
	// because these params are passed to server exec() call :)
	
	$COMMIT = get_parameter ("commit", 0);
	$MODE = get_parameter ("mode", "ssh");
	
	if ($MODE == "telnet")
		$USER = "<auto>";
	
	$id_agente = get_parameter ("id_agente");
	
	$ip = db_get_sql ("SELECT direccion FROM tagente WHERE id_agente = $id_agente");
	
	if ($HOST == "")
		$HOST = $ip;
	
	if (($HOST == "") OR ($USER == "")) {
		if ($COMMIT == 1) {
			ui_print_error_message(__("You need to specify a user and a host address"));
		}
		
		echo "<form method=post>";
		echo "<table class='databox filters' cellspacing=4 cellpadding=4 width=100%>";
		echo "<td>".__("Host address")."<td><input type=text size=25 value='$HOST' name=host>";
		//echo "<tr>";
		echo "<td>".__("User")."<td><input type=text size=25 value='$USER' name=user>" . ui_print_help_tip(__('For security reasons the following characters are not allowed: %s', '< > | ` $ ; &'), true);
		echo "<td rowspan=2 vertical_aling='middle'>&nbsp;&nbsp;&nbsp;<input type=submit name=connect class='sub upd' style='margin-top:0px;' value=".__("Connect").">";
		echo "<input type=hidden name=commit value=1></td>";
		echo "<tr>";
		echo "<td>".__("Port (use 0 for default)")."<td><input type=text size=5 value='$PORT' name=port>";
		echo "<td>";
		echo __("Connect mode")."<td><select name=mode>";
		if ($MODE == "telnet") {
			echo "<option>telnet";
			echo "<option>ssh";
		}
		else {
			echo "<option>ssh";
			echo "<option>telnet";
		}
		echo "</select>";
		
		
		echo "</form></table>";
	}
	
	else {
		if ($MODE == "telnet") {
			if ($PORT == 0)
				$PORT = 23;
		}
		else {
			if ($PORT == 0)
				$PORT = 22;
		}
		
		if ($MODE == "ssh")
			echo "<iframe style='border: 0px' src='http://".$SERVER_ADDR.":8022/anyterm.html?param=-p $PORT $USER@$HOST' width='100%' height=550>";
		else
			echo "<iframe style='border: 0px' src='http://".$SERVER_ADDR.":8023/anyterm.html?param=$HOST $PORT' width='100%' height=550>";
		
		echo "</iframe>";
	}
}
?>
