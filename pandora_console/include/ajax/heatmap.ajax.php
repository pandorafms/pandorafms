<?php
/**
 * Heatmap.
 *
 * @category   Heatmap
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

use PandoraFMS\Enterprise\Metaconsole\Node;

if (is_ajax() === true) {
    global $config;

    // Login check.
    check_login();

    $getFilters = (bool) get_parameter('getFilters', 0);
    $getFilterType = (bool) get_parameter('getFilterType', 0);
    $getInfo = (bool) get_parameter('getInfo', 0);
    $type = get_parameter('type', 0);

    if ($getFilters === true) {
        $refresh = get_parameter('refresh', SECONDS_5MINUTES);
        $search = get_parameter('search', '');
        $group = get_parameter('group', true);

        echo '<form id="form_dialog" method="post">';
                echo html_print_label_input_block(
                    __('Refresh'),
                    html_print_select(
                        [
                            '30'                      => __('30 seconds'),
                            (string) SECONDS_1MINUTE  => __('1 minute'),
                            '180'                     => __('3 minutes'),
                            (string) SECONDS_5MINUTES => __('5 minutes'),
                        ],
                        'refresh',
                        $refresh,
                        '',
                        '',
                        0,
                        true,
                        false,
                        false,
                        '',
                        false,
                        'width: 100%; margin-top: 10px;'
                    ),
                    [
                        'label_class' => 'font-title-font',
                        'div_class'   => 'mrgn_top_10px',
                    ]
                );

                echo html_print_label_input_block(
                    __('Search'),
                    html_print_input_text(
                        'search',
                        $search,
                        '',
                        30,
                        255,
                        true,
                        false,
                        false,
                        '',
                        'w100p'
                    ),
                    [
                        'label_class' => 'font-title-font',
                        'div_class'   => 'mrgn_top_10px',
                    ]
                );

                echo html_print_label_input_block(
                    __('Type'),
                    html_print_select(
                        [
                            0 => __('Group agents'),
                            1 => __('Group modules by tag'),
                            2 => __('Group modules by module group'),
                            3 => __('Group modules by agents'),
                        ],
                        'type',
                        $type,
                        '',
                        '',
                        0,
                        true,
                        false,
                        false,
                        '',
                        false,
                        'width: 100%; margin-top: 10px;'
                    ),
                    [
                        'label_class' => 'font-title-font',
                        'div_class'   => 'mrgn_top_10px',
                    ]
                );

                echo html_print_label_input_block(
                    __('Show groups'),
                    '<div class="w100p">'.html_print_checkbox('group', 1, $group, true).'</div>',
                    [
                        'label_class' => 'font-title-font',
                        'div_class'   => 'mrgn_top_10px',
                    ]
                );
        echo '</form>';
    }


    if ($getFilterType === true) {
        $filter = get_parameter('filter', 0);
        $label = ' ';

        switch ($type) {
            case 0:
            default:
                $label = __('Group');
                $input = html_print_input(
                    [
                        'type'           => 'select_groups',
                        'returnAllGroup' => true,
                        'name'           => 'filter[]',
                        'selected'       => $filter,
                        'return'         => true,
                        'required'       => true,
                        'privilege'      => 'AR',
                        'multiple'       => true,
                    ]
                );
            break;

            case 1:
                $label = __('Tag');
                if (tags_has_user_acl_tags($config['id_user']) === false) {
                    $input = html_print_select_from_sql(
                        'SELECT id_tag, name
                        FROM ttag
                        WHERE id_tag
                        ORDER BY name',
                        'filter[]',
                        $filter,
                        '',
                        '',
                        '',
                        true,
                        true,
                        false,
                        false,
                        'width: 100%',
                        '5'
                    );
                } else {
                    $user_tags = tags_get_user_tags($config['id_user'], 'AR');
                    if (!empty($user_tags)) {
                        $id_user_tags = array_keys($user_tags);

                        $input = html_print_select_from_sql(
                            'SELECT id_tag, name
                            FROM ttag
                            WHERE id_tag IN ('.implode(',', $id_user_tags).')
                            ORDER BY name',
                            'filter[]',
                            $filter,
                            '',
                            '',
                            '',
                            true,
                            true,
                            false,
                            false,
                            'width: 100%',
                            '5'
                        );
                    } else {
                        $input = html_print_select_from_sql(
                            'SELECT id_tag, name
                            FROM ttag
                            WHERE id_tag
                            ORDER BY name',
                            'filter[]',
                            $filter,
                            '',
                            '',
                            '',
                            true,
                            true,
                            false,
                            false,
                            'width: 100%',
                            '5'
                        );
                    }
                }
            break;

            case 2:
                $label = __('Module group');
                $input = html_print_select_from_sql(
                    'SELECT id_mg, name FROM tmodule_group ORDER BY name',
                    'filter[]',
                    $filter,
                    '',
                    __('Not assigned'),
                    '0',
                    true,
                    true,
                    true,
                    false,
                    'width: 100%',
                    '5'
                );
            break;

            case 3:
                // Empty.
            break;
        }

        echo html_print_label_input_block(
            $label,
            $input,
            [
                'label_class' => 'font-title-font',
                'div_class'   => 'mrgn_top_10px',
            ]
        );
    }

    if ($getInfo === true) {
        enterprise_include_once('include/functions_agents.php');
        $id = get_parameter('id', 0);
        $id_server = get_parameter('id_server', 0);
        if (empty($id_server) === false) {
            $nd = new Node($id_server);
            $nd->connect();
        }

        switch ($type) {
            case 3:
            case 2:
                $data = db_get_row('tagente_modulo', 'id_agente_modulo', $id);

                // Nombre.
                $link = sprintf(
                    'index.php?sec=view&sec2=operation/agentes/status_monitor%s&ag_modulename=%s',
                    '&refr=0&ag_group=0&module_option=1&status=-1',
                    $data['nombre']
                );
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Module name').'</p>';
                echo '<a href="'.$link.'" class="info-dialog">'.$data['nombre'].'</a>';
                echo '</div>';

                // Descripcion.
                $description = (empty($data['descripcion']) === true) ? '-' : $data['descripcion'];
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Description').'</p>';
                echo '<p class="info-dialog">'.$description.'</p>';
                echo '</div>';

                // Agent.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Agent').'</p>';
                echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$data['id_agente'].'"
                    class="info-dialog" target="_blank">'.agents_get_alias($data['id_agente']).'</a>';
                echo '</div>';

                // Group.
                $group = (empty($data['id_module_group']) === true)
                    ? '-'
                    : modules_get_modulegroup_name($data['id_module_group']);

                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Module group').'</p>';
                echo '<p class="info-dialog">'.$group.'</p>';
                echo '</div>';
            break;

            case 1:
                $data = db_get_row('tagente_modulo', 'id_agente_modulo', $id);

                // Nombre.
                $link = sprintf(
                    'index.php?sec=view&sec2=operation/agentes/status_monitor%s&ag_modulename=%s',
                    '&refr=0&ag_group=0&module_option=1&status=-1',
                    $data['nombre']
                );
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Module name').'</p>';
                echo '<a href="'.$link.'" class="info-dialog" target="_blank">'.$data['nombre'].'</a>';
                echo '</div>';

                // Descripcion.
                $description = (empty($data['descripcion']) === true) ? '-' : $data['descripcion'];
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Description').'</p>';
                echo '<p class="info-dialog">'.$description.'</p>';
                echo '</div>';

                // Agent.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Agent').'</p>';
                echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$data['id_agente'].'"
                    class="info-dialog" target="_blank">'.agents_get_alias($data['id_agente']).'</a>';
                echo '</div>';

                // Group.
                $group = (empty($data['id_module_group']) === true)
                    ? '-'
                    : modules_get_modulegroup_name($data['id_module_group']);

                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Module group').'</p>';
                echo '<p class="info-dialog">'.$group.'</p>';
                echo '</div>';

                // Tag.
                $tags = db_get_all_rows_sql('SELECT id_tag FROM ttag_module WHERE id_agente_modulo ='.$id);
                $tags_name = '';
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Tag').'</p>';
                foreach ($tags as $key => $tag) {
                    $tags_name .= tags_get_name($tag['id_tag']).', ';
                }

                $tags_name = trim($tags_name, ', ');
                echo '<p class="info-dialog">'.$tags_name.'</p>';
                echo '</div>';
            break;

            case 0:
            default:
                $data = agents_get_agent($id);

                // Alias.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Agent').'</p>';
                echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$data['id_agente'].'"
                    class="info-dialog" target="_blank">'.$data['alias'].'</a>';
                echo '</div>';

                // Ip.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('IP').'</p>';
                echo '<p class="info-dialog">'.$data['direccion'].'</p>';
                echo '</div>';

                // OS.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('OS').'</p>';
                echo '<p class="info-dialog">'.ui_print_os_icon($data['id_os'], true, true).'</p>';
                echo '</div>';

                // Description.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Description').'</p>';
                echo '<p class="info-dialog">'.$data['comentarios'].'</p>';
                echo '</div>';

                // Group.
                $secondary_groups = '';
                $secondary = enterprise_hook('agents_get_secondary_groups', [$data['id_agente']]);
                if (isset($secondary['for_select']) === true && empty($secondary['for_select']) === false) {
                    $secondary_groups = implode(', ', $secondary['for_select']);
                    $secondary_groups = ', '.$secondary_groups;
                }

                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Group').'</p>';
                echo '<p class="info-dialog">'.groups_get_name($data['id_grupo']).$secondary_groups.'</p>';
                echo '</div>';


                // Events.
                $result_graph_event = enterprise_hook(
                    'graph_graphic_agentevents',
                    [
                        $id,
                        100,
                        40,
                        SECONDS_1DAY,
                        '',
                        true,
                        false,
                        false,
                        1,
                    ]
                );

                if ($result_graph_event !== -1) {
                    echo '<div class="div-dialog">';
                    echo $result_graph_event;
                    echo '</div>';
                }
            break;
        }

        if (empty($id_server) === false) {
            $nd->disconnect();
        }
    }

    return;
}
