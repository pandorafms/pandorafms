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

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_agents.php');

echo '<h1>'.__('Compound alerts').'</h1>';

$groups = get_user_groups ();
$agents = get_group_agents (array_keys ($groups), false, "none");

$table->class = 'alert_list';
$table->width = '90%';

foreach ($agents as $agent_id => $agent_name) {
	$alerts = get_agent_alerts_compound ($agent_id);
	if (empty ($alerts))
		continue;
	
	echo '<h3>'.$agent_name.' - '.__('Compound alerts').'</h3>';
	
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->head[0] = __('Name');
	
	foreach ($alerts as $alert) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound&id='.$alert['id'].'">';
		$data[0] .= $alert['name'];
		$data[0] .= '</a>';
		
		array_push ($table->data, $data);
	}
	
	print_table ($table);
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound">';
print_submit_button (__('Create'), 'crtbtn', false, 'class="sub next"');
print_input_hidden ('new_compound', 1);
echo '</form>';
echo '</div>';
?>
