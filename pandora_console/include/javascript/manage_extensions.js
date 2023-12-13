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
  var title = textsToTranslate["Migrate"];
  var method = "migrateApp";

  $("#migrate_modal").dialog({
    resizable: true,
    draggable: true,
    modal: true,
    width: 630,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    closeOnEscape: false,
    title: title,
    open: function() {
      $("#migrate_modal").empty();
      $.ajax({
        type: "POST",
        url: url,
        data: {
          page: page,
          method: "loadMigrateModal",
          shortName: shortName,
          hash: hash
        },
        dataType: "html",
        success: function(data) {
          $("#migrate_modal").append(data);

          $("#button-migrate").click(function() {
            // All fields are required.
            loadingMigration(true);
            $.ajax({
              type: "POST",
              url: url,
              data: {
                page: page,
                method: method,
                hash: $("#text-hash").val(),
                shortName: shortName
              },
              success: function(data) {
                loadingMigration(false);
                showMsg(data, shortName);
              },
              error: function(e) {
                loadingMigration(false);
                e.error = e.statusText;
                showMsg(JSON.stringify(e));
              }
            });
          });

          $("#button-cancel").click(function() {
            $("#migrate_modal").dialog("close");
          });
        }
      });
    }
  });

  $(".ui-widget-overlay").css("background", "#000");
  $(".ui-widget-overlay").css("opacity", 0.6);
  $(".ui-draggable").css("cursor", "inherit");
}

/**
 * Process ajax responses and shows a dialog with results.
 */
function showMsg(data, shortName) {
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
  } else {
    $("input[name='button_migrate-" + shortName + "']").hide();
    $("#migrate_modal").dialog("close");
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

function loadingMigration(status) {
  if (status) {
    $("#migration-spinner").show();
    $("#button-migrate").attr("disabled", "true");
    $("#button-cancel").attr("disabled", "true");
  } else {
    $("#migration-spinner").hide();
    $("#button-migrate").removeAttr("disabled");
    $("#button-cancel").removeAttr("disabled");
  }
}
