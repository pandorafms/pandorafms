<?php
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es
// Copyright (c) 2008 Artica Soluciones Tecnologicas S.L, info@artica.es
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation;  version 2
// of the License, or (at your option) any later version.
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

if (! give_acl ($config['id_user'], 0, 'PM')) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to use Open Update Manager extension");
	include ("general/noaccess.php");
	exit;
}

$db =& um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);

$settings = um_db_load_settings ();

echo '<h3>'.__('Update manager').'</h3>';

echo '<div class="notify" style="width: 80%" >';
echo '<img src="images/information.png" /> ';
/* Translators: Do not translade Update Manager, it's the name of the program */
echo __('The new <a href="http://updatemanager.sourceforge.net">Update Manager</a> client is shipped with the new Pandora FMS 2.0. It lets systems administrators to do not need to update their PandoraFMS manually since the Update Manager is the one getting new modules, new plugins and new features (even full migrations tools for future versions) automatically');
echo '<p />';
echo __('Update Manager is one of the most advanced features of PandoraFMS 2.0 Enterprise version, for more information visit <a href="http://pandorafms.com">http://pandorafms.com</a>');
echo '</div>';

$user_key = get_user_key ();
$package = um_client_check_latest_update ($settings, $user_key);

if (is_int ($package) && $package == 1) {
	echo '<h5 class="suc">'.__('Your system is up-to-date').'.</h5>';
} elseif ($package === false) {
	echo '<h5 class="error">'.__('Server connection failed')."</h5>";
} elseif (is_int ($package) && $package == 0) {
	echo '<h5 class="error">'.__('Server authorization rejected')."</h5>";
} else {
	echo '<h5 class="suc">'.__('There\'s a new update for Pandora')."</h5>";
	
	$table->width = '50%';
	$table->head = array ();
	$table->data = array ();
	$table->head[0] = '<strong>'.__('Description').'</strong>';
	$table->data[0][0] = html_entity_decode ($package->description);
	
	print_table ($table);
}

echo '<h4>'.__('Your system version number is').': '.$settings->current_update.'</h4>';

?>
