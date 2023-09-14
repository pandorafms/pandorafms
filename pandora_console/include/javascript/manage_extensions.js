/* globals $, page, url, textsToTranslate, confirmDialog*/
$(document).ready(function() {
  function loading(status) {
    if (status) {
      $(".spinner-fixed").show();
      $("#button-upload_button").attr("disabled", "true");
    } else {
      $(".spinner-fixed").hide();
      $("#button-upload_button").removeAttr("disabled");
    }
  }

  $("#uploadExtension").submit(function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append("page", page);
    formData.append("method", "validateIniName");
    loading(true);
    $.ajax({
      method: "POST",
      url: url,
      data: formData,
      processData: false,
      contentType: false,
      success: function(data) {
        loading(false);
        data = JSON.parse(data);
        if (data.success) {
          if (data.warning) {
            confirmDialog({
              title: textsToTranslate["Warning"],
              message: data.message,
              strOKButton: textsToTranslate["Confirm"],
              strCancelButton: textsToTranslate["Cancel"],
              onAccept: function() {
                loading(true);
                $("#uploadExtension")[0].submit();
              },
              onDeny: function() {
                return false;
              }
            });
          } else {
            $("#uploadExtension")[0].submit();
          }
        } else {
          confirmDialog({
            title: textsToTranslate["Error"],
            message: data.message,
            ok: textsToTranslate["Ok"],
            hideCancelButton: true,
            onAccept: function() {
              return false;
            }
          });
        }
      },
      error: function() {
        loading(false);
        confirmDialog({
          title: textsToTranslate["Error"],
          message: textsToTranslate["Failed to upload extension"],
          ok: textsToTranslate["Ok"],
          hideCancelButton: true,
          onAccept: function() {
            return false;
          }
        });
      }
    });
  });
});

/**
 * Loads modal from AJAX to add a new key or edit an existing one.
 */
function show_migration_form(shortName, hash) {
  var btn_ok_text = textsToTranslate["Migrate"];
  var btn_cancel_text = textsToTranslate["Cancel"];
  var title = textsToTranslate["Migrate"];
  var method = "migrateApp";

  load_modal({
    target: $("#migrate_modal"),
    form: "modal_migrate_form",
    url: url,
    ajax_callback: showMsg,
    modal: {
      title: title,
      ok: btn_ok_text,
      cancel: btn_cancel_text
    },
    extradata: [
      {
        name: "shortName",
        value: shortName
      },
      {
        name: "hash",
        value: hash
      }
    ],
    onshow: {
      page: page,
      method: "loadMigrateModal"
    },
    onsubmit: {
      page: page,
      method: method
    }
  });
}

/**
 * Process ajax responses and shows a dialog with results.
 */
function showMsg(data) {
  var title = textsToTranslate["migrationSuccess"];
  var text = "";
  var failed = 0;
  try {
    data = JSON.parse(data);
    text = data["result"];
  } catch (err) {
    title = textsToTranslate["Error"];
    text = err.message;
    failed = 1;
  }
  if (!failed && data["error"] != undefined) {
    title = textsToTranslate["Error"];
    text = data["error"];
    failed = 1;
  }
  if (data["report"] != undefined) {
    data["report"].forEach(function(item) {
      text += "<br>" + item;
    });
  }

  $("#msg").empty();
  $("#msg").html(text);
  $("#msg").dialog({
    width: 450,
    position: {
      my: "center",
      at: "center",
      of: window,
      collision: "fit"
    },
    title: title,
    buttons: [
      {
        class:
          "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
        text: "OK",
        click: function(e) {
          if (!failed) {
            $(".ui-dialog-content").dialog("close");
            $(".info").hide();
          } else {
            $(this).dialog("close");
          }
        }
      }
    ]
  });
}
