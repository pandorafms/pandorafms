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

global $config;

include_once('include/functions_reports.php');

$linkReport = false;
$searchReports = check_acl ($config["id_user"], 0, "RR");

if (check_acl ($config['id_user'], 0, "RW")) {
	$linkReport = true;
}

$reports = false;

//Check ACL
$userreports = reports_get_reports();

$userreports_id = array();
foreach($userreports as $userreport) {
	$userreports_id[] = $userreport['id_report'];
}

if(!$userreports_id){
	$reports_condition = " AND 1<>1";
}
else {
	$reports_condition = " AND id_report IN (".implode(',',$userreports_id).")";
}
	
$reports = false;

if($searchReports) {
	$sql = "SELECT id_report, name, description
		FROM treport
		WHERE (name LIKE '%" . $stringSearchSQL . "%' OR description LIKE '%" . $stringSearchSQL . "%')".$reports_condition.
		" LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$sql_count = "SELECT COUNT(id_report) AS count
		FROM treport
		WHERE (name LIKE '%" . $stringSearchSQL . "%' OR description LIKE '%" . $stringSearchSQL . "%')".$reports_condition;
		
	if($only_count) {
		$totalReports = db_get_value_sql($sql_count);
	}
	else {
		$reports = db_process_sql($sql);
		$totalReports = db_get_value_sql($sql_count);
	}
}
?>
