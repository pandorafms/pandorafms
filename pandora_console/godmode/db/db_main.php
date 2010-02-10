<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
require_once ("include/config.php");
if ($config['flash_charts']) {
	require_once ("include/fgraph.php");
}

check_login ();

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Database Management");
	require ("general/noaccess.php");
	return;
}

// Get some general DB stats (not very heavy)
// NOTE: this is not realtime monitoring stats, are more focused on DB sanity

$stat_access = get_db_sql ("SELECT COUNT(*) FROM tagent_access WHERE id_agent != 0");
$stat_data = get_db_sql ("SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo != 0");
$stat_data_string = get_db_sql ("SELECT COUNT(*) FROM tagente_datos_string WHERE id_agente_modulo != 0");
$stat_modules = get_db_sql ("SELECT COUNT(*) FROM tagente_estado WHERE id_agente_modulo != 0");
$stat_event = get_db_sql (" SELECT COUNT(*) FROM tevento");
$stat_agente = get_db_sql (" SELECT COUNT(*) FROM tagente");
$stat_uknown = get_db_sql ("SELECT COUNT(*) FROM tagente WHERE ultimo_contacto < NOW() - (intervalo *2)");
$stat_noninit = get_db_sql ("SELECT COUNT(*) FROM tagente_estado WHERE utimestamp = 0;");

// Todo: Recalculate this data dinamically using the capacity and total agents

$max_access = 1000000;
$max_data = 12000000;

echo '<h2>'.__('Database maintenance').' &raquo; '.__('Current database maintenance setup').'</h2>';

echo '<table class=databox width="550" cellspacing="4" cellpadding="4" border="0">';

// Current setup

echo '<tr><th colspan=2><i>';
echo __('Database setup');
echo '</i></td></tr>';

echo '<tr class="rowOdd"><td>';
echo __('Max. time before compact data');
echo '<td><b>';
echo   $config['days_compact'].' '.__('days');
echo '</b></td></tr>';

echo '<tr class="rowPair"><td>';
echo __('Max. time before purge');
echo '<td><b>';
echo $config['days_purge'].' '.__('days');
echo '</b></td></tr>';


// DB size stats

echo '<tr><th colspan=2><i>';
echo __('Database size stats');
echo '</i></td></tr>';

echo '<tr class="rowPair"><td>';
echo __('Total agents');
echo '<td><b>';
echo $stat_agente;
echo '</b></td></tr>';

echo '<tr class="rowOdd"><td>';
echo __('Total events');
echo '<td><b>';
echo $stat_event;
echo '</b></td></tr>';

echo '<tr class="rowPair"><td>';
echo __('Total data items (tagente_datos)');
echo '<td><b>';

if ($stat_data > $max_data)
	echo "<font color='#ff0000'>$stat_data</font>";
else
	echo $stat_data;

echo '</b></td></tr>';

echo '<tr class="rowOdd"><td>';
echo __('Total data string items (tagente_datos_string)');
echo '<td><b>';
echo $stat_data_string;
echo '</b></td></tr>';

echo '<tr class="rowPair"><td>';
echo __('Total modules configured');
echo '<td><b>';
echo $stat_modules;
echo '</b></td></tr>';



echo '<tr class="rowOdd"><td>';
echo __('Total agent access records');
echo '<td><b>';
if ($stat_access > $max_access)
	echo "<font color='#ff0000'>$stat_access</font>";
else
	echo $stat_access;
echo '</b></td></tr>';

// Sanity

echo '<tr><th colspan=2><i>';
echo __('Database sanity');
echo '</i></td></tr>';

echo '<tr class="rowPair"><td>';
echo __('Total uknown agents');
echo '<td><b>';
echo $stat_uknown;
echo '</b></td></tr>';

echo '<tr class="rowOdd"><td>';
echo __('Total non-init modules');
echo '<td><b>';
echo $stat_noninit;
echo '</b></td></tr>';




echo '<tr class="rowPair"><td>';
echo __('Last time on DB maintance');
echo '<td>';

if (!isset($config['db_maintance'])){
	echo "<b><font size=12px>".__("Never")."</font></b>";
} else {
	$seconds = time()-$config['db_maintance'];
	if ($seconds > 90000)  //(1,1 days)
		echo "<b><font color='#ff0000' size=12px>";
	else
		echo "<font><b>";

	echo human_time_description($seconds);
	echo " *";
}
echo "</td></tr>";


echo '<tr><td colspan=2>';
echo '<div align="justify"><br><hr width=100%>';
echo '(*) '.__('Please check your Pandora Server setup and be sure that database maintenance daemon is running. It\'s very important to keep up-to-date database to get the best performance and results in Pandora');
echo '</div>';
echo '</td></tr></table>';
?>
