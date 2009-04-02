<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

/**
 * Get a custom user report.
 *
 * @param int Report id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Report with the given id. False if not available or readable.
 */
function get_report ($id_report, $filter = false, $fields = false) {
	global $config;
	
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_report'] = $id_report;
	$filter[] = sprintf ('private = 0 OR (private = 1 AND id_user = "%s")', $config['id_user']);
	if (is_array ($fields))
		$fields[] = 'id_group';
	
	$report = get_db_row_filter ('treport', $filter, $fields);
	if (! give_acl ($config['id_user'], $report['id_group'], 'AR'))
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
function get_reports ($filter = false, $fields = false) {
	global $config;
	
	if (! is_array ($filter))
		$filter = array ();
	$filter[] = sprintf ('private = 0 OR (private = 1 AND id_user = "%s")', $config['id_user']);
	if (is_array ($fields)) {
		$fields[] = 'id_group';
		$fields[] = 'id_user';
	}
	
	$reports = array ();
	$all_reports = @get_db_all_rows_filter ('treport', $filter, $fields);
	if ($all_reports !== FALSE)
	foreach ($all_reports as $report){
		if ($config['id_user'] != $report['id_user'] && ! give_acl ($config['id_user'], $report['id_group'], 'AR'))
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
function create_report ($name, $id_group, $values = false) {
	global $config;
	
	if (! is_array ($values))
		$values = array ();
	$values['name'] = $name;
	$values['id_group'] = $id_group;
	$values['id_user'] = $config['id_user'];
	
	return @process_sql_insert ('treport', $values);
}


/**
 * Updates a report.
 *
 * @param int Report id.
 * @param array Extra values to be set.
 * 
 * @return bool True if the report was updated. False otherwise.
 */
function update_report ($id_report, $values) {
	$report = get_report ($id_report);
	if ($report === false)
		return false;
	return (@process_sql_update ('treport',
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
function delete_report ($id_report) {
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return false;
	$report = get_report ($id_report);
	if ($report === false)
		return false;
	$res1 = @process_sql_delete ('treport_content', array ('id_report' => $id_report));
	$res2 = @process_sql_delete ('treport', array ('id_report' => $id_report));
	
	return $res1 && $res2;
}

/**
 * Deletes a content from a report.
 * 
 * @param int Report content id to be deleted.
 *
 * @return bool True if deleted, false otherwise.
 */
function get_report_content ($id_report_content, $filter = false, $fields = false) {
	$id_report_content = safe_int ($id_report_content);
	if (empty ($id_report_content))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	if (is_array ($fields))
		$fields[] = 'id_report';
	$filter['id_rc'] = $id_report_content;
	
	$content = @get_db_row_filter ('treport_content', $filter, $fields);
	if ($content === false)
		return false;
	$report = get_report ($content['id_report']);
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
function create_report_content ($id_report, $values) {
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return false;
	$report = get_report ($id_report);
	if ($report === false)
		return false;
	if (! is_array ($values))
		return false;
	$values['id_report'] = $id_report;
	unset ($values['`order`']);
	$order = (int) get_db_value ('MAX(`order`)', 'treport_content', 'id_report', $id_report);
	$values['`order`'] = $order + 1;
	
	return @process_sql_insert ('treport_content', $values);
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
function get_report_contents ($id_report, $filter = false, $fields = false) {
	$id_report = safe_int ($id_report);
	if (empty ($id_report))
		return array ();
	
	$report = get_report ($id_report);
	if ($report === false)
		return array ();
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_report'] = $id_report;
	$filter['order'] = '`order`';
	
	$contents = get_db_all_rows_filter ('treport_content', $filter, $fields);
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
function move_report_content_up ($id_report_content) {
	if (empty ($id_report_content))
		return false;
	
	$content = get_report_content ($id_report_content);
	if ($content === false)
		return false;
	$order = get_db_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
	/* Set the previous element order to the current of the content we want to change */
	process_sql_update ('treport_content', 
		array ('`order` = `order` + 1'),
		array ('id_report' => $content['id_report'],
			'`order` = '.($order - 1)));
	return (@process_sql_update ('treport_content',
		array ('`order` = `order` - 1'),
		array ('id_rc' => $id_report_content))) !== false;
}

/**
 * Moves a content from a report up.
 * 
 * @param int Report content id to be moved.
 *
 * @return bool True if moved, false otherwise.
 */
function move_report_content_down ($id_report_content) {
	if (empty ($id_report_content))
		return false;
	
	$content = get_report_content ($id_report_content);
	if ($content === false)
		return false;
	$order = get_db_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
	/* Set the previous element order to the current of the content we want to change */
	process_sql_update ('treport_content', 
		array ('`order` = `order` - 1'),
		array ('id_report' => (int) $content['id_report'],
			'`order` = '.($order + 1)));
	return (@process_sql_update ('treport_content',
		array ('`order` = `order` + 1'),
		array ('id_rc' => $id_report_content))) !== false;
}

/**
 * Deletes a content from a report.
 * 
 * @param int Report content id to be deleted.
 *
 * @return bool True if deleted, false otherwise.
 */
function delete_report_content ($id_report_content) {
	if (empty ($id_report_content))
		return false;
	
	$content = get_report_content ($id_report_content);
	if ($content === false)
		return false;
	$order = get_db_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
	process_sql_update ('treport_content',
		array ('`order` = `order` - 1'),
		array ('id_report' => (int) $content['id_report'],
			'`order` > '.$order));
	return (@process_sql_delete ('treport_content',
		array ('id_rc' => $id_report_content))) !== false;
}
?>
