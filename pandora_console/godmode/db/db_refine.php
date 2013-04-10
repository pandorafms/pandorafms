<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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

check_login ();

if (! check_acl ($config['id_user'], 0, "DM")) {
	db_pandora_audit("ACL Violation","Trying to access Database Debug Admin section");
	require ("general/noaccess.php");
	exit;
}

require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . '/include/functions_users.php');

ui_print_page_header (__('Database maintenance').' &raquo; '.__('Database debug'), "images/gm_db.png", false, "", true);


if ((isset ($_GET["operacion"])) && (!isset ($_POST["update_agent"]))) {
	// DATA COPY
	if (isset ($_POST["eliminar"])) {
		$max = get_parameter_post ("max", 0);
		$min = get_parameter_post ("min", 0);
		if ($max == $min) {
			echo '<h3 class="error">'.__('Error').': '.__('Maximum is equal to minimum').'</h3>';
			return;
		}
		$origen_modulo = get_parameter_post ("origen_modulo", array ());
		if (empty ($origen_modulo)) {
			echo '<h3 class="error">'.__('Error').': '.__('No modules have been selected').'</h3>';
			return;
		}

		// Source (agent)
		$id_origen = (int) get_parameter_post ("origen", 0);
		
		// Copy
		foreach ($origen_modulo as $id_agentemodulo) {
			echo "<br /><br />".__('Filtering data module')."<b> [".modules_get_agentmodule_name ($id_agentemodulo)."]</b>";
			$sql = sprintf ("DELETE FROM tagente_datos WHERE id_agente_modulo = %d AND (datos < '%s' OR datos > '%s')", $id_agentemodulo, $min, $max);
			db_process_sql ($sql);
		} 
	} //if copy modules or alerts
	echo '<br /><br /><h3 class="suc">'.__('Filtering completed').'</h3>';
}
echo '<form method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_refine&operacion=1">';
echo "<table width='98%' border='0' cellspacing='4' cellpadding='4' class='databox'>";

echo '<tr><td class="datost">';
echo '<div style="float:left; width: 250px;">';
echo '<b>'.__('Source agent').'</b><br /><br />';

$agent_selected = get_parameter_post ("origen", 0);
$agents = agents_get_group_agents (array_keys (users_get_groups ($config["id_user"], "AW")));

html_print_select ($agents, "origen", $agent_selected, 'javascript:this.form.update_agent.click();', __('No agent selected'), '0', false, false, false, '', false, 'max-width:300px !important;');

echo '&nbsp;&nbsp;';

html_print_submit_button (__('Get Info'), 'update_agent', false, 'style="display:none;"');

echo '<br /><br />';
echo '<b>'.__('Modules').'</b><br /><br />';

$module_selected = get_parameter_post ("origen", array ());
$modules = agents_get_modules ($module_selected, false, 'delete_pending != 1');

html_print_select ($modules, "origen_modulo[]", $module_selected, '', '', '0', false, true, false, '', false, 'max-width: 300px !important;');

echo '</div>'; //Left div

echo '<div style="float:left; width:\'98%\'; margin-left:20% ">
	<b>'.__('Purge data out of these limits').'</b><br /><br />';
echo '<table><tr><td>';
echo __('Minimum').': ';
echo '</td><td>';
html_print_input_text ("min", 0, __('Minimum'), 4, 0, false);
echo '</td></tr>';
echo '<tr><td>';
echo __('Maximum').': ';
echo '</td><td>';
html_print_input_text ("max", 0, __('Maximum'), 4, 0, false);
echo '</td></tr>';
echo '</table>';
echo '</div>';
echo '<div style="clear:both;">&nbsp;</div>';
html_print_submit_button (__('Delete'), 'eliminar', false, 'class="sub delete" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;"');
echo '</td></tr></table>';

?>
