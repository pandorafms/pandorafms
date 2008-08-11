<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
?>

<center>
<div class='databox' id='login'>
	<h1 id='log'><?php echo __('logged_out'); ?></h1>
	<div class='databox' style='width: 400px;'>
		<form method="post" action="index.php?login=1">
		<table cellpadding='4' cellspacing='1' width='400'>
		<tr><td align='left'>
			<a href="index.php">
			<img src="images/pandora_logo.png" border="0" alt="logo"></a><br>
			<?php echo $pandora_version; ?>
		</td><td valign='bottom'>
			<?php echo __('logout_msg'); ?>
		</td></tr>
		</table>
		</form>
	</div>
	<div id="ip"><?php echo 'IP: <b class="f10">'.$REMOTE_ADDR.'</b>'; ?></div>
</div>
</center>




