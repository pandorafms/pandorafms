<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

function update_pandora_get_packages_online_ajax() {
	global $config;
	global $conf_update_pandora;
	if (empty($conf_update_pandora))
		$conf_update_pandora = update_pandora_get_conf();
	
	require_once ($config["homedir"] .
		"/extensions/update_manager/lib/libupdate_manager_client.php");
	require_once ($config["homedir"] .
		"/extensions/update_manager/lib/libupdate_manager.php");
	require_once ($config["homedir"] .
		"/extensions/update_manager/load_updatemanager.php");
	
	$last = get_parameter('last', 0);
	
	db_clean_cache();
	$settings = um_db_load_settings ();
	$user_key = get_user_key ($settings);
	
	$params = array(
		new xmlrpcval((int)$conf_update_pandora['last_contact'], 'int'),
		new xmlrpcval($user_key, 'string'),
		new xmlrpcval($settings->customer_key, 'string'));
	
	$result = um_xml_rpc_client_call ('192.168.70.202',
		'upd/server/pandora-server.php',
		'80',
		'',
		'',
		'',
		'',
		'get_lastest_package_update_open',
		$params);
	
	if ($result == false) {
		$return['last'] = $last;
		$return['correct'] = 0;
		$return['package'] = __('Error download packages.');
		$return['end'] = 1;
	}
	else {
		$value = $result->value();
		$package = $value->scalarval();
		
		$return['correct'] = 1;
		if (empty($package)) {
			$return['correct'] = 0;
		}
		
		$return['last'] = $last;
		$return['package'] = $package;
		$return['end'] = 1;
	}
	
	echo json_encode($return);
}

function update_pandora_download_package() {
	global $config;
	global $conf_update_pandora;
	if (empty($conf_update_pandora))
		$conf_update_pandora = update_pandora_get_conf();
	
	require_once ($config["homedir"] .
		"/extensions/update_manager/lib/libupdate_manager_client.php");
	
	$dir = $config['attachment_store'] .  '/update_pandora/';
	
	$package = get_parameter('package', '');
	
	$params = array(new xmlrpcval($package, 'string'));
	
	$result = um_xml_rpc_client_call ('192.168.70.166',
		'upd/server/example-server.php',
		'80',
		'',
		'',
		'',
		'',
		'get_lastest_package_url_update_open',
		$params);
	
	if ($result == false) {
		$info_json = json_encode(array('correct' => 0));
		
		file_put_contents('/tmp/' . $package . '.info.txt', $info_json, LOCK_EX);
		
		$return = array('correct' => 0);
	}
	else {
		$conf_update_pandora['last_contact'] = time();
		update_pandora_update_conf();
		
		$value = $result->value();
		$package_url = $value->scalarval();
		
		if (empty($package_url)) {
			$info_json = json_encode(array('correct' => 0));
			
			file_put_contents('/tmp/' . $package . '.info.txt', $info_json, LOCK_EX);
			
			$return = array('correct' => 0);
		}
		else {
			$targz = $package;
			$url = $package_url;
			
			$file = fopen($dir . $targz, "w");
			
			$mch = curl_multi_init();
			$c = curl_init();
			
			curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($c, CURLOPT_FILE, $file);
			
			curl_multi_add_handle($mch ,$c);
			$running = null;
			do {
				curl_multi_exec($mch ,$running);
				$info = curl_getinfo ($c);
				
				$data = array();
				$data['correct'] = 1;
				$data['filename'] = $targz;
				$data['size'] = $info['download_content_length'];
				$data['size_download'] = $info['size_download'];
				$data['speed_download'] = $info['speed_download'];
				
				$info_json = json_encode($data);
				
				file_put_contents('/tmp/' . $package . '.info.txt', $info_json, LOCK_EX);
				
				sleep(1);
			}
			while($running > 0);
			
			$return = array('correct' => 1);
		}
	}
	
	echo json_encode($return);
}

function update_pandora_check_download_package() {
	global $config;
	
	require_once ($config["homedir"] . '/include/functions_graph.php');
	
	sleep(1);
	
	$package = get_parameter('package', '');
	$return = array('correct' => 1,
		'info_download' => "<b>Size:</b> %s/%s bytes <b>Speed:</b> %s bytes/second",
		'progres_bar' => progress_bar(0, 300, 20, '0%', 1, false, "#00ff00"),
		'progres_bar_text' => '0%',
		'percent' => 0);
	
	$info_json = @file_get_contents('/tmp/' . $package . '.info.txt');
	
	$info = json_decode($info_json, true);
	
	if ($info['correct'] == 0) {
		$return['correct'] = 0;
		unlink('/tmp/' . $package . '.info.txt');
	}
	else {
		$percent = 0;
		$size_download = 0;
		$size = 0;
		$speed_download = 0;
		if ($info['size_download'] > 0) {
			$percent = format_numeric(
				($info['size_download'] / $info['size']) * 100, 2);
			$return['percent'] = $percent;
			$size_download = $info['size_download'];
			$size = $info['size'];
			$speed_download = $info['speed_download'];
			
			$return['info_download'] = sprintf($return['info_download'],
				format_for_graph($size_download, 2), format_for_graph($size, 2),
				format_for_graph($speed_download, 2));
		}
		else {
			$return['info_download'] = __('<b>Starting: </b> connect to server');
		}
		
		$img = progress_bar($percent, 300, 20, $percent . '%', 1, false, "#00ff00");
		$return['progres_bar'] = $img;
		preg_match_all('/src=[\'\"]([^\"^\']*)[\'\"]/i', $img, $attr);
		$return['progres_bar_src'] = $attr[1];
		preg_match_all('/alt=[\'\"]([^\"^\']*)[\'\"]/i', $img, $attr);
		$return['progres_bar_alt'] = $attr[1];
		preg_match_all('/title=[\'\"]([^\"^\']*)[\'\"]/i', $img, $attr);
		$return['progres_bar_title'] = $attr[1];
		$return['filename'] = $info['filename'];
	}
	
	echo json_encode($return);
}

function update_pandora_install_package() {
	global $config;
	global $conf_update_pandora;
	if (empty($conf_update_pandora))
		$conf_update_pandora = update_pandora_get_conf();
	
	$dir = $config['attachment_store'] .  '/update_pandora/';
	
	$package = get_parameter('package', '');
	$filename = get_parameter('filename', '');
	
	//Get total files
	$command = 'tar tzvf ' . $dir . $filename . '| wc -l > /tmp/' . $package . '.info.txt';
	exec($command, $output, $status);
	html_debug_print($command, true);
	
	$command = 'tar xzvf ' . $dir . $filename . ' -C ' . $config['homedir'] . ' 1>/tmp/' . $package . '.files.info.txt';
	html_debug_print($command, true);
	
	//Maybe this line run for seconds or minutes
	exec($command, $output, $status);
	
	if (($status == 0) || ($status == 2)) {
		$conf_update_pandora['last_installed'] = $filename;
		update_pandora_update_conf();
		echo json_encode(array('correct' => 1));
	}
	else {
		echo json_encode(array('correct' => 0));
	}
}

function update_pandora_check_install_package() {
	global $config;
	
	require_once ($config["homedir"] . '/include/functions_graph.php');
	
	sleep(1);
	
	$package = get_parameter('package', '');
	$filename = get_parameter('filename', '');
	
	//TODO MAYBE THE FILE OF FILES DON'T EXIST AT THE MOMENT
	
	$files = @file('/tmp/' . $package . '.files.info.txt');
	if (empty($files))
		$files = array();
	$total = (int)file_get_contents('/tmp/' . $package . '.info.txt');
	
	$return = array('correct' => 1,
		'info' => "<div id='list_files_install'
			style='text-align: left; margin: 10px; padding: 5px; width: 90%%; height: 100px;
			overflow: scroll; border: 1px solid #666'>%s</div>",
		'src' => progress_bar(0, 300, 20, '0%', 1, false, "#0000ff"),
		'alt' => '0%',
		'percent' => 0);
	
	$percent = 0;
	if ((count($files) > 0) && ($total > 0)) {
		$percent = format_numeric((count($files) / $total) * 100, 2);
		if ($percent > 100)
			$percent = 100;
	}
	
	$files_txtbox = (string)implode("<br />", $files);
	$return['info'] = sprintf($return['info'], $files_txtbox);
	$img = progress_bar($percent, 300, 20, $percent . '%', 1, false, "#0000ff");
	$return['percent'] = $percent;
	preg_match_all('/src=[\'\"]([^\"^\']*)[\'\"]/i', $img, $attr);
	$return['src'] = $attr[1];
	preg_match_all('/alt=[\'\"]([^\"^\']*)[\'\"]/i', $img, $attr);
	$return['alt'] = $attr[1];
	preg_match_all('/title=[\'\"]([^\"^\']*)[\'\"]/i', $img, $attr);
	$return['title'] = $attr[1];
	
	if ($percent == 100) {
		unlink('/tmp/' . $package . '.files.info.txt');
		unlink('/tmp/' . $package . '.info.txt');
	}
	
	echo json_encode($return);
}

?>
