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

if (give_acl ($config['id_user'], 0, "IR") != 1) {
	audit_db($config['id_user'],$config["remote_addr"], "ACL Violation","Trying to access incident search");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".__('Incident management')." &gt; ".__('Please select a search criterion')."</h2>";
echo '<div style="width:650px;"><div style="float:right;"><img src="images/pulpo_lupa.png" class="bot" align="left"></div>
	<div style="float:left;"><form name="busqueda" method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident">
	<table width="500px" cellpadding="4" cellspacing="4" class="databox">
	<tr><td class="datos">'.__('Created by:').'</td><td class="datos">';

print_select (list_users (), "usuario", "All", '', __('All'), "All", false, false, false, "w120");

echo '</td></tr><tr><td class="datos2">'.__('Search text').': (*)</td>
	<td class="datos2">';
	
print_input_text ('texto', '', '', 45);	

echo '</td></tr><tr>
	<td class="datos" colspan="2"><i>'.__('(*) The text search will look for all words entered as a substring in the title and description of each incident').'
	</i></td></tr><tr><td align="right" colspan="2">';

print_submit_button (__('Search'), 'uptbutton', false, 'class="sub search"');

echo '</td></tr></table></form></div></div>';
?>
