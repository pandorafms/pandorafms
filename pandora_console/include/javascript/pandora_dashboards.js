/* globals $, GridStack, load_modal, TreeController, forced_title_callback, createVisualConsole, tinyMCE*/
// eslint-disable-next-line no-unused-vars
function show_option_dialog(settings) {
  load_modal({
    target: $("#modal-update-dashboard"),
    form: "form-update-dashboard",
    url: settings.url_ajax,
    modal: {
      title: settings.title,
      cancel: settings.btn_cancel,
      ok: settings.btn_text
    },
    onshow: {
      page: settings.url,
      method: "drawFormDashboard",
      extradata: {
        dashboardId: settings.dashboardId
      }
    },
    onsubmit: {
      page: settings.url,
      method: "updateDashboard",
      dataType: "json"
    },
    ajax_callback: update_dashboard
  });
}

function update_dashboard(data) {
  if (data.error === 1) {
    console.error(data.error_mesage);
    return;
  }

  $(".ui-dialog-content").dialog("close");
  var url = data.url + "&dashboardId=" + data.dashboardId;
  location.replace(url);
}

/**
 * Onchange input switch private.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function showGroup() {
  $("#li-group").removeClass("hidden");
  var private = $("#private").prop("checked");
  if (private) {
    $("#id_group").removeAttr("required");
    $("#li-group").hide();
  } else {
    $("#id_group").attr("required", true);
    $("#li-group").show();
  }
}

// eslint-disable-next-line no-unused-vars
function initialiceLayout(data) {
  var grid = GridStack.init({
    float: true,
    column: 12,
    alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    ),
    resizable: {
      handles: "e, se, s, sw, w"
    },
    disableDrag: true,
    disableResize: true,
    draggable: false
  });

  var positionGrid = grid.el.getBoundingClientRect();
  var gridWidth = positionGrid.width;

  getCellsLayout();

  function getCellsLayout() {
    $.ajax({
      method: "post",
      url: data.url,
      data: {
        page: data.page,
        method: "getCellsLayout",
        dashboardId: data.dashboardId,
        auth_class: data.auth.class,
        auth_hash: data.auth.hash,
        id_user: data.auth.user
      },
      dataType: "json",
      success: function(d) {
        loadLayout(d);
      },
      error: function(error) {
        console.error(error);
        return [];
      }
    });
    return false;
  }

  function loadLayout(items) {
    // Remove layout.
    grid.removeAll();
    // Update.
    grid.batchUpdate();
    // Add widgets.
    items.forEach(function(item) {
      var id = parseInt(item.id);
      var position = item.position;
      var widgetId = item.widgetId;
      // Retrocompatibility old dashboard.
      position = {
        x: 0,
        y: 0,
        width: 4,
        height: 4,
        autoPosition: true,
        minWidth: 0,
        maxWidth: 2000,
        minHeight: 0,
        maxHeight: 2000
      };
      if (item.position !== "") {
        position = JSON.parse(item.position);
      }
      addCell(
        id,
        position.x,
        position.y,
        position.width,
        position.height,
        position.autoPosition,
        position.minWidth,
        position.maxWidth,
        position.minHeight,
        position.maxHeight,
        widgetId,
        false
      );
    });
    // Commit.
    grid.commit();
    return false;
  }

  function addCell(
    id,
    x,
    y,
    width,
    height,
    autoPosition,
    minWidth,
    maxWidth,
    minHeight,
    maxHeight,
    widgetId,
    needSaveLayout = false
  ) {
    $.ajax({
      method: "post",
      url: data.url,
      data: {
        page: data.page,
        method: "drawCell",
        dashboardId: data.dashboardId,
        cellId: id,
        widgetId: widgetId,
        gridWidth: gridWidth,
        auth_class: data.auth.class,
        auth_hash: data.auth.hash,
        id_user: data.auth.user
      },
      dataType: "html",
      success: function(cellData) {
        var elem = grid.addWidget(
          cellData,
          x,
          y,
          width,
          height,
          autoPosition,
          minWidth,
          maxWidth,
          minHeight,
          maxHeight,
          id
        );

        // Add spinner.
        var element = $("#widget-" + id);
        element = element[0].querySelector(".content-widget");
        addSpinner(element);

        // Width and height.
        var newWidth = $(elem).attr("data-gs-width");
        var newHeight = $(elem).attr("data-gs-height");

        $.ajax({
          method: "post",
          url: data.url,
          data: {
            page: data.page,
            method: "drawWidget",
            dashboardId: data.dashboardId,
            cellId: id,
            widgetId: widgetId,
            newWidth: newWidth,
            newHeight: newHeight,
            gridWidth: gridWidth,
            auth_class: data.auth.class,
            auth_hash: data.auth.hash,
            id_user: data.auth.user
          },
          dataType: "html",
          success: function(widgetData) {
            // Remove spinner.
            removeSpinner(element);
            $("#widget-" + id + " .content-widget").append(widgetData);

            $("#button-add-widget-" + id).click(function() {
              addWidgetDialog(id);
            });

            if (!$("#checkbox-edit-mode").is(":checked")) {
              $(".add-widget").hide();
            } else {
              $(".new-widget-message").hide();
            }

            if (needSaveLayout === true) {
              var parentElement = $("#widget-" + id).parent();
              grid.enableMove(parentElement, true);
              grid.enableResize(parentElement, true);
              grid.float(false);
            }
          },
          error: function(error) {
            console.error(error);
          }
        });

        if (needSaveLayout === true) {
          saveLayout();
        }

        if (!$("#checkbox-edit-mode").is(":checked")) {
          $(".header-options").hide();
        }

        $("#delete-widget-" + id).click(function(event) {
          // eslint-disable-next-line no-undef
          confirmDialog({
            title: "Are you sure?",
            message:
              "<h4 style='text-align: center;padding-top: 20px;'>All changes made to this widget will be lost</h4>",
            cancel: "Cancel",
            ok: "Ok",
            onAccept: function() {
              // Continue execution.
              var nodo = event.target.offsetParent;
              deleteCell(id, nodo.parentNode);
            }
          });
        });

        $("#configure-widget-" + id).click(function() {
          configurationWidget(id, widgetId);
        });
      },
      error: function(error) {
        console.error(error);
      }
    });
  }

  function saveLayout() {
    var items = $(".grid-stack > .grid-stack-item:visible")
      .map(function(i, el) {
        el = $(el);
        var node = el.data("_gridstack_node");
        return {
          id: node.id,
          x: node.x,
          y: node.y,
          width: node.width,
          height: node.height
        };
      })
      .toArray();

    $.ajax({
      method: "post",
      url: data.url,
      data: {
        page: data.page,
        method: "saveLayout",
        dashboardId: data.dashboardId,
        items: items,
        auth_class: data.auth.class,
        auth_hash: data.auth.hash,
        id_user: data.auth.user
      },
      dataType: "html",
      success: function(data) {
        return data;
      },
      error: function(error) {
        console.error(error);
      }
    });

    return false;
  }

  function deleteCell(cellId, node) {
    $.ajax({
      method: "post",
      url: data.url,
      data: {
        page: data.page,
        dashboardId: data.dashboardId,
        method: "deleteCell",
        cellId: cellId,
        auth_class: data.auth.class,
        auth_hash: data.auth.hash,
        id_user: data.auth.user
      },
      dataType: "json",
      success: function(data) {
        // By default x and y = 0
        // width and height = 4
        // position auto = true.
        if (data.result !== 0) {
          grid.removeWidget(node);
          saveLayout();
        }
      },
      error: function(error) {
        console.error(error);
      }
    });
  }

  function insertCellLayout() {
    $.ajax({
      method: "post",
      url: data.url,
      data: {
        page: data.page,
        method: "insertCellLayout",
        dashboardId: data.dashboardId,
        auth_class: data.auth.class,
        auth_hash: data.auth.hash,
        id_user: data.auth.user
      },
      dataType: "json",
      success: function(data) {
        // By default x and y = 0
        // width and height = 4
        // position auto = true.
        if (data.cellId !== 0) {
          addCell(data.cellId, 0, 0, 4, 4, true, 0, 2000, 0, 2000, 0, true);
        }
      },
      error: function(error) {
        console.error(error);
      }
    });
  }

  function configurationWidget(cellId, widgetId) {
    load_modal({
      target: $("#modal-config-widget"),
      form: "form-config-widget",
      url: data.url,
      modal: {
        title: "Configure widget",
        cancel: "Cancel",
        ok: "Ok"
      },
      onshow: {
        page: data.page,
        method: "drawConfiguration",
        extradata: {
          cellId: cellId,
          dashboardId: data.dashboardId,
          widgetId: widgetId
        },
        width: widgetId == 14 || widgetId == 2 ? 750 : 450,
        maxHeight: 600,
        minHeight: 400
      },
      onsubmit: {
        page: data.page,
        method: "saveWidgetIntoCell",
        dataType: "json",
        preaction: function() {
          if (tinyMCE != undefined && tinyMCE.editors.length > 0 && widgetId) {
            // Content tiny.
            var label = tinyMCE.activeEditor.getContent();
            $("#textarea_text").val(label);
          }
        }
      },
      ajax_callback: update_widget_to_cell,
      onsubmitClose: 1,
      beforeClose: function() {
        tinyMCE.remove("#textarea_text");
        tinyMCE.execCommand("mceRemoveControl", true, "textarea_text");
      }
    });
  }

  function update_widget_to_cell(data) {
    if (data.error === 1) {
      console.error(data.text);
      return;
    }

    // Add spinner.
    var element = $("#widget-" + data.cellId);
    element = element[0].querySelector(".content-widget");
    addSpinner(element);

    // Width and height.
    var newWidth = $("#widget-" + data.cellId)
      .parent()
      .attr("data-gs-width");

    var newHeight = $("#widget-" + data.cellId)
      .parent()
      .attr("data-gs-height");

    redraw(data.cellId, newWidth, newHeight, gridWidth, data.widgetId);
  }

  // Operations.
  // Add Widgets.
  $("#add-widget").click(function() {
    insertCellLayout();
  });

  // Enable Layout.
  $("#checkbox-edit-mode").click(function() {
    if ($("#checkbox-edit-mode").is(":checked")) {
      grid.movable(".grid-stack-item", true);
      grid.resizable(".grid-stack-item", true);
      grid.float(false);
      $(".header-options").show();
      $(".add-widget").show();
      $(".new-widget-message").hide();
      $("#container-layout").addClass("container-layout");
      $("#add-widget").removeClass("invisible");
    } else {
      grid.movable(".grid-stack-item", false);
      grid.resizable(".grid-stack-item", false);
      grid.float(true);
      $(".header-options").hide();
      $(".add-widget").hide();
      $(".new-widget-message").show();
      $("#container-layout").removeClass("container-layout");
      $("#add-widget").addClass("invisible");
    }
  });

  // End drag.
  $(".grid-stack").on("dragstop", function() {
    setTimeout(function() {
      saveLayout();
    }, 200);
  });

  // Start Resize.
  $(".grid-stack").on("resizestart", function(event) {
    var element = event.target.querySelector(".content-widget");
    addSpinner(element);
  });

  // End Resize.
  $(".grid-stack").on("gsresizestop", function(event, elem) {
    var grid = this;

    // Grid Height and Width.
    var positionGrid = grid.getBoundingClientRect();
    // var gridHeight = positionGrid.height;
    var gridWidth = positionGrid.width;

    // Width and height.
    var newWidth = $(elem).attr("data-gs-width");
    var newHeight = $(elem).attr("data-gs-height");

    var cellId = elem.getAttribute("data-gs-id");

    redraw(cellId, newWidth, newHeight, gridWidth);
  });

  // Add Spinner
  function addSpinner(element) {
    var divParent = document.createElement("div");
    divParent.className = "div-dashboard-spinner";

    var divSpinner = document.createElement("div");
    divSpinner.className = "dashboard-spinner";
    divParent.appendChild(divSpinner);

    element.innerHTML = "";

    element.appendChild(divParent);
  }

  // Remove Spinner
  function removeSpinner(element) {
    // Div resize.
    var div = element.querySelector(".div-dashboard-spinner");
    if (div !== null) {
      var parent = div.parentElement;
      if (parent !== null) {
        parent.removeChild(div);
      }
    }
  }

  function addWidgetDialog(id) {
    $("#modal-add-widget")
      .dialog({
        title: "New Widget",
        resizable: false,
        modal: true,
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        width: 600,
        height: 600,
        open: function() {
          loadWidgetsDialog(id, 0);
        }
      })
      .show();
  }

  function loadWidgetsDialog(cellId, offset, search) {
    $.ajax({
      method: "post",
      url: data.url,
      data: {
        dashboardId: data.dashboardId,
        page: data.page,
        method: "drawAddWidget",
        cellId: cellId,
        offset: offset,
        search: search,
        auth_class: data.auth.class,
        auth_hash: data.auth.hash,
        id_user: data.auth.user
      },
      dataType: "html",
      success: function(data) {
        $("#modal-add-widget").empty();
        $("#modal-add-widget").append(data);
        $("a.pagination").click(function() {
          var offset = $(this)
            .attr("href")
            .split("=")
            .pop();

          loadWidgetsDialog(cellId, offset, search);
        });

        document.getElementById("text-search-widget").focus();
        if (typeof search !== "undefined") {
          document.getElementById("text-search-widget").value = "";
          document.getElementById("text-search-widget").value = search;
        }

        $("input[name=search-widget]").on(
          "keyup",
          debounce(function() {
            loadWidgetsDialog(cellId, 0, this.value);
          }, 300)
        );

        $(".img-add-widget").click(function() {
          // Empty and close modal.
          $("#modal-add-widget").empty();
          $("#modal-add-widget").dialog("close");

          // Extract Id widget
          var widgetId = this.id.replace("img-add-widget-", "");

          // Add spinner.
          var element = $("#widget-" + cellId);
          element = element[0].querySelector(".content-widget");
          addSpinner(element);

          // Width and height.
          var newWidth = $("#widget-" + data.cellId)
            .parent()
            .attr("data-gs-width");

          var newHeight = $("#widget-" + data.cellId)
            .parent()
            .attr("data-gs-height");

          redraw(cellId, newWidth, newHeight, gridWidth, widgetId);
        });
      },
      error: function(error) {
        console.error(error);
      }
    });
  }

  function redraw(cellId, newWidth, newHeight, gridWidth, widgetId = 0) {
    $.ajax({
      method: "post",
      url: data.url,
      data: {
        page: data.page,
        method: "drawCell",
        dashboardId: data.dashboardId,
        cellId: cellId,
        widgetId: widgetId,
        gridWidth: gridWidth,
        redraw: true,
        auth_class: data.auth.class,
        auth_hash: data.auth.hash,
        id_user: data.auth.user
      },
      dataType: "html",
      success: function(cellData) {
        var element = $("#widget-" + cellId);

        // Widget empty.
        element.empty();

        // Add Resize element.
        element.append(cellData);

        // Add spinner.
        var elementSpinner = element[0].querySelector(".content-widget");
        addSpinner(elementSpinner);

        $.ajax({
          method: "post",
          url: data.url,
          data: {
            page: data.page,
            method: "drawWidget",
            dashboardId: data.dashboardId,
            cellId: cellId,
            newWidth: newWidth,
            newHeight: newHeight,
            gridWidth: gridWidth,
            widgetId: widgetId,
            auth_class: data.auth.class,
            auth_hash: data.auth.hash,
            id_user: data.auth.user
          },
          dataType: "html",
          success: function(dataWidget) {
            var element = $("#widget-" + cellId);
            element = element[0].querySelector(".content-widget");
            removeSpinner(element);

            // Widget empty.
            $("#widget-" + cellId + " .content-widget").empty();

            // Add Resize element.
            $("#widget-" + cellId + " .content-widget").append(dataWidget);

            $("#button-add-widget-" + cellId).click(function() {
              addWidgetDialog(cellId);
            });

            if (!$("#checkbox-edit-mode").is(":checked")) {
              $(".add-widget").hide();
            } else {
              $(".new-widget-message").hide();
            }
          },
          error: function(error) {
            console.error(error);
          }
        });

        if (!$("#checkbox-edit-mode").is(":checked")) {
          $(".header-options").hide();
        }

        $("#delete-widget-" + cellId).click(function(event) {
          // eslint-disable-next-line no-undef
          confirmDialog({
            title: "Are you sure?",
            message:
              "<h4 style='text-align: center;padding-top: 20px;'>All changes made to this widget will be lost</h4>",
            cancel: "Cancel",
            ok: "Ok",
            onAccept: function() {
              // Continue execution.
              var nodo = event.target.offsetParent;
              deleteCell(cellId, nodo.parentNode);
            }
          });
        });

        $("#configure-widget-" + cellId).click(function() {
          configurationWidget(cellId, widgetId);
        });

        saveLayout();
      }
    });
  }
}

/**
 * Onchange input image.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function imageIconChange(data) {
  data = JSON.parse(atob(data));

  var nameImg = document.getElementById("imageSrc").value;

  if (nameImg == 0) {
    $("#li-image-item label").empty();
    return;
  }

  $.ajax({
    method: "post",
    url: data.url,
    data: {
      page: data.page,
      method: "imageIconDashboardAjax",
      nameImg: nameImg,
      dashboardId: data.dashboardId
    },
    dataType: "html",
    success: function(data) {
      $("#li-image-item label").empty();
      $("#li-image-item label").append(data);
    },
    error: function(error) {
      console.error(error);
    }
  });
}

/**
 * Load network map.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function dashboardLoadNetworkMap(settings) {
  // Add spinner.
  var element = document.getElementById("body_cell-" + settings.cellId);

  var divParent = document.createElement("div");
  divParent.id = "div-dashboard-spinner-" + settings.cellId;
  divParent.className = "div-dashboard-spinner";
  var divSpinner = document.createElement("div");
  divSpinner.className = "dashboard-spinner";
  divParent.appendChild(divSpinner);

  element.appendChild(divParent);

  $.ajax({
    method: "post",
    url: settings.url,
    data: {
      page: settings.page,
      networkmap: 1,
      networkmap_id: settings.networkmap_id,
      x_offset: settings.x_offset,
      y_offset: settings.y_offset,
      zoom_dash: settings.zoom_dash,
      auth_class: settings.auth_class,
      auth_hash: settings.auth_hash,
      id_user: settings.id_user,
      ignore_acl: 1,
      node: settings.node
    },
    dataType: "html",
    success: function(data) {
      $("#div-dashboard-spinner-" + settings.cellId).remove();
      $("#body_cell-" + settings.cellId).append(data);
    },
    error: function(error) {
      console.error(error);
    }
  });
}

/**
 * Load Wux Stats map.
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function dashboardLoadWuxStats(settings) {
  $.ajax({
    method: "post",
    url: settings.url,
    data: {
      page: settings.page,
      wux_transaction_stats: 1,
      id_agent: settings.id_agent,
      server_id: settings.server_id,
      transaction: settings.transaction,
      view_all_stats: settings.view_all_stats,
      auth_class: settings.auth_class,
      auth_hash: settings.auth_hash,
      id_user: settings.id_user
    },
    dataType: "html",
    success: function(data) {
      $("#body-cell-" + settings.cellId).append(data);
    },
    error: function(error) {
      console.error(error);
    }
  });
}

// eslint-disable-next-line no-unused-vars
function processTreeSearch(settings) {
  var treeController = TreeController.getController();

  // Clear the tree
  if (
    typeof treeController.recipient != "undefined" &&
    treeController.recipient.length > 0
  ) {
    treeController.recipient.empty();
  }

  var filters = {};
  filters.searchAgent = settings.searchAgent;
  filters.statusAgent = settings.statusAgent;
  filters.searchModule = settings.searchModule;
  filters.statusModule = settings.statusModule;
  filters.groupID = settings.searchGroup;
  filters.searchHirearchy = 1;
  filters.show_not_init_agents = 1;
  filters.show_not_init_modules = 1;

  $.ajax({
    type: "POST",
    url: settings.ajaxUrl,
    data: {
      getChildren: 1,
      page: settings.page,
      type: settings.type,
      auth_class: settings.auth_class,
      auth_hash: settings.auth_hash,
      id_user: settings.id_user,
      filter: filters
    },
    success: function(data) {
      if (data.success) {
        treeController.init({
          recipient: $("div#tree-controller-recipient_" + settings.cellId),
          detailRecipient: {
            render: function(element, data) {
              return {
                open: function() {
                  $("#module_details_window")
                    .hide()
                    .empty()
                    .append(data)
                    .dialog({
                      resizable: true,
                      draggable: true,
                      modal: true,
                      title: "Info module",
                      overlay: {
                        opacity: 0.5,
                        background: "black"
                      },
                      width: 450,
                      height: 500
                    });
                }
              };
            }
          },
          page: settings.page,
          emptyMessage: settings.translate.emptyMessage,
          foundMessage: settings.translate.foundMessage,
          tree: data.tree,
          auth_hash: settings.auth_hash,
          auth_class: settings.auth_class,
          id_user: settings.id_user,
          ajaxURL: settings.ajaxUrl,
          baseURL: settings.baseUrl,
          filter: filters,
          counterTitles: {
            total: {
              agents: settings.translate.total.agents,
              modules: settings.translate.total.modules,
              none: settings.translate.total.none
            },
            alerts: {
              agents: settings.translate.alerts.agents,
              modules: settings.translate.alerts.modules,
              none: settings.translate.alerts.none
            },
            critical: {
              agents: settings.translate.critical.agents,
              modules: settings.translate.critical.modules,
              none: settings.translate.critical.none
            },
            warning: {
              agents: settings.translate.warning.agents,
              modules: settings.translate.warning.modules,
              none: settings.translate.warning.none
            },
            unknown: {
              agents: settings.translate.unknown.agents,
              modules: settings.translate.unknown.modules,
              none: settings.translate.unknown.none
            },
            not_init: {
              agents: settings.translate.not_init.agents,
              modules: settings.translate.not_init.modules,
              none: settings.translate.not_init.none
            },
            ok: {
              agents: settings.translate.ok.agents,
              modules: settings.translate.ok.modules,
              none: settings.translate.ok.none
            }
          }
        });

        if (settings.openAllNodes) {
          $("#widget-" + settings.cellId)
            .find(".leaf-icon")
            .click();
        }
      }
    },
    dataType: "json"
  });
}

function processServiceTree(settings) {
  var treeController = TreeController.getController();

  if (
    typeof treeController.recipient != "undefined" &&
    treeController.recipient.length > 0
  )
    treeController.recipient.empty();

  $(".loading_tree").show();

  var parameters = {};
  parameters["page"] = "include/ajax/tree.ajax";
  parameters["getChildren"] = 1;
  parameters["type"] = "services";
  parameters["filter"] = {};
  parameters["filter"]["searchGroup"] = "";
  parameters["filter"]["searchAgent"] = "";
  parameters["filter"]["statusAgent"] = "";
  parameters["filter"]["searchModule"] = "";
  parameters["filter"]["statusModule"] = "";
  parameters["filter"]["groupID"] = "";
  parameters["filter"]["tagID"] = "";
  parameters["filter"]["searchHirearchy"] = 1;
  parameters["filter"]["show_not_init_agents"] = 1;
  parameters["filter"]["show_not_init_modules"] = 1;
  parameters["filter"]["is_favourite"] = 0;
  parameters["filter"]["width"] = 100;

  $.ajax({
    type: "POST",
    url: settings.ajaxURL,
    data: parameters,
    success: function(data) {
      if (data.success) {
        $(".loading_tree").hide();
        // Get the main values of the tree.
        var rawTree = Object.values(data.tree);
        // Sorting tree by description (TreeController.js).
        rawTree.sort(function(a, b) {
          var x = a.description.toLowerCase();
          var y = b.description.toLowerCase();
          if (x < y) {
            return -1;
          }
          if (x > y) {
            return 1;
          }
          return 0;
        });
        treeController.init({
          recipient: $("div#container_servicemap_" + settings.cellId),
          detailRecipient: {
            render: function(element, data) {
              return {
                open: function() {
                  $("#module_details_window")
                    .hide()
                    .empty()
                    .append(data)
                    .dialog({
                      resizable: true,
                      draggable: true,
                      modal: true,
                      title: "Info",
                      overlay: {
                        opacity: 0.5,
                        background: "black"
                      },
                      width: 450,
                      height: 500
                    });
                }
              };
            }
          },
          page: parameters["page"],
          emptyMessage: "No data found",
          foundMessage: "Found groups",
          tree: rawTree,
          baseURL: settings.baseURL,
          ajaxURL: settings.ajaxURL,
          filter: parameters["filter"],
          counterTitles: {
            total: {
              agents: "Total agents",
              modules: "Total modules",
              none: "Total"
            },
            alerts: {
              agents: "Fired alerts",
              modules: "Fired alerts",
              none: "Fired alerts"
            },
            critical: {
              agents: "Critical agents",
              modules: "Critical modules')",
              none: "Critical"
            },
            warning: {
              agents: "Warning agents",
              modules: "Warning modules",
              none: "Warning"
            },
            unknown: {
              agents: "Unknown agents",
              modules: "Unknown modules",
              none: "Unknown"
            },
            not_init: {
              agents: "Not init agents",
              modules: "Not init modules",
              none: "Not init"
            },
            ok: {
              agents: " Normal agents ",
              modules: " Normal modules ",
              none: " Normal "
            }
          }
        });
      }
    },
    dataType: "json"
  });
}

function show_module_detail_dialog(
  module_id,
  id_agent,
  server_name,
  offset,
  period,
  module_name
) {
  var params = {};
  var f = new Date();
  period = $("#period").val();

  params.selection_mode = $("input[name=selection_mode]:checked").val();
  if (!params.selection_mode) {
    params.selection_mode = "fromnow";
  }

  params.date_from = $("#text-date_from").val();
  if (!params.date_from) {
    params.date_from =
      f.getFullYear() + "/" + (f.getMonth() + 1) + "/" + f.getDate();
  }

  params.time_from = $("#text-time_from").val();
  if (!params.time_from) {
    params.time_from = f.getHours() + ":" + f.getMinutes();
  }

  params.date_to = $("#text-date_to").val();
  if (!params.date_to) {
    params.date_to =
      f.getFullYear() + "/" + (f.getMonth() + 1) + "/" + f.getDate();
  }

  params.time_to = $("#text-time_to").val();
  if (!params.time_to) {
    params.time_to = f.getHours() + ":" + f.getMinutes();
  }

  params.page = "include/ajax/module";
  params.get_module_detail = 1;
  params.server_name = server_name;
  params.id_agent = id_agent;
  params.id_module = module_id;
  params.offset = offset;
  params.period = period;

  $.ajax({
    type: "POST",
    url: "ajax.php",
    data: params,
    dataType: "html",
    success: function(data) {
      $("#module_details_window")
        .hide()
        .empty()
        .append(data)
        .dialog({
          resizable: true,
          draggable: true,
          modal: true,
          title: "Module: " + module_name,
          overlay: {
            opacity: 0.5,
            background: "black"
          },
          width: 650,
          height: 500
        })
        .show();
      refresh_pagination_callback(
        module_id,
        id_agent,
        server_name,
        module_name
      );
      //datetime_picker_callback();
      forced_title_callback();
    }
  });
}
/*
function datetime_picker_callback() {
  $("#text-time_from, #text-time_to").timepicker({
    showSecond: true,
    timeFormat: settings.timeFormat,
    timeOnlyTitle: settings.translate.timeOnlyTitle,
    timeText: settings.translate.timeText,
    hourText: settings.translate.hourText,
    minuteText: settings.translate.minuteText,
    secondText: settings.translate.secondText,
    currentText: settings.translate.currentText,
    closeText: settings.translate.closeText
  });

  $.datepicker.setDefaults($.datepicker.regional[settings.userLanguage]);
  $("#text-date_from, #text-date_to").datepicker({
    dateFormat: settings.dateFormat
  });
}*/

function refresh_pagination_callback(
  module_id,
  id_agent,
  server_name,
  module_name
) {
  $(".binary_dialog").click(function() {
    var classes = $(this).attr("class");
    classes = classes.split(" ");
    var offset_class = classes[2];
    offset_class = offset_class.split("_");
    var offset = offset_class[1];

    var period = $("#period").val();

    show_module_detail_dialog(
      module_id,
      id_agent,
      server_name,
      offset,
      period,
      module_name
    );
    return false;
  });
}

// eslint-disable-next-line no-unused-vars
function dashboardLoadVC(settings) {
  var container = document.getElementById(
    "visual-console-container-" + settings.cellId
  );

  // Add the datetime when the item was received.
  var receivedAt = new Date();

  var beforeUpdate = function(items, visualConsole, props) {
    // Add the datetime when the item was received.
    items.map(function(item) {
      item["receivedAt"] = receivedAt;
      return item;
    });

    var ratio_visualconsole = props.height / props.width;

    props.width = settings.size.width;
    props.height = settings.size.width * ratio_visualconsole;

    if (props.height > settings.size.height) {
      props.height = settings.size.height;
      props.width = settings.size.height / ratio_visualconsole;
    }

    // Update the data structure.
    visualConsole.props = props;
    // Update the items.
    visualConsole.updateElements(items);
  };

  var handleUpdate = function(prevProps, newProps) {
    if (!newProps) return;

    //Remove spinner change VC.
    document
      .getElementById("visual-console-container" + settings.cellId)
      .classList.remove("is-updating");

    var div = document
      .getElementById("visual-console-container" + settings.cellId)
      .querySelector(".div-visual-console-spinner");

    if (div !== null) {
      var parent = div.parentElement;
      if (parent !== null) {
        parent.removeChild(div);
      }
    }

    // Change the links.
    if (prevProps && prevProps.id !== newProps.id) {
      var regex = /(id=|id_visual_console=|id_layout=|id_visualmap=)\d+(&?)/gi;
      var replacement = "$1" + newProps.id + "$2";

      // Tab links.
      var menuLinks = document.querySelectorAll("div#menu_tab a");
      if (menuLinks !== null) {
        menuLinks.forEach(function(menuLink) {
          menuLink.href = menuLink.href.replace(regex, replacement);
        });
      }
    }
  };

  settings.items.map(function(item) {
    item["receivedAt"] = receivedAt;
    return item;
  });

  settings.items.map(function(item) {
    item["cellId"] = settings.cellId;
    return item;
  });

  createVisualConsole(
    container,
    settings.props,
    settings.items,
    settings.baseUrl,
    300 * 1000,
    handleUpdate,
    beforeUpdate,
    settings.size,
    settings.id_user,
    settings.hash
  );
}

// eslint-disable-next-line no-unused-vars
function dashboardShowEventDialog(settings) {
  settings = JSON.parse(atob(settings));
  var dialog_exist = $("div[aria-describedby='event_details_window']");
  if (dialog_exist.length == 1) {
    $("div[aria-describedby='event_details_window']").remove();
  }
  $.ajax({
    method: "post",
    url: settings.ajaxUrl,
    data: {
      page: settings.page,
      get_extended_event: 1,
      event: settings.event,
      dialog_page: "",
      meta: 0,
      history: 0,
      filter: []
    },
    dataType: "html",
    success: function(data) {
      $("#event_details_window")
        .hide()
        .empty()
        .append(data)
        .dialog({
          title: settings.event.evento,
          resizable: true,
          draggable: true,
          modal: true,
          close: function() {
            //$("#refrcounter").countdown("resume");
            //$("div.vc-countdown").countdown("resume");
          },
          overlay: {
            opacity: 0.5,
            background: "black"
          },
          width: 710,
          height: 600
        })
        .show();

      $.post({
        url: settings.ajaxUrl,
        data: {
          page: "include/ajax/events",
          get_comments: 1,
          event: settings.event,
          filter: []
        },
        dataType: "html",
        success: function(data) {
          $("#extended_event_comments_page").empty();
          $("#extended_event_comments_page").html(data);
        }
      });

      //$("#refrcounter").countdown("pause");
      //$("div.vc-countdown").countdown("pause");

      switch (settings.result) {
        case "comment_ok":
          $("#notification_comment_success").show();
          break;
        case "comment_error":
          $("#notification_comment_error").show();
          break;
        case "status_ok":
          $("#notification_status_success").show();
          break;
        case "status_error":
          $("#notification_status_error").show();
          break;
        case "owner_ok":
          $("#notification_owner_success").show();
          break;
        case "owner_error":
          $("#notification_owner_error").show();
          break;
      }

      forced_title_callback();
    },
    error: function(error) {
      console.error(error);
    }
  });
}

// eslint-disable-next-line no-unused-vars
function dashboardInitTinyMce(url) {
  // Initialice.
  tinyMCE.init({
    selector: "#textarea_text",
    theme: "advanced",
    content_css: url + "include/styles/barivion.css",
    theme_advanced_font_sizes:
      "4pt=.visual_font_size_4pt, " +
      "6pt=.visual_font_size_6pt, " +
      "8pt=.visual_font_size_8pt, " +
      "10pt=.visual_font_size_10pt, " +
      "12pt=.visual_font_size_12pt, " +
      "14pt=.visual_font_size_14pt, " +
      "18pt=.visual_font_size_18pt, " +
      "24pt=.visual_font_size_24pt, " +
      "28pt=.visual_font_size_28pt, " +
      "36pt=.visual_font_size_36pt, " +
      "48pt=.visual_font_size_48pt, " +
      "60pt=.visual_font_size_60pt, " +
      "72pt=.visual_font_size_72pt, " +
      "84pt=.visual_font_size_84pt, " +
      "96pt=.visual_font_size_96pt, " +
      "116pt=.visual_font_size_116pt, " +
      "128pt=.visual_font_size_128pt, " +
      "140pt=.visual_font_size_140pt, " +
      "154pt=.visual_font_size_154pt, " +
      "196pt=.visual_font_size_196pt",
    theme_advanced_toolbar_location: "top",
    theme_advanced_toolbar_align: "left",
    theme_advanced_buttons1:
      "bold,italic, |,justifyleft, justifycenter, justifyright, |, undo, redo, |, image, link",
    theme_advanced_buttons2: "fontselect, forecolor, fontsizeselect, |,code",
    theme_advanced_buttons3: "",
    theme_advanced_statusbar_location: "none",
    body_class: "",
    forced_root_block: false,
    force_p_newlines: false,
    force_br_newlines: true,
    convert_newlines_to_brs: false,
    remove_linebreaks: true
  });
}

function debounce(func, wait, immediate) {
  var timeout;
  return function() {
    var context = this,
      args = arguments;
    var later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}

// eslint-disable-next-line no-unused-vars
function formSlides(settings) {
  load_modal({
    target: $("#modal-update-dashboard"),
    form: "slides-form",
    url: settings.url_ajax,
    modal: {
      title: settings.title,
      cancel: settings.btn_cancel,
      ok: settings.btn_text
    },
    onshow: {
      page: settings.url,
      method: "formSlides",
      extradata: {
        dashboardId: settings.dashboardId
      },
      width: 250
    }
  });
}

// eslint-disable-next-line no-unused-vars
function loadSliceWidget(settings) {
  settings = JSON.parse(settings);
  var width = $(window).width();
  $.ajax({
    method: "post",
    url: settings.url,
    data: {
      page: settings.page,
      method: "drawWidget",
      dashboardId: settings.dashboardId,
      cellId: settings.cellId,
      newWidth: 12,
      newHeight: 12,
      gridWidth: width,
      widgetId: settings.widgetId
    },
    dataType: "html",
    success: function(dataWidget) {
      // Widget empty.
      $("#view-slides-cell-mode").empty();

      // Add Resize element.
      $("#view-slides-cell-mode").append(dataWidget);
    },
    error: function(error) {
      console.error(error);
    }
  });
}
