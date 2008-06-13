<?php
// Pandora FMS
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas S.L, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
global $config;
$id_user = $config["id_user"];


if (comprueba_login() != 0) {
        require ("general/noaccess.php");
        exit;
}

if ((give_acl($id_user, 0, "AR")!=1) AND (give_acl($id_user,0,"AW")!=1)) {
        audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
        "Trying to access Agent Management");
        require ("general/noaccess.php");
        exit;
}

echo "<h2>".$lang_label["ag_title"]." &gt; ";
echo $lang_label["monitor_listing"]."</h2>";

$ag_freestring = get_parameter ("ag_freestring", "");
$ag_modulename = get_parameter ("ag_modulename", "");
$ag_group = get_parameter ("ag_group", -1);
$offset = get_parameter ("offset", 0);

$URL = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60";
echo "<form method='post' action='";
if ($ag_group != -1)
	$URL .= "&ag_group_refresh=".$ag_group;

// Module name selector
// This code thanks for an idea from Nikum, nikun_h@hotmail.com
if ($ag_modulename != "")
    $URL .= "&ag_modulename=".$ag_modulename;


// Freestring selector
if ($ag_freestring != "")
    $URL .= "&ag_freestring=".$ag_freestring ;

echo $URL;

// End FORM TAG
echo "'>";

echo "<table cellspacing='4' cellpadding='4' width='600' class='databox'>";
echo "<tr><td valign='middle'>".$lang_label["group"]."</td>";
echo "<td valign='middle'>";
echo "<select name='ag_group' onChange='javascript:this.form.submit();' class='w130'>";

if ( $ag_group > 1 ){
	echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group)."</option>";
} 
echo "<option value=1>".dame_nombre_grupo(1)."</option>";
list_group ($id_user);
echo "</select>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td valign='middle'>".$lang_label["module_name"]."</td>";
echo "<td valign='middle'>
<select name='ag_modulename' onChange='javascript:this.form.submit();'>";
if ( isset($ag_modulename)){
	echo "<option>".$ag_modulename."</option>";
} 
echo "<option>".$lang_label["all"]."</option>";
$sql='SELECT DISTINCT nombre 
FROM tagente_modulo 
WHERE id_tipo_modulo in (2, 9, 12, 18, 6, 100)';
$result=mysql_query($sql);
while ($row=mysql_fetch_array($result)){
	echo "<option>".$row['0']."</option>";
}
echo "</select>";
echo "<td valign='middle'>";
echo lang_string ("Free text");

echo "&nbsp;<input type=text name='ag_freestring' size=15 value='$ag_freestring'>";
echo "<td valign='middle'>";
echo "<input name='uptbutton' type='submit' class='sub' value='".$lang_label["show"]."'";
echo "</form>";
echo "</table>";

// Begin Build SQL sentences

$SQL_pre = "SELECT tagente_modulo.id_agente_modulo, tagente.nombre, tagente_modulo.nombre, tagente_modulo.descripcion, tagente.id_grupo, tagente.id_agente, tagente_modulo.id_tipo_modulo, tagente_modulo.module_interval ";

$SQL_pre_count = "SELECT count(tagente_modulo.id_agente_modulo) ";

$SQL = " FROM tagente, tagente_modulo WHERE tagente.id_agente = tagente_modulo.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_tipo_modulo in (2, 9, 12, 18, 6, 100) ";

// Agent group selector
if ($ag_group > 1)
    $SQL .=" AND tagente.id_grupo = ".$ag_group;
else {
     // User has explicit permission on group 1 ?
    $all_group = get_db_sql ("SELECT COUNT(id_grupo) FROM tusuario_perfil WHERE id_usuario='$id_user' AND id_grupo = 1");
    if ($all_group == 0)
        $SQL .=" AND tagente.id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='$id_user') ";
}

// Module name selector
// This code thanks for an idea from Nikum, nikun_h@hotmail.com
if ($ag_modulename != "")
    $SQL .= " AND tagente_modulo.nombre = '$ag_modulename'";

// Freestring selector
if ($ag_freestring != "")
    $SQL .= " AND ( tagente_modulo.nombre LIKE '%".$ag_freestring."%' OR tagente_modulo.descripcion LIKE '%".$ag_freestring."%') ";
$SQL .= " ORDER BY tagente.id_grupo, tagente.nombre";

// Build final SQL sentences
$SQL_FINAL = $SQL_pre . $SQL;
$SQL_COUNT = $SQL_pre_count . $SQL;

$counter = get_db_sql ($SQL_COUNT);
    if ( $counter > $config["block_size"]) {
        pagination ($counter, $URL, $offset);
        $SQL_FINAL .= " LIMIT $offset , ".$config["block_size"];
    }


if ($counter > 0){
    echo "
    <table cellpadding='4' cellspacing='4' width='750' class='databox'>
    <tr>
    <th>
    <th>".$lang_label["agent"]."</th>
    <th>".$lang_label["type"]."</th>
    <th>".$lang_label["name"]."</th>
    <th>".$lang_label["description"]."</th>
    <th>".$lang_label["interval"]."</th>
    <th>".$lang_label["status"]."</th>
    <th>".$lang_label["timestamp"]."</th>";
    $color =1;
    $result=mysql_query($SQL_FINAL);
   
    
    while ($data=mysql_fetch_array($result)){ //while there are agents
	    if ($color == 1){
		    $tdcolor="datos";
		    $color =0;
	    } else {
		    $tdcolor="datos2";
		    $color =1;
	    }
    
	    echo "<tr><td class='$tdcolor'>";
	    echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$data["id_agente"]."&id_agente_modulo=".$data[0]."&flag=1&tab=data&refr=60'>";
	    echo "<img src='images/target.png'></a>";
	    echo  "</td><td class='$tdcolor'>";
	    echo  "<b><a href='index.php?sec=estado&
	    sec2=operation/agentes/ver_agente&
	    id_agente=".$data[5]."'>".
	    strtoupper(substr($data[1],0,21))."</a></b>";
	    echo "</td><td class='$tdcolor'>";
	    echo "<img src='images/".show_icon_type($data[6])."' border=0></td>";
	    echo "<td class='$tdcolor'>". substr($data[2],0,21). "</td>";
	    echo "<td class='".$tdcolor."f9' title='".$data[3]."'>".substr($data[3],0,30)."</td>";
	    echo "<td class='$tdcolor' align='center' width=25>";
	    if ($data[7] == 0){
		    $my_interval = give_agentinterval($data[5]);
	    } else {
		    $my_interval = $data[7];						
	    }
	    echo $my_interval;
				    
	    $query_gen2='SELECT * FROM tagente_estado 
	    WHERE id_agente_modulo = '.$data[0];
	    $result_gen2=mysql_query($query_gen2);
	    $data2=mysql_fetch_array($result_gen2);
	    echo "<td class='$tdcolor' align='center' width=20>";
	    if ($data2["datos"] > 0){
		    echo "<img src='images/pixel_green.png' width=40 height=18 title='".$lang_label["green_light"]."'>";
	    } else {
		    echo "<img src='images/pixel_red.png' width=40 height=18 title='".$lang_label["red_light"]."'>";
	    }
	    
	    echo  "<td class='".$tdcolor."f9'>";
	    $seconds = time() - $data2["utimestamp"];
	    if ($seconds >= ($my_interval*2))
		    echo "<span class='redb'>";
	    else
		    echo "<span>";
    
	    echo  human_time_comparation($data2["timestamp"]);
        echo  "</span></td></tr>";
    }
    echo "</table>";
} else {
	echo "<div class='nf'>".$lang_label["no_monitors_g"]."</div>";
}

echo "<table width=700 border=0>";
echo "<tr>";
echo "<td class='f9'>";
echo "<img src='images/pixel_green.png' width=40 height=18>&nbsp;&nbsp;".$lang_label["green_light"]."</td>";
echo "<td class='f9'";
echo "<img src='images/pixel_red.png' width=40 height=18>&nbsp;&nbsp;".$lang_label["red_light"]."</td>";
echo "</table>";

?>
