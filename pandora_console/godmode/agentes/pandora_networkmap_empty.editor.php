<?php
/**
 * Empty Network map editor.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

// Check user credentials
check_login();

$id = (int) get_parameter('id_networkmap', 0);

$new_empty_networkmap = (bool) get_parameter('new_empty_networkmap', false);
$edit_networkmap = (bool) get_parameter('edit_networkmap', false);

$not_found = false;

if (empty($id)) {
    $new_empty_networkmap = true;
    $edit_networkmap = false;
}

if ($new_empty_networkmap) {
    $name = '';
    $id_group = 0;
    $node_radius = 40;
    $description = '';
}

if ($edit_networkmap) {
    if (enterprise_installed()) {
        $disabled_generation_method_select = true;
    }

    $disabled_source = true;

    $values = db_get_row('tmap', 'id', $id);

    $not_found = false;
    if ($values === false) {
        $not_found = true;
    } else {
        $id_group = $values['id_group'];

        // ACL for the network map
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }

        $name = io_safe_output($values['name']);

        $description = $values['description'];

        $filter = json_decode($values['filter'], true);

        $node_radius = $filter['node_radius'];
    }
}

// Header.
ui_print_standard_header(
    __('Empty Network maps editor'),
    'images/bricks.png',
    false,
    'network_map_enterprise_edit',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Topology maps'),
        ],
        [
            'link'  => '',
            'label' => __('Networkmap'),
        ],
    ]
);


if ($not_found) {
    ui_print_error_message(__('Not found networkmap.'));
} else {
    $table = new StdClass();
    $table->id = 'form_editor';

    $table->width = '98%';
    $table->class = 'databox_color';

    $table->head = [];

    $table->size = [];
    $table->size[0] = '30%';

    $table->style = [];
    $table->style[0] = 'font-weight: bold; width: 150px;';
    $table->data = [];

    $table->data[0][0] = __('Name');
    $table->data[0][1] = html_print_input_text(
        'name',
        $name,
        '',
        30,
        100,
        true
    );
    $table->data[1][0] = __('Group');
    $table->data[1][1] = '<div class="w250px">'.html_print_select_groups(
        false,
        'AR',
        true,
        'id_group',
        $id_group,
        '',
        '',
        0,
        true
    ).'</div>';

    $table->data[2][0] = __('Node radius');
    $table->data[2][1] = html_print_input_text(
        'node_radius',
        $node_radius,
        '',
        2,
        10,
        true
    );

    $table->data[3][0] = __('Description');
    $table->data[3][1] = html_print_textarea('description', 7, 25, $description, '', true);

    echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';

    html_print_table($table);

    echo "<div style='width: ".$table->width."; text-align: right; margin-top:20px;'>";
    if ($new_empty_networkmap) {
        html_print_input_hidden('save_empty_networkmap', 1);
        html_print_submit_button(
            __('Save networkmap'),
            'crt',
            false,
            'class="sub next"'
        );
    }

    if ($edit_networkmap) {
        html_print_input_hidden('id_networkmap', $id);
        html_print_input_hidden('update_empty_networkmap', 1);
        html_print_submit_button(
            __('Update networkmap'),
            'crt',
            false,
            'class="sub upd"'
        );
    }

    echo '</form>';
    echo '</div>';
}
