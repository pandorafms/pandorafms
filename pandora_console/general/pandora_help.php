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
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once '../include/config.php';

require_once '../include/functions.php';
require_once '../include/functions_html.php';
?>
<html class="help_pname"><head><title>
<?php
echo __('%s help system', get_product_name());
?>
</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<?php echo '<link rel="stylesheet" href="../include/styles/'.$config['style'].'.css?v='.$config['current_package'].'" type="text/css">'; ?>
<body class="height_100p bg_333">
<?php
$id = get_parameter('id');
$id_user = get_parameter('id_user');

$user_language = get_user_language($id_user);

if (file_exists('../include/languages/'.$user_language.'.mo')) {
    $l10n = new gettext_reader(new CachedFileReader('../include/languages/'.$user_language.'.mo'));
    $l10n->load_tables();
}

// Possible file locations
$safe_language = safe_url_extraclean($user_language, 'en');

$safe_id = safe_url_extraclean($id, '');
$files = [
    $config['homedir'].'/include/help/'.$safe_language.'/help_'.$safe_id.'.php',
    $config['homedir'].'/'.ENTERPRISE_DIR.'/include/help/'.$safe_language.'/help_'.$safe_id.'.php',
    $config['homedir'].'/'.ENTERPRISE_DIR.'/include/help/en/help_'.$safe_id.'.php',
    $config['homedir'].'/include/help/en/help_'.$safe_id.'.php',
];
$help_file = '';
foreach ($files as $file) {
    if (file_exists($file)) {
        $help_file = $file;
        break;
    }
}

$logo = ui_get_custom_header_logo(true);

if (! $id || ! file_exists($help_file)) {
    echo '<div id="main_help">';
    if (!is_metaconsole()) {
        echo html_print_image($logo, true, ['border' => '0']);
    }

    echo '</div>';
    echo '<div id="parent_dic">';
    echo '<div  class="databox bg-white font_12px no_border">';
    echo '<hr class="mgn_tp_0">';
    echo '<h1 class="pdd_l_30px">';
    echo __('Help system error');
    echo '</h1>';
    echo "<div class='center bg-white'>";

    echo '</div>';
    echo '<div class="msg msg_pandora_help">'.__("%s help system has been called with a help reference that currently don't exist. There is no help content to show.", get_product_name()).'</div></div></div>';
    echo '<br /><br />';
    echo '<div id="footer_help">';
    // include 'footer.php';
    return;
}

// Show help
echo '<div id="main_help_new">';
if (!empty($config['enterprise_installed']) && is_metaconsole()) {
    echo '<img src="'.$config['homeurl'].$logo.'">';
} else {
    echo html_print_image($logo, true, ['border' => '0']);
}

echo '</div>';
echo '<div id="main_help_new_content">';
ob_start();
require_once $help_file;
$help = ob_get_contents();
ob_end_clean();

// Add a line after H1 tags
echo $help;
echo '</div>';
echo '<div id="footer_help">';
// require 'footer.php';
echo '</div>';
?>
</body>
</html>
