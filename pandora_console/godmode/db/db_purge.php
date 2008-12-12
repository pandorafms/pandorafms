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


// Load global vars
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Database Purge Section");
	include ("general/noaccess.php");
	exit;
}

//id_agent = -1: None selected; id_agent = 0: All
if (isset ($_POST["agent"])){
	$id_agent = (int) get_parameter_post ("agent", -1); //Default to none selected
} else {
	$id_agent = -1;
}



echo '<h2>'.__('Database Maintenance').' &gt; '.__('Database purge').'</h2>
	<img src="reporting/fgraph.php?tipo=db_agente_purge&id='.$id_agent.'" />
	<br /><br />
	<h3>'.__('Get data from agent').'</h3>';

// All data (now)
$time["all"] = time ();

// 1 day ago
$time["1day"] = $time["all"]-86400;

// 3 days ago
$time["3day"] = $time["all"]-(86400*3);

// 1 week ago
$time["1week"] = $time["all"]-(86400*7);

// 2 weeks ago
$time["2week"] = $time["all"]-(86400*14);

// 1 month ago
$time["1month"] = $time["all"]-(86400*30);

// Three months ago
$time["3month"] = $time["all"]-(86400*90);
	
//Init data
$data["1day"] = 0;
$data["3day"] = 0;
$data["1week"] = 0;
$data["2week"] = 0; 
$data["1month"] = 0; 
$data["3month"] = 0; 
$data["total"] = 0;

# ADQUIRE DATA PASSED AS FORM PARAMETERS
# ======================================

# Purge data using dates
if (isset($_POST["purgedb"])) {
	$from_date = get_parameter_post ("date_purge", 0); //0: No time selected
	if ($id_agent > 0) {
		echo __('Purge task launched for agent')." ".get_agent_name ($id_agent)." :: ".__('Data older than')." ".human_time_description ($from_date);
		echo "<h3>".__('Please be patient. This operation can take a long time depending on the amount of modules.')."</h3>";
		
		$sql = sprintf ("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = %d", $id_agent);
		$result=get_db_all_rows_sql ($sql);
		if (empty ($result)) {
			$result = array ();
		}
		
		//Made it in a transaction so it gets done all at once.
		process_sql ("SET AUTOCOMMIT=0;");
		process_sql ("START TRANSACTION;"); //We start a transaction for consistency 
		$errors = 0;
		foreach ($result as $row) {
			echo __('Deleting records for module')." ".get_agentmodule_name ($row["id_agente_modulo"]);
			echo "<br />";
			flush (); //Flush here in case there are errors and the script dies, at least we know where we ended
			set_time_limit (); //Reset the time limit just in case
			$sql = sprintf ("DELETE FROM `tagente_datos` WHERE `id_agente_modulo` = %d AND `utimestamp` < %d",$row["id_agente_modulo"],$from_date);
			if (process_sql ($sql) === false)
				$errors++;
			$sql = sprintf ("DELETE FROM `tagente_datos_inc` WHERE `id_agente_modulo` = %d AND `utimestamp` < %d",$row["id_agente_modulo"],$from_date);
			if (process_sql ($sql) === false) 
				$errors++;				
			$sql = sprintf ("DELETE FROM `tagente_datos_string` WHERE `id_agente_modulo` = %d AND `utimestamp` < %d",$row["id_agente_modulo"],$from_date);
			if (process_sql ($sql) === false) 
				$errors++;				
		}
		
		if ($errors > 0) {
			process_sql ("ROLLBACK;"); //If we have errors, rollback
		} else {
			process_sql ("COMMIT;"); //Otherwise commit
		}
		
		process_sql ("SET AUTOCOMMIT=1;"); //Set autocommit back to 1
	} else {
		//All agents
		echo __('Deleting records for all agents');
		flush ();
		$query = sprintf ("DELETE FROM `tagente_datos` WHERE `utimestamp` < %d",$from_date);
		process_sql ($query);
		$query = sprintf ("DELETE FROM `tagente_datos_inc` WHERE `utimestamp` < %d",$from_date);
		process_sql ($query);
		$query = sprintf ("DELETE FROM `tagente_datos_string` WHERE `utimestamp` < %d",$from_date);
		process_sql ($query);
	}
	echo "<br /><br />";
}

# Select Agent for further operations.
$agents = get_group_agents (1, true);
$agents[-1] = __('Choose agent');
$agents[0] = __('All agents'); 

echo '<form action="index.php?sec=gdbman&sec2=godmode/db/db_purge" method="post">';
echo '<div style="width:100%;">';
print_select ($agents, "agent", $id_agent, "this.form.submit();", "", "", false, false, false);
print_help_tip (__("Select the agent you want information about"));
echo '<noscript>';
print_submit_button (__('Get data'), 'purgedb_ag', false, 'class="sub upd"');
print_help_tip (__("Click here to get the data from the agent specified in the select box")); 
echo '</noscript><br />';

if ($id_agent > 0) {
	$title = __('Information on agent').' '.get_agent_name ($id_agent).' '.__('in the database');
} else {
	$title = __('Information on all agents').' '.__('in the database');
}

echo "<h3>".$title."</h3>";	
flush (); //Flush before we do some SQL stuff
if ($id_agent > 0) { //If the agent is not All or Not selected
	$modules = get_agent_modules ($id_agent);
	sprintf ("AND id_agente_modulo IN(%s)", implode (",", array_keys ($modules)));
} else {
	$query = "";
}

$data["1day"] = get_db_sql (sprintf ("SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp > %d %s", $time["1day"], $query));
$data["3day"] = get_db_sql (sprintf ("SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp > %d %s", $time["3day"], $query));
$data["1week"] = get_db_sql (sprintf ("SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp > %d %s", $time["1week"], $query));
$data["2week"] = get_db_sql (sprintf ("SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp > %d %s", $time["2week"], $query));
$data["1month"] = get_db_sql (sprintf ("SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp > %d %s", $time["1month"], $query));
$data["3month"] = get_db_sql (sprintf ("SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE utimestamp > %d %s", $time["3month"], $query));
$data["total"] = get_db_sql (sprintf ("SELECT COUNT(id_agente_datos) FROM tagente_datos WHERE 1=1 %s", $query));

$data["1day"] += get_db_sql (sprintf ("SELECT COUNT(id_adi) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["1day"], $query));
$data["3day"] += get_db_sql (sprintf ("SELECT COUNT(id_adi) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["3day"], $query));
$data["1week"] += get_db_sql (sprintf ("SELECT COUNT(id_adi) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["1week"], $query));
$data["2week"] += get_db_sql (sprintf ("SELECT COUNT(id_adi) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["2week"], $query));
$data["1month"] += get_db_sql (sprintf ("SELECT COUNT(id_adi) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["1month"], $query));
$data["3month"] += get_db_sql (sprintf ("SELECT COUNT(id_adi) FROM tagente_datos_inc WHERE utimestamp > %d %s", $time["3month"], $query));
$data["total"] += get_db_sql (sprintf ("SELECT COUNT(id_adi) FROM tagente_datos_inc WHERE 1=1 %s", $query));

$data["1day"] += get_db_sql (sprintf ("SELECT COUNT(id_tagente_datos_string) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1day"], $query));
$data["3day"] += get_db_sql (sprintf ("SELECT COUNT(id_tagente_datos_string) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["3day"], $query));
$data["1week"] += get_db_sql (sprintf ("SELECT COUNT(id_tagente_datos_string) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1week"], $query));
$data["2week"] += get_db_sql (sprintf ("SELECT COUNT(id_tagente_datos_string) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["2week"], $query));
$data["1month"] += get_db_sql (sprintf ("SELECT COUNT(id_tagente_datos_string) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["1month"], $query));
$data["3month"] += get_db_sql (sprintf ("SELECT COUNT(id_tagente_datos_string) FROM tagente_datos_string WHERE utimestamp > %d %s", $time["3month"], $query));
$data["total"] += get_db_sql (sprintf ("SELECT COUNT(id_tagente_datos_string) FROM tagente_datos_string WHERE 1=1 %s", $query));


if (isset ($table)) {
	unset ($table); //since $table is an object, we make sure it's gone first
}

$table->width = 300;
$table->border = 0;
$table->class = "databox";
$table->cellspacing = 4;
$table->cellpadding = 4;

$table->data[0] = array (__('Packets less than three months old'), $data["3month"]);
$table->data[1] = array (__('Packets less than one month old'), $data["1month"]);
$table->data[2] = array (__('Packets less than two weeks old'), $data["2week"]);
$table->data[3] = array (__('Packets less than one week old'), $data["1week"]);
$table->data[4] = array (__('Packets less than three days old'), $data["3day"]);
$table->data[5] = array (__('Packets less than one day old'), $data["1day"]);
$table->data[6] = array ('<b>'.__('Total number of packets').'</b>', '<b>'.$data["total"].'</b>');

print_table ($table);

echo '<br />';
echo '<h3>'.__('Purge data').'</h3>
<table width="300" border="0" class="databox" cellspacing="4" cellpadding="4">
<tr><td>
<select name="date_purge" width="255px">
<option value="'.$time["3month"].'">'.__('Purge data over 3 months').'</option>
<option value="'.$time["1month"].'">'.__('Purge data over 1 month').'</option>
<option value="'.$time["2week"].'">'.__('Purge data over 2 weeks').'</option>
<option value="'.$time["1week"].'">'.__('Purge data over 1 week').'</option>
<option value="'.$time["3day"].'">'.__('Purge data over 3 days').'</option>
<option value="'.$time["1day"].'">'.__('Purge data over 1 day').'</option>
<option value="'.$time["all"].'">'.__('All data until now').'</option>
</select>
</td><td>
<input class="sub wand" type="submit" name="purgedb" value="'.__('Do it!').'" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;" />
</td></tr>
</table>
</form>';
?>
