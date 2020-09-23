#!/usr/bin/osascript

set first_time to 1
set text_display to "Please set your Pandora FMS IP address:"

repeat
	if (first_time = 1) then
		set text_display to "You can set a specific group for this agent (must exist in Pandora):"
	end if
	set my_group to display dialog ¬
		text_display with title ¬
		"Target group" default answer "Servers" ¬
		buttons {"Continue"} ¬
		default button "Continue"
	if ((text returned of my_group) = "") then
		set first_time to 0
	else
		exit repeat
	end if
end repeat

return (text returned of my_group)