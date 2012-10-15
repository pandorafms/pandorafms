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

extensions_add_opemode_tab_agent ('ssh_gateway','SSH Gateway','extensions/ssh_gateway/secure_console.png',"ssh_gateway", "v1r1");

function ssh_gateway () {

	$SERVER_ADDR = $_SERVER['SERVER_ADDR'];

	$HOST = get_parameter ("host", "");
	$USER = get_parameter ("user", "");

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

	if (($HOST == "") OR ($USER == "")){

		if ($COMMIT == 1){
			echo "<h3 class=error>".__("You need to specify a user and a host address")."</h3>";

		}

		echo "<form method=post>";
		echo "<table class=databox cellspacing=4 cellpadding=4>";
		echo "<td>".__("Host address")."<td><input type=text size=25 value='$HOST' name=host>";
		echo "<tr>";
		echo "<td>".__("User")."<td><input type=text size=25 value='$USER' name=user>";
		echo "<tr><td>";
		echo __("Connect mode")."<td><select name=mode>";
		if ($MODE == "telnet"){
			echo "<option>telnet";
			echo "<option>ssh";
		} else {
			echo "<option>ssh";
			echo "<option>telnet";
		}	
		echo "</select>";
	
		echo "&nbsp;&nbsp;&nbsp;<input type=submit name=connect class='sub upd' value=".__("Connect").">";
		echo "<td><input type=hidden name=commit value=1>";
		echo "</form></table>";
	}

	else {
		if ($MODE == "ssh")
			echo "<iframe style='border: 0px' src='http://".$SERVER_ADDR.":8022/anyterm.html?param=$USER@$HOST' width='100%' height=550>";
		else
			echo "<iframe style='border: 0px' src='http://".$SERVER_ADDR.":8023/anyterm.html?param=$HOST' width='100%' height=550>";

	        echo "</iframe>";
	}

}

?>
