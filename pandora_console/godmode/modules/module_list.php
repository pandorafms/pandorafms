<?php
/**
 * Module Type List.
 *
 * @category   Modules.
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access module management'
    );
    include 'general/noaccess.php';
    exit;
}

// Header.
ui_print_standard_header(
    __('Defined module types'),
    'images/module_group.png',
    false,
    '',
    true,
    [],
    [
        [
            'link'  => '',
            'label' => __('Resources'),
        ],
        [
            'link'  => '',
            'label' => __('Module types'),
        ],
    ]
);


$update_module = (bool) get_parameter_post('update_module');

// Update
if ($update_module === true) {
    $name = get_parameter_post('name');
    $id_type = get_parameter_post('id_type');
    $description = get_parameter_post('description');
    $icon = get_parameter_post('icon');
    $category = get_parameter_post('category');

    $values = [
        'descripcion' => $description,
        'categoria'   => $category,
        'nombre'      => $name,
        'icon'        => $icon,
    ];

    $result = db_process_sql_update('ttipo_modulo', $values, ['id_tipo' => $id_type]);

    if (! $result) {
        ui_print_error_message(__('Problem modifying module'));
    } else {
        ui_print_success_message(__('Module updated successfully'));
    }
}

$table = new stdClass();
$table->id = 'module_type_list';
$table->class = 'info_table';
$table->size = [];
$table->size[0] = '5%';
$table->size[1] = '5%';
$table->head = [];
$table->head[0] = __('ID');
$table->head[1] = __('Icon');
$table->head[2] = __('Name');
$table->head[3] = __('Description');

$table->data = [];

$rows = db_get_all_rows_sql('SELECT * FROM ttipo_modulo ORDER BY id_tipo');
if ($rows === false) {
    $rows = [];
}

foreach ($rows as $row) {
    $data[0] = $row['id_tipo'];
    $data[1] = html_print_image('images/'.$row['icon'], true, ['class' => 'main_menu_icon invert_filter']);
    $data[2] = $row['nombre'];
    $data[3] = $row['descripcion'];

    array_push($table->data, $data);
}

html_print_table($table);
// $tablePagination = ui_pagination($total_groups, $url, $offset, 0, true, 'offset', false);
