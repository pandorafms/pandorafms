<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require_once ('functions_html.php');

/** 
 * Prints a help tip icon.
 * 
 * @param id Help id
 * @param return Flag to return or output the result
 * 
 * @return The help tip if return flag was active.
 */
function pandora_help ($help_id, $return = false) {
	global $config;
	$output = '&nbsp;<img class="img_help" src="images/help.png" onClick="pandora_help(\''.$help_id.'\')">';
	if ($return)
		return $output;
	echo $output;
}

/** 
 * Cleans a string by decoding from UTF-8 and replacing the HTML
 * entities.
 * 
 * @param value String to be cleaned.
 * 
 * @return The string cleaned.
 */
function safe_input ($value) {
	if (is_numeric ($value))
		return $value;
	if (is_array ($value)) {
		array_walk ($value,'safe_input');
		return $value;
	}
	return htmlentities (utf8_decode ($value), ENT_QUOTES); 
}

/** 
 * Pandora debug functions.
 *
 * It prints a variable value and a message.
 * 
 * @param var Variable to be displayed
 * @param mesg Message to be displayed
 */
function pandora_debug ($var, $msg) { 
	echo "[Pandora DEBUG (".$var."): (".$msg.")<br />";
} 

/** 
 * Clean a string.
 * 
 * @param string 
 * 
 * @return 
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
	return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&#38;" , strtr($string, $trans));
}

/** 
 * Replace all \n characters with <br /> to show line breaks in HTML code.
 * 
 * @param string String to replace the characters
 * 
 * @return The string with the characters replaced.
 */
function clean_output_breaks ($string){
	$myoutput = salida_limpia ($string);
	return preg_replace ('/\n/',"<br />", $myoutput);
}

/** 
 * Cleans a string to be shown in a graphic.
 * 
 * @param string String to be cleaned
 * 
 * @return String with special characters cleaned.
 */
function output_clean_strict ($string) {
	return preg_replace ('/[\|\@\$\%\/\(\)\=\?\*\&\#]/', '', $string);
}


/** 
 * WARNING: Deprecated function, use safe_input. Keep from compatibility.
 */
function entrada_limpia ($string) {
	return safe_input ($string);
}

/** 
 * Performs an extra clean to a string.
 *
 * It's useful on sec and sec2 index parameters, to avoid the use of
 * malicious parameters. The string is also stripped to 125 charactes.
 * 
 * @param string String to clean
 * 
 * @return 
 */
function parameter_extra_clean ($string) {
	/* Clean "://" from the strings
	 See: http://seclists.org/lists/incidents/2004/Jul/0034.html
	*/
	$pos = strpos ($string, "://");
	if ($pos != 0)
		$string = substr_replace ($string, "", $pos, +3);
	/* Strip the string to 125 characters */
	$string = substr_replace ($string, "", 125);
	return preg_replace ('/[^a-z0-9_\/]/i', '', $string);
}

/** 
 * Get a human readable string with a time threshold in seconds,
 * minutes, days or weeks.
 * 
 * @param int_seconds 
 * 
 * @return 
 */
function give_human_time ($int_seconds) {
	$key_suffix = 's';
	$periods = array('year'   => 31556926,
			 'month'  => 2629743,
			 'day'    => 86400,
			 'hour'   => 3600,
			 'minute' => 60,
			 'second' => 1);

	// used to hide 0's in higher periods
	$flag_hide_zero = true;

	// do the loop thang
	foreach( $periods as $key => $length ) {
		// calculate
		$temp = floor( $int_seconds / $length );

		// determine if temp qualifies to be passed to output
		if( !$flag_hide_zero || $temp > 0 ) {
			// store in an array
			$build[] = $temp.' '.$key.($temp != 1 ? 's' : null);

			// set flag to false, to allow 0's in lower periods
			$flag_hide_zero = true;
		}

		// get the remainder of seconds
		$int_seconds = fmod ($int_seconds, $length);
	}

	// return output, if !empty, implode into string, else output $if_reached
	return (!empty ($build) ? implode (', ', $build) : $if_reached);
}

/** 
 * Add a help link to show help in a popup window.
 * 
 * @param help_id Help id to be shown when clicking.
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
 * Prints a generic error message for some unhandled error.
 * 
 * @param error Aditional error string to be shown. Blank by default
 */
function unmanaged_error ($error = "") {
	require("config.php");
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
 * @param directory Local path.
 * @param stringSearch String to match the values.
 * @param searchHandler Pattern of files to match.
 * @param return Flag to print or return the list.
 * 
 * @return The list if $return parameter is true.
 */
function list_files ($directory, $stringSearch, $searchHandler, $return) {
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
		if ($return == 0) {
			return $result;
		}
		echo ("<pre>\n");
		print_r ($result);
		echo ("</pre>\n");
	}
}

/** 
 * Prints a pagination menu to browse into a collection of data.
 * 
 * @param count Number of elements in the collection.
 * @param url URL of the pagination links. It must include all form values as GET form.
 * @param offset Current offset for the pagination
 * 
 * @return It returns nothing, it prints the pagination.
 */
function pagination ($count, $url, $offset) {
	global $config;
	
	/* 	URL passed render links with some parameter
			&offset - Offset records passed to next page
	  		&counter - Number of items to be blocked 
	   	Pagination needs $url to build the base URL to render links, its a base url, like 
	   " http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
	   
	*/
	$block_limit = 15; // Visualize only $block_limit blocks
	if ($count <= $config["block_size"]) {
		return;
	}
	// If exists more registers than I can put in a page, calculate index markers
	$index_counter = ceil($count/$config["block_size"]); // Number of blocks of block_size with data
	$index_page = ceil($offset/$config["block_size"])-(ceil($block_limit/2)); // block to begin to show data;
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

	echo "<div>";
	// Show GOTO FIRST button
	echo '<a href="'.$url.'&offset=0">';
	echo "<img src='images/control_start_blue.png' class='bot'>";
	echo "</a>";
	echo "&nbsp;";
	// Show PREVIOUS button
	if ($index_page > 0){
		$index_page_prev= ($index_page-(floor($block_limit/2)))*$config["block_size"];
		if ($index_page_prev < 0)
			$index_page_prev = 0;
		echo '<a href="'.$url.'&offset='.$index_page_prev.'"><img src="images/control_rewind_blue.png" class="bot"></a>';
	}
	echo "&nbsp;";echo "&nbsp;";
	// Draw blocks markers
	// $i stores number of page
	for ($i = $inicio_pag; $i < $index_limit; $i++) {
		$inicio_bloque = ($i * $config["block_size"]);
		$final_bloque = $inicio_bloque + $config["block_size"];
		if ($final_bloque > $count){ // if upper limit is beyond max, this shouldnt be possible !
			$final_bloque = ($i-1)*$config["block_size"] + $count-(($i-1) * $config["block_size"]);
		}
		echo "<span>";
			
		$inicio_bloque_fake = $inicio_bloque + 1;
		// To Calculate last block (doesnt end with round data,
		// it must be shown if not round to block limit)
		echo '<a href="'.$url.'&offset='.$inicio_bloque.'">';
		if ($inicio_bloque == $offset)
			echo "<b>[ $i ]</b>";
		else
			echo "[ $i ]";
		echo '</a> ';	
		echo "</span>";
	}
	echo "&nbsp;";echo "&nbsp;";
	// Show NEXT PAGE (fast forward)
	// Index_counter stores max of blocks
	if (($paginacion_maxima == 1) AND (($index_counter - $i) > 0)) {
		$prox_bloque = ($i+ceil($block_limit/2))*$config["block_size"];
		if ($prox_bloque > $count)
			$prox_bloque = ($count -1) - $config["block_size"];
		echo '<a href="'.$url.'&offset='.$prox_bloque.'">';
		echo "<img class='bot' src='images/control_fastforward_blue.png'></a> ";
		$i = $index_counter;
	}
	// if exists more registers than i can put in a page (defined by $block_size config parameter)
	// get offset for index calculation
	// Draw "last" block link, ajust for last block will be the same
	// as painted in last block (last integer block).	
	if (($count - $config["block_size"]) > 0){
		$myoffset = floor(($count-1)/ $config["block_size"])* $config["block_size"];
		echo '<a href="'.$url.'&offset='.$myoffset.'">';
		echo "<img class='bot' src='images/control_end_blue.png'>";
		echo "</a>";
	}
	// End div and layout
	echo "</div>";
}



/** 
 * Format a unix timestamp to render a datetime string with specific format
 *
 * format comes with $config["date_format"]
 * 
 * @param utimestamp Unixtimestamp integer format
 * @param alt_format Alternative format, for use insted configþ[]
 * 
 * @return 
 */
function format_datetime ($timestamp, $alt_format = "") {
	
	global $config;
	
	if ($alt_format == "")
		$alt_format = $config["date_format"];
		
	return date($alt_format, $timestamp); 
}


/** 
 * Format a number with decimals and thousands separator.
 *
 * If the number is zero or it's integer value, no decimals are
 * shown. Otherwise, the number of decimals are given in the call.
 * 
 * @param number Number to be rendered
 * @param decimals Number of decimals to be shown. Default value: 1
 * @param dec_point Decimal separator string. Default value: .
 * @param thousands_sep Thousands separator string. Default value: ,
 * 
 * @return 
 */
function format_numeric ($number, $decimals = 1, $dec_point = ".", $thousands_sep = ",") {
	if ($number == 0)
		return 0;
	
	/* If has decimals */
	if (fmod ($number , 1) > 0)
		return number_format ($number, $decimals, $dec_point, $thousands_sep);
	return number_format ($number, 0, $dec_point, $thousands_sep);
}

/** 
 * Render numeric data for a graph.
 *
 * It adds magnitude suffix to the number (M for millions, K for thousands...)
 * 
 * @param number Number to be rendered
 * @param decimals Number of decimals to display. Default value: 1
 * @param dec_point Decimal separator character. Default value: .
 * @param thousands_sep Thousands separator character. Default value: ,
 * 
 * @return A number rendered to be displayed gently on a graph.
 */
function format_for_graph ($number , $decimals = 1, $dec_point = ".", $thousands_sep = ",") {
	if ($number > 1000000000) {
		if (fmod ($number, 1000000000) > 0){
			return number_format ($number / 1000000000, $decimals, $dec_point, $thousands_sep)." G";
		}
	}
	if ($number > 1000000) {
		if (fmod ($number, 1000000) > 0)
			return number_format ($number / 1000000, $decimals, $dec_point, $thousands_sep)." M";
		return number_format ($number / 1000000, 0, $dec_point, $thousands_sep)." M";
	}
	
	if ($number > 1000) {
		if (fmod ($number, 1000) > 0)
			return number_format ($number / 1000, $decimals, $dec_point, $thousands_sep )." K";
		return number_format ($number / 1000, 0, $dec_point, $thousands_sep )." K";
	}
	/* If it has decimals */
	if (fmod ($number, 1))
		return number_format ($number, $decimals, $dec_point, $thousands_sep);
	return number_format ($number, 0, $dec_point, $thousands_sep);
}

/** 
 * Get a human readable string of the difference between current time
 * and given timestamp.
 * 
 * @param timestamp Timestamp to compare with current time.
 * 
 * @return A human readable string of the diference between current
 * time and given timestamp.
 */
function human_time_comparation ($timestamp) {
	if ($timestamp == "") {
		return "0 ".__('minutes');
	}
	
	$ahora = date ("Y/m/d H:i:s");
	$seconds = strtotime ($ahora) - strtotime ($timestamp);
	
	if ($seconds < 3600)
		return format_numeric ($seconds / 60, 1)." ".__('minutes');
	
	if ($seconds >= 3600 && $seconds < 86400)
		return format_numeric ($seconds / 3600, 1)." ".__('hours');
	
	if ($seconds >= 86400 && $seconds < 2592000)
		return format_numeric ($seconds / 86400, 1)." ".__('days');
	
	if ($seconds >= 2592000 && $seconds < 15552000)
		return format_numeric ($seconds / 2592000, 1)." ".__('months');
	return " +6 ".__('months');
}

/** 
 * Transform an amount of time in seconds into a human readable
 * strings of minutes, hours or days.
 * 
 * @param seconds Seconds elapsed time
 * 
 * @return A human readable translation of minutes.
 */
function human_time_description_raw ($seconds) {
	global $lang_label;
	if ($seconds < 3600)
		return format_numeric($seconds/60,2)." ".__('minutes');
	
	if ($seconds >= 3600 && $seconds < 86400)
		return format_numeric ($seconds/3600,2)." ".__('hours');
	
	return format_numeric ($seconds/86400,2)." ".__('days');
}

/** 
 * Get a human readable label for a period of time.
 *
 * It only works with rounded period of times (one hour, two hours, six hours...)
 * 
 * @param period Period of time in seconds
 * 
 * @return A human readable label for a period of time.
 */
function human_time_description ($period) {
	global $lang_label;
	
	switch ($period) {
	case 3600:
		return __('One hour');
		break;
	case 7200:
		return __('Two hours');
		break;
	case 21600:
		return __('Six hours');
		break;
	case 43200:
		return __('12 hours');
		break;
	case 86400:
		return __('One day');
		break;
	case 172800:
		return __('Two days');
		break;
	case 432000:
		return __('Five days');
		break;
	case 604800:
		return __('One week');
		break;
	case 1296000:
		return __('15 days');
		break;
	case 2592000:
		return __('One month');
		break;
	case 5184000:
		return __('Two months');
		break;
	case 15552000:
		return __('Six months');
		break;
	default:
		return human_time_description_raw ($period);
	}
	return $period_label;
}

/** 
 * Get current time minus some seconds.
 * 
 * @param seconds Seconds to substract from current time.
 * 
 * @return The current time minus the seconds given.
 */
function human_date_relative ($seconds) {
	$ahora=date("Y/m/d H:i:s");
	$ahora_s = date("U");
	$ayer = date ("Y/m/d H:i:s", $ahora_s - $seconds);
	return $ayer;
}

/** 
 * 
 * 
 * @param lapse 
 * 
 * @return 
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
 * Get a paramter from a request.
 *
 * It checks first on post request, if there were nothing defined, it
 * would return get request
 * 
 * @param name 
 * @param default 
 * 
 * @return 
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
 * Get a parameter from get request array.
 * 
 * @param name Name of the parameter
 * @param default Value returned if there were no parameter.
 * 
 * @return Parameter value.
 */
function get_parameter_get ($name, $default = "") {
	if ((isset ($_GET[$name])) && ($_GET[$name] != ""))
		return safe_input ($_GET[$name]);

	return $default;
}

/** 
 * Get a parameter from post request array.
 * 
 * @param name Name of the parameter
 * @param default Value returned if there were no parameter.
 * 
 * @return Parameter value.
 */
function get_parameter_post ($name, $default = "") {
	if ((isset ($_POST[$name])) && ($_POST[$name] != ""))
		return safe_input ($_POST[$name]);

	return $default;
}

/** 
 * Get name of a priority value.
 * 
 * @param priority Priority value
 * 
 * @return Name of given priority
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
 * 
 * 
 * @param row 
 * 
 * @return 
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
		$days_output .= "Mo ";
	if ($row["tuesday"] != 0)
		$days_output .= "Tu ";
	if ($row["wednesday"] != 0)
		$days_output .= "We ";
	if ($row["thursday"] != 0)
		$days_output .= "Th ";
	if ($row["friday"] != 0)
		$days_output .= "Fr ";
	if ($row["saturday"] != 0)
		$days_output .= "Sa ";
	if ($row["sunday"] != 0)
		$days_output .= "Su ";
	if ($check > 1) {	
		return str_replace (" ",", ",$days_output);
	}
	return rtrim ($days_output);
}

/** 
 * 
 * 
 * @param row2 
 * 
 * @return 
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
 * 
 * 
 * @param row2 
 * @param tdcolor 
 * @param id_tipo_modulo 
 * @param combined 
 * 
 * @return 
 */
function show_alert_row_edit ($row2, $tdcolor = "datos", $id_tipo_modulo = 1, $combined = 0){
	global $config;
	global $lang_label;

	$string = "";
	if ($row2["disable"] == 1){
		$string .= "<td class='$tdcolor'><b><i>".__('Disabled')."</b></i>";
	} elseif ($id_tipo_modulo != 0) {
		$string .= "<td class='$tdcolor'><img src='images/".show_icon_type($id_tipo_modulo)."' border=0>";
	} else {
		$string .= "<td class='$tdcolor'>--";
	}

	if (isset($row2["operation"])){
		$string = $string."<td class=$tdcolor>".$row2["operation"];
	} else {
		$string = $string."<td class=$tdcolor>".get_db_sql("SELECT nombre FROM talerta WHERE id_alerta = ".$row2["id_alerta"]);
	}

	$string = $string."<td class='$tdcolor'>".human_time_description($row2["time_threshold"]);
	if ($row2["dis_min"]) {
		$mytempdata = fmod($row2["dis_min"], 1);
		if ($mytempdata == 0)
			$mymin = intval($row2["dis_min"]);
		else
			$mymin = $row2["dis_min"];
		$mymin = format_for_graph($mymin );
	} else {
		$mymin = 0;
	}


	if ($row2["dis_max"]!=0){
		$mytempdata = fmod($row2["dis_max"], 1);
		if ($mytempdata == 0)
			$mymax = intval($row2["dis_max"]);
		else
			$mymax = $row2["dis_max"];
		$mymax =  format_for_graph($mymax );
	} else {
		$mymax = 0;
	}


	// We have alert text ?
	if ($row2["alert_text"]!= "") {
		$string = $string."<td colspan=2 class='$tdcolor'>".__('Text')."</td>";
	} else {
		$string = $string."<td class='$tdcolor'>".$mymin."</td>";
		$string = $string."<td class='$tdcolor'>".$mymax."</td>";
	}

	// Alert times
	$string = $string."<td class='$tdcolor'>";
	$string .= get_alert_times ($row2);

	// Description
	$string = $string."</td><td class='$tdcolor'>".salida_limpia ($row2["descripcion"]);

	// Has recovery notify activated ?
	if ($row2["recovery_notify"] > 0)
		$recovery_notify = __('Yes');
	else
		$recovery_notify = __('No');

	// calculate priority
	$priority = get_alert_priority ($row2["priority"]);

	// calculare firing conditions
	if ($row2["alert_text"] != "") {
		$firing_cond = __('Text')."(".substr($row2["alert_text"],0,8).")";
	} else {
		$firing_cond = $row2["min_alerts"]." / ".$row2["max_alerts"];
	}
	// calculate days
	$firing_days = get_alert_days ( $row2 );

	// More details EYE tooltip
	$string = $string."<td class='$tdcolor'>";
	$string.= "<a href=# class=info><img class='top'
	src='images/eye.png' alt=''>";

	// Add float info table
	$string.= "
	<span>
	<table cellspacing='2' cellpadding='0'
	style='margin-left:2px;'>
		<tr><th colspan='2' width='91'>".
		__('Recovery')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$recovery_notify</b></td></tr>
		<tr><th colspan='2' width='91'>".
		__('Priority')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$priority</b></td></tr>
		<tr><th colspan='2' width='91'>".
		__('Alert Ctrl.')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>".$firing_cond."</b></td></tr>
		<tr><th colspan='2' width='91'>".
		__('Firing days')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>".$firing_days."</b></td></tr>
		</table></span></A>";

	return $string;
}

/** 
 * 
 * 
 * @param data 
 * @param tdcolor 
 * @param combined 
 * 
 * @return 
 */
function show_alert_show_view ($data, $tdcolor = "datos", $combined = 0) {
	global $config;
	global $lang_label;

	if ($combined == 0) {
		$sql = sprintf ("SELECT tagente.nombre AS agent_name, tagente_modulo.nombre AS module_name, tagente_modulo.id_agente FROM `tagente_modulo`, `tagente` WHERE `tagente_modulo`.`id_agente` = `tagente`.`id_agente` AND `tagente_modulo`.`id_agente_modulo` = '%d'",$data["id_agente_modulo"]);
		$result = get_db_all_rows_sql ($sql);
		$agent_name = $result[0]["agent_name"]; 
		$id_agente = $result[0]["id_agente"];
		$module_name = $result[0]["module_name"];
	} else {
		$sql = sprintf ("SELECT `nombre` FROM `tagente` WHERE `id_agente` = '%d'",$data["id_agent"]);
		$agent_name =  get_db_sql ($sql);
		$id_agente = $data["id_agent"];
	}
	$alert_name = get_db_sql ("SELECT nombre FROM talerta WHERE id_alerta = ".$data["id_alerta"]);

	echo "<td class='".$tdcolor."f9' title='$alert_name'>".substr($alert_name,0,15)."</td>";
	if ($combined == 0) {
		echo "<td class='".$tdcolor."'>".substr($module_name,0,12)."</td>";
	} else {
		echo "<td class='".$tdcolor."'>";
		// More details EYE tooltip (combined)
		echo " <a href='#' class='info_table'><img class='top' src='images/eye.png' alt=''><span>";
		echo show_alert_row_mini ($data["id_aam"]);
		echo "</span></a> ";
		echo substr($agent_name,0,16)."</td>"; 
	}

	// Description
	echo "<td class='".$tdcolor."'>".$data["descripcion"]."</td>";

	// Extended info
	echo "<td class='".$tdcolor."'>";

	// Has recovery notify activated ?
	if ($data["recovery_notify"] > 0)
		$recovery_notify = __('Yes');
	else
		$recovery_notify = __('No');

	// calculate priority
	$priority = get_alert_priority ($data["priority"]);

	// calculare firing conditions
	if ($data["alert_text"] != ""){
		$firing_cond = __('Text')."(".substr($data["alert_text"],0,8).")";
	} else {
		$firing_cond = $data["min_alerts"]." / ".$data["max_alerts"];
	}
	// calculate days
	$firing_days = get_alert_days ($data);

	// More details EYE tooltip
	echo "<a href='#' class='info'><img class='top'
	src='images/eye.png' alt=''>";

	// Add float info table
	echo "<span>
		<table cellspacing='2' cellpadding='0'
		style='margin-left:2px;'>
		<tr><th colspan='2' width='91'>".
		__('Recovery')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$recovery_notify</b></td></tr>
		<tr><th colspan='2' width='91'>".
		__('Priority')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$priority</b></td></tr>
		<tr><th colspan='2' width='91'>".
		__('Alert Ctrl.')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>".$firing_cond."</b></td></tr>
		<tr><th colspan='2' width='91'>".
		__('Firing days')."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>".$firing_days."</b></td></tr>
		</table></span></a>";

	$mytempdata = fmod($data["dis_min"], 1);
	if ($mytempdata == 0)
		$mymin = intval($data["dis_min"]);
	else
		$mymin = $data["dis_min"];
	$mymin = format_for_graph($mymin );

	$mytempdata = fmod($data["dis_max"], 1);
	if ($mytempdata == 0)
		$mymax = intval($data["dis_max"]);
	else
		$mymax = $data["dis_max"];
	$mymax =  format_for_graph($mymax );
	// Text alert ?
	if ($data["alert_text"] != "")
		echo "<td class='".$tdcolor."' colspan=2>".__('Text')."</td>";
	else {
		echo "<td class='".$tdcolor."'>".$mymin."</td>";
		echo "<td class='".$tdcolor."'>".$mymax."</td>";
	}
	echo "<td  align='center' class='".$tdcolor."'>".human_time_description($data["time_threshold"]);
	if ($data["last_fired"] == "0000-00-00 00:00:00") {
		echo "<td align='center' class='".$tdcolor."f9'>".__('Never')."</td>";
	} else {
		echo "<td align='center' class='".$tdcolor."f9'>".human_time_comparation ($data["last_fired"])."</td>";
	}
	echo "<td align='center' class='".$tdcolor."'>".$data["times_fired"]."</td>";
	if ($data["times_fired"] <> 0){
		echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_red.png' title='".__('Alert fired')."'>";
		echo "</td>";
		$id_grupo_alerta = get_db_value ("id_grupo", "tagente", "id_agente", $id_agente);
		if (give_acl($config["id_user"], $id_grupo_alerta, "AW") == 1) {
			echo "<td align='center' class='".$tdcolor."'>";
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&
				id_agente=$id_agente&validate_alert=".$data["id_aam"]."'><img src='images/ok.png'></a>";
			echo "</td>";
		}
	} else {
		echo "<td class='".$tdcolor."' align='center'>
			<img width='20' height='9' src='images/pixel_green.png' title='".__('Alert not fired')."'></td>";
	}
}

/**
 * Get report types in an array.
 * 
 * @return An array with all the possible reports in Pandora where the array index is the report id.
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
 * @param type Type id of the report.
 *
 * @return Report type name.
 */
function get_report_name ($type) {
	$types = get_report_types ();
	if (! isset ($types[$type]))
		return __('Unknown');
	return $types[$type];
}

/**
 * Get report type name from type id.
 *
 * @param type Type id of the report.
 *
 * @return Report type name.
 */
function get_report_type_data_source ($type) {
	switch ($type) {
	case 1:
	case 'simple_graph':
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
	case 6:
	case 'monitor_report':
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
 * @param module_name Module name to check.
 *
 * @return true if the module is of type "data"
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
 * @param module_name Module name to check.
 *
 * @return true if the module is of type "proc"
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
 * @param module_name Module name to check.
 *
 * @return true if the module is of type "inc"
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
 * @param module_name Module name to check.
 *
 * @return true if the module is of type "string"
 */
function is_module_data_string ($module_name) {
	$result = ereg ('^(.*string)$', $module_name);
	if ($result === false)
		return false;
	return true;
}

/**
 * Checks if a module is of type "string"
 *
 * @return module_name Module name to check.
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
 * @return An array with all the priorities.
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
 * Get priority value from priority name.
 *
 * @param priority Priority name.
 */
function return_priority ($priority) {
	global $config;

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
	case -1: 
		return __('All');
	}
}

/**
 * Avoid magic_quotes protection
 *
 * @param string Text string to be stripped of magic_quotes protection
 */

function unsafe_string ($string) {
	if (get_magic_quotes_gpc () == 1) 
		$string = stripslashes ($string);
	return $string;
}

?>
