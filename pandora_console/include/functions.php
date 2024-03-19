<?php
// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
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

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;

/*
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
 * @return array the list of files if $return parameter is true.
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
    global $config;

    // Translate to float in case there are characters in the string so
    // fmod doesn't throw a notice.
    $number = (float) $number;

    if ($number == 0) {
        return 0;
    }

    if (fmod($number, 1) > 0) {
        return number_format(
            $number,
            $decimals,
            $config['decimal_separator'],
            ($config['thousand_separator'] ?? ',')
        );
    }

    return number_format(
        $number,
        0,
        $config['decimal_separator'],
        ($config['thousand_separator'] ?? ',')
    );
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
    $sufix='',
    $two_lines=false
) {
    // Exception to exclude modules whose unit is already formatted as KB (satellite modules)
    if (!empty($sufix) && $sufix == 'KB') {
        return;
    }

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
    if ($two_lines === true) {
        return remove_right_zeros(format_numeric($number, $decimals)).'<br>'.$shorts[$pos].$sufix;
    }

    return remove_right_zeros(format_numeric($number, $decimals)).$shorts[$pos].$sufix;
}


function human_milliseconds_to_string($seconds, $size_text='large')
{
    $ret = '';

    // get the days
    $days = intval(intval($seconds) / (360000 * 24));
    if ($days > 0) {
        if ($size_text === 'short') {
            $ret .= str_replace(' ', '', "$days d").' ';
        } else {
            $ret .= "$days days ";
        }
    }

    // get the hours
    $hours = ((intval($seconds) / 360000) % 24);
    if ($hours > 0) {
        if ($size_text === 'short') {
            $ret .= str_replace(' ', '', "$hours h").' ';
        } else {
            $ret .= "$hours hours ";
        }
    }

    // get the minutes
    $minutes = ((intval($seconds) / 6000) % 60);
    if ($minutes > 0) {
        if ($size_text === 'short') {
            $ret .= str_replace(' ', '', "$minutes m").' ';
        } else {
            $ret .= "$minutes minutes ";
        }
    }

    // get the seconds
    $seconds = ((intval($seconds) / 100) % 60);
    if ($seconds > 0) {
        if ($size_text === 'short') {
            $ret .= str_replace(' ', '', "$seconds s").' ';
        } else {
            $ret .= "$seconds seconds ";
        }
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
 * INTERNAL (use ui_print_timestamp for output):
 * Transform an amount of time in seconds into a human readable
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
    if (isset($units) === false || empty($units) === true) {
        $units = 'large';
    }

    switch ($units) {
        case 'tiny':
            $secondsString = __('s');
            $daysString = __('d');
            $monthsString = __('M');
            $yearsString = __('Y');
            $minutesString = __('m');
            $hoursString = __('h');
            $nowString = __('N');
        break;

        default:
        case 'large':
            $secondsString = __('seconds');
            $daysString = __('days');
            $monthsString = __('months');
            $yearsString = __('years');
            $minutesString = __('minutes');
            $hoursString = __('hours');
            $nowString = __('Now');
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
        $seconds = (float) $seconds;

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
        setcookie($name, '', -1, '/');
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

    if (isset($_FILES[$name])) {
        return get_parameter_file($name, $default);
    }

    return $default;
}


function get_parameter_date($name, $default='', $date_format='Y/m/d')
{
    $date_end = get_parameter('date_end', 0);
    $time_end = get_parameter('time_end');
    $datetime_end = strtotime($date_end.' '.$time_end);

    $custom_date = get_parameter('custom_date', 0);
    $range = get_parameter('range', SECONDS_1DAY);
    $date_text = get_parameter('range_text', SECONDS_1DAY);
    $date_init_less = (strtotime(date('Y/m/d')) - SECONDS_1DAY);
    $date_init = get_parameter('date_init', date(DATE_FORMAT, $date_init_less));
    $time_init = get_parameter('time_init', date(TIME_FORMAT, $date_init_less));
    $datetime_init = strtotime($date_init.' '.$time_init);
    if ($custom_date === '1') {
        if ($datetime_init >= $datetime_end) {
            $datetime_init = $date_init_less;
        }

        $date_init = date('Y/m/d H:i:s', $datetime_init);
        $date_end = date('Y/m/d H:i:s', $datetime_end);
        $period = ($datetime_end - $datetime_init);
    } else if ($custom_date === '2') {
        $date_units = get_parameter('range_units');
        $date_end = date('Y/m/d H:i:s');
        $date_init = date('Y/m/d H:i:s', (strtotime($date_end) - ((int) $date_text * (int) $date_units)));
        $period = (strtotime($date_end) - strtotime($date_init));
    } else if (in_array($range, ['this_week', 'this_month', 'past_week', 'past_month'])) {
        if ($range === 'this_week') {
            $monday = date('Y/m/d', strtotime('last monday'));

            $sunday = date('Y/m/d', strtotime($monday.' +6 days'));
            $period = (strtotime($sunday) - strtotime($monday));
            $date_init = $monday;
            $date_end = $sunday;
        } else if ($range === 'this_month') {
            $date_end = date('Y/m/d', strtotime('last day of this month'));
            $first_of_month = date('Y/m/d', strtotime('first day of this month'));
            $date_init = $first_of_month;
            $period = (strtotime($date_end) - strtotime($first_of_month));
        } else if ($range === 'past_month') {
            $date_end = date('Y/m/d', strtotime('last day of previous month'));
            $first_of_month = date('Y/m/d', strtotime('first day of previous month'));
            $date_init = $first_of_month;
            $period = (strtotime($date_end) - strtotime($first_of_month));
        } else if ($range === 'past_week') {
            $date_end = date('Y/m/d', strtotime('sunday', strtotime('last week')));
            $first_of_week = date('Y/m/d', strtotime('monday', strtotime('last week')));
            $date_init = $first_of_week;
            $period = (strtotime($date_end) - strtotime($first_of_week));
        }
    } else {
        $date_end = date('Y/m/d H:i:s');
        $date_init = date('Y/m/d H:i:s', (strtotime($date_end) - $range));
        $period = (strtotime($date_end) - strtotime($date_init));
    }

    return [
        'date_init' => date($date_format, strtotime($date_init)),
        'date_end'  => date($date_format, strtotime($date_end)),
        'period'    => $period,
    ];
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
 * Get a parameter from a post file request.
 *
 * @param string $name    key of the parameter in the $_FILES array
 * @param mixed  $default default value if the key wasn't found
 *
 * @return mixed Whatever was in that parameter, cleaned however
 */
function get_parameter_file($name, $default='')
{
    if ((isset($_FILES[$name])) && !empty($_FILES[$name])) {
        return io_safe_input($_FILES[$name]);
    }

    return $default;
}


/**
 * Get header.
 *
 * @param string      $key     Key.
 * @param string|null $default Default.
 *
 * @return string|null
 */
function get_header(string $key, ?string $default=null): ?string
{
    static $headers;
    if (!isset($headers)) {
        $headers = getAllHeaders();
    }

    $adjust_key = ucwords(strtolower($key));
    if (isset($headers[$adjust_key])) {
        return $headers[$adjust_key];
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
    $types['ncm'] = __('Network configuration manager');

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
 * Translates status into string.
 *
 * @param integer $status Agent status.
 *
 * @return string Translation.
 */
function get_agent_status_string($status)
{
    switch ($status) {
        case AGENT_STATUS_CRITICAL:
        return __('CRITICAL');

        case AGENT_STATUS_WARNING:
        return __('WARNING');

        case AGENT_STATUS_ALERT_FIRED:
        return __('ALERT FIRED');

        case AGENT_STATUS_NOT_INIT:
        return __('NO DATA');

        case AGENT_STATUS_NORMAL:
        return __('NORMAL');

        case AGENT_STATUS_UNKNOWN:
        default:
        return __('UNKNOWN');
    }
}


/**
 * Translates status into string.
 *
 * @param integer $status Module status.
 *
 * @return string Translation.
 */
function get_module_status_string($status)
{
    switch ($status) {
        case AGENT_MODULE_STATUS_CRITICAL_BAD:
        return __('CRITICAL');

        case AGENT_MODULE_STATUS_WARNING_ALERT:
        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
        return __('ALERT FIRED');

        case AGENT_MODULE_STATUS_WARNING:
        return __('WARNING');

        case AGENT_MODULE_STATUS_UNKNOWN:
        return __('UNKNOWN');

        case AGENT_MODULE_STATUS_NO_DATA:
        case AGENT_MODULE_STATUS_NOT_INIT:
        return __('NO DATA');

        case AGENT_MODULE_STATUS_NORMAL_ALERT:
        case AGENT_MODULE_STATUS_NORMAL:
        default:
        return __('NORMAL');
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

        return call_user_func_array(
            $function_name,
            array_values(($parameters ?? []))
        );
    }

    return ENTERPRISE_NOT_HOOK;
}


/**
 * Include an enterprise file.
 *
 * @param string $filename  Enterprise file to be included.
 * @param array  $variables Variables to be exported, as [varname => value].
 *
 * @return mixed
 */
function enterprise_include($filename, $variables=[])
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
        if (is_array($variables) === true) {
            extract($variables);
        }

        include_once $filepath;
        return true;
    }

    return ENTERPRISE_NOT_HOOK;
}


/**
 * Includes a file from enterprise section.
 *
 * @param string $filename  Enterprise file to be included.
 * @param array  $variables Variables to be exported, as [varname => value].
 *
 * @return mixed Result code.
 */
function enterprise_include_once($filename, $variables=[])
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
        if (is_array($variables) === true) {
            extract($variables);
        }

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
 * Verifies if current Pandora FMS installation is a Metaconsole.
 *
 * @return boolean True metaconsole installation, false if not.
 */
function is_metaconsole()
{
    global $config;

    if (isset($config['metaconsole']) === false) {
        return false;
    }

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
 * Check if there is management operations are allowed in current context
 *
 * @param string $hkey Hash ke.
 *
 * @return boolean
 */
function is_management_allowed($hkey='')
{
    $nodes = db_get_value('count(*) as n', 'tmetaconsole_setup');
    if ($nodes !== false) {
        $nodes = (int) $nodes;
    }

    return ( (is_metaconsole() && (is_centralized() || $nodes === 0))
        || (!is_metaconsole() && !is_centralized())
        || (!is_metaconsole() && is_centralized()) && $hkey == generate_hash_to_api());
}


/**
 * Return true if is a centrallised environment.
 *
 * @return boolean
 */
function is_centralized()
{
    global $config;

    if (isset($config['centralized_management']) === false) {
        return false;
    }

    return (bool) $config['centralized_management'];
}


/**
 * @brief Check if there is centralized management in metaconsole environment.
 *             Usefull to display some policy features on metaconsole.
 *
 * @return boolean
 */
function is_central_policies()
{
    return is_metaconsole() && is_centralized();
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
    if ($code !== true && ($code <= ERR_GENERIC || $code === false)) {
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
    $format='-Oa',
    $load_mibs='-m ALL'
) {
    global $config;

    if (empty($ip_target) === true) {
        return [];
    }

    // Note: quick_print is ignored
    // Fix for snmp port
    if (!empty($snmp_port)) {
        $ip_target = $ip_target.':'.$snmp_port;
    }

    // Escape the base OID
    if ($base_oid != '') {
        $base_oid = escapeshellarg($base_oid);
    }

    switch (PHP_OS) {
        case 'FreeBSD':
            $snmpwalk_bin = '/usr/local/bin/snmpwalk';
        break;

        case 'NetBSD':
            $snmpwalk_bin = '/usr/pkg/bin/snmpwalk';
        break;

        default:
            if ($snmp_version == '1') {
                $snmpwalk_bin = 'snmpwalk';
            } else {
                $snmpwalk_bin = 'snmpbulkwalk';
            }
        break;
    }

    switch (PHP_OS) {
        case 'WIN32':
        case 'WINNT':
        case 'Windows':
            $error_redir_dir = 'NUL';
            $snmpwalk_bin = 'snmpwalk';
        break;

        default:
            $error_redir_dir = '/dev/null';
        break;
    }

    if (empty($config['snmpwalk']) === false) {
        if ($snmp_version == '1') {
            $snmpwalk_bin = $config['snmpwalk_fallback'];
        } else {
            $snmpwalk_bin = $config['snmpwalk'];
        }
    }

    $output = [];
    $rc = 0;
    switch ($snmp_version) {
        case '3':
            switch ($snmp3_security_level) {
                case 'authNoPriv':
                    $command_str = $snmpwalk_bin.' '.$load_mibs.' '.$format.' '.$extra_arguments.' -v 3'.' -u '.escapeshellarg($snmp3_auth_user).' -A '.escapeshellarg($snmp3_auth_pass).' -l '.escapeshellarg($snmp3_security_level).' -a '.escapeshellarg($snmp3_auth_method).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
                break;

                case 'noAuthNoPriv':
                    $command_str = $snmpwalk_bin.' '.$load_mibs.' '.$format.' '.$extra_arguments.' -v 3'.' -u '.escapeshellarg($snmp3_auth_user).' -l '.escapeshellarg($snmp3_security_level).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
                break;

                default:
                    $command_str = $snmpwalk_bin.' '.$load_mibs.' '.$format.' '.$extra_arguments.' -v 3'.' -u '.escapeshellarg($snmp3_auth_user).' -A '.escapeshellarg($snmp3_auth_pass).' -l '.escapeshellarg($snmp3_security_level).' -a '.escapeshellarg($snmp3_auth_method).' -x '.escapeshellarg($snmp3_privacy_method).' -X '.escapeshellarg($snmp3_privacy_pass).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
                break;
            }
        break;

        case '2':
        case '2c':
        case '1':
        default:
            $command_str = $snmpwalk_bin.' '.$load_mibs.' '.$extra_arguments.' '.$format.' -v '.escapeshellarg($snmp_version).' -c '.escapeshellarg(io_safe_output($snmp_community)).' '.escapeshellarg($ip_target).' '.$base_oid.' 2> '.$error_redir_dir;
        break;
    }

    if (enterprise_installed()) {
        if (empty($server_to_exec) === false) {
            $server_data = db_get_row('tserver', 'id_server', $server_to_exec);

            if (empty($server_data['port'])) {
                exec('ssh pandora_exec_proxy@'.$server_data['ip_address'].' "'.$command_str.'"', $output, $rc);
            } else {
                exec('ssh -p '.$server_data['port'].' pandora_exec_proxy@'.$server_data['ip_address'].' "'.$command_str.'"', $output, $rc);
            }
        } else {
            exec($command_str, $output, $rc);
        }
    } else {
        exec($command_str, $output, $rc);
    }

    $snmpwalk = [];

    // Check if OID is available.
    if (count($output) == 1 && strpos($output[0], 'No Such Object available on this agent at this OID') !== false) {
        return $snmpwalk;
    }

    // Parse the output of snmpwalk.
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
    if (preg_match('/([ ]*(delete|drop|alter|modify|password|pass|insert|update)\b[ \\]+)/i', $sql)) {
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

        // No exists $config. Exit inmediatly.
        include 'general/noaccess.php';
        exit;
    }

    if ((isset($_SESSION['id_usuario'])) && ($_SESSION['id_usuario'] != '')) {
        if (is_user($_SESSION['id_usuario'])
            || (isset($_SESSION['merge-request-user-trick']) === true
            && $_SESSION['merge-request-user-trick'] === $_SESSION['id_usuario'])
        ) {
            if (isset($config['auth']) === true && $config['auth'] === 'ad' && is_user($_SESSION['id_usuario'])) {
                // User name in active directory is case insensitive.
                // Get the user name from database.
                $user_info = get_user_info($_SESSION['id_usuario']);
                $config['id_user'] = $user_info['id_user'];
            } else {
                $config['id_user'] = $_SESSION['id_usuario'];
            }

            return true;
        }
    } else {
        include_once $config['homedir'].'/mobile/include/db.class.php';
        include_once $config['homedir'].'/mobile/include/system.class.php';
        include_once $config['homedir'].'/mobile/include/user.class.php';

        if (isset($_SESSION['user'])) {
            $user = User::getInstance();
            $id_user = $user->getIdUser();
            if (is_user($id_user)) {
                $_SESSION['id_usuario'] = $id_user;
                $config['id_user'] = $id_user;
                return true;
            }
        }
    }

    if (!$output) {
        return false;
    }

    db_pandora_audit(
        AUDIT_LOG_HACK_ATTEMPT,
        'Trying to access without a valid session',
        'N/A'
    );
    include $config['homedir'].'/general/noaccess.php';
    exit;
}


/**
 * Check access privileges to resources
 *
 * Access can be:
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
 * @param boolean $cache        Use cache.
 *
 * @return boolean 1 if the user has privileges, 0 if not.
 */
function check_acl(
    $id_user,
    $id_group,
    $access,
    $onlyOneGroup=false,
    $cache=true
) {
    if (empty($id_user)) {
        // User ID needs to be specified.
        trigger_error('Security error: check_acl got an empty string for user id', E_USER_WARNING);
        return 0;
    } else if (is_user_admin($id_user)) {
        return 1;
    } else {
        $id_group = (int) $id_group;
    }

    if ($id_group != 0 || $onlyOneGroup === true) {
        $groups_list_acl = users_get_groups(
            $id_user,
            $access,
            false,
            true,
            null,
            'id_grupo',
            $cache
        );
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
 * @param string  $id_user to check the ACL
 * @param array   $groups. All groups to check
 * @param string  $access. Profile to check
 * @param boolean $cache   Use cached group information.
 *
 * @return boolean True if at least one of this groups check the ACL
 */
function check_acl_one_of_groups($id_user, $groups, $access, $cache=true)
{
    foreach ($groups as $group) {
        if (check_acl($id_user, $group, $access, false, $cache)) {
            return true;
        }
    }

    return false;
}


/**
 * Check access privileges to resources (write or management is not allowed for 'all' group )
 *
 * Access can be:
 * AR - Agent Read
 * AW - Agent Write
 * LW - Alert Write
 * UM - User Management
 * DM - DB Management
 * LM - Alert Management
 * PM - Pandora Management
 *
 * @param integer $id_user      User id.
 * @param integer $id_group     Agents group id to check from.
 * @param string  $access       Access privilege.
 * @param boolean $onlyOneGroup Flag to check acl for specified group only (not to roots up, or check acl for 'All' group when $id_group is 0).
 *
 * @return boolean 1 if the user has privileges, 0 if not.
 */
function check_acl_restricted_all($id_user, $id_group, $access, $onlyOneGroup=false)
{
    if (empty($id_user)) {
        // User ID needs to be specified.
        trigger_error('Security error: check_acl got an empty string for user id', E_USER_WARNING);
        return 0;
    } else if (is_user_admin($id_user)) {
        return 1;
    } else {
        $id_group = (int) $id_group;
    }

    $access_string = get_acl_column($access);

    if ($id_group != 0 || $onlyOneGroup === true) {
        $groups_list_acl = users_get_groups($id_user, $access, false, true, null);
    } else {
        $groups_list_acl = get_users_acl($id_user);

        // Only allow view ACL tokens in case user cannot manage group all.
        if (users_can_manage_group_all($access) === false) {
            if (preg_match('/_view/i', $access_string) == 0) {
                return 0;
            }
        }
    }

    if (is_array($groups_list_acl)) {
        if (isset($groups_list_acl[$id_group])) {
            if (isset($groups_list_acl[$id_group][$access_string])
                && $groups_list_acl[$id_group][$access_string] > 0
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
 * Get the name of the database column of one access flag
 *
 * @param string access flag
 *
 * @return string Column name
 */
function get_acl_column($access)
{
    switch ($access) {
        case 'AR':
        return 'agent_view';

        case 'AW':
        return 'agent_edit';

        case 'AD':
        return 'agent_disable';

        case 'LW':
        return 'alert_edit';

        case 'LM':
        return 'alert_management';

        case 'PM':
        return 'pandora_management';

        case 'DM':
        return 'db_management';

        case 'UM':
        return 'user_management';

        case 'RR':
        return 'report_view';

        case 'RW':
        return 'report_edit';

        case 'RM':
        return 'report_management';

        case 'ER':
        return 'event_view';

        case 'EW':
        return 'event_edit';

        case 'EM':
        return 'event_management';

        case 'MR':
        return 'map_view';

        case 'MW':
        return 'map_edit';

        case 'MM':
        return 'map_management';

        case 'VR':
        return 'vconsole_view';

        case 'VW':
        return 'vconsole_edit';

        case 'VM':
        return 'vconsole_management';

        case 'NR':
        return 'network_config_view';

        case 'NW':
        return 'network_config_edit';

        case 'NM':
        return 'network_config_management';

        default:
        return '';
    }
}


function get_users_acl($id_user)
{
    static $users_acl_cache = [];

    if (isset($users_acl_cache[$id_user]) === true
        && is_array($users_acl_cache[$id_user]) === true
    ) {
        $rowdup = $users_acl_cache[$id_user];
    } else {
        $query = sprintf(
            "SELECT sum(tperfil.agent_view) as agent_view,
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
						sum(tperfil.vconsole_management) as vconsole_management,
                        sum(tperfil.network_config_view) as network_config_view,
						sum(tperfil.network_config_edit) as network_config_edit,
						sum(tperfil.network_config_management) as network_config_management
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
 * @param integer $id_user      User id.
 * @param integer $id_dashboard Dashboard id.
 *
 * @return array Dashboard name of the given user.
 */
function get_user_dashboards($id_user, $id_dashboard=null)
{
    if (users_is_admin($id_user)) {
        $sql = "SELECT id, name
			FROM tdashboard WHERE id_user = '".$id_user."' OR id_user = ''";
    } else {
        $user_can_manage_all = users_can_manage_group_all('RR');
        if ($user_can_manage_all) {
            $sql = "SELECT id, name
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

            $sql = 'SELECT id, name
				FROM tdashboard
				WHERE id_group IN ('.implode(',', $u_groups).") AND (id_user = '".$id_user."' OR id_user = '')";
        }
    }

    if ($id_dashboard !== null) {
        $sql .= sprintf(' AND id = %d', $id_dashboard);
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the possible periods in seconds.
 *
 * @param boolean $custom       Flag to show or not custom fist option
 * @param boolean $show_default Show the periods by default if it is empty
 * @param boolean $allow_zero   Allow the use of the value zero.
 *
 * @return array The possible periods in an associative array.
 */
function get_periods($custom=true, $show_default=true, $allow_zero=false)
{
    global $config;

    $periods = [];

    if ($custom) {
        $periods[-1] = __('custom');
    }

    if (empty($config['interval_values'])) {
        if ($show_default) {
            if ($allow_zero === true) {
                $periods[0] = sprintf(__('%s seconds'), '0');
            }

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
    if (isset($data) === false) {
        return false;
    }

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
    if (isset($data) === false || is_image_data($data)) {
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

    static $userinfo;

    if ($userinfo === null) {
        $userinfo = get_user_info($config['id_user']);
    }

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
            $message .= __('Please check this PHP runtime variable values: <pre>  upload_max_filesize (currently '.ini_get('upload_max_filesize').')</pre>');
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
    global $config;

    sort($array);
    $index = (($config['percentil'] / 100) * count($array));

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


function date2strftime_format($date_format, $timestamp=null)
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
        'O' => '%z',
        'T' => '%Z',
        '%' => '%%',
        'G' => '%k',
        'z' => '%j',
        'U' => '%s',
        'c' => '%FT%T%z',
        'r' => '%d %b %Y %H:%M:%S %z',
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
            // Check extra formats.
            switch ($date_format) {
                default: $return .= date($date_format, $timestamp);
                break;

                case 'n':
                    if (stristr(PHP_OS, 'win')) {
                        $return .= '%#m';
                    } else {
                        $return .= '%-m';
                    }

                case 'u':
                    if (preg_match('/^[0-9]*\\.([0-9]+)$/', $timestamp, $reg)) {
                        $decimal = substr(str_pad($reg[1], 6, '0'), 0, 6);
                    } else {
                        $decimal = '000000';
                    }

                    $return .= $decimal;
                break;

                break;
            }
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


function update_check_config_token($cfgtoken, $cfgvalue)
{
    global $config;
    db_process_sql('START TRANSACTION');
    if (isset($config[$cfgtoken])) {
        delete_config_token($cfgtoken);
    }

    $insert = db_process_sql(sprintf("INSERT INTO tconfig (token, value) VALUES ('%s', '%s')", $cfgtoken, $cfgvalue));
    db_process_sql('COMMIT');
    if ($insert) {
        $config[$cfgtoken] = $cfgvalue;
        return true;
    } else {
        return false;
    }
}


function delete_config_token($cfgtoken)
{
    $delete = db_process_sql(sprintf('DELETE FROM tconfig WHERE token = "%s"', $cfgtoken));

    if ($delete) {
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
                    if ($num <= $config['MR']) {
                        continue;
                    }

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


/**
 * Label graph Sparse.
 *
 * @param array $data                Data chart.
 * @param array $show_elements_graph Data visual styles chart.
 *
 * @return array Array label.
 */
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

    if (isset($show_elements_graph['array_colors']) === true
        && empty($show_elements_graph['array_colors']) === false
        && is_array($show_elements_graph['array_colors']) === true
    ) {
        $color_series = $show_elements_graph['array_colors'];
    } else {
        $color_series = color_graph_array();
    }

    if ($show_elements_graph['id_widget_dashboard']) {
        $opcion = unserialize(
            db_get_value_filter(
                'options',
                'twidget_dashboard',
                ['id' => $show_elements_graph['id_widget_dashboard']]
            )
        );
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
            $str = '';
            if ($show_elements_graph['compare'] == 'overlapped') {
                if ($key == 'sum2') {
                    $str = ' ('.__('Previous').')';
                }
            }

            if (strpos($key, 'summatory') !== false) {
                $data_return['series_type'][$key] = $type_graph;
                $data_return['legend'][$key] = __('Summatory series').' '.$str;
                $data_return['color'][$key] = $color_series['summatory'];
            } else if (strpos($key, 'average') !== false) {
                $data_return['series_type'][$key] = $type_graph;
                $data_return['legend'][$key] = __('Average series').' '.$str;
                $data_return['color'][$key] = $color_series['average'];
            } else if (strpos($key, 'sum') !== false
                || strpos($key, 'baseline') !== false
            ) {
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
                        $name_legend = $show_elements_graph['labels'][$value['agent_module_id']];
                        $name_legend .= ' / ';
                        $name_legend .= __('Unit ').' ';
                        $name_legend .= $show_elements_graph['unit'].': ';
                    } else {
                        if (isset($show_elements_graph['from_interface']) === true
                            && (bool) $show_elements_graph['from_interface'] === true
                        ) {
                            $label_interfaces = array_flip($show_elements_graph['modules_series']);
                            $name_legend = $show_elements_graph['labels'][$value['agent_module_id']][$label_interfaces[$value['agent_module_id']]].': ';
                        } else if (is_array($show_elements_graph['labels'][$value['agent_module_id']]) === true) {
                            $name_legend = 'Avg: ';

                            if (array_key_exists('agent_alias', $value)
                                && array_key_exists('module_name', $value)
                                && array_key_exists('unit', $value)
                            ) {
                                $name_legend .= $value['agent_alias'];
                                $name_legend .= ' / ';
                                $name_legend .= $value['module_name'];
                                $name_legend .= ' / ';
                                $name_legend .= __('Unit ').' ';
                                $name_legend .= $value['unit'].': ';
                            }
                        } else {
                            $name_legend = $show_elements_graph['labels'][$value['agent_module_id']].': ';
                        }
                    }
                } else {
                    if (strpos($key, 'baseline') !== false) {
                        if ($value['unit']) {
                            $name_legend = $value['agent_alias'];
                            $name_legend .= ' / ';
                            $name_legend .= $value['module_name'];
                            $name_legend .= ' / ';
                            $name_legend .= __('Unit ').' ';
                            $name_legend .= $value['unit'].'Baseline ';
                        } else {
                            $name_legend = $value['agent_alias'];
                            $name_legend .= ' / ';
                            $name_legend .= $value['module_name'].'Baseline ';
                        }
                    } else {
                        $name_legend = '';

                        if (isset($show_elements_graph['graph_analytics']) === true && $show_elements_graph['graph_analytics'] === true) {
                            $name_legend .= '<div class="graph-analytics-legend-main">';
                                $name_legend .= '<div class="graph-analytics-legend-square" style="background-color: '.$color_series[$i]['color'].';">';
                                    $name_legend .= '<span class="square-value">';
                                    $name_legend .= format_for_graph(
                                        end(end($value['data'])),
                                        1,
                                        $config['decimal_separator'],
                                        $config['thousand_separator'],
                                        1000,
                                        '',
                                        true
                                    );
                                    $name_legend .= '</span>';
                                    $name_legend .= '<span class="square-unit" title="'.$value['unit'].'">'.$value['unit'].'</span>';
                                $name_legend .= '</div>';
                                $name_legend .= '<div class="graph-analytics-legend">';
                                    $name_legend .= '<span>'.$value['agent_alias'].'</span>';
                                    $name_legend .= '<span title="'.$value['module_name'].'">'.$value['module_name'].'</span>';
                                $name_legend .= '</div>';
                            $name_legend .= '</div>';
                        } else {
                            if (isset($show_elements_graph['fullscale']) === true
                                && (int) $show_elements_graph['fullscale'] === 1
                            ) {
                                $name_legend .= 'Tip: ';
                            } else {
                                $name_legend .= 'Avg: ';
                            }

                            if ($value['unit']) {
                                $name_legend .= $value['agent_alias'];
                                $name_legend .= ' / ';
                                $name_legend .= $value['module_name'];
                                $name_legend .= ' / ';
                                $name_legend .= __('Unit ').' ';
                                $name_legend .= $value['unit'].': ';
                            } else {
                                $name_legend .= $value['agent_alias'];
                                $name_legend .= ' / ';
                                $name_legend .= $value['module_name'].': ';
                            }
                        }
                    }
                }

                if (isset($value['weight']) === true
                    && empty($value['weight']) === false
                ) {
                    $name_legend .= ' ('.__('Weight');
                    $name_legend .= ' * '.$value['weight'].') ';
                }

                $data_return['legend'][$key] = '<span style="font-size: 9pt; font-weight: bolder;">'.$name_legend.'</span>';
                if ((int) $value['min'] === PHP_INT_MAX) {
                    $value['min'] = 0;
                }

                if ((int) $value['max'] === (-PHP_INT_MAX)) {
                    $value['max'] = 0;
                }

                if (isset($show_elements_graph['graph_analytics']) === false) {
                    $data_return['legend'][$key] .= '<span class="legend-font-small">'.__('Min').' </span><span class="bolder">'.remove_right_zeros(
                        number_format(
                            $value['min'],
                            $config['graph_precision'],
                            $config['csv_decimal_separator'],
                            $config['csv_decimal_separator'] == ',' ? '.' : ','
                        )
                    ).' '.$value['unit'].'</span>&nbsp;<span class="legend-font-small">'.__('Max').' </span><span class="bolder">'.remove_right_zeros(
                        number_format(
                            $value['max'],
                            $config['graph_precision'],
                            $config['csv_decimal_separator'],
                            $config['csv_decimal_separator'] == ',' ? '.' : ','
                        )
                    ).' '.$value['unit'].'</span>&nbsp;<span class="legend-font-small">'._('Avg.').' </span><span class="bolder">'.remove_right_zeros(
                        number_format(
                            $value['avg'],
                            $config['graph_precision'],
                            $config['csv_decimal_separator'],
                            $config['csv_decimal_separator'] == ',' ? '.' : ','
                        )
                    ).' '.$value['unit'].'</span>&nbsp;'.$str;
                }

                if ($show_elements_graph['compare'] == 'overlapped'
                    && $key == 'sum2'
                ) {
                    $data_return['color'][$key] = $color_series['overlapped'];
                } else {
                    $data_return['color'][$key] = $color_series[$i];
                    $i++;
                }
            } else if (!$show_elements_graph['fullscale']
                && strpos($key, 'min') !== false
                || !$show_elements_graph['fullscale']
                && strpos($key, 'max') !== false
            ) {
                $data_return['series_type'][$key] = $type_graph;

                $name_legend = '';

                if ((int) $show_elements_graph['type_mode_graph'] != 0) {
                    if (strpos($key, 'min') !== false) {
                        $name_legend .= 'Min: ';
                    }

                    if (strpos($key, 'max') !== false) {
                        $name_legend .= 'Max: ';
                    }
                }

                if ($show_elements_graph['unit']) {
                    $name_legend .= $value['agent_alias'];
                    $name_legend .= ' / ';
                    $name_legend .= $value['module_name'];
                    $name_legend .= ' / ';
                    $name_legend .= __('Unit ').' ';
                    $name_legend .= $show_elements_graph['unit'].': ';
                } else {
                    $name_legend .= $value['agent_alias'];
                    $name_legend .= ' / ';
                    $name_legend .= $value['module_name'].': ';
                }

                $data_return['legend'][$key] = '<span style="font-size: 9pt; font-weight: bolder;">'.$name_legend.'</span>';
                if ($show_elements_graph['type_mode_graph']) {
                    $data_return['legend'][$key] .= '<span class="legend-font-small">'.__('Min:').' </span><span class="bolder">';
                    $data_return['legend'][$key] .= remove_right_zeros(
                        number_format(
                            $value['min'],
                            $config['graph_precision'],
                            $config['decimal_separator'],
                            $config['thousand_separator']
                        )
                    ).' '.$value['unit'];
                    $data_return['legend'][$key] .= '</span>&nbsp;<span class="legend-font-small">'.__('Max:').' </span><span class="bolder">';
                    $data_return['legend'][$key] .= remove_right_zeros(
                        number_format(
                            $value['max'],
                            $config['graph_precision'],
                            $config['decimal_separator'],
                            $config['thousand_separator']
                        )
                    ).' '.$value['unit'];
                    $data_return['legend'][$key] .= '</span>&nbsp;<span class="legend-font-small">'._('Avg:').' </span><span class="bolder">';
                    $data_return['legend'][$key] .= remove_right_zeros(
                        number_format(
                            $value['avg'],
                            $config['graph_precision'],
                            $config['decimal_separator'],
                            $config['thousand_separator']
                        )
                    ).' '.$value['unit'].' </span>&nbsp;'.$str;
                }

                if ($show_elements_graph['compare'] == 'overlapped'
                    && $key == 'sum2'
                ) {
                    $data_return['color'][$key] = $color_series['overlapped'];
                } else {
                    $data_return['color'][$key] = $color_series[$i];
                    $i++;
                }
            } else if (strpos($key, 'event') !== false) {
                $data_return['series_type'][$key] = 'points';
                if ($show_elements_graph['show_events']) {
                    $data_return['legend'][$key] = '<span style="font-size: 9pt; font-weight: bolder;">'.__('Events').'</span>'.$str;
                }

                $data_return['color'][$key] = $color_series['event'];
            } else if (strpos($key, 'alert') !== false) {
                $data_return['series_type'][$key] = 'points';
                if ($show_elements_graph['show_alerts']) {
                    $data_return['legend'][$key] = '<span style="font-size: 9pt; font-weight: bolder;">'.__('Alert').'</span>'.$str;
                }

                $data_return['color'][$key] = $color_series['alert'];
            } else if (strpos($key, 'unknown') !== false) {
                $data_return['series_type'][$key] = 'unknown';
                if ($show_elements_graph['show_unknown']) {
                    $data_return['legend'][$key] = '<span style="font-size: 9pt; font-weight: bolder;">'.__('Unknown').'</span>'.$str;
                }

                $data_return['color'][$key] = $color_series['unknown'];
            } else if (strpos($key, 'percentil') !== false) {
                $data_return['series_type'][$key] = 'percentil';
                if ($show_elements_graph['percentil']) {
                    if ($show_elements_graph['unit']) {
                        $name_legend = '<span style="font-size: 9pt; font-weight: bolder;">'.__('Percentil').'</span>';
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
                            $config['graph_precision'],
                            $config['decimal_separator'],
                            $config['thousand_separator']
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


/**
 * Draw chart pdf.
 *
 * @param string  $type_graph_pdf  Type graph.
 * @param array   $params          Params.
 * @param boolean $params_combined Params only charts combined.
 * @param boolean $module_list     Array modules.
 *
 * @return string Img or base64.
 */
function generator_chart_to_pdf(
    $type_graph_pdf,
    $params,
    $params_combined=false,
    $module_list=false
) {
    global $config;
    $hack_metaconsole = '';
    if (is_metaconsole() === true) {
        $hack_metaconsole = '../..';
    }

    if (!$params['return_img_base_64']) {
        $img_file = 'img_'.uniqid().'.png';
        $img_path = $config['homedir'].'/attachment/'.$img_file;
        $img_url  = ui_get_full_url(false).$hack_metaconsole.'/attachment/'.$img_file;
    }

    if ($type_graph_pdf !== 'combined') {
        $params_combined = [];
        $module_list = [];
    }

    // If not install chromium avoid 500 convert tu images no data to show.
    $chromium_dir = io_safe_output($config['chromium_path']);
    $result_ejecution = exec($chromium_dir.' --version');
    if (empty($result_ejecution) === true) {
        if ($params['return_img_base_64']) {
            $params['base64'] = true;
        }

        return graph_nodata_image($params);
    }

    try {
        $browserFactory = new BrowserFactory($chromium_dir);

        // Starts headless chrome.
        $browser = $browserFactory->createBrowser(['noSandbox' => true]);

        // Creates a new page.
        $page = $browser->createPage();

        // Generate Html.
        $html = chart_generator(
            $type_graph_pdf,
            $params,
            $params_combined,
            $module_list
        );

        $page->setHtml($html);

        // Dynamic.
        $dynamic_height = $page->evaluate('document.getElementById("container-chart-generator-item").clientHeight')->getReturnValue();
        if (empty($dynamic_height) === true) {
            $dynamic_height = 200;
        }

        if (isset($params['options']['viewport']) === true
            && isset($params['options']['viewport']['height']) === true
            && empty($params['options']['viewport']['height']) === false
        ) {
            $dynamic_height = $params['options']['viewport']['height'];
        }

        $dynamic_width = $page->evaluate('document.getElementById("container-chart-generator-item").clientWidth')->getReturnValue();
        if (empty($dynamic_width) === true) {
            $dynamic_width = 794;
        }

        if (isset($params['options']['viewport']) === true
            && isset($params['options']['viewport']['width']) === true
            && empty($params['options']['viewport']['width']) === false
        ) {
            $dynamic_width = $params['options']['viewport']['width'];
        }

        $clip = new Clip(0, 0, $dynamic_width, $dynamic_height);

        if ($params['return_img_base_64']) {
            $b64 = $page->screenshot(['clip' => $clip])->getBase64();
            // To be used in alerts.
            return $b64;
        } else {
            // To be used in PDF files.
            $b64 = $page->screenshot(['clip' => $clip])->saveToFile($img_path);
            $config['temp_images'][] = $img_path;
            return '<img src="'.$img_url.'" />';
        }
    } catch (\Throwable $th) {
        error_log($th);
    } finally {
        $browser->close();
    }
}


/**
 * Html print chart for chromium
 *
 * @param string $type_graph_pdf  Chart mode.
 * @param array  $params          Params.
 * @param array  $params_combined Params Combined charts.
 * @param array  $module_list     Module list Combined charts.
 *
 * @return string Output Html.
 */
function chart_generator(
    string $type_graph_pdf,
    array $params,
    array $params_combined=[],
    array $module_list=[]
) : string {
    global $config;

    include_once $config['homedir'].'/include/graphs/functions_d3.php';

    if (isset($params['backgroundColor']) === false) {
        $params['backgroundColor'] = 'inherit';
    }

    $hack_metaconsole = (is_metaconsole() === true) ? '../../' : '';

    $output = '<!DOCTYPE>';
    $output .= '<html>';
    $output .= '<head>';
    $output .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    $output .= '<title>Pandora FMS Graph</title>';
    $output .= '<script type="text/javascript">';
    $output .= 'var phpTimezone = "'.date_default_timezone_get().'";';
    $output .= 'var configHomeurl = "'.((is_metaconsole() === false) ? $config['homeurl'] : '../../').'";';
    $output .= '</script>';

    $css_files = [
        'pandora'          => 'include/styles/',
        'pandora_minimal'  => 'include/styles/',
        'jquery-ui.min'    => 'include/styles/js/',
        'jquery-ui_custom' => 'include/styles/js/',
    ];

    foreach ($css_files as $name => $path) {
        $output .= ui_require_css_file($name, $path, true, true);
    }

    $js_files = [
        'pandora_ui'                     => 'include/javascript/',
        'jquery.current'                 => 'include/javascript/',
        'jquery.pandora'                 => 'include/javascript/',
        'jquery-ui.min'                  => 'include/javascript/',
        'date'                           => 'include/javascript/timezone/src/',
        'pandora'                        => 'include/javascript/',
        'jquery.flot'                    => 'include/graphs/flot/',
        'jquery.flot.min'                => 'include/graphs/flot/',
        'jquery.flot.time'               => 'include/graphs/flot/',
        'jquery.flot.pie'                => 'include/graphs/flot/',
        'jquery.flot.crosshair.min'      => 'include/graphs/flot/',
        'jquery.flot.stack.min'          => 'include/graphs/flot/',
        'jquery.flot.selection.min'      => 'include/graphs/flot/',
        'jquery.flot.resize.min'         => 'include/graphs/flot/',
        'jquery.flot.threshold'          => 'include/graphs/flot/',
        'jquery.flot.threshold.multiple' => 'include/graphs/flot/',
        'jquery.flot.symbol.min'         => 'include/graphs/flot/',
        'jquery.flot.exportdata.pandora' => 'include/graphs/flot/',
        'jquery.flot.axislabels'         => 'include/graphs/flot/',
        'pandora.flot'                   => 'include/graphs/flot/',
        'chart'                          => 'include/graphs/chartjs/',
        'chartjs-plugin-datalabels.min'  => 'include/graphs/chartjs/',
    ];

    foreach ($js_files as $name => $path) {
        $output .= ui_require_javascript_file($name, $path, true, true);
    }

    $output .= include_javascript_d3(true, true);

    $output .= '</head>';
    $output .= '<body style="width:794px; margin: 0px; background-color:'.$params['backgroundColor'].';">';
    $params['only_image'] = false;
    $params['menu'] = false;
    $params['disable_black'] = true;

    $viewport = [
        'width'  => 0,
        'height' => 0,
    ];

    $style = 'width:100%;';
    if (isset($params['options']['viewport']) === true) {
        $viewport = $params['options']['viewport'];
        if (empty($viewport['width']) === false) {
            $style .= 'width:'.$viewport['width'].'px;';
        }

        if (empty($viewport['height']) === false) {
            $style .= 'height:'.$viewport['height'].'px;';
        }
    }

    $output .= '<div id="container-chart-generator-item" style="'.$style.' margin:0px;">';
    switch ($type_graph_pdf) {
        case 'combined':
            $params['pdf'] = true;
            $result = graphic_combined_module(
                $module_list,
                $params,
                $params_combined
            );

            $output .= $result;
        break;

        case 'sparse':
            $params['pdf'] = true;
            $output .= grafico_modulo_sparse($params);
        break;

        case 'pie_graph':
            $params['pdf'] = true;
            $chart = get_build_setup_charts(
                'PIE',
                $params['options'],
                $params['chart_data']
            );

            $output .= $chart->render(true);
        break;

        case 'vbar_graph':
            $params['pdf'] = true;
            $chart = get_build_setup_charts(
                'BAR',
                $params['options'],
                $params['chart_data']
            );

            $output .= $chart->render(true);
        break;

        case 'ring_graph':
            $params['pdf'] = true;
            $params['options']['width'] = 500;
            $params['options']['height'] = 500;

            $chart = get_build_setup_charts(
                'DOUGHNUT',
                $params['options'],
                $params['chart_data']
            );

            $output .= $chart->render(true);
        break;

        case 'line_graph':
            $params['pdf'] = true;
            $params['options']['width'] = '100%';
            $params['options']['height'] = 200;
            $chart = get_build_setup_charts(
                'LINE',
                $params['options'],
                $params['chart_data']
            );
            $output .= $chart->render(true);
        break;

        case 'slicebar':
            $output .= flot_slicesbar_graph(
                $params['graph_data'],
                $params['period'],
                $params['width'],
                $params['height'],
                $params['legend'],
                $params['colors'],
                $params['fontpath'],
                $params['round_corner'],
                $params['homeurl'],
                $params['watermark'],
                $params['adapt_key'],
                $params['stat_winalse'],
                $params['id_agent'],
                $params['full_legend_daterray'],
                $params['not_interactive'],
                $params['ttl'],
                $params['sizeForTicks'],
                $params['show'],
                $params['date_to'],
                $params['server_id']
            );
        break;

        default:
            // Code...
        break;
    }

    $output .= '</div>';
    $output .= '</body>';
    $output .= '</html>';

    return $output;
}


/**
 * Get the product name.
 *
 * @return string If the installation is open, it will be 'Pandora FMS'.
 *         If the product name stored is empty, it returns 'Pandora FMS' too.
 */
function get_product_name()
{
    global $config;

    $stored_name = enterprise_hook('enterprise_get_product_name');
    if (empty($stored_name) || $stored_name == ENTERPRISE_NOT_HOOK) {
        if (isset($config['rb_product_name_alt']) === true
            && empty($config['rb_product_name_alt']) === false
        ) {
            return $config['rb_product_name_alt'];
        }

        return 'Pandora FMS';
    }

    return $stored_name;
}


/**
 * Get the copyright notice.
 *
 * @return string If the installation is open, it will be 'Pandora FMS'.
 *         If the product name stored is empty, it returns 'Pandora FMS' too.
 */
function get_copyright_notice()
{
    $stored_name = enterprise_hook('enterprise_get_copyright_notice');
    if (empty($stored_name) || $stored_name == ENTERPRISE_NOT_HOOK) {
        return 'PandoraFMS.com';
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
 *         "console": Display with a message in console.log.
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
        break;

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

    $user_language = get_user_language($config['id_user']);

    $es = false;
    $result = 'https://pandorafms.com/manual/en/documentation/';
    if ($user_language == 'es') {
        $es = true;
        $result = 'https://pandorafms.com/manual/es/documentation/';
    }

    switch ($section_name) {
        case 'snmp_browser_view':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#navegador_snmp_de_pandora_fms';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#pandora_fms_snmp_browser';
            }
        break;

        case 'snmp_trap_generator_view':
            if ($es) {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#generador_de_traps';
            } else {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#trap_generator';
            }
        break;

        case 'real_time_view':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization#graficas_real-time';
            } else {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization#real-time_graphs';
            }
        break;

        case 'agent_main_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#visualizacion_del_agente';
            } else {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#agent_display';
            }
        break;

        case 'alert_config':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#creacion_de_una_accion';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#creating_an_action';
            }
        break;

        case 'alert_macros':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#macros_sustituibles_en_los_campos_field1_field10';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#replaceable_macros_within_field1_field10';
            }
        break;

        case 'alerts_config':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts';
            }
        break;

        case 'alert_special_days':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#lista_de_dias_especiales';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#list_of_special_days';
            }
        break;

        case 'alerts':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#Alertas';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#Alerts';
            }
        break;

        case 'collections':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#Colecciones_de_ficheros';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#File_Collections';
            }
        break;

        case 'component_groups':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/03_templates_and_components#grupos_de_componentes';
            } else {
                $result .= 'pandorafms/management_and_operation/03_templates_and_components#component_groups';
            }
        break;

        case 'configure_gis_map_edit':
            if ($es) {
                $result .= 'pandorafms/monitoring/20_gis#mapas_gis';
            } else {
                $result .= 'pandorafms/monitoring/20_gis#gis_maps';
            }
        break;

        case 'event_alert':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/02_events#event_alerts_event_correlation';
            } else {
                $result .= 'pandorafms/management_and_operation/02_events#event_alerts_event_correlation';
            }
        break;

        case 'eventview':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/02_events#introduccion';
            } else {
                $result .= 'pandorafms/management_and_operation/02_events#introduction';
            }
        break;

        case 'export_server':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/03_export_server#anadir_un_servidor_de_destino';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/03_export_server=yes#adding_a_target_server';
            }
        break;

        case 'external_alert':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#external_alerts';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#external_alerts';
            }
        break;

        case 'gis_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/20_gis#configuracion_del_agent_gis';
            } else {
                $result .= 'pandorafms/monitoring/20_gis#the_agent_s_gis_setup';
            }
        break;

        case 'graph_builder':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization#crear_graficas_combinadas';
            } else {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization#creating_combined_graphs';
            }
        break;

        case 'graph_editor':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization#agregar_elementos_a_graficas_combinadas';
            } else {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization#adding_elements_to_combined_graphs';
            }
        break;

        case 'dashboards_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/09_dashboard#introduccion';
            } else {
                $result .= 'pandorafms/management_and_operation/09_dashboard#introduction';
            }
        break;

        case 'history_database':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#base_de_datos_historica';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#the_history_database';
            }
        break;

        case 'inventory_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/04_inventory#modulos_de_inventario';
            } else {
                $result .= 'pandorafms/management_and_operation/04_inventory#inventory_modules';
            }
        break;

        case 'ipam_list_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#introduccion';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#introduction';
            }
        break;

        case 'ipam_calculator_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#calculadora_de_subredes';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#subnetwork_calculator';
            }
        break;

        case 'ipam_vlan_config_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#vlan_ipam';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#vlan_ipam';
            }
        break;

        case 'ipam_vlan_statistics_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#estadisticas_ipam_vlan';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#ipam_vlan_stats';
            }
        break;

        case 'ipam_vlan_wizard_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#wizard_ipam_vlan';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#ipam_vlan_wizard:';
            }
        break;

        case 'ipam_supernet_config_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#ipam_supernet';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#ipam_supernet';
            }
        break;

        case 'ipam_supernet_map_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#mapa_superred_ipam';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#ipam_supernet_map';
            }
        break;

        case 'ipam_supernet_statistics_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#estadisticas_ipam_vlan';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#ipam_supernet_stats';
            }
        break;

        case 'ipam_new_tab':
        case 'ipam_edit_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#vista_de_edicion';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#edit_view';
            }
        break;

        case 'ipam_massive_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#vista_operaciones_masivas';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#mass_operations_view';
            }
        break;

        case 'ipam_network_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#vista_de_edicion';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#edit_view';
            }
        break;

        case 'ipam_force_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/11_ipam#vista_de_iconos';
            } else {
                $result .= 'pandorafms/monitoring/11_ipam#icon_view';
            }
        break;

        case 'macros_visual_maps':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#macros_en_las_consolas_visuales';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#Macros_in_Visual_Consoles';
            }
        break;

        case 'linked_map_status_calc':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#opciones_avanzadas_de_cada_elemento';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#advanced_options_of_each_element';
            }
        break;

        case 'main_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#configuracion_de_un_agente_logico_en_consola';
            } else {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#agent_setup_in_the_console';
            }
        break;

        case 'manage_alert_list':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#gestionar_alertas_desde_el_agente';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#managing_alerts_from_within_the_agent';
            }
        break;

        case 'alert_scalate':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#escalado_de_alertas';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#scaling_alerts';
            }
        break;

        case 'network_map_enterprise_edit':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#mapa_de_red_no_vacio';
            } else {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#non_empty_network_map';
            }
        break;

        case 'network_map_enterprise_list':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#introduccion';
            } else {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#introduction';
            }
        break;

        case 'network_map_enterprise_empty':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#mapa_de_red_vacio';
            } else {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#empty_network_map';
            }
        break;

        case 'network_map_enterprise_view':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#mapa_de_red_vacio';
            } else {
                $result .= 'pandorafms/management_and_operation/06_data_presentation_network_maps#empty_network_map';
            }
        break;

        case 'pcap_filter':
            if ($es) {
                $result .= 'pandorafms/monitoring/18_netflow#creacion_del_filtro';
            } else {
                $result .= 'pandorafms/monitoring/18_netflow#filter_creation';
            }
        break;

        case 'planned_downtime':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#introduccion';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#introduction';
            }
        break;

        case 'planned_downtime_editor':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#creacion_parada_planificada';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#create_a_scheduled_downtime';
            }
        break;

        case 'plugin_definition':
            if ($es) {
                $result .= 'pandorafms/technical_reference/05_anexo_server_plugins_development#registro_manual_de_un_plugin_en_la_consola';
            } else {
                $result .= 'pandorafms/technical_reference/05_anexo_server_plugins_development#plugin_manual_registration';
            }
        break;

        case 'plugin_macros':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#macros_internas';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#internal_macros';
            }
        break;

        case 'prediction_source_module':
            if ($es) {
                $result .= 'pandorafms/monitoring/10_other_monitoring#tipos_de_monitorizacion_predictiva';
            } else {
                $result .= 'pandorafms/monitoring/10_other_monitoring#types_of_predictive_monitoring';
            }
        break;

        case 'wmi_module_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#monitorizacion_de_windows_remotos_con_wmi';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#windows_remote_monitoring_with_wmi';
            }
        break;

        case 'template_reporting_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#introduccion';
            } else {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#introduction';
            }
        break;

        case 'reporting_template_list_item_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#pestana_list_items';
            } else {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#the_list_items_tab';
            }
        break;

        case 'reporting_template_item_editor_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#pestana_item_editor';
            } else {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#item_editor_tab';
            }
        break;

        case 'reporting_template_advanced_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#opciones_avanzadas_de_informe';
            } else {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#report_advanced_options';
            }
        break;

        case 'reporting_item_editor_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#pestana_item_editor';
            } else {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#item_editor_tab';
            }
        break;

        case 'response_macros':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/02_events#event_responses_macros';
            } else {
                $result .= 'pandorafms/management_and_operation/02_events#event_responses_macros';
            }
        break;

        case 'servers':
            if ($es) {
                $result .= 'pandorafms/installation/03_interface#gestion_de_servidores';
            } else {
                $result .= 'pandorafms/installation/03_interface#server_management';
            }
        break;

        case 'snmpwalk':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#navegador_snmp_de_pandora_fms';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#pandora_fms_snmp_browser';
            }
        break;

        case 'wizard_reporting_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#Asistente_de_plantillas';
            } else {
                $result .= 'pandorafms/management_and_operation/08_data_presentation_reports#Template_Wizard';
            }
        break;

        case 'user_edit_notifications':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#Configuraci.C3.B3n_de_notificaciones';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#Notification_setup';
            }
        break;

        case 'view_services':
            if ($es) {
                $result .= 'pandorafms/monitoring/07_services#introduccion';
            } else {
                $result .= 'pandorafms/monitoring/07_services#introduction';
            }
        break;

        case 'visual_console_editor_data_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creacion_-_datos_generales';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creation_-_general_data';
            }
        break;

        case 'visual_console_editor_editor_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creacion_y_edicion_de_consolas_visuales';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creation_and_edition_of_visual_consoles';
            }
        break;

        case 'visual_console_editor_list_elements_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creacion_-_lista_de_elementos';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creation_-_list_of_elements';
            }
        break;

        case 'visual_console_editor_wizard_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creacion_-_wizard';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creation_-_wizard';
            }
        break;

        case 'visual_console_editor_wizard_services_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creacion_-_wizard_de_servicios';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#creation_-_service_wizard';
            }
        break;

        case 'visual_console_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#opciones_avanzadas_de_cada_elemento';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#advanced_options_of_each_element';
            }
        break;

        case 'config_service_element_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/07_services#configuracion_de_elementos';
            } else {
                $result .= 'pandorafms/monitoring/07_services#element_configuration';
            }
        break;

        case 'config_service_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/07_services#configuracion_inicial';
            } else {
                $result .= 'pandorafms/monitoring/07_services#initial_configuration';
            }
        break;

        case 'other_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#otra_configuracion';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#other_configuration';
            }
        break;

        case 'services_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_servicios';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#service_setup';
            }
        break;

        case 'visual_consoles_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_de_las_consolas_visuales';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#visual_console_setup';
            }
        break;

        case 'charts_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_de_graficas';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#chart_settings';
            }
        break;

        case 'front_and_text_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_de_fuente_y_texto';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#font_and_text_settings';
            }
        break;

        case 'gis_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_gis';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#gis_configuration';
            }
        break;

        case 'style_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_de_estilo';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#style_configuration';
            }
        break;

        case 'behavoir_conf_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_del_comportamiento';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#performance_configuration';
            }
        break;

        case 'setup_ehorus_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#ehorus';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#ehorus';
            }
        break;

        case 'setup_module_library_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#libreria_de_modulos';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#module_library';
            }
        break;

        case 'db_status_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#DB_Schema_Check';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#DB_Schema_Check';
            }
        break;

        case 'database_backup_utility_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#Backup';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#Backup';
            }
        break;

        case 'others_database_maintenance_options_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#otros';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#others';
            }
        break;

        case 'database_maintenance_options_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#opciones_de_mantenimiento_de_la_base_de_datos';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#database_maintenance_options';
            }
        break;

        case 'database_maintenance_status_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#estado_del_mantenimiento_de_las_bases_de_datos';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#database_maintenance_status';
            }
        break;

        case 'historical_database_maintenance_options_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#opciones_de_mantenimiento_de_la_base_de_datos_historica';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#historical_database_maintenance_options';
            }
        break;

        case 'setup_enterprise_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#enterprise';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#features_of_the_enterprise_version';
            }
        break;

        case 'setup_general_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#general_setup';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#general_setup';
            }
        break;

        case 'servers_ha_clusters_tab':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/06_ha#alta_disponibilidad_del_servidor_de_datos';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/06_ha#ha_of_data_server';
            }
        break;

        case 'plugins_tab':
            if ($es) {
                $result .= 'pandorafms/technical_reference/06_anexo_agent_plugins#caracteristicas_basicas_de_plugin_de_agente';
            } else {
                $result .= 'pandorafms/technical_reference/06_anexo_agent_plugins#basic_features_of_the_agent_plugin';
            }
        break;

        case 'create_agent':
            if ($es) {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#configuracion_de_un_agente_logico_en_consola';
            } else {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#agent_setup_in_the_console';
            }
        break;

        case 'module_library':
            if ($es) {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#libreria_de_modulos';
            } else {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#module_library';
            }
        break;

        case 'agent_snmp_explorer_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#wizard_snmp_de_pandora_fms';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#pandora_fms_snmp_wizard';
            }
        break;

        case 'agent_snmp_interfaces_explorer_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#wizard_snmp_de_pandora_fms';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#pandora_fms_snmp_wizard';
            }
        break;

        case 'agent_snmp_wmi_explorer_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#wizard_wmi';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#wmi_wizard';
            }
        break;

        case 'acl_setup_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#introduccion';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#introduction';
            }
        break;

        case 'profile_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#perfiles_en_pandora_fms';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#profiles_in_pandora_FMS';
            }
        break;

        case 'configure_profiles_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#perfiles_en_pandora_fms';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#profiles_in_pandora_fms';
            }
        break;

        case 'network_component_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#parametros_comunes';
            } else {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#common_parameters';
            }
        break;

        case 'local_component_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/03_templates_and_components#componentes_locales';
            } else {
                $result .= 'pandorafms/management_and_operation/03_templates_and_components#local_components';
            }
        break;

        case 'agent_autoconf_tab':
            if ($es) {
                $result .= 'pandorafms/installation/05_configuration_agents#creacionedicion_de_autoconfiguracion';
            } else {
                $result .= 'pandorafms/installation/05_configuration_agents#creation_of_an_automatic_agent_configuration';
            }
        break;

        case 'policies_management_tab':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#introduccion';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/02_policy#introduction';
            }
        break;

        case 'massive_agents_tab':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/01_massive_operations#operaciones_masivasagentes';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/01_massive_operations#massive_operations_-_agents';
            }
        break;

        case 'massive_modules_tab':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/01_massive_operations#operaciones_masivasmodulos';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/01_massive_operations#massive_operationsmodules';
            }
        break;

        case 'massive_policies_tab':
            if ($es) {
                $result .= 'pandorafms/complex_environments_and_optimization/01_massive_operations#editar_modulos_de_politicas_masivamente';
            } else {
                $result .= 'pandorafms/complex_environments_and_optimization/01_massive_operations#edit_policy_modules_massively';
            }
        break;

        case 'alert_templates_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#introduccion3';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#introduction3';
            }
        break;

        case 'configure_alert_template_step_1':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#paso_1general';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#step_1general';
            }
        break;

        case 'configure_alert_template_step_2':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#paso_2condiciones';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#step_2conditions';
            }
        break;

        case 'configure_alert_template_step_3':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#paso_3campos_avanzados';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#step_3advanced_fields';
            }
        break;

        case 'alerts_action':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#introduccion2';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#introduction2';
            }
        break;

        case 'configure_alert_event_step_1':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/02_events#alertas_de_eventos_correlacion_de_eventos';
            } else {
                $result .= 'pandorafms/management_and_operation/02_events#event_alerts_event_correlation';
            }
        break;

        case 'configure_event_rule_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/02_events#alertas_de_eventos_correlacion_de_eventos';
            } else {
                $result .= 'pandorafms/management_and_operation/02_events#event_alerts_event_correlation';
            }
        break;

        case 'snmp_alert_overview_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#introduccion';
            } else {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#introduction';
            }
        break;

        case 'snmp_alert_update_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#anadir_una_alerta';
            } else {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#adding_an_alert';
            }
        break;

        case 'local_module_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#parametros_comunes';
            } else {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#common_parameters';
            }
        break;

        case 'local_module':
            if ($es) {
                $result .= 'pandorafms/monitoring/02_operations#tipos_de_modulos';
            } else {
                $result .= 'pandorafms/monitoring/02_operations#types_of_modules';
            }
        break;

        case 'data_server_module_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/02_operations#tipos_de_modulos';
            } else {
                $result .= 'pandorafms/monitoring/02_operations#types_of_modules';
            }
        break;

        case 'network_module_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#monitorizacion_icmp';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#icmp_monitoring';
            }
        break;

        case 'wux_console':
            if ($es) {
                $result .= 'pandorafms/monitoring/13_user_monitorization#crear_un_modulo_de_analisis_web_en_pandora_fms_console';
            } else {
                $result .= 'pandorafms/monitoring/13_user_monitorization#create_a_web_analysis_module_in_pandora_fms_console';
            }
        break;

        case 'icmp_module_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#monitorizacion_icmp';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#icmp_Monitoring';
            }
        break;

        case 'snmp_module_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#monitorizando_con_modulos_de_red_tipo_snmp';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#monitoring_through_network_modules_with_snmp';
            }
        break;

        case 'tcp_module_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#monitorizacion_tcp';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#tcp_monitoring';
            }
        break;

        case 'webserver_module_tab':
            if ($es) {
                $result .= 'Monitorizacion_web#creacion_de_modulos_web';
            } else {
                $result .= 'Web_Monitoring#creating_web_modules';
            }
        break;

        case 'wmi_query_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#monitorizacion_de_windows_remotos_con_wmi';
            } else {
                $result .= 'pandorafms/monitoring/03_remote_monitoring#windows_remote_monitoring_with_wmi';
            }
        break;

        case 'omnishell':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/16_omnishell';
            } else {
                $result .= 'pandorafms/management_and_operation/16_omnishell';
            }
        break;

        case 'module_type_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/02_operations#tipos_de_modulos';
            } else {
                $result .= 'pandorafms/monitoring/02_operations#types_of_modules';
            }
        break;

        case 'render_view_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/20_gis#operacion';
            } else {
                $result .= 'pandorafms/monitoring/20_gis#operation';
            }
        break;

        case 'quickshell_settings':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#websocket_engine';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#websocket_engine';
            }
        break;

        case 'discovery':
            if ($es) {
                $result .= 'pandorafms/monitoring/04_discovery';
            } else {
                $result .= 'pandorafms/monitoring/04_discovery';
            }

        case 'alert_configure':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#creacion_de_alertas_de_correlacion';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#correlation_alert_creation';
            }
        break;

        case 'alert_correlation':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#correlacion_de_alertasalertas_en_eventos_y_logs';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#alert_correlationevent_and_log_alerts';
            }
        break;

        case 'alert_rules':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#reglas_dentro_de_una_alerta_de_correlacion';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#rules_within_a_correlation_alert';
            }
        break;

        case 'alert_fields':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#paso_3campos_avanzados';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#step_3advanced_fields';
            }
        break;

        case 'alert_triggering':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/01_alerts#configurando_la_plantilla';
            } else {
                $result .= 'pandorafms/management_and_operation/01_alerts#configuring_an_alert_template';
            }
        break;

        case 'log_viewer_advanced_options':
            if ($es) {
                $result .= 'pandorafms/monitoring/09_log_monitoring#visualizacion_y_busqueda_avanzadas';
            } else {
                $result .= 'pandorafms/monitoring/09_log_monitoring#display_and_advanced_search';
            }
        break;

        case 'log_viewer':
            if ($es) {
                $result .= 'pandorafms/monitoring/09_log_monitoring#visualizacion_y_busqueda';
            } else {
                $result .= 'pandorafms/monitoring/09_log_monitoring#display_and_search';
            }
        break;

        case 'elasticsearch_interface':
            if ($es) {
                $result .= 'pandorafms/monitoring/09_log_monitoring#elasticsearch_interface';
            } else {
                $result .= 'pandorafms/monitoring/09_log_monitoring#elasticsearch_interface';
            }
        break;

        case 'snmp_console':
            if ($es) {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#acceso_a_la_consola_de_recepcion_de_traps';
            } else {
                $result .= 'pandorafms/monitoring/08_snmp_traps_monitoring#access_to_trap_reception_console';
            }
        break;

        case 'cluster_view':
            if ($es) {
                $result .= 'pandorafms/monitoring/19_clusters#planificando_la_monitorizacion';
            } else {
                $result .= 'pandorafms/monitoring/19_clusters#planning_monitoring';
            }
        break;

        case 'aws_view':
            if ($es) {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudamazon_web_services_aws';
            } else {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudamazon_web_services_aws';
            }
        break;

        case 'sap_view':
            if ($es) {
                $result .= 'pandorafms/monitoring/04_discovery#sap_view';
            } else {
                $result .= 'pandorafms/monitoring/04_discovery#sap_view';
            }
        break;

        case 'vmware_view':
            if ($es) {
                $result .= 'pandorafms/monitoring/05_virtual_environment_monitoring#gestion_y_visualizacion_de_la_arquitectura_virtual_vmware';
            } else {
                $result .= 'pandorafms/monitoring/05_virtual_environment_monitoring#vmware_virtual_architecture_management_and_display';
            }
        break;

        case 'visual_console_view':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#Elementos_que_puede_contener_un_mapa';
            } else {
                $result .= 'pandorafms/management_and_operation/05_data_presentation_visual_maps#Elements_a_map_can_contain';
            }
        break;

        case 'create_container':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization/#contenedores_de_graficas';
            } else {
                $result .= 'pandorafms/management_and_operation/07_data_presentation_visualization/#graph_containers';
            }
        break;

        case 'setup_ITSM_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/14_incidence_management';
            } else {
                $result .= 'pandorafms/management_and_operation/14_incidence_management';
            }
        break;

        case 'ITSM_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/14_incidence_management#visualizacion_de_tickets';
            } else {
                $result .= 'pandorafms/management_and_operation/14_incidence_management#ticket_display';
            }
        break;

        case 'deployment_center_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/04_discovery#despliegue_automatico_de_agentes';
            } else {
                $result .= 'pandorafms/monitoring/04_discovery#automatic_agent_deployment';
            }
        break;

        case 'Aws_credentials_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudamazon_web_services_aws';
            } else {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudamazon_web_services_aws';
            }
        break;

        case 'Google_credentials_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudgoogle_cloud_platform_gcp';
            } else {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudgoogle_cloud_platform_gcp';
            }
        break;

        case 'Azure_credentials_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudmicrosoft_azure';
            } else {
                $result .= 'pandorafms/monitoring/04_discovery#discovery_cloudmicrosoft_azure';
            }
        break;

        case 'add_policy_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#parametros_comunes';
            } else {
                $result .= 'pandorafms/monitoring/01_intro_monitoring#common_parameters';
            }
        break;

        case 'password_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#password';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#password_policy';
            }
        break;

        case 'setup_netflow_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#netflow';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#netflow';
            }
        break;

        case 'map_connection_tab':
            if ($es) {
                $result .= 'pandorafms/monitoring/20_gis#conexiones_gis';
            } else {
                $result .= 'pandorafms/monitoring/20_gis#gis_connections';
            }
        break;

        case 'command_definition':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/16_omnishell#funcionamiento';
            } else {
                $result .= 'pandorafms/management_and_operation/16_omnishell#usage_example';
            }
        break;

        case 'network_tools_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#Network_Tools';
            } else {
                $result .= 'pandorafms/management_and_operation/11_managing_and_administration#Network_Tools';
            }
        break;

        case 'reports_configuration_tab':
            if ($es) {
                $result .= 'pandorafms/management_and_operation/12_console_setup#configuracion_informes';
            } else {
                $result .= 'pandorafms/management_and_operation/12_console_setup#reports_configuration';
            }
        break;

        default:
            // Default.
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


/**
 * Update config token that contains custom module units.
 *
 * @param  string Name of new module unit.
 * @return boolean Success or failure.
 */
function add_custom_module_unit($value)
{
    global $config;

    $custom_module_units = get_custom_module_units();

    $custom_module_units[$value] = $value;

    $new_conf = json_encode($custom_module_units);

    $return = config_update_value(
        'custom_module_units',
        $new_conf
    );

    if ($return) {
        $config['custom_module_units'] = $new_conf;
        return true;
    } else {
        return false;
    }
}


function get_custom_module_units()
{
    global $config;

    if (!isset($config['custom_module_units'])) {
        $custom_module_units = [];
    } else {
        $custom_module_units = json_decode(
            io_safe_output($config['custom_module_units']),
            true
        );
    }

    return $custom_module_units;
}


function delete_custom_module_unit($value)
{
    global $config;

    $custom_units = get_custom_module_units();

    unset($custom_units[io_safe_output($value)]);

    $new_conf = json_encode($custom_units);
    $return = config_update_value(
        'custom_module_units',
        $new_conf
    );

    if ($return) {
        $config['custom_module_units'] = $new_conf;

        return true;
    } else {
        return false;
    }
}


/**
 * Get multiplier to be applied on module data in order to represent it properly. Based on setup configuration and module's unit, either 1000 or 1024 will be returned.
 *
 * @param string Module's unit.
 *
 * @return integer Multiplier.
 */
function get_data_multiplier($unit)
{
    global $config;

    switch ($config['use_data_multiplier']) {
        case 0:
            if (strpos(strtolower($unit), 'yte') !== false) {
                $multiplier = 1024;
            } else {
                $multiplier = 1000;
            }
        break;

        case 2:
            $multiplier = 1024;
        break;

        case 1:
        default:
            $multiplier = 1000;
        break;
    }

    return $multiplier;
}


/**
 * Send test email to check email setups.
 *
 * @param string $to     Target email account.
 * @param array  $params Array with connection data.
 * Available fields:
 * 'email_smtpServer',
 * 'email_smtpPort',
 * 'email_username',
 * 'email_password',
 * 'email_encryption',
 * 'email_from_dir',
 * 'email_from_name',
 *
 * @return integer Status of the email send task.
 */
function send_test_email(
    string $to,
    array $params=null
) {
    global $config;

    $valid_params = [
        'email_smtpServer',
        'email_smtpPort',
        'email_username',
        'email_password',
        'email_encryption',
        'email_from_dir',
        'email_from_name',
    ];

    if (empty($params) === true) {
        foreach ($valid_params as $token) {
            $params[$token] = $config[$token];
        }
    } else {
        if (array_diff($valid_params, array_keys($params)) === false) {
            return false;
        }
    }

    $result = false;
    try {
        $transport = new Swift_SmtpTransport(
            $params['email_smtpServer'],
            $params['email_smtpPort']
        );

        $transport->setUsername(io_safe_output($params['email_username']));
        $transport->setPassword(io_output_password($params['email_password']));

        if ($params['email_encryption']) {
            $transport->setEncryption($params['email_encryption']);
        }

        $mailer = new Swift_Mailer($transport);

        $message = new Swift_Message(io_safe_output(__('Testing Pandora FMS email')));

        $message->setFrom(
            [
                $params['email_from_dir'] => io_safe_output(
                    $params['email_from_name']
                ),
            ]
        );

        $to = trim($to);
        $message->setTo([$to => $to]);
        $message->setBody(
            __('This is an email test sent from Pandora FMS. If you can read this, your configuration works.'),
            'text/html'
        );

        ini_restore('sendmail_from');

        $result = $mailer->send($message);
    } catch (Exception $e) {
        error_log($e->getMessage());
        db_pandora_audit(
            AUDIT_LOG_SYSTEM,
            sprintf(
                'Cron jobs mail: %s',
                $e->getMessage()
            )
        );
    }

    return $result;

}


/**
 * Check ip is valid into network
 *
 * @param string $ip   Ip XXX.XXX.XXX.XXX.
 * @param string $cidr Network XXX.XXX.XXX.XXX/XX.
 *
 * @return boolean
 */
function cidr_match($ip, $cidr)
{
    list($subnet, $mask) = explode('/', $cidr);

    if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1) ) == ip2long($subnet)) {
        return true;
    }

    return false;
}


/**
 * Microtime float number.
 *
 * @return float
 */
function microtime_float()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float) $usec + (float) $sec);
}


/**
 * Return array of ancestors of item, given array.
 *
 * @param integer     $item    From index.
 * @param array       $data    Array data.
 * @param string      $key     Pivot key (identifies the parent).
 * @param string|null $extract Extract certain column or index.
 * @param array       $visited Cycle detection.
 *
 * @return array Array of ancestors.
 */
function get_ancestors(
    int $item,
    array $data,
    string $key,
    ?string $extract=null,
    array &$visited=[]
) :array {
    if (isset($visited[$item]) === true) {
        return [];
    }

    $visited[$item] = 1;

    if (isset($data[$item]) === false) {
        return [];
    }

    if (isset($data[$item][$key]) === false) {
        if ($extract !== null) {
            return [$data[$item][$extract]];
        }

        return [$item];
    }

    if ($extract !== null) {
        return array_merge(
            get_ancestors($data[$item][$key], $data, $key, $extract, $visited),
            [$data[$item][$extract]]
        );
    }

    return array_merge(
        get_ancestors($data[$item][$key], $data, $key, $extract, $visited),
        [$item]
    );
}


if (function_exists('str_contains') === false) {


    /**
     * Checks if $needle is found in $haystack and returns a boolean value.
     * For lower than PHP8 versions.
     *
     * @param string $haystack The string who can have the needle.
     * @param string $needle   The needle.
     *
     * @return boolean True if haystack contains the needle.
     */
    function str_contains(string $haystack, string $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }


}


/**
 * Is reporting console node.
 *
 * @return boolean
 */
function is_reporting_console_node()
{
    global $config;
    if (isset($config['reporting_console_enable']) === true
        && (bool) $config['reporting_console_enable'] === true
        && isset($config['reporting_console_node']) === true
        && (bool) $config['reporting_console_node'] === true
    ) {
        return true;
    }

    return false;
}


/**
 * Acl reporting console node.
 *
 * @param string $path Path.
 *
 * @return boolean
 */
function acl_reporting_console_node($path, $tab='')
{
    global $config;
    if (is_reporting_console_node() === false) {
        return true;
    }

    if (is_metaconsole() === true) {
        if ($path === 'advanced/metasetup') {
            switch ($tab) {
                case 'update_manager_online':
                case 'update_manager_offline':
                case 'update_manager_history':
                case 'update_manager_setup':
                case 'file_manager':
                return true;

                default:
                return false;
            }
        }

        if ($path === 'advanced/users_setup') {
            switch ($tab) {
                case 'user_edit':
                return true;

                default:
                return false;
            }
        }

        if ($path === $config['homedir'].'/godmode/users/configure_user'
            || $path === 'advanced/links'
            || $path === $config['homedir'].'/enterprise/extensions/cron'
        ) {
            return true;
        }
    } else {
        if ($path === 'godmode/servers/discovery') {
            switch ($tab) {
                case 'main':
                case 'tasklist':
                return true;

                default:
                return false;
            }
        }

        if ($path === 'operation/users/user_edit'
            || $path === 'operation/users/user_edit_notifications'
            || $path === 'godmode/setup/file_manager'
            || $path === 'godmode/update_manager/update_manager'
        ) {
            return true;
        }
    }

    return false;

}


/**
 * Necessary checks for the reporting console.
 *
 * @return string
 */
function notify_reporting_console_node()
{
    $return = '';

    // Check php memory limit.
    $PHPmemory_limit = config_return_in_bytes(ini_get('memory_limit'));
    if ($PHPmemory_limit !== -1) {
        $url = 'http://php.net/manual/en/ini.core.php#ini.memory-limit';
        if ($config['language'] == 'es') {
            $url = 'http://php.net/manual/es/ini.core.php#ini.memory-limit';
        }

        $msg = __("Not recommended '%s' value in PHP configuration", $PHPmemory_limit);
        $msg .= '<br>'.__('Recommended value is: -1');
        $msg .= '<br>'.__('Please, change it on your PHP configuration file (php.ini) or contact with administrator');
        $msg .= '<br><a href="'.$url.'" target="_blank">'.__('Documentation').'</a>';

        $return = ui_print_error_message($msg, '', true);
    }

    return $return;
}


/**
 * Auxiliar Ordenation function
 *
 * @param string $sort      Direction of sort.
 * @param string $sortField Field for perform the sorting.
 *
 * @return mixed
 */
function arrayOutputSorting($sort, $sortField)
{
    return function ($a, $b) use ($sort, $sortField) {
        if ($sort === 'up' || $sort === 'asc') {
            if (is_string($a[$sortField]) === true) {
                return strnatcasecmp($a[$sortField], $b[$sortField]);
            } else {
                return ($a[$sortField] - $b[$sortField]);
            }
        } else {
            if (is_string($a[$sortField]) === true) {
                return strnatcasecmp($b[$sortField], $a[$sortField]);
            } else {
                return ($a[$sortField] + $b[$sortField]);
            }
        }
    };
}


/**
 * Get dowload started cookie from js and set ready cokkie for download ready comntrol.
 *
 * @return void
 */
function setDownloadCookieToken()
{
    $download_cookie = get_cookie('downloadToken', false);
    if ($download_cookie === false) {
        return;
    } else {
        setcookie(
            'downloadReady',
            $download_cookie,
            (time() + 15),
            '/'
        );
    }
}


/**
 * Get header Authorization
 * */
function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        // Nginx or fast CGI
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } else if (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        // print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    return $headers;
}


/**
 * Get access token from header
 *
 * @return array/false Token received, false in case thre is no token.
 * */
function getBearerToken()
{
    $headers = getAuthorizationHeader();

    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }

    return false;
}


/**
 * Check whether an instance of pandora_db is running.
 *
 * @return boolean Result.
 */
function is_pandora_db_running()
{
    // Get current DB name: useful for metaconsole connection to node.
    $db_name = db_get_sql('SELECT DATABASE()');

    $is_free_lock = mysql_db_process_sql(
        'SELECT IS_FREE_LOCK("'.$db_name.'_pandora_db") AS "value"',
        'affected_rows',
        '',
        false
    );

    $is_free_lock = (bool) $is_free_lock[0]['value'];

    return !$is_free_lock;
}


/**
 * Check nms license on api.
 *
 * @return boolean.
 * */
function nms_check_api()
{
    global $config;

    if ((int) $config['license_nms'] === 1) {
        returnError('license_error');
        return true;
    }
}


/**
 * Simply return a string enclosed in quote delimiter to be used in csv exports.
 *
 * @param string $str String to be formatted.
 *
 * @return string Formatted string.
 */
function csv_format_delimiter(?string $str)
{
    if ($str === null) {
        return $str;
    }

    // Due to the ticket requirements, double quote is used as fixed string delimiter.
    // TODO: a setup option that enables user to choose a delimiter character would probably be desirable in the future.
    return '"'.$str.'"';
}


/**
 * Get List translate string.
 *
 * @param string  $language Language.
 * @param string  $text     Text.
 * @param integer $page     Page.
 *
 * @return array List.
 */
function getListStringsTranslate($language, $text='', $page=0)
{
    global $config;

    $fileLanguage = $config['homedir'].'/include/languages/'.$language.'.po';

    $file = file($fileLanguage);

    $listStrings = [];
    $readingOriginal = false;
    $readingTranslation = false;
    $original = '';
    $translation = '';
    foreach ($file as $line) {
        // Jump empty lines.
        if (strlen(trim($line)) == 0) {
            continue;
        }

        // Jump comment lines.
        if (preg_match('/^#.*$/', $line) > 0) {
            continue;
        }

        if (preg_match('/^msgid "(.*)"/', $line, $match) == 1) {
            if (empty($original) === false && (preg_match('/.*'.$text.'.*/', $original) >= 1)) {
                $listStrings[$original] = [];
                $listStrings[$original]['po'] = $translation;
                $listStrings[$original]['ext'] = '';
            }

            $original = '';
            $readingOriginal = false;
            $readingTranslation = false;

            if (strlen($match[1]) > 0) {
                $original = $match[1];
            } else {
                $readingOriginal = true;
            }
        } else if (preg_match('/^msgstr "(.*)"/', $line, $match) == 1) {
            if (strlen($match[1]) > 0) {
                $translation = $match[1];
            } else {
                $readingOriginal = false;
                $readingTranslation = true;
                $translation = '';
            }
        } else if (preg_match('/^"(.*)"/', $line, $match) == 1) {
            if ($readingOriginal) {
                $original = $original.$match[1];
            } else {
                $translation = $translation.$match[1];
            }
        }
    }

    if (empty($original) === false && (preg_match('/.*'.$text.'.*/', $original) >= 1)) {
        $listStrings[$original] = [];
        $listStrings[$original]['po'] = $translation;
        $listStrings[$original]['ext'] = '';
    }

    $sql = sprintf(
        'SELECT *
        FROM textension_translate_string
			WHERE lang = "%s"',
        $language
    );

    $dbListStrings = db_get_all_rows_sql($sql);
    if ($dbListStrings === false) {
        $dbListStrings = [];
    }

    foreach ($dbListStrings as $row) {
        if (array_key_exists(io_safe_output($row['string']), $listStrings)) {
            $listStrings[io_safe_output($row['string'])]['ext'] = io_safe_output($row['translation']);
        }
    }

    return $listStrings;
}


/**
 * Translate.
 *
 * @param string $string String.
 *
 * @return mixed
 */
function get_defined_translation($string)
{
    global $config;
    static $cache = [];
    static $cache_translation = [];

    $language = get_user_language();

    if (func_num_args() !== 1) {
        $args = func_get_args();
        array_shift($args);
    }

    // Try with the cache.
    if (isset($cache[$language]) === true) {
        if (isset($cache[$language][$string]) === true) {
            if (func_num_args() === 1) {
                return $cache[$language][$string];
            } else {
                return vsprintf($cache[$language][$string], $args);
            }
        }
    }

    if ((isset($config['ignore_cache_translate']) === false || $config['ignore_cache_translate'] !== true)
        && is_array($cache_translation) === true && count($cache_translation) === 0
    ) {
        $cache_translation_all = db_get_all_rows_sql(
            sprintf(
                'SELECT translation, string
			    FROM textension_translate_string
		    	WHERE lang = "%s"',
                $language
            )
        );
        $cache_translation = false;
        if ($cache_translation_all !== false) {
            foreach ($cache_translation_all as $key => $value) {
                $cache_translation[md5(io_safe_output($value['string']))] = $value['translation'];
            }
        }
    } else {
        if ($cache_translation === false) {
            return false;
        }

        if (empty($cache_translation[md5($string)]) === false) {
            $translation = $cache_translation[md5($string)];
        } else {
            return false;
        }
    }

    if (empty($translation) === true) {
        return false;
    } else {
        $cache[$language][$string] = io_safe_output($translation);

        if (func_num_args() === 1) {
            return $cache[$language][$string];
        } else {
            return vsprintf($cache[$language][$string], $args);
        }
    }
}


/**
 * Merge any number of arrays by pairs of elements at the same index.
 *
 * @param array $arrays Arrays.
 *
 * @return array
 */
function createPairsFromArrays($arrays)
{
    $resultArray = [];

    // Check if all arrays have the same length.
    $lengths = array_map('count', $arrays);

    if (count(array_unique($lengths)) === 1) {
        $count = $lengths[0];

        for ($i = 0; $i < $count; $i++) {
            // Build pairs and add to the result array.
            $pair = array_map(
                function ($array) use ($i) {
                    return $array[$i];
                },
                $arrays
            );

            $resultArray[] = $pair;
        }

        return $resultArray;
    } else {
        return [];
    }
}
