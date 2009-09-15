var original_command = "";

function parse_alert_command (command) {
	value = $("#text-field1").attr ("value");
	re = /_FIELD1_/gi;
	command = command.replace (re, "\""+value+"\"");
	
	value = $("#text-field2").attr ("value");
	re = /_FIELD2_/gi;
	command = command.replace (re, "\""+value+"\"");
	
	value = $("#textarea_field3").val();
	re = /_FIELD3_/gi;
	command = command.replace (re, "\""+value+"\"");
	
	return command;
}

function render_command_preview () {
	$("#textarea_command_preview").text (parse_alert_command (original_command));
}
