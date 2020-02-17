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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

enterprise_hook('open_meta_frame');

require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('meta/include/functions_agents_meta.php');

if (is_ajax()) {
    if (! check_acl($config['id_user'], 0, 'AR')) {
        db_pandora_audit('ACL Violation', 'Trying to access Group Management');
        include 'general/noaccess.php';
        return;
    }

    $get_group_json = (bool) get_parameter('get_group_json');
    $get_group_agents = (bool) get_parameter('get_group_agents');
    $get_is_disabled = (bool) get_parameter('get_is_disabled');

    if ($get_group_json) {
        $id_group = (int) get_parameter('id_group');

        if ($id_group == 0) {
            $group = [
                'id_grupo'  => 0,
                'nombre'    => 'All',
                'icon'      => 'world',
                'parent'    => 0,
                'disabled'  => 0,
                'custom_id' => null,
            ];
            echo json_encode($group);
            return;
        }

        if (! check_acl($config['id_user'], $id_group, 'AR')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Alert Management'
            );
            echo json_encode(false);
            return;
        }

        $group = db_get_row('tgrupo', 'id_grupo', $id_group);

        echo json_encode($group);
        return;
    }

    if ($get_group_agents) {
        ob_clean();
        $id_group = (int) get_parameter('id_group');
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
        $serialized_separator = (string) get_parameter('serialized_separator', '|');
        $force_serialized = (bool) get_parameter('force_serialized', false);

        if (! check_acl($config['id_user'], $id_group, 'AR')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Alert Management'
            );
            echo json_encode(false);
            return;
        }

        if (https_is_running()) {
            header('Content-type: application/json');
        }

        if ($filter_agents_json != '') {
            $filter['id_agente'] = json_decode(io_safe_output($filter_agents_json), true);
        }

        if ($all_agents) {
            $filter['all_agents'] = true;
        } else {
            $filter['disabled'] = $disabled;
        }

        if ($search != '') {
            $filter['string'] = $search;
        }

        if ($status_agents != AGENT_STATUS_ALL) {
            $filter['status'] = $status_agents;
        }

        // Juanma (22/05/2014) Fix: If remove void agents set.
        $_sql_post = ' 1=1 ';
        if ($show_void_agents == 0) {
            $_sql_post .= ' AND id_agente IN (SELECT a.id_agente FROM tagente a, tagente_modulo b WHERE a.id_agente=b.id_agente AND b.delete_pending=0) AND \'1\'';
            $filter[$_sql_post] = '1';
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
                    } else if ($serialized && !is_metaconsole() && $force_serialized) {
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

    if ($get_is_disabled) {
        $index = get_parameter('id_agent');

        $agent_disabled = db_get_value_filter('disabled', 'tagente', ['id_agente' => $index]);

        $return['disabled'] = $agent_disabled;
        $return['id_agent'] = $index;

        echo json_encode($return);
        return;
    }

    return;
}

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Group Management'
    );
    include 'general/noaccess.php';
    return;
}

$sec = defined('METACONSOLE') ? 'advanced' : 'gagente';
$url_credbox  = 'index.php?sec='.$sec.'&sec2=godmode/groups/group_list&tab=credbox';
$url_tree  = 'index.php?sec='.$sec.'&sec2=godmode/groups/group_list&tab=tree';
$url_groups = 'index.php?sec='.$sec.'&sec2=godmode/groups/group_list&tab=groups';

$buttons['tree'] = [
    'active' => false,
    'text'   => '<a href="'.$url_tree.'">'.html_print_image(
        'images/gm_massive_operations.png',
        true,
        [
            'title' => __('Tree Group view'),
        ]
    ).'</a>',
];

$buttons['groups'] = [
    'active' => false,
    'text'   => '<a href="'.$url_groups.'">'.html_print_image(
        'images/group.png',
        true,
        [
            'title' => __('Group view'),
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
        ]
    ).'</a>',
];

$tab = (string) get_parameter('tab', 'groups');

$title = __('Groups defined in %s', get_product_name());
// Marks correct tab.
switch ($tab) {
    case 'tree':
        $buttons['tree']['active'] = true;
    break;

    case 'credbox':
        $buttons['credbox']['active'] = true;
        $title = __('Credential store');
    break;

    case 'groups':
    default:
        $buttons['groups']['active'] = true;
    break;
}

// Header.
if (defined('METACONSOLE')) {
    agents_meta_print_header();
    echo '<div class="notify">';
    echo __('Edit or delete groups can cause problems with synchronization');
    echo '</div>';
} else {
    ui_print_page_header(
        $title,
        'images/group.png',
        false,
        'group_list_tab',
        true,
        $buttons
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
if (($create_group) && (check_acl($config['id_user'], 0, 'PM'))) {
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
    $check = db_get_value('nombre', 'tgrupo', 'nombre', $name);
    $propagate = (bool) get_parameter('propagate');

    // Check if name field is empty.
    if ($name != '') {
        if (!$check) {
            $values = [
                'nombre'      => $name,
                'icon'        => empty($icon) ? '' : substr($icon, 0, -4),
                'parent'      => $id_parent,
                'disabled'    => $alerts_disabled,
                'custom_id'   => $custom_id,
                'id_skin'     => $skin,
                'description' => $description,
                'contact'     => $contact,
                'propagate'   => $propagate,
                'other'       => $other,
                'password'    => io_safe_input($group_pass),
            ];

            $result = db_process_sql_insert('tgrupo', $values);
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
if ($update_group) {
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

    // Check if name field is empty.
    if ($name != '') {
        $sql = sprintf(
            'UPDATE tgrupo
             SET nombre = "%s",
                icon = "%s",
                disabled = %d,
                parent = %d,
                custom_id = "%s",
                propagate = %d,
                id_skin = %d,
                description = "%s",
                contact = "%s",
                other = "%s",
                password = "%s"
            WHERE id_grupo = %d',
            $name,
            empty($icon) ? '' : substr($icon, 0, -4),
            !$alerts_enabled,
            $id_parent,
            $custom_id,
            $propagate,
            $skin,
            $description,
            $contact,
            $other,
            $group_pass,
            $id_group
        );

        $result = db_process_sql($sql);
    } else {
        $result = false;
    }

    if ($result !== false) {
        ui_print_success_message(__('Group successfully updated'));
    } else {
        ui_print_error_message(__('There was a problem modifying group'));
    }
}

// Delete group.
if (($delete_group) && (check_acl($config['id_user'], 0, 'PM'))) {
    $id_group = (int) get_parameter('id_group');

    $usedGroup = groups_check_used($id_group);

    if (!$usedGroup['return']) {
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
    } else {
        ui_print_error_message(
            sprintf(__('The group is not empty. It is use in %s.'), implode(', ', $usedGroup['tables']))
        );
    }

    if ($result && (!$usedGroup['return'])) {
        ui_print_success_message(__('Group successfully deleted'));
    } else {
        ui_print_error_message(__('There was a problem deleting group'));
    }
}


// Credential store is loaded previously in this document to avoid
// process group tree - list forms.
if ($tab == 'tree') {
    /*
     * Group tree view.
     */

    echo html_print_image(
        'images/spinner.gif',
        true,
        [
            'class' => 'loading_tree',
            'style' => 'display: none;',
        ]
    );
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

    if (!empty($search)) {
        $search_name = 'AND t.nombre LIKE "%'.$search.'%"';
    }

    if (!users_can_manage_group_all('AR')) {
        $user_groups_acl = users_get_groups(false, 'AR');
        $groups_acl = implode(',', $user_groups_ACL);
        if (empty($groups_acl)) {
            return ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __('There are no defined groups'),
                ]
            );
        }

        $acl = 'AND t.id_grupo IN ('.$groups_acl.')';
    }

    $form = "<form method='post' action=''>";
        $form .= "<table class='databox filters' width='100%' style='font-weight: bold;'>";
            $form .= '<tr><td>'.__('Search').'&nbsp;';
                $form .= html_print_input_text('search', $search, '', 100, 100, true);
            $form .= '</td><td>';
                $form .= "<input name='find' type='submit' class='sub search' value='".__('Search')."'>";
            $form .= '<td></tr>';
        $form .= '</table>';
    $form .= '</form>';

    echo $form;

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

    if (!empty($groups)) {
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
        $table->head[6] = __('Actions');
        $table->headstyle[6] = 'min-width: 100px;';
        $table->align = [];
        $table->align[0] = 'left';
        $table->align[2] = 'left';
        $table->align[6] = 'left';
        $table->size[0] = '3%';
        $table->size[5] = '30%';
        $table->size[6] = '5%';
        $table->data = [];

        foreach ($groups as $key => $group) {
            $url = 'index.php?sec=gagente&sec2=godmode/groups/configure_group&id_group='.$group['id_grupo'];
            $url_delete = 'index.php?sec=gagente&sec2=godmode/groups/group_list&delete_group=1&id_group='.$group['id_grupo'];
            $table->data[$key][0] = $group['id_grupo'];
            $table->data[$key][1] = '<a href="'.$url.'">'.$group['nombre'].'</a>';
            if ($group['icon'] != '') {
                $table->data[$key][2] = html_print_image(
                    'images/groups_small/'.$group['icon'].'.png',
                    true,
                    [
                        'style' => '',
                        'class' => 'bot',
                        'alt'   => $group['nombre'],
                        'title' => $group['nombre'],
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
            $table->cellclass[$key][6] = 'action_buttons';
            $table->data[$key][6] = '<a href="'.$url.'">'.html_print_image(
                'images/config.png',
                true,
                [
                    'alt'    => __('Edit'),
                    'title'  => __('Edit'),
                    'border' => '0',
                ]
            ).'</a>';

            $confirm_message = __('Are you sure?');
            if ($group['has_child']) {
                $confirm_message = __('The child groups will be updated to use the parent id of the deleted group').'. '.$confirm_message;
            }

            $table->data[$key][6] .= '<a href="'.$url_delete.'" onClick="if (!confirm(\' '.$confirm_message.'\')) return false;">'.html_print_image(
                'images/cross.png',
                true,
                [
                    'alt'    => __('Delete'),
                    'title'  => __('Delete'),
                    'border' => '0',
                ]
            ).'</a>';
        }

        echo ui_pagination(
            $groups_count,
            false,
            $offset,
            $block_size,
            true,
            'offset',
            true
        );
        html_print_table($table);
        echo ui_pagination(
            $groups_count,
            false,
            $offset,
            $block_size,
            true,
            'offset',
            true,
            'pagination-bottom'
        );
    } else {
        ui_print_info_message(['no_close' => true, 'message' => __('There are no defined groups') ]);
    }
}

if (check_acl($config['id_user'], 0, 'PM')) {
    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/groups/configure_group">';
        echo '<div class="action-buttons" style="width:100%;">';
            html_print_submit_button(__('Create group'), 'crt', false, 'class="sub next"');
        echo '</div>';
    echo '</form>';
}

ui_require_javascript_file('TreeController', 'include/javascript/tree/');

enterprise_hook('close_meta_frame');
$tab = 'group_edition';

?>

<?php if (!is_metaconsole()) { ?>
    <script type="text/javascript" src="include/javascript/fixed-bottom-box.js"></script>
<?php } else { ?>
    <script type="text/javascript" src="../../include/javascript/fixed-bottom-box.js"></script>
<?php } ?>

<script type="text/javascript">
    var treeController = TreeController.getController();

    if (typeof treeController.recipient != 'undefined' && treeController.recipient.length > 0)
            treeController.recipient.empty();

        $(".loading_tree").show();

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
                    $(".loading_tree").hide();

                    treeController.init({
                        recipient: $("div#tree-controller-recipient"),
                        //detailRecipient: $.fixedBottomBox({ width: 400, height: window.innerHeight * 0.9 }),
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
