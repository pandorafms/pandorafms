<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

include_once($config['homedir'] . "/include/functions_db.php");

// Update the execution interval of the given module
function cron_update_module_interval ($module_id, $cron) {
	
	// Check for a valid cron
	if ($cron == '' || $cron == '* * * * *') {
		return;
	}
	
	return db_process_sql ('UPDATE tagente_estado SET current_interval = ' . cron_next_execution ($cron) . ' WHERE id_agente_modulo = ' . (int) $module_id);
}


// Get the number of seconds left to the next execution of the given cron entry.
function cron_next_execution ($cron) {
	
	// Get day of the week and month from cron config
	list ($minute, $hour, $mday, $month, $wday) = explode (" ", $cron);
	
	// Get current time
	$cur_time = time();
	
	// Any day of the way
	if ($wday == '*') {
		$nex_time = cron_next_execution_date ($cron,  $cur_time);
		return $nex_time - $cur_time;
	}
	
	// A specific day of the week
	$count = 0;
	$nex_time = $cur_time;
	do {
		$nex_time = cron_next_execution_date ($cron, $nex_time);
		$nex_time_wd = $nex_time;
		list ($nex_mon, $nex_wday) = explode (" ", date ("m w", $nex_time_wd));
		
		do {
			// Check the day of the week
			if ($nex_wday == $wday) {
				return $nex_time_wd - $cur_time;
			}
			
			// Move to the next day of the month
			$nex_time_wd += 86400;
			list ($nex_mon_wd, $nex_wday) = explode (" ", date ("m w", $nex_time_wd));
		}
		while ($mday == '*' && $nex_mon_wd == $nex_mon);
		
		$count++;
	}
	while ($count < 60);
	
	// Something went wrong, default to 5 minutes
	return 300;
}

// Get the next execution date for the given cron entry in seconds since epoch.
function cron_next_execution_date ($cron, $cur_time = false) {
	
	// Get cron configuration
	list ($min, $hour, $mday, $mon, $wday) = explode (" ", $cron);
	
	// Months start from 0
	if ($mon != '*') {
		$mon -= 1;
	}
	
	// Get current time
	if ($cur_time === false) {
		$cur_time = time();
	}
	list ($cur_min, $cur_hour, $cur_mday, $cur_mon, $cur_year) = explode (" ", date ("i H d m Y", $cur_time));
	
	// Get first next date candidate from cron configuration
	$nex_min = $min;
	$nex_hour = $hour;
	$nex_mday = $mday;
	$nex_mon = $mon;
	$nex_year = $cur_year;
	
	// Replace wildcards
	if ($min == '*') {
		if ($hour != '*' || $mday != '*' || $wday != '*' || $mon != '*') {
			$nex_min = 0;
		}
		else {
			$nex_min = $cur_min;
		}
	}
	if ($hour == '*') {
		if ($mday != '*' || $wday != '*' ||$mon != '*') {
			$nex_hour = 0;
		}
		else {
			$nex_hour = $cur_hour;
		}
	}
	if ($mday == '*') {
		if ($mon != '*') {
			$nex_mday = 1;
		}
		else {
			$nex_mday = $cur_mday;
		}
	}
	if ($mon == '*') {
		$nex_mon = $cur_mon;
	}
	
	// Find the next execution date
	$count = 0;
	do {
		$next_time = mktime($nex_hour, $nex_min, 0, $nex_mon, $nex_mday, $nex_year);
		if ($next_time > $cur_time) {
			return $next_time;
		}
		if ($min == '*' && $hour == '*' && $wday == '*' && $mday == '*' && $mon == '*') {
			list ($nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year) = explode (" ", date ("i H d m Y", $next_time + 60));
		}
		else if ($hour == '*' && $wday == '*' && $mday == '*' && $mon == '*') {
	 		list ($nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year) = explode (" ", date ("i H d m Y", $next_time + 3600));
		}
		else if ($mday == '*' && $mon == '*') {
	 		list ($nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year) = explode (" ", date ("i H d m Y", $next_time + 86400));
		}
		else if ($mon == '*') {
			$nex_mon = $nex_mon + 1;
			if ($nex_mon > 11) {
				$nex_mon = 0;
				$nex_year++;
			}
		}
		else {
			$nex_year++;
		}
		$count++;
	}
	while ($count < 86400);
	
	// Something went wrong, default to 5 minutes
	return $cur_time + 300;
}

?>
