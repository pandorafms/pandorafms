<?php
/**
 * View for Delete alerts in Massive Operations
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
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

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive alert deletion'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_agents.php';
require_once 'include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';

if (is_ajax()) {
    $get_agents = (bool) get_parameter('get_agents');
    $recursion = (int) get_parameter('recursion');
    $disabled_modules = (int) get_parameter('disabled_modules');

    if ($get_agents) {
        $id_group = (int) get_parameter('id_group');
        $id_alert_template = (int) get_parameter('id_alert_template');
        // Is is possible add keys prefix to avoid auto sorting in js object conversion
        $keys_prefix = (string) get_parameter('keys_prefix', '');

        if ($recursion) {
            $groups = groups_get_children_ids($id_group, true);
        } else {
            $groups = [$id_group];
        }

        if ($disabled_modules == 0) {
            $filter['tagente_modulo.disabled'] = '<> 1';
        } else {
            unset($filter['tagente_modulo.disabled']);
        }

        $agents_alerts = [];
        foreach ($groups as $group) {
            $agents_alerts_one_group = alerts_get_agents_with_alert_template(
                $id_alert_template,
                $group,
                $filter,
                [
                    'tagente.alias',
                    'tagente.id_agente',
                ]
            );
            if (is_array($agents_alerts_one_group)) {
                $agents_alerts = array_merge($agents_alerts, $agents_alerts_one_group);
            }
        }

        $agents = index_array($agents_alerts, 'id_agente', 'alias');

        asort($agents);

        // Add keys prefix
        if ($keys_prefix !== '') {
            foreach ($agents as $k => $v) {
                $agents[$keys_prefix.$k] = $v;
                unset($agents[$k]);
            }
        }

        echo json_encode($agents);
        return;
    }

    return;
}


function process_manage_delete($id_alert_template, $id_agents, $module_names)
{
    if (empty($id_alert_template)) {
        ui_print_error_message(__('No alert selected'));
        return false;
    }

    if (empty($id_agents) || $id_agents[0] == 0) {
        ui_print_error_message(__('No agents selected'));
        return false;
    }

    $module_selection_mode = get_parameter('modules_selection_mode');

    $alert_list = db_get_all_rows_filter('talert_template_modules', ['id_alert_template' => $id_alert_template], 'id_agent_module');

    foreach ($module_names as $module) {
        foreach ($id_agents as $id_agent) {
            $module_id = modules_get_agentmodule_id($module, $id_agent);
            // The module can exist in several of the selected agents, but we have to check if it has an alert.
            foreach ($alert_list as $alert) {
                if ($alert['id_agent_module'] == $module_id['id_agente_modulo']) {
                    $modules_id[] = $module_id['id_agente_modulo'];
                }
            }
        }
    }

    // If is selected "ANY" option then we need the module selection
    // mode: common or all modules
    if (count($module_names) == 1 && $module_names[0] == '0') {
        if ($module_selection_mode == 'common') {
            $sql = 'SELECT t1.id_agente_modulo
				FROM tagente_modulo t1
				WHERE t1.id_agente_modulo IN (
						SELECT t2.id_agent_module
						FROM talert_template_modules t2
						WHERE
							t2.id_alert_template = '.$id_alert_template.')
					AND t1.id_agente IN ('.implode(',', $id_agents).');';
            $modules = db_get_all_rows_sql($sql);

            if (empty($modules)) {
                $modules = [];
            }

            $modules_id = [];
            foreach ($modules as $module) {
                $modules_id[$module['id_agente_modulo']] = $module['id_agente_modulo'];
            }
        } else {
            // For agents selected
            $modules_id = [];

            foreach ($id_agents as $id_agent) {
                $current_modules_agent = agents_get_modules($id_agent, 'id_agente_modulo', ['disabled' => 0]);
                if ($current_modules_agent != false) {
                    // And their modules
                    foreach ($current_modules_agent as $current_module) {
                        $module_alerts = alerts_get_alerts_agent_module($current_module);

                        if ($module_alerts != false) {
                            // And for all alert in modules
                            foreach ($module_alerts as $module_alert) {
                                // Find the template in module
                                if ($module_alert['id_alert_template'] == $id_alert_template) {
                                    $modules_id[] = $module_alert['id_agent_module'];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $conttotal = 0;
    $contsuccess = 0;
    foreach ($modules_id as $module) {
        $success = alerts_delete_alert_agent_module(
            false,
            [
                'id_agent_module'   => $module,
                'id_alert_template' => $id_alert_template,
            ]
        );

        if ($success) {
            $contsuccess++;
        }

        $conttotal++;
    }

    ui_print_result_message(
        $contsuccess > 0,
        __('Successfully deleted').'('.$contsuccess.'/'.$conttotal.')',
        __('Could not be deleted')
    );

    return (bool) ($contsuccess > 0);
}


$id_group = (int) get_parameter('id_group');
$id_agents = get_parameter('id_agents');
$module_names = get_parameter('module');
$id_alert_template = (int) get_parameter('id_alert_template');

$delete = (bool) get_parameter_post('delete');

if ($delete) {
    $result = process_manage_delete($id_alert_template, $id_agents, $module_names);

    $info = [
        'Agent'    => implode(',', $id_agents),
        'Template' => $id_alert_template,
        'Module'   => implode(',', $module_names),
    ];

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Delete alert ',
            false,
            false,
            json_encode($info)
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Fail try to delete alert',
            false,
            false,
            json_encode($info)
        );
    }
}

$groups = users_get_groups();
$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'AW')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

$table = new stdClass();
$table->id = 'delete_table';
$table->class = 'databox filters';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold;';
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data = [];

$usr_groups = users_get_groups($config['id_user'], 'LW', true);
$filter_groups = '';
$filter_groups = implode(',', array_keys($usr_groups));
$templates = alerts_get_alert_templates(['id_group IN ('.$filter_groups.')'], ['id', 'name']);
$table->data[0][0] = __('Alert template');
$table->data[0][1] = html_print_select(
    index_array($templates, 'id', 'name'),
    'id_alert_template',
    $id_alert_template,
    false,
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 100%;'
);
$table->data[0][2] = '';
$table->data[0][3] = '';

$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(
    false,
    'AW',
    $return_all_group,
    'id_group',
    $id_group,
    '',
    '',
    '',
    true,
    false,
    true,
    '',
    $id_alert_template == 0,
    'width: 100%;'
);

$table->data[0][2] = __('Show alerts on disabled modules');
$table->data[0][3] = html_print_checkbox('disabled_modules', 1, false, true, false);


$table->data[1][2] = __('Group recursion');
$table->data[1][3] = html_print_checkbox('recursion', 1, false, true, false);

$table->data[2][0] = __('Agents');
$table->data[2][0] .= '<span id="agent_loading" class="invisible">';
$table->data[2][0] .= html_print_image('images/spinner.png', true);
$table->data[2][0] .= '</span>';
$agents_alerts = alerts_get_agents_with_alert_template(
    $id_alert_template,
    $id_group,
    false,
    [
        'tagente.alias',
        'tagente.id_agente',
    ]
);

$table->data[2][1] = html_print_select(
    index_array($agents_alerts, 'id_agente', 'alias'),
    'id_agents[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    $id_alert_template == 0
);
$table->data[2][2] = __('When select agents');
$table->data[2][2] .= '<br>';
$table->data[2][2] .= html_print_select(
    [
        'common' => __('Show common modules'),
        'all'    => __('Show all modules'),
    ],
    'modules_selection_mode',
    'common',
    false,
    '',
    '',
    true
);
$table->data[2][3] = html_print_select([], 'module[]', '', false, '', '', true, true, false);

echo '<form method="post" id="form_alerts" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_alerts" >';
html_print_table($table);

attachActionButton('delete', 'delete', $table->width, false, $SelectAction);

echo '</form>';

// Hack to translate text "none" in PHP to javascript
echo '<span id ="none_text" class="invisible">'.__('None').'</span>';

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;


$(document).ready (function () {
    $("#form_alerts").submit(function() {
        var get_parameters_count = window.location.href.slice(
            window.location.href.indexOf('?') + 1).split('&').length;
        var post_parameters_count = $("#form_alerts").serializeArray().length;
        
        var count_parameters =
            get_parameters_count + post_parameters_count;
        
        if (count_parameters > limit_parameters_massive) {
            alert("<?php echo __('Unsucessful sending the data, please contact with your administrator or make with less elements.'); ?>");
            return false;
        }
    });
    
    
    $("#id_agents").change(agent_changed_by_multiple_agents_with_alerts);
    
    $("#id_alert_template").change (function () {
        if (this.value != 0) {
            $("#id_agents").enable ();
            $("#id_group").enable ().change ();
        }
        else {
            $("#id_group, #id_agents").disable ();
        }
    });
    
    $("#id_group").change (function () {
        var $select = $("#id_agents").disable ();
        showSpinner();
        $("option", $select).remove ();
        
        jQuery.post ("ajax.php",
            {"page" : "godmode/massive/massive_delete_alerts",
            "get_agents" : 1,
            "id_group" : this.value,
            "recursion" : $("#checkbox-recursion").is(":checked") ? 1 : 0,
            "disabled_modules" : $("#checkbox-disabled_modules").is(":checked") ? 1 : 0,
            "id_alert_template" : $("#id_alert_template").val(),
            // Add a key prefix to avoid auto sorting in js object conversion
            "keys_prefix" : "_"
            },
            function (data, status) {
                options = "";
                jQuery.each (data, function (id, value) {
                    // Remove keys_prefix from the index
                    id = id.substring(1);
                    
                    options += "<option value=\""+id+"\">"+value+"</option>";
                });
                $("#id_agents").append (options);
                hideSpinner();
                $select.enable ();
            },
            "json"
        );
    });
    
    $("#checkbox-recursion").click(function () {
        $("#id_group").trigger("change");
    });
    
    $("#modules_selection_mode").change (function() {
        $("#id_agents").trigger('change');
    });

    $("#checkbox-disabled_modules").click(function () {
        $("#id_group").trigger("change");
    });
});
/* ]]> */
</script>
