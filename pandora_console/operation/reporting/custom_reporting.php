<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Login check
require ("include/config.php");

check_login ();

echo "<h2>".__('reporting')." &gt; ";
echo __('custom_reporting')."</h2>";

$reports = get_reports ($config['id_user']);

if (sizeof ($reports) == 0) {
	echo "<div class='nf'>".__('no_reporting_def')."</div>";
	return;
}

$table->width = '580px';
$table->head = array ();
$table->head[0] = __('report_name');
$table->head[1] = __('description');
$table->head[2] = __('HTML');
$table->head[3] = __('PDF');
$table->align = array ();
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->data = array ();

foreach ($reports as $report) {
	$data = array ();
	
	$data[0] = $report['name'];
	$data[1] = $report['description'];
	$data[2] = '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'">
			<img src="images/reporting.png"></a>';
	$data[3] = '<a href="operation/reporting/reporting_viewer_pdf.php?id_report='.$report['id_report'].'" '.
		'target="_new"><img src="images/pdf.png"></a>';
	array_push ($table->data, $data);
}

print_table ($table);
?>
