/**************************************
 These functions require jQuery library
 **************************************/

/** 
 * Draw a line between two elements in a div
 * 
 * @param line Line to draw. JavaScript object with the following properties:
	- x1 X coordinate of the first point. If not set, it will get the coord from node_begin position
	- y1 Y coordinate of the first point. If not set, it will get the coord from node_begin position
	- x2 X coordinate of the second point. If not set, it will get the coord from node_end position
	- y2 Y coordinate of the second point. If not set, it will get the coord from node_end position
	- color Color of the line to draw
	- node_begin Id of the beginning node
	- node_end Id of the finishing node
 * @param id_div Div to draw the lines in
 * @param editor Boolean variable to set other css selector in editor (when true).
 */
function draw_line (line, id_div) {
	selector = '';
	
	//Check if the global var resize_map is defined
	if (typeof(resize_map) == 'undefined') {
		resize_map = 0;
	}
	
	var lineThickness = 2;
	if (line['thickness'])
		lineThickness = line['thickness'];
	
	div = document.getElementById (id_div);
	
	brush = new jsGraphics (div);
	brush.setStroke (lineThickness);
	brush.setColor (line['color']);
	
	have_node_begin_img = $('#' + line['node_begin'] + " img").length;
	have_node_end_img = $('#' + line['node_end'] + " img").length;
	
	if (have_node_begin_img) {
		var img_pos_begin = $('#' + line['node_begin'] + " img").position();
		var img_margin_left_begin = $('#' + line['node_begin'] + " img").css("margin-left");
		var img_margin_left_begin_aux = img_margin_left_begin.split("px");
		img_margin_left_begin = parseFloat(img_margin_left_begin_aux[0]);

		var img_margin_top_begin = $('#' + line['node_begin'] + " img").css("margin-top");
		var img_margin_top_begin_aux = img_margin_top_begin.split("px");
		img_margin_top_begin = parseFloat(img_margin_top_begin_aux[0]);
	}
	if (have_node_end_img) {
		var img_pos_end = $('#' + line['node_end'] + " img").position();
		var img_margin_left_end = $('#' + line['node_end'] + " img").css("margin-left");
		var img_margin_left_end_aux = img_margin_left_end.split("px");
		img_margin_left_end = parseFloat(img_margin_left_end_aux[0]);

		var img_margin_top_end = $('#' + line['node_end'] + " img").css("margin-top");
		var img_margin_top_end_aux = img_margin_top_end.split("px");
		img_margin_top_end = parseFloat(img_margin_top_end_aux[0]);
	}

	if (line['x1']) {
		x1 = line['x'];
	}
	else {
		if (have_node_begin_img) {
			width = $('#' + line['node_begin'] + " img").width();
			x1 = parseInt($('#' + line['node_begin']).css (selector + 'left')) + (width / 2) + img_pos_begin.left + img_margin_left_begin;
		}
		else {
			width = $('#' + line['node_begin']).width();
			x1 = parseInt($('#' + line['node_begin']).css (selector + 'left')) + (width / 2);
		}
		
	}
	
	if (line['y1']) {
		y1 = line['y1'];
	}
	else {
		if (have_node_begin_img) {
			height = parseInt($('#' + line['node_begin'] + " img").css('height'));
			y1 = parseInt($('#' + line['node_begin']).css (selector + 'top')) + (height / 2) + img_pos_begin.top + img_margin_top_begin;
		}
		else {
			height = $('#' + line['node_begin']).height();
			y1 = parseInt($('#' + line['node_begin']).css (selector + 'top')) + (height / 2);
		}
	}
	
	if (line['x2']) {
		x2 = line['x2'];
	}
	else {
		if (have_node_end_img) {
			width = $('#' + line['node_end'] + " img").width();
			x2 = parseInt($('#' + line['node_end']).css (selector + 'left')) + (width / 2) + img_pos_end.left + img_margin_left_end;
		}
		else {
			width = $('#' + line['node_end']).width();
			x2 = parseInt($('#' + line['node_end']).css (selector + 'left')) + (width / 2);
		}
	}
	
	if (line['y2']) {
		y2 = line['y2'];
	}
	else {
		if (have_node_end_img) {
			height = parseInt($('#' + line['node_end'] + " img").css('height'));
			y2 = parseInt($('#' + line['node_end']).css (selector + 'top')) + (height / 2) + img_pos_end.top + img_margin_top_end;
		}
		else {
			height = $('#' + line['node_end']).height();
			y2 = parseInt($('#' + line['node_end']).css (selector + 'top')) + (height / 2);
		}
	}
	
	
	brush.drawLine (x1, y1, x2, y2);
	brush.paint ();
}

/** 
 * Draw all the lines in an array on a div
 * 
 * @param lines Array with lines objects (see draw_line)
 * @param id_div Div to draw the lines in
 * @param editor Boolean variable to set other css selector in editor (when true).
 */
function draw_lines (lines, id_div, editor) {
	jQuery.each (lines, function (i, line) {
		draw_line (line, id_div, editor);
	});
}

/** 
 * Delete all the lines on a div
 *
 * The lines has the class 'map-line', so all the elements with this
 * class are removed.
 *
 * @param id_div Div to delete the lines in
 */
function delete_lines (id_div) {
	$('#' + id_div + ' .map-line').remove ();
}


/** 
 * Re-draw all the lines in an array on a div
 *
 * It deletes all the lines and create then again.
 * 
 * @param lines Array with lines objects (see draw_line)
 * @param id_div Div to draw the lines in
 * @param editor Boolean variable to set other css selector in editor (when true).
 */
function refresh_lines (lines, id_div, editor) {
	delete_lines (id_div);
	draw_lines (lines, id_div, editor);
}


function draw_user_lines_read(divId) {
	divId = divId || 'background';
	var obj_js_user_lines = new jsGraphics(divId);
	
	obj_js_user_lines.clear();
	
	// Draw the previous lines
	for (iterator = 0; iterator < user_lines.length; iterator++) {
		obj_js_user_lines.setStroke(parseInt(user_lines[iterator]['line_width']));
		obj_js_user_lines.setColor(user_lines[iterator]['line_color']);
		obj_js_user_lines.drawLine(
			parseInt(user_lines[iterator]['start_x']),
			parseInt(user_lines[iterator]['start_y']),
			parseInt(user_lines[iterator]['end_x']),
			parseInt(user_lines[iterator]['end_y']));
		
	}
	
	obj_js_user_lines.paint();
}

function center_labels() {
	jQuery.each($(".item"), function(i, item) {

		if ($(item).width() > $("img", item).width() && ($("img", item).width() != null)) {
			dif_width = $(item).width() - $("img", item).width();
			
			x = parseInt($(item).css("left"));

			x = x - (dif_width / 2);

			$(item).css("left", x + "px").css("text-align", "center");
		}
	});
}