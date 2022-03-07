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

if (is_ajax() === true) {
    global $config;

    // Login check.
    check_login();

    $getFilters = (bool) get_parameter('getFilters', 0);
    $getFilterType = (bool) get_parameter('getFilterType', 0);
    $getInfo = (bool) get_parameter('getInfo', 0);
    $type = get_parameter('type', 0);

    if ($getFilters === true) {
        $refresh = get_parameter('refresh', 30);
        $search = get_parameter('search', '');
        $group = get_parameter('group', true);

        echo '<form id="form_dialog" method="post">';
            echo '<div class="div-dialog">';
                echo '<p class="label-dialog">'.__('Refresh').'</p>';
                echo html_print_select(
                    [
                        '30'                      => __('30 seconds'),
                        (string) SECONDS_1MINUTE  => __('1 minute'),
                        '180'                     => __('3 minute'),
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
                    'margin-top: 3px;'
                );
            echo '</div>';

            echo '<div class="div-dialog">';
                echo '<p class="label-dialog">'.__('Search').'</p>';
                echo html_print_input_text('search', $search, '', 30, 255, true);
            echo '</div>';

            echo '<div class="div-dialog">';
                echo '<p class="label-dialog">'.__('Type').'</p>';
                echo html_print_select(
                    [
                        0 => __('Group agents'),
                        1 => __('Group modules by tag'),
                        2 => __('Group modules by module group'),
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
                    'margin-top: 3px;width:70%'
                );
            echo '</div>';

            echo '<div class="div-dialog">';
                echo '<p class="label-dialog">'.__('Show groups').'</p>';
                echo html_print_checkbox('group', 1, $group, true);
            echo '</div>';
        echo '</form>';
    }


    if ($getFilterType === true) {
        $filter = get_parameter('filter', 0);
        echo '<div id="filter_type" class="div-dialog">';
        switch ($type) {
            case 0:
            default:
                echo '<p style="width:42%;font-weight: bold;">'.__('Group').'</p>';
                echo html_print_input(
                    [
                        'type'           => 'select_groups',
                        'returnAllGroup' => true,
                        'name'           => 'filter[]',
                        'selected'       => $filter,
                        'return'         => true,
                        'required'       => true,
                        'privilege'      => 'AR',
                    ]
                );
            break;

            case 1:
                echo '<p class="label-dialog">'.__('Tag').'</p>';
                if (tags_has_user_acl_tags($config['id_user']) === false) {
                    echo html_print_select_from_sql(
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
                        'width: 200px',
                        '5'
                    );
                } else {
                    $user_tags = tags_get_user_tags($config['id_user'], 'AR');
                    if (!empty($user_tags)) {
                        $id_user_tags = array_keys($user_tags);

                        echo html_print_select_from_sql(
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
                            'width: 200px',
                            '5'
                        );
                    } else {
                        echo html_print_select_from_sql(
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
                            'width: 200px',
                            '5'
                        );
                    }
                }
            break;

            case 2:
                $module_groups = modules_get_modulegroups();
                // $module_groups[0] = _('Not assigned');
                // hd(current($filter));
                echo '<p class="label-dialog">'.__('Module group').'</p>';
                echo html_print_select(
                    $module_groups,
                    'filter[]',
                    $filter,
                    '',
                    _('Not assigned'),
                    0,
                    true,
                    false,
                    false,
                    '',
                    false,
                    'width: 70%',
                    false,
                    false,
                    false,
                    '',
                    true
                );
            break;
        }

        echo '</div>';
    }

    if ($getInfo === true) {
        $id = get_parameter('id', 0);
        switch ($type) {
            case 2:
                $data = db_get_row('tagente_modulo', 'id_agente_modulo', $id);
                // Nombre.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Module name').'</p>';
                echo '<a class="info-dialog">'.$data['nombre'].'</a>';
                echo '</div>';

                // Descripcion.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Description').'</p>';
                echo '<p class="info-dialog">'.$data['descripcion'].'</p>';
                echo '</div>';

                // Agent.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Agent').'</p>';
                echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$data['id_agente'].'"
                    class="info-dialog" target="_blank">'.agents_get_alias($data['id_agente']).'</a>';
                echo '</div>';

                // Group.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Group').'</p>';
                echo '<p class="info-dialog">'.modules_get_modulegroup_name($data['id_module_group']).'</p>';
                echo '</div>';
            break;

            case 1:
                $data = db_get_row('tagente_modulo', 'id_agente_modulo', $id);
                // Nombre.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Module name').'</p>';
                echo '<a class="info-dialog">'.$data['nombre'].'</a>';
                echo '</div>';

                // Descripcion.
                echo '<div class="div-dialog">';
                echo '<p sclass="title-dialog">'.__('Description').'</p>';
                echo '<p class="info-dialog">'.$data['descripcion'].'</p>';
                echo '</div>';

                // Agent.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Agent').'</p>';
                echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$data['id_agente'].'"
                    class="info-dialog" target="_blank">'.agents_get_alias($data['id_agente']).'</a>';
                echo '</div>';

                // Group.
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Group').'</p>';
                echo '<p class="info-dialog">'.modules_get_modulegroup_name($data['id_module_group']).'</p>';
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
                echo '<div class="div-dialog">';
                echo '<p class="title-dialog">'.__('Group').'</p>';
                echo '<p class="info-dialog">'.groups_get_name($data['id_grupo']).'</p>';
                echo '</div>';

                // Events.
                echo '<div class="div-dialog">';
                echo graph_graphic_agentevents(
                    $id,
                    100,
                    80,
                    SECONDS_1DAY,
                    '',
                    true,
                    false,
                    false,
                    1
                );
                echo '</div>';
            break;
        }
    }

    return;
}
