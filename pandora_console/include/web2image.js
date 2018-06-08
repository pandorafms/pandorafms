var system = require('system');

if (system.args.length < 3 || system.args.length > 11) {
	phantom.exit(1);
}

var webPage	        = require('webpage');
var page            = webPage.create();
var url             = system.args[1];
var type_graph_pdf  = system.args[2];
var url_params      = system.args[3];
var url_params_comb = system.args[4];
var url_module_list = system.args[5];
var output_filename = system.args[6];
var _width          = system.args[7];
var _height         = system.args[8];
var session_id      = system.args[9];
var base_64         = system.args[10];


if (!_width) {
	_width = 750;
}

if (!_height) {
	_height = 350;
}

if(type_graph_pdf == 'combined'){
	finish_url = url + "?" + "data=" + url_params +
				"&data_combined=" + url_params_comb +
				"&data_module_list=" + url_module_list +
				"&type_graph_pdf=" + type_graph_pdf +
				"&session_id=" + session_id;
}
else{
	finish_url = url + "?" + "data=" + url_params +
				"&type_graph_pdf=" + type_graph_pdf +
				"&session_id=" + session_id;
}

page.viewportSize = { width: _width, height: _height };
//page.zoomFactor = 1.75;

page.open(finish_url, function start(status) {

});

page.onLoadFinished = function (status) {
	if(!base_64){
		page.render(output_filename, {format: 'png'});
	}
	else{
		var base64 = page.renderBase64('png');
		console.log(base64);
	}
	phantom.exit();
}


