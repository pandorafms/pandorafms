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
 * @package    Include
 * @subpackage Generic_Functions
 */

/**
 * Include the html and ui functions.
 */
require_once 'functions_html.php';
require_once 'functions_ui.php';
require_once 'functions_io.php';

/**
 * Check referer to avoid external attacks
 *
 * @return bool true if all is ok, false if referer is not equal to current web page
 */
// function check_referer() {
// global $config;
//
// If it is disabled the check referer security
// if (!$config["referer_security"])
// return true;
//
// $referer = '';
// if (isset($_SERVER['HTTP_REFERER'])) {
// $referer = $_SERVER['HTTP_REFERER'];
// }
//
// If refresh is performed then dont't check referer
// This is done due to problems with HTTP_REFERER var when metarefresh is performed
// if ($config["refr"] > 0)
// return true;
//
// Check if the referer have a port (for example when apache run in other port to 80)
// if (preg_match('/http(s?):\/\/.*:[0-9]*/', $referer) == 1) {
// $url = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $config["homeurl"];
// }
// else {
// $url = ui_get_full_url();
// $url = preg_replace('/http(s?):\/\//','',$url);
// }
//
// Remove protocol from referer
// $referer = preg_replace('/http(s?):\/\//','',$referer);
// $referer = preg_replace('/\?.*/','',$referer);
//
// if (strpos($url, $referer) === 0) {
// return true;
// }
// else {
// return false;
// }
// }
function https_is_running()
{
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
    ) {
        return true;
    }

    if (isset($_SERVER['HTTPS'])
        && ($_SERVER['HTTPS'] === true
        || $_SERVER['HTTPS'] == 'on')
    ) {
        return true;
    }

    return false;
}


/**
 * Cleans an object or an array and casts all values as integers
 *
 * @param mixed   $value String or array of strings to be cleaned
 * @param integer $min   If value is smaller than min it will return false
 * @param integer $max   if value is larger than max it will return false
 *
 * @return mixed The cleaned string. If an array was passed, the invalid values
 * will be removed
 */
function safe_int($value, $min=false, $max=false)
{
    if (is_array($value)) {
        foreach ($value as $key => $check) {
            $check = safe_int($check, $min, $max);
            if ($check !== false) {
                $value[$key] = $check;
            } else {
                unset($value[$key]);
            }
        }
    } else {
        $value = (int) $value;
        // Cast as integer
        if (($min !== false && $value < $min) || ($max !== false && $value > $max)) {
            // If it's smaller than min or larger than max return false
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
function output_clean_strict($string)
{
    return preg_replace('/[\|\@\$\%\/\(\)\=\?\*\&\#]/', '', $string);
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
function safe_url_extraclean($string, $default_string='')
{
    // Strip the string to 125 characters
    $string = substr($string, 0, 125);

    // Search for unwanted characters
    if (preg_match('/[^a-zA-Z0-9_\/\.\-]|(\/\/)|(\.\.)/', $string)) {
        return $default_string;
    }

    return $string;
}


/**
 * List files in a directory in the local path.
 *
 * @param string  $directory     Local path.
 * @param string  $stringSearch  String to match the values.
 * @param string  $searchHandler Pattern of files to match.
 * @param boolean $return        Whether to print or return the list.
 *
 * @return string he list of files if $return parameter is true.
 */
function list_files($directory, $stringSearch, $searchHandler, $return=false)
{
    $errorHandler = false;
    $result = [];
    if (! $directoryHandler = @opendir($directory)) {
        echo "<pre>\nerror: directory \"$directory\" doesn't exist!\n</pre>\n";
        return $errorHandler = true;
    }

    if ($searchHandler == 0) {
        while (false !== ($fileName = @readdir($directoryHandler))) {
            $result[$fileName] = $fileName;
        }
    }

    if ($searchHandler == 1) {
        while (false !== ($fileName = @readdir($directoryHandler))) {
            if ((@substr_count($fileName, $stringSearch) > 0) || (@substr_count($fileName, strtoupper($stringSearch)) > 0)) {
                $result[$fileName] = $fileName;
            }
        }
    }

    if (($errorHandler == true) && (@count($result) === 0)) {
        echo "<pre>\nerror: no filetype \"$fileExtension\" found!\n</pre>\n";
    } else {
        asort($result);
        if ($return === false) {
            echo "<pre>\n";
            print_r($result);
            echo "</pre>\n";
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
 * @param float   $number   Number to be rendered
 * @param integer $decimals numbers after comma to be shown. Default value: 1
 *
 * @return string A formatted number for use in output
 */
function format_numeric($number, $decimals=1)
{
    // Translate to float in case there are characters in the string so
    // fmod doesn't throw a notice
    $number = (float) $number;

    if ($number == 0) {
        return 0;
    }

    // Translators: This is separator of decimal point
    $dec_point = __('.');
    // Translators: This is separator of decimal point
    $thousands_sep = __(',');

    // If has decimals
    if (fmod($number, 1) > 0) {
        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }

    return number_format($number, 0, $dec_point, $thousands_sep);
}


/**
 * Render numeric data for a graph. It adds magnitude suffix to the number
 * (M for millions, K for thousands...). Base can be modified with divider.
 *
 * @param float   $number        Number to be rendered.
 * @param integer $decimals      Numbers after comma (default 1).
 * @param string  $dec_point     Decimal separator character (default .).
 * @param string  $thousands_sep Thousands separator character (default ,).
 * @param integer $divider       Number to divide the rendered number.
 * @param string  $sufix         Units of the multiple.
 *
 * @return string A string with the number and the multiplier
 */
function format_for_graph(
    $number,
    $decimals=1,
    $dec_point='.',
    $thousands_sep=',',
    $divider=1000,
    $sufix=''
) {
    $shorts = [
        '',
        'K',
        'M',
        'G',
        'T',
        'P',
        'E',
        'Z',
        'Y',
    ];
    $pos = 0;
    while ($number >= $divider) {
        // As long as the number can be divided by divider.
        $pos++;
        // Position in array starting with 0.
        $number = ($number / $divider);
    }

    // This will actually do the rounding and the decimals.
    return remove_right_zeros(format_numeric($number, $decimals)).$shorts[$pos].$sufix;
}


function human_milliseconds_to_string($seconds)
{
    $ret = '';

    // get the days
    $days = intval(intval($seconds) / (360000 * 24));
    if ($days > 0) {
        $ret .= "$days days ";
    }

    // get the hours
    $hours = ((intval($seconds) / 360000) % 24);
    if ($hours > 0) {
        $ret .= "$hours hours ";
    }

    // get the minutes
    $minutes = ((intval($seconds) / 6000) % 60);
    if ($minutes > 0) {
        $ret .= "$minutes minutes ";
    }

    // get the seconds
    $seconds = (intval($seconds / 100) % 60);
    if ($seconds > 0) {
        $ret .= "$seconds seconds";
    }

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
function format_integer_round($number, $rounder=5)
{
    return ((int) ($number / $rounder + 0.5) * $rounder);
}


/**
 * INTERNAL: Use ui_print_timestamp for output Get a human readable string of
 * the difference between current time and given timestamp.
 *
 * TODO: Make sense out of all these time functions and stick with 2 or 3
 *
 * @param integer $timestamp Unixtimestamp to compare with current time.
 * @param string  $units     The type of unit, by default 'large'.
 *
 * @return string A human readable string of the diference between current
 * time and a given timestamp.
 */
function human_time_comparation($timestamp, $units='large')
{
    global $config;

    if (!is_numeric($timestamp)) {
        $timestamp = time_w_fixed_tz($timestamp);
    }

    $seconds = (get_system_time() - $timestamp);

    // $seconds could be negative, because get_system_time() could return cached value
    // (that might be the time a session begins at).
    // So negative values are to be rounded off to 'NOW'.
    if ($seconds < 0) {
        $seconds = 0;
    }

    return human_time_description_raw($seconds, false, $units);
}


/**
 * This function gets the time from either system or sql based on preference and returns it
 *
 * @return integer Unix timestamp
 */
function get_system_time()
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_get_system_time();

            break;
        case 'postgresql':
        return postgresql_get_system_time();

            break;
        case 'oracle':
        return oracle_get_system_time();

            break;
    }
}


/**
 * This function provide the user language configuration if is not default, otherwise return the system language
 *
 * @param string $id_user
 *
 * @return string user active language code
 */
function get_user_language($id_user=null)
{
    global $config;

    $quick_language = get_parameter('quick_language_change', 0);

    if ($quick_language) {
        $language = get_parameter('language', 0);

        if (defined('METACONSOLE')) {
            if ($id_user == null) {
                $id_user = $config['id_user'];
            }

            if ($language !== 0) {
                update_user($id_user, ['language' => $language]);
            }
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
        $userinfo = get_user_info($id_user);
        if ($userinfo['language'] != 'default') {
            return $userinfo['language'];
        }
    }

    return $config['language'];
}


/**
 * This function get the user language and set it on the system
 */
function set_user_language()
{
    global $config;
    global $l10n;

    $l10n = null;
    $user_language = get_user_language();

    if (file_exists('./include/languages/'.$user_language.'.mo')) {
        $l10n = new gettext_reader(new CachedFileReader('./include/languages/'.$user_language.'.mo'));
        $l10n->load_tables();
    }
}


/**
 * INTERNAL (use ui_print_timestamp for output): Transform an amount of time in seconds into a human readable
 * strings of minutes, hours or days.
 *
 * @param integer $seconds Seconds elapsed time
 * @param integer $exactly If it's true, return the exactly human time
 * @param string  $units   The type of unit, by default 'large'.
 *
 * @return string A human readable translation of minutes.
 */
function human_time_description_raw($seconds, $exactly=false, $units='large')
{
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

    if (empty($seconds)) {
        return $nowString;
        // slerena 25/03/09
        // Most times $seconds is empty is because last contact is current date
        // Put here "uknown" or N/A or something similar is not a good idea
    }

    if ($exactly) {
        $returnDate = '';

        $years = floor($seconds / SECONDS_1YEAR);

        if ($years != 0) {
            $seconds = ($seconds - ($years * SECONDS_1YEAR));

            $returnDate .= "$years $yearsString ";
        }

        $months = floor($seconds / SECONDS_1MONTH);

        if ($months != 0) {
            $seconds = ($seconds - ($months * SECONDS_1MONTH));

            $returnDate .= "$months $monthsString ";
        }

        $days = floor($seconds / SECONDS_1DAY);

        if ($days != 0) {
            $seconds = ($seconds - ($days * SECONDS_1DAY));

            $returnDate .= "$days $daysString ";
        }

        $returnTime = '';

        $hours = floor($seconds / SECONDS_1HOUR);

        if ($hours != 0) {
            $seconds = ($seconds - ($hours * SECONDS_1HOUR));

            $returnTime .= "$hours $hoursString ";
        }

        $mins = floor($seconds / 60);

        if ($mins != 0) {
            $seconds = ($seconds - ($mins * 60));

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
        } else {
            return $return;
        }
    }

    if ($seconds < SECONDS_1MINUTE) {
        return format_numeric($seconds, 0).' '.$secondsString;
    }

    if ($seconds < SECONDS_1HOUR) {
        $minutes = floor($seconds / 60);
        $seconds = ($seconds % SECONDS_1MINUTE);
        if ($seconds == 0) {
            return $minutes.' '.$minutesString;
        }

        $seconds = sprintf('%02d', $seconds);
        return $minutes.' '.$minutesString.' '.$seconds.' '.$secondsString;
    }

    if ($seconds < SECONDS_1DAY) {
        return format_numeric(($seconds / SECONDS_1HOUR), 0).' '.$hoursString;
    }

    if ($seconds < SECONDS_1MONTH) {
        return format_numeric(($seconds / SECONDS_1DAY), 0).' '.$daysString;
    }

    if ($seconds < SECONDS_6MONTHS) {
        return format_numeric(($seconds / SECONDS_1MONTH), 0).' '.$monthsString;
    }

    return '+6 '.$monthsString;
}


/**
 * INTERNAL (use ui_print_timestamp for output): Transform an amount of time in seconds into a human readable
 * strings of minutes, hours or days. Used in alert views.
 *
 * @param integer $seconds Seconds elapsed time
 * @param integer $exactly If it's true, return the exactly human time
 * @param string  $units   The type of unit, by default 'large'.
 *
 * @return string A human readable translation of minutes.
 */
function human_time_description_alerts($seconds, $exactly=false, $units='tiny')
{
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

    if (empty($seconds)) {
        return $nowString;
        // slerena 25/03/09
        // Most times $seconds is empty is because last contact is current date
        // Put here "uknown" or N/A or something similar is not a good idea
    }

    if ($exactly) {
        $returnDate = '';

        $years = floor($seconds / SECONDS_1YEAR);

        if ($years != 0) {
            $seconds = ($seconds - ($years * SECONDS_1YEAR));

            $returnDate .= "$years $yearsString ";
        }

        $months = floor($seconds / SECONDS_1MONTH);

        if ($months != 0) {
            $seconds = ($seconds - ($months * SECONDS_1MONTH));

            $returnDate .= "$months $monthsString ";
        }

        $days = floor($seconds / SECONDS_1DAY);

        if ($days != 0) {
            $seconds = ($seconds - ($days * SECONDS_1DAY));

            $returnDate .= "$days $daysString ";
        }

        $returnTime = '';

        $hours = floor($seconds / SECONDS_1HOUR);

        if ($hours != 0) {
            $seconds = ($seconds - ($hours * SECONDS_1HOUR));

            $returnTime .= "$hours $hoursString ";
        }

        $mins = floor($seconds / SECONDS_1MINUTE);

        if ($mins != 0) {
            $seconds = ($seconds - ($mins * SECONDS_1MINUTE));

            if ($hours == 0) {
                $returnTime .= "$mins $minutesString ";
            } else {
                $returnTime = sprintf('%02d', $hours)."$hoursString".sprintf('%02d', $mins)."$minutesString";
            }
        }

        if ($seconds != 0) {
            if ($hours == 0) {
                $returnTime .= "$seconds $secondsString ";
            } else {
                $returnTime = sprintf('%02d', $hours)."$hoursString".sprintf('%02d', $mins)."$minutesString".sprintf('%02d', $seconds)."$secondsString";
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
        } else {
            return $return;
        }
    }

    if ($seconds < 60) {
        return format_numeric($seconds, 0).' '.$secondsString;
    }

    if ($seconds < SECONDS_1HOUR) {
        $minutes = floor($seconds / SECONDS_1MINUTE);
        $seconds = ($seconds % SECONDS_1MINUTE);
        if ($seconds == 0) {
            return $minutes.' '.$minutesString;
        }

        $seconds = sprintf('%02d', $seconds);
        return $minutes.' '.$minutesString.' '.$seconds.' '.$secondsString;
    }

    if ($seconds < SECONDS_1DAY) {
        return format_numeric(($seconds / SECONDS_1HOUR), 0).' '.$hoursString;
    }

    if ($seconds < SECONDS_1MONTH) {
        return format_numeric(($seconds / SECONDS_1DAY), 0).' '.$daysString;
    }

    if ($seconds < SECONDS_6MONTHS) {
        return format_numeric(($seconds / SECONDS_1MONTH), 0).' '.$monthsString;
    }

    return '+6 '.$monthsString;
}


/**
 * @deprecated Get current time minus some seconds. (Do your calculations yourself on unix timestamps)
 *
 * @param integer $seconds Seconds to substract from current time.
 *
 * @return integer The current time minus the seconds given.
 */
function human_date_relative($seconds)
{
    $ahora = date('Y/m/d H:i:s');
    $ahora_s = date('U');
    $ayer = date('Y/m/d H:i:s', ($ahora_s - $seconds));

    return $ayer;
}


/**
 * @deprecated Use ui_print_timestamp instead
 */
function render_time($lapse)
{
    $myhour = intval(($lapse * 30) / 60);
    if ($myhour == 0) {
        $output = '00';
    } else {
        $output = $myhour;
    }

    $output .= ':';
    $mymin = fmod(($lapse * 30), 60);
    if ($mymin == 0) {
        $output .= '00';
    } else {
        $output .= $mymin;
    }

    return $output;
}


/**
 * Get a parameter from a request between values.
 *
 * It checks first on post request, if there were nothing defined, it
 * would return get request
 *
 * @param string $name    key of the parameter in the $_POST or $_GET array
 * @param array  $values  The list of values that parameter to be.
 * @param mixed  $default default value if the key wasn't found
 *
 * @return mixed Whatever was in that parameter, cleaned however
 */
function get_parameterBetweenListValues($name, $values, $default)
{
    $parameter = $default;
    // POST has precedence
    if (isset($_POST[$name])) {
        $parameter = get_parameter_post($name, $default);
    }

    if (isset($_GET[$name])) {
        $parameter = get_parameter_get($name, $default);
    }

    foreach ($values as $value) {
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
 * @param string $name    key of the parameter in the $_POST or $_GET array
 * @param mixed  $default default value if the key wasn't found
 *
 * @return mixed Whatever was in that parameter, cleaned however
 */


function get_parameter_checkbox($name, $default='')
{
    $sent = get_parameter($name.'_sent', 0);

    // If is not sent, return the default
    if (!$sent) {
        return $default;
    }

    // If sent, get parameter normally
    return get_parameter($name, 0);
}


/**
 * Transforms a swicth data (on - non present) to a int value.
 *
 * @param string $name    Variable, switch name.
 * @param string $default Default value.
 *
 * @return integer Value, 1 on, 0 off.
 */
function get_parameter_switch($name, $default='')
{
    $data = get_parameter($name, null);

    if ($data === null) {
        return (isset($default) ? $default : 0);
    } else if ($data == 'on') {
        return 1;
    }

    // Return value assigned to switch.
    return $data;
}


function get_cookie($name, $default='')
{
    if (isset($_COOKIE[$name])) {
        return $_COOKIE[$name];
    } else {
        return $default;
    }
}


function set_cookie($name, $value)
{
    if (is_null($value)) {
        unset($_COOKIE[$value]);
        setcookie($name, null, -1, '/');
    } else {
        setcookie($name, $value);
    }
}


/**
 * Returns database ORDER clause from datatables AJAX call.
 *
 * @param boolean $as_array Return as array or as string.
 *
 * @return string Order or empty.
 */
function get_datatable_order($as_array=false)
{
    $order = get_parameter('order');

    if (is_array($order)) {
        $column = $order[0]['column'];
        $direction = $order[0]['dir'];
    }

    if (!isset($column) || !isset($direction)) {
        return '';
    }

    $columns = get_parameter('columns');

    if (is_array($columns)) {
        $column_name = $columns[$column]['data'];
    }

    if (!isset($column_name)) {
        return '';
    }

    if ($as_array) {
        return [
            'direction' => $direction,
            'field'     => $column_name,
        ];
    }

    return $column_name.' '.$direction;
}


/**
 * Get a parameter from a request.
 *
 * It checks first on post request, if there were nothing defined, it
 * would return get request
 *
 * @param string $name    key of the parameter in the $_POST or $_GET array
 * @param mixed  $default default value if the key wasn't found
 *
 * @return mixed Whatever was in that parameter, cleaned however
 */
function get_parameter($name, $default='')
{
    // POST has precedence
    if (isset($_POST[$name])) {
        return get_parameter_post($name, $default);
    }

    if (isset($_GET[$name])) {
        return get_parameter_get($name, $default);
    }

    return $default;
}


/**
 * Get a parameter from a get request.
 *
 * @param string $name    key of the parameter in the $_GET array
 * @param mixed  $default default value if the key wasn't found
 *
 * @return mixed Whatever was in that parameter, cleaned however
 */
function get_parameter_get($name, $default='')
{
    if ((isset($_GET[$name])) && ($_GET[$name] != '')) {
        return io_safe_input($_GET[$name]);
    }

    return $default;
}


/**
 * Get a parameter from a post request.
 *
 * @param string $name    key of the parameter in the $_POST array
 * @param mixed  $default default value if the key wasn't found
 *
 * @return mixed Whatever was in that parameter, cleaned however
 */
function get_parameter_post($name, $default='')
{
    if ((isset($_POST[$name])) && ($_POST[$name] != '')) {
        return io_safe_input($_POST[$name]);
    }

    return $default;
}


/**
 * Get name of a priority value.
 *
 * @param integer $priority Priority value
 *
 * @return string Name of given priority
 */
function get_alert_priority($priority=0)
{
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
 * @param array $row The array of boolean values to check. They should have monday -> sunday in boolean
 *
 * @return string Translated names of days
 */
function get_alert_days($row)
{
    global $config;
    $days_output = '';

    $check = ($row['monday'] + $row['tuesday'] + $row['wednesday'] + $row['thursday'] + $row['friday'] + $row['saturday'] + $row['sunday']);

    if ($check == 7) {
        return __('All');
    } else if ($check == 0) {
        return __('None');
    }

    if ($row['monday'] != 0) {
        $days_output .= __('Mon').' ';
    }

    if ($row['tuesday'] != 0) {
        $days_output .= __('Tue').' ';
    }

    if ($row['wednesday'] != 0) {
        $days_output .= __('Wed').' ';
    }

    if ($row['thursday'] != 0) {
        $days_output .= __('Thu').' ';
    }

    if ($row['friday'] != 0) {
        $days_output .= __('Fri').' ';
    }

    if ($row['saturday'] != 0) {
        $days_output .= __('Sat').' ';
    }

    if ($row['sunday'] != 0) {
        $days_output .= __('Sun');
    }

    if ($check > 1) {
        return str_replace(' ', ', ', $days_output);
    }

    return rtrim($days_output);
}


/**
 * Gets the alert times values and returns them as string
 *
 * @param array Array with time_from and time_to in it's keys
 *
 * @return string A string with the concatenated values
 */
function get_alert_times($row2)
{
    if ($row2['time_from']) {
        $time_from_table = $row2['time_from'];
    } else {
        $time_from_table = __('N/A');
    }

    if ($row2['time_to']) {
        $time_to_table = $row2['time_to'];
    } else {
        $time_to_table = __('N/A');
    }

    if ($time_to_table == $time_from_table) {
        return __('N/A');
    }

    return substr($time_from_table, 0, 5).' - '.substr($time_to_table, 0, 5);
}


/**
 * Checks if a module is of type "data"
 *
 * @param string $module_name Module name to check.
 *
 * @return boolean True if the module is of type "data"
 */
function is_module_data($module_name)
{
    return preg_match('/\_data$/', $module_name);
}


/**
 * Checks if a module is of type "proc"
 *
 * @param string $module_name Module name to check.
 *
 * @return boolean true if the module is of type "proc"
 */
function is_module_proc($module_name)
{
    return preg_match('/\_proc$/', $module_name);
}


/**
 * Checks if a module is of type "inc"
 *
 * @param string $module_name Module name to check.
 *
 * @return boolean true if the module is of type "inc"
 */
function is_module_inc($module_name)
{
    return preg_match('/\_inc$/', $module_name);
}


/**
 * Checks if a module is of type "string"
 *
 * @param string $module_name Module name to check.
 *
 * @return boolean true if the module is of type "string"
 */
function is_module_data_string($module_name)
{
    return preg_match('/\_string$/', $module_name);
}


/**
 * Checks if a module data is uncompressed according
 * to the module type.
 *
 * @param string module_type Type of the module.
 *
 * @return boolean true if the module data is uncompressed.
 */
function is_module_uncompressed($module_type)
{
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
function get_event_types($id_type=false)
{
    global $config;

    $types = [];

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
    } else {
        return $types[$id_type];
    }
}


/**
 * Get an array with all the priorities.
 *
 * @return array An array with all the priorities.
 */
function get_priorities($priority_param=false)
{
    global $config;

    $priorities = [];
    $priorities[EVENT_CRIT_MAINTENANCE] = __('Maintenance');
    $priorities[EVENT_CRIT_INFORMATIONAL] = __('Informational');
    $priorities[EVENT_CRIT_NORMAL] = __('Normal');
    $priorities[EVENT_CRIT_MINOR] = __('Minor');
    $priorities[EVENT_CRIT_WARNING] = __('Warning');
    $priorities[EVENT_CRIT_MAJOR] = __('Major');
    $priorities[EVENT_CRIT_CRITICAL] = __('Critical');
    $priorities[EVENT_CRIT_WARNING_OR_CRITICAL] = __('Warning').'/'.__('Critical');
    $priorities[EVENT_CRIT_NOT_NORMAL] = __('Not normal');
    $priorities[EVENT_CRIT_OR_NORMAL] = __('Critical').'/'.__('Normal');

    foreach ($priorities as $key => $priority) {
        $priorities[$key] = ui_print_truncate_text($priority, GENERIC_SIZE_TEXT, false, true, false);
    }

    if ($priority_param === false) {
        return $priorities;
    } else {
        return $priorities[$priority_param];
    }
}


/**
 * Get priority name from priority value.
 *
 * @param integer $priority value (integer) as stored eg. in database.
 *
 * @return string priority string.
 */
function get_priority_name($priority)
{
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
function get_priority_class($priority)
{
    switch ($priority) {
        case 0:
        return 'datos_blue';

            break;
        case 1:
        return 'datos_grey';

            break;
        case 2:
        return 'datos_green';

            break;
        case 3:
        return 'datos_yellow';

            break;
        case 4:
        return 'datos_red';

            break;
        case 5:
        return 'datos_pink';

            break;
        case 6:
        return 'datos_brown';

            break;
        default:
        return 'datos_grey';
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
function get_priority_style($priority_class)
{
    switch ($priority_class) {
        case 'datos_blue':
            $style_css_criticity = 'background-color: '.COL_MAINTENANCE.'; color: #FFFFFF;';
        break;

        case 'datos_grey':
            $style_css_criticity = 'background-color: '.COL_UNKNOWN.'; color: #FFFFFF;';
        break;

        case 'datos_green':
            $style_css_criticity = 'background-color: '.COL_NORMAL.'; color: #FFFFFF;';
        break;

        case 'datos_yellow':
            $style_css_criticity = 'background-color: '.COL_WARNING.';';
        break;

        case 'datos_red':
            $style_css_criticity = 'background-color: '.COL_CRITICAL.'; color: #FFFFFF;';
        break;

        case 'datos_pink':
            $style_css_criticity = 'background-color: '.COL_MINOR.';';
        break;

        case 'datos_brown':
            $style_css_criticity = 'background-color: '.COL_MAJOR.'; color: #FFFFFF;';
        break;

        case 'datos_grey':
        default:
            $style_css_criticity = 'background-color: '.COL_UNKNOWN.'; color: #FFFFFF;';
        break;
    }

    return $style_css_criticity;
}


/**
 * Check if the enterprise version is installed.
 *
 * @return boolean If it is installed return true, otherwise return false.
 */
function enterprise_installed()
{
    $return = false;

    // Load enterprise extensions.
    if (defined('DESTDIR')) {
        return $return;
    }

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
function license_free()
{
    $return = true;

    $pandora_license = db_get_value('value', 'tupdate_settings', '`key`', 'customer_key');
    if ($pandora_license !== 'PANDORA-FREE') {
        $return = false;
    }

    return $return;
}


/**
 * TODO: Document enterprise functions
 */
function enterprise_hook($function_name, $parameters=false)
{
    if (function_exists($function_name)) {
        if (!is_array($parameters)) {
            return call_user_func($function_name);
        }

        return call_user_func_array($function_name, $parameters);
    }

    return ENTERPRISE_NOT_HOOK;
}


/**
 * TODO: Document enterprise functions
 */
function enterprise_include($filename)
{
    global $config;

    // Load enterprise extensions.
    if (defined('DESTDIR')) {
        $destdir = DESTDIR;
    } else {
        $destdir = '';
    }

    $filepath = realpath($destdir.$config['homedir'].'/'.ENTERPRISE_DIR.'/'.$filename);

    if ($filepath === false) {
        return ENTERPRISE_NOT_HOOK;
    }

    if (strncmp($config['homedir'], $filepath, strlen($config['homedir'])) != 0) {
        return ENTERPRISE_NOT_HOOK;
    }

    if (file_exists($filepath)) {
        include $filepath;
        return true;
    }

    return ENTERPRISE_NOT_HOOK;
}


/**
 * Includes a file from enterprise section.
 *
 * @param string $filename Target file.
 *
 * @return mixed Result code.
 */
function enterprise_include_once($filename)
{
    global $config;

    // Load enterprise extensions.
    if (defined('DESTDIR')) {
        $destdir = DESTDIR;
    } else {
        $destdir = '';
    }

    $filepath = realpath($config['homedir'].'/'.ENTERPRISE_DIR.'/'.$filename);

    if ($filepath === false) {
        return ENTERPRISE_NOT_HOOK;
    }

    if (strncmp($config['homedir'], $filepath, strlen($config['homedir'])) != 0) {
        return ENTERPRISE_NOT_HOOK;
    }

    if (file_exists($filepath)) {
        include_once $filepath;
        return true;
    }

    return ENTERPRISE_NOT_HOOK;
}


// These are wrapper functions for PHP. Don't document them
if (!function_exists('mb_strtoupper')) {
    // Multibyte not loaded - use wrapper functions
    // You should really load multibyte especially for foreign charsets


    /**
     * @ignore
     */
    function mb_strtoupper($string, $encoding=false)
    {
            return strtoupper($string);
    }


    /**
     * @ignore
     */
    function mb_strtolower($string, $encoding=false)
    {
        return strtoupper($string);
    }


    /**
     * @ignore
     */
    function mb_substr($string, $start, $length, $encoding=false)
    {
        return substr($string, $start, $length);
    }


    /**
     * @ignore
     */
    function mb_strlen($string, $encoding=false)
    {
        return strlen($string);
    }


    /**
     * @ignore
     */
    function mb_strimwidth($string, $start, $length, $trimmarker=false, $encoding=false)
    {
        return substr($string, $start, $length);
    }


}


/**
 * Put quotes if magic_quotes protection
 *
 * @param string Text string to be protected with quotes if magic_quotes protection is disabled
 */
function safe_sql_string($string)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
        return mysql_safe_sql_string($string);

            break;
        case 'postgresql':
        return postgresql_safe_sql_string($string);

            break;
        case 'oracle':
        return oracle_safe_sql_string($string);

            break;
    }
}


/**
 * Verifies if current Pandora FMS installation is a Metaconsole.
 *
 * @return boolean True metaconsole installation, false if not.
 */
function is_metaconsole()
{
    global $config;
    return (bool) $config['metaconsole'];
}


/**
 * Check if current Pandora FMS installation has joined a Metaconsole env.
 *
 * @return boolean True joined, false if not.
 */
function has_metaconsole()
{
    global $config;
    return (bool) $config['node_metaconsole'] && (bool) $config['metaconsole_node_id'];
}


/**
 * @brief Check if there is management operations are allowed in current context
 * (node // meta)
 *
 * @return boolean
 */
function is_management_allowed($hkey='')
{
    global $config;
    return ( (is_metaconsole() && $config['centralized_management'])
        || (!is_metaconsole() && !$config['centralized_management'])
        || (!is_metaconsole() && $config['centralized_management']) && $hkey == generate_hash_to_api());
}


/**
 * @brief Check if there is centralized management in metaconsole environment.
 *             Usefull to display some policy features on metaconsole.
 *
 * @return boolean
 */
function is_central_policies()
{
    global $config;
    return is_metaconsole() && $config['centralized_management'];
}


/**
 * @brief Check if there is centralized management in node environment. Usefull
 *             to reduce the policy functionallity on nodes.
 *
 * @return boolean
 */
function is_central_policies_on_node()
{
    global $config;
    return (!is_metaconsole()) && $config['centralized_management'];
}


/**
 * Checks if current execution is under an AJAX request.
 *
 * This functions checks if an 'AJAX' constant is defined
 *
 * @return boolean True if the request was done via AJAX. False otherwise
 */
function is_ajax()
{
    return defined('AJAX');
}


/**
 * Check if a code is an error code
 *
 * @param int code of an operation. Tipically the id of a module, agent... or a code error
 *
 * @return boolean true if a result code is an error or false otherwise
 */
function is_error($code)
{
    if ($code !== true and ($code <= ERR_GENERIC || $code === false)) {
        return true;
    } else {
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
function index_array($array, $index='id', $value='name')
{
    $retval = [];

    if (! is_array($array)) {
        return $retval;
    }

    foreach ($array as $index_array => $element) {
        if (!is_null($index)) {
            if (! isset($element[$index])) {
                continue;
            }
        }

        if ($value === false) {
            $retval[$element[$index]] = $element;
            continue;
        }

        if (! isset($element[$value])) {
            continue;
        }

        if (is_null($index)) {
            $retval[$index_array] = $element[$value];
        } else {
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
 * @param  int Id of module type
 * @return string Graph type, as used in stat_win.php (Graphs launcher)
 */


function return_graphtype($id_module_type)
{
    switch ($id_module_type) {
        case 3:
        case 10:
        case 17:
        case 23:
        return 'string';

            break;
        case 2:
        case 6:
        case 21:
        case 18:
        case 9:
        case 31:
        case 100:
        return 'boolean';

            break;
        case 24:
        return 'log4x';

            break;
        default:
        return 'sparse';
            break;
    }
}


/**
 * Translate the key in assoc array to numeric offset.
 *
 * @param array $array The array to return the offset.
 * @param mixed $key   The key to translate to offset.
 *
 * @return mixed The offset or false is fail.
 */
function array_key_to_offset($array, $key)
{
    $offset = array_search($key, array_keys($array));

    return $offset;
}


/**
 * Undocumented function
 *
 * @param array $arguments Following format:
 *  [
 *   'ip_target'
 *   'snmp_version'
 *   'snmp_community'
 *   'snmp3_auth_user'
 *   'snmp3_security_level'
 *   'snmp3_auth_method'
 *   'snmp3_auth_pass'
 *   'snmp3_privacy_method'
 *   'snmp3_privacy_pass'
 *   'quick_print'
 *   'base_oid'
 *   'snmp_port'
 *   'server_to_exec'
 *   'extra_arguments'
 *   'format'
 *  ]
 *
 * @return array SNMP result.
 */
function get_h_snmpwalk(array $arguments)
{
    return get_snmpwalk(
        $arguments['ip_target'],
        $arguments['snmp_version'],
        isset($arguments['snmp_community']) ? $arguments['snmp_community'] : '',
        isset($arguments['snmp3_auth_user']) ? $arguments['snmp3_auth_user'] : '',
        isset($arguments['snmp3_security_level']) ? $arguments['snmp3_security_level'] : '',
        isset($arguments['snmp3_auth_method']) ? $arguments['snmp3_auth_method'] : '',
        isset($arguments['snmp3_auth_pass']) ? $arguments['snmp3_auth_pass'] : '',
        isset($arguments['snmp3_privacy_method']) ? $arguments['snmp3_privacy_method'] : '',
        isset($arguments['snmp3_privacy_pass']) ? $arguments['snmp3_privacy_pass'] : '',
        isset($arguments['quick_print']) ? $arguments['quick_print'] : 0,
        isset($arguments['base_oid']) ? $arguments['base_oid'] : '',
        isset($arguments['snmp_port']) ? $arguments['snmp_port'] : '',
        isset($arguments['server_to_exec']) ? $arguments['server_to_exec'] : 0,
        isset($arguments['extra_arguments']) ? $arguments['extra_arguments'] : '',
        isset($arguments['format']) ? $arguments['format'] : '-Oa'
    );
}


/**
 * Make a snmpwalk and return it.
 *
 * @param string  $ip_target            The target address.
 * @param string  $snmp_version         Version of the snmp: 1,2,2c or 3.
 * @param string  $snmp_community       Snmp_community.
 * @param string  $snmp3_auth_user      Snmp3_auth_user.
 * @param string  $snmp3_security_level Snmp3_security_level.
 * @param string  $snmp3_auth_method    Snmp3_auth_method.
 * @param string  $snmp3_auth_pass      Snmp3_auth_pass.
 * @param string  $snmp3_privacy_method Snmp3_privacy_method.
 * @param string  $snmp3_privacy_pass   Snmp3_privacy_pass.
 * @param integer $quick_print          To get all details 0, 1: only value.
 * @param string  $base_oid             Base_oid.
 * @param string  $snmp_port            Snmp_port.
 * @param integer $server_to_exec       Server_to_exec.
 * @param string  $extra_arguments      Extra_arguments.
 * @param string  $format               Format to apply, for instance, to
 *                                      retrieve hex-dumps: --hexOutputLength.
 *
 * @return array SNMP result.
 */
function get_snmpwalk(
    $ip_target,
    $snmp_version,
    $snmp_community='',
    $snmp3_auth_user='',
    $snmp3_security_level='',
    $snmp3_auth_method='',
    $snmp3_auth_pass='',
    $snmp3_privacy_method='',
    $snmp3_privacy_pass='',
    $quick_print=0,
    $base_oid='',
    $snmp_port='',
    $server_to_exec=0,
    $extra_arguments='',
    $format='-Oa'
) {
    global $config;

    // Note: quick_print is ignored
    // Fix for snmp port
    if (!empty($snmp_port)) {
        $ip_target = $ip_target.':'.$snmp_port;
    }

    // Escape the base OID
    if ($base_oid != '') {
        $base_oid = escapeshellarg($base_oid);
    }

    if (empty($config['snmpwalk'])) {
        switch (PHP_OS) {
            case 'FreeBSD':
                $snmpwalk_bin = '/usr/local/bin/snmpwalk';
            break;

            case 'NetBSD':
                $snmpwalk_bin = '/usr/pkg/bin/snmpwalk';
            break;

            default:
                $snmpwalk_bin = 'snmpwalk';
            break;
        }
    } else {
        $snmpwalk_bin = $config['snmpwalk'];
    }

    switch (PHP_OS) {
        case 'WIN32':
        case 'WINNT':
        case 'Windows':
            $error_redir_dir = 'NUL';
        break;

        default:
            $error_redir_dir = '/dev/null';
        break;
    }

    $output = [];
    $rc = 0;
    switch ($snmp_version) {
        case '3':
            switch ($snmp3_security_level) {
                case 'authNoPriv':
                    $command_str = $snmpwalk_bin.' -m ALL '.$format.' '.$extra_arguments.' -v 3'.' -u '.escapeshellarg($snmp3_auth_user).' -A '.escapeshellarg($snmp3_auth_pass).' -l '.escapeshellarg($snmp3_security_level).' -a '.escapeshellarg($snmp3_auth_method).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
                break;

                case 'noAuthNoPriv':
                    $command_str = $snmpwalk_bin.' -m ALL '.$format.' '.$extra_arguments.' -v 3'.' -u '.escapeshellarg($snmp3_auth_user).' -l '.escapeshellarg($snmp3_security_level).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
                break;

                default:
                    $command_str = $snmpwalk_bin.' -m ALL '.$format.' '.$extra_arguments.' -v 3'.' -u '.escapeshellarg($snmp3_auth_user).' -A '.escapeshellarg($snmp3_auth_pass).' -l '.escapeshellarg($snmp3_security_level).' -a '.escapeshellarg($snmp3_auth_method).' -x '.escapeshellarg($snmp3_privacy_method).' -X '.escapeshellarg($snmp3_privacy_pass).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
                break;
            }
        break;

        case '2':
        case '2c':
        case '1':
        default:
            $command_str = $snmpwalk_bin.' -m ALL '.$extra_arguments.' '.$format.' -v '.escapeshellarg($snmp_version).' -c '.escapeshellarg(io_safe_output($snmp_community)).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
        break;
    }

    if (enterprise_installed()) {
        if ($server_to_exec != 0) {
            $server_data = db_get_row('tserver', 'id_server', $server_to_exec);
            exec('ssh pandora_exec_proxy@'.$server_data['ip_address'].' "'.$command_str.'"', $output, $rc);
        } else {
            exec($command_str, $output, $rc);
        }
    } else {
        exec($command_str, $output, $rc);
    }

    // Parse the output of snmpwalk.
    $snmpwalk = [];
    foreach ($output as $line) {
        // Separate the OID from the value.
        if (strpos($format, 'q') === false) {
            $full_oid = explode(' = ', $line, 2);
        } else {
            $full_oid = explode(' ', $line, 2);
        }

        if (isset($full_oid[1])) {
            $snmpwalk[$full_oid[0]] = $full_oid[1];
        }
    }

    return $snmpwalk;
}


/**
 * Copy from:
 * http://stackoverflow.com/questions/1605844/imagettfbbox-returns-wrong-dimensions-when-using-space-characters-inside-text
 */
function calculateTextBox($font_size, $font_angle, $font_file, $text)
{
    $box = imagettfbbox($font_size, $font_angle, $font_file, $text);

    $min_x = min([$box[0], $box[2], $box[4], $box[6]]);
    $max_x = max([$box[0], $box[2], $box[4], $box[6]]);
    $min_y = min([$box[1], $box[3], $box[5], $box[7]]);
    $max_y = max([$box[1], $box[3], $box[5], $box[7]]);

    return [
        'left'   => ($min_x >= -1) ? -abs($min_x + 1) : abs($min_x + 2),
        'top'    => abs($min_y),
        'width'  => ($max_x - $min_x),
        'height' => ($max_y - $min_y),
        'box'    => $box,
    ];
}


/**
 * Convert a string to an image
 *
 * @param string $ip_target The target address.
 *
 * @return array SNMP result.
 */
function string2image(
    $string,
    $width,
    $height,
    $fontsize=3,
    $degrees='0',
    $bgcolor='#FFFFFF',
    $textcolor='#000000',
    $padding_left=4,
    $padding_top=1,
    $home_url=''
) {
    global $config;

    $string = str_replace('#', '', $string);

    // Set the size of image from the size of text
    if ($width === false) {
        $size = calculateTextBox($fontsize, 0, $config['fontpath'], $string);

        $fix_value = (1 * $fontsize);
        // Fix the imagettfbbox cut the tail of "p" character.
        $width = ($size['width'] + $padding_left + $fix_value);
        $height = ($size['height'] + $padding_top + $fix_value);

        $padding_top = ($padding_top + $fix_value);
    }

    $im = imagecreate($width, $height);
    $bgrgb = html_html2rgb($bgcolor);
    $bgc = imagecolorallocate($im, $bgrgb[0], $bgrgb[1], $bgrgb[2]);
    // Set the string
    $textrgb = html_html2rgb($textcolor);
    imagettftext(
        $im,
        $fontsize,
        0,
        $padding_left,
        ($height - $padding_top),
        imagecolorallocate($im, $textrgb[0], $textrgb[1], $textrgb[2]),
        $config['fontpath'],
        $string
    );
    // imagestring($im, $fontsize, $padding_left, $padding_top, $string, ImageColorAllocate($im,$textrgb[0],$textrgb[1],$textrgb[2]));
    // Rotates the image
    $rotated = imagerotate($im, $degrees, 0);

    // Cleaned string file name (as the slash)
    $stringFile = str_replace('/', '___', $string);

    // Generate the image
    $file_url = $config['attachment_store'].'/string2image-'.$stringFile.'.gif';
    imagegif($rotated, $file_url);
    imagedestroy($rotated);

    $file_url = str_replace('#', '%23', $file_url);
    $file_url = str_replace('%', '%25', $file_url);
    $file_url = str_replace($config['attachment_store'], $home_url.'attachment', $file_url);

    return $file_url;
}


/**
 * Function to restrict SQL on custom-user-defined queries
 *
 * @param  string SQL code
 * @return string SQL code validated (it will return empty if SQL is not ok)
 **/


function check_sql($sql)
{
    // We remove "*" to avoid things like SELECT * FROM tusuario
    // Check that it not delete_ as "delete_pending" (this is a common field in pandora tables).
    if (preg_match('/\*|delete[^_]|drop|alter|modify|password|pass|insert|update/i', $sql)) {
        return '';
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
 * @return boolean 0 on success exit() on no success
 */


function check_login($output=true)
{
    global $config;

    if (!isset($config['homedir'])) {
        if (!$output) {
            return false;
        }

        // No exists $config. Exit inmediatly
        include 'general/noaccess.php';
        exit;
    }

    if ((isset($_SESSION['id_usuario'])) and ($_SESSION['id_usuario'] != '')) {
        if (is_user($_SESSION['id_usuario'])) {
            $config['id_user'] = $_SESSION['id_usuario'];

            return true;
        }
    } else {
        include_once $config['homedir'].'/mobile/include/user.class.php';

        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            $id_user = $user->getIdUser();
            if (is_user($id_user)) {
                return true;
            }
        }
    }

    if (!$output) {
        return false;
    }

    db_pandora_audit('No session', 'Trying to access without a valid session', 'N/A');
    include $config['homedir'].'/general/noaccess.php';
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
 * @param integer $id_user      User id
 * @param integer $id_group     Agents group id to check from
 * @param string  $access       Access privilege
 * @param boolean $onlyOneGroup Flag to check acl for specified group only (not to roots up, or check acl for 'All' group when $id_group is 0).
 *
 * @return boolean 1 if the user has privileges, 0 if not.
 */
function check_acl($id_user, $id_group, $access, $onlyOneGroup=false)
{
    if (empty($id_user)) {
        // User ID needs to be specified
        trigger_error('Security error: check_acl got an empty string for user id', E_USER_WARNING);
        return 0;
    } else if (is_user_admin($id_user)) {
        return 1;
    } else {
        $id_group = (int) $id_group;
    }

    if ($id_group != 0 || $onlyOneGroup === true) {
        $groups_list_acl = users_get_groups($id_user, $access, false, true, null);
    } else {
        $groups_list_acl = get_users_acl($id_user);
    }

    if (is_array($groups_list_acl)) {
        if (isset($groups_list_acl[$id_group])) {
            $access = get_acl_column($access);
            if (isset($groups_list_acl[$id_group][$access])
                && $groups_list_acl[$id_group][$access] > 0
            ) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    return 0;
}


/**
 * Check the ACL of a list of groups.
 *
 * @param string $id_user to check the ACL
 * @param array  $groups. All groups to check
 * @param string $access. Profile to check
 *
 * @return boolean True if at least one of this groups check the ACL
 */
function check_acl_one_of_groups($id_user, $groups, $access)
{
    foreach ($groups as $group) {
        if (check_acl($id_user, $group, $access)) {
            return true;
        }
    }

    return false;
}


/**
 * Get the name of the database column of one access flag
 *
 * @param string access flag
 *
 * @return string Column name
 */
function get_acl_column($access)
{
    switch ($access) {
        case 'IR':
        return 'incident_view';

            break;
        case 'IW':
        return 'incident_edit';

            break;
        case 'IM':
        return 'incident_management';

            break;
        case 'AR':
        return 'agent_view';

            break;
        case 'AW':
        return 'agent_edit';

            break;
        case 'AD':
        return 'agent_disable';

            break;
        case 'LW':
        return 'alert_edit';

            break;
        case 'LM':
        return 'alert_management';

            break;
        case 'PM':
        return 'pandora_management';

            break;
        case 'DM':
        return 'db_management';

            break;
        case 'UM':
        return 'user_management';

            break;
        case 'RR':
        return 'report_view';

            break;
        case 'RW':
        return 'report_edit';

            break;
        case 'RM':
        return 'report_management';

            break;
        case 'ER':
        return 'event_view';

            break;
        case 'EW':
        return 'event_edit';

            break;
        case 'EM':
        return 'event_management';

            break;
        case 'MR':
        return 'map_view';

            break;
        case 'MW':
        return 'map_edit';

            break;
        case 'MM':
        return 'map_management';

            break;
        case 'VR':
        return 'vconsole_view';

            break;
        case 'VW':
        return 'vconsole_edit';

            break;
        case 'VM':
        return 'vconsole_management';

            break;
        default:
        return '';
            break;
    }
}


function get_users_acl($id_user)
{
    static $users_acl_cache = [];

    if (is_array($users_acl_cache[$id_user])) {
        $rowdup = $users_acl_cache[$id_user];
    } else {
        $query = sprintf(
            "SELECT sum(tperfil.incident_view) as incident_view,
						sum(tperfil.incident_edit) as incident_edit,
						sum(tperfil.incident_management) as incident_management,
						sum(tperfil.agent_view) as agent_view,
						sum(tperfil.agent_edit) as agent_edit,
						sum(tperfil.alert_edit) as alert_edit,
						sum(tperfil.alert_management) as alert_management,
						sum(tperfil.pandora_management) as pandora_management,
						sum(tperfil.db_management) as db_management,
						sum(tperfil.user_management) as user_management,
						sum(tperfil.report_view) as report_view,
						sum(tperfil.report_edit) as report_edit,
						sum(tperfil.report_management) as report_management,
						sum(tperfil.event_view) as event_view,
						sum(tperfil.event_edit) as event_edit,
						sum(tperfil.event_management) as event_management,
						sum(tperfil.agent_disable) as agent_disable,
						sum(tperfil.map_view) as map_view,
						sum(tperfil.map_edit) as map_edit,
						sum(tperfil.map_management) as map_management,
						sum(tperfil.vconsole_view) as vconsole_view,
						sum(tperfil.vconsole_edit) as vconsole_edit,
						sum(tperfil.vconsole_management) as vconsole_management
					FROM tusuario_perfil, tperfil
					WHERE tusuario_perfil.id_perfil = tperfil.id_perfil
						AND tusuario_perfil.id_usuario = '%s'",
            $id_user
        );

        $rowdup = db_get_all_rows_sql($query);
        $users_acl_cache[$id_user] = $rowdup;
    }

    if (empty($rowdup) || !$rowdup) {
        return 0;
    }

    return $rowdup;
}


/**
 * Get the name of a plugin
 *
 * @param int id_plugin Plugin id.
 *
 * @return string The name of the given plugin
 */
function dame_nombre_pluginid($id_plugin)
{
    return (string) db_get_value('name', 'tplugin', 'id', (int) $id_plugin);
}


/**
 * Get the operating system id.
 *
 * @param string Operating system name.
 *
 * @return id Id of the given operating system.
 */
function get_os_id($os_name)
{
    return (string) db_get_value('id_os', 'tconfig_os', 'name', $os_name);
}


/**
 * Get the operating system name.
 *
 * @param int Operating system id.
 *
 * @return string Name of the given operating system.
 */
function get_os_name($id_os)
{
    return (string) db_get_value('name', 'tconfig_os', 'id_os', (int) $id_os);
}


/**
 * Get user's dashboards
 *
 * @param int user id.
 *
 * @return array Dashboard name of the given user.
 */
function get_user_dashboards($id_user)
{
    if (users_is_admin($id_user)) {
        $sql = "SELECT name
			FROM tdashboard WHERE id_user = '".$id_user."' OR id_user = ''";
    } else {
        $user_can_manage_all = users_can_manage_group_all('RR');
        if ($user_can_manage_all) {
            $sql = "SELECT name
				FROM tdashboard WHERE id_user = '".$id_user."' OR id_user = ''";
        } else {
            $user_groups = users_get_groups($id_user, 'RR', false);
            if (empty($user_groups)) {
                return false;
            }

            $u_groups = [];
            foreach ($user_groups as $id => $group_name) {
                $u_groups[] = $id;
            }

            $sql = 'SELECT name
				FROM tdashboard
				WHERE id_group IN ('.implode(',', $u_groups).") AND (id_user = '".$id_user."' OR id_user = '')";
        }
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the possible periods in seconds.
 *
 * @param bool Flag to show or not custom fist option
 * @param bool Show the periods by default if it is empty
 *
 * @return The possible periods in an associative array.
 */
function get_periods($custom=true, $show_default=true)
{
    global $config;

    $periods = [];

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
        } else {
            $periods[-1] = __('Empty').': '.__('Default values will be used');
        }
    } else {
        $values = explode(',', $config['interval_values']);
        foreach ($values as $v) {
            $periods[$v] = human_time_description_raw($v, true);
        }
    }

    return $periods;
}


/**
 * Recursive copy directory
 */
function copy_dir($src, $dst)
{
    $dir = opendir($src);
    $return = true;

    if (!$dir) {
        return false;
    }

    @mkdir($dst);
    while (false !== ( $file = readdir($dir))) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if (is_dir($src.'/'.$file)) {
                $return = copy_dir($src.'/'.$file, $dst.'/'.$file);

                if (!$return) {
                    break;
                }
            } else {
                $r = copy($src.'/'.$file, $dst.'/'.$file);
            }
        }
    }

    closedir($dir);

    return $return;
}


function delete_dir($dir)
{
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!delete_dir($dir.'/'.$item)) {
            return false;
        }
    }

    return rmdir($dir);
}


/**
 * Returns 1 if the data contains a codified image (base64)
 */
function is_image_data($data)
{
    return (substr($data, 0, 10) == 'data:image');
}


/**
 *  Returns 1 if this is Snapshot data, 0 otherwise
 *  Looks for two or more carriage returns.
 */
function is_snapshot_data($data)
{
    return is_image_data($data);
}


/**
 * Check if text is too long to put it into a black screen
 *
 * @param  string Data value
 * @return boolean True if black window should be displayed
 */
function is_text_to_black_string($data)
{
    if (is_image_data($data)) {
        return false;
    }

    // Consider large text if data is greater than 200 characters
    return ((int) strlen($data)) > 200;
}


/**
 *  Create an invisible div with a provided ID and value to
 * can retrieve it from javascript with function get_php_value(name)
 */
function set_js_value($name, $value)
{
    html_print_div(
        [
            'id'      => 'php_to_js_value_'.$name,
            'content' => json_encode($value),
            'hidden'  => true,
        ]
    );
}


function is_array_empty($InputVariable)
{
    $Result = true;

    if (is_array($InputVariable) && count($InputVariable) > 0) {
        foreach ($InputVariable as $Value) {
            $Result = $Result && is_array_empty($Value);
        }
    } else {
        $Result = empty($InputVariable);
    }

    return $Result;
}


// This function is used to give or not access to nodes in
// Metaconsole. Sometimes is used in common code between
// Meta and normal console, so if Meta is not activated, it
// will return 1 always
// Return 0 if the user hasnt access to node/detail 1 otherwise
function can_user_access_node()
{
    global $config;

    $userinfo = get_user_info($config['id_user']);

    if (is_metaconsole()) {
        return $userinfo['is_admin'] == 1 ? 1 : $userinfo['metaconsole_access_node'];
    } else {
        return 1;
    }
}


/**
 *  Get the upload status code
 */
function get_file_upload_status($file_input_name)
{
    if (!isset($_FILES[$file_input_name])) {
        return -1;
    }

    return $_FILES[$file_input_name]['error'];
}


/**
 *  Get a human readable message with the upload status code
 */
function translate_file_upload_status($status_code)
{
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
 *
 * @param string name of the argument
 * @param mixed array with arguments
 * @param string defualt value for this argument
 *
 * @return string value for the argument
 */
function get_argument($argument, $arguments, $default)
{
    if (isset($arguments[$argument])) {
        return $arguments[$argument];
    } else {
        return $default;
    }
}


/**
 *  Get the arguments given in a function returning default value if not defined
 *
 * @param  mixed arguments
 *             - id_user: user who can see the news
 *          - modal: true if want to get modal news. false to return not modal news
 *             - limit: number of max news returned
 * @return mixed list of news
 */
function get_news($arguments)
{
    global $config;

    $id_user = get_argument('id_user', $arguments, $config['id_user']);
    $modal = get_argument('modal', $arguments, false);
    $limit = get_argument('limit', $arguments, 99999999);

    $id_group = array_keys(users_get_groups($id_user, false, true));

    // Empty groups
    if (empty($id_group)) {
        return [];
    }

    $id_group = implode(',', $id_group);
    $current_datetime = date('Y-m-d H:i:s', time());
    $modal = (int) $modal;

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $sql = sprintf(
                "SELECT id_news,subject,timestamp,text,author
				FROM tnews WHERE id_group IN (%s) AND 
								modal = %s AND 
								(expire = 0 OR (expire = 1 AND expire_timestamp > '%s'))
				ORDER BY timestamp DESC
				LIMIT %s",
                $id_group,
                $modal,
                $current_datetime,
                $limit
            );
        break;

        case 'oracle':
            $sql = sprintf(
                "SELECT subject,timestamp,text,author
				FROM tnews
				WHERE rownum <= %s AND id_group IN (%s) AND 
								modal = %s AND 
								(expire = 0 OR (expire = 1 AND expire_timestamp > '%s'))
				ORDER BY timestamp DESC",
                $limit,
                $id_group,
                $modal,
                $current_datetime
            );
        break;
    }

    $news = db_get_all_rows_sql($sql);

    if (empty($news)) {
        $news = [];
    }

    return $news;
}


/**
 * Print audit data in CSV format.
 *
 * @param array Audit data.
 */
function print_audit_csv($data)
{
    global $config;
    global $graphic_type;

    $divider = html_entity_decode($config['csv_divider']);

    if (!$data) {
        echo __('No data found to export');
        return 0;
    }

    $config['ignore_callback'] = true;
    while (@ob_end_clean()) {
    }

    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename=audit_log'.date('Y-m-d_His').'.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM
    print pack('C*', 0xEF, 0xBB, 0xBF);

    echo __('User').$divider.__('Action').$divider.__('Date').$divider.__('Source IP').$divider.__('Comments')."\n";
    foreach ($data as $line) {
        echo io_safe_output($line['id_usuario']).$divider.io_safe_output($line['accion']).$divider.io_safe_output(date($config['date_format'], $line['utimestamp'])).$divider.$line['ip_origen'].$divider.io_safe_output($line['descripcion'])."\n";
    }

    exit;
}


/**
 * Validate the code given to surpass the 2 step authentication
 *
 * @param string User name
 * @param string Code given by the authenticator app
 *
 * @return -1 if the parameters introduced are incorrect,
 *             there is a problem accessing the user secret or
 *            if an exception are launched.
 *            true if the code is valid.
 *            false if the code is invalid.
 */
function validate_double_auth_code($user, $code)
{
    global $config;
    include_once $config['homedir'].'/include/auth/GAuth/Auth.php';
    $result = false;

    if (empty($user) || empty($code)) {
        $result = -1;
    } else {
        $secret = db_get_value('secret', 'tuser_double_auth', 'id_user', $user);

        if ($secret === false) {
            $result = -1;
        } else if (!empty($secret)) {
            try {
                $gAuth = new \GAuth\Auth($secret);
                $result = $gAuth->validateCode($code);
            } catch (Exception $e) {
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
function is_double_auth_enabled($user)
{
    $result = (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $user);

    return $result;
}


function clear_pandora_error_for_header()
{
    global $config;

    $config['alert_cnt'] = 0;
    $_SESSION['alert_msg'] = [];
}


function set_pandora_error_for_header($message, $title=null)
{
    global $config;

    if (!isset($config['alert_cnt'])) {
        $config['alert_cnt'] = 0;
    }

    if (( !isset($_SESSION['alert_msg']) && (!is_array($_SESSION['alert_msg'])) )) {
        $_SESSION['alert_msg'] = [];
    }

    $message_config = [];
    if (isset($title)) {
        $message_config['title'] = $title;
    }

    $message_config['message'] = $message;
    $message_config['no_close'] = true;

    $config['alert_cnt']++;
    $_SESSION['alert_msg'][] = [
        'type'    => 'error',
        'message' => $message_config,
    ];
}


function get_pandora_error_for_header()
{
    $result = '';

    if (isset($_SESSION['alert_msg']) && is_array($_SESSION['alert_msg'])) {
        foreach ($_SESSION['alert_msg'] as $key => $value) {
            if (!isset($value['type']) || !isset($value['message'])) {
                continue;
            }

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


function set_if_defined(&$var, $test)
{
    if (isset($test)) {
        $var = $test;

        return true;
    } else {
        return false;
    }
}


function set_unless_defined(&$var, $default)
{
    if (! isset($var)) {
        $var = $default;

        return true;
    } else {
        return false;
    }
}


function set_when_empty(&$var, $default)
{
    if (empty($var)) {
        $var = $default;

        return true;
    } else {
        return false;
    }
}


function sort_by_column(&$array_ref, $column)
{
    if (!empty($column)) {
        usort(
            $array_ref,
            function ($a, $b) use ($column) {
                return strcmp($a[$column], $b[$column]);
            }
        );
    }
}


function array2XML($data, $root=null, $xml=null)
{
    if ($xml == null) {
        $xml = simplexml_load_string(
            "<?xml version='1.0' encoding='UTF-8'?>\n<".$root.' />'
        );
    }

    foreach ($data as $key => $value) {
        if (is_numeric($key)) {
            $key = 'item_'.$key;
        }

        if (is_array($value)) {
            $node = $xml->addChild($key);
            array2XML($value, $root, $node);
        } else {
            $value = htmlentities($value);

            if (!is_numeric($value) && !is_bool($value)) {
                if (!empty($value)) {
                    $xml->addChild($key, $value);
                }
            } else {
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
function extract_column($array, $column)
{
    $column_is_arr = is_array($column);

    return array_map(
        function ($item) use ($column_is_arr, $column) {
            if ($column_is_arr) {
                return array_reduce(
                    $column,
                    function ($carry, $col) use ($item) {
                        $carry[$col] = $item[$col];
                        return $item[$col];
                    },
                    []
                );
            } else {
                return $item[$column];
            }
        },
        $array
    );
}


function get_percentile($percentile, $array)
{
    sort($array);
    $index = (($percentile / 100) * count($array));

    if (floor($index) == $index) {
        $result = (($array[($index - 1)] + $array[$index]) / 2);
    } else {
        $result = $array[floor($index)];
    }

    return $result;
}


if (!function_exists('hex2bin')) {


    function hex2bin($data)
    {
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
            } else {
                $data = (string) $data;
            }
        } else {
            trigger_error(__FUNCTION__.'() expects parameter 1 to be string, '.gettype($data).' given', E_USER_WARNING);
            return;
            // null in this case
        }

        $len = strlen($data);
        if (($len % 2)) {
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


function get_refresh_time_array()
{
    return [
        '0'                        => __('Disable'),
        '5'                        => __('5 seconds'),
        '10'                       => __('10 seconds'),
        '15'                       => __('15 seconds'),
        '30'                       => __('30 seconds'),
        (string) SECONDS_1MINUTE   => __('1 minute'),
        (string) SECONDS_2MINUTES  => __('2 minutes'),
        (string) SECONDS_5MINUTES  => __('5 minutes'),
        (string) SECONDS_15MINUTES => __('15 minutes'),
        (string) SECONDS_30MINUTES => __('30 minutes'),
        (string) SECONDS_1HOUR     => __('1 hour'),
    ];
}


function date2strftime_format($date_format)
{
    $replaces_list = [
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
    ];

    $return = '';

    // character to character because
    // Replacement order gotcha
    // http://php.net/manual/en/function.str-replace.php
    $chars = str_split($date_format);
    foreach ($chars as $c) {
        if (isset($replaces_list[$c])) {
            $return .= $replaces_list[$c];
        } else {
            $return .= $c;
        }
    }

    return $return;
}


function pandora_setlocale()
{
    global $config;

    $replace_locale = [
        'ca'    => 'ca_ES',
        'de'    => 'de_DE',
        'en_GB' => 'de',
        'es'    => 'es_ES',
        'fr'    => 'fr_FR',
        'it'    => 'it_IT',
        'nl'    => 'nl_BE',
        'pl'    => 'pl_PL',
        'pt'    => 'pt_PT',
        'pt_BR' => 'pt_BR',
        'sk'    => 'sk_SK',
        'tr'    => 'tr_TR',
        'cs'    => 'cs_CZ',
        'el'    => 'el_GR',
        'ru'    => 'ru_RU',
        'ar'    => 'ar_MA',
        'ja'    => 'ja_JP.UTF-8',
        'zh_CN' => 'zh_CN',
    ];

    $user_language = get_user_language($config['id_user']);

    setlocale(
        LC_ALL,
        str_replace(array_keys($replace_locale), $replace_locale, $user_language)
    );
}


function update_config_token($cfgtoken, $cfgvalue)
{
    global $config;

    $delete = db_process_sql("DELETE FROM tconfig WHERE token = '$cfgtoken'");
    $insert = db_process_sql("INSERT INTO tconfig (token, value) VALUES ('$cfgtoken', '$cfgvalue')");

    if ($delete && $insert) {
        return true;
    } else {
        return false;
    }
}


function get_number_of_mr($package, $ent, $offline)
{
    global $config;

    if (!$ent) {
        $dir = $config['attachment_store'].'/downloads/pandora_console/extras/mr';
    } else {
        if ($offline) {
            $dir = $package.'/extras/mr';
        } else {
            $dir = sys_get_temp_dir().'/pandora_oum/'.$package.'/extras/mr';
        }
    }

    $mr_size = [];

    if (file_exists($dir) && is_dir($dir)) {
        if (is_readable($dir)) {
            $files = scandir($dir);
            // Get all the files from the directory ordered by asc
            if ($files !== false) {
                $pattern = '/^\d+\.sql$/';
                $sqlfiles = preg_grep($pattern, $files);
                // Get the name of the correct files
                $pattern = '/\.sql$/';
                $replacement = '';
                $sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles);

                foreach ($sqlfiles_num as $num) {
                    $mr_size[] = $num;
                }
            }
        }
    }

    return $mr_size;
}


function remove_right_zeros($value)
{
    $is_decimal = explode('.', $value);
    if (isset($is_decimal[1])) {
        $value_to_return = rtrim($value, '0');
        return rtrim($value_to_return, '.');
    } else {
        return $value;
    }
}


function register_pass_change_try($id_user, $success)
{
    $values = [];
    $values['id_user'] = $id_user;
    $reset_pass_moment = new DateTime('now');
    $reset_pass_moment = $reset_pass_moment->format('Y-m-d H:i:s');
    $values['reset_moment'] = $reset_pass_moment;
    $values['success'] = $success;
    db_process_sql_insert('treset_pass_history', $values);
}


function isJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}


/**
 * returns true or false if it is a valid ip
 * checking ipv4 and ipv6 or resolves the name dns
 *
 * @param string address
 */
function validate_address($address)
{
    if ($address) {
        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ip_address_dns = gethostbyname($address);
                if (!filter_var($ip_address_dns, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    if (!filter_var($ip_address_dns, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        return false;
                    }
                }
            }
        }
    }

    return true;
}


/**
 * Used to get the offset in seconds to the UTC date.
 *
 * @param string Timezone identifier.
 */
function get_utc_offset($timezone)
{
    if (empty($timezone)) {
        return 0;
    }

    $dtz = new DateTimeZone($timezone);
    $dt = new DateTime('now', $dtz);

    return $dtz->getOffset($dt);
}


function get_system_utc_offset()
{
    global $config;
    return get_utc_offset($config['timezone']);
}


function get_current_utc_offset()
{
    return get_utc_offset(date_default_timezone_get());
}


function get_fixed_offset()
{
    return (get_current_utc_offset() - get_system_utc_offset());
}


/**
 * Used to transform the dates without timezone information (like '2018/05/23 10:10:10')
 * to a unix timestamp compatible with the user custom timezone.
 *
 * @param string Date without timezone information.
 * @param number Offset between the date timezone and the user's default timezone.
 */
function time_w_fixed_tz($date, $timezone_offset=null)
{
    if ($timezone_offset === null) {
        $timezone_offset = get_fixed_offset();
    }

    return (strtotime($date) + $timezone_offset);
}


/**
 * Used to transform the dates without timezone information (like '2018/05/23 10:10:10')
 * to a date compatible with the user custom timezone.
 *
 * @param string Date without timezone information.
 * @param string Date format.
 * @param number Offset between the date timezone and the user's default timezone.
 */
function date_w_fixed_tz($date, $format=null, $timezone_offset=null)
{
    global $config;

    if ($format === null) {
        $format = $config['date_format'];
    }

    return date($format, time_w_fixed_tz($date, $timezone_offset));
}


function color_graph_array()
{
    global $config;

    $color_series = [];

    $color_series[0] = [
        'border' => '#000000',
        'color'  => $config['graph_color1'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series[1] = [
        'border' => '#000000',
        'color'  => $config['graph_color2'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[2] = [
        'border' => '#000000',
        'color'  => $config['graph_color3'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series[3] = [
        'border' => '#000000',
        'color'  => $config['graph_color4'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[4] = [
        'border' => '#000000',
        'color'  => $config['graph_color5'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[5] = [
        'border' => '#000000',
        'color'  => $config['graph_color6'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[6] = [
        'border' => '#000000',
        'color'  => $config['graph_color7'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[7] = [
        'border' => '#000000',
        'color'  => $config['graph_color8'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[8] = [
        'border' => '#000000',
        'color'  => $config['graph_color9'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[9] = [
        'border' => '#000000',
        'color'  => $config['graph_color10'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[10] = [
        'border' => '#000000',
        'color'  => COL_GRAPH9,
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[11] = [
        'border' => '#000000',
        'color'  => COL_GRAPH10,
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[12] = [
        'border' => '#000000',
        'color'  => COL_GRAPH11,
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[13] = [
        'border' => '#000000',
        'color'  => COL_GRAPH12,
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];
    $color_series[14] = [
        'border' => '#000000',
        'color'  => COL_GRAPH13,
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['event'] = [
        'border' => '#ff0000',
        'color'  => '#FF5733',
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['alert'] = [
        'border' => '#ffff00',
        'color'  => '#ffff00',
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['unknown'] = [
        'border' => '#999999',
        'color'  => '#E1E1E1',
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['percentil'] = [
        'border' => '#000000',
        'color'  => '#003333',
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['projection'] = [
        'border' => '#000000',
        'color'  => $config['graph_color8'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['overlapped'] = [
        'border' => '#000000',
        'color'  => $config['graph_color9'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['summatory'] = [
        'border' => '#000000',
        'color'  => $config['graph_color7'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['average'] = [
        'border' => '#000000',
        'color'  => $config['graph_color10'],
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['no_data'] = [
        'border' => '#000000',
        'color'  => '#f2c40e',
        'alpha'  => CHART_DEFAULT_ALPHA,
    ];

    $color_series['unit'] = [
        'border' => null,
        'color'  => '#0097BC',
        'alpha'  => 10,
    ];

    return $color_series;
}


function series_type_graph_array($data, $show_elements_graph)
{
    global $config;

    if (isset($show_elements_graph['stacked'])) {
        switch ($show_elements_graph['stacked']) {
            case 2:
            case 4:
                $type_graph = 'line';
            break;

            default:
                $type_graph = 'area';
            break;
        }
    } else {
        $type_graph = $show_elements_graph['type_graph'];
    }

    $color_series = color_graph_array();

    if ($show_elements_graph['id_widget_dashboard']) {
        $opcion = unserialize(db_get_value_filter('options', 'twidget_dashboard', ['id' => $show_elements_graph['id_widget_dashboard']]));
        if ($show_elements_graph['graph_combined']) {
            foreach ($show_elements_graph['modules_id'] as $key => $value) {
                $color_series[$key] = [
                    'border' => '#000000',
                    'color'  => $opcion[$value],
                    'alpha'  => CHART_DEFAULT_ALPHA,
                ];
            }
        } else {
            $color_series[0] = [
                'border' => '#000000',
                'color'  => $opcion['max'],
                'alpha'  => CHART_DEFAULT_ALPHA,
            ];
        }
    }

    $i = 0;
    if (isset($data) && is_array($data)) {
        foreach ($data as $key => $value) {
            if ($show_elements_graph['compare'] == 'overlapped') {
                if ($key == 'sum2') {
                    $str = ' ('.__('Previous').')';
                }
            }

            if (strpos($key, 'summatory') !== false) {
                $data_return['series_type'][$key] = $type_graph;
                $data_return['legend'][$key]      = __('Summatory series').' '.$str;
                $data_return['color'][$key]       = $color_series['summatory'];
            } else if (strpos($key, 'average') !== false) {
                $data_return['series_type'][$key] = $type_graph;
                $data_return['legend'][$key]      = __('Average series').' '.$str;
                $data_return['color'][$key]       = $color_series['average'];
            } else if (strpos($key, 'sum') !== false || strpos($key, 'baseline') !== false) {
                switch ($value['id_module_type']) {
                    case 21:
                    case 2:
                    case 6:
                    case 18:
                    case 9:
                    case 31:
                    case 100:
                        $data_return['series_type'][$key] = 'boolean';
                    break;

                    default:
                        $data_return['series_type'][$key] = $type_graph;
                    break;
                }

                if (isset($show_elements_graph['labels'][$value['agent_module_id']])
                    && is_array($show_elements_graph['labels'])
                    && (count($show_elements_graph['labels']) > 0)
                ) {
                    if ($show_elements_graph['unit']) {
                        $name_legend = $show_elements_graph['labels'][$value['agent_module_id']].' / '.__('Unit ').' '.$show_elements_graph['unit'].': ';
                        $data_return['legend'][$key] = $show_elements_graph['labels'][$value['agent_module_id']].' / '.__('Unit ').' '.$show_elements_graph['unit'].': ';
                    } else {
                        $name_legend = $show_elements_graph['labels'][$value['agent_module_id']].': ';
                        $data_return['legend'][$key] = $show_elements_graph['labels'][$value['agent_module_id']].': ';
                    }
                } else {
                    if (strpos($key, 'baseline') !== false) {
                        if ($value['unit']) {
                            $name_legend = $data_return['legend'][$key] = $value['agent_alias'].' / '.$value['module_name'].' / '.__('Unit ').' '.$value['unit'].'Baseline ';
                        } else {
                            $name_legend = $data_return['legend'][$key] = $value['agent_alias'].' / '.$value['module_name'].'Baseline ';
                        }
                    } else {
                        if ($value['unit']) {
                            $name_legend = $data_return['legend'][$key] = $value['agent_alias'].' / '.$value['module_name'].' / '.__('Unit ').' '.$value['unit'].': ';
                        } else {
                            $name_legend = $data_return['legend'][$key] = $value['agent_alias'].' / '.$value['module_name'].': ';
                        }
                    }
                }

                $data_return['legend'][$key] .= __('Min:').remove_right_zeros(
                    number_format(
                        $value['min'],
                        $config['graph_precision']
                    )
                ).' '.__('Max:').remove_right_zeros(
                    number_format(
                        $value['max'],
                        $config['graph_precision']
                    )
                ).' '._('Avg:').remove_right_zeros(
                    number_format(
                        $value['avg'],
                        $config['graph_precision']
                    )
                ).' '.$str;

                if ($show_elements_graph['compare'] == 'overlapped' && $key == 'sum2') {
                    $data_return['color'][$key] = $color_series['overlapped'];
                } else {
                    $data_return['color'][$key] = $color_series[$i];
                    $i++;
                }
            } else if (!$show_elements_graph['fullscale'] && strpos($key, 'min') !== false
                || !$show_elements_graph['fullscale'] && strpos($key, 'max') !== false
            ) {
                $data_return['series_type'][$key] = $type_graph;

                if ($show_elements_graph['unit']) {
                    $name_legend = $data_return['legend'][$key] = $value['agent_alias'].' / '.$value['module_name'].' / '.__('Unit ').' '.$show_elements_graph['unit'].': ';
                } else {
                    $name_legend = $data_return['legend'][$key] = $value['agent_alias'].' / '.$value['module_name'].': ';
                }

                $data_return['legend'][$key] = $name_legend;
                if ($show_elements_graph['type_mode_graph']) {
                    $data_return['legend'][$key] .= __('Min:').remove_right_zeros(
                        number_format(
                            $value['min'],
                            $config['graph_precision']
                        )
                    ).' '.__('Max:').remove_right_zeros(
                        number_format(
                            $value['max'],
                            $config['graph_precision']
                        )
                    ).' '._('Avg:').remove_right_zeros(
                        number_format(
                            $value['avg'],
                            $config['graph_precision']
                        )
                    ).' '.$str;
                }

                if ($show_elements_graph['compare'] == 'overlapped' && $key == 'sum2') {
                    $data_return['color'][$key] = $color_series['overlapped'];
                } else {
                    $data_return['color'][$key] = $color_series[$i];
                    $i++;
                }
            } else if (strpos($key, 'event') !== false) {
                $data_return['series_type'][$key] = 'points';
                if ($show_elements_graph['show_events']) {
                    $data_return['legend'][$key] = __('Events').' '.$str;
                }

                $data_return['color'][$key] = $color_series['event'];
            } else if (strpos($key, 'alert') !== false) {
                $data_return['series_type'][$key] = 'points';
                if ($show_elements_graph['show_alerts']) {
                    $data_return['legend'][$key] = __('Alert').' '.$str;
                }

                $data_return['color'][$key] = $color_series['alert'];
            } else if (strpos($key, 'unknown') !== false) {
                $data_return['series_type'][$key] = 'unknown';
                if ($show_elements_graph['show_unknown']) {
                    $data_return['legend'][$key] = __('Unknown').' '.$str;
                }

                $data_return['color'][$key] = $color_series['unknown'];
            } else if (strpos($key, 'percentil') !== false) {
                $data_return['series_type'][$key] = 'percentil';
                if ($show_elements_graph['percentil']) {
                    if ($show_elements_graph['unit']) {
                        $name_legend = __('Percentil').' ';
                        $name_legend .= $config['percentil'].'º ';
                        $name_legend .= __('of module').' ';
                        $name_legend .= $value['agent_alias'].' / ';
                        $name_legend .= $value['module_name'].' / ';
                        $name_legend .= __('Unit ').' ';
                        $name_legend .= $show_elements_graph['unit'].': ';
                    } else {
                        $name_legend = __('Percentil').' ';
                        $name_legend .= $config['percentil'].'º ';
                        $name_legend .= __('of module').' ';
                        $name_legend .= $value['agent_alias'].' / ';
                        $name_legend .= $value['module_name'].': ';
                    }

                    $data_return['legend'][$key] .= $name_legend;
                    $data_return['legend'][$key] .= remove_right_zeros(
                        number_format(
                            $value['data'][0][1],
                            $config['graph_precision']
                        )
                    ).' '.$str;
                }

                $data_return['color'][$key] = $color_series['percentil'];
            } else if (strpos($key, 'projection') !== false) {
                $data_return['series_type'][$key] = $type_graph;
                $data_return['legend'][$key]      = __('Projection').' '.$str;
                $data_return['color'][$key]       = $color_series['projection'];
            } else {
                $data_return['series_type'][$key] = $type_graph;
                $data_return['legend'][$key] = $key;
                $data_return['color'][$key] = $color_series[$i];
                $i++;
            }

            if ($i > 14) {
                $i = 0;
            }
        }

        return $data_return;
    }

    return false;
}


function generator_chart_to_pdf($type_graph_pdf, $params, $params_combined=false, $module_list=false)
{
    global $config;

    if (is_metaconsole()) {
        $hack_metaconsole = '../..';
    } else {
        $hack_metaconsole = '';
    }

    $file_js  = $config['homedir'].'/include/web2image.js';
    $url      = ui_get_full_url(false).$hack_metaconsole.'/include/chart_generator.php';

    if (!$params['return_img_base_64']) {
        $img_file = 'img_'.uniqid().'.png';
        $img_path = $config['homedir'].'/attachment/'.$img_file;
        $img_url  = ui_get_full_url(false).$hack_metaconsole.'/attachment/'.$img_file;
    }

    $width_img  = 500;

    // Set height image.
    $height_img = 170;
    $params['height'] = 170;
    if ((int) $params['landscape'] === 1) {
        $height_img = 150;
        $params['height'] = 150;
    }

    if ($type_graph_pdf === 'slicebar') {
        $width_img  = 360;
        $height_img = 70;
    }

    $params_encode_json = urlencode(json_encode($params));

    if ($params_combined) {
        $params_combined = urlencode(json_encode($params_combined));
    }

    if ($module_list) {
        $module_list = urlencode(json_encode($module_list));
    }

    $session_id = session_id();

    $cmd = '"'.io_safe_output($config['phantomjs_bin']).DIRECTORY_SEPARATOR.'phantomjs" --ssl-protocol=any --ignore-ssl-errors=true "'.$file_js.'" '.' "'.$url.'"'.' "'.$type_graph_pdf.'"'.' "'.$params_encode_json.'"'.' "'.$params_combined.'"'.' "'.$module_list.'"'.' "'.$img_path.'"'.' "'.$width_img.'"'.' "'.$height_img.'"'.' "'.$session_id.'"'.' "'.$params['return_img_base_64'].'"';

    $result = null;
    $retcode = null;
    exec($cmd, $result, $retcode);

    $img_content = join("\n", $result);

    if ($params['return_img_base_64']) {
        // To be used in alerts.
        return $img_content;
    } else {
        // to be used in PDF files.
        $config['temp_images'][] = $img_path;
        return '<img src="'.$img_url.'" />';
    }
}


/**
 * Get the product name.
 *
 * @return string If the installation is open, it will be 'Pandora FMS'.
 *         If the product name stored is empty, it returns 'Pandora FMS' too.
 */
function get_product_name()
{
    $stored_name = enterprise_hook('enterprise_get_product_name');
    if (empty($stored_name) || $stored_name == ENTERPRISE_NOT_HOOK) {
        return 'Pandora FMS';
    }

    return $stored_name;
}


/**
 * Get the copyright notice.
 *
 * @return string If the installation is open, it will be 'Artica ST'.
 *         If the product name stored is empty, it returns 'Artica ST' too.
 */
function get_copyright_notice()
{
    $stored_name = enterprise_hook('enterprise_get_copyright_notice');
    if (empty($stored_name) || $stored_name == ENTERPRISE_NOT_HOOK) {
        return 'Ártica ST';
    }

    return $stored_name;
}


/**
 * Generate a random code to prevent cross site request fogery attacks
 *
 * @return string Generated code
 */
function generate_csrf_code()
{
    // Start session to make this var permanent
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['csrf_code'] = md5(uniqid(mt_rand(), true));
    session_write_close();
    return $_SESSION['csrf_code'];
}


/**
 * Validate the CSRF code
 *
 * @return boolean True if code is valid
 */
function validate_csrf_code()
{
    $code = get_parameter('csrf_code');
    return isset($code) && isset($_SESSION['csrf_code'])
        && $_SESSION['csrf_code'] == $code;
}


function generate_hash_to_api()
{
    return (string) hash('sha256', db_get_value('value', 'tupdate_settings', '`key`', 'customer_key'));
}


/**
 * Disable the profiller and display de result
 *
 * @param string Key to identify the profiler run.
 * @param string Way to display the result
 *         "link" (default): Click into word "Performance" to display the profilling info.
 *         "console": Display with a message in pandora_console.log.
 */
function pandora_xhprof_display_result($key='', $method='link')
{
    // Check if function exists
    if (!function_exists('tideways_xhprof_disable')) {
        error_log('Cannot find tideways_xhprof_disable function');
        return;
    }

    $run_id = uniqid();
    $data = tideways_xhprof_disable();
    $source = "pandora_$key";
    file_put_contents(
        sys_get_temp_dir().'/'.$run_id.".$source.xhprof",
        serialize($data)
    );
    $new_url = "http://{$_SERVER['HTTP_HOST']}/profiler/index.php?run={$run_id}&source={$source}";
    switch ($method) {
        case 'console':
            error_log("'{$new_url}'");
        case 'link':
        default:
            echo "<a href='{$new_url}' target='_new'>Performance</a>\n";
        break;
    }
}


/**
 * From a network with a mask remove the smallest ip and the highest
 *
 * @param string $address Identify the network.
 * @param string $mask    Identify the mask network.
 *
 * @return array or false with smallest ip and highest ip.
 */
function range_ips_for_network($address, $mask)
{
    if (!isset($address) || !isset($mask)) {
        return false;
    }

    // Convert ip addresses to long form.
    $address_long = ip2long($address);
    $mask_long = ip2long($mask);

    // Calculate first usable address.
    $ip_host_first = ((~$mask_long) & $address_long);
    $ip_first = (($address_long ^ $ip_host_first));

    // Calculate last usable address.
    $ip_broadcast_invert = ~$mask_long;
    $ip_last = (($address_long | $ip_broadcast_invert) - 1);

    $range = [
        'first' => long2ip($ip_first),
        'last'  => long2ip($ip_last),
    ];

    return $range;
}


/**
 * from two ips find out if there is such an ip
 *
 * @param string ip ip wont validate
 * @param string ip_lower
 * @param string ip_upper
 *
 * @return boolean true or false if the ip is between the two ips
 */
function is_in_network($ip, $ip_lower, $ip_upper)
{
    if (!isset($ip) || !isset($ip_lower) || !isset($ip_upper)) {
        return false;
    }

    $ip = (float) sprintf('%u', ip2long($ip));
    $ip_lower = (float) sprintf('%u', ip2long($ip_lower));
    $ip_upper = (float) sprintf('%u', ip2long($ip_upper));

    if ($ip >= $ip_lower && $ip <= $ip_upper) {
        return true;
    } else {
        return false;
    }
}


/**
 *
 */
function ip_belongs_to_network($ip, $network, $mask)
{
    if ($ip == $network) {
        return true;
    }

    $ranges = range_ips_for_network($network, $mask);
    return is_in_network($ip, $ranges['first'], $ranges['last']);
}


/**
 * convert the mask to cird format
 *
 * @param  string mask
 * @return string true or false if the ip is between the two ips
 */
function mask2cidr($mask)
{
    if (!isset($mask)) {
        return 0;
    }

    $long = ip2long($mask);
    $base = ip2long('255.255.255.255');
    return (32 - log((($long ^ $base) + 1), 2));
}


/**
 * convert the cidr prefix to subnet mask
 *
 * @param  int cidr prefix
 * @return string subnet mask
 */
function cidr2mask($int)
{
    return long2ip(-1 << (32 - (int) $int));
}


function get_help_info($section_name)
{
    global $config;

    $user_language = get_user_language($id_user);

    $es = false;
    $result = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:';
    if ($user_language == 'es') {
        $es = true;
        $result = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:';
    }

    switch ($section_name) {
        case 'tactical_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Vista_t.C3.A1ctica';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Tactical_view';
            }
        break;

        case 'group_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Vista_de_Grupos';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Group_view';
            }
        break;

        case 'tree_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Vista_de_.C3.A1rbol';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#The_Tree_View';
            }
        break;

        case 'monitor_detail_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Detalles_Monitores';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Monitor_Details';
            }
        break;

        case 'tag_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Vista_de_etiquetas';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Tag_view';
            }
        break;

        case 'alert_validation':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Detalles_de_Alertas';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Alert_Details';
            }
        break;

        case 'agents_alerts_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Vista_de_agente_.2F_alerta';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Agent.2F_Alert_View';
            }
        break;

        case 'agents_module_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Vista_de_agente_.2F_modulo';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Agents_.2F_Modules_View';
            }
        break;

        case 'module_groups_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Vista_de_grupos_de_m.C3.B3dulos';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Module_Groups_View';
            }
        break;

        case 'snmp_browser_view':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Navegador_SNMP_de_Pandora_FMS';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#Pandora_FMS_SNMP_MIB_Browser';
            }
        break;

        case 'snmp_trap_generator_view':
            if ($es) {
                $result .= 'Monitorizacion_traps_SNMP&printable=yes#Generador_de_Traps';
            } else {
                $result .= 'SNMP_traps_Monitoring&printable=yes#Trap_Generator';
            }
        break;

        case 'real_time_view':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Gr.C3.A1ficas_Real-time';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Real-time_Graphs';
            }
        break;

        case 'agent_status':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Detalles_del_agente';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Agent_Details';
            }
        break;

        case 'agent_main_tab':
            if ($es) {
                $result .= 'Intro_Monitorizacion&printable=yes#Visualizaci.C3.B3n_del_agente';
            } else {
                $result .= 'Intro_Monitoring&printable=yes#Agent_configuration_in_the_console_2';
            }
        break;

        case 'alert_config':
            if ($es) {
                $result .= 'Alertas&printable=yes#Creaci.C3.B3n_de_una_Acci.C3.B3n';
            } else {
                $result .= 'Alerts&printable=yes#Creating_an_Action';
            }
        break;

        case 'alert_macros':
            if ($es) {
                $result .= 'Alertas&printable=yes#Macros_sustituibles_en_los_campos_Field1.2C_Field2.2C_Field3..._Field10';
            } else {
                $result .= 'Alerts&printable=yes#Replaceable_Macros_within_Field_1_through_Field_10';
            }
        break;

        case 'alerts_config':
            if ($es) {
                $result .= 'Alertas&printable=yes#Configuraci.C3.B3n_de_alertas_en_Pandora_FMS';
            } else {
                $result .= 'Alerts&printable=yes#Alert_Configuration_in_Pandora_FMS';
            }
        break;

        case 'alert_special_days':
            if ($es) {
                $result .= 'Alertas&printable=yes#Lista_de_d.C3.ADas_especiales';
            } else {
                $result .= 'Alerts&printable=yes#List_of_special_days';
            }
        break;

        case 'alerts':
            if ($es) {
                $result .= 'Politicas&printable=yes#Alertas';
            } else {
                $result .= 'Policy&printable=yes#Alerts';
            }
        break;

        case 'collections':
            if ($es) {
                $result .= 'Politicas&printable=yes#Colecciones_de_ficheros';
            } else {
                $result .= 'Policy&printable=yes#File_Collections';
            }
        break;

        case 'component_groups':
            if ($es) {
                $result .= 'Plantillas_y_Componentes&printable=yes#Grupos_de_componentes';
            } else {
                $result .= 'Templates_and_components&printable=yes#Component_Groups';
            }
        break;

        case 'configure_gis_map':
            if ($es) {
                $result .= 'Pandora_GIS&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'GIS&printable=yes#Introduction';
            }
        break;

        case 'configure_gis_map_edit':
            if ($es) {
                $result .= 'Pandora_GIS&printable=yes#GIS_Maps';
            } else {
                $result .= 'GIS&printable=yes#GIS_Maps';
            }
        break;

        case 'event_alert':
            if ($es) {
                $result .= 'Eventos&printable=yes#Introducci.C3.B3n_2';
            } else {
                $result .= 'Events&printable=yes#Introduction_2';
            }
        break;

        case 'eventview':
            if ($es) {
                $result .= 'Eventos&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Events&printable=yes#Introduction';
            }
        break;

        case 'export_server':
            if ($es) {
                $result .= 'ExportServer&printable=yes#A.C3.B1adir_un_servidor_de_destino';
            } else {
                $result .= 'Export_Server&printable=yes#Adding_a_Target_Server';
            }
        break;

        case 'external_alert':
            if ($es) {
                $result .= 'Politicas&printable=yes#Alertas_Externas';
            } else {
                $result .= 'Policy&printable=yes#External_Alerts';
            }
        break;

        case 'gis_tab':
            if ($es) {
                $result .= 'Pandora_GIS&printable=yes#Configuraci.C3.B3n_del_Agent_GIS';
            } else {
                $result .= 'GIS&printable=yes#The_Agent.27s_GIS_Setup';
            }
        break;

        case 'graph_builder':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Crear_Gr.C3.A1ficas_combinadas';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Creating_combined_graphs';
            }
        break;

        case 'graph_editor':
            if ($es) {
                $result .= 'Presentacion_datos/visualizacion&printable=yes#Agregar_elementos_a_gr.C3.A1ficas_combinadas';
            } else {
                $result .= 'Data_Presentation/Visualization&printable=yes#Adding_elements_to_combined_graphs';
            }
        break;

        case 'dashboards_tab':
            if ($es) {
                $result .= 'Dashboard&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Dashboard&printable=yes#Introduction';
            }
        break;

        case 'history_database':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Base_de_datos_hist.C3.B3rica';
            } else {
                $result .= 'Console_Setup&printable=yes#The_History_Database';
            }
        break;

        case 'inventory_tab':
            if ($es) {
                $result .= 'Inventario&printable=yes#M.C3.B3dulos_de_inventario';
            } else {
                $result .= 'Inventory&printable=yes#Inventory_Modules';
            }
        break;

        case 'ipam_list_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'IPAM&printable=yes#Introduction';
            }
        break;

        case 'ipam_calculator_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Calculadora_de_subredes';
            } else {
                $result .= 'IPAM&printable=yes#Subnetwork_calculator';
            }
        break;

        case 'ipam_vlan_config_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Vlan_IPAM';
            } else {
                $result .= 'IPAM&printable=yes#VLAN_IPAM';
            }
        break;

        case 'ipam_vlan_statistics_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Estad.C3.ADsticas_IPAM_Vlan';
            } else {
                $result .= 'IPAM&printable=yes#IPAM_VLAN_Stats';
            }
        break;

        case 'ipam_vlan_wizard_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Wizard_IPAM_Vlan';
            } else {
                $result .= 'IPAM&printable=yes#IPAM_VLAN_Wizard:';
            }
        break;

        case 'ipam_supernet_config_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#IPAM_Supernet';
            } else {
                $result .= 'IPAM&printable=yes#IPAM_Supernet';
            }
        break;

        case 'ipam_supernet_map_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Mapa_Superred_IPAM';
            } else {
                $result .= 'IPAM&printable=yes#IPAM_Supernet_Map';
            }
        break;

        case 'ipam_supernet_statistics_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Estad.C3.ADsticas_IPAM_Superred';
            } else {
                $result .= 'IPAM&printable=yes#IPAM_Supernet_Stats';
            }
        break;

        case 'ipam_new_tab':
        case 'ipam_edit_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Vista_de_edici.C3.B3n';
            } else {
                $result .= 'IPAM&printable=yes#Edit_view';
            }
        break;

        case 'ipam_massive_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Vista_Operaciones_masivas';
            } else {
                $result .= 'IPAM&printable=yes#Massive_operations_view';
            }
        break;

        case 'ipam_network_tab':
        case 'ipam_force_tab':
            if ($es) {
                $result .= 'IPAM&printable=yes#Vista_de_iconos';
            } else {
                $result .= 'IPAM&printable=yes#Icon_view';
            }
        break;

        case 'macros_visual_maps':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Macros_en_las_consolas_visuales';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Macros_in_Visual_Consoles';
            }
        break;

        case 'linked_map_status_calc':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Mapa_asociado';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Associated_Map';
            }
        break;

        case 'main_tab':
            if ($es) {
                $result .= 'Intro_Monitorizacion&printable=yes#Configuraci.C3.B3n_del_agente_en_consola';
            } else {
                $result .= 'Intro_Monitoring&printable=yes#Agent_configuration_in_the_console';
            }
        break;

        case 'manage_alert_list':
            if ($es) {
                $result .= 'Alertas&printable=yes#Gestionar_alertas_desde_el_agente';
            } else {
                $result .= 'Alerts&printable=yes#Managing_Alerts_from_within_the_Agent';
            }
        break;

        case 'alert_scalate':
            if ($es) {
                $result .= 'Alertas&printable=yes#Escalado_de_alertas';
            } else {
                $result .= 'Alerts&printable=yes#Scaling_Alerts';
            }
        break;

        case 'map_builder_intro':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Introduction';
            }
        break;

        case 'map_builder_favorite':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Consolas_visuales_favoritas';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Favorite_visual_consoles';
            }
        break;

        case 'map_builder_template':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Plantillas_de_consolas_visuales';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Visual_Console_Templates';
            }
        break;

        case 'map_builder_wizard':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Asistente_de_consola_visuales';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Wizard_Visual_Console';
            }
        break;

        case 'module_linking':
            if ($es) {
                $result .= 'Politicas&printable=yes#Tipos_de_m.C3.B3dulos';
            } else {
                $result .= 'Policy&printable=yes#Types_of_Modules';
            }
        break;

        case 'network_map_enterprise_edit':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_de_red&printable=yes#Mapa_de_red_no_vac.C3.ADo';
            } else {
                $result .= 'Data_Presentation/Network_Maps&printable=yes#Non_empty_network_map';
            }
        break;

        case 'network_map_enterprise_list':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_de_red&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Data_Presentation/Network_Maps&printable=yes#Introduction';
            }
        break;

        case 'network_map_enterprise_empty':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_de_red&printable=yes#Mapa_de_red_vac.C3.ADo';
            } else {
                $result .= 'Data_Presentation/Network_Maps&printable=yes#Empty_network_map';
            }
        break;

        case 'network_map_enterprise_view':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_de_red&printable=yes#Vista_de_un_mapa_de_red';
            } else {
                $result .= 'Data_Presentation/Network_Maps&printable=yes#Network_map_view';
            }
        break;

        case 'transactional_view':
            if ($es) {
                $result .= 'Monitorizacion_transaccional&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Transactional_Monitoring&printable=yes#Introduction';
            }
        break;

        case 'pcap_filter':
            if ($es) {
                $result .= 'Netflow&printable=yes#Creaci.C3.B3n_del_filtro';
            } else {
                $result .= 'Netflow&printable=yes#Filter_creation';
            }
        break;

        case 'planned_downtime':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Introducci.C3.B3n_4';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Introduction_4';
            }
        break;

        case 'planned_downtime_editor':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Creaci.C3.B3n_parada_planificada';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Create_a_scheduled_downtime';
            }
        break;

        case 'plugin_definition':
            if ($es) {
                $result .= 'Anexo_Server_Plugins&printable=yes#Registro_manual_de_un_plugin_en_la_consola';
            } else {
                $result .= 'Anexo_Server_plugins_developement&printable=yes#Plugin_manual_registration';
            }
        break;

        case 'plugin_macros':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Macros_internas';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#Internal_Macros';
            }
        break;

        case 'plugin_policy':
            if ($es) {
                $result .= 'Politicas&printable=yes#Plugins_de_agente';
            } else {
                $result .= 'Policy&printable=yes#Agent_Plug_Ins';
            }
        break;

        case 'policy_queue':
            if ($es) {
                $result .= 'Politicas&printable=yes#Gesti.C3.B3n_de_la_cola_de_pol.C3.ADticas';
            } else {
                $result .= 'Policy&printable=yes#Policy_Queues_Management';
            }
        break;

        case 'prediction_source_module':
            if ($es) {
                $result .= 'Monitorizacion_otra&printable=yes#Tipos_de_monitorizaci.C3.B3n_predictiva';
            } else {
                $result .= 'Other_Monitoring&printable=yes#Types_of_predictive_monitoring';
            }
        break;

        case 'wmi_module_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Monitorizaci.C3.B3n_de_Windows_remotos_con_WMI';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#Windows_Remote_Monitoring_with_WMI';
            }
        break;

        case 'template_reporting_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#Introduction';
            }
        break;

        case 'reporting_template_list_item_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Pesta.C3.B1a_List_Items';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_.27List_Items.27_Tab';
            }
        break;

        case 'reporting_template_item_editor_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Pesta.C3.B1a_Item_editor';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_.27Item_Editor.27_Tab';
            }
        break;

        case 'reporting_template_advanced_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Opciones_avanzadas_de_informe';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_Advanced_Options_Tab';
            }
        break;

        case 'reporting_advanced_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Opciones_avanzadas_de_informe';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_Advanced_Options_Tab';
            }
        break;

        case 'reporting_global_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Global';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_Global_Tab';
            }
        break;

        case 'reporting_item_editor_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Pesta.C3.B1a_Item_editor';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_.27Item_Editor.27_Tab';
            }
        break;

        case 'reporting_list_items_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Pesta.C3.B1a_List_Items';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_.27List_Items.27_Tab';
            }
        break;

        case 'reporting_wizard_sla_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Wizard_SLA';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_SLA_Wizard_Tab';
            }
        break;

        case 'reporting_wizard_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Wizard_general';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#The_Wizard_Tab';
            }
        break;

        case 'response_macros':
            if ($es) {
                $result .= 'Eventos&printable=yes#Event_Responses_macros';
            } else {
                $result .= 'Events&printable=yes#Event_Responses_macros';
            }
        break;

        case 'events_responses_tab':
            if ($es) {
                $result .= 'Eventos&printable=yes#Introducci.C3.B3n_3';
            } else {
                $result .= 'Events&printable=yes#Introduction_3';
            }
        break;

        case 'servers':
            if ($es) {
                $result .= 'Interfaz&printable=yes#Gesti.C3.B3n_de_servidores';
            } else {
                $result .= 'Interface&printable=yes#Server_management';
            }
        break;

        case 'snmpwalk':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Navegador_SNMP_de_Pandora_FMS';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#Pandora_FMS_SNMP_MIB_Browser';
            }
        break;

        case 'tags_config':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Sistemas_de_permisos_ampliados_mediante_etiquetas_.28tags.29';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Permission_system_extended_by_tags';
            }
        break;

        case 'transactional_map_phases':
            if ($es) {
                $result .= 'Monitorizacion_transaccional&printable=yes#Creaci.C3.B3n_del_.C3.A1rbol_de_fases';
            } else {
                $result .= 'Transactional_Monitoring&printable=yes#Creating_the_phase_tree';
            }
        break;

        case 'transactional_map_phases_data':
            if ($es) {
                $result .= 'Monitorizacion_transaccional&printable=yes#Configuraci.C3.B3n_de_los_scripts_de_control';
            } else {
                $result .= 'Transactional_Monitoring&printable=yes#Control_scripts_configuration';
            }
        break;

        case 'wizard_reporting_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Informes&printable=yes#Asistente_de_plantillas';
            } else {
                $result .= 'Data_Presentation/Reports&printable=yes#Template_Wizard';
            }
        break;

        case 'user_edit_notifications':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Configuraci.C3.B3n_de_notificaciones';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Notification_configuration';
            }
        break;

        case 'view_services':
            if ($es) {
                $result .= 'Servicios&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Services&printable=yes#Introduction';
            }
        break;

        case 'visual_console_editor_data_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Creaci.C3.B3n_-_Datos_generales';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Creation_-_General_data';
            }
        break;

        case 'visual_console_editor_editor_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Creaci.C3.B3n_y_edici.C3.B3n_de_consolas_visuales';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Creation_and_edition_of_Visual_Consoles';
            }
        break;

        case 'visual_console_editor_list_elements_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Creaci.C3.B3n_-_lista_de_elementos';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Creation_-_List_of_Elements';
            }
        break;

        case 'visual_console_editor_wizard_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Creaci.C3.B3n_-_Wizard';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Creation_-_Wizard';
            }
        break;

        case 'visual_console_editor_wizard_services_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Creaci.C3.B3n_-_Wizard_de_Servicios';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Creation_-_Service_Wizard';
            }
        break;

        case 'visual_console_tab':
            if ($es) {
                $result .= 'Presentacion_datos/Mapas_visuales&printable=yes#Mapa_asociado';
            } else {
                $result .= 'Data_Presentation/Visual_Maps&printable=yes#Associated_Map';
            }
        break;

        case 'view_created_map_services_tab':
            if ($es) {
                $result .= 'Servicios&printable=yes#Vista_de_mapa_de_servicio';
            } else {
                $result .= 'Services&printable=yes#Service_Map_View';
            }
        break;

        case 'view_created_services_tab':
            if ($es) {
                $result .= 'Servicios&printable=yes#Lista_simple_de_un_servicio_y_todos_los_elementos_que_contiene';
            } else {
                $result .= 'Services&printable=yes#List-based_view_of_a_Service_and_its_Elements';
            }
        break;

        case 'config_service_element_tab':
            if ($es) {
                $result .= 'Servicios&printable=yes#Configuraci.C3.B3n_de_elementos';
            } else {
                $result .= 'Services&printable=yes#Element_Configuration';
            }
        break;

        case 'config_service_tab':
            if ($es) {
                $result .= 'Servicios&printable=yes#Configuraci.C3.B3n_inicial';
            } else {
                $result .= 'Services&printable=yes#Initial_Configuration';
            }
        break;

        case 'other_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Otra_configuraci.C3.B3n';
            } else {
                $result .= 'Console_Setup&printable=yes#Other_configuration';
            }
        break;

        case 'services_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Configuraci.C3.B3n_servicios';
            } else {
                $result .= 'Console_Setup&printable=yes#Services_configuration';
            }
        break;

        case 'visual_consoles_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Configuraci.C3.B3n_de_las_consolas_visuales';
            } else {
                $result .= 'Console_Setup&printable=yes#Visual_console_configuration';
            }
        break;

        case 'charts_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Configuraci.C3.B3n_de_gr.C3.A1ficas';
            } else {
                $result .= 'Console_Setup&printable=yes#Chart_settings';
            }
        break;

        case 'front_and_text_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Configuraci.C3.B3n_de_Fuente_y_texto';
            } else {
                $result .= 'Console_Setup&printable=yes#Font_and_text_settings';
            }
        break;

        case 'gis_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Configuraci.C3.B3n_GIS';
            } else {
                $result .= 'Console_Setup&printable=yes#GIS_configuration';
            }
        break;

        case 'style_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Configuraci.C3.B3n_de_estilo';
            } else {
                $result .= 'Console_Setup&printable=yes#Style_configuration';
            }
        break;

        case 'behavoir_conf_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Configuraci.C3.B3n_del_comportamiento';
            } else {
                $result .= 'Console_Setup&printable=yes#Behaviour_configuration';
            }
        break;

        case 'setup_ehorus_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#eHorus';
            } else {
                $result .= 'Console_Setup&printable=yes#EHorus';
            }
        break;

        case 'diagnostic_tool_tab':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Diagnostic_tool';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Diagnostic_tool';
            }
        break;

        case 'performance_metrics_tab':
            if ($es) {
                $result .= 'Optimizacion&printable=yes#Comprobaci.C3.B3n_del_fichero_my.ini.2Fcnf';
            } else {
                $result .= 'Optimization&printable=yes#Check_my.ini.2Fcnf_settings';
            }
        break;

        case 'db_status_tab':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#DB_Schema_Check';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#DB_Schema_Check';
            }
        break;

        case 'database_backup_utility_tab':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Backup';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Backup';
            }
        break;

        case 'update_manager_offline_tab':
            if ($es) {
                $result .= 'Actualizacion&printable=yes#Actualizaciones_.22offline.22';
            } else {
                $result .= 'Anexo_Upgrade&printable=yes#.22Offline.22_updates';
            }
        break;

        case 'update_manager_online_tab':
            if ($es) {
                $result .= 'Actualizacion&printable=yes#Actualizaciones_.22online.22';
            } else {
                $result .= 'Anexo_Upgrade&printable=yes#.22Online.22_updates';
            }
        break;

        case 'others_database_maintenance_options_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Otros';
            } else {
                $result .= 'Console_Setup&printable=yes#Others';
            }
        break;

        case 'database_maintenance_options_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Opciones_de_mantenimiento_de_la_base_de_datos';
            } else {
                $result .= 'Console_Setup&printable=yes#Database_maintenance_options';
            }
        break;

        case 'database_maintenance_status_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Estado_del_mantenimiento_de_las_bases_de_datos';
            } else {
                $result .= 'Console_Setup&printable=yes#Database_maintenance_status';
            }
        break;

        case 'historical_database_maintenance_options_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Opciones_de_mantenimiento_de_la_base_de_datos_hist.C3.B3rica';
            } else {
                $result .= 'Console_Setup&printable=yes#Historical_database_maintenance_options';
            }
        break;

        case 'setup_enterprise_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Enterprise';
            } else {
                $result .= 'Console_Setup&printable=yes#Features_of_the_Enterprise_Version';
            }
        break;

        case 'setup_general_tab':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#General_Setup';
            } else {
                $result .= 'Console_Setup&printable=yes#General_Setup';
            }
        break;

        case 'export_target_tab':
            if ($es) {
                $result .= 'ExportServer&printable=yes#A.C3.B1adir_un_servidor_de_destino';
            } else {
                $result .= 'Export_Server&printable=yes#Adding_a_Target_Server';
            }
        break;

        case 'servers_ha_clusters_tab':
            if ($es) {
                $result .= 'HA&printable=yes#Alta_disponibilidad_del_Servidor_de_Datos';
            } else {
                $result .= 'HA&printable=yes#HA_of_Data_Server';
            }
        break;

        case 'plugins_tab':
            if ($es) {
                $result .= 'Anexo_Agent_Plugins&printable=yes#Caracter.C3.ADsticas_b.C3.A1sicas_de_plugin_de_agente';
            } else {
                $result .= 'Anexo_Agent_Plugins&printable=yes#Basic_Features_of_the_Agent_Plugin';
            }
        break;

        case 'create_agent':
            if ($es) {
                $result .= 'Intro_Monitorizacion&printable=yes#Configuraci.C3.B3n_del_agente_en_consola';
            } else {
                $result .= 'Intro_Monitoring&printable=yes#Agent_configuration_in_the_console';
            }
        break;

        case 'agent_snmp_explorer_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Wizard_SNMP';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#SNMP_Wizard';
            }
        break;

        case 'agent_snmp_interfaces_explorer_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#SNMP_Interfaces_wizard';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#SNMP_Interface_Wizard';
            }
        break;

        case 'agent_snmp_wmi_explorer_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Wizard_WMI';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#WMI_Wizard';
            }
        break;

        case 'group_list_tab':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Introducci.C3.B3n_2';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Introduction_2';
            }
        break;

        case 'acl_setup_tab':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Introducci.C3.B3n_3';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Introduction_3';
            }
        break;

        case 'profile_tab':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Perfiles_en_Pandora_FMS';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Profiles_in_Pandora_FMS';
            }
        break;

        case 'configure_profiles_tab':
            if ($es) {
                $result .= 'Gestion_y_Administracion&printable=yes#Perfiles_en_Pandora_FMS';
            } else {
                $result .= 'Managing_and_Administration&printable=yes#Profiles_in_Pandora_FMS';
            }
        break;

        case 'network_component_tab':
            if ($es) {
                $result .= 'Plantillas_y_Componentes&printable=yes#Componentes_de_red';
            } else {
                $result .= 'Templates_and_components&printable=yes#Network_Components';
            }
        break;

        case 'local_component_tab':
            if ($es) {
                $result .= 'Plantillas_y_Componentes&printable=yes#Componentes_locales';
            } else {
                $result .= 'Templates_and_components&printable=yes#Local_Components';
            }
        break;

        case 'module_template_tab':
            if ($es) {
                $result .= 'Plantillas_y_Componentes&printable=yes#Plantillas_de_m.C3.B3dulos';
            } else {
                $result .= 'Templates_and_components&printable=yes#Module_Templates';
            }
        break;

        case 'agent_autoconf_tab':
            if ($es) {
                $result .= 'Configuracion_Agentes&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Configuration_Agents&printable=yes#Introduction';
            }
        break;

        case 'policies_management_tab':
            if ($es) {
                $result .= 'Politicas&printable=yes#Introducci.C3.B3n';
            } else {
                $result .= 'Policy&printable=yes#Introduction';
            }
        break;

        case 'massive_agents_tab':
            if ($es) {
                $result .= 'Operaciones_Masivas&printable=yes#Edici.C3.B3n_masiva_de_agentes';
            } else {
                $result .= 'Massive_Operations&printable=yes#Agent_massive_edition';
            }
        break;

        case 'massive_modules_tab':
            if ($es) {
                $result .= 'Operaciones_Masivas&printable=yes#Edici.C3.B3n_masiva_de_m.C3.B3dulos';
            } else {
                $result .= 'Massive_Operations&printable=yes#Modules_massive_edition';
            }
        break;

        case 'massive_policies_tab':
            if ($es) {
                $result .= 'Operaciones_Masivas&printable=yes#Editar_m.C3.B3dulos_de_pol.C3.ADticas_masivamente';
            } else {
                $result .= 'Massive_Operations&printable=yes#Edit_policy_modules_massively';
            }
        break;

        case 'alert_templates_tab':
            if ($es) {
                $result .= 'Alertas&printable=yes#Introducci.C3.B3n_4';
            } else {
                $result .= 'Alerts&printable=yes#Introduction_4';
            }
        break;

        case 'configure_alert_template_step_1':
            if ($es) {
                $result .= 'Alertas&printable=yes#Paso_1:_General';
            } else {
                $result .= 'Alerts&printable=yes#Step_1:_General';
            }
        break;

        case 'configure_alert_template_step_2':
            if ($es) {
                $result .= 'Alertas&printable=yes#Paso_2:_Condiciones';
            } else {
                $result .= 'Alerts&printable=yes#Step_2:_Conditions';
            }
        break;

        case 'configure_alert_template_step_3':
            if ($es) {
                $result .= 'Alertas&printable=yes#Paso_3:_Campos_avanzados';
            } else {
                $result .= 'Alerts&printable=yes#Step_3:_Advanced_fields';
            }
        break;

        case 'alerts_action':
            if ($es) {
                $result .= 'Alertas&printable=yes#Introducci.C3.B3n_3';
            } else {
                $result .= 'Alerts&printable=yes#Introduction_3';
            }
        break;

        case 'alerts_command_tab':
            if ($es) {
                $result .= 'Alertas&printable=yes#Introducci.C3.B3n_2';
            } else {
                $result .= 'Alerts&printable=yes#Introduction_2';
            }
        break;

        case 'alerts_config_command_tab':
            if ($es) {
                $result .= 'Alertas&printable=yes#Creaci.C3.B3n_de_un_comando_para_una_alerta';
            } else {
                $result .= 'Alerts&printable=yes#Command_Creation_for_an_Alert';
            }
        break;

        case 'configure_alert_event_step_1':
            if ($es) {
                $result .= 'Eventos&printable=yes#Creaci.C3.B3n_alerta_de_evento';
            } else {
                $result .= 'Events&printable=yes#Event_Alert_creation';
            }
        break;

        case 'configure_event_rule_tab':
            if ($es) {
                $result .= 'Eventos&printable=yes#Creaci.C3.B3n_alerta_de_evento';
            } else {
                $result .= 'Events&printable=yes#Event_Alert_creation';
            }
        break;

        case 'snmp_alert_overview_tab':
            if ($es) {
                $result .= 'Monitorizacion_traps_SNMP&printable=yes#Introducci.C3.B3n_2';
            } else {
                $result .= 'SNMP_traps_Monitoring&printable=yes#Introduction_2';
            }
        break;

        case 'snmp_alert_update_tab':
            if ($es) {
                $result .= 'Monitorizacion_traps_SNMP&printable=yes#A.C3.B1adir_una_alerta';
            } else {
                $result .= 'SNMP_traps_Monitoring&printable=yes#Alert_Creation';
            }
        break;

        case 'sound_console_tab':
            if ($es) {
                $result .= 'Eventos&printable=yes#Uso';
            } else {
                $result .= 'Events&printable=yes#Use';
            }
        break;

        case 'local_module_tab':
            if ($es) {
                $result .= 'Intro_Monitorizacion&printable=yes#Par.C3.A1metros_comunes';
            } else {
                $result .= 'Intro_Monitoring&printable=yes#Common_Parameters';
            }
        break;

        case 'local_module':
            if ($es) {
                $result .= 'Operacion&printable=yes#Tipos_de_m.C3.B3dulos';
            } else {
                $result .= 'Operations&printable=yes#Types_of_Modules';
            }
        break;

        case 'data_server_module_tab':
            if ($es) {
                $result .= 'Operacion&printable=yes#Tipos_de_m.C3.B3dulos';
            } else {
                $result .= 'Operations&printable=yes#Types_of_Modules';
            }
        break;

        case 'network_module_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Monitorizaci.C3.B3n_ICMP';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#ICMP_Monitoring';
            }
        break;

        case 'wux_console':
            if ($es) {
                $result .= 'Monitorizacion_Usuario&printable=yes#Crear_un_m.C3.B3dulo_de_an.C3.A1lisis_web_en_Pandora_FMS_Console';
            } else {
                $result .= 'User_Monitorization&printable=yes#Creating_a_Web_Analytics_module_in_Pandora_FMS_Console';
            }
        break;

        case 'gis_basic_configurations_tab':
            if ($es) {
                $result .= 'Pandora_GIS&printable=yes#Configuraci.C3.B3n_B.C3.A1sica';
            } else {
                $result .= 'GIS&printable=yes#Basic_Configuration';
            }
        break;

        case 'gis_map_connection_tab':
            if ($es) {
                $result .= 'Pandora_GIS&printable=yes#Mapas_Open_Street';
            } else {
                $result .= 'GIS&printable=yes#Open_Street_Maps';
            }
        break;

        case 'icmp_module_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Monitorizaci.C3.B3n_ICMP';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#ICMP_Monitoring';
            }
        break;

        case 'snmp_module_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Monitorizando_con_m.C3.B3dulos_de_red_tipo_SNMP';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#Monitoring_by_Network_Modules_with_SNMP';
            }
        break;

        case 'tcp_module_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Monitorizaci.C3.B3n_TCP';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#TCP_Monitoring';
            }
        break;

        case 'webserver_module_tab':
            if ($es) {
                $result .= 'Monitorizacion_web&printable=yes#Creaci.C3.B3n_de_m.C3.B3dulos_web';
            } else {
                $result .= 'Web_Monitoring&printable=yes#Creating_Web_Modules';
            }
        break;

        case 'wmi_query_tab':
            if ($es) {
                $result .= 'Monitorizacion_remota&printable=yes#Monitorizaci.C3.B3n_de_Windows_remotos_con_WMI';
            } else {
                $result .= 'Remote_Monitoring&printable=yes#Windows_Remote_Monitoring_with_WMI';
            }
        break;

        case 'omnishell':
            if ($es) {
                $result .= 'Omnishell&printable=yes';
            } else {
                $result .= 'Omnishell&printable=yes';
            }
        break;

        case 'module_type_tab':
            if ($es) {
                $result .= 'Operacion&printable=yes#Tipos_de_m.C3.B3dulos';
            } else {
                $result .= '';
            }
        break;

        case 'render_view_tab':
            if ($es) {
                $result .= 'Pandora_GIS&printable=yes#Operaci.C3.B3n';
            } else {
                $result .= 'GIS&printable=yes#Operation';
            }
        break;

        case 'quickshell_settings':
            if ($es) {
                $result .= 'Configuracion_Consola&printable=yes#Websocket_Engine';
            } else {
                $result .= 'Console_Setup&printable=yes#Websocket_engine';
            }
        break;

        case 'discovery':
            if ($es) {
                $result .= 'Discovery&printable=yes';
            } else {
                $result .= 'Discovery&printable=yes';
            }
        break;
    }

    return $result;
}


if (!function_exists('getallheaders')) {


    /**
     * Fix for php-fpm
     *
     * @return array
     */
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }


}
