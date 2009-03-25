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
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Database Management");
	require ("general/noaccess.php");
	return;
}
// Todo for a good DB maintenance 
/* 
	- Delete too on datos_string and and datos_inc tables 
	
	- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
	  onto a small chunk of interpolated data (1 month - 600 registers)
	
	- A more powerful selection (by Agent, by Module, etc).
*/

echo '<h2>'.__('Database Maintenance').' &gt; '.__('Current database maintenance setup').'</h2>
<table width="550" cellspacing="3" cellpadding="3" border="0">
<tr><td>
<i>'.__('Max. time before compact data').':</i>&nbsp;<b>'.$config['days_compact'].' '.__('days').'</b><br /><br />
<i>'.__('Max. time before purge').':</i>&nbsp;<b>'.$config['days_purge'].' '.__('days').'</b><br /><br />
</td></tr>
<tr><td>
<div align="justify">
'.__('Please check your Pandora Server setup and be sure that database maintenance daemon is running. It\'s very important to keep up-to-date database to get the best performance and results in Pandora').'
</div><br />
<img src="reporting/fgraph.php?tipo=db_agente_purge&id=-1&height=250&width=490">
</td></tr>
</table>';
?>
