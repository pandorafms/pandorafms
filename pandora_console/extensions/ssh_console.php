<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

add_extension_opemode_tab_agent ('ssh_console','SSH Console','extensions/ssh_console/ssh.png',"main_ssh_console");


function main_ssh_console () {

	$id_agente = get_parameter ("id_agente");

	$ip = get_db_sql ("SELECT direccion FROM tagente WHERE id_agente = $id_agente");

?>
<div>
<APPLET CODE="com.mindbright.application.MindTerm.class" style="border: 2px;" ARCHIVE="extensions/ssh_console/mindterm.jar" WIDTH=720 HEIGHT=500>
<PARAM NAME="sepframe" value="false">
<PARAM NAME="debug" value="false">
<param name="verbose" value="false">
<param name="bg-color" value="black">
<param name="fg-color" value="white">
<param name="resizable" value="true">
<param name="save-lines" value="2000">
<param name="menus" value="pop2"5>
<param name="exit-on-logout" value="true">
<PARAM NAME="server" value="<?PHP echo $ip; ?>">
</applet>
</div>
<?php


}

?>
