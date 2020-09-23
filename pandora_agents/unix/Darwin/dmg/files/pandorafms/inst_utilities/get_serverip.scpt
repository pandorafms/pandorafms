#!/usr/bin/osascript

set first_time to 1
set text_display to "Please set your Pandora FMS IP address:"

repeat
	if (first_time = 1) then
		set text_display to "Please set your Pandora FMS IP address:"
	end if
	set my_serverip to display dialog ¬
		text_display with title ¬
		"Pandora FMS Server address" default answer "localhost" ¬
		buttons {"Continue"} ¬
		default button "Continue"
	if ((text returned of my_serverip) = "") then
		set first_time to 0
	else
		exit repeat
	end if
end repeat

return (text returned of my_serverip)
