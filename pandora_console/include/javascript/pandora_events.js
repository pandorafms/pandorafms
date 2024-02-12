/*global jQuery, $, forced_title_callback, confirmDialog, progressTimeBar, checkExistParameterUrl*/

// Show the modal window of an event
function show_event_dialog(event, dialog_page) {
  var ajax_file = getUrlAjax();

  var view = ``;

  if ($("#event_details_window").length) {
    view = "#event_details_window";
  } else if ($("#sound_event_details_window").length) {
    view = "#sound_event_details_window";
  }

  if (dialog_page == undefined) {
    dialog_page = "general";
  }

  try {
    event = JSON.parse(atob(event));
  } catch (e) {
    console.error(e);
    return;
  }

  var inputs = $("#events_form :input");
  var values = {};
  inputs.each(function() {
    values[this.name] = $(this).val();
  });

  // Metaconsole mode flag
  var meta = $("#hidden-meta").val();

  // History mode flag
  var history = $("#hidden-history").val();

  jQuery.post(
    ajax_file,
    {
      page: "include/ajax/events",
      get_extended_event: 1,
      dialog_page: dialog_page,
      event: event,
      meta: meta,
      history: history,
      filter: values
    },
    function(data) {
      $(view)
        .hide()
        .empty()
        .append(data)
        .dialog({
          title: event.event_title,
          resizable: true,
          draggable: true,
          modal: false,
          minWidth: 875,
          minHeight: 600,
          close: function() {
            $("#refrcounter").countdown("resume");
            $("div.vc-countdown").countdown("resume");
          },
          overlay: {
            opacity: 0.5,
            background: "black"
          },
          width: 710,
          height: 650,
          autoOpen: true,
          open: function() {
            if (
              $.ui &&
              $.ui.dialog &&
              $.ui.dialog.prototype._allowInteraction
            ) {
              var ui_dialog_interaction =
                $.ui.dialog.prototype._allowInteraction;
              $.ui.dialog.prototype._allowInteraction = function(e) {
                if ($(e.target).closest(".select2-dropdown").length)
                  return true;
                return ui_dialog_interaction.apply(this, arguments);
              };
            }
          },
          _allowInteraction: function(event) {
            return !!$(event.target).is(".select2-input") || this._super(event);
          }
        })
        .show();

      $("#refrcounter").countdown("pause");
      $("div.vc-countdown").countdown("pause");

      forced_title_callback();
    },
    "html"
  );
  return false;
}

// Check the response type and open it in a modal dialog or new window
function execute_response(event_id, server_id) {
  var response_id = $("#select_custom_response option:selected").val();

  var response_parameters_list = $('input[name^="values_params_"]');
  var response_parameters = [];
  if (response_parameters_list.length > 0) {
    response_parameters_list.each(function() {
      var acum = {
        name: $(this).attr("name"),
        value: $(this).val()
      };
      response_parameters.push(acum);
    });
  }

  var params = [];
  params.push({ name: "page", value: "include/ajax/events" });
  params.push({ name: "get_response", value: 1 });
  params.push({ name: "response_id", value: response_id });
  params.push({ name: "server_id", value: server_id });
  params.push({ name: "event_id", value: event_id });
  params.push({
    name: "response_parameters",
    value: JSON.stringify(response_parameters)
  });

  jQuery.ajax({
    data: params,
    type: "POST",
    url: getUrlAjax(),
    dataType: "json",
    success: function(response) {
      // If cannot get response abort it
      if (response == null) {
        return [];
      }

      response["event_id"] = event_id;
      response["server_id"] = server_id;
      if (response["type"] == "url" && response["new_window"] == 1) {
        window.open(response["target"], "_blank");
      } else {
        show_response_dialog(response_id, response);
      }
    }
  });
}

// Check the response type and open it in a modal dialog or new window
function execute_response_massive(events, response_id, response_parameters) {
  var params = [];
  params.push({ name: "page", value: "include/ajax/events" });
  params.push({ name: "get_response_massive", value: 1 });
  params.push({ name: "response_id", value: response_id });
  params.push({ name: "events", value: JSON.stringify(events) });
  params.push({ name: "response_parameters", value: response_parameters });

  jQuery.ajax({
    data: params,
    type: "POST",
    url: getUrlAjax(),
    dataType: "json",
    success: function(data) {
      // If cannot get response abort it
      if (data == null) {
        return [];
      }

      $(".container-massive-events-response").empty();

      // Convert to array.
      var array_data = Object.entries(data.event_response_targets);
      var total_count = array_data.length;

      // Each input checkeds.
      array_data.forEach(function(element, index) {
        var id = element[0];
        var target = element[1].target;
        var meta = $("#hidden-meta").val();
        var event_id = id;
        var server_id = 0;
        if (meta != 0) {
          var split_id = id.split("|");
          event_id = split_id[0];
          server_id = split_id[1];
        }

        var end = 0;
        if (total_count - 1 === index) {
          end = 1;
        }

        var response = data.event_response;
        response["event_id"] = event_id;
        response["server_id"] = server_id;
        response["target"] = target;
        if (response["type"] == "url" && response["new_window"] == 1) {
          window.open(response["target"], "_blank");
        } else {
          var params = [];
          params.push({ name: "page", value: "include/ajax/events" });
          params.push({ name: "get_row_response_action", value: 1 });
          params.push({ name: "response_id", value: response_id });
          params.push({ name: "server_id", value: response.server_id });
          params.push({ name: "end", value: end });
          params.push({ name: "response", value: JSON.stringify(response) });

          jQuery.ajax({
            data: params,
            type: "POST",
            url: getUrlAjax(),
            dataType: "html",
            success: function(data) {
              $(".container-massive-events-response").append(data);
              response["event_id"] = event_id;
              response["server_id"] = server_id;
              response["target"] = target;

              var indexstr = event_id;
              if (meta != 0) {
                indexstr += "-" + server_id;
              }

              perform_response(
                btoa(JSON.stringify(response)),
                response_id,
                indexstr
              );
            }
          });
        }
      });
    }
  });
}

//Show the modal window of an event response
function show_response_dialog(response_id, response) {
  var params = [];
  params.push({ name: "page", value: "include/ajax/events" });
  params.push({ name: "dialogue_event_response", value: 1 });
  params.push({ name: "event_id", value: response.event_id });
  params.push({ name: "target", value: response.target });
  params.push({ name: "response_id", value: response_id });
  params.push({ name: "server_id", value: response.server_id });
  params.push({ name: "response", value: JSON.stringify(response) });

  var view = ``;

  if ($("#event_response_window").length) {
    view = "#event_response_window";
  } else if ($("#sound_event_response_window").length) {
    view = "#sound_event_response_window";
  }

  jQuery.ajax({
    data: params,
    type: "POST",
    url: getUrlAjax(),
    dataType: "html",
    success: function(data) {
      $(view)
        .hide()
        .empty()
        .append(data)
        .dialog({
          title: $("#select_custom_response option:selected").html(),
          resizable: true,
          draggable: true,
          modal: false,
          open: function() {
            perform_response(btoa(JSON.stringify(response)), response_id, "");
          },
          width: response["modal_width"],
          height: response["modal_height"],
          buttons: []
        })
        .show();
    }
  });
}

// Perform a response and put the output into a div
function perform_response(response, response_id, index = "") {
  $("#re_exec_command" + index).hide();
  $("#response_loading_command" + index).show();
  $("#response_out" + index).html("");

  try {
    response = JSON.parse(atob(response));
  } catch (e) {
    console.error(e);
    return;
  }

  var params = [];
  params.push({ name: "page", value: "include/ajax/events" });
  params.push({ name: "perform_event_response", value: 1 });
  params.push({ name: "target", value: response["target"] });
  params.push({ name: "response_id", value: response_id });
  params.push({ name: "event_id", value: response["event_id"] });
  params.push({ name: "server_id", value: response["server_id"] });
  params.push({ name: "response", value: JSON.stringify(response) });

  jQuery.ajax({
    data: params,
    type: "POST",
    url: getUrlAjax(),
    dataType: "html",
    success: function(data) {
      var out = data.replace(/[\n|\r]/g, "<br>");
      $("#response_out" + index).html(out);
      $("#response_loading_command" + index).hide();
      $("#re_exec_command" + index).show();
    }
  });

  return false;
}

// Change the status of an event to new, in process or validated.
function event_change_status(event_ids, server_id, group_rep) {
  var new_status = $("#estado").val();

  $("#button-status_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: {
      page: "include/ajax/events",
      change_status: 1,
      event_ids: event_ids,
      new_status: new_status,
      server_id: server_id,
      group_rep: group_rep
    },
    type: "POST",
    url: getUrlAjax(),
    dataType: "json",
    success: function(data) {
      $("#button-status_button").removeAttr("disabled");
      $("#response_loading").hide();

      if ($("#notification_status_success").length) {
        $("#notification_status_success").hide();
      }

      if ($("#notification_status_error").length) {
        $("#notification_status_error").hide();
      }

      if (data.status == "status_ok") {
        $.ajax({
          type: "POST",
          url: "ajax.php",
          data: {
            page: "include/ajax/events",
            get_Acknowledged: 1,
            event_id: event_ids,
            server_id: server_id
          },
          success: function(response) {
            $(".general_acknowleded").html(response);
          }
        });

        if ($("#table_events").length) {
          $("#table_events")
            .DataTable()
            .draw(false);
        }

        $("#notification_status_success").show();

        $("#general_status")
          .find(".general_status")
          .text(data.status_title);
        $("#general_status")
          .find("img")
          .attr("src", data.status_img);
      } else {
        $("#notification_status_error").show();
      }
    }
  });
  return false;
}

// Change te owner of an event to one user of empty
function event_change_owner(event_id, server_id) {
  var new_owner = $("#id_owner").val();

  $("#button-owner_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: {
      page: "include/ajax/events",
      change_owner: 1,
      event_id: event_id,
      server_id: server_id,
      new_owner: new_owner
    },
    type: "POST",
    url: getUrlAjax(),
    async: true,
    dataType: "html",
    success: function(data) {
      $("#button-owner_button").removeAttr("disabled");
      $("#response_loading").hide();

      if ($("#notification_owner_success").length) {
        $("#notification_owner_success").hide();
      }

      if ($("#notification_owner_error").length) {
        $("#notification_owner_error").hide();
      }

      if (data == "owner_ok") {
        // if (typeof dt_events !== "undefined") {
        //   dt_events.draw(false);
        // }

        if ($("#table_events").length) {
          $("#table_events")
            .DataTable()
            .draw(false);
        }
        $("#notification_owner_success").show();
        if (new_owner == -1) {
          $("#extended_event_general_page table td.general_owner").html(
            "<i>N/A</i>"
          );
        } else {
          $("#extended_event_general_page table td.general_owner").text(
            new_owner
          );
        }
      } else {
        $("#notification_owner_error").show();
      }
    }
  });

  return false;
}

// Save a comment into an event
function event_comment(current_event) {
  var event;
  try {
    event = JSON.parse(atob(current_event));
  } catch (e) {
    console.error(e);
    return;
  }

  var comment = $("#textarea_comment").val();

  if (comment == "") {
    show_event_dialog(current_event, "comments", "comment_error");
    return false;
  }

  var params = {
    page: "include/ajax/events",
    add_comment: 1,
    event_id: event.event_rep > 0 ? event.max_id_evento : event.id_evento,
    comment: comment,
    server_id: event.server_id
  };

  $("#button-comment_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: params,
    type: "POST",
    url: getUrlAjax(),
    dataType: "html",
    success: function() {
      $("#button-comment_button").removeAttr("disabled");
      $("#response_loading").hide();
      $("#button-filter_comments_button").click();
    }
  });

  return false;
}

// Save custom_field into an event.
function update_event_custom_id(event_id, server_id) {
  var event_custom_id = $("#text-event_custom_id").val();

  var params = {
    page: "include/ajax/events",
    update_event_custom_id: 1,
    event_custom_id: event_custom_id,
    event_id: event_id,
    server_id: server_id
  };

  $("#button-update_custom_field").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: params,
    type: "POST",
    url: getUrlAjax(),
    dataType: "html",
    success: function(data) {
      if (data === "update_error") {
        alert("Event Custom ID not valid");
      }
      $("#button-update_custom_field").removeAttr("disabled");
      $("#response_loading").hide();
      $("#button-events_form_search_bt").trigger("click");
    }
  });
}

var processed = 0;
function update_event(table, id_evento, type, event_rep, row, server_id) {
  var inputs = $("#events_form :input");
  var values = {};
  var redraw = false;
  inputs.each(function() {
    values[this.name] = $(this).val();
  });
  var t1 = new Date();

  $.ajax({
    async: true,
    type: "POST",
    url: getUrlAjax(),
    data: {
      page: "include/ajax/events",
      validate_event: type.validate_event,
      in_process_event: type.in_process_event,
      delete_event: type.delete_event,
      id_evento: id_evento,
      server_id: server_id,
      event_rep: event_rep,
      filter: values
    },
    success: function(d) {
      processed += 1;
      var t2 = new Date();
      var diff_g = t2.getTime() - t1.getTime();
      var diff_s = diff_g / 1000;
      if (processed >= $(".chk_val:checked").length) {
        // If operation takes less than 2 seconds, redraw.
        if (diff_s < 2 || $(".chk_val:checked").length > 1) {
          redraw = true;
        }
        if (redraw) {
          $("#" + table)
            .DataTable()
            .draw(false);
        } else {
          $(row)
            .closest("tr")
            .remove();
        }
      }
    },
    error: function() {
      processed += 1;
    }
  });
}
// Update events matching current filters and id_evento selected.

function validate_event(table, id_evento, event_rep, row, server_id) {
  var button = document.getElementById("val-" + id_evento);
  var meta = $("#hidden-meta").val();
  if (meta != 0) {
    button = document.getElementById("val-" + id_evento + "-" + server_id);
  }

  if (!button) {
    // Button does not exist. Ignore.
    processed += 1;
    return;
  }

  button.children[0];
  button.children[0].src = "images/spinner.gif";
  return update_event(
    table,
    id_evento,
    { validate_event: 1 },
    event_rep,
    row,
    server_id
  );
}

function in_process_event(table, id_evento, event_rep, row, server_id) {
  var button = document.getElementById("proc-" + id_evento);
  var meta = $("#hidden-meta").val();
  if (meta != 0) {
    button = document.getElementById("proc-" + id_evento + "-" + server_id);
  }

  if (!button) {
    // Button does not exist. Ignore.
    processed += 1;
    return;
  }

  button.children[0];
  button.children[0].src = "images/spinner.gif";
  return update_event(
    table,
    id_evento,
    { in_process_event: 1 },
    event_rep,
    row,
    server_id
  );
}

function delete_event(table, id_evento, event_rep, row, server_id) {
  var button = document.getElementById("del-" + id_evento);
  var meta = $("#hidden-meta").val();
  if (meta != 0) {
    button = document.getElementById("del-" + id_evento + "-" + server_id);
  }

  if (!button) {
    // Button does not exist. Ignore.
    processed += 1;
    return;
  }
  var message = "<h3 style = 'text-align: center;' > Are you sure?</h3> ";
  confirmDialog({
    title: "ATTENTION",
    message: message,
    cancel: "Cancel",
    ok: "Ok",
    onAccept: function() {
      button.children[0];
      button.children[0].src = "images/spinner.gif";
      return update_event(
        table,
        id_evento,
        { delete_event: 1 },
        event_rep,
        row,
        server_id
      );
    },
    onDeny: function() {
      button.children[0];
      button.children[0].src = "images/delete.svg";
      return;
    }
  });
}

function execute_delete_event_reponse(
  table,
  id_evento,
  event_rep,
  row,
  server_id
) {
  var button = document.getElementById("del-" + id_evento);
  var meta = $("#hidden-meta").val();
  if (meta != 0) {
    button = document.getElementById("del-" + id_evento + "-" + server_id);
  }

  if (!button) {
    // Button does not exist. Ignore.
    processed += 1;
    return;
  }
  button.children[0];
  button.children[0].src = "images/spinner.gif";
  return update_event(
    table,
    id_evento,
    { delete_event: 1 },
    event_rep,
    row,
    server_id
  );
}

// Imported from old files.
function execute_event_response(event_list_btn) {
  var response_id = $("select[name=response_id]").val();
  if (!isNaN(response_id)) {
    table_info_response_event(response_id, 0, 0, true);
  }

  var message =
    "<h4 style = 'text-align: center; color:black' > Are you sure?</h4> <div id='massive-parameters-response'></div> ";
  confirmDialog({
    title: "ATTENTION",
    message: message,
    cancel: "Cancel",
    ok: "Ok",
    onAccept: function() {
      // Continue execution.
      processed = 0;
      $("#max_custom_event_resp_msg").hide();
      $("#max_custom_selected").hide();

      var total_checked = $(".chk_val:checked").length;

      // Check select an event.
      if (total_checked == 0) {
        $("#max_custom_selected").show();
        return;
      }

      if (!isNaN(response_id)) {
        var response_parameters_list = $('input[name^="values_params_"]');
        var response_parameters = [];
        if (response_parameters_list.length > 0) {
          response_parameters_list.each(function() {
            var acum = {
              name: $(this).attr("name"),
              value: $(this).val()
            };
            response_parameters.push(acum);
          });
        }

        response_parameters = JSON.stringify(response_parameters);

        if (event_list_btn) {
          $("#button-submit_event_response").hide(function() {
            $("#response_loading_dialog").show(function() {
              show_response_dialog_massive(response_id, response_parameters);
            });
          });
        } else {
          check_execute_response_massive(response_id, response_parameters);
        }
      } else {
        // It is not a custom response
        switch (response_id) {
          case "in_progress_selected":
            $(".chk_val:checked").each(function() {
              var event_id = $(this).val();
              var meta = $("#hidden-meta").val();
              var server_id = 0;
              if (meta != 0) {
                var split_id = event_id.split("|");
                event_id = split_id[0];
                server_id = split_id[1];
              }

              in_process_event(
                "table_events",
                event_id,
                $(this).attr("event_rep"),
                this.parentElement.parentElement,
                server_id
              );
            });
            break;
          case "validate_selected":
            $(".chk_val:checked").each(function() {
              var event_id = $(this).val();
              var meta = $("#hidden-meta").val();
              var server_id = 0;
              if (meta != 0) {
                var split_id = event_id.split("|");
                event_id = split_id[0];
                server_id = split_id[1];
              }

              validate_event(
                "table_events",
                event_id,
                $(this).attr("event_rep"),
                this.parentElement.parentElement,
                server_id
              );
            });
            break;
          case "delete_selected":
            $(".chk_val:checked").each(function() {
              var event_id = $(this).val();
              var meta = $("#hidden-meta").val();
              var server_id = 0;
              if (meta != 0) {
                var split_id = event_id.split("|");
                event_id = split_id[0];
                server_id = split_id[1];
              }

              execute_delete_event_reponse(
                "table_events",
                event_id,
                $(this).attr("event_rep"),
                this.parentElement.parentElement,
                server_id
              );
            });
            break;
        }
      }
    },
    onDeny: function() {
      processed += 1;
      return;
    }
  });
}

function show_response_dialog_massive(response_id, response_parameters) {
  var params = [];
  params.push({ name: "page", value: "include/ajax/events" });
  params.push({ name: "get_response", value: 1 });
  params.push({ name: "response_id", value: response_id });

  jQuery.ajax({
    data: params,
    type: "POST",
    url: getUrlAjax(),
    dataType: "json",
    success: function(response) {
      // If cannot get response abort it
      if (response == null) {
        return [];
      }

      $("#event_response_window")
        .hide()
        .empty()
        .append('<div class="container-massive-events-response"></div>')
        .dialog({
          title: $("#response_id option:selected").html(),
          resizable: true,
          draggable: true,
          modal: false,
          open: function() {
            check_execute_response_massive(response_id, response_parameters);
          },
          close: function() {
            $("#checkbox-all_validate_box").prop("checked", false);
            $(".chk_val").prop("checked", false);
            $("#response_loading_dialog").hide();
            $("#button-submit_event_response").show();
          },
          buttons: [
            {
              text: "Execute All",
              id: "execute-again-all",
              class:
                "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
              click: function() {
                execute_event_response(false);
              }
            }
          ],
          width: response["modal_width"],
          height: response["modal_height"]
        })
        .show();
    }
  });
}

function check_execute_response_massive(response_id, response_parameters) {
  var events = [];
  $(".container-massive-events-response").empty();
  $(".chk_val:checked").each(function() {
    var event_id = $(this).val();
    var meta = $("#hidden-meta").val();
    var server_id = 0;
    if (meta != 0) {
      var split_id = event_id.split("|");
      event_id = split_id[0];
      server_id = split_id[1];

      if (events[server_id] === undefined) {
        events[server_id] = [];
      }

      events[server_id].push(event_id);
    } else {
      events.push(event_id);
    }
  });

  execute_response_massive(events, response_id, response_parameters);
}

function event_widget_options() {
  if ($("#customFilter").val() != "-1") {
    $.ajax({
      type: "POST",
      url: "ajax.php",
      dataType: "json",
      data: {
        page: "include/ajax/events",
        get_filter_values: 1,
        id: $("#customFilter").val()
      },
      success: function(data) {
        if (data["event_type"] === "") {
          $("#eventType").val("0");
          $("#eventType").trigger("change");
        } else {
          $("#eventType").val(data["event_type"]);
          $("#eventType").trigger("change");
        }

        $("#limit").val(data["pagination"]);
        $("#limit").trigger("change");

        $("input[name='maxHours']").val(data["event_view_hr"]);

        $("#eventStatus").val(data["status"]);
        $("#eventStatus").trigger("change");

        let posicion = data["severity"].indexOf(-1);
        if (posicion !== -1) {
          $("#severity").val(-1);
          $("#severity").trigger("change");
        } else {
          const severity_array = data["severity"].split(",");
          $("#severity").val(severity_array[0]);
          $("#severity").trigger("change");
        }

        $("#tagsId option").attr("selected", false);
        $.each(
          atob(data["tag_with"])
            .slice(0, -1)
            .slice(1)
            .split(","),
          function(i, e) {
            $(`#tagsId option[value=${e}]`).prop("selected", true);
          }
        );

        $(".event-widget-input").disable();
      }
    });
  } else {
    $(".event-widget-input").enable();
  }
}

function process_buffers(buffers) {
  $("#events_buffers_display").empty();
  if (buffers != null && buffers.settings != undefined && buffers.data) {
    var params = [];
    params.push("page=include/ajax/events");
    params.push("process_buffers=1");
    params.push("buffers=" + JSON.stringify(buffers));

    jQuery.ajax({
      data: params.join("&"),
      type: "POST",
      url: getUrlAjax(),
      async: true,
      dataType: "html",
      success: function(data) {
        $("#events_buffers_display").html(data);
      }
    });
  }
}

function changeUrlParameterForModalSound(settings, filter_id) {
  /* 
    Basicamente esta funcion lo que hace es: cuando activas el modal sound
    y das al start para empezar a filtrar lo que hace es mirar si paras o arrancas
    con el mode y settear en la url los settings necesarios para iniciar el modal,
    si estaba en star añadira los parametros y si estaba parado los quitara,
    con esto se consigue que si se hace f5 o reload en la pagina mantenga la busqueda.
  */
  let mode = $("#hidden-mode_alert").val();
  if ("history" in window) {
    let href = window.location.href;
    if (checkExistParameterUrl(href, "settings") === null) {
      href += "&settings=1";
    }

    var regex_settings = /(settings=)[^&]+(&?)/gi;
    var replacement_settings = "$1" + settings + "$2";
    href = href.replace(regex_settings, replacement_settings);

    if (checkExistParameterUrl(href, "filter_id") === null) {
      href += "&filter_id=1";
    }

    var regex_filter_id = /(filter_id=)[^&]+(&?)/gi;
    var replacement_filter_id = "$1" + filter_id + "$2";
    href = href.replace(regex_filter_id, replacement_filter_id);
    if (mode == 0) {
      let filter_id = $("#filter_id option:selected").val();
      let interval = $("#interval option:selected").val();
      let time_sound = $("#time_sound option:selected").val();
      let sound_id = $("#sound_id option:selected").val();
      let parameters = {
        filter_id: filter_id,
        interval: interval,
        time_sound: time_sound,
        sound_id: sound_id,
        mode: mode
      };
      parameters = JSON.stringify(parameters);
      parameters = btoa(parameters);

      if (checkExistParameterUrl(href, "parameters") === null) {
        href += "&parameters=1";
      }

      var regex_parameters = /(parameters=)[^&]+(&?)/gi;
      var replacement_parameters = "$1" + parameters + "$2";
      href = href.replace(regex_parameters, replacement_parameters);
    } else {
      if (checkExistParameterUrl(href, "parameters") !== null) {
        var regex = new RegExp(
          "([?&])" + encodeURIComponent("parameters") + "=[^&]*(&|$)",
          "i"
        );
        href = href.replace(regex, "$1");
      }
    }

    window.history.replaceState({}, document.title, href);
  }
}

function openSoundEventsDialog(settings, dialog_parameters) {
  let encode_settings = settings;
  if (dialog_parameters != undefined && dialog_parameters) {
    dialog_parameters = JSON.parse(atob(dialog_parameters));
  } else {
    dialog_parameters = undefined;
  }
  settings = JSON.parse(atob(settings));
  // Check modal exists and is open.
  if (
    $("#modal-sound").hasClass("ui-dialog-content") &&
    $("#modal-sound").dialog("isOpen")
  ) {
    $(".ui-dialog-titlebar-minimize").trigger("click");
    return;
  }
  //Modify button
  $("#minimize_arrow_event_sound").removeClass("arrow_menu_up");
  $("#minimize_arrow_event_sound").addClass("arrow_menu_down");
  $("#minimize_arrow_event_sound").show();

  // Initialize modal.
  $("#modal-sound")
    .empty()
    .dialog({
      title: settings.title,
      resizable: false,
      modal: false,
      width: 600,
      height: 600,
      dialogClass: "modal-sound",
      open: function() {
        $.ajax({
          method: "post",
          url: settings.url,
          data: {
            page: settings.page,
            drawConsoleSound: 1
          },
          dataType: "html",
          success: function(data) {
            $("#modal-sound").append(data);
            $("#tabs-sound-modal").tabs({
              disabled: [1]
            });

            // Test sound.
            $("#button-melody_sound").click(function() {
              var sound = false;
              if ($("#id_sound_event").length == 0) {
                sound = true;
              }

              test_sound_button(sound, settings.urlSound);
            });

            // Play Stop.
            $("#button-start-search").click(function() {
              var id_filter_event = $("#hidden-id_filter_event").val();
              changeUrlParameterForModalSound(encode_settings, id_filter_event);
              var mode = $("#hidden-mode_alert").val();
              var action = false;
              if (mode == 0) {
                action = true;
              }
              if ($("#button-start-search").hasClass("play")) {
                $("#modal-sound").css({
                  height: "500px"
                });
                $("#modal-sound")
                  .parent()
                  .css({
                    height: "550px"
                  });
              } else {
                $("#modal-sound").css({
                  height: "450px"
                });
                $("#modal-sound")
                  .parent()
                  .css({
                    height: "500px"
                  });
              }

              action_events_sound(action, settings);
            });

            if (dialog_parameters != undefined) {
              if ($("#button-start-search").hasClass("play")) {
                $("#filter_id").val(dialog_parameters.filter_id);
                $("#interval").val(dialog_parameters.interval);
                $("#time_sound").val(dialog_parameters.time_sound);
                $("#sound_id").val(dialog_parameters.sound_id);

                $("#filter_id").trigger("change");
                $("#interval").trigger("change");
                $("#time_sound").trigger("change");
                $("#sound_id").trigger("change");

                $("#button-start-search").trigger("click");
              }
            }

            // Silence Alert.
            $("#button-no-alerts").click(function() {
              if ($("#button-no-alerts").hasClass("silence-alerts") === true) {
                // Remove audio.
                remove_audio();

                // Clean events.
                $("#tabs-sound-modal .elements-discovered-alerts ul").empty();
                $("#tabs-sound-modal .empty-discovered-alerts").removeClass(
                  "invisible_important"
                );

                // Clean progress.
                $("#progressbar_time").empty();

                // Change img button.
                $("#button-no-alerts")
                  .removeClass("silence-alerts")
                  .addClass("alerts");
                // Change value button.
                $("#button-no-alerts").val(settings.noAlert);
                $("#button-no-alerts > span").text(settings.noAlert);

                // Background button.
                $(".container-button-alert").removeClass("fired");

                // New progress.
                listen_event_sound(settings);
              }
            });
          },
          error: function(error) {
            console.error(error);
          }
        });
      },
      close: function() {
        $("#minimize_arrow_event_sound").hide();
        remove_audio();
        $(this).dialog("destroy");

        let href = window.location.href;
        if (checkExistParameterUrl(href, "parameters") !== null) {
          var regex_parameter = new RegExp(
            "([?&])" + encodeURIComponent("parameters") + "=[^&]*(&|$)",
            "i"
          );
          href = href.replace(regex_parameter, "$1");
        }

        if (checkExistParameterUrl(href, "settings") !== null) {
          var regex_settings = new RegExp(
            "([?&])" + encodeURIComponent("settings") + "=[^&]*(&|$)",
            "i"
          );
          href = href.replace(regex_settings, "$1");
        }

        window.history.replaceState({}, document.title, href);
      }
    })
    .show();
}

function openSoundEventModal(settings) {
  var win = "";
  if ($("#hidden-metaconsole_activated").val() === "1") {
    win = open(
      "../../operation/events/sound_events.php",
      "day_123",
      "width=600,height=500"
    );
  } else {
    win = open(
      "operation/events/sound_events.php",
      "day_123",
      "width=600,height=500"
    );
  }
  if (win) {
    //Browser has allowed it to be opened
    win.focus();
  } else {
    //Browser has blocked it
    alert("Please allow popups for this website");
  }

  settings = JSON.parse(atob(settings));

  // Check modal exists and is open.
  if (
    $("#modal-sound").hasClass("ui-dialog-content") &&
    $("#modal-sound").dialog("isOpen")
  ) {
    return;
  }
}

function test_sound_button(test_sound, urlSound) {
  if (test_sound === true) {
    $("#button-melody_sound").addClass("blink-image");
    add_audio(urlSound);
  } else {
    $("#button-melody_sound").removeClass("blink-image");
    remove_audio();
  }
}

function action_events_sound(mode, settings) {
  if (mode === true) {
    // Enable tabs.
    $("#tabs-sound-modal").tabs("option", "disabled", [0]);
    // Active tabs.
    $("#tabs-sound-modal").tabs("option", "active", 1);
    // Change mode.
    $("#hidden-mode_alert").val(1);
    // Change img button.
    $("#button-start-search")
      .removeClass("play")
      .addClass("stop");
    $("#button-start-search")
      .find("div")
      .removeClass("play")
      .addClass("stop");
    // Change value button.
    $("#button-start-search").val(settings.stop);
    $("#button-start-search > span").text(settings.stop);
    // Add Progress bar.
    listen_event_sound(settings);
  } else {
    // Enable tabs.
    $("#tabs-sound-modal").tabs("option", "disabled", [1]);
    // Active tabs.
    $("#tabs-sound-modal").tabs("option", "active", 0);
    // Change mode.
    $("#hidden-mode_alert").val(0);
    // Change img button.
    $("#button-start-search")
      .removeClass("stop")
      .addClass("play");
    $("#button-start-search")
      .find("div")
      .removeClass("stop")
      .addClass("play");
    // Change value button.
    $("#button-start-search").val(settings.start);
    $("#button-start-search > span").text(settings.start);
    // Remove progress bar.
    $("#progressbar_time").empty();
    // Remove audio.
    remove_audio();
    // Clean events.
    $("#tabs-sound-modal .elements-discovered-alerts ul").empty();
    $("#tabs-sound-modal .empty-discovered-alerts").removeClass(
      "invisible_important"
    );
    // Change img button.
    $("#button-no-alerts")
      .removeClass("silence-alerts")
      .addClass("alerts");
    // Change value button.
    $("#button-no-alerts").val(settings.noAlert);
    $("#button-no-alerts > span").text(settings.noAlert);

    // Background button.
    $(".container-button-alert").removeClass("fired");
  }
}

function add_audio(urlSound) {
  var sound = urlSound + $("#tabs-sound-modal #sound_id").val();
  $(".actions-sound-modal").append(
    "<audio id='id_sound_event' src='" +
      sound +
      "' autoplay='true' hidden='true' loop='false'>"
  );
  $("#button-sound_events_button").addClass("animation-blink");
}

function remove_audio() {
  $(".actions-sound-modal audio").remove();
  $("#button-sound_events_button").removeClass("animation-blink");
}

function listen_event_sound(settings) {
  progressTimeBar(
    "progressbar_time",
    $("#interval").val(),
    "infinite",
    function() {
      // Search events.
      check_event_sound(settings);
    }
  );
}

function check_event_sound(settings) {
  // Update elements time.
  $(".elements-discovered-alerts ul li").each(function() {
    let element_time = $(this)
      .children(".li-hidden")
      .val();
    let obj_time = new Date(element_time);
    let current_dt = new Date();
    let timestamp = current_dt.getTime() - obj_time.getTime();
    timestamp = timestamp / 1000;
    if (timestamp <= 60) {
      timestamp = Math.round(timestamp) + " seconds";
    } else if (timestamp <= 3600) {
      let minute = Math.floor((timestamp / 60) % 60);
      minute = minute < 10 ? "0" + minute : minute;
      let second = Math.floor(timestamp % 60);
      second = second < 10 ? "0" + second : second;
      timestamp = minute + " minutes " + second + " seconds";
    } else {
      let hour = Math.floor(timestamp / 3600);
      hour = hour < 10 ? "0" + hour : hour;
      let minute = Math.floor((timestamp / 60) % 60);
      minute = minute < 10 ? "0" + minute : minute;
      let second = Math.round(timestamp % 60);
      second = second < 10 ? "0" + second : second;
      timestamp = hour + " hours " + minute + " minutes " + second + " seconds";
    }
    $(this)
      .children(".li-time")
      .children("span")
      .html(timestamp);
  });
  jQuery.post(
    settings.url,
    {
      page: "include/ajax/events",
      get_events_fired: 1,
      filter_id: $("#tabs-sound-modal #filter_id").val(),
      interval: $("#tabs-sound-modal #interval").val(),
      time_sound: $("#tabs-sound-modal #time_sound").val()
    },
    function(data) {
      if (data != false) {
        // Hide empty.
        $("#tabs-sound-modal .empty-discovered-alerts").addClass(
          "invisible_important"
        );

        // Change img button.
        $("#button-no-alerts")
          .removeClass("alerts")
          .addClass("silence-alerts");
        // Change value button.
        $("#button-no-alerts").val(settings.silenceAlarm);
        $("#button-no-alerts > span").text(settings.silenceAlarm);

        // Background button.
        $(".container-button-alert").addClass("fired");

        // Remove audio.
        remove_audio();

        // Apend audio.
        add_audio(settings.urlSound);

        // Add elements.
        data.forEach(function(element) {
          var li = document.createElement("li");
          var b64 = btoa(JSON.stringify(element));
          li.insertAdjacentHTML(
            "beforeend",
            '<div class="li-priority">' + element.priority + "</div>"
          );
          li.insertAdjacentHTML(
            "beforeend",
            '<div class="li-type">' + element.type + "</div>"
          );
          li.insertAdjacentHTML(
            "beforeend",
            `<div class="li-title"><a href="javascript:" onclick="show_event_dialog('${b64}')">${element.message}</a></div>`
          );
          li.insertAdjacentHTML(
            "beforeend",
            '<div class="li-time">' + element.timestamp + "</div>"
          );
          li.insertAdjacentHTML(
            "beforeend",
            '<input type="hidden" value="' +
              element.event_timestamp +
              '" class="li-hidden"/>'
          );
          $("#tabs-sound-modal .elements-discovered-alerts ul").prepend(li);
        });

        // -100 delay sound.
        setTimeout(
          remove_audio,
          parseInt($("#tabs-sound-modal #time_sound").val()) * 1000 - 100
        );
      }
    },
    "json"
  );
}

function table_info_response_event(response_id, event_id, server_id, massive) {
  var params = [];
  params.push({ name: "page", value: "include/ajax/events" });
  params.push({ name: "get_response", value: 1 });
  params.push({ name: "response_id", value: response_id });
  params.push({ name: "server_id", value: server_id });
  params.push({ name: "event_id", value: event_id });

  var url = getUrlAjax();

  jQuery.ajax({
    data: params,
    type: "POST",
    url: url,
    dataType: "json",
    success: function(response) {
      if (response) {
        var params = [];
        params.push({ name: "page", value: "include/ajax/events" });
        params.push({ name: "draw_row_response_info", value: 1 });
        params.push({ name: "massive", value: massive === true ? 1 : 0 });
        params.push({ name: "response", value: JSON.stringify(response) });

        jQuery.ajax({
          data: params,
          type: "POST",
          url: url,
          dataType: "html",
          success: function(output) {
            if (massive === true) {
              $("#massive-parameters-response").append(output);
            } else {
              $(".params_rows").remove();
              $("#responses_table").append(output);
            }
          }
        });
      }
    }
  });
}

function getUrlAjax() {
  if ($("#hidden-ajax_file").length) {
    return $("#hidden-ajax_file").val();
  } else if ($("#hidden-ajax_file_sound_console").length) {
    return $("#hidden-ajax_file_sound_console").val();
  }
}

function addElement(name_select, id_modal) {
  var modal = document.getElementById(id_modal);
  var parent = $(modal).parent();
  $(modal).dialog({
    title: "Choose columns",
    width: 330,
    buttons: [
      {
        class:
          "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-next",
        text: "Confirm",
        click: function() {
          $(modal)
            .find("select option:selected")
            .each(function(key, option) {
              $("select[name='" + name_select + "']").append(option);
            });
          var clone = $(modal).clone();
          $(modal)
            .dialog("destroy")
            .remove();
          $(clone).hide();
          $(parent).append(clone);
        }
      }
    ],
    close: function() {
      var clone = $(modal).clone();
      $(modal)
        .dialog("destroy")
        .remove();
      $(clone).hide();
      $(parent).append(clone);
    }
  });
}

function removeElement(name_select, id_modal) {
  var modal = document.getElementById(id_modal);
  $("select[name='" + name_select + "'] option:selected").each(function(
    key,
    option
  ) {
    $(modal)
      .find("select")
      .append(option);
  });
}

function get_table_events_tabs(event, filter) {
  var custom_event_view_hr = $("#hidden-comments_events_max_hours_old").val();
  $.post({
    url: "ajax.php",
    data: {
      page: "include/ajax/events",
      get_comments: 1,
      event: event,
      filter: filter,
      custom_event_view_hr: custom_event_view_hr
    },
    dataType: "html",
    success: function(data) {
      $("#extended_event_comments_page").empty();
      $("#extended_event_comments_page").html(data);
    }
  });
}
// Define the minimize button functionality;
function hidden_dialog(dialog) {
  setTimeout(function() {
    $("#modal-sound").css("visibility", "hidden");
    dialog.css("z-index", "-1");
  }, 200);
}

function show_dialog(dialog) {
  setTimeout(function() {
    $("#modal-sound").css("visibility", "visible");
    dialog.css("z-index", "1115");
  }, 50);
}

/*
#############################################################################
##
## + Compacts the Modal Sound Dialog to a tiny toolbar
## + Dynamically adds a button which can reduce/reapply the dialog size
## + If alarm gets raised & minimized, the dialog window maximizes and the toolbar flashes red for 10 seconds. 
## - Works fine until a link/action gets clicked. The Toolbar shifts to the bottom of the Modal-Sound Dialog.
##
#############################################################################
*/

$(document).ajaxSend(function(event, jqXHR, ajaxOptions) {
  const requestBody = ajaxOptions.data;
  try {
    if (
      requestBody &&
      typeof requestBody.includes === "function" &&
      requestBody.includes("drawConsoleSound=1")
    ) {
      // Find the dialog element by the aria-describedby attribute
      var dialog = $('[aria-describedby="modal-sound"]');

      // Select the close button within the dialog
      var closeButton = dialog.find(".ui-dialog-titlebar-close");

      // Add the minimize button before the close button
      var minimizeButton = $("<button>", {
        class:
          "ui-corner-all ui-widget ui-button-icon-only ui-window-minimize ui-dialog-titlebar-minimize minimize-buttom-image",
        type: "button",
        title: "Minimize"
      }).insertBefore(closeButton);

      // Add the minimize icon to the minimize button
      $("<span>", {
        class: "ui-button-icon ui-icon"
      }).appendTo(minimizeButton);

      $("<span>", {
        class: "ui-button-icon-space"
      })
        .html(" ")
        .appendTo(minimizeButton);

      // Add the disengage button before the minimize button
      var disengageButton = $("<button>", {
        class:
          "ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-disengage disengage-buttom-image",
        type: "button",
        title: "Disengage"
      }).insertBefore(minimizeButton);

      minimizeButton.click(function(e) {
        if ($("#minimize_arrow_event_sound").hasClass("arrow_menu_up")) {
          $("#minimize_arrow_event_sound").removeClass("arrow_menu_up");
          $("#minimize_arrow_event_sound").addClass("arrow_menu_down");
        } else if (
          $("#minimize_arrow_event_sound").hasClass("arrow_menu_down")
        ) {
          $("#minimize_arrow_event_sound").removeClass("arrow_menu_down");
          $("#minimize_arrow_event_sound").addClass("arrow_menu_up");
        }

        if (!dialog.data("isMinimized")) {
          $(".ui-widget-overlay").hide();
          dialog.data("originalPos", dialog.position());
          dialog.data("originalSize", {
            width: dialog.width(),
            height: dialog.height()
          });
          dialog.data("isMinimized", true);

          dialog.animate(
            {
              height: "40px",
              top: $(window).height() - 100
            },
            200,
            "linear",
            hidden_dialog(dialog)
          );
          dialog.css({ height: "" });
          dialog.animate(
            {
              height: dialog.data("originalSize").height + "px",
              top: dialog.data("originalPos").top + "px"
            },
            5
          );
        } else {
          $(".ui-widget-overlay").show();
          dialog.data("isMinimized", false);

          dialog.animate(
            {
              height: "40px",
              top: $(window).height() - 100
            },
            5
          );
          dialog.animate(
            {
              height: dialog.data("originalSize").height + "px",
              top: dialog.data("originalPos").top + "px"
            },
            200,
            "linear",
            show_dialog(dialog)
          );
        }
      });

      disengageButton.click(function() {
        $(".ui-dialog-titlebar-close").trigger("click");
        $("#button-sound_events_button_hidden").trigger("click");
      });

      // Listener to check if the dialog content contains <li> elements
      var dialogContent = dialog.find(".ui-dialog-content");
      var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          var addedNodes = mutation.addedNodes;
          for (var i = 0; i < addedNodes.length; i++) {
            if (addedNodes[i].nodeName.toLowerCase() === "li") {
              console.log("The dialog content contains an <li> tag.");
              break;
            }
          }
        });
      });

      // Configure and start observing the dialog content for changes
      var config = { childList: true, subtree: true };
      observer.observe(dialogContent[0], config);
    }
  } catch (e) {
    console.log(e);
  }
});

/*
#############################################################################
##
## + Compacts the Modal Sound Dialog popup and removes the widget-overlay 
##
##
#############################################################################
*/
$(document).ajaxSend(function(event, jqXHR, ajaxOptions) {
  const requestBody = ajaxOptions.data;
  try {
    if (
      requestBody &&
      typeof requestBody.includes === "function" &&
      requestBody.includes("drawConsoleSound=1")
    ) {
      console.log(
        "AJAX request sent with drawConsoleSound=1:",
        ajaxOptions.url
      );

      // Find the dialog element by the aria-describedby attribute
      var dialog = $('[aria-describedby="modal-sound"]');
      dialog.css({
        // "backgroundColor":"black",
        // "color":"white"
      });

      // Set CSS properties for #modal-sound
      $("#modal-sound").css({
        height: "450px",
        margin: "0px"
      });

      // Set CSS properties for #tabs-sound-modal
      $("#tabs-sound-modal").css({
        "margin-top": "0px",
        padding: "0px",
        "font-weight": "bolder"
      });

      // Set CSS properties for #actions-sound-modal
      $("#actions-sound-modal").css({
        "margin-bottom": "0px"
      });

      // Hide the overlay with specific class and z-index
      $('.ui-widget-overlay.ui-front[style="z-index: 10000;"]').css(
        "display",
        "none"
      );
    }
  } catch (e) {
    console.log(e);
  }
});

function openEvents(severity) {
  $('input[name="filter[severity]"]').val(severity);
  $("#event_redirect").submit();
}

// Load Asteroids game.
$(window).on("load", function() {
  let counter = 0;
  $("#button-sound_events_button")
    .off("click")
    .on("click", function(e) {
      counter++;
      let flagEasternEgg = $("#flagEasternEgg").val();
      if (counter == 12 && flagEasternEgg == true) {
        $("#modal-asteroids")
          .dialog({
            title: "Asteroids",
            resizable: true,
            modal: true,
            width: 900,
            height: 700,
            open: function() {
              $.ajax({
                method: "post",
                url: getUrlAjax(),
                data: {
                  page: "include/ajax/events",
                  playAsteroids: 1
                },
                dataType: "html",
                success: function(data) {
                  $("#modal-asteroids").html(data);
                  $(".ui-widget-content").css("background", "#222");
                  $(".ui-dialog-title").css("color", "#fff");
                },
                error: function(error) {
                  console.error(error);
                }
              });
            },
            close: function() {
              counter = 0;
              $(".ui-widget-content").css("background", "#fff");
              $(".ui-dialog-title").css("color", "rgb(51, 51, 51)");
            }
          })
          .show();
      }
    });
});
