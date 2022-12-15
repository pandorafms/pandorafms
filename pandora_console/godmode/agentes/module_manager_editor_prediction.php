<?php
/**
 * Prediction module manager editor.
 *
 * @category   Modules
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

enterprise_include_once('include/functions_policies.php');
enterprise_include_once('godmode/agentes/module_manager_editor_prediction.php');
require_once 'include/functions_agents.php';
ui_require_jquery_file('validate');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$page = get_parameter('page', '');
$id_agente = get_parameter('id_agente', '');
$agent_name = get_parameter('agent_name', agents_get_alias($id_agente));
$id_agente_modulo = get_parameter('id_agent_module', 0);
$custom_integer_2 = get_parameter('custom_integer_2', 0);
$sql = 'SELECT *
	FROM tagente_modulo
	WHERE id_agente_modulo = '.$id_agente_modulo;
$row = db_get_row_sql($sql);
$is_service = false;
$is_synthetic = false;
$is_synthetic_avg = false;
$ops = false;
if ($row !== false && is_array($row) === true) {
    $prediction_module = $row['prediction_module'];
    $custom_integer_2 = $row['custom_integer_2'];
    // Services are an Enterprise feature.
    $custom_integer_1 = $row['custom_integer_1'];

    switch ((int) $prediction_module) {
        case MODULE_PREDICTION_SERVICE:
            $selected = 'service_selected';
            $custom_integer_2 = 0;
        break;

        case MODULE_PREDICTION_SYNTHETIC:
            $ops_json = enterprise_hook(
                'modules_get_synthetic_operations',
                [$id_agente_modulo]
            );

            $ops = json_decode($ops_json, true);

            // Erase the key of array serialize as <num>**.
            $chunks = explode('**', reset(array_keys($ops)));

            $first_op = explode('_', $chunks[1]);

            if (isset($first_op[1]) === true && $first_op[1] === 'avg') {
                $selected = 'synthetic_avg_selected';
            } else {
                $selected = 'synthetic_selected';
            }

            $custom_integer_1 = 0;
            $custom_integer_2 = 0;
        break;

        case MODULE_PREDICTION_TRENDING:
            $selected = 'trending_selected';
            $prediction_module = $custom_integer_1;
        break;

        case MODULE_PREDICTION_PLANNING:
            $selected = 'capacity_planning';
            $prediction_module = $custom_integer_1;
            $estimation_interval = $custom_string_1;
            $estimation_type = $custom_string_2;
        break;

        default:
            $prediction_module = $custom_integer_1;
        break;
    }
} else {
    $selected = 'capacity_planning';
    $custom_integer_1 = 0;
}

if (strstr($page, 'policy_modules') === false) {
    if ($config['enterprise_installed']) {
        $disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
    } else {
        $disabledBecauseInPolicy = false;
    }

    if ($disabledBecauseInPolicy) {
        $disabledTextBecauseInPolicy = 'disabled = "disabled"';
    }
}

$extra_title = __('Prediction server module');

$data = [];
$data[0] = __('Source module');
$data[0] .= ui_print_help_icon('prediction_source_module', true);
push_table_simple($data, 'caption_module_service_synthetic_selector');
// Services and Synthetic are an Enterprise feature.
$module_service_synthetic_selector = enterprise_hook('get_module_service_synthetic_selector', [$selected]);
if ($module_service_synthetic_selector !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = $module_service_synthetic_selector;

    $table_simple->colspan['module_service_synthetic_selector'][1] = 3;
    push_table_simple($data, 'module_service_synthetic_selector');
}

$data = [];
$data[0] = __('Agent');
$data[1] = __('Module');
$data[2] = __('Period');
$table_simple->cellclass['caption_prediction_module'][0] = 'w33p';
$table_simple->cellclass['caption_prediction_module'][1] = 'w33p';
$table_simple->cellclass['caption_prediction_module'][2] = 'w33p';
push_table_simple($data, 'caption_prediction_module');

$data = [];
// Get module and agent of the target prediction module.
if (empty($prediction_module) === false) {
    $id_agente_clean = modules_get_agentmodule_agent($prediction_module);
    $prediction_module_agent = modules_get_agentmodule_agent_name($prediction_module);
    $agent_name_clean = $prediction_module_agent;
    $agent_alias = agents_get_alias($id_agente_clean);
} else {
    $id_agente_clean = 0;
    $agent_name_clean = '';
    $agent_alias = '';
}

$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'agent_name';
$params['value'] = $agent_alias;
$params['javascript_is_function_select'] = true;
$params['selectbox_id'] = 'prediction_module';
$params['none_module_text'] = __('Select Module');
$params['use_hidden_input_idagent'] = true;
$params['input_style'] = 'width: 100%;';
$params['hidden_input_idagent_id'] = 'hidden-id_agente_module_prediction';
$data[0] = ui_print_agent_autocomplete_input($params);

if ($id_agente > 0) {
    $predictionModuleInput = html_print_select_from_sql(
        'SELECT id_agente_modulo, nombre
            FROM tagente_modulo
            WHERE delete_pending = 0
                AND history_data = 1
                AND id_agente =  '.$id_agente_clean.'
                AND id_agente_modulo  <> '.$id_agente_modulo,
        'prediction_module',
        $prediction_module,
        '',
        __('Select Module'),
        0,
        true,
        false,
        true,
        false,
        'width: 100%;'
    );
} else {
    $predictionModuleInput = '<select id="prediction_module" name="custom_integer_1" disabled="disabled"><option value="0">Select an Agent first</option></select>';
}

$data[1] = $predictionModuleInput;
$data[2] = html_print_select([__('Weekly'), __('Monthly'), __('Daily')], 'custom_integer_2', $custom_integer_2, '', '', 0, true, false, true, '', false, 'width: 100%;');
$data[2] .= html_print_input_hidden('id_agente_module_prediction', $id_agente, true);
$table_simple->cellclass['prediction_module'][0] = 'w33p';
$table_simple->cellclass['prediction_module'][1] = 'w33p';
$table_simple->cellclass['prediction_module'][2] = 'w33p';
push_table_simple($data, 'prediction_module');

$data = [];
$data[0] = __('Calculation type');
$data[1] = __('Future estimation');
$data[2] = __('Limit value');
$table_simple->cellclass['caption_capacity_planning'][0] = 'w33p';
$table_simple->cellclass['caption_capacity_planning'][1] = 'w33p';
$table_simple->cellclass['caption_capacity_planning'][2] = 'w33p';
push_table_simple($data, 'caption_capacity_planning');

$data = [];
$data[0] = html_print_select(
    [
        'estimation_absolute'    => __('Estimated absolute value'),
        'estimation_calculation' => __('Calculation of days to reach limit'),
    ],
    'estimation_type',
    $estimation_type,
    '',
    '',
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 100%;'
);

$data[1] = html_print_input(
    [
        'type'   => 'interval',
        'return' => 'true',
        'name'   => 'estimation_interval',
        'value'  => $estimation_interval,
        'class'  => 'w100p',
    ],
    'div',
    false
);

$data[2] = html_print_input(
    [
        'type'   => 'number',
        'return' => 'true',
        'id'     => 'estimation_days',
        'name'   => 'estimation_days',
        'value'  => $estimation_interval,
        'class'  => 'w100p',
    ]
);
$table_simple->cellclass['capacity_planning'][0] = 'w33p';
$table_simple->cellclass['capacity_planning'][1] = 'w33p';
$table_simple->cellclass['capacity_planning'][2] = 'w33p';
push_table_simple($data, 'capacity_planning');

// Services are an Enterprise feature.
$selector_form = enterprise_hook('get_selector_form', [$custom_integer_1]);
if ($selector_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = $selector_form['caption'];
    push_table_simple($data, 'caption_service_module');

    $data = [];
    $data[0] = $selector_form['input'];
    push_table_simple($data, 'service_module');
}

// Synthetic modules are an Enterprise feature.
$synthetic_module_form = enterprise_hook('get_synthetic_module_form');
if ($synthetic_module_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = $synthetic_module_form;
    push_table_simple($data, 'synthetic_module');
}

$trending_module_form = enterprise_hook('get_trending_module_form', [$custom_string_1]);
if ($trending_module_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = $trending_module_form['caption'];
    push_table_simple($data, 'caption_trending_module');

    $data = [];
    $data[0] = $trending_module_form['input'];
    push_table_simple($data, 'trending_module');
}

// Netflow modules are an Enterprise feature.
$netflow_module_form = enterprise_hook('get_netflow_module_form', [$custom_integer_1]);
if ($netflow_module_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = '';
    $data[1] = $netflow_module_form;
    push_table_simple($data, 'netflow_module');
}

// Removed common useless parameter.
unset($table_advanced->data[3]);
?>
<script type="text/javascript">
    $(document).ready(function() {
        <?php
        enterprise_hook(
            'setup_services_synth',
            [
                $selected,
                $is_netflow,
                $ops,
            ]
        );
        ?>
    });
</script>