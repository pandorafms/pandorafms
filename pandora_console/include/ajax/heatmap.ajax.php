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
                        WHERE id_tag NOT IN (
                            SELECT a.id_tag
                            FROM ttag a, ttag_module b
                            WHERE a.id_tag = b.id_tag)
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
                            WHERE id_tag IN ('.implode(',', $id_user_tags).') AND
                                id_tag NOT IN (
                                SELECT a.id_tag
                                FROM ttag a, ttag_module b
                                WHERE a.id_tag = b.id_tag)
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
                            WHERE id_tag NOT IN (
                                SELECT a.id_tag
                                FROM ttag a, ttag_module b
                                WHERE a.id_tag = b.id_tag)
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
                echo '<p class="label-dialog">'.__('Module group').'</p>';
                echo html_print_select(
                    $module_groups,
                    'filter[]',
                    $filter,
                    '',
                    _('Not assigned'),
                    '',
                    true,
                    false,
                    false,
                    '',
                    false,
                    'width: 70%'
                );
            break;
        }

        echo '</div>';
    }

    if ($getInfo === true) {
        $id = get_parameter('id', 0);
        switch ($type) {
            case 2:
            break;

            case 1:
            break;

            case 0:
            default:
                $data = agents_get_agent($id);

                // Alias.
                echo '<div class="div-dialog">';
                echo '<p style="width:40%;font-weight: bold;padding-left: 20px;">'.__('Agent').'</p>';
                echo '<a style="width:60%;font-weight: bold;">'.$data['alias'].'</a>';
                echo '</div>';

                // Ip.
                echo '<div class="div-dialog">';
                echo '<p style="width:40%;font-weight: bold;padding-left: 20px;">'.__('IP').'</p>';
                echo '<p style="width:60%;font-weight: bold;">'.$data['direccion'].'</p>';
                echo '</div>';

                // OS.
                echo '<div class="div-dialog">';
                echo '<p style="width:40%;font-weight: bold;padding-left: 20px;">'.__('OS').'</p>';
                echo '<p style="width:60%;font-weight: bold;">'.ui_print_os_icon($data['id_os'], true, true).'</p>';
                echo '</div>';

                // Description.
                echo '<div class="div-dialog">';
                echo '<p style="width:40%;font-weight: bold;padding-left: 20px;">'.__('Description').'</p>';
                echo '<p style="width:60%;font-weight: bold;">'.$data['comentarios'].'</p>';
                echo '</div>';

                // Group.
                echo '<div class="div-dialog">';
                echo '<p style="width:40%;font-weight: bold;padding-left: 20px;">'.__('Group').'</p>';
                echo '<p style="width:60%;font-weight: bold;">'.groups_get_name($data['id_grupo']).'</p>';
                echo '</div>';

                // Events.
                echo '<div class="div-dialog">';
                echo graph_graphic_agentevents(
                    $id,
                    100,
                    40,
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
