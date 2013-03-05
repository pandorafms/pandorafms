<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
if (!isset ($id_agente)) {
	die ("Not Authorized");
}

require_once ('include/functions_gis.php');
require_once ('include/functions_html.php');
require_once ('include/functions_agents.php');

ui_require_javascript_file('openlayers.pandora');

echo "<div style='margin-bottom: 10px;'></div>";

$agentData = gis_get_data_last_position_agent($id_agente);
$updateGisData = db_get_value('update_gis_data','tagente', 'id_agente', $id_agente);
$agent_name = agents_get_name($id_agente);

//Avoid the agents with characters that fails the div.
$agent_name = md5($agent_name);

/* Map with the current position */
echo "<div id=\"" . $agent_name . "_agent_map\" style=\"border:1px solid black; width:98%; height: 30em;\"></div>";

if (!gis_get_agent_map($id_agente, "500px", "98%", false)) {
	echo "<br /><div class='nf'>" . __("There is no default map.") . "</div>";
} 

if ($agentData === false) {
	echo "<p>" . __("There is no GIS data for this agent, so it's positioned in default position of map.") . "</p>";
}
echo "<div class='warn'>" .__("Warning: When you change the Agent position, the agent automatically activates the 'Ignore new GIS data' option") . "</div>";

$table->width = '85%';
$table->data = array();

$table->head[0] =__('Agent position');
$table->head_colspan[0] = 2;

$table->data[1][0] = __('Latitude: ');
$table->data[1][1] = html_print_input_text_extended ('latitude', $agentData['stored_latitude'], 'text-latitude', '', 10, 10, false, '',
	array('onchange' => "setIgnoreGISDataEnabled()", 'onkeyup' => "setIgnoreGISDataEnabled()"), true);

$table->data[2][0] = __('Longitude: ');
$table->data[2][1] = html_print_input_text_extended ('longitude', $agentData['stored_longitude'], 'text-longitude', '', 10, 10, false, '',
	array('onchange' => "setIgnoreGISDataEnabled()", 'onkeyup' => "setIgnoreGISDataEnabled()"), true);

$table->data[3][0] = __('Altitude: ');
$table->data[3][1] = html_print_input_text_extended ('altitude', $agentData['stored_altitude'], 'text-altitude', '', 10, 10, false, '',
	array('onchange' => "setIgnoreGISDataEnabled()", 'onkeyup' => "setIgnoreGISDataEnabled()"), true);

$table->data[4][0] = __('Ignore new GIS data:');
$table->data[4][1] = __('Disabled').' '.html_print_radio_button_extended ("update_gis_data", 1, '', $updateGisData, false, '', 'style="margin-right: 40px;"', true);
$table->data[4][1] .= __('Enabled').' '.html_print_radio_button_extended ("update_gis_data", 0, '', $updateGisData, false, '', 'style="margin-right: 40px;"', true);

$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=gis&id_agente='.$id_agente;
echo "<form method='post' action='" . $url . "' onsubmit ='return validateFormFields();'>";
html_print_input_hidden('update_gis', 1);
html_print_table($table);

echo '<div class="action-buttons" style="width: ' . $table->width . '; float: left;">';
html_print_submit_button (__('Update'), '', false, 'class="sub upd"');
echo '</div>';
echo "</form>";
?>
<script type="text/javascript">
function setIgnoreGISDataEnabled() {
	$("#radiobtn0001").removeAttr("checked");
	$("#radiobtn0002").attr("checked","checked");
}

function validateFormFields() {
	longitude = $('input[name=longitude]').val();
	latitude = $('input[name=latitude]').val();
	altitude = $('input[name=altitude]').val();
	valid = true;

	$('input[name=longitude]').css('background', '#ffffff');
	$('input[name=latitude]').css('background', '#ffffff');
	$('input[name=altitude]').css('background', '#ffffff');
 
	//Validate longitude
	if ((jQuery.trim(longitude).length == 0) ||
		isNaN(parseFloat(longitude))) {
		$('input[name=longitude]').css('background', '#cc0000');

		valid = false;
	}

	//Validate latitude
	if ((jQuery.trim(latitude).length == 0) ||
		isNaN(parseFloat(latitude))) {
		$('input[name=latitude]').css('background', '#cc0000');

		valid = false;
	}

	//Validate altitude
	if ((jQuery.trim(altitude).length == 0) ||
		isNaN(parseFloat(altitude))) {
		$('input[name=altitude]').css('background', '#cc0000');

		valid = false;
	}

	if (valid) return true;
	else return false;
}

$(document).ready (
		function () { 
			function changePositionAgent(e) {
				var lonlat = map.getLonLatFromViewPortPx(e.xy);
				var layer = map.getLayersByName("layer_for_agent_<?php echo $agent_name ?>");

				layer = layer[0];
				feature = layer.features[0];

				lonlat.transform(map.getProjectionObject(), map.displayProjection); //transform the lonlat in object proyection to "standar proyection"

				$('input[name=latitude]').val(lonlat.lat);
				$('input[name=longitude]').val(lonlat.lon);

				if ($('input[name=altitude]').val().length == 0)
					$('input[name=altitude]').val(0)

				setIgnoreGISDataEnabled();
				
				//return to no-standar the proyection for to move
				feature.move(lonlat.transform(map.displayProjection, map.getProjectionObject()));
			}
			
			js_activateEvents(changePositionAgent);
		});
</script>
