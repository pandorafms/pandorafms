<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
 * @subpackage Io
 */


/**
 * Safe input function for array.
 *
 * @param mixed $item The item pass as reference of item.
 *
 * @return void
 */
function io_safe_input_array(&$item)
{
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
function io_safe_expreg($string)
{
    // Scape regular expression characters
    $string = str_replace('(', '\(', $string);
    $string = str_replace(')', '\)', $string);
    $string = str_replace('{', '\{', $string);
    $string = str_replace('}', '\}', $string);
    $string = str_replace('[', '\[', $string);
    $string = str_replace(']', '\]', $string);
    $string = str_replace('.', '\.', $string);
    $string = str_replace('*', '\*', $string);
    $string = str_replace('+', '\+', $string);
    $string = str_replace('?', '\?', $string);
    $string = str_replace('|', '\|', $string);
    $string = str_replace('^', '\^', $string);
    $string = str_replace('$', '\$', $string);

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
function io_safe_input($value)
{
    // Stop!! Are you sure to modify this critical code? Because the older
    // versions are serius headache in many places of Pandora.
    if (is_numeric($value)) {
        return $value;
    }

    if (is_array($value)) {
        array_walk($value, 'io_safe_input_array');
        return $value;
    }

    if (! mb_check_encoding($value, 'UTF-8')) {
        $value = utf8_encode($value);
    }

    $valueHtmlEncode = htmlentities($value, ENT_QUOTES, 'UTF-8', true);

    // Replace the character '\' for the equivalent html entitie
    $valueHtmlEncode = str_replace('\\', '&#92;', $valueHtmlEncode);

    // First attempt to avoid SQL Injection based on SQL comments
    // Specific for MySQL.
    $valueHtmlEncode = str_replace('/*', '&#47;&#42;', $valueHtmlEncode);
    $valueHtmlEncode = str_replace('*/', '&#42;&#47;', $valueHtmlEncode);

    // Replace ( for the html entitie
    $valueHtmlEncode = str_replace('(', '&#40;', $valueHtmlEncode);

    // Replace ( for the html entitie
    $valueHtmlEncode = str_replace(')', '&#41;', $valueHtmlEncode);

    // Fixed the º character, because the Perl in the Pandora Server
    // use the hex value instead the human readble.
    // TICKET: #1495
    $valueHtmlEncode = str_replace('&ordm;', '&#xba;', $valueHtmlEncode);

    // Fixed the ° character.
    // TICKET: 1223
    $valueHtmlEncode = str_replace('&deg;', '&#176;', $valueHtmlEncode);

    // Fixed the ¿ charater.
    $valueHtmlEncode = str_replace('&iquest;', '¿', $valueHtmlEncode);
    // Fixed the ¡ charater.
    $valueHtmlEncode = str_replace('&iexcl;', '¡', $valueHtmlEncode);
    // Fixed the € charater.
    $valueHtmlEncode = str_replace('&euro;', '€', $valueHtmlEncode);

    // Replace some characteres for html entities
    for ($i = 0; $i < 33; $i++) {
        $valueHtmlEncode = str_ireplace(
            chr($i),
            io_ascii_to_html($i),
            $valueHtmlEncode
        );
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
function io_safe_input_html($value)
{
    // Stop!! Are you sure to modify this critical code? Because the older
    // versions are serius headache in many places of Pandora.
    if (is_numeric($value)) {
        return $value;
    }

    if (is_array($value)) {
        array_walk($value, 'io_safe_input');
        return $value;
    }

    if (! mb_check_encoding($value, 'UTF-8')) {
        $value = utf8_encode($value);
    }

    return $value;
}


/**
 * Convert ascii char to html entitines
 *
 * @param int num of ascci char
 *
 * @return string String of html entitie
 */
function io_ascii_to_html($num)
{
    if ($num <= 15) {
        return '&#x0'.dechex($num).';';
    } else {
        return '&#x'.dechex($num).';';
    }
}


/**
 * Convert hexadecimal html entity value to char
 *
 * @param string String of html hexadecimal value
 *
 * @return string String with char
 */
function io_html_to_ascii($hex)
{
    $dec = hexdec($hex);

    return chr($dec);
}


/**
 * Safe output function for array.
 *
 * @param mixed   $item The item pass as reference of item.
 * @param mixed   $key  The key of array.
 * @param boolean $utf8 The encoding.
 *
 * @return void
 */
function io_safe_output_array(&$item, $key=false, $utf8=true)
{
    $item = io_safe_output($item, $utf8);
}


/**
 * Convert the $value encode in html entity to clear char string. This function
 * should be called always to "clean" HTML encoded data; to render to a text
 * plain ascii file, to render to console, or to put in any kind of data field
 * who doesn't make the HTML render by itself.
 *
 * @param string|array $value String or array of strings to be cleaned.
 * @param boolean      $utf8  Flag, set the output encoding in utf8, by default true.
 *
 * @return mixed
 */
function io_safe_output($value, $utf8=true)
{
    if (is_numeric($value)) {
        return $value;
    }

    if (is_array($value)) {
        array_walk($value, 'io_safe_output_array');

        return $value;
    }

    if (! mb_check_encoding($value, 'UTF-8')) {
        $value = utf8_encode($value);
    }

    // Replace the html entitie of ( for the char
    $value = str_replace('&#40;', '(', $value);

    // Replace the html entitie of ) for the char
    $value = str_replace('&#41;', ')', $value);

    // Replace the html entitie of < for the char
    $value = str_replace('&lt;', '<', $value);

    // Replace the html entitie of > for the char
    $value = str_replace('&gt;', '>', $value);

    if ($utf8) {
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    } else {
        $value = html_entity_decode($value, ENT_QUOTES);
    }

    return $value;
}


/**
 * Convert the $value encode in html entity to clear char string. This function
 * should be called always to "clean" HTML encoded data; to render to a text
 * plain ascii file, to render to console, or to put in any kind of data field
 * who doesn't make the HTML render by itself.
 *
 * @param mixed String or array of strings to be cleaned.
 * @param boolean                                        $utf8 Flag, set the output encoding in utf8, by default true.
 *
 * @return unknown_type
 */
function io_safe_output_html($value, $utf8=true)
{
    if (is_numeric($value)) {
        return $value;
    }

    if (is_array($value)) {
        array_walk($value, 'io_safe_output');
        return $value;
    }

    // Replace the html entitie of ( for the char
    $value = str_replace('&#40;', '(', $value);

    // Replace the html entitie of ) for the char
    $value = str_replace('&#41;', ')', $value);

    // Replace the <
    $value = str_replace('&lt;', '<', $value);

    // Replace the <
    $value = str_replace('&gt;', '>', $value);

    // Revert html entities to chars
    for ($i = 0; $i < 33; $i++) {
        $value = str_ireplace('&#x'.dechex($i).';', io_html_to_ascii(dechex($i)), $value);
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
function io_salida_limpia($string)
{
    $quote_style = ENT_QUOTES;
    static $trans;

    if (! isset($trans)) {
        $trans = get_html_translation_table(HTML_ENTITIES, $quote_style);
        foreach ($trans as $key => $value) {
            $trans[$key] = '&#'.ord($key).';';
        }

        // dont translate the '&' in case it is part of &xxx;
        $trans[chr(38)] = '&';
    }

    // after the initial translation, _do_ map standalone "&" into "&#38;"
    return preg_replace(
        '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/',
        '&#38;',
        strtr($string, $trans)
    );
}


/**
 * Cleans a string by encoding to UTF-8 and replacing the HTML
 * entities to their numeric counterparts (possibly double encoding)
 *
 * @param mixed String or array of strings to be cleaned.
 *
 * @return mixed The cleaned string or array.
 */
function io_safe_output_xml($string)
{
    if (is_numeric($string)) {
        return $string;
    }

    if (is_array($string)) {
        array_walk($string, 'io_safe_output_xml');
        return $string;
    }

    static $table;
    static $replace;

    if (empty($table)) {
        $table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
        $replace = [];

        foreach ($table as $key => $value) {
            $table[$key] = '/'.$value.'/';
            $char = htmlentities($key, ENT_QUOTES, 'UTF-8');
            $replace[$char] = '&#'.ord($key).';';
        }
    }

    // now perform a replacement using preg_replace
    // each matched value in $table will be replaced with the corresponding value in $replace
    return preg_replace($table, $replace, $string);
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
function __($string /*, variable arguments */)
{
    global $l10n;
    global $config;
    static $extensions_cache = [];

    if (isset($config['id_user'])) {
        if (count($extensions_cache) > 0 && array_key_exists($config['id_user'], $extensions_cache)) {
            $extensions = $extensions_cache[$config['id_user']];
        } else {
            $extensions = extensions_get_extensions();
            $extensions_cache[$config['id_user']] = $extensions;
        }
    } else {
        $extensions = null;
    }

    if (empty($extensions)) {
        $extensions = [];
    }

    global $config;

    if (defined('METACONSOLE')) {
        enterprise_include_once('meta/include/functions_meta.php');

        $tranlateString = call_user_func_array(
            'meta_get_defined_translation',
            array_values(func_get_args())
        );

        if ($tranlateString !== false) {
            return $tranlateString;
        }
    } else if (enterprise_installed()
        && isset($config['translate_string_extension_installed'])
        && $config['translate_string_extension_installed'] == 1
        && array_key_exists('translate_string.php', $extensions)
    ) {
        enterprise_include_once('extensions/translate_string/functions.php');

        $tranlateString = call_user_func_array(
            'get_defined_translation',
            array_values(func_get_args())
        );

        if ($tranlateString !== false) {
            return $tranlateString;
        }
    }

    if ($string == '') {
        return $string;
    }

    if (func_num_args() == 1) {
        if (is_null($l10n)) {
            return $string;
        }

        return str_replace('\'', '`', $l10n->translate($string));
    }

    $args = func_get_args();
    $string = array_shift($args);

    if (is_null($l10n)) {
        return vsprintf($string, $args);
    }

    return vsprintf(str_replace('\'', '`', $l10n->translate($string)), $args);
}


/**
 * Get a translated string for extension
 *
 * @param string String to translate. It can have special format characters like
 * a printf
 * @param mixed Optional parameters to be replaced in string. Example:
 * <code>
 * echo ___('Hello!');
 * echo ___('Hello, %s!', $user);
 * </code>
 *
 * @return string The translated string. If not defined, the same string will be returned
 */
function ___($string /*, variable arguments */)
{
       global $config;

       $trace = debug_backtrace();
    foreach ($config['extensions'] as $extension) {
            $extension_file = $extension['file'];
        if (!isset($config['extensions'][$extension_file]['translate_function'])) {
               continue;
        }

        foreach ($trace as $item) {
            if (pathinfo($item['file'], PATHINFO_BASENAME) == $extension_file) {
                $tranlateString = call_user_func_array(
                    $config['extensions'][$extension_file]['translate_function'],
                    array_values(func_get_args())
                );
                if ($tranlateString !== false) {
                    return $tranlateString;
                }
            }
        }
    }

    return call_user_func_array(
        '__',
        array_values(func_get_args())
    );
}


/**
 * json_encode for multibyte characters.
 *
 * @param string Text string to be encoded.
 */
function io_json_mb_encode($string, $encode_options=0)
{
    $v = json_encode($string, $encode_options);
    $v = preg_replace_callback(
        "/\\\\u([0-9a-zA-Z]{4})/",
        function ($matches) {
            return mb_convert_encoding(
                pack('H*', $matches[1]),
                'UTF-8',
                'UTF-16'
            );
        },
        $v
    );
    $v = preg_replace('/\\\\\//', '/', $v);
    return $v;
}


/**
 * Prepare the given password to be stored in the Pandora FMS Database,
 * encrypting it if necessary.
 *
 * @param string password Password to be stored.
 *
 * @return string The processed password.
 */
function io_input_password($password)
{
    global $config;

    enterprise_include_once('include/functions_crypto.php');
    $ciphertext = enterprise_hook(
        'openssl_encrypt_decrypt',
        [
            'encrypt',
            io_safe_input($password),
        ]
    );
    if ($ciphertext === ENTERPRISE_NOT_HOOK) {
            return io_safe_input($password);
    }

    return $ciphertext;
}


/**
 * Process the given password read from the Pandora FMS Database,
 * decrypting it if necessary.
 *
 * @param string $password  Password read from the DB.
 * @param string $wrappedBy Wrap the password with the informed character.
 *
 * @return string The processed password.
 */
function io_output_password($password, $wrappedBy='')
{
    global $config;

    enterprise_include_once('include/functions_crypto.php');
    $plaintext = enterprise_hook(
        'openssl_encrypt_decrypt',
        [
            'decrypt',
            $password,
        ]
    );

    $output = ($plaintext === ENTERPRISE_NOT_HOOK) ? $password : $plaintext;

    return sprintf(
        '%s%s%s',
        $wrappedBy,
        io_safe_output($output),
        $wrappedBy
    );
}


/**
 * Clean html tags symbols for prevent use JS
 *
 * @param string $string String for safe.
 *
 * @return string
 */
function io_safe_html_tags(string $string)
{
    // Must have safe output for work properly.
    $string = io_safe_output($string);
    if (strpos($string, '<') !== false && strpos($string, '>') !== false) {
        $output = strstr($string, '<', true);
        $tmpOutput = strstr($string, '<');
        $output .= strstr(substr($tmpOutput, 1), '>', true);
        $tmpOutput = strstr($string, '>');
        $output .= substr($tmpOutput, 1);
        // If the string still contains tags symbols.
        if (strpos($string, '<') !== false && strpos($string, '>') !== false) {
            $output = io_safe_html_tags($output);
        }
    } else {
        $output = $string;
    }

    return $output;
}


/**
 * Execute io_safe_input againt each values in JSON.
 *
 * @param string json
 *
 * @return string json where each value is encoded
 */
function io_safe_input_json($json)
{
    $output_json = '';

    if (empty($json)) {
        return $output_json;
    }

    $array_json = json_decode($json, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        return $output_json;
    }

    foreach ($array_json as $key => $value) {
        if (is_array($value)) {
            $value_json = json_encode($value, JSON_UNESCAPED_UNICODE);
            $array_json[$key] = json_decode(io_safe_input_json($value_json), true);
        } else {
            $array_json[$key] = io_safe_input($value);
        }
    }

    $output_json = json_encode($array_json, JSON_UNESCAPED_UNICODE);

    return $output_json;
}


/**
 * Merge json value in $json_merge to $json
 *
 * @param string  json to be merged.
 * @param string  json containing the values to merge.
 * @param boolean limit the values to be merged to those with a key of 'value', true by default.
 *
 * @retrun string merged json
 *
 * e.g.)
 *   arg1 json: {"1":{"macro":"_field1_","desc":"DESCRIPTION","help":"HELP","value":"","hide":""}}
 *   arg2 json: {"1":{"value":"xxxx"}}
 *   -> return json: {"1":{"macro":"_field1_","desc":"DESCRIPTION","help":"HELP","value":"xxxx","hide":""}}
 */
function io_merge_json_value($json, $json_merge, $value_key_only=true)
{
    $output_json = '';

    $array_json = json_decode($json, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        return $output_json;
    }

    $array_json_merge = json_decode($json_merge, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        return $output_json;
    }

    foreach ($array_json_merge as $key => $value) {
        if (is_array($value) && !empty($array_json[$key])) {
            $merged_json = io_merge_json_value(
                json_encode($array_json[$key], JSON_UNESCAPED_UNICODE),
                json_encode($value, JSON_UNESCAPED_UNICODE),
                $value_key_only
            );
            $array_json[$key] = json_decode($merged_json, true);
        } else {
            if (array_key_exists($key, $array_json)
                && ($value_key_only == false || $key == 'value')
            ) {
                $array_json[$key] = $array_json_merge[$key];
            }
        }
    }

    $output_json = json_encode($array_json, JSON_UNESCAPED_UNICODE);

    return $output_json;
}
