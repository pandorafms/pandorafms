/* eslint-disable no-unused-vars */
/* global ajaxPage,texts,ActiveXObject, updates */
/* global $,clientMode */

// Define following functions to customize responses. Do not edit this code.
/* global toggleUpdateList,showUpdateDetails */
/* global handleErrorMessage, handleSuccessMessage */
/* global confirmDialog */
/* global nextUpdateVersion:writable */
var _auxIntervalReference = null;

function evalScript(elem) {
  var scripts = elem.getElementsByTagName("script");

  for (var i = 0; i < scripts.length; i++) {
    var script = document.createElement("script");
    var data = scripts[i].text;

    script.type = "text/javascript";
    try {
      // doesn't work on ie...
      script.appendChild(document.createTextNode(data));
    } catch (e) {
      // IE has funky script nodes
      script.text = data;
    }
    elem.parentNode.appendChild(script);
  }
}
/**
 * Executes a request against a target.
 * @param {
 *  url,
 *  method,
 *  data,
 *  sync,
 *  success,
 *  error,
 *  contentType,
 *  accept
 * } settings for AJAX request.
 */
function ajax(settings) {
  var html = {
    // Default values
    url: "",
    method: "POST",
    data: {},
    sync: true,
    contentType: "application/json",
    accept: "application/json",
    success: null,
    error: function(c, msg) {
      console.error(c + ":" + msg);
    },
    response: null,
    xmlHttp: null,
    send: function() {
      this.data["ajax"] = 1;
      if (window.XMLHttpRequest) {
        this.xmlHttp = new XMLHttpRequest();
      } else {
        this.xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
      }
      if (this.method == "GET") {
        this.xmlHttp.open(this.method, this.url, this.sync);
        this.data = "data=" + encodeURIComponent(JSON.stringify(this.data));
        this.data += "&page=" + encodeURIComponent(html.page);
        this.data += "&mode=" + clientMode;
      } else {
        // POST method.
        this.xmlHttp.open(this.method, this.url, this.sync);
        if (
          this.contentType == "application/json" ||
          this.contentType == "json"
        ) {
          this.contentType = "application/json";
          this.data = "data=" + encodeURIComponent(JSON.stringify(this.data));
          this.data += "&page=" + encodeURIComponent(html.page);
          this.data += "&mode=" + clientMode;
        }
      }
      // Prepare request.
      this.xmlHttp.setRequestHeader(
        "Content-type",
        "application/x-www-form-urlencoded"
      );
      this.xmlHttp.setRequestHeader("Accept", this.accept);

      // Receiving response.
      this.xmlHttp.onreadystatechange = function() {
        if (this.readyState == 4) {
          if (this.status == 200) {
            var response;
            if (html.accept == "json" || html.accept == "application/json") {
              response = JSON.parse(this.response);
            } else {
              response = this.response;
            }
            if (html.success) {
              html.success(response);
            }
          } else {
            html.error(this.status, this);
          }
        }
      };

      // Execute request.
      this.xmlHttp.send(this.data);
    }
  };

  [
    "url",
    "method",
    "data",
    "sync",
    "success",
    "error",
    "contentType",
    "accept",
    "page",
    "cors"
  ].forEach(function(i) {
    if (settings[i] != undefined) {
      html[i] = settings[i];
    }
  });

  html["data"]["cors"] = html["cors"];

  // Perform.
  html.send();
}

/**
 * Prevents user exit or page change.
 */
function preventExit() {
  var dbuttons = document.getElementById("um-buttons");
  if (dbuttons != null) dbuttons.style.display = "none";

  var dprog = document.getElementById("um-progress");
  if (dprog != null) dprog.style.display = "block";

  // Warning before leaving the page (back button, or outgoinglink)
  window.onbeforeunload = function() {
    return texts.preventExitMsg;
  };
}

/**
 * Cancels prevention.
 */
function cleanExit() {
  var dbuttons = document.getElementById("um-buttons");
  if (dbuttons != null) dbuttons.style.display = "block";

  var dprog = document.getElementById("um-progress");
  if (dprog != null) dprog.style.display = "none";

  window.onbeforeunload = undefined;
}

/**
 * Formats a error message.
 *
 * @param {string} msg
 * @returns
 */
function umErrorMsg(msg) {
  if (typeof handleErrorMessage == "function") {
    return handleErrorMessage(msg);
  } else {
    return '<div class="um-error"><p>' + msg + "</p></div>";
  }
}

/**
 * Formats a success message.
 *
 * @param {string} msg
 * @returns
 */
function umSuccessMsg(msg) {
  if (typeof handleSuccessMessage == "function") {
    handleSuccessMessage(msg);
  } else {
    return '<div class="success"><p>' + msg + "</p></div>";
  }
}

/**
 * Updates product to next version.
 *
 * @param {dictionary} settings:
 *   url: string
 *   success: function
 *   error: function
 */
function updateNext(settings) {
  preventExit();
  var progressInterval = setInterval(function() {
    updateProgress(settings.url, settings.auth);
  }, 1000);

  ajax({
    url: settings.url,
    cors: settings.auth,
    page: ajaxPage,
    data: {
      action: "nextUpdate"
    },
    success: function(d) {
      if (typeof d.error == "undefined") {
        if (typeof settings.success == "function") settings.success(d.result);
      } else {
        if (typeof settings.error == "function") settings.error(0, d.error);
      }
      clearInterval(progressInterval);
      cleanExit();
      //location.reload();
    },
    error: function(e, request) {
      if (typeof response == "object") {
        var r;
        try {
          r = JSON.parse(request.response);
          if (r.error != "undefined") r = r.error;
          else r = request.response;
        } catch (e) {
          // Do not change.
          r = request.response;
        }
      }
      if (typeof settings.error == "function") settings.error(e, r);
      clearInterval(progressInterval);
      cleanExit();
    }
  });
}

/**
 * Updates product to latest version.
 * @param {dictionary} settings:
 *   url: string
 *   success: function
 *   error: function
 */
function updateLatest(settings) {
  preventExit();
  var dprog = document.getElementById("um-progress");
  dprog.style.display = "block";
  var progressInterval = setInterval(function() {
    updateProgress(settings.url, settings.auth);
  }, 1000);

  ajax({
    url: settings.url,
    cors: settings.auth,
    page: ajaxPage,
    data: {
      action: "latestUpdate"
    },
    success: function(d) {
      if (typeof handleSuccessMessage == "function") {
        handleSuccessMessage(d);
      } else {
        if (typeof d.error == "undefined") {
          if (typeof settings.success == "function") settings.success(d.result);
        } else {
          if (typeof settings.error == "function") settings.error(0, d.error);
        }
      }
      clearInterval(progressInterval);
      cleanExit();
    },
    error: function(e, request) {
      if (typeof request == "object") {
        var r;
        try {
          r = JSON.parse(request.response);
          if (r.error != "undefined") r = r.error;
          else r = request.response;
        } catch (e) {
          // Do not change.
          r = request.response;
        }
      }
      if (typeof handleErrorMessage == "function") {
        handleErrorMessage(r.response);
      } else {
        if (typeof settings.error == "function") settings.error(e, r);
      }
      clearInterval(progressInterval);
      cleanExit();
    }
  });
}

/**
 * Updates progres bar.
 *
 * @param {string} url
 * @param {string} auth
 */
function updateProgress(url, auth) {
  var dprog = document.getElementById("um-progress");
  var general_progress = document.getElementById("um-progress-general");
  var general_label = document.getElementById("um-progress-general-label");
  var task_progress = document.getElementById("um-progress-task");
  var task_label = document.getElementById("um-progress-task-label");

  var general_action = document.getElementById("um-progress-version");
  var task_action = document.getElementById("um-progress-description");

  if (general_label.innerText == undefined) {
    // Initialize.
    general_progress.style.width = "0 %";
    general_label.innerText = "0 %";

    task_progress.style.width = "0 %";
    task_label.innerText = "0 %";

    general_action.innerText = "";
    task_action.innerText = "";
  }

  if (general_label.innerText == "100.00 %") {
    cleanExit();
    if (_auxIntervalReference != null) {
      window.clearInterval(_auxIntervalReference);
    }
    return;
  }

  ajax({
    url: url,
    cors: auth,
    page: ajaxPage,
    data: {
      action: "status"
    },
    success: function(d) {
      // Clean.
      general_progress.style.width = "0 %";
      general_label.innerText = "0 %";

      task_progress.style.width = "0 %";
      task_label.innerText = "0 %";

      general_action.innerText = "";
      task_action.innerText = "";
      var p;

      if (d.result.global_percent == null) {
        p = d.result.percent;
      } else {
        p = d.result.global_percent;
      }

      general_progress.style.width = p + "%";
      if (p != undefined) {
        general_label.innerText = p.toFixed(2) + " %";
      }

      var percent = d.result.percent;
      if (percent == null) {
        percent = 0;
      }

      task_progress.style.width = percent + "%";
      task_label.innerText = percent.toFixed(2) + " %";

      general_action.innerText = d.result.processing;
      task_action.innerText = d.result.message;
    },
    error: function(d) {
      dprog.innerHTML = umErrorMsg(d.error);
    }
  });
}

/**
 * Show updates.
 *
 * @param {string} url Ajax.url
 * @param {string} auth CORS
 */
function showProgress(url, auth) {
  preventExit();
  var dprog = document.getElementById("um-progress");
  dprog.style.display = "block";
  var dum = document.getElementById("um-loading");
  dum.style.display = "none";
  _auxIntervalReference = setInterval(function() {
    updateProgress(url, auth);
  }, 1000);
}

/**
 * Search for updates.
 *
 * @param {string} url
 * @param {string} auth CORS
 */
function searchUpdates(url, auth) {
  const updates_list = document.getElementById("um-updates");
  var um_buttons = document.getElementById("um-buttons");
  var op_loading = document.getElementById("um-loading");
  var op_msg = document.getElementById("loading-msg");

  op_loading.style.display = "block";
  op_msg.innerHTML = texts.searchingUpdates;

  ajax({
    url: url,
    cors: auth,
    page: ajaxPage,
    data: {
      action: "getUpdates"
    },
    accept: "text/html",
    success: function(d) {
      op_loading.style.display = "none";
      if (d != "") {
        updates_list.innerHTML = d;
        um_buttons.style.display = "block";
      } else {
        // No updates.
        updates_list.innerHTML = texts.alreadyUpdated;
      }
      evalScript(updates_list);
    },
    error: function(errno, err) {
      op_loading.style.display = "none";
      if (err.response) {
        updates_list.innerHTML = err.response;
      } else {
        updates_list.innerHTML = errno;
      }
    }
  });
}

function umConfirm(settings) {
  if (typeof confirmDialog == "function") {
    confirmDialog(settings);
  } else {
    if (confirm(settings.message)) {
      if (typeof settings.onAccept == "function") settings.onAccept();
    } else {
      if (typeof settings.onDeny == "function") settings.onDeny();
    }
  }
}

function umShowUpdateDetails(update) {
  var um_update_details = document.getElementById("um-update-details");
  var header = document.getElementById("um-update-details-header");
  var content = document.getElementById("um-update-details-content");
  var detailTitle = document
    .getElementsByClassName("um-package-details")
    .item(0).innerText;
  header.innerText = texts.updateText + " " + updates[update].version;
  content.innerText = updates[update].description;

  if (typeof showUpdateDetails != "function") {
    um_update_details.style.display = "block";
    if (typeof $ == "function") {
      $("#um-update-details").dialog({
        title: detailTitle,
        width: 800,
        height: 600
      });
    }
  } else {
    showUpdateDetails(um_update_details, updates[update]);
  }
}

function changelog() {
  window.open("https://pandorafms.com/en/changelog/", "_blank").focus();
}

function umToggleUpdateList() {
  if (typeof toggleUpdateList == "function") {
    toggleUpdateList();
  } else {
    var update_list = document.getElementById("update-list");
    if (update_list)
      if (update_list.style.maxHeight == "10em") {
        update_list.style.maxHeight = "0";
        update_list.style.overflowY = "none";
      } else {
        update_list.style.maxHeight = "10em";
        update_list.style.overflowY = "auto";
      }
  }
}

function umUINextUpdate(version) {
  var pkg_version = document.getElementById("pkg_version");
  var update_list = document.getElementById("update-list");
  var nextVersion = document.getElementById("next-version");
  var nextVersionLink = document.getElementById("um-package-details-next");
  var updates_left = document.getElementById("updates_left");
  var listNextUpdate;
  var listNextVersion;

  pkg_version.innerText = version;
  nextUpdateVersion;

  if (update_list) {
    do {
      listNextUpdate = update_list.children[0];
      if (listNextUpdate == null) break;
      listNextVersion = listNextUpdate.children[0].innerText;
      update_list.removeChild(listNextUpdate);
      if (updates_left.innerText > 0) {
        updates_left.innerText = updates_left.innerText - 1;
      } else {
        updates_left.parentNode.parentNode.removeChild(updates_left.parentNode);
        update_list.parentNode.removeChild(update_list);
      }
    } while (
      listNextVersion <= version ||
      version == null ||
      listNextVersion == null
    );

    nextVersion.innerText = listNextVersion;
    nextVersionLink.href =
      "javascript: umShowUpdateDetails('" + listNextVersion + "');";

    nextUpdateVersion = listNextVersion;
  }

  if (nextUpdateVersion == null || version >= nextUpdateVersion) {
    var updates_list = document.getElementById("um-updates");
    var um_buttons = document.getElementById("um-buttons");
    updates_list.innerHTML = texts.alreadyUpdated;
    um_buttons.innerHTML = "";
  }
}

function validateEmail(email) {
  const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
}
