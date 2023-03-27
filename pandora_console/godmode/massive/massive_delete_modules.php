<?php
/**
 * View for delete modules in Massive Operations
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
        'Trying to access agent massive deletion'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_agents.php';
require_once 'include/functions_modules.php';
require_once 'include/functions_users.php';

if (is_ajax()) {
    $get_agents = (bool) get_parameter('get_agents');

    if ($get_agents) {
        $id_group = (int) get_parameter('id_group');
        $module_name = (string) get_parameter('module_name');
        $recursion = (int) get_parameter('recursion');

        $agents_modules = modules_get_agents_with_module_name(
            $module_name,
            $id_group,
            [
                'delete_pending'          => 0,
                'tagente_modulo.disabled' => 0,
            ],
            [
                'tagente.id_agente',
                'tagente.alias',
            ],
            $recursion
        );

        echo json_encode(index_array($agents_modules, 'id_agente', 'alias'));
        return;
    }

    return;
}


function process_manage_delete($module_name, $id_agents, $module_status='all')
{
    global $config;

    $status_module = (int) get_parameter('status_module');

    if (empty($module_name)) {
        ui_print_error_message(__('No module selected'));
        return false;
    }

    if (empty($id_agents)) {
        ui_print_error_message(__('No agents selected'));
        return false;
    }

    $module_name = (array) $module_name;

    // We are selecting "any" agent for the selected modules
    if (($id_agents[0] == 0) and (is_array($id_agents)) and (count($id_agents) == 1)) {
        $id_agents = null;
    }

    $selection_delete_mode = get_parameter('selection_mode', 'modules');

    // Selection mode by Agents
    if ($selection_delete_mode == 'agents') {
        // We are selecting "any" module for the selecteds agents
        if (($module_name[0] == '0') and (is_array($module_name)) and (count($module_name) == 1)) {
            if ($status_module != -1) {
                $filter_for_module_deletion = sprintf('tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_estado where estado = %s OR utimestamp=0 )', $status_module);
            } else {
                $filter_for_module_deletion = false;
            }
        } else {
            $filter_for_module_deletion = sprintf('tagente_modulo.nombre IN ("%s")', implode('","', $module_name));
        }

        $modules = agents_get_modules(
            $id_agents,
            'id_agente_modulo',
            $filter_for_module_deletion,
            true
        );
    } else {
        if ($status_module != -1) {
            $modules = agents_get_modules(
                $id_agents,
                'id_agente_modulo',
                sprintf('tagente_modulo.nombre IN ("%s") AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_estado where estado = %s OR utimestamp=0 )', implode('","', $module_name), $status_module),
                true
            );
        } else {
            $modules = agents_get_modules(
                $id_agents,
                'id_agente_modulo',
                'tagente_modulo.nombre IN ("'.implode('","', $module_name).'")',
                true
            );
        }
    }

    if (($module_status == 'unknown') && ($module_name[0] == '0') && (is_array($module_name)) && (count($module_name) == 1)) {
        $modules_to_delete = [];
        foreach ($modules as $mod_id) {
            $mod_status = (int) db_get_value_filter('estado', 'tagente_estado', ['id_agente_modulo' => $mod_id]);

            // Unknown, not init and no data modules
            if ($mod_status == 3 || $mod_status == 4 || $mod_status == 5) {
                $modules_to_delete[$mod_id] = $mod_id;
            }
        }

        $modules = $modules_to_delete;
    }

    $count_deleted_modules = count($modules);
    $success = modules_delete_agent_module($modules);

    if (! $success) {
        ui_print_error_message(
            __('There was an error deleting the modules, the operation has been cancelled')
        );

        return false;
    } else {
        ui_print_success_message(
            __('Successfully deleted').'&nbsp;('.$count_deleted_modules.')'
        );

        return true;
    }
}


$module_type = (int) get_parameter('module_type');
$group_select = get_parameter('groups_select');

$delete = (bool) get_parameter_post('delete');
$module_name = get_parameter('module_name');
$agents_select = get_parameter('agents');
$agents_id = get_parameter('id_agents');
$modules_select = get_parameter('module');
$selection_mode = get_parameter('selection_mode', 'modules');
$recursion = get_parameter('recursion');
$modules_selection_mode = get_parameter('modules_selection_mode');

if ($delete) {
    switch ($selection_mode) {
        case 'modules':
            $force = get_parameter('force_type', false);

            if ($agents_select == false) {
                $agents_select = [];
                $agents_ = [];
            }

            $agents_ = $agents_select;
            $modules_ = $module_name;
        break;

        case 'agents':
            $force = get_parameter('force_group', false);

            $agents_ = $agents_id;
            $modules_ = $modules_select;
        break;
    }

    $count = 0;
    $success = 0;

    // If the option to select all of one group or module type is checked
    if ($force) {
        if ($force == 'type') {
            $condition = '';
            if ($module_type != 0) {
                $condition = ' AND t2.id_tipo_modulo = '.$module_type;
            }

            $groups = users_get_groups($config['id_user'], 'AW', false);
            $group_id_list = ($groups ? join(',', array_keys($groups)) : '0');
            $condition = ' AND t1.id_grupo IN ('.$group_id_list.') ';

            $agents_ = db_get_all_rows_sql(
                'SELECT DISTINCT(t1.id_agente)
				FROM tagente t1, tagente_modulo t2
				WHERE t1.id_agente = t2.id_agente AND t2.delete_pending = 0 '.$condition
            );
            foreach ($agents_ as $id_agent) {
                $module_name = db_get_all_rows_filter('tagente_modulo', ['id_agente' => $id_agent['id_agente'], 'id_tipo_modulo' => $module_type, 'delete_pending' => 0], 'nombre');

                if ($module_name == false) {
                    $module_name = [];
                }

                foreach ($module_name as $mod_name) {
                    $result = process_manage_delete($mod_name['nombre'], $id_agent['id_agente'], $modules_selection_mode);
                    $count++;
                    $success += (int) $result;
                }
            }
        } else if ($force == 'group') {
            if ($group_select == 0) {
                $agents_ = array_keys(agents_get_group_agents(array_keys(users_get_groups($config['id_user'], 'AW', false)), false, 'none'));
            } else {
                $agents_ = array_keys(agents_get_group_agents($group_select, false, 'none'));
            }

            foreach ($agents_ as $id_agent) {
                $module_name = db_get_all_rows_filter('tagente_modulo', ['id_agente' => $id_agent], 'nombre');
                if ($module_name == false) {
                    $module_name = [];
                } else {
                    $result = process_manage_delete([0 => 0], $id_agent, $modules_selection_mode);
                }

                $success += (int) $result;
            }
        }

        // We empty the agents array to skip the standard procedure
        $agents_ = [];
    }

    if (!$force) {
        $result = false;
        $result = process_manage_delete($modules_, $agents_, $modules_selection_mode);
    }

    $info = [
        'Agent'  => implode(',', $agents_),
        'Module' => implode(',', $modules_),
    ];

    $auditMessage = ((bool) $result === true) ? 'Delete module' : 'Fail try to delete module';

    db_pandora_audit(
        AUDIT_LOG_MASSIVE_MANAGEMENT,
        $auditMessage,
        false,
        false,
        json_encode($info)
    );
}

$groups = users_get_groups();

$agents = agents_get_group_agents(
    array_keys(users_get_groups()),
    false,
    'none'
);
$module_types = db_get_all_rows_filter(
    'tagente_modulo,ttipo_modulo',
    [
        'tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
        'id_agente' => array_keys($agents),
        'disabled' => 0,
        'order' => 'ttipo_modulo.nombre'
    ],
    [
        'DISTINCT(id_tipo)',
        'CONCAT(ttipo_modulo.descripcion," (",ttipo_modulo.nombre,")") AS description',
    ]
);

if ($module_types === false) {
    $module_types = [];
}

$types = [];
foreach ($module_types as $type) {
    $types[$type['id_tipo']] = $type['description'];
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->data = [];
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';

$table->data['selection_mode'][0] = __('Selection mode');
$table->data['selection_mode'][1] = '<span class="massive_span">'.__('Select modules first ').'</span>'.html_print_radio_button_extended('selection_mode', 'modules', '', $selection_mode, false, '', 'class="mrgn_right_40px"', true).'<br>';
$table->data['selection_mode'][1] .= '<span class="massive_span">'.__('Select agents first ').'</span>'.html_print_radio_button_extended('selection_mode', 'agents', '', $selection_mode, false, '', 'class="mrgn_right_40px"', true);

$table->rowclass['form_modules_1'] = 'select_modules_row';
$table->data['form_modules_1'][0] = __('Module type');
$table->data['form_modules_1'][0] .= '<span id="module_loading" class="invisible">';
$table->data['form_modules_1'][0] .= html_print_image('images/spinner.png', true);
$table->data['form_modules_1'][0] .= '</span>';
$types[0] = __('All');
$table->colspan['form_modules_1'][1] = 2;
$table->data['form_modules_1'][1] = html_print_select(
    $types,
    'module_type',
    '',
    false,
    __('Select'),
    -1,
    true,
    false,
    true,
    '',
    false,
    'width:100%'
);
$table->data['form_modules_1'][3] = __('Select all modules of this type').' '.html_print_checkbox_extended(
    'force_type',
    'type',
    '',
    '',
    false,
    'class="mrgn_right_40px"',
    true,
    ''
);

$modules = [];
if ($module_type != '') {
    $filter = ['id_tipo_modulo' => $module_type];
} else {
    $filter = false;
}

$names = agents_get_modules(
    array_keys($agents),
    'tagente_modulo.nombre',
    $filter,
    false
);
foreach ($names as $name) {
    $modules[$name['nombre']] = $name['nombre'];
}

$table->rowclass['form_agents_1'] = 'select_agents_row';
$table->data['form_agents_1'][0] = __('Agent group');
$groups = users_get_groups($config['id_user'], 'AW', false);
$groups[0] = __('All');
$table->colspan['form_agents_1'][1] = 2;
$table->data['form_agents_1'][1] = html_print_select_groups(
    false,
    'AW',
    true,
    'groups_select',
    '',
    false,
    '',
    '',
    true
).' '.__('Group recursion').' '.html_print_checkbox('recursion', 1, false, true, false);
$table->data['form_agents_1'][3] = __('Select all modules of this group').' '.html_print_checkbox_extended(
    'force_group',
    'group',
    '',
    '',
    false,
    '',
    'class="mrgn_right_40px"',
    true
);

$tags = tags_get_user_tags();
$table->rowstyle['form_modules_4'] = 'vertical-align: top;';
$table->rowclass['form_modules_4'] = 'select_modules_row select_modules_row_2';
$table->data['form_modules_4'][0] = __('Tags');
$table->data['form_modules_4'][1] = html_print_select(
    $tags,
    'tags[]',
    $tags_name,
    false,
    __('Any'),
    -1,
    true,
    true,
    true
);

$table->rowclass['form_agents_2'] = 'select_agents_row';
$table->data['form_agents_2'][0] = __('Status');
$table->colspan['form_agents_2'][1] = 2;
$status_list = [];
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data['form_agents_2'][1] = html_print_select(
    $status_list,
    'status_agents',
    'selected',
    '',
    __('All'),
    AGENT_STATUS_ALL,
    true
);
$table->data['form_agents_2'][3] = '';

$table->rowclass['form_modules_3'] = '';
$table->data['form_modules_3'][0] = __('Module Status');
$table->colspan['form_modules_3'][1] = 2;
$status_list = [];
$status_list[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$status_list[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$status_list[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');
$table->data['form_modules_3'][1] = html_print_select(
    $status_list,
    'status_module',
    'selected',
    '',
    __('All'),
    AGENT_MODULE_STATUS_ALL,
    true
);
$table->data['form_modules_3'][3] = '';

$table->rowstyle['form_modules_filter'] = 'vertical-align: top;';
$table->rowclass['form_modules_filter'] = 'select_modules_row select_modules_row_2';
$table->data['form_modules_filter'][0] = __('Filter Modules');
$table->data['form_modules_filter'][1] = html_print_input_text('filter_modules', '', '', 20, 255, true);

$table->rowstyle['form_modules_2'] = 'vertical-align: top;';
$table->rowclass['form_modules_2'] = 'select_modules_row select_modules_row_2';
$table->data['form_modules_2'][0] = __('Modules');
$table->data['form_modules_2'][1] = html_print_select(
    $modules,
    'module_name[]',
    $module_name,
    false,
    __('Select'),
    -1,
    true,
    true,
    true,
    '',
    false,
    'width:100%'
).' '.__('Select all modules').' '.html_print_checkbox('select_all_modules', 1, false, true, false, '', false, "class='static'");

$table->data['form_modules_2'][2] = __('When select modules');
$table->data['form_modules_2'][2] .= '<br>';
$table->data['form_modules_2'][2] .= html_print_select(
    [
        'common' => __('Show common agents'),
        'all'    => __('Show all agents'),
    ],
    'agents_selection_mode',
    'common',
    false,
    '',
    '',
    true,
    false,
    true,
    '',
    false
);
$table->data['form_modules_2'][3] = html_print_select(
    [],
    'agents[]',
    $agents_select,
    false,
    __('None'),
    0,
    true,
    true,
    false,
    '',
    false,
    'width:100%'
);

$tags = tags_get_user_tags();
$table->rowstyle['form_agents_4'] = 'vertical-align: top;';
$table->rowclass['form_agents_4'] = 'select_agents_row select_agents_row_2';
$table->data['form_agents_4'][0] = __('Tags');
$table->data['form_agents_4'][1] = html_print_select(
    $tags,
    'tags[]',
    $tags_name,
    false,
    __('Any'),
    -1,
    true,
    true,
    true
);

$table->rowstyle['form_agents_filter'] = 'vertical-align: top;';
$table->rowclass['form_agents_filter'] = 'select_agents_row select_agents_row_2';
$table->data['form_agents_filter'][0] = __('Filter Agents');
$table->data['form_agents_filter'][1] = html_print_input_text('filter_agents', '', '', 20, 255, true);

$table->rowstyle['form_agents_3'] = 'vertical-align: top;';
$table->rowclass['form_agents_3'] = 'select_agents_row select_agents_row_2';
$table->data['form_agents_3'][0] = __('Agents');
$table->data['form_agents_3'][1] = html_print_select(
    $agents,
    'id_agents[]',
    $agents_id,
    false,
    '',
    '',
    true,
    true,
    false,
    '',
    false,
    'width:100%'
).' '.__('Select all agents').' '.html_print_checkbox('select_all_agents', 1, false, true, false, '', false, "class='static'");

$table->data['form_agents_3'][2] = __('When select agents');
$table->data['form_agents_3'][2] .= '<br>';
$table->data['form_agents_3'][2] .= html_print_select(
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
$table->data['form_agents_3'][3] = html_print_select(
    [],
    'module[]',
    $modules_select,
    false,
    '',
    '',
    true,
    true,
    false,
    '',
    false,
    'width:100%'
);



echo '<form method="post" id="form_modules" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_modules" >';
html_print_table($table);

attachActionButton('delete', 'delete', $table->width, false, $SelectAction);

echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_jquery_file('form');
// Hack to translate text "none" in PHP to javascript
echo '<span id ="none_text" class="invisible">'.__('None').'</span>';
echo '<span id ="select_agent_first_text" class="invisible">'.__('Please, select an agent first').'</span>';

// Load JS files.
ui_require_javascript_file('pandora_modules');
ui_require_jquery_file('pandora.controls');

if ($selection_mode == 'modules') {
    $modules_row = '';
    $agents_row = 'none';
} else {
    $modules_row = 'none';
    $agents_row = '';
}
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {

    $("#checkbox-select_all_modules").change(function() {
        if( $('#checkbox-select_all_modules').prop('checked')) {
            $("#module_name option").prop('selected', 'selected');
            $("#module_name").trigger('change');
        } else {
            $("#module_name option").prop('selected', false);
            $("#module_name").trigger('change');
        }
    });

    $("#module_name").change(function() {
        var options_length = $("#module_name option").length;
        var options_selected_length = $("#module_name option:selected").length;

        if (options_selected_length < options_length) {
            $('#checkbox-select_all_modules').prop("checked", false);
        }
    });

    $("#checkbox-select_all_agents").change(function() {
        if( $('#checkbox-select_all_agents').prop('checked')) {
            $("#id_agents option").prop('selected', 'selected');
            $("#id_agents").trigger('change');
        } else {
            $("#id_agents option").prop('selected', false);
            $("#id_agents").trigger('change');
        }
    });

    $("#id_agents").change(function() {
        var options_length = $("#id_agents option").length;
        var options_selected_length = $("#id_agents option:selected").length;

        if (options_selected_length < options_length) {
            $('#checkbox-select_all_agents').prop("checked", false);
        }
    });

    $("#id_agents").change(agent_changed_by_multiple_agents);
    $("#module_name").change(module_changed_by_multiple_modules);
    
    clean_lists();
    
    $(".select_modules_row")
        .css('display', '<?php echo $modules_row; ?>');
    $(".select_agents_row")
        .css('display', '<?php echo $agents_row; ?>');
    $(".select_modules_row_2").css('display', 'none');
    
    // Trigger change to refresh selection when change selection mode
    $("#agents_selection_mode").change (function() {
        $("#module_name").trigger('change');
    });
    $("#modules_selection_mode").change (function() {
        $("#id_agents").trigger('change');
    });
    
    $("#module_type").change (function () {
        $('#checkbox-force_type').attr('checked', false);
        if (this.value < 0) {
            clean_lists();
            $(".select_modules_row_2").css('display', 'none');
            return;
        }
        else {
            $("#module").html('<?php echo __('None'); ?>');
            $("#module_name").html('');
            $('input[type=checkbox]').removeAttr('disabled');
            $(".select_modules_row_2").css('display', '');
        }
        
        $("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8")
            .hide ();
        
        var params = {
            "page" : "operation/agentes/ver_agente",
            "get_agent_modules_json" : 1,
            "truncate_module_names": 1,
            "get_distinct_name" : 1,
            "indexed" : 0,
            "privilege" : "AW",
            "safe_name": 1
        };
        
        if (this.value != '0')
            params['id_tipo_modulo'] = this.value;
        
        var status_module = $('#status_module').val();
        if (status_module != '-1')
            params['status_module'] = status_module;

        var tags_to_search = $('#tags').val();
        if (tags_to_search != null) {
            if (tags_to_search[0] != -1) {
                params['tags'] = tags_to_search;
            }
        }
        
        showSpinner();
        $("tr#delete_table-edit1, tr#delete_table-edit2").hide ();
        $("#module_name").attr ("disabled", "disabled")
        $("#module_name option[value!=0]").remove ();
        jQuery.post ("ajax.php",
            params,
            function (data, status) {
                jQuery.each (data, function (id, value) {
                    option = $("<option></option>")
                        .attr({value: value["nombre"], title: value["nombre"]})
                        .html(value["safe_name"]);
                    $("#module_name").append (option);
                });
                hideSpinner();
                $("#module_name").removeAttr ("disabled");
                //Filter modules. Call the function when the select is fully loaded.
                var textNoData = "<?php echo __('None'); ?>";
                filterByText($('#module_name'), $("#text-filter_modules"), textNoData);
            },
            "json"
        );
    });
    
    function clean_lists() {
        $("#id_agents").html('<?php echo __('None'); ?>');
        $("#module_name").html('<?php echo __('None'); ?>');
        $("#agents").html('<?php echo __('None'); ?>');
        $("#module").html('<?php echo __('None'); ?>');
        $('input[type=checkbox]').attr('checked', false);
        $('input[type=checkbox]').attr('disabled', true);
        $('#module_type').val(-1);
        $('#groups_select').val(-1);
    }
    
    $('input[type=checkbox]').not(".static").change (
        function () {
            if (this.id == "checkbox-force_type") {
                if (this.checked) {
                    $(".select_modules_row_2").css('display', 'none');
                    $("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").show ();
                }
                else {
                    $(".select_modules_row_2").css('display', '');
                    if ($('#module_name option:selected').val() == undefined) {
                        $("tr#delete_table-edit1, tr#delete_table-edit2, tr#delete_table-edit3, tr#delete_table-edit35, tr#delete_table-edit4, tr#delete_table-edit5, tr#delete_table-edit6, tr#delete_table-edit7, tr#delete_table-edit8").hide ();
                    }
                }
            }
            else if (this.id == "checkbox-recursion") {
                $("#groups_select").trigger("change");
            }
            else {
                if (this.checked) {
                    $(".select_agents_row_2").css('display', 'none');
                }
                else {
                    $(".select_agents_row_2").css('display', '');
                }
            }
        }
    );
    
    $("#form_modules input[name=selection_mode]").change (function () {
        selector = this.value;
        clean_lists();
        
        if (selector == 'agents') {
            $(".select_modules_row").hide();
            $(".select_agents_row").show();
            $("#groups_select").trigger("change");
        }
        else if (selector == 'modules') {
            $(".select_agents_row").hide();
            $(".select_modules_row").show();
            $("#module_type").trigger("change");
        }
    });

    var recursion;

    $("#checkbox-recursion").click(function () {
        recursion = this.checked ? 1 : 0;
    });

    $("#groups_select").change (
        function () {
            $('#checkbox-force_group').attr('checked', false);
            if (this.value < 0) {
                clean_lists();
                $(".select_agents_row_2").css('display', 'none');
                return;
            }
            else {
                $("#module").html('<?php echo __('None'); ?>');
                $("#id_agents").html('');
                $('input[type=checkbox]').removeAttr('disabled');
                $(".select_agents_row_2").css('display', '');
            }
            
            jQuery.post ("ajax.php",
                {"page" : "operation/agentes/ver_agente",
                    "get_agents_group_json" : 1,
                    "recursion" : recursion,
                    "id_group" : this.value,
                    "privilege" : "AW",
                    status_agents: function () {
                        return $("#status_agents").val();
                    },
                    // Add a key prefix to avoid auto sorting in js object conversion
                    "keys_prefix" : "_"
                },
                function (data, status) {
                    $("#id_agents").html('');
                    jQuery.each (data, function (id, value) {
                        // Remove keys_prefix from the index
                        id = id.substring(1);
                        
                        option = $("<option></option>")
                            .attr ("value", value["id_agente"])
                            .html (value["alias"]);
                        $("#id_agents").append (option);
                    });
                    //Filter agents. Call the function when the select is fully loaded.
                    var textNoData = "<?php echo __('None'); ?>";
                    filterByText($('#id_agents'), $("#text-filter_agents"), textNoData);
                },
                "json"
            );
        }
    );
    
    $("#status_agents").change(function() {
        $("#groups_select").trigger("change");
    });

    $("#tags").change(function() {
        selector = $("#form_edit input[name=selection_mode]:checked").val();
        $("#module_type").trigger("change");
    });
    $("#tags1").change(function() {
        selector = $("#form_edit input[name=selection_mode]:checked").val();
        $("#id_agents").trigger("change");
    });
        
    if("<?php echo $delete; ?>"){
        if("<?php echo $selection_mode; ?>" == 'agents'){
            $("#groups_select").trigger("change");
        }
    }

    $("#status_module").change(function() {
        selector = $("#form_modules input[name=selection_mode]:checked").val();
        if(selector == 'agents') {
            $("#id_agents").trigger("change");
        }
        else if(selector == 'modules') {
            $("#module_type").trigger("change");
        }
    });
    
    $('#agents').change(function(e){
        for(var i=0;i<document.forms["form_modules"].agents.length;i++)    {
            
            if(document.forms["form_modules"].agents[0].selected == true){
                var any = true;
            }
            if(i != 0 && document.forms["form_modules"].agents[i].selected){
                    var others = true;
            }
            if(any && others){
                    document.forms["form_modules"].agents[0].selected = false;
            }    
        }
    });
    
    $('#module').change(function(e){
        for(var i=0;i<document.forms["form_modules"].module.length;i++)    {
            
            if(document.forms["form_modules"].module[0].selected == true){
                var any = true;
            }
            if(i != 0 && document.forms["form_modules"].module[i].selected){
                    var others = true;
            }
            if(any && others){
                    document.forms["form_modules"].module[0].selected = false;
            }    
        }
    });
        
});
/* ]]> */
</script>

