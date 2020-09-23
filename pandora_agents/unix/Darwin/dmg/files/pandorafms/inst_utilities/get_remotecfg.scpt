#!/usr/bin/osascript

set first_time to 1
set text_display to "Enable remote config for this agent?"

repeat
	if (first_time = 1) then
		set text_display to "Enable remote config for this agent? (Enterprise only)"
	end if
	set my_remotecfg to display dialog ¬
		text_display with title ¬
		"Remote config" ¬
		buttons {"No", "Yes"} ¬
		default button "Yes"
	if (button returned of my_remotecfg) is "Yes" then
	 	set remote_config to "1"
	else
		set remote_config to "0"
	end if
	exit repeat
end repeat

return (remote_config)