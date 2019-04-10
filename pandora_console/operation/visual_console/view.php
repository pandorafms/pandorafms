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

// Query parameters.
$visualConsoleId = (int) get_parameter(!is_metaconsole() ? 'id' : 'id_visualmap');
$pure = (bool) get_parameter('pure', $config['pure']);

if (!$visualConsoleId) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$layout = db_get_row('tlayout', 'id', $visualConsoleId);
if (!$layout) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access visual console without id layout'
    );
    include 'general/noaccess.php';
    exit;
}

$groupId = $layout['id_group'];
$visualConsoleName = $layout['name'];

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
    $url_base = 'index.php?sec=network&sec2=godmode/reporting/visual_console_builder&action=';

    $hash = md5($config['dbpass'].$visualConsoleId.$config['id_user']);

    $options['public_link']['text'] = '<a href="'.ui_get_full_url(
        'operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$visualConsoleId.'&id_user='.$config['id_user']
    ).'" target="_blank">'.html_print_image(
        'images/camera_mc.png',
        true,
        ['title' => __('Show link to public Visual Console')]
    ).'</a>';
    $options['public_link']['active'] = false;

    $options['data']['text'] = '<a href="'.$url_base.$action.'&tab=data&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/op_reporting.png',
        true,
        ['title' => __('Main data')]
    ).'</a>';
    $options['list_elements']['text'] = '<a href="'.$url_base.$action.'&tab=list_elements&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/list.png',
        true,
        ['title' => __('List elements')]
    ).'</a>';

    if (enterprise_installed()) {
        $options['wizard_services']['text'] = '<a href="'.$url_base.$action.'&tab=wizard_services&id_visual_console='.$visualConsoleId.'">'.html_print_image(
            'images/wand_services.png',
            true,
            ['title' => __('Services wizard')]
        ).'</a>';
    }

    $options['wizard']['text'] = '<a href="'.$url_base.$action.'&tab=wizard&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/wand.png',
        true,
        ['title' => __('Wizard')]
    ).'</a>';
    $options['editor']['text'] = '<a href="'.$url_base.$action.'&tab=editor&id_visual_console='.$visualConsoleId.'">'.html_print_image(
        'images/builder.png',
        true,
        ['title' => __('Builder')]
    ).'</a>';
}

$options['view']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$visualConsoleId.'">'.html_print_image('images/operation.png', true, ['title' => __('View')]).'</a>';
$options['view']['active'] = true;

if (!is_metaconsole()) {
    if (!$config['pure']) {
        $options['pure']['text'] = '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$visualConsoleId.'&pure=1">'.html_print_image('images/full_screen.png', true, ['title' => __('Full screen mode')]).'</a>';
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

use Models\VisualConsole\Container as VisualConsole;

// TODO: Show an error message when the models can't be loaded.
$visualConsole = VisualConsole::fromArray($layout);
$visualConsoleItems = VisualConsole::getItemsFromDB($visualConsoleId);

// TODO: Extract to a function.
$vcClientPath = 'include/visual-console-client';
$dir = $config['homedir'].'/'.$vcClientPath;
if (is_dir($dir)) {
    $dh = opendir($dir);
    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            preg_match('/.*.js$/', $file, $match, PREG_OFFSET_CAPTURE);
            if (empty($match) === false) {
                $url = ui_get_full_url(false, false, false, false).$vcClientPath.'/'.$match[0][0];
                echo '<script type="text/javascript" src="'.$url.'"></script>';
                continue;
            }

            preg_match('/.*.css$/', $file, $match, PREG_OFFSET_CAPTURE);
            if (empty($match) === false) {
                $url = ui_get_full_url(false, false, false, false).$vcClientPath.'/'.$match[0][0];
                echo '<link rel="stylesheet" type="text/css" href="'.$url.'" />';
            }
        }

        closedir($dh);
    }
}

echo '<div id="visual-console-container" style="margin:0px auto;position:relative;"></div>';

if ($pure === true) {
    // Floating menu - Start.
    echo '<div id="vc-controls" style="z-index: 999">';

    echo '<div id="menu_tab">';
    echo '<ul class="mn">';

    // Quit fullscreen.
    echo '<li class="nomn">';
    echo '<a href="index.php?sec=network&sec2=operation/visual_console/render_view&id='.$visualConsoleId.'">';
    echo html_print_image('images/normal_screen.png', true, ['title' => __('Back to normal mode')]);
    echo '</a>';
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
        background-color: <?php echo $layout['background_color']; ?>;
    }
    div#main_pure {
        height: 100%;
        margin: 0px;
        background-color: <?php echo $layout['background_color']; ?>;
    }
</style>
    <?php
}
?>

<script type="text/javascript">
    var container = document.getElementById("visual-console-container");
    var props = <?php echo (string) $visualConsole; ?>;
    var items = <?php echo '['.implode($visualConsoleItems, ',').']'; ?>;

    if (container != null) {
        try {
            var visualConsole = new VisualConsole(container, props, items);
            console.log(visualConsole);
        } catch (error) {
            console.log("ERROR", error.message);
        }
    }
</script>
