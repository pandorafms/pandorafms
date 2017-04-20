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

require_once($homedir . 'include/config.php');
require_once($homedir . 'include/functions_config.php');
include_once($homedir . 'include/functions_ui.php');
include_once($homedir . 'include/functions_users.php');
include_once($homedir . 'include/functions.php');
include_once($homedir . 'include/functions_html.php');

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
        echo '<form method="post" action="' . ui_get_full_url('index.php?correct_pass_change=true') . '"><div class="login_logo_icon">';
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
                
            echo '<div class="login_pass">';
				echo '<div>';
					html_print_image ("/images/candado_login.png", false);
				echo '</div>';
				html_print_input_text_extended ("pass1", '', "pass1", '', '', '' ,false,
					'', 'autocomplete="off" placeholder="'.__('New Password').'"', false, true);
            echo '</div>';
            echo '<div class="login_pass">';
				echo '<div>';
					html_print_image ("/images/candado_login.png", false);
				echo '</div>';
				html_print_input_text_extended ("pass2", '', "pass2", '', '', '' ,false,
					'', 'autocomplete="off" placeholder="'.__('Repeat password').'"', false, true);
            echo '</div>';
            echo '<div id="reset_pass_button" style="display:none;" class="login_button">';
				html_print_submit_button(__("Change password"), "login_button", false, 'class="sub next_login"');
            echo '</div>';
            echo '<div id="error_pass_message" style="display:none; text-align:center;">';
				html_print_label(__("Passwords must be the same"), "error_pass_label", false, array('style' => 'font-size:12pt; color:red;'));
            echo '</div>';
            html_print_input_hidden('id_user', $id_user);

        echo '</form>';

        echo '<form method="post" action="' . ui_get_full_url('index.php') . '">';
            echo '<div class="login_button">';
                html_print_submit_button(__("Back to login"), "login_button", false, 'class="sub next_login"');
            echo '</div>';
        echo '</form></div>';

        echo '<div style="float:right;" class="login_data">';
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

    echo '</div>';
    echo '<div id="ver_num">'.$pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '') . '</div>';
    echo '</div>';

ui_require_css_file ('dialog');
ui_require_css_file ('jquery-ui-1.10.0.custom');
ui_require_jquery_file('jquery-ui-1.10.0.custom');

?>

<script type="text/javascript" language="javascript">

$(document).ready (function () {
    $('#pass2').on('input', function(e) {
        var pass1 = $('#pass1').val();
        var pass2 = $('#pass2').val();
        if (pass1 != pass2) {
            $("#reset_pass_button").css('display', 'none');
            $("#error_pass_message").css('display', '');
        }
        else {
            $("#reset_pass_button").css('display', '');
            $("#error_pass_message").css('display', 'none');
        }
    });
});

</script>