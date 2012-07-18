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
global $config;

require_once ($config["homedir"] . '/include/functions_graph.php'); 
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . "/include/functions_modules.php");

check_login ();

if (! check_acl ($config['id_user'], 0, "DM")) {
	db_pandora_audit( "ACL Violation",
		"Trying to access Database Purge Section");
	include ("general/noaccess.php");
	exit;
}

//id_agent = -1: None selected; id_agent = 0: All
$id_agent = (int) get_parameter_post ("agent", -1);

ui_print_page_header (__('Database maintenance').' &raquo; '.__('Database purge'), "images/god8.png", false, "", true);

echo grafico_db_agentes_purge($id_agent);

echo '<br /><br />';
echo '<h4>'.__('Get data from agent').'</h4>';

// All data (now)
$time["all"] = get_system_time ();
// 1 day ago
$time["1day"] = $time["all"] - SECONDS_1DAY;
// 3 days ago
$time["3day"] = $time["all"] - SECONDS_1DAY * 3;
// 1 week ago
$time["1week"] = $time["all"] - SECONDS_1WEEK;
// 2 weeks ago
$time["2week"] = $time["all"] - SECONDS_1WEEK * 2;
// 1 month ago
$time["1month"] = $time["all"] - SECONDS_1MONTH;
// Three months ago
$time["3month"] = $time["all"] - SECONDS_3MONTHS;

//Init data
$data["1day"] = 0;
$data["3day"] = 0;
$data["1week"] = 0;
$data["2week"] = 0; 
$data["1month"] = 0; 
$data["3month"] = 0; 
$data["total"] = 0;


// Purge data using dates
if (isset($_POST["purgedb"])) {
	$from_date = get_parameter_post ("date_purge", 0);
	if ($id_agent > 0) {
		echo __('Purge task launched for agent')." ".agents_get_name ($id_agent)." :: ".__('Data older than')." ".human_time_description_raw ($from_date);
		echo "<h3>".__('Please be patient. This operation can take a long time depending on the amount of modules.')."</h3>";
		
		$sql = sprintf ("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = %d", $id_agent);
		$result = db_get_all_rows_sql ($sql);
		if (empty ($result)) {
			$result = array ();
		}
		
		//Made it in a transaction so it gets done all at once.
		db_process_sql_begin ();
		
		$errors = 0;
		$affected = 0;
		foreach ($result as $row) {
			echo __('Deleting records for module')." ".modules_get_agentmodule_name ($row["id_agente_modulo"]);
			echo "<br />";
			flush (); //Flush here in case there are errors and the script dies, at least we know where we ended
			set_time_limit (); //Reset the time limit just in case
			
			$result = db_process_sql_delete('tagente_datos', array('id_agente_modulo' => $row["id_agente_modulo"], 'utimestamp' => '< ' . $from_date));
			
			if ($result === false)
				$errors++;
			else
				$affected += $result;
			
			if ($errors == 0) {
				$result = db_process_sql_delete('tagente_datos_inc', array('id_agente_modulo' => $row["id_agente_modulo"], 'utimestamp' => '< ' . $from_date));
				
				if ($result === false)
					$errors++;
				else
					$affected += $result;
			}
			if ($errors == 0) {
				$result = db_process_sql_delete('tagente_datos_string', array('id_agente_modulo' => $row["id_agente_modulo"], 'utimestamp' => '< ' . $from_date));
				
				if ($result === false)
					$errors++;
				else
					$affected += $result;
			}
			if ($errors == 0) {
				$result = db_process_sql_delete('tagente_datos_log4x', array('id_agente_modulo' => $row["id_agente_modulo"], 'utimestamp' => '< ' . $from_date));
				
				if ($result === false)
					$errors++;
				else
					$affected += $result;
			}
		}
		
		if ($errors > 0) {
			db_process_sql_rollback ();
		}
		else {
			db_process_sql_commit ();
			
			echo __('Total records deleted: ') . $affected;
		}
	}
	else {
		//All agents
		echo __('Deleting records for all agents');
		flush ();
		
		db_process_sql_delete('tagente_datos', array('utimestamp' => '< ' . $from_date));
		db_process_sql_delete('tagente_datos_inc', array('utimestamp' => '< ' . $from_date));
		db_process_sql_delete('tagente_datos_string', array('utimestamp' => '< ' . $from_date));
		db_process_sql_delete('tagente_datos_log4x', array('utimestamp' => '< ' . $from_date));
	}
	echo "<br /><br />";
}

# Select Agent for further operations.
$agents = agents_get_group_agents (0, true);
$agents[-1] = __('Choose agent');
$agents[0] = __('All agents'); 

echo '<form action="index.php?sec=gdbman&sec2=godmode/db/db_purge" method="post">';
echo '<div style="width:100%;">';
html_print_select ($agents, "agent", $id_agent, "this.form.submit();", "", "", false, false, false);
ui_print_help_tip (__("Select the agent you want information about"));
echo '<noscript>';
html_print_submit_button (__('Get data'), 'purgedb_ag', false, 'class="sub upd"');
ui_print_help_tip (__("Click here to get the data from the agent specified in the select box")); 
echo '</noscript><br />';

if ($id_agent > 0) {
	$title = __('Information on agent %s in the database', agents_get_name ($id_agent));
}
else {
	$title = __('Information on all agents in the database');
}

echo '<h4>'.$title.'</h4>';
//Flush before we do some SQL stuff
flush ();

if ($id_agent > 0) { //If the agent is not All or Not selected
	$modules = agents_get_modules ($id_agent);
	$query = sprintf (" AND id_agente_modulo IN (%s)", implode (",", array_keys ($modules)));
}
else {
	$query = "";
}

$data["1day"] = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1day"], $query));
$data["3day"] = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["3day"], $query));
$data["1week"] = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1week"], $query));
$data["2week"] = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["2week"], $query));
$data["1month"] = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["1month"], $query));
$data["3month"] = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE utimestamp > %d %s", $time["3month"], $query));
$data["total"] = db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE 1=1 %s", $query));

$data["1day"]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["1day"], $query));
$data["3day"]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["3day"], $query));
$data["1week"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["1week"], $query));
$data["2week"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["2week"], $query));
$data["1month"] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["1month"], $query));
$data["3month"] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["3month"], $query));
$data["total"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_inc WHERE 1=1 %s", $query));

$data["1day"]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1day"], $query));
$data["3day"]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["3day"], $query));
$data["1week"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1week"], $query));
$data["2week"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["2week"], $query));
$data["1month"] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1month"], $query));
$data["3month"] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["3month"], $query));
$data["total"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE 1=1 %s", $query));

$data["1day"]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1day"], $query));
$data["3day"]   += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["3day"], $query));
$data["1week"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1week"], $query));
$data["2week"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["2week"], $query));
$data["1month"] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["1month"], $query));
$data["3month"] += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE utimestamp > %d %s", $time["3month"], $query));
$data["total"]  += db_get_sql (sprintf ("SELECT COUNT(*) FROM tagente_datos_log4x WHERE 1=1 %s", $query));

$table->width = '98%';
$table->border = 0;
$table->class = "databox";

$table->data[0][0] = __('Packets less than three months old');
$table->data[0][1] = format_numeric($data["3month"]);
$table->data[1][0] = __('Packets less than one month old');
$table->data[1][1] = format_numeric($data["1month"]);
$table->data[2][0] = __('Packets less than two weeks old');
$table->data[2][1] = format_numeric($data["2week"]);
$table->data[3][0] = __('Packets less than one week old');
$table->data[3][1] = format_numeric($data["1week"]);
$table->data[4][0] = __('Packets less than three days old');
$table->data[4][1] = format_numeric($data["3day"]);
$table->data[5][0] = __('Packets less than one day old');
$table->data[5][1] = format_numeric($data["1day"]);
$table->data[6][0] = '<strong>'.__('Total number of packets').'</strong>';
$table->data[6][1] = '<strong>'.format_numeric($data["total"]).'</strong>';

html_print_table ($table);

echo '<br />';
echo '<h4>'.__('Purge data').'</h4>';

$table->data = array ();

$times = array ();
$times[$time["3month"]] = __('Purge data over 3 months');
$times[$time["1month"]] = __('Purge data over 1 month');
$times[$time["2week"]] = __('Purge data over 2 weeks');
$times[$time["1week"]] = __('Purge data over 1 week');
$times[$time["3day"]] = __('Purge data over 3 days');
$times[$time["1day"]] = __('Purge data over 1 day');
$times[$time["all"]] = __('All data until now');

$table->data[0][0] = html_print_select ($times, 'date_purge', '', '', '', '',
	true, false, false);
$table->data[0][1] = html_print_submit_button (__('Purge'), "purgedb", false,
	'class="sub wand"', true);

html_print_table ($table);

echo '</form>';
?>
