<?php
/**
 * View for Add actions alerts in Massive Operations
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
        'Trying to access massive agent deletion section'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_agents.php';
require_once 'include/functions_alerts.php';
require_once 'include/functions_users.php';

if (is_ajax()) {
    $get_alerts = (bool) get_parameter('get_alerts');

    if ($get_alerts) {
        $id_agents = get_parameter('id_agents');
        if (empty($id_agents)) {
            echo json_encode('');
            return;
        }

        if (is_array($id_agents) && count($id_agents) == 1 && $id_agents[0] == '') {
            $id_agents = false;
        }

        $alert_templates = agents_get_alerts_simple($id_agents);
        echo json_encode(index_array($alert_templates, 'id_alert_template', 'template_name'));
        return;
    }

    return;
}

$id_group = (int) get_parameter('id_group');
$id_agents = get_parameter('id_agents');
$id_alert_templates = (array) get_parameter('id_alert_templates');
$recursion = get_parameter('recursion');
$add = (bool) get_parameter_post('add');

if ($add) {
    if (empty($id_agents) || $id_agents[0] == 0) {
        ui_print_result_message(false, '', __('Could not be added').'. '.__('No agents selected'));
    } else {
        $actions = get_parameter('action');
        $fires_min = (int) get_parameter('fires_min');
        $fires_max = (int) get_parameter('fires_max');

        if (!empty($actions)) {
            $modules = get_parameter('module');
            $modules_id = [];
            if (!empty($modules)) {
                $modules_id = [];

                foreach ($modules as $module) {
                    foreach ($id_agents as $id_agent) {
                        if ($module == '0') {
                                // Get all modules of agent.
                                $agent_modules = db_get_all_rows_filter(
                                    'tagente_modulo',
                                    ['id_agente' => $id_agent],
                                    'id_agente_modulo'
                                );

                                $agent_modules_id = array_map(
                                    function ($field) {
                                        return $field['id_agente_modulo'];
                                    },
                                    $agent_modules
                                );

                                $modules_id = array_merge($modules_id, $agent_modules_id);
                        } else {
                            $module_id = modules_get_agentmodule_id($module, $id_agent);
                            $modules_id[] = $module_id['id_agente_modulo'];
                        }
                    }
                }

                $agent_alerts = agents_get_alerts($id_agents);
                $cont = 0;
                $agent_alerts_id = [];

                foreach ($agent_alerts['simple'] as $agent_alert) {
                    if ((in_array($agent_alert['id_alert_template'], $id_alert_templates)) && (in_array($agent_alert['id_agent_module'], $modules_id))) {
                        $agent_alerts_id[$cont] = $agent_alert['id'];
                        $cont += 1;
                    }
                }

                $options = [];

                if ($fires_min > 0) {
                    $options['fires_min'] = $fires_min;
                }

                if ($fires_max > 0) {
                    $options['fires_max'] = $fires_max;
                }

                if (empty($agent_alerts_id)) {
                    ui_print_result_message(false, '', __('Could not be added').'. '.__('No alerts selected'));
                } else {
                    $results = true;
                    foreach ($agent_alerts_id as $agent_alert_id) {
                        foreach ($actions as $action) {
                            $result = alerts_add_alert_agent_module_action($agent_alert_id, $action, $options);
                            if ($result === false) {
                                $results = false;
                            }
                        }
                    }

                    $info = [
                        'Agents'    => implode(',', $id_agents),
                        'Alerts'    => addslashes(io_json_mb_encode($agent_alerts)),
                        'Fires Min' => $fires_min,
                        'Fires_max' => $fires_max,
                        'Actions'   => implode(',', $actions),
                    ];
                    db_pandora_audit(
                        AUDIT_LOG_MASSIVE_MANAGEMENT,
                        'Add alert action '.json_encode($id_agents),
                        false,
                        false,
                        json_encode($info)
                    );
                    ui_print_result_message($results, __('Successfully added'), __('Could not be added'));
                }
            } else {
                ui_print_result_message(false, '', __('Could not be added').'. '.__('No modules selected'));
            }
        } else {
            ui_print_result_message(false, '', __('Could not be added').'. '.__('No actions selected'));
        }
    }
}

$groups = users_get_groups();
$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'AW')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

// Avoid php warning
if (empty($alert_templates)) {
    $alert_templates = '';
}

$table = new stdClass();
$table->id = 'delete_table';
$table->width = '98%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data = [];
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(
    false,
    'AW',
    $return_all_group,
    'id_group',
    $id_group,
    false,
    '',
    '',
    true
);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox('recursion', 1, $recursion, true, false);

$table->data[1][0] = __('Agents with templates');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image('images/spinner.png', true);
$table->data[1][0] .= '</span>';
$table->data[1][1] = html_print_select([], 'id_agents[]', 0, false, __('Any'), '', true, true);

$table->data[2][0] = __('Alert templates');
$table->data[2][1] = html_print_select([], 'id_alert_templates[]', '', '', '', '', true, true, true, '', $alert_templates == 0);
$table->data[2][2] = __('When select agents');
$table->data[2][2] .= '<br>';
$table->data[2][2] .= html_print_select(
    [
        'common'  => __('Show common modules'),
        'all'     => __('Show all modules'),
        'unknown' => __('Show unknown and not init modules'),
    ],
    'modules_selection_mode',
    'common',
    false,
    '',
    '',
    true
);
$table->data[2][3] = html_print_select(
    [],
    'module[]',
    $modules_select,
    false,
    '',
    '',
    true,
    true,
    false
);

$actions = alerts_get_alert_actions();
$table->data[3][0] = __('Action');
$table->data[3][1] = html_print_select($actions, 'action[]', '', '', '', '', true, true);
$table->data[3][1] .= '<span><a href="#" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
$table->data[3][1] .= '<span id="advanced_actions" class="advanced_actions invisible">';
$table->data[3][1] .= __('Number of alerts match from').' ';
$table->data[3][1] .= html_print_input_text('fires_min', 0, '', 4, 10, true);
$table->data[3][1] .= ' '.__('to').' ';
$table->data[3][1] .= html_print_input_text('fires_max', 0, '', 4, 10, true);
$table->data[3][1] .= '</span>';

echo '<form method="post" id="form_alerts" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=add_action_alerts">';
html_print_table($table);

$sql = 'SELECT id_agente FROM tagente_modulo WHERE id_agente_modulo IN (SELECT id_agent_module FROM talert_template_modules)';
$agents_with_templates = db_get_all_rows_sql($sql);
$agents_with_templates_json = [];
foreach ($agents_with_templates as $ag) {
    $agents_with_templates_json[] = $ag['id_agente'];
}

$agents_with_templates_json = json_encode($agents_with_templates_json);

echo "<input type='hidden' id='hidden-agents_with_templates' value='$agents_with_templates_json'>";

attachActionButton('add', 'create', $table->width, false, $SelectAction);

echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_javascript_file('massive_operations');
ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
?>

<script type="text/javascript">

$(document).ready (function () {
    
    update_alerts();
    
    var recursion;
    $("#checkbox-recursion").click(function () {
        recursion = this.checked;
        $("#id_group").trigger("change");
    });
    
    var filter_agents_json = $("#hidden-agents_with_templates").val();
    
    $("#id_group").pandoraSelectGroupAgent ({
        agentSelect: "select#id_agents",
        privilege: "AW",
        add_alert_bulk_op: true,
        recursion: function() {return recursion},
        filter_agents_json: filter_agents_json,
        callbackPost: function () {
            var $select_template = $("#id_alert_templates").disable ();
            $("option", $select_template).remove ();
        }
    });
    
    $("#id_agents").change (function () {
        update_alerts();
    });
    
    $("#id_alert_templates").change(alert_templates_changed_by_multiple_agents_with_alerts);
    
    $("#modules_selection_mode").click(function () {
        $("#id_alert_templates").trigger("change");
    });
    
    function update_alerts() {
        var idAgents = Array();
        jQuery.each ($("#id_agents option:selected"), function (i, val) {
            idAgents.push($(val).val());
        });
        showSpinner();
        
        var $select_template = $("#id_alert_templates").disable ();
        
        jQuery.post ("ajax.php", {
                "page" : "godmode/massive/massive_add_action_alerts",
                "get_alerts" : 1,
                "id_agents[]" : idAgents
            },
            function (data, status) {
                $("option", $select_template).remove ();
                options = "";
                jQuery.each (data, function (id, value) {
                    options += "<option value=\""+id+"\">"+value+"</option>";
                });
                
                if (options == "") {
                    options += "<option><?php echo __('None'); ?></option>";
                }
                
                $("#id_alert_templates").append (options);
                hideSpinner();
                $select_template.enable ();
            },
            "json"
        );
    }
    
    $("a.show_advanced_actions").click (function () {
        /* It can be done in two different sites, so it must use two different selectors */
        actions = $(this).parents ("form").children ("span.advanced_actions");
        if (actions.length == 0)
            actions = $(this).parents ("div").children ("span.advanced_actions")
        $("#advanced_actions").removeClass("advanced_actions invisible");
        $(this).remove ();
        
        return false;
    });
    
    $('#id_group').trigger('change');
});
/* ]]> */
</script>
