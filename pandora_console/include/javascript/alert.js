/* eslint-disable no-unused-vars */
/* global $, load_modal, generalShowMsg, confirmDialog, jQuery */

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

function ajax_get_integria_custom_fields(
  ticket_type_id,
  values,
  recovery_values,
  max_macro_fields
) {
  values = values || [];
  recovery_values = recovery_values || [];

  if (
    ticket_type_id === null ||
    ticket_type_id === "" ||
    (Array.isArray(values) &&
      values.length === 0 &&
      Array.isArray(recovery_values) &&
      recovery_values.length === 0)
  ) {
    for (let i = 8; i <= max_macro_fields; i++) {
      $("[name=field" + i + "_value\\[\\]").val("");
      $("[name=field" + i + "_recovery_value\\[\\]").val("");
    }
  }

  // On ticket type change, hide all table rows and inputs corresponding to custom fields, regardless of what its type is.
  for (let i = 8; i <= max_macro_fields; i++) {
    $("[name=field" + i + "_value\\[\\]").hide();
    $("[name=field" + i + "_recovery_value\\[\\]").hide();
    $("#table_macros-field" + i).hide();
    $("[name=field" + i + "_value_container").hide();
    $("[name=field" + i + "_recovery_value_container").hide();
  }

  jQuery.post(
    "ajax.php",
    {
      page: "godmode/alerts/configure_alert_action",
      get_integria_ticket_custom_types: 1,
      ticket_type_id: ticket_type_id
    },
    function(data) {
      data.forEach(function(custom_field, key) {
        var custom_field_key = key + 8; // Custom fields start from field 8.

        if (custom_field_key > max_macro_fields) {
          return;
        }

        // Display field row for current input.
        var custom_field_row = $("#table_macros-field" + custom_field_key);
        custom_field_row.show();

        // Replace label text of field row for current input.
        var label_html = $("#table_macros-field" + custom_field_key + " td")
          .first()
          .html();
        var label_name = label_html.split("<br>")[0];
        var new_html_content = custom_field_row
          .html()
          .replace(label_name, custom_field.label);
        custom_field_row.html(new_html_content);

        switch (custom_field.type) {
          case "CHECKBOX":
            var checkbox_selector = $(
              'input[type="checkbox"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            );
            var checkbox_recovery_selector = $(
              'input[type="checkbox"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            );

            checkbox_selector.on("change", function() {
              if (checkbox_selector.prop("checked")) {
                checkbox_selector.attr("value", "1");
              } else {
                checkbox_selector.attr("value", "0");
              }
            });

            checkbox_recovery_selector.on("change", function() {
              if (checkbox_recovery_selector.prop("checked")) {
                checkbox_recovery_selector.attr("value", "1");
              } else {
                checkbox_recovery_selector.attr("value", "0");
              }
            });

            if (typeof values[key] !== "undefined") {
              if (values[key] == 1) {
                checkbox_selector.prop("checked", true);
                checkbox_selector.attr("value", "1");
              } else {
                checkbox_selector.prop("checked", false);
                checkbox_selector.attr("value", "0");
              }
            }

            if (typeof recovery_values[key] !== "undefined") {
              if (recovery_values[key] == 1) {
                checkbox_recovery_selector.prop("checked", true);
                checkbox_recovery_selector.attr("value", "1");
              } else {
                checkbox_recovery_selector.prop("checked", false);
                checkbox_recovery_selector.attr("value", "0");
              }
            }

            $("[name=field" + custom_field_key + "_value_container]").show();
            $(
              "[name=field" + custom_field_key + "_recovery_value_container]"
            ).show();
            $(
              'input[type="checkbox"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            ).show();
            $(
              'input[type="checkbox"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).show();
            break;
          case "COMBO":
            var combo_input = $(
              "select[name=field" + custom_field_key + "_value\\[\\]]"
            );
            var combo_input_recovery = $(
              "select[name=field" + custom_field_key + "_recovery_value\\[\\]]"
            );

            combo_input.find("option").remove();
            combo_input_recovery.find("option").remove();

            var combo_values_array = custom_field.comboValue.split(",");

            combo_values_array.forEach(function(value) {
              combo_input.append(
                $("<option>", {
                  value: value,
                  text: value
                })
              );

              combo_input_recovery.append(
                $("<option>", {
                  value: value,
                  text: value
                })
              );
            });

            if (typeof values[key] !== "undefined") {
              combo_input.val(values[key]);
            }

            if (typeof recovery_values[key] !== "undefined") {
              combo_input_recovery.val(recovery_values[key]);
            }

            combo_input.show();
            combo_input_recovery.show();
            break;
          case "DATE":
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            ).removeClass("hasDatepicker");
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).removeClass("hasDatepicker");
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            ).datepicker("destroy");
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).datepicker("destroy");
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            ).show();
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).show();
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            ).datepicker({ dateFormat: "<?php echo 'yy-mm-dd 00:00:00'; ?>" });
            $(
              'input.datepicker[type="text"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).datepicker({ dateFormat: "<?php echo 'yy-mm-dd 00:00:00'; ?>" });
            $.datepicker.setDefaults(
              $.datepicker.regional["<?php echo get_user_language(); ?>"]
            );

            if (typeof values[key] !== "undefined") {
              $(
                'input.datepicker[type="text"][name=field' +
                  custom_field_key +
                  "_value\\[\\]]"
              ).val(values[key]);
            }

            if (typeof recovery_values[key] !== "undefined") {
              $(
                'input.datepicker[type="text"][name=field' +
                  custom_field_key +
                  "_recovery_value\\[\\]]"
              ).val(recovery_values[key]);
            }
            break;
          case "NUMERIC":
            if (typeof values[key] !== "undefined") {
              $(
                'input[type="number"][name=field' +
                  custom_field_key +
                  "_value\\[\\]]"
              ).val(values[key]);
            }

            if (typeof recovery_values[key] !== "undefined") {
              $(
                'input[type="number"][name=field' +
                  custom_field_key +
                  "_recovery_value\\[\\]]"
              ).val(recovery_values[key]);
            }

            $(
              'input[type="number"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            ).show();
            $(
              'input[type="number"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).show();
            break;
          case "TEXT":
            if (typeof values[key] !== "undefined") {
              $(
                'input.normal[type="text"][name=field' +
                  custom_field_key +
                  "_value\\[\\]]"
              ).val(values[key]);
            }

            if (typeof recovery_values[key] !== "undefined") {
              $(
                'input.normal[type="text"][name=field' +
                  custom_field_key +
                  "_recovery_value\\[\\]]"
              ).val(recovery_values[key]);
            }

            $(
              'input.normal[type="text"][name=field' +
                custom_field_key +
                "_value\\[\\]]"
            ).show();
            $(
              'input.normal[type="text"][name=field' +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).show();
            break;
          case "TEXTAREA":
          default:
            if (typeof values[key] !== "undefined") {
              $("textarea[name=field" + custom_field_key + "_value\\[\\]]").val(
                values[key]
              );
            }

            if (typeof recovery_values[key] !== "undefined") {
              $(
                "textarea[name=field" +
                  custom_field_key +
                  "_recovery_value\\[\\]]"
              ).val(recovery_values[key]);
            }

            $(
              "textarea[name=field" + custom_field_key + "_value\\[\\]]"
            ).show();
            $(
              "textarea[name=field" +
                custom_field_key +
                "_recovery_value\\[\\]]"
            ).show();
            break;
        }
      });
    },
    "json"
  );
}
