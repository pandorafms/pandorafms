/*


*/

function pandoraFlotPie(graph_id, values, labels, nseries, width, font_size, water_mark, separator) {
	var labels = labels.split(separator);
	var data = values.split(separator);

	for( var i = 0; i<nseries; i++)
	{
		data[i] = { label: labels[i], data: parseInt(data[i]) }
	}
	
	var label_conf;
	
	if(width < 400) {
		label_conf = {
			show: false
		};
	}
	else {
		label_conf = {
			show: true,
			radius: 3/4,
			formatter: function(label, series){
				return '<div style="font-size:'+font_size+'pt;text-align:center;padding:2px;color:white;">'+label+'<br/>'+Math.round(series.percent)+'%</div>';
			},
			background: {
				opacity: 0.5,
				color: '#000'
			}
		};
	}
	
	var plot = $.plot($('#'+graph_id), data,
	{
			series: {
				pie: {
					show: true,
					radius: 3/4,
					label: label_conf
					//$label_str
				}
			},
			legend: {
				show: true
			},
			grid: {
				hoverable: true,
				clickable: true
			}
	});
	
	var legends = $('#'+graph_id+' .legendLabel');
	legends.each(function () {
		// fix the widths so they don't jump around
		$(this).css('width', $(this).width()+5);
	});

	// Events
	$('#'+graph_id).bind('plothover', pieHover);
	$('#'+graph_id).bind('plotclick', pieClick);
	$('#'+graph_id).bind('mouseout',resetInteractivity);

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
	
	if(water_mark) {
		set_watermark(graph_id, plot, $('#watermark_image_'+graph_id).attr('src'));
	}
}

function pandoraFlotHBars(graph_id, values, labels, water_mark, maxvalue, water_mark, separator, separator2) {
	
	values = values.split(separator2);
	var datas = new Array();
	
	for(i=0;i<values.length;i++) {
		var serie = values[i].split(separator);
		var aux = new Array();
		$.each(serie.reverse(),function(i,v) {
			aux.push([v, i]);
		});
		
		datas.push({ 
			data: aux, 
			bars: { show: true, horizontal: true, fillColor: { colors: [ { opacity: 0.7 }, { opacity: 1 } ] }, lineWidth:1, steps:false } 
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
					  position: 'right',
					  
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
	$('#'+graph_id+' .yAxis .tickLabel').each(function() {
		tickTop = $(this).css('top').split('px')[0];
		tickNewTop = parseInt(parseInt(tickTop) - (yAxisHeight/2)-3);
		$(this).css('top', tickNewTop+'px');
		
		valuesNewTop = parseInt(parseInt(tickTop) - (yAxisHeight));
		
		$('#value_'+i+'_'+graph_id).css('top',parseInt(plot.offset().top) + parseInt(valuesNewTop));

		pixelPerValue = parseInt(plot.width()) / maxvalue;
		
		inCanvasValuePos = parseInt(pixelPerValue * ($('#value_'+i+'_'+graph_id).html()));
		
		$('#value_'+i+'_'+graph_id).css('left',plot.offset().left + inCanvasValuePos - $('#value_'+i+'_'+graph_id).css('width').split('px')[0] - 3);
		i++;
	});
	
	// When resize the window, adjust the values
	$('#'+graph_id).parent().resize(function () {
		i = 0;
		pixelPerValue = parseInt(plot.width()) / maxvalue;
		
		$('#'+graph_id+' .yAxis .tickLabel').each(function() {			
			inCanvasValuePos = parseInt(pixelPerValue * ($('#value_'+i+'_'+graph_id).html()));
			
			$('#value_'+i+'_'+graph_id).css('left',plot.offset().left + inCanvasValuePos - $('#value_'+i+'_'+graph_id).css('width').split('px')[0] - 3);
			i++;
		});
    });
	
	// Format functions
    function xFormatter(v, axis) {
		if(labels[v] != undefined) {
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
    $('#'+graph_id).bind('plothover',  function (event, pos, item) {
		$('.values_'+graph_id).css('font-weight', '');
		if(item != null) {
			index = item.dataIndex;
			$('#value_'+index+'_'+graph_id).css('font-weight', 'bold');
		}
    });
    
	if(water_mark) {
		set_watermark(graph_id, plot, $('#watermark_image_'+graph_id).attr('src'));
	}
}

function pandoraFlotVBars(graph_id, values, labels, labels_long, legend, colors, water_mark, maxvalue, water_mark, separator, separator2) {
	
	values = values.split(separator2);
	legend = legend.split(separator);
	labels_long = labels_long.split(separator);
	colors = colors.split(separator);

	var datas = new Array();
	
	for(i=0;i<values.length;i++) {
		var serie = values[i].split(separator);
		
		var serie_color;
		if(colors[i] != '') {
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
	
	labels = labels.split(separator).reverse();

	var stack = 0, bars = true, lines = false, steps = false;

	var options = { 
			series: { 
				shadowSize: 0.1 
			},
			crosshair: { mode: 'xy' },
			selection: { mode: 'x', color: '#777' },
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
					tickSize: 1,
					color: '#000'
					},
					{
					  // align if we are to the right
					  alignTicksWithAxis: 1,
					  position: 'right',
					  
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
		if(labels[v] != undefined) {
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
    
	if(water_mark) {
		set_watermark(graph_id, plot, $('#watermark_image_'+graph_id).attr('src'));
	}
}

function pandoraFlotSlicebar(graph_id, values, datacolor, labels, legend, acumulate_data, intervaltick, water_mark, maxvalue, separator, separator2) {
	
	values = values.split(separator2);
	labels = labels.split(separator);
	legend = legend.split(separator);
	acumulate_data = acumulate_data.split(separator);
	datacolor = datacolor.split(separator);
	
	var datas = new Array();
	
	for(i=0;i<values.length;i++) {
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

	// Format functions
    function xFormatter(v, axis) {
		for(i = 0; i < acumulate_data.length; i++) {
			if(acumulate_data[i] == v) {
				return '<span style=\'font-size: 7pt\'>' + legend[i] + '</span>';
			}
		}
		return '';
    }
}

function pandoraFlotArea(graph_id, values, labels, labels_long, legend, colors, type, serie_types, water_mark, width, max_x, homeurl, unit, font_size, menu, events, event_ids, legend_events, alerts, alert_ids, legend_alerts, yellow_threshold, red_threshold, separator, separator2) {

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
	
	for(i=0;i<values.length;i++) {
		var serie = values[i].split(separator);
		var aux = new Array();
		var critical_min = new Array();
		var warning_min = new Array();
		$.each(serie,function(i,v) {
			aux.push([i, v]);
			if(threshold) {
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
		if(colors[i] != '') {
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
			id: 'serie_'+i,
			data: aux, 
			label: legend[i],
			color: serie_color,
			//threshold: [{ below: 80, color: "rgb(200, 20, 30)" } , { below: 65, color: "rgb(30, 200, 30)" }, { below: 50, color: "rgb(30, 200, 30)" }],
			lines: { show: line_show, fill: filled, fillColor: { colors: [ { opacity: 1 }, { opacity: 1 } ]}, lineWidth:1, steps:false },
			points: { show: points_show }
		});

		// Prepared to turn series with a checkbox
		// showed[i] = true;
	}
	
	var threshold_data = new Array();
	
	if(threshold) {
		// Warning and critical treshold
		threshold_data.push({ 
			id: 'critical_min',
			data: critical_min, 
			label: null,
			color: critical,
			lines: { show: true, fill: false, lineWidth:3},
		});
		threshold_data.push({ 
			id: 'warning_min',
			data: warning_min, 
			label: null,
			color: warning,
			lines: { show: true, fill: false, lineWidth:3},
		});
	}
	
	// The first execution, the graph data is the base data
	datas = data_base;
				
	// minTickSize
	var mts = 130;
		
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
					minTickSize: mts,
					color: '#000'
					} ],
			yaxes: [ { 
					tickFormatter: yFormatter,
					min: 0,
					color: '#000'
					},
					{
					  // align if we are to the right
					  alignTicksWithAxis: 1,
					  position: 'right',
					  
					  //tickFormatter: dFormatter
					} ]
					,
			legend: { 
				position: 'se', 
				container: $('#legend_'+graph_id),
				labelFormatter: lFormatter
				}
	   };

	var stack = 0, bars = true, lines = false, steps = false;
	
	var plot = $.plot($('#'+graph_id), datas, options);

	// Adjust the overview plot to the width and position of the main plot
	yAxisWidth = $('#'+graph_id+' .yAxis .tickLabel').css('width');
	
	overview_pix = $('#overview_'+graph_id).css('width').split('px');
	new_overview_width = parseInt(overview_pix[0])-parseInt(yAxisWidth);

	$('#overview_'+graph_id).css('width',new_overview_width);

	$('#overview_'+graph_id).css('margin-left',yAxisWidth);
	
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
					minTickSize: mts,
					color: '#000'
					} ],
        yaxis: { ticks: [], min: 0, autoscaleMargin: 0.1 },
        selection: { mode: 'x', color: '#777' },
        legend: {show: false},
        crosshair: { mode: 'x' }
    });
    
    // Connection between plot and miniplot
    
    $('#'+graph_id).bind('plotselected', function (event, ranges) {
        // do the zooming
        dataInSelection = ranges.xaxis.to - ranges.xaxis.from;
        dataInPlot = plot.getData()[0].data.length;
        
        factor = dataInSelection / dataInPlot;
        
        new_mts = parseInt(factor*mts);
                
        plot = $.plot($('#'+graph_id), datas,
                      $.extend(true, {}, options, {
                        xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to},
						xaxes: [ { 
								tickFormatter: xFormatter,
								minTickSize: new_mts,
								color: '#000'
								} ],                          
						legend: { show: false },
                      }));
		
		$('#menu_cancelzoom_'+graph_id).attr('src',homeurl+'/images/zoom_cross.png');
		
		currentRanges = ranges;
        // don't fire event on the overview to prevent eternal loop
        overview.setSelection(ranges, true);
    });
    
    $('#overview_'+graph_id).bind('plotselected', function (event, ranges) {
        plot.setSelection(ranges);
    });
    
    var legends = $('#legend_'+graph_id+' .legendLabel');
    
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

			if(series.label == null) {
				continue;
			}
			
            // find the nearest points, x-wise
            for (j = 0; j < series.data.length; ++j)
                if (series.data[j][0] > pos.x) {
                    break;
				}
            
            var y = series.data[j][1];
														
			if(currentRanges == null || (currentRanges.xaxis.from < j && j < currentRanges.xaxis.to)) {
				$('#timestamp_'+graph_id).show();
				if(width < 400) {
					$('#timestamp_'+graph_id).text(labels[j] + ' (' + parseFloat(y).toFixed(2) + ')');
				}
				else {
					$('#timestamp_'+graph_id).text(labels_long[j]);
				}
				
				$('#timestamp_'+graph_id).css('top', plot.offset().top);
				
				var timesize = $('#timestamp_'+graph_id).width();
				
				if(currentRanges != null) {
					dataset = plot.getData();
				}
				
				var timenewpos = dataset[0].xaxis.p2c(pos.x)+plot.offset().left;
			
				var canvaslimit = plot.offset().left + plot.width();
							
				if(timesize+timenewpos > canvaslimit) {
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
			
			if(serie_types[i] != 'points') {
				legends.eq(i).text(label_aux.replace(/=.*/, '= ' + parseFloat(y).toFixed(2) +' '+unit));
			}				
			
			legends.eq(i).css('font-size',font_size+'pt');
			legends.eq(i).css('color','#000');
			
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
		if (item && item.series.label != '') {
			plot.unhighlight();
			var canvaslimit = parseInt(plot.offset().left + plot.width());
			var dataset  = plot.getData();
			var timenewpos = parseInt(dataset[0].xaxis.p2c(pos.x)+plot.offset().left);
			var extrasize = parseInt($('#extra_'+graph_id).css('width').split('px')[0]);
			
			var left_pos;
			if(extrasize+timenewpos > canvaslimit) {
				left_pos = timenewpos - extrasize - 20;
			}
			else {
				left_pos = timenewpos + 20;
			}
			
			var extra_info = '<i>No info to show</i>';
			var extra_show = false;
			
			$('#extra_'+graph_id).css('left',left_pos);
			$('#extra_'+graph_id).css('top',plot.offset().top + 25);
			
			switch(item.series.label) {
				case legend_alerts:
					extra_info = '<b>'+legend_alerts+' - '+labels_long[item.dataIndex]+'</b>'+get_event_details(alertsz[item.dataIndex]);
					extra_show = true;
					break;
				case legend_events:
					extra_info = '<b>'+legend_events+' - '+labels_long[item.dataIndex]+'</b>'+get_event_details(eventsz[item.dataIndex]);
					extra_show = true;
					break;
				default:
						return;
					break;
			}
			
			extra_info = get_event_details(eventsz[item.dataIndex]);
			if(extra_show) {
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
            legends.eq(i).text(legends.eq(i).text().replace(/=.*/, ''));
        }
        plot.clearCrosshair();
        overview.clearCrosshair();
	}
	
	// Format functions
    function xFormatter(v, axis) {
		if(labels[v] == undefined) {
			return '';
		}
        return '<div style=font-size:'+font_size+'pt>'+labels[v]+'</div>';
    }
    
    function yFormatter(v, axis) {
        return '<div style=font-size:'+font_size+'pt>'+v+' '+unit+'</div>';
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
	
	if(menu) {
		$('#menu_overview_'+graph_id).click(function() {
			$('#overview_'+graph_id).toggle();
		});
		
		$('#menu_threshold_'+graph_id).click(function() {
			datas = new Array();
			
			if(thresholded) {
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

			plot = $.plot($('#'+graph_id), datas, options);
		});
		
		$('#menu_cancelzoom_'+graph_id).click(function() {
			// cancel the zooming
			plot = $.plot($('#'+graph_id), data_base,
			  $.extend(true, {}, options, {
				  xaxis: { min: 0, max: max_x },
				  legend: { show: false }
			  }));
			
			$('#menu_cancelzoom_'+graph_id).attr('src',homeurl+'/images/zoom_cross.disabled.png');
			overview.clearSelection();
		});
		
		// Adjust the menu image on top of the plot
		$('#menu_overview_'+graph_id)[0].onload = function() {
			var menu_width = $('#menu_'+graph_id).css('width').split('px')[0];
			var canvaslimit_right = parseInt(plot.offset().left + plot.width());
			var canvaslimit_top = parseInt(plot.offset().top - 8);
			var n_options = parseInt($('#menu_'+graph_id).children().length);

			$('#menu_'+graph_id).css('left',canvaslimit_right-menu_width-20);
			$('#menu_'+graph_id).css('top',canvaslimit_top-parseInt(this.height));
			$('#menu_'+graph_id).show();
		}		
	}
	
	if(water_mark) {
		set_watermark(graph_id, plot, $('#watermark_image_'+graph_id).attr('src'));
	}
}

function set_watermark(graph_id, plot, watermark_src) {
		var img = new Image();
		img.src = watermark_src;
		var context = plot.getCanvas().getContext('2d');

		// Once it's loaded draw the image on the canvas.
		img.addEventListener('load', function () {
			//~ // Now resize the image: x, y, w, h.
			
			var down_ticks_height = 0;
			if($('#'+graph_id+' .yAxis .tickLabel').eq(0).css('height') != undefined) {
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
		jQuery.ajax ({
			data: inputs.join ("&"),
			type: 'GET',
			url: action="../../ajax.php",
			timeout: 10000,
			dataType: 'html',
			async: false,
			success: function (data) {
				table = data;
			}
		});
	}
	
	return table;
}
