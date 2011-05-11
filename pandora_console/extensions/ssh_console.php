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

extensions_add_opemode_tab_agent ('ssh_console','SSH Console','extensions/ssh_console/ssh.png',"main_ssh_console");


function main_ssh_console () {

	$id_agente = get_parameter ("id_agente");

	$ip = db_get_sql ("SELECT direccion FROM tagente WHERE id_agente = $id_agente");

?>
<div>
<applet code="com.mindbright.application.MindTerm.class" style="border: 2px;" archive="extensions/ssh_console/mindterm.jar" width="720" height="500">
	<param name="sepframe" value="false">
	<param name="debug" value="false">
	<param name="verbose" value="false">
	<param name="bg-color" value="black">
	<param name="fg-color" value="white">
	<param name="resizable" value="true">
	<param name="save-lines" value="2000">
	<param name="menus" value="pop2"5>
	<param name="exit-on-logout" value="true">
	<param name="server" value="<?PHP echo $ip; ?>">
</applet>
</div>
<?php

}

?>
