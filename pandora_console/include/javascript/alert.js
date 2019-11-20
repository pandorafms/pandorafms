/* eslint-disable no-unused-vars */
/* global $, load_modal, generalShowMsg, confirmDialog */

function allowDrop(ev) {
  ev.preventDefault();
}

function drag(ev) {
  ev.dataTransfer.setData("html", ev.target.outerHTML);
}

function edit(id, str) {
  // If not defined id return.
  console.log(id);

  if (id == "variable-text") {
    return;
  }

  // Value input hidden.
  var valueHidden = $("#hidden-json-rule").val();

  // Convert to array.
  var arrayValueHidden = JSON.parse(valueHidden);

  // Extract to id number row.
  var numberField = id.replace("element-", "");

  // Check do no undefined.
  if (arrayValueHidden[numberField] != undefined) {
    // Change value.
    arrayValueHidden[numberField].value = str;

    // Update value json-rule.
    $("#hidden-json-rule").val(JSON.stringify(arrayValueHidden));
  }

  return;
}

function drop(ev) {
  ev.preventDefault();

  // Source Element.
  var content = ev.dataTransfer.getData("html");

  // Extract ID.
  var id = $(content).attr("id");

  // Extract clas type.
  var classType = $(content)
    .attr("class")
    .split(/\s+/)[0];

  // Remove Class.
  content = $(content).removeClass(classType);

  // Input hidden.
  var valueHidden = $("#hidden-json-rule").val();

  // Initialize stack.
  var stack = [];
  // Check first.
  if (valueHidden != 0) {
    // Value decode.
    stack = JSON.parse(valueHidden);
  }

  // Change ID for non repeat and use variable change text.
  content = $(content).attr("id", "element-" + stack.length);

  // Add stack.
  stack.push({
    type: classType,
    element: id.replace(classType + "-", ""),
    value: $(content).text()
  });

  // Convert to json tring for value input hidden.
  var stackString = JSON.stringify(stack);

  // Set input hidden.
  $("#hidden-json-rule").val(stackString);

  // Next button to submit is disabled
  $("#submit-rule").attr("disabled", true);

  // Source class type action.
  switch (classType) {
    case "fields":
      $(".fields").addClass("opacityElements");
      $(".fields").attr("draggable", false);

      $(".operators").removeClass("opacityElements");
      $(".operators").attr("draggable", true);
      break;

    case "operators":
      $(".operators").addClass("opacityElements");
      $(".operators").attr("draggable", false);

      $(".variables").removeClass("opacityElements");
      $(".variables").attr("draggable", true);
      break;

    case "variables":
      $(".variables").addClass("opacityElements");
      $(".variables").attr("draggable", false);

      $(".modifiers").removeClass("opacityElements");
      $(".modifiers").attr("draggable", true);
      $(".nexos").removeClass("opacityElements");
      $(".nexos").attr("draggable", true);
      $("#submit-rule").attr("disabled", false);
      break;

    case "modifiers":
      $(".modifiers").addClass("opacityElements");
      $(".modifiers").attr("draggable", false);
      $(".nexos").addClass("opacityElements");
      $(".nexos").attr("draggable", false);

      $(".variables").removeClass("opacityElements");
      $(".variables").attr("draggable", true);
      break;

    case "nexos":
      $(".modifiers").addClass("opacityElements");
      $(".modifiers").attr("draggable", false);
      $(".nexos").addClass("opacityElements");
      $(".nexos").attr("draggable", false);

      $(".fields").removeClass("opacityElements");
      $(".fields").attr("draggable", true);
      break;
    default:
      break;
  }

  // Create content.
  var data = document.createElement("span");

  content = $(content).prop("outerHTML");
  // If content nexo line break.
  if (content.includes("nexo")) {
    content = "<br/>" + content;
  }

  // Add source element in content.
  data.innerHTML = content;

  // Add content to target.
  document.getElementById(ev.target.id).appendChild(data);
}

function add_alert_action(settings) {
  load_modal({
    target: $("#modal-add-action-form"),
    form: "modal_form_add_actions",
    url: settings.url_ajax,
    modal: {
      title: settings.title,
      cancel: settings.btn_cancel,
      ok: settings.btn_text
    },
    onshow: {
      page: settings.url,
      method: "addAlertActionForm",
      extradata: {
        id: settings.id
      }
    },
    onsubmit: {
      page: settings.url,
      method: "addAlertAction",
      dataType: "json"
    },
    ajax_callback: add_alert_action_acept,
    idMsgCallback: "msg-add-action"
  });
}

function add_alert_action_acept(data, idMsg) {
  if (data.error === 1) {
    console.log(data.text);
    return;
  }

  if ($("#emptyli-al-" + data.id_alert).length > 0) {
    $("#emptyli-al-" + data.id_alert).remove();
  }

  $.ajax({
    method: "post",
    url: data.url,
    data: {
      page: data.page,
      method: "addRowActionAjax",
      id_alert: data.id_alert,
      id_action: data.id_action
    },
    dataType: "html",
    success: function(li) {
      $(".ui-dialog-content").dialog("close");
      $("#ul-al-" + data.id_alert).append(li);
    },
    error: function(error) {
      console.log(error);
    }
  });
}

function delete_alert_action(settings) {
  confirmDialog({
    title: settings.title,
    message: settings.msg,
    onAccept: function() {
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: settings.page,
          method: "deleteActionAlert",
          id_alert: settings.id_alert,
          id_action: settings.id_action
        },
        dataType: "json",
        success: function(data) {
          // Delete row table.
          $(
            "#li-al-" + settings.id_alert + "-act-" + settings.id_action
          ).remove();

          var num_row = $("#ul-al-" + settings.id_alert + " li").length;
          if (num_row === 0) {
            var emptyli =
              "<li id='emptyli-al-" +
              settings.id_alert +
              "'>" +
              settings.emptyli +
              "</li>";
            $("#ul-al-" + settings.id_alert).append(emptyli);
          }
        },
        error: function(error) {
          console.log(error);
        }
      });
    }
  });
}

function standby_alert(settings) {
  confirmDialog({
    title: settings.title,
    message: settings.msg,
    onAccept: function() {
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: settings.page,
          method: "standByAlert",
          id_alert: settings.id_alert,
          standby: settings.standby
        },
        dataType: "html",
        success: function(data) {
          $("#standby-alert-" + settings.id_alert).empty();
          $("#standby-alert-" + settings.id_alert).append(data);
        },
        error: function(error) {
          console.log(error);
        }
      });
    }
  });
}

function disabled_alert(settings) {
  confirmDialog({
    title: settings.title,
    message: settings.msg,
    onAccept: function() {
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: settings.page,
          method: "disabledAlert",
          id_alert: settings.id_alert,
          disabled: settings.disabled
        },
        dataType: "json",
        success: function(data) {
          $("#disabled-alert-" + settings.id_alert).empty();
          $("#disabled-alert-" + settings.id_alert).append(data.disabled);
          $("#status-alert-" + settings.id_alert).empty();
          $("#status-alert-" + settings.id_alert).append(data.status);
        },
        error: function(error) {
          console.log(error);
        }
      });
    }
  });
}
