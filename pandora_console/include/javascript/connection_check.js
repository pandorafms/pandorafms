/* globals $, get_php_value, infoMessage */
/**
 * -------------------------------------
 *        Connection Check
 * --------------------------------------
 */
$(document).ready(function() {
  checkConnection(get_php_value("check_conexion_interval"));
});

/**
 * Performs connection tests every minutes and add connection listeners
 * @param {integer} time in minutes
 */

function checkConnection(seconds) {
  var cicle = seconds * 1000;
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
        msg = err.statusText;
      } else {
        connected = true;
      }

      showConnectionMessage(connected, msg);
    });
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
