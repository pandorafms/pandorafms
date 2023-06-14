<?php
/**
 * Common module editor.
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

// Begin.
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_categories.php';
require_once $config['homedir'].'/include/graphs/functions_d3.php';

use PandoraFMS\Agent;

include_javascript_d3();


global $config;


function prepend_table_simple($row, $id=false)
{
    global $table_simple;

    if ($id) {
        $data = [$id => $row];
    } else {
        $data = [$row];
    }

    $table_simple->data = array_merge($data, $table_simple->data);
}


function push_table_simple($row, $id=false)
{
    global $table_simple;

    if ($id) {
        $data = [$id => $row];
    } else {
        $data = [$row];
    }

    $table_simple->data = array_merge($table_simple->data, $data);
}


function prepend_table_advanced($row, $id=false)
{
    global $table_advanced;

    if ($id) {
        $data = [$id => $row];
    } else {
        $data = [$row];
    }

    $table_advanced->data = array_merge($data, $table_advanced->data);
}


function push_table_advanced($row, $id=false)
{
    global $table_advanced;

    if ($id) {
        $data = [$id => $row];
    } else {
        $data = [$row];
    }

    $table_advanced->data = array_merge($table_advanced->data, $data);
}


function add_component_selection($id_network_component_type)
{
    global $table_simple;
    global $config;

    $component_groups = network_components_get_groups($id_network_component_type);

    if ($config['style'] === 'pandora_black' && is_metaconsole() === false) {
        $background_row = 'background-color: #444';
    } else {
        $background_row = 'background-color: #ececec';
    }

    $data = [];
    $data[0] = '<span id="component_group" class="left">';
    $data[0] .= html_print_select(
        $component_groups,
        'network_component_group',
        '',
        '',
        '--'.__('Manual setup').'--',
        0,
        true,
        false,
        false,
        'w50p'
    );
    $data[0] .= '</span>';
    $data[1] = '<span id="no_component" class="invisible error">'.__('No component was found').'</span>';
    $data[1] = '<span id="component" class="invisible right">';
    $data[1] = html_print_select(
        [],
        'network_component',
        '',
        '',
        '---'.__('Manual setup').'---',
        0,
        true,
        false,
        true,
        'w50p',
    );
    $data[1] .= '</span>';
    $data[1] .= '<span id="component_loading" class="invisible">'.html_print_image('images/spinner.gif', true).'</span>';
    $data[1] .= html_print_input_hidden('id_module_component_type', $id_network_component_type, true);

    $table_simple->rowstyle['module_component'] = $background_row.'; padding-bottom: 10px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;';

    prepend_table_simple($data, 'module_component');

    $data = [];
    $data[0] = __('Using module component').' ';
    $table_simple->rowstyle['caption_module_component'] = $background_row.'; padding-top: 5px; border-top-left-radius: 8px; border-top-right-radius: 8px;';
    prepend_table_simple($data, 'caption_module_component');
}


require_once 'include/functions_network_components.php';
enterprise_include_once('include/functions_policies.php');

// If code comes from policies disable export select.
global $__code_from;

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$largeClassDisabledBecauseInPolicy = '';

$update_module_id = (int) get_parameter_get('update_module');
$edit_module      = (bool) get_parameter_get('edit_module');
$page             = get_parameter('page', '');
$in_policies_page = strstr($page, 'policy_modules');

if ($in_policies_page === false && $id_agent_module) {
    if ($config['enterprise_installed']) {
        if (policies_is_module_linked($id_agent_module) == 1) {
            $disabledBecauseInPolicy = 1;
        } else {
            $disabledBecauseInPolicy = 0;
        }
    } else {
        $disabledBecauseInPolicy = false;
    }

    if ($disabledBecauseInPolicy) {
        $disabledTextBecauseInPolicy = 'readonly = "readonly"';
    }
}

if ($disabledBecauseInPolicy) {
    $classdisabledBecauseInPolicy = 'readonly';
    $largeClassDisabledBecauseInPolicy = 'class=readonly';
} else {
    $classdisabledBecauseInPolicy = '';
}

if (empty($id_agent_module) === false && isset($id_agente) === true) {
    $moduleIdContent = html_print_div(
        [
            'class'   => 'moduleIdBox',
            'content' => __('ID').'&nbsp;<span class="font_14pt">'.$id_agent_module.'</span>',
        ],
        true
    );
} else {
    $moduleIdContent = '';
}

$policy_link = db_get_value(
    'policy_linked',
    'tagente_modulo',
    'id_agente_modulo',
    $id_agent_module
);

if ((int) $policy_link !== 0) {
    $disabled_enable = 1;
} else {
    $disabled_enable = 0;
}

if ((isset($id_agent_module) === true && $id_agent_module > 0) || (int) $id_policy_module !== 0) {
    $edit = false;
} else {
    $edit = true;
}

$table_simple = new stdClass();
$table_simple->id = 'simple';
$table_simple->styleTable = 'border-radius: 8px;';
$table_simple->class = 'w100p floating_form';
$table_simple->data = [];
$table_simple->style = [];
$table_simple->cellclass = [];
$table_simple->colspan = [];
$table_simple->rowspan = [];
$table_simple->cellpadding = 2;
$table_simple->cellspacing = 0;
$table_simple->rowspan[3][2] = 3;
$table_simple->rowspan[4][2] = 3;
// Special configuration for some rows.
$table_simple->rowclass['caption_target_ip'] = 'field_half_width pdd_t_10px';
$table_simple->rowclass['target_ip'] = 'field_half_width';
$table_simple->rowclass['caption_tcp_send_receive'] = 'field_half_width pdd_t_10px';
$table_simple->rowclass['tcp_send_receive'] = 'field_half_width';
$table_simple->rowclass['caption_configuration_data'] = 'field_half_width pdd_t_10px';
$table_simple->rowclass['textarea_configuration_data'] = 'field_half_width';
$table_simple->rowclass['configuration_data'] = 'field_half_width';
$table_simple->cellstyle['configuration_data'][0] = 'justify-content: flex-end;padding-top: 10px;';
$table_simple->rowclass['textarea_web_checks'] = 'field_half_width';
$table_simple->rowclass['buttons_web_checks'] = 'field_half_width';
$table_simple->cellstyle['buttons_web_checks'][0] = 'justify-content: flex-end;padding-top: 10px;';

$table_simple->rowclass['caption_module_name'] = 'field_half_width pdd_t_10px';
$table_simple->rowclass['module_name'] = 'field_half_width';
$table_simple->data['caption_module_name'][0] = __('Name');
$table_simple->data['caption_module_name'][1] = __('Disabled');
$table_simple->data['module_name'][0] = html_print_input_text_extended(
    'name',
    $name,
    'text-name',
    '',
    65,
    100,
    $disabledBecauseInPolicy,
    '',
    $largeClassDisabledBecauseInPolicy,
    true
).$moduleIdContent;

$table_simple->data['module_name'][1] = html_print_checkbox_switch(
    'disabled',
    1,
    $disabled,
    true,
    $disabled_enable
);
/*
    html_print_checkbox(
    'disabled',
    1,
    $disabled,
    true,
    $disabled_enable,
    '',
    false,
    '',
    '',
    'style="margin-left: 5px;"'
    );
*/
// Caption for Module group and Type.
$table_simple->rowclass['captions_module_n_type'] = 'field_half_width pdd_t_10px';
$table_simple->rowclass['module_n_type'] = 'field_half_width';
$table_simple->data['captions_module_n_type'][0] = html_print_input_hidden('id_module_type_hidden', $id_module_type, true);
$table_simple->data['captions_module_n_type'][0] .= __('Module group');
$table_simple->data['captions_module_n_type'][1] = __('Type').ui_print_help_icon($help_type, true, '', '', '', 'module_type_help');
// Module group and Type.
$table_simple->rowclass['module_n_type'] = 'field_half_width';
$table_simple->data['module_n_type'][0] .= html_print_select_from_sql(
    'SELECT id_mg, name FROM tmodule_group ORDER BY name',
    'id_module_group',
    $id_module_group,
    '',
    __('Not assigned'),
    '0',
    true,
    false,
    true,
    $disabledBecauseInPolicy,
    'width: 480px'
);

if ($edit === false) {
    $sql = sprintf(
        'SELECT id_tipo, nombre
			FROM ttipo_modulo
			WHERE id_tipo = %s
			ORDER BY descripcion',
        $id_module_type
    );

    $type_names = db_get_all_rows_sql($sql);

    $type_names_hash = [];
    foreach ($type_names as $tn) {
        $type_names_hash[$tn['id_tipo']] = $tn['nombre'];
    }

    $table_simple->data['module_n_type'][1] = '<span class="result_info_text">'.modules_get_moduletype_description($id_module_type).' ('.$type_names_hash[$id_module_type].')</span>';
} else {
    $idModuleType = (isset($id_module_type) === true) ? $id_module_type : '';
    // Removed web analysis and log4x from select.
    $tipe_not_in = '24, 25';
    if (is_metaconsole() === true) {
        $tipe_not_in .= ', 34, 35, 36, 37';
    }

    $sql = sprintf(
        'SELECT id_tipo, descripcion, nombre, categoria
		FROM ttipo_modulo
		WHERE categoria IN (%s)
        AND id_tipo NOT IN (%s)
		ORDER BY id_tipo ASC',
        implode(',', $categories),
        $tipe_not_in
    );

    $type_names = db_get_all_rows_sql($sql);

    $type_names_hash = [];
    $type_description_hash = [];
    if (isset($type_names) === true
        && is_array($type_names) === true
    ) {
        foreach ($type_names as $tn) {
            $type_names_hash[$tn['id_tipo']] = $tn['nombre'];
            $type_description_hash[$tn['id_tipo']] = $tn['descripcion'];
        }
    }

    $table_simple->data['module_n_type'][1] = html_print_select(
        $type_description_hash,
        'id_module_type',
        $idModuleType,
        $disabledBecauseInPolicy,
        '',
        0,
        true,
        false,
        false,
        '',
        false,
        'width: 480px;',
        false,
        100
    );
}

// Store the relation between id and name of the types on a hidden field.
$table_simple->data['module_n_type'][1] .= html_print_input_hidden(
    'type_names',
    base64_encode(io_json_mb_encode($type_names_hash)),
    true
);


if ($edit_module === true) {
    $id_module_type = (int) $id_module_type;
    // Check if the module type is string.
    switch ($id_module_type) {
        case MODULE_TYPE_GENERIC_DATA_STRING:
        case MODULE_TYPE_REMOTE_TCP_STRING:
        case MODULE_TYPE_REMOTE_SNMP_STRING:
        case MODULE_TYPE_ASYNC_STRING:
        case MODULE_TYPE_WEB_CONTENT_STRING:
        case MODULE_TYPE_REMOTE_CMD_STRING:
            $stringTypeModule = true;
        break;

        default:
            $stringTypeModule = false;
        break;
    }

    if (($id_module_type >= 1 && $id_module_type <= 5)
        || ($id_module_type >= 21 && $id_module_type <= 23)
        || ($id_module_type === 100)
    ) {
        $help_header = 'local_module';
    }

    if ($id_module_type === 6 || $id_module_type === 7
    ) {
        $help_header = 'icmp_module_tab';
    }

    if ($id_module_type >= 15 && $id_module_type <= 18) {
        $help_header = 'snmp_module_tab';
    }

    if ($id_module_type >= 8 && $id_module_type <= 11) {
        $help_header = 'tcp_module_tab';
    }

    if ($id_module_type >= 30 && $id_module_type <= 33) {
        $help_header = 'webserver_module_tab';
    }
}

if ((bool) $disabledBecauseInPolicy === true) {
    $table_simple->data['module'][0] .= html_print_input_hidden(
        'id_module_group',
        $id_module_group,
        true
    );
}

// Thresholds Table.
$tableBasicThresholds = new stdClass();
$tableBasicThresholds->class = 'w100p';
$tableBasicThresholds->id = 'basic_thresholds';
$tableBasicThresholds->style = [];
$tableBasicThresholds->rowclass = [];
$tableBasicThresholds->data = [];

// WARNING THRESHOLD.
$tableBasicThresholds->rowclass['caption_warning_threshold'] = 'field_half_width pdd_t_10px';
$tableBasicThresholds->rowclass['warning_threshold'] = 'field_half_width';
$tableBasicThresholds->data['caption_warning_threshold'][0] .= __('Warning threshold').'&nbsp;';

$tableBasicThresholds->data['caption_warning_threshold'][0] .= '<span class="font_11" id="caption_minmax_warning">('.__('Min / Max').')</span>';
$tableBasicThresholds->data['warning_threshold'][0] .= html_print_input_text(
    'min_warning',
    $min_warning,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy || $edit === true,
    false,
    '',
    $classdisabledBecauseInPolicy
);
$tableBasicThresholds->data['warning_threshold'][1] .= html_print_input_text(
    'max_warning',
    $max_warning,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy || $edit === true,
    false,
    '',
    $classdisabledBecauseInPolicy
).'</span>';

$tableBasicThresholds->data['switch_warning_threshold'][0] .= html_print_switch_radio_button(
    [
        html_print_radio_button_extended('warning_thresholds_checks', 'normal_warning', __('Normal'), ($percentage_warning && $warning_inverse) ? false : 'normal_warning', false, '', '', true, false, '', 'radius-normal_warning'),
        html_print_radio_button_extended('warning_thresholds_checks', 'warning_inverse', __('Inverse interval'), ($warning_inverse) ? 'warning_inverse' : false, $disabledBecauseInPolicy, '', '', true, false, '', 'radius-warning_inverse'),
        html_print_radio_button_extended('warning_thresholds_checks', 'percentage_warning', __('Percentage'), ($percentage_warning) ? 'percentage_warning' : false, $disabledBecauseInPolicy, '', '', true, false, '', 'radius-percentage_warning'),
    ],
    [ 'class' => 'margin-top-10' ],
    true
);

$basicThresholdsIntervalWarning = [];
$basicThresholdsIntervalWarning[] = '<span>'.__('Inverse interval').'</span>';
$basicThresholdsIntervalWarning[] = html_print_checkbox_switch(
    'warning_inverse_string',
    'warning_inverse_string',
    $warning_inverse,
    true,
    $disabledBecauseInPolicy
);

$tableBasicThresholds->rowclass['caption_switch_warning_inverse_string'] = 'field_half_width';
$tableBasicThresholds->data['caption_switch_warning_inverse_string'][0] = html_print_div(
    [
        'class'   => 'margin-top-10',
        'style'   => 'display: flex; flex-direction: row-reverse; align-items: center;',
        'content' => implode('', $basicThresholdsIntervalWarning),
    ],
    true
);

$tableBasicThresholds->data['caption_warning_threshold'][0] .= '<span class="font_11" id="caption_str_warning">('.__('Str.').')</span>';
$tableBasicThresholds->data['warning_threshold'][0] .= html_print_input_text(
    'str_warning',
    str_replace('"', '', $str_warning),
    '',
    10,
    1024,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
).'</span>';


$tableBasicThresholds->data['switch_warning_threshold'][0] .= html_print_div(
    [
        'id'      => 'percentage_warning',
        'content' => $divPercentageContent,
    ],
    true
);

// CHANGE TO CRITICAL STATUS
$tableBasicThresholds->data['caption_warning_time'][0] .= __('Change to critical status after');
$tableBasicThresholds->data['warning_time'][0] .= html_print_input_text('warning_time', $warning_time, '', 5, 15, true);
$tableBasicThresholds->data['warning_time'][1] .= '&nbsp;&nbsp;<b>'.__('intervals in warning status.').'</b>';

// CRITICAL THRESHOLD.
$tableBasicThresholds->rowclass['caption_critical_threshold'] = 'field_half_width pdd_t_10px';
$tableBasicThresholds->rowclass['critical_threshold'] = 'field_half_width';
$tableBasicThresholds->data['caption_critical_threshold'][0] .= __('Critical threshold').'&nbsp;';
$tableBasicThresholds->data['caption_critical_threshold'][0] .= '<span class="font_11" id="caption_minmax_critical">('.__('Min / Max').')</span>';
$tableBasicThresholds->data['critical_threshold'][0] .= html_print_input_text(
    'min_critical',
    $min_critical,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy || $edit === false,
    false,
    '',
    $classdisabledBecauseInPolicy
);
$tableBasicThresholds->data['critical_threshold'][1] .= html_print_input_text(
    'max_critical',
    $max_critical,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy || $edit === false,
    false,
    '',
    $classdisabledBecauseInPolicy
).'</span>';

$tableBasicThresholds->data['switch_critical_threshold'][0] .= html_print_switch_radio_button(
    [
        html_print_radio_button_extended('critical_thresholds_checks', 'normal_critical', __('Normal'), ($percentage_critical && $critical_inverse) ? false : 'normal_critical', false, '', '', true, false, '', 'radius-normal_critical'),
        html_print_radio_button_extended('critical_thresholds_checks', 'critical_inverse', __('Inverse interval'), ($critical_inverse) ? 'critical_inverse' : false, $disabledBecauseInPolicy, '', '', true, false, '', 'radius-critical_inverse'),
        html_print_radio_button_extended('critical_thresholds_checks', 'percentage_critical', __('Percentage'), ($percentage_critical) ? 'percentage_critical' : false, $disabledBecauseInPolicy, '', '', true, false, '', 'radius-percentage_critical'),
    ],
    [ 'class' => 'margin-top-10' ],
    true
);


$basicThresholdsIntervalCritical = [];
$basicThresholdsIntervalCritical[] = '<span>'.__('Inverse interval').'</span>';
$basicThresholdsIntervalCritical[] = html_print_checkbox_switch(
    'critical_inverse_string',
    'critical_inverse_string',
    $critical_inverse,
    true,
    $disabledBecauseInPolicy
);

$tableBasicThresholds->rowclass['caption_switch_critical_inverse_string'] = 'field_half_width';
$tableBasicThresholds->data['caption_switch_critical_inverse_string'][0] = html_print_div(
    [
        'class'   => 'margin-top-10',
        'style'   => 'display: flex; flex-direction: row-reverse; align-items: center;',
        'content' => implode('', $basicThresholdsIntervalCritical),
    ],
    true
);

$tableBasicThresholds->data['switch_critical_threshold'][0] .= html_print_div(
    [
        'id'      => 'percentage_critical',
        'content' => $divPercentageContent,
    ],
    true
);

$tableBasicThresholds->data['caption_critical_threshold'][0] .= '<span class="font_11" id="caption_str_critical">('.__('Str.').')</span>';
$tableBasicThresholds->data['critical_threshold'][0] .= html_print_input_text(
    'str_critical',
    str_replace('"', '', $str_critical),
    '',
    10,
    1024,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);

$table_simple->rowstyle['thresholds_table'] = 'margin-top: 15px;height: 400px;width: 100%';
$table_simple->cellclass['thresholds_table'][0] = 'table_section half_section_left';
$table_simple->data['thresholds_table'][0] = html_print_table($tableBasicThresholds, true);
if (modules_is_string_type($id_module_type) === false || (bool) $edit === true) {
    $table_simple->cellclass['thresholds_table'][1] = 'table_section half_section_rigth';
    $table_simple->data['thresholds_table'][1] = '<svg id="svg_dinamic" width="500" height="300"> </svg>';
}

$table_simple->rowclass['caption_historical_data'] = 'mrgn_top_10px';
$table_simple->data['caption_historical_data'][0] = __('Historical data');
if ($disabledBecauseInPolicy) {
    // If is disabled, we send a hidden in his place and print a false
    // checkbox because HTML dont send disabled fields
    // and could be disabled by error.
    $table_simple->data['historical_data'][0] = html_print_checkbox_switch(
        'history_data_fake',
        1,
        $history_data,
        true,
        $disabledBecauseInPolicy
    );
    $table_simple->data['historical_data'][0] .= html_print_input_hidden('history_data', (int) $history_data, true);
} else {
    $table_simple->data['historical_data'][0] = html_print_checkbox_switch(
        'history_data',
        1,
        $history_data,
        true,
        $disabledBecauseInPolicy
    );
}

// Business Logic for Advanced Part.
global $__code_from;
// Code comes from module_editor.
if ($__code_from === 'modules') {
    $__table_modules = 'ttag_module';
    $__id_where = 'b.id_agente_modulo';
    $__id = (int) $id_agent_module;

    $__sql = ' AND b.id_policy_module = 0';
    $__sql_policy = ' AND b.id_policy_module != 0';
} else {
    // Code comes from policy module editor.
    global $__id_pol_mod;
    $__table_modules = 'ttag_policy_module';
    $__id_where = 'b.id_policy_module';
    $__id = $__id_pol_mod;

    $__sql = '';
}

// In the data modules, the interval is not in seconds. It is a factor
// to be multiplied for the agent interval.
if ((int) $moduletype === MODULE_DATA) {
    $interval_factor = 1;
    if (isset($id_agente) === true) {
        $agent_interval = (float) agents_get_interval($id_agente);
        if ($agent_interval > 0) {
            $interval = (float) $interval;
            $interval_factor = ($interval / $agent_interval);
        }

        $outputExecutionInterval = human_time_description_raw($interval).' ('.sprintf(__('Agent interval x %s'), $interval_factor).') ';
    } else {
        $outputExecutionInterval = sprintf(__('Agent interval x %s'), $interval_factor);
    }

    if ($__code_from === 'policies') {
        // If is the policy form, module_interval will store the factor (not the seconds).
        // So server will transform it to interval in seconds.
        $outputExecutionInterval = sprintf(__('Default').': 1', $interval_factor);
        $outputExecutionInterval .= html_print_input_hidden('module_interval', $interval_factor, true);
    }

    // If it is a non policy form, the module_interval will not provided and will.
    // be taken the agent interval (this code is at configurar_agente.php).
} else {
    $interval = ($interval === '') ? '300' : $interval;
    $outputExecutionInterval = html_print_extended_select_for_time('module_interval', $interval, '', '', '0', false, true, false, false, $classdisabledBecauseInPolicy, $disabledBecauseInPolicy);
}

$module_id_policy_module = 0;
if (isset($module['id_policy_module']) === true) {
    $module_id_policy_module = $module['id_policy_module'];
}

$cps_array[-1] = __('Disabled');
$cps_array[0] = __('Enabled');

if ($cps_module > 0) {
    $cps_array[$cps_module] = __('Enabled');
} else {
    $cps_inc = 0;
    if (isset($id_agent_module) === true && empty($id_agent_module) === false) {
        $cps_inc = enterprise_hook('service_modules_cps', [$id_agent_module]);
        if ($cps_inc === ENTERPRISE_NOT_HOOK) {
            $cps_inc = 0;
        }
    }

    if ($cps_inc > -1) {
        $cps_array[$cps_inc] = __('Enabled');
    }
}

// JS Scripts for ff thresholds.
ob_start();
?>
<script>
    $(document).ready(function(){
        ffStateChange('<?php echo (int) $each_ff; ?>');
    });

    function ffStateChange(state) {
        var type = (parseInt(state) === 0) ? 'all' : 'each';
        $('tr[id*="ff_thresholds"]').css('display', 'none');
        $('tr[id*="ff_thresholds_'+type+'"]').css('display', 'flex');
    }
</script>
<?php
$ffThresholdsScript = ob_get_clean();

// Advanced form part.
$table_advanced = new stdClass();
$table_advanced->id = 'advanced';
$table_advanced->styleTable = 'border-radius: 8px';
$table_advanced->width = '100%';
$table_advanced->class = 'w100p floating_form';
$table_advanced->data = [];
$table_advanced->style = [];
$table_advanced->rowclass = [];
$table_advanced->cellclass = [];
$table_advanced->colspan = [];
$table_advanced->rowspan = [];

$table_advanced->data['title_1'] = html_print_subtitle_table(__('Identification and Categorization'));
$table_advanced->rowclass['captions_custom_category'] = 'field_half_width pdd_t_10px';
$table_advanced->rowclass['custom_id_category'] = 'field_half_width';
$table_advanced->data['captions_custom_category'][0] = __('Custom ID');
$table_advanced->data['captions_custom_category'][1] = __('Category');

$table_advanced->data['custom_id_category'][0] = html_print_input_text(
    'custom_id',
    $custom_id,
    '',
    20,
    65,
    true,
    (($config['module_custom_id_ro'] && $__code_from !== 'policies') ? true : $disabledBecauseInPolicy),
    false,
    '',
    (($config['module_custom_id_ro'] && $__code_from !== 'policies') ? 'readonly' : $classdisabledBecauseInPolicy)
);

if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
    $table_advanced->data['custom_id_category'][1] = html_print_select(
        categories_get_all_categories('forselect'),
        'id_category',
        $id_category,
        '',
        __('None'),
        0,
        true,
        false,
        true,
        '',
        $disabledBecauseInPolicy
    );
} else {
    // Store in a hidden field if is not visible to avoid delete the value.
    $table_advanced->data['custom_id_category'][1] .= html_print_input_hidden('id_category', $id_category, true);
}

// Tags.
$table_advanced->rowclass['caption_tags_module_parent'] = 'field_half_width pdd_t_10px';
$table_advanced->rowclass['tags_module_parent'] = 'field_half_width';
$table_advanced->data['caption_tags_module_parent'][0] = __('Tags available');

$tagsAvailableData = '';
$tagsCompleteData = '';
if (tags_has_user_acl_tags($config['id_user']) === false) {
    $tagsAvailableData .= html_print_select_from_sql(
        "SELECT id_tag, name
		FROM ttag 
		WHERE id_tag NOT IN (
			SELECT a.id_tag
			FROM ttag a, $__table_modules b 
			WHERE a.id_tag = b.id_tag AND $__id_where = $__id )
			ORDER BY name",
        'id_tag_available[]',
        '',
        '',
        '',
        '',
        true,
        true,
        false,
        $disabledBecauseInPolicy,
        'width: 200px',
        '5'
    );
} else {
    $user_tags = tags_get_user_tags($config['id_user'], 'AW');
    if (empty($user_tags) === false) {
        $id_user_tags = array_keys($user_tags);

        $tagsAvailableData .= html_print_select_from_sql(
            'SELECT id_tag, name
			FROM ttag 
			WHERE id_tag IN ('.implode(',', $id_user_tags).") AND
				id_tag NOT IN (
				SELECT a.id_tag
				FROM ttag a, $__table_modules b 
				WHERE a.id_tag = b.id_tag AND $__id_where = $__id )
				ORDER BY name",
            'id_tag_available[]',
            '',
            '',
            '',
            '',
            true,
            true,
            false,
            $disabledBecauseInPolicy,
            'width: 200px',
            '5'
        );
    } else {
        $tagsAvailableData .= html_print_select_from_sql(
            "SELECT id_tag, name
			FROM ttag
			WHERE id_tag NOT IN (
				SELECT a.id_tag
				FROM ttag a, $__table_modules b
				WHERE a.id_tag = b.id_tag AND $__id_where = $__id )
				ORDER BY name",
            'id_tag_available[]',
            '',
            '',
            '',
            '',
            true,
            true,
            false,
            $disabledBecauseInPolicy,
            'width: 200px',
            '5'
        );
    }
}

$tagsAvailableData .= html_print_image(
    'images/plus.svg',
    true,
    [
        'id'    => 'right',
        'title' => __('Add tags to module'),
        'class' => 'main_menu_icon invert_filter clickable mrgn_lft_5px',
    ]
);

$tagsCompleteData = html_print_div(
    [
        'class'   => 'tags_available_container',
        'content' => $tagsAvailableData,
    ],
    true
);

$sqlGetTags = sprintf(
    'SELECT a.id_tag, name FROM ttag a, %s b WHERE a.id_tag = b.id_tag AND %s = %s %s ORDER BY name',
    $__table_modules,
    $__id_where,
    $__id,
    $__sql
);

$listSelectedTags = db_get_all_rows_sql($sqlGetTags);
if (empty($listSelectedTags) === false) {
    $listSelectedTagShow = array_reduce(
        $listSelectedTags,
        function ($carry, $item) {
            $carry[] = $item['id_tag'];
            return $carry;
        }
    );
} else {
    $listSelectedTagShow = [];
}


$tagsCompleteData .= html_print_div(
    [
        'class'   => 'tags_selected_container',
        'content' => html_print_select_from_sql(
            $sqlGetTags,
            'id_tag_selected[]',
            $listSelectedTagShow,
            '',
            '',
            '',
            true,
            true,
            false,
            $disabledBecauseInPolicy,
            'width: 200px;',
            '5'
        ),
    ],
    true
);
/*
    $tagsCompleteData .= html_print_select(
    $listSelectedTags,
    'id_tag_selected[]',
    $listSelectedTagShow,
    '',
    '',
    0,
    true,
    true,
    false,
    '',
    $disabledBecauseInPolicy,
    'width: 200px;'
    );
*/
$table_advanced->data['tags_module_parent'][0] .= html_print_div(
    [
        'class'   => 'tags_complete_container',
        'content' => $tagsCompleteData,
    ],
    true
);

if ((bool) $in_policies_page === false) {
    // Cannot select the current module to be itself parent.
    $module_parent_filter = ($id_agent_module) ? ['tagente_modulo.id_agente_modulo' => '<>'.$id_agent_module] : [];
    $table_advanced->data['caption_tags_module_parent'][1] = __('Module parent');
    // TODO. Review cause dont know not works.
    /*
        $agent = new Agent($id_agente);
        $modules_can_be_parent = $agent->searchModules(
        $module_parent_filter,
        0
        );
    */
    $modules_can_be_parent = agents_get_modules(
        $id_agente,
        false,
        $module_parent_filter
    );
    // If the user cannot have access to parent module, only print the name.
    if ((int) $parent_module_id !== 0
        && in_array($parent_module_id, array_keys($modules_can_be_parent)) === true
    ) {
        $parentModuleOutput = db_get_value(
            'nombre',
            'tagente_modulo',
            'id_agente_modulo',
            $parent_module_id
        );
    } else {
        $parentModuleOutput = html_print_select(
            $modules_can_be_parent,
            'parent_module_id',
            $parent_module_id,
            '',
            __('Not assigned'),
            '0',
            true
        );
    }

    $table_advanced->cellstyle['tags_module_parent'][1] = 'align-self: flex-start;';
    $table_advanced->data['tags_module_parent'][1] = html_print_div(
        [
            'class'   => 'parent_module_container w100p',
            'content' => $parentModuleOutput,
        ],
        true,
    );
}

$table_advanced->rowclass['caption_tags_from_policy_module_parent'] = 'field_half_width pdd_t_10px';
$table_advanced->rowclass['tags_from_policy_module_parent'] = 'field_half_width';

if ($__code_from === 'modules') {
    $table_advanced->data['caption_tags_from_policy_module_parent'][0] = __('Tags from policy');
    $table_advanced->data['tags_from_policy_module_parent'][0] = html_print_select_from_sql(
        "SELECT a.id_tag, name 
		FROM ttag a, $__table_modules b
		WHERE a.id_tag = b.id_tag AND $__id_where = $__id
			$__sql_policy
		ORDER BY name",
        'id_tag_policy[]',
        '',
        '',
        '',
        '',
        true,
        true,
        false,
        $disabledBecauseInPolicy,
        'width: 200px',
        '5'
    );

    $table_advanced->data['tags_from_policy_module_parent'][1] = '';
}

$table_advanced->rowclass['caption_textarea_description_instructions'] = 'field_half_width pdd_t_10px';
$table_advanced->rowclass['textarea_description_instructions'] = 'field_half_width';
$table_advanced->data['caption_textarea_description_instructions'][0] = __('Description');
$table_advanced->data['caption_textarea_description_instructions'][1] = __('Unknown instructions');
$table_advanced->data['textarea_description_instructions'][0] = html_print_textarea(
    'description',
    5,
    35,
    $description,
    $disabledTextBecauseInPolicy,
    true,
    $largeClassDisabledBecauseInPolicy
);
$table_advanced->data['textarea_description_instructions'][1] = html_print_textarea(
    'unknown_instructions',
    5,
    35,
    $unknown_instructions,
    $disabledTextBecauseInPolicy,
    true,
    $largeClassDisabledBecauseInPolicy
);


$table_advanced->rowclass['caption_textarea_crit_warn_instructions'] = 'field_half_width pdd_t_10px';
$table_advanced->rowclass['textarea_crit_warn_instructions'] = 'field_half_width';
$table_advanced->data['caption_textarea_crit_warn_instructions'][0] = __('Critical instructions');
$table_advanced->data['caption_textarea_crit_warn_instructions'][1] = __('Warning instructions');
$table_advanced->data['textarea_crit_warn_instructions'][0] = html_print_textarea(
    'critical_instructions',
    5,
    35,
    $critical_instructions,
    $disabledTextBecauseInPolicy,
    true,
    $largeClassDisabledBecauseInPolicy
);

$table_advanced->data['textarea_crit_warn_instructions'][1] = html_print_textarea(
    'warning_instructions',
    5,
    35,
    $warning_instructions,
    $disabledTextBecauseInPolicy,
    true,
    $largeClassDisabledBecauseInPolicy
);

$table_advanced->data['title_2'] = html_print_subtitle_table(__('Execution interval'));

$table_advanced->data['caption_execution_interval'][0] = __('Interval');
$table_advanced->data['execution_interval'][0] = '<span class="result_info_text">'.$outputExecutionInterval.'</span>';
$table_advanced->data['execution_interval'][0] .= html_print_input_hidden('moduletype', $moduletype, true);

// Cron Table.
$tableCron = new stdClass();
$tableCron->class = 'w100p';
$tableCron->id = 'advanced_cron';
$tableCron->style = [];
$tableCron->rowclass = [];
$tableCron->data = [];

// Cron table styles.
$tableCron->cellstyle['cron_from_select'][0] = 'padding: 0; margin: 0 -4px; width: 100%;';
$tableCron->cellstyle['cron_to_select'][0] = 'padding: 0; margin: 0 -4px; width: 100%;';

if (isset($id_agente) === true && (int) $moduletype === MODULE_DATA) {
    $has_remote_conf = enterprise_hook('config_agents_has_remote_configuration', [$agent['id_agente']]);
    $tableCron->data['caption_cron_from_select'][0] = __('Cron from');
    $tableCron->data['cron_from_select'][0] = html_print_extended_select_for_cron($hour_from, $minute_from, $mday_from, $month_from, $wday_from, true, ((bool) $has_remote_conf === true) ? $disabledBecauseInPolicy : true);

    $tableCron->data['caption_cron_to_select'][0] = __('Cron to');
    $tableCron->data['cron_to_select'][0] = html_print_extended_select_for_cron($hour_to, $minute_to, $mday_to, $month_to, $wday_to, true, ((bool) $has_remote_conf === true) ? $disabledBecauseInPolicy : true, true);
} else {
    $tableCron->data['caption_cron_from_select'][0] = __('Cron from');
    $tableCron->data['cron_from_select'][0] = html_print_extended_select_for_cron($hour_from, $minute_from, $mday_from, $month_from, $wday_from, true, $disabledBecauseInPolicy);

    $tableCron->data['caption_cron_to_select'][0] = __('Cron to');
    $tableCron->data['cron_to_select'][0] = html_print_extended_select_for_cron($hour_to, $minute_to, $mday_to, $month_to, $wday_to, true, $disabledBecauseInPolicy, true);
}

$table_advanced->rowclass['cron_section'] = 'table_section full_section';
$table_advanced->data['cron_section'] = html_print_table($tableCron, true);

$table_advanced->data['title_3'] = html_print_subtitle_table(__('Thresholds and state changes'));

$table_advanced->rowclass['caption_min_max_values'] = 'w50p pdd_t_10px';
$table_advanced->rowclass['min_max_values'] = 'w50p';
$table_advanced->data['caption_min_max_values'][0] = __('Min. Value');
$table_advanced->data['caption_min_max_values'][1] = __('Max. Value');

$table_advanced->data['min_max_values'][0] = html_print_input_text('min', $min, '', 5, 15, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy.' w100p');
$table_advanced->data['min_max_values'][1] = html_print_input_text('max', $max, '', 5, 15, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy.' w100p');

// Dynamic Threholds.
$tableDynamicThreshold = new stdClass();
$tableDynamicThreshold->class = 'w100p';
$tableDynamicThreshold->id = 'advanced_dynamic';
$tableDynamicThreshold->style = [];
$tableDynamicThreshold->rowclass = [];
$tableDynamicThreshold->data = [];

$tableDynamicThreshold->data['caption_dynamic_threshold_interval'][0] = __('Dynamic Threshold Interval');
$tableDynamicThreshold->rowclass['dynamic_threshold_interval'] = 'w540px';
$tableDynamicThreshold->data['dynamic_threshold_interval'][0] = html_print_extended_select_for_time(
    'dynamic_interval',
    $dynamic_interval,
    '',
    __('None'),
    '0',
    10,
    true,
    '',
    false,
    $classdisabledBecauseInPolicy.' w50p',
    $disabledBecauseInPolicy
);

$tableDynamicThreshold->rowclass['caption_adv_dynamic_threshold_interval'] = 'pdd_t_10px w100p';
$tableDynamicThreshold->rowclass['adv_dynamic_threshold_interval'] = 'w100p';
$tableDynamicThreshold->cellclass['caption_adv_dynamic_threshold_interval'][0] = 'w33p';
$tableDynamicThreshold->cellclass['caption_adv_dynamic_threshold_interval'][1] = 'w33p';
$tableDynamicThreshold->cellclass['adv_dynamic_threshold_interval'][0] = 'w33p';
$tableDynamicThreshold->cellclass['adv_dynamic_threshold_interval'][1] = 'w33p';
$tableDynamicThreshold->data['caption_adv_dynamic_threshold_interval'][0] = __('Min.');
$tableDynamicThreshold->data['caption_adv_dynamic_threshold_interval'][1] = __('Max.');
$tableDynamicThreshold->data['adv_dynamic_threshold_interval'][0] = html_print_input_text(
    'dynamic_min',
    $dynamic_min,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy.' w100p'
);
$tableDynamicThreshold->data['adv_dynamic_threshold_interval'][1] = html_print_input_text(
    'dynamic_max',
    $dynamic_max,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy.' w100p'
);

$tableDynamicThreshold->rowclass['caption_adv_dynamic_threshold_twotailed'] = 'pdd_t_10px w100p';
$tableDynamicThreshold->rowclass['adv_dynamic_threshold_twotailed'] = 'w100p';
$tableDynamicThreshold->cellclass['caption_adv_dynamic_threshold_twotailed'][0] = 'w33p';
$tableDynamicThreshold->cellclass['adv_dynamic_threshold_twotailed'][0] = 'w33p';
$tableDynamicThreshold->data['caption_adv_dynamic_threshold_twotailed'][0] = __('Two Tailed');
$tableDynamicThreshold->data['adv_dynamic_threshold_twotailed'][0] = html_print_checkbox_switch(
    'dynamic_two_tailed',
    1,
    $dynamic_two_tailed,
    true,
    $disabledBecauseInPolicy
);

$table_advanced->rowclass['dynamic_threshold_table'] = 'table_section full_section';
$table_advanced->data['dynamic_threshold_table'] = html_print_table($tableDynamicThreshold, true);

$tableFFThreshold = new stdClass();
$tableFFThreshold->class = 'w100p';
$tableFFThreshold->id = 'advanced_flipflop';
$tableFFThreshold->style = [];
$tableFFThreshold->rowclass = [];
$tableFFThreshold->data = [];
// FF stands for Flip-flop.
$tableFFThreshold->data['caption_ff_main_thresholds'][0] = __('FF threshold');
$tableFFThreshold->rowclass['ff_main_thresholds'] = 'w100p';
$tableFFThreshold->data['ff_main_thresholds'][0] = html_print_switch_radio_button(
    [
        html_print_radio_button_extended('each_ff', 0, __('All state changing'), $each_ff, false, 'ffStateChange(0)', '', true, false, '', 'ff_all_state'),
        html_print_radio_button_extended('each_ff', 1, __('Each state changing'), $each_ff, false, 'ffStateChange(1)', '', true, false, '', 'ff_each_state'),
    ],
    [ 'add_content' => $ffThresholdsScript ],
    true
);

$tableFFThreshold->rowclass['caption_ff_thresholds_all'] = 'w50p ff_thresholds_line pdd_t_10px';
$tableFFThreshold->rowclass['ff_thresholds_all'] = 'w50p ff_thresholds_line';
$tableFFThreshold->cellclass['caption_ff_thresholds_all'][0] = 'w50p';
$tableFFThreshold->cellclass['ff_thresholds_all'][0] = 'w50p';
$tableFFThreshold->data['caption_ff_thresholds_all'][0] = __('Change all states');
$tableFFThreshold->data['ff_thresholds_all'][0] = html_print_input_text(
    'ff_event',
    $ff_event,
    '',
    5,
    15,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);

$tableFFThreshold->rowclass['caption_ff_thresholds_each'] = 'w50p ff_thresholds_line pdd_t_10px';
$tableFFThreshold->rowclass['ff_thresholds_each'] = 'w50p ff_thresholds_line';
$tableFFThreshold->cellclass['caption_ff_thresholds_each'][0] = 'w33p';
$tableFFThreshold->cellclass['caption_ff_thresholds_each'][1] = 'w33p';
$tableFFThreshold->cellclass['caption_ff_thresholds_each'][2] = 'w33p';
$tableFFThreshold->cellclass['ff_thresholds_each'][0] = 'w33p';
$tableFFThreshold->cellclass['ff_thresholds_each'][1] = 'w33p';
$tableFFThreshold->cellclass['ff_thresholds_each'][2] = 'w33p';
$tableFFThreshold->data['caption_ff_thresholds_each'][0] = __('To normal');
$tableFFThreshold->data['caption_ff_thresholds_each'][1] = __('To warning');
$tableFFThreshold->data['caption_ff_thresholds_each'][2] = __('To critical');

$tableFFThreshold->data['ff_thresholds_each'][0] = html_print_input_text(
    'ff_event_normal',
    $ff_event_normal,
    '',
    5,
    15,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);

$tableFFThreshold->data['ff_thresholds_each'][1] = html_print_input_text(
    'ff_event_warning',
    $ff_event_warning,
    '',
    5,
    15,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);

$tableFFThreshold->data['ff_thresholds_each'][2] = html_print_input_text(
    'ff_event_critical',
    $ff_event_critical,
    '',
    5,
    15,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);


$table_advanced->rowclass['flipflop_thresholds_table'] = 'table_section full_section';
$table_advanced->data['flipflop_thresholds_table'] = html_print_table($tableFFThreshold, true);

$table_advanced->rowclass['caption_ff_interval_timeout'] = 'w50p';
$table_advanced->rowclass['ff_interval_timeout'] = 'w50p';
$table_advanced->cellclass['caption_ff_interval_timeout'][0] = 'w50p';
$table_advanced->cellclass['caption_ff_interval_timeout'][1] = 'w50p';
$table_advanced->cellclass['ff_interval_timeout'][0] = 'w50p';
$table_advanced->cellclass['ff_interval_timeout'][1] = 'w50p';
$table_advanced->data['caption_ff_interval_timeout'][0] = __('FF interval');
$table_advanced->data['caption_ff_interval_timeout'][1] = __('FF timeout');
$table_advanced->data['ff_interval_timeout'][0] = html_print_input_text(
    'module_ff_interval',
    $ff_interval,
    '',
    5,
    10,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy.' w100p'
);

$module_type_name = modules_get_type_name($id_module_type);
$table_advanced->data['ff_interval_timeout'][1] = '';
if ((bool) preg_match('/async/', $module_type_name) === true || $edit === true) {
    $table_advanced->data['ff_interval_timeout'][1] .= '<span id="ff_timeout" class="result_info_text">'.html_print_input_text(
        'ff_timeout',
        $ff_timeout,
        '',
        5,
        10,
        true,
        $disabledBecauseInPolicy
    ).'</span>';
}

if ((bool) preg_match('/async/', $module_type_name) === false || $edit === true) {
    $table_advanced->data['ff_interval_timeout'][1] .= '<span id="ff_timeout_disable" class="result_info_text">'.__('Disabled').'</span>';
}

$table_advanced->data['caption_ff_keep_counters'][0] = __('Keep counters');
$table_advanced->data['ff_keep_counters'][0] = html_print_checkbox_switch(
    'ff_type',
    1,
    $ff_type,
    true,
    $disabledBecauseInPolicy
);

$table_advanced->data['title_4'] = html_print_subtitle_table(__('Data and their processing'));

$table_advanced->rowclass['caption_process_unit'] = 'w50p';
$table_advanced->rowclass['process_unit'] = 'w50p';
$table_advanced->data['caption_process_unit'][0] = __('Unit');
$table_advanced->data['caption_process_unit'][1] = __('Post process');
$table_advanced->data['process_unit'][0] = html_print_extended_select_for_unit(
    'unit',
    $unit,
    '',
    'none',
    '0',
    false,
    true,
    'width: 100%',
    false
);
$table_advanced->data['process_unit'][1] = html_print_extended_select_for_post_process(
    'post_process',
    $post_process,
    '',
    '',
    '0',
    false,
    true,
    'width:10em',
    false,
    $disabledBecauseInPolicy
);

$table_advanced->data['title_5'] = html_print_subtitle_table(__('Notifications and alerts'));

$table_advanced->data['caption_export_target'][0] = __('Export target');
if ($__code_from === 'policies') {
    $none_text = __('Not needed');
    $disabled_export = true;
} else {
    $none_text = __('None');
    $disabled_export = false;
}

$table_advanced->data['export_target'][0] = html_print_select_from_sql(
    'SELECT id, name FROM tserver_export ORDER BY name',
    'id_export',
    $id_export,
    '',
    $none_text,
    '0',
    true,
    false,
    false,
    $disabled_export
);
// Code comes from module_editor.
if ($__code_from === 'modules') {
    $throw_unknown_events_check = modules_is_disable_type_event($id_agent_module, EVENTS_GOING_UNKNOWN);
} else {
    global $__id_pol_mod;

    $throw_unknown_events_check = policy_module_is_disable_type_event($__id_pol_mod, EVENTS_GOING_UNKNOWN);
}

$table_advanced->data['caption_discard_unknown'][0] = __('Discard unknown events');
$table_advanced->data['discard_unknown'][0] = html_print_checkbox_switch(
    'throw_unknown_events',
    1,
    $throw_unknown_events_check,
    true,
    $disabledBecauseInPolicy
);

$table_advanced->data['caption_quiet'][0] = __('Quiet');
$table_advanced->data['quiet'][0] = html_print_checkbox_switch(
    'quiet_module',
    1,
    $quiet_module,
    true,
    $disabledBecauseInPolicy
);

$table_advanced->data['caption_cascade_protection'][0] = __('Cascade Protection Services');
$table_advanced->data['cascade_protection'][0] = html_print_select($cps_array, 'cps_module', $cps_module, '', '', 0, true, false, true, '', $disabledBecauseInPolicy);


$table_advanced->rowclass['caption_max_timeout_retries'] = 'w50p';
$table_advanced->rowclass['max_timeout_retries'] = 'w50p';
$table_advanced->data['caption_max_timeout_retries'][0] = __('Timeout');
$table_advanced->data['caption_max_timeout_retries'][1] = __('Retries');
$table_advanced->data['max_timeout_retries'][0] = html_print_input_text('max_timeout', $max_timeout, '', 5, 10, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy.' w100p');
$table_advanced->data['max_timeout_retries'][1] = html_print_input_text('max_retries', $max_retries, '', 5, 10, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy.' w100p');

// Advanced form part.
$table_macros = new stdClass();
$table_macros->id = 'module_macros';
$table_macros->width = '100%';
$table_macros->class = 'no-class';
$table_macros->data = [];
$table_macros->style = [];
$table_macros->style[0] = 'font-weight: bold;';
$table_macros->style[2] = 'font-weight: bold;';
$table_macros->style[5] = 'font-weight: bold;';
$table_macros->colspan = [];

$macro_count = 0;
if (isset($module_macros)) {
    if (is_array($module_macros)) {
        foreach ($module_macros as $macro_name => $macro_value) {
            $table_macros->data[$macro_count][0] = __('Name');
            $table_macros->data[$macro_count][1] = html_print_input_text('module_macro_names[]', $macro_name, '', 50, 60, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);
            $table_macros->data[$macro_count][2] = __('Value');
            $table_macros->data[$macro_count][3] = html_print_input_text('module_macro_values[]', $macro_value, '', 50, 60, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);
            if (!$disabledBecauseInPolicy) {
                $table_macros->data[$macro_count][4] = '<a href="javascript: delete_macro('.$macro_count.');">'.html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']).'</a>';
            }

            $macro_count++;
        }
    }
}

if (!$disabledBecauseInPolicy) {
    $table_macros->data[$macro_count][0] = '<span>'.__('Custom macros').'</span> <a href="javascript:add_macro();">'.html_print_image('images/fail@svg.svg', true, ['style' => 'rotate:45deg', 'class' => 'main_menu_icon invert_filter']).'</a>';

    $table_macros->colspan[$macro_count][0] = 5;
}

$macro_count++;

html_print_input_hidden('module_macro_count', $macro_count);

// Advanced form part.
// Add relationships.
$table_new_relations = new stdClass();
$table_new_relations->id = 'module_new_relations';
$table_new_relations->width = '100%';
$table_new_relations->class = 'filter-table-adv';
$table_new_relations->data = [];
$table_new_relations->style = [];
$table_new_relations->size[0] = '33%';
$table_new_relations->size[1] = '33%';
$table_new_relations->size[2] = '33%';

$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'autocomplete_agent_name';
$params['helptip_text'] = '';
$params['use_hidden_input_idagent'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_id'] = 'hidden-autocomplete_id_agent';
$params['javascript_function_action_after_select_js_call'] = 'change_modules_autocomplete_input();';
$table_new_relations->data[0][0] = html_print_label_input_block(
    __('Agent'),
    ui_print_agent_autocomplete_input($params)
);


$table_new_relations->data[0][1] = html_print_label_input_block(
    __('Module'),
    '<div id="module_autocomplete">'.html_print_input_text('', '', '', false, 255, true, true, false, '', 'w100p').'</div>'
);

$array_rel_type = [];
$array_rel_type['direct'] = __('Direct');
$array_rel_type['failover'] = __('Failover');
$table_new_relations->data[0][2] = html_print_label_input_block(
    __('Rel. type'),
    html_print_select(
        $array_rel_type,
        'relation_type',
        '',
        '',
        '',
        0,
        true,
        false,
        true,
        'w100p',
        false,
        'width:100%'
    )
);

$table_new_relations->data[1][0] = ' ';
$table_new_relations->data[1][1] = ' ';
$table_new_relations->cellstyle[1][2] = 'width:100% !important;';
$table_new_relations->cellclass[1][2] = 'flex flex-end';
$table_new_relations->data[1][2] = "<div id='add_relation_status' class='inline_line' style='margin-right:10px'></div>".html_print_button(
    __('Add relationship')."<div id='add_relation_status'></div>",
    'add_relation',
    false,
    'add_new_relation();',
    [
        'class' => 'mini',
        'icon'  => 'next',
        'mode'  => 'secondary',
        'style' => 'margin-top: 10px; margin-right: 10px',
    ],
    true
);

// Relationship list.
$table_relations = new stdClass();
$table_relations->id = 'module_relations';
$table_relations->width = '100%';
$table_relations->class = 'info_table';
$table_relations->styleTable = 'border: none';
$table_relations->head = [];
$table_relations->data = [];
$table_relations->rowstyle[-1] = 'display: none;';

$table_relations->head[0] = __('Agent');
$table_relations->head[1] = __('Module');
$table_relations->head[2] = __('Type');
$table_relations->head[3] = __('Changes');
$table_relations->head[4] = __('Delete');
$table_relations->headclass[0] = 'w20p';
$table_relations->headclass[1] = 'w20p';
$table_relations->headclass[2] = 'w20p';
$table_relations->headclass[3] = 'w20p';
$table_relations->headclass[4] = 'w20p';
$table_relations->style[0] = 'width:20%';
$table_relations->style[1] = 'width:20%';
$table_relations->style[2] = 'width:20%';
$table_relations->style[3] = 'width:20%';
$table_relations->style[4] = 'width:20%';

// Create an invisible row to use their html to add new rows.
$table_relations->data[-1][0] = '';
$table_relations->data[-1][1] = '';
$table_relations->data[-1][2] = '';
$table_relations->data[-1][3] = '<a id="disable_updates_button" class="alpha50" href="">';
$table_relations->data[-1][3] .= html_print_image(
    'images/policy@svg.svg',
    true,
    ['class' => 'main_menu_icon invert_filter']
).'</a>';
$table_relations->data[-1][4] = '<a id="delete_relation_button" href="">';
$table_relations->data[-1][4] .= html_print_image(
    'images/delete.svg',
    true,
    ['class' => 'main_menu_icon invert_filter']
).'</a>';


$relations_count = 0;
if ($id_agent_module) {
    $module_relations = modules_get_relations(
        ['id_module' => $id_agent_module]
    );

    if (!$module_relations) {
        $module_relations = [];
    }

    $relations_count = 0;
    foreach ($module_relations as $key => $module_relation) {
        // Styles.
        $table_relations->cellclass[$relations_count][4] = 'table_action_buttons';

        if ($module_relation['module_a'] == $id_agent_module) {
            $module_id = $module_relation['module_b'];
            $agent_id = modules_give_agent_id_from_module_id(
                $module_relation['module_b']
            );
        } else {
            $module_id = $module_relation['module_a'];
            $agent_id = modules_give_agent_id_from_module_id(
                $module_relation['module_a']
            );
        }

        $agent_name = ui_print_agent_name($agent_id, true);

        $module_name = modules_get_agentmodule_name($module_id);
        if (empty($module_name) || $module_name == 'false') {
            $module_name = $module_id;
        }

        if ($module_relation['disable_update']) {
            $disabled_update_class = '';
        } else {
            $disabled_update_class = 'alpha50';
        }

        // Agent name.
        $table_relations->data[$relations_count][0] = $agent_name;
        // Module name.
        $table_relations->data[$relations_count][1] = "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$agent_id.'&tab=module&edit_module=1&id_agent_module='.$module_id."'>".ui_print_truncate_text(
            $module_name,
            'module_medium',
            true,
            true,
            true,
            '[&hellip;]'
        ).'</a>';
        // Type.
        $table_relations->data[$relations_count][2] = ($module_relation['type'] === 'direct') ? __('Direct') : __('Failover');
        // Lock relationship updates.
        $table_relations->data[$relations_count][3] = '<a id="disable_updates_button" class="'.$disabled_update_class.'"href="javascript: change_lock_relation('.$relations_count.', '.$module_relation['id'].');">'.html_print_image(
            'images/policy@svg.svg',
            true,
            ['class' => 'main_menu_icon invert_filter']
        ).'</a>';
        // Delete relationship.
        $table_relations->data[$relations_count][4] = '<a id="delete_relation_button" href="javascript: delete_relation('.$relations_count.', '.$module_relation['id'].');">'.html_print_image(
            'images/delete.svg',
            true,
            ['class' => 'main_menu_icon invert_filter']
        ).'</a>';
        $relations_count++;
    }
}

html_print_input_hidden('module_relations_count', $relations_count);

ui_require_jquery_file('json');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
    var disabledBecauseInPolicy = <?php echo '\''.((empty($disabledBecauseInPolicy) === true) ? '0' : '1').'\''; ?>;
    var idModuleType  = '<?php echo $type_names_hash[$id_module_type]; ?>';
    if (idModuleType != '') {
        setModuleType(idModuleType);
    }

    $("#right").click (function () {
        jQuery.each($("select[name='id_tag_available[]'] option:selected"), function (key, value) {
            tag_name = $(value).html();
            if (tag_name != <?php echo "'".__('None')."'"; ?>) {
                $("select[name='id_tag_selected[]']").append(value);
                $("#id_tag_available").find("option[value='" + tag_name + "']").remove();
                $("#id_tag_selected").find("option[value='']").remove();
                if($("#id_tag_available option").length == 0) {
                    $("select[name='id_tag_available[]']").append(
                        $("<option></option>").val('').html(
                            '<i><?php echo __('None'); ?></i>'
                        )
                    );
                }
            }
        });
    });

    $("#left").click (function () {
        jQuery.each($("select[name='id_tag_selected[]'] option:selected"), function (key, value) {
                tag_name = $(value).html();
                if (tag_name != <?php echo "'".__('None')."'"; ?>) {
                    id_tag = $(value).attr('value');
                    $("select[name='id_tag_available[]']").append(value);
                    $("#id_tag_selected").find("option[value='" + id_tag + "']").remove();
                    $("#id_tag_available").find("option[value='']").remove();
                    if($("#id_tag_selected option").length == 0) {
                        $("select[name='id_tag_selected[]']").append(
                            $("<option></option>").val('').html(
                                '<i><?php echo __('None'); ?></i>'
                            )
                        );
                    }
                }
        });
    });
    
    $("#submit-updbutton").click(function () {
        $('#id_tag_selected option').map(function() {
            $(this).prop('selected', true);
        });
    });
    
    $("#submit-crtbutton").click(function () {
        $('#id_tag_selected option').map(function() {
            $(this).prop('selected', true);
        });
    });
    
    $("#id_module_type").change(function () {
        var type_selected = $(this).val();
        var type_names = jQuery.parseJSON(Base64.decode($('#hidden-type_names').val()));
        
        var type_name_selected = type_names[type_selected];
        var element = document.getElementById("module_type_help");
        var language =  "<?php echo $config['language']; ?>" ;
        if (typeof element !== 'undefined' && element !== null) {
            element.onclick = function (event) {
                if(type_name_selected == 'async_data' ||
                 type_name_selected == 'async_proc' ||
                 type_name_selected == 'async_string' ||
                 type_name_selected == 'generic_proc'||
                 type_name_selected == 'generic_data' ||
                 type_name_selected == 'generic_data_inc' ||
                 type_name_selected == 'generic_data_inc_abs'||
                 type_name_selected == 'generic_data_string' ||
                 type_name_selected == 'keep_alive'
                   ){
                    if (language == 'es'){
                     window.open(
                         'https://pandorafms.com/manual/es/documentation/03_monitoring/02_operations#tipos_de_modulos',
                         '_blank',
                         'width=800,height=600'
                            );
                   }
                   else{
                    window.open(
                        'https://pandorafms.com/manual/en/documentation/03_monitoring/02_operations#types_of_modules',
                         '_blank',
                         'width=800,height=600'
                         );
                   }
                  
                    
                }
                if(type_name_selected == 'remote_icmp' ||
                 type_name_selected == 'remote_icmp_proc'
                 ){
                     if(language == 'es'){
                        window.open(
                        'https://pandorafms.com/manual/es/documentation/03_monitoring/03_remote_monitoring#monitorizacion_icmp',
                         '_blank',
                         'width=800,height=600'
                         );
                     }
                     else{
                        window.open(
                        'https://pandorafms.com/manual/en/documentation/03_monitoring/03_remote_monitoring#icmp_monitoring',
                         '_blank',
                         'width=800,height=600'
                         );
                     }
                  
                    
                }
                if(type_name_selected == 'remote_snmp_string' ||
                 type_name_selected == 'remote_snmp_proc' ||
                 type_name_selected == 'remote_snmp_inc' ||
                 type_name_selected == 'remote_snmp'
                 ){
                     if(language == 'es'){
                        window.open(
                        'https://pandorafms.com/manual/es/documentation/03_monitoring/03_remote_monitoring#monitorizando_con_modulos_de_red_tipo_snmp',
                         '_blank',
                         'width=800,height=600'
                         );
                     }
                     else{
                        window.open(
                        'https://pandorafms.com/manual/en/documentation/03_monitoring/03_remote_monitoring#monitoring_through_network_modules_with_snmp',
                         '_blank',
                         'width=800,height=600'
                         );
                     }
                }

                if(type_name_selected == 'remote_tcp_string' ||
                 type_name_selected == 'remote_tcp_proc' ||
                 type_name_selected == 'remote_tcp_inc' ||
                 type_name_selected == 'remote_tcp'
                   ){
                       if(language == 'es'){
                        window.open(
                        'https://pandorafms.com/manual/es/documentation/03_monitoring/03_remote_monitoring#monitorizacion_tcp',
                         '_blank',
                         'width=800,height=600'
                         );
                       }
                       else{
                        window.open(
                        'https://pandorafms.com/manual/en/documentation/03_monitoring/03_remote_monitoring#tcp_monitoring',
                         '_blank',
                         'width=800,height=600'
                         );
                       }
                }
                if(type_name_selected == 'web_data' ||
                 type_name_selected == 'web_proc' ||
                 type_name_selected == 'web_content_data' ||
                 type_name_selected == 'web_content_string'
                   ){
                       if(language == 'es'){
                        window.open(
                        'https://pandorafms.com/manual/es/documentation/03_monitoring/06_web_monitoring#creacion_de_modulos_web',
                         '_blank',
                         'width=800,height=600'
                         );
                       }
                       else{
                        window.open(
                        'https://pandorafms.com/manual/en/documentation/03_monitoring/06_web_monitoring#creating_web_modules',
                         '_blank',
                         'width=800,height=600'
                         );
                       }
                }
            }
        }

        setModuleType(type_name_selected);
    });

    function setModuleType(type_name_selected) {
        if (type_name_selected.match(/_string$/) == null) {
            // Hide string fields.
            $('[id*=str_warning]').hide();
            $('[id*=str_critical]').hide();
            $('[id*=switch_warning_inverse_string]').hide();
            $('[id*=switch_critical_inverse_string]').hide();
            // Show numeric fields.
            $('[id*=switch_warning_threshold]').show();
            $('[id*=switch_critical_threshold]').show();
            $('#caption_minmax_warning').show();
            $('#caption_minmax_critical').show();
            $('#text-min_warning').show();
            $('#text-min_critical').show();
            $('#text-max_warning').show();
            $('#text-max_critical').show();
            $('#percentage_warning').show();
            $('#percentage_critical').show();
            // Show dinamic reference.
            $('#svg_dinamic').show();
        }
        else {
            // Show string fields.
            $('[id*=str_warning]').show();
            $('[id*=str_critical]').show();
            $('[id*=switch_warning_inverse_string]').show();
            $('[id*=switch_critical_inverse_string]').show();
            // Hide numeric fields.
            $('[id*=switch_warning_threshold]').hide();
            $('[id*=switch_critical_threshold]').hide();
            $('#caption_minmax_warning').hide();
            $('#caption_minmax_critical').hide();
            $('#text-min_warning').hide();
            $('#text-min_critical').hide();
            $('#text-max_warning').hide();
            $('#text-max_critical').hide();
            $('#percentage_warning').hide();
            $('#percentage_critical').hide();
            // Hide dinamic reference.
            $('#svg_dinamic').hide();
        }

        if (type_name_selected.match(/async/) == null) {
            $('#ff_timeout').hide();
            $('#ff_timeout_disable').show();
        }
        else {
            $('#ff_timeout').show();
            $('#ff_timeout_disable').hide();
        }
    }

    $("#id_module_type").trigger('change');

    // Prevent the form submission when the user hits the enter button from the relationship autocomplete inputs
    $("#text-autocomplete_agent_name").keydown(function(event) {
        if(event.keyCode == 13) { // key code 13 is the enter button
            event.preventDefault();
        }
    });

    //validate post_process. Change ',' by '.'
    $("#submit-updbutton").click (function () {
        validate_post_process();
    });
    $("#submit-crtbutton").click (function () {
        validate_post_process();
    });

    //Dynamic_interval;
    disabled_status(disabledBecauseInPolicy);
    $('#dynamic_interval_select').change (function() {
        disabled_status(disabledBecauseInPolicy);
    });
    $('#dynamic_interval').change (function() {
        disabled_status(disabledBecauseInPolicy);
    });

    disabled_two_tailed(disabledBecauseInPolicy);

    //Dynamic_options_advance;
    $('.hide_dinamic').hide();

    //paint graph stutus critical and warning:
    paint_graph_values();
    $('#text-min_warning').on ('input', function() {
        paint_graph_values();
        if (isNaN($('#text-min_warning').val()) && !($('#text-min_warning').val() == "-")){
            $('#text-min_warning').val(0);
        }
    });
    $('#text-max_warning').on ('input', function() {
        paint_graph_values();
        if (isNaN($('#text-max_warning').val()) && !($('#text-max_warning').val() == "-")){
            $('#text-max_warning').val(0);
        }
    });
    $('#text-min_critical').on ('input', function() {
        paint_graph_values();
        if (isNaN($('#text-min_critical').val()) && !($('#text-min_critical').val() == "-")){
            $('#text-min_critical').val(0);
        }
    });
    $('#text-max_critical').on ('input', function() {
        paint_graph_values();
        if (isNaN($('#text-max_critical').val()) && !($('#text-max_critical').val() == "-")){
            $('#text-max_critical').val(0);
        }
    });

    $('.switch_radio_button label').on('click', function(){
        var thisLabel = $(this).attr('for');
        $('#'+thisLabel).prop('checked', true);
        $('#'+thisLabel).siblings().prop('checked', false);

        if ($('#radius-percentage_warning').prop('checked') === true || $('#radius-percentage_critical').prop('checked') === true) {
            $("#svg_dinamic").hide();
        } else {
            paint_graph_values();
            $("#svg_dinamic").show();
        }

        if ($('#radius-percentage_warning').prop('checked') === true) {
            $('#radius-warning_inverse').hide();
            $('#label-radius-warning_inverse').hide();
        }

        if ($('#radius-warning_inverse').prop('checked') === true) {
            $('#radius-percentage_warning').hide();
            $('#label-radius-percentage_warning').hide();
        }

        if ($('#radius-normal_warning').prop('checked') === true) {
            $('#radius-warning_inverse').show();
            $('#label-radius-warning_inverse').show();
            $('#radius-percentage_warning').show();
            $('#label-radius-percentage_warning').show();
        }


        if ($('#radius-percentage_critical').prop('checked') === true) {
            $('#radius-critical_inverse').hide();
            $('#label-radius-critical_inverse').hide();
        }

        if ($('#radius-critical_inverse').prop('checked') === true) {
            $('#radius-percentage_critical').hide();
            $('#label-radius-percentage_critical').hide();
        }

        if ($('#radius-normal_critical').prop('checked') === true) {
            $('#radius-critical_inverse').show();
            $('#label-radius-critical_inverse').show();
            $('#radius-percentage_critical').show();
            $('#label-radius-percentage_critical').show();
        }
    });



});

//readonly and add class input
function disabled_status (disabledBecauseInPolicy) {
    var dynamic_interval_select_value = $('#dynamic_interval_select').val();
    var dynamic_interval_value = $('#dynamic_interval_select').val();
    if(typeof dynamic_interval_select_value != "undefined" && typeof dynamic_interval_value != "undefined"
        && dynamic_interval_select_value != 0 && dynamic_interval_value != 0){
        $('#text-min_warning').prop('readonly', true);
        $('#text-min_warning').addClass('readonly');
        $('#text-max_warning').prop('readonly', true);
        $('#text-max_warning').addClass('readonly');
        $('#text-min_critical').prop('readonly', true);
        $('#text-min_critical').addClass('readonly');
        $('#text-max_critical').prop('readonly', true);
        $('#text-max_critical').addClass('readonly');
    } else {
        if (disabledBecauseInPolicy == 0){
            $('#text-min_warning').prop('readonly', false);
            $('#text-min_warning').removeClass('readonly');
            $('#text-max_warning').prop('readonly', false);
            $('#text-max_warning').removeClass('readonly');
            $('#text-min_critical').prop('readonly', false);
            $('#text-min_critical').removeClass('readonly');
            $('#text-max_critical').prop('readonly', false);
            $('#text-max_critical').removeClass('readonly');
        }
    }
}

function disabled_two_tailed (disabledBecauseInPolicy) {
    if (disabledBecauseInPolicy == 1){
            $('#text-dynamic_max')
                .prop('readonly', true)
                .addClass('readonly');
    }
}

//Dynamic_options_advance;
function advanced_option_dynamic() {
    if($('.hide_dinamic').is(":visible")){
        $('.hide_dinamic').hide();

    } else {
        $('.hide_dinamic').show();
    }
}

/* Relationship javascript */

// Change the modules autocomplete input depending on the result of the agents autocomplete input
function change_modules_autocomplete_input () {
    var id_agent = parseInt($("#hidden-autocomplete_id_agent").val());
    var module_autocomplete = $("#module_autocomplete");
    var load_icon = '<?php html_print_image('images/spinner.gif', false); ?>';
    var error_icon = '<?php html_print_image('images/error-red@svg.svg', false, ['class' => 'main_menu_icon invert_filter']); ?>';
    if (!module_autocomplete.hasClass('working')) {
        module_autocomplete.addClass('working');
        module_autocomplete.html(load_icon);
        $.ajax({
            type: "POST",
            url: "ajax.php",
            dataType: "html",
            data: {
                page: "include/ajax/module",
                get_module_autocomplete_input: true,
                id_agent: id_agent
            },
            success: function (data) {
                module_autocomplete.removeClass('working');
                if (data) {
                    module_autocomplete.html(data);
                    // Prevent the form submission when the user hits the enter button from the relationship autocomplete inputs
                    $("#text-autocomplete_module_name").keydown(function(event) {
                        if(event.keyCode == 13) { // key code 13 is the enter button
                            event.preventDefault();
                        }
                    });
                }
                else {
                    module_autocomplete.html(error_icon);
                }
                $('#text-autocomplete_module_name').addClass('w90p');
            },
            error: function (data) {
                module_autocomplete.removeClass('working');
                module_autocomplete.html(error_icon);
            }
        });
    }
}

// Add a new relation
function add_new_relation () {
    var module_a_id = parseInt(
        $("#hidden-id_agent_module").val()
    );
    var module_b_id = parseInt(
        $("#hidden-autocomplete_module_name_hidden").val()
    );
    var module_b_name = $("#text-autocomplete_module_name").val();
    var agent_b_name = $("#text-autocomplete_agent_name").val();
    var relation_type = $("#relation_type").val();
    var hiddenRow = $("#module_relations--1");
    var button = $("#button-add_relation");
    var iconPlaceholder = $("#add_relation_status");
    var load_icon = '<?php html_print_image('images/spinner.gif', false, ['style' => 'vertical-align:middle;']); ?>';
    var suc_icon = '<?php html_print_image('images/validate.svg', false, ['class' => 'main_menu_icon invert_filter', 'style' => 'vertical-align:middle;']); ?>';
    var error_icon = '<?php html_print_image('images/error-red@svg.svg', false, ['class' => 'main_menu_icon invert_filter', 'style' => 'vertical-align:middle;']); ?>';

    if (!button.hasClass('working')) {
        button.addClass('working');
        iconPlaceholder.html(load_icon);

        $.ajax({
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            data: {
                page: "include/ajax/module",
                add_module_relation: true,
                id_module_a: module_a_id,
                id_module_b: module_b_id,
                name_module_b: module_b_name,
                relation_type: relation_type
            },
            success: function (data) {
                button.removeClass('working');
                if (data === false) {
                    iconPlaceholder.html(error_icon);
                    setTimeout( function() { iconPlaceholder.html(''); }, 2000);
                }
                else {
                    iconPlaceholder.html(suc_icon);
                    setTimeout( function() { iconPlaceholder.html(''); }, 2000);

                    // Add the new row
                    var relationsCount = parseInt($("#hidden-module_relations_count").val());

                    var rowClass = "datos";
                    if (relationsCount % 2 != 0) {
                        rowClass = "datos2";
                    }

                    var rowHTML = '<tr id="module_relations-' + relationsCount + '" class="' + rowClass + '">' +
                                    '<td style="width:20%" id="module_relations-' + relationsCount + '-0"><b>' + agent_b_name + '</b></td>' +
                                    '<td style="width:20%" id="module_relations-' + relationsCount + '-1">' + module_b_name + '</td>' +
                                    '<td style="width:20%" id="module_relations-' + relationsCount + '-2">' + relation_type + '</td>' +
                                    '<td style="width:20%" id="module_relations-' + relationsCount + '-3">' +
                                        '<a id="disable_updates_button" class="alpha50" href="javascript: change_lock_relation(' + relationsCount + ', ' + data + ');">' +
                                            '<?php echo html_print_image('images/policy@svg.svg', true, ['class' => 'main_menu_icon invert_filter']); ?>' +
                                        '</a>' +
                                    '</td>' +
                                    '<td style="width:20%" id="module_relations-' + relationsCount + '-4" class="table_action_buttons">' +
                                        '<a id="delete_relation_button" href="javascript: delete_relation(' + relationsCount + ', ' + data +  ');">' +
                                            '<?php echo html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']); ?>' +
                                        '</a>' +
                                    '</td>' +
                                '</tr>';
                    $("#module_relations").find("tbody").append(rowHTML);

                    $("#hidden-module_relations_count").val(relationsCount + 1);
                    $("#text-autocomplete_module_name").val('');
                }
            },
            error: function (data) {
                button.removeClass('working');
                iconPlaceholder.html(error_icon);
                setTimeout( function() { iconPlaceholder.html(''); }, 2000);
            }
        });
    }
}

// Delete an existing relation
function change_lock_relation (num_row, id_relation) {
    var row = $("#module_relations-" + num_row);
    var button = row.find("#disable_updates_button");
    var oldSrc = button.find("img").prop("src");
    var isEnabled = button.hasClass('alpha50');
    
    if (row.length > 0 && !button.hasClass('working')) {
        button.addClass('working');
        button.removeClass('alpha50');
        button.find("img").prop("src", 'images/spinner.gif');
        
        $.ajax({
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            data: {
                page: "include/ajax/module",
                change_module_relation_updates: true,
                id_relation: id_relation
            },
            success: function (data) {
                if (data === false) {
                    button.addClass('alpha50');
                }
                button.removeClass('working');
                button.find("img").prop("src", oldSrc);
            },
            error: function (data) {
                if (isEnabled) {
                    button.addClass('alpha50');
                }
                button.removeClass('working');
                button.find("img").prop("src", oldSrc);
            }
        });
    }
}

// Delete an existing relation
function delete_relation (num_row, id_relation) {
    var row = $("#module_relations-" + num_row);
    var button = row.find("#delete_relation_button");
    var oldSrc = button.find("img").prop("src");
    
    if (row.length > 0 && !button.hasClass('working')) {
        button.addClass('working');
        button.find("img").prop("src", 'images/spinner.gif');
        
        $.ajax({
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            data: {
                page: "include/ajax/module",
                remove_module_relation: true,
                id_relation: id_relation
            },
            success: function (data) {
                button.removeClass('working');
                button.find("img").prop("src", oldSrc);
                if (data === true) {
                    row.remove();
                }
            },
            error: function (data) {
                button.removeClass('working');
                button.find("img").prop("src", oldSrc);
            }
        });
    }
}

function validate_post_process() {
    var post_process = $("#text-post_process").val();
    if(post_process !== undefined){
        var new_post_process = post_process.replace(',', '.');    
        $("#text-post_process").val(new_post_process);
    }
}

//function paint graph.
function paint_graph_values(){
    var min_w = parseFloat($('#text-min_warning').val());
    if(min_w == '0.00' || isNaN(min_w)){ min_w = 0; }

    var max_w = parseFloat($('#text-max_warning').val());
    if(max_w == '0.00' || isNaN(max_w)){ max_w = 0; }

    var min_c = parseFloat($('#text-min_critical').val());
    if(min_c =='0.00' || isNaN(min_c)){ min_c = 0; }

    var max_c = parseFloat($('#text-max_critical').val());
    if(max_c =='0.00' || isNaN(max_c)){ max_c = 0; }

    var inverse_w = $('input:radio[value=warning_inverse]:checked').val();
    if(!inverse_w){ inverse_w = 0; }

    var inverse_c = $('input:radio[value=critical_inverse]:checked').val();
    if(!inverse_c){ inverse_c = 0; }

    //inicialiced error.
    var error_w = 0;
    var error_c = 0;

    //messages legend.
    var legend_normal = '<?php echo __('Normal Status'); ?>';
    var legend_warning = '<?php echo __('Warning Status'); ?>';
    var legend_critical = '<?php echo __('Critical Status'); ?>';

    //messages error.
    var message_error_warning = '<?php echo __('Please introduce a maximum warning higher than the minimun warning'); ?>';
    var message_error_critical = '<?php echo __('Please introduce a maximum critical higher than the minimun critical'); ?>';
    var message_error_percentage = '<?php echo __('Please introduce a positive percentage value'); ?>';
    
    //if haven't error
    if(max_w == 0 || max_w > min_w){
        if(max_c == 0 || max_c > min_c){
            paint_graph_status(
                min_w, max_w, min_c, max_c, inverse_w, 
                inverse_c, error_w, error_c,
                legend_normal, legend_warning, legend_critical,
                message_error_warning, message_error_critical
            );
        } else {
            error_c = 1;
            paint_graph_status(
                0,0,0,0,0,0, error_w, error_c,
                legend_normal, legend_warning, legend_critical,
                message_error_warning, message_error_critical
            );
        }
    } else {
        error_w = 1;
        paint_graph_status(
            0,0,0,0,0,0, error_w, error_c, 
            legend_normal, legend_warning, legend_critical,
            message_error_warning, message_error_critical
        );
    }
}

/* ]]> */
</script>
