function check_new_chats_icon(id_icon) {
	if (new_chat) {
		$("#" + id_icon).pulsate ();
	}
}

function check_new_chats_icon_ajax(id_icon) {
	var exit = false;
	
	url_chunks = location.href.split('&');
	$.each(url_chunks, function(key, chunk) {
		if (chunk == 'sec2=operation/users/webchat')
			exit = true;
			return;
	});
	
	if (exit) {
		return;
	}
	
	old = global_counter_chat;
	get_last_global_counter();
	
	if (old < global_counter_chat) {
		$("#" + id_icon).pulsate ();
	}
	
	setTimeout("check_new_chats_icon(\"" + id_icon + "\")", 5000);
}

function get_last_global_counter() {
	var parameters = {};
	parameters['page'] = "operation/users/webchat";
	parameters['get_last_global_counter'] = 1;
	
	$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: parameters,
		dataType: "json",
		async: false,
		success: function(data) {
			if (data['correct'] == 1) {
				console.log(global_counter_chat+" < "+data['global_counter']);
				global_counter_chat = data['global_counter'];
			}
		}
	});
}
