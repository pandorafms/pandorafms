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
        echo '<form method="post" action="' . ui_get_full_url('index.php?reset=true') . '"><div class="login_logo_icon">';
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
                
            echo '<div class="login_nick">';
                echo '<div>';
                    html_print_image ("/images/usuario_login.png", false);
                echo '</div>';
                html_print_input_text_extended ("user_reset_pass", '', "user_reset_pass", '', '', '' , false,
                    '', 'autocomplete="off" placeholder="'.__('User to reset password').'"');
            echo '</div>';
            echo '<div class="login_button">';
                html_print_submit_button(__("Reset password"), "login_button", false, 'class="sub next_login"');
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

    echo '</div>';
    echo '<div id="ver_num">'.$pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '') . '</div>';
    echo '</div>';
    
if ($show_error) {
    echo '<div id="reset_pass_error" title="' . __('Reset password failed') . '">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_stop.png', true, array("alt" => __('Reset password failed'), "border" => 0));
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>' . __('ERROR') . '</h1>';
                    echo '<p>'  . $error . '</p>';
                echo '</div>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button("Ok", 'reset_pass_error', false);  
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}

ui_require_css_file ('dialog');
ui_require_css_file ('jquery-ui-1.10.0.custom');
ui_require_jquery_file('jquery-ui-1.10.0.custom');

?>

<script type="text/javascript" language="javascript">

$(document).ready (function () {
    $(function() {
        $( "#reset_pass_error" ).dialog({
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

    $("#submit-reset_pass_error").click (function () {
        $("#reset_pass_error" ).dialog('close');
    });
});

</script>