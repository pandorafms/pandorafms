<?php
/**
 * Group management view.
 *
 * @category   Group View
 * @package    Pandora FMS
 * @subpackage Opensource
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

// Begin.
ui_require_css_file('tree');
ui_require_css_file('fixed-bottom-box');

// Load global vars.
global $config;

check_login();

require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';

if (is_metaconsole() === true) {
    enterprise_include_once('include/functions_metaconsole.php');
    enterprise_include_once('meta/include/functions_agents_meta.php');
}

if (is_ajax() === true) {
    if ((bool) check_acl($config['id_user'], 0, 'AR') === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Group Management'
        );
        include 'general/noaccess.php';
        return;
    }

    $get_group_json = (bool) get_parameter('get_group_json');
    $get_group_agents = (bool) get_parameter('get_group_agents');
    $get_is_disabled = (bool) get_parameter('get_is_disabled');

    if ($get_group_json === true) {
        $id_group = (int) get_parameter('id_group');

        if ($id_group === 0 || $id_group === -1) {
            $group = [
                'id_grupo'  => 0,
                'nombre'    => 'None',
                'icon'      => 'world@svg.svg',
                'parent'    => 0,
                'disabled'  => 0,
                'custom_id' => null,
            ];
            echo json_encode($group);
            return;
        }

        if ((bool) check_acl($config['id_user'], $id_group, 'AR') === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Alert Management'
            );
            echo json_encode(false);
            return;
        }

        $group = db_get_row('tgrupo', 'id_grupo', $id_group);

        echo json_encode($group);
        return;
    }

    if ($get_group_agents === true) {
        ob_clean();
        $id_group = (int) get_parameter('id_group');
        $id_os = (int) get_parameter('id_os', 0);
        $disabled = (int) get_parameter('disabled', 0);
        $search = (string) get_parameter('search', '');
        $recursion = (int) get_parameter('recursion', 0);
        $privilege = (string) get_parameter('privilege', '');
        $all_agents = (int) get_parameter('all_agents', 0);
        // Is is possible add keys prefix to avoid auto sorting in
        // js object conversion.
        $keys_prefix = (string) get_parameter('keys_prefix', '');
        // This attr is for the operation "bulk alert accions add", it controls
        // the query that take the agents from db.
        $add_alert_bulk_op = get_parameter('add_alert_bulk_op', false);
        // Ids of agents to be include in the SQL clause as id_agent IN ().
        $filter_agents_json = (string) get_parameter('filter_agents_json', '');
        $status_agents = (int) get_parameter('status_agents', AGENT_STATUS_ALL);
        // Juanma (22/05/2014) Fix: If setted remove void agents from result
        // (by default and for compatibility show void agents).
        $show_void_agents = (int) get_parameter('show_void_agents', 1);
        $serialized = (bool) get_parameter('serialized', false);
        $serialized_separator = (string) get_parameter(
            'serialized_separator',
            '|'
        );
        $force_serialized = (bool) get_parameter('force_serialized', false);
        $nodes = (array) get_parameter('nodes', []);

        if ((bool) check_acl($config['id_user'], $id_group, 'AR') === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Alert Management'
            );
            echo json_encode(false);
            return;
        }

        if (https_is_running() === true) {
            header('Content-type: application/json');
        }

        if ($filter_agents_json != '') {
            $filter['id_agente'] = json_decode(
                io_safe_output($filter_agents_json),
                true
            );
        }

        if ($all_agents) {
            $filter['all_agents'] = true;
        } else {
            $filter['disabled'] = $disabled;
        }

        if ($search != '') {
            $filter['aliasRegex'] = $search;
        }

        if ($status_agents != AGENT_STATUS_ALL) {
            $filter['status'] = $status_agents;
        }

        if ($id_os !== 0) {
            $filter['id_os'] = $id_os;
        }

        $_sql_post = ' 1=1 ';
        if ($show_void_agents == 0) {
            $_sql_post .= ' AND id_agente IN (SELECT a.id_agente FROM tagente a, tagente_modulo b WHERE a.id_agente=b.id_agente AND b.delete_pending=0) AND \'1\'';
            $filter[$_sql_post] = '1';
        }

        if (is_metaconsole() === true && empty($nodes) === false) {
            $filter['id_server'] = $nodes;
        }

        $id_groups_get_agents = $id_group;
        if ($id_group == 0 && $privilege != '') {
            $groups = users_get_groups($config['id_user'], $privilege, false);
            // If group ID doesn't matter and $privilege is specified
            // (like 'AW'), retruns all agents that current user has $privilege
            // privilege for.
            $id_groups_get_agents = array_keys($groups);
        }

        $agents = agents_get_group_agents(
            $id_groups_get_agents,
            $filter,
            'none',
            false,
            $recursion,
            $serialized,
            $serialized_separator,
            $add_alert_bulk_op,
            $force_serialized
        );

        $agents_aux = [];
        foreach ($agents as $key => $value) {
            if (empty($search) === true) {
                $agents_aux[$key] = $value;
            } else if (preg_match('/'.$search.'/', io_safe_output($value)) === true) {
                $agents_aux[$key] = $value;
            }
        }

        $agents = $agents_aux;

        $agents_disabled = [];
        // Add keys prefix.
        if ($keys_prefix !== '') {
            foreach ($agents as $k => $v) {
                $agents[$keys_prefix.$k] = $v;
                unset($agents[$k]);
                if ($all_agents) {
                    // Unserialize to get the status.
                    if ($serialized && is_metaconsole()) {
                        $agent_info = explode($serialized_separator, $k);
                        $agent_disabled = db_get_value_filter(
                            'disabled',
                            'tmetaconsole_agent',
                            [
                                'id_tagente'            => $agent_info[1],
                                'id_tmetaconsole_setup' => $agent_info[0],
                            ]
                        );
                    } else if ($serialized
                        && is_metaconsole() === false
                        && $force_serialized
                    ) {
                        $agent_info = explode($serialized_separator, $k);
                        $agent_disabled = db_get_value_filter(
                            'disabled',
                            'tagente',
                            ['id_agente' => $agent_info[1]]
                        );
                    } else if (!$serialized && is_metaconsole()) {
                        // Cannot retrieve the disabled status.
                        // Mark all as not disabled.
                        $agent_disabled = 0;
                    } else {
                        $agent_disabled = db_get_value_filter(
                            'disabled',
                            'tagente',
                            ['id_agente' => $k]
                        );
                    }

                    $agents_disabled[$keys_prefix.$k] = $agent_disabled;
                }
            }
        }

        if ($all_agents) {
            $all_agents_array = [];
            $all_agents_array['agents'] = $agents;
            $all_agents_array['agents_disabled'] = $agents_disabled;

            $agents = $all_agents_array;
        }

        echo json_encode($agents);
        return;
    }

    if ($get_is_disabled === true) {
        $index = get_parameter('id_agent');

        $agent_disabled = db_get_value_filter(
            'disabled',
            'tagente',
            ['id_agente' => $index]
        );

        $return['disabled'] = $agent_disabled;
        $return['id_agent'] = $index;

        echo json_encode($return);
        return;
    }

    return;
}

$tab = (string) get_parameter('tab', 'groups');

if ($tab !== 'credbox'
    && (bool) check_acl($config['id_user'], 0, 'PM') === false
    && (bool) check_acl($config['id_user'], 0, 'AW') === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
} else if ($tab === 'credbox'
    && (bool) check_acl($config['id_user'], 0, 'UM') === false
    && (bool) check_acl($config['id_user'], 0, 'PM') === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Credential Store'
    );
    include 'general/noaccess.php';
    return;
}

$sec = defined('METACONSOLE') ? 'advanced' : 'gagente';
$url_credbox  = 'index.php?sec=gmodules&sec2=godmode/groups/group_list&tab=credbox';
$url_tree  = 'index.php?sec='.$sec.'&sec2=godmode/groups/group_list&tab=tree';
$url_groups = 'index.php?sec='.$sec.'&sec2=godmode/groups/group_list&tab=groups';

$buttons['tree'] = [
    'active' => false,
    'text'   => '<a href="'.$url_tree.'">'.html_print_image(
        'images/snmp-trap@svg.svg',
        true,
        [
            'title' => __('Tree Group view'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$buttons['groups'] = [
    'active' => false,
    'text'   => '<a href="'.$url_groups.'">'.html_print_image(
        'images/groups@svg.svg',
        true,
        [
            'title' => __('Group view'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$buttons['credbox'] = [
    'active' => false,
    'text'   => '<a href="'.$url_credbox.'">'.html_print_image(
        'images/key.png',
        true,
        [
            'title' => __('Credential Store'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$title = __('Groups defined in %s', get_product_name());
// Marks correct tab.
switch ($tab) {
    case 'tree':
        $buttons['tree']['active'] = true;
        $title .= sprintf(' &raquo; %s', __('Tree view'));
    break;

    case 'credbox':
        $buttons['credbox']['active'] = true;
        $title = __('Credential store');
    break;

    case 'groups':
    default:
        $buttons['groups']['active'] = true;
        $title .= sprintf(' &raquo; %s', __('Table view'));
    break;
}

// Header.
if (is_metaconsole() === true) {
    agents_meta_print_header();
} else {
    // Header.
    ui_print_standard_header(
        $title,
        'images/group.png',
        false,
        '',
        false,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Profiles'),
            ],
            [
                'link'  => '',
                'label' => __('Manage agents group'),
            ],
        ]
    );
}

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=godmode/groups/group_list&tab=groups'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All groups information is read only. Go to %s to manage it.',
            $url
        )
    );
}

// Load credential store view before parse list-tree forms.
if ($tab == 'credbox') {
    include_once __DIR__.'/credential_store.php';
    // Stop script.
    return;
}

$create_group = (bool) get_parameter('create_group');
$update_group = (bool) get_parameter('update_group');
$delete_group = (bool) get_parameter('delete_group');
$pure = get_parameter('pure', 0);

// Create group.
if ($is_management_allowed === true
    && $create_group === true
    && ((bool) check_acl($config['id_user'], 0, 'PM') === true)
) {
    $name = (string) get_parameter('name');
    $icon = (string) get_parameter('icon');
    $id_parent = (int) get_parameter('id_parent');
    $group_pass = (string) get_parameter('group_pass');
    $alerts_disabled = (bool) get_parameter('alerts_disabled');
    $custom_id = (string) get_parameter('custom_id');
    $skin = (string) get_parameter('skin');
    $description = (string) get_parameter('description');
    $contact = (string) get_parameter('contact');
    $other = (string) get_parameter('other');
    $max_agents = (int) get_parameter('max_agents', 0);
    $check = db_get_value('nombre', 'tgrupo', 'nombre', $name);
    $propagate = (bool) get_parameter('propagate');

    $aviable_name = true;
    if (preg_match('/script/i', $name)) {
        $aviable_name = false;
    }

    // Check if name field is empty.
    if ($name != '') {
        if (!$check) {
            if ($aviable_name === true) {
                $values = [
                    'nombre'      => $name,
                    'icon'        => $icon,
                    'parent'      => $id_parent,
                    'disabled'    => $alerts_disabled,
                    'custom_id'   => $custom_id,
                    'id_skin'     => $skin,
                    'description' => $description,
                    'contact'     => $contact,
                    'propagate'   => $propagate,
                    'other'       => $other,
                    'password'    => io_safe_input($group_pass),
                    'max_agents'  => $max_agents,
                ];

                $result = db_process_sql_insert('tgrupo', $values);
            }

            if ($result) {
                ui_print_success_message(__('Group successfully created'));
            } else {
                ui_print_error_message(__('There was a problem creating group'));
            }
        } else {
            ui_print_error_message(__('Each group must have a different name'));
        }
    } else {
        ui_print_error_message(__('Group must have a name'));
    }
}

// Update group.
if ($is_management_allowed === true && $update_group === true) {
    $id_group = (int) get_parameter('id_group');
    $name = (string) get_parameter('name');
    $icon = (string) get_parameter('icon');
    $id_parent = (int) get_parameter('id_parent');
    $description = (string) get_parameter('description');
    $group_pass = (string) get_parameter('group_pass');
    $alerts_enabled = (bool) get_parameter('alerts_enabled');
    $custom_id = (string) get_parameter('custom_id');
    $propagate = (bool) get_parameter('propagate');
    $skin = (string) get_parameter('skin');
    $description = (string) get_parameter('description');
    $contact = (string) get_parameter('contact');
    $other = (string) get_parameter('other');
    $max_agents = (int) get_parameter('max_agents', 0);

    $aviable_name = true;
    if (preg_match('/script/i', $name)) {
        $aviable_name = false;
    }

    // Check if group name is unique.
    $check = db_get_value_filter(
        'nombre',
        'tgrupo',
        [
            'nombre'   => $name,
            'id_grupo' => $id_group,
        ],
        'AND NOT'
    );

    // Check if name field is empty.
    if ($name != '') {
        if (!$check) {
            if ($aviable_name === true) {
                $values = [
                    'nombre'      => $name,
                    'icon'        => $icon,
                    'parent'      => ($id_parent == -1) ? 0 : $id_parent,
                    'disabled'    => !$alerts_enabled,
                    'custom_id'   => $custom_id,
                    'id_skin'     => $skin,
                    'description' => $description,
                    'contact'     => $contact,
                    'propagate'   => $propagate,
                    'other'       => $other,
                    'password'    => io_safe_input($group_pass),
                    'max_agents'  => $max_agents,
                ];

                $result = db_process_sql_update(
                    'tgrupo',
                    $values,
                    ['id_grupo' => $id_group]
                );
            }

            if ($result) {
                ui_update_name_fav_element($id_group, 'Groups', $name);
                ui_print_success_message(__('Group successfully updated'));
            } else {
                ui_print_error_message(__('There was a problem modifying group'));
            }
        } else {
            ui_print_error_message(__('Each group must have a different name'));
        }
    } else {
        ui_print_error_message(__('Group must have a name'));
    }
}

// Delete group.
if ($is_management_allowed === true
    && $delete_group === true
    && ((bool) check_acl($config['id_user'], 0, 'PM') === true)
) {
    $id_group = (int) get_parameter('id_group');

    $usedGroup = groups_check_used($id_group);

    if (!$usedGroup['return']) {
        $errors_meta = false;
        if (is_metaconsole()) {
            $group_name = groups_get_name($id_group);
            $servers = metaconsole_get_servers();

            $error_counter = 0;
            $success_counter = 0;
            $success_nodes = [];
            $error_nodes = [];
            // Check if the group can be deleted or not.
            if (isset($servers) === true
                && is_array($servers) === true
            ) {
                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $result_exist_group = db_get_row_filter(
                            'tgrupo',
                            [
                                'nombre'   => $group_name,
                                'id_grupo' => $id_group,
                            ]
                        );
                        if ($result_exist_group !== false) {
                            $used_group = groups_check_used($id_group);
                            // Save the names of the nodes that are empty
                            // and can be deleted, and those that cannot.
                            if (!$used_group['return']) {
                                $success_nodes[] .= $server['server_name'];
                                $success_counter++;
                            } else {
                                $error_nodes[] .= $server['server_name'];
                                $error_counter++;
                            }
                        }
                    }

                    metaconsole_restore_db();
                }
            }

            if ($error_counter > 0) {
                ui_print_error_message(
                    __(
                        'The group %s could not be deleted because it is not empty in the nodes',
                        $group_name
                    ).': '.implode(', ', $error_nodes)
                );
                $errors_meta = true;
            } else {
                if ($success_counter > 0) {
                    $error_deleting_counter = 0;
                    $success_deleting_counter = 0;
                    $error_deleting = [];
                    $success_deleting = [];
                    $error_connecting_node = [];
                    // Delete the group in the nodes.
                    if (isset($servers) === true
                        && is_array($servers) === true
                    ) {
                        foreach ($servers as $server) {
                            if (metaconsole_connect($server) == NOERR) {
                                $group = db_get_row_filter(
                                    'tgrupo',
                                    ['id_grupo' => $id_group]
                                );

                                db_process_sql_update(
                                    'tgrupo',
                                    ['parent' => $group['parent']],
                                    ['parent' => $id_group]
                                );

                                db_process_sql_delete(
                                    'tgroup_stat',
                                    ['id_group' => $id_group]
                                );

                                $result = db_process_sql_delete(
                                    'tgrupo',
                                    ['id_grupo' => $id_group]
                                );

                                if ($result === false) {
                                    $error_deleting[] .= $server['server_name'];
                                    $error_deleting_counter++;
                                } else {
                                    $success_deleting[] .= $server['server_name'];
                                    $success_deleting_counter++;
                                }
                            } else {
                                $error_deleting_counter++;
                                $error_connecting_node[] .= $server['server_name'];
                            }

                            metaconsole_restore_db();
                        }
                    }

                    // If the group could not be deleted in any node,
                    // do not delete it in meta.
                    if ($error_deleting_counter > 0) {
                        $errors_meta = true;
                        if (empty($error_connecting_node) === false) {
                            ui_print_error_message(
                                __(
                                    'Error connecting to %s',
                                    implode(
                                        ', ',
                                        $error_connecting_node
                                    ).'. The group has not been deleted in the metaconsole.'
                                )
                            );
                        }

                        if (empty($error_deleting) === false) {
                            ui_print_error_message(
                                __(
                                    'The group has not been deleted in the metaconsole due to an error in the node database'
                                ).': '.implode(', ', $error_deleting)
                            );
                        }
                    }

                    if ($success_deleting_counter > 0) {
                        ui_print_success_message(
                            __(
                                'The group %s has been deleted in the nodes',
                                $group_name
                            ).': '.implode(', ', $success_deleting)
                        );
                    }
                }
            }
        }

        if ($errors_meta === false) {
            $group = db_get_row_filter(
                'tgrupo',
                ['id_grupo' => $id_group]
            );

            db_process_sql_update(
                'tgrupo',
                ['parent' => $group['parent']],
                ['parent' => $id_group]
            );

            $result = db_process_sql_delete(
                'tgroup_stat',
                ['id_group' => $id_group]
            );

            $result = db_process_sql_delete(
                'tgrupo',
                ['id_grupo' => $id_group]
            );

            if ($result && (!$usedGroup['return'])) {
                db_process_sql_delete(
                    'tfavmenu_user',
                    [
                        'id_element' => $id_group,
                        'section'    => 'Groups',
                        'id_user'    => $config['id_user'],
                    ]
                );
                ui_print_success_message(__('Group successfully deleted'));
            } else {
                ui_print_error_message(
                    __('There was a problem deleting group')
                );
            }
        }
    } else {
        ui_print_error_message(
            sprintf(
                __('The group is not empty. It is use in %s.'),
                implode(', ', $usedGroup['tables'])
            )
        );
    }
}

// Credential store is loaded previously in this document to avoid
// process group tree - list forms.
ui_print_spinner(__('Loading'));
if ($tab == 'tree') {
    /*
     * Group tree view.
     */
    echo "<div id='tree-controller-recipient'></div>";
} else {
    /*
     * Group list view.
     */

    $acl = '';
    $search_name = '';
    $offset = (int) get_parameter('offset', 0);
    $search = (string) get_parameter('search', '');
    $block_size = $config['block_size'];

    $tablePagination = '';

    if (empty($search) === false) {
        $search_name = 'AND t.nombre LIKE "%'.$search.'%"';
    }

    if (users_can_manage_group_all('AR') === false) {
        $user_groups_acl = users_get_groups(false, 'AR');
        $groups_acl = implode('","', $user_groups_acl);
        if (empty($groups_acl) === true) {
            return ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __('There are no defined groups'),
                ]
            );
        }

        $acl = 'AND t.nombre IN ("'.$groups_acl.'")';
    }

    $form = "<form method='post' action=''>";
        $form .= "<table class='filter-table-adv' width='100%'>";
            $form .= '<tr><td>'.html_print_label_input_block(
                __('Search'),
                html_print_input_text(
                    'search',
                    $search,
                    '',
                    30,
                    30,
                    true
                )
            );
            $form .= '</td>';
            $form .= '</tr>';
        $form .= '</table>';
        $buttons = html_print_submit_button(
            __('Filter'),
            'find',
            false,
            [
                'icon' => 'search',
                'mode' => 'mini',
            ],
            true
        );

        $form .= html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => $buttons,
            ],
            true
        );
    $form .= '</form>';

    ui_toggle(
        $form,
        '<span class="subsection_header_title">'.__('Filters').'</span>',
        'filter_form',
        '',
        true,
        false,
        '',
        'white-box-content',
        'box-flat white_table_graph fixed_filter_bar'
    );

    if (is_metaconsole() === true) {
        ui_print_info_message(
            __('Edit or delete groups can cause problems with synchronization')
        );
    }

    $groups_sql = sprintf(
        'SELECT t.*,
			p.nombre  AS parent_name,
			IF(t.parent=p.id_grupo, 1, 0) AS has_child
		 FROM tgrupo t
		 LEFT JOIN tgrupo p
			ON t.parent=p.id_grupo
		 WHERE 1=1
		 %s
         %s
		ORDER BY nombre
        LIMIT %d, %d',
        $acl,
        $search_name,
        $offset,
        $block_size
    );

    $groups = db_get_all_rows_sql($groups_sql);

    if (empty($groups) === false) {
        // Count all groups for pagination only saw user and filters.
        $groups_sql_count = sprintf(
            'SELECT count(*)
			FROM tgrupo t
			WHERE 1=1
            %s
            %s',
            $acl,
            $search_name
        );
        $groups_count = db_get_value_sql($groups_sql_count);

        $table = new StdClass();
        $table->width = '100%';
        $table->class = 'info_table';
        $table->headstyle = [];
        $table->head = [];
        $table->head[0] = __('ID');
        $table->headstyle[0] = 'min-width: 100px;';
        $table->head[1] = __('Name');
        $table->headstyle[1] = 'min-width: 100px;';
        $table->head[2] = __('Icon');
        $table->headstyle[2] = 'min-width: 100px;';
        $table->head[3] = __('Alerts');
        $table->headstyle[3] = 'min-width: 100px;';
        $table->head[4] = __('Parent');
        $table->headstyle[4] = 'min-width: 100px;';
        $table->head[5] = __('Description');
        $table->headstyle[5] = 'min-width: 100px;';
        if ($is_management_allowed === true) {
            $table->head[6] = __('Actions');
            $table->headstyle[6] = 'min-width: 100px;';
        }

        $table->align = [];
        $table->align[0] = 'left';
        $table->align[2] = 'left';
        if ($is_management_allowed === true) {
            $table->align[6] = 'left';
        }

        $table->size[0] = '3%';
        $table->size[5] = '30%';
        if ($is_management_allowed === true) {
            $table->size[6] = '5%';
        }

        $table->data = [];

        foreach ($groups as $key => $group) {
            $url_edit = 'index.php?sec=gagente&sec2=godmode/groups/configure_group&id_group='.$group['id_grupo'];
            if (is_metaconsole()) {
                $url_delete = 'index.php?sec=gagente&sec2=godmode/groups/group_list&delete_group=1&id_group='.$group['id_grupo'].'&tab=groups';
            } else {
                $url_delete = 'index.php?sec=gagente&sec2=godmode/groups/group_list&delete_group=1&id_group='.$group['id_grupo'];
            }

            $table->data[$key][0] = $group['id_grupo'];
            if ($is_management_allowed === true) {
                $table->data[$key][1] = '<a href="'.$url_edit.'">'.$group['nombre'].'</a>';
            } else {
                $table->data[$key][1] = $group['nombre'];
            }

            if ($group['icon'] != '') {
                $extension = pathinfo($group['icon'], PATHINFO_EXTENSION);
                if (empty($extension) === true) {
                    $group['icon'] .= '.png';
                }

                if (empty($extension) === true || $extension === 'png') {
                    $path = 'images/groups_small/'.$group['icon'];
                } else {
                    $path = 'images/'.$group['icon'];
                }

                $table->data[$key][2] = html_print_image(
                    $path,
                    true,
                    [
                        'style' => '',
                        'class' => 'bot main_menu_icon invert_filter',
                        'alt'   => io_safe_input($group['nombre']),
                        'title' => io_safe_input($group['nombre']),
                    ],
                    false,
                    false,
                    false,
                    true
                );
            } else {
                $table->data[$key][2] = '';
            }


            // Reporting_get_group_stats.
            $table->data[$key][3] = ($group['disabled']) ? __('Disabled') : __('Enabled');
            $table->data[$key][4] = $group['parent_name'];
            $table->data[$key][5] = $group['description'];
            if ($is_management_allowed === true) {
                $table->cellclass[$key][6] = 'table_action_buttons';
                $table->data[$key][6] = '<a href="'.$url_edit.'">'.html_print_image(
                    'images/edit.svg',
                    true,
                    [
                        'alt'   => __('Edit'),
                        'title' => __('Edit'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>';

                if (is_metaconsole() === true) {
                    $confirm_message = __('Are you sure? This group will also be deleted in all the nodes.');
                } else {
                    $confirm_message = __('Are you sure?');
                }

                if ($group['has_child']) {
                    $confirm_message = __('The child groups will be updated to use the parent id of the deleted group').'. '.$confirm_message;
                }

                $table->data[$key][6] .= '<a href="'.$url_delete.'" onClick="if (!confirm(\' '.$confirm_message.'\')) return false;">'.html_print_image(
                    'images/delete.svg',
                    true,
                    [
                        'alt'   => __('Delete'),
                        'title' => __('Delete'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>';
            }
        }

        html_print_table($table);
        $tablePagination = ui_pagination(
            $groups_count,
            false,
            $offset,
            $block_size,
            true,
            'offset',
            false,
            ''
        );
    } else {
        ui_print_info_message(
            [
                'no_close' => true,
                'message'  => __('There are no defined groups'),
            ]
        );
    }
}

$button_form = '';
if ($is_management_allowed === true
    && (bool) check_acl($config['id_user'], 0, 'PM') === true
) {
    $button_form = '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/groups/configure_group">';
    $button_form .= html_print_submit_button(
        __('Create group'),
        'crt',
        false,
        ['icon' => 'next'],
        true
    );
    $button_form .= '</form>';
}


html_print_action_buttons(
    $button_form,
    [
        'type'          => 'data_table',
        'class'         => 'fixed_action_buttons',
        'right_content' => $tablePagination,
    ]
);


ui_require_javascript_file('TreeController', 'include/javascript/tree/');

$tab = 'group_edition';

?>

<?php if (is_metaconsole() === false) { ?>
    <script type="text/javascript" src="include/javascript/fixed-bottom-box.js"></script>
<?php } else { ?>
    <script type="text/javascript" src="../../include/javascript/fixed-bottom-box.js"></script>
<?php } ?>

<script type="text/javascript">
    var treeController = TreeController.getController();
    treeController.meta = <?php echo (is_metaconsole() === true) ? 1 : 0; ?>;

    if (typeof treeController.recipient != 'undefined' && treeController.recipient.length > 0)
            treeController.recipient.empty();

        showSpinner();

        var parameters = {};
        parameters['page'] = "include/ajax/tree.ajax";
        parameters['getChildren'] = 1;
        parameters['type'] = "<?php echo $tab; ?>";
        parameters['filter'] = {};
        parameters['filter']['searchGroup'] = '';
        parameters['filter']['searchAgent'] = '';
        parameters['filter']['statusAgent'] = '';
        parameters['filter']['searchModule'] = '';
        parameters['filter']['statusModule'] = '';
        parameters['filter']['groupID'] = '';
        parameters['filter']['tagID'] = '';
        parameters['filter']['searchHirearchy'] = 1;
        parameters['filter']['show_not_init_agents'] = 1;
        parameters['filter']['show_not_init_modules'] = 1;

        $.ajax({
            type: "POST",
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            data: parameters,
            success: function(data) {
                if (data.success) {
                    hideSpinner();

                    treeController.init({
                        recipient: $("div#tree-controller-recipient"),
                        page: parameters['page'],
                        emptyMessage: "<?php echo __('No data found'); ?>",
                        foundMessage: "<?php echo __('Found groups'); ?>",
                        tree: data.tree,
                        baseURL: "<?php echo ui_get_full_url(false, false, false, is_metaconsole()); ?>",
                        ajaxURL: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                        filter: parameters['filter'],
                        counterTitles: {
                            total: {
                                agents: "<?php echo __('Total agents'); ?>",
                                modules: "<?php echo __('Total modules'); ?>",
                                none: "<?php echo __('Total'); ?>"
                            },
                            alerts: {
                                agents: "<?php echo __('Fired alerts'); ?>",
                                modules: "<?php echo __('Fired alerts'); ?>",
                                none: "<?php echo __('Fired alerts'); ?>"
                            },
                            critical: {
                                agents: "<?php echo __('Critical agents'); ?>",
                                modules: "<?php echo __('Critical modules'); ?>",
                                none: "<?php echo __('Critical'); ?>"
                            },
                            warning: {
                                agents: "<?php echo __('Warning agents'); ?>",
                                modules: "<?php echo __('Warning modules'); ?>",
                                none: "<?php echo __('Warning'); ?>"
                            },
                            unknown: {
                                agents: "<?php echo __('Unknown agents'); ?>",
                                modules: "<?php echo __('Unknown modules'); ?>",
                                none: "<?php echo __('Unknown'); ?>"
                            },
                            not_init: {
                                agents: "<?php echo __('Not init agents'); ?>",
                                modules: "<?php echo __('Not init modules'); ?>",
                                none: "<?php echo __('Not init'); ?>"
                            },
                            ok: {
                                agents: "<?php echo __('Normal agents'); ?>",
                                modules: "<?php echo __('Normal modules'); ?>",
                                none: "<?php echo __('Normal'); ?>"
                            }
                        }
                    });
                }
            },
            dataType: "json"
        });
</script>
