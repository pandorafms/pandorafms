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
			$("#alert_messages").hide ()
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


function event_change_status() {
	var event_id = $('#hidden-id_event').val();
	var new_status = $('#estado').val();
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("change_status=1");
	params.push("event_id="+event_id);
	params.push("new_status="+new_status);
	
	$('#button-status_button').attr('disabled','disabled');

	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$('#button-status_button').removeAttr('disabled');
			show_event_dialog(event_id, $('#hidden-group_rep').val(), 'actions', data);
			if(data == 'ok') {
			}
			else {
			}
		}
	});	
	return false;
}

function event_change_owner() {
	var event_id = $('#hidden-id_event').val();
	var new_owner = $('#id_owner').val();
	
	var params = [];
	params.push("page=include/ajax/events");
	params.push("change_owner=1");
	params.push("event_id="+event_id);
	params.push("new_owner="+new_owner);
	
	$('#button-owner_button').attr('disabled','disabled');
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$('#button-owner_button').removeAttr('disabled');
			show_event_dialog(event_id, $('#hidden-group_rep').val(), 'actions', data);
		}
	});	
	
	return false;
}

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
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: true,
		timeout: 10000,
		dataType: 'html',
		success: function (data) {
			$('#button-comment_button').removeAttr('disabled');
			show_event_dialog(event_id, $('#hidden-group_rep').val(), 'comments', data);
		}
	});	
	
	return false;
}
