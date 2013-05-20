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

include_once($config['homedir'] . "/include/functions_groups.php");
include_once($config['homedir'] . "/include/functions_agents.php");

/**
 * Print javascript to add parent lines between the agents.
 * 
 * @param inteer $idMap The id of map.
 * 
 * @return None
 */
function gis_add_parent_lines() {
	gis_make_layer(__('Hierarchy of agents'));
	
	echo "<script type='text/javascript'>";
	echo "$(document).ready (function () {
		var layer = map.getLayersByName('" . __('Hierarchy of agents') . "');
		layer = layer[0];
		
		map.setLayerIndex(layer, 0);
		
		js_refreshParentLines('" . __('Hierarchy of agents') . "');
	});";
	echo "</script>";
}

/**
 * Return the data of last position of agent from tgis_data_status.
 * 
 * @param integer $idAgent The id of agent.
 * @param boolean $returnEmptyArrayInFail The set return a empty array when fail and true.
 * @return Array The row of agent in tgis_data_status, and it's a associative array.
 */
function gis_get_data_last_position_agent($idAgent, $returnEmptyArrayInFail = false) {
	$returnVar = db_get_row('tgis_data_status', 'tagente_id_agente', $idAgent);
	
	if (($returnVar === false) && ($returnEmptyArrayInFail)) {
		return array();
	}
	
	return $returnVar;
}

/**
 * Write a javascript vars for to call the js_printMap.
 * 
 * @param string $idDiv The id of div to paint the map.
 * @param integer $iniZoom The zoom to init the map.
 * @param float $latCenter The latitude center to init the map.
 * @param float $lonCenter The longitude center to init the map.
 * @param array $baselayers The list of baselayer with the connection data.
 * @param array $controls The string list of controls.
 * 
 * @return None
 */
function gis_print_map($idDiv, $iniZoom, $latCenter, $lonCenter, $baselayers, $controls = null) {
	ui_require_javascript_file('OpenLayers/OpenLayers');
	
	echo "<script type='text/javascript'>";
	echo "var controlsList = [];";
	foreach ($controls as $control) {
		echo "controlsList.push('" . $control . "');";
	}
	echo "var idDiv = '" . $idDiv . "';";
	echo "var initialZoom = " . $iniZoom . ";";
	echo "var centerLatitude = " . $latCenter . ";";
	echo "var centerLongitude = " . $lonCenter . ";";
	
	echo "var baselayerList = [];";
	echo "var baselayer = null;";
	
	foreach ($baselayers as $baselayer) {
		echo "baselayer = {
			bb_bottom: null,
			bb_left: null,
			bb_right: null,
			bb_top: null,
			gmap_type: null,
			image_height: null,
			image_width: null,
			num_zoom_levels: null,
			name: null,
			type: null,
			url: null};";
		
		echo "baselayer['type'] = '" . $baselayer['typeBaseLayer'] . "';";
		echo "baselayer['name'] = '" . $baselayer['name'] . "';";
		echo "baselayer['num_zoom_levels'] = '" . $baselayer['num_zoom_levels'] . "';";
		
		switch ($baselayer['typeBaseLayer']) {
			case 'OSM':
				echo "baselayer['url'] = '" . $baselayer['url'] . "';";
				break;
			case 'Static_Image':
				echo "baselayer['bb_left'] = '" . $baselayer['bb_left'] . "';";
				echo "baselayer['bb_bottom'] = '" . $baselayer['bb_bottom'] . "';";
				echo "baselayer['bb_right'] = '" . $baselayer['bb_right'] . "';";
				echo "baselayer['bb_top'] = '" . $baselayer['bb_top'] . "';";
				echo "baselayer['image_width'] = '" . $baselayer['image_width'] . "';";
				echo "baselayer['image_height'] = '" . $baselayer['image_height'] . "';";
				echo "baselayer['url'] = '" . $baselayer['url'] . "';";
				break;
			case 'Gmap':
				echo "baselayer['gmap_type'] = '" . $baselayer['gmap_type'] . "';";
				break;
		}
		
		echo "baselayerList.push(baselayer);";
	}
	
	echo "js_printMap(idDiv, initialZoom, centerLatitude, centerLongitude,
		baselayerList, controlsList)";
	
	echo "</script>";
}

function gis_make_layer($name, $visible = true, $dot = null, $idLayer = null) {
	if ($dot == null) {
		$dot['url'] = 'images/dot_green.png';
		$dot['width'] = 20; //11;
		$dot['height'] = 20; //11;
	}
	$visible = (bool)$visible;
	?>
	<script type="text/javascript">
	$(document).ready (
		function () {
			//Creamos el estilo
			var style = new OpenLayers.StyleMap(
				{fontColor: "#ff0000",
					labelYOffset: -<?php echo $dot['height']; ?>,
					graphicHeight: <?php echo $dot['height']; ?>, 
					graphicWidth: <?php echo $dot['width']; ?>, 
					externalGraphic: "<?php echo $dot['url']; ?>", label:"${nombre}"
				}
			);
			
			//Creamos la capa de tipo vector
			var layer = new OpenLayers.Layer.Vector(
			'<?php echo $name; ?>', {styleMap: style}
			);

			layer.data = {};
			layer.data.id = '<?php echo $idLayer; ?>';

		 	layer.setVisibility(<?php echo $visible; ?>);
					map.addLayer(layer);

			layer.events.on({
		 		"featureselected": function(e) {
					if (e.feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
						var feature = e.feature;
						var featureData = feature.data;
						var long_lat = featureData.long_lat;
						var popup;
						
						var img_src= null;
						var parameter = Array();
						parameter.push ({name: "page", value: "include/ajax/skins.ajax"});
						parameter.push ({name: "get_image_path", value: "1"});
						parameter.push ({name: "img_src", value: "images/spinner.gif"});

						jQuery.ajax ({
							type: 'POST',
							url: action="ajax.php",
							data: parameter,
							async: false,
							timeout: 10000,
							success: function (data) {
								img_src = data;
							}
						});						

						popup = new OpenLayers.Popup.FramedCloud('cloud00',
								long_lat,
								null,
								'<div class="cloudContent' + featureData.id + '" style="text-align: center;">' + img_src + '</div>',
								null,
								true,
								function () { popup.destroy(); });
						feature.popup = popup;
						map.addPopup(popup);
						
						jQuery.ajax ({
							data: "page=operation/gis_maps/ajax&opt="+featureData.type+"&id=" + featureData.id,
							type: "GET",
							dataType: 'json',
							url: "ajax.php",
							timeout: 10000,
							success: function (data) {
								if (data.correct) {
									$('.cloudContent' + featureData.id).css('text-align', 'left');
											$('.cloudContent' + featureData.id).html(data.content);
											popup.updateSize();
								}
							}
						});
					}
				}
			});
		}
	);
	</script>
	<?php
}

function gis_activate_select_control($layers=null) {
?>
	<script type="text/javascript">
	$(document).ready (
		function () {
			var layers = map.getLayersByClass("OpenLayers.Layer.Vector");
			var select = new OpenLayers.Control.SelectFeature(layers);
			map.addControl(select);
			select.activate();
		}
	);
	</script>
	<?php
}

/**
 * Activate the feature refresh by ajax.
 * 
 * @param Array $layers Its a rows of table "tgis_map_layer" or None is all.
 * @param integer $lastTimeOfData The time in unix timestamp of last query of data GIS in DB.
 * 
 * @return None
 */
function gis_activate_ajax_refresh($layers = null, $lastTimeOfData = null) {
	if ($lastTimeOfData === null) $lastTimeOfData = time();
	
	ui_require_jquery_file ('json');
	?>
	<script type="text/javascript">
		var last_time_of_data = <?php echo $lastTimeOfData; ?>; //This time use in the ajax query to next recent points.
		var refreshAjaxIntervalSeconds = 60000;
		var idIntervalAjax = null;
		var oldRefreshAjaxIntervalSeconds = 60000;

		<?php
		if ($layers === null) {
			echo "var agentView = 1;";
		}
		else {
			echo "var agentView = 0;";
		}
		?>

		function refreshAjaxLayer(layer) {
			var featureIdArray = Array();
			
			for (featureIndex = 0; featureIndex < layer.features.length; featureIndex++) {
				feature = layer.features[featureIndex];

				if (feature.data.type != 'point_path_info') {
					if (feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
						featureIdArray.push(feature.data.id);
					}
				}
			}

			if (featureIdArray.length > 0) {
				jQuery.ajax ({
				data: "page=operation/gis_maps/ajax&opt=get_new_positions&id_features=" + featureIdArray.toString()
					+ "&last_time_of_data=" + last_time_of_data + "&layer_id=" + layer.data.id + "&agent_view=" + agentView,
				type: "GET",
				dataType: 'json',
				url: "ajax.php",
				timeout: 10000,
				success: function (data) {
					if (data.correct) {
						content = $.evalJSON(data.content);

						if (content != null) {
							for (var idAgent in content) {
								if (isInt(idAgent)) {
									agentDataGIS = content[idAgent];
									feature = searchPointAgentById(idAgent);
									layer.removeFeatures(feature);
									
									delete feature;
									feature = null
									status = parseInt(agentDataGIS['status']);
										
									js_addAgentPointExtent(layer.name, agentDataGIS['name'],
									agentDataGIS['stored_longitude'], agentDataGIS['stored_latitude'],
									agentDataGIS['icon_path'], 20, 20, idAgent, 'point_agent_info', status, agentDataGIS['id_parent']);

									//TODO: Optimize, search a new position to call for all agent in the layer and or optimice code into function.
									js_refreshParentLines();
								}
							}
						}
					}
				}
				});
			}
		}
	
		function clock_ajax_refresh() {
			//console.log(new Date().getHours() + ":" + new Date().getMinutes() + ":" + new Date().getSeconds());
			for (layerIndex = 0; layerIndex < map.getNumLayers(); layerIndex++) {
				layer = map.layers[layerIndex];
				<?php
				if ($layers === null) {
					?>
					if (layer.isVector) {
						refreshAjaxLayer(layer);
					}
					<?php
				}
				else {
					foreach ($layers as $layer) {
						?>
						if (layer.name == '<?php echo $layer['layer_name']; ?>') {
							refreshAjaxLayer(layer);
						}
						<?php
					} 
				}
				?>
			}
			
			last_time_of_data = Math.round(new Date().getTime() / 1000); //Unixtimestamp

			//Test if the user change the refresh time.
			if (oldRefreshAjaxIntervalSeconds != refreshAjaxIntervalSeconds) {
				clearInterval(idIntervalAjax);
				idIntervalAjax = setInterval("clock_ajax_refresh()", refreshAjaxIntervalSeconds);
				oldRefreshAjaxIntervalSeconds = refreshAjaxIntervalSeconds;
			}
		}
	
		$(document).ready (
			function () {
				idIntervalAjax = setInterval("clock_ajax_refresh()", refreshAjaxIntervalSeconds);
			}
		);
	</script>
	<?php
}

function gis_add_agent_point($layerName, $pointName, $lat, $lon, $icon = null, $width = 20,
	$height = 20, $point_id = '', $status = -1, $type_string = '', $idParent = 0) {
		
	global $config;
	if (!$config['gis_label'])
		$pointName = '';
	?>
	<script type="text/javascript">
		$(document).ready (
			function () {
				<?php
				if ($icon != null) {
					//echo "js_addPointExtent('$layerName', '$pointName', $lon, $lat, '$icon', $width, $height, $point_id, '$type_string', $status);";
					echo "js_addAgentPointExtent('$layerName', '$pointName', $lon, $lat, '$icon', $width, $height, $point_id, '$type_string', $status, $idParent);";
				}
				else {
					//echo "js_addPoint('$layerName', '$pointName', $lon, $lat, $point_id, '$type_string', $status);";
					echo "js_addAgentPoint('$layerName', '$pointName', $lon, $lat, $point_id, '$type_string', $status, $idParent);";
				} 
				?>
			}
		);
	</script>
	<?php
}

/**
 * Get the agents in layer but not by group in layer.
 * 
 * @param integer $idLayer Layer ID.
 * @param array $fields Fields of row tagente to return.
 * 
 * @return array The array rows of tagente of agents in the layer.
 */
function gis_get_agents_layer($idLayer, $fields = null) {
	
	if ($fields === null) {
		$select = '*';
	}
	else {
		$select = implode(',',$fields);
	}
	
	$agents = db_get_all_rows_sql('SELECT ' . $select . '
		FROM tagente
		WHERE id_agente IN (
				SELECT tagente_id_agente
				FROM tgis_map_layer_has_tagente
				WHERE tgis_map_layer_id_tmap_layer = ' . $idLayer . ');');
	
	if ($agents !== false) {
		foreach ($agents as $index => $agent) {
			$agents[$index] = $agent['nombre'];
		}
	}
	else {
		return array();
	}
	
	
	return $agents;
}

function gis_add_point_path($layerName, $lat, $lon, $color, $manual = 1, $id) {
	?>
	<script type="text/javascript">
	$(document).ready (
		function () {
			js_addPointPath('<?php echo $layerName; ?>', <?php echo $lon; ?>, <?php echo $lat; ?>, '<?php echo $color; ?>', <?php echo $manual; ?>, <?php echo $id; ?>);
		}
	);
	</script>
	<?php
}

function gis_get_maps() {
	return db_get_all_rows_in_table ('tgis_map', 'map_name');
}
/**
 * Gets the configuration of all the base layers of a map
 * 
 * @param $idMap: Map identifier of the map to get the configuration
 *
 * @return An array of arrays of configuration parameters
 */
function gis_get_map_conf($idMap) {
	$mapConfs= db_get_all_rows_sql('SELECT tconn.*, trel.default_map_connection
		FROM tgis_map_connection AS tconn, tgis_map_has_tgis_map_connection AS trel
		WHERE trel.tgis_map_connection_id_tmap_connection = tconn.id_tmap_connection
			AND trel.tgis_map_id_tgis_map = ' . $idMap);
	return $mapConfs;
}

function gis_get_map_connection($idMapConnection) {
	return db_get_row('tgis_map_connection', 'id_tmap_connection', $idMapConnection);
}

function gis_get_layers($idMap) {
	$layers = db_get_all_rows_sql('SELECT *
		FROM tgis_map_layer
		WHERE tgis_map_id_tgis_map = ' . $idMap);
	
	return $layers;
}

function gis_get_agent_icon_map($idAgent, $state = false, $status = null) {
	global $config;
	
	$row = db_get_row_sql('
		SELECT id_grupo, icon_path
		FROM tagente
		WHERE id_agente = ' . $idAgent);
	
	if (($row['icon_path'] === null) || (strlen($row['icon_path']) == 0)) {
		if ($config['gis_default_icon'] != "") {
			$icon = "images/gis_map/icons/" . $config['gis_default_icon'];
		}
		else {
			$icon = "images/groups_small/" . groups_get_icon($row['id_grupo']);
		}
	}
	else {
		$icon = "images/gis_map/icons/" . $row['icon_path'];
	}
	
	if ($state === false) {
		return $icon . ".png";
	}
	else {
		if ($status === null) {
			$status = agents_get_status($idAgent);
			if ($status === null) {
				$status = -1;
			}
		}
		switch ($status) {
			case 1:
			case 4:
				//Critical (BAD or ALERT)
				$state = ".bad";
				break;
			case 0:
				//Normal (OK)
				$state = ".ok";
				break;
			case 2:
				//Warning
				$state = ".warning";
				break;
			default:
				// Default is Grey (Other)
				$state = '.default';
				break;
		}
		$returnIcon = $icon . $state . ".png";
		
		return $returnIcon;
	}
}

/**
 * Print the path of agent.
 * 
 * @param string $layerName String of layer.
 * @param integer $idAgent The id of agent
 * @param integer $history_time Number of seconds in the past to show from where to start the history path.
 * @param array $lastPosition The last position of agent that is not in history table. 
 * 
 * @return None
 */
function gis_add_path($layerName, $idAgent, $lastPosition = null, $history_time = null) {
	global $config;
	
	if ($history_time === null) {
		$where = '1 = 1';
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				$where = 'start_timestamp >= FROM_UNIXTIME(UNIX_TIMESTAMP() - ' . $history_time . ')';
				break;
			case "postgresql":
				$where = 'start_timestamp >= to_timestamp(ceil(date_part("epoch", CURRENT_TIMESTAMP)) - ' . $history_time . ')';
				break;
			case "oracle":
				$where = 'start_timestamp >= to_timestamp(\'01-01-1970 00:00:00\', \'DD-MM-YYYY HH24:MI:SS\') + NUMTODSINTERVAL((ceil((sysdate - to_date(\'19700101000000\',\'YYYYMMDDHH24MISS\')) * (86400)) - ' . $history_time . '),\'SECOND\')';
				break;
		}
	}
	
	$listPoints = db_get_all_rows_sql('SELECT *
		FROM tgis_data_history
		WHERE
			tagente_id_agente = ' . $idAgent . ' AND
			' . $where . '
		ORDER BY end_timestamp ASC');
	
	//If the agent is empty the history
	if ($listPoints === false) {
		return;
	}
	
	$avaliableColors = array("#ff0000", "#00ff00", "#0000ff", "#000000");
	
	$randomIndex = array_rand($avaliableColors);
	$color = $avaliableColors[$randomIndex];
	?>
	<script type="text/javascript">
		$(document).ready (
			function () {
				<?php
				if ($listPoints != false) {
					$listPoints = (array)$listPoints;
					$first = true;
					
					echo "var points = [";
					foreach($listPoints as $point) {
						if (!$first) echo ",";
						$first =false;
						echo "new OpenLayers.Geometry.Point(" . $point['longitude'] . ", " . $point['latitude'] . ")";
					}
					
					if ($lastPosition !== null) {
						echo ", new OpenLayers.Geometry.Point(" . $lastPosition['longitude'] . ", " . $lastPosition['latitude'] . ")";
					}
					echo "];";
				} 
				?>
				
				js_addLineString('<?php echo $layerName; ?>', points, '<?php echo $color; ?>');
			}
		);
	</script>
	<?php
	if ($listPoints != false) {
		foreach($listPoints as $point) {
			if ((end($listPoints) != $point) || (($lastPosition !== null)))
				gis_add_point_path($layerName, $point['latitude'], $point['longitude'], $color, (int)$point['manual_placement'], $point['id_tgis_data']);
		}
	}
}

function gis_delete_map_connection($idConectionMap) {
	
	db_process_sql_delete ('tgis_map_connection', array('id_tmap_connection' => $idConectionMap));
	
	//TODO DELETE IN OTHER TABLES
}

function gis_save_map_connection($mapConnection_name, $mapConnection_group,
			$mapConnection_numLevelsZoom, $mapConnection_defaultZoom,
			$mapConnection_defaultLatitude, $mapConnection_defaultLongitude,
			$mapConnection_defaultAltitude, $mapConnection_centerLatitude,
			$mapConnection_centerLongitude, $mapConnection_centerAltitude,
			$mapConnectionData, $idConnectionMap = null) {

	if ($idConnectionMap !== null) {
		$returnQuery = db_process_sql_update('tgis_map_connection',
			array(
				'conection_name' => $mapConnection_name, 
				'connection_type' => $mapConnectionData['type'], 
				'conection_data' => json_encode($mapConnectionData),
				'num_zoom_levels' => $mapConnection_numLevelsZoom,
				'default_zoom_level' => $mapConnection_defaultZoom,
				'default_longitude' => $mapConnection_defaultLongitude,
				'default_latitude' => $mapConnection_defaultLatitude,
				'default_altitude' => $mapConnection_defaultAltitude,
				'initial_longitude' => $mapConnection_centerLongitude,
				'initial_latitude' => $mapConnection_centerLatitude,
				'initial_altitude' => $mapConnection_centerAltitude,
				'group_id' => $mapConnection_group
			),
			array('id_tmap_connection' => $idConnectionMap)
		);
	}
	else {
		$returnQuery = db_process_sql_insert('tgis_map_connection',
			array(
				'conection_name' => $mapConnection_name, 
				'connection_type' => $mapConnectionData['type'], 
				'conection_data' => json_encode($mapConnectionData),
				'num_zoom_levels' => $mapConnection_numLevelsZoom,
				'default_zoom_level' => $mapConnection_defaultZoom,
				'default_longitude' => $mapConnection_defaultLongitude,
				'default_latitude' => $mapConnection_defaultLatitude,
				'default_altitude' => $mapConnection_defaultAltitude,
				'initial_longitude' => $mapConnection_centerLongitude,
				'initial_latitude' => $mapConnection_centerLatitude,
				'initial_altitude' => $mapConnection_centerAltitude,
				'group_id' => $mapConnection_group
			)
		);
	}
	
	return $returnQuery;
}

/**
 * Delete the map in the all tables are related.
 * 
 * @param $idMap integer The id of map.
 * @return None
 */
function gis_delete_map($idMap) {
	$listIdLayers = db_get_all_rows_sql("SELECT id_tmap_layer FROM tgis_map_layer WHERE tgis_map_id_tgis_map = " . $idMap);
	
	if ($listIdLayers !== false) {
		foreach ($listIdLayers as $idLayer) {
			db_process_sql_delete('tgis_map_layer_has_tagente', array('tgis_map_layer_id_tmap_layer' => $idLayer));
		}
	}
	db_process_sql_delete('tgis_map_layer', array('tgis_map_id_tgis_map' => $idMap));
	db_process_sql_delete('tgis_map_has_tgis_map_connection', array('tgis_map_id_tgis_map' => $idMap));
	db_process_sql_delete('tgis_map', array('id_tgis_map' => $idMap));
	
	$numMaps = db_get_num_rows('SELECT * FROM tgis_map');
	
	db_clean_cache();
}

/**
 * Save the map into DB, tgis_map and with id_map save the connetions in 
 * tgis_map_has_tgis_map_connection, and with id_map save the layers in 
 * tgis_map_layer and witch each id_layer save the agent in this layer in
 * table tgis_map_layer_has_tagente.
 * 
 * @param $map_name
 * @param $map_initial_longitude
 * @param $map_initial_latitude
 * @param $map_initial_altitude
 * @param $map_zoom_level
 * @param $map_background
 * @param $map_default_longitude
 * @param $map_default_latitude
 * @param $map_default_altitude
 * @param $map_group_id
 * @param $map_connection_list
 * @param $arrayLayers
 */
function gis_save_map($map_name, $map_initial_longitude, $map_initial_latitude,
	$map_initial_altitude, $map_zoom_level, $map_background,
	$map_default_longitude, $map_default_latitude, $map_default_altitude,
	$map_group_id, $map_connection_list, $arrayLayers) {
	
	$idMap = db_process_sql_insert('tgis_map',
		array('map_name' => $map_name,
			'initial_longitude' => $map_initial_longitude,
			'initial_latitude' => $map_initial_latitude,
			'initial_altitude' => $map_initial_altitude,
			'zoom_level' => $map_zoom_level,
			'map_background' => $map_background,
			'default_longitude' => $map_default_longitude,
			'default_latitude' => $map_default_latitude,
			'default_altitude' => $map_default_altitude,
			'group_id' => $map_group_id
		)
	);
	
	$numMaps = db_get_num_rows('SELECT * FROM tgis_map');
	
	if ($numMaps == 1)
		db_process_sql_update('tgis_map', array('default_map' => 1), array('id_tgis_map' => $idMap));
	
	foreach ($map_connection_list as $map_connection) {
		db_process_sql_insert('tgis_map_has_tgis_map_connection',
			array(
				'tgis_map_id_tgis_map' => $idMap,
				'tgis_map_connection_id_tmap_connection' => $map_connection['id_conection'],
				'default_map_connection' => $map_connection['default']
			)
		);
	}
	
	foreach ($arrayLayers as $index => $layer) {
		$idLayer = db_process_sql_insert('tgis_map_layer',
			array(
				'layer_name' => $layer['layer_name'],
				'view_layer' => $layer['layer_visible'],
				'layer_stack_order' => $index,
				'tgis_map_id_tgis_map' => $idMap,
				'tgrupo_id_grupo' => $layer['layer_group']
			)
		);
		if ((isset($layer['layer_agent_list'])) AND (count($layer['layer_agent_list']) > 0)) {
			foreach ($layer['layer_agent_list'] as $agent_name) {
				db_process_sql_insert('tgis_map_layer_has_tagente',
					array(
						'tgis_map_layer_id_tmap_layer' => $idLayer,
						'tagente_id_agente' => agents_get_agent_id(io_safe_input($agent_name))
					)
				);
			}
		}
	}
}

function gis_update_map($idMap, $map_name, $map_initial_longitude, $map_initial_latitude,
	$map_initial_altitude, $map_zoom_level, $map_background,
	$map_default_longitude, $map_default_latitude, $map_default_altitude,
	$map_group_id, $map_connection_list, $arrayLayers) {
		
	db_process_sql_update('tgis_map',
		array('map_name' => $map_name,
			'initial_longitude' => $map_initial_longitude,
			'initial_latitude' => $map_initial_latitude,
			'initial_altitude' => $map_initial_altitude,
			'zoom_level' => $map_zoom_level,
			'map_background' => $map_background,
			'default_longitude' => $map_default_longitude,
			'default_latitude' => $map_default_latitude,
			'default_altitude' => $map_default_altitude,
			'group_id' => $map_group_id
		),
		array('id_tgis_map' => $idMap));
		
	db_process_sql_delete('tgis_map_has_tgis_map_connection', array('tgis_map_id_tgis_map' => $idMap));
	
	foreach ($map_connection_list as $map_connection) {
		db_process_sql_insert('tgis_map_has_tgis_map_connection',
			array(
				'tgis_map_id_tgis_map' => $idMap,
				'tgis_map_connection_id_tmap_connection' => $map_connection['id_conection'], 
				'default_map_connection' => $map_connection['default']
			)
		);
	}
	
	$listOldIdLayers = db_get_all_rows_sql('SELECT id_tmap_layer
		FROM tgis_map_layer WHERE tgis_map_id_tgis_map = ' . $idMap);
	if ($listOldIdLayers == false)
		$listOldIdLayers = array();
	
	$list_onlyIDsLayers = array();
	foreach ($listOldIdLayers as $idLayer) {
		db_process_sql_delete('tgis_map_layer_has_tagente',
			array('tgis_map_layer_id_tmap_layer' => $idLayer['id_tmap_layer']));
		
		$list_onlyIDsLayers[$idLayer['id_tmap_layer']] = 0;
	}
	
	
	foreach ($arrayLayers as $index => $layer) {
		
		if ($layer['id'] != 0) {
			$idLayer = $layer['id'];
			unset($list_onlyIDsLayers[$idLayer]);
			
			db_process_sql_update('tgis_map_layer',
				array(
					'layer_name' => $layer['layer_name'],
					'view_layer' => $layer['layer_visible'],
					'layer_stack_order' => $index,
					'tgis_map_id_tgis_map' => $idMap,
					'tgrupo_id_grupo' => $layer['layer_group']
				),
				array('id_tmap_layer' => $idLayer));
		}
		else {
			$idLayer = db_process_sql_insert('tgis_map_layer',
				array(
					'layer_name' => $layer['layer_name'],
					'view_layer' => $layer['layer_visible'],
					'layer_stack_order' => $index,
					'tgis_map_id_tgis_map' => $idMap,
					'tgrupo_id_grupo' => $layer['layer_group']
				)
			);
		}
		
		
		
		if (array_key_exists('layer_agent_list', $layer)) {
			if (count($layer['layer_agent_list']) > 0) {
				foreach ($layer['layer_agent_list'] as $agent_name) {
					
					db_process_sql_insert('tgis_map_layer_has_tagente',
						array(
							'tgis_map_layer_id_tmap_layer' => $idLayer,
							'tagente_id_agente' => agents_get_agent_id(
								io_safe_input($agent_name))
						)
					);
				}
			}
		}
		
	}
	
	//Delete layers that not carry the $arrayLayers
	foreach ($list_onlyIDsLayers as $idLayer => $trash) {
		db_process_sql_delete('tgis_map_layer',
			array('id_tmap_layer' => $idLayer));
	}
}

/**
 * Get the configuration parameters of a map connection
 * 
 * @param idConnection: connection identifier for the map
 *
 * @result: An array with all the configuration parameters
 */
function gis_get_conection_conf($idConnection) {
	$confParameters = db_get_row_sql('SELECT * FROM tgis_map_connection WHERE id_tmap_connection = ' . $idConnection);
	return $confParameters;
}

/**
 * Shows the map of an agent in a div with the width and heigth given and the history if asked
 *
 * @param $agent_id: id of the agent as in the table tagente;
 * @param $height: heigth in a string in css format
 * @param $width: width in a string in css format
 * @param $show_history: by default or when this parameter is false in the map the path with the 
 * @param $centerInAgent boolean Default is true, set the map center in the icon agent. 
 * @param $history_time: Number of seconds in the past to show from where to start the history path.
 *
 * @return boolean True ok and false fail. 
 */
function gis_get_agent_map($agent_id, $heigth, $width, $show_history = false, $centerInAgent = true, $history_time = 86400) {
	$defaultMap = db_get_all_rows_sql("
		SELECT t1.*, t3.conection_name, t3.connection_type,
			t3.conection_data, t3.num_zoom_levels
		FROM tgis_map AS t1, 
			tgis_map_has_tgis_map_connection AS t2, 
			tgis_map_connection AS t3
		WHERE t1.default_map = 1
			AND t2.tgis_map_id_tgis_map = t1.id_tgis_map
			AND t2.default_map_connection = 1
			AND t3.id_tmap_connection = t2.tgis_map_connection_id_tmap_connection");
	
	if ($defaultMap === false)
		return false;
	
	$defaultMap = $defaultMap[0];
	
	$agent_position = gis_get_data_last_position_agent($agent_id);
	if ($agent_position === false) {
		$agentPositionLongitude = $defaultMap['default_longitude'];
		$agentPositionLatitude = $defaultMap['default_latitude'];
		$agentPositionAltitude = $defaultMap['default_altitude'];
	}
	else {
		$agentPositionLongitude = $agent_position['stored_longitude'];
		$agentPositionLatitude = $agent_position['stored_latitude'];
		$agentPositionAltitude = $agent_position['stored_altitude'];
	}
	
	$agent_name = agents_get_name($agent_id);
	
	//Avoid the agents with characters that fails the div.
	$agent_name = md5($agent_name);
	
	$baselayers[0]['name'] = $defaultMap['conection_name'];
	$baselayers[0]['num_zoom_levels'] = $defaultMap['num_zoom_levels'];
	
	$conectionData = json_decode($defaultMap['conection_data'], true);
	$baselayers[0]['typeBaseLayer'] = $conectionData['type'];
	$controls = array('PanZoomBar', 'ScaleLine', 'Navigation', 'MousePosition');
	$gmap_layer = false;
	
	switch ($conectionData['type']) {
		case 'OSM':
			$baselayers[0]['url'] = $conectionData['url'];
			break;
		case 'Gmap':
			$baselayers[0]['gmap_type'] = $conectionData['gmap_type'];
			$baselayers[0]['gmap_key'] = $conectionData['gmap_key'];
			$gmap_key = $conectionData['gmap_key'];
			// Onece a Gmap base layer is found we mark it to import the API
			$gmap_layer = true;
			break;
		case 'Static_Image':
			$baselayers[0]['url'] = $conectionData['url'];
			$baselayers[0]['bb_left'] = $conectionData['bb_left'];
			$baselayers[0]['bb_right'] = $conectionData['bb_right'];
			$baselayers[0]['bb_bottom'] = $conectionData['bb_bottom'];
			$baselayers[0]['bb_top'] = $conectionData['bb_top'];
			$baselayers[0]['image_width'] = $conectionData['image_width'];
			$baselayers[0]['image_height'] = $conectionData['image_height'];
			break;
	}
	
	if ($gmap_layer === true) {
		?>
		<script type="text/javascript" src="http://maps.google.com/maps?file=api&v=2&sensor=false&key=<?php echo $gmap_key ?>" ></script>
		<?php
	}
	
	gis_print_map($agent_name . "_agent_map", $defaultMap['zoom_level'],
		$defaultMap['initial_latitude'],
		$defaultMap['initial_longitude'], $baselayers, $controls);
		
	gis_make_layer("layer_for_agent_" . $agent_name);
	
	$agent_icon = gis_get_agent_icon_map($agent_id, true);
	$agent_icon_size = getimagesize($agent_icon);
	$agent_icon_width = $agent_icon_size[0];
	$agent_icon_height = $agent_icon_size[1];
	$status = agents_get_status($agent_id);
	
	/* If show_history is true, show the path of the agent */
	if ($show_history) {
		$lastPosition = array(
			'longitude' => $agentPositionLongitude,
			'latitude' => $agentPositionLatitude);
		gis_add_path("layer_for_agent_" . $agent_name,
			$agent_id, $lastPosition, $history_time);
	}
	
	
	gis_add_agent_point("layer_for_agent_" . $agent_name,
		$agent_name, $agentPositionLatitude, $agentPositionLongitude,
		$agent_icon, $agent_icon_width, $agent_icon_height, $agent_id,
		$status, 'point_agent_info');
	
	if ($centerInAgent) {
		?>
		<script type="text/javascript">
		$(document).ready (
			function () { 
				var lonlat = new OpenLayers.LonLat(<?php echo $agentPositionLongitude; ?>, <?php echo $agentPositionLatitude; ?>)
					.transform(map.displayProjection, map.getProjectionObject());
				map.setCenter(lonlat, <?php echo $defaultMap['zoom_level']; ?>, false, false);
			});
		</script>
		<?php
	}
	
	return true;
}

/**
 * Return a array of images as icons in the /pandora_console/images/gis_map/icons.
 * 
 * @param boolean $fullpath Return as image.png or full path.
 * 
 * @return Array The array is [N][3], where the N index is name base of icon and [N]['ok'] ok image, [N]['bad'] bad image, [N]['warning'] warning image and [N]['default] default image 
 */
function gis_get_array_list_icons($fullpath = true) {
	$return = array();
	$validExtensions = array('jpg', 'jpeg', 'gif', 'png');
	
	$path = '';
	if ($fullpath)
		$path = 'images/gis_map/icons/';
	
	$dir = scandir('images/gis_map/icons/');
	
	foreach ($dir as $index => $item) {
		$chunks = explode('.', $item);
		
		$extension = end($chunks);
		
		if (!in_array($extension, $validExtensions))
			unset($dir[$index]);
	}
	
	foreach ($dir as $item) {
		$chunks = explode('.', $item);
		$extension = end($chunks);
		
		$nameWithoutExtension = str_replace("." . $extension, "", $item);
		$nameClean = str_replace(array('.bad', '.ok', '.warning', '.default'), "", $nameWithoutExtension);
		
		$return[$nameClean]['ok'] = $path . $nameClean . '.ok.png';
		$return[$nameClean]['bad'] = $path . $nameClean . '.bad.png';
		$return[$nameClean]['warning'] = $path . $nameClean . '.warning.png';
		$return[$nameClean]['default'] = $path . $nameClean . '.default.png';
	}
	
	return $return;
}

function gis_validate_map_data($map_name, $map_zoom_level,
	$map_initial_longitude, $map_initial_latitude, $map_initial_altitude,
	$map_default_longitude, $map_default_latitude, $map_default_altitude,
	$map_connection_list, $map_levels_zoom) {
	$invalidFields = array();
	
	echo "<style type='text/css'>";
	
	//validateMap
	if ($map_name == '') {
		echo "input[name=map_name] {background: #FF5050;}";
		$invalidFields['map_name'] = true;
	}
	
	//validate zoom level
	if (($map_zoom_level == '') || ($map_zoom_level > $map_levels_zoom)) {
		echo "input[name=map_zoom_level] {background: #FF5050;}";
		$invalidFields['map_zoom_level'] = true;
	}
	
	//validate map_initial_longitude
	if ($map_initial_longitude == '') {
		echo "input[name=map_initial_longitude] {background: #FF5050;}";
		$invalidFields['map_initial_longitude'] = true;
	}
	
	//validate map_initial_latitude
	if ($map_initial_latitude == '') {
		echo "input[name=map_initial_latitude] {background: #FF5050;}";
		$invalidFields['map_initial_latitude'] = true;
	}
	
	//validate map_initial_altitude
	if ($map_initial_altitude == '') {
		echo "input[name=map_initial_altitude] {background: #FF5050;}";
		$invalidFields['map_initial_altitude'] = true;
	}
	
	//validate map_default_longitude
	if ($map_default_longitude == '') {
		echo "input[name=map_default_longitude] {background: #FF5050;}";
		$invalidFields['map_default_longitude'] = true;
	}
	
	//validate map_default_latitude
	if ($map_default_latitude == '') {
		echo "input[name=map_default_latitude] {background: #FF5050;}";
		$invalidFields['map_default_latitude'] = true;
	}
	
	//validate map_default_altitude
	if ($map_default_altitude == '') {
		echo "input[name=map_default_altitude] {background: #FF5050;}";
		$invalidFields['map_default_altitude'] = true;
	}
	
	//validate map_default_altitude
	if ($map_connection_list == '') {
		$invalidFields['map_connection_list'] = true;
	}

	echo "</style>";
	
	return $invalidFields;
}

/**
 * Get all data (connections, layers with agents) of a map passed as id.
 * 
 * @param integer $idMap The id of map in database.
 * 
 * @return Array Return a asociative array whith the items 'map', 'connections' and 'layers'. And in 'layers' has data and item 'agents'.
 */
function gis_get_map_data($idMap) {
	global $config;
	
	$returnVar = array();
	
	$map = db_get_row('tgis_map', 'id_tgis_map', $idMap);
	
	switch ($config["dbtype"]) {
		case "mysql":
			$connections = db_get_all_rows_sql('SELECT t1.tgis_map_connection_id_tmap_connection AS id_conection,
					t1.default_map_connection AS `default`, (
						SELECT t2.num_zoom_levels
						FROM tgis_map_connection AS t2
						WHERE t2.id_tmap_connection = t1.tgis_map_connection_id_tmap_connection) AS num_zoom_levels
				FROM tgis_map_has_tgis_map_connection AS t1
				WHERE t1.tgis_map_id_tgis_map = '. $map['id_tgis_map']);
			break;
		case "postgresql":
		case "oracle":
			$connections = db_get_all_rows_sql('SELECT t1.tgis_map_connection_id_tmap_connection AS id_conection,
					t1.default_map_connection AS "default", (
						SELECT t2.num_zoom_levels
						FROM tgis_map_connection AS t2
						WHERE t2.id_tmap_connection = t1.tgis_map_connection_id_tmap_connection) AS num_zoom_levels
				FROM tgis_map_has_tgis_map_connection AS t1
				WHERE t1.tgis_map_id_tgis_map = '. $map['id_tgis_map']);
			break;
	}
	$layers = db_get_all_rows_sql('SELECT id_tmap_layer, layer_name,
			tgrupo_id_grupo AS layer_group, view_layer AS layer_visible
		FROM tgis_map_layer
		WHERE tgis_map_id_tgis_map = ' . $map['id_tgis_map'] . '
		ORDER BY layer_stack_order ASC;');
	if ($layers === false) $layers = array();
	
	foreach ($layers as $index => $layer) {
		$agents = db_get_all_rows_sql('SELECT nombre
			FROM tagente
			WHERE id_agente IN (
				SELECT tagente_id_agente
				FROM tgis_map_layer_has_tagente
				WHERE tgis_map_layer_id_tmap_layer = ' . $layer['id_tmap_layer'] . ')');
		if ($agents !== false)
			$layers[$index]['layer_agent_list'] = $agents;
		else
			$layers[$index]['layer_agent_list'] = array();
	}
	
	$returnVar['map'] = $map;
	$returnVar['connections'] = $connections;
	$returnVar['layers'] = $layers;
	
	return $returnVar;
}

/**
 * This function use in form the "pandora_console/godmode/gis_maps/configure_gis_map.php"
 * in the case of edit a map or when there are any error in save new map. Because this function
 * return a html code that it has the rows of connections.
 * 
 * @param Array $map_connection_list The list of map connections for convert a html.
 * 
 * @return String The html source code.
 */
function gis_add_conection_maps_in_form($map_connection_list) {
	$returnVar = '';
	
	foreach ($map_connection_list as $mapConnection) {
		$mapConnectionRowDB = gis_get_map_connection($mapConnection['id_conection']);
		
		if ($mapConnection['default']) {
			$radioButton = html_print_radio_button_extended('map_connection_default', $mapConnection['id_conection'], '', $mapConnection['id_conection'], false, 'changeDefaultConection(this.value)', '', true);
		}
		else
			$radioButton = html_print_radio_button_extended('map_connection_default', $mapConnection['id_conection'], '', null, false, 'changeDefaultConection(this.value)', '', true);
		
		$returnVar .= '
			<tbody id="map_connection_' . $mapConnection['id_conection'] . '">
				<tr class="row_0">
					<td>' . html_print_input_text ('map_connection_name_' . $mapConnection['id_conection'], $mapConnectionRowDB['conection_name'], '', 20, 40, true, true) . '</td>
					<td>' . $radioButton . '</td>
					<td><a id="delete_row" href="javascript: deleteConnectionMap(\'' . $mapConnection['id_conection'] . '\')">' . html_print_image("images/cross.png", true, array("alt" => "")) . '</a></td>
				</tr>
			</tbody>
			<script type="text/javascript">
			connectionMaps.push(' . $mapConnection['id_conection'] . ');
			</script>
			';
	}
	
	return $returnVar;
}

/**
 * From a list of connection maps, extract the num levels zooom
 * 
 * @param array $map_connection_list The list of connections maps.
 * 
 * @return integer The num zoom levels.
 */
function gis_get_num_zoom_levels_connection_default($map_connection_list) {
	foreach ($map_connection_list as $connection) {
		if ($connection['default']) {
			return $connection['num_zoom_levels'];
		}
	}
}

/**
 * This function use in form the "pandora_console/godmode/gis_maps/configure_gis_map.php"
 * in the case of edit a map or when there are any error in save new map. Because this function
 * return a html code that it has the rows of layers of map.
 * 
 * @param Array $layer_list The list of layers for convert a html.
 * 
 * @return String The html source code.
 */
function gis_add_layer_list($layer_list) {
	$returnVar = '';
	
	$count = 0;
	foreach ($layer_list as $layer) {
		//Create the layer temp form as it was in the form
		$layerTempForm = array();
		$layerTempForm['id'] = $layer['id'];
		$layerTempForm['layer_name'] = $layer['layer_name'];
		$layerTempForm['layer_group'] = $layer['layer_group'];
		$layerTempForm['layer_visible'] = $layer['layer_visible'];
		if (array_key_exists('layer_agent_list', $layer)) {
			foreach ($layer['layer_agent_list'] as $agent) {
				$layerTempForm['layer_agent_list'][] = $agent;
			}
		}
		
		$layerDataJSON = json_encode($layerTempForm);
		
		$returnVar .= '
			<tbody id="layer_item_' . $count . '">
				<tr>
					<td class="col1">' .
						$layer['layer_name'] .
						'</td>
					<td class="up_arrow"><a id="up_arrow" href="javascript: upLayer(' . $count . ');">' . html_print_image("images/up.png", true, array("alt" => "")) . '</a></td>
					<td class="down_arrow"><a id="down_arrow" href="javascript: downLayer(' . $count . ');">' . html_print_image("images/down.png", true, array("alt" => "")) . '</a></td>
					<td class="col3">
						<a id="edit_layer" href="javascript: editLayer(' . $count . ');">' . html_print_image("images/config.png", true, array("alt" => "")) . '</a>
					</td>
					<td class="col4">
						<input type="hidden" name="layer_values_' . $count . '" value=\'' . $layerDataJSON . '\' id="layer_values_' . $count . '" />
						<a id="delete_row" href="javascript: deleteLayer(' . $count . ')">' . html_print_image("images/cross.png", true, array("alt" => "")) . '</a>
					</td>
				</tr>
			</tbody>
			<script type="text/javascript">
				layerList.push(countLayer);
				countLayer++;
				updateArrowLayers();
			</script>';
		
		$count ++;
	}
	
	return $returnVar;
}
?>
