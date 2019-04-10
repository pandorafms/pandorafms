<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
require_once '../../include/config.php';

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

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

global $vc_public_view;
$vc_public_view = true;
// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head.
ob_start('ui_process_page_head');
// Enterprise main.
enterprise_include('index.php');

require_once 'include/functions_visual_map.php';

$hash = get_parameter('hash');
$id_layout = (int) get_parameter('id_layout');
$graph_javascript = (bool) get_parameter('graph_javascript');
$config['id_user'] = get_parameter('id_user');

$myhash = md5($config['dbpass'].$id_layout.$config['id_user']);

// Check input hash.
if ($myhash != $hash) {
    exit;
}

$refr = (int) get_parameter('refr', 0);
$layout = db_get_row('tlayout', 'id', $id_layout);

if (! $layout) {
    db_pandora_audit('ACL Violation', 'Trying to access visual console without id layout');
    include $config['homedir'].'/general/noaccess.php';
    exit;
}

if (!isset($config['pure'])) {
    $config['pure'] = 0;
}

use Models\VisualConsole\Container as VisualConsole;

if ($layout) {
    $id_group = $layout['id_group'];
    $layout_name = $layout['name'];

    $visualConsole = VisualConsole::fromArray($layout);
    $visualConsoleItems = VisualConsole::getItemsFromDB($id_layout);

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
}

// Floating menu - Start.
echo '<div id="vc-controls" style="z-index:300;">';

echo '<div id="menu_tab">';
echo '<ul class="mn">';

// QR code.
echo '<li class="nomn">';
echo '<a href="javascript: show_dialog_qrcode();">';
echo '<img class="vc-qr" src="../../images/qrcode_icon_2.jpg"/>';
echo '</a>';
echo '</li>';

// Console name.
echo '<li class="nomn">';
echo '<div class="vc-title">'.$layout_name.'</div>';
echo '</li>';

echo '</ul>';
echo '</div>';

echo '</div>';
// Floating menu - End
// QR code dialog.
echo '<div style="display: none;" id="qrcode_container" title="'.__('QR code of the page').'">';
echo '<div id="qrcode_container_image"></div>';
echo '</div>';

$ignored_params['refr'] = '';
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
