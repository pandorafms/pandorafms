<?php 

// Pandora FMS - the Free Monitoring System
// ========================================
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
require("include/config.php");

if (comprueba_login() == 0) 
 	if ((give_acl($id_user, 0, "PM")==1) or (dame_admin($id_user)==1)) {	
	if (isset($_GET["update"])){
		$config["block_size"]=$_POST["block_size"];
		$config["language"]=$_POST["language_code"];
		$config["days_compact"]=$_POST["days_compact"];
		$config["days_purge"]=$_POST["days_purge"];
		$config["graph_res"]=$_POST["graph_res"];
		$config["step_compact"]=$_POST["step_compact"];
		$config["show_unknown"]=$_POST["show_unknown"];
		$config["show_lastalerts"]=$_POST["show_lastalerts"];
		$config["style"] = $_POST["style"];
        $config["remote_config"] = $_POST["remote_config"];
		
		$config["graph_color1"] = $_POST["graph_color1"];
		$config["graph_color2"] = $_POST["graph_color2"];
		$config["graph_color3"] = $_POST["graph_color3"];

        $result2=mysql_query("UPDATE tconfig SET VALUE='".$config["remote_config"]."' WHERE TOKEN='remote_config'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["block_size"]."' WHERE TOKEN='block_size'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["language"]."' WHERE TOKEN='language_code'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["days_purge"]."' WHERE TOKEN='days_purge'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["days_compact"]." ' WHERE TOKEN='days_compact'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["graph_res"]."' WHERE TOKEN='graph_res'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["step_compact"]."' WHERE TOKEN='step_compact'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["show_unknown"]."' WHERE token='show_unknown'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["show_lastalerts"]."' WHERE token='show_lastalerts'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["style"]."' WHERE token='style'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["graph_color1"]."' WHERE token='graph_color1'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["graph_color2"]."' WHERE token='graph_color2'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config["graph_color3"]."' WHERE token='graph_color3'");

	}	
	echo "<h2>".$lang_label["setup_screen"]." &gt; ";
	echo $lang_label["general_config"]."</h2>";
	echo "<form name='setup' method='POST' action='index.php?sec=gsetup&amp;sec2=godmode/setup/setup&update=1'>";
	echo '<table width="500" cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr><td class="datos">'.$lang_label["language_code"].'</td>';
	echo '<td class="datos"><select name="language_code" onChange="javascript:this.form.submit();" width="180px">';
	
	$sql="SELECT * FROM tlanguage";
	$result=mysql_query($sql);

	$result2=mysql_query("SELECT * FROM tlanguage WHERE id_language = '".$config["language"]."'");
	if ($row2=mysql_fetch_array($result2)){
		echo '<option value="'.$row2["id_language"].'">'.$row2["name"]."</option>";
	}
	while ($row=mysql_fetch_array($result)){
		echo "<option value=".$row["id_language"].">".$row["name"]."</option>";
	}
	echo '</select></td></tr>';

    echo '<tr><td class="datos2">'.lang_string ("Remote config directory");
    echo '<td class="datos2"><input type="text" name="remote_config" size=30 value="'.$config["remote_config"].'"></td></tr>';
	
	echo '<tr><td class="datos">'.lang_string("Graph color (min)");
	echo '<td class="datos"><input type="text" name="graph_color1" size=8 value="'.$config["graph_color1"].'"></td></tr>';

	echo '<tr><td class="datos2">'.lang_string("Graph color (avg)");
	echo '<td class="datos2"><input type="text" name="graph_color2" size=8 value="'.$config["graph_color2"].'"></td></tr>';

	echo '<tr><td class="datos">'.lang_string("Graph color (max)");
	echo '<td class="datos"><input type="text" name="graph_color3" size=8 value="'.$config["graph_color3"].'"></td></tr>';

	echo '<tr><td class="datos2">'.$lang_label["days_compact"];
	echo '<td class="datos2"><input type="text" name="days_compact" size=5 value="'.$config["days_compact"].'"></td></tr>';
	
	echo '<tr><td class="datos">'.$lang_label["days_purge"];
	echo '<td class="datos"><input type="text" name="days_purge" size=5 value="'.$config["days_purge"].'"></td></tr>';
	
	echo '<tr><td class="datos2">'.$lang_label["graph_res"];
	echo '<td class="datos2"><input type="text" name="graph_res" size=5 value="'.$config["graph_res"].'"></td></tr>';
	
	echo '<tr><td class="datos">'.$lang_label["step_compact"].'</td>';
	echo '<td class="datos"><input type="text" name="step_compact" size=5 value="'.$config["step_compact"].'"></td></tr>';

	echo '<tr><td class="datos2">'.$lang_label["show_unknown"].'</td>';
	echo '<td class="datos2"><select name="show_unknown" class="w120">';
	if ($config["show_unknown"]==1) {
		echo '<option value="1">'.$lang_label["active"].'</option>';
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
	}
	else {
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
		echo '<option value="1">'.$lang_label["active"].'</option>';
	}

	echo '<tr><td class="datos">'.$lang_label["show_lastalerts"];
	echo '<td class="datos"><select name="show_lastalerts" class="w120">';
	if ($config["show_lastalerts"]==1) {
		echo '<option value="1">'.$lang_label["active"].'</option>';
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
	}
	else {
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
		echo '<option value="1">'.$lang_label["active"].'</option>';
	}

	echo '<tr><td class="datos2">'.$lang_label["style_template"].'</td>';
	echo '<td class="datos2">';
	echo '<select name="style" class="w155">';
	if ($config["style"] != ""){
		echo '<option>'.$config["style"].'</option>';
      	}
	$ficheros2 = list_files('include/styles/', "pandora",1, 0);
        $a=0;
        while (isset($ficheros2[$a])){
		$fstyle = substr($ficheros2[$a],0,strlen($ficheros2[$a])-4);
		if (($fstyle != $config["style"]) AND ($fstyle != "pandora_minimal"))
			echo "<option>".$fstyle."</option>";
        	$a++;
        }
        echo '</select>';


    echo '<tr><td class="datos">'.$lang_label["block_size"];
    echo '<td class="datos"><input type="text" name="block_size" size=5 value="'.$config["block_size"].'"></td></tr>';

	echo "</table>";
	echo "<table width=500>";
	echo "<tr><td align='right'>";
	echo '<input type="submit" class="sub upd" value="'.$lang_label["update"].'">';
	echo "</td></tr>";
	echo "</table>";
}
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management");
		require ("general/noaccess.php");
	}
