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
function draw_line (line, id_div, editor) {
	if (typeof(editor) == 'undefined') {
		editor = false;
		selector = 'margin-';
	}
	if (editor) {
		selector = '';
	}
	
	div = document.getElementById (id_div);
	brush = new jsGraphics (div);
	brush.setStroke (1);
	brush.setColor (line['color']);
	
	if (line['x1']) {
		x1 = line['x'];
	}
	else {
		x1 = parseInt ($('#'+line['node_begin']).css (selector + 'left')) + ($('#'+line['node_begin']).width() / 2);
	}
	if (line['y1']) {
		y1 = line['y1'];
	}
	else {
		y1 = parseInt ($('#'+line['node_begin']).css (selector + 'top')) + ($('#'+line['node_begin']).height() / 2);
	}
	if (line['x2']) {
		x2 = line['x2'];
	}
	else {
		x2 = parseInt ($('#'+line['node_end']).css (selector + 'left')) + ($('#'+line['node_end']).width() / 2);
	}
	if (line['y2']) {
		y2 = line['y2'];
	}
	else {
		y2 = parseInt ($('#'+line['node_end']).css (selector + 'top')) + ($('#'+line['node_end']).height() / 2);
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
	jQuery.each (lines, function () {
		draw_line (this, id_div, editor);
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
