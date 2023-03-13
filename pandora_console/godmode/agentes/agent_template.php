<?php
/**
 * Agent Modules Templates.
 *
 * @category   Module
 * @package    Pandora FMS
 * @subpackage Agent Configuration
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

global $config;

// Load global vars.
if (isset($id_agente) === false) {
    die('Not Authorized');
}

require_once $config['homedir'].'/include/functions_modules.php';

// ==========================
// TEMPLATE ASSIGMENT LOGIC
// ==========================
if (isset($_POST['template_id']) === true) {
    // Take agent data.
    $row = db_get_row('tagente', 'id_agente', $id_agente);
    if ($row !== false) {
        $intervalo = $row['intervalo'];
        $nombre_agente = $row['nombre'];
        $direccion_agente = $row['direccion'];
        $ultima_act = $row['ultimo_contacto'];
        $ultima_act_remota = $row['ultimo_contacto_remoto'];
        $comentarios = $row['comentarios'];
        $id_grupo = $row['id_grupo'];
        $id_os = $row['id_os'];
        $os_version = $row['os_version'];
        $agent_version = $row['agent_version'];
        $disabled = $row['disabled'];
    } else {
        return;
    }

    $id_np = get_parameter_post('template_id');
    $name_template = db_get_value('name', 'tnetwork_profile', 'id_np', $id_np);
    $npc = db_get_all_rows_field_filter('tnetwork_profile_component', 'id_np', $id_np);
    if ($npc === false) {
        $npc = [];
    }

    $success_count = 0;
    $error_count = 0;
    $modules_already_added = [];

    foreach ($npc as $row) {
        $nc = db_get_all_rows_field_filter('tnetwork_component', 'id_nc', $row['id_nc']);

        if ($nc === false) {
            $nc = [];
        }

        foreach ($nc as $row2) {
            // Insert each module from tnetwork_component into agent.
            $values = [
                'id_agente'             => $id_agente,
                'id_tipo_modulo'        => $row2['type'],
                'descripcion'           => __('Created by template ').$name_template.' . '.$row2['description'],
                'max'                   => $row2['max'],
                'min'                   => $row2['min'],
                'module_interval'       => $row2['module_interval'],
                'tcp_port'              => $row2['tcp_port'],
                'tcp_send'              => $row2['tcp_send'],
                'tcp_rcv'               => $row2['tcp_rcv'],
                'snmp_community'        => $row2['snmp_community'],
                'snmp_oid'              => $row2['snmp_oid'],
                'ip_target'             => $direccion_agente,
                'id_module_group'       => $row2['id_module_group'],
                'id_modulo'             => $row2['id_modulo'],
                'plugin_user'           => $row2['plugin_user'],
                'plugin_pass'           => $row2['plugin_pass'],
                'plugin_parameter'      => $row2['plugin_parameter'],
                'unit'                  => $row2['unit'],
                'max_timeout'           => $row2['max_timeout'],
                'max_retries'           => $row2['max_retries'],
                'id_plugin'             => $row2['id_plugin'],
                'post_process'          => $row2['post_process'],
                'dynamic_interval'      => $row2['dynamic_interval'],
                'dynamic_max'           => $row2['dynamic_max'],
                'dynamic_min'           => $row2['dynamic_min'],
                'dynamic_two_tailed'    => $row2['dynamic_two_tailed'],
                'min_warning'           => $row2['min_warning'],
                'max_warning'           => $row2['max_warning'],
                'str_warning'           => $row2['str_warning'],
                'min_critical'          => $row2['min_critical'],
                'max_critical'          => $row2['max_critical'],
                'str_critical'          => $row2['str_critical'],
                'critical_inverse'      => $row2['critical_inverse'],
                'warning_inverse'       => $row2['warning_inverse'],
                'percentage_warning'    => $row2['percentage_warning'],
                'percentage_critical'   => $row2['percentage_critical'],
                'critical_instructions' => $row2['critical_instructions'],
                'warning_instructions'  => $row2['warning_instructions'],
                'unknown_instructions'  => $row2['unknown_instructions'],
                'id_category'           => $row2['id_category'],
                'macros'                => $row2['macros'],
                'each_ff'               => $row2['each_ff'],
                'min_ff_event'          => $row2['min_ff_event'],
                'min_ff_event_normal'   => $row2['min_ff_event_normal'],
                'min_ff_event_warning'  => $row2['min_ff_event_warning'],
                'min_ff_event_critical' => $row2['min_ff_event_critical'],
                'ff_type'               => $row2['ff_type'],
            ];

            $name = $row2['name'];

            // Put tags in array if the component has to add them later.
            if (empty($row2['tags']) === false) {
                $tags = explode(',', $row2['tags']);
            } else {
                $tags = [];
            }

            // Check if this module exists in the agent.
            $module_name_check = db_get_value_filter('id_agente_modulo', 'tagente_modulo', ['delete_pending' => 0, 'nombre' => $name, 'id_agente' => $id_agente]);

            if ($module_name_check !== false) {
                $modules_already_added[] = $row2['name'];
                $error_count++;
            } else {
                $id_agente_modulo = modules_create_agent_module($id_agente, $name, $values);

                if ($id_agente_modulo === false) {
                    $error_count++;
                } else {
                    if (empty($tags) === false) {
                        // Creating tags.
                        $tag_ids = [];
                        foreach ($tags as $tag_name) {
                            $tag_id = tags_get_id($tag_name);

                            // If tag exists in the system we store to create it.
                            $tag_ids[] = $tag_id;
                        }

                        tags_insert_module_tag($id_agente_modulo, $tag_ids);
                    }

                    $success_count++;
                }
            }
        }
    }

    if ($error_count > 0) {
        if (empty($modules_already_added) === true) {
            ui_print_error_message(__('Error adding modules').sprintf(' (%s)', $error_count));
        } else {
            ui_print_error_message(__('Error adding modules. The following errors already exists: ').implode(', ', $modules_already_added));
        }
    }

    if ($success_count > 0) {
        ui_print_success_message(__('Modules successfully added'));
    }
}

// Main header
// ==========================
// TEMPLATE ASSIGMENT FORM
// ==========================
$nps = db_get_all_fields_in_table('tnetwork_profile', 'name');
if ($nps === false) {
    $nps = [];
}

$select = [];
foreach ($nps as $row) {
    $select[$row['id_np']] = $row['name'];
}

$filterTable = new stdClass();
$filterTable->width = '100%';
$filterTable->class = 'filter-table-adv';
$filterTable->size[0] = '100%';
$filterTable->data = [];

$filterTable->data[0][0] = html_print_label_input_block(
    __('Module templates'),
    html_print_select(
        $select,
        'template_id',
        '',
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 250px '
    )
);

$filterTable->data[1][0] = html_print_submit_button(
    __('Assign'),
    'crt',
    false,
    [
        'class' => 'float-right',
        'icon'  => 'wand',
        'mode'  => 'mini',
    ],
    true
);

$outputFilterTable = '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=template&id_agente='.$id_agente.'">';
$outputFilterTable .= ui_toggle(
    html_print_table($filterTable, true),
    '<span class="subsection_header_title">'.__('Assign').'</span>',
    __('Assign'),
    'assign',
    true,
    true,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph fixed_filter_bar'
);
$outputFilterTable .= '</form>';

echo $outputFilterTable;

// ==========================
// MODULE VISUALIZATION TABLE
// ==========================
$sql = sprintf(
    'SELECT *
    FROM tagente_modulo
    WHERE id_agente = %d AND delete_pending = false
    ORDER BY id_module_group, nombre',
    $id_agente
);

$result = db_get_all_rows_sql($sql);
if ($result === false) {
    $result = [];
}

$table = new stdClass();
$table->width = '100%';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->class = 'info_table';
$table->head = [];
$table->data = [];
$table->align = [];

$table->head[0] = '<span>'.__('Module name').'</span>';
$table->head[1] = '<span>'.__('Type').'</span>';
$table->head[2] = '<span>'.__('Description').'</span>';
$table->head[3] = '<span>'.__('Action').'</span>';

$table->align[1] = 'left';
$table->align[3] = 'left';
$table->size[0] = '30%';
$table->size[1] = '5%';
$table->size[3] = '8%';

foreach ($result as $row) {
    $table->cellclass[][3] = 'table_action_buttons';

    $data = [];

    $data[0] = '<span>'.$row['nombre'];
    $data[1] = ($row['id_tipo_modulo'] > 0) ? ui_print_moduletype_icon($row['id_tipo_modulo'], true, false, true) : '';
    $data[2] = mb_substr($row['descripcion'], 0, 60);
    $data[3] = html_print_menu_button(
        [
            'href'    => 'index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&tab=template&id_agente='.$id_agente.'&delete_module='.$row['id_agente_modulo'],
            'image'   => 'images/delete.svg',
            'title'   => __('Delete'),
            'onClick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
        ],
        true
    );
    $data[3] .= html_print_menu_button(
        [
            'href'  => 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&edit_module=1&id_agent_module='.$row['id_agente_modulo'],
            'image' => 'images/edit.svg',
            'title' => __('Edit'),
        ],
        true
    );

    array_push($table->data, $data);
}

if (empty($table->data) === false) {
    $output = html_print_table($table, true);
} else {
    $output = ui_print_empty_data(__('No modules'), '', true);
}

html_print_div(
    [
        'class'   => 'datatable_form',
        'content' => $output,
    ]
);
