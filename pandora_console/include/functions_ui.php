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
 * @subpackage UI
 */

// Check to avoid error when load this library in error screen situations
if (isset($config['homedir'])) {
	require_once($config['homedir'] . '/include/functions_agents.php');
	require_once($config['homedir'] . '/include/functions_modules.php');
	require_once($config['homedir'] . '/include/functions.php');
	require_once($config['homedir'] . '/include/functions_groups.php');
	require_once($config['homedir'] . '/include/functions_users.php');
	require_once($config['homedir'] . '/include/functions_html.php');
}

function ui_bbcode_to_html($text, $allowed_tags = array('[url]')) {
	$return = $text;
	
	if (array_search('[url]', $allowed_tags) !== false) {
		$return = preg_replace("/\[url=([^\]]*)\]/",
			"<a target=\"_blank\" rel=\"noopener noreferrer\" href=\"$1\">", $return);
		$return = str_replace('[/url]', '</a>', $return);
	}
	
	return $return;
}

/**
 * Truncate a text to num chars (pass as parameter) and if flag show tooltip is
 * true the html artifal to show the tooltip with rest of text.
 * 
 * @param string $text The text to truncate.
 * @param mixed $numChars Number chars (-3 for char "[...]") max the text. Or the strings "agent_small", "agent_medium", "module_small", "module_medium", "description" or "generic" for to take the values from user config.
 * @param boolean $showTextInAToopTip Flag to show the tooltip.
 * @param boolean $return Flag to return as string or not.
 * @param boolean $showTextInTitle Flag to show the text on title.
 * @param string $suffix String at the end of a strimmed string.
 * @param string $style Style associated to the text.
 *
 * @return string Truncated text.
 */
function ui_print_truncate_text($text, $numChars = GENERIC_SIZE_TEXT, $showTextInAToopTip = true, $return = true, $showTextInTitle = true, $suffix = '&hellip;', $style = false) {
	global $config;
	
	if (is_string($numChars)) {
		switch ($numChars) {
			case 'agent_small':
				$numChars = $config['agent_size_text_small'];
				break;
			case 'agent_medium':
				$numChars = $config['agent_size_text_medium'];
				break;
			case 'module_small':
				$numChars = $config['module_size_text_small'];
				break;
			case 'module_medium':
				$numChars = $config['module_size_text_medium'];
				break;
			case 'description':
				$numChars = $config['description_size_text'];
				break;
			case 'item_title':
				$numChars = $config['item_title_size_text'];
				break;
			default:
				$numChars = (int)$numChars;
				break;
		}
	}
	
	
	if ($numChars == 0) {
		if ($return == true) {
			return $text;
		}
		else {
			echo $text;
		}
	}
	
	$text_html_decoded = io_safe_output($text);
	$text_has_entities = $text != $text_html_decoded;
	
	if (mb_strlen($text_html_decoded, "UTF-8") > ($numChars)) {
		// '/2' because [...] is in the middle of the word.
		$half_length = intval(($numChars - 3) / 2);
		
		// Depending on the strange behavior of mb_strimwidth() itself,
		// the 3rd parameter is not to be $numChars but the length of
		// original text (just means 'large enough').
		$truncateText2 = mb_strimwidth($text_html_decoded,
			(mb_strlen($text_html_decoded, "UTF-8") - $half_length),
			mb_strlen($text_html_decoded, "UTF-8"), "", "UTF-8" );
		
		$truncateText = mb_strimwidth($text_html_decoded, 0,
			($numChars - $half_length), "", "UTF-8");
		
		// Recover the html entities to avoid XSS attacks
		$truncateText = ($text_has_entities)
			? io_safe_input($truncateText) . $suffix . io_safe_input($truncateText2)
			: $truncateText . $suffix . $truncateText2;
		
		if ($showTextInTitle) {
			if ($style === null) {
				$truncateText = $truncateText;
			}
			else if ($style !== false) {
				$truncateText = '<span style="' . $style . '" title="' . $text . '">' .
					$truncateText . '</span>';
			}
			else {
				$truncateText = '<span title="' . $text . '">' . $truncateText . '</span>';
			}
		}
		if ($showTextInAToopTip) {
			$truncateText = $truncateText . ui_print_help_tip(htmlspecialchars($text), true);
		}
		else {
			if ($style !== false) {
				$truncateText = '<span style="' . $style . '">' . $truncateText . '</span>';
			}
		}
	}
	else {
		if ($style !== false) {
			$truncateText = '<span style="' . $style . '">' . $text . '</span>';
		}
		else {
			$truncateText = $text;
		}
	}
	
	if ($return == true) {
		return $truncateText;
	}
	else {
		echo $truncateText;
	}
}

/**
 * Print a string with a smaller font depending on its size.
 * 
 * @param string $string String to be display with a smaller font.
 * @param boolean $return Flag to return as string or not.
 */
function printSmallFont ($string, $return = true) {
	$str = io_safe_output($string);
	$length = strlen($str);
	if ($length >= 30) {
		$size = 0.7;
	}
	elseif ($length >= 20) {
		$size = 0.8;
	}
	elseif ($length >= 10) {
		$size = 0.9;
	}
	elseif ($length < 10) {
		$size = 1;
	}
	
	$s = '<span style="font-size: '.$size.'em;">';
	$s .= $string;
	$s .= '</span>';
	if ($return) {
		return $s;
	}
	else {
		echo $s;
	}
}

/**
 * Prints a generic message between tags.
 * 
 * @param mixed The string message or array ('title', 'message', 'icon', 'no_close', 'force_style')  to be displayed
 * @param string the class to user
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_message ($message, $class = '', $attributes = '', $return = false, $tag = 'h3') {
	static $first_execution = true;
	
	$text_title = '';
	$text_message = '';
	$icon_image = '';
	$no_close_bool = false;
	$force_style = '';
	if (is_array($message)) {
		if (!empty($message['title']))
			$text_title = $message['title'];
		if (!empty($message['message']))
			$text_message = $message['message'];
		if (!empty($message['icon']))
			$icon_image = $message['icon'];
		if (!empty($message['no_close']))
			$no_close_bool = $message['no_close'];
		if (!empty($message['force_style']))
			$force_style = $message['force_style'];
	}
	else {
		$text_message = $message;
	}
	
	if (empty($text_title)) {
		switch ($class) {
			default:
			case 'info':
				$text_title = __('Information');
				break;
			case 'error':
				$text_title = __('Error');
				break;
			case 'suc':
				$text_title = __('Success');
				break;
			case 'warning':
				$text_title = __('Warning');
				break;
		}
	}
	
	if (empty($icon_image)) {
		switch ($class) {
			default:
			case 'info':
				$icon_image = 'images/information_big.png';
				break;
			case 'error':
				$icon_image = 'images/err.png';
				break;
			case 'suc':
				$icon_image = 'images/suc.png';
				break;
			case 'warning':
				$icon_image = 'images/warning_big.png';
				break;
		}
		
		$icon_image = $icon_image;
	}
	
	$id = 'info_box_' . uniqid();

	//Use the no_meta parameter because this image is only in the base console
	$output = '<table cellspacing="0" cellpadding="0" id="' . $id . '" ' . $attributes . '
		class="info_box ' . $id . ' ' . $class . ' textodialogo" style="' . $force_style . '">
		<tr>
			<td class="icon" rowspan="2" style="padding-right: 10px; padding-top: 3px;">' . html_print_image($icon_image, true, false, false, false, true) . '</td>
			<td class="title" style="text-transform: uppercase; padding-top: 10px;"><b>' . $text_title . '</b></td>
			<td class="icon" style="text-align: right; padding-right: 3px;">';
	if (!$no_close_bool) {
		//Use the no_meta parameter because this image is only in the base console
		$output .= '<a href="javascript: close_info_box(\'' . $id . '\')">' .
			html_print_image('images/blade.png', true, false, false, false, true) . '</a>';
	}
	
	$output .= 	'</td>
		</tr>
		<tr>
			<td style="color:#333;padding-top:10px">' . $text_message . '</td>
			<td></td>
		</tr>
		</table>';
	
	if (($first_execution) && (!$no_close_bool)) {
		$first_execution = false;
		
		$output .= '
			<script type="text/javascript">
				function close_info_box(id) {
					$("." + id).hide();
				}
			</script>
		';
	}
	
	if ($return)
		return $output;
	else
		echo $output;
	
	return '';
}

/**
 * Prints an error message.
 * 
 * @param mixed The string error message or array ('title', 'message', 'icon', 'no_close') to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_error_message ($message, $attributes = '', $return = false, $tag = 'h3') {
	return ui_print_message ($message, 'error', $attributes, $return, $tag);
}

/**
 * Prints an operation success message.
 * 
 * @param mixed The string message or array ('title', 'message', 'icon', 'no_close') to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_success_message ($message, $attributes = '', $return = false, $tag = 'h3') {
	return ui_print_message ($message, 'suc', $attributes, $return, $tag);
}

/**
 * Prints an operation info message.
 * 
 * @param mixed The string message or array ('title', 'message', 'icon', 'no_close') to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_info_message ($message, $attributes = '', $return = false, $tag = 'h3') {
	return ui_print_message ($message, 'info', $attributes, $return, $tag);
}

function ui_print_empty_data($message, $attributes = '', $return = false, $tag = 'h3') {
	return ui_print_message ($message, 'info', $attributes, $return, $tag);
}

/**
 * Evaluates a result using empty() and then prints an error or success message
 * 
 * @param mixed The results to evaluate. 0, NULL, false, '' or
 * array() is bad, the rest is good
 * @param mixed The string or array ('title', 'message') to be displayed if the result was good
 * @param mixed The string or array ('title', 'message') to be displayed if the result was bad
 * @param string Any other attributes to be set for the h3
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_result_message ($result, $good = '', $bad = '', $attributes = '', $return = false, $tag = 'h3') {
	if ($good == '' || $good === false)
		$good = __('Request successfully processed');
	
	if ($bad == '' || $bad === false)
		$bad = __('Error processing request');
	
	if (empty ($result)) {
		return ui_print_error_message ($bad, $attributes, $return, $tag);
	}
	else {
		return ui_print_success_message ($good, $attributes, $return, $tag);
	}
}

/**
 * Prints an warning message.
 * 
 * @param mixed The string warning message or array ('title', 'message', 'icon', 'no_close') to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_warning_message ($message, $attributes = '', $return = false, $tag = 'h3') {
	return ui_print_message ($message, 'warning', $attributes, $return, $tag);
}

/**
 * Evaluates a unix timestamp and returns a span (or whatever tag specified)
 * with as title the correctly formatted full timestamp and a time comparation
 * in the tag
 *
 * @param int Any type of timestamp really, but we prefer unixtime
 * @param bool Whether to output the string or return it
 * @param array An array with different options for this function
 *	Key html_attr: which html attributes to add (defaults to none)
 *	Key tag: Which html tag to use (defaults to span)
 *	Key prominent: Overrides user preference and display "comparation" or "timestamp"
 *	key units: The type of units.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_timestamp ($unixtime, $return = false, $option = array ()) {
	global $config;
	
	//TODO: Add/use a javascript timer for the seconds so it automatically updates as time passes by
	
	if (isset ($option["html_attr"])) {
		$attributes = $option["html_attr"];
	}
	else {
		$attributes = "";
	}
	
	if (isset ($option["tag"])) {
		$tag = $option["tag"];
	}
	else {
		$tag = "span";
	}
	
	if (empty ($option["style"])) {
		$style = 'style="white-space:nowrap;"';
	}
	else {
		$style = 'style="' . $option["style"] . '"';
	}
	
	if (!empty ($option["prominent"])) {
		$prominent = $option["prominent"];
	}
	else {
		$prominent = $config["prominent_time"];
	}
	
	if (!is_numeric ($unixtime)) {
		$unixtime = time_w_fixed_tz($unixtime);
	}
	
	//prominent_time is either timestamp or comparation
	if ($unixtime <= 0) {
		$title = __('Unknown') . '/' . __('Never');
		$data = __('Unknown');
	}
	elseif ($prominent == "timestamp") {
		pandora_setlocale();
		
		$title = human_time_comparation ($unixtime);
		$data = strftime(date2strftime_format($config["date_format"]),
			$unixtime);
	}
	else {
		$title = date ($config["date_format"], $unixtime);
		$units = 'large';
		if (isset($option['units'])) {
			$units = $option['units'];
		}
		$data = human_time_comparation ($unixtime, $units);
	}
	
	$output = '<'.$tag;
	switch ($tag) {
		default:
			//Usually tags have title attributes, so by default we add,
			//then fall through to add attributes and data
			$output .= ' title="'.$title.'" '.$style.'>'.$data.'</'.$tag.'>';
			break;
		case "h1":
		case "h2":
		case "h3":
			//Above tags don't have title attributes
			$output .= ' '.$attributes.' '.$style.'>'.$data.'</'.$tag.'>';
			break;
	}
	
	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Prints a username with real name, link to the user_edit page etc.
 *
 * @param string The username to render
 * @param bool Whether to return or print
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_username ($username, $return = false) {
	$string = '<a href="index.php?sec=usuario&amp;sec2=operation/users/user_edit&amp;id='.$username.'">'.get_user_fullname ($username).'</a>';
	
	if ($return)
		return $string;
	
	echo $string;
}

function ui_print_tags_warning ($return = false) {
	$msg = '<div id="notify_conf" class="notify">';
	$msg .= __("Is possible that this view uses part of information which your user has not access");
	$msg .= '</div>';
	
	if ($return) {
		return $msg;
	}
	else {
		echo $msg;
	}
}

/**
 * Print group icon within a link
 * 
 * @param int Group id
 * @param bool Whether to return or print
 * @param string What path to use (relative to images/). Defaults to groups_small
 * @param string Style for group image
 * @param bool Whether the group have link or not
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_group_icon ($id_group, $return = false, $path = "groups_small", $style='', $link = true, $force_show_image = false, $show_as_image = false) {
	global $config;
	
	if ($id_group > 0)
		$icon = (string) db_get_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	else
		$icon = "world";
	
	
	$output = '';
	
	// Don't show link in metaconsole
	if (defined('METACONSOLE'))
		$link = false;
	
	if ($link)
		$output = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id_group.'">';
	
	if ($config['show_group_name']) {
		$output .= '<span title="'. groups_get_name($id_group, true) .'">' .
			groups_get_name($id_group, true) . '&nbsp;</span>';
	}
	else {
		if (empty ($icon))
			$output .= '<span title="'. groups_get_name($id_group, true).'">&nbsp;&nbsp;</span>';
		else {
			$output .= html_print_image("images/" . $path . "/" . $icon . ".png",
				true, array("style" => $style, "class" => "bot", "alt" => groups_get_name($id_group, true), "title" => groups_get_name ($id_group, true)), false, false, false, true);
		}
	}
	
	if ($link)
		$output .= '</a>';
	
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Print group icon within a link. Other version.
 * 
 * @param int Group id
 * @param bool Whether to return or print
 * @param string What path to use (relative to images/). Defaults to groups_small
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_group_icon_path ($id_group, $return = false, $path = "images/groups_small", $style='', $link = true) {
	if ($id_group > 0)
		$icon = (string) db_get_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	else
		$icon = "world";
	
	if ($style == '')
		$style = 'width: 16px; height: 16px;';
	
	$output = '';
	if ($link)
		$output = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id_group.'">';
	
	if (empty ($icon))
		$output .= '<span title="'. groups_get_name($id_group, true).'">&nbsp;-&nbsp</span>';
	else
		$output .= '<img style="' . $style . '" class="bot" src="'.$path.'/'.$icon.'.png" alt="'.groups_get_name ($id_group, true).'" title="'.groups_get_name ($id_group, true).'" />';
	
	if ($link)
		$output .= '</a>';
	
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Get the icon of an operating system.
 *
 * @param int Operating system id
 * @param bool Whether to also append the name of the OS after the icon
 * @param bool Whether to return or echo the result
 * @param bool Whether to apply skin or not
 * 
 * @return string HTML with icon of the OS
 */
function ui_print_os_icon ($id_os, $name = true, $return = false,
	$apply_skin = true, $networkmap = false, $only_src = false,
	$relative = false, $options = false, $big_icons = false) {
	
	$subfolder = 'os_icons';
	if ($networkmap) {
		$subfolder = 'networkmap';
	}
	if ($big_icons)
		$subfolder .= '/so_big_icons';
	
	$icon = (string) db_get_value ('icon_name', 'tconfig_os', 'id_os', (int) $id_os);
	$os_name = get_os_name ($id_os);
	if (empty ($icon)) {
		if ($only_src) {
			$output = html_print_image("images/" . $subfolder . "/unknown.png",
				true, $options, true, $relative, false, true);
		}
		else {
			return "-";
		}
	}
	else if ($apply_skin) {
		if ($only_src) {
			$output = html_print_image("images/" . $subfolder . "/" . $icon, true, $options, true, $relative, false, true);
		}
		else {
			if (!isset($options['title'])) {
				$options['title'] = $os_name;
			}
			$output = html_print_image("images/" . $subfolder . "/" . $icon, true, $options, false, $relative, false, true);
		}
	}
	else
		//$output = "<img src='images/os_icons/" . $icon . "' alt='" . $os_name . "' title='" . $os_name . "'>";
		$output = "images/" . $subfolder . "/" . $icon;
	
	if ($name === true) {
		$output .= '&nbsp;&nbsp;' . $os_name;
	}
	
	if (!$return)
		echo $output;
	
	return $output;
}

function ui_print_type_agent_icon ( $id_os = false, $remote_contact = false, $contact = false, 
						$return = false, $remote = 0, $version = ""){

	if($id_os == 19){
		//Satellite
		$options['title'] = __('Satellite');
		$output = html_print_image("images/op_satellite.png", true, $options, false, false, false, true);
	}
	else if ($remote_contact == $contact && $remote == 0 && $version == ""){
		//Network
		$options['title'] = __('Network');
		$output = html_print_image("images/network.png", true, $options, false, false, false, true);
	}
	else{
		//Software
		$options['title'] = __('Software');
		$output = html_print_image("images/data.png", true, $options, false, false, false, true);
	}

	return $output;
}

/**
 * Prints an agent name with the correct link
 * 
 * @param int Agent id
 * @param bool Whether to return the string or echo it too
 * @param int Now uses styles to accomplish this
 * @param string Style of name in css.
 * @param string server url to concatenate at the begin of the link
 * @param string extra parameters to concatenate in the link
 * @param string name of the agent to avoid the query in some cases
 * @param bool if the agent will provided with link or not
 * @param bool use the agent alias or the name
 * 
 * @return string HTML with agent name and link
 */
function ui_print_agent_name ($id_agent, $return = false, $cutoff = 'agent_medium', $style = '', $cutname = false, $server_url = '', $extra_params = '', $known_agent_name = false, $link = true, $alias = true) {
	if ($known_agent_name === false) {
		if ($alias) {
			$agent_name = (string) agents_get_alias($id_agent);
		}
		else {
			$agent_name = (string) agents_get_name($id_agent);
		}
	}
	else {
		$agent_name = $known_agent_name;
	}
	
	if ($alias) {
		$agent_name_full = (string) agents_get_name($id_agent);
	}
	else {
		$agent_name_full = $agent_name;
	}
	
	if ($cutname) {
		$agent_name = ui_print_truncate_text($agent_name, $cutoff, true, true, true, '[&hellip;]', $style);
	}
	
	if ($link) {
		$url = $server_url . 'index.php?sec=estado&amp;'.
			'sec2=operation/agentes/ver_agente&amp;' .
			'id_agente=' . $id_agent.$extra_params;
		
		$output = '<a style="' . $style . '"' .
			' href="' . $url . '"' .
			' title="'.$agent_name_full.'"><b><span style="'.$style.'">'.$agent_name.'</span></b></a>';
	}
	else {
		$output = '<b><span style="'.$style.'">'.$agent_name.'</span></b>';
	}
	
	//TODO: Add a pretty javascript (using jQuery) popup-box with agent details
	
	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Formats a row from the alert table and returns an array usable in the table function
 * 
 * @param array A valid (non empty) row from the alert table
 * @param bool Whether or not this is a combined alert
 * @param bool Whether to print the agent information with the module information
 * @param string Tab where the function was called from (used for urls)
 * @param mixed Style for agent name or default (false)
 * 
 * @return array A formatted array with proper html for use in $table->data (6 columns)
 */
function ui_format_alert_row ($alert, $agent = true, $url = '', $agent_style = false) {
	global $config;
	
	if (!isset($alert['server_data'])) {
		$server_name = '';
		$server_id = '';
		$url_hash = '';
		$console_url = '';
	}
	else {
		$server_data = $alert['server_data'];
		$server_name = $server_data['server_name'];
		$server_id = $server_data['id'];
		$console_url = $server_data['server_url'] . '/';
		$url_hash = metaconsole_get_servers_url_hash($server_data);
	}
	
	$actionText = "";
	require_once ($config['homedir'] . "/include/functions_alerts.php");
	$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');
	$id_group = (int) get_parameter ("ag_group", 0); //0 is the All group (selects all groups)
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		if ($agent) {
			$index = array('policy' => 0, 'standby' => 1, 'force_execution' => 2, 'agent_name' => 3, 'module_name' => 4,
				'description' => 5, 'template' => 5, 'action' => 6, 'last_fired' => 7, 'status' => 8,
				'validate' => 9);
		}
		else {
			$index = array('policy' => 0, 'standby' => 1, 'force_execution' => 2, 'agent_name' => 3, 'module_name' => 3,
				'description' => 4, 'template' => 4, 'action' => 5, 'last_fired' => 6, 'status' => 7,
				'validate' => 8);
		}
	}
	else {
		if ($agent) {
			$index = array('standby' => 0, 'force_execution' => 1, 'agent_name' => 2, 'module_name' => 3,
				'description' => 4, 'template' => 4, 'action' => 5, 'last_fired' => 6, 'status' => 7,
				'validate' => 8);
		}
		else {
			$index = array('standby' => 0, 'force_execution' => 1, 'agent_name' => 2, 'module_name' => 2,
				'description' => 3, 'template' => 3, 'action' => 4, 'last_fired' => 5, 'status' => 6,
				'validate' => 7);
		}
	}
	
	if ($alert['disabled']) {
		$disabledHtmlStart = '<span style="font-style: italic; color: #aaaaaa;">';
		$disabledHtmlEnd = '</span>';
		$styleDisabled = "font-style: italic; color: #aaaaaa;";
	}
	else {
		$disabledHtmlStart = '';
		$disabledHtmlEnd = '';
		$styleDisabled = "";
	}
	
	if (empty ($alert))
	{
		if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK)
			return array ("", "", "", "", "", "", "", "");
		else
			return array ("", "", "", "", "", "", "");
	}


	if (defined('METACONSOLE')) {

		$server = db_get_row ('tmetaconsole_setup', 'id', $alert['server_data']['id']);

		if (metaconsole_connect($server) == NOERR) {

			// Get agent data from node
			$agente = db_get_row ('tagente', 'id_agente', $alert['id_agent']);

			metaconsole_restore_db ();
		}

	} else {
		// Get agent id
		$id_agent = modules_get_agentmodule_agent ($alert['id_agent_module']);
		$agente = db_get_row ('tagente', 'id_agente', $id_agent);
	}

	$template = alerts_get_alert_template ($alert['id_alert_template']);
	$description = io_safe_output($template['name']);
	
	$data = array ();

	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		if(is_metaconsole()){
			$node = metaconsole_get_connection_by_id($alert['server_data']['id']);
			if (metaconsole_load_external_db($node) !== NOERR) {
				// Restore the default connection.
				metaconsole_restore_db();
				$errors++;
				break;
			}
		}

		$policyInfo = policies_is_alert_in_policy2($alert['id'], false);
		if ($policyInfo === false)
			$data[$index['policy']] = '';
		else {
			$img = 'images/policies.png';
			if(!is_metaconsole()){
				$data[$index['policy']] = '<a href="?sec=gmodules&amp;sec2=enterprise/godmode/policies/policies&amp;id=' . $policyInfo['id'] . '">' .
					html_print_image($img,true, array('title' => $policyInfo['name'])) .
					'</a>';
			}else{
				$data[$index['policy']] = '<a href="?sec=gmodules&amp;sec2=advanced/policymanager&amp;id=' . $policyInfo['id'] . '">' .
					html_print_image($img,true, array('title' => $policyInfo['name'])) .
					'</a>';
			}
		}

		if(is_metaconsole()){
			metaconsole_restore_db();
		}
	}

	// Standby
	$data[$index['standby']] = '';
	if (isset ($alert["standby"]) && $alert["standby"] == 1) {
		$data[$index['standby']] = html_print_image ('images/bell_pause.png', true, array('title' => __('Standby on')));
	}
	
	if (!defined('METACONSOLE')) {
		// Force alert execution
		if (check_acl ($config["id_user"], $id_group, "AW") || check_acl ($config["id_user"], $id_group, "LM")) {
			if ($alert["force_execution"] == 0) {
				$data[$index['force_execution']] =
					'<a href="'.$url.'&amp;id_alert='.$alert["id"].'&amp;force_execution=1&refr=60">' . html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'))) . '</a>';
			}
			else {
				$data[$index['force_execution']] =
					'<a href="'.$url.'&amp;id_alert='.$alert["id"].'&amp;refr=60">' . html_print_image("images/refresh.png", true) . '</a>';
			}
		}
	}

	$data[$index['agent_name']] = $disabledHtmlStart;
	if ($agent == 0) {
		$data[$index['module_name']] .=
			ui_print_truncate_text(isset($alert['agent_module_name']) ? $alert['agent_module_name'] : modules_get_agentmodule_name ($alert["id_agent_module"]), 'module_small', false, true, true, '[&hellip;]', 'font-size: 7.2pt');
	}
	else {
		if (defined('METACONSOLE')) {
			$agent_name = $alert['agent_name'];
			$id_agent = $alert['id_agent'];
		}
		else {
			$agent_name = false;
			$id_agent = modules_get_agentmodule_agent ($alert["id_agent_module"]);
		}

		if (defined('METACONSOLE') || !can_user_access_node ()) {
			$data[$index['agent_name']] = ui_print_truncate_text($agent_name, 'agent_small', false, true, false, '[&hellip;]', 'font-size:7.5pt;');
		}
		else {
			if ($agent_style !== false) {
				$data[$index['agent_name']] .='<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agent.'"> <span style="font-size: 7pt;font-weight:bold" title ="' . $agente['nombre']. '">'.$agente["alias"].'</span></a>';
			}
			else {
				$data[$index['agent_name']] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agent.'"> <span style="font-size: 7pt;font-weight:bold" title ="' . $agente['nombre']. '">'.$agente["alias"].'</span></a>';
			}
		}

		$data[$index['module_name']] =
			ui_print_truncate_text (isset($alert['agent_module_name']) ? $alert['agent_module_name'] : modules_get_agentmodule_name ($alert["id_agent_module"]), 'module_small', false, true, true, '[&hellip;]', 'font-size: 7.2pt');
	}
	
	$data[$index['agent_name']] .= $disabledHtmlEnd;
	
	$data[$index['description']] = '';
	
	if (defined('METACONSOLE')) {
		$data[$index['template']] .= '<a class="template_details" href="' . ui_get_full_url('/', false, false, false) . '/ajax.php?page=enterprise/meta/include/ajax/tree_view.ajax&action=get_template_tooltip&id_template=' . $template['id'] . '&server_name=' . $alert['server_data']['server_name'] . '">';
	}
	else {
		$data[$index['template']] .= '<a class="template_details" href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template=' . $template['id'] . '">';
	}
	$data[$index['template']] .= html_print_image ('images/zoom.png', true);
	$data[$index['template']] .= '</a> ';
	$actionDefault = db_get_value_sql("SELECT id_alert_action
		FROM talert_templates WHERE id = " . $alert['id_alert_template']);
	
	$data[$index['description']] .= $disabledHtmlStart .
		ui_print_truncate_text (io_safe_output($description), 'description', false, true, true, '[&hellip;]', 'font-size: 7.1pt') .
		$disabledHtmlEnd;
	
	$actions = alerts_get_alert_agent_module_actions ($alert['id'], false, $alert['server_data']['id']);
	
	if (!empty($actions)) {
		$actionText = '<div><ul class="action_list">';
		foreach ($actions as $action) {
			$actionText .= '<div style="margin-bottom: 5px;" ><span class="action_name"><li>' . $action['name'];
			if ($action["fires_min"] != $action["fires_max"]) {
				$actionText .=  " (".$action["fires_min"] . " / ". $action["fires_max"] . ")";
			}
			$actionText .= '</li></span></div>';
		}
		$actionText .= '</ul></div>';
	}
	else {
		if ($actionDefault != "")
		$actionText = db_get_sql ("SELECT name FROM talert_actions WHERE id = $actionDefault"). " <i>(".__("Default") . ")</i>";
	}
	
	$data[$index['action']] = $actionText;
	
	$data[$index['last_fired']] = $disabledHtmlStart . ui_print_timestamp ($alert["last_fired"], true) . $disabledHtmlEnd;
	
	
	$status = STATUS_ALERT_NOT_FIRED;
	$title = "";
	
	if ($alert["times_fired"] > 0) {
		$status = STATUS_ALERT_FIRED;
		$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
	}
	elseif ($alert["disabled"] > 0) {
		$status = STATUS_ALERT_DISABLED;
		$title = __('Alert disabled');
	}
	else {
		$status = STATUS_ALERT_NOT_FIRED;
		$title = __('Alert not fired');
	}
	
	$data[$index['status']] = ui_print_status_image($status, $title, true);
	
	if (!defined('METACONSOLE')) {
		if (check_acl ($config["id_user"], $id_group, "LW") || check_acl ($config["id_user"], $id_group, "LM")) {
			$data[$index['validate']] = '';

			$data[$index['validate']] .= html_print_checkbox (
				"validate[]",
				$alert["id"],
				false,
				true,
				false,
				'',
				true);
		}
	}
	
	return $data;
}

/**
 * Prints a substracted string, length specified by cutoff, the full string will be in a rollover.
 *
 * @param string The string to be cut
 * @param int At how much characters to cut
 * @param bool Whether to return or print it out
 * @param int Size font (fixed) in px, applyed as CSS style (optional)
 *
 * @return An HTML string
 */
function ui_print_string_substr ($string, $cutoff = 16, $return = false, $fontsize = 0) {
	if (empty ($string)) {
		return "";
	}
	
	$string2 = io_safe_output ($string);
	if (mb_strlen($string2, "UTF-8") >  $cutoff) {
		$string3 = "...";
	}
	else {
		$string3 = "";
	}
	
	$font_size_mod = "";
	
	if ($fontsize > 0) {
		$font_size_mod = "style='font-size: ".$fontsize."pt'";
	}
	$string = '<span '.$font_size_mod.' title="'.io_safe_input($string2).'">'.mb_substr ($string2, 0, $cutoff, "UTF-8").$string3.'</span>';
	
	if ($return === false) {
		echo $string;
	}
	
	return $string;
}

/**
 * Gets a helper text explaining the requirement needs for an alert template
 * to get it fired.
 *
 * @param int Alert template id.
 * @param bool Wheter to return or print it out.
 * @param bool Wheter to put the values in the string or not.
 *
 * @return An HTML string if return was true.
 */
function ui_print_alert_template_example ($id_alert_template, $return = false, $print_values = true) {
	$output = '';
	
	$output .= html_print_image("images/information.png", true);
	$output .= '<span id="example">';
	$template = alerts_get_alert_template ($id_alert_template);
	
	switch ($template['type']) {
		case 'equal':
			/* Do not translate the HTML attributes */
			$output .= __('The alert would fire when the value is <span id="value"></span>');
			break;
		case 'not_equal':
			/* Do not translate the HTML attributes */
			$output .= __('The alert would fire when the value is not <span id="value"></span>');
			break;
		case 'regex':
			if ($template['matches_value'])
				/* Do not translate the HTML attributes */
				$output .= __('The alert would fire when the value matches <span id="value"></span>');
			else
				/* Do not translate the HTML attributes */
				$output .= __('The alert would fire when the value doesn\'t match <span id="value"></span>');
			$value = $template['value'];
			break;
		case 'max_min':
			if ($template['matches_value'])
				/* Do not translate the HTML attributes */
				$output .= __('The alert would fire when the value is between <span id="min"></span> and <span id="max"></span>');
			else
				/* Do not translate the HTML attributes */
				$output .= __('The alert would fire when the value is not between <span id="min"></span> and <span id="max"></span>');
			break;
		case 'max':
			/* Do not translate the HTML attributes */
			$output .= __('The alert would fire when the value is over <span id="max"></span>');
			
			break;
		case 'min':
			/* Do not translate the HTML attributes */
			$output .= __('The alert would fire when the value is under <span id="min"></span>');
			
			break;
		case 'warning':
			/* Do not translate the HTML attributes */
			$output .= __('The alert would fire when the module is in warning status');
			
			break;
		case 'critical':
			/* Do not translate the HTML attributes */
			$output .= __('The alert would fire when the module is in critical status');
			break;
	}
	
	if ($print_values) {
		/* Replace span elements with real values. This is done in such way to avoid
		 duplicating strings and make it easily modificable via Javascript. */
		$output = str_replace ('<span id="value"></span>', $template['value'], $output);
		$output = str_replace ('<span id="max"></span>', $template['max_value'], $output);
		$output = str_replace ('<span id="min"></span>', $template['min_value'], $output);
	}
	$output .= '</span>';
	if ($return)
		return $output;
	echo $output;
}

/**
 * Prints a help tip icon.
 * 
 * @param string Id of the help article
 * @param bool Whether to return or output the result
 * @param string Home url if its necessary
 * @param string Image path
 * @param bool Route is relative or not
 * 
 * @return string The help tip
 */
function ui_print_help_icon ($help_id, $return = false, $home_url = '', $image = "images/help.png", $is_relative = false) {
	global $config;

	// Do not display the help icon if help is disabled
	if ($config['disable_help']) return '';

	if (empty($home_url))
		$home_url = "";
	
	if (defined('METACONSOLE')) {
		$home_url = "../../" . $home_url;
	}
	
	$output = html_print_image (
		$image,
		true,
		array ("class" => "img_help",
			"title" => __('Help'),
			"onclick" => "open_help ('" . $help_id . "','" . $home_url . "','" . $config['id_user'] . "')"
		),
		false,
		$is_relative && is_metaconsole()
	);
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Add a CSS file to the HTML head tag.
 *
 * To make a CSS file available just put it in include/styles. The
 * file name should be like "name.css". The "name" would be the value
 * needed to pass to this function.
 * 
 * @param string Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
 * <code>
 * ui_require_css_file ('pandora');
 * // Would include include/styles/pandora.js
 * </code>
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function ui_require_css_file ($name, $path = 'include/styles/') {
	global $config;
	
	$filename = $path . $name . '.css';
	
	if (! isset ($config['css']))
		$config['css'] = array ();
	
	if (isset ($config['css'][$name]))
		return true;
	
	if (! file_exists ($filename) &&
		! file_exists ($config['homedir'] . '/' . $filename))
		return false;
	
	$config['css'][$name] = $filename;
	
	return true;
}

/**
 * Add a javascript file to the HTML head tag.
 *
 * To make a javascript file available just put it in include/javascript. The
 * file name should be like "name.js". The "name" would be the value
 * needed to pass to this function.
 * 
 * @param string Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
 * <code>
 * ui_require_javascript_file ('pandora');
 * // Would include include/javascript/pandora.js
 * </code>
 * @param bool Just echo the script tag of the file.
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function ui_require_javascript_file ($name, $path = 'include/javascript/', $echo_tag = false) {
	global $config;
	
	$filename = $path . $name . '.js';
	
	if ($echo_tag) {
		echo '<script type="text/javascript" src="' .
			ui_get_full_url(false, false, false, false) .
			$filename . '"></script>';
		return;
	}
	
	if (! isset ($config['js']))
		$config['js'] = array ();
	
	if (isset ($config['js'][$name]))
		return true;
	
	/* We checks two paths because it may fails on enterprise */
	if (! file_exists ($filename) && ! file_exists ($config['homedir'] . '/' . $filename))
		return false;
	
	if (defined('METACONSOLE')) {
		$config['js'][$name] = "../../" . $filename;
	}
	else {
		$config['js'][$name] = $filename;
	}
	
	return true;
}

/**
 * Add a enteprise javascript file to the HTML head tag.
 *
 * To make a javascript file available just put it in <ENTERPRISE_DIR>/include/javascript. The
 * file name should be like "name.js". The "name" would be the value
 * needed to pass to this function.
 * 
 * @param string Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
 * <code>
 * ui_require_javascript_file ('pandora');
 * // Would include include/javascript/pandora.js
 * </code>
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function ui_require_javascript_file_enterprise($name, $disabled_metaconsole = false) {
	global $config;
	
	$metaconsole_hack = '';
	if ($disabled_metaconsole) {
		$metaconsole_hack = '../../';
	}
	
	$filename = $metaconsole_hack . ENTERPRISE_DIR . '/include/javascript/' .$name.'.js';
	
	if (! isset ($config['js']))
		$config['js'] = array ();
	
	if (isset ($config['js'][$name]))
		return true;
	
	/* We checks two paths because it may fails on enterprise */
	if (!file_exists ($filename) &&
		!file_exists ($config['homedir'] . '/' . $filename)) {
		
		return false;
	}
	
	$config['js'][$name] = $filename;
	
	return true;
}

/**
 * Add a jQuery file to the HTML head tag.
 *
 * To make a jQuery script available just put it in include/javascript. The
 * file name should be like "jquery.name.js". The "name" would be the value
 * needed to pass to this function. Notice that this function does not manage
 * jQuery denpendencies.
 * 
 * @param string Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
 * <code>
 * ui_require_jquery_file ('form');
 * // Would include include/javascript/jquery.form.js
 * </code>
 * @param bool Just echo the script tag of the file.
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function ui_require_jquery_file ($name, $path = 'include/javascript/', $echo_tag = false) {
	global $config;
	
	$filename = $path.'jquery.'.$name.'.js';
	
	if ($echo_tag) {
		echo '<script type="text/javascript" src="' .
			ui_get_full_url(false, false, false, false) .
			$filename . '"></script>';
		return;
	}
	
	if (! isset ($config['jquery']))
		$config['jquery'] = array ();
	if (isset ($config['jquery'][$name]))
		return true;
	/* We checks two paths because it may fails on enterprise */
	if (! file_exists ($filename) && ! file_exists ($config['homedir'].'/'.$filename))
		return false;
	
	$config['jquery'][$name] = $filename;
	
	return true;
}

/**
 * Callback function to add stuff to the head. This allows us to add scripts
 * to the header after the fact as well as extensive validation.
 *
 * DO NOT CALL print_f, echo, ob_start, ob_flush, ob_end functions here.
 *
 * To add css just put them in include/styles and then add them to the
 * $config['css'] array
 *
 * @param string Callback will fill this with the current buffer.
 * @param bitfield Callback will fill this with a bitfield (see ob_start)
 * 
 * @return string String to return to the browser
 */
function ui_process_page_head ($string, $bitfield) {
	global $config;
	global $vc_public_view;
	
	if (isset ($config['ignore_callback']) && $config['ignore_callback'] == true) {
		return;
	}
	
	$output = '';
	
	$config_refr = -1;
	if (isset($config["refr"]))
		$config_refr = $config["refr"];
	
	// If user is logged or displayed view is the public view of visual console
	if ($config_refr > 0 &&
		(isset($config['id_user']) || $vc_public_view == 1)) {
		
		if ($config['enable_refr'] ||
			$_GET['sec2'] == 'operation/agentes/estado_agente' ||
			$_GET['sec2'] == 'operation/agentes/tactical' ||
			$_GET['sec2'] == 'operation/agentes/group_view' ||
			$_GET['sec2'] == 'operation/events/events' ||
			$_GET['sec2'] == 'operation/snmpconsole/snmp_view' ||
			$_GET['sec2'] == 'enterprise/dashboard/main_dashboard') {
			
			$query = ui_get_url_refresh (false, false);
			//$output .= '<meta http-equiv="refresh" content="' .
				//$config_refr . '; URL=' . $query . '" />';
			
		}
	}
	$output .= "\n\t";
	$output .= '<title>' . get_product_name() . ' - '.__('the Flexible Monitoring System').'</title>
		<meta http-equiv="expires" content="never" />
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="resource-type" content="document" />
		<meta name="distribution" content="global" />
		<meta name="author" content="' . get_copyright_notice() . '" />
		<meta name="copyright" content="(c) ' . get_copyright_notice() . '" />
		<meta name="robots" content="index, follow" />';
		$output .='<link rel="icon" href="' . ui_get_favicon() . '" type="image/ico" />';
		$output .='	
		<link rel="shortcut icon" href="' . ui_get_favicon() . '" type="image/x-icon" />
		<link rel="alternate" href="operation/events/events_rss.php" title="Pandora RSS Feed" type="application/rss+xml" />';
	
	if ($config["language"] != "en") {
		//Load translated strings - load them last so they overload all the objects
		ui_require_javascript_file ("time_".$config["language"]);
		ui_require_javascript_file ("date".$config["language"]);
		ui_require_javascript_file ("countdown_".$config["language"]);
	}
	$output .= "\n\t";
	
	
	
	
	
	////////////////////////////////////////////////////////////////////
	//Load CSS
	////////////////////////////////////////////////////////////////////
	if (empty ($config['css'])) {
		$config['css'] = array ();
	}
	
	$login_ok = true;
	if (! isset ($config['id_user']) && isset ($_GET["login"])) {
		if (isset($_POST['nick']) and isset($_POST['pass'])) {
			$nick = get_parameter_post ("nick"); //This is the variable with the login
			$pass = get_parameter_post ("pass"); //This is the variable with the password
			$nick = db_escape_string_sql($nick);
			$pass = db_escape_string_sql($pass);
			
			// process_user_login is a virtual function which should be defined in each auth file.
			// It accepts username and password. The rest should be internal to the auth file.
			// The auth file can set $config["auth_error"] to an informative error output or reference their internal error messages to it
			// process_user_login should return false in case of errors or invalid login, the nickname if correct
			$nick_in_db = process_user_login ($nick, $pass);
			
			if ($nick_in_db === false) {
				$login_ok = false;
			}
		}
	}
	
	//First, if user has assigned a skin then try to use css files of
	//skin subdirectory
	$isFunctionSkins = enterprise_include_once ('include/functions_skins.php');
	if (!$login_ok) {
		if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
			enterprise_hook('skins_cleanup');
		}
	}
	
	
	$exists_css = false;
	if ($login_ok and $isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
		//Checks if user's skin is available
		$exists_skin = enterprise_hook('skins_is_path_set');
		if ($exists_skin) {
			$skin_path = enterprise_hook('skins_get_skin_path');
			$skin_styles = themes_get_css ($skin_path . 'include/styles/');
			$exists_css = !empty($skin_styles);
		}
	}
	//If skin's css files exists then add them
	if ($exists_css) {
		foreach ($skin_styles as $filename => $name) {
			$style = substr ($filename, 0, strlen ($filename) - 4);
			$config['css'][$style] = $skin_path . 'include/styles/' . $filename;
		}
	}
	//Otherwise assign default and user's css
	else {
		//User style should go last so it can rewrite common styles
		$config['css'] = array_merge (array (
			"common" => "include/styles/common.css",
			"menu" => "include/styles/menu.css",
			$config['style'] => "include/styles/" . $config['style'] . ".css"),
			$config['css']);
	}
	
	
	
	// Add the jquery UI styles CSS
	$config['css']['jquery-UI'] = "include/styles/jquery-ui-1.10.0.custom.css";
	// Add the dialog styles CSS
	$config['css']['dialog'] = "include/styles/dialog.css";
	// Add the dialog styles CSS
	$config['css']['dialog'] = "include/javascript/introjs.css";
	
	
	
	//We can't load empty and we loaded (conditionally) ie
	$loaded = array ('', 'ie');
	
	foreach ($config['css'] as $name => $filename) {
		if (in_array ($name, $loaded))
			continue;
		
		array_push ($loaded, $name);
		
		$url_css = ui_get_full_url($filename);
		$output .= '<link rel="stylesheet" href="' . $url_css . '" type="text/css" />'."\n\t";
		
	}
	////////////////////////////////////////////////////////////////////
	//End load CSS
	////////////////////////////////////////////////////////////////////
	
	
	
	
	////////////////////////////////////////////////////////////////////
	//Load JS
	////////////////////////////////////////////////////////////////////
	if (empty ($config['js'])) {
		$config['js'] = array (); //If it's empty, false or not init set array to empty just in case
	}
	
	
	//Pandora specific JavaScript should go first
	$config['js'] = array_merge (array ("pandora" => "include/javascript/pandora.js"), $config['js']);
	//Load base64 javascript library
	$config['js']['base64'] = "include/javascript/encode_decode_base64.js";
	//Load webchat javascript library
	$config['js']['webchat'] = "include/javascript/webchat.js";
	//Load qrcode library
	$config['js']['qrcode'] = "include/javascript/qrcode.js";
	//Load intro.js library (for bubbles and clippy)
	$config['js']['intro'] = "include/javascript/intro.js";
	$config['js']['clippy'] = "include/javascript/clippy.js";
	//Load Underscore.js library
	$config['js']['underscore'] = "include/javascript/underscore-min.js";
	
	
	//Load other javascript
	//We can't load empty
	$loaded = array ('');
	foreach ($config['js'] as $name => $filename) {
		if (in_array ($name, $loaded))
			continue;
		
		array_push ($loaded, $name);
		
		$url_js = ui_get_full_url($filename);
		$output .= '<script type="text/javascript" src="' . $url_js . '"></script>'."\n\t";
	}
	////////////////////////////////////////////////////////////////////
	//End load JS
	////////////////////////////////////////////////////////////////////
	
	
	
	////////////////////////////////////////////////////////////////////
	//Load jQuery
	////////////////////////////////////////////////////////////////////
	if (empty ($config['jquery'])) {
		$config['jquery'] = array (); //If it's empty, false or not init set array to empty just in case
	}
	
	//Pandora specific jquery should go first
	$black_list_pages_old_jquery = array('operation/gis_maps/index');
	if (in_array(get_parameter('sec2'), $black_list_pages_old_jquery)) {
		$config['jquery'] = array_merge (
			array ("jquery" => "include/javascript/jquery.js",
				"ui" => "include/javascript/jquery.ui.core.js",
				"dialog" => "include/javascript/jquery.ui.dialog.js",
				"pandora" => "include/javascript/jquery.pandora.js"),
			$config['jquery']);
	}
	else {
		$config['jquery'] = array_merge(
			array ("jquery" => "include/javascript/jquery-1.9.0.js",
				"pandora" => "include/javascript/jquery.pandora.js",
				'jquery-ui' => 'include/javascript/jquery.jquery-ui-1.10.0.custom.js'),
			$config['jquery']);
	}
	
	// Include the datapicker language if exists
	if (file_exists('include/languages/datepicker/jquery.ui.datepicker-'.$config['language'].'.js')) {
		$config['jquery']['datepicker_language'] = 'include/languages/datepicker/jquery.ui.datepicker-'.$config['language'].'.js';
	}
	
	
	// Include countdown library
	$config['jquery']['countdown'] = 'include/javascript/jquery.countdown.js';
	
	
	//Then add each script as necessary
	$loaded = array ('');
	foreach ($config['jquery'] as $name => $filename) {
		if (in_array ($name, $loaded))
			continue;
		
		array_push ($loaded, $name);
		
		$url_js = ui_get_full_url($filename);
		$output .= '<script type="text/javascript" src="' . $url_js . '"></script>'."\n\t";
	}
	////////////////////////////////////////////////////////////////////
	//End load JQuery
	////////////////////////////////////////////////////////////////////
	
	
	
	
	if ($config['flash_charts']) {
		//Include the javascript for the js charts library
		include_once($config["homedir"] . '/include/graphs/functions_flot.php');
		$output .= include_javascript_dependencies_flot_graph(true);
	}
	
	
	$output .= '<!--[if gte IE 6]>
		<link rel="stylesheet" href="include/styles/ie.css" type="text/css"/>
		<![endif]-->';
	
	$output .= $string;
	
	
	return $output;
}

/**
 * Callback function to add stuff to the body
 *
 * @param string Callback will fill this with the current buffer.
 * @param bitfield Callback will fill this with a bitfield (see ob_start)
 * 
 * @return string String to return to the browser
 */
function ui_process_page_body ($string, $bitfield) {
	global $config;
	
	if (isset ($config['ignore_callback']) &&
		$config['ignore_callback'] == true) {
		return;
	}
	
	// Show custom background
	$output = '<body'.($config["pure"] ? ' class="pure"' : '').'>';
	
	
	$output .= $string;
	
	
	$output .= '</body>';
	
	return $output;
}

/**
 * Prints a pagination menu to browse into a collection of data.
 * 
 * @param int $count Number of elements in the collection.
 * @param string $url URL of the pagination links. It must include all form
 * values as GET form.
 * @param int $offset Current offset for the pagination. Default value would be
 * taken from $_REQUEST['offset']
 * @param int $pagination Current pagination size. If a user requests a larger
 * pagination than config["block_size"]
 * @param bool $return Whether to return or print this
 * @param string $offset_name The name of parameter for the offset.
 * @param bool $print_total_items Show the text with the total items. By default true.
 * @param string $set_id Set id of div.
 *
 * @return string The pagination div or nothing if no pagination needs to be done
 */
function ui_pagination ($count, $url = false, $offset = 0,
	$pagination = 0, $return = false, $offset_name = 'offset',
	$print_total_items = true, $other_class = '',
	$script = "",
	$parameter_script = array('count' => '', 'offset' => 'offset_param'), $set_id = '') {
	
	global $config;
	
	if (empty ($pagination)) {
		$pagination = (int) $config["block_size"];
	}
	
	if (is_string ($offset)) {
		$offset_name = $offset;
		$offset = (int) get_parameter ($offset_name);
	}
	
	if (empty ($offset)) {
		$offset = (int) get_parameter ($offset_name);
	}
	
	if (empty ($url)) {
		$url = ui_get_url_refresh (array ($offset_name => false));
	}
	
	if(!empty($set_id)){
		$set_id=  " id = '$set_id'";
	}

	// Pagination links for users include delete, create and other params, now not use these params, and not retry the previous action when go to pagination link.
	
	$remove = array("user_del","disable_user","delete_user");
	$url = explode("&",$url);
	
	$finalUrl = array();
	foreach ($url as $key => $value) {
		if(strpos($value, $remove[0]) === false && strpos($value, $remove[1]) === false && strpos($value, $remove[2]) === false){
			array_push($finalUrl,$value);
		}
	}
	$url = implode("&",$finalUrl);

	/*
	 URL passed render links with some parameter
	 &offset - Offset records passed to next page
	 &counter - Number of items to be blocked
	 Pagination needs $url to build the base URL to render links, its a base url, like
	 " http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
	 */
	$block_limit = PAGINATION_BLOCKS_LIMIT; // Visualize only $block_limit blocks
	if ($count <= $pagination) {
		
		if ($print_total_items) {
			$output = "<div class='pagination $other_class' $set_id>";
			//Show the count of items
			$output .= sprintf(__('Total items: %s'), $count);
			// End div and layout
			$output .= "</div>";
			
			if ($return === false)
				echo $output;
			
			return $output;
		}
		
		return false;
	}
	
	$number_of_pages = ceil($count / $pagination);
	$actual_page = floor($offset / $pagination);
	$ini_page = floor($actual_page / $block_limit) * $block_limit;
	$end_page = $ini_page + $block_limit - 1;
	if ($end_page > $number_of_pages) {
		$end_page = $number_of_pages - 1;
	}
	
	
	$output = "<div class='pagination $other_class' $set_id>";
	
	//Show the count of items
	if ($print_total_items) {
		$output .= sprintf(__('Total items: %s'), $count);
		$output .= '<br>';
	}
	
	// Show GOTO FIRST PAGE button
	if ($number_of_pages > $block_limit) {
		
		if (!empty($script)) {
			$script_modified = $script;
			$script_modified = str_replace(
				$parameter_script['count'], $count, $script_modified);
			$script_modified = str_replace(
				$parameter_script['offset'], 0, $script_modified);
			
			$output .= "<a class='pagination $other_class offset_0'
				href='javascript: $script_modified;'>" .
				html_print_image ("images/go_first.png", true, array ("class" => "bot")) .
				"</a>&nbsp;";
		}
		else {
			$output .= "<a class='pagination $other_class offset_0' href='$url&amp;$offset_name=0'>" .
				html_print_image ("images/go_first.png", true, array ("class" => "bot")) .
				"</a>&nbsp;";
		}
	}
	
	// Show PREVIOUS PAGE GROUP OF PAGES
	// For example
	// You are in the 12 page with a block of 5 pages
	// << < 10 - 11 - [12] - 13 - 14 > >>
	// Click in <
	// Result << < 5 - 6 - 7 - 8 - [9] > >>
	if ($ini_page >= $block_limit) {
		$offset_previous_page = ($ini_page - 1) * $pagination;
		
		if (!empty($script)) {
			$script_modified = $script;
			$script_modified = str_replace(
				$parameter_script['count'], $count, $script_modified);
			$script_modified = str_replace(
				$parameter_script['offset'], $offset_previous_page, $script_modified);
			
			$output .= "<a class='pagination $other_class offset_$offset_previous_page'
				href='javacript: $script_modified;'>" .
				html_print_image ("images/go_previous.png", true, array ("class" => "bot")) .
				"</a>";
		}
		else {
			$output .= "<a class='pagination $other_class offset_$offset_previous_page' href='$url&amp;$offset_name=$offset_previous_page'>" .
				html_print_image ("images/go_previous.png", true, array ("class" => "bot")) .
				"</a>";
		}
	}
	
	
	// Show pages
	for ($iterator = $ini_page; $iterator <= $end_page;  $iterator++) {
		
		$actual_page = (int)($offset / $pagination);
		
		if ($iterator == $actual_page) {
			$output .= "<span style='font-weight: bold;'>";
		}
		else {
			$output .= "<span>";
		}
		
		$offset_page = $iterator * $pagination;
		
		if (!empty($script)) {
			$script_modified = $script;
			$script_modified = str_replace(
				$parameter_script['count'], $count, $script_modified);
			$script_modified = str_replace(
				$parameter_script['offset'], $offset_page, $script_modified);
			
			$output .= "<a class='pagination $other_class offset_$offset_page'
				href='javascript: $script_modified;'>";
		}
		else {
			
			$output .= "<a class='pagination $other_class offset_$offset_page' href='$url&amp;$offset_name=$offset_page'>";
			
		}
		
		$output .= "[ $iterator ]";
		
		$output .= '</a></span>';
		
	}
	
	
	// Show NEXT PAGE GROUP OF PAGES
	// For example
	// You are in the 12 page with a block of 5 pages
	// << < 10 - 11 - [12] - 13 - 14 > >>
	// Click in >
	// Result << < [15] - 16 - 17 - 18 - 19 > >>
	if ($number_of_pages - $ini_page > $block_limit) {
		$offset_next_page = ($end_page + 1) * $pagination;
		
		if (!empty($script)) {
			$script_modified = $script;
			$script_modified = str_replace(
				$parameter_script['count'], $count, $script_modified);
			$script_modified = str_replace(
				$parameter_script['offset'], $offset_next_page, $script_modified);
			
			$output .= "<a class='pagination $other_class offset_$offset_next_page'
				href='javascript: $script_modified;'>" .
				html_print_image ("images/go_next.png", true, array ("class" => "bot")) .
				"</a>";
		}
		else {
			$output .= "<a class='pagination $other_class offset_$offset_next_page' href='$url&amp;$offset_name=$offset_next_page'>" .
				html_print_image ("images/go_next.png", true, array ("class" => "bot")) .
				"</a>";
		}
	}
	
	//Show GOTO LAST PAGE button
	if ($number_of_pages > $block_limit) {
		$offset_lastpage = ($number_of_pages - 1) * $pagination;
		
		
		if (!empty($script)) {
			$script_modified = $script;
			$script_modified = str_replace(
				$parameter_script['count'], $count, $script_modified);
			$script_modified = str_replace(
				$parameter_script['offset'], $offset_lastpage, $script_modified);
			
			$output .= "<a class='pagination $other_class offset_$offset_lastpage'
				href='javascript: $script_modified;'>" .
				html_print_image ("images/go_last.png", true, array ("class" => "bot")) .
				"</a>";
		}
		else {
			$output .= "<a class='pagination $other_class offset_$offset_lastpage' href='$url&amp;$offset_name=$offset_lastpage'>" .
				html_print_image ("images/go_last.png", true, array ("class" => "bot")) .
				"</a>";
		}
	}
	
	// End div and layout
	$output .= "</div>";
	
	if ($return === false)
		echo $output;
	
	return $output;
}

/**
 * Prints only a tip button which shows a text when the user puts the mouse over it.
 * 
 * @param string Complete text to show in the tip
 * @param bool whether to return an output string or echo now
 * @param img displayed image
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_session_action_icon ($action, $return = false) {
	$key_icon = array(
		'acl' => 'images/delete.png',
		'agent' => 'images/agent.png',
		'module' => 'images/module.png',
		'alert' => 'images/bell.png',
		'incident' => 'images/default_list.png',
		'logon' => 'images/house.png',
		'logoff' => 'images/house.png',
		'massive' => 'images/config.png',
		'hack' => 'images/application_edit.png',
		'event' => 'images/lightning_go.png',
		'policy' => 'images/policies.png',
		'report' => 'images/reporting.png',
		'file collection' => 'images/collection_col.png',
		'user' => 'images/user_green.png',
		'password' => 'images/lock.png',
		'session' => 'images/heart_col.png',
		'snmp' => 'images/snmp.png',
		'command' => 'images/bell.png',
		'category' => 'images/category_col.png',
		'dashboard' => 'images/dashboard_col.png',
		'api' => 'images/eye.png',
		'db' => 'images/database.png',
		'setup' => 'images/cog.png');
	
	$output = '';
	foreach($key_icon as $key => $icon) {
		if (stristr($action, $key) !== false) {
			$output = html_print_image($icon, true, array('title' => $action), false, false, false, true) . ' ';
			break;
		}
	}
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Prints only a tip button which shows a text when the user puts the mouse over it.
 * 
 * @param string Complete text to show in the tip
 * @param bool whether to return an output string or echo now
 * @param img displayed image
 * @param bool Print image in relative way
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_help_tip ($text, $return = false, $img = 'images/tip.png', $is_relative = false) {
	$output =
		'<a href="javascript:" class="tip" >' .
			html_print_image (
				$img,
				true,
				array('title' => $text),
				false,
				$is_relative && is_metaconsole()
			) .
		'</a>';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Powerful debug function that also shows a backtrace.
 * 
 * This functions need to have active $config['debug'] variable to work.
 *
 * @param mixed Variable name to debug
 * @param bool Wheter to print the backtrace or not.
 * 
 * @return bool Tru if the debug was actived. False if not.
 */
function ui_debug ($var, $backtrace = true) {
	global $config;
	if (! isset ($config['debug']))
		return false;
	
	static $id = 0;
	static $trace_id = 0;
	
	$id++;
	
	if ($backtrace) {
		echo '<div class="debug">';
		echo '<a href="#" onclick="$(\'#trace-'.$id.'\').toggle ();return false;">Backtrace</a>';
		echo '<div id="trace-'.$id.'" class="backtrace invisible">';
		echo '<ol>';
		$traces = debug_backtrace ();
		/* Ignore debug function */
		unset ($traces[0]);
		foreach ($traces as $trace) {
			$trace_id++;
			
			/* Many classes are used to allow better customization.
			Please, do not remove them */
			echo '<li>';
			if (isset ($trace['class']))
				echo '<span class="class">'.$trace['class'].'</span>';
			if (isset ($trace['type']))
				echo '<span class="type">'.$trace['type'].'</span>';
			echo '<span class="function">';
			echo '<a href="#" onclick="$(\'#args-'.$trace_id.'\').toggle ();return false;">'.$trace['function'].'()</a>';
			echo '</span>';
			if (isset ($trace['file'])) {
				echo ' - <span class="filename">';
				echo str_replace ($config['homedir'].'/', '', $trace['file']);
				echo ':'.$trace['line'].'</span>';
			}
			else {
				echo ' - <span class="filename"><em>Unknown file</em></span>';
			}
			echo '<pre id="args-'.$trace_id.'" class="invisible">';
			echo '<div class="parameters">Parameter values:</div>';
			echo '<ol>';
			foreach ($trace['args'] as $arg) {
				echo '<li>';
				print_r ($arg);
				echo '</li>';
			}
			echo '</ol>';
			echo '</pre>';
			echo '</li>';
		}
		echo '</ol>';
		echo '</div></div>';
	}
	
	/* Actually print the variable given */
	echo '<pre class="debug">';
	print_r ($var);
	echo '</pre>';
	
	return true;
}

/**
 * Prints icon of a module type
 * 
 * @param int Module Type ID
 * @param bool Whether to return or print
 * @param bool $relative Whether to use relative path to image or not (i.e. $relative= true : /pandora/<img_src>).
 * @param bool $options Whether to use image options like style, border or title on the icon.
 *
 * @return string An HTML string with the icon. Printed if return is false
 */
function ui_print_moduletype_icon ($id_moduletype, $return = false, $relative = false, $options = true, $src = false) {
	global $config;
	
	$type = db_get_row ("ttipo_modulo", "id_tipo", (int) $id_moduletype, array ("descripcion", "icon"));
	if ($type === false) {
		$type = array ();
		$type["descripcion"] = __('Unknown type');
		$type["icon"] = 'b_down.png';
	}
	$imagepath = 'images/'.$type["icon"];
	if (! file_exists ($config['homedir'].'/'.$imagepath))
		$imagepath = ENTERPRISE_DIR.'/'.$imagepath;
	
	if ($src) {
		return $imagepath;
	}
	
	if ($options) {
		return html_print_image ($imagepath, $return,
			array ("border" => 0,
				"title" => $type["descripcion"]), false, $relative);
	}
	else {
		return html_print_image ($imagepath, $return,
			false, false, $relative);
	}
}

/**
 * Print module max/min values for warning/critical state
 *
 * @param float Max value for warning state
 * @param float Min value for warning state
 * @param float Max value for critical state
 * @param float Min value for critical state
 *
 * @return string HTML string
 */
function ui_print_module_warn_value ($max_warning, $min_warning, $str_warning, $max_critical, $min_critical, $str_critical) {
	$data = "<span style='font-size: 8px' title='" . __("Warning") . ": " . __("Max") . $max_warning . "/" . __("Min") . $min_warning . " - " . __("Critical") . ": " . __("Max") . $max_critical . "/" . __("Min") . $min_critical . "'>";
	
	if ($max_warning != $min_warning) {
		$data .= format_for_graph($max_warning) ."/". format_for_graph ($min_warning);
	}
	else {
		$data .= __("N/A");
	}
	
	$data .= " - ";
	
	if ($max_critical != $min_critical) {
		$data .= format_for_graph($max_critical) . "/" .
			format_for_graph ($min_critical);
	}
	else {
		$data .= __("N/A");
	}
	$data .= "</span>";
	return $data;
}

/**
* Format a file size from bytes to a human readable meassure.
*
* @param int File size in bytes
* @return string Bytes converted to a human readable meassure.
*/
function ui_format_filesize ($bytes) {
	$bytes = (int) $bytes;
	$strs = array ('B', 'kB', 'MB', 'GB', 'TB');
	if ($bytes <= 0) {
		return "0 ".$strs[0];
	}
	$con = 1024;
	$log = (int) (log ($bytes, $con));
	
	return format_numeric ($bytes / pow ($con, $log), 1).' '.$strs[$log];
}


/**
 * Returns the current path to the selected image set to show the
 * status of agents and alerts.
 *
 * @return array An array with the image path, image width and image height.
 */
function ui_get_status_images_path () {
	global $config;
	
	$imageset = $config["status_images_set"];
	
	if (strpos ($imageset, ",") === false)
		$imageset .= ",40x18";
	list ($imageset, $sizes) = preg_split ("/\,/", $imageset);
	
	if (strpos ($sizes, "x") === false)
		$sizes .= "x18";
	list ($imagewidth, $imageheight) = preg_split ("/x/", $sizes);
	
	$imagespath = 'images/status_sets/'.$imageset;
	
	return array ($imagespath);
}

/**
 * Prints an image representing a status.
 *
 * @param string
 * @param string
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param array options to set image attributes: I.E.: style
 * @param Path of the image, if not provided use the status path
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_status_image ($type, $title = "", $return = false, $options = false, $path = false) {
	if ($path === false) {
		list ($imagepath) = ui_get_status_images_path ();
	}
	else {
		$imagepath = $path;
	}
	
	$imagepath .= "/" . $type;
	
	if ($options === false) {
		$options = array();
	}
	
	$options['title'] = $title;
	
	return html_print_image ($imagepath, $return, $options, false, false, false, true);
}

/**
 * Print a code into a DIV and enable a toggle to show and hide it
 * 
 * @param string html code
 * @param string name of the link
 * @param string title of the link
 * @param bool if the div will be hidden by default (default: true)
 * @param bool Whether to return an output string or echo now (default: true)
 * 
 */

function ui_toggle($code, $name, $title = '', $hidden_default = true, $return = false) {
	// Generate unique Id
	$uniqid = uniqid('');
	
	// Options
	if ($hidden_default) {
		$style = 'display:none';
		$image_a = html_print_image("images/down.png", true, false, true);
		$image_b = html_print_image("images/go.png", true, false, true);
		$original = "images/go.png";
	}
	else {
		$style = '';
		$image_a = html_print_image("images/down.png", true, false, true);
		$image_b = html_print_image("images/go.png", true, false, true);
		$original = "images/down.png";
	}
	
	// Link to toggle
	$output = '';
	$output .= '<a href="javascript:" id="tgl_ctrl_'.$uniqid.'">' . html_print_image ($original, true, array ("title" => $title, "id" => "image_".$uniqid)) . '&nbsp;&nbsp;<b>'.$name.'</b></a>';
	$output .= '<br />';
	
	//if (!defined("METACONSOLE"))
		//$output .= '<br />';
	
	// Code into a div
	$output .= "<div id='tgl_div_".$uniqid."' style='".$style."'>\n";
	$output .= $code;
	$output .= "</div>";
	
	// JQuery Toggle
	$output .= '<script type="text/javascript">' . "\n";
	$output .= "	var hide_tgl_ctrl_" . $uniqid . " = " . (int)$hidden_default . ";\n";
	$output .= '	/* <![CDATA[ */' . "\n";
	$output .= "	$(document).ready (function () {\n";
	$output .= "		$('#tgl_ctrl_".$uniqid."').click(function() {\n";
	$output .= "			if (hide_tgl_ctrl_" . $uniqid . ") {\n";
	$output .= "				hide_tgl_ctrl_" . $uniqid . " = 0;\n";
	$output .= "				$('#tgl_div_".$uniqid."').toggle();\n";
	$output .= "				$('#image_".$uniqid."').attr({src: '".$image_a."'});\n";
	$output .= "			}\n";
	$output .= "			else {\n";
	$output .= "				hide_tgl_ctrl_" . $uniqid . " = 1;\n";
	$output .= "				$('#tgl_div_".$uniqid."').toggle();\n";
	$output .= "				$('#image_".$uniqid."').attr({src: '".$image_b."'});\n";
	$output .= "			}\n";
	$output .= "		});\n";
	$output .= "	});\n";
	$output .= '/* ]]> */';
	$output .= '</script>';
	
	if (!$return) {
		echo $output;
	}
	else {
		return $output;
	}
}

/**
 * Construct and return the URL to be used in order to refresh the current page correctly.
 *
 * @param array Extra parameters to be added to the URL. It has prevalence over
 * GET and POST. False values will be ignored.
 * @param bool Whether to return the relative URL or the absolute URL. Returns
 * relative by default
 * @param bool Whether to add POST values to the URL.
 */
function ui_get_url_refresh ($params = false, $relative = true, $add_post = true) {
	// Agent selection filters and refresh
	global $config;
	
	// slerena, 8/Ene/2015 - Need to put index.php on URL which have it.
	if (strpos($_SERVER['REQUEST_URI'], 'index.php') === false)
		$url = '';
	else
		$url = 'index.php';

	if (sizeof ($_REQUEST)) {
		//Some (old) browsers don't like the ?&key=var
		$url .= '?';
	}
	
	if (! is_array ($params))
		$params = array ();
	/* Avoid showing login info */
	$params['pass'] = false;
	$params['nick'] = false;
	$params['unnamed'] = false;
	
	//We don't clean these variables up as they're only being passed along
	foreach ($_GET as $key => $value) {
		if (isset ($params[$key]))
			continue;
		if (strstr ($key, 'create'))
			continue;
		if (strstr ($key, 'update'))
			continue;
		if (strstr ($key, 'new'))
			continue;
		if (strstr ($key, 'delete'))
			continue;
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$url .= $key."[".$k.']='.$v.'&';
			}
		}
		else {
			$url .= $key.'='.$value.'&';
		}
	}
	
	if ($add_post) {
		foreach ($_POST as $key => $value) {
			if (isset ($params[$key]))
				continue;
			if (strstr ($key, 'create'))
				continue;
			if (strstr ($key, 'update'))
				continue;
			if (strstr ($key, 'new'))
				continue;
			if (strstr ($key, 'delete'))
				continue;
			if (is_array($value)) {
				foreach ($value as $k => $v) {
					$url .= $key."[".$k.']='.$v.'&';
				}
			}
			else {
				$url .= $key.'='.$value.'&';
			}
			
		}
	}
	
	foreach ($params as $key => $value) {
		if ($value === false)
			continue;
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$url .= $key."[".$k.']='.$v.'&';
			}
		}
		else {
			$url .= $key.'='.$value.'&';
		}
	}
	
	/* Removes final & */
	$pos = strrpos ($url, '&', 0);
	if ($pos) {
		$url = substr_replace ($url, '', $pos, 5);
	}
	
	$url = htmlspecialchars ($url);
	
	if (! $relative) {
		return ui_get_full_url ($url);
	}
	
	return $url;
}

/**
 * Returns a full URL in Pandora. (with the port and https in some systems)
 *
 * An example of full URL is http:/localhost/pandora_console/index.php?sec=gsetup&sec2=godmode/setup/setup
 *
 * @param mixed $url If provided, it will be added after the index.php, but it is false boolean value, put the homeurl in the url.
 * @param boolean $no_proxy To avoid the proxy checks, by default it is false.
 * @param boolean $metaconsole_root Set the root to the metaconsole dir if the metaconsole is enabled, true by default.
 *
 * @return string A full URL in Pandora.
 */
function ui_get_full_url ($url = '', $no_proxy = false, $add_name_php_file = false, $metaconsole_root = true) {
	global $config;
	
	$port = null;   // null means 'use the starndard port'
	$proxy = false; //By default Pandora FMS doesn't run across proxy.
	
	if(isset ($_SERVER['HTTP_X_FORWARDED_PROTO'])
		&& $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
		$_SERVER['HTTPS'] = 'on';
	}
	if (isset ($_SERVER['HTTPS'])
		&& ($_SERVER['HTTPS'] === true
		|| $_SERVER['HTTPS'] == 'on')) {
		$protocol = 'https';
		if ( $_SERVER['SERVER_PORT'] != 443) {
			$port = $_SERVER['SERVER_PORT'];
		}
	}
	elseif ($config['https']) {
		//When $config["https"] is set, enforce https
		$protocol = 'https';
	}
	else {
		$protocol = 'http';
		
		if ( $_SERVER['SERVER_PORT'] != 80) {
			$port = $_SERVER['SERVER_PORT'];
		}
	}
	
	if (!$no_proxy) {
		//Check if the PandoraFMS runs across the proxy like as
		//mod_proxy of Apache
		//and check if public_url is setted
		if (!empty($config['public_url'])
			&& (!empty($_SERVER['HTTP_X_FORWARDED_HOST']))) {
			$fullurl = $config['public_url'];
			$proxy = true;
		}
		else {
			$fullurl = $protocol.'://' . $_SERVER['SERVER_NAME'];
		}
	}
	else {
		$fullurl = $protocol.'://' . $_SERVER['SERVER_NAME'];
	}
	
	// using a different port than the standard
	if (!$proxy) {
		// using a different port than the standard
		if ( $port != null ) {
			$fullurl .= ":" . $port;
		}
	}
	
	if ($url === '') {
		if ($proxy) {
			$url = '';
		}
		else {
			$url = $_SERVER['REQUEST_URI'];
		}
	}
	elseif ($url === false) {
		if ($proxy) {
			$url = '';
		}
		else {
			//Only add the home url
			$url = $config['homeurl_static'] . '/';
		}
		
		if (defined('METACONSOLE') && $metaconsole_root) {
			$url .= 'enterprise/meta/';
		}
	}
	elseif (!strstr($url, ".php")) {
		if ($proxy) {
			$fullurl .= '/';
		}
		else {
			$fullurl .= $config['homeurl_static'] . '/';
		}
		
		if (defined('METACONSOLE') && $metaconsole_root) {
			$fullurl .= 'enterprise/meta/';
		}
	}
	else {
		if ($proxy) {
			$fullurl .= '/';
		}
		else {
			if ($add_name_php_file) {
				$fullurl .= $_SERVER['SCRIPT_NAME'];
			}
			else {
				$fullurl .= $config['homeurl_static'] . '/';
				
				if (defined('METACONSOLE') && $metaconsole_root) {
					$fullurl .= 'enterprise/meta/';
				}
			}
		}
	}
	
	if (substr($fullurl, -1, 1) === substr($url, 0, 1)) {
		if (substr($fullurl, -1, 1) === '/') {
			$url = substr($url, 1);
		}
	}
	
	return $fullurl . $url;
}

/**
 * Return a standard page header (Pandora FMS 3.1 version)
 *
 * @param string Title
 * @param string Icon path
 * @param boolean Return (false will print using a echo)
 * @param boolean help (Help ID to print the Help link)
 * @param boolean Godmode (false = operation mode).
 * @param string Options (HTML code for make tabs or just a brief info string
 * @return string Header HTML
 */

function ui_print_page_header ($title, $icon = "", $return = false, 
	$help = "", $godmode = false, $options = "", $modal = false, 
	$message = "", $numChars = GENERIC_SIZE_TEXT, $alias = "") {
	
	$title = io_safe_input_html($title);
	if (($icon == "") && ($godmode == true)) {
		$icon = "images/gm_setup.png";
	}
	
	if (($icon == "") && ($godmode == false)) {
		$icon = "";
	}
	
	if ($godmode == true) {
		$type = "view";
		$type2 = "menu_tab_frame_view";
		$separator_class = "separator";
	}
	else {
		$type = "view";
		$type2 = "menu_tab_frame_view";
		$separator_class = "separator_view";
	}

	$buffer = '<div id="'.$type2.'" style=""><div id="menu_tab_left">';

	$buffer .= '<ul class="mn"><li class="' . $type . '">';
	
	if(strpos($title, "Monitoring » Services »") != -1){
		$title = str_replace("Monitoring » Services » Service Map » ",'',$title);
	}
	
	$buffer .= '<span>';
	if (empty($alias)) $buffer .= ui_print_truncate_text($title, $numChars);
	else $buffer .=  ui_print_truncate_text($alias, $numChars);
	
	if ($modal && !enterprise_installed()){
		$buffer .= "
		<div id='".$message."' class='publienterprise' title='Community version' style='float: right;margin-top: -2px !important;'><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>
		";
	}

	if(!is_metaconsole()){
		if ($help != ""){
			$buffer .= "<div class='head_help' style='float: right; margin-top: -2px !important;'>" .
				ui_print_help_icon ($help, true, '', 'images/help_w.png') . "</div>";
		}
	}

	$buffer .= '</span>';

	if(is_metaconsole()){
		if ($help != ""){
			$buffer .= "<div class='head_help'>" . ui_print_help_icon ($help, true, '', 'images/help_30.png') . "</div>";
		}
	}

	$buffer .= '</li></ul></div>';

	if (is_array($options)) {
		$buffer .= '<div id="menu_tab"><ul class="mn">';
		foreach ($options as $key => $option) {
			if (empty($option)) {
				continue;
			}
			else if ($key === 'separator') {
				continue;
				//$buffer .= '<li class='.$separator_class.'>';
				//$buffer .= '</li>';
			}
			else {
				if (is_array($option)) {
					$class = 'nomn';
					if (isset($option['active'])) {
						if ($option['active']) {
							$class = 'nomn_high';
						}
					}
					
					// Tabs forced to other styles
					if (isset($option['godmode']) && $option['godmode']) {
						$class .= ' tab_godmode';
					}
					else if (isset($option['operation']) && ($option['operation'])) {
						$class .= ' tab_operation';
					}
					else {
						$class .= $godmode ? ' tab_godmode' : ' tab_operation';
					}
					
					$buffer .= '<li class="' . $class . '">';
					$buffer .= $option['text'];
					if (isset($option['sub_menu']))
						$buffer .= $option['sub_menu'];
					$buffer .= '</li>';
					
				}
				else {
					$buffer .= '<li class="nomn">';
					$buffer .= $option;
					$buffer .= '</li>';
				}
			}
		}
		$buffer .= '</ul></div>';
	}
	else {
		if ($options != "") {
			$buffer .= '<div id="menu_tab"><ul class="mn"><li>';
			$buffer .= $options;
			$buffer .= '</li></ul></div>';
		}
	}
	
	$buffer .=  '</div>';
	
	if (!$return)
		echo $buffer;
	
	return $buffer;
}


/**
 * Print a input for agent autocomplete, this input search into your
 * pandora DB (or pandoras DBs when you have metaconsole) for agents
 * that have name near to equal that you are writing into the input.
 * 
 * This generate a lot of lines of html and javascript code.
 *
 * @parameters array Array with several properties:
 *  - $parameters['return'] boolean, by default is false
 *    true  - return as html string the code (html and js)
 *    false - print the code.
 * 
 *  - $parameters['input_name'] the input name (needs to get the value)
 *    string  - The name.
 *    default - "agent_autocomplete_<aleatory_uniq_raw_letters/numbers>"
 * 
 *  - $parameters['input_id'] the input id (needs to get the value)
 *    string  - The ID.
 *    default - "text-<input_name>"
 * 
 *  - $parameters['selectbox_group'] the id of selectbox with the group
 *    string  - The ID of selectbox.
 *    default - "" empty string
 * 
 *  - $parameters['icon_image'] the small icon to show into the input in
 *    the right side.
 *    string  - The url for the image.
 *    default - "images/lightning.png"
 * 
 *  - $parameters['value'] The initial value to set the input.
 *    string  - The value.
 *    default - "" emtpy string
 * 
 *  - $parameters['show_helptip'] boolean, by  default is false
 *    true  - print the icon out the field in side right the tiny star
 *            for tip.
 *    false - does not print
 * 
 *  - $parameters['helptip_text'] The text to show in the tooltip.
 *    string  - The text to show into the tooltip.
 *    default - "Type at least two characters to search." (translate)
 * 
 *  - $parameters['use_hidden_input_idagent'] boolean, Use a field for
 *    store the id of agent from the ajax query. By default is false.
 *    true  - Use the field for id agent and the sourcecode work with
 *            this.
 *    false - Doesn't use the field (maybe this doesn't exist outer)
 * 
 *  - $parameters['print_hidden_input_idagent'] boolean, Print a field
 *    for store the id of agent from the ajax query. By default is
 *    false.
 *    true  - Print the field for id agent and the sourcecode work with
 *            this.
 *    false - Doesn't print the field (maybe this doesn't exist outer)
 *
 *  - $parameters['hidden_input_idagent_name'] The name of hidden input
 *    for to store the id agent.
 *    string  - The name of hidden input.
 *    default - "agent_autocomplete_idagent_<aleatory_uniq_raw_letters/numbers>"
 *
 *  - $parameters['hidden_input_idagent_id'] The id of hidden input
 *    for to store the id agent.
 *    string  - The id of hidden input.
 *    default - "hidden-<hidden_input_idagent_name>"
 *
 *  - $parameters['hidden_input_idagent_value'] The initial value to set
 *    the input id agent for store the id agent.
 *    string  - The value.
 *    default - 0
 *
 *  - $parameters['size'] The size in characters for the input of agent.
 *    string  - A number of characters.
 *    default - 30
 *
 *  - $parameters['maxlength'] The max characters that can store the
 *    input of agent.
 *    string  - A number of characters max to store
 *    default - 100
 *
 *  - $parameters['disabled'] Set as disabled the input of agent. By
 *    default is false
 *    true  - Set disabled the input of agent.
 *    false - Set enabled the input of agent.
 *
 *  - $parameters['selectbox_id'] The id of select box that stores the
 *    list of modules of agent select.
 *    string - The id of select box.
 *    default - "id_agent_module"
 *
 *  - $parameters['add_none_module'] Boolean, add the list of modules
 *    the "none" entry, with value 0. By default is true
 *    true  - add the none entry.
 *    false - does not add the none entry.
 *
 *  - $parameters['none_module_text'] Boolean, add the list of modules
 *    the "none" entry, with value 0.
 *    string  - The text to put for none module for example "select a
 *              module"
 *    default - "none" (translate)
 *
 *  - $parameters['print_input_server'] Boolean, print the hidden field
 *    to store the server (metaconsole). By default false.
 *    true  - Print the hidden input for the server.
 *    false - Does not print.
 *
 *  - $parameters['use_input_server'] Boolean, use the hidden field
 *    to store the server (metaconsole). By default false.
 *    true  - Use the hidden input for the server.
 *    false - Does not print.
 *
 *  - $parameters['input_server_name'] The name for hidden field to
 *    store the server.
 *    string  - The name of field for server.
 *    default - "server_<aleatory_uniq_raw_letters/numbers>"
 *
 *  - $parameters['input_server_id'] The id for hidden field to store
 *    the server.
 *    string  - The id of field for server.
 *    default - "hidden-<input_server_name>"
 *
 *  - $parameters['input_server_value'] The value to store into the
 *    field server.
 *    string  - The name of server.
 *    default - "" empty string
 *
 *  - $parameters['metaconsole_enabled'] Boolean, set the sourcecode for
 *    to make some others things that run of without metaconsole. By
 *    default false.
 *    true  - Set the gears for metaconsole.
 *    false - Run as without metaconsole.
 *
 *  - $parameters['javascript_ajax_page'] The page to send the ajax
 *    queries.
 *    string  - The url to ajax page, remember the url must be into your
 *              domain (ajax security).
 *    default - "ajax.php"
 *
 *  - $parameters['javascript_function_action_after_select'] The name of
 *    function to call after the user select a agent into the list in
 *    the autocomplete field.
 *    string  - The name of function.
 *    default - ""
 *
 *  - $parameters['javascript_function_action_after_select_js_call'] The
 *    call of this function to call after user select a agent into the
 *    list in the autocomplete field. Instead the
 *    $parameters['javascript_function_action_after_select'], this is
 *    overwrite the previous element. And this is necesary when you need
 *    to set some params in your custom function.
 *    string  - The call line as javascript code.
 *    default - ""
 *
 *  - $parameters['javascript_function_action_into_source'] The source
 *    code as block string to call when the autocomplete starts to get
 *    the data from ajax.
 *    string  - A huge string with your function as javascript.
 *    default - ""
 *
 *  - $parameters['javascript'] Boolean, set the autocomplete agent to
 *    use javascript or enabled javascript. By default true.
 *    true  - Enabled the javascript.
 *    false - Disabled the javascript.
 *
 *  - $parameters['javascript_is_function_select'] Boolean, set to
 *    enable to call a function when user select a agent in the
 *    autocomplete list. By default false.
 *    true  - Enabled this feature.
 *    false - Disabled this feature.
 *
 *  - $parameters['javascript_code_function_select'] The name of
 *    function to call when user select a agent in the autocomplete
 *    list.
 *    string  - The name of function but remembers this function pass
 *              the parameter agent_name.
 *    default - "function_select_<input_name>"
 *
 *  - $parameters['javascript_name_function_select'] The source
 *    code as block string to call when user select a agent into the
 *    list in the autocomplete field. Althought use this element, you
 *    need use the previous parameter to set name of your custom
 *    function or call line.
 *    string  - A huge string with your function as javascript.
 *    default - A lot of lines of source code into a string, please this
 *              lines you can read in the source code of function.
 *
 *  - $parameters['javascript_change_ajax_params'] The params to pass in
 *    the ajax query for the list of agents.
 *    array   - The associative array with the key and value to pass in
 *              the ajax query.
 *    default - A lot of lines of source code into a string, please this
 *              lines you can read in the source code of function.
 *
 *  - $parameters['javascript_function_change'] The source code as block
 *    string with all javascript code to run autocomplete field.
 *    string - The source code javascript into a string.
 *    default - A lot of lines of source code into a string, please this
 *              lines you can read in the source code of function.
 *
 *  - $parameters['javascript_document_ready'] Boolean, set the
 *    javascript sourcecode to run with the document is ready. By
 *    default is true.
 *    true  - Set to run when document is ready.
 *    false - Not set to run.
 *
 *  - $parameters['javascript_tags'] Boolean, print the html tags for
 *    javascript. By default is true.
 *    true  - Print the javascript tags.
 *    false - Doesn't print the tags.
 *
 *  - $parameters['javascript_tags'] Boolean, print the html tags for
 *    javascript. By default is true.
 *    true  - Print the javascript tags.
 *    false - Doesn't print the tags.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_agent_autocomplete_input($parameters) {
	global $config;
	
	//Normalize and extract the data from $parameters
	//------------------------------------------------------------------
	$return = false; //Default value
	if (isset($parameters['return'])) {
		$return = $parameters['return'];
	}
	
	
	$input_name = uniqid('agent_autocomplete_'); //Default value
	if (isset($parameters['input_name'])) {
		$input_name = $parameters['input_name'];
	}
	
	
	$input_id = 'text-' . $input_name; //Default value
	if (isset($parameters['input_id'])) {
		$input_id = $parameters['input_id'];
	}
	
	
	$selectbox_group = ''; //Default value
	if (isset($parameters['selectbox_group'])) {
		$selectbox_group = $parameters['selectbox_group'];
	}
	
	
	//Default value
	$icon_image = html_print_image('images/search_agent.png', true, false, true);
	if (isset($parameters['icon_image'])) {
		$icon_image = $parameters['icon_image'];
	}
	
	
	$value = ''; //Default value
	if (isset($parameters['value'])) {
		$value = $parameters['value'];
	}
	
	
	$show_helptip = true; //Default value
	if (isset($parameters['show_helptip'])) {
		$show_helptip = $parameters['show_helptip'];
	}
	
	
	$helptip_text = __("Type at least two characters to search."); //Default value
	if (isset($parameters['helptip_text'])) {
		$helptip_text = $parameters['helptip_text'];
	}
	
	
	$use_hidden_input_idagent = false; //Default value
	if (isset($parameters['use_hidden_input_idagent'])) {
		$use_hidden_input_idagent = $parameters['use_hidden_input_idagent'];
	}
	
	
	$print_hidden_input_idagent = false; //Default value
	if (isset($parameters['print_hidden_input_idagent'])) {
		$print_hidden_input_idagent = $parameters['print_hidden_input_idagent'];
	}
	
	
	$hidden_input_idagent_name = uniqid('agent_autocomplete_idagent_'); //Default value
	if (isset($parameters['hidden_input_idagent_name'])) {
		$hidden_input_idagent_name = $parameters['hidden_input_idagent_name'];
	}
	
	
	$hidden_input_idagent_id = 'hidden-' . $input_name; //Default value
	if (isset($parameters['hidden_input_idagent_id'])) {
		$hidden_input_idagent_id = $parameters['hidden_input_idagent_id'];
	}
	
	$hidden_input_idagent_value = (int)get_parameter($hidden_input_idagent_name, 0); //Default value
	if (isset($parameters['hidden_input_idagent_value'])) {
		$hidden_input_idagent_value = $parameters['hidden_input_idagent_value'];
	}
	
	
	$size = 30; //Default value
	if (isset($parameters['size'])) {
		$size = $parameters['size'];
	}
	
	
	$maxlength = 100; //Default value
	if (isset($parameters['maxlength'])) {
		$maxlength = $parameters['maxlength'];
	}
	
	
	$disabled = false; //Default value
	if (isset($parameters['disabled'])) {
		$disabled = $parameters['disabled'];
	}
	
	
	$selectbox_id = 'id_agent_module'; //Default value
	if (isset($parameters['selectbox_id'])) {
		$selectbox_id = $parameters['selectbox_id'];
	}
	
	
	$add_none_module = true; //Default value
	if (isset($parameters['add_none_module'])) {
		$add_none_module = $parameters['add_none_module'];
	}
	
	
	$none_module_text = '--'; //Default value
	if (isset($parameters['none_module_text'])) {
		$none_module_text = $parameters['none_module_text'];
	}
	
	
	$print_input_server = false; //Default value
	if (isset($parameters['print_input_server'])) {
		$print_input_server = $parameters['print_input_server'];
	}
	
	
	$print_input_id_server = false; //Default value
	if (isset($parameters['print_input_id_server'])) {
		$print_input_id_server = $parameters['print_input_id_server'];
	}
	
	
	$use_input_server = false; //Default value
	if (isset($parameters['use_input_server'])) {
		$use_input_server = $parameters['use_input_server'];
	}
	
	
	$use_input_id_server = false; //Default value
	if (isset($parameters['use_input_id_server'])) {
		$use_input_id_server = $parameters['use_input_id_server'];
	}
	
	
	$input_server_name = uniqid('server_'); //Default value
	if (isset($parameters['input_server_name'])) {
		$input_server_name = $parameters['input_server_name'];
	}
	
	
	$input_id_server_name = uniqid('server_'); //Default value
	if (isset($parameters['input_id_server_name'])) {
		$input_id_server_name = $parameters['input_id_server_name'];
	}
	
	
	$input_server_id = 'hidden-' . $input_server_name; //Default value
	if (isset($parameters['input_server_id'])) {
		$input_server_id = $parameters['input_server_id'];
	}
	
	
	$input_id_server_id = 'hidden-' . $input_id_server_name; //Default value
	if (isset($parameters['input_id_server_id'])) {
		$input_id_server_id = $parameters['input_id_server_id'];
	}
	
	
	$input_server_value = ''; //Default value
	if (isset($parameters['input_server_value'])) {
		$input_server_value = $parameters['input_server_value'];
	}
	
	
	$input_id_server_value = ''; //Default value
	if (isset($parameters['input_id_server_value'])) {
		$input_id_server_value = $parameters['input_id_server_value'];
	}

	$from_ux_transaction = ''; //Default value
	if (isset($parameters['from_ux'])) {
		$from_ux_transaction = $parameters['from_ux'];
	}

	$from_wux_transaction = ''; //Default value
	if (isset($parameters['from_wux'])) {
		$from_wux_transaction = $parameters['from_wux'];
	}

	$cascade_protection = false; //Default value
	if (isset($parameters['cascade_protection'])) {
		$cascade_protection = $parameters['cascade_protection'];
	}
	
	$metaconsole_enabled = false; //Default value
	if (isset($parameters['metaconsole_enabled'])) {
		$metaconsole_enabled = $parameters['metaconsole_enabled'];
	}
	else {
		// If metaconsole_enabled param is not setted then pick source configuration
		if (defined('METACONSOLE'))
			$metaconsole_enabled = true;
		else
			$metaconsole_enabled = false;
	}

	$get_only_string_modules = false;
	if (isset($parameters['get_only_string_modules'])) {
		$get_only_string_modules = true;
	}
	
	$spinner_image = html_print_image('images/spinner.gif', true, false, true);
	if (isset($parameters['spinner_image'])) {
		$spinner_image = $parameters['spinner_image'];
	}
	
	
	// Javascript configurations
	//------------------------------------------------------------------
	$javascript_ajax_page = ui_get_full_url('ajax.php', false, false, false, false); //Default value
	if (isset($parameters['javascript_ajax_page'])) {
		$javascript_ajax_page = $parameters['javascript_ajax_page'];
	}
	
	
	
	$javascript_function_action_after_select = ''; //Default value
	$javascript_function_action_after_select_js_call = ''; //Default value
	if (isset($parameters['javascript_function_action_after_select'])) {
		$javascript_function_action_after_select = $parameters['javascript_function_action_after_select'];
		$javascript_function_action_after_select_js_call =
			$javascript_function_action_after_select . '();';
	}
	
	
	
	if (isset($parameters['javascript_function_action_after_select_js_call'])) {
		if ($javascript_function_action_after_select_js_call !=
			$parameters['javascript_function_action_after_select_js_call']) {
			$javascript_function_action_after_select_js_call =
				$parameters['javascript_function_action_after_select_js_call'];
		}
	}
	
	
	
	$javascript_function_action_into_source = ''; //Default value
	$javascript_function_action_into_source_js_call = ''; //Default value
	if (isset($parameters['javascript_function_action_into_source'])) {
		$javascript_function_action_into_source = $parameters['javascript_function_action_into_source'];
		$javascript_function_action_into_source_js_call =
			$javascript_function_action_into_source . '();';
	}
	
	
	
	if (isset($parameters['javascript_function_action_into_source_js_call'])) {
		if ($javascript_function_action_into_source_js_call !=
			$parameters['javascript_function_action_into_source_js_call']) {
			$javascript_function_action_into_source_js_call =
				$parameters['javascript_function_action_into_source_js_call'];
		}
	}
	
	
	
	$javascript = true; //Default value
	if (isset($parameters['javascript'])) {
		$javascript = $parameters['javascript'];
	}
	
	$get_order_json = false;
	if (isset($parameters['get_order_json'])) {
		$get_order_json = true;
	}
	
	$javascript_is_function_select = false; //Default value
	if (isset($parameters['javascript_is_function_select'])) {
		$javascript_is_function_select = $parameters['javascript_is_function_select'];
	}
	
	
	
	$javascript_name_function_select = 'function_select_' . $input_name; //Default value
	if (isset($parameters['javascript_name_function_select'])) {
		$javascript_name_function_select = $parameters['javascript_name_function_select'];
	}

	if ($from_ux_transaction != "") {
		$javascript_code_function_select = '
		function function_select_' . $input_name . '(agent_name) {
			$("#' . $selectbox_id . '").empty();
			
			var inputs = [];
			inputs.push ("id_agent=" + $("#' . $hidden_input_idagent_id . '").val());
			inputs.push ("get_agent_transactions=1");
			inputs.push ("page=enterprise/include/ajax/ux_transaction.ajax");
			
			jQuery.ajax ({
				data: inputs.join ("&"),
				type: "POST",
				url: action="' . $javascript_ajax_page . '",
				dataType: "json",
				success: function (data) {
					if (data) {
						$("#' . $selectbox_id . '").append ($("<option value=0>None</option>"));
						jQuery.each (data, function (id, value) {
							$("#' . $selectbox_id . '").append ($("<option value=" + id + ">" + value + "</option>"));
						});
					}
				}
			});
			
			return false;
		}
		';
	}
	elseif ($from_wux_transaction != "") {
		$javascript_code_function_select = '
		function function_select_' . $input_name . '(agent_name) {
			$("#' . $selectbox_id . '").empty();
			
			var inputs = [];
			inputs.push ("id_agent=" + $("#' . $hidden_input_idagent_id . '").val());
			inputs.push ("get_agent_transactions=1");
			inputs.push ("page=enterprise/include/ajax/wux_transaction.ajax");
			
			jQuery.ajax ({
				data: inputs.join ("&"),
				type: "POST",
				url: action="' . $javascript_ajax_page . '",
				dataType: "json",
				success: function (data) {
					if (data) {
						$("#' . $selectbox_id . '").append ($("<option value=0>None</option>"));
						jQuery.each (data, function (id, value) {
							$("#' . $selectbox_id . '").append ($("<option value=" + id + ">" + value + "</option>"));
						});
					}
				}
			});
			
			return false;
		}
		';
	}
	else {
		$javascript_code_function_select = '
		function function_select_' . $input_name . '(agent_name) {
			
			$("#' . $selectbox_id . '").empty ();
			
			var inputs = [];
			inputs.push ("agent_name=" + agent_name);
			inputs.push ("delete_pending=0");
			inputs.push ("get_agent_modules_json=1");
			inputs.push ("page=operation/agentes/ver_agente");
			
			if (' . ((int) !$metaconsole_enabled) . ') {
				inputs.push ("force_local_modules=1");
			}

			if (' . ((int) $get_order_json) . ') {
				inputs.push ("get_order_json=1");
			}

			if (' . ((int) $get_only_string_modules) . ') {
				inputs.push ("get_only_string_modules=1");
			}
			
			if (' . ((int)$metaconsole_enabled) . ') {
				if ((' . ((int)$use_input_server) . ')
						|| (' . ((int)$print_input_server) . ')) {
					inputs.push ("server_name=" + $("#' . $input_server_id . '").val());
				}
				
				if ((' . ((int)$use_input_id_server) . ')
						|| (' . ((int)$print_input_id_server) . ')) {
					inputs.push ("server_id=" + $("#' . $input_id_server_id . '").val());
				}
				
			}
			
			if ((' . ((int)$print_hidden_input_idagent) . ')
				|| (' . ((int)$use_hidden_input_idagent) . ')) {
				
				inputs.push ("id_agent=" + $("#' . $hidden_input_idagent_id . '").val());
			}
			
			jQuery.ajax ({
				data: inputs.join ("&"),
				type: "POST",
				url: action="' . $javascript_ajax_page . '",
				dataType: "json",
				success: function (data) {
					if (' . ((int)$add_none_module) . ') {
						$("#' . $selectbox_id . '")
							.append($("<option></option>")
							.attr("value", 0).text("' . $none_module_text . '"));
					}
					
					jQuery.each (data, function(i, val) {
						s = js_html_entity_decode(val["nombre"]);
						$("#' . $selectbox_id . '")
							.append ($("<option></option>")
							.attr("value", val["id_agente_modulo"]).text (s));
					});
					if('. (int)$cascade_protection .' == 0){
						$("#' . $selectbox_id . '").enable();
					}
					$("#' . $selectbox_id . '").fadeIn ("normal");
				}
			});
			
			return false;
		}
		';
	}
	
	if (isset($parameters['javascript_code_function_select'])) {
		$javascript_code_function_select = $parameters['javascript_code_function_select'];
	}
	
	
	
	//============ INIT javascript_change_ajax_params ==================
	//Default value
	$javascript_page = 'include/ajax/agent';
	if (isset($parameters['javascript_page'])) {
		$javascript_page = $parameters['javascript_page'];
	}
	
	
	
	$javascript_change_ajax_params_original = array(
		'page' => '"' . $javascript_page . '"',
		'search_agents' => 1,
		'id_group' => 'function() {
				var group_id = 0;
				
				if (' . ((int)!empty($selectbox_group)) . ') {
					group_id = $("#' . $selectbox_group . '").val();
				}
				
				return group_id;
			}',
		'q' => 'term');
	
	if (!$metaconsole_enabled) {
		$javascript_change_ajax_params_original['force_local'] = 1;
	}
	
	if (isset($parameters['javascript_change_ajax_params'])) {
		$javascript_change_ajax_params = array();
		
		$found_page = false;
		foreach ($parameters['javascript_change_ajax_params'] as $key => $param_ajax) {
			if ($key == 'page') {
				$found_page = true;
				if ($javascript_page != $param_ajax) {
					$javascript_change_ajax_params['page'] = $param_ajax;
				}
				else {
					$javascript_change_ajax_params['page'] = $javascript_page;
				}
			}
			else {
				$javascript_change_ajax_params[$key] = $param_ajax;
			}
		}
		
		if (!$found_page) {
			$javascript_change_ajax_params['page'] = $javascript_page;
		}
	}
	else {
		$javascript_change_ajax_params = $javascript_change_ajax_params_original;
	}
	$first = true;
	$javascript_change_ajax_params_text = 'var data_params = {';
	foreach ($javascript_change_ajax_params as $key => $param_ajax) {
		if (!$first) $javascript_change_ajax_params_text .= ",\n";
		else $first = false;
		$javascript_change_ajax_params_text .= '"' . $key . '":' . $param_ajax;
	}
	$javascript_change_ajax_params_text .= '};';
	//============ END javascript_change_ajax_params ===================
	
	
	
	$javascript_function_change = ''; //Default value
	$javascript_function_change .='
		function set_functions_change_autocomplete_' . $input_name . '() {
			var cache_' . $input_name . ' = {};
			
			$("#' . $input_id . '").autocomplete({
				minLength: 2,
				source: function( request, response ) {
					var term = request.term; //Word to search
					' . $javascript_change_ajax_params_text . '
					var groupId = data_params.id_group();

					// Index cache by group Id
					if (cache_' . $input_name . '[groupId] == null) {
						cache_' . $input_name . '[groupId] = {};
					}
					
					//Set loading
					$("#' . $input_id . '")
						.css("background","url(\"' . $spinner_image . '\") right center no-repeat");
					
					//Function to call when the source
					if (' . ((int)!empty($javascript_function_action_into_source_js_call)) . ') {
						' . $javascript_function_action_into_source_js_call . '
					}
					
					//==== CACHE CODE ==================================
					//Check the cache
					var found = false;
					if (term in cache_' . $input_name . '[groupId]) {
						response(cache_' . $input_name . '[groupId][term]);
						
						//Set icon
						$("#' . $input_id . '")
							.css("background","url(\"' . $icon_image . '\") right center no-repeat");
						return;
					}
					else {
						//Check if other terms cached start with same
						//letters.
						//TODO: At the moment DISABLED CODE
						/*
						for (i = 1; i < term.length; i++) {
							var term_match = term.substr(0, term.length - i);
							
							$.each(cache_' . $input_name . '[groupId], function (oldterm, olddata) {
								var pattern = new RegExp("^" + term_match + ".*","gi");
								
								if (oldterm.match(pattern)) {
									response(cache_' . $input_name . '[groupId][oldterm]);
									
									found = true;
									
									return;
								}
							});
							
							if (found) {
								break;
							}
						}
						*/
					}
					//==================================================
					
					
					if (found) {
						//Set icon
						$("#' . $input_id . '")
							.css("background","url(\"' . $icon_image . '\") right center no-repeat");
						
						select_item_click = 0;
						
						return;
					}
					
					jQuery.ajax ({
						data: data_params,
						type: "POST",
						url: action="' . $javascript_ajax_page . '",
						dataType: "json",
						success: function (data) {
								cache_' . $input_name . '[groupId][term] = data; //Save the cache
								
								response(data);
								
								//Set icon
								$("#' . $input_id . '")
									.css("background",
										"url(\"' . $icon_image . '\") right center no-repeat");
								
								select_item_click = 0;
								
								return;
							}
						});
					
					return;
				},
				//---END source-----------------------------------------
				
				
				select: function( event, ui ) {
					var agent_name = ui.item.alias;
					var agent_id = ui.item.id;
					var server_name = "";
					var server_id = "";
					
					
					if (' . ((int)$metaconsole_enabled) . ') {
						server_name = ui.item.server;
					}
					else {
						server_name = ui.item.ip;
					}
					
					
					if ((' . ((int)$use_input_id_server) . ')
						|| (' . ((int)$print_input_id_server) . ')) {
						server_id = ui.item.id_server;
					}
					
					
					
					//Put the name
					$(this).val(agent_name);
					
					if ((' . ((int)$print_hidden_input_idagent) . ')
						|| (' . ((int)$use_hidden_input_idagent) . ')) {
						$("#' . $hidden_input_idagent_id . '").val(agent_id);
					}
					
					//Put the server id into the hidden input
					if ((' . ((int)$use_input_server) . ')
						|| (' . ((int)$print_input_server) . ')) {
						$("#' . $input_server_id . '").val(server_name);
					}
					
					//Put the server id into the hidden input
					if ((' . ((int)$use_input_id_server) . ')
						|| (' . ((int)$print_input_id_server) . ')) {
						$("#' . $input_id_server_id . '").val(server_id);
					}
					
					//Call the function to select (example fill the modules)
					if (' . ((int)$javascript_is_function_select) . ') {
						' . $javascript_name_function_select . '(agent_name);
					}
					
					//Function to call after the select
					if (' . ((int)!empty($javascript_function_action_after_select_js_call)) . ') {
						' . $javascript_function_action_after_select_js_call . '
					}
					
					select_item_click = 1;
					
					return false;
				}
				})
			.data("ui-autocomplete")._renderItem = function( ul, item ) {
				if (item.ip == "") {
					text = "<a>" + item.alias+ "</a>";
				}
				else {
					text = "<a>" + item.alias
						+ "<br><span style=\"font-size: 70%; font-style: italic;\">IP:" + item.ip + "</span></a>";
				}
				
				switch (item.filter) {
					default:
					case \'agent\':
						return $("<li style=\'background: #DFFFC4;\'></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
					case \'address\':
						return $("<li style=\'background: #F7CFFF;\'></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
					case \'description\':
						return $("<li style=\'background: #FEFCC6;\'></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
					case \'alias\':
						return $("<li style=\'background: #a8e7eb;\'></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
				}
				
				
			};
			
			//Force the size of autocomplete
			$(".ui-autocomplete").css("max-height", "100px");
			$(".ui-autocomplete").css("overflow-y", "auto");
			/* prevent horizontal scrollbar */
			$(".ui-autocomplete").css("overflow-x", "hidden");
			/* add padding to account for vertical scrollbar */
			$(".ui-autocomplete").css("padding-right", "20px");
			
			//Force to style of items
			$(".ui-autocomplete").css("text-align", "left");
		}';
	
	if (isset($parameters['javascript_function_change'])) {
		$javascript_function_change = $parameters['javascript_function_change'];
	}
	
	$javascript_document_ready = true;//Default value
	if (isset($parameters['javascript_document_ready'])) {
		$javascript_document_ready = $parameters['javascript_document_ready'];
	}
	
	$javascript_tags = true;//Default value
	if (isset($parameters['javascript_tags'])) {
		$javascript_tags = $parameters['javascript_tags'];
	}
	
	$disabled_javascript_on_blur_function = false;//Default value
	if (isset($parameters['disabled_javascript_on_blur_function'])) {
		$disabled_javascript_on_blur_function = $parameters['disabled_javascript_on_blur_function'];
	}
	
	$javascript_on_blur_function_name = 'function_on_blur_' . $input_name;//Default value
	if (isset($parameters['javascript_on_blur_function_name'])) {
		$javascript_on_blur_function_name = $parameters['javascript_on_blur_function_name'];
	}
	
	$check_only_empty_javascript_on_blur_function = false;//Default value
	if (isset($parameters['check_only_empty_javascript_on_blur_function'])) {
		$check_only_empty_javascript_on_blur_function = $parameters['check_only_empty_javascript_on_blur_function'];
	}
	
	//Default value
	$javascript_on_blur = '
		/*
		This function is a callback when the autocomplete agent
		input lost the focus.
		*/
		function ' . $javascript_on_blur_function_name . '() {
			input_value = $("#' . $input_id . '").val();
			
			if (input_value.length == 0) {
				if ((' . ((int)$print_hidden_input_idagent) . ')
					|| (' . ((int)$use_hidden_input_idagent) . ')) {
					$("#' . $hidden_input_idagent_id . '").val(0);
				}
				
				//Put the server id into the hidden input
				if ((' . ((int)$use_input_server) . ')
					|| (' . ((int)$print_input_server) . ')) {
					$("#' . $input_server_id . '").val("");
				}
				
				//Put the server id into the hidden input
				if ((' . ((int)$use_input_id_server) . ')
					|| (' . ((int)$print_input_id_server) . ')) {
					$("#' . $input_id_server_id . '").val("");
				}
				
				return;
			}
			
			if (' . ((int)$check_only_empty_javascript_on_blur_function) . ') {
				return
			}
			
			
			if (select_item_click) {
				return;
			}
			
			//Set loading
			$("#' . $input_id . '")
				.css("background",
					"url(\"' . $spinner_image . '\") right center no-repeat");
			
			
			
			var term = input_value; //Word to search
			
			' . $javascript_change_ajax_params_text . '
			
			if (' . ((int) !$metaconsole_enabled) . ') {
				data_params[\'force_local\'] = 1;
			}
			
			jQuery.ajax ({
				data: data_params,
				type: "POST",
				url: action="' . $javascript_ajax_page . '",
				dataType: "json",
				success: function (data) {
						if (data.length == 0) {
							//Set icon
							$("#' . $input_id . '")
								.css("background",
									"url(\"' . $icon_image . '\") right center no-repeat");
							
							return;
						}
						
						var agent_name = data[0].name;
						var agent_id = data[0].id;
						var server_name = "";
						var server_id = "";
						
						if (' . ((int)$metaconsole_enabled) . ') {
							server_name = data[0].server;
						}
						else {
							server_name = data[0].ip;
						}
						
						if ((' . ((int)$use_input_id_server) . ')
						|| (' . ((int)$print_input_id_server) . ')) {
							server_id = data[0].id_server;
						}
						
						if ((' . ((int)$print_hidden_input_idagent) . ')
							|| (' . ((int)$use_hidden_input_idagent) . ')) {
							$("#' . $hidden_input_idagent_id . '").val(agent_id);
						}
						
						//Put the server id into the hidden input
						if ((' . ((int)$use_input_server) . ')
							|| (' . ((int)$print_input_server) . ')) {
							$("#' . $input_server_id . '").val(server_name);
						}
						
						//Put the server id into the hidden input
						if ((' . ((int)$use_input_id_server) . ')
							|| (' . ((int)$print_input_id_server) . ')) {
							$("#' . $input_id_server_id . '").val(server_id);
						}
						
						//Call the function to select (example fill the modules)
						if (' . ((int)$javascript_is_function_select) . ') {
							' . $javascript_name_function_select . '(agent_name);
						}
						
						//Function to call after the select
						if (' . ((int)!empty($javascript_function_action_after_select_js_call)) . ') {
							' . $javascript_function_action_after_select_js_call . '
						}
						
						//Set icon
						$("#' . $input_id . '")
							.css("background",
								"url(\"' . $icon_image . '\") right center no-repeat");
						
						return;
					}
				});
		}
		';
	if (isset($parameters['javascript_on_blur'])) {
		$javascript_on_blur = $parameters['javascript_on_blur'];
	}
	
	//------------------------------------------------------------------
	
	$html = '';
	
	
	$attrs = array();
	$attrs['style'] =
		'background: url(' . $icon_image . ') no-repeat right;';
	
	if (!$disabled_javascript_on_blur_function) {
		$attrs['onblur'] = $javascript_on_blur_function_name . '()';
	}
	
	$html = html_print_input_text_extended($input_name, $value,
		$input_id, $helptip_text, $size, $maxlength, $disabled, '', $attrs, true);
	if ($show_helptip) {
		$html .= ui_print_help_tip ($helptip_text, true);
	}
	
	if ($print_hidden_input_idagent) {
		$html .= html_print_input_hidden_extended($hidden_input_idagent_name,
			$hidden_input_idagent_value, $hidden_input_idagent_id, true);
	}
	
	if ($print_input_server) {
		$html .= html_print_input_hidden_extended($input_server_name,
			$input_server_value, $input_server_id, true);
	}
	
	if ($print_input_id_server) {
		$html .= html_print_input_hidden_extended($input_id_server_name,
			$input_id_server_value, $input_id_server_id, true);
	}
	
	//Write the javascript
	if ($javascript) {
		if ($javascript_tags) {
			$html .= '<script type="text/javascript">
				/* <![CDATA[ */';
		}
		
		$html .= 'var select_item_click = 0;' . "\n";
		
		$html .= $javascript_function_change;
		if ($javascript_is_function_select) {
			$html .= $javascript_code_function_select;
		}
		
		$html .= $javascript_on_blur;
		
		if ($javascript_document_ready) {
			$html .= '$(document).ready (function () {
				set_functions_change_autocomplete_' . $input_name . '();
				});';
		}
		
		if ($javascript_tags) {
			$html .= '/* ]]> */
				</script>';
		}
	}
	
	if ($return) {
		return $html;
	}
	else {
		echo $html;
	}
}


/**
 * Return error strings (title and message) for each error code
 * 
 * @param string error code
 */
function ui_get_error ($error_code = '') {
	// FIXME: Deprecated. Pandora shouldn't go inside this
	return array(
		'title' => __('Unhandled error'),
		'message' => __('An unhandled error occurs')
	);
}

function ui_include_time_picker($echo_tags = false) {
	if (is_ajax () || $echo_tags) {
		echo '<script type="text/javascript" src="' .
			ui_get_full_url(false, false, false, false) .
			'include/javascript/jquery.ui-timepicker-addon.js' . '"></script>';
	}
	else {
		ui_require_jquery_file ("ui-timepicker-addon");
	}
	
	if (file_exists('include/javascript/i18n/jquery-ui-timepicker-' . substr(get_user_language(), 0, 2) . '.js')) {
		echo '<script type="text/javascript" src="' .
			ui_get_full_url('include/javascript/i18n/jquery-ui-timepicker-' . substr(get_user_language(), 0, 2) . '.js', false, false, false) . '"></script>';
	}
}

function ui_print_module_string_value($value, $id_agente_module,
	$current_interval, $module_name = null) {
	
	global $config;
	
	if (is_null($module_name))
		$module_name = modules_get_agentmodule_name($id_agente_module);
	
	$id_type_web_content_string = db_get_value('id_tipo',
		'ttipo_modulo', 'nombre', 'web_content_string');
	
	$is_web_content_string = (bool)db_get_value_filter('id_agente_modulo',
		'tagente_modulo',
		array('id_agente_modulo' => $id_agente_module,
			'id_tipo_modulo' => $id_type_web_content_string));
	
	//Fixed the goliat sends the strings from web
	//without HTML entities
	if ($is_web_content_string) {
		$value = io_safe_input($value);
	}


	$is_snapshot = is_snapshot_data ($module["datos"]);
	$is_large_image = is_text_to_black_string ($module["datos"]);
	if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {


		$row[7] = ui_get_snapshot_image($link, $is_snapshot) . '&nbsp;&nbsp;';
	}

	$is_snapshot = is_snapshot_data($value);
	$is_large_image = is_text_to_black_string ($value);
	if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {
		$link = ui_get_snapshot_link( array(
			'id_module' => $id_agente_module,
			'last_data' => $value,
			'interval' => $current_interval,
			'module_name' => $module_name,
			'timestamp' => db_get_value('timestamp', 'tagente_estado', 'id_agente_modulo', $id_agente_module)
		));
		$salida = ui_get_snapshot_image($link, $is_snapshot) . '&nbsp;&nbsp;';
	} else {
		$sub_string = substr(io_safe_output($value), 0, 12);
		if ($value == $sub_string) {
			if ($value == 0 && !$sub_string) {
				$salida = 0;
			}
			else {
				$salida = $value;
			}
		}
		else {
			//Fixed the goliat sends the strings from web
			//without HTML entities
			if ($is_web_content_string) {
				$sub_string = substr($value, 0, 12);
			}
			else {
				//Fixed the data from Selenium Plugin
				if ($value != strip_tags($value)) {
					$value = io_safe_input($value);
					$sub_string = substr($value, 0, 12);
				}
				else {
					$sub_string = substr(io_safe_output($value),0, 12);
				}
			}
			
			
			
			if ($value == $sub_string) {
				$salida = $value;
			}
			else {
				$value = preg_replace ('/</', '&lt;', $value);
				$value = preg_replace ('/>/', '&gt;', $value);
				$value = preg_replace ('/\n/i','<br>',$value);
				$value = preg_replace ('/\s/i','&nbsp;',$value);
				
				$title_dialog =
					modules_get_agentmodule_agent_alias($id_agente_module) .
					" / " . $module_name;
				$salida = "<div " .
					"id='hidden_value_module_" . $id_agente_module . "'
					style='display: none; width: 100%; height: 100%; overflow: scroll; padding: 10px; font-size: 14px; line-height: 16px; font-family: mono,monospace; text-align: left' title='" . $title_dialog . "'>" .
					$value .
					"</div>" . 
					"<span " .
					"id='value_module_" . $id_agente_module . "'
					style='white-space: nowrap;'>" . 
					'<span id="value_module_text_' . $id_agente_module . '">' .
						$sub_string . '</span> ' .
					"<a href='javascript: toggle_full_value(" . $id_agente_module . ")'>" .
						html_print_image("images/zoom.png", true) . "</a>" . "</span>";
			}
		}
	}
	
	return $salida;
}

/**
* Displays a tag list
*/
function ui_print_tags_view($title = '', $tags = array()) {
	if (!empty($title)){
		$tv .= '<div class="tag-wrapper">';
		$tv .= '<h3>' . $title . '</h3>';
	} else {
		$tv .= '<div class="tag-wrapper" style="padding-top: 10px">';
	}
	
	foreach ($tags as $tag) {
		$tv .= '<div class=pandora-tag>';
			$tv .= '<span class=pandora-tag-title>';
				$tv .= $tag['title'];
			$tv .= '</span>';
				
			$tv .= '<span class=pandora-tag-value>';
				$tv .= $tag['value'];
			$tv .= '</span>';
		$tv .= '</div>';
	}
	$tv .= '</div>';
	echo $tv;
}

/**
 * @brief Get the link to open a snapshot into a new page
 *
 * @param Array Params to build the link (see $default_params)
 * @param bool Flag to choose de return value:
 * 		true: Get the four params required in the function of pandora.js winopen_var (js use)
 * 		false: Get an inline winopen_var function call (php user)
 */
function ui_get_snapshot_link($params, $only_params = false) {
	global $config;

	$default_params = array(
		'id_module' => 0, //id_agente_modulo
		'module_name' => '',
		'interval' => 300,
		'timestamp' => 0,
		'id_node' => 0
	);

	// Merge default params with passed params
	$params = array_merge ($default_params, $params);

	// First parameter of js winopeng_var
	$page = $config['homeurl_static'] . "/operation/agentes/snapshot_view.php";

	$url = "$page?" .
		"id=" . $params['id_module'] .
		"&label=" . rawurlencode(urlencode(io_safe_output($params['module_name']))).
		"&id_node=" . $params['id_node'];
	if ($params['timestamp'] != 0) $url .= "&timestamp=" . $parms['timestamp'];
	if ($params['timestamp'] != 0) $url .= "&refr=" . $parms['interval'];

	// Second parameter of js winopeng_var
	$win_handle = dechex(crc32('snapshot_' . $params['id_module']));

	$link_parts = array ($url, $win_handle, 700, 480);

	// Return only the params to js execution
	if ($only_params) return $link_parts;

	// Return the function call to inline js execution
	return "winopeng_var('" . implode("', '", $link_parts) . "')";
}

/**
 * @brief Get the snapshot image with the link to open a snapshot into a new page
 *
 * @param string Built link
 * @param bool Picture image or list image
 *
 * @return string HTML anchor link with image
 */
function ui_get_snapshot_image ($link, $is_image) {
	$image_name = $is_image ? 'photo.png' : 'default_list.png';

	$link = '<a href="javascript:' . $link . '">' .
		html_print_image("images/$image_name", true,
			array('border' => '0',
				'alt' => '',
				'title' => __('Snapshot view'))
		) .
	'</a>';

	return $link;
}

function ui_get_using_system_timezone_warning ($tag = "h3", $return = true) {
	global $config;

	$user_offset = (-get_fixed_offset() / 60) / 60;

	if ($config["timezone"] != date_default_timezone_get()) {
		$message = sprintf(
			__("These controls are using the timezone of the system (%s) instead of yours (%s). The difference with your time zone in hours is %s."),
			$config["timezone"],
			date_default_timezone_get(),
			$user_offset > 0 ? "+" . $user_offset : $user_offset
		);
		return ui_print_info_message($message, '', $return, $tag);
	} else
		return '';
	
}

/**
 * Get the custom docs logo
 *
 * @return string with the path to logo. False if it should not be displayed
 *
 */
function ui_get_docs_logo () {
	global $config;

	// Default logo to open version (enterprise_installed function only works in login status)
	if (!file_exists (ENTERPRISE_DIR . "/load_enterprise.php")) {
		return "images/icono_docs.png";
	}

	if (empty($config['custom_docs_logo'])) return false;
	return 'enterprise/images/custom_general_logos/' . $config['custom_docs_logo'];
}

/**
 * Get the custom support logo
 *
 * @return string with the path to logo. False if it should not be displayed
 *
 */
function ui_get_support_logo () {
	global $config;

	// Default logo to open version (enterprise_installed function only works in login status)
	if (!file_exists (ENTERPRISE_DIR . "/load_enterprise.php")) {
		return "images/icono_support.png";
	}

	if (empty($config['custom_support_logo'])) return false;
	return 'enterprise/images/custom_general_logos/' . $config['custom_support_logo'];
}

/**
 * Get the custom header logo
 *
 * @return string with the path to logo. If it is not set, return the default value
 *
 */
function ui_get_custom_header_logo ($white_bg = false) {
	global $config;

	if (empty($config['enterprise_installed'])) {
		return 'images/pandora_tinylogo_open.png';
	}

	$stored_logo = is_metaconsole()
		? ($white_bg ? $config['meta_custom_logo_white_bg'] : $config['meta_custom_logo'])
		: ($white_bg ? $config['custom_logo_white_bg'] : $config['custom_logo']);
	if (empty($stored_logo)) return 'images/pandora_tinylogo.png';
	return 'enterprise/images/custom_logo/' . $stored_logo;
}

/**
 * Get the central networkmap logo
 *
 * @return string with the path to logo. If it is not set, return the default.
 *
 */
function ui_get_logo_to_center_networkmap () {
	global $config;

	if ((!enterprise_installed()) || empty($config['custom_network_center_logo'])) {
		return 'images/networkmap/bola_pandora_network_maps.png';
	}

	return 'enterprise/images/custom_general_logos/' . $config['custom_support_logo'];
}

/**
 * Get the mobile console login logo
 *
 * @return string with the path to logo. If it is not set, return the default.
 *
 */
function ui_get_mobile_login_icon () {
	global $config;

	if ((!enterprise_installed()) || empty($config['custom_mobile_console_logo'])) {
		return is_metaconsole()
			? "mobile/images/metaconsole_mobile.png"
			: "mobile/images/pandora_mobile_console.png";
	}

	return 'enterprise/images/custom_general_logos/' . $config['custom_mobile_console_logo'];
}

/**
 * Get the favicon
 *
 * @return string with the path to logo. If it is not set, return the default.
 *
 */
function ui_get_favicon () {
	global $config;

	if (empty($config['custom_favicon'])) {
		return !is_metaconsole()
			? "images/pandora.ico"
			: "enterprise/meta/images/favicon_meta.ico";
	}

	return 'images/custom_favicon/' . $config['custom_favicon'];
}

?>
