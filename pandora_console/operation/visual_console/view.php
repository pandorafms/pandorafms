<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

// Login check.
check_login();

require_once $config['homedir'].'/vendor/autoload.php';
require_once $config['homedir'].'/include/functions_visual_map.php';

ui_require_css_file('visual_maps');

// Query parameters.
$visualConsoleId = (int) get_parameter(!is_metaconsole() ? 'id' : 'id_visualmap');
// To hide the menus.
$pure = (bool) get_parameter('pure', $config['pure']);
// Refresh interval in seconds.
$refr = (int) get_parameter('refr', $config['vc_refr']);

// Load Visual Console.
use Models\VisualConsole\Container as VisualConsole;
$visualConsole = null;
try {
    $visualConsole = VisualConsole::fromDB(['id' => $visualConsoleId]);
} catch (Throwable $e) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without Id'
    );
    include 'general/noaccess.php';
    exit;
}

$visualConsoleData = $visualConsole->toArray();
$groupId = $visualConsoleData['groupId'];
$visualConsoleName = $visualConsoleData['name'];

// ACL.
$aclRead = check_acl($config['id_user'], $groupId, 'VR');
$aclWrite = check_acl($config['id_user'], $groupId, 'VW');
$aclManage = check_acl($config['id_user'], $groupId, 'VM');

if (!$aclRead && !$aclWrite && !$aclManage) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without group access'
    );
    include 'general/noaccess.php';
    exit;
}

// Render map.
$options = [];

$options['consoles_list']['text'] = '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder">'.html_print_image(
    'images/visual_console.png',
    true,
    ['title' => __('Visual consoles list')]
).'</a>';

if ($aclWrite || $aclManage) {
    $action = get_parameterBetweenListValues(
        is_metaconsole() ? 'action2' : 'action',
        [
            'new',
            'save',
            'edit',
            'update',
            'delete',
        ],
        'edit'
    );

    $baseUrl = 'index.php?sec=network&sec2=godmode/reporting/visual_console_builder&action='.$action;

    $hash = md5($config['dbpass'].$visualConsoleId.$config['id_user']);

    $options['public_link']['text'] = '<a href="'.ui_get_full_url(
        'operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$visualConsoleId.'&id_user='.$config['id_user']
    ).'" target="_blank">'.html_print_image(
        'images/camera_mc.png',
        true,
        ['title' => __('Show link to public Visual Console')]
    ).'</a>';
    $options['public_link']['active'] = false;

    $options['data']['text'] = '<a href="'.$baseUrl.'&tab=data&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/op_reporting.png',
        true,
        ['title' => __('Main data')]
    ).'</a>';
    $options['list_elements']['text'] = '<a href="'.$baseUrl.'&tab=list_elements&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/list.png',
        true,
        ['title' => __('List elements')]
    ).'</a>';

    if (enterprise_installed()) {
        $options['wizard_services']['text'] = '<a href="'.$baseUrl.'&tab=wizard_services&id_visual_console='.$visualConsoleId.'">'.html_print_image(
            'images/wand_services.png',
            true,
            ['title' => __('Services wizard')]
        ).'</a>';
    }

    $options['wizard']['text'] = '<a href="'.$baseUrl.'&tab=wizard&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/wand.png',
        true,
        ['title' => __('Wizard')]
    ).'</a>';
    $options['editor']['text'] = '<a href="'.$baseUrl.'&tab=editor&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/builder.png',
        true,
        ['title' => __('Builder')]
    ).'</a>';
}

$options['view']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$visualConsoleId.'&refr='.$refr.'">'.html_print_image(
    'images/operation.png',
    true,
    ['title' => __('View')]
).'</a>';
$options['view']['active'] = true;

if (!is_metaconsole()) {
    if (!$config['pure']) {
        $options['pure']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$visualConsoleId.'&pure=1&refr='.$refr.'">'.html_print_image(
            'images/full_screen.png',
            true,
            ['title' => __('Full screen mode')]
        ).'</a>';
        ui_print_page_header(
            $visualConsoleName,
            'images/visual_console.png',
            false,
            '',
            false,
            $options
        );
    }

    // Set the hidden value for the javascript.
    html_print_input_hidden('metaconsole', 0);
} else {
    // Set the hidden value for the javascript.
    html_print_input_hidden('metaconsole', 1);
}

if ($pure === false) {
    echo '<div class="visual-console-edit-controls">';
    echo '<span>'.__('Move and resize mode').'</span>';
    echo '<span>';
    echo html_print_checkbox_switch('edit-mode', 1, false, true);
    echo '</span>';
    echo '</div>';
    echo '<br />';
}

echo '<div id="visual-console-container"></div>';

if ($pure === true) {
    // Floating menu - Start.
    echo '<div id="vc-controls" style="z-index: 999">';

    echo '<div id="menu_tab">';
    echo '<ul class="mn white-box-content box-shadow flex-row">';

    // Quit fullscreen.
    echo '<li class="nomn">';
    $urlNoFull = 'index.php?sec=network&sec2=operation/visual_console/render_view&id='.$visualConsoleId.'&refr='.$refr;
    echo '<a class="vc-btn-no-fullscreen" href="'.$urlNoFull.'">';
    echo html_print_image('images/normal_screen.png', true, ['title' => __('Back to normal mode')]);
    echo '</a>';
    echo '</li>';

    // Countdown.
    echo '<li class="nomn">';
    echo '<div class="vc-refr">';
    echo '<div id="vc-refr-form">';
    echo __('Refresh').':';
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
    // Floating menu - End.
    ?>
<style type="text/css">
    /* Avoid the main_pure container 1000px height */
    body.pure {
        min-height: 100px;
        margin: 0px;
        overflow: hidden;
        height: 100%;
        background-color: <?php echo $visualConsoleData['backgroundColor']; ?>;
    }
    div#main_pure {
        height: 100%;
        margin: 0px;
        background-color: <?php echo $visualConsoleData['backgroundColor']; ?>;
    }
</style>
    <?php
}

// Check groups can access user.
$aclUserGroups = [];
if (!users_can_manage_group_all('AR')) {
    $aclUserGroups = array_keys(users_get_groups(false, 'AR'));
}

$ignored_params['refr'] = '';
ui_require_javascript_file('pandora_visual_console');
include_javascript_d3();
visual_map_load_client_resources();

// Load Visual Console Items.
$visualConsoleItems = VisualConsole::getItemsFromDB(
    $visualConsoleId,
    $aclUserGroups
);
?>

<script type="text/javascript">
    var container = document.getElementById("visual-console-container");
    var props = <?php echo (string) $visualConsole; ?>;
    var items = <?php echo '['.implode($visualConsoleItems, ',').']'; ?>;
    var baseUrl = "<?php echo ui_get_full_url('/', false, false, false); ?>";
    var handleUpdate = function (prevProps, newProps) {
        if (!newProps) return;

        // Change the background color when the fullscreen mode is enabled.
        if (prevProps
            && prevProps.backgroundColor != newProps.backgroundColor
            && <?php echo json_encode($pure); ?>
        ) {
            var pureBody = document.querySelector("body.pure");
            var pureContainer = document.querySelector("div#main_pure");
            
            if (pureBody !== null) {
                pureBody.style.backgroundColor = newProps.backgroundColor
            }
            if (pureContainer !== null) {
                pureContainer.style.backgroundColor = newProps.backgroundColor
            }
        }

        // Change the title.
        if (prevProps && prevProps.name != newProps.name) {
            // View title.
            var title = document.querySelector(
                "div#menu_tab_frame_view > div#menu_tab_left span"
            );
            if (title !== null) {
                title.textContent = newProps.name;
            }
            // Fullscreen view title.
            var fullscreenTitle = document.querySelector("div.vc-title");
            if (fullscreenTitle !== null) {
                fullscreenTitle.textContent = newProps.name;
            }
            // TODO: Change the metaconsole title.
        }

        // Change the links.
        if (prevProps && prevProps.id !== newProps.id) {
            var regex = /(id=|id_visual_console=|id_layout=|id_visualmap=)\d+(&?)/gi;
            var replacement = '$1' + newProps.id + '$2';

            // Tab links.
            var menuLinks = document.querySelectorAll("div#menu_tab a");
            if (menuLinks !== null) {
                menuLinks.forEach(function (menuLink) {
                    menuLink.href = menuLink.href.replace(regex, replacement);
                });
            }

            // Go back from fullscreen button.
            var btnNoFull = document.querySelector("a.vc-btn-no-fullscreen");
            if (btnNoFull !== null) {
                btnNoFull.href = btnNoFull.href.replace(regex, replacement);
            }

            // Change the URL (if the browser has support).
            if ("history" in window) {
                var href = window.location.href.replace(regex, replacement);
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
        handleUpdate
    );

    // Enable/disable the edition mode.
    $('input[name=edit-mode]').change(function(event) {
        if ($(this).prop('checked')) {
            visualConsoleManager.visualConsole.enableEditMode();
            visualConsoleManager.changeUpdateInterval(0);
        } else {
            visualConsoleManager.visualConsole.disableEditMode();
            visualConsoleManager.changeUpdateInterval(<?php echo ($refr * 1000); ?>); // To ms.
        }
    });

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
