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

if(isset($config["homedir"])) {
	$homedir = $config["homedir"] . '/';
}
else {
	$homedir = '';
}
	
include_once($homedir . 'include/functions_ui.php');
include_once($homedir . 'include/functions.php');
include_once($homedir . 'include/functions_html.php');

if(!isset($login_screen)) {
	$login_screen = 'login';
}

switch($login_screen) {
	case 'login':
		$logo_link = 'http://www.pandorafms.com';
		$logo_title = __('Go to Pandora FMS Website');
		break;
	case 'logout':
		$logo_link = 'index.php';
		$logo_title = __('Go to Login');
		break;
	default:
		error_reporting(0);
		$error_info = ui_get_error($login_screen);
		$logo_link = 'index.php';
		$logo_title = __('Refresh');
		break;
}


$url = '?login=1';
//These variables come from index.php
if (!empty ($page) && !empty ($sec)) {
	foreach ($_GET as $key => $value) {
		$url .= '&amp;'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
	}
}
echo '<div id="login_body"></div>';
echo '<div id="login_outer">';
echo '<div class="databox_login" id="login">';
echo '<div id="login_inner">';

echo '
	<div id="login_in">
		<form method="post" action="' . ui_get_full_url('index.php'.$url) . '">';

	//TODO: Put branding in variables (external file) or database
	/* CUSTOM BRANDING STARTS HERE */

	// Replace the following with your own URL and logo.
	// A mashup of the Pandora FMS logo and your companies highly preferred
	echo '<a href="' . $logo_link . '">';
	if (defined ('PANDORA_ENTERPRISE')) {
		html_print_image ($config['homeurl'] . "/images/pandora_login_enterprise.png", false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
	}
	else {
		html_print_image ($config['homeurl'] . "/images/pandora_login.png", false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
	}
	echo '</a>';

	// This prints the current pandora console version.
	// For stable/live function it might be wise to comment it out

	/* CUSTOM BRANDING ENDS HERE */


	echo '<div style="text-align: center; height: 5px !important;">&nbsp;</div>'; 

	echo '<br />';
	
	switch($login_screen) {
		case 'login':
			if (!empty ($page) && !empty ($sec)) {
				foreach ($_POST as $key => $value) {
					html_print_input_hidden ($key, $value);
				}
			}
			echo '<div class="login_nick">';
			html_print_input_text_extended ("nick", '', "nick", '', '', '' , false, '', 'class="login login_user"');
			echo '</div>';
			echo '<div class="login_pass">';
			html_print_input_text_extended ("pass", '', "pass", '', '', '' ,false, '', 'class="login login_password"', false, true);
			echo '</div>';
			echo '<div class="login_button">';
			html_print_submit_button(__("Log-in"), "login", false, 'class="sub next"');
			echo '</div>';
			break;
		case 'logout':
			echo '<h1 id="log_title">' . __('Logged out') . '</h1>';
			echo '<p class="log_in">';
			echo __('Your session is over. Please close your browser window to close this Pandora session.').'<br /><br />';
			echo '</p>';
			break;
		default:
			if(isset($error_info)) {
				echo '<h1 id="log_title">' . $error_info['title'] . '</h1>';
				echo '<div id="error_buttons">';
				echo '<a href="index.php">' . html_print_image($config['homeurl'] . '/images/refresh.png', true, array('title' => __('Refresh')), false, true) . '</a>';
				echo '<a href="javascript: modal_alert_critical()">' . html_print_image($config['homeurl'] . '/images/help.png', true, array('title' => __('View details')), false, true) . '</a>';
				echo '</div>';
				echo '<div id="log_msg">';
				echo $error_info['message'];
				echo '</div>';
			}
			break;
	}
		
	echo '<div id="ver_num">' . $pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '') . '</div>';

echo '</form>
	</div>
</div>
</div>
</div>';


if (isset ($login_failed)) {
	echo '<div id="login_failed" title="Login failed" style="">';
		
		echo '<div style="position:absolute; top:0px; text-align: center; left:0%; right:0%; height:100px; width:330px; margin: 0 auto; ">';
			
			echo '<div id="error_login" style="margin-top: 20px">';
			echo '<strong style="font-size: 10pt">' . $config["auth_error"] . '</strong>';
			echo '</div>';
			
			echo '<div id="error_login_icon">';
			echo html_print_image('images/error_login.png', true, array("alt" => __('Login failed'), "border" => 0));
			echo '</div>';
			
			echo '<div style="position:absolute; margin: 0 auto; top: 70px; left: 35%; ">';
				html_print_submit_button("Ok", 'hide-login-error', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only" style="width:100px;"');  
			echo '</div>';
			
		echo '</div>';
		
	echo '</div>';
}

ui_require_css_file ('jquery-ui-1.10.0.custom');
ui_require_jquery_file('jquery-ui-1.10.0.custom');
?>

<?php
	if (!isset ($login_failed)) {
?>
	<!--[if lte IE 8]>
	<div id="dialog" title="WARNING! You are using an outdated browser.">
		<div style="position:absolute; top:20px; text-align: left; font-size: 9.5pt; line-height: 18px; left:0%; right:0%; margin: 0 auto; width:650px; border: 1px solid #FFF; ">
			<?php
			echo __("Pandora FMS frontend is built on advanced, modern technologies and does not support old browsers.");
			echo "<br>" . __("It is highly recommended that you choose and install a modern browser. It is free of charge and only takes a couple of minutes.");
			?>
		</div>
		
		<div style="position: relative; top: 90px; margin: 0 auto; width: 650px; border: 1px solid #FFF;">
			<table style="width: 650px;">
				<tr>
					<td style="width: 20%;">
						<a target="_blank" style="text-decoration: none; color: #6495ED;" href="https://www.google.com/chrome">
							<img style="width: 60px;" src="images/google_chrome.png" />
							<div style="position: relative; top: 11px;">
								Google Chrome
								<br />
								<span style="text-decoration: underline;">Download page</span>
							</div>
						</a>
					</td>
					<td style="font-size: 10px; line-height: 15px; width: 20%;">
						<a target="_blank" style="text-decoration: none; color: #6495ED;" href="http://www.mozilla.org/en-US/firefox/fx/">
							<img style="width: 60px;" src="images/mozilla_firefox.png" />
							<div style="position: relative; top: 5px;">
								Mozilla Firefox
								<br />
								<span style="text-decoration: underline;">Download page</span>
							</div>
						</a>
					</td>
					<td style="width: 20%;">
						<a target="_blank" style="text-decoration: none; color: #6495ED;" href="http://windows.microsoft.com/es-ES/internet-explorer/downloads/ie-9/worldwide-languages">
							<img style="width: 63px;" src="images/iexplorer.jpeg" />
							<div style="position: relative; top: 10px;">
								Internet Explorer
								<br />
								<span style="text-decoration: underline;">Download page</span>
							</div>
						</a>
					</td>
					<td style="width: 20%;">
						<a target="_blank" style="text-decoration: none; color: #6495ED;" href="http://www.opera.com/download/">
							<img style="width: 50px;" src="images/opera_browser.png" />
							<div style="position: relative; top: 16px;">
								Opera browser
								<br />
								<span style="text-decoration: underline;">Download page</span>
							</div>
						</a>
					</td>
					<td style="width: 20%;">
						<a target="_blank" style="text-decoration: none; color: #6495ED;" href="http://www.apple.com/es/safari/download/">
							<img style="width: 60px;" src="images/safari_browser.jpeg" />
							<div style="position: relative; top: 11px;">
								Apple safari
								<br />
								<span style="text-decoration: underline;">Download page</span>
							</div>
						</a>
					</td>
				</tr>
			</table>
		</div>
			<div style="position: relative; top:120px; width:650px; margin: 0 auto; text-align: left;  border: 1px solid #FFF;">
				<?php 
					echo '<span style="font-size: 10pt; color: #2E2E2E; font-weight: bold;">';
						echo __('Why is it recommended to upgrade the web browser?'); 
					echo '</span>';
					
					echo '<span style="font-size: 9.5pt; line-height: 18px;">';
						echo '<br><br>' .
							__('New browsers usually come with support for new technologies, increasing web page speed, better privacy settings and so on. They also resolve security and functional issues.');
					echo '</span>';
				?>
			</div>
			
			<div style="float:right; margin-top:160px; margin-right: 50px; width: 200px;">
				<a id="close-dialog-browser" href="#" style="text-decoration: none;">
					<span style="color: #6495ED;">
						<?php
						echo __('Continue despite this warning');
						?>
					</span>
				</a>
			</div>
	</div>
	<![endif]-->
<?php
}

// Hidden div to forced title
html_print_div(array('id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true));

html_print_div(array('id' => 'modal_alert', 'hidden' => true));
?>

<script type="text/javascript" src="include/javascript/jquery-1.9.0.js"></script>
<script type="text/javascript" src="include/javascript/jquery.pandora.js"></script>
<script type="text/javascript" src="include/javascript/jquery.jquery-ui-1.10.0.custom.js"></script>
<script type="text/javascript" language="javascript">
	/* <![CDATA[ */
	
	function modal_alert_critical() {
		$("#modal_alert").hide ()
			.empty ()
			.append ($('#log_msg').html())
			.dialog ({
				title: $('#log_title').html(),
				resizable: true,
				draggable: false,
				modal: true,
				overlay: {
					opacity: 0.5,
					background: "black"
				},
				width: 500,
				height: 300
			})
			.show ();
	}
	<?php
	switch($login_screen) {
		case 'error_authconfig':
		case 'error_emptyconfig':
	?>
			// Auto popup
			//modal_alert_critical();
	<?php
			break;
		default:
	?>
	$(document).ready (function () {
		// IE9- modal warning window
		$(function() {
			$( "#dialog" ).dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 400,
				width: 700,
				overlay: {
					opacity: 0.5,
					background: "black"}
			});
		});
		
		$("#close-dialog-browser").click (function () {
			$("#dialog" ).dialog('close')
		});
		
		$(function() {
			$( "#login_failed" ).dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 150,
				width: 350,
				overlay: {
					opacity: 0.5,
					background: "black"}
			});
		});
		
		$("#submit-hide-login-error").click (function () {
			$("#login_failed" ).dialog('close')
		});
	});
	
	$('#nick').focus();
	
	<?php 
	}
	?>
	/* ]]> */
</script>
