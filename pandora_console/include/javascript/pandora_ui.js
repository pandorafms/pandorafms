/* global $ uniqId*/
/* exported load_modal */
/*JS to Show user modals :
  - Confirm dialogs.
  - Display dialogs.
  - Load modal windows.
  - Logo Previews.
  - General show messages.
*/

var ENTERPRISE_DIR = "enterprise";

/**
 * Display a dialog with an image
 *
 * @param {string} icon_name The name of the icon you will display
 * @param {string} icon_path The path to the icon
 * @param {Object} incoming_options All options
 * 		grayed: {bool} True to display the background black
 * 		title {string} 'Logo preview' by default
 */
function logo_preview(icon_name, icon_path, incoming_options) {
  // Get the options
  options = {
    grayed: false,
    title: "Logo preview"
  };
  $.extend(options, incoming_options);

  if (icon_name == "") return;

  $dialog = $("<div></div>");
  $image = $('<img src="' + icon_path + '">');
  $image.css("max-width", "500px").css("max-height", "500px");

  try {
    $dialog
      .hide()
      .html($image)
      .dialog({
        title: options.title,
        resizable: true,
        draggable: true,
        modal: true,
        dialogClass: options.grayed ? "dialog-grayed" : "",
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        minHeight: 1,
        width: $image.width,
        close: function() {
          $dialog.empty().remove();
        }
      })
      .show();
  } catch (err) {
    // console.log(err);
  }
}

// Advanced Form control.
// eslint-disable-next-line no-unused-vars
function load_modal(settings) {
  var AJAX_RUNNING = 0;
  var data = new FormData();
  if (settings.extradata) {
    settings.extradata.forEach(function(item) {
      if (item.value != undefined) {
        if (item.value instanceof Object || item.value instanceof Array) {
          data.append(item.name, JSON.stringify(item.value));
        } else {
          data.append(item.name, item.value);
        }
      }
    });
  }
  data.append("page", settings.onshow.page);
  data.append("method", settings.onshow.method);
  if (settings.onshow.extradata != undefined) {
    data.append("extradata", JSON.stringify(settings.onshow.extradata));
  }

  if (settings.target == undefined) {
    var uniq = uniqId();
    var div = document.createElement("div");
    div.id = "div-modal-" + uniq;
    div.style.display = "none";

    document.getElementById("main").append(div);

    var id_modal_target = "#div-modal-" + uniq;

    settings.target = $(id_modal_target);
  }

  var width = 630;
  if (settings.onshow.width) {
    width = settings.onshow.width;
  }

  if (settings.modal.overlay == undefined) {
    settings.modal.overlay = {
      opacity: 0.5,
      background: "black"
    };
  }

  if (settings.beforeClose == undefined) {
    settings.beforeClose = function() {};
  }

  settings.target.html("Loading modal...");
  settings.target
    .dialog({
      title: "Loading",
      close: false,
      width: 200,
      buttons: []
    })
    .show();
  var required_buttons = [];
  if (settings.modal.cancel != undefined) {
    //The variable contains a function
    // that is responsible for executing the method it receives from settings
    // which confirms the closure of a modal
    var cancelModal = function() {
      settings.target.dialog("close");
      if (AJAX_RUNNING) return;
      AJAX_RUNNING = 1;
      var formdata = new FormData();

      formdata.append("page", settings.oncancel.page);
      formdata.append("method", settings.oncancel.method);

      $.ajax({
        method: "post",
        url: settings.url,
        processData: false,
        contentType: false,
        data: formdata,
        success: function(data) {
          if (typeof settings.oncancel.callback == "function") {
            settings.oncancel.callback(data);
            settings.target.dialog("close");
          }
          AJAX_RUNNING = 0;
        },
        error: function(data) {
          // console.log(data);
          AJAX_RUNNING = 0;
        }
      });
    };

    required_buttons.push({
      class:
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel",
      text: settings.modal.cancel,
      click: function() {
        if (settings.oncancel != undefined) {
          if (typeof settings.oncancel.confirm == "function") {
            //receive function
            settings.oncancel.confirm(cancelModal);
          } else if (settings.oncancel != undefined) {
            cancelModal();
          }
        } else {
          $(this).dialog("close");
        }
      }
    });
  }

  if (settings.modal.ok != undefined) {
    var btnClickHandler = function(d) {
      if (AJAX_RUNNING) return;
      if (settings.onsubmit != undefined) {
        if (settings.onsubmit.preaction != undefined) {
          settings.onsubmit.preaction();
        }
        AJAX_RUNNING = 1;
        if (settings.onsubmit.dataType == undefined) {
          settings.onsubmit.dataType = "html";
        }

        var formdata = new FormData();
        if (settings.extradata) {
          settings.extradata.forEach(function(item) {
            if (item.value != undefined)
              formdata.append(item.name, item.value);
          });
        }
        formdata.append("page", settings.onsubmit.page);
        formdata.append("method", settings.onsubmit.method);

        var flagError = false;
        if (Array.isArray(settings.form) === false) {
          $("#" + settings.form + " :input").each(function() {
            if (this.checkValidity() === false) {
              $(this).attr("title", this.validationMessage);
              $(this).tooltip({
                tooltipClass: "uitooltip",
                position: {
                  my: "right bottom",
                  at: "right top",
                  using: function(position, feedback) {
                    $(this).css(position);
                    $("<div>")
                      .addClass("arrow")
                      .addClass(feedback.vertical)
                      .addClass(feedback.horizontal)
                      .appendTo(this);
                  }
                }
              });
              $(this).tooltip("open");
  
              var element = $(this);
              setTimeout(
                function(element) {
                  element.tooltip("destroy");
                  element.removeAttr("title");
                },
                3000,
                element
              );

              flagError = true;
            }

            if (this.type == "file") {
              if ($(this).prop("files")[0]) {
                formdata.append(this.name, $(this).prop("files")[0]);
              }
            } else {
              if ($(this).attr("type") == "checkbox") {
                if (this.checked) {
                  formdata.append(this.name, "on");
                }
              } else {
                formdata.append(this.name, $(this).val());
              }
            }
          });
        } else {
          settings.form.forEach(function(element) {
            $("#" + element + " :input, #" + element + " textarea").each(
              function() {
                // TODO VALIDATE ALL INPUTS.
                if (this.type == "file") {
                  if ($(this).prop("files")[0]) {
                    formdata.append(this.name, $(this).prop("files")[0]);
                  }
                } else {
                  if ($(this).attr("type") == "checkbox") {
                    if (this.checked) {
                      formdata.append(this.name, "on");
                    }
                  } else {
                    formdata.append(this.name, $(this).val());
                  }
                }
              }
            );
          });
        }

        if (flagError === false) {
          if (
            settings.onsubmitClose != undefined &&
            settings.onsubmitClose == 1
          ) {
            d.dialog("close");
          }

          $.ajax({
            method: "post",
            url: settings.url,
            processData: false,
            contentType: false,
            data: formdata,
            dataType: settings.onsubmit.dataType,
            success: function(data) {
                console.log("successsssssssssssss");
                console.log(data);
              if (settings.ajax_callback != undefined) {
                if (settings.idMsgCallback != undefined) {
                  settings.ajax_callback(data, settings.idMsgCallback);
                } else {
                  settings.ajax_callback(data);
                }
              }
              AJAX_RUNNING = 0;
            }
          });
        } else {
          AJAX_RUNNING = 0;
        }
      } else {
        // No onsumbit configured. Directly close.
        d.dialog("close");
        if (document.getElementById(settings.form) != undefined) {
          document.getElementById(settings.form).submit();
        }
      }
    }

    required_buttons.push({
      class:
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
      text: settings.modal.ok,
      click: function() {
        if (settings.onsubmit != undefined && settings.onsubmit.onConfirmSubmit != undefined) {
            settings.onsubmit.onConfirmSubmit(btnClickHandler, $(this));
        } else {
            btnClickHandler($(this));
        }
      },
      error: function(data) {
        // console.log(data);
        AJAX_RUNNING = 0;
      }
    });
  }

  $.ajax({
    method: "post",
    url: settings.url,
    processData: false,
    contentType: false,
    data: data,
    success: function(data) {
      if (settings.onshow.parser) {
        data = settings.onshow.parser(data);
      } else {
        data = (function(d) {
          try {
            d = JSON.parse(d);
          } catch (e) {
            // Not JSON
            return d;
          }
          if (d.error) return d.error;

          if (d.result) return d.result;
        })(data);
      }
      settings.target.html(data);
      if (settings.onload != undefined) {
        settings.onload(data);
      }
      settings.target.dialog({
        resizable: true,
        draggable: true,
        modal: true,
        title: settings.modal.title,
        width: width,
        minHeight:
          settings.onshow.minHeight != undefined
            ? settings.onshow.minHeight
            : "auto",
        maxHeight:
          settings.onshow.maxHeight != undefined
            ? settings.onshow.maxHeight
            : "auto",
        overlay: settings.modal.overlay,
        position: {
          my: "top+20%",
          at: "top",
          of: window,
          collision: "fit"
        },
        buttons: required_buttons,
        closeOnEscape: true,
        open: function() {
          //$(".ui-dialog-titlebar-close").hide();
        },
        close: function() {
          if (id_modal_target != undefined) {
            $(id_modal_target).remove();
          }

          if (settings.cleanup != undefined) {
            settings.cleanup();
          }

          $(this).dialog("destroy");
        },
        beforeClose: settings.beforeClose()
      });
    },
    error: function(data) {
      // console.log(data);
    }
  });
}

// Function that shows a dialog box to confirm closures of generic manners.
// The modal id is random.
// eslint-disable-next-line no-unused-vars
function confirmDialog(settings) {
  var randomStr = uniqId();

  if (settings.size == undefined) {
    settings.size = 350;
  }

  if (settings.maxHeight == undefined) {
    settings.maxHeight = 1000;
  }

  if (typeof settings.message == "function") {
    $("body").append(
      '<div id="confirm_' + randomStr + '">' + settings.message() + "</div>"
    );
  } else {
    $("body").append(
      '<div id="confirm_' + randomStr + '">' + settings.message + "</div>"
    );
  }

  $("#confirm_" + randomStr);
  $("#confirm_" + randomStr)
    .dialog({
      title: settings.title,
      close: false,
      width: settings.size,
      maxHeight: settings.maxHeight,
      modal: true,
      buttons: [
        {
          text: "Cancel",
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel",
          click: function() {
            $(this).dialog("close");
            $(this).remove();
            if (typeof settings.onDeny == "function") settings.onDeny();
          }
        },
        {
          text: "Ok",
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
          click: function() {
            $(this).dialog("close");
            if (typeof settings.onAccept == "function") settings.onAccept();
            $(this).remove();
          }
        }
      ]
    })
    .show();
}

/**
 * Function to show modal with message Validation.
 *
 * @param {json} data Json example:
 * $return = [
 *  'error' => 0 or 1,
 *  'title' => [
 *    Failed,
 *    Success,
 *  ],
 *  'text'  => [
 *    Failed,
 *    Success,
 *  ],
 *];
 * @param {string} idMsg ID div charge modal.
 *
 * @return {void}
 */
// eslint-disable-next-line no-unused-vars
function generalShowMsg(data, idMsg) {
  var title = data.title[data.error];
  var text = data.text[data.error];
  var failed = !data.error;

  $("#" + idMsg).empty();
  $("#" + idMsg).html(text);
  $("#" + idMsg).dialog({
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
          } else {
            $(this).dialog("close");
          }
        }
      }
    ]
  });
}

function infoMessage(data, idMsg) {
  var title = data.title;
  var err_messge = data.text;

  if (idMsg == null) {
    idMsg = uniqId();
  }

  if ($("#" + idMsg).length === 0) {
    $("body").append('<div title="' + title + '" id="' + idMsg + '"></div>');
    $("#" + idMsg).empty();
  }

  $("#err_msg").empty();
  $("#err_msg").html("\n\n" + err_messge);

  $("#" + idMsg)
    .dialog({
      height: 250,
      width: 528,
      opacity: 1,
      modal: true,
      position: {
        my: "center",
        at: "center",
        of: window,
        collision: "fit"
      },
      title: data.title,
      buttons: [
        {
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
          text: "Retry",
          click: function(e) {
            handleConnection();
          }
        },
        {
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-cancel",
          text: "Close",
          click: function() {
            $(this).dialog("close");
          }
        }
      ],

      open: function(event, ui) {
        $(".ui-widget-overlay").addClass("error-modal-opened");
      },
      close: function(event, ui) {
        $(".ui-widget-overlay").removeClass("error-modal-opened");
      }
    })
    .show();
}

function reveal_password(name) {
  var passwordElement = $("#password-" + name);
  var revealElement = $("#reveal_password_" + name);
  var imagesPath = "";

  if ($("#hidden-metaconsole_activated").val() == 1) {
    imagesPath = "../../images/";
  } else {
    imagesPath = "images/";
  }

  if (passwordElement.attr("type") == "password") {
    passwordElement.attr("type", "text");
    revealElement.attr("src", imagesPath + "eye_hide.png");
  } else {
    passwordElement.attr("type", "password");
    revealElement.attr("src", imagesPath + "eye_show.png");
  }
}
