<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Copyright (c) 2008 Ramon Novoa <rnovoa@artica.es>
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

///////////////////////////////////////////////////////////////////////////////
// General purpose functions
///////////////////////////////////////////////////////////////////////////////

// Displays the configuration in a textarea
function display_config () {
	global $agent_md5, $config, $id_agente, $lang_label, $nombre_agente;
	
	// Read configuration file
	$file_name = $config["remote_config"] . "/" . $agent_md5 . ".conf";
	$file = fopen($file_name, "rb");
	$agent_config = fread($file, filesize($file_name));
	fclose($file);

	// Display it
	echo '<form name="update" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente='.$id_agente.'&disk_conf='.$agent_md5.'">';	
	echo '<table width="650" cellpadding="4" cellspacing="4" class="databox_color">';
	echo 	'<tr>';
	echo 		'<td class="datos"><b>' . $lang_label["agent_name"] . '</b></td>';
	echo 		'<td class="datos">';
	echo			'<input disabled type="text" name="agente" size=30 value="' . $nombre_agente . '">';
	echo 			'<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $id_agente . '">';
	echo				'<img src="images/lupa.png" border="0" align="middle" alt="">';
	echo			'</a>';
	echo 			'<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=' . $id_agente . '">';
	echo				'<img src="images/cog.png" border="0" align="middle" alt="">';
	echo			'</a>';	
	echo		'</td>';
	echo 	'</tr>';
	echo 	'<tr>';
	echo 		'<td class="datos2" colspan="2">';
	echo 			'<textarea class="conf_editor" name="disk_conf">';
	echo $agent_config;
	echo 			'</textarea>';
	echo 		'</td>';
	echo 	'</tr>';
	echo '</table>';
	echo '<table width="650"><tr><td align="right">';	
	echo 	'<tr>';	
	echo 		'<td align="right">';
	echo 			'<input name="updbutton" type="submit" class="sub upd" value="'.$lang_label["update"].'">';
	echo 		'</td>';
	echo 	'</tr>';
	echo '</table>';	
	echo '</form>';	
}

// Saves the configuration and the md5 hash
function save_config ($agent_config) {
	global $agent_md5, $config;

	$agent_config = unsafe_string ($agent_config);	
	// Save configuration
	$file = fopen($config["remote_config"] . "/" . $agent_md5 . ".conf", "wb");
	fwrite($file, $agent_config);
	fclose($file);
		
	// Save configuration md5
	$config_md5 = md5($agent_config);
	$file = fopen($config["remote_config"] . "/" . $agent_md5 . ".md5", "wb");
	fwrite($file, $config_md5);
	fclose($file);	
}

///////////////////////////////////////////////////////////////////////////////
// Main code
///////////////////////////////////////////////////////////////////////////////

// Load global vars
require("include/config.php");
if (give_acl($id_user, 0, "AW")!=1) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
};

// Update configuration
if (isset($_POST["disk_conf"])) {
	save_config(str_replace("\r\n", "\n", $_POST["disk_conf"]));
	echo "<h3 class='suc'>" . $lang_label["update_agent_ok"] . "</h3>";
}

display_config ();

// Footer
echo "</div><div id='foot'>";
include ("general/footer.php");
echo "</div>";

?>
