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
  var title = data.title;
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

            if (widgetData.includes('class="post-widget"')) {
              widgetData = widgetData.replace("<script", "&lt;script");
              widgetData = widgetData.replace("</script", "&lt;/script");
            }

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
              "<h3 style='text-align: center;padding-top: 20px;'>All changes made to this widget will be lost</h3>",
            cancel: "Cancel",
            ok: "Ok",
            size: 400,
            onAccept: function() {
              // Continue execution.
              var nodo = event.target.offsetParent;
              deleteCell(id, nodo.parentNode);
            }
          });
        });

        $("#configure-widget-" + id).click(function() {
          getSizeModalConfiguration(id, widgetId);
        });
      },
      error: function(error) {
        console.error(error);
      }
    });
  }

  function getSizeModalConfiguration(cellId, widgetId) {
    $.ajax({
      method: "post",
      url: data.url,
      data: {
        page: data.page,
        method: "getSizeModalConfiguration",
        dashboardId: data.dashboardId,
        cellId: cellId,
        widgetId: widgetId
      },
      dataType: "json",
      success: function(size) {
        configurationWidget(cellId, widgetId, size);
      },
      error: function(error) {
        console.log(error);
        return [];
      }
    });
    return false;
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

  function configurationWidget(cellId, widgetId, size) {
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
        width: size.width,
        minHeight: size.height
      },
      onsubmit: {
        page: data.page,
        method: "saveWidgetIntoCell",
        dataType: "json"
      },
      ajax_callback: update_widget_to_cell,
      onsubmitClose: 1
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
        title: data.title,
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
        $("a.pandora_pagination").click(function() {
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
              "<h3 style='text-align: center;padding-top: 20px;'>All changes made to this widget will be lost</h3>",
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
          getSizeModalConfiguration(cellId, widgetId);
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
      node: settings.node,
      dashboard: 1,
      size: settings.size
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
            },
            not_normal: {
              agents: settings.translate.not_normal.agents,
              modules: settings.translate.not_normal.modules,
              none: settings.translate.not_normal.none
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
  var headerMobileFix = 40;

  var container = document.getElementById(
    "visual-console-container-" + settings.cellId
  );

  var interval = 300 * 1000;

  // Add the datetime when the item was received.
  var receivedAt = new Date();

  var beforeUpdate = function(items, visualConsole, props, size) {
    var ratio_visualconsole = props.height / props.width;
    var ratio_w = size.width / props.width;
    var ratio_h = size.height / props.height;
    var acum_height = props.height;
    var acum_width = props.width;

    props.width = size.width;
    props.height = size.width * ratio_visualconsole;

    var ratio = ratio_w;
    if (settings.mobile != undefined && settings.mobile === true) {
      if (props.height < props.width) {
        if (props.height > size.height) {
          ratio = ratio_h;
          props.height = size.height;
          props.width = size.height / ratio_visualconsole;
        }
      } else {
        ratio = ratio_w;
        var height = (acum_height * size.width) / acum_width;
        props.height = height;
        props.width = height / ratio_visualconsole;
      }
    } else {
      if (props.height > size.height) {
        ratio = ratio_h;
        props.height = size.height;
        props.width = size.height / ratio_visualconsole;
      }
    }

    $.ajax({
      method: "post",
      url: settings.baseUrl + "ajax.php",
      data: {
        page: settings.page,
        load_css_cv: 1,
        uniq: settings.uniq,
        ratio: ratio
      },
      dataType: "html",
      success: function(css) {
        $("#css_cv_" + settings.uniq)
          .empty()
          .append(css);

        // Add the datetime when the item was received.
        items.map(function(item) {
          item["receivedAt"] = receivedAt;
          return item;
        });

        // Update the data structure.
        visualConsole.props = props;

        // Update the items.
        visualConsole.updateElements(items);

        //Remove spinner change VC.
        container.classList.remove("is-updating");
        var div = container.querySelector(".div-visual-console-spinner");

        if (div !== null) {
          var parent = div.parentElement;
          if (parent !== null) {
            parent.removeChild(div);
          }
        }

        if (settings.mobile != undefined && settings.mobile === true) {
          // Update Url.
          var regex = /(id=|id_visual_console=|id_layout=|id_visualmap=)\d+(&?)/gi;
          var replacement = "$1" + props.id + "$2";

          var regex_hash = /(hash=)[^&]+(&?)/gi;
          var replacement_hash = "$1" + props.hash + "$2";

          /*
          var regex_width = /(width=)[^&]+(&?)/gi;
          var replacement_width = "$1" + size.width + "$2";

          var regex_height = /(height=)[^&]+(&?)/gi;
          var replacement_height =
            "$1" + (size.height + headerMobileFix) + "$2";
            */

          // Change the URL (if the browser has support).
          if ("history" in window) {
            var href = window.location.href.replace(regex, replacement);
            href = href.replace(regex_hash, replacement_hash);
            //href = href.replace(regex_width, replacement_width);
            //href = href.replace(regex_height, replacement_height);
            window.history.replaceState({}, document.title, href);
          }

          if (props.height > props.width) {
            $(".container-center").css("overflow", "auto");
          } else {
            $(".container-center").css("overflow", "inherit");
          }

          container.classList.remove("cv-overflow");

          // View title.
          var title = document.querySelector(".ui-title");
          if (title !== null) {
            title.textContent = visualConsole.props.name;
          }
        }
      },
      error: function(error) {
        console.error(error);
      }
    });
  };

  var handleUpdate = function() {
    return;
  };

  settings.items.map(function(item) {
    item["receivedAt"] = receivedAt;
    return item;
  });

  settings.items.map(function(item) {
    item["cellId"] = settings.cellId;
    return item;
  });

  var visualConsoleManager = createVisualConsole(
    container,
    settings.props,
    settings.items,
    settings.baseUrl,
    interval,
    handleUpdate,
    beforeUpdate,
    settings.size,
    settings.id_user,
    settings.hash,
    settings.mobile != undefined && settings.mobile === true
      ? "mobile"
      : "dashboard"
  );

  if (settings.props.maintenanceMode != null) {
    visualConsoleManager.visualConsole.enableMaintenanceMode();
  }

  if (settings.mobile_view_orientation_vc === true) {
    $(window).on("orientationchange", function() {
      $(container).width($(window).height());
      $(container).height($(window).width() - headerMobileFix);
      //Remove spinner change VC.
      container.classList.remove("is-updating");
      container.classList.remove("cv-overflow");

      var div = container.querySelector(".div-visual-console-spinner");

      if (div !== null) {
        var parent = div.parentElement;
        if (parent !== null) {
          parent.removeChild(div);
        }
      }

      container.classList.add("is-updating");
      container.classList.add("cv-overflow");
      const divParent = document.createElement("div");
      divParent.className = "div-visual-console-spinner";

      const divSpinner = document.createElement("div");
      divSpinner.className = "visual-console-spinner";

      divParent.appendChild(divSpinner);
      container.appendChild(divParent);

      var dimensions = {
        width: $(window).height(),
        height: $(window).width() - 40
      };

      visualConsoleManager.changeDimensionsVc(dimensions, interval);
    });
  }
}

// eslint-disable-next-line no-unused-vars
function dashboardInitTinyMce(url) {
  // Initialice.
  UndefineTinyMCE("#textarea_text");
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

// eslint-disable-next-line no-unused-vars
function showManualThresholds(element) {
  $("#min_warning").val(null);
  $("#max_warning").val(null);
  $("#min_critical").val(null);
  $("#max_critical").val(null);
  if ($(element).is(":checked") === true) {
    $(".dashboard-input-threshold-warning").removeClass("invisible_important");
    $(".dashboard-input-threshold-critical").removeClass("invisible_important");
  } else {
    $(".dashboard-input-threshold-warning").addClass("invisible_important");
    $(".dashboard-input-threshold-critical").addClass("invisible_important");
  }
}

/**
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function type_change() {
  var type = document.getElementById("type").value;

  switch (type) {
    case "2":
      $("#li_tags").hide();
      $("#li_groups").hide();
      $("#li_module_groups").show();
      break;

    case "1":
      $("#li_tags").show();
      $("#li_groups").hide();
      $("#li_module_groups").hide();
      break;

    default:
    case "3":
    case "0":
      $("#li_tags").hide();
      $("#li_groups").show();
      $("#li_module_groups").hide();
      break;
  }
}
