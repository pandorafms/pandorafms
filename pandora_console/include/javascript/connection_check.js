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

  if (navigator.onLine) {
    isReachable(getServerUrl())
      .then(function(online) {
        if (online) {
          // handle online status
          connected = true;
          showConnectionMessage(connected, msg);
        } else {
          connected = false;
          msg = "No connectivity with server";
          showConnectionMessage(connected, msg);
        }
      })
      .catch(function(err) {
        connected = false;
        msg = err;
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
 * Test server reachibilty and get response.
 *
 * @param {String} url
 *
 * Return {promise}
 */
function isReachable(url) {
  /**
   * Note: fetch() still "succeeds" for 404s on subdirectories,
   * which is ok when only testing for domain reachability.
   *
   * Example:
   *   https://google.com/noexist does not throw
   *   https://noexist.com/noexist does throw
   */
  return fetch(url, { method: "HEAD", mode: "no-cors" })
    .then(function(resp) {
      return resp && (resp.ok || resp.type === "opaque");
    })
    .catch(function(error) {
      console.warn("[conn test failure]:", error);
    });
}

/**
 * Gets server origin url
 */
function getServerUrl() {
  var server_url;

  server_url = window.location.origin;

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
  if (conn) {
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
      }
    })
    .show();
}
