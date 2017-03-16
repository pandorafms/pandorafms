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
echo '<div id="login_body" ' . $login_body_style . '></div>';
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
			echo '<img src="images/custom_logo/logo_login_consola.png" alt="pandora_console">';	
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
					html_print_image ("images/custom_logo_login/login_logo.png", false, array ("class" => "login_logo", "alt" => "logo", "border" => 0, "title" => $logo_title), false, true);
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


if (defined ('METACONSOLE')) {
	echo '<div id="ver_num">';
}
else {
	echo '<div id="ver_num">';
}

echo $pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '') . '</div>';


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

ui_require_css_file ('dialog');
ui_require_css_file ('jquery-ui-1.10.0.custom');
ui_require_jquery_file('jquery-ui-1.10.0.custom');
?>

<?php
// Hidden div to forced title
html_print_div(array('id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true));

html_print_div(array('id' => 'modal_alert', 'hidden' => true));

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
				height: 200
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
		$("#submit-hide-login-error").click (function () {
			$("#login_failed" ).dialog('close')
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
	/* ]]> */
</script>
