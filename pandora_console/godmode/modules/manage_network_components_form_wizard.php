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
require_once $config['homedir'].'/include/graphs/functions_d3.php';

check_login();
include_javascript_d3();

if (!check_acl($config['id_user'], 0, 'PM')
    && !check_acl($config['id_user'], 0, 'AW')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    return;
}


/**
 * Add to the table the extra fields.
 *
 * @param array  $extra_fields Array with the extra fields needed.
 * @param string $protocol     Protocol for define the text.
 *
 * @return void
 */
function generateExtraFields($extra_fields, $protocol)
{
    global $table;
    $cntFields = 0;

    foreach ($extra_fields as $k => $field) {
        // Avoid the not extra fields.
        if (preg_match('/extra_field_/', $k) === 0) {
            continue;
        } else {
            $cntFields++;
        }

        // Get the number of extra field.
        $tmpExtraField = explode('_', $k);
        $idField = $tmpExtraField[2];

        if ($protocol === 'snmp') {
            $extraFieldText = '_oid_'.$idField.'_';
            $rowId = 'pluginRow-'.$protocol.'Row';
        } else if ($protocol === 'wmi') {
            $extraFieldText = '_field_wmi_'.$idField.'_';
            $rowId = $protocol.'Row';
        }

        $data = [];
        $data[0] = '<div class="right">'.$extraFieldText.'</div>';
        $data[1] = html_print_input_text_extended(
            'extra_field_'.$protocol.'_'.$idField,
            $field,
            'extra_field_'.$protocol.'_'.$idField,
            '',
            100,
            10000,
            '',
            '',
            '',
            true
        );

        $table->colspan['oid-list-'.$rowId.'-row-'.$idField][1] = 3;
        push_table_row($data, 'oid-list-'.$rowId.'-row-'.$idField);
    }

    $data = [];
    $image_add = html_print_div(
        [
            'id'      => 'add_field_button',
            'class'   => 'float-right clickable',
            'content' => html_print_image(
                'images/add.png',
                true,
                [
                    'title'   => __('Add a macro oid'),
                    'onclick' => 'manageComponentFields(\'add\', \'oid-list-'.$rowId.'\');',
                    'class'   => 'invert_filter',
                ]
            ),
        ],
        true
    );

    $image_del = html_print_div(
        [
            'id'      => 'del_field_button',
            'class'   => 'float-right',
            'style'   => $cntFields <= 1 ? 'opacity: 0.5;' : '',
            'content' => html_print_image(
                'images/delete.svg',
                true,
                [
                    'title'   => __('Remove last macro oid'),
                    'onclick' => 'manageComponentFields(\'del\', \'oid-list-'.$rowId.'\');',
                    'style'   => 'margin-left: 1em;',
                    'class'   => 'invert_filter',
                ]
            ),
        ],
        true
    );

    $data[0] = html_print_div(
        [
            'id'      => 'combo_oid_button',
            'content' => $image_del.$image_add,
        ],
        true
    );

    push_table_row($data, 'manage-oid-list-'.$rowId);
}


// Get the data.
$module_type_list = [
    MODULE_TYPE_NUMERIC      => __('Numeric'),
    MODULE_TYPE_INCREMENTAL  => __('Incremental'),
    MODULE_TYPE_BOOLEAN      => __('Boolean'),
    MODULE_TYPE_ALPHANUMERIC => __('Alphanumeric'),
];

$module_protocol_list = [
    'snmp' => 'SNMP',
    'wmi'  => 'WMI',
];

$scan_type_list = [
    SCAN_TYPE_FIXED   => 'Fixed',
    SCAN_TYPE_DYNAMIC => 'Dynamic',
];

$execution_type_list = [
    EXECUTION_TYPE_NETWORK => 'Network',
    EXECUTION_TYPE_PLUGIN  => 'Plugin',
];
// Establish module type value.
switch ($type) {
    case MODULE_TYPE_REMOTE_SNMP:
    case MODULE_TYPE_GENERIC_DATA:
        $module_type = MODULE_TYPE_NUMERIC;
    break;

    case MODULE_TYPE_REMOTE_SNMP_INC:
    case MODULE_TYPE_GENERIC_DATA_INC:
        $module_type = MODULE_TYPE_INCREMENTAL;
    break;

    case MODULE_TYPE_REMOTE_SNMP_STRING:
    case MODULE_TYPE_GENERIC_DATA_STRING:
        $module_type = MODULE_TYPE_ALPHANUMERIC;
    break;

    case MODULE_TYPE_REMOTE_SNMP_PROC:
    case MODULE_TYPE_GENERIC_PROC:
        $module_type = MODULE_TYPE_BOOLEAN;
    break;

    default:
        $module_type = MODULE_TYPE_NUMERIC;
    break;
}

$query_filter = [];
if (empty($query_filter) === false) {
    $query_filter = json_decode($query_filter, true);
}

$component_group_list = network_components_get_groups();

// List of server plugins related with Wizard SNMP.
$server_plugin_data = [];
$server_plugin_list = [];
$plugins = db_get_all_rows_sql(
    'SELECT id, description, execute, name, macros, parameters FROM tplugin'
);
foreach ($plugins as $plugin) {
    $server_plugin_list[$plugin['id']] = $plugin['name'];
    $server_plugin_data[$plugin['id']] = [
        'description'   => $plugin['description'],
        'name'          => $plugin['name'],
        'parameters'    => $plugin['parameters'],
        'macros'        => array_reverse(json_decode($plugin['macros'], true)),
        'execute'       => $plugin['execute'],
        'macrosElement' => base64_encode(json_encode(io_safe_output(json_decode($macros, true)))),
    ];
}

// Store the plugin data for JS managing in JSON format.
$hiddenPluginServers = '';
foreach ($server_plugin_data as $index => $plugin) {
    // Description can have special chars that would crash Javascript.
    $plugin['description'] = mb_strimwidth(io_safe_output($plugin['description']), 0, 80, '...');
    $hiddenPluginServers .= html_print_input_hidden(
        'server_plugin_data_'.$index,
        json_encode(io_safe_input($plugin))
    );
}

// Generate needed OID macros.
$extra_fields_names = [];
foreach ($extra_fields as $k => $field) {
    $extra_fields_names[$k] = $module_protocol === 'snmp' ? '_oid_'.$k.'_' : $k;
}

// Convert the string DB format of macros to JSON.
$macros = json_decode($macros);
// Only for extra field generate purposes.
if (empty($macros) === true) {
    $macros = ['extra_field_1' => ''];
}

//
// Construction of form.
//
$table->id = 'network_component';
$table->width = '100%';
$table->class = 'databox';
$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->colspan = [];
if (!enterprise_installed()) {
    $table->colspan[0][1] = 3;
}

$table->data = [];

$data = [];
$data[0] = __('Enabled');
$data[1] = html_print_checkbox_switch(
    'enabled',
    1,
    $enabled,
    true,
    false,
    '',
    false
);

$data[2] = __('Add by default');
$data[3] = html_print_checkbox_switch(
    'module_enabled',
    1,
    $module_enabled,
    true,
    false,
    '',
    false
);

push_table_row($data, 'module-enable-row');

$data = [];
$data[0] = __('Module name');
$data[1] = html_print_input_text_extended(
    'name',
    $name,
    'name',
    '',
    50,
    255,
    '',
    '',
    '',
    true
);

$data[2] = __('Module protocol');
$data[3] = html_print_select(
    $module_protocol_list,
    'module_protocol',
    $module_protocol,
    'manageVisibleFields()',
    '',
    '',
    true,
    false,
    false,
    ''
).'&nbsp;'.html_print_image(
    'images/'.$module_protocol.'.png',
    true,
    [
        'title' => strtoupper($module_protocol).'&nbsp;'.__('Protocol'),
        'class' => 'add_comments_button',
        'style' => 'width: 18px;',
        'id'    => 'module_protocol_symbol',
    ]
);
$type = 4;
$data[4] = html_print_input_hidden('type', $type, true);

push_table_row($data, 'module-name-type-row');

$data = [];

$data[0] = __('Name OID');
$data[1] = html_print_input_text('name_oid', $name_oid, '', 50, 255, true);

$data[2] = __('Manufacturer ID');
$data[3] = html_print_select_from_sql(
    'SELECT manufacturer as `id`, manufacturer FROM tpen GROUP BY manufacturer',
    'manufacturer_id',
    $manufacturer_id,
    '',
    'All',
    '',
    true,
    false,
    false,
    ''
);

push_table_row($data, 'manufacturer-nameOID-snmpRow-row');

$data = [];

$data[0] = __('Module type');
$data[1] = html_print_select(
    $module_type_list,
    'module_type',
    $module_type,
    'changeModuleType()',
    '',
    '',
    true,
    false,
    false,
    ''
);

$data[2] = __('Component Group');
$data[3] = html_print_select(
    $component_group_list,
    'id_group',
    $id_group,
    '',
    '',
    '',
    true,
    false,
    false,
    ''
);

push_table_row($data, 'moduleType-blockName-row');

$data = [];

$data[0] = __('Module unit');
$data[1] = html_print_extended_select_for_unit(
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

push_table_row($data, 'moduleUnit-blockName-row');

$data = [];
$data[0] = __('Warning');
// Warning interval values.
$data[1] = '<span class="minmax_values" id="warning_minmax_values">';
$data[1] .= '<em>'.__('Min.').'</em>&nbsp;';
$data[1] .= html_print_input_text('min_warning', $min_warning, '', 5, 15, true);
$data[1] .= '<br /><em>'.__('Max.').'</em>&nbsp;';
$data[1] .= html_print_input_text('max_warning', $max_warning, '', 5, 15, true);
$data[1] .= '</span>';
// Warning string values.
$data[1] .= '<span class="string_values" id="warning_string_values"><em>'.__('String').' </em>&nbsp;';
$data[1] .= html_print_input_text('str_warning', $str_warning, '', 5, 1024, true).'</span>';
// Warning inverse values.
$data[1] .= '<div id="warning_inverse"><em>'.__('Inverse interval').'</em>&nbsp;';
$data[1] .= html_print_checkbox('warning_inverse', 1, $warning_inverse, true);
$data[1] .= '</div>';

$data[1] .= '<div id="percentage_warning"><em>'.__('Percentage').'</em>&nbsp;';
$data[1] .= ui_print_help_tip('Defines threshold as a percentage of value decrease/increment', true);
$data[1] .= html_print_checkbox('percentage_warning', 1, $percentage_warning, true);
$data[1] .= '</div>';

$data[2] = '<svg id="svg_dinamic" width="500" height="300"> </svg>';
$table->colspan['warning-svg-row'][2] = 2;
$table->rowspan['warning-svg-row'][2] = 3;

push_table_row($data, 'warning-svg-row');

$data = [];
$data[0] .= __('Change to critical status after');
$data[1] .= html_print_input_text('warning_time', $warning_time, '', 5, 15, true);
$data[1] .= '&nbsp;&nbsp;<b>'.__('intervals in warning status.').'</b>';
push_table_row($data, 'title-warning-time');

$data = [];
$data[0] = __('Critical');
// Critical interval values.
$data[1] = '<span class="minmax_values" id="critical_minmax_values">';
$data[1] .= '<em>'.__('Min.').'</em>&nbsp;';
$data[1] .= html_print_input_text(
    'min_critical',
    $min_critical,
    '',
    5,
    15,
    true
);
$data[1] .= '<br /><em>'.__('Max.').'</em>&nbsp;';
$data[1] .= html_print_input_text(
    'max_critical',
    $max_critical,
    '',
    5,
    15,
    true
);
$data[1] .= '</span>';
// Critical string values.
$data[1] .= '<span class="string_values" id="critical_string_values"><em>'.__('String').' </em>&nbsp;';
$data[1] .= html_print_input_text('str_critical', $str_critical, '', 5, 1024, true).'</span>';
// Critical inverse values.
$data[1] .= '<div id="critical_inverse"><em>'.__('Inverse interval').'</em>&nbsp;';
$data[1] .= html_print_checkbox('critical_inverse', 1, $critical_inverse, true);
$data[1] .= '</div>';

$data[1] .= '<Ddiv id="percentage_critical"><em>'.__('Percentage').'</em>&nbsp;';
$data[1] .= ui_print_help_tip('Defines threshold as a percentage of value decrease/increment', true);
$data[1] .= html_print_checkbox('percentage_critical', 1, $percentage_critical, true);
$data[1] .= '</div>';


push_table_row($data, 'critical-row');

$data = [];
$data[0] = __('Description');
$data[1] = html_print_textarea('description', 2, 65, $description, '', true);
$table->colspan['module-description-row'][1] = 3;

push_table_row($data, 'module-description-row');

$data = [];
$data[0] = __('Scan Type');
$data[1] = html_print_select(
    $scan_type_list,
    'scan_type',
    $scan_type,
    '',
    '',
    '',
    true,
    false,
    false,
    ''
);

$data[2] = __('Execution type');
$data[3] = html_print_select(
    $execution_type_list,
    'execution_type',
    $execution_type,
    'manageVisibleFields()',
    '',
    '',
    true,
    false,
    false,
    ''
);

push_table_row($data, 'scan-execution-row');

//
// SNMP rows.
//
$data = [];
$data[0] = __('Value OID');
$data[1] = html_print_input_text_extended(
    'value_oid',
    (string) $value,
    'value_oid',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['value-oid-networkRow-snmpRow'][1] = 3;
push_table_row($data, 'value-oid-networkRow-snmpRow');

$data = [];
$data[0] = __('Macros OID');

push_table_row($data, 'title-oid-macros-pluginRow-snmpRow');

// Generation of extra fields needed.
generateExtraFields($macros, 'snmp');

$data = [];
$data[0] = __('Value operation');
$data[1] = html_print_input_text_extended(
    'value_operation_snmp',
    $macros->value_operation,
    'value_operation_snmp',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['value-operation-pluginRow-snmpRow'][1] = 3;
push_table_row($data, 'value-operation-pluginRow-snmpRow');

$data = [];
$data[0] = __('Satellite execution');
$data[1] = html_print_input_text_extended(
    'satellite_execution_snmp',
    $macros->satellite_execution,
    'satellite_execution_snmp',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['satellite-execution-pluginRow-snmpRow'][1] = 3;
push_table_row($data, 'satellite-execution-pluginRow-snmpRow');

$data = [];
$data[0] = __('Server plugin');
$data[1] = html_print_select(
    $server_plugin_list,
    'server_plugin_snmp',
    $macros->server_plugin,
    'changePlugin()',
    '',
    '',
    true,
    false,
    false,
    ''
).'&nbsp;&nbsp;&nbsp;<span id="selected_plugin_description_snmp"></span>';

push_table_row($data, 'server-plugin-pluginRow-snmpRow');

// The creation of this fields will be dynamically.
$data = [];
$data[0] = 'field0';
$data[1] = html_print_input_text_extended(
    'field0_snmp_field',
    '',
    'field0_snmp_fields',
    '',
    30,
    255,
    '',
    '',
    '',
    true
);

push_table_row($data, 'plugin-snmp-fields-dynamicMacroRow-pluginRow-snmpRow-0');

//
// WMI Fields.
//
$data = [];
$data[0] = __('WMI class');
$data[1] = html_print_input_text_extended(
    'wmi_class',
    $wmi_class,
    'wmi_class',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['wmi-class-wmiRow'][1] = 3;
push_table_row($data, 'wmi-class-wmiRow');

$data = [];
$data[0] = __('Query key field').'&nbsp;(_field_wmi_0_)';
$data[1] = html_print_input_text_extended(
    'query_key_field',
    $query_key_field,
    'query_key_field',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['query-key-field-wmiRow'][1] = 3;
push_table_row($data, 'query-key-field-wmiRow');

$data = [];
$data[0] = __('Query extra fields');

push_table_row($data, 'title-extra-field-wmiRow');

// Generation of extra fields needed.
generateExtraFields($macros, 'wmi');

$data = [];
$data[0] = __('Query filters');
$table->style[0] = 'font-weight: bold;';

push_table_row($data, 'title-query-filters-wmiRow');

$data = [];
$data[0] = '<div class="right">'.__('Scan').'</div>';
$data[1] = html_print_input_text_extended(
    'query_filter_scan',
    $query_filter['scan'],
    'query_filter_scan',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['query-filter-scan-wmiRow'][1] = 3;
push_table_row($data, 'query-filter-scan-wmiRow');

if ($execution_type == EXECUTION_TYPE_NETWORK) {
    $data = [];
    $data[0] = '<div class="right">'.__('Execution').'</div>';
    $data[1] = html_print_input_text_extended(
        'query_filter_execution',
        $query_filter['execution'],
        'query_filter_execution',
        '',
        100,
        10000,
        '',
        '',
        '',
        true
    );
    $table->colspan['query-filter-execution-wmiRow'][1] = 3;
    push_table_row($data, 'query-filter-execution-wmiRow');
}


$data = [];
$data[0] = __('Field value');
$data[1] = html_print_input_number(
    [
        'name'   => 'field_value_filter',
        'value'  => $query_filter['field'],
        'id'     => 'field_value_filter',
        'min'    => 0,
        'return' => true,
    ]
);

$data[2] = __('Key string');
$data[3] = html_print_input_text_extended(
    'key_string_filter',
    $query_filter['key_string'],
    'key_string_filter',
    '',
    30,
    255,
    '',
    '',
    '',
    true
);

push_table_row($data, 'filters-list-fields-networkRow-wmiRow');

$data = [];
$data[0] = __('Value operation');
$data[1] = html_print_input_text_extended(
    'value_operation_wmi',
    $macros->value_operation,
    'value_operation_wmi',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['value-operation-pluginRow-wmiRow'][1] = 3;
push_table_row($data, 'value-operation-pluginRow-wmiRow');

$data = [];
$data[0] = __('Satellite execution');
$data[1] = html_print_input_text_extended(
    'satellite_execution_wmi',
    $macros->satellite_execution,
    'satellite_execution_wmi',
    '',
    100,
    10000,
    '',
    '',
    '',
    true
);
$table->colspan['satellite-execution-pluginRow-wmiRow'][1] = 3;
push_table_row($data, 'satellite-execution-pluginRow-wmiRow');

$data = [];
$data[0] = __('Server plugin');
$data[1] = html_print_select(
    $server_plugin_list,
    'server_plugin_wmi',
    $macros->server_plugin,
    'changePlugin()',
    '',
    '',
    true,
    false,
    false,
    ''
).'&nbsp;&nbsp;&nbsp;<span id="selected_plugin_description_wmi"></span>';

push_table_row($data, 'server-plugin-pluginRow-wmiRow');

// The creation of this fields will be dynamically.
$data = [];
$data[0] = 'field0';
$data[1] = html_print_input_text_extended(
    'field0_wmi_field',
    '',
    'field0_wmi_fields',
    '',
    30,
    255,
    '',
    '',
    '',
    true
);

push_table_row($data, 'plugin-wmi-fields-dynamicMacroRow-pluginRow-wmiRow-0');

?>

<script type="text/javascript">
    // Definition of constants
    const EXECUTION_TYPE_NETWORK          = 
    <?php
    echo '"'.EXECUTION_TYPE_NETWORK.'"';
    ?>
    ;
    const EXECUTION_TYPE_PLUGIN           = 
    <?php
    echo '"'.EXECUTION_TYPE_PLUGIN.'"';
    ?>
    ;
    const MODULE_TYPE_NUMERIC             = 
    <?php
    echo '"'.MODULE_TYPE_NUMERIC.'"';
    ?>
    ;
    const MODULE_TYPE_INCREMENTAL         = 
    <?php
    echo '"'.MODULE_TYPE_INCREMENTAL.'"';
    ?>
    ;
    const MODULE_TYPE_BOOLEAN             = 
    <?php
    echo '"'.MODULE_TYPE_BOOLEAN.'"';
    ?>
    ;
    const MODULE_TYPE_ALPHANUMERIC        = 
    <?php
    echo '"'.MODULE_TYPE_ALPHANUMERIC.'"';
    ?>
    ;

    const MODULE_TYPE_REMOTE_SNMP         = 
    <?php
    echo '"'.MODULE_TYPE_REMOTE_SNMP.'"';
    ?>
    ;
    const MODULE_TYPE_REMOTE_SNMP_INC     = 
    <?php
    echo '"'.MODULE_TYPE_REMOTE_SNMP_INC.'"';
    ?>
    ;
    const MODULE_TYPE_REMOTE_SNMP_STRING  = 
    <?php
    echo '"'.MODULE_TYPE_REMOTE_SNMP_STRING.'"';
    ?>
    ;
    const MODULE_TYPE_REMOTE_SNMP_PROC    = 
    <?php
    echo '"'.MODULE_TYPE_REMOTE_SNMP_PROC.'"';
    ?>
    ;
    const MODULE_TYPE_GENERIC_DATA        = 
    <?php
    echo '"'.MODULE_TYPE_GENERIC_DATA.'"';
    ?>
    ;
    const MODULE_TYPE_GENERIC_PROC        = 
    <?php
    echo '"'.MODULE_TYPE_GENERIC_PROC.'"';
    ?>
    ;
    const MODULE_TYPE_GENERIC_DATA_STRING = 
    <?php
    echo '"'.MODULE_TYPE_GENERIC_DATA_STRING.'"';
    ?>
    ;
    const MODULE_TYPE_GENERIC_DATA_INC    = 
    <?php
    echo '"'.MODULE_TYPE_GENERIC_DATA_INC.'"';
    ?>
    ;

    $(document).ready(function(){        
        // Show the needed fields.
        manageVisibleFields();
        // Show the proper module type
        changeModuleType();
        $(
    "#network_component-plugin-snmp-fields-dynamicMacroRow-pluginRow-snmpRow-0"
  ).attr("style", "display: none;");
        // Change plugin values and macros.
        changePlugin();
    });

</script>