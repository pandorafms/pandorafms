var system = require("system");

/* global phantom */

if (system.args.length < 3 || system.args.length > 11) {
  phantom.exit(1);
}

var url = system.args[1];
var type_graph_pdf = system.args[2];
var url_params = system.args[3];
var url_params_comb = system.args[4];
var url_module_list = system.args[5];
var output_filename = system.args[6];
var viewport_width = system.args[7];
var viewport_height = system.args[8];
var session_id = system.args[9];
var base_64 = system.args[10];
var post_data = "";

if (!viewport_width) {
  viewport_width = 750;
}

if (!viewport_height) {
  viewport_height = 350;
}

if (type_graph_pdf == "combined") {
  post_data = {
    data: url_params,
    session_id: session_id,
    type_graph_pdf: type_graph_pdf,
    data_module_list: url_module_list,
    data_combined: url_params_comb
  };
} else {
  post_data = {
    data: url_params,
    session_id: session_id,
    type_graph_pdf: type_graph_pdf
  };
}

/* DEBUG
page.onAlert = function() {
  console.log("onAlert");
};
page.onCallback = function() {
  console.log("onCallback");
};
page.onClosing = function() {
  console.log("onClosing");
};
page.onConfirm = function() {
  console.log("onConfirm");
};
page.onConsoleMessage = function() {
  console.log("onConsoleMessage");
};
page.onError = function() {
  console.log("onError");
};
page.onFilePicker = function() {
  console.log("onFilePicker");
};
page.onInitialized = function() {
  console.log("onInitialized");
};
page.onLoadFinished = function() {
  console.log("onLoadFinished");
};
page.onLoadStarted = function() {
  console.log("onLoadStarted");
};
page.onNavigationRequested = function() {
  console.log("onNavigationRequested");
};
page.onPageCreated = function() {
  console.log("onPageCreated");
};
page.onPrompt = function() {
  console.log("onPrompt");
};
page.onResourceError = function() {
  console.log("onResourceError");
};
page.onResourceReceived = function(res) {
  console.log("onResourceReceived" + ";" + res.url + ";" + res.status);
};
page.onResourceRequested = function(res) {
  console.log("onResourceRequested" + ";" + res.url);
};
page.onResourceTimeout = function() {
  console.log("onResourceTimeout");
};
page.onUrlChanged = function(url) {
  console.log("onUrlChanged" + ";" + url);
};

*/

var page = require("webpage").create();

page.onResourceError = function(resourceError) {
  console.log(
    "Unable to load resource (#" +
      resourceError.id +
      "URL:" +
      resourceError.url +
      ")"
  );
  console.log(
    "Error code: " +
      resourceError.errorCode +
      ". Description: " +
      resourceError.errorString
  );
  phantom.exit(1);
};

// Not supposed to be prompted messages.
page.onPrompt = function() {
  console.log("Prompt message detected.");
  phantom.exit(1);
};

page.viewportSize = {
  width: viewport_width,
  height: viewport_height
};

page.zoomFactor = 1;

page.settings.userAgent =
  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36";
page.settings.resourceTimeout = 2000;
page.settings.localToRemoteUrlAccessEnabled = true;

page.onConsoleMessage = function(msg) {
  console.log(msg);
  page.close();
  phantom.exit();
};

page.onError = function(msg) {
  console.log(msg);
  page.close();
  phantom.exit();
};

page.onCallback = function() {
  if (!base_64) {
    page.render(output_filename, { format: "png" });
  } else {
    page.settings.loadImages = false;
    var base64 = page.renderBase64("png");
    // do not remove this console.output
    console.log(base64);
  }
  phantom.exit();
};

page.open(url, "POST", "data=" + btoa(JSON.stringify(post_data)), function(
  status
) {
  if (status == "fail") {
    console.out("Failed to generate chart.");
    phantom.exit();
  }
});

phantom.onError = function(msg, trace) {
  var msgStack = ["PHANTOM ERROR: " + msg];
  if (trace && trace.length) {
    msgStack.push("TRACE:");
    trace.forEach(function(t) {
      msgStack.push(
        " -> " +
          (t.file || t.sourceURL) +
          ": " +
          t.line +
          (t.function ? " (in function " + t.function + ")" : "")
      );
    });
  }
  console.log(msgStack.join("\n"));
  phantom.exit(1);
};
