<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
if (isset($config['homedir'])) {
    $homedir = $config['homedir'].'/';
} else {
    $homedir = '';
}

global $config;



ui_require_css_file('login', 'include/styles/', true);

require_once __DIR__.'/../include/functions_ui.php';
require_once __DIR__.'/../include/functions.php';
require_once __DIR__.'/../include/functions_html.php';

echo '<style>
        :root {';
if ($config['style'] === 'pandora') {
    echo '--login-background-color: rgba(255, 255, 255, 0.4);';
    echo '--login-label-color: #545454;';
    echo '--login-text-color: #000;';
    $style_theme = 'white-theme';
} else {
    echo '--login-background-color: rgba(0, 0, 0, 0.8);';
    echo '--login-label-color: #c5c5c5;';
    echo '--login-text-color: #fff;';
    $style_theme = '';
}

echo '}
</style>';

if ($config['visual_animation']) {
    // form#login_form, div.login_data {
    echo '<style>
    div.container_login {
            animation: container_login 3s ease;
        }
        
        @keyframes container_login {
            0% {
                transform: scale(.9);
                opacity: 0.1;
            }
            
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
	</style>';
}


if (!isset($login_screen)) {
    $login_screen = 'login';
}

switch ($login_screen) {
    case 'login':
        $logo_link = 'http://www.pandorafms.com';
        $logo_title = __('Go to %s Website', get_product_name());
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
    case 'disabled_access_node':
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

$splash_title = __('Splash login');

$url = '?login=1';
// These variables come from index.php
if (!empty($page) && !empty($sec)) {
    foreach ($_GET as $key => $value) {
        $url .= '&amp;'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
    }
}

$login_body_style = '';
// Overrides the default background with the defined by the user.
$background_url = 'images/backgrounds/background_pandora_console_keys.jpg';

if (empty($config['random_background']) === false) {
    $random_backgrounds = scandir($config['homedir'].'/images/backgrounds/random_backgrounds');
    unset($random_backgrounds[0], $random_backgrounds[1]);
    $random_background = array_rand($random_backgrounds);
    $background_url = 'images/backgrounds/random_backgrounds/'.$random_backgrounds[$random_background];
    $background_100 = 'background-size: cover !important; background-position: center !important; ';
}

if (empty($config['login_background']) === false) {
    $background_url = 'images/backgrounds/'.$config['login_background'];
    $background_100 = 'background-size: cover !important; background-position: center !important; ';
}

// Support for Internet Explorer and Microsoft Edge browsers
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Edge') !== false) {
    $background_url = 'images/backgrounds/background_pandora_console_keys.jpg';
    $background_100 = '';
}

if (empty($config['background_opacity']) === false) {
    $opacity = $config['background_opacity'];
} else {
    $opacity = 30;
}

if ($config['style'] === 'pandora') {
    $opacity_color = '255, 255, 255, .';
} else {
    $opacity_color = '0, 0, 0, .';
}

$login_body_style = 'style="'.$background_100.'background: linear-gradient(rgba('.$opacity_color.$opacity.'), rgba('.$opacity_color.$opacity.")), url('".$background_url."');\"";

// Get alternative custom in case of db fail.
$custom_fields = [
    'custom_logo_login',
    'custom_splash_login',
    'custom_title1_login',
    'custom_title2_login',
    'rb_product_name',
    'custom_docs_url',
    'custom_support_url',
];

foreach ($custom_fields as $field) {
    if (!isset($config[$field])) {
        if (isset($config[$field.'_alt'])) {
            $config[$field] = $config[$field.'_alt'];
            $custom_conf_enabled = true;
        }
    }
}


// Get the custom icons.
$docs_logo = ui_get_docs_logo();
$support_logo = ui_get_support_logo();
echo '<div id="login_body" '.$login_body_style.'>';
echo '<div id="header_login" class="'.$style_theme.'">';

echo '<div id="list_icon_docs_support"><ul>';

if (isset($config['custom_docs_url'])) {
    if ($docs_logo !== false) {
        echo '<li id="li_margin_doc_img"><a href="'.ui_get_full_external_url($config['custom_docs_url']).'" target="_blank"><img src="'.$docs_logo.'" alt="docs"></a></li>';
    }

    echo '<li id="li_margin_doc"><a href="'.ui_get_full_external_url($config['custom_docs_url']).'" target="_blank">'.__('Docs').'</li>';
} else if (!$custom_conf_enabled) {
    echo '<li id="li_margin_doc_img"><a href="https://pandorafms.com/manual/" target="_blank"><img src="'.$docs_logo.'" alt="docs"></a></li>';
    echo '<li id="li_margin_doc"><a href="https://pandorafms.com/manual/" target="_blank">'.__('Docs').'</li>';
}

if (isset($config['custom_support_url'])) {
    if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
        if ($support_logo !== false) {
            echo '<li id="li_margin_support_img"><a href="'.ui_get_full_external_url($config['custom_support_url']).'" target="_blank"><img src="'.$support_logo.'" alt="support"></a></li>';
        }

        echo '<li id="li_margin_support"><a href="'.ui_get_full_external_url($config['custom_support_url']).'" target="_blank">'.__('Support').'</li>';
    } else {
        echo '<li id="li_margin_support_img"><a href="https://pandorafms.com/monitoring-services/support/" target="_blank"><img src="'.$support_logo.'" alt="support"></a></li>';
        echo '<li id="li_margin_support"><a href="https://support.pandorafms.com" target="_blank">'.__('Support').'</a></li>';
    }
} else if (!$custom_conf_enabled) {
    echo '<li id="li_margin_support_img"><a href="https://support.pandorafms.com" target="_blank"><img src="'.$support_logo.'" alt="support"></a></li>';
    echo '<li id="li_margin_support"><a href="https://support.pandorafms.com" target="_blank">'.__('Support').'</a></li>';
}

        echo '</ul></div>';


echo '</div>';

echo '<div class="container_login">';
echo '<div class="login_page">';
    echo '<form method="post" id="login_form" action="'.ui_get_full_url('index.php'.$url).'" ><div class="login_logo_icon">';
        echo '<a href="'.$logo_link.'">';
if (is_metaconsole() === true) {
    if (!isset($config['custom_logo_login'])) {
        html_print_image(
            'enterprise/images/custom_logo_login/Pandora-FMS-1.png',
            false,
            [
                'class'  => 'login_logo',
                'alt'    => 'logo',
                'border' => 0,
                'title'  => $logo_title,
            ],
            false
        );
    } else {
        html_print_image(
            'enterprise/images/custom_logo_login/'.$config['custom_logo_login'],
            false,
            [
                'class'  => 'login_logo',
                'alt'    => 'logo',
                'border' => 0,
                'title'  => $logo_title,
            ],
            false
        );
    }
} else if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    if (!isset($config['custom_logo_login'])) {
        html_print_image(ui_get_full_url('enterprise/images/custom_logo_login/Pandora-FMS-1.png'), false, ['class' => 'login_logo', 'alt' => 'logo', 'border' => 0, 'title' => $logo_title], false, true);
    } else {
        html_print_image(ui_get_full_url('enterprise/images/custom_logo_login/'.$config['custom_logo_login']), false, ['class' => 'login_logo', 'alt' => 'logo', 'border' => 0, 'title' => $logo_title], false, true);
    }
} else {
    if (empty($config['custom_logo_login']) === true) {
        html_print_image(ui_get_full_url('images/custom_logo_login/Pandora-FMS-1.png'), false, ['class' => 'login_logo', 'alt' => 'logo', 'border' => 0, 'title' => $logo_title], false, true);
    } else {
        html_print_image(ui_get_full_url('images/custom_logo_login/').$config['custom_logo_login'], false, ['class' => 'login_logo', 'alt' => 'logo', 'border' => 0, 'title' => $logo_title], false, true);
    }
}

        echo '</a></div>';

switch ($login_screen) {
    case 'logout':
    case 'login':
    case 'disabled_access_node':
        if (!empty($page) && !empty($sec)) {
            foreach ($_POST as $key => $value) {
                html_print_input_hidden(io_safe_input($key), io_safe_input($value));
            }
        }

        if ($config['auth'] == 'saml') {
            echo '<div id="log_nick" class="login_nick invisible '.$style_theme.'" >';
                html_print_input_text_extended(
                    'nick',
                    '',
                    'nick',
                    '',
                    '',
                    '',
                    false,
                    '',
                    'placeholder="'.__('User').'"'
                );
            echo '</div>';

            echo '<div id="log_pass" class="login_pass invisible">';
                html_print_input_text_extended(
                    'pass',
                    '',
                    'pass',
                    '',
                    '',
                    '',
                    false,
                    '',
                    'placeholder="'.__('Password').'"',
                    false,
                    true
                );
            echo '</div>';

            echo '<div id="log_button" class="login_button invisible">';
                html_print_submit_button(__('Let&#39;s go'), 'login_button', false, [ 'fixed_id' => 'submit-login_button', 'class' => 'next_login']);
            echo '</div>';

            echo '<div class="login_button" id="remove_button">';
                html_print_submit_button(
                    __('Login as admin'),
                    'input_saml',
                    false,
                    [
                        'fixed_id' => 'submit-admin_button',
                        'class'    => 'next_login',
                        'onclick'  => 'show_normal_menu()',
                        'id'       => 'input_saml',
                    ]
                );
            echo '</div>';

            echo '<div class="login_button login_button_saml">';
                html_print_submit_button(
                    __('Login with SAML'),
                    'login_button_saml',
                    false,
                    ['class' => 'next_login secondary']
                );
            echo '</div>';
        } else {
            echo '<div class="login_nick '.$style_theme.'">';
                html_print_input_text_extended(
                    'nick',
                    '',
                    'nick',
                    '',
                    '',
                    '',
                    false,
                    '',
                    'autocomplete="off" class="input" placeholder=" "'
                );
                echo '<label for="nick" class="placeholder">'.__('User').'</label>';
            echo '</div>';
            echo '<div class="login_pass '.$style_theme.'">';
                html_print_input_text_extended(
                    'pass',
                    '',
                    'pass',
                    '',
                    '',
                    '',
                    false,
                    '',
                    'autocomplete="off" class="input " placeholder=" " style="background-image: url(images/enable.svg);"',
                    false,
                    true
                );
                echo '<label for="pass" class="placeholder">'.__('Password').'</label>';
            echo '</div>';
            echo '<div class="login_button">';
                html_print_submit_button(
                    __('Let&#39;s go'),
                    'login_button',
                    false,
                    ['fixed_id' => 'submit-login_button']
                );
            echo '</div>';
        }
    break;

    case 'double_auth':
        if (!empty($page) && !empty($sec)) {
            foreach ($_POST as $key => $value) {
                html_print_input_hidden(io_safe_input($key), $value);
            }
        }

        echo '<div class="login_nick '.$style_theme.'">';
        echo '<div>';

        echo '</div>';
        html_print_input_text_extended(
            'auth_code',
            '',
            'auth_code',
            '',
            '',
            '',
            false,
            '',
            'class="login login_password input" placeholder=" "',
            false,
            true
        );
        echo '<label for="pass" class="placeholder">'.__('Authentication code').'</label>';
        echo '</div>';
        echo '<div class="login_button">';
        // html_print_submit_button(__('Check code').'&nbsp;&nbsp;>', 'login_button', false, 'class="next_login"');
        html_print_submit_button(
            __('Check code'),
            'login_button',
            false,
            [
                'fixed_id' => 'submit-login_button',
                'class'    => 'next_login',
            ]
        );
        echo '</div>';
    break;

    default:
        if (isset($error_info)) {
            echo '<h1 id="log_title">'.$error_info['title'].'</h1>';
            echo '<div id="error_buttons">';
            echo '<a href="index.php">'.html_print_image($config['homeurl'].'/images/refresh_white.png', true, ['title' => __('Refresh')], false, true).'</a>';
            echo '<a href="javascript: modal_alert_critical()">'.html_print_image($config['homeurl'].'/images/help_white.png', true, ['title' => __('View details')], false, true).'</a>';
            echo '</div>';
            echo '<div id="log_msg">';
            echo $error_info['message'];
            echo '</div>';
        }
    break;
}

if ($config['enterprise_installed']) {
    if ($config['reset_pass_option']) {
        $reset_pass_link = 'reset_pass.php';
        // Reset password link.
        echo '<div class="reset_password">';
        if ((!$config['centralized_management'])) {
            echo '<a href="index.php?reset=true&first=true">'.__('Forgot your password?');
            echo '</a>';
        } else {
            echo '<a href="javascript:centralized_mode_reset_dialog();">'.__('Forgot your password?');
            echo '</a>';

            echo '<div id="centralized_mode_reset_dialog" title="'.__('Centralized mode').'" style="display:none">';
                echo '<div class="content_alert">';
                    echo '<div class="icon_message_alert">';
                        echo html_print_image('images/icono_stop.png', true, ['alt' => __('Centralized mode'), 'border' => 0]);
                    echo '</div>';
                    echo '<div class="content_message_alert">';
                        echo '<div class="text_message_alert">';
                            echo '<p>'.__('This node is configured with centralized mode. Go to metaconsole to reset the password').'</p>';
                        echo '</div>';
                        echo '<br>';
                        echo '<div class="button_message_alert">';
                            html_print_submit_button('Ok', 'centralized_mode_reset_button', false, ['class' => 'mini float-right']);
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        }

        echo '</div>';
    }
}

echo '
    <div class="loader" id="spinner_login">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
';
echo '<div id="ver_num">'.$pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '').'</div>';

// CSRF validation.
if (isset($_SESSION['csrf_code']) === true) {
    unset($_SESSION['csrf_code']);
}

html_print_csrf_hidden();

    echo '</form></div>';
    echo '<div class="login_data">';

    echo '<div class ="img_banner_login">';
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    if (empty($config['custom_splash_login']) === false && $config['custom_splash_login'] !== 'default') {
        if ($config['custom_splash_login'] !== 'none.png') {
            html_print_image(
                'enterprise/images/custom_splash_login/'.$config['custom_splash_login'],
                false,
                [
                    'class'  => 'splash-logo',
                    'alt'    => 'splash',
                    'border' => 0,
                ],
                false,
                false
            );
        }
    } else {
        echo '
                <div class="loginimg-container">
                    <div class="lineone"></div> 
                    <div class="linetwo"></div>
                    <div class="linethree"></div>
                    <div style="display:flex;">
                        <div class="towerone"></div>
                        <div class="towertwo"></div>
                        <div class="towerthree"></div>
                        <div class="towerfour"></div>
                    </div>
                </div>
            ';
    }
} else {
    echo '
                <div class="loginimg-container">
                    <div class="lineone"></div> 
                    <div class="linetwo"></div>
                    <div class="linethree"></div>
                    <div style="display:flex;">
                        <div class="towerone"></div>
                        <div class="towertwo"></div>
                        <div class="towerthree"></div>
                        <div class="towerfour"></div>
                    </div>
                </div>
            ';
}

            echo '</div>';

        echo '<div class ="text_banner_login">';
echo '<div><span class="span1">';
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    if ($config['custom_title1_login']) {
        echo io_safe_output($config['custom_title1_login']);
    } else {
        echo __('ONE TOOL TO RULE THEM ALL');
    }
} else {
    echo __('ONE TOOL TO RULE THEM ALL');
}

echo '</span></div>';
echo '<div><span class="span2">';
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    if ($config['custom_title2_login']) {
        echo io_safe_output($config['custom_title2_login']);
    }
}

echo '</span></div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

if (empty($process_error_message) && isset($mail)) {
    echo '<div id="reset_correct" title="'.__('Password reset').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_logo_pandora.png', true, ['alt' => __('Password reset'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.__('INFO').'</h1>';
                    echo '<p>'.__('An email has been sent to your email address').'</p>';
                echo '</div>';
                echo '<br>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button('Ok', 'reset_correct_button', false, ['class' => 'mini float-right']);
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
} else if (isset($process_error_message) && !empty($process_error_message)) {
    echo '<div id="reset_correct" title="'.__('Error').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_stop.png', true, ['alt' => __('Forbidden'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.__('ERROR').'</h1>';
                    echo '<p>'.$process_error_message.'</p>';
                    echo '<br>';
                echo '</div>';
                echo '<br>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button('Ok', 'reset_correct_button', false, ['class' => 'mini float-right']);
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}


if (isset($correct_reset_pass_process)) {
    echo '<div id="final_process_correct" title="'.__('Password reset').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_logo_pandora.png', true, ['alt' => __('Password reset'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.__('SUCCESS').'</h1>';
                    echo '<p>'.$correct_reset_pass_process.'</p>';
                echo '</div>';
                echo '<br>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button('Ok', 'final_process_correct_button', false, ['class' => 'mini float-right']);
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}

if (isset($login_failed)) {
    $nick = io_safe_input(get_parameter_post('nick'));
    $user_in_db = db_get_row_filter(
        'tusuario',
        ['id_user' => $nick],
        '*'
    );
    $fails = $user_in_db['failed_attempt'];
    // If user not exist, and attempts its enable, lets make array and fails attemps.
    if ($fails == false && $config['enable_pass_policy'] && $user_in_db === false) {
        $nick_array_error = json_decode(base64_decode($config['nicks_error']), true);
        $nick = strtolower($nick);
        if (isset($nick_array_error[$nick]) !== false) {
            $nick_array_error[$nick] += 1;
        } else {
            $nick_array_error[$nick] = 1;
        }

        $fails = $nick_array_error[$nick];
        // Save or update the array.
        if ($config['nicks_error']) {
            config_update_value('nicks_error', base64_encode(json_encode($nick_array_error)));
        } else {
            config_create_value('nicks_error', base64_encode(json_encode($nick_array_error)));
        }
    } else {
        $fails = ++$fails;
    }

    $attemps = ($config['number_attempts'] - $fails);
    $attemps = ($attemps < 0) ? 0 : $attemps;
    echo '<div id="login_failed" title="'.__('Login failed').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_stop.png', true, ['alt' => __('Login failed'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.__('ERROR').'</h1>';
                    echo '<p>'.$config['auth_error'].'</p>';
                echo '</div>';
    if ($config['enable_pass_policy']) {
        echo '<div class="text_message_alert">';
        if ($attemps !== 0 && $user_in_db['login_blocked'] == 0) {
            echo '<p><strong>'.__('Remaining attempts: ').$attemps.'</strong></p>';
        } else {
            echo '<p><strong>'.__('User is blocked').'</strong></p>';
        }

        echo '</div>';
    }

    echo '<br>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button('Ok', 'hide-login-error', false, ['class' => ' mini float-right']);
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}

if ($login_screen == 'logout') {
    echo '<div id="login_logout" title="'.__('Logged out').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_logo_pandora.png', true, ['alt' => __('Logged out'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.__('Logged out').'</h1>';
    if (empty($config['logout_msg']) === true) {
        echo '<p>'.__('Your session has ended. Please close your browser window to close this %s session.', get_product_name()).'</p>';
    } else {
        echo '<p>'.__($config['logout_msg']).'</p>';
    }

                echo '</div>';
                echo '<br>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button('Ok', 'hide-login-logout', false, ['class' => ' mini float-right']);
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}

if ($login_screen === 'disabled_access_node') {
    echo '<div id="disabled_access_node" title="'.__('User node access not enabled').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_logo_pandora.png', true, ['alt' => __('Centralized user in metaconsole'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.__('Centralized user in metaconsole').'</h1>';
                    echo '<p>'.__('This user does not have access on node, please enable node access on this user from metaconsole.').'</p>';
                echo '</div>';
                echo '<br>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button('Ok', 'hide-login-logout', false, ['class' => 'mini float-right']);
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}

switch ($login_screen) {
    case 'error_dbconfig':
    case 'error_authconfig':
    case 'disabled_node_access':
        if (!isset($config['rb_product_name_alt'])) {
            $title = __('Problem with %s database', get_product_name());
        } else {
            $title = __('Problem with %s database', $config['rb_product_name_alt']);
        }

        $message = __(
            'Cannot connect to the database, please check your database setup in the <b>include/config.php</b> file.<i><br/><br/>
		Probably your database, hostname, user or password values are incorrect or
		the database server is not running.'
        ).'<br /><br />';
        $message .= '<span class="red">';
        $message .= '<b>'.__('DB ERROR').':</b><br>';
        $message .= db_get_last_error();
        $message .= '</span>';

        if ($error_code == 'error_authconfig') {
            $message .= '<br/><br/>';
            $message .= __('If you have modified the auth system, the origin of this problem could be that %s cannot override the authorization variables from the config database. Please remove them from your database by executing:<br><pre>DELETE FROM tconfig WHERE token = "auth";</pre>', get_product_name());
        }
    break;

    case 'error_emptyconfig':
        $title = __('Empty configuration table');
        $message = __(
            'Cannot load configuration variables from database. Please check your database setup in the
			<b>include/config.php</b> file.<i><br><br>
			Most likely your database schema has been created but there are is no data in it, you have a problem with the database access credentials or your schema is out of date.
			<br><br>%s Console cannot find <i>include/config.php</i> or this file has invalid
			permissions and HTTP server cannot read it. Please read documentation to fix this problem.</i>',
            get_product_name()
        ).'<br /><br />';
    break;

    case 'error_noconfig':
        $title = __('No configuration file found');
        $message = __(
            '%s Console cannot find <i>include/config.php</i> or this file has invalid
		permissions and HTTP server cannot read it. Please read documentation to fix this problem.',
            get_product_name()
        ).'<br /><br />';
        if (file_exists('install.php')) {
            $link_start = '<a href="install.php">';
            $link_end = '</a>';
        } else {
            $link_start = '';
            $link_end = '';
        }

        $message .= sprintf(__('You may try to run the %s<b>installation wizard</b>%s to create one.'), $link_start, $link_end);
    break;

    case 'error_install':
        $title = __('Installer active');
        $message = __(
            'For security reasons, normal operation is not possible until you delete installer file.
		Please delete the <i>./install.php</i> file before running %s Console.',
            get_product_name()
        );
    break;

    case 'error_perms':
        $title = __('Bad permission for include/config.php');
        $message = __(
            'For security reasons, <i>config.php</i> must have restrictive permissions, and "other" users
		should not read it or write to it. It should be written only for owner
		(usually www-data or http daemon user), normal operation is not possible until you change
		permissions for <i>include/config.php</i> file. Please do it, it is for your security.'
        );
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

if ($login_screen == 'error_authconfig' || $login_screen == 'error_emptyconfig' || $login_screen == 'error_install'
    || $login_screen == 'error_dbconfig' || $login_screen == 'error_noconfig' || $login_screen == 'error_perms'
    || $login_screen == 'homedir_bad_defined' || $login_screen == 'homeurl_bad_defined'
) {
    echo '<div id="modal_alert" title="'.__('Login failed').'">';
        echo '<div class="content_alert">';
            echo '<div class="icon_message_alert">';
                echo html_print_image('images/icono_stop.png', true, ['alt' => __('Login failed'), 'border' => 0]);
            echo '</div>';
            echo '<div class="content_message_alert">';
                echo '<div class="text_message_alert">';
                    echo '<h1>'.$title.'</h1>';
                    echo '<p> '.$message.'</h1>';
                echo '</div>';
                echo '<br>';
                echo '<div class="button_message_alert">';
                    html_print_submit_button('Ok', 'hide-login-error', false, ['class' => 'mini float-right']);
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}

ui_require_css_file('dialog');
ui_require_css_file('jquery-ui.min', 'include/styles/js/');
ui_require_jquery_file('jquery-ui.min');
ui_require_jquery_file('jquery-ui_custom');
?>

<?php
// Hidden div to forced title.
html_print_div(['id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true]);

// html_print_div(array('id' => 'modal_alert', 'hidden' => true));
?>
<script type="text/javascript" language="javascript">    
    function show_normal_menu() {
        event.preventDefault();
        const style = '<?php echo $style_theme; ?>';

        document.getElementById('remove_button').style.display = 'none';
        document.getElementById('log_nick').style.display = 'block';
        document.getElementById('log_pass').style.display = 'block';
        document.getElementById('log_nick').className = `login_nick ${style}`;
        document.getElementById('log_pass').className = `login_pass ${style}`;
        document.getElementById('log_button').style.display = 'block';
    }

    switch ("<?php echo $login_screen; ?>") {
        case 'error_authconfig':
        case 'error_dbconfig':
        case 'error_emptyconfig':
        case 'error_noconfig':
        case 'error_install':
        case 'error_perms':
        case 'homedir_bad_defined':
        case 'homeurl_bad_defined':
            // Auto popup
            $(document).ready (function () {
                $(function() {
                    $("#modal_alert").dialog ({
                        title: $('#log_title').html(),
                        resizable: true,
                        draggable: false,
                        modal: true,
                        width: 600,
                        overlay: {
                            opacity: 0.5,
                            background: "black"
                        }
                    });
                });

                $("#button-hide-login-error").click (function () {
                    $("#modal_alert" ).dialog('close');
                    
                });
            });

        break;

        case 'logout':
            $(document).ready (function () {
                $(function() {
                    $("#login_logout").dialog({
                        resizable: true,
                        draggable: true,
                        modal: true,
                        width: 528,
                        clickOutside: true,
                        overlay: {
                            opacity: 0.5,
                            background: "black"
                        },
                        open: function (event, ui) {
                            $(".ui-widget-overlay").click(function () {
                                $('#login_logout').dialog('close');
                            });
                        }
                    });
                });

                $("#button-hide-login-logout").click (function () {
                    $( "#login_logout" ).dialog( "close" );
                });
            });
        break;

        case 'disabled_access_node':
            $(document).ready (function () {
                $(function() {
                    $("#disabled_access_node").dialog({
                        resizable: true,
                        draggable: true,
                        modal: true,
                        width: 528,
                        clickOutside: true,
                        overlay: {
                            opacity: 0.5,
                            background: "black"
                        }
                    });
                });

                $("#button-hide-login-logout").click (function () {
                    document.location = "<?php echo ui_get_full_url('index.php'); ?>";
                });        
            });
        break;

        default:
            $(document).ready (function () {
                // IE9- modal warning window
                $(function() {
                    $( "#dialog" ).dialog({
                        resizable: true,
                        draggable: true,
                        modal: true,
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
                        height: 230,
                        width: 530,
                        overlay: {
                            opacity: 0.5,
                            background: "black"
                        }
                    });
                });

                $("#button-hide-login-error").click (function () {
                    $("#login_failed" ).dialog('close');
                    $("#login_correct_pass").dialog('close');
                });
            });
            $('#nick').focus();
        break;
    }

    $(document).ready (function () {
        $(function() {
            $("#reset_correct").dialog({
                resizable: true,
                draggable: true,
                modal: true,
                width: 528,
                clickOutside: true,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                }
            });
        });

        $("#button-reset_correct_button").click (function () {
            $("#reset_correct").dialog('close');
        });        
    });

    $(document).ready (function () {
        $(function() {
            $("#final_process_correct").dialog({
                resizable: true,
                draggable: true,
                modal: true,
                width: 528,
                clickOutside: true,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                }
            });
        });

        $("#submit-final_process_correct_button").click (function () {
            $("#final_process_correct").dialog('close');
        });        
    });

    function centralized_mode_reset_dialog() {
        $("#centralized_mode_reset_dialog").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            width: 528,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        });

        $("#submit-centralized_mode_reset_button").click (function () {
            $("#centralized_mode_reset_dialog").dialog('close');
        });
    }

    $(document).ready(function () {
        $('#submit-login_button span').removeAttr('style');
        $('#spinner_login').hide();
    });

    $('#submit-login_button').click(function (e) {
        $('.login_nick').hide();
        $('.login_pass').hide();
        $('.login_button').hide();
        $('.reset_password').hide();
        $('#spinner_login').show();
    });

    /* ]]> */
</script>
