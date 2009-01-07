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

$url = '?login=1';
//These variables come from index.php
if (!empty ($page) && !empty ($sec)) {
	foreach ($_GET as $key => $value) {
		$url .= '&'.$key.'='.$value;
	}
}

echo '<div class="databox" id="login">
	<h1 id="log">'.__('Pandora FMS Web Console').'</h1><br>
	<div class="databox" id="login_in">
		<form method="post" action="index.php'.$url.'">
		<table cellpadding="4" cellspacing="1" width="400">';

if (isset ($login_failed)) {
	echo '<tr><td colspan="3">';
	echo '<h3 class="error" style="width: 200px">'.__('Login failed').'</h3>';
	echo '</td></tr>';
}

echo '<tr><td rowspan="3" align="left" style="border-right: solid 1px #678;">';

if (!empty ($page) && !empty ($sec)) {
	foreach ($_POST as $key => $value) {
		print_input_hidden ($key, $vale);
	}
}

//TODO: Put branding in variables (external file) or database
/* CUSTOM BRANDING STARTS HERE */

// Replace the following with your own URL and logo.
// A mashup of the Pandora FMS logo and your companies highly preferred
echo '<a href="http://pandorafms.org" title="Go to pandorafms.org..." alt="Pandora FMS - Free Monitoring System">';
echo '<img src="images/pandora_logo.png" border="0" alt="logo" />';
echo '</a><br />';

// This prints the current pandora console version.
// For stable/live function it might be wise to comment it out
echo $pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : ''); 
	
/* CUSTOM BRANDING ENDS HERE */

echo '</td><td class="f9b">
		'.__('Login').':<br />'.print_input_text_extended ("nick", '', "nick", '', '', '' , false, '', 'class="login"', true).'
	</td></tr>
	<tr><td class="f9b">
		'.__('Password').':<br />'.print_input_text_extended ("pass", '', "pass", '', '', '' ,false, '', 'class="login"', true, true).'
	</td></tr>
	<tr><td align="center">
		'.print_submit_button ("Login",'',false,'class="sub next"',true).'
	</td></tr>
	</table>
	</form>
	</div>
	<div id="ip">'.__('Your IP').': <b class="f10">'.$config["remote_addr"].'</b>
</div>

	</div><script type="text/javascript">document.getElementById(\'nick\').focus();</script>';
?>
