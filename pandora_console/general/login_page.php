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

if (isset($config["homedir"])) {
	$homedir = $config["homedir"] . '/';
}
else {
	$homedir = '';
}

include_once($homedir . 'include/functions_ui.php');
include_once($homedir . 'include/functions.php');
include_once($homedir . 'include/functions_html.php');

if (!isset($login_screen)) {
	$login_screen = 'login';
}

switch ($login_screen) {
	case 'login':
		$logo_link = 'http://www.pandorafms.com';
		$logo_title = __('Go to Pandora FMS Website');
		break;
	case 'logout':
	case 'double_auth':
	case 'error_install':
	case 'error_authconfig':
	case 'error_dbconfig':
	case 'error_noconfig':
	case 'error_perms':
	case 'homedir_bad_defined':
	case 'homeurl_bad_defined':
		$logo_link = 'index.php';
		$logo_title = __('Go to Login');
		break;
	default:
		error_reporting(0);
		$error_info = ui_get_error($login_screen);
		$logo_link = 'index.php';
		$logo_title = __('Refresh');
		break;
	$splash_title = __('Splash login');
}

$url = '?login=1';
//These variables come from index.php
if (!empty ($page) && !empty ($sec)) {
	foreach ($_GET as $key => $value) {
		$url .= '&amp;'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
	}
}
$login_body_style = '';
// Overrides the default background with the defined by the user
if (!empty($config['login_background'])) {
	$background_url = "../../images/backgrounds/" . $config['login_background'];
	$login_body_style = "style=\"background-image: url('$background_url');\"";
}
echo '<div id="login_body" ' . $login_body_style . '>';
echo '<div id="header_login">';
	echo '<div id="icon_custom_pandora">';
		if (defined ('PANDORA_ENTERPRISE')) {
			if(isset ($config['custom_logo'])){
				echo '<img src="images/custom_logo/' . $config['custom_logo'] .'" alt="pandora_console">';
			}
			else{
				echo '<img src="images/custom_logo/logo_login_consola.png" alt="pandora_console">';
			}
		}
		else{
			echo '<img src="images/custom_logo/pandora_logo_head_3.png" alt="pandora_console">';	
		}
	echo '</div>';
	echo '<div id="list_icon_docs_support"><ul>';
		echo '<li><a href="http://wiki.pandorafms.com/" target="_blank"><img src="images/icono_docs.png" alt="docs pandora"></a></li>';
		echo '<li>' . __('Docs') . '</li>';
		echo '<li id="li_margin_left"><a href="https://pandorafms.com/monitoring-services/support/" target="_blank"><img src="images/icono_support.png" alt="support pandora"></a></li>';
		echo '<li>' . __('Support') . '</li>';
	echo '</ul></div>';	
echo '</div>';

echo '<div class="container_login">';
echo '<div class="login_page">';
	echo '<form method="post" action="' . ui_get_full_url('index.php'.$url) . '" ><div class="login_logo_icon">';
		echo '<a href="' . $logo_link . '">';
			if (defined ('METACONSOLE')) {
				if (!isset ($config["custom_logo_login"])){
					html_print_image ("images/custom_logo_login/login_logo.png", false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
				}
				else{
					html_print_image ("images/custom_logo_login/".$config['custom_logo_login'], false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
				}
			}
			else if (defined ('PANDORA_ENTERPRISE')) {

				if (!isset ($config["custom_logo_login"])){
					html_print_image ("enterprise/images/custom_logo_login/login_logo_v7.png", false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
				}
				else{
					html_print_image ("enterprise/images/custom_logo_login/".$config['custom_logo_login'], false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
				}
			}
			else {
				if (!isset ($config["custom_logo_login"]) || $config["custom_logo_login"] == 0){
					html_print_image ("images/custom_logo_login/pandora_logo.png", false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
				}
				else{
					html_print_image ("images/custom_logo_login/".$config['custom_logo_login'], false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
				}
				echo "<br><span style='font-size:120%;color:white;top:10px;position:relative;'>Community edition</span>";
			}
		echo '</a></div>';
			
	switch ($login_screen) {
		case 'logout':
		case 'login':
			if (!empty ($page) && !empty ($sec)) {
				foreach ($_POST as $key => $value) {
					html_print_input_hidden ($key, $value);
				}
			}
			if ($config['auth'] == 'saml') {
				echo '<div id="log_nick" class="login_nick" style="display: none;">';
					echo '<div>';
						html_print_image ("/images/usuario_login.png", false);
					echo '</div>';
					html_print_input_text_extended ("nick", '', "nick", '', '', '' , false,
						'', 'placeholder="'.__('User').'"');
				echo '</div>';

				echo '<div id="log_pass" class="login_pass" style="display: none;">';
					echo '<div>';
						html_print_image ("/images/candado_login.png", false);
					echo '</div>';
					html_print_input_text_extended ("pass", '', "pass", '', '', '' ,false,
						'', 'placeholder="'.__('Password').'"', false, true);
				echo '</div>';
				
				echo '<div id="log_button" class="login_button" style="display: none; margin-bottom: 20px;">';
					html_print_submit_button(__("Login as admin"), "login_button", false, 'class="sub next_login"');
				echo '</div>';
				
				echo '<div class="login_button" id="remove_button" style="margin-bottom: 20px;">';
					echo '<input type="button" id="input_saml" value="Login as admin" onclick="show_normal_menu()">';
				echo '</div>';

				echo '<div class="login_button">';
					html_print_submit_button(__("Login with SAML"), "login_button_saml", false, '');
				echo '</div>';
			}
			else {
				echo '<div class="login_nick">';
				echo '<div>';
					html_print_image ("/images/usuario_login.png", false);
				echo '</div>';
				html_print_input_text_extended ("nick", '', "nick", '', '', '' , false,
					'', 'autocomplete="off" placeholder="'.__('User').'"');
				echo '</div>';
				echo '<div class="login_pass">';
				echo '<div>';
					html_print_image ("/images/candado_login.png", false);
				echo '</div>';
				html_print_input_text_extended ("pass", '', "pass", '', '', '' ,false,
					'', 'autocomplete="off" placeholder="'.__('Password').'"', false, true);
				echo '</div>';
				echo '<div class="login_button">';
				html_print_submit_button(__("Login"), "login_button", false, 'class="sub next_login"');
				echo '</div>';
			}
			
			break;
		case 'double_auth':
			if (!empty ($page) && !empty ($sec)) {
				foreach ($_POST as $key => $value) {
					html_print_input_hidden ($key, $value);
				}
			}
			echo '<div class="login_nick">';
			echo '<div>';
				html_print_image ("/images/icono_autenticacion.png", false);
			echo '</div>';
			html_print_input_text_extended ("auth_code", '', "auth_code", '', '', '' , false, '', 'class="login login_password" placeholder="'.__('Authentication code').'"', false, true);
			echo '</div>';
			echo '<div class="login_button">';
			html_print_submit_button(__("Check code") . '&nbsp;&nbsp;>', "login_button", false, 'class="sub next_login"');
			echo '</div>';
			break;
		default:
			if (isset($error_info)) {
				echo '<h1 id="log_title">' . $error_info['title'] . '</h1>';
				echo '<div id="error_buttons">';
				echo '<a href="index.php">' . html_print_image($config['homeurl'] . '/images/refresh_white.png', true, array('title' => __('Refresh')), false, true) . '</a>';
				echo '<a href="javascript: modal_alert_critical()">' . html_print_image($config['homeurl'] . '/images/help_white.png', true, array('title' => __('View details')), false, true) . '</a>';
				echo '</div>';
				echo '<div id="log_msg">';
				echo $error_info['message'];
				echo '</div>';
			}
			break;
	}

	$reset_pass_link = 'reset_pass.php';
	// Reset password link
	echo '<div style="width:70%; height:40px; margin-right:auto; margin-left:auto; margin-top:20px; text-align:center;">';
	echo '<a style="color: white !important;" href="index.php?reset=true&first=true">' . __('Forgotten your password?');
	echo '</a>';
	echo '</div>';

	echo '</form></div>';
	echo '<div class="login_data">';
		echo '<div class ="text_banner_login">';
			echo '<div><span class="span1">';
				if(defined ('PANDORA_ENTERPRISE')){
					if($config['custom_title1_login']){
						echo strtoupper(io_safe_output($config['custom_title1_login']));
					}
					else{
						echo __('WELCOME TO PANDORA FMS');
					}
				}
				else{
					echo __('WELCOME TO PANDORA FMS');
				}
			echo '</span></div>';
			echo '<div><span class="span2">';
				if(defined ('PANDORA_ENTERPRISE')){
					if($config['custom_title2_login']){
						echo strtoupper(io_safe_output($config['custom_title2_login']));
					}
					else{
						echo __('NEXT GENERATION');
					}
				}
				else{
					echo __('NEXT GENERATION');
				}
			echo '</span></div>';
		echo '</div>';
		echo '<div class ="img_banner_login">';
			if (defined ('PANDORA_ENTERPRISE')) {
				if(isset($config['custom_splash_login'])){
					html_print_image ("enterprise/images/custom_splash_login/".$config['custom_splash_login'], false, array ( "alt" => "splash", "border" => 0, "title" => $splash_title), false, true);
				}
				else{
					html_print_image ("enterprise/images/custom_splash_login/splash_image_default.png", false, array ("alt" => "logo", "border" => 0, "title" => $splash_title), false, true);
				}
			} 
			else{
				html_print_image ("images/splash_image_default.png", false, array ("alt" => "logo", "border" => 0, "title" => $splash_title), false, true);
			}
		echo '</div>';
	echo '</div>';
echo '</div>';
echo '<div id="ver_num">'.$pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '') . '</div>';
echo '</div>';

if ($mail != "") {
	if ($email_error_message == '') {
		echo '<div id="reset_correct" title="' . __('Password reset') . '">';
			echo '<div class="content_alert">';
				echo '<div class="icon_message_alert">';
					echo html_print_image('images/icono_logo_pandora.png', true, array("alt" => __('Password reset'), "border" => 0));
				echo '</div>';
				echo '<div class="content_message_alert">';
					echo '<div class="text_message_alert">';
						echo '<h1>' . __('INFO') . '</h1>';
						echo '<p>'  . __('An email has been sent to the user\'s address') . '</p>';
					echo '</div>';
					echo '<div class="button_message_alert">';
						html_print_submit_button("Ok", 'reset_correct_button', false);  
					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
	else {
		echo '<div id="reset_correct" title="' . __('Password reset') . '">';
			echo '<div class="content_alert">';
				echo '<div class="icon_message_alert">';
					echo html_print_image('images/icono_stop.png', true, array("alt" => __('Password reset'), "border" => 0));
				echo '</div>';
				echo '<div class="content_message_alert">';
					echo '<div class="text_message_alert">';
						echo '<h1>' . __('ERROR') . '</h1>';
						echo '<p>'  . $email_error_message . '</p>';
					echo '</div>';
					echo '<div class="button_message_alert">';
						html_print_submit_button("Ok", 'reset_correct_button', false);  
					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
}

if (isset ($login_failed)) {
	echo '<div id="login_failed" title="' . __('Login failed') . '">';
		echo '<div class="content_alert">';
			echo '<div class="icon_message_alert">';
				echo html_print_image('images/icono_stop.png', true, array("alt" => __('Login failed'), "border" => 0));
			echo '</div>';
			echo '<div class="content_message_alert">';
				echo '<div class="text_message_alert">';
					echo '<h1>' . __('ERROR') . '</h1>';
					echo '<p>'  . $config["auth_error"] . '</p>';
				echo '</div>';
				echo '<div class="button_message_alert">';
					html_print_submit_button("Ok", 'hide-login-error', false);  
				echo '</div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
}

if ($login_screen == 'logout'){
	echo '<div id="login_logout" title="' . __('Logged out') . '">';
		echo '<div class="content_alert">';
			echo '<div class="icon_message_alert">';
				echo html_print_image('images/icono_logo_pandora.png', true, array("alt" => __('Logged out'), "border" => 0));
			echo '</div>';
			echo '<div class="content_message_alert">';
				echo '<div class="text_message_alert">';
					echo '<h1>'. __('Logged out') .'</h1>';
					echo '<p>' . __('Your session is over. Please close your browser window to close this Pandora session.') .'</p>';
				echo '</div>';
				echo '<div class="button_message_alert">';
					html_print_submit_button("Ok", 'hide-login-logout', false);  
				echo '</div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
}

switch ($login_screen) {
	case 'error_authconfig':
	case 'error_dbconfig':
		$title = __('Problem with Pandora FMS database');
		$message = __('Cannot connect to the database, please check your database setup in the <b>include/config.php</b> file.<i><br/><br/>
		Probably your database, hostname, user or password values are incorrect or
		the database server is not running.').'<br /><br />';
		$message .= '<span class="red">';
		$message .= '<b>' . __('DB ERROR') . ':</b><br>';
		$message .= db_get_last_error();
		$message .= '</span>';
		
		if ($error_code == 'error_authconfig') {
			$message .= '<br/><br/>';
			$message .= __('If you have modified auth system, this problem could be because Pandora cannot override authorization variables from the config database. Remove them from your database by executing:<br><pre>DELETE FROM tconfig WHERE token = "auth";</pre>');
		}
		break;
	case 'error_emptyconfig':
		$title = __('Empty configuration table');
		$message = __('Cannot load configuration variables from database. Please check your database setup in the
		<b>include/config.php</b> file.<i><br><br>
		Most likely your database schema has been created but there are is no data in it, you have a problem with the database access credentials or your schema is out of date.
		<br><br>Pandora FMS Console cannot find <i>include/config.php</i> or this file has invalid
		permissions and HTTP server cannot read it. Please read documentation to fix this problem.</i>').'<br /><br />';
		break;
	case 'error_noconfig':
		$title = __('No configuration file found');
		$message = __('Pandora FMS Console cannot find <i>include/config.php</i> or this file has invalid
		permissions and HTTP server cannot read it. Please read documentation to fix this problem.').'<br /><br />';
		if (file_exists('install.php')) {
			$link_start = '<a href="install.php">';
			$link_end = '</a>';
		}
		else {
			$link_start = '';
			$link_end = '';
		}
		
		$message .= sprintf(__('You may try to run the %s<b>installation wizard</b>%s to create one.'), $link_start, $link_end);
		break;
	case 'error_install':
		$title = __('Installer active');
		$message = __('For security reasons, normal operation is not possible until you delete installer file.
		Please delete the <i>./install.php</i> file before running Pandora FMS Console.');
		break;
	case 'error_perms':
		$title = __('Bad permission for include/config.php');
		$message = __('For security reasons, <i>config.php</i> must have restrictive permissions, and "other" users
		should not read it or write to it. It should be written only for owner
		(usually www-data or http daemon user), normal operation is not possible until you change
		permissions for <i>include/config.php</i> file. Please do it, it is for your security.');
		break;
	case 'homedir_bad_defined':
		$title = __('Bad defined homedir');
		$message = __('In the config.php file in the variable $config["homedir"] = add the correct path');
		break;
	case 'homeurl_bad_defined':
		$title = __('Bad defined homeurl or homeurl_static');
		$message = __('In the config.php file in the variable $config["homeurl"] or $config["homeurl_static"] = add the correct path');
		break;
}

if($login_screen == 'error_authconfig' || $login_screen == 'error_emptyconfig' || $login_screen == 'error_install' ||
	$login_screen == 'error_dbconfig' || $login_screen == 'error_noconfig' || $login_screen == 'error_perms' || 
	$login_screen == 'homedir_bad_defined' || $login_screen == 'homeurl_bad_defined'){
	echo '<div id="modal_alert" title="' . __('Login failed') . '">';
		echo '<div class="content_alert">';
			echo '<div class="icon_message_alert">';
				echo html_print_image('images/icono_stop.png', true, array("alt" => __('Login failed'), "border" => 0));
			echo '</div>';
			echo '<div class="content_message_alert">';
				echo '<div class="text_message_alert">';
					echo '<h1>' . $title . '</h1>';
					echo '<p> ' . $message . '</h1>';
				echo '</div>';
				echo '<div class="button_message_alert">';
					html_print_submit_button("Ok", 'hide-login-error', false);  
				echo '</div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
}

ui_require_css_file ('dialog');
ui_require_css_file ('jquery-ui-1.10.0.custom');
ui_require_jquery_file('jquery-ui-1.10.0.custom');
?>

<?php
// Hidden div to forced title
html_print_div(array('id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true));

//html_print_div(array('id' => 'modal_alert', 'hidden' => true));

?>
<script type="text/javascript" language="javascript">	
	function show_normal_menu() {
		document.getElementById('input_saml').style.display = 'none';
		document.getElementById('log_nick').style.display = 'block';
		document.getElementById('log_pass').style.display = 'block';
		document.getElementById('log_button').style.display = 'block';
		document.getElementById('remove_button').style.display = 'none';
		document.getElementById('log_nick').className = 'login_nick';
		document.getElementById('log_pass').className = 'login_pass';
	}

	<?php
	switch($login_screen) {
		case 'error_authconfig':
		case 'error_dbconfig':
		case 'error_emptyconfig':
		case 'error_noconfig':
		case 'error_install':
		case 'error_perms':
		case 'homedir_bad_defined':
		case 'homeurl_bad_defined':

	?>
	// Auto popup
	$(document).ready (function () {
		$(function() {
			$("#modal_alert").dialog ({
				title: $('#log_title').html(),
				resizable: true,
				draggable: false,
				modal: true,
				width: 600,
				height: 250,
				overlay: {
					opacity: 0.5,
					background: "black"
				}
			});
		});

		$("#submit-hide-login-error").click (function () {
			$("#modal_alert" ).dialog('close');
			
		});
	});

	<?php
		break;
		case 'logout':
	?>
	$(document).ready (function () {
		$(function() {
			$("#login_logout").dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 220,
				width: 528,
				clickOutside: true,
				overlay: {
					opacity: 0.5,
					background: "black"
				}
			});
		});

		$("#submit-hide-login-logout").click (function () {
			$("#login_logout").dialog('close');
		});		
	});

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
					background: "black"
				}
			});
		});
		
		$("#close-dialog-browser").click (function () {
			$("#dialog" ).dialog('close');
		});
		
		$(function() {
			$( "#login_failed" ).dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 220,
				width: 528,
				overlay: {
					opacity: 0.5,
					background: "black"
				}
			});
		});

		$("#submit-hide-login-error").click (function () {
			$("#login_failed" ).dialog('close');
			$("#login_correct_pass").dialog('close');
		});
	});
	
	$('#nick').focus();
	
	<?php 
	}
	?>

	$(document).ready (function () {
		$(function() {
			$("#reset_correct").dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 220,
				width: 528,
				clickOutside: true,
				overlay: {
					opacity: 0.5,
					background: "black"
				}
			});
		});

		$("#submit-reset_correct_button").click (function () {
			$("#reset_correct").dialog('close');
		});		
	});
	/* ]]> */
</script>
