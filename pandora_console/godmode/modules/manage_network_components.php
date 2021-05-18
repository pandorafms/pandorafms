<?php
/**
 * Remote components
 *
 * @category   Remote Components
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

global $config;

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'PM') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_network_components.php';
require_once $config['homedir'].'/include/functions_categories.php';
enterprise_include_once('meta/include/functions_components_meta.php');
require_once $config['homedir'].'/include/functions_component_groups.php';

// Header.
if (is_metaconsole() === true) {
    $sec = 'advanced';

    $id_modulo = (int) get_parameter('id_component_type');
    $new_component = (bool) get_parameter('new_component');
} else {
    $id_modulo = (int) get_parameter('id_component_type');
    $new_component = (bool) get_parameter('new_component');
    if ($id_modulo == COMPONENT_TYPE_NETWORK || $id_modulo == COMPONENT_TYPE_PLUGIN || $id_modulo == COMPONENT_TYPE_WMI || $id_modulo == COMPONENT_TYPE_WIZARD) {
        $help_header = 'local_module_tab';
    } else if (!$new_component) {
        $help_header = 'network_component_tab';
    } else {
        $help_header = 'network_component_tab';
    }

    $sec = 'gmodules';
}

$is_management_allowed = true;
if (is_management_allowed() === false) {
    $is_management_allowed = false;
}

$type = (int) get_parameter('type');
$name = io_safe_input(strip_tags(io_safe_output((string) get_parameter('name'))));
$description = io_safe_input(strip_tags(io_safe_output((string) get_parameter('description'))));
$max = (int) get_parameter('max');
$min = (int) get_parameter('min');
$tcp_send = (string) get_parameter('tcp_send');
$tcp_rcv = (string) get_parameter('tcp_rcv');
$tcp_port = (int) get_parameter('tcp_port');
$snmp_oid = (string) get_parameter('snmp_oid');
$snmp_community = (string) get_parameter('snmp_community');
$id_module_group = (int) get_parameter('id_module_group');
$module_interval = (int) get_parameter('module_interval');
$id_group = (int) get_parameter('id_group');
$plugin_user = (string) get_parameter('plugin_user');
$plugin_pass = io_input_password((string) get_parameter('plugin_pass'));
$plugin_parameter = (string) get_parameter('plugin_parameter');
$macros = (string) get_parameter('macros');
$id_modulo = (int) get_parameter('id_component_type');
$new_component = (bool) get_parameter('new_component');

if (empty($macros) === false) {
    $macros = json_decode(base64_decode($macros), true);

    foreach ($macros as $k => $m) {
        $macros[$k]['value'] = get_parameter($m['macro'], '');
    }

    $macros = io_json_mb_encode($macros);
}

$max_timeout = (int) get_parameter('max_timeout');
$max_retries = (int) get_parameter('max_retries');
$id_plugin = (int) get_parameter('id_plugin');
$dynamic_interval = (int) get_parameter('dynamic_interval');
$dynamic_max = (int) get_parameter('dynamic_max');
$dynamic_min = (int) get_parameter('dynamic_min');
$dynamic_two_tailed = (int) get_parameter('dynamic_two_tailed');
$min_warning = (float) get_parameter('min_warning');
$max_warning = (float) get_parameter('max_warning');
$str_warning = (string) get_parameter('str_warning');
$min_critical = (float) get_parameter('min_critical');
$max_critical = (float) get_parameter('max_critical');
$str_critical = (string) get_parameter('str_critical');
$ff_event = (int) get_parameter('ff_event');
$history_data = (bool) get_parameter('history_data');

// Don't read as (float) because it lost it's decimals when put into MySQL
// where are very big and PHP uses scientific notation, p.e:
// 1.23E-10 is 0.000000000123.
$post_process = (string) get_parameter('post_process', 0.0);

$unit = (string) get_parameter('unit');
$id = (int) get_parameter('id');
$wizard_level = get_parameter('wizard_level', 'nowizard');
$critical_instructions = (string) get_parameter('critical_instructions');
$warning_instructions = (string) get_parameter('warning_instructions');
$unknown_instructions = (string) get_parameter('unknown_instructions');
$critical_inverse = (int) get_parameter('critical_inverse');
$warning_inverse = (int) get_parameter('warning_inverse');
$id_category = (int) get_parameter('id_category');
$id_tag_selected = (array) get_parameter('id_tag_selected');
$pure = get_parameter('pure', 0);
$ff_event_normal = (int) get_parameter('ff_event_normal');
$ff_event_warning = (int) get_parameter('ff_event_warning');
$ff_event_critical = (int) get_parameter('ff_event_critical');
$ff_type = (int) get_parameter('ff_type');
$each_ff = (int) get_parameter('each_ff');

if (count($id_tag_selected) == 1 && empty($id_tag_selected[0])) {
    $tags = '';
} else {
    $tags = implode(',', $id_tag_selected);
}

$snmp_version = (string) get_parameter('snmp_version');
$snmp3_auth_user = (string) io_safe_output(get_parameter('snmp3_auth_user'));
$snmp3_auth_pass = io_input_password((string) get_parameter('snmp3_auth_pass'));
$snmp3_auth_method = (string) get_parameter('snmp3_auth_method');
$snmp3_privacy_method = (string) get_parameter('snmp3_privacy_method');
$snmp3_privacy_pass = io_input_password(
    (string) get_parameter('snmp3_privacy_pass')
);
$snmp3_security_level = (string) get_parameter('snmp3_security_level');

$command_text = (string) get_parameter('command_text');
$command_credential_identifier = (string) get_parameter(
    'command_credential_identifier'
);
$command_os = (string) get_parameter('command_os');

$throw_unknown_events = get_parameter('throw_unknown_events', false);
// Set the event type that can show.
$disabled_types_event = [EVENTS_GOING_UNKNOWN => (int) $throw_unknown_events];
$disabled_types_event = json_encode($disabled_types_event);

$create_component = (bool) get_parameter('create_component');
$update_component = (bool) get_parameter('update_component');
$delete_component = (bool) get_parameter('delete_component');
$duplicate_network_component = (bool) get_parameter(
    'duplicate_network_component'
);
$delete_multiple = (bool) get_parameter('delete_multiple');
$multiple_delete = (bool) get_parameter('multiple_delete', 0);
$create_network_from_module = (bool) get_parameter(
    'create_network_from_module',
    0
);
$create_network_from_snmp_browser = (bool) get_parameter(
    'create_network_from_snmp_browser',
    0
);

if ($is_management_allowed === true && $duplicate_network_component) {
    $source_id = (int) get_parameter('source_id');

    $id = network_components_duplicate_network_component($source_id);
    ui_print_result_message(
        $id,
        __(
            'Successfully created from %s',
            network_components_get_name($source_id)
        ),
        __('Could not be created')
    );

    // List unset for jump the bug in the pagination
    // that the make another copy for each pass into pages.
    unset($_GET['source_id']);
    unset($_GET['duplicate_network_component']);

    $id = 0;
}

// Wizard Common.
$module_enabled   = get_parameter_switch('module_enabled');
$module_protocol  = get_parameter('module_protocol', 'snmp');
$scan_type        = (int) get_parameter('scan_type', SCAN_TYPE_FIXED);
$execution_type   = (int) get_parameter('execution_type', EXECUTION_TYPE_NETWORK);
// Wizard SNMP.
$manufacturer_id  = get_parameter('manufacturer_id');
$name_oid         = get_parameter('name_oid');
$value            = get_parameter('value_oid');
// Other Wizard WMI fields.
$query_filter     = '';
$wmi_class        = get_parameter('wmi_class');
$query_key_field  = get_parameter('query_key_field');
// Enabled Module.
$enabled          = get_parameter_switch('enabled');

if ($id_modulo === COMPONENT_TYPE_WIZARD) {
    // Wizard Common extra fields.
    $macros = [];

    $macros['satellite_execution']  = get_parameter('satellite_execution_'.$module_protocol);
    $macros['value_operation']      = get_parameter('value_operation_'.$module_protocol);
    $macros['server_plugin']        = get_parameter('server_plugin_'.$module_protocol);

    if ($module_protocol === 'snmp') {
        // If not select any manufacturer_id, there is 'all'.
        if (empty($manufacturer_id) === true) {
            $manufacturer_id = 'all';
        }
    } else if ($module_protocol === 'wmi') {
        // Wizard WMI Query filters.
        $query_filter                   = [];
        $query_filter['scan']           = get_parameter('query_filter_scan');
        $query_filter['execution']      = get_parameter('query_filter_execution');
        $query_filter['field']          = get_parameter('field_value_filter');
        $query_filter['key_string']         = get_parameter('key_string_filter');
        $query_filter                   = json_encode($query_filter);
    }

    // Default extra field.
    $extra_fields = [ 'extra_field_1' => '' ];
    // If Plugin execution is selected.
    if ($execution_type === EXECUTION_TYPE_PLUGIN || $module_protocol === 'wmi') {
        // Search all parameters received with extra_fields.
        foreach ($_REQUEST as $parameter => $thisValue) {
            // Extra fields (OIDs Macros or WMI Extra fields).
            if (preg_match('/extra_field_'.$module_protocol.'_/', $parameter) !== 0) {
                $tmpParameter = explode('_', $parameter);
                $extra_fields['extra_field_'.$tmpParameter[3]] = get_parameter($parameter);
            }

            // The plugin macros.
            if (preg_match('/'.$module_protocol.'_field/', $parameter) !== 0) {
                $macros[$parameter] = io_safe_input($thisValue);
            }
        }

        // All of macros saved in the same array.
        $macros = json_encode(array_merge($extra_fields, $macros));
    }
}

$custom_string_1 = '';
$custom_string_2 = '';
$custom_string_3 = '';

// Header.
if (is_metaconsole() === true) {
    components_meta_print_header();
    $sec = 'advanced';
} else {
    if ($id_modulo == 2 || $id_modulo == 4 || $id_modulo == 6) {
        $help_header = 'local_module_tab';
    } else if ($new_component == false && $id == 0) {
        $help_header = '';
    } else {
        $help_header = 'network_component_tab';
    }

    ui_print_page_header(
        __('Module management').' &raquo; '.__('Remote component management'),
        '',
        false,
        $help_header,
        true,
        '',
        false,
        'modulemodal'
    );
    $sec = 'gmodules';
}

if ($is_management_allowed === false) {
    ui_print_warning_message(
        __('This node is configured with centralized mode. All Remote components information is read only. Go to metaconsole to manage it.')
    );
}

if ($type >= MODULE_TYPE_REMOTE_SNMP && $type <= MODULE_TYPE_REMOTE_SNMP_PROC) {
    // New support for snmp v3.
    $tcp_send = $snmp_version;
    $plugin_user = $snmp3_auth_user;
    $plugin_pass = $snmp3_auth_pass;
    $plugin_parameter = $snmp3_auth_method;
    $custom_string_1 = $snmp3_privacy_method;
    $custom_string_2 = $snmp3_privacy_pass;
    $custom_string_3 = $snmp3_security_level;
} else if ($type >= MODULE_TYPE_REMOTE_CMD && $type <= MODULE_TYPE_REMOTE_CMD_INC) {
    $tcp_send = $command_text;
    $custom_string_1 = $command_credential_identifier;
    $custom_string_2 = $command_os;
}

if ($is_management_allowed === true && $create_component) {
    $name_check = db_get_value(
        'name',
        'tnetwork_component',
        'name',
        $name
    );

    if ($name && !$name_check) {
        $id = network_components_create_network_component(
            $name,
            $type,
            $id_group,
            [
                'description'           => $description,
                'module_interval'       => $module_interval,
                'max'                   => $max,
                'min'                   => $min,
                'tcp_send'              => $tcp_send,
                'tcp_rcv'               => $tcp_rcv,
                'tcp_port'              => $tcp_port,
                'snmp_oid'              => $snmp_oid,
                'snmp_community'        => $snmp_community,
                'id_module_group'       => $id_module_group,
                'id_modulo'             => $id_modulo,
                'id_plugin'             => $id_plugin,
                'plugin_user'           => $plugin_user,
                'plugin_pass'           => $plugin_pass,
                'plugin_parameter'      => $plugin_parameter,
                'macros'                => $macros,
                'max_timeout'           => $max_timeout,
                'max_retries'           => $max_retries,
                'history_data'          => $history_data,
                'dynamic_interval'      => $dynamic_interval,
                'dynamic_max'           => $dynamic_max,
                'dynamic_min'           => $dynamic_min,
                'dynamic_two_tailed'    => $dynamic_two_tailed,
                'min_warning'           => $min_warning,
                'max_warning'           => $max_warning,
                'str_warning'           => $str_warning,
                'min_critical'          => $min_critical,
                'max_critical'          => $max_critical,
                'str_critical'          => $str_critical,
                'min_ff_event'          => $ff_event,
                'custom_string_1'       => $custom_string_1,
                'custom_string_2'       => $custom_string_2,
                'custom_string_3'       => $custom_string_3,
                'post_process'          => $post_process,
                'unit'                  => $unit,
                'wizard_level'          => $wizard_level,
                'critical_instructions' => $critical_instructions,
                'warning_instructions'  => $warning_instructions,
                'unknown_instructions'  => $unknown_instructions,
                'critical_inverse'      => $critical_inverse,
                'warning_inverse'       => $warning_inverse,
                'id_category'           => $id_category,
                'tags'                  => $tags,
                'disabled_types_event'  => $disabled_types_event,
                'min_ff_event_normal'   => $ff_event_normal,
                'min_ff_event_warning'  => $ff_event_warning,
                'min_ff_event_critical' => $ff_event_critical,
                'ff_type'               => $ff_type,
                'each_ff'               => $each_ff,
                'manufacturer_id'       => $manufacturer_id,
                'protocol'              => $module_protocol,
                'scan_type'             => $scan_type,
                'execution_type'        => $execution_type,
                'value'                 => $value,
                'query_class'           => $wmi_class,
                'query_key_field'       => $query_key_field,
                'query_filters'         => $query_filter,
                'name_oid'              => $name_oid,
                'module_enabled'        => $module_enabled,
                'enabled'               => $enabled,
            ]
        );
    } else {
        $id = '';
    }

    if ($id === false || !$id) {
        db_pandora_audit(
            'Module management',
            'Fail try to create remote component'
        );

        if ($name_check !== false) {
            // If name exists, advice about it.
            ui_print_error_message(__('Could not be created because the component exists'));
        } else {
            // Other cases.
            ui_print_error_message(__('Could not be created'));
        }

        include_once 'godmode/modules/manage_network_components_form.php';
        return;
    }

    db_pandora_audit('Module management', 'Create network component #'.$id);
    ui_print_success_message(__('Created successfully'));
    $id = 0;
}

if ($is_management_allowed === true && $update_component) {
    $id = (int) get_parameter('id');

    if (!empty($name)) {
        $result = network_components_update_network_component(
            $id,
            [
                'type'                  => $type,
                'name'                  => $name,
                'id_group'              => $id_group,
                'description'           => $description,
                'module_interval'       => $module_interval,
                'max'                   => $max,
                'min'                   => $min,
                'tcp_send'              => $tcp_send,
                'tcp_rcv'               => $tcp_rcv,
                'tcp_port'              => $tcp_port,
                'snmp_oid'              => $snmp_oid,
                'snmp_community'        => $snmp_community,
                'id_module_group'       => $id_module_group,
                'id_modulo'             => $id_modulo,
                'id_plugin'             => $id_plugin,
                'plugin_user'           => $plugin_user,
                'plugin_pass'           => $plugin_pass,
                'plugin_parameter'      => $plugin_parameter,
                'macros'                => $macros,
                'max_timeout'           => $max_timeout,
                'max_retries'           => $max_retries,
                'history_data'          => $history_data,
                'dynamic_interval'      => $dynamic_interval,
                'dynamic_max'           => $dynamic_max,
                'dynamic_min'           => $dynamic_min,
                'dynamic_two_tailed'    => $dynamic_two_tailed,
                'min_warning'           => $min_warning,
                'max_warning'           => $max_warning,
                'str_warning'           => $str_warning,
                'min_critical'          => $min_critical,
                'max_critical'          => $max_critical,
                'str_critical'          => $str_critical,
                'min_ff_event'          => $ff_event,
                'custom_string_1'       => $custom_string_1,
                'custom_string_2'       => $custom_string_2,
                'custom_string_3'       => $custom_string_3,
                'post_process'          => $post_process,
                'unit'                  => $unit,
                'wizard_level'          => $wizard_level,
                'critical_instructions' => $critical_instructions,
                'warning_instructions'  => $warning_instructions,
                'unknown_instructions'  => $unknown_instructions,
                'critical_inverse'      => $critical_inverse,
                'warning_inverse'       => $warning_inverse,
                'id_category'           => $id_category,
                'tags'                  => $tags,
                'disabled_types_event'  => $disabled_types_event,
                'min_ff_event_normal'   => $ff_event_normal,
                'min_ff_event_warning'  => $ff_event_warning,
                'min_ff_event_critical' => $ff_event_critical,
                'ff_type'               => $ff_type,
                'each_ff'               => $each_ff,
                'manufacturer_id'       => $manufacturer_id,
                'protocol'              => $module_protocol,
                'scan_type'             => $scan_type,
                'execution_type'        => $execution_type,
                'value'                 => $value,
                'query_class'           => $wmi_class,
                'query_key_field'       => $query_key_field,
                'query_filters'         => $query_filter,
                'name_oid'              => $name_oid,
                'module_enabled'        => $module_enabled,
                'enabled'               => $enabled,
            ]
        );
    } else {
        $result = '';
    }

    if ($result === false || !$result) {
        db_pandora_audit(
            'Module management',
            'Fail try to update network component #'.$id
        );
        ui_print_error_message(__('Could not be updated'));
        include_once 'godmode/modules/manage_network_components_form.php';
        return;
    }

    db_pandora_audit('Module management', 'Update network component #'.$id);
    ui_print_success_message(__('Updated successfully'));

    $id = 0;
}

if ($is_management_allowed === true && $delete_component) {
    $id = (int) get_parameter('id');

    $result = network_components_delete_network_component($id);

    if ($result) {
        db_pandora_audit(
            'Module management',
            'Delete network component #'.$id
        );
    } else {
        db_pandora_audit(
            'Module management',
            'Fail try to delete network component #'.$id
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
    $id = 0;
}

if ($is_management_allowed === true && $multiple_delete) {
    $ids = (array) get_parameter('delete_multiple', []);

    foreach ($ids as $id) {
        $result = network_components_delete_network_component($id);

        if ($result === false) {
            break;
        }
    }

    $str_ids = implode(',', $ids);
    if ($result) {
        db_pandora_audit(
            'Module management',
            'Multiple delete network component:'.$str_ids
        );
    } else {
        db_pandora_audit(
            'Module management',
            'Fail try to delete network component:'.$str_ids
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully multiple deleted'),
        __('Not deleted. Error deleting multiple data')
    );

    $id = 0;
}

if ($id || $new_component
    || $create_network_from_module
    || $create_network_from_snmp_browser
) {
    include_once $config['homedir'].'/godmode/modules/manage_network_components_form.php';
    return;
}


$search_id_group = (int) get_parameter('search_id_group');
$search_string = (string) get_parameter('search_string');

$url = ui_get_url_refresh(
    [
        'offset'          => false,
        'search_string'   => $search_string,
        'search_id_group' => $search_id_group,
    ],
    true,
    false
);

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';

$table->data = [];

$table->data[0][0] = __('Group');

$component_groups = network_components_get_groups();

if ($component_groups === false) {
    $component_groups = [];
}

foreach ($component_groups as $component_group_key => $component_group_val) {
    $num_components = db_get_num_rows(
        'SELECT id_nc
		FROM tnetwork_component
		WHERE id_group = '.$component_group_key
    );

    $childs = component_groups_get_childrens($component_group_key);

    $num_components_childs = 0;

    if ($childs !== false) {
        foreach ($childs as $child) {
            $num_components_childs += db_get_num_rows(
                'SELECT id
				FROM tlocal_component
				WHERE id_network_component_group = '.$child['id_sg']
            );
        }
    }
}

$table->data[0][1] = html_print_select(
    $component_groups,
    'search_id_group',
    $search_id_group,
    '',
    __('All'),
    0,
    true,
    false,
    false
);
$table->data[0][2] = __('Free Search').ui_print_help_tip(
    __('Search by name, description, tcp send or tcp rcv, list matches.'),
    true
);
$table->data[0][3] = html_print_input_text(
    'search_string',
    $search_string,
    '',
    25,
    255,
    true
);
if (is_metaconsole() === true) {
    $table->data[0][4] = '<div>';
} else {
    $table->data[0][4] = '<div class="action-buttons">';
}

$table->data[0][4] .= html_print_submit_button(
    __('Search'),
    'search',
    false,
    'class="sub search"',
    true
);
$table->data[0][4] .= '</div>';

if (is_metaconsole() === true) {
    $filter = '<form class="filters_form" method="post" action="'.$url.'">';
    $filter .= html_print_table($table, true);
    $filter .= '</form>';
    ui_toggle($filter, __('Show Options'));
} else {
    echo '<form method="post" action="'.$url.'">';
    html_print_table($table);
    echo '</form>';
}

$filter = [];
if ($search_id_group) {
    $filter['id_group'] = $search_id_group;
}

if ($search_string != '') {
    $filter[] = '(name LIKE '."'%".$search_string."%'".'OR description LIKE '."'%".$search_string."%'".'OR tcp_send LIKE '."'%".$search_string."%'".'OR tcp_rcv LIKE '."'%".$search_string."%'".')';
}

$total_components = network_components_get_network_components(
    false,
    $filter,
    'COUNT(*) AS total'
);
$total_components = $total_components[0]['total'];
ui_pagination($total_components, $url);
$filter['offset'] = (int) get_parameter('offset');
$filter['limit'] = (int) $config['block_size'];
$components = network_components_get_network_components(
    false,
    $filter,
    [
        'id_nc',
        'name',
        'description',
        'id_group',
        'type',
        'max',
        'min',
        'module_interval',
        'id_modulo',
    ]
);
if ($components === false) {
    $components = [];
}

unset($table);

$table->width = '100%';
$table->head = [];
$table->class = 'info_table';
if ($is_management_allowed === true) {
    $table->head['checkbox'] = html_print_checkbox(
        'all_delete',
        0,
        false,
        true,
        false
    );
}

$table->head[0] = __('Module name');
$table->head[1] = __('Server');
$table->head[2] = __('Type');
$table->head[3] = __('Description');
$table->head[4] = __('Group');
$table->head[5] = __('Max/Min');
if ($is_management_allowed === true) {
    $table->head[6] = __('Action');
}

$table->size = [];
if ($is_management_allowed === true) {
    $table->size['checkbox'] = '20px';
}

$table->size[1] = '40px';
$table->size[2] = '50px';
if ($is_management_allowed === true) {
    $table->size[6] = '80px';
    $table->align[6] = 'left';
}

$table->data = [];

foreach ($components as $component) {
    $data = [];

    if ($component['max'] == $component['min'] && $component['max'] == 0) {
        $component['max'] = __('N/A');
        $component['min'] = __('N/A');
    }

    if ($is_management_allowed === true) {
        $data['checkbox'] = html_print_checkbox_extended(
            'delete_multiple[]',
            $component['id_nc'],
            false,
            false,
            '',
            'class="check_delete"',
            true
        );

        $data[0] = '<a href="index.php?sec='.$sec.'&sec2=godmode/modules/manage_network_components&id='.$component['id_nc'].'&pure='.$pure.'">';
        $data[0] .= io_safe_output($component['name']);
        $data[0] .= '</a>';
    } else {
        $data[0] = io_safe_output($component['name']);
    }

    switch ($component['id_modulo']) {
        case MODULE_NETWORK:
            $data[1] .= html_print_image(
                'images/op_network.png',
                true,
                [
                    'title' => __('Network module'),
                    'class' => 'invert_filter',
                ]
            );
        break;

        case MODULE_WMI:
            $data[1] .= html_print_image(
                'images/wmi.png',
                true,
                [
                    'title' => __('WMI module'),
                    'class' => 'invert_filter',
                ]
            );
        break;

        case MODULE_PLUGIN:
            $data[1] .= html_print_image(
                'images/plugin.png',
                true,
                [
                    'title' => __('Plug-in module'),
                    'class' => 'invert_filter',
                ]
            );
        break;

        case MODULE_WIZARD:
            $data[1] .= html_print_image(
                'images/wand.png',
                true,
                [
                    'title' => __('Wizard module'),
                    'class' => 'invert_filter',
                ]
            );
        break;

        default:
            // Not possible.
        break;
    }

    $data[2] = ui_print_moduletype_icon($component['type'], true);
    $data[3] = "<span class='font_8px'>".mb_strimwidth(io_safe_output($component['description']), 0, 60, '...').'</span>';
    $data[4] = network_components_get_group_name($component['id_group']);
    $data[5] = $component['max'].' / '.$component['min'];

    if ($is_management_allowed === true) {
        $table->cellclass[][6] = 'action_buttons';
        $data[6] = '<a class="inline_line float-left" href="'.$url.'&search_id_group='.$search_id_group.'search_string='.$search_string.'&duplicate_network_component=1&source_id='.$component['id_nc'].'">'.html_print_image(
            'images/copy.png',
            true,
            [
                'alt'   => __('Duplicate'),
                'title' => __('Duplicate'),
                'class' => 'invert_filter',
            ]
        ).'</a>';
        $data[6] .= '<a href="'.$url.'&delete_component=1&id='.$component['id_nc'].'&search_id_group='.$search_id_group.'search_string='.$search_string.'" onclick="if (! confirm (\''.__('Are you sure?').'\')) return false" >'.html_print_image(
            'images/cross.png',
            true,
            [
                'alt'   => __('Delete'),
                'title' => __('Delete'),
                'class' => 'invert_filter',
            ]
        ).'</a>';
    }

    array_push($table->data, $data);
}

if (isset($data) === true) {
    if ($is_management_allowed === true) {
        echo "<form method='post' action='index.php?sec=".$sec.'&sec2=godmode/modules/manage_network_components&search_id_group=0search_string=&pure='.$pure."'>";
        html_print_input_hidden('multiple_delete', 1);
    }

    html_print_table($table);
    ui_pagination(
        $total_components,
        $url,
        0,
        0,
        false,
        'offset',
        true,
        'pagination-bottom'
    );
    if ($is_management_allowed === true) {
        echo "<div id='btn_delete_5' class='float-right'>";
        html_print_submit_button(
            __('Delete'),
            'delete_btn',
            false,
            'class="sub delete"'
        );
        echo '</div>';
        echo '</form>';
    }
} else {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('There are no defined network components'),
        ]
    );
}

if ($is_management_allowed === true) {
    echo '<form method="post" action="'.$url.'">';
    echo '<div class="right_align mrgn_btn_15px">';
    html_print_input_hidden('new_component', 1);
    html_print_select(
        [
            COMPONENT_TYPE_NETWORK => __('Create a new network component'),
            COMPONENT_TYPE_PLUGIN  => __('Create a new plugin component'),
            COMPONENT_TYPE_WMI     => __('Create a new WMI component'),
            COMPONENT_TYPE_WIZARD  => __('Create a new wizard component'),
        ],
        'id_component_type',
        '',
        '',
        '',
        '',
        ''
    );
    html_print_submit_button(
        __('Create'),
        'crt',
        false,
        'class="sub next mrgn_lft_5px"'
    );
    echo '</div>';
    echo '</form>';
}

enterprise_hook('close_meta_frame');

?>
<script type="text/javascript">
    $( document ).ready(function() {
        $('[id^=checkbox-delete_multiple]').change(function(){
            if($(this).parent().parent().hasClass('checkselected')){
                $(this).parent().parent().removeClass('checkselected');
            }
            else{
                $(this).parent().parent().addClass('checkselected');
            }
        });

        $('[id^=checkbox-all_delete]').change(function(){
            if ($("#checkbox-all_delete").prop("checked")) {
                $('[id^=checkbox-delete_multiple]')
                    .parent()
                    .parent()
                    .addClass('checkselected');
                $(".check_delete")
                    .prop("checked", true);
            }
            else{
                $('[id^=checkbox-delete_multiple]')
                    .parent()
                    .parent()
                    .removeClass('checkselected');
                $(".check_delete").prop("checked", false);
            }
        });
    });

</script>
