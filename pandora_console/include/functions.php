<?php

// Pandora FMS - the Free Monitoring System
// ========================================
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

require ('functions_html.php');

function pandora_help ($id, $return = false) {
	global $config;
	$output = '<img src="'.$config['homeurl'].'/images/help.png" onClick="pandora_help(\''.$id.'\')">';
	if ($return)
		return $return;
	echo $output;
}
// ---------------------------------------------------------------
// safe_output()
// Write a string to screen, deleting all kind of problematic characters
// This should be safe for XSS.
// --------------------------------------------------------------- 

function safe_output ($string) {
	return preg_replace('/[^\x09\x0A\x0D\x20-\x7F]/e', '"&#".ord($0).";"', $string);
}

// ---------------------------------------------------------------
// safe_input()
// Get parameter, using UTF8 encoding, and cleaning bad codes
// --------------------------------------------------------------- 

function safe_input ($value) {
	if (is_numeric ($value))
		return $value;
	return htmlentities (utf8_decode ($value), ENT_QUOTES); 
}

// ---------------------------------------------------------------
// salida_sql: Parse \' for replace to ' character, prearing
// SQL sentences to execute.
// --------------------------------------------------------------- 

function salida_sql ($string) {
	return mysql_escape_string ($string);
}


// input: var, string. 
//          mesg, mesage to show, var content. 
// --------------------------------------------------------------- 

function midebug($var, $mesg){ 
	echo "[DEBUG (".$var."]: (".$mesg.")"; 
	echo "<br>";
} 

// --------------------------------------------------------------- 
// array_in
// Search "item" in a given array, return 1 if exists, 0 if not
// ---------------------------------------------------------------

function array_in($exampleArray, $item){
	$result = 0;
	foreach ($exampleArray as $key => $value){
		if ($value == $item){
			$result = 1;
		}
  	}
	return $result;
}


// ---------------------------------------------------------------
// parse and clear string
// --------------------------------------------------------------- 

function salida_limpia ($string){
	$quote_style=ENT_QUOTES;
	static $trans;
	if (!isset($trans)) {
		$trans = get_html_translation_table(HTML_ENTITIES, $quote_style);
		foreach ($trans as $key => $value)
			$trans[$key] = '&#'.ord($key).';';
		// dont translate the '&' in case it is part of &xxx;
		$trans[chr(38)] = '&';
	}
	// after the initial translation, _do_ map standalone '&' into '&#38;'
	return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&#38;" , strtr($string, $trans));
}

function clean_output_breaks ($string){
	$myoutput = salida_limpia($string);
	return preg_replace ('/\n/',"<br>", $myoutput);
}


function output_clean_strict ($string){
	$string = preg_replace('/[\|\@\$\%\/\(\)\=\?\*\&\#]/','',$string);
	return $string;
}

// ---------------------------------------------------------------
// This function reads a string and returns it "clean"
// for use in DB, againts string XSS and so on
// ---------------------------------------------------------------

function entrada_limpia ($texto){
	$filtro0 = utf8_decode($texto);
	$filtro1 =  htmlentities($filtro0, ENT_QUOTES); 
	return $filtro1;
}

// ---------------------------------------------------------------
// Esta funcion lee una cadena y la da "limpia", para su uso con 
// parametros pasados a funcion de abrir fichero. Usados en sec y sec2
// ---------------------------------------------------------------

function parametro_limpio($texto){
	// Metemos comprobaciones de seguridad para los includes de paginas pasados por parametro
	// Gracias Raul (http://seclists.org/lists/incidents/2004/Jul/0034.html)
	// Consiste en purgar los http:// de las cadenas
	$pos = strpos($texto,"://");	// quitamos la parte "fea" de http:// o ftp:// o telnet:// :-)))
	if ($pos <> 0)
		$texto = substr_replace($texto,"",$pos,+3);   
	// limitamos la entrada de datos por parametros a 125 caracteres
	$texto = substr_replace($texto,"",125);
	$safe = preg_replace('/[^a-z0-9_\/]/i','',$texto);
	return $safe;
}

// ---------------------------------------------------------------
// Esta funcion se supone que cierra todos los tags HTML abiertos y no cerrados
// ---------------------------------------------------------------

// string closeOpenTags(string string [, string beginChar [, stringEndChar [, string CloseChar]]]);

function closeOpenTags ($str, $open = "<", $close = ">", $end = "/", $tokens = "_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")
{ $chars = array();
	for ($i = 0; $i < strlen($tokens); $i++)
	{ $chars[] = substr($tokens, $i, 1); }

	$openedTags = array();
	$closedTags = array();
	$tag = FALSE;
	$closeTag = FALSE;
	$tagName = "";

	for ($i = 0; $i < strlen($str); $i++)
	{ $char = substr($str, $i, 1);
	if ($char == $open)
	{ $tag = TRUE; continue; }
	if ($char == $end)
	{ $closeTag = TRUE; continue; }
	if ($tag && in_array($char, $chars))
	{ $tagName .= $char; }
	else
	{if ($closeTag)
		{if (isset($closedTags[$tagName]))
			{ $closedTags[$tagName]++; }
		else
			{ $closedTags[$tagName] = 1; } }
		elseif ($tag)
			{if (isset($openedTags[$tagName]))
			{ $openedTags[$tagName]++; }
			else
			{ $openedTags[$tagName] = 1; } }
		$tag = FALSE; $closeTag = FALSE; $tagName = ""; } 
	 }

	while(list($tag, $count) = each($openedTags))
	{
	$closedTags[$tag] = isset($closedTags[$tag]) ? $closedTags[$tag] : 0;
	$count -= $closedTags[$tag];
	if ($count < 1) continue;
	$str .= str_repeat($open.$end.$tag.$close, $count);
	}
	return $str;

	}

// ---------------------------------------------------------------
// Return string with time-threshold in secs, mins, days or weeks
// ---------------------------------------------------------------

function give_human_time ($int_seconds){
	$key_suffix = 's';
	$periods = array('year'        => 31556926,
		'month'        => 2629743,
		'day'        => 86400,
		'hour'        => 3600,
		'minute'    => 60,
		'second'    => 1
		);

	// used to hide 0's in higher periods
	$flag_hide_zero = true;

	// do the loop thang
	foreach( $periods as $key => $length ) {
		// calculate
		$temp = floor( $int_seconds / $length );

		// determine if temp qualifies to be passed to output
		if( !$flag_hide_zero || $temp > 0 ) {
			// store in an array
			$build[] = $temp.' '.$key.($temp!=1?'s':null);

			// set flag to false, to allow 0's in lower periods
			$flag_hide_zero = true;
		}

		// get the remainder of seconds
		$int_seconds = fmod($int_seconds, $length);
	}

	// return output, if !empty, implode into string, else output $if_reached
	return ( !empty($build)?implode(', ', $build):$if_reached );
}

// ---------------------------------------------------------------
// This function show a popup window using a help_id (unused)
// ---------------------------------------------------------------

function popup_help ($help_id){
	echo "<a href='javascript:help_popup(".$help_id.")'>[H]</a>";
}

// ---------------------------------------------------------------
// no_permission () - Display no perm. access
// ---------------------------------------------------------------

function no_permission () {
	require("config.php");
	require ("include/languages/language_".$config["language"].".php");
	echo "<h3 class='error'>".$lang_label["no_permission_title"]."</h3>";
	echo "<img src='images/noaccess.png' alt='No access' width='120'><br><br>";
	echo "<table width=550>";
	echo "<tr><td>";
	echo $lang_label["no_permission_text"];
	echo "</table>";
	echo "<tr><td><td><td><td>";
	include "general/footer.php";
	exit;
}

// ---------------------------------------------------------------
// unmanaged_error -  Display generic error message and stop execution
// ---------------------------------------------------------------

function unmanaged_error ($error = "") {
	require("config.php");
	require ("include/languages/language_".$config["language"].".php");
	echo "<h3 class='error'>".lang_string("Unmanaged error")."</h3>";
	echo "<img src='images/errror.png' alt='error'><br><br>";
	echo "<table width=550>";
	echo "<tr><td>";
	echo lang_string("Unmanaged error_text");
	echo "<tr><td>";
	echo $error;
	echo "</table>";
	echo "<tr><td><td><td><td>";
	include "general/footer.php";
	exit;
}

function list_files($directory, $stringSearch, $searchHandler, $outputHandler) {
	$errorHandler = false;
	$result = array();
	if (! $directoryHandler = @opendir ($directory)) {
		echo ("<pre>\nerror: directory \"$directory\" doesn't exist!\n</pre>\n");
		return $errorHandler = true;
	}
	if ($searchHandler == 0) {
		while (false !== ($fileName = @readdir ($directoryHandler))) {
			@array_push ($result, $fileName);
		}
	}
	if ($searchHandler == 1) {
		while(false !== ($fileName = @readdir ($directoryHandler))) {
			if(@substr_count ($fileName, $stringSearch) > 0) {
				@array_push ($result, $fileName);
			}
		}
	}
	if (($errorHandler == true) &&  (@count ($result) === 0)) {
		echo ("<pre>\nerror: no filetype \"$fileExtension\" found!\n</pre>\n");
	} else {
		sort ($result);
		if ($outputHandler == 0) {
			return $result;
		}
		if ($outputHandler == 1) {
			echo ("<pre>\n");
			print_r ($result);
			echo ("</pre>\n");
		}
	}
}


function pagination ($count, $url, $offset ) {
	global $config;
	require ("include/languages/language_".$config["language"].".php");
	
	/* 	URL passed render links with some parameter
			&offset - Offset records passed to next page
	  		&counter - Number of items to be blocked 
	   	Pagination needs $url to build the base URL to render links, its a base url, like 
	   " http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
	   
	*/
	$block_limit = 15; // Visualize only $block_limit blocks
	if ($count > $config["block_size"]){
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
}


// ---------------------------------------------------------------
// Render data in a fashion way :-)
// ---------------------------------------------------------------
function format_numeric ($number, $decimals = 2, $dec_point = ".", $thousands_sep = ",") {
	if ($number == 0)
		return 0;
	// If has decimals
	if (fmod($number , 1) > 0)
		return number_format ($number, $decimals, $dec_point, $thousands_sep);
	else
		return number_format ($number, 0, $dec_point, $thousands_sep);
}

// ---------------------------------------------------------------
// Render numeric data in a easy way to the user
// ---------------------------------------------------------------
function format_for_graph ( $number , $decimals=2, $dec_point=".", $thousands_sep=",") {
	if ($number > "1000000")
		if (fmod ($number, 1000000) > 0)
			return number_format ($number/1000000, $decimals, $dec_point, $thousands_sep)." M";
		else
			return number_format ($number/1000000, 0, $dec_point, $thousands_sep)." M";
			
	if ($number > "1000")
		if (fmod ($number, 1000) > 0)
			return number_format ($number/1000, $decimals, $dec_point, $thousands_sep )." K";
		else
			return number_format ($number/1000, 0, $dec_point, $thousands_sep )." K";
	// If has decimals
	if (fmod ($number , 1)> 0)
		return number_format ($number, $decimals, $dec_point, $thousands_sep);
	return number_format ($number, 0, $dec_point, $thousands_sep);
}

function give_parameter_get ( $name, $default = "" ){
	$output = $default;
	if (isset ($_GET[$name])){
		$output = $_GET[$name];
	}
	return $output;
}

function give_parameter_post ( $name, $default = "" ){
	$output = $default;
	if (isset ($_POST[$name])){
		$output = $_POST[$name];
	}
	return $output;
}

function give_parameter_get_numeric ( $name, $default = "-1" ){
	$output = $default;
	if (isset ($_GET[$name])){
		$output = $_GET[$name];
	}
	if (is_numeric($output))
		return $output;
	else
		return -1;
}

function give_parameter_post_numeric ( $name, $default = "" ){
	$output = $default;
	if (isset ($_POST[$name])){
		$output = $_POST[$name];
	}
	if (is_numeric($output))
		return $output;
	else
		return -1;
}

function human_time_comparation ( $timestamp ){
	global $lang_label;
	if ($timestamp != ""){
		$ahora=date("Y/m/d H:i:s");
		$seconds = strtotime($ahora) - strtotime($timestamp);
	} else
		$seconds = 0;

	if ($seconds < 3600)
		$render = format_numeric($seconds/60,1)." ".$lang_label["minutes"];
	elseif (($seconds >= 3600) and ($seconds < 86400))
		$render = format_numeric ($seconds/3600,1)." ".$lang_label["hours"];
	elseif (($seconds >= 86400) and ($seconds < 2592000))
		$render = format_numeric ($seconds/86400,1)." ".$lang_label["days"];
	elseif (($seconds >= 2592000)  and ($seconds < 15552000))
		$render = format_numeric ($seconds/2592000,1)." ".$lang_label["months"];
	elseif ($seconds >= 15552000)
		$render = " +6 ".$lang_label["months"];
	return $render;
}

function human_time_description_raw ($seconds){
	global $lang_label;
	if ($seconds < 3600)
		$render = format_numeric($seconds/60,2)." ".$lang_label["minutes"];
	elseif (($seconds >= 3600) and ($seconds < 86400))
		$render = format_numeric ($seconds/3600,2)." ".$lang_label["hours"];
	elseif ($seconds >= 86400)
		$render = format_numeric ($seconds/86400,2)." ".$lang_label["days"];
	return $render;	
}

function human_time_description ($period){
	global $lang_label;
	switch ($period) {
	case 3600: 	$period_label = $lang_label["hour"];
			break;
	case 7200: 	$period_label = $lang_label["2_hours"];
			break;
	case 21600: 	$period_label = $lang_label["6_hours"];
			break;
	case 43200: 	$period_label = $lang_label["12_hours"];
			break;
	case 86400: 	$period_label = $lang_label["last_day"];
			break;
	case 172800: 	$period_label = $lang_label["two_days"];
			break;
	case 432000: 	$period_label = $lang_label["five_days"];
			break;
	case 604800: 	$period_label = $lang_label["last_week"];
			break;
	case 1296000: 	$period_label = $lang_label["15_days"];
			break;
	case 2592000: 	$period_label = $lang_label["last_month"];
			break;
	case 5184000: 	$period_label = $lang_label["two_month"];
			break;
	case 15552000: 	$period_label = $lang_label["six_months"];
			break;
	default: 	$period_label = human_time_description_raw ($period);
	}
	return $period_label;
}

// This function returns MYSQL Date from now - seconds passed as parameter

function human_date_relative ( $seconds ) {
	$ahora=date("Y/m/d H:i:s");
	$ahora_s = date("U");
	$ayer = date ("Y/m/d H:i:s", $ahora_s - $seconds);
	return $ayer;
}

function render_time ($lapse) {
	$myhour = intval(($lapse*30)/60);
	if ($myhour == 0)
		$output = "00";
	else
		$output = $myhour;
	$output .=":";
	$mymin = fmod(($lapse*30),60);
	if ($mymin == 0)
		$output .= "00";
	else
		$output .= $mymin;
	return $output;
}

function get_parameter ($name, $default = '') {
	// POST has precedence
	if (isset($_POST[$name]))
		return get_parameter_post ($name, $default);

	if (isset($_GET[$name]))
		return get_parameter_get ($name, $default);

	return $default;
}

function get_parameter_get ($name, $default = "") {
	if ((isset ($_GET[$name])) && ($_GET[$name] != ""))
		return safe_input ($_GET[$name]);

	return $default;
}

function get_parameter_post ( $name, $default = "" ){
	if ((isset ($_POST[$name])) && ($_POST[$name] != ""))
		return safe_input ($_POST[$name]);

	return $default;
}

function get_alert_priority ($priority = 0) {
	global $config;
	switch ($priority) {
	case 0: 
		return lang_string("Maintenance");
		break;
	case 1:
		return lang_string("Informational");
		break;
	case 2:
		return lang_string("Normal");
		break;
	case 3:
		return lang_string("Warning");
		break;
	case 4:
		return lang_string("Critical");
		break;
	}
	return '';
}

function get_alert_days ( $row ){
	global $config;
	global $lang_label;
	$days_output = "";

	$check = $row["monday"] + $row["tuesday"] + $row["wednesday"] + $row["thursday"]+ $row["friday"] + $row["saturday"] + $row["sunday"];
	
	if ($row["monday"] != 0)
		return "Mo";
	if ($row["tuesday"] != 0)
		return "Tu";
	if ($row["wednesday"] != 0)
		return "We";
	if ($row["thursday"] != 0)
		return "Th";
	if ($row["friday"] != 0)
		return "Fr";
	if ($row["saturday"] != 0)
		return "Sa";
	if ($row["sunday"] != 0)
		return "Su";
	if ($check == 7)
		return lang_string ("all");
	
	return lang_string ("none");
}

function get_alert_times ($row2){
	global $config;
	global $lang_label;

	if ($row2["time_from"]){
		$time_from_table = $row2["time_from"];
	} else {
		$time_from_table = lang_string("N/A");
	}
	if ($row2["time_to"]){
		$time_to_table = $row2["time_to"];
	} else {
		$time_to_table = lang_string("N/A");
	}
	$string = "";
	if ($time_to_table == $time_from_table)
		$string .= $lang_label["N/A"];
	else
		$string .= substr($time_from_table,0,5)." - ".substr($time_to_table,0,5);
	return $string;
}

function show_alert_row_edit ($row2, $tdcolor = "datos", $id_tipo_modulo = 1, $combined = 0){
	global $config;
	global $lang_label;

	$string = "";
	if ($row2["disable"] == 1){
		$string .= "<td class='$tdcolor'><b><i>".$lang_label["disabled"]."</b></i>";
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
		$string = $string."<td colspan=2 class='$tdcolor'>".$lang_label["text"]."</td>";
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
		$recovery_notify = lang_string("Yes");
	else
		$recovery_notify = lang_string("No");

	// calculate priority
	$priority = get_alert_priority ($row2["priority"]);

	// calculare firing conditions
	if ($row2["alert_text"] != "") {
		$firing_cond = lang_string("text")."(".substr($row2["alert_text"],0,8).")";
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
		lang_string("Recovery")."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$recovery_notify</b></td></tr>
		<tr><th colspan='2' width='91'>".
		lang_string("Priority")."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$priority</b></td></tr>
		<tr><th colspan='2' width='91'>".
		lang_string("Alert Ctrl.")."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>".$firing_cond."</b></td></tr>
		<tr><th colspan='2' width='91'>".
		lang_string("Firing days")."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>".$firing_days."</b></td></tr>
		</table></span></A>";

	return $string;
}

function show_alert_show_view ($data, $tdcolor = "datos", $combined = 0) {
	global $config;
	global $lang_label;

	if ($combined == 0) {
		$module_name = get_db_sql ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ".$data["id_agente_modulo"]);
		$agent_name = get_db_sql ("SELECT tagente.nombre FROM tagente_modulo, tagente WHERE tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.id_agente_modulo = ".$data["id_agente_modulo"]);
		$id_agente = get_db_sql ("SELECT id_agente FROM tagente_modulo WHERE id_agente_modulo = ".$data["id_agente_modulo"]);
	} else {
		$agent_name =  get_db_sql ("SELECT nombre FROM tagente WHERE id_agente =".$data["id_agent"]);
		$id_agente = $data["id_agent"];
	}
	$alert_name = get_db_sql ("SELECT nombre FROM talerta WHERE id_alerta = ".$data["id_alerta"]);

	echo "<td class='".$tdcolor."'>".$alert_name."</td>";
	if ($combined == 0) {
		echo "<td class='".$tdcolor."'>".substr($module_name,0,21)."</td>";
	} else {
		echo "<td class='".$tdcolor."'>";
		// More details EYE tooltip (combined)
		echo " <a href='#' class='info_table'><img class='top' src='images/eye.png' alt=''><span>";
		echo show_alert_row_mini ($data["id_aam"]);
		echo "</span></a> ";
		echo substr($agent_name,0,21)."</td>"; 
	}

	// Description
	echo "<td class='".$tdcolor."'>".$data["descripcion"]."</td>";

	// Extended info    
	echo "<td class='".$tdcolor."'>";

	// Has recovery notify activated ?
	if ($data["recovery_notify"] > 0)
		$recovery_notify = lang_string("Yes");
	else
		$recovery_notify = lang_string("No");

	// calculate priority
	$priority = get_alert_priority ($data["priority"]);

	// calculare firing conditions
	if ($data["alert_text"] != ""){
		$firing_cond = lang_string("text")."(".substr($data["alert_text"],0,8).")";
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
		lang_string("Recovery")."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$recovery_notify</b></td></tr>
		<tr><th colspan='2' width='91'>".
		lang_string("Priority")."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>$priority</b></td></tr>
		<tr><th colspan='2' width='91'>".
		lang_string("Alert Ctrl.")."</th></tr>
		<tr><td colspan='2' class='datos' align='center'><b>".$firing_cond."</b></td></tr>
		<tr><th colspan='2' width='91'>".
		lang_string("Firing days")."</th></tr>
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
		echo "<td class='".$tdcolor."' colspan=2>".$lang_label["text"]."</td>";
	else {
		echo "<td class='".$tdcolor."'>".$mymin."</td>";
		echo "<td class='".$tdcolor."'>".$mymax."</td>";
	}
	echo "<td  align='center' class='".$tdcolor."'>".human_time_description($data["time_threshold"]);
	if ($data["last_fired"] == "0000-00-00 00:00:00") {
		echo "<td align='center' class='".$tdcolor."f9'>".$lang_label["never"]."</td>";
	} else {
		echo "<td align='center' class='".$tdcolor."f9'>".human_time_comparation ($data["last_fired"])."</td>";
	}
	echo "<td align='center' class='".$tdcolor."'>".$data["times_fired"]."</td>";
	if ($data["times_fired"] <> 0){
		echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_red.png' title='".$lang_label["fired"]."'>";
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
			<img width='20' height='9' src='images/pixel_green.png' title='".$lang_label["not_fired"]."'></td>";
	}
}

function form_render_check ($name_form, $value_form = 1){
	echo "<input name='$name_form' type='checkbox' ";
	if ($value_form != 0) {
		echo "checked='1' ";
	}
	echo "value=1>";
}

/**
 * Get report types in an array.
 * 
 * @return An array with all the possible reports in Pandora where the array index is the report id.
 */
function get_report_types () {
	$types = array ();
	$types['simple_graph'] = lang_string ('simple_graph');
	$types['custom_graph'] = lang_string ('custom_graph');
	$types['SLA'] = lang_string ('SLA');
	$types['event_report'] = lang_string ('event_report');
	$types['alert_report'] = lang_string ('alert_report');
	$types['monitor_report'] = lang_string ('monitor_report');
	$types['avg_value'] = lang_string ('avg_value');
	$types['max_value'] = lang_string ('max_value');
	$types['min_value'] = lang_string ('min_value');
	$types['sumatory'] = lang_string ('sumatory');
	$types['general_group_report'] = lang_string ('general_group_report');
	$types['monitor_health'] = lang_string ('monitor_health');
	$types['agents_detailed'] = lang_string ('agents_detailed');

	return $types;
}

/**
 * Get report type name from type id.
 *
 * @param $type Type id of the report.
 *
 * @return Report type name.
 */
function get_report_name ($type) {
	$types = get_report_types ();
	if (! isset ($types[$type]))
		return lang_string ('unknown');
	return $types[$type];
}

/**
 * Get report type name from type id.
 *
 * @param $type Type id of the report.
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
 * @param $module_name Module name to check.
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
 * @param $module_name Module name to check.
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
 * @param $module_name Module name to check.
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
 * @param $module_name Module name to check.
 *
 * @return true if the module is of type "string"
 */
function is_module_data_string ($module_name) {
	$result = ereg ('^(.*string)$', $module_name);
	if ($result === false)
		return false;
	return true;
}

function get_event_types () {
	$types = array ();
	$types['unknown'] = lang_string ('unknown');
	$types['monitor_up'] = lang_string ('monitor_up');
	$types['monitor_down'] = lang_string ('monitor_down');
	$types['alert_fired'] = lang_string ('alert_fired');
	$types['alert_recovered'] = lang_string ('alert_recovered');
	$types['alert_ceased'] = lang_string ('alert_ceased');
	$types['alert_manual_validation'] = lang_string ('alert_manual_validation');
	$types['recon_host_detected'] = lang_string ('recon_host_detected');
	$types['new_agent'] = lang_string ('new_agent');
	$types['system'] = lang_string ('sytem');
	$types['error'] = lang_string ('error');
	return $types;
}

function form_priority ($priority = 0, $form_name = "priority", $show_all = 0) {
	global $config;

	echo '<select name="'.$form_name.'">';
	switch ($priority) {
	case 0: 
		echo "<option value=0>".lang_string("Maintenance");
		echo "<option value=1>".lang_string("Informational");
		echo "<option value=2>".lang_string("Normal");
		echo "<option value=3>".lang_string("Warning");
		echo "<option value=4>".lang_string("Critical");
		break;
	case 1: 
		echo "<option value=1>".lang_string("Informational");
		echo "<option value=0>".lang_string("Maintenance");
		echo "<option value=2>".lang_string("Normal");
		echo "<option value=3>".lang_string("Warning");
		echo "<option value=4>".lang_string("Critical");
		break;
	case 2: 
		echo "<option value=2>".lang_string("Normal");
		echo "<option value=0>".lang_string("Maintenance");
		echo "<option value=1>".lang_string("Informational");
		echo "<option value=3>".lang_string("Warning");
		echo "<option value=4>".lang_string("Critical");
		break;
	case 3: 
		echo "<option value=3>".lang_string("Warning");
		echo "<option value=0>".lang_string("Maintenance");
		echo "<option value=1>".lang_string("Informational");
		echo "<option value=2>".lang_string("Normal");
		echo "<option value=4>".lang_string("Critical");
		break;
	case 4: 
		echo "<option value=4>".lang_string("Critical");
		echo "<option value=0>".lang_string("Maintenance");
		echo "<option value=1>".lang_string("Informational");
		echo "<option value=2>".lang_string("Normal");
		echo "<option value=3>".lang_string("Warning");
		break;
	case -1: 
		echo "<option value=-1>".lang_string("All");
		echo "<option value=4>".lang_string("Critical");
		echo "<option value=0>".lang_string("Maintenance");
		echo "<option value=1>".lang_string("Informational");
		echo "<option value=2>".lang_string("Normal");
		echo "<option value=3>".lang_string("Warning");
		break;
	}
	if ($show_all == 1)
		echo "<option value=-1>".lang_string("All");
	echo "</select>";    
}


function return_priority ($priority){
	global $config;

	switch ($priority) {
	case 0: 
		return lang_string ("Maintenance");
	case 1: 
		return lang_string ("Informational");
	case 2: 
		return lang_string ("Normal");
	case 3: 
		return lang_string ("Warning");
	case 4: 
		return lang_string ("Critical");
	case -1: 
		return lang_string ("All");
	}
}

// Show combo with agents
function form_agent_combo ($id_agent = 0, $form_name = "id_agent") {
	global $config;
	echo '<select name="'.$form_name.'" style="width:120px">';
	if ($id_agent != 0)
		echo "<option value='".$id_agent."'>".dame_nombre_agente($id_agent)."</option>";
	else
		echo "<option value='0'>".lang_string("None")."</option>";
	$sql = 'SELECT * FROM tagente';
	$result = mysql_query($sql);
	while ($row = mysql_fetch_array ($result)) {
		// if (give_acl($config["id_user"], $row["id_grupo"], "AR")==1)
		echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
	}
	echo "</select>";
}
?>
