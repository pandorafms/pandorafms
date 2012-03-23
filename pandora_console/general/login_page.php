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

$url = '?login=1';
//These variables come from index.php
if (!empty ($page) && !empty ($sec)) {
	foreach ($_GET as $key => $value) {
		$url .= '&amp;'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
	}
}

echo '<div class="databox_login" id="login">';

//	echo "<h1>Put here your custom welcome message</h1>";

//echo '<br /><br /><br />';
echo '
	<div id="login_in">
		<form method="post" autocomplete="off" action="index.php'.$url.'">
		<table cellpadding="4" cellspacing="1" width="420">';

if (isset ($login_failed)) {
//	echo '<tr><td colspan="3">';
	echo '<div id="error_login">';
	echo '<h3 class="error">'.__('Login failed').': '.$config["auth_error"].'</h3>';
	echo '</div>';
//	echo '</td></tr>';
}

echo '<tr><td rowspan="3" align="left">';

if (!empty ($page) && !empty ($sec)) {
	foreach ($_POST as $key => $value) {
		html_print_input_hidden ($key, $value);
	}
}

//TODO: Put branding in variables (external file) or database
/* CUSTOM BRANDING STARTS HERE */

// Replace the following with your own URL and logo.
// A mashup of the Pandora FMS logo and your companies highly preferred
echo '&nbsp;&nbsp;<a href="http://pandorafms.org" title="Go to pandorafms.org...">';
html_print_image ("images/pandora_logo.png", false, array ("alt" => "logo", "border" => 0));
echo '</a><br />';

// This prints the current pandora console version.
// For stable/live function it might be wise to comment it out
echo '&nbsp;&nbsp;&nbsp;' . $pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : ''); 
	
/* CUSTOM BRANDING ENDS HERE */

echo '</td><td class="f9b">
		'.__('Login').':<br />'.html_print_input_text_extended ("nick", '', "nick", '', '', '' , false, '', 'class="login"', true).'
	</td></tr>
	<tr><td class="f9b">
	<br>
		'.__('Password').':<br />'.html_print_input_text_extended ("pass", '', "pass", '', '', '' ,false, '', 'class="login"', true, true).'
	</td></tr>
	<tr><td align="center">
	<br>
		'.html_print_submit_button ("Login",'',false,'class="sub next"',true).'
	</td></tr>
	</table>
	</form>
	</div>
	<div id="ip">'.__('Your IP').': <b class="f10">'.$config["remote_addr"].'</b></div>
</div>';
?>
<script type="text/javascript" language="javascript">
/* <![CDATA[ */
document.getElementById('nick').focus();
/* ]]> */
</script>
