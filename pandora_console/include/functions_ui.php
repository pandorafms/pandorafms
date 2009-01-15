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
	$string = '<a href="index.php?sec=usuario&sec2=operation/users/user_edit&ver='.$username.'">'.dame_nombre_real ($username).'</a>';
	
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
	$output = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agent.'" title="'.$agent_name.'"><b>';
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
function format_alert_row ($alert, $combined = false, $agent = true, $url = '') {
	require_once ("include/functions_alerts.php");
	
	if (empty ($alert))
		return array ("", "", "", "", "", "", "");
	
	// Get agent id
	$id_agente = get_agentmodule_agent ($alert["id_agent_module"]);
	$template = get_alert_template ($alert['id_alert_template']);
	
	$data = array ();
	
	// Force alert execution
	$data[0] = '';
	if (! $combined) {
		if ($alert["force_execution"] == 0) {
			$data[0] = '<a href="'.$url.'&id_alert='.$alert["id"].'&force_execution=1&refr=60"><img src="images/target.png" ></a>';
		} else {
			$data[0] = '<a href="'.$url.'&id_alert='.$alert["id"].'&refr=60"><img src="images/refresh.png" /></a>';
		}
	}
	
	if ($combined == 1) {
		$data[1] =  print_agent_name ($id_agente, true, 20);
	} elseif ($agent == 0) {
		$data[1] = mb_substr (get_agentmodule_name ($alert["id_agent_module"]), 0, 20);
	} else {
		$data[1] = print_agent_name (get_agentmodule_agent ($alert["id_agent_module"]), true, 20);
	}
	
	$data[2] = '<span class="left">';
	$data[2] .= mb_substr (safe_input ($template["description"]), 0, 35);
	$data[2] .= '</span> <span class="right">';
	$data[2] .= '<a class="template_details" href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$template['id'].'">';
	$data[2] .= '<img src="images/zoom.png" />';
	$data[2] .= '</a></span>';
	
	$data[3] = print_timestamp ($alert["last_fired"], true);
	
	$options = array ();
	$options["height"] = 9;
	$options["width"] = 20;
	
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
	
	$data[5] = print_checkbox ("validate[]", $alert["id"], false, true);	
	
	return $data;
}
?>
