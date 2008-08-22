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

$addr = "";
if (isset($_GET['sec'])){
	$addr = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': '') . '://' . $_SERVER['SERVER_NAME'];
	
	if ($_SERVER['SERVER_PORT'] != 80 && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE && $_SERVER['SERVER_PORT'] != 443))
		$query .= ":" . $_SERVER['SERVER_PORT'];
	
	$addr .= $_SERVER['REQUEST_URI'];
	
	$addr = urlencode($addr);
}

echo '<div class="databox" id="login">
	<h1 id="log">'.__('Welcome to Pandora FMS Web Console').'</h1>
	<div class="databox" id="login_in">
		<form method="post" action="index.php?login=1">
		<table cellpadding="4" cellspacing="1" width="400">
		<tr><td rowspan="3" align="left" style="border-right: solid 1px #678;">
			<a href="index.php"><img src="images/pandora_logo.png" border="0" alt="logo"></a><br />
			'.$pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '').'
		</td><td class="f9b">
			'.__('Login').':<br />'.print_input_text_extended ("nick", '', "nick", '', '', '' , false, '', 'class="login"', true).'
		</td></tr>
		<tr><td class="f9b">
			'.__('Password').':<br />'.print_input_text_extended ("pass", '', "pass", '', '', '' ,false, '', 'class="login"', true, true).'
		</td></tr>
		<tr><td align="center">
			'.print_submit_button ("Login",'',false,'class="sub next"',true).'
		</td></tr>
		</table>
		'.((strlen($addr) > 0) ? print_input_hidden("redirect",$addr,true) : '').'
		</form>
	</div>
	<div id="ip">IP: <b class="f10">'.$REMOTE_ADDR.'</b></div>
	</div><script type="text/javascript">document.getElementById(\'nick\').focus();</script>';
?>
