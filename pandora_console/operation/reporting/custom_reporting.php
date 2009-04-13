<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Login check
check_login ();

require_once ('include/functions_reports.php');

// Load enterprise extensions
enterprise_include ('operation/reporting/custom_reporting.php');

echo "<h2>".__('Reporting')." &raquo; ";
echo __('Custom reporting')."</h2>";

$reports = get_reports ();

if (sizeof ($reports) == 0) {
	echo "<div class='nf'>".__('There are no defined reportings')."</div>";
	return;
}

$table->width = '580px';
$table->head = array ();
$table->head[0] = __('Report name');
$table->head[1] = __('Description');
$table->head[2] = __('HTML');
$table->head[3] = __('XML');

enterprise_hook ('load_custom_reporting_1');

$table->align = array ();
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->data = array ();

foreach ($reports as $report) {
	if ($report['private'] && ($report['id_user'] != $config['id_user'] && ! is_user_admin ($config['id_user']))) {
		continue;
	}
	
	$data = array ();
	
	$data[0] = $report['name'];
	$data[1] = $report['description'];
	$data[2] = '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'">
			<img src="images/reporting.png" /></a>';
	$data[3] = '<a href="ajax.php?page=operation/reporting/reporting_xml&id='.$report['id_report'].'"><img src="images/database_lightning.png" /></a>'; //I chose ajax.php because it's supposed to give XML anyway

	enterprise_hook ('load_custom_reporting_2');
	array_push ($table->data, $data);
}

print_table ($table);
?>
