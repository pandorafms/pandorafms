<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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
    $recursion = (bool) get_parameter('recursion', false);

    if ($get_agents) {
        $id_group = (int) get_parameter('id_group', 0);
        // Is is possible add keys prefix to avoid auto
        // sorting in js object conversion.
        $keys_prefix = (string) get_parameter('keys_prefix', '');

        if ($id_group == 0) {
            $agents = agents_get_group_agents(
                array_keys(
                    users_get_groups(
                        $config['id_user'],
                        'AW',
                        true,
                        false
                    )
                ),
                false,
                '',
                false,
                $recursion
            );
        } else {
            $agents = agents_get_group_agents(
                $id_group,
                false,
                '',
                false,
                $recursion
            );
        }

        // Add keys prefix.
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


function process_manage_add($id_alert_template, $id_agents, $module_names)
{
    if (empty($id_agents) || $id_agents[0] == 0) {
        ui_print_error_message(__('No agents selected'));
        return false;
    }

    if (empty($id_alert_template)) {
        ui_print_error_message(__('No alert selected'));
        return false;
    }

    $modules_id = [];

    foreach ($module_names as $module) {
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

    $conttotal = 0;
    $contsuccess = 0;
    foreach ($modules_id as $module) {
        $success = alerts_create_alert_agent_module($module, $id_alert_template);

        if ($success) {
            $contsuccess++;
        }

        $conttotal++;
    }

    if ($contsuccess > 0) {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Add alert',
            false,
            false,
            'Alert template: '.$id_alert_template.' Modules: '.json_encode($modules_id)
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Fail try to add alert',
            false,
            false,
            'Alert template: '.$id_alert_template.' Modules: '.json_encode($modules_id)
        );
    }

    ui_print_result_message(
        $contsuccess > 0,
        __('Successfully added').'('.$contsuccess.'/'.$conttotal.')',
        __('Could not be added')
    );

}


$id_group = (int) get_parameter('id_group', -1);
$id_agents = get_parameter('id_agents');
$module_names = get_parameter('module');
$id_alert_template = (int) get_parameter('id_alert_template');
$recursion = get_parameter('recursion');

$add = (bool) get_parameter_post('add');

if ($add) {
    process_manage_add($id_alert_template, $id_agents, $module_names);
}

$groups = users_get_groups();
$own_info = get_user_info($config['id_user']);
if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, 'AW')) {
    $return_all_group = false;
} else {
    $return_all_group = true;
}

$table = new stdClass();
$table->id = 'add_table';
$table->class = 'databox filters';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold; vertical-align:top';
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '25%';
$table->size[2] = '15%';
$table->size[3] = '30%';

$table->data = [];

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(
    false,
    'AW',
    $return_all_group,
    'id_group',
    0,
    '',
    'Select',
    -1,
    true,
    false,
    true,
    '',
    false,
    'width:180px;'
);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox('recursion', 1, $recursion, true, false, '', true);

$table->data[1][0] = __('Agents');
$table->data[1][0] .= '<span id="agent_loading" class="invisible">';
$table->data[1][0] .= html_print_image('images/spinner.png', true);
$table->data[1][0] .= '</span>';
$agents_alerts = alerts_get_agents_with_alert_template(
    $id_alert_template,
    $id_group,
    false,
    [
        'tagente.nombre',
        'tagente.id_agente',
    ]
);

$agents = agents_get_group_agents(array_keys(users_get_groups($config['id_user'], 'AW', false)));
$table->data[1][1] = html_print_select(
    $agents,
    'id_agents[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width:180px;'
);
$table->data[1][2] = __('When select agents');
$table->data[1][2] .= '<br>';
$table->data[1][2] .= html_print_select(
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
$table->data[1][3] = html_print_select([], 'module[]', '', false, '', '', true, true, false, '', false, 'width:180px;');

$usr_groups = users_get_groups($config['id_user'], 'LW', true);
$filter_groups = '';
$filter_groups = implode(',', array_keys($usr_groups));
$templates = alerts_get_alert_templates(['id_group IN ('.$filter_groups.')'], ['id', 'name']);
$table->data[2][0] = __('Alert template');
$table->data[2][1] = html_print_select(
    index_array($templates, 'id', 'name'),
    'id_alert_template',
    $id_alert_template,
    false,
    __('Select'),
    0,
    true
);
$table->data[2][2] = '';
$table->data[2][3] = '';

echo '<form method="post" id="form_alerts" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=add_alerts">';
html_print_table($table);

attachActionButton('add', 'add', $table->width, false, $SelectAction);

echo '</form>';

// TODO: Change to iu_print_error system.
echo '<h3 class="error invisible" id="message"> </h3>';

// Hack to translate text "none" in PHP to javascript.
echo '<span id ="none_text" class="invisible">'.__('None').'</span>';

ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;

$(document).ready (function () {
/*     $("#form_alerts").submit(function() {
        var get_parameters_count = window.location.href.slice(
            window.location.href.indexOf('?') + 1).split('&').length;
        var post_parameters_count = $("#form_alerts").serializeArray().length;

        var count_parameters =
            get_parameters_count + post_parameters_count;

        if (count_parameters > limit_parameters_massive) {
            alert("<?php echo __('Unsucessful sending the data, please contact with your administrator or make with less elements.'); ?>");
            return false;
        }
    }); */

    $("#checkbox-recursion").click(function () {
        $("#id_group").trigger("change");
    });

    $("#id_agents").change(agent_changed_by_multiple_agents);

    $("#id_group").change (function () {
        var $select = $("#id_agents").enable ();
        showSpinner();
        $("option", $select).remove ();

        jQuery.post ("ajax.php",
            {"page" : "godmode/massive/massive_add_alerts",
            "get_agents" : 1,
            "id_group" : this.value,
            "recursion" : $("#checkbox-recursion").is(":checked") ? 1 : 0,
            // Add a key prefix to avoid auto sorting in js object conversion.
            "keys_prefix" : "_"
            },
            function (data, status) {
                options = "";
                jQuery.each (data, function (id, value) {
                    // Remove keys_prefix from the index.
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

    $("#id_group").value = "0";

    $("#id_group").click (
    function () {
        $(this).css ("width", "auto");
    });

    $("#id_group").blur (function () {
        $(this).css ("width", "180px");
    });

    $("#id_agents").click (
    function () {
        $(this).css ("width", "auto");
    });

    $("#id_agents").blur (function () {
        $(this).css ("width", "180px");
    });

    $("#module").click (
    function () {
        $(this).css ("width", "auto");
    });

    $("#module").blur (function () {
        $(this).css ("width", "180px");
    });

    $("#modules_selection_mode").change (function() {
        $("#id_agents").trigger('change');
    });
});
/* ]]> */
</script>
