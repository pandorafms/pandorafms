<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

?>

<div class='databox' id='login'>
	<div id='login_f' class='databox'>
		<h1 id="log_f" style='margin-top: 0px;' class="error"><?php echo __('Authentication Error'); ?></h1>
	
		<div style='width: 350px'>
			<a href="index.php"><img src="images/pandora_logo.png" border="0"></a><br>
			<?php echo $pandora_version; ?>
		</div>
		<center>
		<div>
			<img src='images/noaccess.png' alt='No access'>
		</div>
		</center>

		<div class="msg"><?php echo __('Either, your password or your login are incorrect. Please check your CAPS LOCK key, username and password are case SeNSiTiVe.<br><br>All actions, included failed login attempts are logged in Pandora FMS System logs, and these can be reviewed by each user, please report to admin any incident or malfunction.'); ?></div>
	</div>
</div>
