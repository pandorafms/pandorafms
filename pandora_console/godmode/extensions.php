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
check_login();

global $config;

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access extensions list'
    );
    include 'general/noaccess.php';
    exit;
}

// Header.
ui_print_standard_header(
    __('Extensions'),
    'images/extensions.png',
    false,
    '',
    true,
    [],
    [
        [
            'link'  => '',
            'label' => __('Admin tools'),
        ],
        [
            'link'  => '',
            'label' => __('Extension manager'),
        ],
        [
            'link'  => '',
            'label' => __('Defined extensions'),
        ],
    ]
);

if (count($config['extensions']) == 0) {
    $extensions = extensions_get_extension_info();
    if (empty($extensions)) {
        echo '<h3>'.__('There are no extensions defined').'</h3>';
        return;
    }
}

$enterprise = (bool) get_parameter('enterprise', 0);
$delete = get_parameter('delete', '');
$enabled = get_parameter('enabled', '');
$disabled = get_parameter('disabled', '');


if ($delete != '') {
    if ($enterprise) {
        if (!file_exists($config['homedir'].'/enterprise/extensions/ext_backup')) {
            mkdir($config['homedir'].'/enterprise/extensions/ext_backup');
        }
    } else {
        if (!file_exists($config['homedir'].'/extensions/ext_backup')) {
            mkdir($config['homedir'].'/extensions/ext_backup');
        }
    }

    if ($enterprise) {
        $source = $config['homedir'].'/enterprise/extensions/'.$delete;
        $endFile = $config['homedir'].'/enterprise/extensions/ext_backup/'.$delete;
    } else {
        $source = $config['homedir'].'/extensions/'.$delete;
        $endFile = $config['homedir'].'/extensions/ext_backup/'.$delete;
    }


    rename($source, $endFile);

    ?>
    <script type="text/javascript">
    $(document).ready(function() {
            var href = location.href.replace(/&enterprise=(0|1)&delete=.*/g, "");
            location = href;
        }
    );
    </script>
    <?php
}


if ($enabled != '') {
    if ($enterprise) {
        $endFile = $config['homedir'].'/enterprise/extensions/'.$enabled;
        $source = $config['homedir'].'/enterprise/extensions/disabled/'.$enabled;
    } else {
        $endFile = $config['homedir'].'/extensions/'.$enabled;
        $source = $config['homedir'].'/extensions/disabled/'.$enabled;
    }

    rename($source, $endFile);
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
            var href = location.href.replace(/&enterprise=(0|1)&enabled=.*/g, "");
            location = href;
        }
    );
    </script>
    <?php
}

if ($disabled != '') {
    if ($enterprise) {
        if (!file_exists($config['homedir'].'/enterprise/extensions/disabled')) {
            mkdir($config['homedir'].'/enterprise/extensions/disabled');
        }
    } else {
        if (!file_exists($config['homedir'].'/extensions/disabled')) {
            mkdir($config['homedir'].'/extensions/disabled');
        }
    }

    if ($enterprise) {
        $source = $config['homedir'].'/enterprise/extensions/'.$disabled;
        $endFile = $config['homedir'].'/enterprise/extensions/disabled/'.$disabled;
    } else {
        $source = $config['homedir'].'/extensions/'.$disabled;
        $endFile = $config['homedir'].'/extensions/disabled/'.$disabled;
    }


    rename($source, $endFile);
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
            var href = location.href
            href = href.replace(/&enterprise=(0|1)&disabled=.*/g, "");
            location = href;
        }
    );
    </script>
    <?php
}

$extensions = extensions_get_extension_info();

$table = new StdClass;
$table->width = '100%';

$table->head = [];
$table->head[] = __('File');
$table->head[] = __('Version');
$table->head[] = __('Enterprise');
$table->head[] = __('Godmode Function');
$table->head[] = __('Godmode Menu');
$table->head[] = __('Operation Menu');
$table->head[] = __('Operation Function');
$table->head[] = __('Login Function');
$table->head[] = __('Agent operation tab');
$table->head[] = __('Agent godmode tab');
$table->head[] = __('Operation');

$table->class = 'info_table';

$table->align = [];
$table->align[] = 'left';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';

$table->data = [];
foreach ($extensions as $file => $extension) {
    $data = [];

    $on = html_print_image('images/dot_green.png', true);
    $off = html_print_image('images/dot_red.png', true);
    if (!$extension['enabled']) {
        $on = html_print_image('images/dot_green.disabled.png', true);
        $off = html_print_image('images/dot_red.disabled.png', true);
        $data[] = '<i class="grey">'.$file.'</i>';

        // Get version of this extensions
        if ($config['extensions'][$file]['operation_menu']) {
            $data[] = $config['extensions'][$file]['operation_menu']['version'];
        } else if ($config['extensions'][$file]['godmode_menu']) {
            $data[] = $config['extensions'][$file]['godmode_menu']['version'];
        } else if ($config['extensions'][$file]['extension_ope_tab']) {
            $data[] = $config['extensions'][$file]['extension_ope_tab']['version'];
        } else if ($config['extensions'][$file]['extension_god_tab']) {
            $data[] = $config['extensions'][$file]['extension_god_tab']['version'];
        } else {
            $data[] = __('N/A');
        }
    } else {
        $data[] = $file;

        // Get version of this extension
        if ($config['extensions'][$file]['operation_menu']) {
            $data[] = $config['extensions'][$file]['operation_menu']['version'];
        } else if ($config['extensions'][$file]['godmode_menu']) {
            $data[] = $config['extensions'][$file]['godmode_menu']['version'];
        } else if (isset($config['extensions'][$file]['extension_ope_tab'])) {
            $data[] = $config['extensions'][$file]['extension_ope_tab']['version'];
        } else if ($config['extensions'][$file]['extension_god_tab']) {
            $data[] = $config['extensions'][$file]['extension_god_tab']['version'];
        } else {
            $data[] = __('N/A');
        }
    }

    if ($extension['enterprise']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    if ($extension['godmode_function']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    if ($extension['godmode_menu']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    if ($extension['operation_menu']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    if ($extension['operation_function']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    if ($extension['login_function']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    if ($extension['extension_ope_tab']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    if ($extension['extension_god_tab']) {
        $data[] = $on;
    } else {
        $data[] = $off;
    }

    // Avoid to delete or disabled update_manager
    if ($file != 'update_manager.php') {
        $table->cellclass[][10] = 'table_action_buttons';
        if (!$extension['enabled']) {
            $data[] = html_print_menu_button(
                [
                    'href'    => 'index.php?sec=godmode/extensions&amp;sec2=godmode/extensions&enterprise='.(int) $extension['enterprise'].'&delete='.$file,
                    'image'   => 'images/cross.disabled.png',
                    'title'   => __('Delete'),
                    'onClick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
                ],
                true
            ).html_print_menu_button(
                [
                    'href'  => 'index.php?sec=godmode/extensions&amp;sec2=godmode/extensions&enterprise='.(int) $extension['enterprise'].'&enabled='.$file,
                    'image' => 'images/lightbulb_off.png',
                    'title' => __('Delete'),
                ],
                true
            );
        } else {
            $data[] = html_print_menu_button(
                [
                    'href'    => 'index.php?sec=godmode/extensions&amp;sec2=godmode/extensions&enterprise='.(int) $extension['enterprise'].'&delete='.$file,
                    'image'   => 'images/delete.svg',
                    'class'   => 'main_menu_icon invert_filter',
                    'title'   => __('Delete'),
                    'onClick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
                ],
                true
            ).html_print_menu_button(
                [
                    'href'  => 'index.php?sec=godmode/extensions&amp;sec2=godmode/extensions&enterprise='.(int) $extension['enterprise'].'&disabled='.$file,
                    'image' => 'images/lightbulb.png',
                    'title' => __('Delete'),
                ],
                true
            );
        }
    } else {
        $data[] = '';
    }

    $table->data[] = $data;
}

html_print_table($table);

