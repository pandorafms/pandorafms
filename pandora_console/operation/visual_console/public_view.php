<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
require_once '../../include/config.php';

use PandoraFMS\User;

// Set root on homedir, as defined in setup.
chdir($config['homedir']);

ob_start();
// Enterprise support.
if (file_exists(ENTERPRISE_DIR.'/load_enterprise.php')) {
    include_once ENTERPRISE_DIR.'/load_enterprise.php';
}

if (file_exists(ENTERPRISE_DIR.'/include/functions_login.php')) {
    include_once ENTERPRISE_DIR.'/include/functions_login.php';
}

require_once $config['homedir'].'/vendor/autoload.php';

ui_require_css_file('register', 'include/styles/', true);

// Connection lost alert.
// ui_require_javascript_file('connection_check', 'include/javascript/', true);
set_js_value('absolute_homeurl', ui_get_full_url(false, false, false, false));
$conn_title = __('Connection with console has been lost');
$conn_text = __('Connection to the console has been lost. Please check your internet connection.');
ui_print_message_dialog($conn_title, $conn_text, 'connection', '/images/fail@svg.svg');

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

global $vc_public_view;
global $config;

$vc_public_view = true;
$config['public_access'] = true;

// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head.
ob_start('ui_process_page_head');
// Enterprise main.
enterprise_include('index.php');

$url_css = ui_get_full_url('include/styles/visual_maps.css', false, false, false);
echo '<link rel="stylesheet" href="'.$url_css.'?v='.$config['current_package'].'" type="text/css" />';

require_once 'include/functions_visual_map.php';

$hash = (string) get_parameter('hash');

// Check input hash.
// DO NOT move it after of get parameter user id.
if (User::validatePublicHash($hash) !== true) {
    db_pandora_audit(
        AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT,
        'Trying to access public visual console'
    );
    include 'general/noaccess.php';
    exit;
}

$visualConsoleId = (int) get_parameter('id_layout');
$userAccessMaintenance = null;
if (empty($config['id_user']) === true) {
    $config['id_user'] = (string) get_parameter('id_user');
} else {
    $userAccessMaintenance = $config['id_user'];
}

$refr = (int) get_parameter('refr', ($config['refr'] ?? null));

if (!isset($config['pure'])) {
    $config['pure'] = 0;
}

// Load Visual Console.
use Models\VisualConsole\Container as VisualConsole;
$visualConsole = null;
try {
    $visualConsole = VisualConsole::fromDB(['id' => $visualConsoleId]);
} catch (Throwable $e) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access visual console without Id'
    );
    include $config['homedir'].'/general/noaccess.php';
    exit;
}

$visualConsoleData = $visualConsole->toArray();
$visualConsoleName = $visualConsoleData['name'];

$bg_color = '';
if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
    $bg_color = 'style="background-color: #222"';
}

echo '<div id="visual-console-container"></div>';

// Floating menu - Start.
echo '<div id="vc-controls" class="zindex300" '.$bg_color.'>';

echo '<div id="menu_tab">';
echo '<ul class="mn white-box-content box-shadow flex-row">';

// QR code.
echo '<li class="nomn">';
echo '<a style="padding-top: 0;" href="javascript: show_dialog_qrcode();">';
echo '<img class="vc-qr" src="../../images/qrcode_icon_2.jpg"/>';
echo '</a>';
echo '</li>';

// Countdown.
echo '<li class="nomn" style="display: flex; align-items: center">';
echo '<div class="vc-refr">';
echo '<div id="vc-refr-form" style="display: flex; align-items: center">';
echo '<span class="margin-right-1">'.__('Refresh').'</span>';
echo html_print_select(
    get_refresh_time_array(),
    'vc-refr',
    $refr,
    '',
    '',
    0,
    true,
    false,
    false
);
echo '</div>';
echo '</div>';
echo '</li>';

// Console name.
echo '<li class="nomn">';
echo '<div class="vc-title">'.$visualConsoleName.'</div>';
echo '</li>';

echo '</ul>';
echo '</div>';

echo '</div>';

// QR code dialog.
echo '<div class="invisible" id="qrcode_container" title="'.__('QR code of the page').'">';
echo '<div id="qrcode_container_image"></div>';
echo '</div>';


// Check groups can access user.
$aclUserGroups = [];
if (!users_can_manage_group_all('AR')) {
    $aclUserGroups = array_keys(users_get_groups(false, 'AR'));
}

$ignored_params['refr'] = '';
ui_require_javascript_file('pandora_visual_console', 'include/javascript/', true);
include_javascript_d3();
visual_map_load_client_resources();

// Load Visual Console Items.
$visualConsoleItems = VisualConsole::getItemsFromDB(
    $visualConsoleId,
    $aclUserGroups
);

?>

<style type="text/css">
    body {
        background-color: <?php echo $visualConsoleData['backgroundColor']; ?>;
    }
</style>

<script type="text/javascript">
    var container = document.getElementById("visual-console-container");
    var user = "<?php echo $userAccessMaintenance; ?>";
    var props = <?php echo (string) $visualConsole; ?>;
    var items = <?php echo '['.implode(',', $visualConsoleItems).']'; ?>;
    var baseUrl = "<?php echo ui_get_full_url('/', false, false, false); ?>";

    var controls = document.getElementById('vc-controls');
    autoHideElement(controls, 1000);

    var handleUpdate = function (prevProps, newProps) {
        if (!newProps) return;

        //Remove spinner change VC.
        document
            .getElementById("visual-console-container")
            .classList.remove("is-updating");

        var div = document
            .getElementById("visual-console-container")
            .querySelector(".div-visual-console-spinner");

        if (div !== null) {
            var parent = div.parentElement;
            if (parent !== null) {
                parent.removeChild(div);
            }
        }

        // Change the background color when the fullscreen mode is enabled.
        if (prevProps
            && prevProps.backgroundColor != newProps.backgroundColor
        ) {
            var body = document.querySelector("body");
            if (body !== null) {
                body.style.backgroundColor = newProps.backgroundColor
            }
        }

        // Change the title.
        if (prevProps && prevProps.name != newProps.name) {
            var title = document.querySelector("div.vc-title");
            if (title !== null) {
                title.textContent = newProps.name;
            }

            // Fullscreen Meta view title.
            var titleMeta = document.querySelector("div.vc-title-meta");
            if (titleMeta !== null) {
                titleMeta.textContent = newProps.name;
            }
        }

        // Change the links.
        if (prevProps && prevProps.id !== newProps.id) {
            var regex = /(id=|id_visual_console=|id_layout=|id_visualmap=)\d+(&?)/gi;
            var replacement = '$1' + newProps.id + '$2';

            var regex_hash = /(hash=)[^&]+(&?)/gi;
            var replacement_hash = '$1' + newProps.hash + '$2';
            // Tab links.
            var menuLinks = document.querySelectorAll("div#menu_tab a");
            if (menuLinks !== null) {
                menuLinks.forEach(function (menuLink) {
                    menuLink.href = menuLink.href.replace(regex, replacement);
                    //menuLink.href = menuLink.href.replace(
                    //    regex_hash,
                    //    replacement_hash
                    //);
                });
            }

            // Change the URL (if the browser has support).
            if ("history" in window) {
                var href = window.location.href.replace(regex, replacement);
                //href = href.replace(regex_hash, replacement_hash);
                window.history.replaceState({}, document.title, href);
            }
        }
    }

    // Add the datetime when the item was received.
    var receivedAt = new Date();
    items.map(function(item) {
        item["receivedAt"] = receivedAt;
        return item;
    });

    var visualConsoleManager = createVisualConsole(
        container,
        props,
        items,
        baseUrl,
        <?php echo ($refr * 1000); ?>,
        handleUpdate,
        // BeforeUpdate.
        null,
        // Size.
        null,
        // User id.
        "<?php echo get_parameter('id_user', ''); ?>",
        // Hash.
        "<?php echo get_parameter('hash', ''); ?>"
    );

    if(props.maintenanceMode != null) {
        if(props.maintenanceMode.user !== user) {
            visualConsoleManager.visualConsole.enableMaintenanceMode();
        }
    }

    var controls = document.getElementById('vc-controls');
    autoHideElement(controls, 1000);

    // Update the data fetch interval.
    $('select#vc-refr').change(function(event) {
        var refr = Number.parseInt(event.target.value);

        if (!Number.isNaN(refr)) {
            visualConsoleManager.changeUpdateInterval(refr * 1000); // To ms.

            // Change the URL (if the browser has support).
            if ("history" in window) {
                var regex = /(refr=)\d+(&?)/gi;
                var replacement = '$1' + refr + '$2';
                var href = window.location.href.replace(regex, replacement);
                window.history.replaceState({}, document.title, href);
            }
        }
    });
</script>
<?php
// Clean session to avoid direct access.
if ($config['force_instant_logout'] === true) {
    // Force user logout.
    $iduser = $_SESSION['id_usuario'];
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];
    session_destroy();
    header_remove('Set-Cookie');
    if (isset($_COOKIE[session_name()]) === true) {
        setcookie(session_name(), $_COOKIE[session_name()], (time() - 4800), '/');
    }
}

while (ob_get_length() > 0) {
    ob_end_flush();
}