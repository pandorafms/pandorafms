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
check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access massive operation section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');

$tab = (string) get_parameter ('tab');

$img_style = array ("class" => "top", "width" => 16);

echo '<div id="menu_tab_frame">';
echo '<div id="menu_tab">';
echo '<ul class="mn">';

echo '<li class="'.($tab == 'copy_modules' ? 'nomn_high' : 'nomn').'">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=copy_modules">';
print_image ("images/copy.png", false, $img_style);
echo '&nbsp;'.__('Copy').'</a>';
echo '</li>';

echo '<li class="'.($tab == 'edit_modules' || $tab == '' ? 'nomn_high' : 'nomn').'">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=edit_modules">';
print_image ("images/book_edit.png", false, $img_style);
echo '&nbsp; '.__('Edit modules').'</a>';
echo '</li>';

echo '<li class="'.($tab == 'delete_agents' || $tab == '' ? 'nomn_high' : 'nomn').'">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=delete_agents">';
print_image ("images/delete_agents.png", false, $img_style);
echo '&nbsp; '.__('Delete agents').'</a>';
echo '</li>';

echo '<li class="'.($tab == 'delete_modules' || $tab == '' ? 'nomn_high' : 'nomn').'">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=delete_modules">';
print_image ("images/delete_modules.png", false, $img_style);
echo '&nbsp; '.__('Delete modules').'</a>';
echo '</li>';

echo '<li class="'.($tab == 'delete_alerts' || $tab == '' ? 'nomn_high' : 'nomn').'">';
echo '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=delete_alerts">';
print_image ("images/delete_alerts.png", false, $img_style);
echo '&nbsp; '.__('Delete alerts').'</a>';
echo '</li>';

echo "</ul></div></div>";

echo '<div style="height: 25px;">&nbsp;</div>';

echo '<h2>'.__('Agent configuration'). ' &raquo; '. __('Massive operations').'</h2>';
switch ($tab) {
case 'delete_alerts':
	require_once ('godmode/agentes/massive_delete_alerts.php');
	break;
case 'delete_agents':
	require_once ('godmode/agentes/massive_delete_agents.php');
	break;
case 'delete_modules':
	require_once ('godmode/agentes/massive_delete_modules.php');
	break;
case 'edit_modules':
	require_once ('godmode/agentes/massive_edit_modules.php');
	break;
case 'copy_modules':
default:
	require_once ('godmode/agentes/massive_config.php');
}
?>
