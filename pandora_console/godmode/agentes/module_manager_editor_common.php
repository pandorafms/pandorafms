<?php


// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_categories.php';
require_once $config['homedir'].'/include/graphs/functions_d3.php';

include_javascript_d3();


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

    if ($config['style'] === 'pandora_black') {
        $background_row = 'background-color: #444';
    } else {
        $background_row = 'background-color: #cfcfcf';
    }

    $data = [];
    $data[0] = __('Using module component').' ';

    $component_groups = network_components_get_groups($id_network_component_type);
    $data[1] = '<span id="component_group" class="left">';
    $data[1] .= html_print_select(
        $component_groups,
        'network_component_group',
        '',
        '',
        '--'.__('Manual setup').'--',
        0,
        true,
        false,
        false
    );
    $data[1] .= '</span>';
    $data[1] .= html_print_input_hidden('id_module_component_type', $id_network_component_type, true);
    $data[1] .= '<span id="no_component" class="invisible error">';
    $data[1] .= __('No component was found');
    $data[1] .= '</span>';
    $data[1] .= '<span id="component" class="invisible right">';
    $data[1] .= html_print_select(
        [],
        'network_component',
        '',
        '',
        '---'.__('Manual setup').'---',
        0,
        true
    );
    $data[1] .= '</span>';
    $data[1] .= ' <span id="component_loading" class="invisible">';
    $data[1] .= html_print_image('images/spinner.png', true);
    $data[1] .= '</span>';

    $table_simple->colspan['module_component'][1] = 3;
    $table_simple->rowstyle['module_component'] = $background_row;

    prepend_table_simple($data, 'module_component');
}


require_once 'include/functions_network_components.php';
enterprise_include_once('include/functions_policies.php');

// If code comes from policies disable export select.
global $__code_from;

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$largeClassDisabledBecauseInPolicy = '';

$page = get_parameter('page', '');

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

$update_module_id = (int) get_parameter_get('update_module');
$edit_module = (bool) get_parameter_get('edit_module');
$table_simple = new stdClass();
$table_simple->id = 'simple';
$table_simple->width = '100%';
$table_simple->class = 'no-class';
$table_simple->data = [];
$table_simple->style = [];
$table_simple->style[0] = 'font-weight: bold; width: 25%;';
$table_simple->style[1] = 'width: 25%';
$table_simple->style[2] = 'font-weight: bold; width: 25%;';
$table_simple->style[3] = 'width: 25%';
$table_simple->cellclass = [];

$table_simple->colspan = [];



$table_simple->colspan[6][1] = 3;

$table_simple->rowspan = [];
if (strstr($page, 'policy_modules')) {
    $table_simple->rowspan[3][2] = 3;
    $table_simple->colspan[3][2] = 2;
} else {
    $table_simple->rowspan[4][2] = 3;
    $table_simple->colspan[4][2] = 2;
    $table_simple->colspan[5][1] = 3;
}

$table_simple->data[0][0] = __('Name');
$table_simple->data[0][1] = html_print_input_text_extended(
    'name',
    io_safe_input(html_entity_decode($name, ENT_QUOTES, 'UTF-8')),
    'text-name',
    '',
    45,
    100,
    $disabledBecauseInPolicy,
    '',
    $largeClassDisabledBecauseInPolicy,
    true
);

if (!empty($id_agent_module) && isset($id_agente)) {
    $table_simple->data[0][1] .= '&nbsp;<b>'.__('ID').'</b>&nbsp;&nbsp;'.$id_agent_module.' ';

    $table_simple->data[0][1] .= '&nbsp;<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&delete_module='.$id_agent_module.'"
		onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
    $table_simple->data[0][1] .= html_print_image(
        'images/cross.png',
        true,
        ['title' => __('Delete module')]
    );
    $table_simple->data[0][1] .= '</a> ';
}

$disabled_enable = 0;
$policy_link = db_get_value(
    'policy_linked',
    'tagente_modulo',
    'id_agente_modulo',
    $id_agent_module
);

if ($policy_link != 0) {
    $disabled_enable = 1;
}

$table_simple->data[0][2] = __('Disabled');
$table_simple->data[0][2] .= html_print_checkbox(
    'disabled',
    1,
    $disabled,
    true,
    $disabled_enable
);
$table_simple->data[0][3] = __('Module group');
$table_simple->data[0][3] .= html_print_select_from_sql(
    'SELECT id_mg, name FROM tmodule_group ORDER BY name',
    'id_module_group',
    $id_module_group,
    '',
    __('Not assigned'),
    '0',
    true,
    false,
    true,
    $disabledBecauseInPolicy
);

if ((isset($id_agent_module) && $id_agent_module) || $id_policy_module != 0) {
    $edit = false;
} else {
    $edit = true;
}

$in_policy = strstr($page, 'policy_modules');
if (!$in_policy) {
    // Cannot select the current module to be itself parent.
    $module_parent_filter = ($id_agent_module) ? ['tagente_modulo.id_agente_modulo' => "<>$id_agent_module"] : '';
    $table_simple->data[1][0] = __('Module parent');
    $modules_can_be_parent = agents_get_modules(
        $id_agente,
        false,
        $module_parent_filter
    );
    // If the user cannot have access to parent module, only print the name.
    if ($parent_module_id != 0
        && !in_array($parent_module_id, array_keys($modules_can_be_parent))
    ) {
        $table_simple->data[1][1] = db_get_value(
            'nombre',
            'tagente_modulo',
            'id_agente_modulo',
            $parent_module_id
        );
    } else {
        $table_simple->data[1][1] = html_print_select(
            $modules_can_be_parent,
            'parent_module_id',
            $parent_module_id,
            '',
            __('Not assigned'),
            '0',
            true
        );
    }
}

$table_simple->data[2][0] = __('Type').' '.ui_print_help_icon($help_type, true, '', 'images/help_green.png', '', 'module_type_help');
$table_simple->data[2][0] .= html_print_input_hidden('id_module_type_hidden', $id_module_type, true);

if (!$edit) {
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

    $table_simple->data[2][1] = '<em>'.modules_get_moduletype_description($id_module_type).' ('.$type_names_hash[$id_module_type].')</em>';
    $table_simple->data[2][1] .= html_print_input_hidden(
        'type_names',
        base64_encode(io_json_mb_encode($type_names_hash)),
        true
    );
} else {
    if (isset($id_module_type)) {
        $idModuleType = $id_module_type;
    } else {
        $idModuleType = '';
    }

    // Removed web analysis and log4x from select.
    $tipe_not_in = '24, 25';
    // TODO: FIX credential store for remote command in metaconsole.
    if (is_metaconsole()) {
        $tipe_not_in = '24, 25, 34, 35, 36, 37';
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

    $table_simple->data[2][1] = html_print_select(
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
        false,
        false,
        100
    );

    // Store the relation between id and name of the types on a hidden field.
    $table_simple->data[2][1] .= html_print_input_hidden(
        'type_names',
        base64_encode(io_json_mb_encode($type_names_hash)),
        true
    );
}

if ($edit_module) {
    $id_module_type = (int) $id_module_type;
    if (($id_module_type >= 1 && $id_module_type <= 5)
        || ($id_module_type >= 21 && $id_module_type <= 23)
        || ($id_module_type == 100)
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

    $table_simple->data[2][0] = __('Type').' ';
    $table_simple->data[2][0] .= ui_print_help_icon($help_header, true);
}

if ($disabledBecauseInPolicy) {
    $table_simple->data[2][3] .= html_print_input_hidden(
        'id_module_group',
        $id_module_group,
        true
    );
}

$table_simple->data[3][0] = __('Dynamic Threshold Interval');
$table_simple->data[3][1] = html_print_extended_select_for_time(
    'dynamic_interval',
    $dynamic_interval,
    '',
    'None',
    '0',
    10,
    true,
    'width:150px',
    false,
    $classdisabledBecauseInPolicy,
    $disabledBecauseInPolicy
);
$table_simple->data[3][1] .= '<a onclick=advanced_option_dynamic()>'.html_print_image('images/cog.png', true, ['title' => __('Advanced options Dynamic Threshold')]).'</a>';
if ($in_policy) {
    $table_simple->cellclass[2][2] = 'hide_dinamic';
    $table_simple->cellclass[2][3] = 'hide_dinamic';
} else {
    $table_simple->cellclass[3][2] = 'hide_dinamic';
    $table_simple->cellclass[3][3] = 'hide_dinamic';
}

$table_simple->data[3][2] = '<span><em>'.__('Dynamic Threshold Min. ').'</em>';
$table_simple->data[3][2] .= html_print_input_text(
    'dynamic_min',
    $dynamic_min,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);
$table_simple->data[3][2] .= '<br /><em>'.__('Dynamic Threshold Max. ').'</em>';
$table_simple->data[3][2] .= html_print_input_text(
    'dynamic_max',
    $dynamic_max,
    '',
    10,
    255,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);
$table_simple->data[3][3] = '<span><em>'.__('Dynamic Threshold Two Tailed: ').'</em>';
$table_simple->data[3][3] .= html_print_checkbox('dynamic_two_tailed', 1, $dynamic_two_tailed, true, $disabledBecauseInPolicy);

$table_simple->data[4][0] = __('Warning status');
if (!modules_is_string_type($id_module_type) || $edit) {
    $table_simple->data[4][1] .= '<span id="minmax_warning"><em>'.__('Min. ').'</em>';
    $table_simple->data[4][1] .= html_print_input_text(
        'min_warning',
        $min_warning,
        '',
        10,
        255,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    );
    $table_simple->data[4][1] .= '<br /><em>'.__('Max.').'</em>';
    $table_simple->data[4][1] .= html_print_input_text(
        'max_warning',
        $max_warning,
        '',
        10,
        255,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    ).'</span>';
}

if (modules_is_string_type($id_module_type) || $edit) {
    $table_simple->data[4][1] .= '<span id="string_warning"><em>'.__('Str.').'</em>';
    $table_simple->data[4][1] .= html_print_input_text(
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
}

    $table_simple->data[4][1] .= '<br /><em>'.__('Inverse interval').'</em>';
    $table_simple->data[4][1] .= html_print_checkbox('warning_inverse', 1, $warning_inverse, true, $disabledBecauseInPolicy);

if (!modules_is_string_type($id_module_type) || $edit) {
    $table_simple->data[4][2] = '<svg id="svg_dinamic" width="500" height="300"> </svg>';
}

$table_simple->data[5][0] = __('Critical status');
if (!modules_is_string_type($id_module_type) || $edit) {
    $table_simple->data[5][1] .= '<span id="minmax_critical"><em>'.__('Min. ').'</em>';
    $table_simple->data[5][1] .= html_print_input_text(
        'min_critical',
        $min_critical,
        '',
        10,
        255,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    );
    $table_simple->data[5][1] .= '<br /><em>'.__('Max.').'</em>';
    $table_simple->data[5][1] .= html_print_input_text(
        'max_critical',
        $max_critical,
        '',
        10,
        255,
        true,
        $disabledBecauseInPolicy,
        false,
        '',
        $classdisabledBecauseInPolicy
    ).'</span>';
}

if (modules_is_string_type($id_module_type) || $edit) {
    $table_simple->data[5][1] .= '<span id="string_critical"><em>'.__('Str.').'</em>';
    $table_simple->data[5][1] .= html_print_input_text(
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
    ).'</span>';
}

$table_simple->data[5][1] .= '<br /><em>'.__('Inverse interval').'</em>';
$table_simple->data[5][1] .= html_print_checkbox('critical_inverse', 1, $critical_inverse, true, $disabledBecauseInPolicy);

// FF stands for Flip-flop.
$table_simple->data[6][0] = __('FF threshold').' ';

$table_simple->data[6][1] .= __('Keep counters');
$table_simple->data[6][1] .= html_print_checkbox(
    'ff_type',
    1,
    $ff_type,
    true,
    $disabledBecauseInPolicy
).'<br />';

$table_simple->data[6][1] .= html_print_radio_button(
    'each_ff',
    0,
    '',
    $each_ff,
    true,
    $disabledBecauseInPolicy
);
$table_simple->data[6][1] .= ' '.__('All state changing').' : ';
$table_simple->data[6][1] .= html_print_input_text(
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
).'<br />';
$table_simple->data[6][1] .= html_print_radio_button(
    'each_ff',
    1,
    '',
    $each_ff,
    true,
    $disabledBecauseInPolicy
);

$table_simple->data[6][1] .= ' '.__('Each state changing').' : ';
$table_simple->data[6][1] .= __('To normal');
$table_simple->data[6][1] .= html_print_input_text(
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
).' ';

$table_simple->data[6][1] .= __('To warning');
$table_simple->data[6][1] .= html_print_input_text(
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
).' ';

$table_simple->data[6][1] .= __('To critical');
$table_simple->data[6][1] .= html_print_input_text(
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

$table_simple->data[7][0] = __('Historical data');
if ($disabledBecauseInPolicy) {
    // If is disabled, we send a hidden in his place and print a false
    // checkbox because HTML dont send disabled fields
    // and could be disabled by error.
    $table_simple->data[7][1] = html_print_checkbox(
        'history_data_fake',
        1,
        $history_data,
        true,
        $disabledBecauseInPolicy
    );
    $table_simple->data[7][1] .= '<input type="hidden" name="history_data" value="'.(int) $history_data.'">';
} else {
    $table_simple->data[7][1] = html_print_checkbox(
        'history_data',
        1,
        $history_data,
        true,
        $disabledBecauseInPolicy
    );
}

// Advanced form part.
$table_advanced = new stdClass();
$table_advanced->id = 'advanced';
$table_advanced->width = '100%';
$table_advanced->class = 'no-class';
$table_advanced->data = [];
$table_advanced->style = [];
$table_advanced->style[0] = $table_advanced->style[3] = $table_advanced->style[5] = 'font-weight: bold;';
$table_advanced->colspan = [];

$table_advanced->data[0][0] = __('Description');
$table_advanced->colspan[0][1] = 6;
$table_advanced->data[0][1] = html_print_textarea(
    'description',
    2,
    65,
    $description,
    $disabledTextBecauseInPolicy,
    true,
    $largeClassDisabledBecauseInPolicy
);

$table_advanced->data[1][0] = __('Custom ID');
$table_advanced->colspan[1][1] = 2;
$table_advanced->data[1][1] = html_print_input_text(
    'custom_id',
    $custom_id,
    '',
    20,
    65,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
);

$table_advanced->data[1][3] = __('Unit');
// $table_advanced->data[1][4] = html_print_input_text ('unit', $unit, '', 20, 65, true,
// $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy);
// $table_advanced->colspan[1][4] = 3;
$table_advanced->data[1][4] = html_print_extended_select_for_unit(
    'unit',
    $unit,
    '',
    '',
    '0',
    false,
    true,
    false,
    false
);
$table_advanced->colspan[1][4] = 3;

$module_id_policy_module = 0;
if (isset($module['id_policy_module'])) {
    $module_id_policy_module = $module['id_policy_module'];
}

// In the data modules, the interval is not in seconds. It is a factor
// to be multiplied for the agent interval
if ($moduletype == MODULE_DATA) {
    $table_advanced->data[2][0] = __('Interval');
    $table_advanced->colspan[2][1] = 2;
    $interval_factor = 1;
    if (isset($id_agente)) {
        $agent_interval = agents_get_interval($id_agente);
        $interval_factor = ($interval / $agent_interval);
        $table_advanced->data[2][1] = human_time_description_raw($interval).' ('.sprintf(__('Agent interval x %s'), $interval_factor).') ';
    } else {
        $table_advanced->data[2][1] = sprintf(__('Agent interval x %s'), $interval_factor);
    }

    if ($__code_from == 'policies') {
        // If is the policy form, module_interval will store the factor (not the seconds).
        // So server will transform it to interval in seconds
        $table_advanced->data[2][1] = sprintf(__('Default').': 1', $interval_factor);
        $table_advanced->data[2][1] .= html_print_input_hidden('module_interval', $interval_factor, true);
    } else if ($module_id_policy_module != 0) {
        $table_advanced->data[2][1] .= ui_print_help_tip(__('The policy modules of data type will only update their intervals when policy is applied.'), true);
    }

    // If it is a non policy form, the module_interval will not provided and will
    // be taken the agent interval (this code is at configurar_agente.php)
} else {
    $table_advanced->data[2][0] = __('Interval');
    $table_advanced->colspan[2][1] = 2;
    $table_advanced->data[2][1] = html_print_extended_select_for_time('module_interval', $interval, '', '', '0', false, true, false, false, $classdisabledBecauseInPolicy, $disabledBecauseInPolicy);
}

$table_advanced->data[2][1] .= html_print_input_hidden('moduletype', $moduletype, true);

$table_advanced->data[2][3] = __('Post process');
$table_advanced->data[2][4] = html_print_extended_select_for_post_process(
    'post_process',
    $post_process,
    '',
    '',
    '0',
    false,
    true,
    false,
    false,
    $disabledBecauseInPolicy
);
$table_advanced->colspan[2][4] = 3;

$table_advanced->data[3][0] = __('Min. Value');
$table_advanced->colspan[3][1] = 2;

$table_advanced->data[3][1] = html_print_input_text('min', $min, '', 5, 15, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy).' '.ui_print_help_tip(__('Any value below this number is discarted.'), true);
$table_advanced->data[3][3] = __('Max. Value');
$table_advanced->data[3][4] = html_print_input_text('max', $max, '', 5, 15, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy).' '.ui_print_help_tip(__('Any value over this number is discarted.'), true);
$table_advanced->colspan[3][4] = 3;

$table_advanced->data[4][0] = __('Export target');
// Default text message for export target select and disabled option
$none_text = __('None');
$disabled_export = false;

if ($__code_from == 'policies') {
    $none_text = __('Not needed');
    $disabled_export = true;
}

$table_advanced->data[4][1] = html_print_select_from_sql(
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
).ui_print_help_tip(__('In case you use an Export server you can link this module and export data to one these.'), true);
$table_advanced->colspan[4][1] = 2;

// Code comes from module_editor
if ($__code_from == 'modules') {
    $throw_unknown_events_check = modules_is_disable_type_event($id_agent_module, EVENTS_GOING_UNKNOWN);
} else {
    global $__id_pol_mod;

    $throw_unknown_events_check = policy_module_is_disable_type_event($__id_pol_mod, EVENTS_GOING_UNKNOWN);
}

$table_advanced->data[4][3] = __('Discard unknown events');
$table_advanced->data[4][4] = html_print_checkbox(
    'throw_unknown_events',
    1,
    $throw_unknown_events_check,
    true,
    $disabledBecauseInPolicy
);
$table_advanced->colspan[4][4] = 3;

$table_advanced->data[5][0] = __('FF interval');
$table_advanced->data[5][1] = html_print_input_text(
    'module_ff_interval',
    $ff_interval,
    '',
    5,
    10,
    true,
    $disabledBecauseInPolicy,
    false,
    '',
    $classdisabledBecauseInPolicy
).ui_print_help_tip(__('Module execution flip flop time interval (in secs).'), true);
$table_advanced->colspan[5][1] = 2;

$table_advanced->data[5][3] = __('FF timeout');

$module_type_name = modules_get_type_name($id_module_type);
$table_advanced->data[5][4] = '';
if (preg_match('/async/', $module_type_name) || $edit) {
    $table_advanced->data[5][4] .= '<span id="ff_timeout">'.html_print_input_text(
        'ff_timeout',
        $ff_timeout,
        '',
        5,
        10,
        true,
        $disabledBecauseInPolicy
    ).ui_print_help_tip(__('Timeout in secs from start of flip flop counting. If this value is exceeded, FF counter is reset. Set to 0 for no timeout.'), true).'</span>';
}

if (!preg_match('/async/', $module_type_name) || $edit) {
    $table_advanced->data[5][4] .= '<span id="ff_timeout_disable">'.__('Disabled').ui_print_help_tip(__('This value can be set only in the async modules.'), true).'</span>';
}

$table_advanced->colspan[5][4] = 3;

/*
    Tags */
// This var comes from module_manager_editor.php or policy_modules.php
global $__code_from;
$table_advanced->data[6][0] = __('Tags available');
// Code comes from module_editor
if ($__code_from == 'modules') {
    $__table_modules = 'ttag_module';
    $__id_where = 'b.id_agente_modulo';
    $__id = (int) $id_agent_module;

    $__sql = ' AND b.id_policy_module = 0';
    $__sql_policy = ' AND b.id_policy_module != 0';
} else {
    // Code comes from policy module editor
    global $__id_pol_mod;
    $__table_modules = 'ttag_policy_module';
    $__id_where = 'b.id_policy_module';
    $__id = $__id_pol_mod;

    $__sql = '';
}

if (!tags_has_user_acl_tags($config['id_user'])) {
    $table_advanced->data[6][1] = html_print_select_from_sql(
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
    if (!empty($user_tags)) {
        $id_user_tags = array_keys($user_tags);

        $table_advanced->data[6][1] = html_print_select_from_sql(
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
        $table_advanced->data[6][1] = html_print_select_from_sql(
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

$table_advanced->data[6][2] = html_print_image('images/darrowright.png', true, ['id' => 'right', 'title' => __('Add tags to module')]);
// html_print_input_image ('add', 'images/darrowright.png', 1, '', true, array ('title' => __('Add tags to module')));
$table_advanced->data[6][2] .= '<br><br><br><br>'.html_print_image('images/darrowleft.png', true, ['id' => 'left', 'title' => __('Delete tags to module')]);
// html_print_input_image ('add', 'images/darrowleft.png', 1, '', true, array ('title' => __('Delete tags to module')));
$table_advanced->data[6][3] = '<b>'.__('Tags selected').'</b>';
$table_advanced->data[6][4] = html_print_select_from_sql(
    "SELECT a.id_tag, name 
	FROM ttag a, $__table_modules b
	WHERE a.id_tag = b.id_tag AND $__id_where = $__id
		$__sql
	ORDER BY name",
    'id_tag_selected[]',
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

if ($__code_from == 'modules') {
    $table_advanced->data[6][5] = '<b>'.__('Tags from policy').'</b>';
    $table_advanced->data[6][6] = html_print_select_from_sql(
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
}

$table_advanced->data[7][0] = __('Quiet');
$table_advanced->data[7][0] .= ui_print_help_tip(__('The module still stores data but the alerts and events will be stop'), true);
$table_advanced->data[7][1] = html_print_checkbox('quiet_module', 1, $quiet_module, true, $disabledBecauseInPolicy);

$cps_array[-1] = __('Disabled');
if ($cps_module > 0) {
    $cps_array[$cps_module] = __('Enabled');
} else {
    $cps_inc = 0;
    if ($id_agent_module) {
        $cps_inc = enterprise_hook('service_modules_cps', [$id_agent_module]);
        if ($cps_inc === ENTERPRISE_NOT_HOOK) {
            $cps_inc = 0;
        }
    }

    $cps_array[$cps_inc] = __('Enabled');
}

$table_advanced->data[7][2] = __('Cascade Protection Services');
$table_advanced->data[7][2] .= ui_print_help_tip(__('Disable the alerts and events of the elements that belong to this service'), true);
$table_advanced->colspan[7][3] = 5;
$table_advanced->data[7][3] = html_print_select($cps_array, 'cps_module', $cps_module, '', '', 0, true, false, true, '', $disabledBecauseInPolicy);

$table_advanced->data[8][0] = __('Critical instructions').ui_print_help_tip(__('Instructions when the status is critical'), true);
$table_advanced->data[8][1] = html_print_textarea('critical_instructions', 2, 65, $critical_instructions, $disabledTextBecauseInPolicy, true, $largeClassDisabledBecauseInPolicy);

$table_advanced->colspan[8][1] = 6;

$table_advanced->data[9][0] = __('Warning instructions').ui_print_help_tip(__('Instructions when the status is warning'), true);
$table_advanced->data[9][1] = html_print_textarea('warning_instructions', 2, 65, $warning_instructions, $disabledTextBecauseInPolicy, true, $largeClassDisabledBecauseInPolicy);
$table_advanced->colspan[9][1] = 6;

$table_advanced->data[10][0] = __('Unknown instructions').ui_print_help_tip(__('Instructions when the status is unknown'), true);
$table_advanced->data[10][1] = html_print_textarea('unknown_instructions', 2, 65, $unknown_instructions, $disabledTextBecauseInPolicy, true, $largeClassDisabledBecauseInPolicy);
$table_advanced->colspan[10][1] = 6;

if (isset($id_agente) && $moduletype == MODULE_DATA) {
    $has_remote_conf = enterprise_hook('config_agents_has_remote_configuration', [$agent['id_agente']]);
    if ($has_remote_conf) {
        $table_advanced->data[11][0] = __('Cron from');
        $table_advanced->data[11][1] = html_print_extended_select_for_cron($hour_from, $minute_from, $mday_from, $month_from, $wday_from, true, $disabledBecauseInPolicy);
        $table_advanced->colspan[11][1] = 6;

        $table_advanced->data[12][0] = __('Cron to');
        $table_advanced->data[12][1] = html_print_extended_select_for_cron($hour_to, $minute_to, $mday_to, $month_to, $wday_to, true, $disabledBecauseInPolicy, true);
        $table_advanced->colspan[12][1] = 6;
    } else {
        $table_advanced->data[11][0] = __('Cron from');
        $table_advanced->data[11][1] = html_print_extended_select_for_cron($hour_from, $minute_from, $mday_from, $month_from, $wday_from, true, true);
        $table_advanced->colspan[11][1] = 6;

        $table_advanced->data[12][0] = __('Cron to');
        $table_advanced->data[12][1] = html_print_extended_select_for_cron($hour_to, $minute_to, $mday_to, $month_to, $wday_to, true, true, true);
        $table_advanced->colspan[12][1] = 6;
    }
} else {
    $table_advanced->data[11][0] = __('Cron from');
    $table_advanced->data[11][1] = html_print_extended_select_for_cron($hour_from, $minute_from, $mday_from, $month_from, $wday_from, true, $disabledBecauseInPolicy);
    $table_advanced->colspan[11][1] = 6;

    $table_advanced->data[12][0] = __('Cron to');
    $table_advanced->data[12][1] = html_print_extended_select_for_cron($hour_to, $minute_to, $mday_to, $month_to, $wday_to, true, $disabledBecauseInPolicy, true);
    $table_advanced->colspan[12][1] = 6;
}

$table_advanced->data[13][0] = __('Timeout');
$table_advanced->data[13][1] = html_print_input_text('max_timeout', $max_timeout, '', 5, 10, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy).' '.ui_print_help_tip(__('Seconds that agent will wait for the execution of the module.'), true);
$table_advanced->data[13][2] = '';
$table_advanced->data[13][3] = __('Retries');
$table_advanced->data[13][4] = html_print_input_text('max_retries', $max_retries, '', 5, 10, true, $disabledBecauseInPolicy, false, '', $classdisabledBecauseInPolicy).' '.ui_print_help_tip(__('Number of retries that the module will attempt to run.'), true);
$table_advanced->colspan[13][4] = 3;

if (check_acl($config['id_user'], 0, 'PM')) {
    $table_advanced->data[14][0] = __('Category');
    $table_advanced->data[14][1] = html_print_select(
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
    $table_advanced->colspan[14][1] = 6;
} else {
    // Store in a hidden field if is not visible to avoid delete the value
    $table_advanced->data[13][4] .= html_print_input_hidden('id_category', $id_category, true);
}

// Advanced form part
$table_macros = new stdClass();
$table_macros->id = 'module_macros';
$table_macros->width = '100%';
$table_macros->class = 'no-class';
$table_macros->data = [];
$table_macros->style = [];
$table_macros->style[0] = 'font-weight: bold;';
$table_macros->style[2] = 'font-weight: bold;';
$table_macros->style[5] = 'width: 10px';
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
                $table_macros->data[$macro_count][4] = '<a href="javascript: delete_macro('.$macro_count.');">'.html_print_image('images/cross.png', true).'</a>';
            }

            $macro_count++;
        }
    }
}

if (!$disabledBecauseInPolicy) {
    $table_macros->data[$macro_count][0] = '<span>'.__('Custom macros').'</span> <a href="javascript:add_macro();">'.html_print_image('images/add.png', true).'</a>';

    $table_macros->colspan[$macro_count][0] = 5;
}

$macro_count++;

html_print_input_hidden('module_macro_count', $macro_count);

// Advanced form part.
// Add relationships.
$table_new_relations = new stdClass();
$table_new_relations->id = 'module_new_relations';
$table_new_relations->width = '100%';
$table_new_relations->class = 'no-class';
$table_new_relations->data = [];
$table_new_relations->style = [];
$table_new_relations->style[0] = 'width: 10%; font-weight: bold;';
$table_new_relations->style[1] = 'width: 25%; text-align: center;';
$table_new_relations->style[2] = 'width: 10%; font-weight: bold;';
$table_new_relations->style[3] = 'width: 25%; text-align: center;';
$table_new_relations->style[4] = 'width: 10%; font-weight: bold;';
$table_new_relations->style[5] = 'width: 25%; text-align: center;';

$table_new_relations->data[0][0] = __('Agent');
$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'autocomplete_agent_name';
$params['use_hidden_input_idagent'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_id'] = 'hidden-autocomplete_id_agent';
$params['javascript_function_action_after_select_js_call'] = 'change_modules_autocomplete_input();';
$table_new_relations->data[0][1] = ui_print_agent_autocomplete_input($params);
$table_new_relations->data[0][2] = __('Module');
$table_new_relations->data[0][3] = "<div id='module_autocomplete'></div>";

$array_rel_type = [];
$array_rel_type['direct'] = __('Direct');
$array_rel_type['failover'] = __('Failover');
$table_new_relations->data[0][4] = __('Rel. type');
$table_new_relations->data[0][5] = html_print_select(
    $array_rel_type,
    'relation_type',
    '',
    '',
    '',
    0,
    true,
    false,
    true,
    ''
);

$table_new_relations->data[0][6] = html_print_button(
    __('Add relationship'),
    'add_relation',
    false,
    'javascript: add_new_relation();',
    'class="sub add"',
    true
);
$table_new_relations->data[0][6] .= "&nbsp;&nbsp;<div id='add_relation_status' style='display: inline;'></div>";

// Relationship list.
$table_relations = new stdClass();
$table_relations->id = 'module_relations';
$table_relations->width = '100%';
$table_relations->class = 'databox data';
$table_relations->head = [];
$table_relations->data = [];
$table_relations->rowstyle = [];
$table_relations->rowstyle[-1] = 'display: none;';
$table_relations->style = [];
$table_relations->style[3] = 'width: 10%; text-align: center;';
$table_relations->style[4] = 'width: 10%; text-align: center;';

$table_relations->head[0] = __('Agent');
$table_relations->head[1] = __('Module');
$table_relations->head[2] = __('Type');
$table_relations->head[3] = __('Changes').ui_print_help_tip(
    __('Activate this to prevent the relation from being updated or deleted'),
    true
);
$table_relations->head[4] = __('Delete');

// Create an invisible row to use their html to add new rows.
$table_relations->data[-1][0] = '';
$table_relations->data[-1][1] = '';
$table_relations->data[-1][2] = '';
$table_relations->data[-1][3] = '<a id="disable_updates_button" class="alpha50" href="">';
$table_relations->data[-1][3] .= html_print_image('images/lock.png', true).'</a>';
$table_relations->data[-1][4] = '<a id="delete_relation_button" href="">';
$table_relations->data[-1][4] .= html_print_image('images/cross.png', true).'</a>';


$relations_count = 0;
if ($id_agent_module) {
    $module_relations = modules_get_relations(['id_module' => $id_agent_module]);

    if (!$module_relations) {
        $module_relations = [];
    }

    $relations_count = 0;
    foreach ($module_relations as $key => $module_relation) {
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
        $table_relations->data[$relations_count][1] = "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$agent_id.'&tab=module&edit_module=1&id_agent_module='.$module_id."'>".ui_print_truncate_text($module_name, 'module_medium', true, true, true, '[&hellip;]').'</a>';
        // Type.
        $table_relations->data[$relations_count][2] = ($module_relation['type'] === 'direct') ? __('Direct') : __('Failover');
        // Lock relationship updates.
        $table_relations->data[$relations_count][3] = '<a id="disable_updates_button" class="'.$disabled_update_class.'"href="javascript: change_lock_relation('.$relations_count.', '.$module_relation['id'].');">'.html_print_image('images/lock.png', true).'</a>';
        // Delete relationship.
        $table_relations->data[$relations_count][4] = '<a id="delete_relation_button" href="javascript: delete_relation('.$relations_count.', '.$module_relation['id'].');">'.html_print_image('images/cross.png', true).'</a>';
        $relations_count++;
    }
}

html_print_input_hidden('module_relations_count', $relations_count);

ui_require_jquery_file('json');

?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
    var disabledBecauseInPolicy = '<?php echo $disabledBecauseInPolicy; ?>';
    $("#right").click (function () {
        jQuery.each($("select[name='id_tag_available[]'] option:selected"), function (key, value) {
            tag_name = $(value).html();
            if (tag_name != <?php echo "'".__('None')."'"; ?>) {
                id_tag = $(value).attr('value');
                $("select[name='id_tag_selected[]']").append($("<option></option>").val(id_tag).html('<i>' + tag_name + '</i>'));
                $("#id_tag_available").find("option[value='" + id_tag + "']").remove();
                $("#id_tag_selected").find("option[value='']").remove();
                if($("#id_tag_available option").length == 0) {
                    $("select[name='id_tag_available[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                }
            }
        });
    });
    
    $("#left").click (function () {
        jQuery.each($("select[name='id_tag_selected[]'] option:selected"), function (key, value) {
                tag_name = $(value).html();
                if (tag_name != <?php echo "'".__('None')."'"; ?>) {
                    id_tag = $(value).attr('value');
                    $("select[name='id_tag_available[]']").append($("<option>").val(id_tag).html('<i>' + tag_name + '</i>'));
                    $("#id_tag_selected").find("option[value='" + id_tag + "']").remove();
                    $("#id_tag_available").find("option[value='']").remove();
                    if($("#id_tag_selected option").length == 0) {
                        $("select[name='id_tag_selected[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
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
                     'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Operacion&printable=yes#Tipos_de_m.C3.B3dulos',
                     '_blank',
                     'width=800,height=600'
                        );
               }
               else{
                window.open(
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Operations&printable=yes#Types_of_Modules',
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
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Monitorizacion_remota&printable=yes#Monitorizaci.C3.B3n_ICMP',
                     '_blank',
                     'width=800,height=600'
                     );
                 }
                 else{
                    window.open(
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Remote_Monitoring&printable=yes#ICMP_Monitoring',
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
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Monitorizacion_remota&printable=yes#Monitorizando_con_m.C3.B3dulos_de_red_tipo_SNMP',
                     '_blank',
                     'width=800,height=600'
                     );
                 }
                 else{
                    window.open(
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Remote_Monitoring&printable=yes#Monitoring_by_Network_Modules_with_SNMP',
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
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Monitorizacion_remota&printable=yes#Monitorizaci.C3.B3n_TCP',
                     '_blank',
                     'width=800,height=600'
                     );
                   }
                   else{
                    window.open(
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Remote_Monitoring&printable=yes#TCP_Monitoring',
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
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Monitorizacion_web&printable=yes#Creaci.C3.B3n_de_m.C3.B3dulos_web',
                     '_blank',
                     'width=800,height=600'
                     );
                   }
                   else{
                    window.open(
                    'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Web_Monitoring&printable=yes#Creating_Web_Modules',
                     '_blank',
                     'width=800,height=600'
                     );
                   }
            }
        }

        if (type_name_selected.match(/_string$/) == null) {
            // Numeric types
            $('#string_critical').hide();
            $('#string_warning').hide();
            $('#minmax_critical').show();
            $('#minmax_warning').show();
            $('#svg_dinamic').show();
        }
        else {
            // String types
            $('#string_critical').show();
            $('#string_warning').show();
            $('#minmax_critical').hide();
            $('#minmax_warning').hide();
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
    });

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
    $('#checkbox-dynamic_two_tailed').change (function() {
        disabled_two_tailed(disabledBecauseInPolicy);
    });


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
    $('#checkbox-warning_inverse').change (function() {
        paint_graph_values();
    });
    $('#checkbox-critical_inverse').change (function() {
        paint_graph_values();
    });

});

//readonly and add class input
function disabled_status (disabledBecauseInPolicy) {
    if($('#dynamic_interval_select').val() != 0 && $('#dynamic_interval').val() != 0){
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
    if($('#checkbox-dynamic_two_tailed').prop('checked')){
        $('#text-dynamic_max').prop('readonly', false);
        $('#text-dynamic_max').removeClass('readonly');
    }
    else{
        if (disabledBecauseInPolicy == 0){
            $('#text-dynamic_max').prop('readonly', true);
            $('#text-dynamic_max').addClass('readonly');
        }
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
    var error_icon = '<?php html_print_image('images/error_red.png', false); ?>';
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
    var suc_icon = '<?php html_print_image('images/ok.png', false, ['style' => 'vertical-align:middle;']); ?>';
    var error_icon = '<?php html_print_image('images/error_red.png', false, ['style' => 'vertical-align:middle;']); ?>';

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
                                    '<td id="module_relations-' + relationsCount + '-0"><b>' + agent_b_name + '</b></td>' +
                                    '<td id="module_relations-' + relationsCount + '-1">' + module_b_name + '</td>' +
                                    '<td id="module_relations-' + relationsCount + '-2">' + relation_type + '</td>' +
                                    '<td id="module_relations-' + relationsCount + '-3" style="width: 10%; text-align: center;">' +
                                        '<a id="disable_updates_button" class="alpha50" href="javascript: change_lock_relation(' + relationsCount + ', ' + data + ');">' +
                                            '<?php echo html_print_image('images/lock.png', true); ?>' +
                                        '</a>' +
                                    '</td>' +
                                    '<td id="module_relations-' + relationsCount + '-4" style="width: 10%; text-align: center;">' +
                                        '<a id="delete_relation_button" href="javascript: delete_relation(' + relationsCount + ', ' + data +  ');">' +
                                            '<?php echo html_print_image('images/cross.png', true); ?>' +
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

//function paint graph
function paint_graph_values(){
    //Parse integrer
    var min_w = parseFloat($('#text-min_warning').val());
        if(min_w == '0.00'){ min_w = 0; }
    var max_w = parseFloat($('#text-max_warning').val());
        if(max_w == '0.00'){ max_w = 0; }
    var min_c = parseFloat($('#text-min_critical').val());
        if(min_c =='0.00'){ min_c = 0; }
    var max_c = parseFloat($('#text-max_critical').val());
        if(max_c =='0.00'){ max_c = 0; }
    var inverse_w = $('input:checkbox[name=warning_inverse]:checked').val();
        if(!inverse_w){ inverse_w = 0; }
    var inverse_c = $('input:checkbox[name=critical_inverse]:checked').val();
        if(!inverse_c){ inverse_c = 0; }

    //inicialiced error
    var error_w = 0;
    var error_c = 0;
    //messages legend
    var legend_normal = '<?php echo __('Normal Status'); ?>';
    var legend_warning = '<?php echo __('Warning Status'); ?>';
    var legend_critical = '<?php echo __('Critical Status'); ?>';
    //messages error
    var message_error_warning = '<?php echo __('Please introduce a maximum warning higher than the minimun warning'); ?>';
    var message_error_critical = '<?php echo __('Please introduce a maximum critical higher than the minimun critical'); ?>';
    
    //if haven't error
    if(max_w == 0 || max_w > min_w){
        if(max_c == 0 || max_c > min_c){
            paint_graph_status(min_w, max_w, min_c, max_c, inverse_w, 
                                inverse_c, error_w, error_c,
                                legend_normal, legend_warning, legend_critical,
                                message_error_warning, message_error_critical);
        } else {
            error_c = 1;
            paint_graph_status(0,0,0,0,0,0, error_w, error_c,
                            legend_normal, legend_warning, legend_critical,
                            message_error_warning, message_error_critical);
        }
    } else {
        error_w = 1;
        paint_graph_status(0,0,0,0,0,0, error_w, error_c, 
                            legend_normal, legend_warning, legend_critical,
                            message_error_warning, message_error_critical);
    }
}
/* End of relationship javascript */

/* ]]> */
</script>
