<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
enterprise_include_once('include/functions_policies.php');

global $module;

$macros = $module['macros'];

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$classdisabledBecauseInPolicy = '';
$page = get_parameter('page', '');
if (strstr($page, 'policy_modules') === false) {
    if ($config['enterprise_installed']) {
        $disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
    } else {
        $disabledBecauseInPolicy = false;
    }

    if ($disabledBecauseInPolicy) {
        $disabledTextBecauseInPolicy = 'readonly = "readonly"';
        $classdisabledBecauseInPolicy = 'readonly';
    }
}

define('ID_NETWORK_COMPONENT_TYPE', 4);

if (empty($edit_module)) {
    // Function in module_manager_editor_common.php
    add_component_selection(ID_NETWORK_COMPONENT_TYPE);
} else {
    // TODO: Print network component if available
}

$extra_title = __('Plugin server module');

$data = [];
$data[0] = __('Plugin').ui_print_help_icon('plugin_macros', true);
$data[1] = html_print_select_from_sql(
    'SELECT id, name FROM tplugin ORDER BY name',
    'id_plugin',
    $id_plugin,
    'changePluginSelect();',
    __('None'),
    0,
    true,
    false,
    false,
    $disabledBecauseInPolicy
);
// Store the macros in base64 into a hidden control to move between pages
$data[1] .= html_print_input_hidden(
    'macros',
    (isset($macros) === true) ? base64_encode($macros) : '',
    true
);

$table_simple->colspan['plugin_1'][2] = 2;

if (!empty($id_plugin)) {
    $preload = db_get_sql("SELECT description FROM tplugin WHERE id = $id_plugin");
    $preload = io_safe_output($preload);
    $preload = str_replace("\n", '<br>', $preload);
} else {
    $preload = '';
}

$data[2] = '<span class="normal" id="plugin_description">'.$preload.'</span>';

push_table_simple($data, 'plugin_1');

// A hidden "model row" to clone it from javascript to add fields dynamicly
$data = [];
$data[0] = 'macro_desc';
$data[0] .= ui_print_help_tip('macro_help', true);
$data[1] = html_print_input_text('macro_name[]', 'macro_value', '', 100, 1024, true);
$table_simple->colspan['macro_field'][1] = 3;
$table_simple->rowstyle['macro_field'] = 'display:none';

push_table_simple($data, 'macro_field');

$password_fields = [];

// If there are $macros, we create the form fields
if (!empty($macros)) {
    $macros = json_decode(io_safe_output($macros), true);
    foreach ($macros as $k => $m) {
        $data = [];
        $data[0] = $m['desc'];
        if (!empty($m['help'])) {
            $data[0] .= ui_print_help_tip($m['help'], true);
        }

        $m_hide = false;
        if (isset($m['hide'])) {
            $m_hide = $m['hide'];
        }

        if ($m_hide) {
            $data[1] = html_print_input_password(
                $m['macro'],
                io_output_password($m['value']),
                '',
                100,
                1024,
                true,
                false,
                false,
                '',
                'off',
                true
            );
            array_push($password_fields, $m);
        } else {
            $data[1] = html_print_input_text(
                $m['macro'],
                $m['value'],
                '',
                100,
                1024,
                true,
                $disabledBecauseInPolicy,
                false,
                '',
                $classdisabledBecauseInPolicy
            );
        }

        $table_simple->colspan['macro'.$m['macro']][1] = 3;
        $table_simple->rowclass['macro'.$m['macro']] = 'macro_field';

        push_table_simple($data, 'macro'.$m['macro']);
    }
}

// Add input password values with js to hide it in browser inspector.
foreach ($password_fields as $k => $p) {
    echo "
        <script>
            $(document).ready(() => {
                $('input[name=\"".$p['macro']."\"]').val('".$p['value']."');
            });
        </script>
    ";
}

?>
<script type="text/javascript">
    function changePluginSelect() {
        if (typeof flag_load_plugin_component !== 'undefined' && flag_load_plugin_component) {
            flag_load_plugin_component = false;
            
            return;
        }

        const moduleId = <?php echo $id_agent_module; ?>;
        
        load_plugin_description($("#id_plugin").val());
        
        load_plugin_macros_fields('simple-macro', moduleId);
        
        forced_title_callback();

        $('select#id_plugin').select2('close');
    }

    $(document).ready(function () {
        if ($("#id_plugin").val() !== 0) {
            changePluginSelect();
        }
    });
</script>
