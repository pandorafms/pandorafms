/* global $ */
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
 * Display a confirm dialog box
 *
 * @param string Text to display
 * @param string Ok button text
 * @param string Cancel button text
 * @param function Callback to action when ok button is pressed
 */
function display_confirm_dialog(message, ok_text, cancel_text, ok_function) {
  // Clean function to close the dialog
  var clean_function = function() {
    $("#pandora_confirm_dialog_text").hide();
    $("#pandora_confirm_dialog_text").remove();
  };

  // Modify the ok function to close the dialog too
  var ok_function_clean = function() {
    ok_function();
    clean_function();
  };

  var buttons_obj = {};
  buttons_obj[cancel_text] = clean_function;
  buttons_obj[ok_text] = ok_function_clean;

  // Display the dialog
  $("body").append(
    '<div id="pandora_confirm_dialog_text"><h3>' + message + "</h3></div>"
  );
  $("#pandora_confirm_dialog_text").dialog({
    resizable: false,
    draggable: true,
    modal: true,
    dialogClass: "pandora_confirm_dialog",
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    closeOnEscape: true,
    modal: true,
    buttons: buttons_obj
  });
}

// Advanced Form control.
function load_modal(settings) {
  var AJAX_RUNNING = 0;
  var data = new FormData();
  if (settings.extradata) {
    settings.extradata.forEach(function(item) {
      if (item.value != undefined) data.append(item.name, item.value);
    });
  }
  data.append("page", settings.onshow.page);
  data.append("method", settings.onshow.method);

  var width = 630;
  if (settings.onshow.width) {
    width = settings.onshow.width;
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
    required_buttons.push({
      class:
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
      text: settings.modal.ok,
      click: function() {
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

          if (flagError === false) {
            $.ajax({
              method: "post",
              url: settings.url,
              processData: false,
              contentType: false,
              data: formdata,
              dataType: settings.onsubmit.dataType,
              success: function(data) {
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
          $(this).dialog("close");
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
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        buttons: required_buttons,
        closeOnEscape: false,
        open: function() {
          $(".ui-dialog-titlebar-close").hide();
        }
      });
    },
    error: function(data) {
      // console.log(data);
    }
  });
}

//Function that shows a dialog box to confirm closures of generic manners. The modal id is random
function confirmDialog(settings) {
  var randomStr =
    Math.random()
      .toString(36)
      .substring(2, 15) +
    Math.random()
      .toString(36)
      .substring(2, 15);

  $("body").append(
    '<div id="confirm_' + randomStr + '">' + settings.message + "</div>"
  );
  $("#confirm_" + randomStr);
  $("#confirm_" + randomStr)
    .dialog({
      title: settings.title,
      close: false,
      width: 350,
      modal: true,
      buttons: [
        {
          text: "Cancel",
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel",
          click: function() {
            $(this).dialog("close");
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
