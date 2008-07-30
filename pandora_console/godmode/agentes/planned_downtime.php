<?php
// Pandora FMS - the Flexible Monitoring System
// ========================================
// Copyright (c) 2008 Evi Vanoost <vanooste@rcbi.rochester.edu>
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

/*
Database schema:

CREATE TABLE  `pandora`.`tplanned_downtime` (
`id` MEDIUMINT( 8 ) NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 100 ) NOT NULL ,
`description` TEXT NOT NULL ,
`start` INT NOT NULL ,
`end` INT NOT NULL ,
`module_id` BIGINT( 14 ) NOT NULL ,
PRIMARY KEY (  `id` ) ,
INDEX (  `start` ,  `end` ,  `module_id` ) ,
UNIQUE (
`id`
)
) ENGINE = INNODB

*/

//ACL
require("include/config.php");
if (give_acl($id_user, 0, "AW")!=1) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access downtime scheduler");
	require ("general/noaccess.php");
	exit;
};

function generate_options ($start, $number, $default = false) {
	for($i=$start; $i<$start+$number; $i++) {
		$val = str_pad($i,2,0,STR_PAD_LEFT);
		echo '<option value="'.$val.'"';
		if($val == $default) {
			echo ' selected="selected"';
		}
		echo '>'.$val.'</option>';
	}
}
//Initialize data
$id_agente = get_parameter ("id_agente");
$modules = get_modules_in_agent ($id_agente);
$from_year = date("Y");
$from_month = date("m");
$from_day = date("d");
$to_year = date("Y");
$to_month = date("m");
$to_day = date("d");

//Here cometh the parsing of the entered form
if(isset ($_GET["delete"])) {
	$sql = sprintf ("DELETE FROM tplanned_downtime WHERE id = %d",$_GET["delete"]);
	$result = process_sql ($sql);
	if ($result === false) {
		echo '<h3 class="error">'.lang_string ("delete_no").'</h3>';
	} else {
		echo '<h3 class="suc">'.lang_string ("delete_ok").'</h3>';
	}															
} elseif(isset ($_POST["crtbutton"])) {
	$post_name = get_parameter_post ("downtime_name");
	$post_description = get_parameter_post ("downtime_description");
	$post_module_id = (int) get_parameter_post ("downtime_module_id");
	$post_from_year = (int) get_parameter_post ("from_year");
	$post_from_month = (int) get_parameter_post ("from_month");
	$post_from_day = (int) get_parameter_post ("from_day");
	$post_time_from = explode (":",get_parameter_post ("time_from"));
	$post_to_year = (int) get_parameter_post ("to_year");
	$post_to_month = (int) get_parameter_post ("to_month");
	$post_to_day = (int) get_parameter_post ("to_day");
	$post_time_to = explode (":",get_parameter_post ("time_to"));
	
	$start = mktime ($post_time_from[0],$post_time_from[1],00,$post_from_month,$post_from_day,$post_from_year);
	$end = mktime ($post_time_to[0],$post_time_to[1],00,$post_to_month,$post_to_day,$post_to_year);
	//make it a unixtime for easy storing/retrieving (int)
		
	if($start > $end) {
		echo '<h3 class="error">'.lang_string ("create_no").': START &gt; END</h3>';
	} else {
		if($_POST["crtbutton"] == "Add") {
			$sql = sprintf ("INSERT INTO tplanned_downtime (`name`, `description`, `start`, `end`, `module_id`) 
				VALUES ('%s','%s',%d,%d,%d)",$post_name,$post_description,$start,$end,$post_module_id);
		} elseif ($_POST["crtbutton"] == "Update") {
			$upd_id = (int) get_parameter_post ("update_id");
			$sql = sprintf ("UPDATE tplanned_downtime 
				SET `name`='%s', `description`='%s', `start`=%d, `end`=%d, `module_id`=%d 
				WHERE `id` = '%d'",$post_name,$post_description,$start,$end,$post_module_id,$upd_id);
		} else {
			die("Unspecified crtbutton");
		}

		$result = process_sql ($sql);
		if ($result === false) {
			echo '<h3 class="error">'.lang_string ("create_no").'</h3>';
		} else {
			echo '<h3 class="suc">'.lang_string ("create_ok").'</h3>';
		}
	}
} elseif (isset ($_GET["update"])) {
	$sql = sprintf ("SELECT `id`, `name`, `description`, `start`, `end`, `module_id`  FROM `tplanned_downtime` WHERE `id` = %d",$_GET["update"]);
	$result = get_db_row_sql ($sql);
	$name = $result["name"];
	$description = $result["description"];
	$module_id = $result["module_id"];
	$start = $result["start"];
	$end = $result["end"];
	$from_year = date("Y",$start);
	$from_month = date("m",$start);
	$from_day = date("d",$start);
	$to_year = date("Y",$end);
	$to_month = date("m",$end);
	$to_day = date("d",$end);
	$time_from = date("H:i",$start);
	$time_to = date("H:i",$end); 
}
//---

//Page header
echo '<h3>'.lang_string ("Planned Downtime Form").' <img class="img_help" src="images/help.png" onClick="pandora_help(\'planned_downtime\')" /></h3>';

//Table header
echo '<form name="planned_downtime" method="POST" action="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&id_agente='.$id_agente.'">
	<table width="650" cellpadding="4" cellspacing="4" class="databox_color" border="0">
	<tr><td class="datos3">'.lang_string ("name").':</td><td class="datos3"><input type="text" name="downtime_name" value="'.(isset($name) ? $name : '').'" /></td>
	<td class="datos3">'.lang_string ("modules").'</td><td class="datos3">';

//Select modules
echo '<select name="downtime_module_id">';
if (isset ($module_id)) {
	echo '<option value="'.$module_id.'">'.dame_nombre_modulo_agentemodulo ($module_id).'</option>';
}
foreach ($modules as $module) {
	echo '<option value="'.$module["id_agente_modulo"].'">'.$module["nombre"].'</option>';
} 
echo '</select></td></tr>';

//Description
echo '<tr><td class="datos">'.lang_string ("description").':</td>
	<td colspan="3" class="datos"><textarea name="downtime_description" rows="2" cols="60">'.(isset($description) ? $description : '').'</textarea></td></tr>
	<tr><td class="datos">'.lang_string ("time_from").':</td><td class="datos"><select name="from_year">';
generate_options ($from_year, 5);
echo '</select><select name="from_month">';
generate_options (1, 12, $from_month);
echo '</select><select name="from_day">';
generate_options (1, 31, $from_day);
echo '</select><select name="time_from">';
if(isset ($time_from)) {
	echo '<option value="'.$time_from.'">'.$time_from.'</option>';
}
for ($a=0; $a < 48; $a++){
	echo '<option value="'.render_time ($a).'">'.render_time ($a).'</option>';
}
echo '<option value="23:59">23:59</select>
</td>
<td class="datos">'.lang_string ("time_to").':</td><td class="datos"><select name="to_year">';
generate_options ($to_year, 5);
echo '</select><select name="to_month">';
generate_options (1, 12, $to_month);
echo '</select><select name="to_day">';
generate_options (1, 31, $to_day);
echo '</select><select name="time_to">';
if(isset ($time_to)) {
	echo '<option value="'.$time_to.'">'.$time_to.'</option>';
}
echo '<option value="23:59">23:59</option>';
for ($a=0; $a < 48; $a++){
	        echo '<option value="'.render_time ($a).'">'.render_time ($a).'</option>';
}
echo '</select></td></tr><tr><td colspan="4" align="right">';
if (!isset ($_GET["update"])) {
	echo '<input name="crtbutton" type="submit" value="Add" class="sub wand" />';
} else {
	echo '<input name="crtbutton" type="submit" value="Update" class="sub upd" />
	<input name="update_id" type="hidden" value="'.$_GET["update"].'" />';
}
//Finish form table
echo '</td></tr></table></form>';

//Start Overview of existing planned downtime
echo '<h3>'.lang_string ("Planned Downtime").':</h3>';
echo '<table width="650" cellpadding="4" cellspacing="4" class="databox" border="0">
	<tr><th>'.lang_string ("name").':</th><th>'.lang_string ("module").':</th>
	<th>'.lang_string ("time_from").':</th><th>'.lang_string ("time_to").':</th><th></th></tr>';
$sql = sprintf ("SELECT tplanned_downtime.id, tplanned_downtime.name, tplanned_downtime.module_id, tplanned_downtime.start, tplanned_downtime.end 
	FROM tplanned_downtime, tagente_modulo WHERE tplanned_downtime.module_id = tagente_modulo.id_agente_modulo 
	AND tagente_modulo.id_agente = %d AND end > UNIX_TIMESTAMP(NOW())",$id_agente);
$result = get_db_all_rows_sql ($sql);
if ($result === false) {
	echo '<tr><td colspan="5">'.lang_string ("No planned downtime").'</td></tr>';
	$result = array();
}
foreach($result as $row) {
	echo '<tr><td>'.$row['name'].'</td><td>'.dame_nombre_modulo_agentemodulo ($row['module_id']).'</td>
	<td>'.date ("Y-m-d H:i",$row['start']).'</td><td>'.date ("Y-m-d H:i",$row['end']).'</td>
	<td><a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&id_agente='.$id_agente.'&delete='.$row['id'].'">
	<img src="images/cross.png" border="0" alt="'.lang_string ("delete").'"></a>
	<a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&id_agente='.$id_agente.'&update='.$row['id'].'">
	<img src="images/config.png" border="0" alt="Update"></a></td></tr>';
}
echo '</table>';
?>
