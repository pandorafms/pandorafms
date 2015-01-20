var original_command = "";

function parse_alert_command (command, classs) {
	if (classs == 'recovery') {
		classs = 'fields_recovery';
	}
	else {
		classs = 'fields';
	}
	
	var nfield = 1;
	$('.' + classs).each(function() {
		// Only render values different from ''
		if ($(this).val() == '') {
			nfield++;
			return;
		}
		var field = '_field' + nfield + '_';
		nfield++;
		var regex = new RegExp(field,"gi");
		command = command.replace (regex, $(this).val());
	});
	
	return command;
}

function render_command_preview (original_command) {
	$("#textarea_command_preview").text (parse_alert_command (original_command, ''));
}

function render_command_recovery_preview (original_command) {
	$("#textarea_command_recovery_preview").text (parse_alert_command (original_command, 'recovery'));
}

function render_command_description (command_description) {
	if (command_description != '') {
		command_description = '<br>'+command_description;
	}
	$("#command_description").html(command_description);
}
