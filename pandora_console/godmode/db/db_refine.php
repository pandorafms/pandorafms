<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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



// Load global vars
require_once ("include/config.php");

check_login ();	

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access Database Debug Admin section");
	require ("general/noaccess.php");
	return;
}

if ((isset ($_GET["operacion"])) && (!isset ($_POST["update_agent"]))) {
	// DATA COPY
	if (isset ($_POST["eliminar"])) {
		echo "<h2>".__('Delete Data')."</h2>";
		// First checkings
		
		$max = $_POST["max"];
		$min = $_POST["min"];
		if ($max == $min) {
			echo "<h3 class='error'>ERROR ".__('Maximum equal to minimum')."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}
		$origen_modulo = mysql_real_esape_string ($_POST["origen_modulo"]);
	 	if (count($origen_modulo) <= 0) {
			echo "<h3 class='error'>ERROR: ".__('No modules has been selected')."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}

		// Source (agent)
		$id_origen = $_POST["origen"];
		
		// Copy
		for ($a = 0; $a < count ($origen_modulo); $a++) { // For every agent selected
			$id_agentemodulo = $origen_modulo[$a];
			echo "<br><br>".__('Filtering data module')."<b> [".get_agentmodule_name ($id_agentemodulo)."]</b>";
			$sql ='DELETE FROM tagente_datos WHERE id_agente_modulo = '.$origen_modulo[$a].' AND ( datos < '.$min.' OR  datos > '.$max.' )';
			process_sql ($sql);
			//echo "<br>DEBUG DELETE $sql1 <br>";
		} 
	} //if copy modules or alerts
} else { // Form view
	?>
	<h2><?php echo __('Database Maintenance'); ?> &gt; 
	<?php echo __('Database debug'); ?></h2>
	<form method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_refine&operacion=1">
	<table width='500' border='0' cellspacing='4' cellpadding='4' class='databox'>
	<tr>
	<td class="datost"><b><?php echo __('Source agent'); ?></b><br><br>
	<select name="origen" class="w130">
	<?php
	if ( (isset ($_POST["update_agent"])) && (isset ($_POST["origen"])) ) {
		echo "<option value=".$_POST["origen"].">".get_agent_name($_POST["origen"]);
	}
	// Show combo with agents
	$sql1='SELECT * FROM tagente';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ){
			if ( $_POST["origen"] != $row["id_agente"])
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
		else
			echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
	}
	echo '</select>&nbsp;&nbsp;<input type=submit name="update_agent" class="sub upd" value="'.__('Get Info').'"><br><br>';
	echo "<b>".__('Modules')."</b><br><br>";
	echo "<select name='origen_modulo[]' size=5 multiple=yes class='w130'>";
	if ( (isset($_POST["update_agent"])) && (isset ($_POST["origen"])) ) {
		// Populate Module/Agent combo
		$agente_modulo = $_POST["origen"];
		$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$agente_modulo;
		$result = mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
	 		echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"]."</option>";	
		}
	}
	echo "</select>";
	?>
	<td class="datost"><b><?php echo __('Purge data out these limits'); ?></b><br><br>
		<table cellspacing=3 cellpadding=3 border=0>
		<tr class=datos><td><?php echo __('Minimum'); ?><td><input type="text" name="min" size=4 value=0>
		<tr class=datos><td><?php echo __('Maximum'); ?><td><input type="text" name="max" size=4 value=0>	
		<tr><td></td></tr>
		<tr><td class="bot" colspan="2" align="right">
		<input type="submit" name="eliminar" class="sub delete" value="<?php echo __('Delete').'" onClick="if (!confirm("'.__('Are you sure?').'")) return false;>'; ?>
		</table>
	</td></tr>

	</table>
	
<?php
}
?>
