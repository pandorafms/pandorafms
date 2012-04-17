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

/**
 * @package Include
 * @subpackage Generic_Functions
 */

/**
 * Include the html and ui functions. 
 */
require_once ('functions_html.php');
require_once ('functions_ui.php');
require_once('functions_io.php');

function check_refererer() {
	global $config;
	
	$referer = '';
	if (isset($_SERVER['HTTP_REFERER'])) {
		$referer = $_SERVER['HTTP_REFERER'];
	}
	
	$url = 'http://';
	if ($config['https']) {
		$url = 'https://';
	}
	//Check if the referer have a port (for example when apache run in other port to 80)
	if (preg_match('/http(s?):\/\/.*:[0-9]*/', $referer) == 1) {
		$url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $config["homeurl"];
	}
	else {
		$url .= $_SERVER['SERVER_NAME'] . $config["homeurl"];
	}
	
	if (strpos($referer, $url) === 0) {
		return true;
	}
	else {
		return false;
	}
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
 * Performs an extra clean to a string removing all but alphanumerical 
 * characters _ and / The string is also stripped to 125 characters from after ://
 * It's useful on sec and sec2, to avoid the use of malicious parameters.
 * 
 * TODO: Make this multibyte safe (I don't know if there is an attack vector there)
 *
 * @param string String to clean
 * @param default_string String that will be returned if invalid characters are found.
 * 
 * @return string Cleaned string
 */
function safe_url_extraclean ($string, $default_string = '') {

	/* Strip the string to 125 characters */
	$string = substr ($string, 0, 125);

	/* Search for unwanted characters */
	if (preg_match ('/[^a-zA-Z0-9_\/\.\-]|(\/\/)|(\.\.)/', $string)) {
		return $default_string;
	}

	return $string;
}

/** 
 * DEPRECATED: This function is not used anywhere. Remove it?
 * (use general/noaccess.php followed by exit instead)
 */
function no_permission () {
	require ("config.php");
	echo "<h3 class='error'>".__('You don\'t have access')."</h3>";
	echo html_print_image('images/noaccess.png', true, array("alt" => 'No access', "width" => '120')) . "<br /><br />";
	echo "<table width=550>";
	echo "<tr><td>";
	echo __('You don\'t have enough permission to access this resource');
	echo "</table>";
	echo "<tr><td><td><td><td>";
	include "general/footer.php";
	exit;
}

/** 
 * DEPRECATED: This function is not used anywhere. Remove it?
 * (use print_error function instead followed by return or exit)
 *
 * @param string $error Aditional error string to be shown. Blank by default
 */
function unmanaged_error ($error = "") {
	require_once ("config.php");
	echo "<h3 class='error'>".__('Unmanaged error')."</h3>";
	echo html_print_image('images/error.png', true, array("alt" => 'error')) . "<br /><br />";
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
	if (($errorHandler == true) && (@count ($result) === 0)) {
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
	$shorts = array ("", "K", "M", "G", "T", "P", "E", "Z", "Y");
	$pos = 0;
	while ($number >= 1000) { //as long as the number can be divided by 1000
		$pos++; //Position in array starting with 0
		$number = $number / 1000;
	}
	
	return format_numeric ($number, $decimals). $shorts[$pos]; //This will actually do the rounding and the decimals
}

/**
 * Rounds an integer to a multiple of 5.
 *
 * Example:
 * <code>
 * echo format_integer_round (18);
 * // Will return 20
 *
 * echo format_integer_round (21);
 * // Will return 25
 *
 * echo format_integer_round (25, 10);
 * // Will return 30
 * </code>
 *
 * @param int Number to be rounded.
 * @param int Rounder number, default value is 5.
 *
 * @param Number rounded to a multiple of rounder
 */
function format_integer_round ($number, $rounder = 5) {
	return (int) ($number / $rounder + 0.5) * $rounder;
}

/** 
 * INTERNAL: Use ui_print_timestamp for output Get a human readable string of 
 * the difference between current time and given timestamp.
 * 
 * TODO: Make sense out of all these time functions and stick with 2 or 3
 *
 * @param int $timestamp Unixtimestamp to compare with current time.
 * @param string $units The type of unit, by default 'large'.
 * 
 * @return string A human readable string of the diference between current
 * time and a given timestamp.
 */
function human_time_comparation ($timestamp, $units = 'large') {
	global $config;
	
	if (!is_numeric ($timestamp)) {
		$timestamp = strtotime ($timestamp);
	}
	
	$seconds = get_system_time () - $timestamp;

	return human_time_description_raw($seconds, false, $units);
}

/**
 * This function gets the time from either system or sql based on preference and returns it
 *
 * @return int Unix timestamp
 */
function get_system_time () {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_get_system_time();
			break;
		case "postgresql":
			return postgresql_get_system_time();
			break;
		case "oracle":
			return oracle_get_system_time();
			break;
	}
}

/**
 * This function provide the user language configuration if is not default, otherwise return the system language
 *
 *  @param int $id_user
 * 
 *  @return string user active language code
 */
function get_user_language ($id_user = false) {
	global $config;

	$quick_language = get_parameter('quick_language_change', 0);

	if($quick_language) {
		$language = get_parameter('language', 0);
		
		if($language === 'default') {
			return $config['language'];
		}

		if($language !== 0) {
			return $language;
		}
	}
	
	if($id_user === false && isset($config['id_user'])) {
		$id_user = $config['id_user'];
	}
	
	if($id_user !== false) {
		$userinfo = get_user_info ($id_user);
		if ($userinfo['language'] != 'default'){
			return $userinfo['language'];
		}
	}
	
	return $config['language'];
}

/**
 * This function get the user language and set it on the system
 */
function set_user_language() {
	global $config;
	global $l10n;

	$l10n = NULL;
	$user_language = get_user_language ();

	if (file_exists ('./include/languages/'.$user_language.'.mo')) {
		$l10n = new gettext_reader (new CachedFileReader ('./include/languages/'.$user_language.'.mo'));
		$l10n->load_tables();
	}
}

/** 
 * INTERNAL (use ui_print_timestamp for output): Transform an amount of time in seconds into a human readable
 * strings of minutes, hours or days.
 * 
 * @param int $seconds Seconds elapsed time
 * @param int $exactly If it's true, return the exactly human time
 * @param string $units The type of unit, by default 'large'.
 * 
 * @return string A human readable translation of minutes.
 */
function human_time_description_raw ($seconds, $exactly = false, $units = 'large') {
	
	switch ($units) {
		case 'large':
			$secondsString = __('seconds');
			$daysString = __('days');
			$monthsString = __('months');
			$yearsString = __('years');
			$minutesString = __('minutes');
			$hoursString = __('hours');
			$nowString = __('Now');
			break;
		case 'tiny':
			$secondsString = __('s');
			$daysString = __('d');
			$monthsString = __('M');
			$yearsString = __('Y');
			$minutesString = __('m');
			$hoursString = __('h');
			$nowString = __('N');
			break;
	}

	if (empty ($seconds)) {
		return $nowString; 
		// slerena 25/03/09
		// Most times $seconds is empty is because last contact is current date
		// Put here "uknown" or N/A or something similar is not a good idea
	}
	
	if ($exactly) {
		$returnDate = '';
		
		$years = floor($seconds / 31104000);
		
		if($years != 0) {
			$seconds = $seconds - ($years * 31104000);
			
			$returnDate .= "$years $yearsString ";
		}
		
		$months = floor($seconds / 2592000);
		
		if($months != 0) {
			$seconds = $seconds - ($months * 2592000);
			
			$returnDate .= "$months $monthsString ";
		}

		$days = floor($seconds / 86400);
		
		if($days != 0) {
			$seconds = $seconds - ($days * 86400);
			
			$returnDate .= "$days $daysString ";
		}
		
		$returnTime = '';

		$hours = floor($seconds / 3600);
		
		if($hours != 0) {
			$seconds = $seconds - ($hours * 3600);
			
			$returnTime .= "$hours $hoursString ";
		}
		
		$mins = floor($seconds / 60);
		
		if($mins != 0) {
			$seconds = $seconds - ($mins * 60);
			
			if($hours == 0) {
				$returnTime .= "$mins $minutesString ";
			}
			else {
				$returnTime = sprintf("%02d",$hours).':'.sprintf("%02d",$mins);
			}
		}
		
		if($seconds != 0) {
			if($hours == 0) {
				$returnTime .= "$seconds $secondsString ";
			}
			else {
				$returnTime = sprintf("%02d",$hours).':'.sprintf("%02d",$mins).':'.sprintf("%02d",$seconds);
			}
		}
		
		$return = ' ';
		
		if($returnDate != '') {
			$return = $returnDate;
		}
		
		if($returnTime != '') {
			$return .= $returnTime;
		}
		
		if($return == ' ') {
			return $nowString; 
		}
		else {
			return $return;
		}
		
	}
	
	if ($seconds < 60)
		return format_numeric ($seconds, 0)." " . $secondsString;
	
	if ($seconds < 3600) {
		$minutes = floor($seconds / 60);
		$seconds = $seconds % 60;
		if ($seconds == 0)
			return $minutes.' ' . $minutesString;
		$seconds = sprintf ("%02d", $seconds);
		return $minutes.':'.$seconds.' ' . $minutesString;
	}
	
	if ($seconds < 86400)
		return format_numeric ($seconds / 3600, 0)." " . $hoursString;
	
	if ($seconds < 2592000)
		return format_numeric ($seconds / 86400, 0) . " " . $daysString;
	
	if ($seconds < 15552000)
		return format_numeric ($seconds / 2592000, 0)." ". $monthsString;
	
	return "+6 " . $monthsString;
}

/** 
 * @deprecated Get current time minus some seconds. (Do your calculations yourself on unix timestamps)
 * 
 * @param int $seconds Seconds to substract from current time.
 * 
 * @return int The current time minus the seconds given.
 */
function human_date_relative ($seconds) {
	$ahora = date("Y/m/d H:i:s");
	$ahora_s = date("U");
	$ayer = date ("Y/m/d H:i:s", $ahora_s - $seconds);
	
	return $ayer;
}

/** 
 * @deprecated Use ui_print_timestamp instead
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
 * Get a parameter from a request between values.
 *
 * It checks first on post request, if there were nothing defined, it
 * would return get request
 * 
 * @param string $name key of the parameter in the $_POST or $_GET array
 * @param array $values The list of values that parameter to be.
 * @param mixed $default default value if the key wasn't found
 * 
 * @return mixed Whatever was in that parameter, cleaned however 
 */
function get_parameterBetweenListValues ($name, $values, $default) {
	$parameter = $default;
	// POST has precedence
	if (isset($_POST[$name]))
		$parameter = get_parameter_post ($name, $default);
	
	if (isset($_GET[$name]))
		$parameter = get_parameter_get ($name, $default);
		
	foreach($values as $value) {
		if ($value == $parameter) {
			return $value;
		}
	}

	return $default;
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
		return io_safe_input ($_GET[$name]);

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
		return io_safe_input ($_POST[$name]);

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
 * Checks if a module is of type "data"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool True if the module is of type "data"
 */
function is_module_data ($module_name) {
	return preg_match ('/\_data$/', $module_name);
}

/**
 * Checks if a module is of type "proc"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool true if the module is of type "proc"
 */
function is_module_proc ($module_name) {
	return preg_match ('/\_proc$/', $module_name);
}

/**
 * Checks if a module is of type "inc"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool true if the module is of type "inc"
 */
function is_module_inc ($module_name) {
	return preg_match ('/\_inc$/', $module_name);
}

/**
 * Checks if a module is of type "string"
 *
 * @param string $module_name Module name to check.
 *
 * @return bool true if the module is of type "string"
 */
function is_module_data_string ($module_name) {
	return preg_match ('/\_string$/', $module_name);
}

/**
 * Checks if a module data is uncompressed according
 * to the module type.
 *
 * @param string module_type Type of the module.
 *
 * @return bool true if the module data is uncompressed.
 */
function is_module_uncompressed ($module_type) {
	if (strstr($module_type, 'async') !== false || strstr($module_type, 'log4x') !== false) {
		return true;
	}
	return false;
}

/**
 * Get all event types in an array
 *
 * @return array module_name Module name to check.
 */
function get_event_types () {
	global $config;
	
	$types = array ();
	
	$types['unknown'] = __('Unknown');

	$types['critical'] = __('Monitor Critical');
	$types['warning'] = __('Monitor Warning');
 	$types['normal'] = __('Monitor Normal');

	$types['alert_fired'] = __('Alert fired');
	$types['alert_recovered'] = __('Alert recovered');
	$types['alert_ceased'] = __('Alert ceased');
	$types['alert_manual_validation'] = __('Alert manual validation');
	$types['recon_host_detected'] = __('Recon host detected');
	$types['system'] = __('System');
	$types['error'] = __('Error');
	$types['configuration_change'] = __('Configuration change ');
	
	if (isset($config['text_char_long'])) {
		foreach ($types as $key => $type) {
			$types[$key] = ui_print_truncate_text($type, $config['text_char_long'], false, true, false);
		}
	}
	
	return $types;
}

/**
 * Get an array with all the priorities.
 *
 * @return array An array with all the priorities.
 */
function get_priorities () {
	global $config;
	
	$priorities = array ();
	$priorities[0] = __('Maintenance');
	$priorities[1] = __('Informational');
	$priorities[2] = __('Normal');
	$priorities[3] = __('Warning');
	$priorities[4] = __('Critical');
	
	if (isset($config['text_char_long'])) {
		foreach ($priorities as $key => $priority) {
			$priorities[$key] = ui_print_truncate_text($priority, $config['text_char_long'], false, true, false);
		}
	}
	
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
		default: 
			return __('All');
			break;
	}
}

/**
 * Get priority class (CSS class) from priority value.
 *
 * @param int priority value (integer) as stored eg. in database.
 *
 * @return string CSS priority class.
 */
function get_priority_class($priority) {
	switch ($priority) {
		case 0: 
			return "datos_blue";
			break;
		case 1: 
			return "datos_grey";
			break;
		case 2:
			return "datos_green";
			break;
		case 3: 
			return "datos_yellow";
			break;
		case 4: 
			return "datos_red";
			break;
		default: 
			return "datos_grey";
			break;
	}
}

/**
 * Check if the enterprise version is installed.
 * 
 * @return boolean If it is installed return true, otherwise return false.
 */
function enterprise_installed() {
	$return = false;
	
	if (defined('PANDORA_ENTERPRISE')) {
		if (PANDORA_ENTERPRISE) {
			$return = true;
		}
	}
	
	return $return;
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
	if (strncmp ($config["homedir"], $filepath, strlen ($config["homedir"])) != 0){
		return ENTERPRISE_NOT_HOOK;
	}
	if (file_exists ($filepath)) {
		include ($filepath);
		return true;
	}
	return ENTERPRISE_NOT_HOOK;
}

function enterprise_include_once ($filename) {
	global $config;
	
	// Load enterprise extensions
	$filepath = realpath ($config["homedir"].'/'.ENTERPRISE_DIR.'/'.$filename);
	
	if ($filepath === false)
		return ENTERPRISE_NOT_HOOK;
	
	if (strncmp ($config["homedir"], $filepath, strlen ($config["homedir"])) != 0)
		return ENTERPRISE_NOT_HOOK;
	
	if (file_exists ($filepath)) {
		require_once ($filepath);
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
	
	/**
	 * @ignore
	 */
	function mb_strimwidth ($string, $start, $length, $trimmarker = false, $encoding = false) {
		return substr ($string, $start, $length);
	}
}

/**
 * Put quotes if magic_quotes protection
 *
 * @param string Text string to be protected with quotes if magic_quotes protection is disabled
 */
function safe_sql_string($string) {
	global $config;

	switch ($config["dbtype"]) {
		case "mysql":
			return mysql_safe_sql_string($string);
			break;
		case "postgresql":
			return postgresql_safe_sql_string($string);
			break;
		case "oracle":
			return oracle_safe_sql_string($string);
			break;
	}
}

/**
 * Checks if current execution is under an AJAX request.
 *
 * This functions checks if an 'AJAX' constant is defined
 *
 * @return bool True if the request was done via AJAX. False otherwise
 */
function is_ajax () {
	return defined ('AJAX');
}

/**
 * Check if a code is an error code
 * 
 * @param int code of an operation. Tipically the id of a module, agent... or a code error
 * 
 * @return bool true if a result code is an error or false otherwise
 */
function is_error($code) {
	if($code !== true AND ($code <= ERR_GENERIC || $code === false)) {
		return true;
	}
	else {
		return false;
	}
}
/**
 * Transform an array of database result into an indexed array.
 *
 * This function is useful when the results of a database needs to be used
 * in a html_print_select() function.
 *
 * @param array Array with the results.
 * @param string Field of each row in the given array that will act as index. False
 * will set all the values.
 * @param string Field of each row in the array that will act as value.
 *
 * @return array An array having the given index as fields and the given values
 * as value.
 */
function index_array ($array, $index = 'id', $value = 'name') {
	$retval = array ();
	
	if (! is_array ($array))
		return $retval;
	
	foreach ($array as $element) {
		if (! isset ($element[$index]))
			continue;
		if ($value === false) {
			$retval[$element[$index]] = $element;
			continue;
		}
		
		if (! isset ($element[$value]))
			continue;
		$retval[$element[$index]] = $element[$value];
	}
	
	return $retval;
}

/**
 * Return a graph type (string) given a module_type
 *
 * This function is useful to determine what kind of graph will be
 * used, depending on the source data type, depending if it's 
 * numeric, boolean or a string type.
 *
 * @param int Id of module type
 * @return string Graph type, as used in stat_win.php (Graphs launcher)
 */

function return_graphtype ($id_module_type){
	switch($id_module_type){
		case 3:
		case 10:
		case 17:
		case 23:
			return "string";
			break;
		case 2:
		case 6:
		case 21:
		case 18:
		case 9:
			return "boolean";
			break;
		case 24:
			return "log4x";
			break;
	}


	return "sparse";
}

/**
 * Translate the key in assoc array to numeric offset.
 * 
 * @param array $array The array to return the offset.
 * @param mixed $key The key to translate to offset.
 * 
 * @return mixed The offset or false is fail.
 */
function array_key_to_offset($array, $key) {
	$offset = array_search($key, array_keys($array));
	
	return $offset;
}

/**
 * Make a snmpwalk and return it.
 * 
 * @param string $ip_target The target address.
 * @param string $snmp_version Version of the snmp: 1,2,2c or 3.
 * @param string $snmp_community.
 * @param string $snmp3_auth_user.
 * @param string $snmp3_security_level.
 * @param string $snmp3_auth_method.
 * @param string $snmp3_auth_pass.
 * @param string $snmp3_privacy_method.
 * @param string $snmp3_privacy_pass.
 * @param integer $quick_print 0 for all details, 1 for only value.
 * 
 * @return array SNMP result.
 */
function get_snmpwalk($ip_target, $snmp_version, $snmp_community = '', $snmp3_auth_user = '',
				$snmp3_security_level = '', $snmp3_auth_method = '', $snmp3_auth_pass = '',
				$snmp3_privacy_method = '', $snmp3_privacy_pass = '', $quick_print = 0, $base_oid = "", $snmp_port = '') {
					
	snmp_set_quick_print ($quick_print);
	
	// Fix for snmp port
	if (!empty($snmp_port)){
		$ip_target = $ip_target.':'.$snmp_port;
	}
	
	switch ($snmp_version) {
		case '3':
			$snmpwalk = @snmp3_real_walk ($ip_target, $snmp3_auth_user,
				$snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
				$snmp3_privacy_method, $snmp3_privacy_pass, $base_oid);
			break;
		case '2':
		case '2c':
			$snmpwalk = @snmp2_real_walk ($ip_target, $snmp_community, $base_oid);
			break;
		case '1':
		default:
			$snmpwalk = @snmprealwalk($ip_target, $snmp_community, $base_oid);	
			break;
	}
	
	return $snmpwalk;
}

/**
 * Copy from:
 * http://stackoverflow.com/questions/1605844/imagettfbbox-returns-wrong-dimensions-when-using-space-characters-inside-text
 */
function calculateTextBox($font_size, $font_angle, $font_file, $text) {
	$box = imagettfbbox($font_size, $font_angle, $font_file, $text);
	
	$min_x = min(array($box[0], $box[2], $box[4], $box[6]));
	$max_x = max(array($box[0], $box[2], $box[4], $box[6]));
	$min_y = min(array($box[1], $box[3], $box[5], $box[7]));
	$max_y = max(array($box[1], $box[3], $box[5], $box[7]));
	
	return array(
		'left' => ($min_x >= -1) ? -abs($min_x + 1) : abs($min_x + 2),
		'top' => abs($min_y),
		'width' => $max_x - $min_x,
		'height' => $max_y - $min_y,
		'box' => $box
	);
}

/**
 * Convert a string to an image
 * 
 * @param string $ip_target The target address.
 * 
 * @return array SNMP result.
 */
function string2image($string, $width, $height, $fontsize = 3, 
	$degrees = '0', $bgcolor = '#FFFFFF', $textcolor = '#000000', 
	$padding_left = 4, $padding_top = 1, $home_url = '') {
	
	global $config;

	$string = str_replace('#','',$string);

	//Set the size of image from the size of text
	if ($width === false) {
		$size = calculateTextBox($fontsize, 0, $config['fontpath'], $string);
		
		$fix_value = 1 * $fontsize; //Fix the imagettfbbox cut the tail of "p" character.
		
		$width = $size['width'] + $padding_left + $fix_value;
		$height = $size['height'] + $padding_top + $fix_value;
		
		$padding_top = $padding_top + $fix_value;
	}
	
	$im = ImageCreate($width,$height);
	$bgrgb = html_html2rgb($bgcolor);
	$bgc = ImageColorAllocate($im,$bgrgb[0],$bgrgb[1],$bgrgb[2]);
	// Set the string
	$textrgb = html_html2rgb($textcolor);
	imagettftext ($im, $fontsize, 0, $padding_left, $height - $padding_top,
		ImageColorAllocate($im,$textrgb[0],$textrgb[1],$textrgb[2]),
		$config['fontpath'], $string);
	//imagestring($im, $fontsize, $padding_left, $padding_top, $string, ImageColorAllocate($im,$textrgb[0],$textrgb[1],$textrgb[2]));
	// Rotates the image
	$rotated = imagerotate($im, $degrees, 0) ; 
	
	//Cleaned string file name (as the slash)
	$stringFile = str_replace('/', '___', $string);
	
	// Generate the image
	$file_url = $config['attachment_store'] . '/string2image-'.$stringFile.'.gif';
	imagegif($rotated, $file_url);
	imagedestroy($rotated);
	
	$file_url = str_replace('#','%23',$file_url);
	$file_url = str_replace('%','%25',$file_url);
	$file_url = str_replace($config['attachment_store'], $home_url . 'attachment', $file_url);
	
	return $file_url;
}

/**
* Function to restrict SQL on custom-user-defined queries 
*
* @param string SQL code
* @return string SQL code validated (it will return empty if SQL is not ok)
**/

function check_sql ($sql) {
	// We remove "*" to avoid things like SELECT * FROM tusuario

	//Check that it not delete_ as "delete_pending" (this is a common field in pandora tables).
	
	if (preg_match("/\*|delete[^_]|drop|alter|modify|union|password|pass|insert|update/i", $sql)) {
		return "";
	}
	return $sql;
}

/**
 * Check if login session variables are set.
 *
 * It will stop the execution if those variables were not set
 *
 * @return bool 0 on success exit() on no success
 */

function check_login () {
	global $config;
	
	if (!isset ($config["homedir"])) {
		// No exists $config. Exit inmediatly
		include("general/noaccess.php");
		exit;
	}
	
	if ((isset($_SESSION["id_usuario"])) AND ($_SESSION["id_usuario"] != "")) {
		if (is_user ($_SESSION["id_usuario"])) {
			$config['id_user'] = $_SESSION["id_usuario"];
			return 0;
		}
	}
	else {
		require_once($config["homedir"].'/mobile/include/user.class.php');
		session_start ();
		session_write_close ();
		if (isset($_SESSION['user'])) {
			$user = $_SESSION['user'];
			$id_user = $user->getIdUser();
			if (is_user ($id_user)) {
				return 0;
			}
		}
	}

	db_pandora_audit("No session", "Trying to access without a valid session", "N/A");
	include ($config["homedir"]."/general/noaccess.php");
	exit;
}

/**
 * Check access privileges to resources
 *
 * Access can be:
 * IR - Incident/report Read
 * IW - Incident/report Write
 * IM - Incident/report Management
 * AR - Agent Read
 * AW - Agent Write
 * LW - Alert Write
 * UM - User Management
 * DM - DB Management
 * LM - Alert Management
 * PM - Pandora Management
 *
 * @param int $id_user User id
 * @param int $id_group Agents group id to check from
 * @param string $access Access privilege
 * @param int $id_agent The agent id.
 *
 * @return bool 1 if the user has privileges, 0 if not.
 */
function check_acl($id_user, $id_group, $access, $id_agent = 0) {
	if (empty ($id_user)) {
		//User ID needs to be specified
		trigger_error ("Security error: check_acl got an empty string for user id", E_USER_WARNING);
		return 0;
	}
	elseif (is_user_admin ($id_user)) {
		return 1;
	}
	else {
		$id_group = (int) $id_group;
	}
	
	$parents_id = array($id_group);
	if ($id_group != 0) {
		$group = db_get_row_filter('tgrupo', array('id_grupo' => $id_group));
		$parents = groups_get_parents($group['parent'], true);

		foreach ($parents as $parent) {
			$parents_id[] = $parent['id_grupo'];
		}
	}
	else {
		$parents_id = array();
	}
	
	// TODO: To reduce this querys in one adding the group condition if necessary (only one line is different)
	//Joined multiple queries into one. That saves on the query overhead and query cache.
	if ($id_group == 0) {
		$query = sprintf("SELECT tperfil.incident_view, tperfil.incident_edit,
				tperfil.incident_management, tperfil.agent_view,
				tperfil.agent_edit, tperfil.alert_edit,
				tperfil.alert_management, tperfil.pandora_management,
				tperfil.db_management, tperfil.user_management
			FROM tusuario_perfil, tperfil
			WHERE tusuario_perfil.id_perfil = tperfil.id_perfil
				AND tusuario_perfil.id_usuario = '%s'", $id_user);
		//GroupID = 0, group id doesnt matter (use with caution!)
	}
	else {
		$query = sprintf("SELECT tperfil.incident_view, tperfil.incident_edit,
				tperfil.incident_management, tperfil.agent_view,
				tperfil.agent_edit, tperfil.alert_edit,
				tperfil.alert_management, tperfil.pandora_management,
				tperfil.db_management, tperfil.user_management
			FROM tusuario_perfil, tperfil
			WHERE tusuario_perfil.id_perfil = tperfil.id_perfil 
				AND tusuario_perfil.id_usuario = '%s'
				AND (tusuario_perfil.id_grupo IN (%s)
				OR tusuario_perfil.id_grupo = 0)", $id_user, implode(', ', $parents_id));
	}

	$rowdup = db_get_all_rows_sql ($query);
	
	if (empty ($rowdup))
		return 0;

	$result = 0;
	foreach ($rowdup as $row) {
		// For each profile for this pair of group and user do...
		switch ($access) {
			case "IR":
				$result += $row["incident_view"];
				break;
			case "IW":
				$result += $row["incident_edit"];
				break;
			case "IM":
				$result += $row["incident_management"];
				break;
			case "AR":
				$result += $row["agent_view"];
				break;
			case "AW":
				$result += $row["agent_edit"];
				break;
			case "LW":
				$result += $row["alert_edit"];
				break;
			case "LM":
				$result += $row["alert_management"];
				break;
			case "PM":
				$result += $row["pandora_management"];
				break;
			case "DM":
				$result += $row["db_management"];
				break;
			case "UM":
				$result += $row["user_management"];
				break;
		}
	}
	
	if ($result >= 1) {
		return 1;
	}

	return 0;
}

/**
 * Get the name of a plugin
 *
 * @param int id_plugin Plugin id.
 *
 * @return string The name of the given plugin
 */
function dame_nombre_pluginid ($id_plugin) {
	return (string) db_get_value ('name', 'tplugin', 'id', (int) $id_plugin);
}

/**
 * Get the operating system name.
 *
 * @param int Operating system id.
 *
 * @return string Name of the given operating system.
 */
function get_os_name ($id_os) {
	return (string) db_get_value ('name', 'tconfig_os', 'id_os', (int) $id_os);
}

/**
 * Get all the possible periods in seconds.
 *
 * @return The possible periods in an associative array.
 */
function get_periods () {
	$periods = array ();
	
	$periods[-1] = __('custom');
	$periods[SECONDS_5MINUTES] = '5 '.__('minutes');
	$periods[SECONDS_30MINUTES] = '30 '.__('minutes');
	$periods[SECONDS_1HOUR] = __('1 hour');
	$periods[SECONDS_6HOURS] = '6 '.__('hours');
	$periods[SECONDS_12HOURS] = '12 '.__('hours');
	$periods[SECONDS_1DAY] = __('1 day');
	$periods[SECONDS_1WEEK] = __('1 week');
	$periods[SECONDS_15DAYS] = __('15 days');
	$periods[SECONDS_1MONTH] = '1 '.__('month');
	$periods[SECONDS_3MONTHS] = '3 '.__('months');
	$periods[SECONDS_6MONTHS] = '6 '.__('months');
	$periods[SECONDS_1YEAR] = '1 '.__('year');
	$periods[SECONDS_2YEARS] = '2 '.__('years');
	$periods[SECONDS_3YEARS] = '3 '.__('years');
	
	return $periods;
}

?>
