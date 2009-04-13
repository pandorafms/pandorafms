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
require_once ("include/config.php");

check_login ();	

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access Database Debug Admin section");
	require ("general/noaccess.php");
	exit;
}

echo '<h2>'.__('Database Maintenance').' &raquo; '.__('Database debug').'</h2>';


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
			echo "<br /><br />".__('Filtering data module')."<b> [".get_agentmodule_name ($id_agentemodulo)."]</b>";
			$sql = sprintf ("DELETE FROM tagente_datos WHERE id_agente_modulo = %d AND (datos < '%s' OR datos > '%s')", $id_agentemodulo, $min, $max);
			process_sql ($sql);
		} 
	} //if copy modules or alerts
	echo '<br /><br /><h3 class="suc">'.__('Filtering completed').'</h3>';
}
echo '<form method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_refine&operacion=1">';
echo '<div style="float:left; width: 250px;">';
echo '<b>'.__('Source agent').'</b><br />';

$agent_selected = get_parameter_post ("origen", 0);
$agents = get_group_agents (array_keys (get_user_groups ($config["id_user"], "AW")));

print_select ($agents, "origen", $agent_selected, 'javascript:this.form.update_agent.click();', __('No agent selected'), '0', false, false, false, 'w130');

echo '&nbsp;&nbsp;';

print_submit_button (__('Get Info'), 'update_agent', false, 'style="display:none;"');

echo '<br /><br />';
echo '<b>'.__('Modules').'</b><br /><br />';

$module_selected = get_parameter_post ("origen", array ());
$modules = get_agent_modules ($module_selected);

print_select ($modules, "origen_modulo[]", $module_selected, '', '', '0', false, true, false, 'w130');

echo '</div>'; //Left div

echo '<div style="float:left; width:250px;"><b>'.__('Purge data out of these limits').'</b><br /><br />';
echo __('Minimum').': ';
print_input_text ("min", 0, __('Minimum'), 4, 0, false);
echo '<br />';
echo __('Maximum').': ';
print_input_text ("max", 0, __('Maximum'), 4, 0, false);
echo '<br />';

print_submit_button (__('Delete'), 'eliminar', false, 'class="sub delete" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;"'); 
echo '</div><div style="clear:both;">&nbsp;</div>';
?>
