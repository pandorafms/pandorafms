(function($) {
  var _options;
  var _self;

  var _boundingBoxes;
  var _zoneCentroids = {};
  var _selectedRegionKey;
  var _selectedPolygon;
  var _mapper;
  var _mapZones = {};
  var _transitions = {};

  var _currentHoverRegion;
  var _hoverRegions = {};
  var _hoverPolygons = [];

  var _loader;
  var _loaderGif;
  var _maskPng;
  var _needsLoader = 0;

  var OpenLayersMapper = function(
    el,
    mouseClickHandler,
    mouseMoveHandler,
    mapOptions
  ) {
    var infoWindow;

    // Create the maps instance
    var map = new OpenLayers.Map(
      OpenLayers.Util.extend(
        {
          div: el,
          projection: "EPSG:900913",
          displayProjection: "EPSG:4326",
          numZoomLevels: 18,
          controls: [
            new OpenLayers.Control.DragPan(),
            new OpenLayers.Control.Navigation({
              mouseWheelOptions: {
                cumulative: false,
                maxDelta: 6,
                interval: 50
              },
              zoomWheelEnabled: false
            }),
            new OpenLayers.Control.Zoom(),
            new OpenLayers.Control.ZoomBox()
          ]
        },
        mapOptions
      )
    );

    var newLayer = new OpenLayers.Layer.OSM(
      "OSM Layer",
      "http://a.tile.openstreetmap.org/${z}/${x}/${y}.png"
    );
    map.addLayer(newLayer);

    var vectors = new OpenLayers.Layer.Vector("vector");

    map.addLayer(vectors);

    $("#timezone").on("change", function() {
      var optionText = $("#timezone option:selected").val();
      selectZone(optionText);
    });

    OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
      defaultHandlerOptions: {
        single: true,
        double: false,
        pixelTolerance: 0,
        stopSingle: false,
        stopDouble: false
      },

      initialize: function() {
        this.handlerOptions = OpenLayers.Util.extend(
          {},
          this.defaultHandlerOptions
        );
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
        this.handler = new OpenLayers.Handler.Click(
          this,
          {
            click: this.trigger
          },
          this.handlerOptions
        );
      },

      trigger: function(e) {
        var position = map.getLonLatFromViewPortPx(e.xy);
        position.transform(
          map.getProjectionObject(),
          new OpenLayers.Projection("EPSG:4326")
        );
        mapClickHandler({
          latLng: {
            lat: function() {
              return position.lat;
            },
            lng: function() {
              return position.lon;
            }
          }
        });
      }
    });
    var click = new OpenLayers.Control.Click();
    map.addControl(click);
    click.activate();

    if (mouseMoveHandler) {
      map.events.register("mousemove", map, function(e) {
        var position = map.getLonLatFromViewPortPx(e.xy);
        position.transform(
          map.getProjectionObject(),
          new OpenLayers.Projection("EPSG:4326")
        );
        mouseMoveHandler({
          latLng: {
            lat: function() {
              return position.lat;
            },
            lng: function() {
              return position.lon;
            }
          }
        });
      });
    }

    var onPolygonSelect = function(feature) {
      if (feature.clickHandler) {
        var position = map.getLonLatFromPixel(
          new OpenLayers.Pixel(
            polygonSelect.handlers.feature.evt.layerX,
            polygonSelect.handlers.feature.evt.layerY
          )
        );
        position.transform(
          map.getProjectionObject(),
          new OpenLayers.Projection("EPSG:4326")
        );
        feature.clickHandler({
          latLng: {
            lat: function() {
              return position.lat;
            },
            lng: function() {
              return position.lon;
            }
          }
        });
      }
    };

    var onPolygonHighlight = function(e) {
      if (e.feature.hoverHandler) {
        e.feature.hoverHandler();
      }
    };

    var polygonHover = new OpenLayers.Control.SelectFeature(vectors, {
      hover: true,
      highlightOnly: true,
      renderIntent: "temporary",
      eventListeners: {
        beforefeaturehighlighted: onPolygonHighlight
      }
    });

    var polygonSelect = new OpenLayers.Control.SelectFeature(vectors, {
      onSelect: onPolygonSelect
    });

    map.addControl(polygonHover);
    map.addControl(polygonSelect);
    polygonHover.activate();
    polygonSelect.activate();

    map.setCenter(new OpenLayers.LonLat(0, 0), mapOptions.zoom);

    var addPolygon = function(
      coords,
      stroke,
      fill,
      clickHandler,
      mouseMoveHandler
    ) {
      for (var i = 0; i < coords.length; i++) {
        coords[i].transform(
          new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
          map.getProjectionObject() // to Spherical Mercator Projection
        );
      }

      var style = {
        strokeColor: stroke.color,
        strokeOpacity: stroke.opacity,
        strokeWidth: stroke.width,
        fillColor: fill.color,
        fillOpacity: fill.opacity
      };
      var linearRing = new OpenLayers.Geometry.LinearRing(coords);
      var feature = new OpenLayers.Feature.Vector(
        new OpenLayers.Geometry.Polygon(linearRing),
        null,
        style
      );

      vectors.addFeatures([feature]);

      // NOTE: Stuff our click/mousemove handlers on the object for use in onPolygonSelect
      feature.clickHandler = clickHandler;
      feature.hoverHandler = mouseMoveHandler;

      return feature;
    };

    var createPoint = function(lat, lng) {
      return new OpenLayers.Geometry.Point(lng, lat);
    };

    var hideInfoWindow = function() {
      if (infoWindow) {
        map.removePopup(infoWindow);
        infoWindow.destroy();
        infoWindow = null;
      }
    };

    var removePolygon = function(mapPolygon) {
      vectors.removeFeatures([mapPolygon]);
    };

    var showInfoWindow = function(pos, content, callback) {
      if (infoWindow) {
        hideInfoWindow(infoWindow);
      }

      pos = new OpenLayers.LonLat(pos.x, pos.y);
      pos.transform(
        new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
        map.getProjectionObject() // to Spherical Mercator Projection
      );

      infoWindow = new OpenLayers.Popup.FramedCloud(
        "timezone_picker_infowindow",
        pos,
        new OpenLayers.Size(100, 100),
        content,
        null,
        true,
        null
      );
      map.addPopup(infoWindow);

      // HACK: callback for popup using a set timeout
      if (callback) {
        setTimeout(function() {
          callback.apply($("#timezone_picker_infowindow"));
        }, 100);
      }
    };

    return {
      addPolygon: addPolygon,
      createPoint: createPoint,
      hideInfoWindow: hideInfoWindow,
      removePolygon: removePolygon,
      showInfoWindow: showInfoWindow
    };
  };

  // Forward declarations to satisfy jshint
  var hideLoader,
    hitTestAndConvert,
    selectPolygonZone,
    showInfoWindow,
    slugifyName;

  var clearHover = function() {
    $.each(_hoverPolygons, function(i, p) {
      _mapper.removePolygon(p);
    });

    _hoverPolygons = [];
  };

  var clearZones = function() {
    $.each(_mapZones, function(i, zone) {
      $.each(zone, function(j, polygon) {
        _mapper.removePolygon(polygon);
      });
    });

    _mapZones = {};
  };

  var drawZone = function(name, lat, lng, callback) {
    if (_mapZones[name]) {
      return;
    }

    $.get(_options.jsonRootUrl + "polygons/" + name + ".json", function(data) {
      _needsLoader--;
      if (_needsLoader === 0 && _loader) {
        hideLoader();
      }

      if (callback) {
        callback();
      }

      data = typeof data === "string" ? JSON.parse(data) : data;

      _mapZones[name] = [];
      $.extend(_transitions, data.transitions);

      var result = hitTestAndConvert(data.polygons, lat, lng);

      if (result.inZone) {
        _selectedRegionKey = name;

        $.each(result.allPolygons, function(i, polygonInfo) {
          var mapPolygon = _mapper.addPolygon(
            polygonInfo.coords,
            {
              color: "#fff",
              opacity: 0.7,
              width: 1
            },
            {
              color: "#82b92e",
              opacity: 0.9
            },
            function() {
              selectPolygonZone(polygonInfo.polygon);
            },
            clearHover
          );

          _mapZones[name].push(mapPolygon);
        });

        selectPolygonZone(result.selectedPolygon);
      }
    }).fail(function() {
      console.warn(arguments);
    });
  };

  var getCurrentTransition = function(transitions) {
    if (transitions.length === 1) {
      return transitions[0];
    }

    var now = _options.date.getTime() / 1000;
    var selected = null;
    $.each(transitions, function(i, transition) {
      if (
        transition[0] < now &&
        i < transitions.length - 1 &&
        transitions[i + 1][0] > now
      ) {
        selected = transition;
      }
    });

    // If we couldn't find a matching transition, just use the first one
    // NOTE: This will sometimes be wrong for events in the past
    if (!selected) {
      selected = transitions[0];
    }

    return selected;
  };

  var hideInfoWindow = function() {
    _mapper.hideInfoWindow();
  };

  hideLoader = function() {
    _loader.remove();
    _loader = null;
  };

  hitTestAndConvert = function(polygons, lat, lng) {
    var allPolygons = [];
    var inZone = false;
    var selectedPolygon;
    $.each(polygons, function(i, polygon) {
      // Ray casting counter for hit testing.
      var rayTest = 0;
      var lastPoint = polygon.points.slice(-2);

      var coords = [];
      var j = 0;
      for (j = 0; j < polygon.points.length; j += 2) {
        var point = polygon.points.slice(j, j + 2);

        coords.push(_mapper.createPoint(point[0], point[1]));

        // Ray casting test
        if (
          (lastPoint[0] <= lat && point[0] >= lat) ||
          (lastPoint[0] > lat && point[0] < lat)
        ) {
          var slope = (point[1] - lastPoint[1]) / (point[0] - lastPoint[0]);
          var testPoint = slope * (lat - lastPoint[0]) + lastPoint[1];
          if (testPoint < lng) {
            rayTest++;
          }
        }

        lastPoint = point;
      }

      allPolygons.push({
        polygon: polygon,
        coords: coords
      });

      // If the count is odd, we are in the polygon
      var odd = rayTest % 2 === 1;
      inZone = inZone || odd;
      if (odd) {
        selectedPolygon = polygon;
      }
    });

    return {
      allPolygons: allPolygons,
      inZone: inZone,
      selectedPolygon: selectedPolygon
    };
  };

  var mapClickHandler = function(e) {
    if (_needsLoader > 0) {
      return;
    }

    hideInfoWindow();

    var lat = e.latLng.lat();
    var lng = e.latLng.lng();

    var candidates = [];
    $.each(_boundingBoxes, function(i, v) {
      var bb = v.boundingBox;
      if (lat > bb.ymin && lat < bb.ymax && lng > bb.xmin && lng < bb.xmax) {
        candidates.push(slugifyName(v.name));
      }
    });

    _needsLoader = candidates.length;
    setTimeout(function() {
      if (_needsLoader > 0) {
        showLoader();
      }
    }, 500);

    clearZones();
    $.each(candidates, function(i, v) {
      drawZone(v, lat, lng, function() {
        $.each(_hoverPolygons, function(i, p) {
          _mapper.removePolygon(p);
        });
        _hoverPolygons = [];
        _currentHoverRegion = null;
      });
    });
  };

  var mouseMoveHandler = function(e) {
    var lat = e.latLng.lat();
    var lng = e.latLng.lng();

    $.each(_boundingBoxes, function(i, v) {
      var bb = v.boundingBox;
      if (lat > bb.ymin && lat < bb.ymax && lng > bb.xmin && lng < bb.xmax) {
        var hoverRegion = _hoverRegions[v.name];
        if (!hoverRegion) {
          return;
        }

        var result = hitTestAndConvert(hoverRegion.hoverRegion, lat, lng);
        var slugName = slugifyName(v.name);
        if (
          result.inZone &&
          slugName !== _currentHoverRegion &&
          slugName !== _selectedRegionKey
        ) {
          clearHover();
          _currentHoverRegion = slugName;

          $.each(result.allPolygons, function(i, polygonInfo) {
            var mapPolygon = _mapper.addPolygon(
              polygonInfo.coords,
              {
                color: "#848484",
                opacity: 0.7,
                width: 1
              },
              {
                color: "#cbcbcb",
                opacity: 0.5
              },
              mapClickHandler,
              null
            );

            _hoverPolygons.push(mapPolygon);
          });

          if (_options.onHover) {
            var transition = getCurrentTransition(hoverRegion.transitions);
            _options.onHover(transition[1], transition[2]);
          }
        }
      }
    });
  };

  selectPolygonZone = function(polygon) {
    _selectedPolygon = polygon;
    var transition = getCurrentTransition(_transitions[polygon.name]);
    var olsonName = polygon.name; //when you click in the map
    var utcOffset = transition[1];
    var tzName = transition[2];

    if (_options.onSelected) {
      _options.onSelected(olsonName);
    } else {
      var pad = function(d) {
        if (d < 10) {
          return "0" + d;
        }
        return d.toString();
      };

      var now = new Date();
      var adjusted = new Date();
      adjusted.setTime(
        adjusted.getTime() +
          (adjusted.getTimezoneOffset() + utcOffset) * 60 * 1000
      );

      if (olsonName == "America/Montreal") {
        olsonName = "America/Toronto";
      } else if (olsonName == "Asia/Chongqing") {
        olsonName = "Asia/Shanghai";
      }

      $("#timezone").val(olsonName); //Select this value in the dropdown
    }
  };

  // Function to draw the timezone in the map, when you select it in the dropdown
  var selectZone = function(olsonName) {
    var centroid = _zoneCentroids[olsonName];
    if (centroid) {
      mapClickHandler({
        latLng: {
          lat: function() {
            return centroid[1];
          },
          lng: function() {
            return centroid[0];
          }
        }
      });
    }
  };

  showInfoWindow = function(content, callback) {
    // Hack to get the centroid of the largest polygon - we just check
    // which has the most edges
    var centroid;
    var maxPoints = 0;
    if (_selectedPolygon.points.length > maxPoints) {
      centroid = _selectedPolygon.centroid;
      maxPoints = _selectedPolygon.points.length;
    }

    hideInfoWindow();

    _mapper.showInfoWindow(
      _mapper.createPoint(centroid[1], centroid[0]),
      content,
      callback
    );
  };

  var showLoader = function() {
    _loader = $(
      '<div style="background: url(' +
        _maskPng +
        ');z-index:10000;">' +
        '<img style="margin-top:-8px;margin-left:-8px" ' +
        'src="' +
        _loaderGif +
        '" /></div>'
    );
    _loader.height(_self.height()).width(_self.width());
    _self.append(_loader);
  };

  slugifyName = function(name) {
    return name.toLowerCase().replace(/[^a-z0-9]/g, "-");
  };

  var methods = {
    init: function(options) {
      _self = this;

      // Populate the options and set defaults
      _options = options || {};
      _options.initialZoom = _options.initialZoom || 2;
      _options.initialLat = _options.initialLat || 0;
      _options.initialLng = _options.initialLng || 0;
      _options.strokeColor = _options.strokeColor || "#fff";
      _options.strokeWeight = _options.strokeWeight || 2;
      _options.strokeOpacity = _options.strokeOpacity || 0.7;
      _options.fillColor = _options.fillColor || "#82b92e";
      _options.fillOpacity = _options.fillOpacity || 0.5;
      _options.jsonRootUrl =
        _options.jsonRootUrl || "include/javascript/tz_json/";
      _options.date = _options.date || new Date();

      _options.mapOptions = $.extend(
        {
          zoom: _options.initialZoom,
          centerLat: _options.initialLat,
          centerLng: _options.initialLng
        },
        _options.mapOptions
      );

      if (typeof _options.hoverRegions === "undefined") {
        _options.hoverRegions = true;
      }

      if (_options.useOpenLayers) {
        _mapper = new OpenLayersMapper(
          _self.get(0),
          mapClickHandler,
          _options.hoverRegions ? mouseMoveHandler : null,
          _options.mapOptions
        );
      }

      // Load the necessary data files
      var loadCount = _options.hoverRegions ? 2 : 1;
      var checkLoading = function() {
        loadCount--;
        if (loadCount === 0) {
          hideLoader();

          if (_options.onReady) {
            _options.onReady();
          }
        }
      };

      showLoader();
      $.get(_options.jsonRootUrl + "bounding_boxes.json", function(data) {
        _boundingBoxes = typeof data === "string" ? JSON.parse(data) : data;
        $.each(_boundingBoxes, function(i, bb) {
          $.extend(_zoneCentroids, bb.zoneCentroids);
        });
        checkLoading();
      });

      if (_options.hoverRegions) {
        $.get(_options.jsonRootUrl + "hover_regions.json", function(data) {
          var hoverData = typeof data === "string" ? JSON.parse(data) : data;
          $.each(hoverData, function(i, v) {
            _hoverRegions[v.name] = v;
          });
          checkLoading();
        });
      }
    },
    setDate: function(date) {
      hideInfoWindow();
      _options.date = date;
    },
    hideInfoWindow: hideInfoWindow,
    showInfoWindow: function(content, callback) {
      // showInfoWindow(content, callback);
    },
    selectZone: function(olsonName) {
      var centroid = _zoneCentroids[olsonName];
      if (centroid) {
        mapClickHandler({
          latLng: {
            lat: function() {
              return centroid[1];
            },
            lng: function() {
              return centroid[0];
            }
          }
        });
      }
    }
  };

  $.fn.timezonePicker = function(method) {
    if (methods[method]) {
      return methods[method].apply(
        this,
        Array.prototype.slice.call(arguments, 1)
      );
    } else if (typeof method === "object" || !method) {
      return methods.init.apply(this, arguments);
    } else {
      $.error("Method " + method + " does not exist on jQuery.timezonePicker.");
    }
  };

  _loaderGif =
    "data:image/gif;base64,R0lGODlhEAAQAPIAAKqqqv///729vejo6P///93d3dPT083NzSH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==";
  _maskPng =
    "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9sJDgA6CHKQBUUAAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAAADUlEQVQI12NgYGDwAQAAUQBNbrgEdAAAAABJRU5ErkJggg==";
})(jQuery);
