<?php
/**
 * Agents defined view.
 *
 * @category   Manage Agents.
 * @package    Pandora FMS
 * @subpackage Resources.
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
check_login();

// Take some parameters (GET).
$offset = (int) get_parameter('offset');
$group_id = (int) get_parameter('group_id');
$ag_group = get_parameter('ag_group_refresh', -1);
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$recursion = (bool) get_parameter('recursion', false);
$disabled = (int) get_parameter('disabled');
$os = (int) get_parameter('os');

if ($ag_group === -1) {
    $ag_group = (int) get_parameter('ag_group', -1);
}

if (($ag_group == -1) && ($group_id != 0)) {
    $ag_group = $group_id;
}

if (! check_acl(
    $config['id_user'],
    0,
    'AW'
) && ! check_acl(
    $config['id_user'],
    0,
    'AD'
)
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access agent manager'
    );
    include 'general/noaccess.php';
    exit;
}

enterprise_include_once('include/functions_policies.php');
require_once 'include/functions_agents.php';
require_once 'include/functions_users.php';
enterprise_include_once('include/functions_config_agents.php');

$search = get_parameter('search');

// Prepare the tab system to the future.
$tab = 'view';

// Setup tab.
$viewtab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente">'.html_print_image(
    'images/see-details@svg.svg',
    true,
    [
        'title' => __('View'),
        'class' => 'invert_filter',
    ]
).'</a>';

$viewtab['operation'] = true;

$viewtab['active'] = false;

$onheader = ['view' => $viewtab];

// Header.
ui_print_standard_header(
    __('Agents defined in %s', get_product_name()),
    'images/agent.png',
    false,
    '',
    true,
    $onheader,
    [
        [
            'link'  => '',
            'label' => __('Resources'),
        ],
        [
            'link'  => '',
            'label' => __('Manage agents'),
        ],
    ]
);

if (is_management_allowed() === false) {
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/massive_operations&tab=massive_agents'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. Go to %s to delete an agent',
            $url
        )
    );
}

// Perform actions.
$agent_to_delete = (int) get_parameter('borrar_agente');
$enable_agent = (int) get_parameter('enable_agent');
$disable_agent = (int) get_parameter('disable_agent');

if ($disable_agent !== 0) {
    $server_name = db_get_row_sql(
        'select server_name from tagente where id_agente = '.$disable_agent
    );
} else if ($enable_agent !== 0) {
    $server_name = db_get_row_sql(
        'select server_name from tagente where id_agente = '.$enable_agent
    );
}

$result = null;

if ($agent_to_delete > 0) {
    $id_agente = $agent_to_delete;
    if (check_acl_one_of_groups(
        $config['id_user'],
        agents_get_all_groups_agent($id_agente),
        'AW'
    )
    ) {
        $id_agentes[0] = $id_agente;
        $result = agents_delete_agent($id_agentes);
    } else {
        // NO permissions.
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            "Trying to delete agent \'".agents_get_name($id_agente)."\'"
        );
        include 'general/noaccess.php';
        exit;
    }

    ui_print_result_message(
        $result,
        __('Success deleted agent.'),
        __('Could not be deleted.')
    );

    if (enterprise_installed() === true) {
        // Check if the remote config file still exist.
        if (isset($config['remote_config']) === true) {
            if ((bool) enterprise_hook('config_agents_has_remote_configuration', [$id_agente]) === true) {
                ui_print_error_message(
                    __('Maybe the files conf or md5 could not be deleted')
                );
            }
        }
    }
}

if ($enable_agent > 0) {
    $result = db_process_sql_update(
        'tagente',
        ['disabled' => 0],
        ['id_agente' => $enable_agent]
    );
    $alias = io_safe_output(agents_get_alias($enable_agent));

    if ((bool) $result !== false) {
        // Update the agent from the metaconsole cache.
        enterprise_include_once('include/functions_agents.php');
        $values = ['disabled' => 0];
        enterprise_hook(
            'agent_update_from_cache',
            [
                $enable_agent,
                $values,
                $server_name,
            ]
        );
        enterprise_hook(
            'config_agents_update_config_token',
            [
                $enable_agent,
                'standby',
                0,
            ]
        );
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            'Enable  '.$alias
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            'Fail to enable '.$alias
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully enabled'),
        __('Could not be enabled')
    );
}

if ($disable_agent > 0 && $agent_to_delete === 0) {
    $result = db_process_sql_update('tagente', ['disabled' => 1], ['id_agente' => $disable_agent]);
    $alias = io_safe_output(agents_get_alias($disable_agent));

    if ($result) {
        // Update the agent from the metaconsole cache.
        enterprise_include_once('include/functions_agents.php');
        $values = ['disabled' => 1];
        enterprise_hook(
            'agent_update_from_cache',
            [
                $disable_agent,
                $values,
                $server_name,
            ]
        );
        enterprise_hook(
            'config_agents_update_config_token',
            [
                $disable_agent,
                'standby',
                1,
            ]
        );

        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            'Disable  '.$alias
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            'Fail to disable '.$alias
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully disabled'),
        __('Could not be disabled')
    );
}

$own_info = get_user_info($config['id_user']);
if ((bool) $own_info['is_admin'] === false && (bool) check_acl(
    $config['id_user'],
    0,
    'AR'
) === false && (bool) check_acl($config['id_user'], 0, 'AW') === false
) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

$showAgentFields = [
    2 => __('Everyone'),
    1 => __('Only disabled'),
    0 => __('Only enabled'),
];

$pre_fields = db_get_all_rows_sql(
    'select distinct(tagente.id_os),tconfig_os.name from tagente,tconfig_os where tagente.id_os = tconfig_os.id_os'
);

$fields = [];

foreach ($pre_fields as $key => $value) {
        $fields[$value['id_os']] = $value['name'];
}

// Filter table.
$filterTable = new stdClass();
$filterTable->class = 'filter-table-adv w100p';
$filterTable->size[0] = '20%';
$filterTable->size[1] = '20%';
$filterTable->size[2] = '20%';
$filterTable->size[3] = '20%';
$filterTable->size[4] = '20%';
$filterTable->data = [];

$filterTable->data[0][0] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        false,
        'AR',
        $return_all_group,
        'ag_group',
        $ag_group,
        'this.form.submit();',
        '',
        0,
        true,
        false,
        true,
        '',
        false
    )
);

$filterTable->data[0][1] = html_print_label_input_block(
    __('Recursion'),
    '<div class="mrgn_top_10px">'.html_print_checkbox_switch(
        'recursion',
        1,
        $recursion,
        true,
        false,
        'this.form.submit()'
    ).'</div>'
);

$filterTable->data[0][2] = html_print_label_input_block(
    __('Show agents'),
    html_print_select(
        $showAgentFields,
        'disabled',
        $disabled,
        'this.form.submit()',
        '',
        0,
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%'
    )
);

$filterTable->data[0][3] = html_print_label_input_block(
    __('Operating System'),
    html_print_select(
        $fields,
        'os',
        $os,
        'this.form.submit()',
        'All',
        0,
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%'
    )
);

$filterTable->data[0][4] = html_print_label_input_block(
    __('Free search').ui_print_help_tip(
        __('Search filter by alias, name, description, IP address or custom fields content'),
        true
    ),
    html_print_input_text(
        'search',
        $search,
        '',
        12,
        255,
        true
    )
);

$filterTable->colspan[1][0] = 5;
$filterTable->data[1][0] = html_print_submit_button(
    __('Filter'),
    'srcbutton',
    false,
    [
        'icon'  => 'search',
        'class' => 'float-right mrgn_right_10px',
        'mode'  => 'mini',
    ],
    true
);

// Print filter table.
$form = '<form method=\'post\'	action=\'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente\'>';
ui_toggle(
    $form.html_print_table($filterTable, true).'</form>',
    '<span class="subsection_header_title">'.__('Filter').'</span>',
    __('Filter'),
    'filter',
    true,
    false,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph fixed_filter_bar'
);


require_once 'godmode/agentes/agent_deploy.php';

// Data table.
$selected = true;
$selectNameUp = false;
$selectNameDown = false;
$selectOsUp = false;
$selectOsDown = false;
$selectGroupUp = false;
$selectGroupDown = false;
$selectRemoteUp = false;
$selectRemoteDown = false;
switch ($sortField) {
    case 'remote':
        switch ($sort) {
            case 'up':
                $selectRemoteUp = $selected;
                $order = [
                    'field'  => 'remote ',
                    'field2' => 'nombre ',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectRemoteDown = $selected;
                $order = [
                    'field'  => 'remote ',
                    'field2' => 'nombre ',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'name':
        switch ($sort) {
            case 'up':
                $selectNameUp = $selected;
                $order = [
                    'field'  => 'alias ',
                    'field2' => 'alias ',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectNameDown = $selected;
                $order = [
                    'field'  => 'alias ',
                    'field2' => 'alias ',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'os':
        switch ($sort) {
            case 'up':
                $selectOsUp = $selected;
                $order = [
                    'field'  => 'id_os',
                    'field2' => 'alias ',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectOsDown = $selected;
                $order = [
                    'field'  => 'id_os',
                    'field2' => 'alias ',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'group':
        switch ($sort) {
            case 'up':
                $selectGroupUp = $selected;
                $order = [
                    'field'  => 'id_grupo',
                    'field2' => 'alias ',
                    'order'  => 'ASC',
                ];
            break;

            case 'down':
                $selectGroupDown = $selected;
                $order = [
                    'field'  => 'id_grupo',
                    'field2' => 'alias ',
                    'order'  => 'DESC',
                ];
            break;

            default:
                // Default.
            break;
        }
    break;

    default:
        $selectNameUp = $selected;
        $selectNameDown = '';
        $selectOsUp = '';
        $selectOsDown = '';
        $selectGroupUp = '';
        $selectGroupDown = '';
        $order = [
            'field'  => 'alias ',
            'field2' => 'alias ',
            'order'  => 'ASC',
        ];
    break;
}

$search_sql = '';
if ($search != '') {
    $sql = sprintf(
        'SELECT DISTINCT taddress_agent.id_agent FROM taddress
	     INNER JOIN taddress_agent ON
	     taddress.id_a = taddress_agent.id_a
	     WHERE taddress.ip LIKE "%%%s%%"',
        $search
    );

    $id = db_get_all_rows_sql($sql);
    if ($id != '') {
        $aux = $id[0]['id_agent'];
        $search_sql = sprintf(
            ' AND ( nombre LIKE "%%%s%%"
             OR alias LIKE "%%%s%%"
             OR comentarios LIKE "%%%s%%"
			 OR EXISTS (SELECT * FROM tagent_custom_data WHERE id_agent = id_agente AND description LIKE "%%%s%%")
             OR tagente.id_agente = %d',
            $search,
            $search,
            $search,
            $search,
            $aux
        );
        $nagent_count = count($id);
        if ($nagent_count >= 2) {
            for ($i = 1; $i < $nagent_count; $i++) {
                $aux = $id[$i]['id_agent'];
                $search_sql .= sprintf(
                    ' OR tagente.id_agente = %d',
                    $aux
                );
            }
        }

        $search_sql .= ')';
    } else {
        $search_sql = sprintf(
            ' AND ( nombre
			 LIKE "%%%s%%" OR alias
			 LIKE "%%%s%%" OR comentarios LIKE "%%%s%%"
			 OR EXISTS (SELECT * FROM tagent_custom_data WHERE id_agent = id_agente AND description LIKE "%%%s%%"))',
            $search,
            $search,
            $search,
            $search
        );
    }
}

if ($disabled == 1) {
    $search_sql .= ' AND disabled = '.$disabled.$search_sql;
} else {
    if ($disabled == 0) {
        $search_sql .= ' AND disabled = 0'.$search_sql;
    }
}

if ($os !== 0) {
    $search_sql .= ' AND id_os = '.$os;
}

$user_groups_to_sql = '';
// Show only selected groups.
if ($ag_group > 0) {
    $ag_groups = [];
    $ag_groups = (array) $ag_group;
    if ($recursion === true) {
        $ag_groups = groups_get_children_ids($ag_group, true);
    }

    $user_groups_to_sql = implode(',', $ag_groups);
} else {
    // Concatenate AW and AD permisions to get all the possible groups where the user can manage.
    $user_groupsAW = users_get_groups($config['id_user'], 'AW');
    $user_groupsAD = users_get_groups($config['id_user'], 'AD');

    $user_groups = ($user_groupsAW + $user_groupsAD);
    $user_groups_to_sql = implode(',', array_keys($user_groups));
}

$sql = sprintf(
    'SELECT COUNT(DISTINCT(tagente.id_agente))
	FROM tagente LEFT JOIN tagent_secondary_group tasg
		ON tagente.id_agente = tasg.id_agent
	WHERE (tagente.id_grupo IN (%s) OR tasg.id_group IN (%s))
		%s',
    $user_groups_to_sql,
    $user_groups_to_sql,
    $search_sql
);

$total_agents = db_get_sql($sql);

$sql = sprintf(
    'SELECT *
	FROM tagente LEFT JOIN tagent_secondary_group tasg
		ON tagente.id_agente = tasg.id_agent
	WHERE (tagente.id_grupo IN (%s) OR tasg.id_group IN (%s))
		%s
	GROUP BY tagente.id_agente
	ORDER BY %s %s, %s %s
	LIMIT %d, %d',
    $user_groups_to_sql,
    $user_groups_to_sql,
    $search_sql,
    $order['field'],
    $order['order'],
    $order['field2'],
    $order['order'],
    $offset,
    $config['block_size']
);

$agents = db_get_all_rows_sql($sql);
$custom_font_size = '';
// Prepare pagination.
// ui_pagination($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&recursion=$recursion&search=$search&sort_field=$sortField&sort=$sort&disabled=$disabled&os=$os", $offset);
if ($agents !== false) {
    // Urls to sort the table.
    if ($config['language'] === 'ja'
        || $config['language'] === 'zh_CN'
        || $own_info['language'] === 'ja'
        || $own_info['language'] === 'zh_CN'
    ) {
        // Adds a custom font size for Japanese and Chinese language.
        $custom_font_size = 'custom_font_size';
    }

    $url_up_agente = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=name&sort=up&disabled=$disabled';
    $url_down_agente = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=name&sort=down&disabled=$disabled';
    $url_up_remote = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=remote&sort=up&disabled=$disabled';
    $url_down_remote = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=remote&sort=down&disabled=$disabled';
    $url_up_os = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=os&sort=up&disabled=$disabled';
    $url_down_os = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=os&sort=down&disabled=$disabled';
    $url_up_group = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=group&sort=up&disabled=$disabled';
    $url_down_group = 'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&recursion='.$recursion.'&search='.$search.'&os='.$os.'&offset='.$offset.'&sort_field=group&sort=down&disabled=$disabled';

    $tableAgents = new stdClass();
    $tableAgents->id = 'agent_list';
    $tableAgents->class = 'info_table tactical_table';
    $tableAgents->head = [];
    $tableAgents->data = [];
    // Header.
    $tableAgents->head[0] = '<span>'.__('Agent name').'</span>';
    $tableAgents->head[0] .= ui_get_sorting_arrows($url_up_agente, $url_down_agente, $selectNameUp, $selectNameDown);
    $tableAgents->head[1] = '<span title=\''.__('Remote agent configuration').'\'>'.__('R').'</span>';
    $tableAgents->head[1] .= ui_get_sorting_arrows($url_up_remote, $url_down_remote, $selectRemoteUp, $selectRemoteDown);
    $tableAgents->head[2] = '<span>'.__('OS').'</span>';
    $tableAgents->head[2] .= ui_get_sorting_arrows($url_up_os, $url_down_os, $selectOsUp, $selectOsDown);
    $tableAgents->head[3] = '<span>'.__('Type').'</span>';
    $tableAgents->head[4] = '<span>'.__('Group').'</span>';
    $tableAgents->head[4] .= ui_get_sorting_arrows($url_up_group, $url_down_group, $selectGroupUp, $selectGroupDown);
    $tableAgents->head[5] = '<span>'.__('Description').'</span>';
    $tableAgents->head[6] = '<span>'.__('Actions').'</span>';
    // Body.
    foreach ($agents as $key => $agent) {
        // Begin Update tagente.remote with 0/1 values.
        $resultHasRemoteConfig = ((int) enterprise_hook('config_agents_has_remote_configuration', [$agent['id_agente']]) > 0);
        db_process_sql_update(
            'tagente',
            ['remote' => ((int) $resultHasRemoteConfig) ],
            'id_agente = '.$agent['id_agente'].''
        );

        $all_groups = agents_get_all_groups_agent(
            $agent['id_agente'],
            $agent['id_grupo']
        );
        $check_aw = check_acl_one_of_groups(
            $config['id_user'],
            $all_groups,
            'AW'
        );
        $check_ad = check_acl_one_of_groups(
            $config['id_user'],
            $all_groups,
            'AD'
        );

        $cluster = db_get_row_sql(
            'select id from tcluster where id_agent = '.$agent['id_agente']
        );

        if ($check_aw === false && $check_ad === false) {
            continue;
        }

        if ((int) $agent['id_os'] === CLUSTER_OS_ID) {
            $cluster = PandoraFMS\Cluster::loadFromAgentId($agent['id_agente']);
            $agentNameUrl = sprintf(
                'index.php?sec=reporting&sec2=operation/cluster/cluster&op=update&id=%s',
                $cluster->id()
            );
            $agentViewUrl = sprintf(
                'index.php?sec=reporting&sec2=operation/cluster/cluster&op=view&id=%s',
                $cluster->id()
            );
        } else {
            $main_tab = ($check_aw === true) ? 'main' : 'module';
            $agentNameUrl = sprintf(
                'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=%s&id_agente=%s',
                $main_tab,
                $agent['id_agente']
            );
            $agentViewUrl = sprintf(
                'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=%s',
                $agent['id_agente']
            );
        }

        if (empty($agent['alias']) === true) {
            $agent['alias'] = io_safe_output($agent['nombre']);
        } else {
            $agent['alias'] = io_safe_output($agent['alias']);
        }

        $additionalDataAgentName = [];

        $inPlannedDowntime = db_get_sql(
            'SELECT executed FROM tplanned_downtime 
			INNER JOIN tplanned_downtime_agents ON tplanned_downtime.id = tplanned_downtime_agents.id_downtime
			WHERE tplanned_downtime_agents.id_agent = '.$agent['id_agente'].' AND tplanned_downtime.executed = 1 
            AND tplanned_downtime.type_downtime <> "disable_agent_modules"'
        );

        if ($inPlannedDowntime !== false) {
            $additionalDataAgentName[] = ui_print_help_tip(
                __('Module in scheduled downtime'),
                true,
                'images/clock.svg'
            );
        }

        if ((bool) $agent['disabled'] === true) {
            $additionalDataAgentName[] = ui_print_help_tip(__('Disabled'), true);
        }

        if ((bool) $agent['quiet'] === true) {
            $additionalDataAgentName[] = html_print_image(
                'images/dot_blue.png',
                true,
                [
                    'border' => '0',
                    'title'  => __('Quiet'),
                    'alt'    => '',
                ]
            );
        }

        // Agent name column (1). Agent name.
        $agentNameColumn = html_print_anchor(
            [
                'href'    => ui_get_full_url($agentViewUrl),
                'title'   => $agent['nombre'],
                'content' => ui_print_truncate_text($agent['alias'], 'agent_medium').implode('', $additionalDataAgentName),
            ],
            true
        );

        $additionalOptionsAgentName = [];
        // Additional options generation.
        if ($check_aw === true) {
            $additionalOptionsAgentName[] = html_print_anchor(
                [
                    'href'    => ui_get_full_url($agentNameUrl),
                    'content' => __('Edit'),
                ],
                true
            );
        }

        if ((int) $agent['id_os'] !== 100) {
            $additionalOptionsAgentName[] = html_print_anchor(
                [
                    'href'    => ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$agent['id_agente']),
                    'content' => __('Modules'),
                ],
                true
            );
        }

        $additionalOptionsAgentName[] = html_print_anchor(
            [
                'href'    => ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$agent['id_agente']),
                'content' => __('Alerts'),
            ],
            true
        );

        $additionalOptionsAgentName[] = html_print_anchor(
            [
                'href'    => ui_get_full_url($agentViewUrl),
                'content' => __('View'),
            ],
            true
        );

        // Agent name column (2). Available options.
        $agentAvailableActionsColumn = html_print_div(
            [
                'class'   => 'left actions clear_left w100p',
                'style'   => 'visibility: hidden',
                'content' => implode(' | ', $additionalOptionsAgentName),
            ],
            true
        );

        // Remote Configuration column.
        if ($resultHasRemoteConfig === true) {
            $remoteConfigurationColumn = html_print_menu_button(
                [
                    'href'  => ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=remote_configuration&id_agente='.$agent['id_agente'].'&disk_conf=1'),
                    'image' => 'images/remote-configuration@svg.svg',
                    'title' => __('Edit remote config'),
                ],
                true
            );
        } else {
            $remoteConfigurationColumn = '';
        }

        // Operating System icon column.
        $osIconColumn = html_print_div(
            [
                'content' => ui_print_os_icon($agent['id_os'], false, true),
            ],
            true
        );

        // Agent type column.
        $agentTypeIconColumn = ui_print_type_agent_icon(
            $agent['id_os'],
            $agent['ultimo_contacto_remoto'],
            $agent['ultimo_contacto'],
            true,
            $agent['remote'],
            $agent['agent_version']
        );

        // Group icon and name column.
        $agentGroupIconColumn = html_print_div(
            [
                'content' => ui_print_group_icon($agent['id_grupo'], true),
            ],
            true
        );

        // Description column.
        $descriptionColumn = ui_print_truncate_text(
            $agent['comentarios'],
            'description',
            true,
            true,
            true,
            '[&hellip;]'
        );

        $agentActionButtons = [];

        if ((bool) $agent['disabled'] === true) {
            $agentDisableEnableTitle = __('Enable agent');
            $agentDisableEnableAction = 'enable';
            $agentDisableEnableCaption = __('You are going to enable a cluster agent. Are you sure?');
            $agentDisableEnableIcon = 'change-active.svg';
        } else {
            $agentDisableEnableTitle = __('Disable agent');
            $agentDisableEnableAction = 'disable';
            $agentDisableEnableCaption = __('You are going to disable a cluster agent. Are you sure?');
            $agentDisableEnableIcon = 'change-pause.svg';
        }

        $agentActionButtons[] = html_print_menu_button(
            [
                'href'    => ui_get_full_url(
                    sprintf(
                        'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&%s_agent=%s&group_id=%s&recursion=%s&search=%s&offset=%s&sort_field=%s&sort=%s&disabled=%s',
                        $agentDisableEnableAction,
                        $agent['id_agente'],
                        $ag_group,
                        $recursion,
                        $search,
                        '',
                        $sortField,
                        $sort,
                        $disabled
                    )
                ),
                'onClick' => ($agent['id_os'] === CLUSTER_OS_ID) ? sprintf('if (!confirm(\'%s\')) return false', $agentDisableEnableCaption) : 'return true;',
                'image'   => sprintf('images/%s', $agentDisableEnableIcon),
                'title'   => $agentDisableEnableTitle,
            ],
            true
        );

        if ($check_aw === true && is_management_allowed() === true) {
            if ($agent['id_os'] !== CLUSTER_OS_ID) {
                $onClickActionDeleteAgent = 'if (!confirm(\' '.__('Are you sure?').'\')) return false;';
            } else {
                $onClickActionDeleteAgent = 'if (!confirm(\' '.__('WARNING! - You are going to delete a cluster agent. Are you sure?').'\')) return false;';
            }

            $agentActionButtons[] = html_print_menu_button(
                [
                    'href'    => ui_get_full_url(
                        sprintf(
                            'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&borrar_agente=%s&%s_agent=%s&group_id=%s&recursion=%s&search=%s&offset=%s&sort_field=%s&sort=%s&disabled=%s',
                            $agent['id_agente'],
                            $agentDisableEnableAction,
                            $agent['id_agente'],
                            $ag_group,
                            $recursion,
                            $search,
                            '',
                            $sortField,
                            $sort,
                            $disabled
                        )
                    ),
                    'onClick' => $onClickActionDeleteAgent,
                    'image'   => sprintf('images/delete.svg'),
                    'title'   => __('Delete agent'),
                ],
                true
            );
        }

        // Action buttons column.
        $actionButtonsColumn = implode('', $agentActionButtons);
        // Defined class for action buttons.
        $tableAgents->cellclass[$key][6] = 'table_action_buttons';
        // Row data.
        $tableAgents->data[$key][0] = $agentNameColumn;
        $tableAgents->data[$key][0] .= $agentAvailableActionsColumn;
        $tableAgents->data[$key][1] = $remoteConfigurationColumn;
        $tableAgents->data[$key][2] = $osIconColumn;
        $tableAgents->data[$key][3] = $agentTypeIconColumn;
        $tableAgents->data[$key][4] = $agentGroupIconColumn;
        $tableAgents->data[$key][5] = $descriptionColumn;
        $tableAgents->data[$key][6] = $actionButtonsColumn;
    }

    html_print_table($tableAgents);

    $tablePagination = ui_pagination(
        $total_agents,
        ui_get_url_refresh(
            [
                'group_id'   => $group_id,
                'search'     => $search,
                'sort_field' => $sortField,
                'sort'       => $sort,
                'status'     => $status,
            ]
        ),
        0,
        0,
        true,
        'offset',
        false,
        'paging_simple_numbers'
    );

    /*
        ui_pagination(
        $total_agents,
        "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&recursion=$recursion&search=$search&sort_field=$sortField&sort=$sort&disabled=$disabled&os=$os",
        $offset
        );
    */
} else {
    $tablePagination = '';
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined agents') ]);
}

if ((bool) check_acl($config['id_user'], 0, 'AW') === true) {
    // Create agent button.
    echo '<form id="create-agent" method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente"></form>';

    $buttons = html_print_button(
        __('Create agent'),
        'crt-2',
        false,
        '',
        [
            'icon'    => 'next',
            'onClick' => "document.getElementById('create-agent').submit();",
        ],
        true
    ).html_print_button(
        __('Deploy agent'),
        'modal_deploy_agent',
        false,
        '',
        [],
        true
    );

    html_print_action_buttons(
        $buttons,
        [
            'type'          => 'data_table',
            'class'         => 'fixed_action_buttons',
            'right_content' => $tablePagination,
        ]
    );
}

?>

<script type="text/javascript">
    $(document).ready (function () {
        $("table#agent_list tr").hover (function () {
                $(".actions", this).css ("visibility", "");
            },
            function () {
                $(".actions", this).css ("visibility", "hidden");
        });
        
        $("#ag_group").click (
            function () {
                $(this).css ("width", "auto");
                $(this).css ("min-width", "100px");
            });
            
        $("#ag_group").blur (function () {
            $(this).css ("width", "100px");
        });
        
    });
</script>
