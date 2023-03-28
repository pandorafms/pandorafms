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
global $config;

// Login check
check_login();

if (! check_acl($config['id_user'], 0, 'LW')
    && ! check_acl($config['id_user'], 0, 'AD')
    && ! check_acl($config['id_user'], 0, 'LM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
$isFunctionPolicies = enterprise_include_once('include/functions_policies.php');
enterprise_include_once('meta/include/functions_alerts_meta.php');

$id_group = 0;
// Check if this page is included from a agent edition
if (isset($id_agente)) {
    $id_group = agents_get_agent_group($id_agente);
} else {
    $id_agente = 0;
}

$create_alert = (bool) get_parameter('create_alert');
$add_action = (bool) get_parameter('add_action');
$update_action = (bool) get_parameter('update_action');
$delete_action = (bool) get_parameter('delete_action');
$delete_alert = (bool) get_parameter('delete_alert');
$update_alert = (bool) get_parameter('update_alert');
$disable_alert = (bool) get_parameter('disable_alert');
$enable_alert = (bool) get_parameter('enable_alert');
$standbyon_alert = (bool) get_parameter('standbyon_alert');
$standbyoff_alert = (bool) get_parameter('standbyoff_alert');
$tab = get_parameter('tab', 'list');
$group = get_parameter('group', 0);
// 0 is All group
$templateName = get_parameter('template_name', '');
$moduleName = get_parameter('module_name', '');
$agentID = get_parameter('agent_id', '');
$agentName = get_parameter('agent_name', '');
$actionID = get_parameter('action_id', '');
$fieldContent = get_parameter('field_content', '');
$searchType = get_parameter('search_type', '');
$priority = get_parameter('priority', '');
$searchFlag = get_parameter('search', 0);
$enabledisable = get_parameter('enabledisable', '');
$status_alert = get_parameter('status_alert', '');
$standby = get_parameter('standby', '');
$pure = get_parameter('pure', 0);
$ag_group = get_parameter('ag_group', 0);
$messageAction = '';

if ($update_alert) {
    $id_alert_agent_module = (int) get_parameter('id_alert_update');

    $id_alert_template = (int) get_parameter('template');
    $id_agent_module = (int) get_parameter('id_agent_module');

    $values_upd = [];

    if (!empty($id_alert_template)) {
        $values_upd['id_agent_module'] = $id_agent_module;
    }

    if (!empty($id_alert_template)) {
        $values_upd['id_alert_template'] = $id_alert_template;
    }

    $id = alerts_update_alert_agent_module($id_alert_agent_module, $values_upd);

    $messageAction = ui_print_result_message(
        $id,
        __('Successfully updated'),
        __('Could not be updated'),
        '',
        true
    );
}

if ($create_alert) {
    $id_alert_template = (int) get_parameter('template');
    $id_agent_module = (int) get_parameter('id_agent_module');

    $exist = db_get_value_sql(
        sprintf(
            'SELECT COUNT(id)
            FROM talert_template_modules
            WHERE id_agent_module = %d
                AND id_alert_template = %d
                AND id_policy_alerts = 0
            ',
            $id_agent_module,
            $id_alert_template
        )
    );

    if ($exist > 0) {
        $messageAction = ui_print_result_message(
            false,
            '',
            __('Already added'),
            '',
            true
        );
    } else {
        $id = alerts_create_alert_agent_module($id_agent_module, $id_alert_template);

        $alert_template_name = db_get_value(
            'name',
            'talert_templates',
            'id',
            $id_alert_template
        );
        $module_name = db_get_value(
            'nombre',
            'tagente_modulo',
            'id_agente_modulo',
            $id_agent_module
        );
        $agent_alias = agents_get_alias(
            db_get_value(
                'id_agente',
                'tagente_modulo',
                'id_agente_modulo',
                $id_agent_module
            )
        );

        // Audit the creation only when the alert creation is correct
        $unsafe_alert_template_name = io_safe_output($alert_template_name);
        $unsafe_module_name = io_safe_output($module_name);
        $unsafe_agent_alias = io_safe_output($agent_alias);
        if ($id) {
            db_pandora_audit(
                AUDIT_LOG_ALERT_MANAGEMENT,
                "Added alert '$unsafe_alert_template_name' for module '$unsafe_module_name' in agent '$unsafe_agent_alias'",
                false,
                false,
                'ID: '.$id
            );
        } else {
            db_pandora_audit(
                AUDIT_LOG_ALERT_MANAGEMENT,
                "Fail Added alert '$unsafe_alert_template_name' for module '$unsafe_module_name' in agent '$unsafe_agent_alias'"
            );
        }


        // Show errors
        if (!isset($messageAction)) {
            $messageAction = __('Could not be created');
        }

        if ($id_alert_template == '') {
            $messageAction = __('No template specified');
        }

        if ($id_agent_module == '') {
            $messageAction = __('No module specified');
        }

        $messageAction = ui_print_result_message(
            $id,
            __('Successfully created'),
            $messageAction,
            '',
            true
        );


        if ($id !== false) {
            $action_select = get_parameter('action_select');

            if ($action_select != 0) {
                $values = [];
                $values['fires_min'] = (int) get_parameter('fires_min');
                $values['fires_max'] = (int) get_parameter('fires_max');
                $values['module_action_threshold'] = (int) get_parameter('module_action_threshold');


                alerts_add_alert_agent_module_action($id, $action_select, $values);
            }
        }
    }
}

if ($delete_alert) {
    $id_alert_agent_module = (int) get_parameter('id_alert');

    $temp = db_get_row('talert_template_modules', 'id', $id_alert_agent_module);
    $id_alert_template = $temp['id_alert_template'];
    $id_agent_module = $temp['id_agent_module'];
    $alert_template_name = db_get_value('name', 'talert_templates', 'id', $id_alert_template);
    $module_name = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
    $agent_alias = agents_get_alias(
        db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module)
    );
    $unsafe_alert_template_name = io_safe_output($alert_template_name);
    $unsafe_module_name = io_safe_output($module_name);
    $unsafe_agent_alias = io_safe_output($agent_alias);

    $result = alerts_delete_alert_agent_module($id_alert_agent_module);

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            "Deleted alert '$unsafe_alert_template_name' for module '$unsafe_module_name' in agent '$unsafe_agent_alias'"
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            "Fail to deleted alert '$unsafe_alert_template_name' for module '$unsafe_module_name' in agent '$unsafe_agent_alias'"
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted'),
        '',
        true
    );
}

if ($add_action) {
    $id_action = (int) get_parameter('action_select');
    $id_alert_module = (int) get_parameter('id_alert_module');
    $fires_min = (int) get_parameter('fires_min');
    $fires_max = (int) get_parameter('fires_max');
    $values = [];
    if ($fires_min != -1) {
        $values['fires_min'] = $fires_min;
    }

    if ($fires_max != -1) {
        $values['fires_max'] = $fires_max;
    }

    $values['module_action_threshold'] = (int) get_parameter('module_action_threshold');

    $result = alerts_add_alert_agent_module_action($id_alert_module, $id_action, $values);

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Add action '.$id_action.' in  alert '.$id_alert_module
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail to add action '.$id_action.' in alert '.$id_alert_module
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully added'),
        __('Could not be added'),
        '',
        true
    );
}

if ($update_action) {
    $alert_id = (int) get_parameter('alert_id');
    $id_action = (int) get_parameter('action_select_ajax-'.$alert_id);
    $id_module_action = (int) get_parameter('id_module_action_ajax');
    $fires_min = (int) get_parameter('fires_min_ajax');
    $fires_max = (int) get_parameter('fires_max_ajax');

    $values = [];
    if ($fires_min != -1) {
        $values['fires_min'] = $fires_min;
    }

    if ($fires_max != -1) {
        $values['fires_max'] = $fires_max;
    }

    $values['module_action_threshold'] = (int) get_parameter('module_action_threshold_ajax');
    $values['id_alert_action'] = $id_action;

    $result = alerts_update_alert_agent_module_action($id_module_action, $values);
    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Update action '.$id_action.' in  alert '.$id_alert_module
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail to updated action '.$id_action.' in alert '.$id_alert_module
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully updated'),
        __('Could not be updated'),
        '',
        true
    );
}

if ($delete_action) {
    $id_action = (int) get_parameter('id_action');
    $id_alert = (int) get_parameter('id_alert');

    $result = alerts_delete_alert_agent_module_action($id_action);

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Delete action '.$id_action.' in alert '.$id_alert
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail to delete action '.$id_action.' in alert '.$id_alert
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted'),
        '',
        true
    );
}

if ($enable_alert) {
    $searchFlag = true;
    $id_alert = (int) get_parameter('id_alert');
    $id_agente = ($id_agente !== 0) ? $id_agente : alerts_get_agent_by_alert($id_alert);

    $result = alerts_agent_module_disable($id_alert, false);

    if ($id_agente) {
        db_process_sql(
            'UPDATE tagente
            SET update_alert_count = 1
            WHERE id_agente = '.$id_agente
        );
    }

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Enable  '.$id_alert
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail to enable '.$id_alert
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully enabled'),
        __('Could not be enabled'),
        '',
        true
    );
}

if ($disable_alert) {
    $searchFlag = true;
    $id_alert = (int) get_parameter('id_alert');
    $id_agente = ($id_agente !== 0) ? $id_agente : alerts_get_agent_by_alert($id_alert);

    $result = alerts_agent_module_disable($id_alert, true);

    if ($id_agente) {
        db_process_sql(
            'UPDATE tagente
            SET update_alert_count = 1
            WHERE id_agente = '.$id_agente
        );
    }

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Disable  '.$id_alert
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail to disable '.$id_alert
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully disabled'),
        __('Could not be disabled'),
        '',
        true
    );
}

if ($standbyon_alert) {
    $searchFlag = true;
    $id_alert = (int) get_parameter('id_alert');

    $result = alerts_agent_module_standby($id_alert, true);

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Standby  '.$id_alert
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail to standby '.$id_alert
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully set standby'),
        __('Could not be set standby'),
        '',
        true
    );
}

if ($standbyoff_alert) {
    $searchFlag = true;
    $id_alert = (int) get_parameter('id_alert');

    $result = alerts_agent_module_standby($id_alert, false);

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Standbyoff  '.$id_alert
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_ALERT_MANAGEMENT,
            'Fail to standbyoff '.$id_alert
        );
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully set off standby'),
        __('Could not be set off standby'),
        '',
        true
    );
}


$searchFlag = true;
if (is_metaconsole() === false) {
    // The tabs will be shown only with manage alerts permissions
    if (check_acl($config['id_user'], 0, 'LW') || check_acl($config['id_user'], 0, 'LM')) {
        $buttons = [
            'list'    => [
                'active' => false,
                'text'   => '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&pure='.$pure.'">'.html_print_image('images/logs@svg.svg', true, ['title' => __('List alerts'), 'class' => 'main_menu_icon invert_filter']).'</a>',
            ],
            'builder' => [
                'active' => false,
                'text'   => '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=builder&pure='.$pure.'">'.html_print_image('images/edit.svg', true, ['title' => __('Builder alert'), 'class' => 'main_menu_icon invert_filter']).'</a>',
            ],
        ];

        $buttons[$tab]['active'] = true;
    } else {
        $buttons = '';
    }

    if ($_GET['sec2'] !== 'operation/cluster/cluster') {
        if ($tab !== 'alert') {
            if ($tab === 'list') {
                ui_print_standard_header(
                    __('Alerts'),
                    'images/gm_alerts.png',
                    false,
                    '',
                    true,
                    $buttons,
                    [
                        [
                            'link'  => '',
                            'label' => __('Manage alerts'),
                        ],
                        [
                            'link'  => '',
                            'label' => __('List'),
                        ],
                    ]
                );
            } else {
                ui_print_standard_header(
                    __('Alerts'),
                    'images/gm_alerts.png',
                    false,
                    '',
                    true,
                    $buttons,
                    [
                        [
                            'link'  => '',
                            'label' => __('Manage alerts'),
                        ],
                        [
                            'link'  => '',
                            'label' => __('Create'),
                        ],
                    ]
                );
            }
        }
    }
} else {
    alerts_meta_print_header();
}

if ($id_agente) {
    $agents = [$id_agente => agents_get_name($id_agente)];

    if ($group == 0) {
        $groups = users_get_groups();
    } else {
        $groups = [0 => __('All')];
    }

    echo $messageAction;

    include_once 'godmode/alerts/alert_list.list.php';
    $all_groups = agents_get_all_groups_agent($id_agente, $agent['id_grupo']);
    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW') || check_acl_one_of_groups($config['id_user'], $all_groups, 'LM')) {
        include_once 'godmode/alerts/alert_list.builder.php';
    }

    return;
}

echo $messageAction;

switch ($tab) {
    case 'list':
        if ($group == 0) {
            $groups = users_get_groups();
        } else {
            $groups = [0 => __('All')];
        }

        $agents = agents_get_group_agents(array_keys($groups), false, 'none', true);

        include_once $config['homedir'].'/godmode/alerts/alert_list.list.php';

    return;

        break;
    case 'builder':
        if ($group == 0) {
            $groups = users_get_groups();
        } else {
            $groups = [0 => __('All')];
        }

        include_once $config['homedir'].'/godmode/alerts/alert_list.builder.php';

    return;

        break;
}
