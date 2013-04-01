<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

?>
<img src="images/login_background.jpg" id="login_body">
<div class="databox_logout" id="login">
	<br>
	<h1 id="log"><?php echo __('Logged out'); ?></h1>
	<br>
	<div style="width: 400px; margin: 0 auto auto;">
		<table cellpadding="4" cellspacing="1" width="400">
		<tr><td align="left">
			<?php
				echo '<a href="index.php">';
				if (defined ('PANDORA_ENTERPRISE')){
					html_print_image ("images/pandora_login_enterprise.png", false, array ("alt" => "logo", "border" => 0));
				}
				else {
					html_print_image ("images/pandora_login.png", false, array ("alt" => "logo", "border" => 0));	
				}
				
				//html_print_image ("images/pandora_login.png", false, array ("alt" => "logo", "border" => 0));
				//echo '</a> '.$pandora_version;
			?>
		</td><td valign="bottom">
			<?php echo __('Your session is over. Please close your browser window to close this Pandora session.').'<br /><br />'; ?>
		</td></tr>
		</table>
	</div>
	<br>

</div>
<div id="ver_num"><?php echo $pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '') ?></div>
