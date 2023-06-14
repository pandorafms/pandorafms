/*global jQuery, $, forced_title_callback, confirmDialog, progressTimeBar*/

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
          title: event.evento,
          resizable: true,
          draggable: true,
          modal: true,
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
function event_change_status(event_ids, server_id) {
  var new_status = $("#estado").val();

  $("#button-status_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: {
      page: "include/ajax/events",
      change_status: 1,
      event_ids: event_ids,
      new_status: new_status,
      server_id: server_id
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
      $("#link_comments").click();
    }
  });

  return false;
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

function openSoundEventModal(settings) {
  if ($("#hidden-metaconsole_activated").val() === "1") {
    var win = open(
      "../../operation/events/sound_events.php",
      "day_123",
      "width=600,height=500"
    );
  } else {
    var win = open(
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
}

function remove_audio() {
  $(".actions-sound-modal audio").remove();
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
          $("#tabs-sound-modal .elements-discovered-alerts ul").append(li);
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
