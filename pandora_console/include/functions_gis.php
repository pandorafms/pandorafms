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
		var map;
		
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

		function showHideLayer(name, action) {
			var layer = map.getLayersByName(name);

			layer[0].setVisibility(action);
		}

		function addPoint(layerName, pointName, lon, lat) {
			var point = new OpenLayers.Geometry.Point(lon, lat)
				.transform(map.displayProjection, map.getProjectionObject());

			var layer = map.getLayersByName(layerName);
			layer = layer[0];

			layer.addFeatures(new OpenLayers.Feature.Vector(point,{nombre: pointName, estado: "ok"}));
		}

		function addPointExtent(layerName, pointName, lon, lat, icon, width, height) {
			var point = new OpenLayers.Geometry.Point(lon, lat)
			.transform(map.displayProjection, map.getProjectionObject());

			var layer = map.getLayersByName(layerName);
			layer = layer[0];
			layer.addFeatures(new OpenLayers.Feature.Vector(point,{estado: "ok"}, {fontWeight: "bolder", fontColor: "#00014F", labelYOffset: -height, graphicHeight: width, graphicWidth: height, externalGraphic: icon, label: pointName}));
		}

		function addPointPath(layerName, lon, lat, color, manual, id) {
			var point = new OpenLayers.Geometry.Point(lon, lat)
				.transform(map.displayProjection, map.getProjectionObject());
			
			var layer = map.getLayersByName(layerName);
			layer = layer[0];

			var pointRadiusNormal = 4;
			var strokeWidth = 2;
			var pointRadiusManual = pointRadiusNormal - (strokeWidth / 2); 
			
			if (manual) {
				point = new OpenLayers.Feature.Vector(point,{estado: "ok", id: id,
					lanlot: new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject())},
					{fillColor: "#ffffff", pointRadius: pointRadiusManual, stroke: 1, strokeColor: color, strokeWidth: strokeWidth, cursor: "pointer"}
				);
			}
			else {
				point = new OpenLayers.Feature.Vector(point,{estado: "ok", id: id,
					lanlot: new OpenLayers.LonLat(lon, lat).transform(map.displayProjection, map.getProjectionObject())},
						{fillColor: color, pointRadius: pointRadiusNormal, cursor: "pointer"}
				);
			}

			layer.addFeatures(point);
		}  

		function addLineString(layerName, points, color) {
			var mapPoints = new Array(points.length);
			var layer = map.getLayersByName(layerName);

			layer = layer[0];
			
			for (var i = 0; i < points.length; i++) {
				mapPoints[i] = points[i].transform(map.displayProjection, map.getProjectionObject());
			}
			
			var lineString = new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(mapPoints),
				null,
				{ strokeWidth: 2, fillOpacity: 0.2, fillColor: color, strokeColor: color}
			);

			layer.addFeatures(lineString);
		}
	</script>
	<?php
}

function makeLayer($name, $visible = true, $dot = null) { static $i = 0;
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
                		var lanlot = featureData.lanlot;

						var popup;
						
        	            popup = new OpenLayers.Popup.FramedCloud('cloud00',
            	            	lanlot,
								null,
								'<div class="cloudContent' + featureData.id + '" style="text-align: center;"><img src="images/spinner.gif" /></div>',
								null,
								true,
								function () { popup.destroy(); });
								feature.popup = popup;
								map.addPopup(popup);

                		jQuery.ajax ({
                    		data: "page=operation/gis_maps/ajax&opt=point_info&id="  + featureData.id,
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

function addPoint($layerName, $pointName, $lat, $lon, $icon = null, $width = 20, $height = 20) {
	?>
	<script type="text/javascript">
	$(document).ready (
		function () {
			<?php
			if ($icon != null) { 
			?>
				addPointExtent('<?php echo $layerName; ?>',
					'<?php echo $pointName; ?>', <?php echo $lon; ?>,
					<?php echo $lat; ?>, '<?php echo $icon; ?>', <?php echo $width; ?>, <?php echo $height?>);
			<?php
			}
			else { 
			?>
			addPoint('<?php echo $layerName; ?>',
					'<?php echo $pointName; ?>', <?php echo $lon; ?>, <?php echo $lat; ?>);
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
			addPointPath('<?php echo $layerName; ?>', <?php echo $lon; ?>, <?php echo $lat; ?>, '<?php echo $color; ?>', <?php echo $manual; ?>, <?php echo $id; ?>);
		}
	);
	</script>
	<?php
}

function getMaps() {
	return get_db_all_rows_in_table ('tgis_map', 'map_name');
}

function getMapConf($idMap) {
	$confsEncode = get_db_all_rows_sql('SELECT * FROM tgis_map_connection WHERE tgis_map_id_tgis_map = ' . $idMap);
	
	$confsDecode = array();
	
	if ($confsEncode !== false) {
		foreach ($confsEncode as $confEncode) {
			$temp = json_decode($confEncode['conection_data'], true);
			
			$confsDecode[$temp['type']][] = $temp['content'];
		}
	}
	
	return $confsDecode;
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
		else
			return $icon . "_" . $state . ".png";
	}
}

function addPath($layerName, $idAgent) {
	
	$listPoints = get_db_all_rows_sql('SELECT * FROM tgis_data WHERE tagente_id_agente = ' . $idAgent . ' ORDER BY end_timestamp ASC');
	
	if ($idAgent == 1) {
	$listPoints = array(
		array('id_tgis_data' => 0, 'longitude' => -3.709, 'latitude' => 40.422, 'altitude' => 0, 'manual_placement' => 1),
		array('id_tgis_data' => 1, 'longitude' => -3.710, 'latitude' => 40.420, 'altitude' => 0, 'manual_placement' => 0),
		array('id_tgis_data' => 2, 'longitude' => -3.711, 'latitude' => 40.420, 'altitude' => 0, 'manual_placement' => 1),
		array('id_tgis_data' => 3, 'longitude' => -3.712, 'latitude' => 40.422, 'altitude' => 0, 'manual_placement' => 0),
		array('id_tgis_data' => 4, 'longitude' => -3.708187, 'latitude' => 40.42056, 'altitude' => 0, 'manual_placement' => 0)
	);
	}
	
	if ($idAgent == 2) {
	$listPoints = array(
		array('id_tgis_data' => 0, 'longitude' => -3.703, 'latitude' => 40.420, 'altitude' => 0, 'manual_placement' => 0),
		array('id_tgis_data' => 0, 'longitude' => -3.704, 'latitude' => 40.422, 'altitude' => 0, 'manual_placement' => 0),
		array('id_tgis_data' => 0, 'longitude' => -3.706, 'latitude' => 40.422, 'altitude' => 0, 'manual_placement' => 0)
	);
	}
	
	if ($idAgent == 3) {
		$listPoints = array(
		array('id_tgis_data' => 0, 'longitude' => -3.701, 'latitude' => 40.425, 'altitude' => 0, 'manual_placement' => 0),
		array('id_tgis_data' => 0, 'longitude' => -3.703, 'latitude' => 40.422, 'altitude' => 0, 'manual_placement' => 0),
		array('id_tgis_data' => 0, 'longitude' => -3.708, 'latitude' => 40.424, 'altitude' => 0, 'manual_placement' => 0),
		array('id_tgis_data' => 0, 'longitude' => -3.705, 'latitude' => 40.421, 'altitude' => 0, 'manual_placement' => 0)
	);
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
					echo "];";
				} 
				?>
				
				addLineString('<?php echo $layerName; ?>', points, '<?php echo $color; ?>');
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

function saveMap($conf, $baselayers, $layers) {
	$return = false;
	
	//TODO validation data
	
	//BY DEFAULT TODO need code and change db
	$articaLongitude = -3.708187;
	$articaLatitude = 40.42056;
	$articaAltitude = 0;
	$defaultControl = array ('type' => 'controls',
		'content' => array('Navigation', 'PanZoomBar', 'ScaleLine')
	);
	//BY DEFAULT TODO need code and change db
	
	$idMap = process_sql_insert('tgis_map',
		array(
			'map_name' => $conf['name'],   
			'initial_longitude' => $articaLongitude,
			'initial_latitude' => $articaLatitude,
			'initial_altitude' => $articaAltitude,
			'zoom_level' => $conf['initial_zoom'],
			'group_id' => $conf['group']
			
		)
	);
	$zoom = array("type" => "numLevelsZoom","content" => $conf['numLevelsZoom']);
	
	process_sql_insert('tgis_map_connection',
		array(
			'conection_data' => json_encode($defaultControl),    
			'tgis_map_id_tgis_map' => $idMap	
		)
	);
	
	process_sql_insert('tgis_map_connection',
		array(
			'conection_data' => json_encode($zoom),    
			'tgis_map_id_tgis_map' => $idMap	
		)
	);
	
	foreach ($baselayers as $baselayer) {
		switch ($baselayer['type']) {
			case 'osm':
				$temp = array(
    				'type' => 'baselayer',
					'content' => array(
            			'typeBaseLayer' => 'OSM',
						'url' => $baselayer['url'],
						'default' => $baselayer['default']
        			)
				);
				
				process_sql_insert('tgis_map_connection',
					array(
						'conection_data' => json_encode($temp),    
						'tgis_map_id_tgis_map' => $idMap	
					)
				);
				break;
		}
	}
	
	foreach($layers as $layer) {
		process_sql_insert('tgis_map_layer',
			array(
				'layer_name' => $layer['name'],
				'view_layer' => $layer['visible'],
				'tgis_map_id_tgis_map' => $idMap,
				'tgrupo_id_grupo' => $layer['group']
			)
		);			
	}
	
	return $return;
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
 * @param $history_time: Number of seconds in the past to show from where to start the history path.
 *
 * @return A div tag with the map and the agent and the history path if asked.
 */
function getAgentMap($agent_id, $heigth, $width, $show_history = false, $history_time = 86400) {
	
	//$default_map_conf = getConectionConf($config['default_map']);
	$default_map_conf = getConectionConf(1);
	$baselayers[0]['url']= 'http://tile.openstreetmap.org/${z}/${x}/${y}.png';
	$baselayers[0]['name'] = "OSM";
	$baselayers[0]['typeBaseLayer'] = "OSM";
	$agent_position = get_agent_last_coords($agent_id);
	$agent_name = $agent_position['nombre'];
	printMap($agent_name."_agent_map", $default_map_conf['default_zoom_level'] , $default_map_conf['num_zoom_levels'],$agent_position['last_latitude'], $agent_position['last_longitude'], $baselayers, $controls = array('PanZoom', 'ScaleLine', 'Navigation', 'MousePosition', 'OverviewMap') );
	//printMap($agent_id."_agent_map", 16 , 19, 40.42056, -3.70818 -3.708187, $baselayers, $controls = null) {

	makeLayer("layer_for_agent_".$agent_name);

	$agent_icon = get_agent_icon_map($agent_id);
		/* If show_history is true, show the path of the agent */
	if ($show_history) {
		/* TODO: only show the last history_time part of the path */
		addPath("layer_for_agent_".$agent_name,$agent_id);
	}
	addPoint("layer_for_agent_".$agent_name, $agent_name, $agent_position['last_latitude'], $agent_position['last_longitude'], $agent_icon);
}
?>

