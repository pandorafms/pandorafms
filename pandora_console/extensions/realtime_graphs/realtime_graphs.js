var max_data_plot = 100;

var options = {
			legend: { container: $("#chartLegend") },
			xaxis: {
				tickFormatter: function (timestamp, axis) {
					var date = new Date(timestamp * 1000);
					
					var server_timezone_offset = get_php_value('timezone_offset');
					var local_timezone_offset = date.getTimezoneOffset()*60*-1;
					
					if (server_timezone_offset != local_timezone_offset) {
						// If timezone of server and client is different, adjust the time to the server
						date = new Date((timestamp + (server_timezone_offset - local_timezone_offset)) * 1000);
					}
					
					var hours = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours());
					var minutes = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes());
					var seconds = (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
					var formattedTime = hours + ':' + minutes + ':' + seconds;
					return formattedTime;
				}
			},
			series: {
				lines: {
					lineWidth: 2,
					fill: true
				}
			},
			colors: ['#6db431']
		}

var data = [];

var id = $('.graph').attr('id');
var plot = $.plot("#" + id, data, options);


var refresh = parseInt($('#refresh').val());
var incremental = $('#checkbox-incremental').is(':checked') || $('#hidden-incremental').val() == 1;
var incremental_base = 0;
var last_inc = 0;
var to;

refresh_graph();

function refresh_graph () {
	var refresh = parseInt($('#refresh').val());
	
	var postvars = new Array();
	var postvars = {};
	postvars['graph'] = $('#graph :selected').val();
	postvars['graph_title'] = $('#graph :selected').html();
	
	postvars['snmp_community'] = $('#text-snmp_community').val();
	postvars['snmp_oid'] = $('#text-snmp_oid').val();
	postvars['snmp_ver'] = $('#snmp_version :selected').val();
	postvars['snmp_address'] = $('#text-ip_target').val();
	
	postvars['refresh'] = refresh;

	var rel_path = $("#hidden-rel_path").val();

	$.ajax({
		url: rel_path + "extensions/realtime_graphs/ajax.php",
		type: "POST",
		dataType: "json",
		data: postvars,
		success: function(serie) {
			var timestamp = serie.data[0][0];
			data = plot.getData();
			if (data.length == 0) {
				for(i = 0; i < max_data_plot; i ++) {
					step = i * (refresh/1000);
					serie.data.unshift([timestamp-step, 0]);
				}
				
				serie = [serie];
				plot = $.plot("#" + id, serie, options);
				return;
			}
			data[0].label = serie.label;
			if (data[0].data.length >= max_data_plot) {
				data[0].data.shift();
			}
			
			if (incremental) {
				var last_item = parseInt(data[0].data.length)-1;
				var last_value = data[0].data[last_item][1];
				
				var current_value = serie.data[0][1];
				
				serie.data[0][1] = current_value - last_inc;
				
				last_inc = current_value;
				
				// Incremental is always positive
				if (serie.data[0][1] < 0) {
					serie.data[0][1] = 0;
				}
			}
			
			data[0].data.push(serie.data[0]);
			$.plot("#" + id, data, options);
		}
	});
	to = window.setTimeout(refresh_graph, refresh);
}

$('#graph').change(function() {
	$('form#realgraph').submit();
});

$('#refresh').change(function() {
	var refresh = parseInt($('#refresh').val());
	
	// Stop and start the Timeout
	clearTimeout(to);
	to = window.setTimeout(refresh_graph, refresh);
});

// Show the SNMP browser window
function snmpBrowserWindow () {
	
	// Keep elements in the form and the SNMP browser synced
	$('#text-target_ip').val($('#text-ip_target').val());
	$('#text-community').val($('#text-snmp_community').val());
	$('#snmp_browser_version').val($('#snmp_version').val());
	$('#snmp3_browser_auth_user').val($('#snmp3_auth_user').val());
	$('#snmp3_browser_security_level').val($('#snmp3_security_level').val());
	$('#snmp3_browser_auth_method').val($('#snmp3_auth_method').val());
	$('#snmp3_browser_auth_pass').val($('#snmp3_auth_pass').val());
	$('#snmp3_browser_privacy_method').val($('#snmp3_privacy_method').val());
	$('#snmp3_browser_privacy_pass').val($('#snmp3_privacy_pass').val());
	
	$("#snmp_browser_container").show().dialog ({
		title: '',
		resizable: true,
		draggable: true,
		modal: true,
		overlay: {
			opacity: 0.5,
			background: "black"
		},
		width: 920,
		height: 500
	});
}

// Set the form OID to the value selected in the SNMP browser
function setOID () {
	
	if($('#snmp_browser_version').val() == '3'){
		$('#text-snmp_oid').val($('#table1-0-1').text());
	} else {
		$('#text-snmp_oid').val($('#snmp_selected_oid').text());
	}
	
	// Close the SNMP browser
	$('.ui-dialog-titlebar-close').trigger('click');
}

$('#checkbox-incremental').change(function() {
	incremental = $('#checkbox-incremental').is(':checked');
	clearGraph();
});

function firstNotZero(data) {
	var notZero = 0;
	for(i = 0; i < data[0].data.length; i ++) {
		if (data[0].data[i][1] != 0) {
			return data[0].data[i][1];
		}
	}
}

function setOnIncremental() {
	
}

function clearGraph() {
	data = plot.getData();
	if (data.length == 0) {
		return;
	}
	
	for(i = 0; i < data[0].data.length; i ++) {
		data[0].data[i][1] = 0;
	}
	
	$.plot("#" + id, data, options);
}
