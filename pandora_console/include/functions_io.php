<?php
/** 
 * Cleans a string by encoding to UTF-8 and replacing the HTML
 * entities. UTF-8 is necessary for foreign chars like asian 
 * and our databases are (or should be) UTF-8
 * 
 * @param mixed String or array of strings to be cleaned.
 * 
 * @return mixed The cleaned string or array.
 */
function safe_input($value) {
	//Stop!! Are you sure to modify this critical code? Because the older
	//versions are serius headache in many places of Pandora.
	
	if (is_numeric($value))
		return $value;
		
	if (is_array($value)) {
		array_walk($value, "safe_input");
		return $value;
	}
	
	//Clean the trash mix into string because of magic quotes.
	if (get_magic_quotes_gpc() == 1) {
		$value = stripslashes($value);
	}
	
	if (! mb_check_encoding ($value, 'UTF-8'))
		$value = utf8_encode ($value);
	
	$valueHtmlEncode =  htmlentities ($value, ENT_QUOTES, "UTF-8", true);
		
	//Replace the character '\' for the equivalent html entitie
	$valueHtmlEncode = str_replace('\\', "&#92;", $valueHtmlEncode);

    // First attempt to avoid SQL Injection based on SQL comments
    // Specific for MySQL.
    $valueHtmlEncode = str_replace('/*', "&#47;&#42;", $valueHtmlEncode);
    $valueHtmlEncode = str_replace('*/', "&#42;&#47;", $valueHtmlEncode);

	return $valueHtmlEncode;
}

/**
 * Convert the $value encode in html entity to clear char string. This function 
 * should be called always to "clean" HTML encoded data; to render to a text
 * plain ascii file, to render to console, or to put in any kind of data field
 * who doesn't make the HTML render by itself.
 * 
 * @param mixed String or array of strings to be cleaned.
 * @param boolean $utf8 Flag, set the output encoding in utf8, by default true.
 * 
 * @return unknown_type
 */
function safe_output($value, $utf8 = true)
{
	if (is_numeric($value))
		return $value;
		
	if (is_array($value)) {
		array_walk($value, "safe_output");
		return $value;
	}
	
	if (! mb_check_encoding ($value, 'UTF-8'))
		$value = utf8_encode ($value);
	
	if ($utf8) {
		$valueHtmlEncode =  html_entity_decode ($value, ENT_QUOTES, "UTF-8");
	}
	else {
		$valueHtmlEncode =  html_entity_decode ($value, ENT_QUOTES);
	}
	
	return $valueHtmlEncode;	
}

/** 
 * Use to clean HTML entities when get_parameter or safe_input functions dont work
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
 * Cleans a string by encoding to UTF-8 and replacing the HTML
 * entities to their numeric counterparts (possibly double encoding)
 * 
 * @param mixed String or array of strings to be cleaned.
 * 
 * @return mixed The cleaned string or array.
 */	
function safe_output_xml ($string) {
	if (is_numeric ($string))
		return $string;
	
	if (is_array ($string)) {
		array_walk ($string, 'safe_output_xml');
		return $string;
	}
	
	static $table;
	static $replace;
	
	if (empty ($table)) {
		$table = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);	
		$replace = array ();
		
		foreach ($table as $key => $value){
			$table[$key] = "/".$value."/";
			$char = htmlentities ($key, ENT_QUOTES, "UTF-8");
			$replace[$char] = "&#".ord ($key).";";
		}
	}
	
	//now perform a replacement using preg_replace
	//each matched value in $table will be replaced with the corresponding value in $replace
	return preg_replace ($table, $replace, $string);
}

/**
 * Avoid magic_quotes protection
 *
 * @param string Text string to be stripped of magic_quotes protection
 */
function unsafe_string ($string) {
	if (get_magic_quotes_gpc ()) 
		return stripslashes ($string);
	return $string;
}


?>
