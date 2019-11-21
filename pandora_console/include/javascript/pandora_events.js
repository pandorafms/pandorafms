/*global jQuery,$,forced_title_callback,Base64, dt_events*/

// Show the modal window of an event
var current_event;
function show_event_dialog(event, dialog_page, result) {
  var ajax_file = $("#hidden-ajax_file").val();

  if (dialog_page == undefined) {
    dialog_page = "general";
  }

  current_event = event;

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
          close: function() {
            $("#refrcounter").countdown("resume");
            $("div.vc-countdown").countdown("resume");
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
        url: "ajax.php",
        data: {
          page: "include/ajax/events",
          get_comments: 1,
          event: event,
          filter: values
        },
        dataType: "html",
        success: function(data) {
          $("#extended_event_comments_page").empty();
          $("#extended_event_comments_page").html(data);
        }
      });

      $("#refrcounter").countdown("pause");
      $("div.vc-countdown").countdown("pause");

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

      forced_title_callback();
    },
    "html"
  );
  return false;
}

// Check the response type and open it in a modal dialog or new window
function execute_response(event_id, server_id) {
  var response_id = $("#select_custom_response option:selected").val();

  var response = get_response(response_id);

  // If cannot get response abort it
  if (response == null) {
    return;
  }

  response["target"] = get_response_target(event_id, response_id, server_id);

  switch (response["type"]) {
    case "command":
      show_response_dialog(event_id, response_id, response);
      break;
    case "url":
      if (response["new_window"] == 1) {
        window.open(response["target"], "_blank");
      } else {
        show_response_dialog(event_id, response_id, response);
      }
      break;
  }
}

//Show the modal window of an event response
function show_response_dialog(event_id, response_id, response) {
  var params = [];
  params.push("page=include/ajax/events");
  params.push("dialogue_event_response=1");
  params.push("massive=0");
  params.push("event_id=" + event_id);
  params.push("target=" + response["target"]);
  params.push("response_id=" + response_id);

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
            perform_response(response["target"], response_id);
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
  event_id,
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
  params.push("event_id=" + event_id);
  params.push("target=" + response["target"]);
  params.push("response_id=" + response_id);

  jQuery.ajax({
    data: params.join("&"),
    response_tg: response["target"],
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
            $(".chk_val").prop("checked", false);
            $("#event_response_command_window").dialog("close");
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
function get_response(response_id) {
  var response = "";

  var params = [];
  params.push("page=include/ajax/events");
  params.push("get_response=1");
  params.push("response_id=" + response_id);

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

// Get an event response description from db
function get_event_name(event_id, meta, history) {
  var name = "";

  var params = [];
  params.push("page=include/ajax/events");
  params.push("get_event_name=1");
  params.push("event_id=" + event_id);
  params.push("meta=" + meta);
  params.push("history=" + history);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: false,
    dataType: "html",
    success: function(data) {
      name = data;
    }
  });

  return name;
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
        target = target.replace(
          "_" + response_params[i] + "_",
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
function perform_response(target, response_id) {
  $("#re_exec_command").hide();
  $("#response_loading_command").show();
  $("#response_out").html("");

  var params = [];
  params.push("page=include/ajax/events");
  params.push("perform_event_response=1");
  params.push("target=" + target);
  params.push("response_id=" + response_id);

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
function perform_response_massive(target, response_id, out_iterator) {
  $("#re_exec_command").hide();
  $("#response_loading_command_" + out_iterator).show();
  $("#response_out_" + out_iterator).html("");

  var params = [];
  params.push("page=include/ajax/events");
  params.push("perform_event_response=1");
  params.push("target=" + target);
  params.push("response_id=" + response_id);

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
function event_change_status(event_ids) {
  var new_status = $("#estado").val();
  var event_id = $("#hidden-id_event").val();
  var meta = $("#hidden-meta").val();
  var history = $("#hidden-history").val();

  $("#button-status_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: {
      page: "include/ajax/events",
      change_status: 1,
      event_ids: event_ids,
      new_status: new_status,
      meta: meta,
      history: history
    },
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: true,
    dataType: "html",
    success: function(data) {
      $("#button-status_button").removeAttr("disabled");
      $("#response_loading").hide();

      if ($("#notification_status_success").length) {
        $("#notification_status_success").hide();
      }

      if ($("#notification_status_error").length) {
        $("#notification_status_error").hide();
      }

      if (data == "status_ok") {
        dt_events.draw(false);
        $("#notification_status_success").show();
      } else {
        $("#notification_status_error").show();
      }
    }
  });
  return false;
}

// Change te owner of an event to one user of empty
function event_change_owner() {
  var event_id = $("#hidden-id_event").val();
  var new_owner = $("#id_owner").val();
  var meta = $("#hidden-meta").val();
  var history = $("#hidden-history").val();

  $("#button-owner_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: {
      page: "include/ajax/events",
      change_owner: 1,
      event_id: event_id,
      new_owner: new_owner,
      meta: meta,
      history: history
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
        dt_events.draw(false);
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
function event_comment() {
  var event;
  try {
    event = JSON.parse(atob(current_event));
  } catch (e) {
    console.error(e);
    return;
  }

  var event_id = event.id_evento;
  var comment = $("#textarea_comment").val();
  var meta = $("#hidden-meta").val();
  var history = $("#hidden-history").val();

  if (comment == "") {
    show_event_dialog(current_event, "comments", "comment_error");
    return false;
  }

  var params = [];
  params.push("page=include/ajax/events");
  params.push("add_comment=1");
  params.push("event_id=" + event_id);
  params.push("comment=" + comment);
  params.push("meta=" + meta);
  params.push("history=" + history);

  $("#button-comment_button").attr("disabled", "disabled");
  $("#response_loading").show();

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    async: true,
    dataType: "html",
    success: function(data) {
      $("#button-comment_button").removeAttr("disabled");
      $("#response_loading").hide();
      $("#link_comments").click();
    }
  });

  return false;
}

//Show event list when fielter repetead is Group agents
function show_events_group_agent(id_insert, id_agent, server_id) {
  var parameter = [];
  parameter.push({ name: "id_agent", value: id_agent });
  parameter.push({ name: "server_id", value: server_id });
  parameter.push({ name: "event_type", value: $("#event_type").val() });
  parameter.push({ name: "severity", value: $("#severity").val() });
  parameter.push({ name: "status", value: $("#status").val() });
  parameter.push({ name: "search", value: $("#text-search").val() });
  parameter.push({
    name: "id_agent_module",
    value: $("input:hidden[name=module_search_hidden]").val()
  });
  parameter.push({
    name: "event_view_hr",
    value: $("#text-event_view_hr").val()
  });
  parameter.push({ name: "id_user_ack", value: $("#id_user_ack").val() });
  parameter.push({
    name: "tag_with",
    value: Base64.decode($("#hidden-tag_with").val())
  });
  parameter.push({
    name: "tag_without",
    value: Base64.decode($("#hidden-tag_without").val())
  });
  parameter.push({
    name: "filter_only_alert",
    value: $("#filter_only_alert").val()
  });
  parameter.push({ name: "date_from", value: $("#text-date_from").val() });
  parameter.push({ name: "date_to", value: $("#text-date_to").val() });
  parameter.push({ name: "server_id_search", value: $("#server_id").val() });
  parameter.push({
    name: "page",
    value: "include/ajax/events"
  });
  parameter.push({
    name: "get_list_events_agents",
    value: 1
  });

  jQuery.ajax({
    type: "POST",
    url: $("#hidden-ajax_file").val(),
    data: parameter,
    dataType: "html",
    success: function(data) {
      $("#" + id_insert).html(data);
      $("#" + id_insert).toggle();
    }
  });
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
function update_event(table, id_evento, type, event_rep, row) {
  var inputs = $("#events_form :input");
  var values = {};
  var redraw = false;
  inputs.each(function() {
    values[this.name] = $(this).val();
  });
  var t1 = new Date();

  // Update events matching current filters and id_evento selected.
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
          table.draw(false);
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

function validate_event(table, id_evento, event_rep, row) {
  var button = document.getElementById("val-" + id_evento);
  if (!button) {
    // Button does not exist. Ignore.
    processed += 1;
    return;
  }

  button.children[0];
  button.children[0].src = "images/spinner.gif";
  return update_event(table, id_evento, { validate_event: 1 }, event_rep, row);
}

function in_process_event(table, id_evento, event_rep, row) {
  var button = document.getElementById("proc-" + id_evento);
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
    row
  );
}

function delete_event(table, id_evento, event_rep, row) {
  var button = document.getElementById("del-" + id_evento);
  if (!button) {
    // Button does not exist. Ignore.
    processed += 1;
    return;
  }

  button.children[0];
  button.children[0].src = "images/spinner.gif";
  return update_event(table, id_evento, { delete_event: 1 }, event_rep, row);
}

// Imported from old files.
function execute_event_response(event_list_btn) {
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
          // Parent: TD. GrandParent: TR.
          in_process_event(
            dt_events,
            $(this).val(),
            $(this).attr("event_rep"),
            this.parentElement.parentElement
          );
        });
        break;
      case "validate_selected":
        $(".chk_val:checked").each(function() {
          validate_event(
            dt_events,
            $(this).val(),
            $(this).attr("event_rep"),
            this.parentElement.parentElement
          );
        });
        break;
      case "delete_selected":
        $(".chk_val:checked").each(function() {
          delete_event(
            dt_events,
            $(this).val(),
            $(this).attr("event_rep"),
            this.parentElement.parentElement
          );
        });
        break;
    }
  }
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
    var server_id = $("#hidden-server_id_" + event_id).val();
    response["target"] = get_response_target(
      event_id,
      response_id,
      server_id,
      response_command
    );

    if (total_checked - 1 === counter) end = 1;

    show_massive_response_dialog(event_id, response_id, response, counter, end);

    counter++;
  });
}
