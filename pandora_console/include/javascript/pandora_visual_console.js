// TODO: Add Artica ST header.
/* globals jQuery, VisualConsole, AsyncTaskManager */

/*
 * *********************
 * * New VC functions. *
 * *********************
 */

/**
 * Generate a Visual Console client.
 * @param {HTMLElement} container Node which will be used to contain the VC.
 * @param {object} props VC container properties.
 * @param {object[]} items List of item definitions.
 * @param {string | null} baseUrl Base URL to perform API requests.
 * @param {number | null} updateInterval Time in milliseconds between VC updates.
 * @param {function | null} onUpdate Callback which will be execuded when the Visual Console.
 * is updated. It will receive two arguments with the old and the new Visual Console's
 * data structure.
 * @return {VisualConsole | null} The Visual Console instance or a null value.
 */
// eslint-disable-next-line no-unused-vars
function createVisualConsole(
  container,
  props,
  items,
  baseUrl,
  updateInterval,
  onUpdate
) {
  if (container == null || props == null || items == null) return null;
  if (baseUrl == null) baseUrl = "";

  var visualConsole = null;
  var asyncTaskManager = new AsyncTaskManager();

  function updateVisualConsole(visualConsoleId, updateInterval, tts) {
    if (tts == null) tts = 0; // Time to start.

    asyncTaskManager.add(
      "visual-console",
      function(done) {
        var abortable = loadVisualConsoleData(
          baseUrl,
          visualConsoleId,
          function(error, data) {
            if (error) {
              console.log(
                "[ERROR]",
                "[VISUAL-CONSOLE-CLIENT]",
                "[API]",
                error.message
              );
              done();
              return;
            }

            // Replace Visual Console.
            if (data != null && data.props != null && data.items != null) {
              try {
                var props =
                  typeof data.props === "string"
                    ? JSON.parse(data.props)
                    : data.props;
                var items =
                  typeof data.items === "string"
                    ? JSON.parse(data.items)
                    : data.items;

                // Add the datetime when the item was received.
                var receivedAt = new Date();
                items.map(function(item) {
                  item["receivedAt"] = receivedAt;
                  return item;
                });

                var prevProps = visualConsole.props;
                // Update the data structure.
                visualConsole.props = props;
                // Update the items.
                visualConsole.updateElements(items);
                // Emit the VC update event.
                if (onUpdate) onUpdate(prevProps, visualConsole.props);
              } catch (ignored) {} // eslint-disable-line no-empty

              done();
            }
          }
        );

        return {
          cancel: function() {
            abortable.abort();
          }
        };
      },
      updateInterval
    );

    asyncTaskManager.add("visual-console-start", function(done) {
      var ref = setTimeout(function() {
        asyncTaskManager.init("visual-console");
        done();
      }, tts);

      return {
        cancel: function() {
          clearTimeout(ref);
        }
      };
    });

    if (tts > 0) {
      // Wait to start the fetch interval.
      asyncTaskManager.init("visual-console-start");
    } else {
      // Start the fetch interval immediately.
      asyncTaskManager.init("visual-console");
    }
  }

  // Initialize the Visual Console.
  try {
    visualConsole = new VisualConsole(container, props, items);
    // VC Item clicked.
    visualConsole.onItemClick(function(e) {
      // Override the link to another VC if it isn't on remote console.
      if (
        e.data &&
        e.data.linkedLayoutId != null &&
        e.data.linkedLayoutId > 0 &&
        e.data.link != null &&
        e.data.link.length > 0 &&
        (e.data.linkedLayoutAgentId == null || e.data.linkedLayoutAgentId === 0)
      ) {
        // Stop the current link behavior.
        e.nativeEvent.preventDefault();
        // Fetch and update the old VC with the new.
        updateVisualConsole(e.data.linkedLayoutId, updateInterval);
      }
    });
    // VC Item moved.
    visualConsole.onItemMoved(function(e) {
      var id = e.item.props.id;
      var data = {
        x: e.newPosition.x,
        y: e.newPosition.y,
        type: e.item.props.type
      };
      var taskId = "visual-console-item-move-" + id;

      // Persist the new position.
      asyncTaskManager
        .add(taskId, function(done) {
          var abortable = updateVisualConsoleItem(
            baseUrl,
            visualConsole.props.id,
            id,
            data,
            function(error, data) {
              // if (!error && !data) return;
              if (error || !data) {
                console.log(
                  "[ERROR]",
                  "[VISUAL-CONSOLE-CLIENT]",
                  "[API]",
                  error ? error.message : "Invalid response"
                );

                // Move the element to its initial position.
                e.item.move(e.prevPosition.x, e.prevPosition.y);
              }

              done();
            }
          );

          return {
            cancel: function() {
              abortable.abort();
            }
          };
        })
        .init();
    });
    // VC Item resized.
    visualConsole.onItemResized(function(e) {
      var id = e.item.props.id;
      var data = {
        width: e.newSize.width,
        height: e.newSize.height,
        type: e.item.props.type
      };
      var taskId = "visual-console-item-resize-" + id;

      // Persist the new size.
      asyncTaskManager
        .add(taskId, function(done) {
          var abortable = updateVisualConsoleItem(
            baseUrl,
            visualConsole.props.id,
            id,
            data,
            function(error, data) {
              // if (!error && !data) return;
              if (error || !data) {
                console.log(
                  "[ERROR]",
                  "[VISUAL-CONSOLE-CLIENT]",
                  "[API]",
                  error ? error.message : "Invalid response"
                );

                // Resize the element to its initial Size.
                e.item.resize(e.prevSize.width, e.prevSize.height);
              }

              done();
            }
          );

          return {
            cancel: function() {
              abortable.abort();
            }
          };
        })
        .init();
    });

    if (updateInterval != null && updateInterval > 0) {
      // Start an interval to update the Visual Console.
      updateVisualConsole(props.id, updateInterval, updateInterval);
    }
  } catch (error) {
    console.log("[ERROR]", "[VISUAL-CONSOLE-CLIENT]", error.message);
  }

  return {
    visualConsole: visualConsole,
    changeUpdateInterval: function(updateInterval) {
      if (updateInterval != null && updateInterval > 0) {
        updateVisualConsole(
          visualConsole.props.id,
          updateInterval,
          updateInterval
        );
      } else {
        // Update interval disabled. Cancel possible pending tasks.
        asyncTaskManager.cancel("visual-console");
        asyncTaskManager.cancel("visual-console-start");
      }
    }
  };
}

/**
 * Fetch a Visual Console's structure and its items.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {number} vcId Identifier of the Visual Console.
 * @param {function} callback Function to be executed on request success or fail.
 * On success, the function will receive an object with the next properties:
 * - `props`: object with the Visual Console's data structure.
 * - `items`: array of data structures of the Visual Console's items.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function loadVisualConsoleData(baseUrl, vcId, callback) {
  // var apiPath = baseUrl + "/include/rest-api";
  var apiPath = baseUrl + "/ajax.php";
  var vcJqXHR = null;
  var itemsJqXHR = null;

  // Initialize the final result.
  var result = {
    props: null,
    items: null
  };

  // Cancel the ajax requests.
  var abort = function(textStatus) {
    if (textStatus == null) textStatus = "abort";

    // -- XMLHttpRequest.readyState --
    // Value	State	  Description
    // 0	    UNSENT	Client has been created. open() not called yet.
    // 4	    DONE   	The operation is complete.

    if (vcJqXHR.readyState !== 0 && vcJqXHR.readyState !== 4)
      vcJqXHR.abort(textStatus);
    if (itemsJqXHR.readyState !== 0 && itemsJqXHR.readyState !== 4)
      itemsJqXHR.abort(textStatus);
  };

  // Check if the required data is complete.
  var checkResult = function() {
    return result.props !== null && result.items !== null;
  };

  // Failed request handler.
  var handleFail = function(jqXHR, textStatus, errorThrown) {
    abort();
    // Manually aborted or not.
    if (textStatus === "abort") {
      callback();
    } else {
      var error = new Error(errorThrown);
      error.request = jqXHR;
      callback(error);
    }
  };

  // Curried function which handle success.
  var handleSuccess = function(key) {
    // Actual request handler.
    return function(data) {
      result[key] = data;
      if (checkResult()) callback(null, result);
    };
  };

  // Visual Console container request.
  vcJqXHR = jQuery
    // .get(apiPath + "/visual-consoles/" + vcId, null, "json")
    .get(
      apiPath,
      {
        page: "include/rest-api/index",
        getVisualConsole: 1,
        visualConsoleId: vcId
      },
      "json"
    )
    .done(handleSuccess("props"))
    .fail(handleFail);
  // Visual Console items request.
  itemsJqXHR = jQuery
    // .get(apiPath + "/visual-consoles/" + vcId + "/items", null, "json")
    .get(
      apiPath,
      {
        page: "include/rest-api/index",
        getVisualConsoleItems: 1,
        visualConsoleId: vcId
      },
      "json"
    )
    .done(handleSuccess("items"))
    .fail(handleFail);

  // Abortable.
  return {
    abort: abort
  };
}

/**
 * Fetch a Visual Console's structure and its items.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {number} vcId Identifier of the Visual Console.
 * @param {number} vcItemId Identifier of the Visual Console's item.
 * @param {Object} data Data we want to save.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function updateVisualConsoleItem(baseUrl, vcId, vcItemId, data, callback) {
  // var apiPath = baseUrl + "/include/rest-api";
  var apiPath = baseUrl + "/ajax.php";
  var jqXHR = null;

  // Cancel the ajax requests.
  var abort = function(textStatus) {
    if (textStatus == null) textStatus = "abort";

    // -- XMLHttpRequest.readyState --
    // Value	State	  Description
    // 0	    UNSENT	Client has been created. open() not called yet.
    // 4	    DONE   	The operation is complete.

    if (jqXHR.readyState !== 0 && jqXHR.readyState !== 4)
      jqXHR.abort(textStatus);
  };

  // Failed request handler.
  var handleFail = function(jqXHR, textStatus, errorThrown) {
    abort();
    // Manually aborted or not.
    if (textStatus === "abort") {
      callback();
    } else {
      var error = new Error(errorThrown);
      error.request = jqXHR;
      callback(error);
    }
  };

  // Function which handle success case.
  var handleSuccess = function(data) {
    callback(null, data);
  };

  // Visual Console container request.
  jqXHR = jQuery
    // .get(apiPath + "/visual-consoles/" + vcId, null, "json")
    .get(
      apiPath,
      {
        page: "include/rest-api/index",
        updateVisualConsoleItem: 1,
        visualConsoleId: vcId,
        visualConsoleItemId: vcItemId,
        data: data
      },
      "json"
    )
    .done(handleSuccess)
    .fail(handleFail);

  // Abortable.
  return {
    abort: abort
  };
}

// TODO: Delete the functions below when you can.
/**************************************
 These functions require jQuery library
 **************************************/

/** 
 * Draw a line between two elements in a div
 * 
 * @param line Line to draw. JavaScript object with the following properties:
	- x1 X coordinate of the first point. If not set, it will get the coord from node_begin position
	- y1 Y coordinate of the first point. If not set, it will get the coord from node_begin position
	- x2 X coordinate of the second point. If not set, it will get the coord from node_end position
	- y2 Y coordinate of the second point. If not set, it will get the coord from node_end position
	- color Color of the line to draw
	- node_begin Id of the beginning node
	- node_end Id of the finishing node
 * @param id_div Div to draw the lines in
 * @param editor Boolean variable to set other css selector in editor (when true).
 */
function draw_line(line, id_div) {
  selector = "";

  //Check if the global var resize_map is defined
  if (typeof resize_map == "undefined") {
    resize_map = 0;
  }

  var lineThickness = 2;
  if (line["thickness"]) lineThickness = line["thickness"];

  div = document.getElementById(id_div);

  brush = new jsGraphics(div);
  brush.setStroke(lineThickness);
  brush.setColor(line["color"]);

  have_node_begin_img = $("#" + line["node_begin"] + " img").length;
  have_node_end_img = $("#" + line["node_end"] + " img").length;

  if (have_node_begin_img) {
    var img_pos_begin = $("#" + line["node_begin"] + " img").position();
    var img_margin_left_begin = $("#" + line["node_begin"] + " img").css(
      "margin-left"
    );
    var img_margin_left_begin_aux = img_margin_left_begin.split("px");
    img_margin_left_begin = parseFloat(img_margin_left_begin_aux[0]);

    var img_margin_top_begin = $("#" + line["node_begin"] + " img").css(
      "margin-top"
    );
    var img_margin_top_begin_aux = img_margin_top_begin.split("px");
    img_margin_top_begin = parseFloat(img_margin_top_begin_aux[0]);
  }
  if (have_node_end_img) {
    var img_pos_end = $("#" + line["node_end"] + " img").position();
    var img_margin_left_end = $("#" + line["node_end"] + " img").css(
      "margin-left"
    );
    var img_margin_left_end_aux = img_margin_left_end.split("px");
    img_margin_left_end = parseFloat(img_margin_left_end_aux[0]);

    var img_margin_top_end = $("#" + line["node_end"] + " img").css(
      "margin-top"
    );
    var img_margin_top_end_aux = img_margin_top_end.split("px");
    img_margin_top_end = parseFloat(img_margin_top_end_aux[0]);
  }

  if (line["x1"]) {
    x1 = line["x"];
  } else {
    if (have_node_begin_img) {
      width = $("#" + line["node_begin"] + " img").width();
      x1 =
        parseInt($("#" + line["node_begin"]).css(selector + "left")) +
        width / 2 +
        img_pos_begin.left +
        img_margin_left_begin;
    } else {
      width = $("#" + line["node_begin"]).width();
      x1 =
        parseInt($("#" + line["node_begin"]).css(selector + "left")) +
        width / 2;
    }
  }

  if (line["y1"]) {
    y1 = line["y1"];
  } else {
    if (have_node_begin_img) {
      height = parseInt($("#" + line["node_begin"] + " img").css("height"));
      y1 =
        parseInt($("#" + line["node_begin"]).css(selector + "top")) +
        height / 2 +
        img_pos_begin.top +
        img_margin_top_begin;
    } else {
      height = $("#" + line["node_begin"]).height();
      y1 =
        parseInt($("#" + line["node_begin"]).css(selector + "top")) +
        height / 2;
    }
  }

  if (line["x2"]) {
    x2 = line["x2"];
  } else {
    if (have_node_end_img) {
      width = $("#" + line["node_end"] + " img").width();
      x2 =
        parseInt($("#" + line["node_end"]).css(selector + "left")) +
        width / 2 +
        img_pos_end.left +
        img_margin_left_end;
    } else {
      width = $("#" + line["node_end"]).width();
      x2 =
        parseInt($("#" + line["node_end"]).css(selector + "left")) + width / 2;
    }
  }

  if (line["y2"]) {
    y2 = line["y2"];
  } else {
    if (have_node_end_img) {
      height = parseInt($("#" + line["node_end"] + " img").css("height"));
      y2 =
        parseInt($("#" + line["node_end"]).css(selector + "top")) +
        height / 2 +
        img_pos_end.top +
        img_margin_top_end;
    } else {
      height = $("#" + line["node_end"]).height();
      y2 =
        parseInt($("#" + line["node_end"]).css(selector + "top")) + height / 2;
    }
  }

  brush.drawLine(x1, y1, x2, y2);
  brush.paint();
}

/**
 * Draw all the lines in an array on a div
 *
 * @param lines Array with lines objects (see draw_line)
 * @param id_div Div to draw the lines in
 * @param editor Boolean variable to set other css selector in editor (when true).
 */
function draw_lines(lines, id_div, editor) {
  jQuery.each(lines, function(i, line) {
    draw_line(line, id_div, editor);
  });
}

/**
 * Delete all the lines on a div
 *
 * The lines has the class 'map-line', so all the elements with this
 * class are removed.
 *
 * @param id_div Div to delete the lines in
 */
function delete_lines(id_div) {
  $("#" + id_div + " .map-line").remove();
}

/**
 * Re-draw all the lines in an array on a div
 *
 * It deletes all the lines and create then again.
 *
 * @param lines Array with lines objects (see draw_line)
 * @param id_div Div to draw the lines in
 * @param editor Boolean variable to set other css selector in editor (when true).
 */
function refresh_lines(lines, id_div, editor) {
  delete_lines(id_div);
  draw_lines(lines, id_div, editor);
}

function draw_user_lines_read(divId) {
  divId = divId || "background";
  var obj_js_user_lines = new jsGraphics(divId);

  obj_js_user_lines.clear();

  // Draw the previous lines
  for (iterator = 0; iterator < user_lines.length; iterator++) {
    obj_js_user_lines.setStroke(parseInt(user_lines[iterator]["line_width"]));
    obj_js_user_lines.setColor(user_lines[iterator]["line_color"]);
    obj_js_user_lines.drawLine(
      parseInt(user_lines[iterator]["start_x"]),
      parseInt(user_lines[iterator]["start_y"]),
      parseInt(user_lines[iterator]["end_x"]),
      parseInt(user_lines[iterator]["end_y"])
    );
  }

  obj_js_user_lines.paint();
}

function center_labels() {
  jQuery.each($(".item"), function(i, item) {
    if (
      $(item).width() > $("img", item).width() &&
      $("img", item).width() != null
    ) {
      dif_width = $(item).width() - $("img", item).width();

      x = parseInt($(item).css("left"));

      x = x - dif_width / 2;

      $(item)
        .css("left", x + "px")
        .css("text-align", "center");
    }
  });
}
