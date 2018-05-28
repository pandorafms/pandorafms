var system = require('system');

if (system.args.length < 2 || system.args.length > 6) {
	//console.log('Usage web2image.js url url_parameters output_filename width height');
	phantom.exit(1);
}

var webPage	        = require('webpage');
var page            = webPage.create();
var url             = system.args[1];
var url_params      = system.args[2];
var output_filename = system.args[3];
var _width          = system.args[4];
var _height         = system.args[5];

if (!_width) {
	_width = 750;
}

if (!_height) {
	_height = 350;
}

page.viewportSize = { width: _width, height: _height };

//console.log("Pagina: " + url);
//console.log("parametros: " + url_params);
//console.log("Archivo salida: " + output_filename);
page.open(url + "?" + "data=" + url_params, function start(status) {
	page.render(output_filename, {format: 'png'}); //, 'quality': 100});
	phantom.exit();
});


