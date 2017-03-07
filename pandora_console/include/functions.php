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

/**
 * Check referer to avoid external attacks
 *
 * @return bool true if all is ok, false if referer is not equal to current web page
 */
//function check_referer() {
//	global $config;
//	
//	//If it is disabled the check referer security
//	if (!$config["referer_security"])
//		return true;
//	
//	$referer = '';
//	if (isset($_SERVER['HTTP_REFERER'])) {
//		$referer = $_SERVER['HTTP_REFERER'];
//	}
//	
//	// If refresh is performed then dont't check referer
//	// This is done due to problems with HTTP_REFERER var when metarefresh is performed
//	if ($config["refr"] > 0) 
//		return true;
//	
//	//Check if the referer have a port (for example when apache run in other port to 80)
//	if (preg_match('/http(s?):\/\/.*:[0-9]*/', $referer) == 1) {
//		$url = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $config["homeurl"];
//	}
//	else {
//		$url = ui_get_full_url();
//		$url = preg_replace('/http(s?):\/\//','',$url);
//	}
//	
//	// Remove protocol from referer
//	$referer = preg_replace('/http(s?):\/\//','',$referer);
//	$referer = preg_replace('/\?.*/','',$referer);
//	
//	if (strpos($url, $referer) === 0) {
//		return true;
//	}
//	else {
//		return false;
//	}
//}

function https_is_running() {
	if (isset ($_SERVER['HTTPS'])
		&& ($_SERVER['HTTPS'] === true
		|| $_SERVER['HTTPS'] == 'on')) {
		
		return true;
	}
	
	return false;
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
			}
			else {
				unset ($value[$key]);
			}
		}
	}
	else {
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
		while (false !== ($fileName = @readdir ($directoryHandler))) {
			if ((@substr_count ($fileName, $stringSearch) > 0) || (@substr_count ($fileName, strtoupper($stringSearch)) > 0)) {
				$result[$fileName] = $fileName;
			}
		}
	}
	if (($errorHandler == true) && (@count ($result) === 0)) {
		echo ("<pre>\nerror: no filetype \"$fileExtension\" found!\n</pre>\n");
	}
	else {
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
	
	return remove_right_zeros(format_numeric ($number, $decimals)). $shorts[$pos]; //This will actually do the rounding and the decimals
}

function human_milliseconds_to_string($seconds) {
    $ret = "";
	
    /*** get the days ***/
    $days = intval(intval($seconds) / (360000*24));
    if($days > 0)
		$ret .= "$days days ";
	
    /*** get the hours ***/
    $hours = (intval($seconds) / 360000) % 24;
    if($hours > 0)
		$ret .= "$hours hours ";

    /*** get the minutes ***/
    $minutes = (intval($seconds) / 6000) % 60;
    if($minutes > 0)
		$ret .= "$minutes minutes ";
	
    /*** get the seconds ***/
    $seconds = intval($seconds / 100) % 60;
    if ($seconds > 0)
        $ret .= "$seconds seconds";
	
    return $ret;
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
	
	// $seconds could be negative, because get_system_time() could return cached value
	// (that might be the time a session begins at).
	// So negative values are to be rounded off to 'NOW'.
	if ( $seconds < 0 ) {
		$seconds = 0;
	}
	
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
 *  @param string $id_user
 * 
 *  @return string user active language code
 */
function get_user_language ($id_user = null) {
	global $config;
	
	$quick_language = get_parameter('quick_language_change', 0);
	
	if ($quick_language) {
		$language = get_parameter('language', 0);
		
		if (defined('METACONSOLE')) {
			
			if ($id_user == null)
				$id_user = $config['id_user'];
			
			if ($language !== 0)
				update_user($id_user, array('language' => $language));
		
		}
		
		if ($language === 'default') {
			return $config['language'];
		}
		
		if ($language !== 0) {
			return $language;
		}
	}
	
	if ($id_user === null && isset($config['id_user'])) {
		$id_user = $config['id_user'];
	}
	
	if ($id_user !== null) {
		$userinfo = get_user_info ($id_user);
		if ($userinfo['language'] != 'default') {
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
	
	if (file_exists ('./include/languages/' . $user_language . '.mo')) {
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
		
		$years = floor($seconds / SECONDS_1YEAR);
		
		if ($years != 0) {
			$seconds = $seconds - ($years * SECONDS_1YEAR);
			
			$returnDate .= "$years $yearsString ";
		}
		
		$months = floor($seconds / SECONDS_1MONTH);
		
		if ($months != 0) {
			$seconds = $seconds - ($months * SECONDS_1MONTH);
			
			$returnDate .= "$months $monthsString ";
		}
		
		$days = floor($seconds / SECONDS_1DAY);
		
		if ($days != 0) {
			$seconds = $seconds - ($days * SECONDS_1DAY);
			
			$returnDate .= "$days $daysString ";
		}
		
		$returnTime = '';
		
		$hours = floor($seconds / SECONDS_1HOUR);
		
		if ($hours != 0) {
			$seconds = $seconds - ($hours * SECONDS_1HOUR);
			
			$returnTime .= "$hours $hoursString ";
		}
		
		$mins = floor($seconds / 60);
		
		if ($mins != 0) {
			$seconds = $seconds - ($mins * 60);
			
			$returnTime .= "$mins $minutesString ";
			
		}
		
		$seconds = (int) $seconds;
		
		if ($seconds != 0) {
			$returnTime .= "$seconds $secondsString ";
		}
		
		$return = ' ';
		
		if ($returnDate != '') {
			$return = $returnDate;
		}
		
		if ($returnTime != '') {
			$return .= $returnTime;
		}
		
		if ($return == ' ') {
			return $nowString; 
		}
		else {
			return $return;
		}
		
	}
	
	if ($seconds < SECONDS_1MINUTE)
		return format_numeric ($seconds, 0)." " . $secondsString;
	
	if ($seconds < SECONDS_1HOUR) {
		$minutes = floor($seconds / 60);
		$seconds = $seconds % SECONDS_1MINUTE;
		if ($seconds == 0)
			return $minutes.' ' . $minutesString;
		$seconds = sprintf ("%02d", $seconds);
		return $minutes.' '. $minutesString . ' ' .$seconds.' ' . $secondsString;
	}
	
	if ($seconds < SECONDS_1DAY)
		return format_numeric ($seconds / SECONDS_1HOUR, 0)." " . $hoursString;
	
	if ($seconds < SECONDS_1MONTH)
		return format_numeric ($seconds / SECONDS_1DAY, 0) . " " . $daysString;
	
	if ($seconds < SECONDS_6MONTHS)
		return format_numeric ($seconds / SECONDS_1MONTH, 0)." ". $monthsString;
	
	return "+6 " . $monthsString;
}

/** 
 * INTERNAL (use ui_print_timestamp for output): Transform an amount of time in seconds into a human readable
 * strings of minutes, hours or days. Used in alert views.
 * 
 * @param int $seconds Seconds elapsed time
 * @param int $exactly If it's true, return the exactly human time
 * @param string $units The type of unit, by default 'large'.
 * 
 * @return string A human readable translation of minutes.
 */
function human_time_description_alerts ($seconds, $exactly = false, $units = 'tiny') {
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
		
		$years = floor($seconds / SECONDS_1YEAR);
		
		if ($years != 0) {
			$seconds = $seconds - ($years * SECONDS_1YEAR);
			
			$returnDate .= "$years $yearsString ";
		}
		
		$months = floor($seconds / SECONDS_1MONTH);
		
		if ($months != 0) {
			$seconds = $seconds - ($months * SECONDS_1MONTH);
			
			$returnDate .= "$months $monthsString ";
		}
		
		$days = floor($seconds / SECONDS_1DAY);
		
		if ($days != 0) {
			$seconds = $seconds - ($days * SECONDS_1DAY);
			
			$returnDate .= "$days $daysString ";
		}
		
		$returnTime = '';
		
		$hours = floor($seconds / SECONDS_1HOUR);
		
		if ($hours != 0) {
			$seconds = $seconds - ($hours * SECONDS_1HOUR);
			
			$returnTime .= "$hours $hoursString ";
		}
		
		$mins = floor($seconds / SECONDS_1MINUTE);
		
		if ($mins != 0) {
			$seconds = $seconds - ($mins * SECONDS_1MINUTE);
			
			if ($hours == 0) {
				$returnTime .= "$mins $minutesString ";
			}
			else {
				$returnTime = sprintf("%02d",$hours) . "$hoursString" .
					sprintf("%02d",$mins) . "$minutesString";
			}
		}
		
		if ($seconds != 0) {
			if ($hours == 0) {
				$returnTime .= "$seconds $secondsString ";
			}
			else {
				$returnTime = sprintf("%02d",$hours) . "$hoursString" .
					sprintf("%02d",$mins) . "$minutesString" .
					sprintf("%02d",$seconds) . "$secondsString";
			}
		}
		
		$return = ' ';
		
		if ($returnDate != '') {
			$return = $returnDate;
		}
		
		if ($returnTime != '') {
			$return .= $returnTime;
		}
		
		if ($return == ' ') {
			return $nowString; 
		}
		else {
			return $return;
		}
		
	}
	
	if ($seconds < 60)
		return format_numeric ($seconds, 0)." " . $secondsString;
	
	if ($seconds < SECONDS_1HOUR) {
		$minutes = floor($seconds / SECONDS_1MINUTE);
		$seconds = $seconds % SECONDS_1MINUTE;
		if ($seconds == 0)
			return $minutes.' ' . $minutesString;
		$seconds = sprintf ("%02d", $seconds);
		return $minutes.' '. $minutesString . ' ' .$seconds.' ' . $secondsString;
	}
	
	if ($seconds < SECONDS_1DAY)
		return format_numeric ($seconds / SECONDS_1HOUR, 0)." " . $hoursString;
	
	if ($seconds < SECONDS_1MONTH)
		return format_numeric ($seconds / SECONDS_1DAY, 0) . " " . $daysString;
	
	if ($seconds < SECONDS_6MONTHS)
		return format_numeric ($seconds / SECONDS_1MONTH, 0)." ". $monthsString;
	
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
 * Get a parameter from a checkbox.
 * 
 * Is checked if the checkbox is sent to fix html bad design
 *
 * @param string $name key of the parameter in the $_POST or $_GET array
 * @param mixed $default default value if the key wasn't found
 * 
 * @return mixed Whatever was in that parameter, cleaned however 
 * 
 */

function get_parameter_checkbox ($name, $default = '') {
	$sent = get_parameter($name.'_sent', 0);
	
	// If is not sent, return the default
	if (!$sent) {
		return $default;
	}
	
	// If sent, get parameter normally
	return get_parameter($name, 0);
}

function get_cookie($name, $default = '') {
	if (isset($_COOKIE[$name])) {
		return $_COOKIE[$name];
	}
	else {
		return $default;
	}
}

function set_cookie($name, $value) {
	if (is_null($value)) {
		unset($_COOKIE[$value]);
		setcookie($name, null, -1, '/');
	}
	else {
		setcookie($name, $value);
	}
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
	if ((isset ($_POST[$name])) && ($_POST[$name] != "")) {
		return io_safe_input ($_POST[$name]);
	}
	
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
		case 5:
			return __('Minor');
			break;
		case 6:
			return __('Major');
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
	}
	elseif ($check == 0) {
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
	if ($row2["time_from"]) {
		$time_from_table = $row2["time_from"];
	}
	else {
		$time_from_table = __('N/A');
	}
	if ($row2["time_to"]) {
		$time_to_table = $row2["time_to"];
	}
	else {
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
function get_event_types ($id_type = false) {
	global $config;
	
	$types = array();
	
	$types['critical'] = __('Monitor Critical');
	$types['warning'] = __('Monitor Warning');
	$types['normal'] = __('Monitor Normal');
	$types['unknown'] = __('Unknown');
	$types['going_unknown'] = __('Monitor Unknown');

	$types['alert_fired'] = __('Alert fired');
	$types['alert_recovered'] = __('Alert recovered');
	$types['alert_ceased'] = __('Alert ceased');
	$types['alert_manual_validation'] = __('Alert manual validation');
	
	$types['new_agent'] = __('Agent created');
	$types['recon_host_detected'] = __('Recon host detected');
	$types['system'] = __('System');
	$types['error'] = __('Error');
	$types['configuration_change'] = __('Configuration change');

	// This types are impersonated by the monitor 'x' types
	// $types['going_up_normal'] = __('Going Normal');
	// $types['going_up_warning'] = __('Going Warning');
	// $types['going_up_critical'] = __('Going Critical');
	// $types['going_down_warning'] = __('Going down Warning');
	// $types['going_down_normal'] = __('Going down Normal');
	// $types['going_down_critical'] = __('Going down Critical');

	foreach ($types as $key => $type) {
		$types[$key] = ui_print_truncate_text($type, GENERIC_SIZE_TEXT, false, true, false);
	}
	
	if ($id_type === false) {
		return $types;
	}
	else {
		return $types[$id_type];
	}
}

/**
 * Get an array with all the priorities.
 *
 * @return array An array with all the priorities.
 */
function get_priorities ($priority_param = false) {
	global $config;
	
	$priorities = array ();
	$priorities[EVENT_CRIT_MAINTENANCE] = __('Maintenance');
	$priorities[EVENT_CRIT_INFORMATIONAL] = __('Informational');
	$priorities[EVENT_CRIT_NORMAL] = __('Normal');
	$priorities[EVENT_CRIT_MINOR] = __('Minor');
	$priorities[EVENT_CRIT_WARNING] = __('Warning');
	$priorities[EVENT_CRIT_MAJOR] = __('Major');
	$priorities[EVENT_CRIT_CRITICAL] = __('Critical');
	$priorities[EVENT_CRIT_WARNING_OR_CRITICAL] = __('Warning').'/'.__('Critical');
	$priorities[EVENT_CRIT_NOT_NORMAL] = __('Not normal');
	$priorities[EVENT_CRIT_OR_NORMAL] = __('Critical') . '/' . __('Normal');
	
	foreach ($priorities as $key => $priority) {
		$priorities[$key] = ui_print_truncate_text($priority, GENERIC_SIZE_TEXT, false, true, false);
	}
	
	if ($priority_param === false) {
		return $priorities;
	}
	else {
		return $priorities[$priority_param];
	}
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
		case 5: 
			return __('Minor');
			break;
		case 6: 
			return __('Major');
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
		case 5: 
			return "datos_pink";
			break;
		case 6: 
			return "datos_brown";
			break;
		default: 
			return "datos_grey";
			break;
	}
}

/**
 * Get priority style from priority class (CSS class).
 *
 * @param string priority class.
 *
 * @return string CSS priority class.
 */
function get_priority_style($priority_class) {
	switch ($priority_class) {
		case "datos_blue":
			$style_css_criticity = 'background-color: ' . COL_MAINTENANCE . '; color: #FFFFFF;';
			break;
		case "datos_grey":
			$style_css_criticity = 'background-color: ' . COL_UNKNOWN . '; color: #FFFFFF;';
			break;
		case "datos_green":
			$style_css_criticity = 'background-color: ' . COL_NORMAL . '; color: #FFFFFF;';
			break;
		case "datos_yellow":
			$style_css_criticity = 'background-color: ' . COL_WARNING . ';';
			break;
		case "datos_red":
			$style_css_criticity = 'background-color: ' . COL_CRITICAL . '; color: #FFFFFF;';
			break;
		case "datos_pink":
			$style_css_criticity = 'background-color: ' . COL_MINOR . ';';
			break;
		case "datos_brown":
			$style_css_criticity = 'background-color: ' . COL_MAJOR . '; color: #FFFFFF;';
			break;
		case "datos_grey":
		default:
			$style_css_criticity = 'background-color: ' . COL_UNKNOWN . '; color: #FFFFFF;';
			break;
	}
	
	return $style_css_criticity;
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
 * Check if the license is PANDORA-FREE.
 * 
 * @return boolean.
 */
function license_free() {
	$return = true;
	
	$pandora_license = db_get_value ('value', 'tupdate_settings', '`key`', 'customer_key');
	if ($pandora_license !== 'PANDORA-FREE') $return = false;
	
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
	$filepath = realpath ($config["homedir"] . '/' . ENTERPRISE_DIR . '/' . $filename);
	
	if ($filepath === false)
		return ENTERPRISE_NOT_HOOK;
	
	if (strncmp ($config["homedir"], $filepath, strlen ($config["homedir"])) != 0) {
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

function is_metaconsole() {
	global $config;
	
	if ($config['metaconsole'])
		return true;
	else
		return false;
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
	if ($code !== true AND ($code <= ERR_GENERIC || $code === false)) {
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
	
	foreach ($array as $index_array => $element) {
		if (!is_null($index)) {
			if (! isset ($element[$index]))
				continue;
		}
		if ($value === false) {
			$retval[$element[$index]] = $element;
			continue;
		}
		
		if (! isset ($element[$value]))
			continue;
		
		if (is_null($index)) {
			$retval[$index_array] = $element[$value];
		}
		else {
			$retval[$element[$index]] = $element[$value];
		}
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

function return_graphtype ($id_module_type) {
	switch ($id_module_type) {
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
		default:
			return "sparse";
			break;
	}
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
function get_snmpwalk($ip_target, $snmp_version, $snmp_community = '',
	$snmp3_auth_user = '', $snmp3_security_level = '',
	$snmp3_auth_method = '', $snmp3_auth_pass = '',
	$snmp3_privacy_method = '', $snmp3_privacy_pass = '',
	$quick_print = 0, $base_oid = "", $snmp_port = '') {
	
	global $config;
	
	// Note: quick_print is ignored
	
	// Fix for snmp port
	if (!empty($snmp_port)) {
		$ip_target = $ip_target.':'.$snmp_port;
	}
	
	// Escape the base OID
	if ($base_oid != "") {
		$base_oid = escapeshellarg ($base_oid);
	}
	
	if (empty($config['snmpwalk'])) {
		switch (PHP_OS) {
			case "FreeBSD":
				$snmpwalk_bin = '/usr/local/bin/snmpwalk';
				break;
			case "NetBSD":
				$snmpwalk_bin = '/usr/pkg/bin/snmpwalk';
				break;
			default:
				$snmpwalk_bin = 'snmpwalk';
				break;
		}
	}
	else {
		$snmpwalk_bin = $config['snmpwalk'];
	}
	
	switch (PHP_OS) {
		case "WIN32":
		case "WINNT":
		case "Windows":
			$error_redir_dir = 'NUL';
			break;
		default:
			$error_redir_dir = '/dev/null';
			break;
	}

	$output = array();
	$rc = 0;
	switch ($snmp_version) {
		case '3':
			switch ($snmp3_security_level) {
				case "authNoPriv":
					$command_str = $snmpwalk_bin .
						' -m ALL -Oa -v 3' .
						' -u ' . escapeshellarg($snmp3_auth_user) .
						' -A ' . escapeshellarg($snmp3_auth_pass) .
						' -l ' . escapeshellarg($snmp3_security_level) .
						' -a ' . escapeshellarg($snmp3_auth_method) .
						' ' . escapeshellarg($ip_target)  .
						' ' . $base_oid .
						' 2> ' . $error_redir_dir;
					break;
				case "noAuthNoPriv":
					$command_str = $snmpwalk_bin .
						' -m ALL -Oa -v 3' .
						' -u ' . escapeshellarg($snmp3_auth_user) .
						' -l ' . escapeshellarg($snmp3_security_level) .
						' ' . escapeshellarg($ip_target)  .
						' ' . $base_oid .
						' 2> ' . $error_redir_dir;
					break;
				default:
					$command_str = $snmpwalk_bin .
						' -m ALL -Oa -v 3' .
						' -u ' . escapeshellarg($snmp3_auth_user) .
						' -A ' . escapeshellarg($snmp3_auth_pass) .
						' -l ' . escapeshellarg($snmp3_security_level) .
						' -a ' . escapeshellarg($snmp3_auth_method) .
						' -x ' . escapeshellarg($snmp3_privacy_method) .
						' -X ' . escapeshellarg($snmp3_privacy_pass) .
						' ' . escapeshellarg($ip_target)  .
						' ' . $base_oid .
						' 2> ' . $error_redir_dir;
					break;
			}
			break;
		case '2':
		case '2c':
		case '1':
		default:
			$command_str = $snmpwalk_bin . ' -m ALL -Oa -v ' . escapeshellarg($snmp_version) . ' -c ' . escapeshellarg($snmp_community) . ' ' . escapeshellarg($ip_target)  . ' ' . $base_oid . ' 2> ' . $error_redir_dir;
			break;
	}
	
	exec($command_str, $output, $rc);
	
	// Parse the output of snmpwalk
	$snmpwalk = array();
	foreach ($output as $line) {
		
		// Separate the OID from the value
		$full_oid = explode (' = ', $line);
		if (isset ($full_oid[1])) {
			$snmpwalk[$full_oid[0]] = $full_oid[1];
		}
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
 * @param boolean $output Set return instead the output. By default true.
 *
 * @return bool 0 on success exit() on no success
 */

function check_login ($output = true) {
	global $config;
	
	if (!isset ($config["homedir"])) {
		if (!$output) {
			return false;
		}
		
		// No exists $config. Exit inmediatly
		include("general/noaccess.php");
		exit;
	}
	
	if ((isset($_SESSION["id_usuario"])) AND ($_SESSION["id_usuario"] != "")) {
		if (is_user ($_SESSION["id_usuario"])) {
			$config['id_user'] = $_SESSION["id_usuario"];
			
			return true;
		}
	}
	else {
		require_once($config["homedir"].'/mobile/include/user.class.php');
		if(session_id() == '') {
			session_start ();
		}
		session_write_close ();
		if (isset($_SESSION['user'])) {
			$user = $_SESSION['user'];
			$id_user = $user->getIdUser();
			if (is_user ($id_user)) {
				return true;
			}
		}
	}
	
	if (!$output) {
		return false;
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
 * @param bool $onlyOneGroup Flag to check acl for specified group only (not to roots up, or check acl for 'All' group when $id_group is 0).
 *
 * @return bool 1 if the user has privileges, 0 if not.
 */
function check_acl($id_user, $id_group, $access, $onlyOneGroup = false) {
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
	if ($id_group != 0 && $onlyOneGroup !== true) {
		$group = db_get_row_filter('tgrupo', array('id_grupo' => $id_group));
		$parents = groups_get_parents($group['parent'], true);
		
		foreach ($parents as $parent) {
			$parents_id[] = $parent['id_grupo'];
		}
	}
	
	// TODO: To reduce this querys in one adding the group condition if necessary (only one line is different)
	//Joined multiple queries into one. That saves on the query overhead and query cache.
	if ($id_group == 0 && $onlyOneGroup !== true) {
		$query = sprintf("SELECT tperfil.incident_view, tperfil.incident_edit,
				tperfil.incident_management, tperfil.agent_view,
				tperfil.agent_edit, tperfil.alert_edit,
				tperfil.alert_management, tperfil.pandora_management,
				tperfil.db_management, tperfil.user_management,
				tperfil.report_view, tperfil.report_edit,
				tperfil.report_management, tperfil.event_view,
				tperfil.event_edit, tperfil.event_management, 
				tperfil.agent_disable,
				tperfil.map_view, tperfil.map_edit, tperfil.map_management,
				tperfil.vconsole_view, tperfil.vconsole_edit, tperfil.vconsole_management
			FROM tusuario_perfil, tperfil
			WHERE tusuario_perfil.id_perfil = tperfil.id_perfil
				AND tusuario_perfil.id_usuario = '%s'", $id_user);
		//GroupID = 0 and onlyOneGroup = false, group id doesnt matter (use with caution!)
	}
	else {
		$query = sprintf("SELECT tperfil.incident_view, tperfil.incident_edit,
				tperfil.incident_management, tperfil.agent_view,
				tperfil.agent_edit, tperfil.alert_edit,
				tperfil.alert_management, tperfil.pandora_management,
				tperfil.db_management, tperfil.user_management,
				tperfil.report_view, tperfil.report_edit,
				tperfil.report_management, tperfil.event_view,
				tperfil.event_edit, tperfil.event_management,
				tperfil.agent_disable,
				tperfil.map_view, tperfil.map_edit, tperfil.map_management,
				tperfil.vconsole_view, tperfil.vconsole_edit, tperfil.vconsole_management
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
	$acl_column = get_acl_column($access);
	foreach ($rowdup as $row) {
		// For each profile for this pair of group and user do...
		if (isset($row[$acl_column])) {
			$result += $row[$acl_column];
		}
	}
	
	if ($result >= 1) {
		return 1;
	}
	
	return 0;
}

/**
 * Get the name of the database column of one access flag
 *
 * @param string access flag
 *
 * @return string Column name
 */
function get_acl_column($access) {
	switch ($access) {
		case "IR":
			return "incident_view";
			break;
		case "IW":
			return "incident_edit";
			break;
		case "IM":
			return "incident_management";
			break;
		case "AR":
			return "agent_view";
			break;
		case "AW":
			return "agent_edit";
			break;
		case "AD":
			return "agent_disable";
			break;
		case "LW":
			return "alert_edit";
			break;
		case "LM":
			return "alert_management";
			break;
		case "PM":
			return "pandora_management";
			break;
		case "DM":
			return "db_management";
			break;
		case "UM":
			return "user_management";
			break;
		case "RR":
			return "report_view";
			break;
		case "RW":
			return "report_edit";
			break;
		case "RM":
			return "report_management";
			break;
		case "ER":
			return "event_view";
			break;
		case "EW":
			return "event_edit";
			break;
		case "EM":
			return "event_management";
			break;
		case "MR":
			return "map_view";
			break;
		case "MW":
			return "map_edit";
			break;
		case "MM":
			return "map_management";
			break;
		case "VR":
			return "vconsole_view";
			break;
		case "VW":
			return "vconsole_edit";
			break;
		case "VM":
			return "vconsole_management";
			break;
		default:
			return "";
			break;
	}
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
 * Get the operating system id.
 *
 * @param string Operating system name.
 *
 * @return id Id of the given operating system.
 */
function get_os_id ($os_name) {
	return (string) db_get_value ('id_os', 'tconfig_os', 'name', $os_name);
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
 * Get user's dashboards
 *
 * @param int user id.
 *
 * @return array Dashboard name of the given user.
 */
function get_user_dashboards ($id_user) {
	$sql = "SELECT name
		FROM tdashboard
		WHERE id_user="."'".$id_user."'";
	
	return db_get_all_rows_sql ($sql);
}

/**
 * Get all the possible periods in seconds.
 *
 * @param bool Flag to show or not custom fist option
 * @param bool Show the periods by default if it is empty
 * 
 * @return The possible periods in an associative array.
 */
function get_periods ($custom = true, $show_default = true) {
	global $config;
	
	
	$periods = array ();
	
	if ($custom) {
		$periods[-1] = __('custom');
	}
	
	if (empty($config['interval_values'])) {
		if ($show_default) {
			$periods[SECONDS_5MINUTES] = sprintf(__('%s minutes'), '5');
			$periods[SECONDS_30MINUTES] = sprintf(__('%s minutes'), '30 ');
			$periods[SECONDS_1HOUR] = __('1 hour');
			$periods[SECONDS_6HOURS] = sprintf(__('%s hours'), '6 ');
			$periods[SECONDS_12HOURS] = sprintf(__('%s hours'), '12 ');
			$periods[SECONDS_1DAY] = __('1 day');
			$periods[SECONDS_1WEEK] = __('1 week');
			$periods[SECONDS_15DAYS] = __('15 days');
			$periods[SECONDS_1MONTH] = __('1 month');
			$periods[SECONDS_3MONTHS] = sprintf(__('%s months'), '3 ');
			$periods[SECONDS_6MONTHS] = sprintf(__('%s months'), '6 ');
			$periods[SECONDS_1YEAR] = __('1 year');
			$periods[SECONDS_2YEARS] = sprintf(__('%s years'), '2 ');
			$periods[SECONDS_3YEARS] = sprintf(__('%s years'), '3 ');
		}
		else {
			$periods[-1] = __('Empty').': '.__('Default values will be used');
		}
	}
	else {
		$values = explode(',',$config['interval_values']);
		foreach($values as $v) {
			$periods[$v] = human_time_description_raw ($v, true);
		}
	}
	
	return $periods;
}


/**
 * Recursive copy directory
 */
function copy_dir($src, $dst) { 
	$dir = opendir($src);
	$return = true;
	
	if (!$dir)
		return false;
	
	@mkdir($dst); 
	while (false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				$return = copy_dir($src . '/' . $file, $dst . '/' . $file);
				
				if (!$return) {
					break;
				}
			}
			else {
				$r = copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}
	
	closedir($dir); 
	
	return $return;
}

function delete_dir($dir) {
	if (!file_exists($dir))
		return true;
	
	if (!is_dir($dir))
		return unlink($dir);
	
	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..')
			continue;
		
		if (!delete_dir($dir . "/" . $item))
			return false;
	}
	
	return rmdir($dir);
}

/**
 * Returns 1 if the data contains a codified image (base64)
 */
function is_image_data ($data) {
	return (substr($data,0,10) == "data:image");
}

/**
*  Returns 1 if this is Snapshot data, 0 otherwise
*  Looks for two or more carriage returns.
*/
function is_snapshot_data ($data) {
	
	// TODO IDEA: In the future, we can set a variable in setup
	// to define how many \n must have a snapshot to define it's 
	// a snapshot. I think two or three is a good value anyway.
	
	$temp = array();
	$count = preg_match_all ("/\n/", $data, $temp);
	
	if ( ($count > 2) || (is_image_data($data)) )
		return 1;
	else
		return 0;
}

/**
*  Create an invisible div with a provided ID and value to
* can retrieve it from javascript with function get_php_value(name)
*/
function set_js_value($name, $value) {
	html_print_div(array('id' => 'php_to_js_value_' . $name,
		'content' => json_encode($value), 'hidden' => true));
}


function is_array_empty($InputVariable)
{
	$Result = true;
	
	if (is_array($InputVariable) && count($InputVariable) > 0) {
		foreach ($InputVariable as $Value) {
			$Result = $Result && is_array_empty($Value);
		}
	}
	else {
		$Result = empty($InputVariable);
	}
	
	return $Result;
}

// This function is used to give or not access to nodes in 
// Metaconsole. Sometimes is used in common code between 
// Meta and normal console, so if Meta is not activated, it
// will return 1 always

// Return 0 if the user hasnt access to node/detail 1 otherwise
function can_user_access_node () {
	global $config;
	
	$userinfo = get_user_info ($config['id_user']);
	
	if (defined('METACONSOLE')) {
		return $userinfo["is_admin"] == 1 ? 1 :
			$userinfo["metaconsole_access_node"];
	}
	else {
		return 1;
	}
}

/**
 *  Get the upload status code
 */
function get_file_upload_status ($file_input_name) {
	if (!isset($_FILES[$file_input_name]))
		return -1;

	return $_FILES[$file_input_name]['error'];
}

/**
 *  Get a human readable message with the upload status code
 */
function translate_file_upload_status ($status_code) {
	switch ($status_code) {
		case UPLOAD_ERR_OK:
			$message = true;
			break;
		case UPLOAD_ERR_INI_SIZE:
			$message = __('The file exceeds the maximum size');
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$message = __('The file exceeds the maximum size');
			break;
		case UPLOAD_ERR_PARTIAL:
			$message = __('The uploaded file was only partially uploaded');
			break;
		case UPLOAD_ERR_NO_FILE:
			$message = __('No file was uploaded');
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$message = __('Missing a temporary folder');
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$message = __('Failed to write file to disk');
			break;
		case UPLOAD_ERR_EXTENSION:
			$message = __('File upload stopped by extension');
			break;
		
		default:
			$message = __('Unknown upload error');
			break;
	}
	
	return $message;
}

/**
 *  Get the arguments given in a function returning default value if not defined
 *  @param string name of the argument
 *  @param mixed array with arguments
 *  @param string defualt value for this argument
 * 
 *  @return string value for the argument
 */
function get_argument ($argument, $arguments, $default) {
	if (isset($arguments[$argument])) {
		return $arguments[$argument];
	}
	else {
		return $default;
	}
}

/**
 *  Get the arguments given in a function returning default value if not defined
 *  @param mixed arguments
 * 			- id_user: user who can see the news
 *  		- modal: true if want to get modal news. false to return not modal news
 * 			- limit: number of max news returned
 *  @return mixed list of news
 */
function get_news($arguments) {
	global $config;
	
	$id_user = get_argument ('id_user', $arguments, $config['id_user']);
	$modal = get_argument ('modal', $arguments, false);
	$limit = get_argument ('limit', $arguments, 99999999);
	
	$id_group = array_keys(users_get_groups($id_user, false, true));
	$id_group = implode(',',$id_group);
	$current_datetime = date('Y-m-d H:i:s', time());
	$modal = (int) $modal;
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf("SELECT subject,timestamp,text,author
				FROM tnews WHERE id_group IN (%s) AND 
								modal = %s AND 
								(expire = 0 OR (expire = 1 AND expire_timestamp > '%s'))
				ORDER BY timestamp DESC
				LIMIT %s", $id_group, $modal, $current_datetime, $limit);
			break;
		case "oracle":
			$sql = sprintf("SELECT subject,timestamp,text,author
				FROM tnews
				WHERE rownum <= %s AND id_group IN (%s) AND 
								modal = %s AND 
								(expire = 0 OR (expire = 1 AND expire_timestamp > '%s'))
				ORDER BY timestamp DESC", $limit, $id_group, $modal, $current_datetime);
			break;
	}
	
	$news = db_get_all_rows_sql ($sql);
	
	if (empty($news)) {
		$news = array();
	}
	
	return $news;
}


/**
 * Print audit data in CSV format.
 *
 * @param array Audit data.
 *
 */
function print_audit_csv ($data) {
	global $config;
	global $graphic_type;

	if (!$data) {
		echo __('No data found to export');
		return 0;
	}

	$config['ignore_callback'] = true;
	while (@ob_end_clean ());
	
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=audit_log".date("Y-m-d_His").".csv");
	header("Pragma: no-cache");
	header("Expires: 0");

	// BOM
	print pack('C*',0xEF,0xBB,0xBF);
	
	echo __('User') . ';' .
		__('Action') . ';' .
		__('Date') . ';' .
		__('Source ID') . ';' .
		__('Comments') ."\n";
	foreach ($data as $line) {
		echo io_safe_output($line['id_usuario']) . ';' .  io_safe_output($line['accion']) . ';' .  $line['fecha'] . ';' .  $line['ip_origen'] . ';'.  io_safe_output($line['descripcion']). "\n";
	}

	exit;
}

/**
 * Validate the code given to surpass the 2 step authentication
 *
 * @param string User name
 * @param string Code given by the authenticator app
 *
 * @return	-1 if the parameters introduced are incorrect,
 * 			there is a problem accessing the user secret or
 *			if an exception are launched.
 *			true if the code is valid.
 *			false if the code is invalid.
 */
function validate_double_auth_code ($user, $code) {
	global $config;
	require_once ($config['homedir'].'/include/auth/GAuth/Auth.php');
	$result = false;
	
	if (empty($user) || empty($code)) {
		$result = -1;
	}
	else {
		$secret = db_get_value('secret', 'tuser_double_auth', 'id_user', $user);
		
		if ($secret === false) {
			$result = -1;
		}
		else if (!empty($secret)) {
			try {
				$gAuth = new \GAuth\Auth($secret);
				$result = $gAuth->validateCode($code);
			}
			catch (Exception $e) {
				$result = -1;
			}
		}
	}
	
	return $result;
}

/**
 * Get if the 2 step authentication is enabled for the user given
 *
 * @param string User name
 *
 * @return true if the user has the double auth enabled or false otherwise.
 */
function is_double_auth_enabled ($user) {
	$result = (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $user);
	
	return $result;
}

function clear_pandora_error_for_header() {
	global $config;
	
	$config["alert_cnt"] = 0;
	$_SESSION["alert_msg"] = "";
}

function set_pandora_error_for_header($message, $title = null) {
	global $config;
	
	if (!isset($config['alert_cnt']))
		$config['alert_cnt'] = 0;
	
	if (!isset($_SESSION['alert_msg']))
		$_SESSION['alert_msg'] = array();
	
	$message_config = array();
	if (isset($title))
		$message_config['title'] = $title;
	$message_config['message'] = $message;
	$message_config['no_close'] = true;
	
	$config['alert_cnt']++;
	$_SESSION['alert_msg'][] = array('type' => 'error', 'message' => $message_config);
}

function get_pandora_error_for_header() {
	$result = '';
	
	if (isset($_SESSION['alert_msg']) && is_array($_SESSION['alert_msg'])) {
		foreach ($_SESSION['alert_msg'] as $key => $value) {
			if (!isset($value['type']) || !isset($value['message']))
				continue;
			
			switch ($value['type']) {
				case 'error':
					$result .= ui_print_error_message($value['message'], '', true);
					break;
				case 'info':
					$result .= ui_print_info_message($value['message'], '', true);
					break;
				default:
					break;
			}
		}
	}
	
	return $result;
}

function set_if_defined (&$var, $test) {
	if (isset($test)) {
		$var = $test;
		
		return true;
	}
	else {
		return false;
	}
}

function set_unless_defined (&$var, $default) {
	if (! isset($var)) {
		$var = $default;
		
		return true;
	}
	else {
		return false;
	}
}

function set_when_empty (&$var, $default) {
	if (empty($var)) {
		$var = $default;
		
		return true;
	}
	else {
		return false;
	}
}

function sort_by_column (&$array_ref, $column_parameter) {
	global $column;
	
	$column = $column_parameter;
	
	if (!empty($column)) {
		usort($array_ref, function ($a, $b) {
			global $column;
			
			return strcmp($a[$column], $b[$column]);
		});
	}
}

function array2XML($data, $root = null, $xml = NULL) {
	if ($xml == null) {
		$xml = simplexml_load_string(
			"<?xml version='1.0' encoding='UTF-8'?>\n<" . $root . " />");
	}
	
	foreach($data as $key => $value) {
		if (is_numeric($key)) {
			$key = "item_" . $key;
		}
		
		if (is_array($value)) {
			$node = $xml->addChild($key);
			array2XML($value, $root, $node);
		}
		else {
			$value = htmlentities($value);
			
			if (!is_numeric($value) && !is_bool($value)) {
				if (!empty($value)) {
					$xml->addChild($key, $value);
				}
			}
			else {
				$xml->addChild($key, $value);
			}
		}
	}
	
	return $xml->asXML();
}

/**
 * Returns an array by extracting a column or columns.
 *
 * @param array Array
 * @param mixed (string/array) Column name/s
 *
 * @return array Array formed by the extracted columns of every array iteration.
 */
function extract_column ($array, $column) {
	$column_is_arr = is_array($column);
	
	return array_map(function($item) use ($column_is_arr, $column) {
		if ($column_is_arr) {
			return array_reduce($column, function($carry, $col) use ($item) {
				$carry[$col] = $item[$col];
				return $item[$col];
			}, array());
		}
		else {
			return $item[$column];
		}
	}, $array);
}


function get_percentile($percentile, $array) {
	sort($array);
	$index = ($percentile / 100) * count($array);
	
	if (floor($index) == $index) {
		 $result = ($array[$index-1] + $array[$index]) / 2;
	}
	else {
		$result = $array[floor($index)];
	}
	
	return $result;
}

if (!function_exists('hex2bin')) {
	function hex2bin($data) {
		static $old;
		if ($old === null) {
			$old = version_compare(PHP_VERSION, '5.2', '<');
		}
		$isobj = false;
		if (is_scalar($data) || (($isobj = is_object($data)) && method_exists($data, '__toString'))) {
			if ($isobj && $old) {
				ob_start();
				echo $data;
				$data = ob_get_clean();
			}
			else {
				$data = (string) $data;
			}
		}
		else {
			trigger_error(__FUNCTION__.'() expects parameter 1 to be string, ' . gettype($data) . ' given', E_USER_WARNING);
			return;//null in this case
		}
		$len = strlen($data);
		if ($len % 2) {
			trigger_error(__FUNCTION__.'(): Hexadecimal input string must have an even length', E_USER_WARNING);
			return false;
		}
		if (strspn($data, '0123456789abcdefABCDEF') != $len) {
			trigger_error(__FUNCTION__.'(): Input string must be hexadecimal string', E_USER_WARNING);
			return false;
		}
		return pack('H*', $data);
	}
}


function get_refresh_time_array() {
	return array (
		'0' => __('Disable'),
		'5' => __('5 seconds'),
		'10' => __('10 seconds'),
		'15' => __('15 seconds'),
		'30' => __('30 seconds'),
		(string)SECONDS_1MINUTE => __('1 minute'),
		(string)SECONDS_2MINUTES => __('2 minutes'),
		(string)SECONDS_5MINUTES => __('5 minutes'),
		(string)SECONDS_15MINUTES => __('15 minutes'),
		(string)SECONDS_30MINUTES => __('30 minutes'),
		(string)SECONDS_1HOUR => __('1 hour'));
}

function date2strftime_format($date_format) {
	$replaces_list = array(
		'D' => '%a',
		'l' => '%A',
		'd' => '%d',
		'j' => '%e',
		'N' => '%u',
		'w' => '%w',
		'W' => '%W',
		'M' => '%b',
		'F' => '%B',
		'm' => '%m',
		'o' => '%G',
		'y' => '%y',
		'Y' => '%Y',
		'H' => '%H',
		'h' => '%I',
		'g' => '%l',
		'a' => '%P',
		'A' => '%p',
		'i' => '%M',
		's' => '%S',
		'u' => '%s',
		'O' => '%z',
		'T' => '%Z',
		'%' => '%%',
		'G' => '%k',
		);
	
	$return = "";
	
	//character to character because 
	// Replacement order gotcha
	//		http://php.net/manual/en/function.str-replace.php
	$chars = str_split($date_format);
	foreach ($chars as $c) {
		if (isset($replaces_list[$c])) {
			$return .= $replaces_list[$c];
		}
		else {
			$return .= $c;
		}
	}
	
	return $return;
}

function pandora_setlocale() {
	global $config;
	
	$replace_locale = array(
		'ca' => 'ca_ES',
		'de' => 'de_DE',
		'en_GB' => 'de',
		'es' => 'es_ES',
		'fr' => 'fr_FR',
		'it' => 'it_IT',
		'nl' => 'nl_BE',
		'pl' => 'pl_PL',
		'pt' => 'pt_PT',
		'pt_BR' => 'pt_BR',
		'sk' => 'sk_SK',
		'tr' => 'tr_TR',
		'cs' => 'cs_CZ',
		'el' => 'el_GR',
		'ru' => 'ru_RU',
		'ar' => 'ar_MA',
		'ja' => 'ja_JP.UTF-8',
		'zh_CN' => 'zh_CN',
		);
	
	$user_language = get_user_language($config['id_user']);
	
	setlocale(LC_ALL,
		str_replace(array_keys($replace_locale), $replace_locale, $user_language));
}

function update_config_token ($cfgtoken, $cfgvalue) {
	global $config;
	
	$delete = db_process_sql ("DELETE FROM tconfig WHERE token = '$cfgtoken'");
	$insert = db_process_sql ("INSERT INTO tconfig (token, value) VALUES ('$cfgtoken', '$cfgvalue')");
	
	if ($delete && $insert) {
		return true;
	}
	else {
		return false;
	}
}

function get_number_of_mr() {
	global $config;
	
	$dir = $config["homedir"]."/extras/mr";
	$mr_size = array();
	
	if (file_exists($dir) && is_dir($dir)) {
		if (is_readable($dir)) {
			$files = scandir($dir); // Get all the files from the directory ordered by asc
			
			if ($files !== false) {
				$pattern = "/^\d+\.sql$/";
				$sqlfiles = preg_grep($pattern, $files); // Get the name of the correct files
				$pattern = "/\.sql$/";
				$replacement = "";
				$sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles);
				
				foreach ($sqlfiles_num as $num) {
					$mr_size[] = $num;
				}
			}
		}
	}
	return $mr_size;
}

function remove_right_zeros ($value) {
	$is_decimal = explode(".", $value);
	if (isset($is_decimal[1])) {
		$value_to_return = rtrim($value, "0");
		return rtrim($value_to_return, ".");
	}
	else {
		return $value;
	}
}
?>
