// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

var map; // Map global var object is use in multiple places.
var statusShow = "all";
//The name of layer hierachy because in PHP change the text name into language of user.
var storeLayerNameHierachy = "";

function js_refreshParentLines(layerName) {
  if (typeof layerName == "undefined") {
    layerName = storeLayerNameHierachy;
  } else {
    storeLayerNameHierachy = layerName;
  }

  listFeaturesWithParents = Array();

  jQuery.each(map.getLayersByClass("OpenLayers.Layer.Vector"), function(
    i,
    layer
  ) {
    visible = layer.visibility;

    jQuery.each(layer.features, function(i, feature) {
      if (feature.data.type == "point_agent_info") {
        id_parent = feature.data.id_parent;
        long_lat = feature.data.long_lat;
        id = feature.data.id;
        status = feature.data.status;

        listFeaturesWithParents[id] = {
          id: id,
          id_parent: id_parent,
          long_lat: long_lat,
          status: status,
          visible: visible
        };
      }
    });
  });

  var layer = map.getLayersByName(layerName);
  layer = layer[0];

  layer.destroyFeatures();

  jQuery.each(listFeaturesWithParents, function(i, feature) {
    //INI "break" of foreach posibilites
    if (typeof feature == "undefined") return;
    if (feature.id_parent == 0) return;
    if (typeof listFeaturesWithParents[feature.id_parent] == "undefined")
      return; //The agent have parent but this parent is not in the map.
    if (!feature.visible || !listFeaturesWithParents[feature.id_parent].visible)
      return;
    if (
      isHideFeatureByStatus(feature.status) ||
      isHideFeatureByStatus(listFeaturesWithParents[feature.id_parent].status)
    )
      return;
    //END "break" of foreach posibilites

    points = new Array();

    points[0] = new OpenLayers.Geometry.Point(
      feature.long_lat.lon,
      feature.long_lat.lat
    );
    points[1] = new OpenLayers.Geometry.Point(
      listFeaturesWithParents[feature.id_parent].long_lat.lon,
      listFeaturesWithParents[feature.id_parent].long_lat.lat
    );

    var line = new OpenLayers.Feature.Vector(
      new OpenLayers.Geometry.LineString(points),
      null,
      {
        strokeWidth: 2,
        fillOpacity: 0.2,
        fillColor: "black",
        strokeDashstyle: "dash",
        strokeColor: "black"
      }
    );

    layer.addFeatures(line);
  });
}

/**
 * Inicialize the map in the browser and the object map.
 *
 * @param string id_div The id of div to draw the map.
 * @param integer initial_zoom The initial zoom to show the map.
 * @param float center_latitude The coord of latitude for center.
 * @param float center_longitude The coord of longitude for center.
 * @param array objBaseLayers The array of baselayers with number index, and the baselayer is another asociative array that content 'type', 'name' and 'url'.
 * @param array arrayControls The array of enabled controls, the controls is: 'Navigation', 'MousePosition', 'OverviewMap', 'PanZoom', 'PanZoomBar', 'ScaleLine', 'Scale'
 *
 * @return None
 */
function js_printMap(
  id_div,
  initial_zoom,
  center_latitude,
  center_longitude,
  objBaseLayers,
  arrayControls
) {
  controlsList = [];

  for (var controlIndex in arrayControls) {
    if (isInt(controlIndex)) {
      switch (arrayControls[controlIndex]) {
        case "Navigation":
          controlsList.push(new OpenLayers.Control.Navigation());
          break;
        case "MousePosition":
          controlsList.push(new OpenLayers.Control.MousePosition());
          break;
        case "OverviewMap":
          controlsList.push(new OpenLayers.Control.OverviewMap());
          break;
        case "PanZoom":
          controlsList.push(new OpenLayers.Control.PanZoom());
          break;
        case "PanZoomBar":
          controlsList.push(new OpenLayers.Control.PanZoomBar());
          break;
        case "ScaleLine":
          controlsList.push(new OpenLayers.Control.ScaleLine());
          break;
        case "Scale":
          controlsList.push(new OpenLayers.Control.Scale());
          break;
        case "layerSwitcher":
          controlsList.push(new OpenLayers.Control.LayerSwitcher());
          break;
      }
    }
  }

  var option = {
    controls: controlsList,
    projection: new OpenLayers.Projection("EPSG:900913"),
    displayProjection: new OpenLayers.Projection("EPSG:4326"),
    units: "m",
    numZoomLevels: 18,
    maxResolution: 156543.0339,
    maxExtent: new OpenLayers.Bounds(
      -20037508,
      -20037508,
      20037508,
      20037508.34
    )
  };

  map = new OpenLayers.Map(id_div, option);
  map.events.Event;
  var baseLayer = null;

  map.events.on({ zoomend: EventZoomEnd });
  map.events.on({ mouseup: EventZoomEnd });

  //Define the maps layer
  for (var baselayerIndex in objBaseLayers) {
    if (isInt(baselayerIndex)) {
      switch (objBaseLayers[baselayerIndex]["type"]) {
        case "OSM":
          baseLayer = null;
          baseLayer = new OpenLayers.Layer.OSM(
            objBaseLayers[baselayerIndex]["name"],
            objBaseLayers[baselayerIndex]["url"],
            {
              numZoomLevels: objBaseLayers[baselayerIndex]["num_zoom_levels"],
              sphericalMercator: true
            }
          );
          map.addLayer(baseLayer);
          break;
        case "Gmap":
          switch (objBaseLayers[baselayerIndex]["gmap_type"]) {
            case "G_PHYSICAL_MAP":
              baseLayer = new OpenLayers.Layer.Google(
                objBaseLayers[baselayerIndex]["name"],
                {
                  type: G_PHYSICAL_MAP,
                  numZoomLevels:
                    objBaseLayers[baselayerIndex]["num_zoom_levels"],
                  sphericalMercator: true
                }
              );
              map.addLayer(baseLayer);
              break;
            case "G_HYBRID_MAP":
              baseLayer = new OpenLayers.Layer.Google(
                objBaseLayers[baselayerIndex]["name"],
                {
                  type: G_HYBRID_MAP,
                  numZoomLevels:
                    objBaseLayers[baselayerIndex]["num_zoom_levels"],
                  sphericalMercator: true
                }
              );
              map.addLayer(baseLayer);
              break;
            case "G_SATELLITE_MAP":
              baseLayer = new OpenLayers.Layer.Google(
                objBaseLayers[baselayerIndex]["name"],
                {
                  type: G_SATELLITE_MAP,
                  numZoomLevels:
                    objBaseLayers[baselayerIndex]["num_zoom_levels"],
                  sphericalMercator: true
                }
              );
              map.addLayer(baseLayer);
              break;
            default:
              baseLayer = new OpenLayers.Layer.Google(
                objBaseLayers[baselayerIndex]["name"],
                {
                  numZoomLevels:
                    objBaseLayers[baselayerIndex]["num_zoom_levels"],
                  sphericalMercator: true
                }
              );
              map.addLayer(baseLayer);
              break;
          }
          break;
        case "Static_Image":
          baseLayer = null;
          baseLayer = new OpenLayers.Layer.Image(
            objBaseLayers[baselayerIndex]["name"],
            objBaseLayers[baselayerIndex]["url"],
            new OpenLayers.Bounds(
              objBaseLayers[baselayerIndex]["bb_left"],
              objBaseLayers[baselayerIndex]["bb_bottom"],
              objBaseLayers[baselayerIndex]["bb_right"],
              objBaseLayers[baselayerIndex]["bb_top"]
            ),
            new OpenLayers.Size(
              objBaseLayers[baselayerIndex]["image_width"],
              objBaseLayers[baselayerIndex]["image_height"]
            ),
            {
              projection: new OpenLayers.Projection("EPSG:4326"),
              numZoomLevels: objBaseLayers[baselayerIndex]["num_zoom_levels"]
            }
          );
          map.addLayer(baseLayer);
          break;
        case "WMS":
          // http://<server>/geoserver/wms
          // ?bbox=-130,24,-66,50
          // &styles=population
          // &Format=application/openlayers
          // &request=GetMap
          // &layers=topp:states
          // &width=550
          // &height=250
          // &srs=EPSG:4326
          var layer = new OpenLayers.Layer.WMS(
            objBaseLayers[baselayerIndex]["name"],
            objBaseLayers[baselayerIndex]["url"],
            {
              layers: objBaseLayers[baselayerIndex]["layers"],
              format: "image/png"
            },
            {
              numZoomLevels: objBaseLayers[baselayerIndex]["num_zoom_levels"]
            }
          );
          map.addLayer(layer);
          break;
      }
    }
  }

  var lonLat = new OpenLayers.LonLat(
    center_longitude,
    center_latitude
  ).transform(map.displayProjection, map.getProjectionObject());

  map.setCenter(lonLat, initial_zoom);
}

function EventZoomEnd(evt, zoom = map.zoom) {
  if (evt == null) {
    var actual_zoom = zoom < 6 ? 6 : zoom;
  } else {
    var actual_zoom = evt.object.zoom < 6 ? 6 : evt.object.zoom;
  }

  var max_width_marker = 38;
  var max_zoom_map = map.numZoomLevels;
  var max_font_size = 15;

  var actual_font_size = (actual_zoom * max_font_size) / max_zoom_map;

  jQuery.each($("tspan"), function(i, tspan) {
    if (actual_zoom <= 18 && actual_zoom > 13)
      actual_font_size = max_font_size - 3;
    else if (actual_zoom <= 13 && actual_zoom >= 8)
      actual_font_size = max_font_size - 6;
    else if (actual_zoom <= 8) actual_font_size = max_font_size - 6;
    $(tspan).css("font-size", actual_font_size);
  });

  var layers = map.getLayersByClass("OpenLayers.Layer.Vector");

  jQuery.each(layers, function(i, layer) {
    var features = layer.features;

    jQuery.each(features, function(j, feature) {
      var graphicHeight = feature.style.graphicHeight || -1;
      var graphicWidth = feature.style.graphicWidth || -1;

      if (graphicHeight > 0 && graphicWidth > 0) {
        var new_width_marker = (actual_zoom * max_width_marker) / max_zoom_map;
        var new_height_marker = (actual_zoom * max_width_marker) / max_zoom_map;

        feature.style.fontSize = "" + actual_font_size + " !important";
        feature.style.graphicHeight = new_height_marker;
        feature.style.graphicWidth = new_width_marker;
        feature.style.labelYOffset = new_width_marker * -1;
      }
    });
    layer.redraw();
  });
}

/**
 * Change the style of state button, and call the function "hideAgentsStatus"
 * whith new state for agents icons to show.
 *
 * @param string newShowStatus State to show.
 * @return none
 */
function changeShowStatus(newShowStatus) {
  statusShow = newShowStatus;
  hideAgentsStatus();
  EventZoomEnd(null, map.zoom);
  js_refreshParentLines();
}

/**
 * Function that change the visibility of feature by status and state var
 * statusShow
 *
 * @param object feature The feature to change the visibility
 * @param int status The status code, it can be (1,4) for bad, (2) for warning, (0) for ok and the rest
 * @return
 */
function hideFeatureByStatus(feature, status) {
  feature.style.display = "none";

  switch (statusShow) {
    case "bad":
      if (status == 1 || status == 4 || status == 100)
        feature.style.display = "";
      break;
    case "warning":
      if (status == 2 || status == 200) feature.style.display = "";
      break;
    case "ok":
      if (status == 0 || status == 300) feature.style.display = "";
      break;
    case "default":
      if (
        status != 1 &&
        status != 4 &&
        status != 2 &&
        status != 0 &&
        status != 100 &&
        status != 200 &&
        status != 300
      )
        feature.style.display = "";
      break;
    case "all":
      feature.style.display = "";
      break;
  }
}

/**
 * Test if the feature is hidden.
 *
 * @param integer status The integer status.
 * @return boolean The true or false.
 */
function isHideFeatureByStatus(status) {
  returnVar = true;

  switch (statusShow) {
    case "bad":
      if (status == 1 || status == 4 || status == 100) returnVar = false;
      break;
    case "warning":
      if (status == 2 || status == 200) returnVar = false;
      break;
    case "ok":
      if (status == 0 || status == 300) returnVar = false;
      break;
    case "default":
      if (
        status != 1 &&
        status != 4 &&
        status != 2 &&
        status != 0 &&
        status != 100 &&
        status != 200 &&
        status != 300
      )
        returnVar = false;
      break;
    case "all":
      returnVar = false;
      break;
  }

  return returnVar;
}

/**
 * Hide the agents icons that not is of current state var statusShow.
 *
 * @return none
 */
function hideAgentsStatus() {
  layers = map.getLayersByClass("OpenLayers.Layer.Vector");

  jQuery.each(layers, function(i, layer) {
    features = layer.features;

    jQuery.each(features, function(j, feature) {
      status = feature.data.status;

      hideFeatureByStatus(feature, status);
    });

    layer.redraw();
  });
}

/**
 * Change the refresh time for the map.
 *
 * @param int time seconds
 * @return none
 */
function changeRefreshTime(time) {
  refreshAjaxIntervalSeconds = time * 1000;

  clearInterval(idIntervalAjax);
  idIntervalAjax = setInterval(
    "clock_ajax_refresh()",
    refreshAjaxIntervalSeconds
  );
  oldRefreshAjaxIntervalSeconds = refreshAjaxIntervalSeconds;
}

/**
 * Make the layer in the map.
 *
 * @param string name The name of layer, it's show in the toolbar of layer.
 * @param boolean visible Set visible the layer.
 * @param array dot It's a asociative array that have 'url' of icon image, 'width' in pixeles and 'height' in pixeles.
 *
 * @return object The layer created.
 */
function js_makeLayer(name, visible, dot) {
  if (dot == null) {
    dot = Array();
    dot["url"] = "images/dot_green.png";
    dot["width"] = 20; //11;
    dot["height"] = 20; //11;
  }

  //Set the style in layer
  var style = new OpenLayers.StyleMap({
    fontColor: "#ff0000",
    labelYOffset: -dot["height"],
    graphicHeight: dot["height"],
    graphicWidth: dot["width"],
    externalGraphic: dot["url"],
    label: "${nombre}"
  });

  //Make the layer as type vector
  var layer = new OpenLayers.Layer.Vector(name, { styleMap: style });

  layer.setVisibility(visible);
  map.addLayer(layer);

  return layer;
}

/**
 * Active and set callbacks of events.
 *
 * @param callbackFunClick Function to call when the user make single click in the map.
 *
 * @return None
 */
function js_activateEvents(callbackFunClick) {
  /**
   * Pandora click openlayers object.
   */
  OpenLayers.Control.PandoraClick = OpenLayers.Class(OpenLayers.Control, {
    defaultHandlerOptions: {
      single: true,
      double: false,
      pixelTolerance: 0,
      stopSingle: false,
      stopDouble: false
    },
    initialize: function(options) {
      this.handlerOptions = OpenLayers.Util.extend(
        {},
        this.defaultHandlerOptions
      );
      OpenLayers.Control.prototype.initialize.apply(this, arguments);
      this.handler = new OpenLayers.Handler.Click(
        this,
        { click: options.callbackFunctionClick },
        this.handlerOptions
      );
    }
  });

  var click = new OpenLayers.Control.PandoraClick({
    callbackFunctionClick: callbackFunClick
  });

  map.addControl(click);
  click.activate();
}

/**
 * Test the value is a int.
 *
 * @param mixed X The val that test to if is int.
 *
 * @return Boolean True if it's int.
 */
function isInt(x) {
  var y = parseInt(x);
  if (isNaN(y)) return false;
  return x == y && x.toString() == y.toString();
}

/**
 * Set the visibility of a layer
 *
 * @param string name The name of layer.
 * @param boolean action True or false
 */
function showHideLayer(name, action) {
  var layer = map.getLayersByName(name);

  layer[0].setVisibility(action);
}

/**
 * Add a point with the default icon in the map.
 *
 * @param string layerName The name of layer to put the point.
 * @param string pointName The name to show in the point.
 * @param float lon The coord of latitude for point.
 * @param float lat The coord of longitude for point.
 * @param string id The id of point.
 * @param string type_string The type of point, it's use for ajax request.
 * @param integer statusAgent The status of point.
 * @param integer idParent Id Parent of agent.
 *
 * @return Object The point.
 */
function js_addAgentPoint(
  layerName,
  pointName,
  lon,
  lat,
  id,
  type_string,
  statusAgent,
  idParent
) {
  var point = new OpenLayers.Geometry.Point(lon, lat).transform(
    map.displayProjection,
    map.getProjectionObject()
  );

  var layer = map.getLayersByName(layerName);
  layer = layer[0];

  feature = new OpenLayers.Feature.Vector(point, {
    id_parent: idParent,
    status: statusAgent,
    nombre: pointName,
    id: id,
    type: type_string,
    long_lat: new OpenLayers.LonLat(lon, lat).transform(
      map.displayProjection,
      map.getProjectionObject()
    )
  });

  if (isHideFeatureByStatus(statusAgent)) {
    feature.style.display = "none";
  }

  layer.addFeatures(feature);

  return feature;
}

/**
 * Add a point with the default icon in the map.
 *
 * @param string layerName The name of layer to put the point.
 * @param string pointName The name to show in the point.
 * @param float lon The coord of latitude for point.
 * @param float lat The coord of longitude for point.
 * @param string id The id of point.
 * @param string type_string The type of point, it's use for ajax request.
 * @param integer statusAgent The status of point.
 *
 * @return Object The point.
 */
function js_addPoint(
  layerName,
  pointName,
  lon,
  lat,
  id,
  type_string,
  statusAgent
) {
  var point = new OpenLayers.Geometry.Point(lon, lat).transform(
    map.displayProjection,
    map.getProjectionObject()
  );

  var layer = map.getLayersByName(layerName);
  layer = layer[0];

  feature = new OpenLayers.Feature.Vector(point, {
    status: statusAgent,
    nombre: pointName,
    id: id,
    type: type_string,
    long_lat: new OpenLayers.LonLat(lon, lat).transform(
      map.displayProjection,
      map.getProjectionObject()
    )
  });

  if (isHideFeatureByStatus(statusAgent)) {
    feature.style.display = "none";
  }

  layer.addFeatures(feature);

  return feature;
}

/**
 * Add a agent point and set the icon in the map.
 *
 * @param string layerName The name of layer to put the point.
 * @param string pointName The name to show in the point.
 * @param float lon The coord of latitude for point.
 * @param float lat The coord of longitude for point.
 * @param string icon Url of icon image.
 * @param integer width The width of icon.
 * @param integer height The height of icon.
 * @param string id The id of point.
 * @param string type_string The type of point, it's use for ajax request.
 * @param integer statusAgent The status of point.
 * @param integer idParent Id Parent of agent.
 *
 * @return Object The point.
 */
function js_addAgentPointExtent(
  layerName,
  pointName,
  lon,
  lat,
  icon,
  width,
  height,
  id,
  type_string,
  statusAgent,
  idParent
) {
  var point = new OpenLayers.Geometry.Point(lon, lat).transform(
    map.displayProjection,
    map.getProjectionObject()
  );

  var layer = map.getLayersByName(layerName);
  layer = layer[0];

  if (typeof statusAgent == "string") statusA = parseInt(statusAgent);
  else statusA = statusAgent;

  feature = new OpenLayers.Feature.Vector(
    point,
    {
      id_parent: idParent.toString(),
      status: statusA,
      id: id.toString(),
      type: type_string,
      long_lat: new OpenLayers.LonLat(lon, lat).transform(
        map.displayProjection,
        map.getProjectionObject()
      )
    },
    {
      fontWeight: "bolder",
      fontColor: "#00014F",
      labelYOffset: -height,
      graphicHeight: width,
      graphicWidth: height,
      externalGraphic: icon,
      label: pointName
    }
  );

  if (isHideFeatureByStatus(statusAgent)) {
    feature.style.display = "none";
  }

  layer.addFeatures(feature);

  return feature;
}

/**
 * Add a point and set the icon in the map.
 *
 * @param string layerName The name of layer to put the point.
 * @param string pointName The name to show in the point.
 * @param float lon The coord of latitude for point.
 * @param float lat The coord of longitude for point.
 * @param string icon Url of icon image.
 * @param integer width The width of icon.
 * @param integer height The height of icon.
 * @param string id The id of point.
 * @param string type_string The type of point, it's use for ajax request.
 * @param integer statusAgent The status of point.
 *
 * @return Object The point.
 */
function js_addPointExtent(
  layerName,
  pointName,
  lon,
  lat,
  icon,
  width,
  height,
  id,
  type_string,
  statusAgent
) {
  var point = new OpenLayers.Geometry.Point(lon, lat).transform(
    map.displayProjection,
    map.getProjectionObject()
  );

  var layer = map.getLayersByName(layerName);
  layer = layer[0];

  if (typeof statusAgent == "string") statusA = parseInt(statusAgent);
  else statusA = statusAgent;

  feature = new OpenLayers.Feature.Vector(
    point,
    {
      status: statusA,
      id: id,
      type: type_string,
      long_lat: new OpenLayers.LonLat(lon, lat).transform(
        map.displayProjection,
        map.getProjectionObject()
      )
    },
    {
      fontWeight: "bolder",
      fontColor: "#00014F",
      labelYOffset: -height,
      graphicHeight: width,
      graphicWidth: height,
      externalGraphic: icon,
      label: pointName
    }
  );

  if (isHideFeatureByStatus(statusAgent)) {
    feature.style.display = "none";
  }

  layer.addFeatures(feature);

  return feature;
}

/**
 *
 * @param string layerName The name of layer to put the point path.
 * @param float lon The coord of latitude for point path.
 * @param float lat The coord of longitude for point path.
 * @param string color The color of point path in rrggbb format.
 * @param boolean manual The type of point path, if it's manual, the point is same a donut.
 * @param string id The id of point path.
 *
 * @return None
 */
function js_addPointPath(layerName, lon, lat, color, manual, id) {
  var point = new OpenLayers.Geometry.Point(lon, lat).transform(
    map.displayProjection,
    map.getProjectionObject()
  );

  var layer = map.getLayersByName(layerName);
  layer = layer[0];

  var pointRadiusNormal = 4;
  var strokeWidth = 2;
  var pointRadiusManual = pointRadiusNormal - strokeWidth / 2;

  if (manual) {
    point = new OpenLayers.Feature.Vector(
      point,
      {
        estado: "ok",
        id: id,
        type: "point_path_info",
        long_lat: new OpenLayers.LonLat(lon, lat).transform(
          map.displayProjection,
          map.getProjectionObject()
        )
      },
      {
        fillColor: "#ffffff",
        pointRadius: pointRadiusManual,
        stroke: 1,
        strokeColor: color,
        strokeWidth: strokeWidth,
        cursor: "pointer"
      }
    );
  } else {
    point = new OpenLayers.Feature.Vector(
      point,
      {
        estado: "ok",
        id: id,
        type: "point_path_info",
        long_lat: new OpenLayers.LonLat(lon, lat).transform(
          map.displayProjection,
          map.getProjectionObject()
        )
      },
      { fillColor: color, pointRadius: pointRadiusNormal, cursor: "pointer" }
    );
  }

  layer.addFeatures(point);
}

/**
 * Draw the lineString.
 *
 * @param string layerName The name of layer to put the point path.
 * @param Array points The array have content the points, but the point as lonlat openlayers object without transformation.
 * @param string color The color of point path in rrggbb format.
 *
 * @return None
 */
function js_addLineString(layerName, points, color) {
  var mapPoints = new Array(points.length);
  var layer = map.getLayersByName(layerName);

  layer = layer[0];

  for (var i = 0; i < points.length; i++) {
    mapPoints[i] = points[i].transform(
      map.displayProjection,
      map.getProjectionObject()
    );
  }

  var lineString = new OpenLayers.Feature.Vector(
    new OpenLayers.Geometry.LineString(mapPoints),
    null,
    { strokeWidth: 2, fillOpacity: 0.2, fillColor: color, strokeColor: color }
  );

  layer.addFeatures(lineString);
}

/**
 * Return feature object for a id agent passed.
 *
 * @param interger id The agent id.
 * @return mixed Return the feature object, if it didn't found then return null.
 */
function searchPointAgentById(id) {
  for (layerIndex = 0; layerIndex < map.getNumLayers(); layerIndex++) {
    layer = map.layers[layerIndex];

    if (layer.features != undefined) {
      for (
        featureIndex = 0;
        featureIndex < layer.features.length;
        featureIndex++
      ) {
        feature = layer.features[featureIndex];
        if (feature.data.id == id) {
          return feature;
        }
      }
    }
  }

  return null;
}
