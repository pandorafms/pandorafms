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
?>

<div class='databox' id='login'>
	<div id='login_f' class='databox'>
		<h1 id="log_f" style='margin-top: 0px;' class="error"><?php echo __('Authentication Error'); ?></h1>
		<div id='noa' style='width:50px' >
			<img src='images/noaccess.png' alt='No access'>
		</div>

		<div style='width: 350px'>
			<a href="index.php"><img src="images/pandora_logo.png" border="0"></a><br>
			<?php echo $pandora_version; ?>
		</div>

		<div class="msg"><?php echo __('Either, your password or your login are incorrect. Please check your CAPS LOCK key, username and password are case SeNSiTiVe.<br><br>All actions, included failed login attempts are logged in Pandora FMS System logs, and these can be reviewed by each user, please report to admin any incident or malfunction.'); ?></div>
	</div>
</div>
