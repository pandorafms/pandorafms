<?PHP

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Link Management");
	require ("general/noaccess.php");
	exit;
}


	if (isset($_POST["create"])){ // If create
		$name = get_parameter_post ("name");
		$link = get_parameter_post ("link");
		$sql_insert = "INSERT INTO tlink (name,link) VALUES ('$name','$link')";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".__('There was a problem creating link')."</h3>";
		else {
			echo "<h3 class='suc'>".__('Link successfully created')."</h3>"; 
			$id_link = mysql_insert_id();
		}
	}

	if (isset($_POST["update"])){ // if update
		$id_link = entrada_limpia($_POST["id_link"]);
		$name = entrada_limpia($_POST["name"]);
		$link = entrada_limpia($_POST["link"]);
    	$sql_update ="UPDATE tlink SET name = '".$name."', link ='".$link."' WHERE id_link = '".$id_link."'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".__('There was a problem modifying link')."</h3>";
		else
			echo "<h3 class='suc'>".__('Link successfully updated')."</h3>";
	}
	
	if (isset($_GET["borrar"])){ // if delete
		$id_link = entrada_limpia($_GET["borrar"]);
		$sql_delete= "DELETE FROM tlink WHERE id_link = ".$id_link;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".__('There was a problem deleting link')."</h3>";
		else
			echo "<h3 class='suc'>".__('Link successfully deleted')."</h3>"; 

	}

	// Main form view for Links edit
	if ((isset($_GET["form_add"])) or (isset($_GET["form_edit"]))){
		if (isset($_GET["form_edit"])){
			$creation_mode = 0;
				$id_link = entrada_limpia($_GET["id_link"]);
				$sql1='SELECT * FROM tlink WHERE id_link = '.$id_link;
				$result=mysql_query($sql1);
				if ($row=mysql_fetch_array($result)){
						$nombre = $row["name"];
				$link = $row["link"];
                	}
				else echo "<h3 class='error'>".__('Name error')."</h3>";
		} else { // form_add
			$creation_mode =1;
			$nombre = "";
			$link = "";
		}

		// Create link
        echo "<h2>".__('Pandora Setup')." &gt; ";
		echo __('Link management')."</h2>";
		echo '<table class="databox" cellpadding="4" cellspacing="4" width="500">';
		echo '<form name="ilink" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/links">';
        	if ($creation_mode == 1)
				echo "<input type='hidden' name='create' value='1'>";
			else
				echo "<input type='hidden' name='update' value='1'>";
		echo "<input type='hidden' name='id_link' value='"; ?>
		<?php if (isset($id_link)) {echo $id_link;} ?>
		<?php
		echo "'>";
		echo '<tr>
		<td class="datos">'.__('Link name').'</td>
		<td class="datos"><input type="text" name="name" size="35" value="'.$nombre.'">';
		echo '<tr>
		<td class="datos2">'.__('Link').'</td>
		<td class="datos2">
		<input type="text" name="link" size="35" value="'.$link.'"></td>';
		echo '</tr>';	
		echo "</table>";
		echo "<table width='500px'>";
		echo "<tr><td align='right'>
		<input name='crtbutton' type='submit' class='sub upd' value='".__('Update')."'>";
		echo '</form></td></tr></table>';
	}

	else {  // Main list view for Links editor
		echo "<h2>".__('Pandora Setup')." &gt; ";
		echo  __('Link management')."</h3>";
		echo "<table cellpadding='4' cellspacing='4' class='databox'>";
		echo "<th width='180px'>".__('Link name')."</th>";
		echo "<th width='80px'>".__('Delete')."</th>";
		$sql1='SELECT * FROM tlink ORDER BY name';
		$result=mysql_query($sql1);
		$color=1;
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=gsetup&sec2=godmode/setup/links&form_edit=1&id_link=".$row["id_link"]."'>".$row["name"]."</a></b></td>";
			echo '<td class="'.$tdcolor.'" align="center"><a href="index.php?sec=gsetup&sec2=godmode/setup/links&id_link='.$row["id_link"].'&borrar='.$row["id_link"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img border=0 src="images/cross.png"></a></td></tr>';
		}
			echo "</table>";
			echo "<table width='290px'>";
			echo "<tr><td align='right'>";
			echo "<form method='post' action='index.php?sec=gsetup&sec2=godmode/setup/links&form_add=1'>";
			echo "<input type='submit' class='sub next' name='form_add' value='".__('Add')."'>";
			echo "</form></table>";
	}
?>
