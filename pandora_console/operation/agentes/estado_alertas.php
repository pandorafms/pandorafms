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

$filter = get_parameter_get ("filter", "all");
$offset = (int) get_parameter_get ("offset", 0);
$id_group = (int) get_parameter ("ag_group", 1); //1 is the All group (selects all groups)

$sec2 = get_parameter_get ('sec2');
$sec2 = safe_url_extraclean ($sec2);
	
$sec = get_parameter_get ('sec');
$sec = safe_url_extraclean ($sec);

$url = 'index.php?sec='.$sec.'&sec2='.$sec2.'&refr='.$config["refr"].'&filter='.$filter.'&ag_group='.$id_group;


// Show alerts for specific agent
if (isset ($_GET["id_agente"])) {
	$id_agent = (int) get_parameter_get ("id_agente", 0);
	$url = $url.'&id_agente='.$id_agent;
	
	$id_group = get_group_agents ($id_agent);
	
	if (give_acl ($config["id_user"], $id_group, "AR") == 0) {
		audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation","Trying to access alert view");
		require ("general/noaccess.php");
		exit;
	}
	
	$alerts_simple = get_agent_alerts_simple ($id_agent, $filter);
	$alerts_combined = get_agent_alerts_combined ($id_agent, $filter);
	$print_agent = 0;
} else {
	if (give_acl ($config["id_user"], $id_group, "AR") == 0) {
		audit_db ($config["id_user"], $config["remote_addr"], "ACL Violation","Trying to access alert view");
		require ("general/noaccess.php");
		exit;
	}
	
	$alerts_simple = array ();
	$alerts_combined = array ();
	
	$agents = array_keys (get_group_agents ($id_group));
	
	foreach ($agents as $id_agent) {
		$simple = get_agent_alerts_simple ($id_agent, $filter);
		$combined = get_agent_alerts_combined ($id_agent, $filter);
		
		$alerts_simple = array_merge ($alerts_simple, $simple);
		$alerts_combined = array_merge ($alerts_combined, $combined);
	}
	
	$print_agent = 1;
}

$tab = get_parameter_get ("tab");
if ($tab != '') {
	echo "<h2>".__('Pandora Agents')." &gt; ".__('Full list of Alerts')."</h2>";
	$url = $url.'&tab='.$tab;
} else {
	echo "<h3>".__('Full list of alerts').'</h3>';
}
	

echo '<form method="post" action="'.$url.'">';
if (isset ($_POST["alert_validate"])) {
	$validate = get_parameter_post ("validate", array ());
	$result = process_alerts_validate ($validate);
	print_error_message ($result, __('Alert(s) validated'), __('Error processing alert(s)'));
}


if ($print_agent == 1) {
	echo '<table cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr><td>'.__('Group').'</td><td valign="middle">';
	
	//Select box
	$fields = get_user_groups ($config["id_user"]);	
	print_select ($fields, "ag_group", $id_group, 'javascript:this.form.submit();" class="w150','');
	
	//And submit button
	echo '</td><td valign="middle"><noscript><input name="uptbutton" type="submit" class="sub" value="'.__('Show').'"></noscript></td>';
	
	//And finish the table here
	echo '<td class="f9" style="padding-left:30px;'.($filter == "fired" ? ' font-weight: bold;' : '').'"><a href="'.$url.'&filter=fired"><img src="images/pixel_red.png" width="18" height="18" title="'.__('Click to filter').'"></a>&nbsp;'.__('Alert fired').'</td>';
	echo '<td class="f9" style="padding-left:30px;'.($filter == "notfired" ? ' font-weight: bold;' : '').'"><a href="'.$url.'&filter=notfired"><img src="images/pixel_green.png" width="18" height="18" title="'.__('Click to filter').'"></a>&nbsp;'.__('Alert not fired').'</td>';
	echo '<td class="f9" style="padding-left:30px;'.($filter == "disabled" ? ' font-weight: bold;' : '').'"><a href="'.$url.'&filter=disabled"><img src="images/pixel_gray.png" width="18" height="18" title="'.__('Click to filter').'"></a>&nbsp;'.__('Alert disabled').'</td></tr></table>';
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = 750;
$table->border = 0;
$table->class = "databox";

$table->head = array ();

$table->head[0] = __('Type');
$table->head[1] = ''; //Placeholder for name
$table->head[2] = __('Description');
$table->head[3] = __('Info');
$table->head[4] = __('Min').'/'.__('Max');
$table->head[5] = __('Last fired');
$table->head[6] = __('Status');
$table->head[7] = __('Validate') . pandora_help('alert_validation', true);
$table->align = array ();
$table->align[0] = "center";
$table->align[3] = "center";
$table->align[4] = "center";
$table->align[5] = "center";
$table->align[6] = "center";
$table->align[7] = "center";

$table->title = __('Single alerts');
if ($print_agent == 0) {
	$table->head[1] = __('Module name');
} else {
	$table->head[1] = __('Agent name');	
}

$table->data = array ();

$counter[0] = 0; //Dual counter. This one counts the total number of alerts
$counter[1] = 0; //Dual counter. This one counts only the printed alerts
foreach ($alerts_simple as $alert) {
	$counter[0]++;
	if (empty ($alert) || $counter[1] >= $config["block_size"] || $counter[0] <= $offset) {
		continue;
	}
	$counter[1]++;
	array_push ($table->data, format_alert_row ($alert, 0, $print_agent));
}

if (!empty ($table->data)) {
	pagination ($counter[0], $url, $offset);
	print_table ($table);
} else {
	echo '<div class="nf">'.__('No simple alerts found').'</div>';
}

$table->title = __('Combined alerts');
$table->head[1] = __('Agent name');
$table->data = array ();

$counter[2] = 0;
$counter[3] = 0;
foreach ($alerts_combined as $alert) {
	$counter[2]++;
	if (empty ($alert) || $counter[3] >= $config["block_size"] || $counter[2] <= $offset) {
		continue;
	}
	$counter[3]++;
	array_push ($table->data, format_alert_row ($alert, 1, $print_agent));
}	

if (!empty ($table->data)) {
	pagination ($counter[0], $url, $offset);
	print_table ($table);
} else {
	echo '<div class="nf">'.__('No combined alerts found').'</div>';
}

if ($counter[1] > 0 || $counter[2] > 0) {
	echo '<div style="text-align: right; width: 750px;">';
	print_submit_button (__('Validate'), 'alert_validate', false, 'class="sub upd"', false);
	echo '</div>';
}
echo '</form>';
?>
