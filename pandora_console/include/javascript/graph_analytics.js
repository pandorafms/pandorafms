/* global $ */
// Droppable options.
var droppableOptions = {
  accept: ".draggable",
  hoverClass: "drops-hover",
  activeClass: "droppable-zone",
  drop: function(event, ui) {
    // Add new module.
    $(this)
      .data("modules")
      .push(ui.draggable.data("id-module"));

    // Create elements.
    createDroppableZones(droppableOptions, getModulesByGraphs());
  }
};

// Doc ready.
$(document).ready(function() {
  // Hide toggles.
  $("#agents-toggle").hide();
  $("#groups-toggle").hide();
  $("#modules-toggle").hide();
  $("[data-button=pause-realtime]")
    .parent()
    .hide();

  // Set droppable zones.
  $(".droppable").droppable(droppableOptions);
});

// Interval change.
$("#interval").change(function(e) {
  createDroppableZones(droppableOptions, getModulesByGraphs());
});

// Collapse filters.
$("div.filters-div-main > .filters-div-header > img").click(function(e) {
  if ($(".filters-div-main").hasClass("filters-div-main-collapsed") === true) {
    $(".filters-div-header > img").attr("src", "images/menu/contraer.svg");
    $(".filters-div-main").removeClass("filters-div-main-collapsed");
    $("#droppable-graphs").removeClass("droppable-graphs-collapsed");
  } else {
    $(".filters-div-header > img").attr("src", "images/menu/expandir.svg");
    $(".filters-div-main").addClass("filters-div-main-collapsed");
    $("#droppable-graphs").addClass("droppable-graphs-collapsed");
  }
});

// Search left.
$("#search-left").keyup(function(e) {
  if ($(this).val() !== "") {
    $.ajax({
      method: "POST",
      url: "ajax.php",
      dataType: "json",
      data: {
        page: "operation/reporting/graph_analytics",
        search_left: e.target.value
      },
      success: function(data) {
        if (data.agents || data.groups || data.modules) {
          var agentsToggle = $(
            "#agents-toggle > div[id^=tgl_div_] > div.white-box-content"
          );
          var groupsToggle = $(
            "#groups-toggle > div[id^=tgl_div_] > div.white-box-content"
          );
          var modulesToggle = $(
            "#modules-toggle > div[id^=tgl_div_] > div.white-box-content"
          );
          agentsToggle.empty();
          groupsToggle.empty();
          modulesToggle.empty();

          if (data.agents) {
            $("#agents-toggle").show();
            data.agents.forEach(agent => {
              agentsToggle.append(
                `<div onclick="clickAgent(event.target);" data-id-agent="${agent.id_agente}" title="${agent.alias}">${agent.alias}</div>`
              );
            });
          } else {
            $("#agents-toggle").hide();
          }

          if (data.groups) {
            $("#groups-toggle").show();
            data.groups.forEach(group => {
              groupsToggle.append(
                `<div onclick="clickGroup(event.target);" data-id-group="${group.id_grupo}" title="${group.nombre}">${group.nombre}</div>`
              );
            });
          } else {
            $("#groups-toggle").hide();
          }

          if (data.modules) {
            $("#modules-toggle").show();
            data.modules.forEach(module => {
              modulesToggle.append(
                `<div class="draggable draggable-container" data-id-module="${module.id_agente_modulo}" title="${module.nombre}">
                  <img class="draggable-icon" src="images/draggable.svg">
                  <div class="draggable-module">
                    <span class="draggable-module-name">${module.nombre}</span>
                    <span class="draggable-agent-name">${module.alias}</span>
                  </div>
                </div>`
              );
            });
          } else {
            $("#modules-toggle").hide();
          }

          // Create draggable elements.
          $(".draggable").draggable({
            revert: "invalid",
            stack: ".draggable",
            helper: "clone"
          });
        } else {
          $("#agents-toggle").hide();
          $("#groups-toggle").hide();
          $("#modules-toggle").hide();
        }
      }
    });
  } else {
    $("#agents-toggle").hide();
    $("#groups-toggle").hide();
    $("#modules-toggle").hide();
  }
});

function clickAgent(e) {
  $("#search-agent").val($(e).data("id-agent"));
  $("#search-group").val("");
  searchRight($("#search-right").val());
}

function clickGroup(e) {
  $("#search-group").val($(e).data("id-group"));
  $("#search-agent").val("");
  searchRight($("#search-right").val());
}

// Search right.
$("#search-right").keyup(function(e) {
  if ($("#search-right") !== "") {
    searchRight(e.target.value);
  }
});

function searchRight(freeSearch) {
  $.ajax({
    method: "POST",
    url: "ajax.php",
    dataType: "json",
    data: {
      page: "operation/reporting/graph_analytics",
      search_right: true,
      free_search: freeSearch,
      search_agent: $("#search-agent").val(),
      search_group: $("#search-group").val()
    },
    success: function(data) {
      var modulesRight = $("#modules-right");

      if (data.modules) {
        modulesRight.empty();

        data.modules.forEach(module => {
          modulesRight.append(
            `<div class="draggable draggable-container" data-id-module="${module.id_agente_modulo}" title="${module.nombre}">
              <img class="draggable-icon" src="images/draggable.svg">
              <div class="draggable-module">
                <span class="draggable-module-name">${module.nombre}</span>
                <span class="draggable-agent-name">${module.alias}</span>
              </div>
            </div>`
          );
        });

        // Create draggable elements.
        $(".draggable").draggable({
          revert: "invalid",
          stack: ".draggable",
          helper: "clone"
        });
      } else {
        modulesRight.empty();
        console.error("NO DATA FOUND");
      }
    }
  });
}

function createDroppableZones(
  droppableOptions,
  modulesByGraphs,
  homeurl = "",
  getInterval = 0
) {
  var url = "ajax.php";
  var interval = $("#interval").val();

  if (homeurl !== "") {
    url = homeurl + "/ajax.php";
  }

  if (getInterval !== 0) {
    interval = getInterval;
  }

  // Clear graph area.
  $("#droppable-graphs").empty();

  // Reset realtime data.
  realtimeGraphs = [];

  // Graph modules.
  modulesByGraphs
    .slice()
    .reverse()
    .forEach(graph => {
      // Max modules by graph.
      var droppableClass = "";
      if (graph.length < 2) {
        droppableClass = "droppable";
      }

      // Create graph div.
      var graphDiv = $(
        `<div class="${droppableClass}" data-modules="[${graph}]"></div>`
      );
      $("#droppable-graphs").prepend(graphDiv);

      // Print graphs.
      $.ajax({
        method: "POST",
        url,
        dataType: "html",
        data: {
          page: "operation/reporting/graph_analytics",
          get_graphs: graph,
          interval
        },
        success: function(data) {
          if (data) {
            graphDiv.append(
              $(
                `<div class="draggable ui-draggable ui-draggable-handle">${data}</div>`
              )
            );

            if (
              $("#hidden-section").val() ===
              "operation/reporting/graph_analytics"
            ) {
              // Create remove button.
              if (
                graphDiv
                  .children()
                  .children()
                  .hasClass("parent_graph") === true
              ) {
                graphDiv
                  .children()
                  .children()
                  .children(":first-child")
                  .prepend(
                    $(
                      '<img src="images/delete.svg" class="remove-graph-analytics" onclick="removeGraph(this);">'
                    )
                  );
              } else {
                graphDiv
                  .children()
                  .append(
                    $(
                      '<img src="images/delete.svg" class="remove-graph-analytics" onclick="removeGraph(this);">'
                    )
                  );
              }
            }
          }
        }
      });

      // Create next droppable zone.
      graphDiv.after(
        $(
          `<div class="droppable droppable-default-zone droppable-new" data-modules="[]"><span class="drop-here">${dropHere}<span></div>`
        )
      );
    });

  // Create first droppable zones and graphs.
  $("#droppable-graphs").prepend(
    $(
      `<div class="droppable droppable-default-zone droppable-new" data-modules="[]"><span class="drop-here">${dropHere}<span></div>`
    )
  );

  // Set droppable zones.
  $(".droppable").droppable(droppableOptions);
}

function getModulesByGraphs() {
  var modulesByGraphs = [];
  $("#droppable-graphs > div").each(function(i, v) {
    var modulesTmp = $(v).data("modules");
    if (modulesTmp.length > 0) {
      modulesByGraphs.push(modulesTmp);
    }
  });

  return modulesByGraphs;
}

function realtimeGraph() {
  realtimeGraphsTmp = realtimeGraphs;
  realtimeGraphs = [];

  realtimeGraphsTmp.forEach(graph => {
    $.each(graph.series_type, function(i, v) {
      // Get new values.
      $.ajax({
        method: "POST",
        url: "ajax.php",
        dataType: "json",
        data: {
          page: "operation/reporting/graph_analytics",
          get_new_values: graph.values[i].agent_module_id,
          date_array: graph.date_array,
          data_module_graph: graph.data_module_graph,
          params: graph.params,
          suffix: i.slice(-1)
        },
        success: function(data) {
          if (data) {
            // Set new values
            graph.values[i].data = data[i].data;
          }
        }
      });
    });

    // New periods.
    var period = $("#interval").val();
    var time = Math.floor(Date.now() / 1000);

    graph.params.date = time;

    var date_array = {
      period,
      final_date: time,
      start_date: time - period
    };

    pandoraFlotArea(
      graph.graph_id,
      graph.values,
      graph.legend,
      graph.series_type,
      graph.color,
      date_array,
      graph.data_module_graph,
      graph.params,
      graph.events_array
    );
  });
}

// Action buttons.
// Start/Pause Realtime.
var realtimeGraphInterval;
$("[data-button=pause-realtime]")
  .parent()
  .hide();

$("[data-button=start-realtime]").click(function(e) {
  $("[data-button=start-realtime]")
    .parent()
    .hide();
  $("[data-button=pause-realtime]")
    .parent()
    .show();

  realtimeGraphInterval = setInterval(realtimeGraph, 5000);
});

$("[data-button=pause-realtime]").click(function(e) {
  $("[data-button=pause-realtime]")
    .parent()
    .hide();
  $("[data-button=start-realtime]")
    .parent()
    .show();

  clearInterval(realtimeGraphInterval);
});

// New graph.
$("[data-button=new]").click(function(e) {
  confirmDialog({
    title: titleNew,
    message: messageNew,
    onAccept: function() {
      $("#droppable-graphs").empty();

      // Create graph div.
      $("#droppable-graphs").prepend(
        $(
          `<div class="droppable droppable-default-zone ui-droppable" data-modules="[]"><span class="drop-here">${dropHere}<span></div>`
        )
      );
      $(".droppable").droppable(droppableOptions);

      // Reset realtime button.
      $("[data-button=pause-realtime]")
        .parent()
        .hide();
      $("[data-button=start-realtime]")
        .parent()
        .show();
    }
  });
});

function updateSelect(element, fields, selected) {
  if (typeof fields === "object") {
    $(element)
      .find("select option[value!=0]")
      .remove();
    $(element)
      .find(".select2-container .select2-selection__rendered")
      .empty();
    Object.keys(fields).forEach(function(key) {
      if (key === selected) {
        $(element)
          .find(".select2-container .select2-selection__rendered")
          .append(`${fields[key]}`);
        $(element)
          .find("select")
          .append(`<option value="${key}" selected>${fields[key]}</option>`);
      } else {
        $(element)
          .find("select")
          .append(`<option value="${key}">${fields[key]}</option>`);
      }
    });
  }
}

// Save graps modal.
$("[data-button=save]").click(function(e) {
  // Filter save mode selector
  $("#save_filter_row1").show();
  $("#save_filter_row2").show();
  $("#update_filter_row1").hide();
  $("#delete_filter_row2").hide();
  $("#radiobtn0002").prop("checked", false);
  $("#radiobtn0001").prop("checked", true);
  $("#text-id_name").val("");

  $("[name='filter_mode']").click(function() {
    if ($(this).val() == "new") {
      $("#save_filter_row1").show();
      $("#save_filter_row2").show();
      $("#submit-save_filter").show();
      $("#update_filter_row1").hide();
      $("#delete_filter_row2").hide();
    } else if ($(this).val() == "update") {
      $("#save_filter_row1").hide();
      $("#save_filter_row2").hide();
      $("#update_filter_row1").show();
      $("#submit-save_filter").hide();
      $("#delete_filter_row2").hide();
    } else {
      $("#save_filter_row1").hide();
      $("#save_filter_row2").hide();
      $("#update_filter_row1").hide();
      $("#submit-save_filter").hide();
      $("#delete_filter_row2").show();
    }
  });

  $.ajax({
    method: "POST",
    url: "ajax.php",
    dataType: "json",
    data: {
      page: "operation/reporting/graph_analytics",
      load_list_filters: 1
    },
    success: function(data) {
      if (data) {
        updateSelect("#save_filter_form", data, 0);
        $("#save-filter-select").dialog({
          resizable: true,
          draggable: true,
          modal: false,
          closeOnEscape: true,
          width: 350,
          title: titleModalActions
        });
      }
    }
  });
});

// Save filter button.
function save_new_filter() {
  $.ajax({
    method: "POST",
    url: "ajax.php",
    dataType: "html",
    data: {
      page: "operation/reporting/graph_analytics",
      save_filter: $("#text-id_name").val(),
      graphs: getModulesByGraphs(),
      interval: $("#interval").val()
    },
    success: function(data) {
      if (data == "saved") {
        confirmDialog({
          title: titleSave,
          message: messageSave,
          hideCancelButton: true,
          onAccept: function() {
            $(
              "button.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close"
            ).click();
          }
        });
      } else {
        var message = messageSaveEmpty;
        if (data === "") {
          message = messageSaveEmptyName;
        }
        confirmDialog({
          title: titleError,
          message,
          hideCancelButton: true
        });
      }
    }
  });
}

// Update filter button.
function save_update_filter() {
  confirmDialog({
    title: titleUpdate,
    message: messageUpdate,
    onAccept: function() {
      $.ajax({
        method: "POST",
        url: "ajax.php",
        dataType: "html",
        data: {
          page: "operation/reporting/graph_analytics",
          update_filter: $("#overwrite_filter").val(),
          graphs: getModulesByGraphs(),
          interval: $("#interval").val()
        },
        success: function(data) {
          if (data == "updated") {
            confirmDialog({
              title: titleUpdateConfirm,
              message: messageUpdateConfirm,
              hideCancelButton: true,
              onAccept: function() {
                $(
                  "button.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close"
                ).click();
              }
            });
          } else {
            confirmDialog({
              title: titleUpdateError,
              message: messageUpdateError,
              hideCancelButton: true
            });
          }
        }
      });
    }
  });
}

// Delete filter.
function delete_filter() {
  confirmDialog({
    title: titleDelete,
    message: messageDelete,
    onAccept: function() {
      $.ajax({
        method: "POST",
        url: "ajax.php",
        dataType: "html",
        data: {
          page: "operation/reporting/graph_analytics",
          delete_filter: $("#delete_filter").val()
        },
        success: function(data) {
          if (data == "deleted") {
            confirmDialog({
              title: titleDeleteConfirm,
              message: messageDeleteConfirm,
              hideCancelButton: true,
              onAccept: function() {
                $(
                  "button.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close"
                ).click();
              }
            });
          } else {
            confirmDialog({
              title: titleDeleteError,
              message: messageDeleteError,
              hideCancelButton: true
            });
          }
        }
      });
    }
  });
}

// Load graps modal.
$("[data-button=load]").click(function(e) {
  $.ajax({
    method: "POST",
    url: "ajax.php",
    dataType: "json",
    data: {
      page: "operation/reporting/graph_analytics",
      load_list_filters: 1
    },
    success: function(data) {
      if (data) {
        updateSelect("#load_filter_form", data, 0);
        $("#load-filter-select").dialog({
          resizable: true,
          draggable: true,
          modal: false,
          closeOnEscape: true,
          width: "auto"
        });
      }
    }
  });
});

// Load filter button.
function load_filter_values(id = 0, homeurl) {
  var url = "ajax.php";
  var filterId = $("#filter_id").val();

  if (id !== 0) {
    filterId = id;
    url = homeurl + "/ajax.php";
  }

  if (id === 0) {
    confirmDialog({
      title: titleLoad,
      message: messageLoad,
      onAccept: function() {
        loadFilter(url, filterId, homeurl, id);
      }
    });
  } else {
    loadFilter(url, filterId, homeurl, id);
  }
}

function loadFilter(url, filterId, homeurl, id) {
  $.ajax({
    method: "POST",
    url,
    dataType: "json",
    data: {
      page: "operation/reporting/graph_analytics",
      load_filter: filterId
    },
    success: function(data) {
      if (data) {
        createDroppableZones(
          droppableOptions,
          data.graphs,
          homeurl,
          data.interval
        );
        if (id === 0) {
          $("#interval")
            .val(data.interval)
            .trigger("change");
        }

        $(
          "button.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close"
        ).click();

        // Reset realtime button.
        if (id === 0) {
          $("[data-button=pause-realtime]")
            .parent()
            .hide();
          $("[data-button=start-realtime]")
            .parent()
            .show();
        }
      } else {
        confirmDialog({
          title: titleLoadConfirm,
          message: messageLoadConfirm,
          hideCancelButton: true
        });
      }
    }
  });
}

// Share button.
$("[data-button=share]").click(function(e) {
  $.ajax({
    method: "POST",
    url: "ajax.php",
    dataType: "json",
    data: {
      page: "operation/reporting/graph_analytics",
      load_list_filters: 1
    },
    success: function(data) {
      if (data) {
        updateSelect("#share_form-0-0", data, 0);
        $("#share-select").dialog({
          resizable: true,
          draggable: true,
          modal: false,
          closeOnEscape: true,
          width: "auto"
        });
      }
    }
  });
});

$("#button-share-modal").click(function(e) {
  const hash = $("#hash_share").val();
  const idFilter = btoa($("#share-id").val());
  const idUser = $("#id_user").val();

  const queryParams = "hash=" + hash + "&id=" + idFilter + "&id_user=" + idUser;

  window.open(
    configHomeurl +
      "operation/reporting/graph_analytics_public.php?" +
      queryParams
  );
});

// Export button.
$("[data-button=export]").click(function(e) {
  $.ajax({
    method: "POST",
    url: "ajax.php",
    dataType: "json",
    data: {
      page: "operation/reporting/graph_analytics",
      load_list_filters: 1
    },
    success: function(data) {
      if (data) {
        updateSelect("#export_form-0-0", data, 0);
        $("#export-select").dialog({
          resizable: true,
          draggable: true,
          modal: false,
          closeOnEscape: true,
          width: "auto",
          title: titleExport
        });
      }
    }
  });
});

// Export graph.
function exportCustomGraph() {
  const filter = parseInt($("#export-filter-id").val());
  const group = parseInt($("#export-group-id").val());

  if (filter !== 0) {
    $.ajax({
      method: "POST",
      url: "ajax.php",
      dataType: "html",
      data: {
        page: "operation/reporting/graph_analytics",
        export_filter: filter,
        group
      },
      success: function(data) {
        if (data === "created") {
          confirmDialog({
            title: titleExportConfirm,
            message: messageExportConfirm,
            hideCancelButton: true,
            onAccept: function() {
              $(
                "button.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close"
              ).click();
            }
          });
        }
      }
    });
  } else {
    confirmDialog({
      title: titleExportError,
      message: messageExportError,
      hideCancelButton: true,
      onAccept: function() {
        $(
          "button.ui-button.ui-corner-all.ui-widget.ui-button-icon-only.ui-dialog-titlebar-close"
        ).click();
      }
    });
  }
}

// Remove graph.
function removeGraph(e) {
  confirmDialog({
    title: titleRemoveConfirm,
    message: messageRemoveConfirm,
    onAccept: function() {
      if (
        $(e)
          .parent()
          .hasClass("menu_graph") === true
      ) {
        $(e)
          .parent()
          .parent()
          .parent()
          .parent()
          .remove();
      } else {
        $(e)
          .parent()
          .parent()
          .remove();
      }
    }
  });
}
