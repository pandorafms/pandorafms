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
 * @subpackage Reporting
 */

require_once ($config['homedir'].'/include/functions_users.php');

/**
 * Get a custom user report.
 *
 * @param int Report id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Report with the given id. False if not available or readable.
 */
function reports_get_report ($id_report, $filter = false, $fields = false) {
	global $config;
	
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return false;
	
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_report'] = $id_report;
	if (!is_user_admin ($config["id_user"]))
		$filter[] = sprintf ('private = 0 OR (private = 1 AND id_user = "%s")',
			$config['id_user']);
	if (is_array ($fields))
		$fields[] = 'id_group';
	
	$report = db_get_row_filter ('treport', $filter, $fields);
	
	if (! check_acl ($config['id_user'], $report['id_group'], 'AR'))
		return false;
	
	return $report;
}

/**
 * Get a list of the reports the user can view.
 *
 * A user can view a report by two ways:
 *  - The user created the report (id_user field in treport)
 *  - The report is not private and the user has reading privileges on 
 *	the group associated to the report
 *
 * @param array Extra filter to retrieve reports. All reports are returned by
 * default
 * @param array Fields to be fetched on every report.
 *
 * @return array An array with all the reports the user can view.
 */
function reports_get_reports ($filter = false, $fields = false, $returnAllGroup = true, $privileges = 'IR') {
	global $config;
	
	if (! is_array ($filter))
		$filter = array ();
	if (!is_user_admin ($config["id_user"]))
		$filter[] = sprintf ('private = 0 OR (private = 1 AND id_user = "%s")',
			$config['id_user']);
	if (is_array ($fields)) {
		$fields[] = 'id_group';
		$fields[] = 'id_user';
	}
	
	$reports = array ();
	$all_reports = @db_get_all_rows_filter ('treport', $filter, $fields);
	if (empty($all_reports))
		$all_reports = array();
	
	//Recheck in all reports if the user have permissions to see each report.
	$groups = users_get_groups ($config['id_user'], $privileges, $returnAllGroup);
	foreach ($all_reports as $report) {
		if (!in_array($report['id_group'], array_keys($groups)))
			continue;
		if ($config['id_user'] != $report['id_user']
			&& ! check_acl ($config['id_user'], $report['id_group'], 'AR'))
			continue;
		array_push ($reports, $report);
	}
	
	return $reports;
}

/**
 * Creates a report.
 *
 * @param string Report name.
 * @param int Group where the report will operate.
 * @param array Extra values to be set. Notice that id_user is automatically
 * set to the logged user.
 * 
 * @return mixed New report id if created. False if it could not be created.
 */
function reports_create_report ($name, $id_group, $values = false) {
	global $config;
	
	if (! is_array ($values))
		$values = array ();
	$values['name'] = $name;
	$values['id_group'] = $id_group;
	$values['id_user'] = $config['id_user'];
	
	return @db_process_sql_insert ('treport', $values);
}


/**
 * Updates a report.
 *
 * @param int Report id.
 * @param array Extra values to be set.
 * 
 * @return bool True if the report was updated. False otherwise.
 */
function reports_update_report ($id_report, $values) {
	$report = reports_get_report ($id_report, false, array ('id_report'));
	if ($report === false)
		return false;
	return (@db_process_sql_update ('treport',
		$values,
		array ('id_report' => $id_report))) !== false;
}

/**
 * Deletes a report.
 * 
 * @param int Report id to be deleted.
 *
 * @return bool True if deleted, false otherwise.
 */
function reports_delete_report ($id_report) {
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return false;
	$report = reports_get_report ($id_report);
	if ($report === false)
		return false;
	@db_process_sql_delete ('treport_content', array ('id_report' => $id_report));
	return @db_process_sql_delete ('treport', array ('id_report' => $id_report));
}

/**
 * Deletes a content from a report.
 * 
 * @param int Report content id to be deleted.
 *
 * @return bool True if deleted, false otherwise.
 */
function reports_get_content ($id_report_content, $filter = false, $fields = false) {
	$id_report_content = safe_int ($id_report_content);
	if (empty ($id_report_content))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	if (is_array ($fields))
		$fields[] = 'id_report';
	$filter['id_rc'] = $id_report_content;
	
	$content = @db_get_row_filter ('treport_content', $filter, $fields);
	if ($content === false)
		return false;
	$report = reports_get_report ($content['id_report']);
	if ($report === false)
		return false;
	return $content;
}

/**
 * Get all the contents of a report.
 *
 * @param int Report id to get contents.
 * @param array Extra filters for the contents.
 * @param array Fields to be fetched. All fields by default
 *
 * @return array All the contents of a report. 
 */
function reports_create_content ($id_report, $values) {
	global $config;
	
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return false;
	$report = reports_get_report ($id_report);
	if ($report === false)
		return false;
	if (! is_array ($values))
		return false;
	$values['id_report'] = $id_report;

	switch ($config["dbtype"]) {
		case "mysql":
			unset ($values['`order`']);
			
			$order = (int) db_get_value ('MAX(`order`)', 'treport_content', 'id_report', $id_report);
			$values['`order`'] = $order + 1;
			break;
		case "postgresql":
		case "oracle":
			unset ($values['"order"']);
			
			$order = (int) db_get_value ('MAX("order")', 'treport_content', 'id_report', $id_report);
			$values['"order"'] = $order + 1;
			break;
	}
	
	return @db_process_sql_insert ('treport_content', $values);
}

/**
 * Get all the contents of a report.
 *
 * @param int Report id to get contents.
 * @param array Extra filters for the contents.
 * @param array Fields to be fetched. All fields by default
 *
 * @return array All the contents of a report. 
 */
function reports_get_contents ($id_report, $filter = false, $fields = false) {
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return array ();
	
	$report = reports_get_report ($id_report);
	if ($report === false)
		return array ();
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_report'] = $id_report;
	$filter['order'] = '`order`';
	
	$contents = db_get_all_rows_filter ('treport_content', $filter, $fields);
	if ($contents === false)
		return array ();
	return $contents;
}

/**
 * Moves a content from a report up.
 * 
 * @param int Report content id to be moved.
 *
 * @return bool True if moved, false otherwise.
 */
function reports_move_content_up ($id_report_content) {
	global $config;
	
	if (empty ($id_report_content))
		return false;
	
	$content = reports_get_content ($id_report_content);
	if ($content === false)
		return false;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$order = db_get_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
			/* Set the previous element order to the current of the content we want to change */
			db_process_sql_update ('treport_content', 
				array ('`order` = `order` + 1'),
				array ('id_report' => $content['id_report'],
					'`order` = '.($order - 1)));
				
			return (@db_process_sql_update ('treport_content',
				array ('`order` = `order` - 1'),
				array ('id_rc' => $id_report_content))) !== false;
			break;
		case "postgresql":
		case "oracle":
			$order = db_get_value ('"order"', 'treport_content', 'id_rc', $id_report_content);
			/* Set the previous element order to the current of the content we want to change */
			db_process_sql_update ('treport_content', 
				array ('"order" = "order" + 1'),
				array ('id_report' => $content['id_report'],
					'"order" = '.($order - 1)));
				
			return (@db_process_sql_update ('treport_content',
				array ('"order" = "order" - 1'),
				array ('id_rc' => $id_report_content))) !== false;
			break;
	}
}

/**
 * Moves a content from a report up.
 * 
 * @param int Report content id to be moved.
 *
 * @return bool True if moved, false otherwise.
 */
function reports_move_content_down ($id_report_content) {
	global $config;
	
	if (empty ($id_report_content))
		return false;
	
	$content = reports_get_content ($id_report_content);
	if ($content === false)
		return false;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$order = db_get_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
			/* Set the previous element order to the current of the content we want to change */
			db_process_sql_update ('treport_content', 
				array ('`order` = `order` - 1'),
				array ('id_report' => (int) $content['id_report'],
					'`order` = '.($order + 1)));
			return (@db_process_sql_update ('treport_content',
				array ('`order` = `order` + 1'),
				array ('id_rc' => $id_report_content))) !== false;
			break;
		case "postgresql":
		case "oracle":
			$order = db_get_value ('"order"', 'treport_content', 'id_rc', $id_report_content);
			/* Set the previous element order to the current of the content we want to change */
			db_process_sql_update ('treport_content', 
				array ('"order" = "order" - 1'),
				array ('id_report' => (int) $content['id_report'],
					'"order" = '.($order + 1)));
			return (@db_process_sql_update ('treport_content',
				array ('"order" = "order" + 1'),
				array ('id_rc' => $id_report_content))) !== false;
			break;
	}
}

/**
 * Deletes a content from a report.
 * 
 * @param int Report content id to be deleted.
 *
 * @return bool True if deleted, false otherwise.
 */
function reports_delete_content ($id_report_content) {
	if (empty ($id_report_content))
		return false;
	
	$content = reports_get_content ($id_report_content);
	if ($content === false)
		return false;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$order = db_get_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
			db_process_sql_update ('treport_content',
				array ('`order` = `order` - 1'),
				array ('id_report' => (int) $content['id_report'],
					'`order` > '.$order));
			break;
		case "postgresql":
		case "oracle":
			$order = db_get_value ('"order"', 'treport_content', 'id_rc', $id_report_content);
			db_process_sql_update ('treport_content',
				array ('"order" = "order" - 1'),
				array ('id_report' => (int) $content['id_report'],
					'"order" > '.$order));
			break;
	}
		
	return (@db_process_sql_delete ('treport_content',
		array ('id_rc' => $id_report_content))) !== false;
}
?>
