<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

require_once 'include/functions_agents.php';

check_login();

$id_agente = get_parameter_get('id_agente', -1);

if ($id_agente === -1) {
    ui_print_error_message(__('There was a problem loading agent'));
    return;
}

// All groups is calculated in ver_agente.php. Avoid to calculate it again
if (!isset($all_groups)) {
    $all_groups = agents_get_all_groups_agent($idAgent, $id_group);
}

if (! check_acl_one_of_groups($config['id_user'], $all_groups, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent General Information'
    );
    include_once 'general/noaccess.php';
    return;
}

$all_customs_fields = (bool) check_acl_one_of_groups(
    $config['id_user'],
    $all_groups,
    'AW'
);

if ($all_customs_fields) {
    $fields = db_get_all_rows_filter('tagent_custom_fields');
} else {
    $fields = db_get_all_rows_filter(
        'tagent_custom_fields',
        ['display_on_front' => 1]
    );
}

if ($fields === false) {
    $fields = [];
    ui_print_empty_data(__('No fields defined'));
} else {
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';
    $table->head = [];
    $table->head[0] = __('Field');
    $table->size[0] = '20%';
    $table->head[1] = __('Display on front').ui_print_help_tip(__('The fields with display on front enabled will be displayed into the agent details'), true);
    $table->size[1] = '20%';
    $table->head[2] = __('Description');
    $table->align = [];
    $table->align[1] = 'left';
    $table->align[2] = 'left';
    $table->data = [];

    foreach ($fields as $field) {
        $data[0] = '<b>'.$field['name'].'</b>';

        if ($field['display_on_front']) {
            $data[1] = html_print_image('images/validate.svg', true, ['class' => 'invert_filter main_menu_icon']);
        } else {
            $data[1] = html_print_image('images/delete.svg', true, ['class' => 'invert_filter main_menu_icon']);
        }

        $custom_value = db_get_all_rows_sql(
            'select tagent_custom_data.description,tagent_custom_fields.is_password_type from tagent_custom_fields 
		INNER JOIN tagent_custom_data ON tagent_custom_fields.id_field = tagent_custom_data.id_field where tagent_custom_fields.id_field = '.$field['id_field'].' and tagent_custom_data.id_agent = '.$id_agente
        );

        if ($custom_value[0]['description'] === false || $custom_value[0]['description'] == '') {
            $custom_value[0]['description'] = '<i>-'.__('empty').'-</i>';
        } else {
            $custom_value[0]['description'] = ui_bbcode_to_html($custom_value[0]['description']);
        }

        if ($custom_value[0]['is_password_type']) {
            $data[2] = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
        } else {
            $data[2] = $custom_value[0]['description'];
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
}
