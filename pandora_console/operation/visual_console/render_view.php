<?PHP

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas, info@artica.es
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


// Login check
global $config;
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

// Get input parameter for layout id
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if (isset($_GET["id"])){
	$id_layout = $_GET["id"];
	$sql="SELECT * FROM tlayout WHERE id = $id_layout";
	$res=mysql_query($sql);
	if ($row = mysql_fetch_array($res)){
		$id_group = $row["id_group"];
		$layout_name = $row["name"];
		$fullscreen = $row["fullscreen"];
		$background = $row["background"];
		$bwidth = $row["width"];
		$bheight = $row["height"];
	} else {		
		audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access visual console without id layout");
		include ("general/noaccess.php");
		exit;
	}
} else {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$refr = get_parameter ("refr", 0);
$pure_url = "&pure=".$config["pure"];

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// RENDER MAP !
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo "<h1>".$layout_name;

if ($config["pure"] == 0){
    echo lang_string("Full screen mode");
    echo "&nbsp;";
    echo "<a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=$id_layout&refr=$refr&pure=1'>";
    echo "<img src='images/monitor.png' title='".lang_string("Full screen mode")."'>";
    echo "</a>";
} else {
    echo lang_string("Back to normal mode");
    echo "&nbsp;";
    echo "<a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=$id_layout&pure=0&refr=$refr'>";
    echo "<img src='images/monitor.png' title='".lang_string("Back to normal mode")."'>";
    echo "</a>";
}

echo "</h1>";

echo "<div id='layout_db' style='z-index: 0; position:relative; background: url(images/console/background/".$background."); width:".$bwidth."px; height:".$bheight."px;'>";
$sql="SELECT * FROM tlayout_data WHERE id_layout = $id_layout";
$res=mysql_query($sql);
$lines = 0;
while ($row = mysql_fetch_array($res)){
	$id_agentmodule = $row["id_agente_modulo"];
	$pos_x = $row["pos_x"];
	$pos_y = $row["pos_y"];
	$height = $row["height"];
	$width = $row["width"];
	$period = $row["period"];
	$image = $row["image"];
	$type = $row["type"];
	$label = $row["label"];
	$label_color = $row["label_color"];
	$parent_item = $row["parent_item"];
	$link_layout = $row["id_layout_linked"];
	$no_link_color = $row["no_link_color"];

	// Linked to other layout ?? - Only if not module defined
	if (($link_layout != 0) && ($id_agentmodule == 0)) { 
		$status = return_status_layout ($link_layout);
	} else {
	 	$id_agent = get_db_value ("id_agente", "tagente_estado", "id_agente_modulo", $id_agentmodule);
		$id_agent_module_parent = get_db_value ("id_agente_modulo", "tlayout_data", "id", $parent_item);
		// Item value
		$status = return_status_agent_module ($id_agentmodule);
		if ($no_link_color == 1)
			$status_parent = -1;
		else
			$status_parent = return_status_agent_module ($id_agent_module_parent);
	}

	// STATIC IMAGE (type = 0)
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if ($type == 0) {
		// Link image
		//index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=1
		if (($link_layout == "") OR ($link_layout == 0)){
			$link_string = "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agent&tab=data'>";
		} else {
			$link_string = "<a href='index.php?sec=visualc&sec2=operation/visual_console/render_view$pure_url&id=$link_layout'>";
		}
		// Draw image
		echo "<div style='z-index: 1; color: #".$label_color."; position: absolute; margin-left: ".$pos_x."px; margin-top:".$pos_y."px; '>";
		echo $link_string;
		if ($status == 0){
			if (($width != "") AND ($width != 0))
				echo "<img src='images/console/icons/".$image."_bad.png' width='$width' height='$height' title='$label'>";
			else
				echo "<img src='images/console/icons/".$image."_bad.png' title='$label'>";	
		} else {
			if (($width != "") AND ($width != 0))
				echo "<img src='images/console/icons/".$image."_ok.png' width='$width' height='$height' title='$label'>";
			else
				echo "<img src='images/console/icons/".$image."_ok.png' title='$label'>";	
		}
		echo "</A>";
			
		// Draw label
		echo "<br>";
		echo $label;
		echo "</div>";
	}
	// SINGLE GRAPH (type = 1)
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if ($type == 1) { // single graph
		if (($link_layout == "") OR ($link_layout == 0)){
			$link_string = "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agent&tab=data'>";
		} else {
			$link_string = "<a href='http://index.php?sec=visualc&sec2=operation/visual_console/render_view$pure_url&id=$link_layout'>";
		}
		// Draw image
		echo "<div style='z-index: 1; color: #".$label_color."; position: absolute; margin-left: ".$pos_x."px; margin-top:".$pos_y."px; '>";
		echo $link_string;
		echo "<img src='reporting/fgraph.php?tipo=sparse&id=$id_agentmodule&label=$label&height=$height&width=$width&period=$period' border=0>";
		echo "</A>";
		echo "</div>";
	}
	if ($type == 2){
		$lines_data[$lines][0]=$pos_x;
		$lines_data[$lines][1]=$pos_y;
		$lines_data[$lines][2]=$width;
		$lines_data[$lines][3]=$height;
		$lines_data[$lines][4]="#".$label_color;
		$lines++;
	}
	
	// Get parent relationship - Create line data
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if (($parent_item != "") AND ($parent_item != 0)){
		$ppos_x =  return_coordinate_X_layoutdata($parent_item);
		$ppos_y =  return_coordinate_Y_layoutdata($parent_item);
		$lines_data[$lines][0]=$pos_x+15;
		$lines_data[$lines][1]=$pos_y+15;
		$lines_data[$lines][2]=$ppos_x+15;
		$lines_data[$lines][3]=$ppos_y+15;
		$lines_data[$lines][4]=$status_parent;
		$lines++;
	}
}

// Javascript code generated on realtime to draw lines
// http://www.walterzorn.com/jsgraphics/jsgraphics_e.htm#docu
echo '<script type="text/javascript">';
echo 'function myDrawFunction(){';

for ($a=0; $a < $lines; $a++){
	echo "	jg_doc_$a.setStroke(2);";
	
	if (substr($lines_data[$a][4],0,1) == "#")
		echo "	jg_doc_$a.setColor('".$lines_data[$a][4]."');"; // CUSTOM COLOR
	elseif ($lines_data[$a][4] >= 1)
		echo "	jg_doc_$a.setColor('00dd00');"; // GREEN
	elseif ($lines_data[$a][4] == 0)
		echo "	jg_doc_$a.setColor('#dd0000');"; // RED
	else 
		echo "	jg_doc_$a.setColor('#BBBBBB');"; // GREY
	echo "	jg_doc_$a.drawLine(".$lines_data[$a][0].",".$lines_data[$a][1].",".$lines_data[$a][2].",".$lines_data[$a][3].");";	
	echo "	jg_doc_$a.paint();";
}

echo '}';

echo "var cnv = document.getElementById('layout_db');";
for ($a=0; $a < $lines; $a++){
	echo "var jg_doc_$a = new jsGraphics(cnv);";
}
echo "myDrawFunction();";
echo "//--></script>";

// End main div
echo "</div>";

echo "<div style='height:30px'>";
echo "</div>";

echo "<form method='post' action='index.php?sec=visualc&sec2=operation/visual_console/render_view$pure_url&id=$id_layout'>";
echo "<table width=300 cellpadding=4 cellspacing=4 class='databox'>";
echo "<tr><td>";
echo $lang_label["auto_refresh_time"];
echo "<td>";
echo "<select name='refr'>";
if ($refr > 0){
	echo "<option value=$refr> $refr ".$lang_label["seconds"];
}

echo "<option value=0>".$lang_label["N/A"];
echo "<option value=5>5 ".$lang_label["seconds"];
echo "<option value=30>30 ".$lang_label["seconds"];
echo "<option value=60>1 ".$lang_label["minutes"];
echo "<option value=120>2 ".$lang_label["minutes"];
echo "<option value=300>5 ".$lang_label["minutes"];
echo "<option value=600>10 ".$lang_label["minutes"];
echo "<option value=1800>30 ".$lang_label["minutes"];
echo "</select>";
echo "<td>";
echo "<input type='submit' class='sub next' value='".$lang_label["refresh"]."'>";
echo "</table>";

echo "</form>";
