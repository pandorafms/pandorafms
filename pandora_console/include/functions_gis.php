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

function printMap($idDiv, $iniZoom, $numLevelZooms, $latCenter, $lonCenter, $baselayers, $controls = null) {
	$controls = (array)$controls;
	
	//require_javascript_file('OpenLayers/OpenLayers');
	?>
	<script type="text/javascript" src="http://dev.openlayers.org/nightly/OpenLayers.js"></script>
	<script type="text/javascript">
		$(document).ready (
			function () {
				map = new OpenLayers.Map ("<?php echo $idDiv; ?>", {
					<?php
					echo "controls: [";
					$first = true;
					foreach ($controls as $control) {
						if (!$first) echo ",";
						$first = false;
						
						switch ($control) {
							case 'Navigation':
								echo "new OpenLayers.Control.Navigation()";
								break;
							case 'MousePosition':
								echo "new OpenLayers.Control.MousePosition()";
								break;
							case 'OverviewMap':
								echo "new OpenLayers.Control.OverviewMap()";
								break;
							case 'PanZoom':
								echo "new OpenLayers.Control.PanZoom()";
								break;
							case 'PanZoomBar':
								echo "new OpenLayers.Control.PanZoomBar()";
								break;
							case 'ScaleLine':
								echo "new OpenLayers.Control.ScaleLine()";
								break;
							case 'Scale':
								echo "new OpenLayers.Control.Scale()";
								break;
						}
					}
					echo ", new OpenLayers.Control.LayerSwitcher()";
					echo "],";
					?>
					maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
					maxResolution: 156543.0399,
					numZoomLevels: <?php echo $numLevelZooms; ?>,
					units: 'm', //metros
					projection: new OpenLayers.Projection("EPSG:900913"),
					displayProjection: new OpenLayers.Projection("EPSG:4326")
				});

				//Define the maps layer
				<?php
				$i = 0;
				foreach ($baselayers as $baselayer) {
					switch ($baselayer['typeBaseLayer']) {
						case 'OSM':
							?>
							var baseLayer = new OpenLayers.Layer.OSM("<?php echo $baselayer['name']; ?>", "<?php echo $baselayer['url']; ?>", {numZoomLevels: <?php echo $numLevelZooms; ?>});
							map.addLayer(baseLayer);
							<?php
							break;
					}
				}
				?>

				if( ! map.getCenter() ){
					var lonLat = new OpenLayers.LonLat(<?php echo $lonCenter; ?>, <?php echo $latCenter; ?>)
						.transform(map.displayProjection, map.getProjectionObject());
					map.setCenter (lonLat, <?php echo $iniZoom; ?>);
				}
			}
		);
	</script>
	<?php
}

function makeLayer($name, $visible = true, $dot = null) {
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

            layer.setVisibility(<?php echo $visible; ?>);
			map.addLayer(layer);

            layer.events.on({
                "featureselected": function(e) {
                	if (e.feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
                    	var feature = e.feature;
                		var featureData = feature.data;
                		var long_lat = featureData.long_lat;

						var popup;
						
        	            popup = new OpenLayers.Popup.FramedCloud('cloud00',
            	            	long_lat,
								null,
								'<div class="cloudContent' + featureData.id + '" style="text-align: center;"><img src="images/spinner.gif" /></div>',
								null,
								true,
								function () { popup.destroy(); });
								feature.popup = popup;
								map.addPopup(popup);

                		jQuery.ajax ({
                    		data: "page=operation/gis_maps/ajax&opt="+featureData.type+"&id="  + featureData.id,
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

function activateSelectControl($layers=null) {
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
 * Activate the feature refresh  by ajax.
 * 
 * @param Array $layers Its a rows of table "tgis_map_layer" or None is all.
 * @param integer $lastTimeOfData The time in unix timestamp of last query of data GIS in DB.
 * 
 * @return None
 */
function activateAjaxRefresh($layers = null, $lastTimeOfData = null) {
	if ($lastTimeOfData === null) $lastTimeOfData = time();
	
	require_jquery_file ('json');
	?>
	<script type="text/javascript">
		var last_time_of_data = <?php echo $lastTimeOfData; ?>; //This time use in the ajax query to next recent points.
		var refreshAjaxIntervalSeconds = 1000;
		var idIntervalAjax = null;

		function searchPointAgentById(id) {
			for (layerIndex = 0; layerIndex < map.getNumLayers(); layerIndex++) {
				layer = map.layers[layerIndex];

				if (layer.features != undefined) {
					for (featureIndex = 0; featureIndex < layer.features.length; featureIndex++) {
						feature = layer.features[featureIndex];
						if (feature.data.id == id) {
							return feature;
						}
					}
				}
			}

			return null;
		}

		function refreshAjaxLayer(layer) {
			var featureIdArray = Array();
			
			for (featureIndex = 0; featureIndex < layer.features.length; featureIndex++) {
				feature = layer.features[featureIndex];

				if (feature.data.type != 'point_path_info') {
					featureIdArray.push(feature.data.id);
				}
			}

			if (featureIdArray.length > 0) {
	    		jQuery.ajax ({
	        		data: "page=operation/gis_maps/ajax&opt=get_new_positions&id_features="  + featureIdArray.toString()
	        			+ "&last_time_of_data=" + last_time_of_data,
	        		type: "GET",
	        		dataType: 'json',
	        		url: "ajax.php",
	        		timeout: 10000,
	        		success: function (data) {
	        			if (data.correct) {
		        			if (data.content != "null") {
		        				listAgentsPoints = $.evalJSON(data.content);
		        				
		        				for (var idAgent in listAgentsPoints) {
		        					if (isInt(idAgent)) {
				        				listPoints = listAgentsPoints[idAgent];
		
				        				for (var pointIndex in listPoints) {
				        					if (isInt(pointIndex)) {
					        					feature = searchPointAgentById(idAgent);
	
					        					var point = new OpenLayers.LonLat(listPoints[pointIndex].longitude, listPoints[pointIndex].latitude)
					        					.transform(map.displayProjection, map.getProjectionObject());
	
					        					feature.data.long_lat = point;
					        					feature.move(point);
				        					}
				        				}
		        					}
		        				}
		        			}
	        			}
	    			}
	    		});
			}
		}
	
		function clock_ajax_refresh() {			
			for (layerIndex = 0; layerIndex < map.getNumLayers(); layerIndex++) {
				layer = map.layers[layerIndex];
				<?php
				if ($layers === null) {
					refreshAjaxLayer(layer);
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
			//last_time_of_data = Math.round(new Date().getTime() / 1000); //Unixtimestamp

			//clearInterval(idIntervalAjax);
		}
	
		$(document).ready (
			function () {
				idIntervalAjax = setInterval("clock_ajax_refresh()", refreshAjaxIntervalSeconds);
			}
		);
	</script>
	<?php
}

function addPoint($layerName, $pointName, $lat, $lon, $icon = null, $width = 20, $height = 20, $point_id  = '', $type_string = '') {
	?>
	<script type="text/javascript">
	$(document).ready (
		function () {
			<?php
			if ($icon != null) { 
			?>
				js_addPointExtent('<?php echo $layerName; ?>',
					'<?php echo $pointName; ?>', <?php echo $lon; ?>,
					<?php echo $lat; ?>, '<?php echo $icon; ?>', <?php echo $width; ?>,
					<?php echo $height?>, <?php echo $point_id; ?>, '<?php echo $type_string; ?>');
			<?php
			}
			else { 
			?>
				js_addPoint('<?php echo $layerName; ?>',
					'<?php echo $pointName; ?>', <?php echo $lon; ?>, <?php echo $lat; ?>, <?php echo $point_id; ?>,
					'<?php echo $type_string; ?>');
			<?php
			} 
			?>
		}
	);
	</script>
	<?php
}

function addPointPath($layerName, $lat, $lon, $color, $manual = 1, $id) {
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

function getMaps() {
	return get_db_all_rows_in_table ('tgis_map', 'map_name');
}
/**
 * Gets the configuration of all the base layers of a map
 * 
 * @param $idMap: Map identifier of the map to get the configuration
 *
 * @return An array of arrays of configuration parameters
 */
function getMapConf($idMap) {
	$mapConfs= get_db_all_rows_sql('SELECT tconn.*, trel.default_map_connection FROM tgis_map_connection AS tconn, tgis_map_has_tgis_map_connection AS trel
			 WHERE trel.tgis_map_connection_id_tmap_connection = tconn.id_tmap_connection AND trel.tgis_map_id_tgis_map = ' . $idMap);
	return $mapConfs;
}

function getLayers($idMap) {
	$layers = get_db_all_rows_sql('SELECT * FROM tgis_map_layer WHERE tgis_map_id_tgis_map = ' . $idMap);
	
	return $layers;
}

function get_agent_last_coords($idAgent) {
		$coords = get_db_row_sql("SELECT last_latitude, last_longitude, last_altitude, nombre FROM tagente WHERE id_agente = " . $idAgent);

		return $coords;
}

function get_agent_icon_map($idAgent, $state = false) {
	$row = get_db_row_sql('SELECT id_grupo, icon_path FROM tagente WHERE id_agente = ' . $idAgent);
	
	if ($row['icon_path'] === null) {
		$iconGroup = "images/groups_small/" . get_group_icon($row['id_grupo']) . ".png";
		return $iconGroup;
	}
	else {
		$icon = "images/gis_map/icons/" . $row['icon_path'];
		if (!$state)
			return $icon . ".png";
		else {
			switch (get_agent_status($idAgent)) {
				case 1:
				case 4:
					//Critical (BAD or ALERT)
					$state = "_bad";
					break;
				case 0:
					//Normal (OK)
					$state = "_ok";
					break;
				case 2:
					//Warning
					$state = "_warning";
					break;
				default:
					// Default is Grey (Other)
					$state = '';
			}
			
			return $icon . $state . ".png";
		}
	}
}

function addPath($layerName, $idAgent) {
	
	$listPoints = get_db_all_rows_sql('SELECT * FROM tgis_data WHERE tagente_id_agente = ' . $idAgent . ' ORDER BY end_timestamp ASC');
	
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
			if (end($listPoints) != $point)
				addPointPath($layerName, $point['latitude'], $point['longitude'], $color, (int)$point['manual_placement'], $point['id_tgis_data']);
		}
	}
}

function deleteMapConnection($idConectionMap) {
	
	process_sql_delete ('tgis_map_connection', array('id_tmap_connection' => $idConectionMap));
	
	//TODO DELETE IN OTHER TABLES
}

function saveMapConnection($mapConnection_name, $mapConnection_group,
			$mapConnection_numLevelsZoom, $mapConnection_defaultZoom,
			$mapConnection_defaultLatitude, $mapConnection_defaultLongitude,
			$mapConnection_defaultAltitude, $mapConnection_centerLatitude,
			$mapConnection_centerLongitude, $mapConnection_centerAltitude,
			$mapConnectionData, $idConnectionMap = null) {

	if ($idConnectionMap !== null) {
		$returnQuery = process_sql_update('tgis_map_connection',
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
		$returnQuery = process_sql_insert('tgis_map_connection',
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
function deleteMap($idMap) {
	$listIdLayers = get_db_all_rows_sql("SELECT id_tmap_layer FROM tgis_map_layer WHERE tgis_map_id_tgis_map = " . $idMap);
	
	if ($listIdLayers !== false) {
		foreach ($listIdLayers as $idLayer) {
			process_sql_delete('tgis_map_layer_has_tagente', array('tgis_map_layer_id_tmap_layer' => $idLayer));
		}
	}
	process_sql_delete('tgis_map_layer', array('tgis_map_id_tgis_map' => $idMap));
	process_sql_delete('tgis_map_has_tgis_map_connection', array('tgis_map_id_tgis_map' => $idMap));
	process_sql_delete('tgis_map', array('id_tgis_map' => $idMap));
	
	$numMaps = get_db_num_rows('SELECT * FROM tgis_map');
	
	clean_cache();
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
function saveMap($map_name, $map_initial_longitude, $map_initial_latitude,
	$map_initial_altitude, $map_zoom_level, $map_background,
	$map_default_longitude, $map_default_latitude, $map_default_altitude,
	$map_group_id, $map_connection_list, $arrayLayers) {
	
	$idMap = process_sql_insert('tgis_map',
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
	
	$numMaps = get_db_num_rows('SELECT * FROM tgis_map');
	
	if ($numMaps == 1)
		process_sql_update('tgis_map', array('default_map' => 1), array('id_tgis_map' => $idMap));
	
	foreach ($map_connection_list as $map_connection) {
		process_sql_insert('tgis_map_has_tgis_map_connection',
			array(
				'tgis_map_id_tgis_map' => $idMap,
				'tgis_map_connection_id_tmap_connection' => $map_connection['id_conection'],                       
				'default_map_connection' => $map_connection['default']
			)
		);
	}
	
	foreach ($arrayLayers as $index => $layer) {
		$idLayer = process_sql_insert('tgis_map_layer',
			array(
				'layer_name' => $layer['layer_name'],
				'view_layer' => $layer['layer_visible'],
				'layer_stack_order' => $index,
				'tgis_map_id_tgis_map' => $idMap,
				'tgrupo_id_grupo' => $layer['layer_group']
			)
		);
		
		if (count($layer['layer_agent_list']) > 0) {
			foreach ($layer['layer_agent_list'] as $agent_name) {
				process_sql_insert('tgis_map_layer_has_tagente',
					array(
						'tgis_map_layer_id_tmap_layer' => $idLayer,
						'tagente_id_agente' => get_agent_id($agent_name)
					)
				);
			}
		}
	}
}

/**
 * Get the configuration parameters of a map connection
 * 
 * @param idConnection: connection identifier for the map
 *
 * @result: An array with all the configuration parameters
 */
 function getConectionConf($idConnection) {
    $confParameters = get_db_row_sql('SELECT * FROM tgis_map_connection WHERE id_tmap_connection = ' . $idConnection);
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
 * @return A div tag with the map and the agent and the history path if asked.
 */
function getAgentMap($agent_id, $heigth, $width, $show_history = false, $centerInAgent = true, $history_time = 86400) {
	$defaultMap = get_db_all_rows_sql("
		SELECT t1.*, t3.conection_name, t3.connection_type, t3.conection_data, t3.num_zoom_levels
		FROM tgis_map AS t1, 
			tgis_map_has_tgis_map_connection AS t2, 
			tgis_map_connection AS t3
		WHERE t1.default_map = 1 AND t2.tgis_map_id_tgis_map = t1.id_tgis_map
			AND t2.default_map_connection = 1
			AND t3.id_tmap_connection = t2.tgis_map_connection_id_tmap_connection");
	$defaultMap = $defaultMap[0];
	
	$agent_position = get_agent_last_coords($agent_id);
	$agent_name = $agent_position['nombre'];
	
	$conectionData = json_decode($defaultMap['conection_data'], true);
	$baselayers[0]['url'] = $conectionData['url'];
	$baselayers[0]['name'] = $defaultMap['conection_name'];
	$baselayers[0]['typeBaseLayer'] = $conectionData['type'];
	$controls = array('PanZoom', 'ScaleLine', 'Navigation', 'MousePosition', 'OverviewMap');
	
	printMap($agent_name."_agent_map", $defaultMap['zoom_level'],
		$defaultMap['num_zoom_levels'], $defaultMap['initial_latitude'],
		$defaultMap['initial_longitude'], $baselayers, $controls);
		
	makeLayer("layer_for_agent_".$agent_name);
	
	$agent_icon = get_agent_icon_map($agent_id);
		/* If show_history is true, show the path of the agent */
	if ($show_history) {
		/* TODO: only show the last history_time part of the path */
		addPath("layer_for_agent_".$agent_name,$agent_id);
	}
	addPoint("layer_for_agent_".$agent_name, $agent_name, $agent_position['last_latitude'], $agent_position['last_longitude'], $agent_icon, 20, 20, $agent_id, 'point_agent_info');
	
	if ($centerInAgent) {
		?>
		<script type="text/javascript">
		$(document).ready (
			function () { 
				var lonlat = new OpenLayers.LonLat(<?php echo $agent_position['last_longitude']; ?>, <?php echo $agent_position['last_latitude']; ?>)
					.transform(map.displayProjection, map.getProjectionObject());
				map.setCenter(lonlat, <?php echo $defaultMap['zoom_level']; ?>, false, false);
			});
		</script>
		<?php
	}
}

/**
 * Return a array of images as icons in the /pandora_console/images/gis_map/icons.
 * 
 * @param boolean $fullpath Return as image.png or full path.
 * 
 * @return Array The array is [N][3], where the N index is name base of icon and [N]['ok'] ok image, [N]['bad'] bad image, [N]['warning'] warning image and [N]['default] default image 
 */
function getArrayListIcons($fullpath = true) {
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
	
	$baseImages = array();
	$stateImages = array();
	foreach ($dir as $item) {
		if (strstr($item, "_") !== false)
			$stateImages[] = $item;
		else
			$baseImages[] = $item;
	}
	
	foreach ($baseImages as $item) {
		$chunks = explode('.', $item);
		$extension = end($chunks);
		
		$nameWithoutExtension = str_replace("." . $extension, "", $item);
		
		$return[$nameWithoutExtension]['ok'] = null;
		$return[$nameWithoutExtension]['bad'] = null;
		$return[$nameWithoutExtension]['warning'] = null;
		$return[$nameWithoutExtension]['default'] = $path . $item;
		
		if (in_array($nameWithoutExtension.'_bad.' . $extension, $stateImages))
			$return[$nameWithoutExtension]['bad'] = $path . $nameWithoutExtension.'_bad.' . $extension;
		
		if (in_array($nameWithoutExtension.'_ok.' . $extension, $stateImages))
			$return[$nameWithoutExtension]['ok'] = $path . $nameWithoutExtension.'_ok.' . $extension;
			$return[$nameWithoutExtension]['bad'] = $path . $nameWithoutExtension.'_bad.' . $extension;
		
		if (in_array($nameWithoutExtension.'_warning.' . $extension, $stateImages))
			$return[$nameWithoutExtension]['warning'] = $path . $nameWithoutExtension.'_warning.' . $extension;
		
	}
	
	return $return;
}
?>
