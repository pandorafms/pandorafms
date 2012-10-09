// Show the modal window of an event
function show_event_dialog(event_id, group_rep, dialog_page, result) {
	if(dialog_page == undefined) {
		dialog_page = 'general';
	}

	jQuery.post ("ajax.php",
		{"page": "include/ajax/events",
		"get_extended_event": 1,
		"group_rep": group_rep,
		"dialog_page": dialog_page,
		"event_id": event_id},
		function (data, status) {
			$("#event_details_window").hide ()
				.empty ()
				.append (data)
				.dialog ({
					title: $("#hidden-event_title_"+event_id).val(),
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
function execute_response(event_id) {
	var response_id = $('#select_custom_response option:selected').val();
	
	var response = get_response(response_id);
	
	// If cannot get response abort it
	if(response == null) {
		return;
	}
		
	response['target'] = get_response_target(event_id, response_id);
	
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
	var params = [];
	params.push("page=include/ajax/events");
	params.push("dialogue_event_response=1");
	params.push("event_id="+event_id);
	params.push("target="+response['target']);
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
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
	var response = '';
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response=1");
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
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
	var response_params;
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response_params=1");
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
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
	var response_description = '';
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response_description=1");
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: false,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			response_description = data;
		}
	});
	
	return response_description;
}

function add_row_param(id_table, param) {
	$('#'+id_table).append('<tr class="params_rows"><td style="text-align:left; padding-left:40px;">'+param+'</td><td style="text-align:left"><input type="text" name="'+param+'" id="'+param+'"></td></tr>');
}

// Get an event response from db
function get_response_target(event_id, response_id) {
	var target = '';
	
	// Replace the main macros
	var params = [];
	params.push("page=include/ajax/events");
	params.push("get_response_target=1");
	params.push("event_id="+event_id);
	params.push("response_id="+response_id);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
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
		url: action="ajax.php",
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
function event_change_status() {
	var event_id = $('#hidden-id_event').val();
	var new_status = $('#estado').val();
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("change_status=1");
	params.push("event_id="+event_id);
	params.push("new_status="+new_status);
	
	$('#button-status_button').attr('disabled','disabled');
	$('#response_loading').show();
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$('#button-status_button').removeAttr('disabled');
			$('#response_loading').hide();
			show_event_dialog(event_id, $('#hidden-group_rep').val(), 'responses', data);
			if(data == 'ok') {
			}
			else {
			}
		}
	});	
	return false;
}

// Change te owner of an event to one user of empty
function event_change_owner() {
	var event_id = $('#hidden-id_event').val();
	var new_owner = $('#id_owner').val();
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("change_owner=1");
	params.push("event_id="+event_id);
	params.push("new_owner="+new_owner);
	
	$('#button-owner_button').attr('disabled','disabled');
	$('#response_loading').show();

	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
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
	var event_id = $('#hidden-id_event').val();
	var comment = $('#textarea_comment').val();
	
	if(comment == '') {
		show_event_dialog(event_id, $('#hidden-group_rep').val(), 'comments', 'comment_error');
		return false;
	}
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("add_comment=1");
	params.push("event_id="+event_id);
	params.push("comment="+comment);
	
	$('#button-comment_button').attr('disabled','disabled');
	$('#response_loading').show();

	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
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
