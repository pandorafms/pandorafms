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

    if (document.getElementById("main") == null) {
      // MC env.
      document.getElementById("page").append(div);
    } else {
      document.getElementById("main").append(div);
    }

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
      id: settings.modal.cancel_button_id,
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
            if (item.value != undefined) formdata.append(item.name, item.value);
          });
        }
        formdata.append("page", settings.onsubmit.page);
        formdata.append("method", settings.onsubmit.method);

        var flagError = false;
        if (Array.isArray(settings.form) === false) {
          $("#" + settings.form + " :input").each(function() {
            if (this.checkValidity() === false) {
              var select2 = $(this).attr("data-select2-id");
              if (typeof select2 !== typeof undefined && select2 !== false) {
                $(this)
                  .next()
                  .attr("title", this.validationMessage);
                $(this)
                  .next()
                  .tooltip({
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
                $(this)
                  .next()
                  .tooltip("open");

                var element = $(this).next();
                setTimeout(
                  function(element) {
                    element.tooltip("destroy");
                    element.removeAttr("title");
                  },
                  3000,
                  element
                );
              } else {
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
              }

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
              if (settings.ajax_callback != undefined) {
                if (settings.idMsgCallback != undefined) {
                  settings.ajax_callback(data, settings.idMsgCallback);
                } else {
                  settings.ajax_callback(data);
                }
              } else {
                generalShowMsg(data, null);
              }

              AJAX_RUNNING = 0;
            },
            error: function(response) {
              generalShowMsg(
                {
                  title: "Failed",
                  text: response.responseText
                },
                null
              );
              AJAX_RUNNING = 0;
            }
          });
        } else {
          AJAX_RUNNING = 0;
        }
      } else {
        if (Array.isArray(settings.form) === false) {
          $("#" + settings.form + " :input").each(function() {
            if (this.checkValidity() === false) {
              var select2 = $(this).attr("data-select2-id");
              if (typeof select2 !== typeof undefined && select2 !== false) {
                $(this)
                  .next()
                  .attr("title", this.validationMessage);
                $(this)
                  .next()
                  .tooltip({
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
                $(this)
                  .next()
                  .tooltip("open");

                var element = $(this).next();
                setTimeout(
                  function(element) {
                    element.tooltip("destroy");
                    element.removeAttr("title");
                  },
                  3000,
                  element
                );
              } else {
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
              }

              flagError = true;
            }
          });
        }

        if (!flagError) {
          // No onsumbit configured. Directly close.
          if (document.getElementById(settings.form) != undefined) {
            document.getElementById(settings.form).submit();
          }
          d.dialog("close");
        }
      }
    };

    required_buttons.push({
      class:
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
      text: settings.modal.ok,
      id: settings.modal.ok_button_id,
      click: function() {
        if (
          settings.onsubmit != undefined &&
          settings.onsubmit.onConfirmSubmit != undefined
        ) {
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
          $(this).dialog("destroy");

          if (id_modal_target != undefined) {
            $(id_modal_target).remove();
          }

          if (settings.cleanup != undefined) {
            settings.cleanup();
          }
        },
        beforeClose: settings.beforeClose()
      });
    },
    error: function(data) {
      console.error(data);
    }
  });
}

// Function that shows a dialog box to confirm closures of generic manners.
// The modal id is random.
// eslint-disable-next-line no-unused-vars
function confirmDialog(settings, idDialog = uniqId()) {
  var hideOkButton = "";
  var hideCancelButton = "";

  if (settings.size == undefined) {
    settings.size = 350;
  }

  if (settings.maxHeight == undefined) {
    settings.maxHeight = 1000;
  }
  // You can hide the OK button.
  if (settings.hideOkButton != undefined) {
    hideOkButton = "invisible_important ";
  }
  // You can hide the Cancel button.
  if (settings.hideCancelButton != undefined) {
    hideCancelButton = "invisible_important ";
  }

  if (settings.strOKButton == undefined) {
    settings.strOKButton = "Ok";
  }

  if (settings.strCancelButton == undefined) {
    settings.strCancelButton = "Cancel";
  }

  if (typeof settings.message == "function") {
    $("body").append(
      '<div id="confirm_' + idDialog + '">' + settings.message() + "</div>"
    );
  } else {
    $("body").append(
      '<div id="confirm_' + idDialog + '">' + settings.message + "</div>"
    );
  }

  var buttons = [
    {
      id: "cancel_btn_dialog",
      text: settings.cancelText
        ? settings.cancelText
        : settings.strCancelButton,
      class:
        hideCancelButton +
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel",
      click: function() {
        if (typeof settings.notCloseOnDeny == "undefined") {
          $(this).dialog("close");
          $(this).remove();
        }
        if (typeof settings.onDeny == "function") settings.onDeny();
      }
    },
    {
      text: settings.strOKButton,
      class:
        hideOkButton +
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
      click: function() {
        if (typeof settings.onAccept == "function") settings.onAccept();
        $(this).dialog("close");
        $(this).remove();
      }
    }
  ];

  if (settings.newButton != undefined) {
    var newButton = {
      text: settings.newButton.text,
      class:
        settings.newButton.class +
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-warning",
      click: function() {
        $(this).dialog("close");
        if (typeof settings.newButton.onFunction == "function")
          settings.newButton.onFunction();
        $(this).remove();
      }
    };

    buttons.unshift(newButton);
  }

  $("#confirm_" + idDialog);
  $("#confirm_" + idDialog)
    .dialog({
      open: settings.open,
      title: settings.title,
      close: function() {
        if (typeof settings.notCloseOnDeny == "undefined") {
          $(this).dialog("close");
          $(this).remove();
        }
        if (typeof settings.onDeny == "function") settings.onDeny();
      },
      width: settings.size,
      maxHeight: settings.maxHeight,
      modal: true,
      buttons: buttons
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

  if (typeof data.error != "number") {
    title = "Response";
    text = data;
    failed = false;

    if (typeof data == "object") {
      title = data.title || "Response";
      text = data.text || data.error || data.result;
      failed = data.failed || data.error;
    }

    if (failed) {
      title = "Error";
      text = data.error;
    }

    if (idMsg == null) {
      idMsg = uniqId();
    }

    if ($("#" + idMsg).length === 0) {
      $("body").append('<div title="' + title + '" id="' + idMsg + '"></div>');
      $("#" + idMsg).empty();
    }
  }

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
        id: "general_message_ok",
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
  // False or null: Show all buttons and classic behaviour,
  // if true, show an OK button and message from data.text.
  var simple = data.simple;

  if (idMsg == null) {
    idMsg = uniqId();
  }

  if ($("#" + idMsg).length === 0) {
    $("body").append('<div title="' + title + '" id="' + idMsg + '"></div>');
    $("#" + idMsg).empty();
  }

  $("#err_msg").empty();
  $("#err_msg").html("\n\n" + err_messge);

  var buttons = [];

  if (simple == null || simple == false) {
    buttons = [
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
    ];
  } else {
    $("#" + idMsg).append($("#err_msg"));
    buttons = [
      {
        class:
          "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
        text: "Ok",
        click: function(e) {
          $("#" + idMsg).dialog("close");
        }
      }
    ];
  }

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
      buttons: buttons,

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

/**
 * Returns html img group icon.
 * @param {int} $id_group
 */
function getGroupIcon(id_group, img_container) {
  $.ajax({
    type: "POST",
    url: "ajax.php",
    dataType: "json",
    data: {
      page: "godmode/groups/group_list",
      get_group_json: 1,
      id_group: id_group
    },
    success: function(data) {
      img_container.attr("src", "images/" + data["icon"]);
    },
    error: function() {
      img_container.attr("src", "");
    }
  });
}

/* Prepare download control */
function getCookie(name) {
  var parts = document.cookie.split(name + "=");
  if (parts.length == 2)
    return parts
      .pop()
      .split(";")
      .shift();
}

function expireCookie(cName) {
  document.cookie =
    encodeURIComponent(cName) +
    "=deleted; expires=" +
    new Date(0).toUTCString();
}

function setCursor(buttonStyle, button) {
  button.css("cursor", buttonStyle);
}

function setToken(tokenName, token) {
  token = typeof token !== "undefined" ? token : new Date().getTime();
  document.cookie = tokenName + "=" + token + ";" + "-1" + ";path=/";

  return token;
}

var downloadTimer;
var attempts = 30;

// Prevents double-submits by waiting for a cookie from the server.
function blockResubmit(button) {
  var downloadToken = setToken("downloadToken");
  setCursor("wait", button);

  // Disable butoon to prevent clicking until download is ready.
  button.disable();
  button.click(false);

  //Show dialog.
  confirmDialog(
    {
      title: get_php_value("prepareDownloadTitle"),
      message: get_php_value("prepareDownloadMsg"),
      hideCancelButton: true
    },
    "downloadDialog"
  );

  downloadTimer = setInterval(function() {
    var downloadReady = getCookie("downloadReady");

    if (downloadToken == downloadReady || attempts == 0) {
      unblockSubmit(button);
    }

    attempts--;
  }, 1000);
}

function unblockSubmit(button) {
  setCursor("pointer", button);
  button.enable();
  button.on("click");
  clearInterval(downloadTimer);
  $("#confirm_downloadDialog").dialog("close");
  expireCookie("downloadToken");
  expireCookie("downloadReady");
  attempts = 30;
}

function favMenuAction(e) {
  var data = JSON.parse(atob(e.value));
  if (data.label === "" && $(e).hasClass("active") === false) {
    $("#dialog-fav-menu").dialog({
      title: "Please choose a title",
      width: 330,
      buttons: [
        {
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-next",
          text: "Confirm",
          click: function() {
            data.label = $("#text-label_fav_menu").val();
            if (data.label.length > 18) {
              data.label = data.label.slice(0, 18) + "...";
            }

            $(e).val(btoa(JSON.stringify(data)));
            favMenuAction(e);
            $(this).dialog("close");
            $("input[name='label_fav_menu']").val("");
          }
        }
      ]
    });
    return;
  }
  $.ajax({
    method: "POST",
    url: "ajax.php",
    dataType: "json",
    data: {
      page: "include/ajax/fav_menu.ajax",
      id_element: data["id_element"],
      url: data["url"],
      label: data["label"],
      section: data["section"]
    },
    success: function(res) {
      if (res.success) {
        if (res.action === "create") {
          $("#image-fav-menu-action1").attr("src", "images/star_fav_menu.png");
          $("#image-fav-menu-action1").addClass("active");
        } else {
          $("#image-fav-menu-action1").attr("src", "images/star_dark.png");
          $("#image-fav-menu-action1").removeClass("active");
        }
      }
    }
  });
}
