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
		<form method="post" action="index.php'.$url.'">
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


//html_debug_print('http://' . $_SERVER['SERVER_NAME'] . $config['homeurl'] . '/advise_navigator.php');
ui_require_css_file ('dialog');
ui_require_jquery_file ('ui.core');
ui_require_jquery_file ('ui.dialog');
?>


<!--[if IE]>
<div id="dialog" title="Pandora FMS Web browser advise" style="-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=50)'; filter: alpha(opacity=50); background:url(images/advise_navigator_background.png) no-repeat center bottom">

	<div style="position:absolute; top:20%; text-align: center; left:0%; right:0%; width:600px;">
		  <img src="images/error.png">
		<?php	  
		  echo __("In order to have the best user experience with Pandora FMS, we <b>strongly recommend</b> to use") . "<br>";
		  echo __("<a href='http://www.mozilla.org/en-US/firefox/fx/'>Mozilla Firefox</a> or <a href='https://www.google.com/chrome'>Google Chrome</a> browsers.") . "<br>"; 
		?>
		  <div style="position: absolute; top:200%; left:20%;">
		  <a href="http://www.mozilla.org/en-US/firefox/fx/"><img alt="Mozilla Firefox" title="Mozilla Firefox" src="images/mozilla_firefox.png"></a>
		  </div>
		  <div style="position: absolute; top:195%; right:20%;">
		  <a href="https://www.google.com/chrome"><img alt="Google Chrome" title="Google Chrome" src="images/google_chrome.png"></a>
		  </div>

		<div style="position: absolute; top:180px; right:43%;">	  
		<?php html_print_submit_button("Ok",'hide-advise',false,'class="sub" style="width:100px;"'); ?>	  
		</div>
	 </div> 
</div>
<![endif]-->


<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$(document).ready (function () {		
	$(function() {
		$( "#dialog" ).dialog({
				resizable: true,
				draggable: true,
				height: 300,
				width: 600,
				overlay: {
							opacity: 0.5,
							background: "black"
						},
				bgiframe: jQuery.browser.msie
			});
	});
	
	$("#submit-hide-advise").click (function () {
		$("#dialog" ).dialog('close')
	});
});

document.getElementById('nick').focus();
/* ]]> */
</script>
