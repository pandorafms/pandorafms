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
 * @param {string|null} id_user User id given for public access.
 * @param {string|null} hash Authorization hash given for public access.
 *
 * @return {VisualConsole | null} The Visual Console instance or a null value.
 */
// eslint-disable-next-line no-unused-vars
function createVisualConsole(
  container,
  props,
  items,
  baseUrl,
  updateInterval,
  onUpdate,
  beforeUpdate,
  size,
  id_user,
  hash,
  mode = ""
) {
  if (container == null || props == null || items == null) return null;
  if (baseUrl == null) baseUrl = "";

  var visualConsole = null;
  var asyncTaskManager = new AsyncTaskManager();

  function updateVisualConsole(
    visualConsoleId,
    updateInterval,
    tts,
    dimensions
  ) {
    if (tts == null) tts = 0; // Time to start.

    if (dimensions != undefined && dimensions != null && dimensions != "") {
      size = dimensions;
    }

    asyncTaskManager.add(
      "visual-console",
      function(done) {
        var abortable = loadVisualConsoleData(
          baseUrl,
          visualConsoleId,
          size,
          id_user,
          hash,
          mode,
          function(error, data) {
            if (error) {
              //Remove spinner change VC.
              container.classList.remove("is-updating");

              var div = container.querySelector(".div-visual-console-spinner");

              if (div !== null) {
                var parent = div.parentElement;
                if (parent !== null) {
                  parent.removeChild(div);
                }
              }
              console.log(
                "[ERROR]",
                "[VISUAL-CONSOLE-CLIENT]",
                "[API]",
                error.message
              );
              abortable.abort();
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

                var receivedAt = new Date();
                var prevProps = visualConsole.props;
                if (beforeUpdate) {
                  beforeUpdate(items, visualConsole, props, size);
                } else {
                  // Add the datetime when the item was received.
                  items.map(function(item) {
                    item["receivedAt"] = receivedAt;
                    return item;
                  });

                  // Update the data structure.
                  visualConsole.props = props;

                  // Update the items.
                  visualConsole.updateElements(items);
                }

                if (window.location.pathname.indexOf("index.php") <= 0) {
                  if (visualConsole.props.maintenanceMode != null) {
                    visualConsole.enableMaintenanceMode();
                  } else {
                    visualConsole.disableMaintenanceMode();
                  }
                }

                // Emit the VC update event.
                if (onUpdate) onUpdate(prevProps, visualConsole.props);
              } catch (ignored) {} // eslint-disable-line no-empty
            }
            done();
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
      var data = e.item.props || {};
      var meta = e.item.meta || {};

      if (meta.editMode) {
        // Item selection.
        if (meta.isSelected) {
          visualConsole.unSelectItem(data.id);
        } else {
          // Unselect the rest of the elements if the
          var isMac = navigator.platform.toUpperCase().indexOf("MAC") >= 0;
          visualConsole.selectItem(
            data.id,
            isMac ? !e.nativeEvent.metaKey : !e.nativeEvent.ctrlKey
          );
        }
      } else if (
        !meta.editMode &&
        data.linkedLayoutId != null &&
        data.linkedLayoutId > 0 &&
        data.link != null &&
        data.link.length > 0 &&
        (data.linkedLayoutAgentId == null || data.linkedLayoutAgentId === 0) &&
        data.linkedLayoutNodeId === 0 &&
        e.nativeEvent.metaKey === false
      ) {
        // Override the link to another VC if it isn't on remote console.
        // Stop the current link behavior.
        e.nativeEvent.preventDefault();
        // Fetch and update the old VC with the new.
        updateVisualConsole(data.linkedLayoutId, updateInterval);
      }
    });
    // VC Item double clicked.
    visualConsole.onItemDblClick(function(e) {
      e.nativeEvent.preventDefault();
      e.nativeEvent.stopPropagation();

      var item = e.item || {};
      var meta = item.meta || {};

      if (meta.editMode && !meta.isUpdating) {
        createOrUpdateVisualConsoleItem(
          visualConsole,
          asyncTaskManager,
          baseUrl,
          item
        );
      } else if (meta.lineMode && item.props.type == 21) {
        load_modal({
          url: baseUrl + "/ajax.php",
          modal: {
            title: "NetworkLink information",
            ok: "Ok"
          },
          extradata: [
            {
              name: "from",
              value: item.props.linkedStart
            },
            {
              name: "to",
              value: item.props.linkedEnd
            }
          ],
          onshow: {
            page: "include/rest-api/index",
            method: "networkLinkPopup"
          }
        });
        // confirmDialog({
        //   title: "todo",
        //   message:
        //     "<pre>" +
        //     item.props.labelStart +
        //     "</pre><br><pre>" +
        //     item.props.labelEnd +
        //     "</pre>"
        // });
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
      if (e.item.props.type === 13 || e.item.props.type === 21) {
        var startIsLeft =
          e.item.props.startPosition.x - e.item.props.endPosition.x <= 0;
        var startIsTop =
          e.item.props.startPosition.y - e.item.props.endPosition.y <= 0;

        data = {
          startX: startIsLeft
            ? e.newPosition.x
            : e.item.props.width + e.newPosition.x,
          startY: startIsTop
            ? e.newPosition.y
            : e.item.props.height + e.newPosition.y,
          endX: startIsLeft
            ? e.item.props.width + e.newPosition.x
            : e.newPosition.x,
          endY: startIsTop
            ? e.item.props.height + e.newPosition.y
            : e.newPosition.y,
          type: e.item.props.type
        };
      }

      if (e.item.props.processValue != undefined) {
        data.processValue = e.item.props.processValue;
      }

      if (e.item.props.percentileType != undefined) {
        data.percentileType = e.item.props.percentileType;
      }

      var taskId = "visual-console-item-update-" + id;

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
    // VC Line Item moved.
    visualConsole.onLineMoved(function(e) {
      var id = e.item.props.id;
      var data = {
        startX: e.startPosition.x,
        startY: e.startPosition.y,
        endX: e.endPosition.x,
        endY: e.endPosition.y
      };

      var taskId = "visual-console-item-update-" + id;

      // Persist the new position.
      asyncTaskManager
        .add(taskId, function(done) {
          var abortable = updateVisualConsoleItem(
            baseUrl,
            visualConsole.props.id,
            id,
            data,
            function(error, data) {
              if (!error && !data) return;

              try {
                var decoded_data = JSON.parse(data);
                visualConsole.updateElement(decoded_data);
              } catch (error) {
                console.error(error);
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
      var item = e.item;
      var id = item.props.id;
      var data = {
        width: e.newSize.width,
        height: e.newSize.height,
        type: item.props.type
      };

      // Trick, to allow the percentile item to reuse the height field to save the max value,
      // it is very ugly, change some year.
      if (item.props.type === 3) {
        data = {
          width: e.newSize.width,
          type: item.props.type
        };
      }

      if (item.props.processValue != undefined) {
        data.processValue = item.props.processValue;
      }

      if (item.props.percentileType != undefined) {
        data.percentileType = item.props.percentileType;
      }

      var taskId = "visual-console-item-update-" + id;
      // Persist the new size.
      asyncTaskManager
        .add(taskId, function(done) {
          var abortable = updateVisualConsoleItem(
            baseUrl,
            visualConsole.props.id,
            id,
            data,
            function(error, data) {
              if (error || !data) {
                console.log(
                  "[ERROR]",
                  "[VISUAL-CONSOLE-CLIENT]",
                  "[API]",
                  error ? error.message : "Invalid response"
                );

                // Resize the element to its initial Size.
                item.resize(e.prevSize.width, e.prevSize.height);
                item.setMeta({ isUpdating: false });
                done();
                return; // Stop task execution.
              }

              if (typeof data === "string") {
                try {
                  data = JSON.parse(data);
                } catch (error) {
                  console.log(
                    "[ERROR]",
                    "[VISUAL-CONSOLE-CLIENT]",
                    "[API]",
                    error ? error.message : "Invalid response"
                  );

                  // Resize the element to its initial Size.
                  item.resize(e.prevSize.width, e.prevSize.height);
                  item.setMeta({ isUpdating: false });
                  done();
                  return; // Stop task execution.
                }
              }

              visualConsole.updateElement(data);
              item.setMeta({ isUpdating: false });

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
    },
    changeDimensionsVc: function(dimensions, interval) {
      if (dimensions != null) {
        updateVisualConsole(visualConsole.props.id, interval, null, dimensions);
      }
    },
    forceUpdateVisualConsole: function() {
      asyncTaskManager.cancel("visual-console");
      asyncTaskManager.cancel("visual-console-start");
      updateVisualConsole(visualConsole.props.id);
    },
    createItem: function(typeString) {
      var type;
      switch (typeString) {
        case "STATIC_GRAPH":
          type = 0;
          break;
        case "MODULE_GRAPH":
          type = 1;
          break;
        case "SIMPLE_VALUE":
        case "SIMPLE_VALUE_MAX":
        case "SIMPLE_VALUE_MIN":
        case "SIMPLE_VALUE_AVG":
          type = 2;
          break;
        case "PERCENTILE_BAR":
        case "PERCENTILE_BUBBLE":
        case "CIRCULAR_PROGRESS_BAR":
        case "CIRCULAR_INTERIOR_PROGRESS_BAR":
          type = 3;
          break;
        case "LABEL":
          type = 4;
          break;
        case "ICON":
          type = 5;
          break;
        case "SERVICE":
          type = 10;
          break;
        case "GROUP_ITEM":
          type = 11;
          break;
        case "BOX_ITEM":
          type = 12;
          break;
        case "LINE_ITEM":
          type = 13;
          break;
        case "AUTO_SLA_GRAPH":
          type = 14;
          break;
        case "DONUT_GRAPH":
          type = 17;
          break;
        case "BARS_GRAPH":
          type = 18;
          break;
        case "CLOCK":
          type = 19;
          break;
        case "COLOR_CLOUD":
          type = 20;
          break;
        case "NETWORK_LINK":
          type = 21;
          break;
        case "ODOMETER":
          type = 22;
          break;
        case "BASIC_CHART":
          type = 23;
          break;
        default:
          type = 0;
      }

      createOrUpdateVisualConsoleItem(
        visualConsole,
        asyncTaskManager,
        baseUrl,
        { itemProps: { type: type } }
      );
    },
    deleteItem: function(item) {
      var aux = item;
      var id = item.props.id;

      item.remove();

      var taskId = "visual-console-item-update-" + id;

      asyncTaskManager
        .add(taskId, function(done) {
          var abortable = removeVisualConsoleItem(
            baseUrl,
            visualConsole.props.id,
            id,
            function(error, data) {
              if (error || !data) {
                console.log(
                  "[ERROR]",
                  "[VISUAL-CONSOLE-CLIENT]",
                  "[API]",
                  error ? error.message : "Invalid response"
                );

                // Add the item to the list.
                var itemRetrieved = aux.props;
                itemRetrieved["receivedAt"] = new Date();
                var newItem = visualConsole.addElement(itemRetrieved);
                newItem.setMeta({ editMode: true });
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
    },
    copyItem: function(item) {
      var id = item.props.id;
      item.setMeta({ isSelected: false, isUpdating: true });

      visualConsole.unSelectItem(id);

      var taskId = "visual-console-item-update-" + id;

      // Persist the new position.
      asyncTaskManager
        .add(taskId, function(done) {
          var abortable = copyVisualConsoleItem(
            baseUrl,
            visualConsole.props.id,
            id,
            function(error, data) {
              if (error || !data) {
                console.log(
                  "[ERROR]",
                  "[VISUAL-CONSOLE-CLIENT]",
                  "[API]",
                  error ? error.message : "Invalid response"
                );

                item.setMeta({ isUpdating: false });

                done();
                return; // Stop task execution.
              }

              item.setMeta({ isUpdating: false });

              var itemRetrieved = item.props;
              if (itemRetrieved["type"] == 13 || itemRetrieved["type"] == 21) {
                var startIsLeft =
                  itemRetrieved["startPosition"]["x"] -
                    itemRetrieved["endPosition"]["x"] <=
                  0;
                var startIsTop =
                  itemRetrieved["startPosition"]["y"] -
                    itemRetrieved["endPosition"]["y"] <=
                  0;

                itemRetrieved["startX"] = startIsLeft
                  ? itemRetrieved["x"] + 20
                  : itemRetrieved["width"] + itemRetrieved["x"] + 20;

                itemRetrieved["startY"] = startIsTop
                  ? itemRetrieved["y"] + 20
                  : itemRetrieved["height"] + itemRetrieved["y"] + 20;

                itemRetrieved["endX"] = startIsLeft
                  ? itemRetrieved["width"] + itemRetrieved["x"] + 20
                  : itemRetrieved["x"] + 20;

                itemRetrieved["endY"] = startIsTop
                  ? itemRetrieved["height"] + itemRetrieved["y"] + 20
                  : itemRetrieved["y"] + 20;
              } else {
                itemRetrieved["x"] = itemRetrieved["x"] + 20;
                itemRetrieved["y"] = itemRetrieved["y"] + 20;
              }
              itemRetrieved["receivedAt"] = new Date();
              itemRetrieved["id"] = data;

              var newItem = visualConsole.addElement(itemRetrieved);
              newItem.setMeta({ editMode: true, isSelected: true });
              visualConsole.selectItem(newItem.props.id);

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
    }
  };
}

/**
 * Fetch a Visual Console's structure and its items.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {number} vcId Identifier of the Visual Console.
 * @param {string|null} id_user User id given for public access.
 * @param {string|null} hash Authorization hash given for public access.
 * @param {function} callback Function to be executed on request success or fail.
 * On success, the function will receive an object with the next properties:
 * - `props`: object with the Visual Console's data structure.
 * - `items`: array of data structures of the Visual Console's items.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function loadVisualConsoleData(
  baseUrl,
  vcId,
  size,
  id_user,
  hash,
  mode,
  callback
) {
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
        visualConsoleId: vcId,
        id_user: typeof id_user !== undefined ? id_user : null,
        auth_hash: typeof hash !== undefined ? hash : null
      },
      "json"
    )
    .done(handleSuccess("props"))
    .fail(handleFail);

  var queryString = new URLSearchParams(window.location.search);
  var widthScreen = 0;
  if (queryString.get("width")) {
    widthScreen = document.body.offsetWidth;
  }

  // Visual Console items request.
  itemsJqXHR = jQuery
    // .get(apiPath + "/visual-consoles/" + vcId + "/items", null, "json")
    .get(
      apiPath,
      {
        page: "include/rest-api/index",
        getVisualConsoleItems: 1,
        size: size,
        visualConsoleId: vcId,
        id_user: typeof id_user == undefined ? id_user : null,
        auth_hash: typeof hash == undefined ? hash : null,
        mode: mode,
        widthScreen: widthScreen
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
    // .post(apiPath + "/visual-consoles/" + vcId, null, "json")
    .post(
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

/**
 * Fetch a Visual Console's structure and its items.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {number} vcId Identifier of the Visual Console.
 * @param {Object} data Data we want to save.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function createVisualConsoleItem(baseUrl, vcId, data, callback) {
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
    .post(
      apiPath,
      {
        page: "include/rest-api/index",
        createVisualConsoleItem: 1,
        visualConsoleId: vcId,
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
function serviceListVisualConsole(baseUrl, vcId, data, callback) {
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
    .post(
      apiPath,
      {
        page: "include/rest-api/index",
        serviceListVisualConsole: 1,
        visualConsoleId: vcId,
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

/**
 * Fetch a Visual Console's structure and its items.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {number} vcId Identifier of the Visual Console.
 * @param {number} vcItemId Identifier of the Visual Console's item.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function getVisualConsoleItem(baseUrl, vcId, vcItemId, callback) {
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
        getVisualConsoleItem: 1,
        visualConsoleId: vcId,
        visualConsoleItemId: vcItemId
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

/**
 * Fetch a Visual Console's structure and its items.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {number} vcId Identifier of the Visual Console.
 * @param {number} vcItemId Identifier of the Visual Console's item.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function removeVisualConsoleItem(baseUrl, vcId, vcItemId, callback) {
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
        removeVisualConsoleItem: 1,
        visualConsoleId: vcId,
        visualConsoleItemId: vcItemId
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

/**
 * Copy an item.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {number} vcId Identifier of the Visual Console.
 * @param {number} vcItemId Identifier of the Visual Console's item.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function copyVisualConsoleItem(baseUrl, vcId, vcItemId, callback) {
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
    .post(
      apiPath,
      {
        page: "include/rest-api/index",
        copyVisualConsoleItem: 1,
        visualConsoleId: vcId,
        visualConsoleItemId: vcItemId
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

/**
 * When invoking modals from JS, some DOM id could be repeated.
 * This method cleans DOM to avoid duplicated IDs.
 */
function cleanupDOM() {
  $("#modalVCItemForm").empty();
}
/* Defined in operations/visual_console/view.php */
/* global $, load_modal, tinyMCE */
function createOrUpdateVisualConsoleItem(
  visualConsole,
  asyncTaskManager,
  baseUrl,
  item
) {
  var nameType = "";
  switch (item.itemProps.type) {
    case 0:
      nameType = "Static graph";
      break;
    case 1:
      nameType = "Module graph";
      break;
    case 2:
      nameType = "Simple Value";
      break;
    case 3:
      nameType = "Percentile";
      break;
    case 4:
      nameType = "Label";
      break;
    case 5:
      nameType = "Icon";
      break;
    case 10:
      nameType = "Service";
      break;
    case 11:
      nameType = "Group";
      break;
    case 12:
      nameType = "Box";
      break;
    case 13:
      nameType = "Line";
      break;
    case 14:
      nameType = "Event history";
      break;
    case 17:
      nameType = "Donut graph";
      break;
    case 18:
      nameType = "Bars graph";
      break;
    case 19:
      nameType = "Clock";
      break;
    case 20:
      nameType = "Color Cloud";
      break;
    case 21:
      nameType = "Network Link";
      break;
    case 22:
      nameType = "Odometer";
      break;
    case 23:
      nameType = "Basic chart";
      break;

    default:
      nameType = "Static graph";
      break;
  }

  var title = "Create item ";
  if (item.itemProps.id) {
    title = "Update item ";
  }
  title += nameType;

  load_modal({
    target: $("#modalVCItemForm"),
    form: ["itemForm-label", "itemForm-general", "itemForm-specific"],
    url: baseUrl + "ajax.php",
    ajax_callback: function(response) {
      var data = JSON.parse(response);

      if (data == false) {
        // Error.
        return;
      }

      if (item.itemProps.id) {
        visualConsole.updateElement(data);
        item.setMeta({ isUpdating: false });
      } else {
        document
          .getElementById("visual-console-container")
          .classList.remove("is-updating");

        var div = document
          .getElementById("visual-console-container")
          .querySelector(".div-visual-console-spinner");
        if (div !== null) {
          var parent = div.parentElement;
          if (parent !== null) {
            parent.removeChild(div);
          }
        }
        data["receivedAt"] = new Date();
        var newItem = visualConsole.addElement(data);
        newItem.setMeta({ editMode: true });
      }
    },
    cleanup: cleanupDOM,
    modal: {
      title: title,
      ok: "OK",
      cancel: "Cancel"
    },
    extradata: [
      {
        name: "type",
        value: item.itemProps.type
      },
      {
        name: "vCId",
        value: visualConsole.props.id
      },
      {
        name: "itemId",
        value: item.itemProps.id ? item.itemProps.id : 0
      }
    ],
    onshow: {
      page: "include/rest-api/index",
      method: "loadTabs",
      maxHeight: 900,
      minHeight: 400
    },
    onsubmit: {
      page: "include/rest-api/index",
      method: "processForm",
      preaction: function() {
        UndefineTinyMCE("#textarea_label");
        if (item.itemProps.id) {
          item.setMeta({ isUpdating: true });
        } else {
          var divParent = document.createElement("div");
          divParent.className = "div-visual-console-spinner";
          var divSpinner = document.createElement("div");
          divSpinner.className = "visual-console-spinner";
          divParent.appendChild(divSpinner);

          document
            .getElementById("visual-console-container")
            .classList.add("is-updating");

          document
            .getElementById("visual-console-container")
            .appendChild(divParent);
        }
      }
    },
    onsubmitClose: 1
  });
}

/**
 * Onchange input type module graph or custom graph.
 * @param {string} type Type graph.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function typeModuleGraph(type) {
  $("#MGautoCompleteAgent").removeClass("hidden");
  $("#MGautoCompleteModule").removeClass("hidden");
  $("#MGcustomGraph").removeClass("hidden");
  $("#MGgraphType").removeClass("hidden");
  $("#MGshowLegend").removeClass("hidden");

  if (type == "module") {
    $("#MGautoCompleteAgent").show();
    $("#MGautoCompleteModule").show();
    $("#MGgraphType").show();
    $("#MGshowLegend").show();
    $("#MGcustomGraph").hide();
    $("#customGraphId").val(0);
  } else if (type == "custom") {
    $("#MGautoCompleteAgent").hide();
    $("#MGautoCompleteModule").hide();
    $("#MGgraphType").hide();
    $("#MGshowLegend").hide();
    $("#MGcustomGraph").show();
  }
}

/**
 * Onchange input Process Simple Value.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function simpleValuePeriod() {
  $("#SVPeriod").removeClass("hidden");
  if ($("#processValue :selected").val() != "none") {
    $("#SVPeriod").show();
  } else {
    $("#SVPeriod").hide();
  }
}

/**
 * Onchange input Linked visual console.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function linkedVisualConsoleChange() {
  $("#li-linkedLayoutStatusType").removeClass("hidden");
  if ($("#getAllVisualConsole :selected").val() != 0) {
    $("#li-linkedLayoutStatusType").show();
  } else {
    $("#li-linkedLayoutStatusType").hide();
    $("#li-linkedLayoutStatusTypeWeight").removeClass("hidden");
    $("#li-linkedLayoutStatusTypeCriticalThreshold").removeClass("hidden");
    $("#li-linkedLayoutStatusTypeWarningThreshold").removeClass("hidden");
    $("#li-linkedLayoutStatusTypeCriticalThreshold").hide();
    $("#li-linkedLayoutStatusTypeWarningThreshold").hide();
    $("#li-linkedLayoutStatusTypeWeight").hide();
  }

  var linkedLayoutExtract = $("#getAllVisualConsole :selected")
    .val()
    .split("|");

  var linkedLayoutNodeId = 0;
  var linkedLayoutId = 0;
  if (linkedLayoutExtract instanceof Array) {
    linkedLayoutId = linkedLayoutExtract[0] ? linkedLayoutExtract[0] : 0;
    linkedLayoutNodeId = linkedLayoutExtract[1] ? linkedLayoutExtract[1] : 0;
  }

  $("#hidden-linkedLayoutId").val(linkedLayoutId);
  $("#hidden-linkedLayoutNodeId").val(linkedLayoutNodeId);
}

/**
 * Onchange input type Linked visual console.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function linkedVisualConsoleTypeChange() {
  $("#li-linkedLayoutStatusTypeWeight").removeClass("hidden");
  $("#li-linkedLayoutStatusTypeCriticalThreshold").removeClass("hidden");
  $("#li-linkedLayoutStatusTypeWarningThreshold").removeClass("hidden");
  if ($("#linkedLayoutStatusType :selected").val() == "service") {
    $("#li-linkedLayoutStatusTypeCriticalThreshold").show();
    $("#li-linkedLayoutStatusTypeWarningThreshold").show();
    $("#li-linkedLayoutStatusTypeWeight").hide();
  } else if ($("#linkedLayoutStatusType :selected").val() == "weight") {
    $("#li-linkedLayoutStatusTypeCriticalThreshold").hide();
    $("#li-linkedLayoutStatusTypeWarningThreshold").hide();
    $("#li-linkedLayoutStatusTypeWeight").show();
  } else {
    $("#li-linkedLayoutStatusTypeCriticalThreshold").hide();
    $("#li-linkedLayoutStatusTypeWarningThreshold").hide();
    $("#li-linkedLayoutStatusTypeWeight").hide();
  }
}

/**
 * Onchange input image.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function imageVCChange(baseUrl, vcId, only) {
  var nameImg = document.getElementById("imageSrc").value;
  if (nameImg == 0) {
    $("#li-image-item label").empty();
    return;
  }

  if (!only) {
    only = 0;
  }
  var fncallback = function(error, data) {
    if (error || !data) {
      console.log(
        "[ERROR]",
        "[VISUAL-CONSOLE-CLIENT]",
        "[API]",
        error ? error.message : "Invalid response"
      );

      return;
    }

    if (typeof data === "string") {
      try {
        data = JSON.parse(data);
      } catch (error) {
        console.log(
          "[ERROR]",
          "[VISUAL-CONSOLE-CLIENT]",
          "[API]",
          error ? error.message : "Invalid response"
        );

        return; // Stop task execution.
      }
    }

    $("#li-image-item label").empty();
    $("#li-image-item label").append(data);
    return;
  };

  getImagesVisualConsole(baseUrl, vcId, nameImg, only, fncallback);
}

/**
 * Fetch groups access user.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {int} vcId Identifier of the Visual Console.
 * @param {string} nameImg Name img.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function getImagesVisualConsole(baseUrl, vcId, nameImg, only, callback) {
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
    .get(
      apiPath,
      {
        page: "include/rest-api/index",
        getImagesVisualConsole: 1,
        visualConsoleId: vcId,
        nameImg: nameImg,
        only: only
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

/**
 * Create Color range.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {int} vcId Identifier of the Visual Console.
 * @return {Void}
 */
// eslint-disable-next-line no-unused-vars
function createColorRange(baseUrl, vcId) {
  var from = document.getElementById("rangeDefaultFrom").value;
  var to = document.getElementById("rangeDefaultTo").value;
  var color = document.getElementById("color-rangeDefaultColor").value;

  if (from == 0 && to == 0) {
    return;
  }

  var fncallback = function(error, data) {
    if (error || !data) {
      console.log(
        "[ERROR]",
        "[VISUAL-CONSOLE-CLIENT]",
        "[API]",
        error ? error.message : "Invalid response"
      );

      return;
    }

    $("#itemForm-specific ul.wizard:first").append(data);

    // Default values.
    document.getElementById("rangeDefaultFrom").value = 0;
    document.getElementById("rangeDefaultTo").value = 0;
    document.getElementById("color-rangeDefaultColor").value = "#000000";
    return;
  };

  createColorRangeVisualConsole(baseUrl, vcId, from, to, color, fncallback);
}

/**
 * Add color ranges.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {int} vcId Identifier of the Visual Console.
 * @param {int} from From range.
 * @param {int} to To range.
 * @param {string} color Color range.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function createColorRangeVisualConsole(
  baseUrl,
  vcId,
  from,
  to,
  color,
  callback
) {
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
    .get(
      apiPath,
      {
        page: "include/rest-api/index",
        createColorRangeVisualConsole: 1,
        visualConsoleId: vcId,
        from: from,
        to: to,
        color: color
      },
      "html"
    )
    .done(handleSuccess)
    .fail(handleFail);

  // Abortable.
  return {
    abort: abort
  };
}

/**
 * Delete color ranges.
 * @param {string} id UniqId for row range.
 * @return {Void}
 */
// eslint-disable-next-line no-unused-vars
function removeColorRange(id) {
  $("#li-" + id).remove();
}

/**
 * Onchange time-zone.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function timeZoneVCChange(baseUrl, vcId) {
  var zone = document.getElementById("zone").value;

  var fncallback = function(error, data) {
    if (error || !data) {
      console.log(
        "[ERROR]",
        "[VISUAL-CONSOLE-CLIENT]",
        "[API]",
        error ? error.message : "Invalid response"
      );

      return;
    }

    if (typeof data === "string") {
      try {
        data = JSON.parse(data);
      } catch (error) {
        console.log(
          "[ERROR]",
          "[VISUAL-CONSOLE-CLIENT]",
          "[API]",
          error ? error.message : "Invalid response"
        );

        return; // Stop task execution.
      }
    }

    removeAllOptions();
    Object.keys(data).forEach(addOption);

    function addOption(item) {
      var select = document.getElementById("clockTimezone");
      select.options[select.options.length] = new Option(item, item);
    }

    function removeAllOptions() {
      var select = document.getElementById("clockTimezone");
      select.options.length = 0;
    }

    return;
  };

  getTimeZoneVisualConsole(baseUrl, vcId, zone, fncallback);
}

/**
 * TimeZone for zones.
 * @param {string} baseUrl Base URL to build the API path.
 * @param {int} vcId Identifier of the Visual Console.
 * @param {string} zone Name zone.
 * @param {function} callback Function to be executed on request success or fail.
 * @return {Object} Cancellable. Object which include and .abort([statusText]) function.
 */
// eslint-disable-next-line no-unused-vars
function getTimeZoneVisualConsole(baseUrl, vcId, zone, callback) {
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
    .get(
      apiPath,
      {
        page: "include/rest-api/index",
        getTimeZoneVisualConsole: 1,
        visualConsoleId: vcId,
        zone: zone
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
