/**
 * -------------------------------------
 *        Connection Check
 * --------------------------------------
 */

checkConnection(1);

/**
 * Performs connection tests every minutes and add connection listeners
 * @param {integer} time in minutes
 */

function checkConnection(minutes) {
  var cicle = minutes * 60 * 1000;
  var checkConnection = setInterval(handleConnection, cicle);

  // Connection listeters.
  window.addEventListener("online", handleConnection);
  window.addEventListener("offline", handleConnection);
}

/**
 * Handle connection status test.
 *
 * Test conectivity with server and shows modal message.
 */
function handleConnection() {
  var connected;
  var msg = "online";

  var homeurl = getServerUrl();
  if (homeurl == null || homeurl == "") {
    return;
  }

  if (navigator.onLine) {
    $.ajax({
      url: homeurl + "include/connection_check.php",
      type: "post",
      dataType: "json"
    })
      .done(function(response) {
        connected = true;
        showConnectionMessage(connected, msg);
      })
      .fail(function(err) {
        // If test connection file is not found, do not show message.
        if (err.status != 404) {
          connected = false;
          msg = err;
        } else {
          connected = true;
        }

        showConnectionMessage(connected, msg);
      });
  } else {
    // handle offline status
    connected = false;
    msg = "Connection offline";
    showConnectionMessage(connected, msg);
  }
}

/**
 * Gets server origin url
 */
function getServerUrl() {
  var server_url;

  try {
    server_url = get_php_value("absolute_homeurl");
  } catch (error) {
    return "";
  }

  return server_url;
}

/**
 * Shows or hide connection infoMessage.
 *
 * @param {bool} conn
 * @param {string} msg
 */
function showConnectionMessage(conn = true, msg = "") {
  var data = {};
  if (conn && closed == false) {
    $("div#message_dialog_connection")
      .closest(".ui-dialog-content")
      .dialog("close");
  } else {
    data.title = "Connection with server has been lost";
    data.text = "Connection status: " + msg;

    infoMessage(data, "message_dialog_connection");
  }
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
        closed = true;
      }
    })
    .show();
}
