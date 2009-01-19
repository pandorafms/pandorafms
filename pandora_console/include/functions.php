<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require_once ('functions_html.php');
require_once ('functions_ui.php');

define ('ENTERPRISE_NOT_HOOK', -1);

/**
 * Prints a help tip icon.
 * 
 * @param string $help_id Id of the help article
 * @param bool $return Whether to return or output the result
 * 
 * @return string The help tip
 */
function pandora_help ($help_id, $return = false) {
	global $config;
	$output = '&nbsp;<img class="img_help" src="images/help.png" onClick="pandora_help(\''.$help_id.'\')">';
	if ($return)
		return $output;
	echo $output;
}

/** 
 * Cleans a string by encoding to UTF-8 and replacing the HTML
 * entities. UTF-8 is necessary for foreign chars like asian 
 * and our databases are (or should be) UTF-8
 * 
 * @param mixed String or array of strings to be cleaned.
 * 
 * @return mixed The cleaned string or array.
 */
function safe_input ($value) {
	if (is_numeric ($value))
		return $value;
	if (is_array ($value)) {
		array_walk ($value, 'safe_input');
		return $value;
	}
	return htmlentities (utf8_encode ($value), ENT_QUOTES, "UTF-8"); 
}

/**
 * Cleans an object or an array and casts all values as integers
 *
 * @param mixed $value String or array of strings to be cleaned
 * @param int $min If value is smaller than min it will return false
 * @param int $max if value is larger than max it will return false
 *
 * @return mixed The cleaned string. If an array was passed, the invalid values 
 * will be removed
 */
function safe_int ($value, $min = false, $max = false) {
	if (is_array ($value)) {
		foreach ($value as $key => $check) {
			$check = safe_int ($check, $min, $max);
			if ($check !== false) {
				$value[$key] = $check;
			} else {
				unset ($value[$key]);
			}
		}
	} else {
		$value = (int) $value; //Cast as integer
		if (($min !== false && $value < $min) || ($max !== false && $value > $max)) {
			//If it's smaller than min or larger than max return false
			return false;
		}
	}
	return $value;
}


/** 
 * It prints a variable value and a message.
 * TODO: Expand this to handle mixed variables
 * 
 * @param mixed variable to be displayed
 * @param string Message to be displayed
 *
 * @return string Variable with message and Pandora DEBUG identifier
 */
function pandora_debug ($var, $msg = '') { 
	echo "[Pandora DEBUG (".$var."): (".$msg.")<br />";
} 

/** 
 * @deprecated Use get_parameter or safe_input functions
 * 
 * @param string String to be cleaned
 * 
 * @return string Cleaned string
 */
function salida_limpia ($string) {
	$quote_style = ENT_QUOTES;
	static $trans;
	if (! isset ($trans)) {
		$trans = get_html_translation_table (HTML_ENTITIES, $quote_style);
		foreach ($trans as $key => $value)
			$trans[$key] = '&#'.ord($key).';';
		// dont translate the '&' in case it is part of &xxx;
		$trans[chr(38)] = '&';
	}
	// after the initial translation, _do_ map standalone "&" into "&#38;"
	return preg_replace ("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&#38;",
			strtr ($string, $trans));
}

/** 
 * Cleans a string of special characters (|,@,$,%,/,\,=,?,*,&,#)
 * Useful for filenames and graphs
 * 
 * @param string String to be cleaned
 * 
 * @return string Special characters cleaned.
 */
function output_clean_strict ($string) {
	return preg_replace ('/[\|\@\$\%\/\(\)\=\?\*\&\#]/', '', $string);
}


/** 
 * @deprecated use safe_input and get_parameter functions. Keep temporarily for compatibility.
 */
function entrada_limpia ($string) {
	return safe_input ($string);
}

/** 
 * Performs an extra clean to a string removing all but alphanumerical 
 * characters _ and / The string is also stripped to 125 characters from after ://
 * It's useful on sec and sec2, to avoid the use of malicious parameters.
 * 
 * TODO: Make this multibyte safe (I don't know if there is an attack vector there)
 *
 * @param string String to clean
 * 
 * @return string Cleaned string
 */
function safe_url_extraclean ($string) {
	/* Clean "://" from the strings
	 See: http://seclists.org/lists/incidents/2004/Jul/0034.html
	*/
	$pos = strpos ($string, "://");
	if ($pos != 0) {
		//Strip the string from (protocol[://] to protocol[://] + 125 chars)
		$string = substr ($string, $pos + 3, $pos + 128);
	} else {
		$string = substr ($string, 0, 125);
	}
	/* Strip the string to 125 characters */
	return preg_replace ('/[^a-z0-9_\/]/i', '', $string);
}

/** 
 * Add a help link to show help in a popup window.
 * 
 * TODO: Get this merged with the other help function(s)
 *
 * @param string $help_id Help id to be shown when clicking.
 * @param bool $return Whether to print this (false) or return (true)
 * 
 * @return string Link with the popup.
 */
function popup_help ($help_id, $return = false) {
	$output = "&nbsp;<a href='javascript:help_popup(".$help_id.")'>[H]</a>";
	if ($return)
		return $output;
	echo $output;
}

/** 
 * Prints a no permission generic error message.
 */
function no_permission () {
	require("config.php");
	echo "<h3 class='error'>".__('You don\'t have access')."</h3>";
	echo "<img src='images/noaccess.png' alt='No access' width='120'><br><br>";
	echo "<table width=550>";
	echo "<tr><td>";
	echo __('You don\'t have enough permission to access this resource');
	echo "</table>";
	echo "<tr><td><td><td><td>";
	include "general/footer.php";
	exit;
}

/** 
 * Prints a generic error message for some unhandled error and exits
 * 
 * TODO: Clean this up so it doesn't use table and insert erroneous td
 * 
 * @param string $error Aditional error string to be shown. Blank by default
 */
function unmanaged_error ($error = "") {
	require_once ("config.php");
	echo "<h3 class='error'>".__('Unmanaged error')."</h3>";
	echo "<img src='images/error.png' alt='error'><br><br>";
	echo "<table width=550>";
	echo "<tr><td>";
	echo __('Unmanaged error');
	echo "<tr><td>";
	echo $error;
	echo "</table>";
	echo "<tr><td><td><td><td>";
	include "general/footer.php";
	exit;
}

/** 
 * List files in a directory in the local path.
 * 
 * @param string $directory Local path.
 * @param string $stringSearch String to match the values.
 * @param string $searchHandler Pattern of files to match.
 * @param bool $return Whether to print or return the list.
 * 
 * @return string he list of files if $return parameter is true.
 */
function list_files ($directory, $stringSearch, $searchHandler, $return = false) {
	$errorHandler = false;
	$result = array ();
	if (! $directoryHandler = @opendir ($directory)) {
		echo ("<pre>\nerror: directory \"$directory\" doesn't exist!\n</pre>\n");
		return $errorHandler = true;
	}
	if ($searchHandler == 0) {
		while (false !== ($fileName = @readdir ($directoryHandler))) {
			$result[$fileName] = $fileName;
		}
	}
	if ($searchHandler == 1) {
		while(false !== ($fileName = @readdir ($directoryHandler))) {
			if(@substr_count ($fileName, $stringSearch) > 0) {
				$result[$fileName] = $fileName;
			}
		}
	}
	if (($errorHandler == true) &&  (@count ($result) === 0)) {
		echo ("<pre>\nerror: no filetype \"$fileExtension\" found!\n</pre>\n");
	} else {
		asort ($result);
		if ($return === false) {
			echo ("<pre>\n");
			print_r ($result);
			echo ("</pre>\n");
		}
		return $result;
	}
}

/** 
 * Prints a pagination menu to browse into a collection of data.
 * 
 * @param int $count Number of elements in the collection.
 * @param string $url URL of the pagination links. It must include all form values as GET form.
 * @param int $offset Current offset for the pagination
 * @param int $pagination Current pagination size. If a user requests a larger pagination than config["block_size"]
 * @param bool $return Whether to return or print this 
 *
 * @return string The pagination div or nothing if no pagination needs to be done
 */
function pagination ($count, $url, $offset, $pagination = 0, $return = false) {
	global $config;
	
	if (empty ($pagination)) {
		$pagination = $config["block_size"];
	}
	
	/* 	URL passed render links with some parameter
			&offset - Offset records passed to next page
			&counter - Number of items to be blocked 
		Pagination needs $url to build the base URL to render links, its a base url, like 
		" http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
	*/
	$block_limit = 15; // Visualize only $block_limit blocks
	if ($count <= $pagination) {
		return false;
	}
	// If exists more registers than I can put in a page, calculate index markers
	$index_counter = ceil($count/$pagination); // Number of blocks of block_size with data
	$index_page = ceil($offset/$pagination)-(ceil($block_limit/2)); // block to begin to show data;
	if ($index_page < 0)
		$index_page = 0;

	// This calculate index_limit, block limit for this search.
	if (($index_page + $block_limit) > $index_counter)
		$index_limit = $index_counter;
	else
		$index_limit = $index_page + $block_limit;

	// This calculate if there are more blocks than visible (more than $block_limit blocks)
	if ($index_counter > $block_limit )
		$paginacion_maxima = 1; // If maximum blocks ($block_limit), show only 10 and "...."
	else
		$paginacion_maxima = 0;

	// This setup first block of query
	if ( $paginacion_maxima == 1)
		if ($index_page == 0)
			$inicio_pag = 0;
		else
			$inicio_pag = $index_page;
	else
		$inicio_pag = 0;

	$output = '<div>';
	// Show GOTO FIRST button
	$output .= '<a href="'.$url.'&offset=0"><img src="images/control_start_blue.png" class="bot" /></a>&nbsp;';
	// Show PREVIOUS button
	if ($index_page > 0){
		$index_page_prev= ($index_page-(floor($block_limit/2)))*$pagination;
		if ($index_page_prev < 0)
			$index_page_prev = 0;
		$output .= '<a href="'.$url.'&offset='.$index_page_prev.'"><img src="images/control_rewind_blue.png" class="bot" /></a>';
	}
	$output .= "&nbsp;&nbsp;";
	// Draw blocks markers
	// $i stores number of page
	for ($i = $inicio_pag; $i < $index_limit; $i++) {
		$inicio_bloque = ($i * $pagination);
		$final_bloque = $inicio_bloque + $pagination;
		if ($final_bloque > $count){ // if upper limit is beyond max, this shouldnt be possible !
			$final_bloque = ($i-1) * $pagination + $count-(($i-1) * $pagination);
		}
		$output .= "<span>";
			
		$inicio_bloque_fake = $inicio_bloque + 1;
		// To Calculate last block (doesnt end with round data,
		// it must be shown if not round to block limit)
		$output .= '<a href="'.$url.'&offset='.$inicio_bloque.'">';
		if ($inicio_bloque == $offset) {
			$output .= "<b>[ $i ]</b>";
		} else {
			$output .= "[ $i ]";
		}
		$output .= '</a></span>';
	}
	$output .= "&nbsp;&nbsp;";
	// Show NEXT PAGE (fast forward)
	// Index_counter stores max of blocks
	if (($paginacion_maxima == 1) AND (($index_counter - $i) > 0)) {
		$prox_bloque = ($i + ceil ($block_limit / 2)) * $pagination;
		if ($prox_bloque > $count)
			$prox_bloque = ($count -1) - $pagination;
		$output .= '<a href="'.$url.'&offset='.$prox_bloque.'"><img class="bot" src="images/control_fastforward_blue.png" /></a>';
		$i = $index_counter;
	}
	// if exists more registers than i can put in a page (defined by $block_size config parameter)
	// get offset for index calculation
	// Draw "last" block link, ajust for last block will be the same
	// as painted in last block (last integer block).	
	if (($count - $pagination) > 0){
		$myoffset = floor(($count-1) / $pagination) * $pagination;
		$output .= '<a href="'.$url.'&offset='.$myoffset.'"><img class="bot" src="images/control_end_blue.png" /></a>';
	}
	// End div and layout
	$output .= "</div>";
	if ($return === false) {
		echo $output;
	}
	return $output;
}

/** 
 * Format a number with decimals and thousands separator.
 *
 * If the number is zero or it's integer value, no decimals are
 * shown. Otherwise, the number of decimals are given in the call.
 * 
 * @param float $number Number to be rendered
 * @param int $decimals numbers after comma to be shown. Default value: 1
 * 
 * @return string A formatted number for use in output
 */
function format_numeric ($number, $decimals = 1) {
	//Translate to float in case there are characters in the string so
	// fmod doesn't throw a notice
	$number = (float) $number;
	
	if ($number == 0)
		return 0;
	
	// Translators: This is separator of decimal point
	$dec_point = __(".");
	// Translators: This is separator of decimal point
	$thousands_sep = __(",");
	
	/* If has decimals */
	if (fmod ($number, 1) > 0)
		return number_format ($number, $decimals, $dec_point, $thousands_sep);
	return number_format ($number, 0, $dec_point, $thousands_sep);
}

/** 
 * Render numeric data for a graph. It adds magnitude suffix to the number 
 * (M for millions, K for thousands...) base-10
 *
 * TODO: base-2 multiplication
 * 
 * @param float $number Number to be rendered
 * @param int $decimals Numbers after comma. Default value: 1
 * @param dec_point Decimal separator character. Default value: .
 * @param thousands_sep Thousands separator character. Default value: ,
 * 
 * @return string A string with the number and the multiplier
 */
function format_for_graph ($number , $decimals = 1, $dec_point = ".", $thousands_sep = ",") {
	$shorts = array("","K","M","G","T","P");
    $pos = 0;
    while ($number>=1000) { //as long as the number can be divided by 1000
        $pos++; //Position in array starting with 0
        $number = $number/1000;
    }
	
	return format_numeric ($number, $decimals). $shorts[$pos]; //This will actually do the rounding and the decimals
}

/** 
 * INTERNAL: Use print_timestamp for output Get a human readable string of 
 * the difference between current time and given timestamp.
 * 
 * TODO: Make sense out of all these time functions and stick with 2 or 3
 *
 * @param int $timestamp Unixtimestamp to compare with current time.
 * 
 * @return string A human readable string of the diference between current
 * time and a given timestamp.
 */
function human_time_comparation ($timestamp) {
	global $config;
	
	if (!is_numeric ($timestamp)) {
		$timestamp = strtotime ($timestamp);
	}
	
	$seconds = get_system_time () - $timestamp;
	
	return human_time_description_raw ($seconds);
}

/**
 * This function gets the time from either system or sql based on preference and returns it
 *
 * @return int Unix timestamp
 */
function get_system_time () {
	global $config;
	
	if ($config["timesource"] = "sql") {
		$time = get_db_sql ("SELECT UNIX_TIMESTAMP()");
		if (empty ($time)) {
			return time ();
		}
		return $time;
	} else {
		return time ();
	}
}

/** 
 * INTERNAL (use print_timestamp for output): Transform an amount of time in seconds into a human readable
 * strings of minutes, hours or days.
 * 
 * @param int $seconds Seconds elapsed time
 * 
 * @return string A human readable translation of minutes.
 */
function human_time_description_raw ($seconds) {
	if (empty ($seconds)) {
		return __('Never');
	}
	
	if ($seconds < 60)
		return format_numeric ($seconds, 0)." ".__('seconds');
	
	if ($seconds < 3600) {
		$minutes = format_numeric ($seconds / 60, 0);
		$seconds = format_numeric ($seconds % 60, 0);
		if ($seconds == 0)
			return $minutes.' '.__('minutes');
		$seconds = sprintf ("%02d", $seconds);
		return $minutes.':'.$seconds.' '.__('minutes');
	}
	
	if ($seconds < 86400)
		return format_numeric ($seconds / 3600, 0)." ".__('hours');
	
	if ($seconds < 2592000)
		return format_numeric ($seconds / 86400, 0)." ".__('days');
	
	if ($seconds < 15552000)
		return format_numeric ($seconds / 2592000, 0)." ".__('months');
	
	return "+6 ".__('months');
}

/** 
 * @deprecated Use print_timestamp for output.
 */
function human_time_description ($period) {
	return human_time_description_raw ($period); //human_time_description_raw does the same but by calculating instead of a switch
}

/** 
 * @deprecated Get current time minus some seconds. (Do your calculations yourself on unix timestamps)
 * 
 * @param int $seconds Seconds to substract from current time.
 * 
 * @return int The current time minus the seconds given.
 */
function human_date_relative ($seconds) {
	$ahora=date("Y/m/d H:i:s");
	$ahora_s = date("U");
	$ayer = date ("Y/m/d H:i:s", $ahora_s - $seconds);
	return $ayer;
}

/** 
 * @deprecated Use print_timestamp instead
 */
function render_time ($lapse) {
	$myhour = intval (($lapse*30) / 60);
	if ($myhour == 0)
		$output = "00";
	else
		$output = $myhour;
	$output .= ":";
	$mymin = fmod ($lapse * 30, 60);
	if ($mymin == 0)
		$output .= "00";
	else
		$output .= $mymin;
	return $output;
}

/** 
 * Get a parameter from a request.
 *
 * It checks first on post request, if there were nothing defined, it
 * would return get request
 * 
 * @param string $name key of the parameter in the $_POST or $_GET array
 * @param mixed $default default value if the key wasn't found
 * 
 * @return mixed Whatever was in that parameter, cleaned however 
 */
function get_parameter ($name, $default = '') {
	// POST has precedence
	if (isset($_POST[$name]))
		return get_parameter_post ($name, $default);

	if (isset($_GET[$name]))
		return get_parameter_get ($name, $default);

	return $default;
}

/** 
 * Get a parameter from a get request.
 *
 * @param string $name key of the parameter in the $_GET array
 * @param mixed $default default value if the key wasn't found
 * 
 * @return mixed Whatever was in that parameter, cleaned however 
 */
function get_parameter_get ($name, $default = "") {
	if ((isset ($_GET[$name])) && ($_GET[$name] != ""))
		return safe_input ($_GET[$name]);

	return $default;
}

/** 
 * Get a parameter from a post request.
 *
 * @param string $name key of the parameter in the $_POST array
 * @param mixed $default default value if the key wasn't found
 * 
 * @return mixed Whatever was in that parameter, cleaned however 
 */
function get_parameter_post ($name, $default = "") {
	if ((isset ($_POST[$name])) && ($_POST[$name] != ""))
		return safe_input ($_POST[$name]);

	return $default;
}

/** 
 * Get name of a priority value.
 * 
 * @param int $priority Priority value
 * 
 * @return string Name of given priority
 */
function get_alert_priority ($priority = 0) {
	global $config;
	switch ($priority) {
	case 0: 
		return __('Maintenance');
		break;
	case 1:
		return __('Informational');
		break;
	case 2:
		return __('Normal');
		break;
	case 3:
		return __('Warning');
		break;
	case 4:
		return __('Critical');
		break;
	}
	return '';
}

/** 
 * Gets a translated string of names of days based on the boolean properties of it's input ($row["monday"] = (bool) 1 will output Mon) 
 * 
 * @param  array $row The array of boolean values to check. They should have monday -> sunday in boolean
 * 
 * @return string Translated names of days
 */
function get_alert_days ($row) {
	global $config;
	$days_output = "";

	$check = $row["monday"] + $row["tuesday"] + $row["wednesday"] + $row["thursday"] + $row["friday"] + $row["saturday"] + $row["sunday"];
	
	if ($check == 7) {
		return __('All');
	} elseif ($check == 0) {
		return __('None');
	} 
	
	if ($row["monday"] != 0)
		$days_output .= __('Mon')." ";
	if ($row["tuesday"] != 0)
		$days_output .= __('Tue')." ";
	if ($row["wednesday"] != 0)
		$days_output .= __('Wed')." ";
	if ($row["thursday"] != 0)
		$days_output .= __('Thu')." ";
	if ($row["friday"] != 0)
		$days_output .= __('Fri')." ";
	if ($row["saturday"] != 0)
		$days_output .= __('Sat')." ";
	if ($row["sunday"] != 0)
		$days_output .= __('Sun');
	
	if ($check > 1) {	
		return str_replace (" ",", ",$days_output);
	}
	
	return rtrim ($days_output);
}

/** 
 * Gets the alert times values and returns them as string
 * 
 * @param array Array with time_from and time_to in it's keys
 * 
 * @return string A string with the concatenated values
 */
function get_alert_times ($row2) {
	if ($row2["time_from"]){
		$time_from_table = $row2["time_from"];
	} else {
		$time_from_table = __('N/A');
	}
	if ($row2["time_to"]){
		$time_to_table = $row2["time_to"];
	} else {
		$time_to_table = __('N/A');
	}
	if ($time_to_table == $time_from_table)
		return __('N/A');
		
	return substr ($time_from_table, 0, 5)." - ".substr ($time_to_table, 0, 5);
}

/**
 * Get report types in an array.
 * 
 * @return array An array with all the possible reports in Pandora where the array index is the report id.
 */
function get_report_types () {
	$types = array ();
	$types['simple_graph'] = __('Simple graph');
	$types['custom_graph'] = __('Custom graph');
	$types['SLA'] = __('S.L.A');
	$types['event_report'] = __('Event report');
	$types['alert_report'] = __('Alert report');
	$types['monitor_report'] = __('Monitor report');
	$types['avg_value'] = __('Avg. Value');
	$types['max_value'] = __('Max. Value');
	$types['min_value'] = __('Min. Value');
	$types['sumatory'] = __('Sumatory');
	$types['general_group_report'] = __('General group report');
	$types['monitor_health'] = __('Monitor health');
	$types['agents_detailed'] = __('Agents detailed view');

	return $types;
}

/**
 * Get report type name from type id.
 *
 * @param int $type Type id of the report.
 *
 * @return string Report type name.
 */
function get_report_name ($type) {
	$types = get_report_types ();
	if (! isset ($types[$type]))
		return __('Unknown');
	return $types[$type];
}

/**
 * Get report type data source from type id.
 *
 * TODO: Better documentation as to what this function does
 *
 * @param mixed $type Type id or type name of the report.
 *
 * @return string Report type name.
 */
function get_report_type_data_source ($type) {
	switch ($type) {
	case 1:
	case 'simple_graph':
	case 6: 
	case 'monitor_report':
	case 7:
	case 'avg_value':
	case 8:
	case 'max_value':
	case 9:
	case 'min_value':
	case 10:
	case 'sumatory':
		return 'module';
	case 2:
	case 'custom_graph':
		return 'custom-graph';
	case 3:
	case 'SLA':
	case 4:
	case 'event_report':
	case 5:
	case 'alert_report':
	case 11:
	case 'general_group_report':
	case 12:
	case 'monitor_health':
	case 13:
	case 'agents_detailed':
		return 'agent-group';
	}
	return 'unknown';
}

/**
 * Checks if a module is of type "data"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool True if the module is of type "data"
 */
function is_module_data ($module_name) {
	$result = ereg ("^(.*_data)$", $module_name);
	if ($result === false)
		return false;
	return true;
}

/**
 * Checks if a module is of type "proc"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool true if the module is of type "proc"
 */
function is_module_proc ($module_name) {
	$result = ereg ('^(.*_proc)$', $module_name);
	if ($result === false)
		return false;
	return true;
}

/**
 * Checks if a module is of type "inc"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool true if the module is of type "inc"
 */
function is_module_inc ($module_name) {
	$result = ereg ('^(.*_inc)$', $module_name);
	if ($result === false)
		return false;
	return true;
}

/**
 * Checks if a module is of type "string"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool true if the module is of type "string"
 */
function is_module_data_string ($module_name) {
	$result = ereg ('^(.*string)$', $module_name);
	if ($result === false)
		return false;
	return true;
}

/**
 * Get all event types in an array
 *
 * @return array module_name Module name to check.
 */
function get_event_types () {
	$types = array ();
	$types['unknown'] = __('Unknown');
	$types['monitor_up'] = __('Monitor up');
	$types['monitor_down'] = __('Monitor down');
	$types['alert_fired'] = __('Alert fired');
	$types['alert_recovered'] = __('Alert recovered');
	$types['alert_ceased'] = __('Alert ceased');
	$types['alert_manual_validation'] = __('Alert manual validation');
	$types['recon_host_detected'] = __('Recon host detected');
	$types['system'] = __('System');
	$types['error'] = __('Error');
	
	return $types;
}

/**
 * Get an array with all the priorities.
 *
 * @return array An array with all the priorities.
 */
function get_priorities () {
	$priorities = array ();
	$priorities[0] = __('Maintenance');
	$priorities[1] = __('Informational');
	$priorities[2] = __('Normal');
	$priorities[3] = __('Warning');
	$priorities[4] = __('Critical');
	
	return $priorities;
}

/**
 * Get priority name from priority value.
 *
 * @param int $priority value (integer) as stored eg. in database.
 *
 * @return string priority string.
 */
function get_priority_name ($priority) {
	switch ($priority) {
	case 0: 
		return __('Maintenance');
	case 1: 
		return __('Informational');
	case 2: 
		return __('Normal');
	case 3: 
		return __('Warning');
	case 4: 
		return __('Critical');
	default: 
		return __('All');
	}
}

/**
 * Get priority class (CSS class) from priority value.
 *
 * @param int priority value (integer) as stored eg. in database.
 *
 * @return string CSS priority class.
 */
function get_priority_class ($priority) {
	switch ($priority) {
	case 0: 
		return "datos_blue";
	case 1: 
		return "datos_grey";
	case 2:
		return "datos_green";
	case 3: 
		return "datos_yellow";
	case 4: 
		return "datos_red";
	default: 
		return "datos_grey";
	}
}
	
/**
 * TODO: Document enterprise functions
 */
function enterprise_hook ($function_name, $parameters = false) {
	if (function_exists ($function_name)) {
		if (!is_array ($parameters))
			return call_user_func ($function_name);
		return call_user_func_array ($function_name, $parameters);
	}
	return ENTERPRISE_NOT_HOOK;
}

/**
 * TODO: Document enterprise functions
 */
function enterprise_include ($filename) {
	global $config;
	// Load enterprise extensions
	$filepath = realpath ($config["homedir"].'/'.ENTERPRISE_DIR.'/'.$filename);
	if ($filepath === false)
		return ENTERPRISE_NOT_HOOK;
	if (file_exists ($filepath)) {
		include ($filepath);
		return true;
	}
	return ENTERPRISE_NOT_HOOK;
}


//These are wrapper functions for PHP. Don't document them
if (!function_exists ("mb_strtoupper")) {
	//Multibyte not loaded - use wrapper functions
	//You should really load multibyte especially for foreign charsets
	
	/**
	 * @ignore
	 */
	function mb_strtoupper ($string, $encoding = false) {
			return strtoupper ($string);
		}

	/**
	 * @ignore
	 */		
	function mb_strtolower ($string, $encoding = false) {
			return strtoupper ($string);
	}
	
	/**
	 * @ignore
	 */
	function mb_substr ($string, $start, $length, $encoding = false) {
		return substr ($string, $start, $length);
	}
	
	/**
	 * @ignore
	 */
	function mb_strlen ($string, $encoding = false) {
		return strlen ($string);
	}
}
?>
