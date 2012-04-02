<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $search_status;
global $search_group;
global $search_string;

global $result;
global $result_status;
global $result_groups;
global $result_resolutions;

if (isset ($result_status['status'])){

	foreach($result_status['status'] as $st) {
		$status[$st['id']] = $st['name'];
	}
}

// Add special status cases
$status[0] = __('Any');
$status[-10] = __('Not closed');

if (isset ($result_groups['group'])){
	foreach($result_groups['group'] as $gr) {
		$groups[$gr['id']] = $gr['name'];
	}
}

$resolutions[0] = __('None');

if (isset ($result_resolutions['resolution'])){
	foreach($result_resolutions['resolution'] as $res) {
		$resolutions[$res['id']] = $res['name'];
	}
}

echo '<form method="post">';

echo '<br><table width="98%" border=0>';
echo '<tr>';
echo '<td>';
echo "<b>".__('Search string')."</b>";
echo '</td>';
echo '<td>';
echo "<b>".__('Status')."</b>";
echo '</td>';
echo '<td>';
echo "<b>".__('Group')."</b>";
echo '</td>';
echo '</tr><tr>';
echo '<td>';
html_print_input_text('search_string', $search_string, '');
echo '</td>';
echo '<td>';
html_print_select ($status, 'search_status', $search_status, '', '', 0, false);
echo '</td>';
echo '<td>';
if (isset($groups)){
html_print_select ($groups, 'search_group', $search_group, '', '', 0, false, false, false);
}
echo '</td>';
echo '<td>';
html_print_submit_button (__('Search'));
echo '</td>';
echo '</tr></table>';

echo '</form>';


// Show headers
$table->width = "98%";
$table->class = "databox";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->align = array ();

$table->head[0] = __('ID');
//$table->head[1] = __('SLA');
$table->head[2] = __('Incident');
$table->head[3] = __('Group');
$table->head[4] = __('Status')."<br/><i>".__('Resolution')."</i>";
$table->head[5] = __('Priority');
$table->head[6] = __('Updated')."<br/><i>".__('Started')."</i>";
$table->head[7] = __('Details');
$table->head[8] = __('Creator');
$table->head[9] = __('Owner');
$table->head[10] = __('Action');

$table->align[4] = "center";
$table->align[5] = "center";

if(isset($result['incident'][0]) && is_array($result['incident'][0])){
	$incidents = $result['incident'];
}
else {
	$incidents = $result;
}

$rowPair = true;
$iterator = 0;
foreach ($incidents as $row) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data = array();

	$data[0] = '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=incident&id_incident='.$row["id_incidencia"].'">'.$row["id_incidencia"].'</a>';
	//$data[1] = "";
	$data[2] = '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=incident&id_incident='.$row["id_incidencia"].'">'.substr(io_safe_output($row["titulo"]),0,45).'</a>';
	$data[3] = $groups[$row["id_grupo"]];
	$data[4] = $status[$row["estado"]]."<br/><i>".$resolutions[$row["resolution"]]."</i>";
	$data[5] = incidents_print_priority_img ($row["prioridad"], true);
	$data[6] = ui_print_timestamp ($row["actualizacion"], true)."<br/><i>" . ui_print_timestamp ($row["inicio"], true)."</i>";
	$data[7] = $row["workunits_hours"]." ".__('Hours')."<br/>".$row["workunits_count"]." ".__('Workunits');
	$data[8] = $row["id_creator"];
	$data[9] = $row["id_usuario"];
	$data[10] = "<a href='index.php?sec=workspace&sec2=operation/integria_incidents/incident&delete_incident=".$row['id_incidencia']."'>".html_print_image("images/cross.png", true, array('title' => __('Delete incident')))."</a><a href='index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=incident&id_incident=".$row["id_incidencia"]."'>".html_print_image("images/config.png", true, array('title' => __('View incident details')))."</a>";

	array_push ($table->data, $data);
}

html_print_table ($table);
?>
