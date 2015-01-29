/*


*/

function pandoraFlotPie(graph_id, values, labels, nseries, width, font_size, water_mark, separator, legend_position, height, colors) {
	var labels = labels.split(separator);
	var data = values.split(separator);
	if (colors != '') {
		colors = colors.split(separator);
	}
	
	var color = null;
	for (var i = 0; i < nseries; i++) {
		if (colors != '') {
			color = colors[i];
		}
		
		data[i] = { label: labels[i], data: parseInt(data[i]), color: color}
	}
	
	var label_conf;
	
	if (width < 400) {
		label_conf = {
			show: false
		};
	}
	else {
		label_conf = {
			show: true,
			radius: 3/4,
			formatter: function(label, series) {
				return '<div style="font-size:' + font_size + 'pt;' +
					'text-align:center;padding:2px;color:white;">' +
						label + '<br/>' + series.percent.toFixed(1) + '%</div>';
			},
			background: {
				opacity: 0.5,
				color: '#000'
			}
		};
	}
	
	var show_legend = true;
	if (legend_position == 'hidden') {
		show_legend = false;
	}
	
	var conf_pie = {
			series: {
				pie: {
					show: true,
					radius: 3/4,
					//offset: {top: -100},
					label: label_conf
					//$label_str
				}
			},
			legend: {
				show: show_legend
			},
			grid: {
				hoverable: true,
				clickable: true
			}
		};
		
		if (width < 400) {
			conf_pie.legend.labelFormatter = function(label, series) {
					return label + " (" + series.percent.toFixed(1) + "%)";
				}
		}
	
	switch (legend_position) {
		case 'bottom':
			if (width > height)
				offset = - (height / 5);
			else
				offset = - (width / 5);
			conf_pie.series.pie.radius = 1 / 2.5;
			conf_pie.series.pie.offset = {top: offset};
			conf_pie.legend.position = "se";
			break;
		case 'right':
		case 'inner':
			conf_pie.legend.container = $('#'+graph_id+"_legend");
		default:
			//TODO FOR TOP OR LEFT OR RIGHT
			break;
	}
	
	var plot = $.plot($('#'+graph_id), data, conf_pie);
	
	var legends = $('#'+graph_id+' .legendLabel');
	legends.each(function () {
		$(this).css('width', $(this).width());
		$(this).css('font-size', font_size+'pt');
	});
	
	// Events
	$('#' + graph_id).bind('plothover', pieHover);
	$('#' + graph_id).bind('plotclick', pieClick);
	$('#' + graph_id).bind('mouseout',resetInteractivity);
	
	function pieHover(event, pos, obj) 
	{
		if (!obj)
			return;
		
		index = obj.seriesIndex;
		legends.css('font-weight', '');
		legends.eq(index).css('font-weight', 'bold');
	}
	
	// Reset styles
	function resetInteractivity() {
		legends.each(function () {
			// fix the widths so they don't jump around
			$(this).css('font-weight', '');
		});
	}
	
	if (water_mark) {
		set_watermark(graph_id, plot,
			$('#watermark_image_' + graph_id).attr('src'));
	}
}

function pandoraFlotHBars(graph_id, values, labels, water_mark,
	maxvalue, water_mark, separator, separator2) {
	
	values = values.split(separator2);
	var datas = new Array();
	
	for (i = 0; i < values.length; i++) {
		var serie = values[i].split(separator);
		var aux = new Array();
		$.each(serie.reverse(),function(i,v) {
			aux.push([v, i]);
		});
		
		datas.push({ 
			data: aux, 
			bars: {
				show: true,
				horizontal: true,
				fillColor: {
					colors: [ { opacity: 0.7 }, { opacity: 1 } ]
				},
				lineWidth: 1,
				steps: false
			} 
		});
	}
	
	labels = labels.split(separator).reverse();
	
	var stack = 0, bars = true, lines = false, steps = false;
	
	var options = {
			series: {
				shadowSize: 0.1
			},
			
			grid: {
				hoverable: true,
				borderWidth:1,
				borderColor: '#666',
				tickColor: '#eee'
				},
			xaxes: [ {
					tickFormatter: yFormatter,
					color: '#000'
					} ],
			yaxes: [ {
					tickFormatter: xFormatter,
					tickSize: 1,
					color: '#000'
					},
					{
					  // align if we are to the right
					  alignTicksWithAxis: 1,
					  position: 'right'
					  
					  //tickFormatter: dFormatter
					} ]
					,
			legend: {
				show: false
			}
		};
	
	// Fixed to avoid the graphs with all 0 datas
	// the X axis show negative part instead to
	// show the axis only the positive part.
	if (maxvalue == 0) {
		options['xaxes'][0]['min'] = 0;
		
		// Fixed the values with a lot of decimals in the situation
		// with all 0 values.
		options['xaxes'][0]['tickDecimals'] = 0;
	}
	
	
	var plot = $.plot($('#' + graph_id), datas, options );
	
	
	// Adjust the top of yaxis tick to set it in the middle of the bars
	yAxisHeight = $('#' + graph_id + ' .yAxis .tickLabel')
		.css('height').split('px')[0];
	
	i = 0;
	$('#' + graph_id + ' .yAxis .tickLabel').each(function() {
		tickTop = $(this).css('top').split('px')[0];
		tickNewTop = parseInt(parseInt(tickTop) - (yAxisHeight / 2) - 3);
		$(this).css('top', tickNewTop + 'px');
		
		valuesNewTop = parseInt(parseInt(tickTop) - (yAxisHeight));
		
		$('#value_' + i + '_' + graph_id)
			.css('top',parseInt(plot.offset().top) + parseInt(valuesNewTop));
		
		pixelPerValue = parseInt(plot.width()) / maxvalue;
		
		inCanvasValuePos = parseInt(pixelPerValue *
			($('#value_' + i + '_' + graph_id).html()));
		label_width = ($('#value_' + i + '_' + graph_id)
			.css('width').split('px')[0] - 3);
		
		label_left_offset = plot.offset().left + inCanvasValuePos + 5; //Label located on right side of bar + 5 pixels
		
		//If label fit into the bar just recalculate left position to fit on right side of bar
		if (inCanvasValuePos > label_width) {
			label_left_offset = plot.offset().left + inCanvasValuePos
				- $('#value_' + i + '_' + graph_id).css('width').split('px')[0] - 3;
		}
		
		$('#value_' + i + '_' + graph_id)
			.css('left', label_left_offset);
		i++;
	});
	
	// When resize the window, adjust the values
	$('#' + graph_id).parent().resize(function () {
		i = 0;
		pixelPerValue = parseInt(plot.width()) / maxvalue;
		
		$('#' + graph_id + ' .yAxis .tickLabel').each(function() {
			inCanvasValuePos = parseInt(pixelPerValue *
				($('#value_' + i + '_' + graph_id).html()));
			label_width = ($('#value_' + i + '_' + graph_id)
				.css('width').split('px')[0] - 3);
			
			label_left_offset = plot.offset().left + inCanvasValuePos + 5; //Label located on right side of bar + 5 pixels
			
			//If label fit into the bar just recalculate left position to fit on right side of bar
			if (inCanvasValuePos > label_width) {
				label_left_offset = plot.offset().left + inCanvasValuePos
					- $('#value_' + i + '_' + graph_id)
						.css('width').split('px')[0] - 3;
			}
			
			$('#value_' + i + '_' + graph_id)
				.css('left', label_left_offset);
			i++;
		});
	});
	
	// Format functions
	function xFormatter(v, axis) {
		if (labels[v] != undefined) {
			return labels[v];
		}
		else {
			return '';
		}
	}
	
	function yFormatter(v, axis) {
		return v;
	}
	
	// Events
	$('#' + graph_id).bind('plothover',  function (event, pos, item) {
		$('.values_' + graph_id).css('font-weight', '');
		if (item != null) {
			index = item.dataIndex;
			$('#value_' + index + '_' + graph_id)
				.css('font-weight', 'bold');
		}
	});
	
	if (water_mark) {
		set_watermark(graph_id, plot,
			$('#watermark_image_' + graph_id).attr('src'));
	}
	
	if (maxvalue == 0) {
		
		// Fixed the position for the graphs with all values in
		// bars is 0.
		
		$(".values_" + graph_id).css("left", (plot.offset().left + 5) + "px");
	}
}

function pandoraFlotVBars(graph_id, values, labels, labels_long, legend, colors, water_mark, maxvalue, water_mark, separator, separator2) {
	
	values = values.split(separator2);
	legend = legend.split(separator);
	labels_long = labels_long.split(separator);
	colors = colors.split(separator);
	
	var datas = new Array();
	
	for (i=0;i<values.length;i++) {
		var serie = values[i].split(separator);
		
		var serie_color;
		if (colors[i] != '') {
			serie_color = colors[i];
		}
		else {
			serie_color = null;
		}
		
		var aux = new Array();
		$.each(serie,function(i,v) {
			aux.push([i, v]);
		});
		
		datas.push({ 
			data: aux, 
			color: serie_color,
			bars: { show: true, horizontal: false, fillColor: { colors: [ { opacity: 0.7 }, { opacity: 1 } ] }, lineWidth:1, steps:false } 
		});
	}
	
	labels = labels.split(separator);
	
	var stack = 0, bars = true, lines = false, steps = false;
	
	var options = { 
			series: { 
				shadowSize: 0.1 
			},
			grid: { 
				hoverable: true, 
				clickable: true, 
				borderWidth:1,
				borderColor: '#666',
				tickColor: '#eee'
				},
			xaxes: [ { 
					tickFormatter: xFormatter,
					color: '#000'
					} ],
			yaxes: [ { 
					tickFormatter: yFormatter,
					color: '#000'
					},
					{
						// align if we are to the right
						alignTicksWithAxis: 1,
						position: 'right'
						
						//tickFormatter: dFormatter
					} ]
					,
			legend: { 
				show: false
				}
		};
	
	var plot = $.plot($('#'+graph_id),datas, options );
	
	// Adjust the top of yaxis tick to set it in the middle of the bars
	yAxisHeight = $('#'+graph_id+' .yAxis .tickLabel').css('height').split('px')[0];
	
	i = 0;
	//~ $('#'+graph_id+' .yAxis .tickLabel').each(function() {
		//~ tickTop = $(this).css('top').split('px')[0];
		//~ tickNewTop = parseInt(parseInt(tickTop) - (yAxisHeight/2)-3);
		//~ $(this).css('top', tickNewTop+'px');
		//~ 
		//~ valuesNewTop = parseInt(parseInt(tickTop) - (yAxisHeight));
		//~ 
		//~ $('#value_'+i+'_'+graph_id).css('top',parseInt(plot.offset().top) + parseInt(valuesNewTop));
//~ 
		//~ pixelPerValue = parseInt(plot.width()) / maxvalue;
		//~ 
		//~ inCanvasValuePos = parseInt(pixelPerValue * ($('#value_'+i+'_'+graph_id).html()));
		//~ 
		//~ $('#value_'+i+'_'+graph_id).css('left',plot.offset().left + inCanvasValuePos - $('#value_'+i+'_'+graph_id).css('width').split('px')[0] - 3);
		//~ i++;
	//~ });
	
	// When resize the window, adjust the values
	//~ $('#'+graph_id).parent().resize(function () {
		//~ i = 0;
		//~ pixelPerValue = parseInt(plot.width()) / maxvalue;
		//~ 
		//~ $('#'+graph_id+' .yAxis .tickLabel').each(function() {			
			//~ inCanvasValuePos = parseInt(pixelPerValue * ($('#value_'+i+'_'+graph_id).html()));
			//~ 
			//~ $('#value_'+i+'_'+graph_id).css('left',plot.offset().left + inCanvasValuePos - $('#value_'+i+'_'+graph_id).css('width').split('px')[0] - 3);
			//~ i++;
		//~ });
	//~ });
	
	// Format functions
	function xFormatter(v, axis) {
		if (labels[v] != undefined) {
			return labels[v];
		}
		else {
			return '';
		}
	}
	
	function yFormatter(v, axis) {
		return v;
	}
	
	function lFormatter(v, axis) {
		return '<div style=color:#000>'+v+'</div>';
	}
	
	// Events
	//~ $('#'+graph_id).bind('plothover',  function (event, pos, item) {
		//~ $('.values_'+graph_id).css('font-weight', '');
		//~ if(item != null) {
			//~ index = item.dataIndex;
			//~ $('#value_'+index+'_'+graph_id).css('font-weight', 'bold');
		//~ }
	//~ });
	
	if (water_mark) {
		set_watermark(graph_id, plot, $('#watermark_image_'+graph_id).attr('src'));
	}
}

function pandoraFlotSlicebar(graph_id, values, datacolor, labels, legend, acumulate_data, intervaltick, water_mark, maxvalue, separator, separator2) {
	
	values = values.split(separator2);
	labels = labels.split(separator);
	legend = legend.split(separator);
	acumulate_data = acumulate_data.split(separator);
	datacolor = datacolor.split(separator);
	
	// Check possible adapt_keys on classes
	check_adaptions(graph_id);
	
	var datas = new Array();
	
	for (i=0;i<values.length;i++) {
		var serie = values[i].split(separator);
		var aux = new Array();
		$.each(serie,function(i,v) {
			aux.push([v, i]);
		});
		
		datas.push({ 
			data: aux, 
			bars: { show: true, fill: true ,fillColor: datacolor[i] , horizontal: true, lineWidth:0, steps:false } 
		});
	}
	
	var stack = 0, bars = true, lines = false, steps = false;
	
	var options = { 
			series: { 
				stack: stack,
				shadowSize: 0.1,
				color: '#ddd'
			},
			grid: { 
				hoverable: true, 
				borderWidth:1,
				borderColor: '#000',
				tickColor: '#fff'
				},
			xaxes: [ { 
					tickFormatter: xFormatter,
					color: '#000',
					tickSize: intervaltick,
					tickLength: 0
					} ],
			yaxes: [ { 
					show: false,
					tickLength: 0
				}],
			legend: { 
				show: false
				}
		};
	
	var plot = $.plot($('#'+graph_id), datas, options );
	
	// Events
	$('#'+graph_id).bind('plothover',  function (event, pos, item) {
		if (item) {
			var from = legend[item.seriesIndex];
			var to = legend[item.seriesIndex+1];
			
			if (to == undefined) {
				to = '>';
			}
			
			$('#extra_'+graph_id).text(from+'-'+to);
			var extra_height = parseInt($('#extra_'+graph_id).css('height').split('px')[0]);
			var extra_width = parseInt($('#extra_'+graph_id).css('width').split('px')[0]);
			$('#extra_'+graph_id).css('left',pos.pageX-(extra_width/2)+'px');
			$('#extra_'+graph_id).css('top',plot.offset().top-extra_height-5+'px');
			$('#extra_'+graph_id).show();
		}
	});
	
	$('#'+graph_id).bind('mouseout',resetInteractivity);
	
	// Reset interactivity styles
	function resetInteractivity() {
		$('#extra_'+graph_id).hide();
	}
	
	// Format functions
	function xFormatter(v, axis) {
		for (i = 0; i < acumulate_data.length; i++) {
			if (acumulate_data[i] == v) {
				return '<span style=\'font-size: 6pt\'>' + legend[i] + '</span>';
			}
		}
		return '';
	}
}

function pandoraFlotArea(graph_id, values, labels, labels_long, legend,
	colors, type, serie_types, water_mark, width, max_x, homeurl, unit,
	font_size, menu, events, event_ids, legend_events, alerts,
	alert_ids, legend_alerts, yellow_threshold, red_threshold,
	force_integer, separator, separator2, series_suffix_str) {
	
	var threshold = true;
	var thresholded = false;
	
	values = values.split(separator2);
	serie_types = serie_types.split(separator);
	labels_long = labels_long.split(separator);
	labels = labels.split(separator);
	legend = legend.split(separator);
	events = events.split(separator);
	event_ids = event_ids.split(separator);
	if (alerts.length != 0)
		alerts = alerts.split(separator);
	else
		alerts = [];
	alert_ids = alert_ids.split(separator);
	colors = colors.split(separator);
	
	var eventsz = new Array();
	$.each(events,function(i,v) {
		eventsz[event_ids[i]] = v;
	});
	
	var alertsz = new Array();
	$.each(alerts,function(i,v) {
		alertsz[alert_ids[i]] = v;
	});
	
	switch(type) {
		case 'line_simple': 
			stacked = null;
			filled = false;
			break;
		case 'line_stacked': 
			stacked = 'stack';
			filled = false;
			break;
		case 'area_simple':
			stacked = null;
			filled = true;
			break;
		case 'area_stacked':
			stacked = 'stack';
			filled = true;
			break;
	}
	
	var datas = new Array();
	var data_base = new Array();
	
	// Prepared to turn series with a checkbox
	// var showed = new Array();
	
	for (i = 0; i < values.length; i++) {
		var serie = values[i].split(separator);
		var aux = new Array();
		var critical_min = new Array();
		var warning_min = new Array();
		$.each(serie, function(i, v) {
			aux.push([i, v]);
			if (threshold) {
				critical_min.push([i,red_threshold]);
				warning_min.push([i,yellow_threshold]);
			}
		});
		
		switch(serie_types[i]) {
			case 'line':
			default:
				line_show = true;
				points_show = false;
				break;
			case 'points':
				line_show = false;
				points_show = true;
				break;
		}
		
		var serie_color;
		if (colors[i] != '') {
			serie_color = colors[i];
		}
		else {
			serie_color = null;
		}
		
		var normalw = '#efe';
		var warningw = '#ffe';
		var criticalw = '#fee';
		var normal = '#0f0';
		var warning = '#ff0';
		var critical = '#f00';
		
		// setup background areas
		//vnormal_max = vwarning_min - 1;
		
		var markings = null;
		
		// Fill the grid background with weak threshold colors
		//~ markings = [
			//~ { color: normalw, yaxis: { from: -1,to: vnormal_max } },
			//~ { color: warningw, yaxis: { from: vwarning_min, to: vwarning_max } },
			//~ { color: criticalw, yaxis: { from: vcritical_min } },
			//~ { color: criticalw, yaxis: { to: -1 } }
		//~ ];
		
		// Data
		data_base.push({ 
			id: 'serie_' + i,
			data: aux, 
			label: legend[i],
			color: serie_color,
			//threshold: [{ below: 80, color: "rgb(200, 20, 30)" } , { below: 65, color: "rgb(30, 200, 30)" }, { below: 50, color: "rgb(30, 200, 30)" }],
			lines: { show: line_show,
				fill: filled,
				fillColor: {
					colors: [ { opacity: 0.9 }, { opacity: 0.9 } ]
				},
				lineWidth: 1,
				steps: false },
			points: { show: points_show }
		});
		
		// Prepared to turn series with a checkbox
		// showed[i] = true;
	}
	
	var threshold_data = new Array();
	
	if (threshold) {
		// Warning and critical treshold
		threshold_data.push({ 
			id: 'critical_min',
			data: critical_min, 
			label: null,
			color: critical,
			lines: { show: true, fill: false, lineWidth:3}
		});
		threshold_data.push({ 
			id: 'warning_min',
			data: warning_min, 
			label: null,
			color: warning,
			lines: { show: true, fill: false, lineWidth:3}
		});
	}
	
	// The first execution, the graph data is the base data
	datas = data_base;
	
	// minTickSize
	var count_data = datas[0].data.length;
	var min_tick_pixels = 80;
	var steps = parseInt( count_data / (width/min_tick_pixels));
	
	var options = {
			series: {
				stack: stacked,
				shadowSize: 0.1
			},
			crosshair: { mode: 'xy' },
			selection: { mode: 'x', color: '#777' },
			grid: {
				hoverable: true, 
				clickable: true, 
				borderWidth:1,
				borderColor: '#666',
				tickColor: '#eee',
				markings: markings
				},
			xaxes: [ {
					tickFormatter: xFormatter,
					minTickSize: steps,
					color: '#000'
				} ],
			yaxes: [ {
						tickFormatter: yFormatter,
						color: '#000'
					},
					{
						// align if we are to the right
						alignTicksWithAxis: 1,
						position: 'right'
						
						//tickFormatter: dFormatter
					} ]
					,
			legend: {
				position: 'se',
				container: $('#legend_' + graph_id),
				labelFormatter: lFormatter
				}
		};
	
	var stack = 0, bars = true, lines = false, steps = false;
	
	var plot = $.plot($('#'+graph_id), datas, options);
	
	// Adjust the overview plot to the width and position of the main plot
	adjust_left_width_canvas(graph_id, 'overview_'+graph_id);
	
	// Adjust linked graph to the width and position of the main plot
	
	// Miniplot
	var overview = $.plot($('#overview_'+graph_id),datas, {
		series: {
			stack: stacked,
			lines: { show: true, lineWidth: 1 },
			shadowSize: 0
		},
		grid: { borderWidth: 1, hoverable: true, autoHighlight: false},
		xaxis: { },
			xaxes: [ {
				tickFormatter: xFormatter,
				minTickSize: steps,
				color: '#000'
				} ],
		yaxis: {ticks: [], autoscaleMargin: 0.1 },
		selection: {mode: 'x', color: '#777' },
		legend: {show: false},
		crosshair: {mode: 'x'}
	});
	
	// Connection between plot and miniplot
	
	$('#' + graph_id).bind('plotselected', function (event, ranges) {
		// do the zooming if exist menu to undo it
		if (menu == 0) {
			return;
		}
		dataInSelection = ranges.xaxis.to - ranges.xaxis.from;
		dataInPlot = plot.getData()[0].data.length;
		
		factor = dataInSelection / dataInPlot;
		
		new_steps = parseInt(factor * steps);
		
		plot = $.plot($('#' + graph_id), datas,
			$.extend(true, {}, options, {
				xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to},
				xaxes: [ { 
						tickFormatter: xFormatter,
						minTickSize: new_steps,
						color: '#000'
						} ],
				legend: { show: false }
			}));
		
		$('#menu_cancelzoom_' + graph_id)
			.attr('src',homeurl + '/images/zoom_cross.png');
		
		currentRanges = ranges;
		// don't fire event on the overview to prevent eternal loop
		overview.setSelection(ranges, true);
	});
	
	$('#overview_' + graph_id)
		.bind('plotselected', function (event, ranges) {
			plot.setSelection(ranges);
		});
	
	var legends = $('#legend_' + graph_id + ' .legendLabel');
	
	
	var updateLegendTimeout = null;
	var latestPosition = null;
	var currentPlot = null;
	var currentRanges = null;
	
	// Update legend with the data of the plot in the mouse position
	function updateLegend() {
		updateLegendTimeout = null;
		
		var pos = latestPosition;
		
		var axes = currentPlot.getAxes();
		if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
			pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
			return;
		}
		
		var j, dataset = currentPlot.getData();
		
		var i = 0;
		for (k = 0; k < dataset.length; k++) {
			
			// k is the real series counter
			// i is the series counter without thresholds
			var series = dataset[k];
			
			if (series.label == null) {
				continue;
			}
			
			// find the nearest points, x-wise
			for (j = 0; j < series.data.length; ++j)
				if (series.data[j][0] > pos.x) {
					break;
				}
			
			var y = series.data[j][1];
			
			if (currentRanges == null || (currentRanges.xaxis.from < j && j < currentRanges.xaxis.to)) {
				$('#timestamp_'+graph_id).show();
				// If no legend, the timestamp labels are short and with value
				if (legends.length == 0) {
					$('#timestamp_'+graph_id).text(labels[j] + ' (' + parseFloat(y).toFixed(2) + ')');
				}
				else {
					$('#timestamp_'+graph_id).text(labels_long[j]);
				}
				
				$('#timestamp_'+graph_id).css('top', plot.offset().top-$('#timestamp_'+graph_id).height()*1.5);
				
				var timesize = $('#timestamp_'+graph_id).width();
				
				if (currentRanges != null) {
					dataset = plot.getData();
				}
				
				var timenewpos = dataset[0].xaxis.p2c(pos.x)+$('.yAxis>div').eq(0).width();
				
				var canvaslimit = plot.width();
				
				if (timesize+timenewpos > canvaslimit) {
					$('#timestamp_'+graph_id).css('left', timenewpos - timesize);
				}
				else {
					$('#timestamp_'+graph_id).css('left', timenewpos);
				}
			}
			else {
				$('#timestamp_'+graph_id).hide();
			}
			
			var label_aux = series.label + ' = ';
			
			// The graphs of points type and unknown graphs will dont be updated
			if (serie_types[i] != 'points' && series.label != $('#hidden-unknown_text').val()) {
				
				$('#legend_' + graph_id + ' .legendLabel')
					.eq(i).text(label_aux.replace(/=.*/,
					'= ' + parseFloat(y).toFixed(2) + ' ' + unit));
			}
			
			$('#legend_' + graph_id + ' .legendLabel')
				.eq(i).css('font-size',font_size+'pt');
			
			$('#legend_' + graph_id + ' .legendLabel')
				.eq(i).css('color','#000');
			
			i++;
		}
	}
	
	// Events
	$('#'+graph_id).bind('plothover',  function (event, pos, item) {
		overview.setCrosshair({ x: pos.x, y: 0 });
		currentPlot = plot;
		latestPosition = pos;
		if (!updateLegendTimeout) {
			updateLegendTimeout = setTimeout(updateLegend, 50);
		}
		
	});
	
	$('#'+graph_id).bind("plotclick", function (event, pos, item) {
		plot.unhighlight();
		if (item && item.series.label != '' && (item.series.label == legend_events || item.series.label == legend_events+series_suffix_str || item.series.label == legend_alerts || item.series.label == legend_alerts+series_suffix_str)) {
			plot.unhighlight();
			var canvaslimit = parseInt(plot.offset().left + plot.width());
			var dataset  = plot.getData();
			var timenewpos = parseInt(dataset[0].xaxis.p2c(pos.x)+plot.offset().left);
			var extrasize = parseInt($('#extra_'+graph_id).css('width').split('px')[0]);
			
			var left_pos;
			if (extrasize+timenewpos > canvaslimit) {
				left_pos = timenewpos - extrasize - 20;
			}
			else {
				left_pos = timenewpos - (extrasize / 2);
			}
			
			var extra_info = '<i>No info to show</i>';
			var extra_show = false;
			
			$('#extra_'+graph_id).css('left',left_pos);
			$('#extra_'+graph_id).css('top',plot.offset().top + 25);
			
			switch(item.series.label) {
				case legend_alerts+series_suffix_str:
				case legend_alerts:
					extra_info = '<b>'+legend_alerts+':<br><span style="font-size:xx-small; font-weight: normal;">From: '+labels_long[item.dataIndex];
					if (labels_long[item.dataIndex+1] != undefined) {
						extra_info += '<br>To: '+labels_long[item.dataIndex+1];
					}
					extra_info += '</span></b>'+get_event_details(alertsz[item.dataIndex]);
					extra_show = true;
					break;
				case legend_events+series_suffix_str:
				case legend_events:
					extra_info = '<b>'+legend_events+':<br><span style="font-size:xx-small; font-weight: normal;">From: '+labels_long[item.dataIndex];
					if (labels_long[item.dataIndex+1] != undefined) {
						extra_info += '<br>To: '+labels_long[item.dataIndex+1];
					}
					extra_info += '</span></b>'+get_event_details(eventsz[item.dataIndex]);
					extra_show = true;
					break;
				default:
					return;
					break;
			}
			
			if (extra_show) {
				$('#extra_'+graph_id).html(extra_info);
				$('#extra_'+graph_id).css('display','');
			}
			plot.highlight(item.series, item.datapoint);
		}
		else {
			$('#extra_'+graph_id).html('');
			$('#extra_'+graph_id).css('display','none');
		}
	});
	
	$('#overview_'+graph_id).bind('plothover',  function (event, pos, item) {
		plot.setCrosshair({ x: pos.x, y: 0 });
		currentPlot = overview;
		latestPosition = pos;
		if (!updateLegendTimeout) {
			updateLegendTimeout = setTimeout(updateLegend, 50);
		}
	});
	
	$('#'+graph_id).bind('mouseout',resetInteractivity);
	$('#overview_'+graph_id).bind('mouseout',resetInteractivity);
	
	// Reset interactivity styles
	function resetInteractivity() {
		$('#timestamp_'+graph_id).hide();
		dataset = plot.getData();
		for (i = 0; i < dataset.length; ++i) {
			$('#legend_' + graph_id + ' .legendLabel')
				.eq(i).text(legends.eq(i).text().replace(/=.*/, ''));
		}
		plot.clearCrosshair();
		overview.clearCrosshair();
	}
	
	// Format functions
	function xFormatter(v, axis) {
		if (labels[v] == undefined) {
			return '';
		}
		return '<div style=font-size:'+font_size+'pt>'+labels[v]+'</div>';
	}
	
	function yFormatter(v, axis) {
		var formatted = number_format(v,force_integer,unit);
		
		return '<div style=font-size:'+font_size+'pt>'+formatted+'</div>';
	}
	
	function lFormatter(v, item) {
		return '<div style=color:#000;font-size:'+font_size+'pt>'+v+'</div>';
		// Prepared to turn series with a checkbox
		//return '<div style=color:#000;font-size:'+font_size+'pt><input type="checkbox" id="' + graph_id + '_' + item.id +'" checked="checked" class="check_serie_'+graph_id+'">'+v+'</div>';
	}
	
	// Prepared to turn series with a checkbox
	//~ $('.check_serie_'+graph_id).click(function() {
		//~ // Format of the id is graph_3905jf93f03_serie_id
		//~ id_clicked = this.id.split('_')[3];
		//~ // Update the serie clicked
		//~ showed[id_clicked] = this.checked;
	//~ });
	
	if (menu) {
		var parent_height;
		$('#menu_overview_' + graph_id).click(function() {
			if ( $('#overview_' + graph_id).css('visibility') == 'hidden' )
				$('#overview_' + graph_id).css('visibility','visible');
			else
				$('#overview_' + graph_id).css('visibility','hidden');
		});
		
		$('#menu_threshold_' + graph_id).click(function() {
			datas = new Array();
			
			if (thresholded) {
				thresholded = false;
			}
			else {
				$.each(threshold_data, function() {
					datas.push(this);
				});
				thresholded = true;
			}
			
			$.each(data_base, function() {
				// Prepared to turning series
				//if(showed[this.id.split('_')[1]]) {
					datas.push(this);
				//}
			});
			
			plot = $.plot($('#' + graph_id), datas, options);
			
			plot.setSelection(currentRanges);
		});
		
		$('#menu_cancelzoom_' + graph_id).click(function() {
			// cancel the zooming
			plot = $.plot($('#'+graph_id), data_base,
				$.extend(true, {}, options, {
					xaxis: {max: max_x },
					legend: { show: false }
				}));
			
			$('#menu_cancelzoom_' + graph_id)
				.attr('src', homeurl + '/images/zoom_cross.disabled.png');
			overview.clearSelection();
			currentRanges = null;
		});
		
		// Adjust the menu image on top of the plot
		// If there is no legend we increase top-padding to make space to the menu
		if (legends.length == 0) {
			$('#menu_' + graph_id).parent().css('padding-top',
				$('#menu_' + graph_id).css('height'));
		}
		
		// Add bottom margin in the legend
		// Estimated height of 24 (works fine with this data in all browsers)
		menu_height = 24;
		var legend_margin_bottom = parseInt($('#legend_'+graph_id).css('margin-bottom').split('px')[0]);
		$('#legend_'+graph_id).css('margin-bottom', menu_height+legend_margin_bottom+'px');
		parent_height = parseInt($('#menu_'+graph_id).parent().css('height').split('px')[0]);
		adjust_menu(graph_id, plot, parent_height);
	}
	
	if (water_mark) {
		set_watermark(graph_id, plot, $('#watermark_image_'+graph_id).attr('src'));
	}
	
	adjust_menu(graph_id, plot, parent_height);
}

function adjust_menu(graph_id, plot, parent_height) {
	if ($('#'+graph_id+' .xAxis .tickLabel').eq(0).css('width') != undefined) {
		left_ticks_width = $('#'+graph_id+' .xAxis .tickLabel').eq(0).css('width').split('px')[0];
	}
	else {
		left_ticks_width = 0;
	}
	
	var parent_height_new = 0;
	
	var legend_height = parseInt($('#legend_'+graph_id).css('height').split('px')[0]) + parseInt($('#legend_'+graph_id).css('margin-top').split('px')[0]);
	if ($('#overview_'+graph_id).css('display') == 'none') {
		overview_height = 0;
	}
	else {
		overview_height = parseInt($('#overview_'+graph_id).css('height').split('px')[0]) + parseInt($('#overview_'+graph_id).css('margin-top').split('px')[0]);
	}
	
	var menu_height = '25';
	
	if ($('#menu_'+graph_id).height() != undefined && $('#menu_'+graph_id).height() > 20) {
		menu_height = $('#menu_'+graph_id).height();
	}
	
	offset_between_graph_and_div_graph_container = $('#' + graph_id).offset().top -
		$('#' + graph_id).parent().offset().top;
	$('#menu_' + graph_id)
		.css('top',
			((offset_between_graph_and_div_graph_container - menu_height - 5) + 'px'));
	
	//$('#legend_' + graph_id).css('width',plot.width());
	
	$('#menu_' + graph_id)
		.css('left',plot.width() - $('#menu_'+graph_id).width() + 10);
	$('#menu_' + graph_id).show();
}

function set_watermark(graph_id, plot, watermark_src) {
	var img = new Image();
	img.src = watermark_src;
	var context = plot.getCanvas().getContext('2d');
	
	// Once it's loaded draw the image on the canvas.
	img.addEventListener('load', function () {
		//~ // Now resize the image: x, y, w, h.
		
		var down_ticks_height = 0;
		if ($('#'+graph_id+' .yAxis .tickLabel').eq(0).css('height') != undefined) {
			down_ticks_height = $('#'+graph_id+' .yAxis .tickLabel').eq(0).css('height').split('px')[0];
		}
		var left_pos = parseInt(context.canvas.width - 3) - $('#watermark_image_'+graph_id)[0].width;
		var top_pos = parseInt(context.canvas.height - down_ticks_height - 20) - $('#watermark_image_'+graph_id)[0].height;
		
		context.drawImage(this, left_pos, top_pos);
		
	}, false);
}

function get_event_details (event_ids) {
	table = '';
	if (typeof(event_ids) != "undefined") {
		var inputs = [];
		var table;
		inputs.push ("get_events_details=1");
		inputs.push ("event_ids="+event_ids);
		inputs.push ("page=include/ajax/events");
		
		// Autologin
		if ($('#hidden-loginhash').val() != undefined) {
			inputs.push ("loginhash=" + $('#hidden-loginhash').val());
			inputs.push ("loginhash_data=" + $('#hidden-loginhash_data').val());
			inputs.push ("loginhash_user=" + $('#hidden-loginhash_user').val());
		}
		
		jQuery.ajax ({
			data: inputs.join ("&"),
			type: 'GET',
			url: action="../../ajax.php",
			timeout: 10000,
			dataType: 'html',
			async: false,
			success: function (data) {
				table = data;
				//forced_title_callback();
			}
		});
	}
	
	return table;
}

function adjust_left_width_canvas(adapter_id, adapted_id) {
	adapter_left_margin = $('#'+adapter_id+' .yAxis .tickLabel').css('width');
	
	adapted_pix = $('#'+adapted_id).css('width').split('px');
	new_adapted_width = parseInt(adapted_pix[0])-parseInt(adapter_left_margin);
	
	$('#'+adapted_id).css('width',new_adapted_width);
	
	$('#'+adapted_id).css('margin-left',adapter_left_margin);
}

function check_adaptions(graph_id) {
	var classes = $('#'+graph_id).attr('class').split(' ');
	
	$.each(classes, function(i,v) {
		// If has a class starting with adapted, we adapt it
		if (v.split('_')[0] == 'adapted') {
			var adapter_id = $('.adapter_'+v.split('_')[1]).attr('id');
			adjust_left_width_canvas(adapter_id, graph_id);
		}
	});
}

function number_format(number, force_integer, unit) {	
	if (force_integer) {
		if (Math.round(number) != number) {
			return '';
		}
	}
	else {
		var decimals = 2;
		var factor = 10 * decimals;
		number = Math.round(number*factor)/factor
	}
	
	var shorts = ["", "K", "M", "G", "T", "P", "E", "Z", "Y"];
	var pos = 0;
	while (1) {
		if (number >= 1000) { //as long as the number can be divided by 1000
			pos++; //Position in array starting with 0
			number = number / 1000;
		}
		else {
			break;
		}
	}
	
	return number+' '+shorts[pos]+unit;
}
