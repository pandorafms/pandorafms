<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

require_javascript_file('openlayers.pandora');

echo "<h2>" . __('Agent configuration') . " &raquo; " . __('Configure GIS data') . "</h2>";

$agentData = get_db_row_sql('SELECT * FROM tagente WHERE id_agente = 8');

/* Map with the current position */
echo "<div id=\"".$agentData['nombre']."_agent_map\"  style=\"border:1px solid black; width:98%; height: 30em;\"></div>";
echo getAgentMap($id_agente, "500px", "98%", false);

$table->width = '60%';
$table->data = array();

$table->colspan[0][0] = 2;

$table->data[0][0] = __('Agent coords:');

$table->data[1][0] = __('Longitude: ');
$table->data[1][1] = print_input_text ('longitude', $agentData['last_longitude'], '', 10, 10, true);

$table->data[2][0] = __('Latitude: ');
$table->data[2][1] = print_input_text ('latitude', $agentData['last_latitude'], '', 10, 10, true);

$table->data[3][0] = __('Altitude: ');
$table->data[3][1] = print_input_text ('altitude', $agentData['last_altitude'], '', 10, 10, true);

$table->data[4][0] = __('Ignore new GIS data:');
$table->data[4][1] = __('Disabled').' '.print_radio_button_extended ("update_gis_data", 0, '', $agentData['update_gis_data'], false, '', 'style="margin-right: 40px;"', true);
$table->data[4][1] .= __('Active').' '.print_radio_button_extended ("update_gis_data", 1, '', $agentData['update_gis_data'], false, '', 'style="margin-right: 40px;"', true);

$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=gis&id_agente='.$id_agente;
echo "<form method='post' action='" . $url . "'>";
print_input_hidden('update_gis', 1);
print_table($table);

echo '<div class="action-buttons" style="clear: left; width: ' . $table->width . '; float: left;">';
print_submit_button (__('Update'), '', false, 'class="sub update"');
echo '</div>';
echo "</form>";
?>
<script type="text/javascript">
$(document).ready (
		function () { 
			function changePositionAgent(e) {
				var lonlat = map.getLonLatFromViewPortPx(e.xy);
				var layer = map.getLayersByName("layer_for_agent_<?php echo $agentData['nombre']; ?>");

				layer = layer[0];
				feature = layer.features[0];

				lonlat.transform(map.getProjectionObject(), map.displayProjection); //transform the lonlat in object proyection to "standar proyection"

				$('input[name=latitude]').val(lonlat.lat);
				$('input[name=longitude]').val(lonlat.lon);

				$("#radiobtn0001").attr("checked","checked");
				$("#radiobtn0002").removeAttr("checked");
				
				//return to no-standar the proyection for to move
				feature.move(lonlat.transform(map.displayProjection, map.getProjectionObject()));
			}
			
			js_activateEvents(changePositionAgent);
		});
</script>