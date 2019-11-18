/* eslint-disable no-unused-vars */
/* global $, load_modal, generalShowMsg, confirmDialog */

function allowDrop(ev) {
  ev.preventDefault();
}

function drag(ev) {
  ev.dataTransfer.setData("html", ev.target.outerHTML);
}

function drop(ev) {
  ev.preventDefault();
  var data = document.createElement("span");
  var content = ev.dataTransfer.getData("html");
  if (content.includes("nexo")) {
    content = "<br/>" + content;
  }
  data.innerHTML = content;
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
