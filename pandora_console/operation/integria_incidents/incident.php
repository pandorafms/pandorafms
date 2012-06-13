<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;
require_once ("include/functions_incidents.php");

check_login ();

if (! check_acl ($config['id_user'], 0, "IR")) {
	db_pandora_audit("ACL Violation","Trying to access incident viewer");
	require ("general/noaccess.php");
	exit;
}

$tab = get_parameter('tab', 'list');
$id_incident = get_parameter('id_incident', 0);

// We choose a strange token to use texts with commas, etc.
$token = ';,;';

// Header
if($tab == 'list' || $tab == 'editor') {
	$buttons = array(
			'list' => array(
				'active' => false,
				'text' => '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=list">' . 
					html_print_image ("images/page_white_text.png", true, array ("title" => __('Incidents'))) .'</a>'),
			'editor' => array(
				'active' => false,
				'text' => '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=editor">' . 
					html_print_image ("images/add.png", true, array ("title" => __('New Incident'))) .'</a>'));
}
else {
	$buttons = array(
			'list' => array(
				'active' => false,
				'text' => '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=list">' . 
					html_print_image ("images/page_white_text.png", true, array ("title" => __('Incidents'))) .'</a>'),
			'incident' => array(
				'active' => false,
				'text' => '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=incident&id_incident='.$id_incident.'">' . 
					html_print_image ("images/eye.png", true, array ("title" => __('Incident details'))) .'</a>'),
			'workunits' => array(
				'active' => false,
				'text' => '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=workunits&id_incident='.$id_incident.'">' . 
					html_print_image ("images/computer.png", true, array ("title" => __('Workunits'))) .'</a>'),
			'files' => array(
				'active' => false,
				'text' => '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=files&id_incident='.$id_incident.'"">' . 
					html_print_image ("images/file.png", true, array ("title" => __('Files'))) .'</a>'),
			'tracking' => array(
				'active' => false,
				'text' => '<a href="index.php?login=1&sec=workspace&sec2=operation/integria_incidents/incident&tab=tracking&id_incident='.$id_incident.'"">' . 
					html_print_image ("images/comments.png", true, array ("title" => __('Tracking'))) .'</a>'));
}
	
$buttons[$tab]['active'] = true;

ui_print_page_header (__('Incident management'), "images/book_edit.png", false, "", false, $buttons);

$update_incident = get_parameter('update_incident', 0);

$integria_api = $config['integria_url']."/include/api.php?return_type=xml&user=".$config['id_user']."&pass=".$config['integria_api_password'];

if($update_incident == 1) {				
	$values[0] = $id_incident;
	$values[1] = str_replace(" ", "%20", io_safe_output(get_parameter('title')));
	$values[2] = str_replace(" ", "%20", io_safe_output(get_parameter('description')));
	$values[3] = str_replace(" ", "%20", io_safe_output(get_parameter('epilog')));
	$values[4] = get_parameter('group');
	$values[5] = get_parameter('priority');
	$values[6] = get_parameter('source');
	$values[7] = get_parameter('resolution');
	$values[8] = get_parameter('status');
	$values[9] = get_parameter('creator', get_parameter('creator_fix'));

	$params = implode($token, $values);

	$url = $integria_api."&op=update_incident&token=".$token."&params=".$params;
	// Call the integria API
	$result = incidents_call_api($url);
}

$create_incident = get_parameter('create_incident', 0);

if($create_incident == 1) {
	$values[0] = str_replace(" ", "%20", io_safe_output(get_parameter('title')));
	$values[1] = get_parameter('group');
	$values[2] = get_parameter('priority');
	$values[3] = str_replace(" ", "%20", io_safe_output(get_parameter('description')));
	$values[4] = $config['integria_inventory'];
	
	$params = implode($token, $values);

	$url = $integria_api."&op=create_incident&token=".$token."&params=".$params;

	// Call the integria API
	$result = incidents_call_api($url);
}

$attach_file = get_parameter('attach_file', 0);

if($attach_file == 1) {
	if($_FILES['new_file']['name'] != "" && $_FILES['new_file']['error'] == 0) {
		$file_content = file_get_contents($_FILES["new_file"]["tmp_name"]);
		
		$values[0] = $id_incident;
		$values[1] = $_FILES['new_file']['name'];
		$values[2] = $_FILES['new_file']['size'];
		$values[3] = str_replace(" ", "%20", io_safe_output(get_parameter('description'), __('No description available')));
		$values[4] = base64_encode($file_content);
		
		
		$params = implode($token, $values);

		$url = $integria_api."&op=attach_file&token=".$token;

		// Call the integria API
		$result = incidents_call_api($url, array('params' => $params));
	}
	else {
		switch ($_FILES['new_file']['error']) {
		case 1:
			echo '<h3 class="error">'.__('File is too big').'</h3>';
			break;
		case 3:
			echo '<h3 class="error">'.__('File was partially uploaded. Please try again').'</h3>';
			break;
		case 4:
			echo '<h3 class="error">'.__('No file was uploaded').'</h3>';
			break;
		default:
			echo '<h3 class="error">'.__('Generic upload error').'(Code: '.$_FILES['new_file']['error'].')</h3>';
		}
	}
}

$delete_file = get_parameter('delete_file', 0);

if($delete_file != 0) {
	$url = $integria_api."&op=delete_file&params=".$delete_file;

	// Call the integria API
	$result = incidents_call_api($url);
}

$delete_incident = get_parameter('delete_incident', 0);

if($delete_incident != 0) {
	$url = $integria_api."&op=delete_incident&params=".$delete_incident;

	// Call the integria API
	$result = incidents_call_api($url);
}

$create_workunit = get_parameter('create_workunit', 0);

if($create_workunit == 1) {
	$values[0] = $id_incident;
	$values[1] = str_replace(" ", "%20", io_safe_output(get_parameter('description')));
	$values[2] = get_parameter('time_used');
	$values[3] = get_parameter('have_cost');
	$values[4] = get_parameter('public');
	$values[5] = get_parameter('profile');
	
	$params = implode($token, $values);
	
	$url = $integria_api."&op=create_workunit&token=".$token."&params=".$params;

	// Call the integria API
	$result = incidents_call_api($url);
}

// Set the url with parameters to call the api
switch($tab) {
	case 'list':
		$search_string = get_parameter('search_string', "");
		$params[0] = $search_string;
		
		$search_status = get_parameter('search_status', -10);
		$params[1] = $search_status;
		
		$search_group = get_parameter('search_group', 1);
		$params[2] = $search_group;
		
		$params = implode($token,$params);
		
		$url = $integria_api."&op=get_incidents&token=".$token."&params=".$params;
		$url_resolutions =  $integria_api."&op=get_incidents_resolutions";
		$url_status =  $integria_api."&op=get_incidents_status";
		$url_groups =  $integria_api."&op=get_groups&params=1";
		break;
	case 'incident':
		$url = $integria_api."&op=get_incident_details&params=".$id_incident;
	case 'editor':
		$url_resolutions =  $integria_api."&op=get_incidents_resolutions";
		$url_status =  $integria_api."&op=get_incidents_status";
		$url_sources =  $integria_api."&op=get_incidents_sources";
		$url_groups =  $integria_api."&op=get_groups&params=0";
		$url_users =  $integria_api."&op=get_users";
		break;
	case 'workunits':
		$url = $integria_api."&op=get_incident_workunits&params=".$id_incident;
		break;
	case 'files':
		$url = $integria_api."&op=get_incident_files&params=".$id_incident;
		break;
	case 'tracking':
		$url = $integria_api."&op=get_incident_tracking&params=".$id_incident;
		break;
}

if(isset($url)) {
	// Call the integria API
	$xml = incidents_call_api($url);
}
else {
	$xml = "<xml></xml>";
}

// If is a valid XML, parse it
if(xml_parse(xml_parser_create(), $xml)) {
	// Check if xml is empty
	if($xml == "<xml>\n</xml>\n") {
		$result = false;
	}
	else {
		$result = incidents_xml_to_array($xml);
	}
	
	if($result == false) {
		$result = array();
	}
	switch($tab) {
		case 'list':
			$result_resolutions = incidents_xml_to_array(incidents_call_api($url_resolutions));
			$result_status = incidents_xml_to_array(incidents_call_api($url_status));
			$result_groups = incidents_xml_to_array(incidents_call_api($url_groups));
			require_once('incident.list.php');
			break;
		case 'editor':
		case 'incident':
			$result_resolutions = incidents_xml_to_array(incidents_call_api($url_resolutions));
			$result_status = incidents_xml_to_array(incidents_call_api($url_status));
			$result_sources = incidents_xml_to_array(incidents_call_api($url_sources));
			$result_groups = incidents_xml_to_array(incidents_call_api($url_groups));
			$result_users = incidents_xml_to_array(incidents_call_api($url_users));
			require_once('incident.incident.php');
			break;
		case 'workunits':
			require_once('incident.workunits.php');
			break;
		case 'files':
			require_once('incident.files.php');
			break;
		case 'tracking':
			require_once('incident.tracking.php');
			break;
	}
}


echo '<div style="clear:both">&nbsp;</div>';
?>
