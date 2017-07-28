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
 * Generates a trap
 *
 * @param string Destiny host address.
 * @param string Snmp community.
 * @param string Snmp OID.
 * @param string Snmp agent.
 * @param string Data of the trap.
 * @param string Snmp especific OID.
 */
function snmp_generate_trap($snmp_host_address, $snmp_community, $snmp_oid, $snmp_agent, $snmp_data, $snmp_type) {
	// Call snmptrap
	if (empty($config['snmptrap'])) {
		switch (PHP_OS) {
			case "FreeBSD":
				$snmptrap_bin = '/usr/local/bin/snmptrap';
				break;
			case "NetBSD":
				$snmptrap_bin = '/usr/pkg/bin/snmptrap';
				break;
			default:
				$snmptrap_bin = 'snmptrap';
				break;
		}
	}
	else {
		$snmptrap_bin = $config['snmptrap'];
	}
	
	$command = "$snmptrap_bin -v 1 -c " . escapeshellarg($snmp_community) .
		" " . escapeshellarg($snmp_host_address) .
		" " . escapeshellarg($snmp_oid) .
		" " . escapeshellarg($snmp_agent) .
		" " . escapeshellarg($snmp_type) .
		" " . escapeshellarg($snmp_data) . " 0 2>&1";
	
	$output = null;
	exec($command, $output, $return);
	
	if ($return == 0) {
		return true;
	}
	else {
		return implode(' ', $output);
	}
}

function snmp_get_default_translations() {
	$return = array();
	$return['.1.3.6.1.4.1.2021.10.1.5.1'] = array(
		'description' => __('Load Average (Last minute)'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.10.1.5.2'] = array(
		'description' => __('Load Average (Last 5 minutes)'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.10.1.5.3'] = array(
		'description' => __('Load Average (Last 15 minutes)'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.4.3.0'] = array(
		'description' => __('Total Swap Size configured for the host'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.4.4.0'] = array(
		'description' => __('Available Swap Space on the host'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.4.5.0'] = array(
		'description' => __('Total Real/Physical Memory Size on the host'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.4.6.0'] = array(
		'description' => __('Available Real/Physical Memory Space on the host'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.4.11.0'] = array(
		'description' => __('Total Available Memory on the host'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.4.15.0'] = array(
		'description' => __('Total Cached Memory'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.4.14.0'] = array(
		'description' => __('Total Buffered Memory'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.3.0'] = array(
		'description' => __('Amount of memory swapped in from disk (kB/s)'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.4.0'] = array(
		'description' => __('Amount of memory swapped to disk (kB/s)'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.57.0'] = array(
		'description' => __('Number of blocks sent to a block device'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.58.0'] = array(
		'description' => __('Number of blocks received from a block device'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.59.0'] = array(
		'description' => __('Number of interrupts processed'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.60.0'] = array(
		'description' => __('Number of context switches'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.50.0'] = array(
		'description' => __('user CPU time'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.52.0'] = array(
		'description' => __('system CPU time'),
		'post_process' => '1'
		);
	$return['.1.3.6.1.4.1.2021.11.53.0'] = array(
		'description' => __('idle CPU time'),
		'post_process' => '1'
		);
	$return['1.3.6.1.2.1.1.3.0'] = array(
		'description' => __('system Up time'),
		'post_process' => '0.00000011574074'
		);
	
	return $return;
}

function snmp_get_user_translations() {
	$row = db_get_row('tconfig', 'token', 'snmp_translations');
	
	if (empty($row)) {
		db_process_sql_insert('tconfig',
			array('token' => 'snmp_translations',
				'value' => json_encode(array())));
		
		$return = array();
	}
	else {
		$return = json_decode($row['value'], true);
	}
	
	return $return;
}

function snmp_get_translation_wizard() {
	$return = array();
	
	$snmp_default_translations = snmp_get_default_translations();
	$snmp_user_translations = snmp_get_user_translations();
	
	foreach ($snmp_default_translations as $oid => $translation) {
		$return[$oid] = array_merge($translation, array('readonly' => 1));
	}
	
	foreach ($snmp_user_translations as $oid => $translation) {
		$return[$oid] = array_merge($translation, array('readonly' => 0));
	}
	
	return $return;
}

function snmp_save_translation($oid, $description, $post_process) {
	$row = db_get_row('tconfig', 'token', 'snmp_translations');
	
	if (empty($row)) {
		db_process_sql_insert('tconfig',
			array('token' => 'snmp_translations',
				'value' => json_encode(array())));
		
		$snmp_translations = array();
	}
	else {
		$snmp_translations = json_decode($row['value'], true);
	}
	
	if (isset($snmp_translations[$oid])) {
		// exists the oid
		return false;
	}
	else {
		$snmp_translations[$oid] = array(
			'description' => $description,
			'post_process' => $post_process
			);
		
		return (bool)db_process_sql_update('tconfig',
			array('value' => json_encode($snmp_translations)),
			array('token' => 'snmp_translations'));
	}
}

function snmp_delete_translation($oid) {
	$row = db_get_row('tconfig', 'token', 'snmp_translations');
	
	if (empty($row)) {
		db_process_sql_insert('tconfig',
			array('token' => 'snmp_translations',
				'value' => json_encode(array())));
		
		$snmp_translations = array();
	}
	else {
		$snmp_translations = json_decode($row['value'], true);
	}
	
	if (isset($snmp_translations[$oid])) {
		unset($snmp_translations[$oid]);
		
		return (bool)db_process_sql_update('tconfig',
			array('value' => json_encode($snmp_translations)),
			array('token' => 'snmp_translations'));
	}
	else {
		// exists the oid
		return false;
	}
}

function snmp_get_translation($oid) {
	$snmp_translations = snmp_get_translation_wizard();
	
	return $snmp_translations[$oid];
}

function snmp_update_translation($oid, $new_oid, $description, $post_process) {
	$row = db_get_row('tconfig', 'token', 'snmp_translations');
	
	if (empty($row)) {
		db_process_sql_insert('tconfig',
			array('token' => 'snmp_translations',
				'value' => json_encode(array())));
		
		$snmp_translations = array();
	}
	else {
		$snmp_translations = json_decode($row['value'], true);
	}
	
	if (isset($snmp_translations[$new_oid])) {
		return false;
	}
	else {
		if (isset($snmp_translations[$oid])) {
			unset($snmp_translations[$oid]);
			
			$snmp_translations[$new_oid] = array(
				'description' => $description,
				'post_process' => $post_process
				);
			
			return (bool)db_process_sql_update('tconfig',
				array('value' => json_encode($snmp_translations)),
				array('token' => 'snmp_translations'));
		}
		else {
			return false;
		}
	}
}

/*
 * Print the event filter tag cloud (enterprise feature)
 * @param $filter_resume: Array with the active filters
 * @param $filter_refs: Array of all arrays to humanize some tags
 */
function print_snmp_tags_active_filters ($filter_resume = array()) {
	global $config;

	$tags_set = array();

	// Alert
	if (isset($filter_resume['filter_fired'])) {
		array_push ($tags_set, array(
			'title' => __('Alert'),
			'value' => $filter_resume['filter_fired']
		));
	}
	// Severity
	if (isset($filter_resume['filter_severity'])) {
		array_push ($tags_set, array(
			'title' => __('Severity'),
			'value' => $filter_resume['filter_severity']
		));
	}
	// Pagination
	if ($filter_resume['pagination'] == "") {
		$filter_resume['pagination'] = $config["block_size"];
	}
	array_push ($tags_set, array(
		'title' => __('Pagination'),
		'value' => $filter_resume['pagination']
	));
	// Free search
	if (isset($filter_resume['free_search_string']) && $filter_resume['free_search_string'] != "") {
		array_push ($tags_set, array(
			'title' => __('Search'),
			'value' => $filter_resume['free_search_string']
		));
	}
	// Status
	if (isset($filter_resume['filter_status']) && $filter_resume['filter_status'] != "") {
		array_push ($tags_set, array(
			'title' => __('Status'),
			'value' => $filter_resume['filter_status']
		));
	}
	// Group by
	if (isset($filter_resume['group_by'])) {
		if ($filter_resume['group_by'] == 0) {
			array_push ($tags_set, array(
				'title' => __('Group by Enterprise String / IP'),
				'value' => __('No')
			));
		}
		else {
			array_push ($tags_set, array(
				'title' => __('Group by Enterprise String / IP'),
				'value' => __('Yes')
			));
		}
	}
	// Date from
	if (isset($filter_resume['date_from_trap']) && $filter_resume['date_from_trap'] != "") {
		if (isset($filter_resume['time_from_trap']) && $filter_resume['time_from_trap'] != "") {
			array_push ($tags_set, array(
				'title' => __('From'),
				'value' => $filter_resume['date_from_trap'] . " " . $filter_resume['time_from_trap']
			));
		}
		else {
			array_push ($tags_set, array(
				'title' => __('From'),
				'value' => $filter_resume['date_from_trap']
			));
		}
	}
	// Date to
	if (isset($filter_resume['date_to_trap']) && $filter_resume['date_to_trap'] != "") {
		if (isset($filter_resume['time_to_trap']) && $filter_resume['time_to_trap'] != "") {
			array_push ($tags_set, array(
				'title' => __('To'),
				'value' => $filter_resume['date_to_trap'] . " " . $filter_resume['time_to_trap']
			));
		}
		else {
			array_push ($tags_set, array(
				'title' => __('To'),
				'value' => $filter_resume['date_to_trap']
			));
		}
	}
	//  Trap type
	if (isset($filter_resume['trap_type'])) {
		array_push ($tags_set, array(
			'title' => __('Trap type'),
			'value' => $filter_resume['trap_type']
		));
	}
	$title = '';
	if(!$config['pure']){
		$title = isset($filter_resume['title'])
			? __('Active filter') . " (" . $filter_resume['title'] . ")"
			: __('Active filters');
	} 
	if (sizeof($filter_resume) > 0) ui_print_tags_view($title, $tags_set);
}
?>
