<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/** 
 * Prints a generic message between tags.
 * 
 * @param string The message string to be displayed
 * @param string the class to user
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function print_message ($message, $class = '', $attributes = '', $return = false, $tag = 'h3') {
	$output = '<'.$tag.(empty ($class) ? '' : ' class="'.$class.'" ').$attributes.'>'.$message.'</'.$tag.'>';
	
	if ($return)
		return $output;
	echo $output;
}

/** 
 * Prints an error message.
 * 
 * @param string The error message to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function print_error_message ($message, $attributes = '', $return = false, $tag = 'h3') {
	return print_message ($message, 'error', $attributes, $return, $tag);
}

/** 
 * Prints an operation success message.
 * 
 * @param string The message to be displayed
 * @param string Any other attributes to be set for the tag.
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function print_success_message ($message, $attributes = '', $return = false, $tag = 'h3') {
	return print_message ($message, 'suc', $attributes, $return, $tag);
}

/** 
 * Evaluates a result using empty() and then prints an error or success message
 * 
 * @param mixed The results to evaluate. 0, NULL, false, '' or 
 * array() is bad, the rest is good
 * @param string The string to be displayed if the result was good
 * @param string The string to be displayed if the result was bad
 * @param string Any other attributes to be set for the h3
 * @param bool Whether to output the string or return it
 * @param string What tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string HTML code if return parameter is true.
 */
function print_result_message ($result, $good = '', $bad = '', $attributes = '', $return = false, $tag = 'h3') {
	if ($good == '' || $good === false)
		$good = __('Request successfully processed');
	
	if ($bad == '' || $bad === false)
		$bad = __('Error processing request');
	
	if (empty ($result)) {
		return print_error_message ($bad, $attributes, $return, $tag);
	}
	return print_success_message ($good, $attributes, $return, $tag);
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
 *
 * @return string HTML code if return parameter is true.
 */
function print_timestamp ($unixtime, $return = false, $option = array ()) {
	global $config;
	
	//TODO: Add/use a javascript timer for the seconds so it automatically updates as time passes by
	
	if (isset ($option["html_attr"])) {
		$attributes = $option["html_attr"];
	} else {
		$attributes = "";
	}
	
	if (isset ($option["tag"])) {
		$tag = $option["tag"];
	} else {
		$tag = "span";
	}
	
	if (empty ($option["style"])) {
		$style = 'style="white-space:nowrap;"';
	} else {
		$style = 'style="'.$option["style"].'"';
	}
	
	if (!empty ($option["prominent"])) {
		$prominent = $option["prominent"];
	} else {
		$prominent = $config["prominent_time"];
	}
				
	if (!is_numeric ($unixtime)) {
		$unixtime = strtotime ($unixtime);
	}

	//prominent_time is either timestamp or comparation
	if ($unixtime == 0) {
		$title = __('Unknown').'/'.__('Never');
		$data = __('Unknown');
	} elseif ($prominent == "timestamp") {
		$title = human_time_comparation ($unixtime);
		$data = date ($config["date_format"], $unixtime);
	} else {
		$title = date ($config["date_format"], $unixtime);
		$data = human_time_comparation ($unixtime);
	}
	
	$output = '<'.$tag;
	switch ($tag) {
	default:
		//Usually tags have title attributes, so by default we add, then fall through to add attributes and data
		$output .= ' title="'.$title.'"';
	case "h1":
	case "h2":
	case "h3":
		//Above tags don't have title attributes
		$output .= ' '.$attributes.' '.$style.'>'.$data.'</'.$tag.'>';
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
function print_username ($username, $return = false) {
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
 *
 * @return string HTML code if return parameter is true.
 */
function print_group_icon ($id_group, $return = false, $path = "groups_small") {
	$icon = (string) get_db_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	
	if (empty ($icon)) {
		return "-";
	}
	
	$output = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id_group.'">';
	$output .= '<img class="bot" src="images/'.$path.'/'.$icon.'.png" alt="'.get_group_name ($id_group).'" title="'.get_group_name ($id_group).'" />';
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
 * 
 * @return string HTML with icon of the OS
 */
function print_os_icon ($id_os, $name = true, $return = false) {
	$icon = (string) get_db_value ('icon_name', 'tconfig_os', 'id_os', (int) $id_os);
	$os_name = get_os_name ($id_os);
	if (empty ($icon)) {
		return "-";
	}
	
	$output = '<img src="images/'.$icon.'" alt="'.$os_name.'" title="'.$os_name.'" />';
	
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
 * 
 * @return string HTML with agent name and link
 */
function print_agent_name ($id_agent, $return = false, $cutoff = 0) {
	$agent_name = (string) get_agent_name ($id_agent);
	$output = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'" title="'.$agent_name.'"><b>'.$agent_name.'</b></a>';
	
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
 * 
 * @return array A formatted array with proper html for use in $table->data (6 columns)
 */
function format_alert_row ($alert, $compound = false, $agent = true, $url = '') {
	require_once ("include/functions_alerts.php");
	
	if (empty ($alert))
		return array ("", "", "", "", "", "", "");
	
	// Get agent id
	if ($compound) {
		$id_agent = $alert['id_agent'];
		$description = $alert['description'];
	} else {
		$id_agent = get_agentmodule_agent ($alert['id_agent_module']);
		$template = get_alert_template ($alert['id_alert_template']);
		$description = $template['name'];
	}
	$data = array ();
	
	// Force alert execution
	$data[0] = '';
	if (! $compound) {
		if ($alert["force_execution"] == 0) {
			$data[0] = '<a href="'.$url.'&id_alert='.$alert["id"].'&force_execution=1&refr=60"><img src="images/target.png" ></a>';
		} else {
			$data[0] = '<a href="'.$url.'&id_alert='.$alert["id"].'&refr=60"><img src="images/refresh.png" /></a>';
		}
	}
	
	if ($compound) {
		$data[1] =  print_agent_name ($id_agent, true, 20);
	} elseif ($agent == 0) {
		$data[1] = mb_substr (get_agentmodule_name ($alert["id_agent_module"]), 0, 20);
	} else {
		$data[1] = print_agent_name (get_agentmodule_agent ($alert["id_agent_module"]), true, 20);
	}
	
	$data[2] = '';
	if (! $compound) {
		$data[2] .= '<a class="template_details" href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$template['id'].'">';
		$data[2] .= print_image ('images/zoom.png', true);
		$data[2] .= '</a> ';
	}
	$data[2] .= mb_substr (safe_input ($description), 0, 35);
	
	$data[3] = print_timestamp ($alert["last_fired"], true);
	

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
	
	$data[4] = print_status_image($status, $title, true);

	
	if ($compound) {
		$data[5] = print_checkbox ("validate_compound[]", $alert["id"], false, true);
	} else {
		$data[5] = print_checkbox ("validate[]", $alert["id"], false, true);
	}
	
	return $data;
}

/**
 * Prints a substracted string, length specified by cutoff, the full string will be in a rollover. 
 *
 * @param string The string to be cut
 * @param int At how much characters to cut
 * @param bool Whether to return or print it out
 *
 * @return An HTML string
 */
function print_string_substr ($string, $cutoff = 16, $return = false) {
	if (empty ($string)) {
		return "";
	}
	$string = '<span title="'.safe_input ($string).'">'.mb_substr ($string, 0, $cutoff, "UTF-8").(mb_strlen ($string. "UTF-8") > $cutoff ? '...' : '').'</span>';
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
function print_alert_template_example ($id_alert_template, $return = false, $print_values = true) {
	$output = '';
	
	$output .= '<img src="images/information.png" /> ';
	$output .= '<span id="example">';
	$template = get_alert_template ($id_alert_template);
	
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
function print_help_icon ($help_id, $return = false) {
	$output = '&nbsp;'.print_image ("images/help.png", true, 
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
 
 * @param string Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
<code>
require_css_file ('pandora');
// Would include include/styles/pandora.js
</code>
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function require_css_file ($name, $path = 'include/styles/') {
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
 
 * @param string Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
<code>
require_javascript_file ('pandora');
// Would include include/javascript/pandora.js
</code>
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function require_javascript_file ($name, $path = 'include/javascript/') {
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
 
 * @param string Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
<code>
require_jquery_file ('form');
// Would include include/javascript/jquery.form.js
</code>
 *
 * @return bool True if the file was added. False if the file doesn't exist.
 */
function require_jquery_file ($name, $path = 'include/javascript/') {
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
function process_page_head ($string, $bitfield) {
	global $config;
	$output = '';
	
	if ($config["refr"] > 0) {
		// Agent selection filters and refresh
		$protocol = 'http';
		$ssl = false;
		if ($config['https']) {
			/* Check with "on" because some web servers like Cherokee always
			 set this value even if SSL is not enabled */
			if (isset ($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === true || $_SERVER['HTTPS'] == 'on')) {
				$protocol = 'https';
				$ssl = true;
			}
		}
		
		$query = $protocol.'://' . $_SERVER['SERVER_NAME'];
		
		if ((!$ssl && $_SERVER['SERVER_PORT'] != 80) || ($ssl && $_SERVER['SERVER_PORT'] != 443)) {
			$query .= ":".$_SERVER['SERVER_PORT'];
		}
		$query .= $_SERVER['SCRIPT_NAME'];
		
		if (sizeof ($_REQUEST))
			//Some (old) browsers don't like the ?&key=var
			$query .= '?1=1';
		
		//We don't clean these variables up as they're only being passed along
		foreach ($_GET as $key => $value) {
			/* Avoid the 1=1 */
			if ($key == 1)
				continue;
			$query .= '&amp;'.$key.'='.$value;
		}
		foreach ($_POST as $key => $value) {
			$query .= '&amp;'.$key.'='.$value;
		}
		
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
	<link rel="alternate" href="operation/events/events_rss.php" title="Pandora RSS Feed" type="application/rss+xml" />';

	if ($config["language"] != "en") {
		//Load translated strings - load them last so they overload all the objects
		require_javascript_file ("time_".$config["language"]);
		require_javascript_file ("date".$config["language"]);
		require_javascript_file ("countdown_".$config["language"]);
	}
	$output .= "\n\t";
	
	//Load CSS
	if (empty ($config['css'])) {
		$config['css'] = array ();
	}
	
	//Style should go first
	$config['css'] = array_merge (array (
		"common" => "include/styles/common.css", 
		$config['style'] => "include/styles/".$config['style'].".css", 
		"menu" => "include/styles/menu.css", 
		"tip", "include/styles/tip.css"
		), $config['css']);
	
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
		} else {
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
		} else {
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
function process_page_body ($string, $bitfield) {
	global $config;
	
	// Show custom background
	if ($config["pure"] == 0) {
		$output = '<body style="background-color:#555555;">';
	} else {
		$output = '<body>'; //Don't enforce a white background color. Let user style sheet do that
	}
	
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
function pagination ($count, $url, $offset = 0, $pagination = 0, $return = false) {
	global $config;
	
	if (empty ($pagination)) {
		$pagination = $config["block_size"];
	}
	
	if (empty ($offset)) {
		$offset = (int) get_parameter ('offset');
	}
	
	$url = safe_input ($url);
	
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
	$output .= '<a class="pagination go_first" href="'.$url.'&amp;offset=0">'.print_image ("images/control_start_blue.png", true, array ("class" => "bot")).'</a>&nbsp;';
	// Show PREVIOUS button
	if ($index_page > 0) {
		$index_page_prev = ($index_page - (floor ($block_limit / 2))) * $pagination;
		if ($index_page_prev < 0)
			$index_page_prev = 0;
		$output .= '<a class="pagination go_rewind" href="'.$url.'&amp;offset='.$index_page_prev.'">'.print_image ("images/control_rewind_blue.png", true, array ("class" => "bot")).'</a>';
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
		$output .= '<a class="pagination" href="'.$url.'&amp;offset='.$inicio_bloque.'">';
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
		$output .= '<a class="pagination go_fastforward" href="'.$url.'&amp;offset='.$prox_bloque.'">'.print_image ("images/control_fastforward_blue.png", true, array ("class" => "bot")).'</a>';
		$i = $index_counter;
	}
	// if exists more registers than i can put in a page (defined by $block_size config parameter)
	// get offset for index calculation
	// Draw "last" block link, ajust for last block will be the same
	// as painted in last block (last integer block).	
	if (($count - $pagination) > 0) {
		$myoffset = floor (($count - 1) / $pagination) * $pagination;
		$output .= '<a class="pagination go_last" href="'.$url.'&amp;offset='.$myoffset.'">'.print_image ("images/control_end_blue.png", true, array ("class" => "bot")).'</a>';
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
 *
 * @return string HTML code if return parameter is true.
 */
function print_help_tip ($text, $return = false) {
	$output = '<a href="#" class="tip">&nbsp;<span>'.$text.'</span></a>';
	
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
function debug ($var, $backtrace = true) {
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
		
			/* Many classes are used to allow better customization. Please, do not
			  remove them */
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
 *
 * @return string An HTML string with the icon. Printed if return is false
 */
function print_moduletype_icon ($id_moduletype, $return = false) {
	global $config;
	
	$type = get_db_row ("ttipo_modulo", "id_tipo", (int) $id_moduletype, array ("descripcion", "icon"));
	if ($type === false) {
		$type = array ();
		$type["descripcion"] = __('Unknown Type'); 
		$type["icon"] = 'b_down.png';
	}
	$imagepath = 'images/'.$type["icon"];
	if (! file_exists ($config['homedir'].'/'.$imagepath))
		$imagepath = ENTERPRISE_DIR.'/'.$imagepath;
	
	return print_image ($imagepath, $return,
		array ("border" => 0,
			"title" => $type["descripcion"]));
}

/**
* Format a file size from bytes to a human readable meassure.
*
* @param int File size in bytes
* @return string Bytes converted to a human readable meassure.
*/
function format_filesize ($bytes) {
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
function get_status_images_path () {
	global $config;

	$imageset = $config["status_images_set"];

	if (strpos ($imageset, ",") === false) 
		$imageset .= ",40x18";
	list ($imageset, $sizes) = split (",", $imageset, 2);

	if (strpos ($sizes, "x") === false)
		$sizes .= "x18";
	list ($imagewidth, $imageheight) = split ("x", $sizes, 2);

	$imagespath = 'images/status_sets/'.$imageset;

	return array ($imagespath);
}

define ('STATUS_MODULE_OK', 'module_ok.png');
define ('STATUS_MODULE_CRITICAL', 'module_critical.png');
define ('STATUS_MODULE_WARNING', 'module_warning.png');

define ('STATUS_AGENT_CRITICAL', 'agent_critical.png');
define ('STATUS_AGENT_WARNING', 'agent_warning.png');
define ('STATUS_AGENT_DOWN', 'agent_down.png');
define ('STATUS_AGENT_OK', 'agent_ok.png');
define ('STATUS_AGENT_NO_DATA', 'agent_no_data.png');
define ('STATUS_AGENT_NO_MONITORS', 'agent_no_monitors.png');

define ('STATUS_ALERT_FIRED', 'alert_fired.png');
define ('STATUS_ALERT_NOT_FIRED', 'alert_not_fired.png');
define ('STATUS_ALERT_DISABLED', 'alert_disabled.png');

define ('STATUS_SERVER_OK', 'server_ok.png');
define ('STATUS_SERVER_DOWN', 'server_down.png');

function print_status_image ($type, $title = "", $return = false) {
	list ($imagepath) = get_status_images_path ();
	
	$imagepath .= "/" . $type;
	
	return print_image ($imagepath, $return, array ("title" => $title));
}

?>
