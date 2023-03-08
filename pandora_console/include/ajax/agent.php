<?php

// Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_reporting.php';
enterprise_include_once('include/functions_metaconsole.php');

// Clean the possible blanks introduced by the included files
ob_clean();

// Get list of agent + ip
// Params:
// * search_agents 1
// * id_agent
// * q
// * id_group
$search_agents = (bool) get_parameter('search_agents');
$get_agents_interfaces = (bool) get_parameter('get_agents_interfaces');
$id_agents = get_parameter('id_agents', []);
$get_agents_group = (bool) get_parameter('get_agents_group', false);
$force_local = (bool) get_parameter('force_local', false);

// Agent detail filter.
$load_filter_modal = get_parameter('load_filter_modal', 0);
$save_filter_modal = get_parameter('save_filter_modal', 0);
$get_agent_filters = get_parameter('get_agent_filters', 0);
$save_agent_filter = get_parameter('save_agent_filter', 0);
$update_agent_filter = get_parameter('update_agent_filter', 0);
$delete_agent_filter = get_parameter('delete_agent_filter', 0);

if (https_is_running()) {
    header('Content-type: application/json');
}

if ($get_agents_interfaces) {
    $agents_interfaces = agents_get_network_interfaces($id_agents);

    // Include alias per agent.
    foreach ($agents_interfaces as $key => $value) {
        $agent_alias = agents_get_alias($key);
        $agents_interfaces[$key]['agent_alias'] = $agent_alias;
    }

    echo json_encode($agents_interfaces);

    return;
}

if ($get_agents_group) {
    $id_group = (int) get_parameter('id_group', -1);
    $mode = (string) get_parameter('mode', 'json');
    $id_server = (int) get_parameter('id_server', 0);
    $serialized = (bool) get_parameter('serialized');

    $return = [];
    if ($id_group != -1) {
        $filter = [];

        if (is_metaconsole() && !empty($id_server)) {
            $filter['id_server'] = $id_server;
        }

        $return = agents_get_group_agents($id_group, $filter, 'none', false, false, $serialized);
    }

    switch ($mode) {
        case 'json':
        default:
            echo json_encode($return);
        break;
    }

    return;
}

if ($search_agents && (!is_metaconsole() || $force_local)) {
    $id_agent = (int) get_parameter('id_agent');
    $string = (string) get_parameter('q');
    $string = strtoupper($string);
    // q is what autocomplete plugin gives
    $id_group = (int) get_parameter('id_group', -1);
    $addedItems = html_entity_decode((string) get_parameter('add'));
    $addedItems = json_decode($addedItems);
    $all = (string) get_parameter('all', 'all');

    $delete_offspring_agents = (int) get_parameter('delete_offspring_agents', 0);

    if ($addedItems != null) {
        foreach ($addedItems as $item) {
            echo $item."|\n";
        }
    }

    $filter = [];

    if ($id_group != -1) {
        if ($id_group == 0) {
            $user_groups = users_get_groups($config['id_user'], 'AR', true);

            $filter['id_grupo'] = array_keys($user_groups);
        } else {
            $filter['id_grupo'] = $id_group;
        }
    }

    if ($all === 'enabled') {
        $filter['disabled'] = 1;
    } else {
        $filter['disabled'] = 0;
    }

    $data = [];
    // Get agents for only the alias.
    $filter_alias = $filter;
    $filter_alias[] = '(UPPER(alias) LIKE "%'.$string.'%")';

    $agents = agents_get_agents($filter_alias, ['id_agente', 'nombre', 'direccion', 'alias']);
    if ($agents !== false) {
        foreach ($agents as $agent) {
            $data[] = [
                'id'     => $agent['id_agente'],
                'name'   => io_safe_output($agent['nombre']),
                'alias'  => io_safe_output($agent['alias']),
                'ip'     => io_safe_output($agent['direccion']),
                'filter' => 'alias',
            ];
        }
    }

    // Get agents for only the name.
    $filter_agents = $filter;
    $filter_agents[] = '(UPPER(alias) NOT LIKE "%'.$string.'%" AND UPPER(nombre) LIKE "%'.$string.'%")';

    $agents = agents_get_agents($filter_agents, ['id_agente', 'nombre', 'direccion', 'alias']);
    if ($agents !== false) {
        foreach ($agents as $agent) {
            $data[] = [
                'id'     => $agent['id_agente'],
                'name'   => io_safe_output($agent['nombre']),
                'alias'  => io_safe_output($agent['alias']),
                'ip'     => io_safe_output($agent['direccion']),
                'filter' => 'agent',
            ];
        }
    }

    // Get agents for only the address.
    $filter_address = $filter;
    $filter_address[] = '(UPPER(alias) NOT LIKE "%'.$string.'%" AND UPPER(nombre) NOT LIKE "%'.$string.'%" AND UPPER(direccion) LIKE "%'.$string.'%")';

    $agents = agents_get_agents($filter_address, ['id_agente', 'nombre', 'direccion', 'alias']);
    if ($agents !== false) {
        foreach ($agents as $agent) {
            $data[] = [
                'id'     => $agent['id_agente'],
                'name'   => io_safe_output($agent['nombre']),
                'alias'  => io_safe_output($agent['alias']),
                'ip'     => io_safe_output($agent['direccion']),
                'filter' => 'address',
            ];
        }
    }

    // Get agents for only the description.
    $filter_description = $filter;
    $filter_description[] = '(UPPER(alias) NOT LIKE "%'.$string.'%" AND UPPER(nombre) NOT LIKE "%'.$string.'%" AND UPPER(direccion) NOT LIKE "%'.$string.'%" AND UPPER(comentarios) LIKE "%'.$string.'%")';

    $agents = agents_get_agents($filter_description, ['id_agente', 'nombre', 'direccion', 'alias']);
    if ($agents !== false) {
        foreach ($agents as $agent) {
            $data[] = [
                'id'     => $agent['id_agente'],
                'name'   => io_safe_output($agent['nombre']),
                'alias'  => io_safe_output($agent['alias']),
                'ip'     => io_safe_output($agent['direccion']),
                'filter' => 'description',
            ];
        }
    }

    if (empty($data) === false && $delete_offspring_agents !== 0) {
        // Gets offspring and deletes them, including himself.
        $agents_offspring = agents_get_offspring($delete_offspring_agents);
        if (empty($agents_offspring) === false) {
            foreach ($data as $key => $value) {
                if (isset($agents_offspring[$value['id']]) === true) {
                    unset($data[$key]);
                }
            }
        }
    }

    echo json_encode($data);
    return;
} else if ($search_agents && is_metaconsole()) {
    $id_agent = (int) get_parameter('id_agent');
    $string = (string) get_parameter('q');
    // Q is what autocomplete plugin gives.
    $id_group = (int) get_parameter('id_group', -1);
    $addedItems = html_entity_decode((string) get_parameter('add'));
    $addedItems = json_decode($addedItems);
    $all = (string) get_parameter('all', 'all');

    if ($addedItems != null) {
        foreach ($addedItems as $item) {
            echo $item."|\n";
        }
    }

    $data = [];

    $fields = [
        'id_tagente AS id_agente',
        'nombre',
        'alias',
        'direccion',
        'id_tmetaconsole_setup AS id_server',
        'server_name',
    ];

    $filter = [];

    if ($id_group != -1) {
        if ($id_group == 0) {
            $user_groups = users_get_groups($config['id_user'], 'AR', true);

            $filter['id_grupo'] = array_keys($user_groups);
        } else {
            $filter['id_grupo'] = $id_group;
        }
    }

    switch ($all) {
        case 'enabled':
            $filter['disabled'] = 0;
        break;

        default:
            // Not possible.
        break;
    }

    if (empty($id_agent) === false) {
        $filter['id_agente'] = $id_agent;
    }

    if (empty($string) === false) {
        // Get agents for only the alias.
        $filter_alias = $filter;
        $filter_alias[] = '(alias LIKE "%'.$string.'%")';

        $agents = db_get_all_rows_filter(
            'tmetaconsole_agent',
            $filter_alias,
            $fields
        );

        if ($agents !== false) {
            foreach ($agents as $agent) {
                $data[] = [
                    'id'        => $agent['id_agente'],
                    'name'      => io_safe_output($agent['nombre']),
                    'alias'     => io_safe_output($agent['alias']).' ('.io_safe_output($agent['server_name']).')',
                    'ip'        => io_safe_output($agent['direccion']),
                    'id_server' => $agent['id_server'],
                    'filter'    => 'alias',
                ];
            }
        }

        // Get agents for only the name.
        $filter_agents = $filter;
        $filter_agents[] = '(alias NOT LIKE "%'.$string.'%" AND nombre LIKE "%'.$string.'%")';

        $agents = db_get_all_rows_filter(
            'tmetaconsole_agent',
            $filter_agents,
            $fields
        );

        if ($agents !== false) {
            foreach ($agents as $agent) {
                $data[] = [
                    'id'        => $agent['id_agente'],
                    'name'      => io_safe_output($agent['nombre']),
                    'alias'     => io_safe_output($agent['alias']).' ('.io_safe_output($agent['server_name']).')',
                    'ip'        => io_safe_output($agent['direccion']),
                    'id_server' => $agent['id_server'],
                    'filter'    => 'agent',
                ];
            }
        }

        // Get agents for only the address.
        $filter_address = $filter;
        $filter_address[] = '(alias NOT LIKE "%'.$string.'%" AND nombre NOT LIKE "%'.$string.'%" AND direccion LIKE "%'.$string.'%")';

        $agents = db_get_all_rows_filter(
            'tmetaconsole_agent',
            $filter_address,
            $fields
        );

        if ($agents !== false) {
            foreach ($agents as $agent) {
                $data[] = [
                    'id'        => $agent['id_agente'],
                    'name'      => io_safe_output($agent['nombre']),
                    'alias'     => io_safe_output($agent['alias']).' ('.io_safe_output($agent['server_name']).')',
                    'ip'        => io_safe_output($agent['direccion']),
                    'id_server' => $agent['id_server'],
                    'filter'    => 'address',
                ];
            }
        }

        // Get agents for only the description.
        $filter_description = $filter;
        $filter_description[] = '(alias NOT LIKE "%'.$string.'%" AND nombre NOT LIKE "%'.$string.'%" AND direccion NOT LIKE "%'.$string.'%" AND comentarios LIKE "%'.$string.'%")';

        $agents = db_get_all_rows_filter(
            'tmetaconsole_agent',
            $filter_description,
            $fields
        );

        if ($agents !== false) {
            foreach ($agents as $agent) {
                $data[] = [
                    'id'        => $agent['id_agente'],
                    'name'      => io_safe_output($agent['nombre']),
                    'alias'     => io_safe_output($agent['alias']).' ('.io_safe_output($agent['server_name']).')',
                    'ip'        => io_safe_output($agent['direccion']),
                    'id_server' => $agent['id_server'],
                    'filter'    => 'description',
                ];
            }
        }
    }

    echo json_encode($data);
    return;
}

// Saves an event filter.
if ($save_agent_filter) {
    $values = [];
    $values['id_name'] = get_parameter('id_name');
    $values['group_id'] = get_parameter('group_id');
    $values['recursion'] = get_parameter('recursion');
    $values['status'] = get_parameter('status');
    $values['search'] = get_parameter('search');
    $values['id_os'] = get_parameter('id_os');
    $values['policies'] = json_encode(get_parameter('policies'));
    $values['search_custom'] = get_parameter('search_custom');
    $values['ag_custom_fields'] = get_parameter('ag_custom_fields');
    $values['id_group_filter'] = get_parameter('id_group_filter');

    $exists = (bool) db_get_value_filter(
        'id_filter',
        'tagent_filter',
        $values
    );

    if ($exists === true) {
        echo 'duplicate';
    } else {
        $result = db_process_sql_insert('tagent_filter', $values);

        if ($result === false) {
            echo 'error';
        } else {
            echo $result;
        }
    }
}

if ($update_agent_filter) {
    $values = [];
    $id = get_parameter('id');

    $values['group_id'] = get_parameter('group_id');
    $values['recursion'] = get_parameter('recursion');
    $values['status'] = get_parameter('status');
    $values['search'] = get_parameter('search');
    $values['id_os'] = get_parameter('id_os');
    $values['policies'] = json_encode(get_parameter('policies'));
    $values['search_custom'] = get_parameter('search_custom');
    $values['ag_custom_fields'] = get_parameter('ag_custom_fields');

    $result = db_process_sql_update(
        'tagent_filter',
        $values,
        ['id_filter' => $id]
    );

    if ($result === false) {
        echo 'error';
    } else {
        echo 'ok';
    }
}

if ($delete_agent_filter) {
    $id = get_parameter('id');

    $user_groups = users_get_groups(
        $config['id_user'],
        'AW',
        users_can_manage_group_all('AW'),
        true
    );

    $sql = 'DELETE
        FROM tagent_filter
        WHERE id_filter = '.$id.' AND id_group_filter IN ('.implode(',', array_keys($user_groups)).')';

    $agent_filters = db_process_sql($sql);

    if ($agent_filters === false) {
        echo 'error';
    } else {
        echo 'ok';
    }
}

if ($get_agent_filters) {
    $user_groups = users_get_groups(
        $config['id_user'],
        'AR',
        users_can_manage_group_all('AR'),
        true
    );

    $sql = 'SELECT id_filter, id_name
        FROM tagent_filter
        WHERE id_group_filter IN ('.implode(',', array_keys($user_groups)).')';

    $agent_filters = db_get_all_rows_sql($sql);

    $result = [];

    if ($agent_filters !== false) {
        foreach ($agent_filters as $agent_filter) {
            $result[$agent_filter['id_filter']] = $agent_filter['id_name'];
        }
    }

    echo io_json_mb_encode($result);
}

if ((int) $load_filter_modal === 1) {
    $user_groups = users_get_groups(
        $config['id_user'],
        'AR',
        users_can_manage_group_all('AR'),
        true
    );

    $sql = 'SELECT id_filter, id_name
        FROM tagent_filter
        WHERE id_group_filter IN ('.implode(',', array_keys($user_groups)).')';

    $agent_filters = db_get_all_rows_sql($sql);

    $filters = [];
    foreach ($agent_filters as $agent_filter) {
        $filters[$agent_filter['id_filter']] = $agent_filter['id_name'];
    }

    echo '<div id="load-filter-select" class="load-filter-modal">';
    echo '<form method="post" id="form_load_filter" action="index.php?sec=view&sec2=operation/agentes/estado_agente&pure=">';

    $table = new StdClass;
    $table->id = 'load_filter_form';
    $table->width = '100%';
    $table->class = 'filter-table-adv';

    $data = [];
    $table->rowid[3] = 'update_filter_row1';
    $data[0] = html_print_label_input_block(
        __('Load filter'),
        html_print_select(
            $filters,
            'filter_id',
            $current,
            '',
            __('None'),
            0,
            true,
            false,
            true,
            '',
            false
        )
    );

    $table->data[] = $data;
    $table->rowclass[] = '';

    html_print_table($table);
    html_print_div(
        [
            'class'   => 'action-buttons',
            'content' => html_print_submit_button(
                __('Load filter'),
                'srcbutton',
                false,
                [
                    'icon' => 'search',
                    'mode' => 'mini',
                ],
                true
            ),
        ],
        false
    );
    echo html_print_input_hidden('load_filter', 1, true);
    echo '</form>';
    echo '</div>';
    ?>

    <script type="text/javascript">
    function show_filter() {
        $("#load-filter-select").dialog({
            resizable: true,
            draggable: true,
            modal: false,
            closeOnEscape: true,
            width: 450
        });
    }

    $(document).ready(function() {
        show_filter();
    });

    </script>
    <?php
    return;
}

if ($save_filter_modal) {
    echo '<div id="save-filter-select">';
    if (check_acl($config['id_user'], 0, 'AW')) {
        echo '<div id="#info_box"></div>';

        $table = new StdClass;
        $table->id = 'save_filter_form';
        $table->width = '100%';
        $table->size = [];
        $table->size[0] = '50%';
        $table->size[1] = '50%';
        $table->class = 'filter-table-adv';
        $data = [];

        $table->rowid[0] = 'update_save_selector';
        $data[0][0] = html_print_label_input_block(
            __('New filter'),
            html_print_radio_button(
                'filter_mode',
                'new',
                '',
                true,
                true
            )
        );

        $data[0][1] = html_print_label_input_block(
            __('Update/delete filter'),
            html_print_radio_button(
                'filter_mode',
                'update',
                '',
                false,
                true
            )
        );

        $table->rowid[1] = 'save_filter_row1';
        $data[1][0] = html_print_label_input_block(
            __('Filter name'),
            html_print_input_text('id_name', '', '', 15, 255, true)
        );

        $labelInput = __('Filter group');
        if (is_metaconsole() === true) {
            $labelInput = __('Save in Group');
        }

        $user_groups_array = users_get_groups_for_select(
            $config['id_user'],
            'AW',
            users_can_manage_group_all('AW'),
            true
        );

        $data[1][1] = html_print_label_input_block(
            $labelInput,
            html_print_select(
                $user_groups_array,
                'id_group_filter_dialog',
                $id_group_filter,
                '',
                '',
                0,
                true,
                false,
                false
            ),
            ['div_class' => 'filter-group-dialog']
        );

        $table->rowid[2] = 'save_filter_row2';

        $table->data[] = $data;
        $table->rowclass[] = '';
        $user_groups = users_get_groups(
            $config['id_user'],
            'AW',
            users_can_manage_group_all('AW'),
            true
        );

        $sql = 'SELECT id_filter, id_name
            FROM tagent_filter
            WHERE id_group_filter IN ('.implode(',', array_keys($user_groups)).')';

        $agent_filters = db_get_all_rows_sql($sql);

        $_filters_update = [];

        if ($agent_filters !== false) {
            foreach ($agent_filters as $agent_filter) {
                $_filters_update[$agent_filter['id_filter']] = $agent_filter['id_name'];
            }
        }

        $data[2][0] = html_print_label_input_block(
            __('Filter'),
            html_print_select(
                $_filters_update,
                'overwrite_filter',
                '',
                '',
                '',
                0,
                true
            )
        );

        $table->data = $data;

        html_print_table($table);
        html_print_div(
            [
                'id'      => 'submit-save_filter',
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Save current filter'),
                    'srcbutton',
                    false,
                    [
                        'icon'    => 'search',
                        'mode'    => 'mini',
                        'onclick' => 'save_new_filter();',
                    ],
                    true
                ),
            ],
            false
        );

        $input_actions = html_print_submit_button(
            __('Delete filter'),
            'delete_filter',
            false,
            [
                'icon'    => 'delete',
                'mode'    => 'mini',
                'onclick' => 'save_delete_filter();',
            ],
            true
        );

        $input_actions .= html_print_submit_button(
            __('Update filter'),
            'srcbutton',
            false,
            [
                'icon'    => 'update',
                'mode'    => 'mini',
                'onclick' => 'save_update_filter();',
            ],
            true
        );

        html_print_div(
            [
                'id'      => 'update_filter_row',
                'class'   => 'action-buttons',
                'content' => $input_actions,
            ],
            false
        );
    } else {
        include 'general/noaccess.php';
    }

    echo '</div>';
    ?>
<script type="text/javascript">

function show_save_filter() {
    $('#save_filter_row2').hide();
    $('#update_filter_row').hide();
    $('#update_delete_row').hide();
    $('.filter-group-dialog').show();
    // Filter save mode selector
    $("[name='filter_mode']").click(function() {
        if ($(this).val() == 'new') {
            $('#save_filter_row2').hide();
            $('#submit-save_filter').show();
            $('#update_filter_row').hide();
            $('#update_delete_row').hide();
            $('.filter-group-dialog').show();
        }
        else {
            $('#save_filter_row2').show();
            $('#update_filter_row').show();
            $('#submit-save_filter').hide();
            $('#update_delete_row').show();
            $('.filter-group-dialog').hide();
        }
    });
    $("#save-filter-select").dialog({
        resizable: true,
        draggable: true,
        modal: false,
        closeOnEscape: true,
        width: 450,
        height: 350
    });
}

function save_new_filter() {
    // If the filter name is blank show error
    if ($('#text-id_name').val() == '') {
        $('#show_filter_error').html("<h3 class='error'><?php echo __('Filter name cannot be left blank'); ?></h3>");
        
        // Close dialog
        $('.ui-dialog-titlebar-close').trigger('click');
        return false;
    }

    var custom_fields_values = $('input[name^="ag_custom_fields"]').map(function() {
        return this.value;
    }).get();

    var custom_fields_ids = $("input[name^='ag_custom_fields']").map(function() {
        var name = $(this).attr("name");
        var number = name.match(/\[(.*?)\]/)[1];

        return number;
    }).get();

    var ag_custom_fields = custom_fields_ids.reduce(function(result, custom_fields_id, index) {
        result[custom_fields_id] = custom_fields_values[index];
        return result;
    }, {});

    var id_filter_save;
    jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {
            "page" : "include/ajax/agent",
            "save_agent_filter" : 1,
            "id_name": $("#text-id_name").val(),
            "id" : $("#overwrite_filter").val(),
            "group_id" : $("#group_id").val(),
            "recursion" : $("#checkbox-recursion").is(':checked'),
            "status" : $("#status").val(),
            "search" : $("#text-search").val(),
            "id_os" : $("#os").val(),
            "policies" : $("#policies").val(),
            "search_custom" : $("#text-search_custom").val(),
            "ag_custom_fields": JSON.stringify(ag_custom_fields),
            "id_group_filter": $("#id_group_filter_dialog").val(),
        },

        function (data) {
            $("#info_box").hide();
            if (data == 'error') {
                $("#info_box").filter(function(i, item) {
                    if ($(item).data('type_info_box') == "error_create_filter") {
                        return true;
                    }
                    else
                        return false;
                }).show();
            }
            else  if (data == 'duplicate') {
                $("#info_box").filter(function(i, item) {
                    if ($(item).data('type_info_box') == "duplicate_create_filter") {
                        return true;
                    }
                    else
                        return false;
                }).show();
            }
            else {
                id_filter_save = data;
                
                $("#info_box").filter(function(i, item) {
                    if ($(item).data('type_info_box') == "success_create_filter") {
                        return true;
                    }
                    else
                        return false;
                }).show();
            }

            // First remove all options of filters select.
            $('#filter_id').find('option').remove().end();

            // Add 'none' option.
            $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('None')."'"; ?> ).attr ("value", 0));    

            // Reload filters select.
            jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                {
                    "page" : "include/ajax/agent",
                    "get_agent_filters" : 1
                },
                function (data) {
                    jQuery.each (data, function (i, val) {
                        s = js_html_entity_decode(val);
                        $('#filter_id').append($('<option></option>').html (s).attr("value", i));
                    });
                },
                "json"
            );

            // Close dialog.
            $("#save-filter-select").dialog('close');
        }
    );
}

function save_update_filter() {
    var id_filter_update =  $("#overwrite_filter").val();
    var name_filter_update = $("#overwrite_filter option[value='"+id_filter_update+"']").text();

    var custom_fields_values = $('input[name^="ag_custom_fields"]').map(function() {
        return this.value;
    }).get();

    var custom_fields_ids = $("input[name^='ag_custom_fields']").map(function() {
        var name = $(this).attr("name");
        var number = name.match(/\[(.*?)\]/)[1];

        return number;
    }).get();

    var ag_custom_fields = custom_fields_ids.reduce(function(result, custom_fields_id, index) {
        result[custom_fields_id] = custom_fields_values[index];
        return result;
    }, {});

    jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {
            "page" : "include/ajax/agent",
            "update_agent_filter" : 1,
            "id" : $("#overwrite_filter").val(),
            "group_id" : $("#group_id").val(),
            "recursion" : $("#checkbox-recursion").is(':checked'),
            "status" : $("#status").val(),
            "search" : $("#text-search").val(),
            "id_os" : $("#os").val(),
            "policies" : $("#policies").val(),
            "search_custom" : $("#text-search_custom").val(),
            "ag_custom_fields": JSON.stringify(ag_custom_fields),
        },
        function (data) {
            $(".info_box").hide();
            if (data == 'ok') {
                $(".info_box").filter(function(i, item) {
                    if ($(item).data('type_info_box') == "success_update_filter") {
                        return true;
                    }
                    else
                        return false;
                }).show();
            }
            else {
                $(".info_box").filter(function(i, item) {
                    if ($(item).data('type_info_box') == "error_create_filter") {
                        return true;
                    }
                    else
                        return false;
                }).show();
            }
        });
        
        // First remove all options of filters select.
        $('#filter_id').find('option').remove().end();

        // Add 'none' option.
        $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('None')."'"; ?> ).attr ("value", 0));    

        // Reload filters select.
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "include/ajax/agent",
                "get_agent_filters" : 1
            },
            function (data) {
                jQuery.each (data, function (i, val) {
                    s = js_html_entity_decode(val);
                    if (i == id_filter_update) {
                        $('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
                    }
                    else {
                        $('#filter_id').append ($('<option></option>').html (s).attr ("value", i));
                    }
                });
            },
            "json"
            );
            
        // Close dialog
        $('.ui-dialog-titlebar-close').trigger('click');
        
        // Update the info with the loaded filter
        $("#hidden-id_name").val($('#text-id_name').val());
        $('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + name_filter_update);
        return false;
}

function save_delete_filter() {
    var id_filter_update =  $("#overwrite_filter").val();

    jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {
            "page" : "include/ajax/agent",
            "delete_agent_filter" : 1,
            "id" : $("#overwrite_filter").val(),
        },
        function (data) {
            $(".info_box").hide();
            if (data == 'ok') {
                $(".info_box").filter(function(i, item) {
                    if ($(item).data('type_info_box') == "success_update_filter") {
                        return true;
                    }
                    else
                        return false;
                }).show();
            }
            else {
                $(".info_box").filter(function(i, item) {
                    if ($(item).data('type_info_box') == "error_create_filter") {
                        return true;
                    }
                    else
                        return false;
                }).show();
            }
        });
        
        // First remove all options of filters select.
        $('#filter_id').find('option').remove().end();

        // Add 'none' option.
        $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('None')."'"; ?> ).attr ("value", 0));    

        // Reload filters select.
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "include/ajax/agent",
                "get_agent_filters" : 1
            },
            function (data) {
                jQuery.each (data, function (i, val) {
                    s = js_html_entity_decode(val);
                    if (i == id_filter_update) {
                        $('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
                    }
                    else {
                        $('#filter_id').append ($('<option></option>').html (s).attr ("value", i));
                    }
                });
            },
            "json"
        );
            
        // Close dialog
        $('.ui-dialog-titlebar-close').trigger('click');

        return false;
}

$(document).ready(function() {
    show_save_filter();
});
</script>
    <?php
    return;
}

return;
