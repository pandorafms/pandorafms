var system = require("system");

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

if (!viewport_width) {
  viewport_width = 750;
}

if (!viewport_height) {
  viewport_height = 350;
}

if (type_graph_pdf == "combined") {
  post_data =
    "data=" +
    url_params +
    "&data_combined=" +
    url_params_comb +
    "&data_module_list=" +
    url_module_list +
    "&type_graph_pdf=" +
    type_graph_pdf +
    "&session_id=" +
    session_id;
} else {
  post_data =
    "data=" +
    url_params +
    "&type_graph_pdf=" +
    type_graph_pdf +
    "&session_id=" +
    session_id;
}

var page = require("webpage").create();

page.viewportSize = {
  width: viewport_width,
  height: viewport_height
};

page.zoomFactor = 1;

page.onConsoleMessage = function(msg) {
  console.log(msg);
};

page.onError = function(msg) {
  console.log(msg);
  page.close();
  phantom.exit();
};

page.onCallback = function(st) {
  if (!base_64) {
    page.render(output_filename, { format: "png" });
  } else {
    var base64 = page.renderBase64("png");
    // do not remove this console.output
    console.log(base64);
  }
  phantom.exit();
};

page.open(url, "POST", post_data, function(status) {
  if (status == "fail") {
    console.out("Failed to generate chart.");
    phantom.exit();
  }
});
