/*global jQuery, $, forced_title_callback, confirmDialog*/

// Show the modal window of an event
function show_event_dialog(event, dialog_page) {
  var ajax_file = $("#hidden-ajax_file").val();

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
      $("#event_details_window")
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
          height: 600,
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

      /*
      switch (result) {
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
      */

      forced_title_callback();
    },
    "html"
  );
  return false;
}

// Check the response type and open it in a modal dialog or new window
function execute_response(event_id, server_id) {
  var response_id = $("#select_custom_response option:selected").val();

  var response = get_response(response_id, server_id);

  // If cannot get response abort it
  if (response == null) {
    return;
  }

  response["target"] = get_response_target(event_id, response_id, server_id);
  response["event_id"] = event_id;
  response["server_id"] = server_id;

  if (response["type"] == "url" && response["new_window"] == 1) {
    window.open(response["target"], "_blank");
  } else {
    show_response_dialog(response_id, response);
  }
}

//Show the modal window of an event response
function show_response_dialog(response_id, response) {
  var params = [];
  params.push("page=include/ajax/events");
  params.push("dialogue_event_response=1");
  params.push("massive=0");
  params.push("event_id=" + response["event_id"]);
  params.push("target=" + encodeURIComponent(response["target"]));
  params.push("response_id=" + response_id);
  params.push("server_id=" + response["server_id"]);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    dataType: "html",
    success: function(data) {
      $("#event_response_window")
        .hide()
        .empty()
        .append(data)
        .dialog({
          title: $("#select_custom_response option:selected").html(),
          resizable: true,
          draggable: true,
          modal: false,
          open: function() {
            perform_response(response, response_id);
          },
          width: response["modal_width"],
          height: response["modal_height"]
        })
        .show();
    }
  });
}

//Show the modal window of event responses when multiple events are selected
function show_massive_response_dialog(
  response_id,
  response,
  out_iterator,
  end
) {
  var params = [];
  params.push("page=include/ajax/events");
  params.push("dialogue_event_response=1");
  params.push("massive=1");
  params.push("end=" + end);
  params.push("out_iterator=" + out_iterator);
  params.push("event_id=" + response["event_id"]);
  params.push("target=" + response["target"]);
  params.push("response_id=" + response_id);
  params.push("server_id=" + response["server_id"]);

  jQuery.ajax({
    data: params.join("&"),
    response_tg: response,
    response_id: response_id,
    out_iterator: out_iterator,
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    dataType: "html",
    success: function(data) {
      if (out_iterator === 0) $("#event_response_window").empty();

      $("#event_response_window")
        .hide()
        .append(data)
        .dialog({
          title: $("#select_custom_response option:selected").html(),
          resizable: true,
          draggable: true,
          modal: false,
          open: function() {
            $("#response_loading_dialog").hide();
            $("#button-submit_event_response").show();
          },
          close: function() {
            $("#checkbox-all_validate_box").prop("checked", false);
            $(".chk_val").prop("checked", false);
          },
          width: response["modal_width"],
          height: response["modal_height"]
        })
        .show();

      perform_response_massive(
        this.response_tg,
        this.response_id,
        this.out_iterator
      );
    }
  });
}

// Get an event response from db
function get_response(response_id, server_id) {
  var response = "";

  var params = [];
  params.push("page=include/ajax/events");
  params.push("get_response=1");
  params.push("response_id=" + response_id);
  params.push("server_id=" + server_id);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: false,
    dataType: "json",
    success: function(data) {
      response = data;
    }
  });

  return response;
}

// Get an event response params from db
function get_response_params(response_id) {
  var response_params;

  var params = [];
  params.push("page=include/ajax/events");
  params.push("get_response_params=1");
  params.push("response_id=" + response_id);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: false,
    dataType: "json",
    success: function(data) {
      response_params = data;
    }
  });

  return response_params;
}

// Get an event response description from db
function get_response_description(response_id) {
  var response_description = "";

  var params = [];
  params.push("page=include/ajax/events");
  params.push("get_response_description=1");
  params.push("response_id=" + response_id);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: false,
    dataType: "html",
    success: function(data) {
      response_description = data;
    }
  });

  return response_description;
}

function add_row_param(id_table, param) {
  $("#" + id_table).append(
    '<tr class="params_rows"><td style="text-align:left; padding-left:40px; font-weight: normal; font-style: italic;">' +
      param +
      '</td><td style="text-align:left" colspan="2"><input type="text" name="' +
      param +
      '" id="' +
      param +
      '"></td></tr>'
  );
}

// Get an event response from db
function get_response_target(
  event_id,
  response_id,
  server_id,
  response_command
) {
  var target = "";

  // Replace the main macros
  var params = [];
  params.push("page=include/ajax/events");
  params.push("get_response_target=1");
  params.push("event_id=" + event_id);
  params.push("response_id=" + response_id);
  params.push("server_id=" + server_id);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: false,
    dataType: "html",
    success: function(data) {
      target = data;
    }
  });

  // Replace the custom params macros.
  var response_params = get_response_params(response_id);
  if (response_params.length > 1 || response_params[0] != "") {
    for (var i = 0; i < response_params.length; i++) {
      if (!response_command) {
        var response_param = "_" + response_params[i] + "_";

        if (
          response_params[i].startsWith("_") &&
          response_params[i].endsWith("_")
        ) {
          response_param = response_params[i];
        }

        target = target.replace(
          response_param,
          $("#" + response_params[i]).val()
        );
      } else {
        target = target.replace(
          "_" + response_params[i] + "_",
          response_command[response_params[i] + "-" + i]
        );
      }
    }
  }

  return target;
}

// Perform a response and put the output into a div
function perform_response(response, response_id) {
  $("#re_exec_command").hide();
  $("#response_loading_command").show();
  $("#response_out").html("");

  var params = [];
  params.push("page=include/ajax/events");
  params.push("perform_event_response=1");
  params.push("target=" + encodeURIComponent(response["target"]));
  params.push("response_id=" + response_id);
  params.push("event_id=" + response["event_id"]);
  params.push("server_id=" + response["server_id"]);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: true,
    dataType: "html",
    success: function(data) {
      var out = data.replace(/[\n|\r]/g, "<br>");
      $("#response_out").html(out);
      $("#response_loading_command").hide();
      $("#re_exec_command").show();
    }
  });

  return false;
}

// Perform a response and put the output into a div
function perform_response_massive(response, response_id, out_iterator) {
  $("#re_exec_command").hide();
  $("#response_loading_command_" + out_iterator).show();
  $("#response_out_" + out_iterator).html("");

  var params = [];
  params.push("page=include/ajax/events");
  params.push("perform_event_response=1");
  params.push("target=" + response["target"]);
  params.push("response_id=" + response_id);
  params.push("event_id=" + response["event_id"]);
  params.push("server_id=" + response["server_id"]);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: true,
    dataType: "html",
    success: function(data) {
      var out = data.replace(/[\n|\r]/g, "<br>");
      $("#response_out_" + out_iterator).html(out);
      $("#response_loading_command_" + out_iterator).hide();
      $("#re_exec_command_" + out_iterator).show();
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
    url: $("#hidden-ajax_file").val(),
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
        if (typeof dt_events !== "undefined") {
          dt_events.draw(false);
        }
        $("#notification_status_success").show();
        if (new_status == 1) {
          $("#extended_event_general_page table td.general_acknowleded").text(
            data.user
          );
        } else {
          $("#extended_event_general_page table td.general_acknowleded").text(
            "N/A"
          );
        }

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
    url: $("#hidden-ajax_file").val(),
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
        if (typeof dt_events !== "undefined") {
          dt_events.draw(false);
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

  var params = [];
  params.push("page=include/ajax/events");
  params.push("add_comment=1");
  if (event.event_rep > 0) {
    params.push("event_id=" + event.max_id_evento);
  } else {
    params.push("event_id=" + event.id_evento);
  }
  params.push("comment=" + comment);
  params.push("server_id=" + event.server_id);

  $("#button-comment_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    dataType: "html",
    success: function() {
      $("#button-comment_button").removeAttr("disabled");
      $("#response_loading").hide();
      $("#link_comments").click();
    }
  });

  return false;
}

function show_event_response_command_dialog(id, response, total_checked) {
  var params = [];
  params.push("page=include/ajax/events");
  params.push("get_table_response_command=1");
  params.push("event_response_id=" + id);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    dataType: "html",
    success: function(data) {
      $("#event_response_command_window")
        .hide()
        .empty()
        .append(data)
        .dialog({
          resizable: true,
          draggable: true,
          modal: false,
          open: function() {
            $("#response_loading_dialog").hide();
            $("#button-submit_event_response").show();
          },
          width: 600,
          height: 300
        })
        .show();

      $("#submit-enter_command").on("click", function(e) {
        e.preventDefault();
        var response_command = [];

        $(".response_command_input").each(function() {
          response_command[$(this).attr("name")] = $(this).val();
        });

        check_massive_response_event(
          id,
          response,
          total_checked,
          response_command
        );
      });
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
    url: $("#hidden-ajax_file").val(),
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
      button.children[0].src = "images/cross.png";
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
  var message =
    "<h4 style = 'text-align: center; color:black' > Are you sure?</h4> ";
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

      var response_id = $("select[name=response_id]").val();

      var total_checked = $(".chk_val:checked").length;

      // Check select an event.
      if (total_checked == 0) {
        $("#max_custom_selected").show();
        return;
      }

      if (!isNaN(response_id)) {
        // It is a custom response
        var response = get_response(response_id);

        // If cannot get response abort it
        if (response == null) {
          return;
        }

        // Limit number of events to apply custom responses
        // due performance reasons.
        if (total_checked > $("#max_execution_event_response").val()) {
          $("#max_custom_event_resp_msg").show();
          return;
        }

        var response_command = [];
        $(".response_command_input").each(function() {
          response_command[$(this).attr("name")] = $(this).val();
        });

        if (event_list_btn) {
          $("#button-submit_event_response").hide(function() {
            $("#response_loading_dialog").show(function() {
              var check_params = get_response_params(response_id);

              if (check_params[0] !== "") {
                show_event_response_command_dialog(
                  response_id,
                  response,
                  total_checked
                );
              } else {
                check_massive_response_event(
                  response_id,
                  response,
                  total_checked,
                  response_command
                );
              }
            });
          });
        } else {
          $("#button-btn_str").hide(function() {
            $("#execute_again_loading").show(function() {
              check_massive_response_event(
                response_id,
                response,
                total_checked,
                response_command
              );
            });
          });
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
                "events",
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
                "events",
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
                "events",
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

function check_massive_response_event(
  response_id,
  response,
  total_checked,
  response_command
) {
  var counter = 0;
  var end = 0;

  $(".chk_val:checked").each(function() {
    var event_id = $(this).val();
    var meta = $("#hidden-meta").val();
    var server_id = 0;
    if (meta != 0) {
      var split_id = event_id.split("|");
      event_id = split_id[0];
      server_id = split_id[1];
    }

    response["target"] = get_response_target(
      event_id,
      response_id,
      server_id,
      response_command
    );
    response["server_id"] = server_id;
    response["event_id"] = event_id;

    if (total_checked - 1 === counter) end = 1;

    show_massive_response_dialog(response_id, response, counter, end);

    counter++;
  });
}

function event_widget_options() {
  if ($("#customFilter").val() != "-1") {
    $(".event-widget-input").disable();
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
      url: $("#hidden-ajax_file").val(),
      async: true,
      dataType: "html",
      success: function(data) {
        $("#events_buffers_display").html(data);
      }
    });
  }
}

function openSoundEventModal(settings) {
  settings = JSON.parse(atob(settings));

  // Check modal exists and is open.
  if (
    $("#modal-sound").hasClass("ui-dialog-content") &&
    $("#modal-sound").dialog("isOpen")
  ) {
    return;
  }

  // Initialize modal.
  $("#modal-sound")
    .dialog({
      title: settings.title,
      resizable: false,
      modal: true,
      position: { my: "right top", at: "right bottom", of: window },
      overlay: {
        opacity: 0.5,
        background: "black"
      },
      width: 600,
      height: 600,
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
            console.log(data);
          },
          error: function(error) {
            console.error(error);
          }
        });
      },
      close: function() {
        $(this).dialog("destroy");
      }
    })
    .show();
}
