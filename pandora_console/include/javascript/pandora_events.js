// Show the modal window of an event
function show_event_dialog(event_id, group_rep, dialog_page, result) {
	var ajax_file = $('#hidden-ajax_file').val();

	if(dialog_page == undefined) {
		dialog_page = 'general';
	}
	
	var similar_ids = $('#hidden-similar_ids_'+event_id).val();
	var timestamp_first = $('#hidden-timestamp_first_'+event_id).val();
	var timestamp_last = $('#hidden-timestamp_last_'+event_id).val();
	var user_comment = $('#hidden-user_comment_'+event_id).val();
	var event_rep = $('#hidden-event_rep_'+event_id).val();
	var server_id = $('#hidden-server_id_'+event_id).val();

	// Metaconsole mode flag
	var meta = $('#hidden-meta').val();
	
	// History mode flag
	var history = $('#hidden-history').val();

	jQuery.post (ajax_file,
		{"page": "include/ajax/events",
		"get_extended_event": 1,
		"group_rep": group_rep,
		"event_rep": event_rep,
		"dialog_page": dialog_page,
		"similar_ids": similar_ids,
		"timestamp_first": timestamp_first,
		"timestamp_last": timestamp_last,
		"user_comment": user_comment,
		"event_id": event_id,
		"server_id": server_id,
		"meta": meta,
		"history": history},
		function (data, status) {
			$("#event_details_window").hide ()
				.empty ()
				.append (data)
				.dialog ({
					title: get_event_name(event_id, meta, history),
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					bgiframe: jQuery.browser.msie,
					width: 620,
					height: 500
				})
				.show ();
				
			switch(result) {
				case 'comment_ok':
					$('#notification_comment_success').show();
					break;
				case 'comment_error':
					$('#notification_comment_error').show();
					break;
				case 'status_ok':
					$('#notification_status_success').show();
					break;
				case 'status_error':
					$('#notification_status_error').show();
					break;
				case 'owner_ok':
					$('#notification_owner_success').show();
					break;
				case 'owner_error':
					$('#notification_owner_error').show();
					break;
			}
		},
		"html"
	);	
	return false;
}

// Check the response type and open it in a modal dialog or new window 
function execute_response(event_id, server_id) {
	var response_id = $('#select_custom_response option:selected').val();
	
	var response = get_response(response_id);
	
	// If cannot get response abort it
	if(response == null) {
		return;
	}
		
	response['target'] = get_response_target(event_id, response_id, server_id);
	
	switch(response['type']) {
		case 'command':
			show_response_dialog(event_id, response_id, response);
			break;
		case 'url':
			if(response['new_window'] == 1) {
				window.open(response['target'],'_blank');
			}
			else {
				show_response_dialog(event_id, response_id, response);
			}
			break;
	}
}

//Show the modal window of an event response
function show_response_dialog(event_id, response_id, response) {
	var ajax_file = $('#hidden-ajax_file').val();

	var params = [];
	params.push("page=include/ajax/events");
	params.push("dialogue_event_response=1");
	params.push("event_id="+event_id);
	params.push("target="+response['target']);
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: false,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$("#event_response_window").hide ()
				.empty ()
				.append (data)
				.dialog ({
					title: $('#select_custom_response option:selected').html(),
					resizable: true,
					draggable: true,
					modal: false,
					open: function(event, ui) {
						perform_response(response['target']);
					},
					bgiframe: jQuery.browser.msie,
					width: response['modal_width'], 
					height: response['modal_height']
				})
				.show ();
		}
	});
}

// Get an event response from db
function get_response(response_id) {
	var ajax_file = $('#hidden-ajax_file').val();
	
	var response = '';
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response=1");
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: false,
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			response = data;
		}
	});
	
	return response;
}

// Get an event response params from db
function get_response_params(response_id) {
	var ajax_file = $('#hidden-ajax_file').val();

	var response_params;
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response_params=1");
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: false,
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			response_params = data;
		}
	});
	
	return response_params;
}

// Get an event response description from db
function get_response_description(response_id) {
	var ajax_file = $('#hidden-ajax_file').val();

	var response_description = '';
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response_description=1");
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: false,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			response_description = data;
		}
	});
	
	return response_description;
}

// Get an event response description from db
function get_event_name(event_id, meta, history) {
	var ajax_file = $('#hidden-ajax_file').val();

	var name = '';
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_event_name=1");
	params.push("event_id="+event_id);
	params.push("meta="+meta);
	params.push("history="+history);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: false,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			name = data;
		}
	});
	
	return name;
}

function add_row_param(id_table, param) {
	$('#'+id_table).append('<tr class="params_rows"><td style="text-align:left; padding-left:40px;">'+param+'</td><td style="text-align:left"><input type="text" name="'+param+'" id="'+param+'"></td></tr>');
}

// Get an event response from db
function get_response_target(event_id, response_id, server_id) {
	var ajax_file = $('#hidden-ajax_file').val();

	var target = '';
	
	// Replace the main macros
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response_target=1");
	params.push("event_id="+event_id);
	params.push("response_id="+response_id);
	params.push("server_id="+server_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: false,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			target = data;
		}
	});
	
	// Replace the custom params macros
	var response_params = get_response_params(response_id);
	
	if(response_params.length > 1 || response_params[0] != '') {
		for(i=0;i<response_params.length;i++) {
			target = target.replace('_'+response_params[i]+'_',$('#'+response_params[i]).val());
		}
	}
	
	return target;
}

// Perform a response and put the output into a div
function perform_response(target) {
	var ajax_file = $('#hidden-ajax_file').val();

	$('#re_exec_command').hide();
	$('#response_loading_command').show();
	$('#response_out').html('');
	
	var finished = 0;
	var time = Math.round(+new Date()/1000);
	var timeout = time + 10;
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("perform_event_response=1");
	params.push("target="+target);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			var out = data.replace(/[\n|\r]/g, "<br>");
			$('#response_out').html(out);
			$('#response_loading_command').hide();
			$('#re_exec_command').show();
		}
	});

	return false;
}

// Change the status of an event to new, in process or validated
function event_change_status(event_ids) {
	var ajax_file = $('#hidden-ajax_file').val();

	var new_status = $('#estado').val();
	var event_id = $('#hidden-id_event').val();
	var meta = $('#hidden-meta').val();
	var history = $('#hidden-history').val();
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("change_status=1");
	params.push("event_ids="+event_ids);
	params.push("new_status="+new_status);
	params.push("meta="+meta);
	params.push("history="+history);
	
	$('#button-status_button').attr('disabled','disabled');
	$('#response_loading').show();
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$('#button-status_button').removeAttr('disabled');
			$('#response_loading').hide();
			show_event_dialog(event_id, $('#hidden-group_rep').val(), 'responses', data);
			if(data == 'status_ok') {
			}
			else {
			}
		}
	});	
	return false;
}

// Change te owner of an event to one user of empty
function event_change_owner() {
	var ajax_file = $('#hidden-ajax_file').val();

	var event_id = $('#hidden-id_event').val();
	var new_owner = $('#id_owner').val();
	var meta = $('#hidden-meta').val();
	var history = $('#hidden-history').val();
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("change_owner=1");
	params.push("event_id="+event_id);
	params.push("new_owner="+new_owner);
	params.push("meta="+meta);
	params.push("history="+history);
	
	$('#button-owner_button').attr('disabled','disabled');
	$('#response_loading').show();

	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$('#button-owner_button').removeAttr('disabled');
			$('#response_loading').hide();
			
			show_event_dialog(event_id, $('#hidden-group_rep').val(), 'responses', data);
		}
	});	
	
	return false;
}

// Save a comment into an event
function event_comment() {
	var ajax_file = $('#hidden-ajax_file').val();

	var event_id = $('#hidden-id_event').val();
	var comment = $('#textarea_comment').val();
	var meta = $('#hidden-meta').val();
	var history = $('#hidden-history').val();

	if(comment == '') {
		show_event_dialog(event_id, $('#hidden-group_rep').val(), 'comments', 'comment_error');
		return false;
	}
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("add_comment=1");
	params.push("event_id="+event_id);
	params.push("comment="+comment);
	params.push("meta="+meta);
	params.push("history="+history);
	
	$('#button-comment_button').attr('disabled','disabled');
	$('#response_loading').show();

	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action=ajax_file,
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$('#button-comment_button').removeAttr('disabled');
			$('#response_loading').show();
			
			show_event_dialog(event_id, $('#hidden-group_rep').val(), 'comments', data);
		}
	});	
	
	return false;
}
