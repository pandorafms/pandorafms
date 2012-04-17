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

require_once($config['homedir'].'/include/functions_agents.php');
require_once($config['homedir'] . '/include/functions_modules.php');
require_once($config['homedir'] . '/include/functions.php');
require_once($config['homedir'] . '/include/functions_groups.php');
require_once($config['homedir'] . '/include/functions_users.php');
require_once($config['homedir'] . '/include/functions_html.php');


/**
 * Truncate a text to num chars (pass as parameter) and if flag show tooltip is
 * true the html artifal to show the tooltip with rest of text.
 * 
 * @param string $text The text to truncate.
 * @param integer $numChars Number chars (-3 for char "[...]") max the text. 
 * @param boolean $showTextInAToopTip Flag to show the tooltip.
 * @param boolean $return Flag to return as string or not.
 * @param boolean $showTextInTitle Flag to show the text on title.
 * @param string $suffix String at the end of a strimmed string.
 * @param string $style Style associated to the text.
 *
 * @return string Truncated text.
 */
function ui_print_truncate_text($text, $numChars = 25, $showTextInAToopTip = true, $return = true, $showTextInTitle = true, $suffix = '[&hellip;]', $style = false) {
	if ($numChars == 0) {
		if ($return == true) {
			return $text;
		}
		else {
			echo $text;
		}
	} 
	
	$text = io_safe_output($text);
	if ((strlen($text)) > ($numChars)) {
		$half_length = intval(($numChars - 3) / 2); // '/2' because [...] is in the middle of the word.
		$truncateText2 = mb_strimwidth($text, (strlen($text) - $half_length), strlen($text));
		// In case $numChars were an odd number.
		$half_length = $numChars - $half_length - 3;
		$truncateText = mb_strimwidth($text, 0, $half_length) . $suffix;
		$truncateText=$truncateText . $truncateText2;
		
		if ($showTextInTitle) {
			if ($style !== false){
				$truncateText = '<span style="' . $style . '" title="'.$text.'">'.$truncateText.'</span>';
			}
			else{
				$truncateText = '<span title="'.$text.'">'.$truncateText.'</span>';
			}
		}
		if ($showTextInAToopTip) {
			if ($style !== false){
				$truncateText = $truncateText . '<a href="#" class="tip">&nbsp;<span style="' . $style . '">' . $text . '</span></a>';
			}	
			else{
				$truncateText = $truncateText . '<a href="#" class="tip">&nbsp;<span>' . $text . '</span></a>';
			}
		}
		else{
			if ($style !== false){
				$truncateText = '<span style="' . $style . '">'.$truncateText.'</span>';
			}				
		}
	}
	else {
		if ($style !== false){ 
			$truncateText = '<span style="' . $style . '">' . $text . '</span>';
		}
		else{ 
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
		}
	}
	
	if (empty($icon_image)) {
		switch ($class) {
			default:
			case 'info':
				$icon_image = 'images/information.png';
				break;
			case 'error':
				$icon_image = 'images/err.png';
				break;
			case 'suc':
				$icon_image = 'images/suc.png';
				break;
		}
	}
	
	$id = 'info_box_' . uniqid();
	
	$output = '<table cellspacing="0" cellpadding="0" id="' . $id . '" ' . $attributes . '
		class="info_box ' . $class . '" style="' . $force_style . '">
		<tr>
			<td class="icon">' . html_print_image($icon_image, true) . '</td>
			<td class="title"><b>' . $text_title . '</b></td>
			<td class="icon">';
	if (!$no_close_bool) {
		$output .= '<a href="javascript: close_info_box(\'' . $id . '\')">' .
			html_print_image('images/cross.disabled.png', true) . '</a>';
	}
	
	$output .= 	'</td>
		</tr>
		<tr>
			<td></td>
			<td>' . $text_message . '</td>
			<td></td>
		</tr>
		</table>';
	
	if (($first_execution) && (!$no_close_bool)) {
		$first_execution = false;
		
		$output .= '
			<script type="text/javascript">
				function close_info_box(id) {
					$("#" + id).hide();
				}
			</script>
		';
	}
	
	if ($return)
		return $output;
	echo $output;
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
	return ui_print_success_message ($good, $attributes, $return, $tag);
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
		$style = 'style="'.$option["style"].'"';
	}
	
	if (!empty ($option["prominent"])) {
		$prominent = $option["prominent"];
	}
	else {
		$prominent = $config["prominent_time"];
	}
	
	if (!is_numeric ($unixtime)) {
		$unixtime = strtotime ($unixtime);
	}
	
	//prominent_time is either timestamp or comparation
	if ($unixtime <= 0) {
		$title = __('Unknown').'/'.__('Never');
		$data = __('Unknown');
	}
	elseif ($prominent == "timestamp") {
		$title = human_time_comparation ($unixtime);
		$data = date ($config["date_format"], $unixtime);
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
		//Usually tags have title attributes, so by default we add, then fall through to add attributes and data
		$output .= ' title="'.$title.'">' . $data .'</'.$tag.'>';
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
function ui_print_group_icon ($id_group, $return = false, $path = "groups_small", $style='', $link = true) {
	if($id_group > 0)
		$icon = (string) db_get_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	else
		$icon = "world";
	
	if($style == '')
		$style = 'width: 16px; height: 16px;';
		
	$output = '';
	if ($link) 
		$output = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id_group.'">';
	
	if (empty ($icon))
		$output .= '<span title="'. groups_get_name($id_group, true).'">&nbsp;-&nbsp</span>';
	else
		$output .= html_print_image("images/" . $path . "/" . $icon . ".png", true, array("style" => $style, "class" => "bot", "alt" => groups_get_name($id_group, true), "title" => groups_get_name ($id_group, true)));
	
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
	if($id_group > 0)
		$icon = (string) db_get_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	else
		$icon = "world";
	
	if($style == '')
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
function ui_print_os_icon ($id_os, $name = true, $return = false, $apply_skin = true, $networkmap = false, $only_src = false, $relative = false) {
	$subfolter = 'os_icons';
	if ($networkmap) {
		$subfolter = 'networkmap';
	}
	
	$icon = (string) db_get_value ('icon_name', 'tconfig_os', 'id_os', (int) $id_os);
	$os_name = get_os_name ($id_os);
	if (empty ($icon)) {
		if ($only_src) {
			$output = html_print_image("images/".$subfolter."/unknown.png", false, false, true, $relative);
		}
		else {
			return "-";
		}
	}
	
	if ($apply_skin) {
		if ($only_src) {
			$output = html_print_image("images/".$subfolter."/".$icon, true, false, true, $relative);
		}
		else {
			$output = html_print_image("images/".$subfolter."/".$icon, true, array("alt" => $os_name, "title" => $os_name), false, $relative);
		}
	}
	else
		//$output = "<img src='images/os_icons/" . $icon . "' alt='" . $os_name . "' title='" . $os_name . "'>";
		$output = "images/".$subfolter."/" . $icon;
	
	if ($name === true) {
		$output .= ' - '.$os_name;
	}
	
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Prints an agent name with the correct link
 * 
 * @param int Agent id
 * @param bool Whether to return the string or echo it too
 * @param int Now uses styles to accomplish this
 * @param string Style of name in css.
 * 
 * @return string HTML with agent name and link
 */
function ui_print_agent_name ($id_agent, $return = false, $cutoff = 0, $style = '', $cutname = false) {
	$agent_name = (string) agents_get_name ($id_agent);
	$agent_name_full = $agent_name;
	if ($cutname) {
		$agent_name = ui_print_truncate_text($agent_name, $cutoff, true, true, true, '[&hellip;]', $style);
	}
	$output = '<a style="' . $style . '" href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'" title="'.$agent_name_full.'"><b><span style="'.$style.'">'.$agent_name.'</span></b></a>';
	
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
function ui_format_alert_row ($alert, $compound = false, $agent = true, $url = '', $agent_style = false) {
	
	global $config;
	
	$actionText = "";
	require_once ("include/functions_alerts.php");
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
	
	// Get agent id
	if ($compound) {
		$id_agent = $alert['id_agent'];
		$description = $alert['description'];
	} 
	else {
		$id_agent = modules_get_agentmodule_agent ($alert['id_agent_module']);
		$template = alerts_get_alert_template ($alert['id_alert_template']);
		$description = io_safe_output($template['name']);
	}
	$data = array ();
	
	if (($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) && (!$compound)) {
		$policyInfo = policies_is_alert_in_policy2($alert['id'], false);
		if ($policyInfo === false)
			$data[$index['policy']] = '';
		else {
			$img = 'images/policies.png';
			
			$data[$index['policy']] = '<a href="?sec=gpolicies&amp;sec2=enterprise/godmode/policies/policies&amp;id=' . $policyInfo['id'] . '">' . 
				html_print_image($img,true, array('title' => $policyInfo['name'])) .
				'</a>';
		}
	}
	else if (($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) && ($compound))
		$data[$index['policy']] = '';
	
	// Standby
	$data[$index['standby']] = '';
	if (isset ($alert["standby"]) && $alert["standby"] == 1) {
		$data[$index['standby']] = html_print_image ('images/bell_pause.png', true, array('title' => __('Standby on')));
	} 
	
	// Force alert execution
	$data[$index['force_execution']] = '';
	if (! $compound) {
		if ($alert["force_execution"] == 0) {
			$data[$index['force_execution']] =
				'<a href="'.$url.'&amp;id_alert='.$alert["id"].'&amp;force_execution=1&refr=60">' . html_print_image("images/target.png", true) . '</a>';
		} 
		else {
			$data[$index['force_execution']] =
				'<a href="'.$url.'&amp;id_alert='.$alert["id"].'&amp;refr=60">' . html_print_image("images/refresh.png", true) . '</a>';
		}
	}
	
	$data[$index['agent_name']] = $disabledHtmlStart;
	if ($compound) {
		if ($agent_style !== false) {
			$data[$index['agent_name']] .= ui_print_agent_name ($id_agent, true, 20, $styleDisabled . " $agent_style");			
		}
		else { 
			$data[$index['agent_name']] .= ui_print_agent_name ($id_agent, true, 20, $styleDisabled);
		}
	} 
	elseif ($agent == 0) {
		$data[$index['module_name']] .= ui_print_truncate_text(modules_get_agentmodule_name ($alert["id_agent_module"]), 30, false, true, true, '[&hellip;]', 'font-size: 7.2pt');
	} 
	else {
		if ($agent_style !== false) {
			$data[$index['agent_name']] .= ui_print_agent_name (modules_get_agentmodule_agent ($alert["id_agent_module"]), true, 20, $styleDisabled . " $agent_style");
		}
		else {
			$data[$index['agent_name']] .= ui_print_agent_name (modules_get_agentmodule_agent ($alert["id_agent_module"]), true, 20, $styleDisabled);		
		}
		$data[$index['module_name']] = ui_print_truncate_text (modules_get_agentmodule_name ($alert["id_agent_module"]), 30, false, true, true, '[&hellip;]', 'font-size: 7.2pt');
	}
	$data[$index['agent_name']] .= $disabledHtmlEnd;
	
	$data[$index['description']] = '';
	if (! $compound) {
		$data[$index['template']] .= '<a class="template_details" href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$template['id'].'">';
		$data[$index['template']] .= html_print_image ('images/zoom.png', true);
		$data[$index['template']] .= '</a> ';
		$actionDefault = db_get_value_sql("SELECT id_alert_action
			FROM talert_templates WHERE id = " . $alert['id_alert_template']);
	}
	else {
		$actionDefault = db_get_value_sql("SELECT id_alert_action FROM talert_compound_actions WHERE id_alert_compound = " . $alert['id']);
	}
	$data[$index['description']] .= $disabledHtmlStart . ui_print_truncate_text (io_safe_input ($description), 35, false, true, true, '[&hellip;]', 'font-size: 7.1pt') . $disabledHtmlEnd;
	
	$actions = alerts_get_alert_agent_module_actions ($alert['id'], false, $compound);
	
	if (!empty($actions)) {
		$actionText = '<div style="margin-left: 10px;"><ul class="action_list">';
		foreach ($actions as $action) {
			$actionText .= '<div><span class="action_name"><li>' . $action['name'];
			if ($action["fires_min"] != $action["fires_max"]){
				$actionText .=  " (".$action["fires_min"] . " / ". $action["fires_max"] . ")";
			}
			$actionText .= '</li></span><br /></div>';
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
	} elseif ($alert["disabled"] > 0) {
		$status = STATUS_ALERT_DISABLED;
		$title = __('Alert disabled');
	} else {
		$status = STATUS_ALERT_NOT_FIRED;
		$title = __('Alert not fired');
	}
	
	$data[$index['status']] = ui_print_status_image($status, $title, true);
	
	if (check_acl ($config["id_user"], $id_group, "LW") == 1) {
		if ($compound) {
			$data[$index['validate']] = html_print_checkbox ("validate_compound[]", $alert["id"], false, true);
		}
		else {
			$data[$index['validate']] = html_print_checkbox ("validate[]", $alert["id"], false, true);
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
	if (mb_strlen($string2, "UTF-8") >  $cutoff){
		$string3 = "...";
	} else {
		$string3 = "";
	}
	
	$font_size_mod = "";
	
	if ($fontsize > 0) {
		$font_size_mod = "style='font-size: ".$fontsize."px'";
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
 * 
 * @return string The help tip
 */
function ui_print_help_icon ($help_id, $return = false) {
	$output = '&nbsp;'.html_print_image ("images/help.png", true, 
		array ("class" => "img_help",
			"title" => __('Help'),
			"onclick" => "open_help ('".$help_id."')"));
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
	
	$filename = $path.$name.'.css';
	
	if (! isset ($config['css']))
		$config['css'] = array ();
	if (isset ($config['css'][$name]))
		return true;
	if (! file_exists ($filename) && ! file_exists ($config['homedir'].'/'.$filename))
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
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function ui_require_javascript_file ($name, $path = 'include/javascript/') {
	global $config;
	
	$filename = $path.$name.'.js';
	
	if (! isset ($config['js']))
		$config['js'] = array ();
	if (isset ($config['js'][$name]))
		return true;
	/* We checks two paths because it may fails on enterprise */
	if (! file_exists ($filename) && ! file_exists ($config['homedir'].'/'.$filename))
		return false;
	
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
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function ui_require_jquery_file ($name, $path = 'include/javascript/') {
	global $config;
	
	$filename = $path.'jquery.'.$name.'.js';
	
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

	if (isset ($config['ignore_callback']) && $config['ignore_callback'] == true) {
		return;
	}
	
	$output = '';
	
	if ($config["refr"] > 0) {
		$query = ui_get_url_refresh (false);
		$output .= '<meta http-equiv="refresh" content="'.$config["refr"].'; URL='.$query.'" />';
	}
	$output .= "\n\t";
	$output .= '<title>Pandora FMS - '.__('the Flexible Monitoring System').'</title>
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<meta name="resource-type" content="document" />
	<meta name="distribution" content="global" />
	<meta name="author" content="Sancho Lerena" />
	<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others" />
	<meta name="keywords" content="pandora, monitoring, system, GPL, software" />
	<meta name="robots" content="index, follow" />
	<link rel="icon" href="images/pandora.ico" type="image/ico" />
	<link rel="shortcut icon" href="images/pandora.ico" type="image/x-icon" />
	<link rel="alternate" href="operation/events/events_rss.php" title="Pandora RSS Feed" type="application/rss+xml" />';

	if ($config["language"] != "en") {
		//Load translated strings - load them last so they overload all the objects
		ui_require_javascript_file ("time_".$config["language"]);
		ui_require_javascript_file ("date".$config["language"]);
		ui_require_javascript_file ("countdown_".$config["language"]);
	}
	$output .= "\n\t";
	
	//Load CSS
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

	//First, if user has assigned a skin then try to use css files of skin subdirectory
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
		if ($exists_skin){
			$skin_path = enterprise_hook('skins_get_skin_path');
			$skin_styles = themes_get_css ($skin_path . 'include/styles/');
			$exists_css = !empty($skin_styles);
		}		
	}
	//If skin's css files exists then add them
	if ($exists_css){
		foreach ($skin_styles as $filename => $name){
			$style = substr ($filename, 0, strlen ($filename) - 4);
			$config['css'][$style] = $skin_path . 'include/styles/' . $filename; 
		}
	} 
	//Otherwise assign default and user's css
	else{
		//User style should go last so it can rewrite common styles
		$config['css'] = array_merge (array (
			"common" => "include/styles/common.css", 
			"menu" => "include/styles/menu.css", 
			"tip", "include/styles/tip.css", 
			$config['style'] => "include/styles/".$config['style'].".css"), $config['css']);
	}	
	
	//We can't load empty and we loaded (conditionally) ie
	$loaded = array ('', 'ie');

	foreach ($config['css'] as $name => $filename) {
		if (in_array ($name, $loaded))
			continue;
		
		array_push ($loaded, $name);
		if (!empty ($config["compact_header"])) {
			$output .= '<style type="text/css">';
			$style = file_get_contents ($config["homedir"]."/".$filename);
			//Replace paths
			$style = str_replace (array ("@import url(", "../../images"), array ("@import url(include/styles/", "images"), $style);
			//Remove comments
			$output .= preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $style);
			$output .= '</style>';
		} else {
			$output .= '<link rel="stylesheet" href="'.$filename.'" type="text/css" />'."\n\t";
		}
	}
	//End load CSS
	
	//Load JS
	if (empty ($config['js'])) {
		$config['js'] = array (); //If it's empty, false or not init set array to empty just in case
	}
	
	//Pandora specific JavaScript should go first
	$config['js'] = array_merge (array ("pandora" => "include/javascript/pandora.js"), $config['js']);
		
	//Load other javascript
	//We can't load empty
	$loaded = array ('');
	foreach ($config['js'] as $name => $filename) {
		if (in_array ($name, $loaded))
			continue;
			
		array_push ($loaded, $name);
		if (!empty ($config["compact_header"])) {
			$output .= '<script type="text/javascript">/* <![CDATA[ */'."\n";
			$output .= file_get_contents ($config["homedir"]."/".$filename);
			$output .= "\n".'/* ]]> */</script>';
		}
		else {
			$output .= '<script type="text/javascript" src="'.$filename.'"></script>'."\n\t";
		}
	}
	//End load JS
			
	//Load jQuery
	if (empty ($config['jquery'])) {
		$config['jquery'] = array (); //If it's empty, false or not init set array to empty just in case
	}
	
	//Pandora specific jquery should go first
	$config['jquery'] = array_merge (array ("jquery" => "include/javascript/jquery.js",
		"pandora" => "include/javascript/jquery.pandora.js"),
		$config['jquery']);
	
		
	//Then add each script as necessary
	$loaded = array ('');
	foreach ($config['jquery'] as $name => $filename) {
		if (in_array ($name, $loaded))
			continue;
		
		array_push ($loaded, $name);
		if (!empty ($config["compact_header"])) {
			$output .= '<script type="text/javascript">/* <![CDATA[ */'."\n";
			$output .= file_get_contents ($config["homedir"]."/".$filename);
			$output .= "\n".'/* ]]> */</script>';
		}
		else {
			$output .= '<script type="text/javascript" src="'.$filename.'"></script>'."\n\t";
		}
	}
	
	
	$output .= '<!--[if gte IE 6]>
	<link rel="stylesheet" href="include/styles/ie.css" type="text/css"/>
	<![endif]-->';

	$output .= $string;

	if (!empty ($config["compact_header"])) {
		$output = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $output);
	}
	
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
	
	if (isset ($config['ignore_callback']) && $config['ignore_callback'] == true) {
		return;
	}
	
	// Show custom background
	$output = '<body'.($config["pure"] ? ' class="pure"' : '').'>';
	
	if (!empty ($config["compact_header"])) {
		require_once ($config["homedir"]."/include/htmlawed.php");
		$htmLawedconfig = array ("valid_xhtml" => 1, "tidy" => -1);
		$output .= htmLawed ($string, $htmLawedconfig);
	} else {
		$output .= $string;
	}

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
 *
 * @return string The pagination div or nothing if no pagination needs to be done
 */
function ui_pagination ($count, $url = false, $offset = 0, $pagination = 0, $return = false, $offset_name = 'offset') {
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
	
	/* 	URL passed render links with some parameter
	 &offset - Offset records passed to next page
	 &counter - Number of items to be blocked 
	 Pagination needs $url to build the base URL to render links, its a base url, like 
	 " http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
	 */
	$block_limit = 15; // Visualize only $block_limit blocks
	if ($count <= $pagination) {
		return false;
	}
	// If exists more registers than I can put in a page, calculate index markers
	$index_counter = ceil ($count /$pagination); // Number of blocks of block_size with data
	$index_page = ceil ($offset / $pagination) - (ceil ($block_limit / 2)); // block to begin to show data;
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
	
	$output = '<div class="pagination">';
	// Show GOTO FIRST button
	$output .= '<a class="pagination go_first" href="'.$url.'&amp;'.$offset_name.'=0">'.html_print_image ("images/go_first.png", true, array ("class" => "bot")).'</a>&nbsp;';
	// Show PREVIOUS button
	if ($index_page > 0) {
		$index_page_prev = ($index_page - (floor ($block_limit / 2))) * $pagination;
		if ($index_page_prev < 0)
			$index_page_prev = 0;
		$output .= '<a class="pagination go_rewind" href="'.$url.'&amp;'.$offset_name.'='.$index_page_prev.'">'.html_print_image ("images/go_previous.png", true, array ("class" => "bot")).'</a>';
	}
	$output .= "&nbsp;&nbsp;";
	// Draw blocks markers
	// $i stores number of page
	for ($i = $inicio_pag; $i < $index_limit; $i++) {
		$inicio_bloque = ($i * $pagination);
		$final_bloque = $inicio_bloque + $pagination;
		if ($final_bloque > $count){ // if upper limit is beyond max, this shouldnt be possible !
			$final_bloque = ($i-1) * $pagination + $count-(($i-1) * $pagination);
		}
		$output .= "<span>";
		
		$inicio_bloque_fake = $inicio_bloque + 1;
		// To Calculate last block (doesnt end with round data,
		// it must be shown if not round to block limit)
		$output .= '<a class="pagination" href="'.$url.'&amp;'.$offset_name.'='.$inicio_bloque.'">';
		if ($inicio_bloque == $offset) {
			$output .= "<b>[ $i ]</b>";
		} else {
			$output .= "[ $i ]";
		}
		$output .= '</a></span>';
	}
	$output .= "&nbsp;&nbsp;";
	// Show NEXT PAGE (fast forward)
	// Index_counter stores max of blocks
	if (($paginacion_maxima == 1) AND (($index_counter - $i) > 0)) {
		$prox_bloque = ($i + ceil ($block_limit / 2)) * $pagination;
		if ($prox_bloque > $count)
			$prox_bloque = ($count -1) - $pagination;
		$output .= '<a class="pagination go_fastforward" href="'.$url.'&amp;'.$offset_name.'='.$prox_bloque.'">'.html_print_image ("images/go_next.png", true, array ("class" => "bot")).'</a>';
		$i = $index_counter;
	}
	// if exists more registers than i can put in a page (defined by $block_size config parameter)
	// get offset for index calculation
	// Draw "last" block link, ajust for last block will be the same
	// as painted in last block (last integer block).	
	if (($count - $pagination) > 0) {
		$myoffset = floor (($count - 1) / $pagination) * $pagination;
		$output .= '<a class="pagination go_last" href="'.$url.'&amp;'.$offset_name.'='.$myoffset.'">'.html_print_image ("images/go_last.png", true, array ("class" => "bot")).'</a>';
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
function ui_print_help_tip ($text, $return = false, $img = 'images/tip.png') {
	$output = '<a href="javascript:" class="tip" >' . html_print_image ($img, true) . '<span>'.$text.'</span></a>';
	
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
			} else {
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
function ui_print_moduletype_icon ($id_moduletype, $return = false, $relative = false, $options = true) {
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
	
	if ($options){
		return html_print_image ($imagepath, $return,
			array ("border" => 0,
				"title" => $type["descripcion"]), false, $relative);
	}
	else{
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
	} else {
		$data .= __("N/A");
	} 

	$data .= " - ";

	if ($max_critical != $min_critical){
		$data .= format_for_graph($max_critical) ."/". format_for_graph ($min_critical);
	} else {
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

define ('STATUS_MODULE_OK', 'module_ok.png');
define ('STATUS_MODULE_CRITICAL', 'module_critical.png');
define ('STATUS_MODULE_WARNING', 'module_warning.png');
define ('STATUS_MODULE_NO_DATA', 'module_no_data.png');

define ('STATUS_AGENT_CRITICAL', 'agent_critical.png');
define ('STATUS_AGENT_WARNING', 'agent_warning.png');
define ('STATUS_AGENT_DOWN', 'agent_down.png');
define ('STATUS_AGENT_OK', 'agent_ok.png');
define ('STATUS_AGENT_NO_DATA', 'agent_no_data.png');

define ('STATUS_ALERT_FIRED', 'alert_fired.png');
define ('STATUS_ALERT_NOT_FIRED', 'alert_not_fired.png');
define ('STATUS_ALERT_DISABLED', 'alert_disabled.png');

define ('STATUS_SERVER_OK', 'server_ok.png');
define ('STATUS_SERVER_DOWN', 'server_down.png');

/**
 * Prints an image representing a status.
 *
 * @param string
 * @param string 
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_status_image ($type, $title = "", $return = false, $options = false) {
	list ($imagepath) = ui_get_status_images_path ();
	
	$imagepath .= "/".$type;
	
	if($options === false) {
		$options = array();
	}
	
	$options['title'] = $title;
	
	return html_print_image ($imagepath, $return, $options);
}

/**
 * Prints a form to search agents.
 *
 * @param array Extra options for the function. To be documented.
 * @param array Extra and hidden filter for the agent search.
 */
function ui_print_ui_agents_list ($options = false, $filter = false, $return = false) {
	global $config;
	
	$output = '';

	$group_filter = true;
	$text_filter = true;
	$access = 'AR';
	$table_heads = array (__('Name'), __('Group'), __('Status'));
	$table_renders = array ('view_link', 'group', 'status');
	$table_align = array ('', '', 'center');
	$table_size = array ('50%', '45%', '5%');
	$fields = false;
	$show_filter_form = true;
	if (is_array ($options)) {
		if (isset ($options['group_filter']))
			$group_filter = (bool) $options['group_filter'];
		if (isset ($options['text_filter']))
			$text_filter = (bool) $options['text_filter'];
		if (isset ($options['access']))
			$access = (string) $options['access'];
		if (isset ($options['table_heads']))
			$table_heads = (array) $options['table_heads'];
		if (isset ($options['table_renders']))
			$table_renders = (array) $options['table_renders'];
		if (isset ($options['table_align']))
			$table_align = (array) $options['table_align'];
		if (isset ($options['table_size']))
			$table_size = (array) $options['table_size'];
		if (isset ($options['fields']))
			$fields = (array) $options['fields'];
		if (isset ($options['show_filter_form']))
			$show_filter_form = (bool) $options['show_filter_form'];
		
		if (count ($table_renders) != count ($table_heads))
			trigger_error ('Different count for table_renders and table_heads options');
		
		unset ($options);
	}
	
	if ($return)
		return ui_get_include_contents ($config['homedir'].'/general/ui/agents_list.php',
			get_defined_vars ());
	
	include ($config['homedir'].'/general/ui/agents_list.php');
}

/**
 * Get the content of a PHP file instead of dumping to the output.
 * 
 * Picked from PHP official doc.
 *
 * @param string File name to include and get content.
 * @param array Extra parameters in an indexed array to be passed to the file.
 *
 * @return string Content of the file after being processed. False if the file
 * could not be included.
 */
function ui_get_include_contents ($filename, $params = false) {
	global $config;

	ob_start ();
	
	if (is_array ($params)) {
		extract ($params);
	}
	
	$filename = realpath ($filename);
	if (strncmp ($config["homedir"], $filename, strlen ($config["homedir"])) != 0) {
		return false;
	}

	$result = include ($filename);
	if ($result === false) {
		ob_end_clean ();
		return false;
	}
	
	$contents = ob_get_contents ();
	ob_end_clean ();

	return $contents;
}
/**
 * Print a code into a DIV and enable a toggle to show and hide it
 * 
 * @param string html code
 * @param string name of the link
 * @param string title of the link
 * @param bool if the div will be hidden by default (default: true)
 * 
 */

function ui_toggle($code, $name, $title = '', $hidde_default = true) {
// Generate unique Id
$uniqid = uniqid('');

// Options
if($hidde_default) {
	$style = 'display:none';
	$toggle_a = "$('#tgl_div_".$uniqid."').show();";
	$toggle_b = "$('#tgl_div_".$uniqid."').hide();";
	$image_a = html_print_image("images/go.png", true, false, true);
	$image_b = html_print_image("images/down.png", true, false, true);
	$original = "images/down.png";
}else {
	$style = '';
	$toggle_a = "$('#tgl_div_".$uniqid."').hide();";
	$toggle_b = "$('#tgl_div_".$uniqid."').show();";
	$image_a = html_print_image("images/down.png", true, false, true);
	$image_b = html_print_image("images/go.png", true, false, true);
	$original = "images/go.png";
}

// Link to toggle
echo '<a href="#" id="tgl_ctrl_'.$uniqid.'"><b>'.$name.'</b>&nbsp;'.html_print_image ($original, true, array ("title" => $title, "id" => "image_".$uniqid)).'</a><br /><br />';

// Code into a div
echo "<div id='tgl_div_".$uniqid."' style='".$style."'>\n";
echo $code;
echo "</div>";

// JQuery Toggle

echo '<script type="text/javascript">';
echo '/* <![CDATA[ */';
echo "$(document).ready (function () {";

	echo "$('#tgl_ctrl_".$uniqid."').toggle(function() {";
		echo $toggle_a;
		echo "$('#image_".$uniqid."').attr({src: '".$image_a."'});";
		echo "}, function() {";
		echo $toggle_b;
		echo "$('#image_".$uniqid."').attr({src: '".$image_b."'});";
	echo "});";
echo "});";
echo '/* ]]> */';
echo '</script>';
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
	$url = '';
	
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
		$url .= $key.'='.$value.'&';
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
			$url .= $key.'='.$value.'&';
		}
	}
	
	foreach ($params as $key => $value) {
		if ($value === false)
			continue;
		$url .= $key.'='.$value.'&';
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
 *
 * @return string A full URL in Pandora.
 */
function ui_get_full_url ($url = '') {
	global $config;
	
	if ($config['https']) {
		//When $config["https"] is set, always force https
		$protocol = 'https';
		$ssl = true;
	}
	elseif (isset ($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === true || $_SERVER['HTTPS'] == 'on')) {
		$protocol = 'https';
		$ssl = true;
	}
	else {
		$protocol = 'http';
		$ssl = false;
	}
	
	$fullurl = $protocol.'://' . $_SERVER['SERVER_NAME'];
	
	if ((!$ssl && $_SERVER['SERVER_PORT'] != 80) || ($ssl && $_SERVER['SERVER_PORT'] != 443)) {
		$fullurl .= ":".$_SERVER['SERVER_PORT'];
	}
	
	if ($url === '') {
		$url = $_SERVER['REQUEST_URI'];
	}
	elseif ($url === false) {
		//Only add the home url
		$url = $config['homeurl'] . '/';
	}
	else {
		$fullurl .= $_SERVER['SCRIPT_NAME'];
	}
	
	return $fullurl.$url;
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

function ui_print_page_header ($title, $icon = "", $return = false, $help = "", $godmode = false, $options = ""){
	$title = io_safe_input_html($title);
	if (($icon == "") && ($godmode == true)) {
		$icon = "images/setup.png";
	}
	
	if (($icon == "") && ($godmode == false)) {
		$icon = "images/comments.png";
	}
	
	if ($godmode == true) {
		$type = "nomn";
		$type2 = "menu_tab_frame";
		$separator_class = "separator";
	} 
	else {
		$type = "view";
		$type2 = "menu_tab_frame_view";
		$separator_class = "separator_view";
	}
	
	
	$buffer = '<div id="'.$type2.'" style=""><div id="menu_tab_left">';
	
	
	$buffer .= '<ul class="mn"><li class="'.$type.'">&nbsp;' . html_print_image($icon, true, array("style" => "margin-bottom: -3px;", "class" => "bottom", "border" => "0", "alt" => "")) . '&nbsp; ';
	$buffer .= $title;
	if ($help != "")
		$buffer .= "&nbsp;&nbsp;" . ui_print_help_icon ($help, true);
	$buffer .= '</li></ul></div>';
	
	if (is_array($options)) {
		$buffer .= '<div id="menu_tab"><ul class="mn">';
		foreach ($options as $key => $option) {
			if ($key === 'separator') {
				$buffer .= '<li class='.$separator_class.'>';
				$buffer .= '</li>';
			}
			else {
				if (is_array($option)) {
					$class = 'nomn';
					if (isset($option['active'])) {
						if ($option['active']) {
							$class = 'nomn_high';
						}
					}
					
					$buffer .= '<li class="' . $class . '">';
					$buffer .= $option['text'];
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
		if ($options != ""){
			$buffer .= '<div id="menu_tab"><ul class="mn"><li class="nomn">';
			$buffer .= $options;
			$buffer .= '</li></ul></div>';
		}
	}

	$buffer .=  '</div>';  //<br /><br /><br />';

	if (!$return)
		echo $buffer;

	return $buffer;
}

/** 
 * Add a help link to show help in a popup window.
 * 
 *
 * @param string $help_id Help id to be shown when clicking.
 * @param bool $return Whether to print this (false) or return (true)
 * 
 * @return string Link with the popup.
 */
function ui_popup_help ($help_id, $return = false) {
	$output = "&nbsp;<a href='javascript:help_popup(".$help_id.")'>[H]</a>";
	if ($return)
		return $output;
	echo $output;
}


?>
