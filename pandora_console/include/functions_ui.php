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
 * Evaluates a result using empty() and then prints an error message or a
 * success message
 * 
 * @param mixed $result the results to evaluate. 0, NULL, false, '' or 
 * array() is bad, the rest is good
 * @param string $good the string to be displayed if the result was good
 * @param string $bad the string to be displayed if the result was bad
 * @param string $attributes any other attributes to be set for the h3
 * @param bool $return whether to output the string or return it
 * @param string $tag what tag to use (you could specify something else than
 * h3 like div or h2)
 *
 * @return string XHTML code if return parameter is true.
 */
function print_error_message ($result, $good = '', $bad = '', $attributes = '', $return = false, $tag = 'h3') {
	if ($good == '' || $good === false)
		$good = __('Request successfully processed');
	
	if ($bad == '' || $bad === false)
		$bad = __('Error processing request');
	
	if (empty ($result)) {
		$output = '<'.$tag.' class="error" '.$attributes.'>'.$bad.'</'.$tag.'>';
	} else {
		$output = '<'.$tag.' class="suc" '.$attributes.'>'.$good.'</'.$tag.'>';
	}

	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Evaluates a unix timestamp and returns a span (or whatever tag specified)
 * with as title the correctly formatted full timestamp and a time comparation
 * in the tag
 *
 * @param int $unixtime: Any type of timestamp really, but we prefer unixtime
 * @param bool $return whether to output the string or return it
 * @param array $option: An array with different options for this function
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
		$title = __('Never');
		$data = __('Never');
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
		$output .= ' '.$attributes.'>'.$data.'</'.$tag.'>';
	}

	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Prints a username with real name, link to the user_edit page etc.
 *
 * @param string $username The username to render
 * @param bool $return Whether to return or print
 *
 * @return string HTML code if return parameter is true.
 */
function print_username ($username, $return = false) {
	$string = '<a href="index.php?sec=usuario&sec2=operation/users/user_edit&id='.$username.'">'.get_user_fullname ($username).'</a>';
	
	if ($return)
		return $string;
	
	echo $string;
}

/** 
 * Print group icon within a link
 * 
 * @param string $id_group Group id
 * @param bool $return Whether to return or print
 * @param string $path What path to use (relative to images/). Defaults to groups_small
 *
 * @return string HTML code if return parameter is true.
 */
function print_group_icon ($id_group, $return = false, $path = "groups_small") {
	$icon = (string) get_db_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	
	if (empty ($icon)) {
		return "-";
	}
	
	$output = '<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$id_group.'">';
	$output .= '<img class="bot" src="images/'.$path.'/'.$icon.'.png" alt="'.get_group_name ($id_group).'" title="'.get_group_name ($id_group).'" />';
	$output .= '</a>';
	
	if ($return)
		return $output;
	
	echo $output;
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
	
	$output = '<img src="images/'.$icon.'" border="0" alt="'.$os_name.'" title="'.$os_name.'" />';
	
	if ($name === true) {
		$output .= ' - '.$os_name;
	}
	
	if ($return)
		return $output;
	
	echo $output;
}

/**
 * Prints an agent name with the correct link
 * 
 * @param int $id_agent Agent id
 * @param bool $return Whether to return the string or echo it too
 * @param int $cutoff After how much characters to cut off the inside of the 
 * link. The full agent name will remain in the roll-over
 * 
 * @return string HTML with agent name and link
 */
function print_agent_name ($id_agent, $return = false, $cutoff = 0) {
	$agent_name = (string) get_agent_name ($id_agent);
	$output = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'" title="'.$agent_name.'"><b>';
	if ($cutoff > 0 && (mb_strlen ($agent_name, "UTF-8") > $cutoff)) {
		$output .= mb_substr (utf8_decode ($agent_name), 0, $cutoff, "UTF-8").'...';
	} else {
		$output .= $agent_name;
	}
	$output .= '</b></a>';
	
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
		$description = $template['description'];
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
	
	$data[2] = '<span class="left">';
	$data[2] .= mb_substr (safe_input ($description), 0, 35);
	$data[2] .= '</span>';
	if (! $compound) {
		$data[2] .= ' <span class="right">';
		$data[2] .= '<a class="template_details" href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$template['id'].'">';
		$data[2] .= '<img src="images/zoom.png" />';
		$data[2] .= '</a></span>';
	}
	
	$data[3] = print_timestamp ($alert["last_fired"], true);
	
	$options = array ();
	$options["height"] = 18;
	$options["width"] = 40;
	
	if ($alert["times_fired"] > 0) {
		$options["src"] = "images/pixel_red.png";
		$options["title"] = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
	} elseif ($alert["disabled"] > 0) {
		$options["src"] = "images/pixel_gray.png";
		$options["title"] = __('Alert disabled');
	} else {
		$options["src"] = "images/pixel_green.png";
		$options["title"] = __('Alert not fired');
	}
	
	$data[4] = print_image ($options["src"], true, $options);
	
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
 * DEPRECATED: Use print_help_icon to avoid confusion with pandora_help javascript function
 */
function pandora_help ($help_id, $return = false) {
	return print_help_icon ($help_id, $return);
}

/**
 * Prints a help tip icon.
 * 
 * @param string $help_id Id of the help article
 * @param bool $return Whether to return or output the result
 * 
 * @return string The help tip
 */
function print_help_icon ($help_id, $return = false) {
	$output = '&nbsp;'.print_image ("images/help.png", true, array ("class" => "img_help", "title" => __('Help'), "onclick" => "pandora_help ('".$help_id."')"));
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
		$query = 'http' . (isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': '') . '://' . $_SERVER['SERVER_NAME'];
		if ($_SERVER['SERVER_PORT'] != 80 && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE && $_SERVER['SERVER_PORT'] != 443)) {
			$query .= ":" . $_SERVER['SERVER_PORT'];
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
	<link rel="stylesheet" href="include/styles/'.$config["style"].'.css" type="text/css" />
	<!--[if gte IE 6]>
	<link rel="stylesheet" href="include/styles/ie.css" type="text/css"/>
	<![endif]-->
	<script type="text/javascript" src="include/javascript/pandora.js"></script>
	<script type="text/javascript" src="include/javascript/jquery.js"></script>
	<script type="text/javascript" src="include/javascript/jquery.pandora.js"></script>
	<script type="text/javascript" src="include/languages/time_'.$config['language'].'.js"></script>
	<script type="text/javascript" src="include/languages/date_'.$config['language'].'.js"></script>
	<script type="text/javascript" src="include/languages/countdown_'.$config['language'].'.js"></script>'."\n\t";
	
	if (!empty ($config['css'])) {
		//We can't load empty and we loaded current style and ie
		$loaded = array ('', $config["style"], 'ie');
		foreach ($config['css'] as $name => $filename) {
			if (in_array ($name, $loaded))
				continue;
			
			array_push ($loaded, $name);
			$output .= '<link rel="stylesheet" href="'.$filename.'" type="text/css" />'."\n\t";
		}
	}
	
	if (!empty ($config['js'])) {
		//Load other javascript
		//We can't load empty and we loaded wz_jsgraphics and pandora
		$loaded = array ('', 'pandora', 'date_'.$config['language'],
			'time_'.$config['language'], 'countdown_'.$config['language']);
		foreach ($config['js'] as $name => $filename) {
			if (in_array ($name, $loaded))
				continue;
			
			array_push ($loaded, $name);
			$output .= '<script type="text/javascript" src="'.$filename.'"></script>'."\n\t";

		}
	}
	
	if (!empty ($config['jquery'])) {
		//Load jQuery
		$loaded = array ('', 'pandora');
		
		//Then add each script as necessary
		foreach ($config['jquery'] as $name => $filename) {
			if (in_array ($name, $loaded))
				continue;
			
			array_push ($loaded, $name);
			$output .= '<script type="text/javascript" src="'.$filename.'"></script>'."\n\t";
		}
	}
	
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
function process_page_body ($string, $bitfield) {
	global $config;
	
	// Show custom background
	if ($config["pure"] == 0) {
		$output = '<body style="background-color:#555555;">';
	} else {
		$output = '<body>'; //Don't enforce a white background color. Let user style sheet do that
	}
	
	$output .= $string;
	
	$output .= '</body>';
	
	return $output;
}
?>
