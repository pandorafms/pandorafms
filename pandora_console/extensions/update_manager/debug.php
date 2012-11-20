<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


////////////////////////////////////
///////PLEASE DONT TOUCH
global $debug_update_manager;
global $debug_mode_output;
////////////////////////////////////
////////////////////////////////////



////////////////////////////////////
///////PLEASE ONLY CHANGE THESE VALUES
$debug_update_manager = 0;
//There are "notice_php" and "logDebug".
$debug_mode_output = "notice_php";
////////////////////////////////////
////////////////////////////////////











////////////////////////////////////
///////PLEASE DONT TOUCH
function print_debug_message_trace($message) {
	global $debug_update_manager;
	global $debug_mode_output;
	
	if ($debug_update_manager) {
		switch ($debug_mode_output) {
			case "notice_php":
				trigger_error("PRINT DEBUG TRACE",
					E_USER_NOTICE);
				
				trigger_error($message, E_USER_NOTICE);
				
				$backtrace = json_encode(debug_backtrace());
				$backtrace_chunks = str_split($backtrace, 1024);
				
				trigger_error("INIT DEBUG BACKTRACE (JSON ENCODE) CHUNKS " .
					count($backtrace_chunks), E_USER_NOTICE);
				foreach ($backtrace_chunks as $chunk) 
					trigger_error($chunk, E_USER_NOTICE);
				trigger_error("END DEBUG BACKTRACE (JSON ENCODE)",
					E_USER_NOTICE);
				break;
			case "logDebug":
				html_debug_print($message, true);
				html_debug_print(debug_backtrace(), true);
				break;
		}
	}
}
////////////////////////////////////
////////////////////////////////////
?>
