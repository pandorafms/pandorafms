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

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}


$pandora_name = 'Pandora FMS';

// Load variables
$layout = (string) get_parameter ('layout', 'radial');
$nooverlap = (boolean) get_parameter ('nooverlap', 0);
$pure = (int) get_parameter ('pure');
$zoom = (float) get_parameter ('zoom');
$ranksep = (float) get_parameter ('ranksep', 2.5);
$simple = (boolean) get_parameter ('simple', 0);
$regen = (boolean) get_parameter ('regen',1); // Always regen by default
$font_size = (int) get_parameter ('font_size', 12);
$group = (int) get_parameter ('group', 0);
$center = (int) get_parameter ('center', 0);

// Check if a node descends from a given node
function is_descendant ($node, $ascendant, $parents) {
	if (! isset ($parents[$node])) {
		return false;
	}

	if ($node == $ascendant) {
		return true;
	}
	
	return is_descendant ($parents[$node], $ascendant, $parents);
}

// Generate a dot graph definition for graphviz
function generate_dot ($group, $simple, $font_size) {
	global $config, $pandora_name, $center;

	$parents = array();
	$orphans = array();
	
	// Open Graph
	$graph = open_graph ();

	// Get agent data	
	$agents = get_db_all_rows_sql ('SELECT id_grupo, nombre, id_os, id_parent, id_agente
		FROM tagente
		WHERE disabled = 0
		' . ($group < 1 ? '' : "AND id_grupo = $group") . '
		ORDER BY id_grupo');

	// Parse agents
	if ($agents){
		foreach ($agents as $agent) {
			if (give_acl ($config["id_user"], $agent["id_grupo"], "AR") == 0)
				continue;
			// Save node parent information to define edges later
			if ($agent['id_parent'] != "0") {
				$parents[$agent['id_agente']] = $agent['id_parent'];
			} else {
				$orphans[$agent['id_agente']] = 1;
			}
		
			// Add node
			$nodes[$agent['id_agente']] = $agent;
		}
	}

	// Create nodes
	foreach ($nodes as $node_id => $node) {
		if ($center > 0 && ! is_descendant ($node_id, $center, $parents)) {
			unset ($parents[$node_id]);
			unset ($orphans[$node_id]);
			unset ($nodes[$node_id]);
			continue;
		}

		$graph .= create_node ($node , $simple, $font_size)."\n\t\t";
	}

	// Define edges
	foreach ($parents as $node => $parent_id) {
		// Verify that the parent is in the graph
		if (isset ($nodes[$parent_id])) {
			$graph .= create_edge ($node, $parent_id);
		} else {
			$orphans[$node] = 1;
		}
	}

	// Create a central node if orphan nodes exist
	if (count ($orphans)) {
		$graph .= create_pandora_node ($pandora_name, $font_size);
	}

	// Define edges for orphan nodes
	foreach (array_keys($orphans) as $node) {
		$graph .= create_edge ('0', $node);
	}
	
	// Close graph
	$graph .= close_graph ();
	
	return $graph;
}

// Returns an edge definition
function create_edge ($head, $tail) {
	global $layout, $nooverlap, $pure, $zoom,
	       $ranksep, $simple, $regen, $font_size, $group;

	// edgeURL allows node navigation
	$edge = $head.' -- '.$tail.'[color="#BDBDBD", headclip=false, tailclip=false,
	edgeURL="index.php?sec=estado&sec2=operation/agentes/networkmap&center='.$head.
	'&layout='.$layout.'&nooverlap=' .$nooverlap.'&pure='.$pure.
	'&zoom='.$zoom.'&ranksep='.$ranksep.'&simple='.$simple.'&regen=1'.
	'&font_size='.$font_size.'&group='.$group.'"];';

	return $edge;
}

// Returns a node definition
function create_node ($agent, $simple = 0, $font_size = 10) {
	$sql = sprintf ('SELECT COUNT(tagente_modulo.id_agente)
			FROM tagente_estado, tagente_modulo
			WHERE tagente_modulo.id_agente = %d
			AND tagente_modulo.id_tipo_modulo in (2, 6, 9, 18, 21, 100)
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0 
			AND tagente_estado.estado = 1',
			$agent['id_agente']);
	$bad_modules = get_db_sql ($sql);
	
	// Set node status
	if ($bad_modules) {
		$status_color = '#FF1D1D';
	} else {
		$status_color = '#8DFF1D';
	}

	// Check for alert
	$sql = sprintf ('SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente
		WHERE tagente.id_agente = %d
		AND tagente.disabled = 0
		AND tagente.id_agente = tagente_modulo.id_agente
		AND tagente_modulo.disabled = 0
		AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
		AND talert_template_modules.times_fired > 0 ',
		$agent['id_agente']);
	$alert_modules = get_db_sql ($sql);
	if ($alert_modules) 
		$status_color = '#FFE308';

	// Short name
	$name = strtolower ($agent["nombre"]);
	if (strlen ($name) > 16)
		$name = substr ($name, 0, 16);

	if ($simple == 0){
		// Set node icon
		if (file_exists ('images/networkmap/'.$agent['id_os'].'.png')) { 
			$img_node = 'images/networkmap/'.$agent['id_os'].'.png';
		} else {
			$img_node = 'images/networkmap/0.png';
		}

		$node = $agent['id_agente'].' [ color="'.$status_color.'", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.40, height=0.40, label=<<TABLE CELLPADDING="0" CELLSPACING="0" BORDER="0"><TR><TD><IMG SRC="'.$img_node.'"/></TD></TR>
		 <TR><TD>'.$name.'</TD></TR></TABLE>>,
		 shape="ellipse", URL="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'",
		 tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
	} else {
		$node = $agent['id_agente'] . ' [ color="' . $status_color . '", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.20, height=0.20, label="", tooltip="ajax.php?page=operation/agentes/ver_agente&get_agent_status_tooltip=1&id_agent='.$agent['id_agente'].'"];';
	}
	return $node;
}

// Returns the definition of the central module
function create_pandora_node ($name, $font_size = 10) {
	global $simple;
	$img = '<TR><TD><IMG SRC="images/networkmap/pandora_node.png"/></TD></TR>';
	$name = '<TR><TD BGCOLOR="#FFFFFF">'.$name.'</TD></TR>';
	$label = '<TABLE BORDER="0">'.$img.$name.'</TABLE>';
	if ($simple == 1){
		$label = "";
	}

	$node = '0 [ color="#364D1F", fontsize='.$font_size.', style="filled", fixedsize=true, width=0.8, height=0.6, label=<'.$label.'>,
		shape="ellipse", URL="index.php?sec=estado&sec2=operation/agentes/estado_grupo" ];';

	return $node;
}

// Opens a group definition
function open_group ($id) {
	$img = 'images/'.get_group_icon ($id).'.png';
	$name = get_group_name ($id);
	
	$group = 'subgraph cluster_' . $id . 
		' { style=filled; color=darkolivegreen3; label=<<TABLE BORDER="0">
		<TR><TD><IMG SRC="'.$img.'"/></TD><TD>'.$name.'</TD></TR>
		</TABLE>>; tooltip="'.$name.'";
		URL="index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id='
		. $id . '";';
	
	return $group;
}

// Closes a group definition
function close_group () {
	return '}';
}

// Opens a graph definition
function open_graph () {
	global $config, $layout, $nooverlap, $pure, $zoom, $ranksep, $font_size;
	$overlap = 'compress';
	$size_x = 8;
	$size_y = 5.4;
	$size = '';

	if ($layout == 'radial') 
		$overlap = 'true';
	
	if ($layout == 'flat' || $layout == 'radial' || $layout == 'spring1' || $layout == "spring2")
		if ($nooverlap != '')
			$overlap = 'scalexy';
	
	if ($pure == 1  && $zoom > 1 ) {
		$size_x *= $zoom;
		$size_y *= $zoom;
	}
	$size = $size_x . ',' . $size_y;
	
	// BEWARE: graphwiz DONT use single ('), you need double (")
	$head = "graph networkmap { labeljust=l; margin=0; ";
	if ($nooverlap != '') {
		$head .= "overlap=\"$overlap\";";
		$head .= "ranksep=\"$ranksep\";";
		$head .= "outputorder=edgesfirst;";
	} 
	$head .= "ratio=fill;";
	$head .= "root=0;";
	$head .= "size=\"$size\";";
	
	return $head;
}

// Closes a graph definition
function close_graph () {
	return '}';
}

// Returns the filter used to achieve the desired layout
function set_filter () {
	global $layout;
	
	switch($layout) {
	case 'flat':
		return 'dot';
	case 'radial':
		return 'twopi';
	case 'circular':
		return 'circo';
	case 'spring1':
		return 'neato';
	case 'spring2':
		return 'fdp';
	default:
		return 'twopi';
	}
}

/* Main code */

echo '<h2>'.__('Pandora Agents').' &raquo; '.__('Network Map').'&nbsp;';
if ($pure == 1) {
	echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;pure=0">';
	print_image ("images/normalscreen.png", false, array ('title' => __('Normal screen'), 'alt' => __('Normal screen')));
	echo '</a>';
} else {
	echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;pure=1">';
	print_image ("images/fullscreen.png", false, array ('title' => __('Normal screen'), 'alt' => __('Normal screen')));
	echo '</a>';
}
echo '</h2>';

// Layout selection
$layout_array = array (
			'circular' => 'circular',
			'radial' => 'radial',
			'spring1' => 'spring 1',
			'spring2' => 'spring 2',
			'flat' => 'flat');

echo '<form action="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;pure='.$pure.'&amp;center='.$center.'" method="post">';
echo '<table cellpadding="4" cellspacing="4" class="databox">';
echo '<tr>';
echo '<td valign="top">' . __('Group') . ' &nbsp;';
print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo WHERE id_grupo > 1 ORDER BY nombre', 'group', $group, '', 'All', 0, false);
echo '</td>';
echo '<td valign="top">' . __('Layout') . ' &nbsp;';
print_select ($layout_array, 'layout', $layout, '', '', '');
echo '</td>';

echo '<td valign="top">' . __('No Overlap') . ' &nbsp;';
print_checkbox ('nooverlap', '1', $nooverlap);
echo '</td>';

echo '<td valign="top">' . __('Simple') . ' &nbsp;';
print_checkbox ('simple', '1', $simple);
echo '</td>';

echo '<td valign="top">' . __('Regenerate') . ' &nbsp;';
print_checkbox ('regen', '1', $regen);
echo '</td>';

if ($pure == "1") {
	// Zoom
	$zoom_array = array (
		'1' => 'x1',
		'1.2' => 'x2',
		'1.6' => 'x3',
		'2' => 'x4',
		'2.5' => 'x5',
		'5' => 'x10',
	);

	echo '<td valign="top">' . __('Zoom') . ' &nbsp;';
	print_select ($zoom_array, 'zoom', $zoom, '', '', '');
	echo '</td>';
	
}

if ($nooverlap == 1){
	echo "<td>";
	echo __('Distance between nodes') . ' &nbsp;';
	print_input_text ('ranksep', $ranksep, $alt = 'Separation between elements in the map (in Non-overlap mode)', 3, 4, 0);
	echo "</td>";
}

echo "<td>";
echo __('Font') . ' &nbsp;';
print_input_text ('font_size', $font_size, $alt = 'Font size (in pt)', 3, 4, 0);
echo "</td>";

//echo '  Display groups  <input type="checkbox" name="group" value="group" class="chk"/>';
echo '<td>';
print_submit_button (__('Update'), "updbutton", false, 'class="sub upd"');
echo '</td></tr>';
echo '</table></form>';

// Set filter
$filter = set_filter ();

// Generate dot file
$graph = generate_dot ($group, $simple, $font_size);

// Generate image and map
// If image was generated just a few minutes ago, then don't regenerate (it takes long) unless regen checkbox is set
$filename_map = $config["attachment_store"]."/networkmap_".$layout;
$filename_img = "attachment/networkmap_".$layout."_".$font_size;
$filename_dot = $config["attachment_store"]."/networkmap_".$layout;
if ($simple) {
	$filename_map .= "_simple";
	$filename_img .= "_simple";
	$filename_dot .= "_simple";
}
if ($nooverlap) {
	$filename_map .= "_nooverlap";
	$filename_img .= "_nooverlap";
	$filename_dot .= "_nooverlap";
}
$filename_map .= ".map";
$filename_img .= ".png";
$filename_dot .= ".dot";

if ($regen != 1 && file_exists ($filename_img) && filemtime ($filename_img) > get_system_time () - 300) {
	$result = true;
} else {
	$fh = @fopen ($filename_dot, 'w');
	if ($fh === false) {
		$result = false;
	} else {
		fwrite ($fh, $graph);
		$cmd = "$filter -Tcmapx -o".$filename_map." -Tpng -o".$filename_img." ".$filename_dot;
		$result = system ($cmd);
		fclose ($fh);
		unlink ($filename_dot);
	}
}

if ($result !== false) {
	if (! file_exists ($filename_map)) {
		echo '<h2 class="err">'.__('Map could not be generated').'</h2>';
		echo $result;
		echo "<br /> Apparently something went wrong reading the output.<br />";
		echo "<br /> Is ".$config["attachment_store"]." readable by the webserver process?";
		return;
	}
	print_image ($filename_img, false, array ("alt" => __('Network Map'), "usemap" => "#networkmap"));
	require ($filename_map);
} else {
	echo '<h2 class="err">'.__('Map could not be generated').'</h2>';
	echo $result;
	echo "<br /> Apparently something went wrong executing the command or writing the output.";
	echo "<br /><br /> Is ".$filter." (usually part of GraphViz) and echo installed and able to be executed by the webserver process?";
	echo "<br /><br /> Is your webserver restricted from executing command line tools through the <code>system()</code> call (PHP Safe Mode or SELinux)";
	echo "<br /><br /> Is ".$config["attachment_store"]." writeable by the webserver process? To change this do the following (POSIX-based systems): chown &lt;apache user&gt; ".$config["attachment_store"];
	return;
}

require_css_file ('cluetip');
require_jquery_file ('cluetip');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("area[title!='<?php echo $pandora_name; ?>']").cluetip ({
		arrows: true,
		attribute: 'title',
		cluetipClass: 'default'
	});
});
/* ]]> */
</script>
