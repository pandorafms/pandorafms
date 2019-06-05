<?php
/**
 * Extension to self monitor Pandora FMS Console
 *
 * @category   Update Manager
 * @package    Pandora FMS
 * @subpackage Update Manager Online
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

ui_require_css_file('update_manager', 'godmode/update_manager/');
require_once __DIR__.'/../../include/functions_update_manager.php';

enterprise_include_once('include/functions_update_manager.php');

require_once __DIR__.'/../../include/functions_config.php';

$memory_limit_min = '500M';
$post_max_size_min = '800M';
$upload_max_filesize_min = '800M';

$PHPmemory_limit_min = config_return_in_bytes($memory_limit_min);
$PHPpost_max_size_min = config_return_in_bytes($post_max_size);
$PHPupload_max_filesize_min = config_return_in_bytes($upload_max_filesize_min);

$php_settings_fine = 0;
$PHP_SETTINGS_REQUIRED = 3;

$memory_limit = config_return_in_bytes(ini_get('memory_limit'));
if ($memory_limit < $PHPmemory_limit_min) {
    ui_print_error_message(
        sprintf(
            __('Your PHP has set memory limit in %s. To use Update Manager Online, please set it to %s'),
            ini_get('memory_limit'),
            $memory_limit_min
        )
    );
} else {
    $php_settings_fine++;
}

$post_max_size = config_return_in_bytes(ini_get('post_max_size'));
if ($post_max_size < $PHPpost_max_size_min) {
    ui_print_error_message(
        sprintf(
            __('Your PHP has post_max_size limited to %s. To use Update Manager Online, please set it to %s'),
            ini_get('post_max_size'),
            $post_max_size_min
        )
    );
} else {
    $php_settings_fine++;
}

$upload_max_filesize = config_return_in_bytes(ini_get('upload_max_filesize'));
if ($upload_max_filesize < $PHPupload_max_filesize_min) {
    ui_print_error_message(
        sprintf(
            __('Your PHP has set maximum allowed size for uploaded files limit in %s. To use Update Manager Online, please set it to %s'),
            ini_get('upload_max_filesize'),
            $upload_max_filesize_min
        )
    );
} else {
    $php_settings_fine++;
}

// Verify registry.
if (update_manager_verify_registration() === false) {
    ui_require_css_file('register');
    registration_wiz_modal(false, true, 'location.reload()');
    ui_print_error_message(
        __('Update Manager Online requires registration')
    );
} else {
    // Console registered.
    $current_package = update_manager_get_current_package();

    if (!enterprise_installed()) {
        $open = true;
    }

    // Translators: Do not translade Update Manager, it's the name of the program.
    if (is_metaconsole()) {
        echo "<style type='text/css' media='screen'>
            @import 'styles/meta_pandora.css';
        </style>";
    }

    if (is_metaconsole()) {
        $baseurl = ui_get_full_url(false, false, false, false);
        echo ' <link rel="stylesheet" type="text/css" href="'.$baseurl.'/godmode/update_manager/update_manager.css">';
        echo "<div id='box_online' class='box_online_meta'>";
    } else {
        echo "<div id='box_online'>";
    }

    if ($php_settings_fine >= $PHP_SETTINGS_REQUIRED) {
        echo "<span class='loading' style='font-size:18pt;'>";
        echo "<img src='images/wait.gif' />";
        echo '</span>';
    }

    echo '<p style="font-weight: 600;">'.__('The latest version of package installed is:').'</p>';
    if ($open) {
        echo '<div id="pkg_version" style="font-size:40pt;">'.$build_version.'</div>';
    } else {
        echo '<div id="pkg_version">'.$current_package.'</div>';
    }

        echo "<div class='checking_package' style='font-size:18pt;width:100%; display: none;'>";
            echo __('Checking for the newest package.');
        echo '</div>';

        echo "<div class='downloading_package' style='font-size:18pt;width:100%; display: none;'>";
            echo __('Downloading for the newest package.');
        echo '</div>';

        echo "<div class='content'></div>";

        echo "<div class='progressbar' style='display: none;'><img class='progressbar_img' src='' /></div>";


    /*
     * -------------------------------------------------------------------------
     * Hello there! :)
     * We added some of what seems to be "buggy" messages to the openSource
     * version recently. This is not to force open-source users to move to the
     * enterprise version, this is just to inform people using Pandora FMS open
     * source that it requires skilled people to maintain and keep it running
     * smoothly without professional support. This does not imply open-source
     * version is limited in any way. If you check the recently added code, it
     * contains only warnings and messages, no limitations except one:
     * we removed the option to add custom logo in header.
     *
     * In the Update Manager section, it warns about the 'danger’ of applying
     * automated updates without a proper backup, remembering in the process
     * that the Enterprise version comes with a human-tested package.
     *
     * Maintaining an OpenSource version with more than 500 agents is not so
     * easy, that's why someone using a Pandora with 8000 agents should consider
     * asking for support. It's not a joke, we know of many setups with a huge
     * number of agents, and we hate to hear that “its becoming unstable and
     * slow” :(
     *
     * You can of course remove the warnings, that's why we include the source
     * and do not use any kind of trick. And that's why we added here this
     * comment, to let you know this does not reflect any change in our
     * opensource mentality of does the last 14 years.
     * -------------------------------------------------------------------------
     */

    if ($open) {
            echo "<div class='update_manager_open'>
            <div class='update_manager_warning'>
                <div><img src='images/icono_info.png'></div>
            <div><p>".__('WARNING: You are just one click away from an automated update. This may result in a damaged system, including loss of data and operativity. Check you have a recent backup. OpenSource updates are automatically created packages, and there is no WARRANTY or SUPPORT. If you need professional support and warranty, please upgrade to Enterprise Version.')."</p></div>
            </div>
            <div style='text-align:center; margin-top:10px;'>
                <a class='update_manager_button_open' href='https://pandorafms.com/pandora-fms-enterprise/' target='_blank'>About Enterprise</a>
            </div>
        </div>";
    }


    if ($php_settings_fine >= $PHP_SETTINGS_REQUIRED) {
        $enterprise = enterprise_hook('update_manager_enterprise_main');

        if ($enterprise == ENTERPRISE_NOT_HOOK) {
            // Open view.
            update_manager_main();
        }
        ?>

        <script type="text/javascript">
        var isopen = "<?php echo $open; ?>";
        if(isopen){
            $(document).ready(function() {
            $('body').append( "<div id='opacidad' style='position:fixed;background:black;opacity:0.6;z-index:1'></div>" );
            jQuery.post ("ajax.php",
                {
            "page": "general/alert_enterprise",
            "message":"infomodal"},
                function (data, status) {
                    $("#alert_messages").hide ()
                        .css ("opacity", 1)
                        .empty ()
                        .append (data)
                        .show ();
                },
                "html"
            );

        return false;

        });
        }
        </script>
        <?php
    }
}
