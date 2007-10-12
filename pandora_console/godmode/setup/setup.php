<?php 

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
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
		$block_size=$_POST["block_size"];
		$language_code=$_POST["language_code"];
		$days_compact=$_POST["days_compact"];
		$days_purge=$_POST["days_purge"];
		$config_graph_res=$_POST["graph_res"];
		$config_step_compact=$_POST["step_compact"];
		$config_bgimage=$_POST["bgimage"];
		$config_show_unknown=$_POST["show_unknown"];
		$config_show_lastalerts=$_POST["show_lastalerts"];
		$config_style = $_POST["style"];
		
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$block_size."' WHERE TOKEN='block_size'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$language_code."' WHERE TOKEN='language_code'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$days_purge."' WHERE TOKEN='days_purge'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$days_compact." ' WHERE TOKEN='days_compact'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_graph_res."' WHERE TOKEN='graph_res'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_step_compact."' WHERE TOKEN='step_compact'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_bgimage."' WHERE token='bgimage'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_show_unknown."' WHERE token='show_unknown'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_show_lastalerts."' WHERE token='show_lastalerts'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_style."' WHERE token='style'");
	}	
	echo "<h2>".$lang_label["setup_screen"]." &gt; ";
	echo $lang_label["general_config"]."</h2>";
	echo "<form name='setup' method='POST' action='index.php?sec=gsetup&amp;sec2=godmode/setup/setup&update=1'>";
	echo '<table width="500" cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr><td class="datos">'.$lang_label["language_code"].'</td>';
	echo '<td class="datos"><select name="language_code" onChange="javascript:this.form.submit();" width="180px">';
	
	$sql="SELECT * FROM tlanguage";
	$result=mysql_query($sql);

	// This combo is dedicated to Raul... beautiful interface for dirty minds :-D
	$result2=mysql_query("SELECT * FROM tlanguage WHERE id_language = '$language_code'");
	if ($row2=mysql_fetch_array($result2)){
		echo '<option value="'.$row2["id_language"].'">'.$row2["name"]."</option>";
	}
	while ($row=mysql_fetch_array($result)){
		echo "<option value=".$row["id_language"].">".$row["name"]."</option>";
	}
	echo '</select></td></tr>';
			
	echo '<tr><td class="datos2">'.$lang_label["block_size"];
	echo '<td class="datos2"><input type="text" name="block_size" size=5 value="'.$block_size.'"></td></tr>';
	
	echo '<tr><td class="datos">'.$lang_label["days_compact"];
	echo '<td class="datos"><input type="text" name="days_compact" size=5 value="'.$days_compact.'"></td></tr>';
	
	echo '<tr><td class="datos2">'.$lang_label["days_purge"];
	echo '<td class="datos2"><input type="text" name="days_purge" size=5 value="'.$days_purge.'"></td></tr>';
	
	echo '<tr><td class="datos">'.$lang_label["graph_res"];
	echo '<td class="datos"><input type="text" name="graph_res" size=5 value="'.$config_graph_res.'"></td></tr>';
	
	echo '<tr><td class="datos2">'.$lang_label["step_compact"].'</td>';
	echo '<td class="datos2"><input type="text" name="step_compact" size=5 value="'.$config_step_compact.'"></td></tr>';

	
	echo '<tr><td class="datos">'.$lang_label["show_unknown"].'</td>';
	echo '<td class="datos"><select name="show_unknown" class="w120">';
	if ($config_show_unknown==1) {
		echo '<option value="1">'.$lang_label["active"].'</option>';
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
	}
	else {
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
		echo '<option value="1">'.$lang_label["active"].'</option>';
	}

	echo '<tr><td class="datos2">'.$lang_label["show_lastalerts"];
	echo '<td class="datos2"><select name="show_lastalerts" class="w120">';
	if ($config_show_lastalerts==1) {
		echo '<option value="1">'.$lang_label["active"].'</option>';
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
	}
	else {
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
		echo '<option value="1">'.$lang_label["active"].'</option>';
	}

	echo '<tr><td class="datos">'.$lang_label["background_image"].'</td>';
	echo '<td class="datos">';
	echo '<select name="bgimage" class="w155">';
	if ($config_bgimage!=""){
		echo '<option>'.$config_bgimage."</option>";
	}
	
	$ficheros = list_files('images/backgrounds/', "background",1, 0);
	$a=0;
	while (isset($ficheros[$a])){
		echo "<option>".$ficheros[$a]."</option>";
		$a++;
	}
	echo '</select>';

	echo '<tr><td class="datos2">'.$lang_label["style_template"].'</td>';
	echo '<td class="datos2">';
	echo '<select name="style" class="w155">';
	if ($config_bgimage!=""){
		echo '<option>'.$config_style.'</option>';
      	}
	$ficheros2 = list_files('include/styles/', "pandora",1, 0);
        $a=0;
        while (isset($ficheros2[$a])){
		$fstyle = substr($ficheros2[$a],0,strlen($ficheros2[$a])-4);
		if (($fstyle != $config_style) AND ($fstyle != "pandora_minimal"))
			echo "<option>".$fstyle."</option>";
        	$a++;
        }
        echo '</select>';
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
