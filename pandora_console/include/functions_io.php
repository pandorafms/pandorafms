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
 * @subpackage Io
 */

/**
 * Safe input function for array.
 * 
 * @param mixed $item The item pass as reference of item.
 * 
 * @return void
 */
function io_safe_input_array(&$item) {
	$item = io_safe_input($item);
}

/** 
 * Scape in a string de reserved characters to use it
 * into a regular expression
 * 
 * @param string String to be scaped
 * 
 * @return string Scaped string
 */
function io_safe_expreg($string) {
	// Scape regular expression characters
	$string = str_replace('(','\(',$string);
	$string = str_replace(')','\)',$string);
	$string = str_replace('{','\{',$string);
	$string = str_replace('}','\}',$string);
	$string = str_replace('[','\[',$string);
	$string = str_replace(']','\]',$string);
	$string = str_replace('.','\.',$string);
	$string = str_replace('*','\*',$string);
	$string = str_replace('+','\+',$string);
	$string = str_replace('?','\?',$string);
	$string = str_replace('|','\|',$string);
	$string = str_replace('^','\^',$string);
	$string = str_replace('$','\$',$string);
	
	return $string;
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
function io_safe_input($value) {
	//Stop!! Are you sure to modify this critical code? Because the older
	//versions are serius headache in many places of Pandora.
	
	if (is_numeric($value))
		return $value;
		
	if (is_array($value)) {
		array_walk($value, "io_safe_input_array");
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
	
	//Replace ( for the html entitie
	$valueHtmlEncode = str_replace('(', "&#40;", $valueHtmlEncode);
	
	//Replace ( for the html entitie
	$valueHtmlEncode = str_replace(')', "&#41;", $valueHtmlEncode);	
	
	//Replace some characteres for html entities
	for ($i=0;$i<33;$i++) {
		$valueHtmlEncode = str_ireplace(chr($i),io_ascii_to_html($i), $valueHtmlEncode);			
	}

	return $valueHtmlEncode;
}

/** 
 * Cleans a string by encoding to UTF-8 and replacing the HTML
 * entities for HTML only. UTF-8 is necessary for foreign chars 
 * like asian and our databases are (or should be) UTF-8
 * 
 * @param mixed String or array of strings to be cleaned.
 * 
 * @return mixed The cleaned string or array.
 */
function io_safe_input_html($value) {
	//Stop!! Are you sure to modify this critical code? Because the older
	//versions are serius headache in many places of Pandora.

	if (is_numeric($value))
		return $value;
		
	if (is_array($value)) {
		array_walk($value, "io_safe_input");
		return $value;
	}
	
	//Clean the trash mix into string because of magic quotes.
	if (get_magic_quotes_gpc() == 1) {
		$value = stripslashes($value);
	}
	
	if (! mb_check_encoding ($value, 'UTF-8'))
		$value = utf8_encode ($value);

	return $value;
}

/** 
 * Convert ascii char to html entitines
 * 
 * @param int num of ascci char
 * 
 * @return string String of html entitie
 */
function io_ascii_to_html($num) {
	
	if ($num <= 15) {
		return "&#x0".dechex($num).";";
	} else {
		return "&#x".dechex($num).";";
	}
}

/** 
 * Convert hexadecimal html entity value to char
 * 
 * @param string String of html hexadecimal value
 * 
 * @return string String with char
 */
function io_html_to_ascii($hex) {
		
	$dec = hexdec($hex);
	
	return chr($dec);
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
function io_safe_output($value, $utf8 = true)
{
	if (is_numeric($value))
		return $value;
		
	if (is_array($value)) {
		array_walk($value, "io_safe_output");
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
	
	//Replace the html entitie of ( for the char
	$valueHtmlEncode = str_replace("&#40;", '(', $valueHtmlEncode);
	
	//Replace the html entitie of ) for the char
	$valueHtmlEncode = str_replace("&#41;", ')', $valueHtmlEncode);

	//Replace the html entitie of < for the char
	$valueHtmlEncode = str_replace("&lt;", '<', $valueHtmlEncode);

	//Replace the html entitie of > for the char
	$valueHtmlEncode = str_replace("&gt;", '>', $valueHtmlEncode);			
	
	//Revert html entities to chars
	for ($i=0;$i<33;$i++) {
		$valueHtmlEncode = str_ireplace("&#x".dechex($i).";",io_html_to_ascii(dechex($i)), $valueHtmlEncode);			
	}	
	
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
function io_safe_output_html($value, $utf8 = true)
{
	if (is_numeric($value))
		return $value;
		
	if (is_array($value)) {
		array_walk($value, "io_safe_output");
		return $value;
	}
		
	//Replace the html entitie of ( for the char
	$value = str_replace("&#40;", '(', $value);
	
	//Replace the html entitie of ) for the char
	$value = str_replace("&#41;", ')', $value);	

	//Replace the <
	$value = str_replace("&lt;", "<", $value);
	
	//Replace the <
	$value = str_replace("&gt;", ">", $value);
	
	//Revert html entities to chars
	for ($i=0;$i<33;$i++) {
		$value = str_ireplace("&#x".dechex($i).";",io_html_to_ascii(dechex($i)), $value);			
	}	
	
	return $value;	
}

/** 
 * Use to clean HTML entities when get_parameter or io_safe_input functions dont work
 * 
 * @param string String to be cleaned
 * 
 * @return string Cleaned string
 */
function io_salida_limpia ($string) {
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
function io_safe_output_xml ($string) {
	if (is_numeric ($string))
		return $string;
	
	if (is_array ($string)) {
		array_walk ($string, 'io_safe_output_xml');
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
function io_unsafe_string ($string) {
	if (get_magic_quotes_gpc ()) 
		return stripslashes ($string);
	return $string;
}

/**
 * Get a translated string
 *
 * @param string String to translate. It can have special format characters like
 * a printf
 * @param mixed Optional parameters to be replaced in string. Example:
 * <code>
 * echo __('Hello!');
 * echo __('Hello, %s!', $user);
 * </code>
 *
 * @return string The translated string. If not defined, the same string will be returned
 */
function __ ($string /*, variable arguments */) {
	global $l10n;

	$extensions = extensions_get_extensions();
	if (empty($extensions)) $extensions = array();

	global $config;

	if ($config['enterprise_installed']) {
		if (isset($config['translate_string_extension_installed']) && $config['translate_string_extension_installed'] == 1) {
			if (array_key_exists('translate_string.php', $extensions)) {
				enterprise_include_once('extensions/translate_string/functions.php');

				$tranlateString = get_defined_translation($string);

				if ($tranlateString !== false) {
					return $tranlateString;
				}
			}
		}
	}

	if ($string == '') {
		return $string;
	}

	if (func_num_args () == 1) {
		if (is_null ($l10n))
		return $string;
		return $l10n->translate ($string);
	}

	$args = func_get_args ();
	$string = array_shift ($args);

	if (is_null ($l10n))
	return vsprintf ($string, $args);

	return vsprintf ($l10n->translate ($string), $args);
}

?>
