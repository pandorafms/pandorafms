<?php
/**
 * Manage AJAX response for event pages.
 *
 * @category   Ajax
 * @package    Pandora FMS
 * @subpackage Events
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

use PandoraFMS\Enterprise\Metaconsole\Node;

// Begin.
global $config;

require_once 'include/functions_events.php';
require_once 'include/functions_agents.php';
require_once 'include/functions_ui.php';
require_once 'include/functions_db.php';
require_once 'include/functions_io.php';
require_once 'include/functions.php';
require_once $config['homedir'].'/include/class/HTML.class.php';
enterprise_include_once('meta/include/functions_events_meta.php');
enterprise_include_once('include/functions_metaconsole.php');

// Check access.
check_login();

if (! check_acl($config['id_user'], 0, 'ER')
    && ! check_acl($config['id_user'], 0, 'EW')
    && ! check_acl($config['id_user'], 0, 'EM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access event viewer'
    );
    include 'general/noaccess.php';
    return;
}

$drawConsoleSound = (bool) get_parameter('drawConsoleSound', false);
$process_buffers = (bool) get_parameter('process_buffers', false);
$get_extended_event = (bool) get_parameter('get_extended_event');
$change_status = (bool) get_parameter('change_status');
$get_Acknowledged = (bool) get_parameter('get_Acknowledged');
$change_owner = (bool) get_parameter('change_owner');
$add_comment = (bool) get_parameter('add_comment');
$dialogue_event_response = (bool) get_parameter('dialogue_event_response');
$perform_event_response = (bool) get_parameter('perform_event_response');
$get_response = (bool) get_parameter('get_response');
$get_response_massive = (bool) get_parameter('get_response_massive');
$get_row_response_action = (bool) get_parameter('get_row_response_action');
$draw_row_response_info = (bool) get_parameter('draw_row_response_info', false);
$meta = get_parameter('meta', 0);
$history = get_parameter('history', 0);
$table_events = get_parameter('table_events', 0);
$total_events = (bool) get_parameter('total_events');
$total_event_graph = (bool) get_parameter('total_event_graph');
$graphic_event_group = (bool) get_parameter('graphic_event_group');
$get_table_response_command = (bool) get_parameter('get_table_response_command');
$save_filter_modal = get_parameter('save_filter_modal', 0);
$load_filter_modal = get_parameter('load_filter_modal', 0);
$get_filter_values = get_parameter('get_filter_values', 0);
$update_event_filter = get_parameter('update_event_filter', 0);
$save_event_filter = get_parameter('save_event_filter', 0);
$in_process_event = (bool) get_parameter('in_process_event', 0);
$validate_event = (bool) get_parameter('validate_event', 0);
$delete_event = (bool) get_parameter('delete_event', 0);
$get_event_filters = get_parameter('get_event_filters', 0);
$get_comments = (bool) get_parameter('get_comments', false);
$get_events_fired = (bool) get_parameter('get_events_fired');
$get_id_source_event = get_parameter('get_id_source_event');
$node_id = (int) get_parameter('node_id', 0);

if ($get_comments === true) {
    $event = get_parameter('event', false);
    $event_rep = (int) get_parameter_post('event')['event_rep'];
    $group_rep = (int) get_parameter_post('event')['group_rep'];

    if ($event === false) {
        return __('Failed to retrieve comments');
    }

    $eventsGrouped = [];
    // Consider if the event is grouped.
    $whereGrouped = '1=1';
    if ($group_rep === EVENT_GROUP_REP_EVENTS && $event_rep > 1) {
        // Default grouped message filtering (evento and estado).
        $whereGrouped = sprintf(
            '`evento` = "%s"',
            $event['evento']
        );

        // If id_agente is reported, filter the messages by them as well.
        if ((int) $event['id_agente'] > 0) {
            $whereGrouped .= sprintf(
                ' AND `id_agente` = %d',
                (int) $event['id_agente']
            );
        }

        if ((int) $event['id_agentmodule'] > 0) {
            $whereGrouped .= sprintf(
                ' AND `id_agentmodule` = %d',
                (int) $event['id_agentmodule']
            );
        }
    } else if ($group_rep === EVENT_GROUP_REP_EXTRAIDS) {
        $whereGrouped = sprintf(
            '`id_extra` = "%s"',
            io_safe_output($event['id_extra'])
        );
    } else {
        $whereGrouped = sprintf('`id_evento` = %d', $event['id_evento']);
    }

    try {
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node = new Node($event['server_id']);
            $node->connect();
        }

        $sql = sprintf(
            'SELECT `user_comment`
            FROM tevento
            WHERE %s',
            $whereGrouped
        );

        // Get grouped comments.
        $eventsGrouped = db_get_all_rows_sql($sql);
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node->disconnect();
        }

        $eventsGrouped = [];
    } finally {
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node->disconnect();
        }
    }

    // End of get_comments.
    echo events_page_comments($event, true, $eventsGrouped);

    return;
}

if ($get_event_filters) {
    $event_filter = events_get_event_filter_select();

    echo io_json_mb_encode($event_filter);
    return;
}

// Delete event (filtered or not).
if ($delete_event === true) {
    $filter = get_parameter('filter', []);
    $id_evento = (int) get_parameter('id_evento', 0);
    $server_id = (int) get_parameter('server_id', 0);
    $event_rep = (int) get_parameter('event_rep', 0);

    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        if ($event_rep === 0) {
            // Disable group by when there're result is unique.
            $filter['group_rep'] = 0;
        }

        // Check acl.
        if (! check_acl($config['id_user'], 0, 'EM')) {
            echo 'unauthorized';
            return;
        }

        $r = events_delete($id_evento, $filter, false, true);
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        $r = false;
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    if ($r === false) {
        echo 'Failed';
    } else {
        echo $r;
    }

    return;
}

// Validates an event (filtered or not).
if ($validate_event === true) {
    $filter = get_parameter('filter', []);
    $id_evento = (int) get_parameter('id_evento', 0);
    $server_id = (int) get_parameter('server_id', 0);
    $event_rep = (int) get_parameter('event_rep', 0);

    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        if ($event_rep === 0) {
            // Disable group by when there're result is unique.
            $filter['group_rep'] = EVENT_GROUP_REP_ALL;
        }

        // Check acl.
        if (!check_acl($config['id_user'], 0, 'EW')) {
            echo 'unauthorized';
            return;
        }

        $r = events_update_status(
            $id_evento,
            EVENT_VALIDATE,
            $filter
        );
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        $r = false;
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    if ($r === false) {
        echo 'Failed';
    } else {
        echo $r;
    }

    return;
}

// Sets status to in progress.
if ($in_process_event === true) {
    $filter = get_parameter('filter', []);
    $id_evento = (int) get_parameter('id_evento', 0);
    $server_id = (int) get_parameter('server_id', 0);
    $event_rep = (int) get_parameter('event_rep', 0);

    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        if ($event_rep === 0) {
            // Disable group by when there're result is unique.
            $filter['group_rep'] = EVENT_GROUP_REP_ALL;
        }

        // Check acl.
        if (! check_acl($config['id_user'], 0, 'EW')) {
            echo 'unauthorized';
            return;
        }

        $r = events_update_status(
            $id_evento,
            EVENT_PROCESS,
            $filter
        );
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        $r = false;
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    if ($r === false) {
        echo 'Failed';
    } else {
        echo $r;
    }

    return;
}

// Saves an event filter.
if ($save_event_filter) {
    $values = [];
    $values['id_name'] = get_parameter('id_name');
    $values['id_group'] = get_parameter('id_group');
    $values['event_type'] = get_parameter('event_type');
    $values['severity'] = implode(',', get_parameter('severity', -1));
    $values['status'] = get_parameter('status');
    $values['search'] = get_parameter('search');
    $values['not_search'] = get_parameter('not_search');
    $values['text_agent'] = get_parameter('text_agent');
    $values['id_agent'] = get_parameter('id_agent');
    $values['id_agent_module'] = get_parameter('id_agent_module');
    $values['pagination'] = get_parameter('pagination');
    $values['event_view_hr'] = get_parameter('event_view_hr');
    $values['id_user_ack'] = get_parameter('id_user_ack');
    $values['owner_user'] = get_parameter('owner_user');
    $values['group_rep'] = get_parameter('group_rep');
    $values['tag_with'] = get_parameter('tag_with', io_json_mb_encode([]));
    $values['tag_without'] = get_parameter(
        'tag_without',
        io_json_mb_encode([])
    );
    $values['filter_only_alert'] = get_parameter('filter_only_alert');
    $values['search_secondary_groups'] = get_parameter('search_secondary_groups');
    $values['search_recursive_groups'] = get_parameter('search_recursive_groups');
    $values['id_group_filter'] = get_parameter('id_group_filter');
    $values['date_from'] = get_parameter('date_from', null);
    $values['time_from'] = get_parameter('time_from');
    $values['date_to'] = get_parameter('date_to', null);
    $values['time_to'] = get_parameter('time_to');
    $values['source'] = get_parameter('source');
    $values['id_extra'] = get_parameter('id_extra');
    $values['user_comment'] = get_parameter('user_comment');
    $values['id_source_event'] = get_parameter('id_source_event');
    $values['custom_data'] = get_parameter('custom_data');
    $values['custom_data_filter_type'] = get_parameter('custom_data_filter_type');

    if (is_metaconsole() === true) {
        $values['server_id'] = implode(',', get_parameter('server_id'));
    }

    $exists = (bool) db_get_value_filter(
        'id_filter',
        'tevent_filter',
        $values
    );

    if ($exists) {
        echo 'duplicate';
    } else {
        $result = db_process_sql_insert('tevent_filter', $values);

        if ($result === false) {
            echo 'error';
        } else {
            echo $result;
        }
    }
}

if ($update_event_filter) {
    $values = [];
    $id = get_parameter('id');
    $values['id_group'] = get_parameter('id_group');
    $values['event_type'] = get_parameter('event_type');
    $values['severity'] = implode(',', get_parameter('severity', -1));
    $values['status'] = get_parameter('status');
    $values['search'] = get_parameter('search');
    $values['not_search'] = get_parameter('not_search');
    $values['text_agent'] = get_parameter('text_agent');
    $values['id_agent'] = get_parameter('id_agent');
    $values['id_agent_module'] = get_parameter('id_agent_module');
    $values['pagination'] = get_parameter('pagination');
    $values['event_view_hr'] = get_parameter('event_view_hr');
    $values['id_user_ack'] = get_parameter('id_user_ack');
    $values['owner_user'] = get_parameter('owner_user');
    $values['group_rep'] = get_parameter('group_rep');
    $values['tag_with'] = get_parameter('tag_with', io_json_mb_encode([]));
    $values['tag_without'] = get_parameter(
        'tag_without',
        io_json_mb_encode([])
    );
    $values['filter_only_alert'] = get_parameter('filter_only_alert');
    $values['search_secondary_groups'] = get_parameter('search_secondary_groups');
    $values['search_recursive_groups'] = get_parameter('search_recursive_groups');
    $values['id_group_filter'] = get_parameter('id_group_filter');
    $values['date_from'] = get_parameter('date_from');
    $values['time_from'] = get_parameter('time_from');
    $values['date_to'] = get_parameter('date_to');
    $values['time_to'] = get_parameter('time_to');
    $values['source'] = get_parameter('source');
    $values['id_extra'] = get_parameter('id_extra');
    $values['user_comment'] = get_parameter('user_comment');
    $values['id_source_event'] = get_parameter('id_source_event');
    $values['custom_data'] = get_parameter('custom_data');
    $values['custom_data_filter_type'] = get_parameter('custom_data_filter_type');

    if (is_metaconsole() === true) {
        $values['server_id'] = implode(',', get_parameter('server_id'));
    }

    if (io_safe_output($values['tag_with']) == '["0"]') {
        $values['tag_with'] = '[]';
    }

    if (io_safe_output($values['tag_without']) == '["0"]') {
        $values['tag_without'] = '[]';
    }

    $result = db_process_sql_update(
        'tevent_filter',
        $values,
        ['id_filter' => $id]
    );

    if ($result === false) {
        echo 'error';
    } else {
        echo 'ok';
    }
}

// Get db values of a single filter.
if ($get_filter_values) {
    $id_filter = get_parameter('id');

    $event_filter = events_get_event_filter($id_filter);

    if ($event_filter === false) {
        $event_filter = [
            'status'                  => EVENT_NO_VALIDATED,
            'event_view_hr'           => $config['event_view_hr'],
            'tag_with'                => [],
            'tag_without'             => [],
            'history'                 => false,
            'module_search'           => '',
            'filter_only_alert'       => '-1',
            'search_secondary_groups' => 0,
            'search_recursive_groups' => 0,
            'user_comment'            => '',
            'id_extra'                => '',
            'id_user_ack'             => '',
            'owner_user'              => '',
            'date_from'               => '',
            'time_from'               => '',
            'date_to'                 => '',
            'time_to'                 => '',
            'severity'                => '',
            'event_type'              => '',
            'group_rep'               => EVENT_GROUP_REP_ALL,
            'id_group'                => 0,
            'id_group_filter'         => 0,
            'group_name'              => 'All',
            'text_agent'              => '',
            'id_agent'                => 0,
            'id_name'                 => 'None',
            'filter_id'               => 0,
        ];
    } else {
        $event_filter['module_search'] = io_safe_output(
            db_get_value_filter(
                'nombre',
                'tagente_modulo',
                ['id_agente_modulo' => $event_filter['id_agent_module']]
            )
        );
        $a = array_keys(users_get_groups(false));
        $event_filter['group_name'] = '';
        foreach ($a as $key => $value) {
            if ($value == $event_filter['id_group']) {
                $event_filter['group_name'] = db_get_value('nombre', 'tgrupo', 'id_grupo', $event_filter['id_group_filter']);
                if ($event_filter['group_name'] === false) {
                    $event_filter['group_name'] = __('All');
                }
            }
        }

        if (is_metaconsole() === true) {
            $server_name = db_get_value('server_name', 'tmetaconsole_setup', 'id', $event_filter['server_id']);
            if ($server_name !== false) {
                $event_filter['server_name'] = $server_name;
            }
        }

        $event_filter['module_search'] = io_safe_output(db_get_value_filter('nombre', 'tagente_modulo', ['id_agente_modulo' => $event_filter['id_agent_module']]));
    }

    $event_filter['search'] = io_safe_output($event_filter['search']);
    $event_filter['id_name'] = io_safe_output($event_filter['id_name']);
    $event_filter['text_agent'] = io_safe_output($event_filter['text_agent']);
    $event_filter['source'] = io_safe_output($event_filter['source']);


    $event_filter['tag_with'] = base64_encode(
        io_safe_output($event_filter['tag_with'])
    );
    $event_filter['tag_without'] = base64_encode(
        io_safe_output($event_filter['tag_without'])
    );

    echo io_json_mb_encode($event_filter);
}

if ($load_filter_modal) {
    $current = db_get_value_filter('default_event_filter', 'tusuario', ['id_user' => $config['id_user']]);
    $filters = events_get_event_filter_select();
    $user_groups_array = users_get_groups_for_select(
        $config['id_user'],
        $access,
        true,
        true,
        false
    );

    echo '<div id="load-filter-select" class="load-filter-modal">';
    echo '<form method="post" id="form_load_filter" action="index.php?sec=eventos&sec2=operation/events/events&pure=">';

    $table = new StdClass;
    $table->id = 'load_filter_form';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->styleTable = 'font-weight: bold; color: #555; text-align:left; border: 0px !important;';
    $table->class = 'databox';
    $filter_id_width = '300px';
    if (is_metaconsole() === true) {
        $table->cellspacing = 0;
        $table->cellpadding = 0;
        $table->class = 'databox filters';
        $filter_id_width = '150px';
    }

    $data = [];
    $table->rowid[3] = 'update_filter_row1';
    $data[0] = '<b>'.__('Load filter').'</b>'.$jump;
    $data[0] .= html_print_select(
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
        false,
        'margin-left:5px; width:'.$filter_id_width.';'
    );

    $table->data[] = $data;
    $table->rowclass[] = '';

    html_print_table($table);

    html_print_div(
        [
            'class'   => 'action-buttons',
            'content' => html_print_submit_button(
                __('Load filter'),
                'load_filter',
                false,
                [
                    'icon' => 'update',
                    'mode' => 'secondary mini',
                ],
                true
            ).html_print_input_hidden('load_filter', 1, true),
        ]
    );
    echo '</form>';
    echo '</div>';
    ?>
<script type="text/javascript">
function show_filter() {
    $("#load-filter-select").dialog({
        title: 'Load filter',
        resizable: true,
        draggable: true,
        modal: false,
        closeOnEscape: true,
        width: 340
    });
}


function load_form_filter() {
    jQuery.post (
        "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {
            "page" : "include/ajax/events",
            "get_filter_values" : 1,
            "id" : $('#filter_id').val()
        },
        function (data) {
            jQuery.each (data, function (i, val) {
                if (i == 'id_name')
                    $("#hidden-id_name").val(val);
                if (i == 'id_group'){
                    $('#id_group').val(val);
                }
                if (i == 'event_type')
                    $("#event_type").val(val);
                if (i == 'severity') {
                    const multiple = val.split(",");
                    $("#severity").val(multiple);
                }
                if (i == 'status')
                    $("#status").val(val);
                if (i == 'search')
                    $('#text-search').val(val);
                if (i == 'not_search')
                    $('#checkbox-not_search').val(val);
                if (i == 'text_agent')
                    $('input[name=text_agent]').val(val);
                if (i == 'id_agent')
                    $('input:hidden[name=id_agent]').val(val);
                if (i == 'id_agent_module')
                    $('input:hidden[name=module_search_hidden]').val(val);
                if (i == 'pagination')
                    $("#pagination").val(val);
                if (i == 'event_view_hr')
                    $("#text-event_view_hr").val(val);
                if (i == 'id_user_ack')
                    $("#id_user_ack").val(val);
                if (i == 'owner_user')
                    $("#owner_user").val(val);
                if (i == 'group_rep')
                    $("#group_rep").val(val);
                if (i == 'tag_with')
                    $("#hidden-tag_with").val(val);
                if (i == 'tag_without')
                    $("#hidden-tag_without").val(val);
                if (i == 'filter_only_alert')
                    $("#filter_only_alert").val(val);
                if (i == 'search_secondary_groups')
                    $("#checkbox-search_secondary_groups").val(val);
                if (i == 'search_recursive_groups')
                    $("#checkbox-search_recursive_groups").val(val);
                if (i == 'id_group_filter')
                    $("#id_group_filter").val(val);
                if (i == 'source')
                    $("#text-source").val(val);
                if (i == 'id_extra')
                    $("#text-id_extra").val(val);
                if (i == 'user_comment')
                    $("#text-user_comment").val(val);
                if (i == 'id_source_event')
                    $("#text-id_source_event").val(val);
                if (i == 'server_id')
                    $("#server_id").val(val);
                if (i == 'server_name')
                    $("#select2-server_id-container").text(val);
                if(i == 'date_from')
                    $("#text-date_from").val(val);
                if(i == 'time_from')
                    $("#text-time_from").val(val);
                if(i == 'date_to')
                    $("#text-date_to").val(val);
                if(i == 'time_to')
                    $("#text-time_to").val(val);
                if(i == 'module_search')
                    $('input[name=module_search]').val(val);
                if(i == 'group_name')
                $("#select2-id_group_filter-container").text(val);

            });
            reorder_tags_inputs();
            // Update the info with the loaded filter
            $('#filterid').val($('#filter_id').val());
            $('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $("#hidden-id_name").val());

        },
        "json"
    );

    // Close dialog.
    $("#load-filter-select").dialog('close');

    // Update indicator.
    $("#current_filter").text($('#filter_id option:selected').text());

    // Search.
    $("#table_events")
            .DataTable()
            .draw(false);
}

$(document).ready (function() {
    show_filter();
})

</script>
    <?php
    return;
}


if ($save_filter_modal) {
    echo '<div id="save-filter-select">';
    if (check_acl($config['id_user'], 0, 'EW')
        || check_acl($config['id_user'], 0, 'EM')
    ) {
        echo '<div id="#info_box"></div>';
        $table = new StdClass;
        $table->id = 'save_filter_form';
        $table->width = '100%';
        $table->cellspacing = 4;
        $table->cellpadding = 4;
        $table->class = 'databox';
        if (is_metaconsole() === true) {
            $table->class = 'databox filters';
            $table->cellspacing = 0;
            $table->cellpadding = 0;
        }

        $table->styleTable = 'font-weight: bold; text-align:left; border: 0px !important;';
        if (is_metaconsole() === false) {
            $table->style[0] = '';
        }

        $data = [];
        $table->rowid[0] = 'update_save_selector';
        $data[0] = html_print_radio_button(
            'filter_mode',
            'new',
            __('New filter'),
            true,
            true
        );

        $data[1] = html_print_radio_button(
            'filter_mode',
            'update',
            __('Update filter'),
            false,
            true
        );

        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $table->rowid[1] = 'save_filter_row1';
        $table->size[0] = '50%';
        $table->size[1] = '50%';
        $table->rowclass[1] = 'flex';
        $table->rowclass[2] = 'flex';
        $table->rowclass[3] = 'flex';
        $table->rowclass[4] = 'flex';
        $data[0] = '<b>'.__('Filter name').'</b>'.$jump;
        $data[0] .= html_print_input_text('id_name', '', '', 15, 255, true);
        if (is_metaconsole()) {
            $data[1] = __('Save in Group').$jump;
        } else {
            $data[1] = '<b>'.__('Filter group').'</b>'.$jump;
        }

        $user_groups_array = users_get_groups_for_select(
            $config['id_user'],
            'EW',
            users_can_manage_group_all(),
            true
        );

        $data[1] .= html_print_select(
            $user_groups_array,
            'id_group_filter_dialog',
            $id_group_filter,
            '',
            '',
            0,
            true,
            false,
            false,
            'w130'
        );

        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $table->rowid[2] = 'save_filter_row2';

        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $table->rowid[3] = 'update_filter_row1';
        $data[0] = __('Overwrite filter').$jump;
        // Fix  : Only admin user can see filters of group ALL for update.
        $_filters_update = events_get_event_filter_select(false);

        $data[0] .= html_print_select(
            $_filters_update,
            'overwrite_filter',
            '',
            '',
            '',
            0,
            true,
            false,
            true,
            'w130'
        );

        $table->data[] = $data;
        $table->rowclass[] = '';

        html_print_table($table);

        html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Save filter'),
                    'save_filter',
                    false,
                    [
                        'icon'    => 'update',
                        'mode'    => 'secondary mini',
                        'onClick' => 'save_new_filter();',
                    ],
                    true
                ),
            ]
        );

        html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Update filter'),
                    'update_filter',
                    false,
                    [
                        'icon'    => 'update',
                        'mode'    => 'secondary mini',
                        'onClick' => 'save_update_filter();',
                    ],
                    true
                ),
            ]
        );
    } else {
        include 'general/noaccess.php';
    }

    $modal_title = __('Save/Update filters');
    echo '</div>';
    ?>
<script type="text/javascript">
function show_save_filter() {
    $('#save_filter_row1').show();
    $('#save_filter_row2').show();
    $('#update_filter_row1').hide();
    $('#button-update_filter').hide();
    // Filter save mode selector
    $("[name='filter_mode']").click(function() {
        if ($(this).val() == 'new') {
            $('#save_filter_row1').show();
            $('#save_filter_row2').show();
            $('#button-save_filter').show();
            $('#update_filter_row1').hide();
            $('#button-update_filter').hide();
        }
        else {
            $('#save_filter_row1').hide();
            $('#save_filter_row2').hide();
            $('#update_filter_row1').show();
            $('#button-update_filter').show();
            $('#button-save_filter').hide();
        }
    });
    $("#save-filter-select").dialog({
        title: '<?php echo $modal_title; ?>',
        resizable: true,
        draggable: true,
        modal: false,
        closeOnEscape: true,
        width: 700
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
    
    var id_filter_save;
    
    jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {
            "page" : "include/ajax/events",
            "save_event_filter" : 1,
            "id_name" : $("#text-id_name").val(),
            "id_group" : $("#id_group_filter").val(),
            "event_type" : $("#event_type").val(),
            "severity" : $("#severity").val(),
            "status" : $("#status").val(),
            "search" : $("#text-search").val(),
            "not_search" : $("#checkbox-not_search").val(),
            "text_agent" : $("#text_id_agent").val(),
            "id_agent" : $('input:hidden[name=id_agent]').val(),
            "id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
            "pagination" : $("#pagination").val(),
            "event_view_hr" : $("#text-event_view_hr").val(),
            "id_user_ack" : $("#id_user_ack").val(),
            "owner_user" : $("#owner_user").val(),
            "group_rep" : $("#group_rep").val(),
            "tag_with": Base64.decode($("#hidden-tag_with").val()),
            "tag_without": Base64.decode($("#hidden-tag_without").val()),
            "filter_only_alert" : $("#filter_only_alert").val(),
            "search_secondary_groups" : $("#checkbox-search_secondary_groups").val(),
            "search_recursive_groups" : $("#checkbox-search_recursive_groups").val(),
            "id_group_filter": $("#id_group_filter_dialog").val(),
            "date_from": $("#text-date_from").val(),
            "time_from": $("#text-time_from").val(),
            "date_to": $("#text-date_to").val(),
            "time_to": $("#text-time_to").val(),
            "source": $("#text-source").val(),
            "id_extra": $("#text-id_extra").val(),
            "user_comment": $("#text-user_comment").val(),
            "id_source_event": $("#text-id_source_event").val(),
            "server_id": $("#server_id").val(),
            "custom_data": $("#text-custom_data").val(),
            "custom_data_filter_type": $("#custom_data_filter_type").val()
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

            // Close dialog.
            $("#save-filter-select").dialog('close');
        }
    );
}

// This updates an event filter
function save_update_filter() {
    var id_filter_update =  $("#overwrite_filter").val();
    var name_filter_update = $("#overwrite_filter option[value='"+id_filter_update+"']").text();
    
    jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {"page" : "include/ajax/events",
        "update_event_filter" : 1,
        "id" : $("#overwrite_filter").val(),
        "id_group" : $("#id_group_filter").val(),
        "event_type" : $("#event_type").val(),
        "severity" : $("#severity").val(),
        "status" : $("#status").val(),
        "search" : $("#text-search").val(),
        "not_search" : $("#checkbox-not_search").val(),
        "text_agent" : $("#text_id_agent").val(),
        "id_agent" : $('input:hidden[name=id_agent]').val(),
        "id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
        "pagination" : $("#pagination").val(),
        "event_view_hr" : $("#text-event_view_hr").val(),
        "id_user_ack" : $("#id_user_ack").val(),
        "owner_user" : $("#owner_user").val(),
        "group_rep" : $("#group_rep").val(),
        "tag_with" : Base64.decode($("#hidden-tag_with").val()),
        "tag_without" : Base64.decode($("#hidden-tag_without").val()),
        "filter_only_alert" : $("#filter_only_alert").val(),
        "search_secondary_groups" : $("#checkbox-search_secondary_groups").val(),
        "search_recursive_groups" : $("#checkbox-search_recursive_groups").val(),
        "id_group_filter": $("#id_group_filter_dialog").val(),
        "date_from": $("#text-date_from").val(),
        "time_from": $("#text-time_from").val(),
        "date_to": $("#text-date_to").val(),
        "time_to": $("#text-time_to").val(),
        "source": $("#text-source").val(),
        "id_extra": $("#text-id_extra").val(),
        "user_comment": $("#text-user_comment").val(),
        "id_source_event": $("#text-id_source_event").val(),
        "server_id": $("#server_id").val(),
        "custom_data": $("#text-custom_data").val(),
        "custom_data_filter_type": $("#custom_data_filter_type").val()

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
        
        // First remove all options of filters select
        $('#filter_id').find('option').remove().end();
        // Add 'none' option the first
        $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('none')."'"; ?> ).attr ("value", 0));    
        // Reload filters select
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {"page" : "include/ajax/events",
                "get_event_filters" : 1
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
    

$(document).ready(function (){
    show_save_filter();
});
</script>
    <?php
    return;
}


if ($get_response === true) {
    if (! check_acl($config['id_user'], 0, 'EW')) {
        echo 'unauthorized';
        return;
    }

    $response_id = get_parameter('response_id');
    $server_id = (int) get_parameter('server_id', 0);
    $event_id = (int) get_parameter('event_id', 0);
    $response_parameters = json_decode(
        io_safe_output(
            get_parameter('response_parameters', '')
        ),
        true
    );

    $event_response = db_get_row(
        'tevent_response',
        'id',
        $response_id
    );

    if (empty($event_response) === true) {
        return [];
    }


    if (empty($event_id) === false) {
        try {
            if (is_metaconsole() === true
                && $server_id > 0
            ) {
                $node = new Node($server_id);
                $node->connect();
            }

            $event_response['target'] = events_get_response_target(
                $event_id,
                $event_response,
                $response_parameters,
                $server_id,
                ($server_id !== 0) ? $node->server_name() : 'Metaconsole'
            );
        } catch (\Exception $e) {
            // Unexistent agent.
            if (is_metaconsole() === true
                && $server_id > 0
            ) {
                $node->disconnect();
            }

            return;
        } finally {
            if (is_metaconsole() === true
                && $server_id > 0
            ) {
                $node->disconnect();
            }
        }
    }

    echo json_encode($event_response);

    return;
}


if ($get_response_massive === true) {
    if (! check_acl($config['id_user'], 0, 'EW')) {
        echo 'unauthorized';
        return;
    }

    $response_id = get_parameter('response_id');

    $event_response = db_get_row(
        'tevent_response',
        'id',
        $response_id
    );

    if (empty($event_response) === true) {
        return [];
    }

    $events = json_decode(
        io_safe_output(
            get_parameter('events', '')
        ),
        true
    );

    $response_parameters = json_decode(
        io_safe_output(
            get_parameter('response_parameters', '')
        ),
        true
    );

    $event_response_targets = [];
    if (is_metaconsole() === true) {
        foreach ($events as $server_id => $idEvents) {
            foreach ($idEvents as $idEvent) {
                $event_response_targets[$idEvent.'|'.$server_id]['target'] = get_events_get_response_target(
                    $idEvent,
                    $event_response,
                    $server_id,
                    $response_parameters
                );
            }
        }
    } else {
        foreach ($events as $idEvent) {
            $event_response_targets[$idEvent]['target'] = get_events_get_response_target(
                $idEvent,
                $event_response,
                0,
                $response_parameters
            );
        }
    }

    $result = [
        'event_response'         => $event_response,
        'event_response_targets' => $event_response_targets,
    ];

    echo json_encode($result);

    return;
}

if ($get_row_response_action === true) {
    $response_id = get_parameter('response_id');
    $response = json_decode(
        io_safe_output(
            get_parameter('response', '')
        ),
        true
    );

    $end = (bool) get_parameter('end', false);
    $index = $response['event_id'];
    if (is_metaconsole() === true) {
        $index .= '-'.$response['server_id'];
    }

    echo get_row_response_action(
        $response,
        $response_id,
        $end,
        $index
    );

    return;
}

if ($perform_event_response === true) {
    global $config;

    if (! check_acl($config['id_user'], 0, 'EW')) {
        echo __('unauthorized');
        return;
    }

    $target = get_parameter('target', '');
    $response_id = get_parameter('response_id');
    $event_id = (int) get_parameter('event_id');
    $server_id = (int) get_parameter('server_id', 0);
    $response = json_decode(
        io_safe_output(
            get_parameter('response', '')
        ),
        true
    );

    $event_response = $response;
    if (empty($event_response) === true) {
        echo __('No data');
        return;
    }

    $command = $event_response['target'];
    $command_timeout = ($event_response !== false) ? $event_response['command_timeout'] : 90;
    if (enterprise_installed() === true) {
        if ($event_response !== false
            && (int) $event_response['server_to_exec'] !== 0
            && $event_response['type'] === 'command'
        ) {
            $commandExclusions = [
                'vi',
                'vim',
                'nano',
            ];

            $server_data = db_get_row(
                'tserver',
                'id_server',
                $event_response['server_to_exec']
            );

            if (in_array(strtolower($command), $commandExclusions) === true) {
                echo 'Only stdin/stdout commands are supported';
            } else {
                switch (PHP_OS) {
                    case 'FreeBSD':
                        $timeout_bin = '/usr/local/bin/gtimeout';
                    break;

                    case 'NetBSD':
                        $timeout_bin = '/usr/pkg/bin/gtimeout';
                    break;

                    default:
                        $timeout_bin = '/usr/bin/timeout';
                    break;
                }

                if (empty($server_data['port']) === true) {
                    system(
                        'ssh pandora_exec_proxy@'.$server_data['ip_address'].' "'.$timeout_bin.' '.$command_timeout.' '.io_safe_output($command).' 2>&1"',
                        $ret_val
                    );
                } else {
                    system(
                        'ssh -p '.$server_data['port'].' pandora_exec_proxy@'.$server_data['ip_address'].' "'.$timeout_bin.' '.$command_timeout.' '.io_safe_output($command).' 2>&1"',
                        $ret_val
                    );
                }
            }
        } else {
            switch (PHP_OS) {
                case 'FreeBSD':
                    $timeout_bin = '/usr/local/bin/gtimeout';
                break;

                case 'NetBSD':
                    $timeout_bin = '/usr/pkg/bin/gtimeout';
                break;

                default:
                    $timeout_bin = '/usr/bin/timeout';
                break;
            }

            system($timeout_bin.' '.$command_timeout.' '.io_safe_output($command).' 2>&1', $ret_val);
        }
    } else {
        switch (PHP_OS) {
            case 'FreeBSD':
                $timeout_bin = '/usr/local/bin/gtimeout';
            break;

            case 'NetBSD':
                $timeout_bin = '/usr/pkg/bin/gtimeout';
            break;

            default:
                $timeout_bin = '/usr/bin/timeout';
            break;
        }

        system($timeout_bin.' '.$command_timeout.' '.io_safe_output($command).' 2>&1', $ret_val);
    }

    if ($ret_val != 0) {
        echo "<div class='left'>";
        echo __('Error executing response');
        echo '</div><br>';
    }

    return;
}

if ($dialogue_event_response) {
    global $config;

    if (! check_acl($config['id_user'], 0, 'EW')) {
        echo 'unauthorized';
        return;
    }

    $event_id = get_parameter('event_id');
    $response_id = get_parameter('response_id');
    $command = get_parameter('target');
    $event_response = json_decode(
        io_safe_output(
            get_parameter('response', '')
        ),
        true
    );

    switch ($event_response['type']) {
        case 'command':
            echo get_row_response_action(
                $event_response,
                $response_id
            );
        break;

        case 'url':
            $command = str_replace('localhost', $_SERVER['SERVER_NAME'], $command);
            echo "<iframe src='".$command."' id='divframe' class='w100p height_90p'></iframe>";
        break;

        default:
            // Ignore.
        break;
    }
}

if ($add_comment === true) {
    $comment = (string) get_parameter('comment');
    $eventId = (int) get_parameter('event_id');
    $server_id = 0;
    if (is_metaconsole() === true) {
        $server_id = (int) get_parameter('server_id');
    }

    // Safe comments for hacks.
    if (preg_match('/script/i', io_safe_output($comment))) {
        $return = false;
    } else {
        try {
            if (is_metaconsole() === true
                && $server_id > 0
            ) {
                $node = new Node($server_id);
                $node->connect();
            }

            $return = events_comment(
                $eventId,
                $comment,
                'Added comment'
            );
        } catch (\Exception $e) {
            // Unexistent agent.
            if (is_metaconsole() === true
                && $server_id > 0
            ) {
                $node->disconnect();
            }

            $return = false;
        } finally {
            if (is_metaconsole() === true
                && $server_id > 0
            ) {
                $node->disconnect();
            }
        }
    }

    echo ($return === true) ? 'comment_ok' : 'comment_error';

    return;
}

if ($change_status === true) {
    $event_ids = get_parameter('event_ids');
    $new_status = get_parameter('new_status');
    $server_id = 0;
    if (is_metaconsole() === true) {
        $server_id = (int) get_parameter('server_id');
    }

    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        $return = events_change_status(
            explode(',', $event_ids),
            $new_status
        );
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        $success = false;
        echo 'owner_error';
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    if ($return !== false) {
        $event_st = events_display_status($new_status);

        echo json_encode(
            [
                'status_title' => $event_st['title'],
                'status_img'   => html_print_image(
                    $event_st['img'],
                    true,
                    false,
                    true
                ),
                'status'       => 'status_ok',
                'user'         => db_get_value(
                    'fullname',
                    'tusuario',
                    'id_user',
                    $config['id_user']
                ),
            ]
        );
    } else {
        echo json_encode(
            [
                'status' => 'status_error',
                'user'   => db_get_value(
                    'fullname',
                    'tusuario',
                    'id_user',
                    $config['id_user']
                ),
            ]
        );
    }

    return;
}

if ($get_Acknowledged === true) {
    $event_id = (int) get_parameter('event_id', 0);
    $server_id = (int) get_parameter('server_id', 0);

    $return = '';
    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        echo events_page_general_acknowledged($event_id);
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        $return = false;
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    return $return;
}

if ($change_owner === true) {
    $new_owner = get_parameter('new_owner', '');
    $event_id = (int) get_parameter('event_id', 0);
    $server_id = (int) get_parameter('server_id', 0);

    if ($new_owner === -1) {
        $new_owner = '';
    }

    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        $return = events_change_owner(
            $event_id,
            $new_owner,
            true
        );
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        $return = false;
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    if ($return === true) {
        echo 'owner_ok';
    } else {
        echo 'owner_error';
    }

    return;
}


// Generate a modal window with extended information of given event.
if ($get_extended_event) {
    global $config;

    $event = io_safe_output(get_parameter('event', false));
    $filter = get_parameter('filter', false);

    if ($event === false) {
        return;
    }

    $event_id = $event['id_evento'];

    $readonly = false;
    if (enterprise_hook('enterprise_acl', [$config['id_user'], 'eventos', 'execute_event_responses']) === false) {
        $readonly = true;
    }

    // Clean url from events and store in array.
    $event['clean_tags'] = events_clean_tags($event['tags']);

    // If the event is not found, we abort.
    if (empty($event) === true) {
        ui_print_error_message('Event not found');
        return false;
    }

    $dialog_page = get_parameter('dialog_page', 'general');
    $filter = get_parameter('filter', []);
    $similar_ids = get_parameter('similar_ids', $event_id);
    $group_rep = $filter['group_rep'];
    $event_rep = (empty($group_rep) === true) ? EVENT_GROUP_REP_EVENTS : $group_rep;
    $timestamp_first = $event['timestamp_first'];
    $timestamp_last = $event['timestamp_last'];
    $server_id = $event['server_id'];
    if (empty($server_id) === true && empty($event['server_name']) === false && is_metaconsole() === true) {
        $server_id = metaconsole_get_id_server($event['server_name']);
    }

    $comments = $event['comments'];

    $event['similar_ids'] = $similar_ids;
    $event['group_rep'] = $group_rep;

    if (isset($comments) === false) {
        $comments = $event['user_comment'];
    }

    // Check ACLs.
    $access = false;
    if (is_user_admin($config['id_user'])) {
        // Do nothing if you're admin, you get full access.
        $access = true;
    } else if ($config['id_user'] == $event['owner_user']) {
        // Do nothing if you're the owner user, you get access.
        $access = true;
    } else if ($event['id_grupo'] == 0) {
        // If the event has access to all groups, you get access.
        $access = true;
    } else {
        // Get your groups.
        $groups = users_get_groups($config['id_user'], 'ER');

        if (in_array($event['id_grupo'], array_keys($groups))) {
            // If event group is among the groups of the user, you get access.
            $access = true;
        } else if ($event['id_agente']
            && agents_check_access_agent($event['id_agente'], 'ER')
        ) {
            // Secondary group, indirect access.
            $access = true;
        }
    }

    if (!$access) {
        // If all the access types fail, abort.
        echo 'Access denied';
        return false;
    }

    // Print group_rep in a hidden field to recover it from javascript.
    html_print_input_hidden('group_rep', (int) $group_rep);
    if ($node_id > 0) {
        html_print_input_hidden('node_id', (int) $node_id);
    }

    if ($event === false) {
        return;
    }

    // Tabs.
    $tabs = "<ul class='event_detail_tab_menu'>";
    $tabs .= "<li><a href='#extended_event_general_page' id='link_general'>".html_print_image(
        'images/event.svg',
        true,
        ['class' => 'invert_filter main_menu_icon']
    ).'<span>'.__('General').'</span></a></li>';
    if (events_has_extended_info($event['id_evento']) === true) {
        $tabs .= "<li><a href='#extended_event_related_page' id='link_related'>".html_print_image(
            'images/details.svg',
            true,
            ['class' => 'invert_filter main_menu_icon']
        ).'<span>'.__('Related').'</span></a></li>';
    }

    $tabs .= "<li><a href='#extended_event_details_page' id='link_details'>".html_print_image(
        'images/details.svg',
        true,
        ['class' => 'invert_filter main_menu_icon']
    ).'<span>'.__('Details').'</span></a></li>';
    $tabs .= "<li><a href='#extended_event_custom_fields_page' id='link_custom_fields'>".html_print_image(
        'images/agent-fields.svg',
        true,
        ['class' => 'invert_filter main_menu_icon']
    ).'<span>'.__('Agent fields').'</span></a></li>';
    $tabs .= "<li><a href='#extended_event_comments_page' id='link_comments'>".html_print_image(
        'images/edit.svg',
        true,
        ['class' => 'invert_filter main_menu_icon']
    ).'<span>'.__('Comments').'</span></a></li>';

    if (!$readonly
        && ((tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'EM',
            $event['clean_tags'],
            []
        )) || (tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'EW',
            $event['clean_tags'],
            []
        )) || (tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'ER',
            $event['clean_tags'],
            []
        )))
    ) {
        $tabs .= "<li><a href='#extended_event_responses_page' id='link_responses'>".html_print_image(
            'images/responses.svg',
            true,
            ['class' => 'invert_filter main_menu_icon']
        ).'<span>'.__('Responses').'</span></a></li>';
    }

    if (empty($event['custom_data']) === false) {
        $tabs .= "<li><a href='#extended_event_custom_data_page' id='link_custom_data'>".html_print_image(
            'images/custom-input@svg.svg',
            true,
            ['class' => 'invert_filter main_menu_icon']
        ).'<span>'.__('Custom data').'</span></a></li>';
    }

    $tabs .= '</ul>';

    // Get criticity image.
    switch ($event['criticity']) {
        default:
        case 0:
            $img_sev = 'images/status_sets/default/severity_maintenance_rounded.png';
        break;
        case 1:
            $img_sev = 'images/status_sets/default/severity_informational_rounded.png';
        break;

        case 2:
            $img_sev = 'images/status_sets/default/severity_normal_rounded.png';
        break;

        case 3:
            $img_sev = 'images/status_sets/default/severity_warning_rounded.png';
        break;

        case 4:
            $img_sev = 'images/status_sets/default/severity_critical_rounded.png';
        break;

        case 5:
            $img_sev = 'images/status_sets/default/severity_minor_rounded.png';
        break;

        case 6:
            $img_sev = 'images/status_sets/default/severity_major_rounded.png';
        break;
    }

    if (!$readonly
        && ((tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'EM',
            $event['clean_tags'],
            []
        )) || (tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'EW',
            $event['clean_tags'],
            []
        )) || (tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'ER',
            $event['clean_tags'],
            []
        )))
    ) {
        $responses = events_page_responses($event, $server_id);
    } else {
        $responses = '';
    }

    $console_url = '';
    $details = events_page_details($event, $server_id);

    $related = '';
    if (events_has_extended_info($event['id_evento']) === true) {
        $related = events_page_related(
            $event,
            $server
        );
    }

    $connected = true;
    if (is_metaconsole() === true && empty($server_id) === false) {
        $server = metaconsole_get_connection_by_id($server_id);
        if (metaconsole_connect($server) === NOERR) {
            $connected = true;
        } else {
            $connected = false;
        }
    }

    if ($connected === true) {
        $custom_fields = events_page_custom_fields($event);
        $custom_data = events_page_custom_data($event);
    }

    if (is_metaconsole() === true && empty($server_id) === false) {
        metaconsole_restore_db();
    }

    $general = events_page_general($event);

    $comments = '<div id="extended_event_comments_page" class="extended_event_pages"></div>';

    $notifications = '<div id="notification_comment_error" class="invisible_events">';
    $notifications .= ui_print_error_message(
        __('Error adding comment'),
        '',
        true
    );
    $notifications .= '</div>';
    $notifications .= '<div id="notification_comment_success" class="invisible_events">';
    $notifications .= ui_print_success_message(
        __('Comment added successfully'),
        '',
        true
    );
    $notifications .= '</div>';
    $notifications .= '<div id="notification_status_error" class="invisible_events">';
    $notifications .= ui_print_error_message(
        __('Error changing event status'),
        '',
        true
    );
    $notifications .= '</div>';
    $notifications .= '<div id="notification_status_success" class="invisible_events">';
    $notifications .= ui_print_success_message(
        __('Event status changed successfully'),
        '',
        true
    );
    $notifications .= '</div>';
    $notifications .= '<div id="notification_owner_error" class="invisible_events">';
    $notifications .= ui_print_error_message(
        __('Error changing event owner'),
        '',
        true
    );
    $notifications .= '</div>';
    $notifications .= '<div id="notification_owner_success" class="invisible_events">';
    $notifications .= ui_print_success_message(
        __('Event owner changed successfully'),
        '',
        true
    );
    $notifications .= '</div>';
    $notifications .= '<div id="notification_delete_error" class="invisible_events">';
    $notifications .= ui_print_error_message(
        __('Error deleting event'),
        '',
        true
    );
    $notifications .= '</div>';


    $loading = '<div id="response_loading" class="invisible_events">'.html_print_image('images/spinner.gif', true).'</div>';

    $i = 0;
    $tab['general'] = $i++;
    $tab['details'] = $i++;
    if (!empty($related)) {
        $tab['related'] = $i++;
    }

    $tab['custom_fields'] = $i++;
    $tab['comments'] = $i++;
    $tab['responses'] = $i++;
    $tab['custom_data'] = $i++;

    $out = '<div id="tabs">'.$tabs.$notifications.$loading.$general.$details.$related.$custom_fields.$comments.$responses.$custom_data.html_print_input_hidden('id_event', $event['id_evento']).'</div>';

    $js = '<script>
	$(function() {
		$tabs = $( "#tabs" ).tabs({
		});
		';

    // Load the required tab.
    switch ($dialog_page) {
        case 'general':
            $js .= '$tabs.tabs( "option", "active", '.$tab['general'].');';
        break;

        case 'details':
            $js .= '$tabs.tabs( "option", "active", '.$tab['details'].');';
        break;

        case 'related':
            $js .= '$tabs.tabs( "option", "active", '.$tab['related'].');';
        break;

        case 'custom_fields':
            $js .= '$tabs.tabs( "option", "active", '.$tab['custom_fields'].');';
        break;

        case 'comments':
            $js .= '$tabs.tabs( "option", "active", '.$tab['comments'].');';
        break;

        case 'responses':
            $js .= '$tabs.tabs( "option", "active", '.$tab['responses'].');';
        break;

        case 'custom_data':
            $js .= '$tabs.tabs( "option", "active", '.$tab['custom_data'].');';
        break;

        default:
            // Ignore.
        break;
    }

    $js .= '});';

    $js .= '
        $("#link_comments").click(function (){
          $.post ({
                url : "ajax.php",
                data : {
                    page: "include/ajax/events",
                    get_comments: 1,
                    event: '.json_encode($event).',
                    event_rep: '.$event_rep.'
                },
                dataType : "html",
                success: function (data) {
                    $("#extended_event_comments_page").empty();
                    $("#extended_event_comments_page").html(data);
                }
            });
        });';

    if (events_has_extended_info($event['id_evento']) === true) {
        $js .= '
        $("#link_related").click(function (){
          $.post ({
                url : "ajax.php",
                data : {
                    page: "include/ajax/events_extended",
                    get_extended_info: 1,
                    id_event: '.$event['id_evento'].'
                },
                dataType : "html",
                success: function (data) {
                    $("#related_data").html(data);
                }
            });
        });';
    }

    $js .= '</script>';

    echo $out.$js;
}

if ($table_events) {
    include_once 'include/functions_events.php';
    include_once 'include/functions_graph.php';

    $id_agente = (int) get_parameter('id_agente');
    $all_events_24h = (int) get_parameter('all_events_24h');

    // Fix: for tag functionality groups have to be all user_groups
    // (propagate ACL funct!).
    $groups = users_get_groups($config['id_user']);

    $tags_condition = tags_get_acl_tags(
        $config['id_user'],
        array_keys($groups),
        'ER',
        'event_condition',
        'AND'
    );

    $tableEvents24h = new stdClass();
    $tableEvents24h->class = 'filter_table';
    $tableEvents24h->styleTable = 'border: 0;padding: 0;margin: 0 0 10px;';
    $tableEvents24h->width = '100%';
    $tableEvents24h->data = [];

    $tableEvents24h->data[0] = html_print_div(
        [
            'class'   => 'flex-row-center',
            'content' => '<span class="font_14px mrgn_right_10px">'.__('Show all Events 24h').'</span>'.html_print_switch(
                [
                    'name'  => 'all_events_24h',
                    'value' => $all_events_24h,
                    'id'    => 'checkbox-all_events_24h',
                ]
            ),
        ]
    );

    html_print_table($tableEvents24h);

    $date_subtract_day = (time() - (24 * 60 * 60));

    if ($all_events_24h !== 0) {
        events_print_event_table(
            'utimestamp > '.$date_subtract_day,
            200,
            '100%',
            false,
            $id_agente,
            true
        );
    } else {
        events_print_event_table(
            'estado <> 1 '.$tags_condition,
            200,
            '100%',
            false,
            $id_agente,
            true
        );
    }
}

if ($total_events) {
    global $config;

    $sql_count_event = 'SELECT SQL_NO_CACHE COUNT(id_evento) FROM tevento  ';
    if ($config['event_view_hr']) {
        $sql_count_event .= 'WHERE utimestamp > (UNIX_TIMESTAMP(NOW()) - '.($config['event_view_hr'] * SECONDS_1HOUR).')';
    }

    $system_events = db_get_value_sql($sql_count_event);
    echo $system_events;
    return;
}

if ($total_event_graph) {
    global $config;

    include_once $config['homedir'].'/include/functions_graph.php';

    $out = '<div style="flex: 0 0 300px; width:99%; height:100%;">';
    $out .= grafico_eventos_total('', 0, 0, false, true);
    $out .= '<div>';
    echo $out;
    return;
}

if ($graphic_event_group) {
    global $config;

    include_once $config['homedir'].'/include/functions_graph.php';

    $out = '<div style="flex: 0 0 300px; width:99%; height:100%;">';
    $out .= grafico_eventos_grupo(0, 0, '', false, true);
    $out .= '<div>';
    echo $out;
    return;
}

if ($get_table_response_command) {
    global $config;

    $response_id = get_parameter('event_response_id');
    $params_string = db_get_value(
        'params',
        'tevent_response',
        'id',
        $response_id
    );

    $params = explode(',', $params_string);

    $table = new stdClass;
    $table->id = 'events_responses_table_command';
    $table->width = '90%';
    $table->styleTable = 'text-align:center; margin: 0 auto;';

    $table->style = [];
    $table->style[0] = 'text-align:center;';
    $table->style[1] = 'text-align:center;';

    $table->head = [];
    $table->head[0] = __('Parameters');
    $table->head[0] .= ui_print_help_tip(
        __('These commands will apply to all selected events'),
        true
    );
    $table->head[1] = __('Value');

    if (isset($params) === true
        && is_array($params) === true
    ) {
        foreach ($params as $key => $value) {
            $table->data[$key][0] = $value;
            $table->data[$key][1] = html_print_input_text(
                $value.'-'.$key,
                '',
                '',
                50,
                255,
                true,
                false,
                false,
                '',
                'response_command_input'
            );
        }
    }

    echo '<form id="form_response_command">';
    echo html_print_table($table, true);
    echo '</form>';
    echo html_print_submit_button(
        __('Execute'),
        'enter_command',
        false,
        'class="sub next float-right mrgn_top_15px mrgn_right_25px"',
        true
    );

    return;
}

if ($process_buffers === true) {
    $buffers = get_parameter('buffers', '');

    $buffers = json_decode(io_safe_output($buffers), true);

    $alert = false;
    $content = '<ul>';
    foreach ($buffers['data'] as $node => $data) {
        $content .= '<li>';
        $content .= '<span><b>';
        $content .= __('Events').': ';
        $content .= $node;
        $content .= '</b></span>';

        $class_total = 'info';
        $str_total = '';
        if ($buffers['settings']['total'] == $data) {
            $alert = true;
            $class_total .= ' danger';
            $str_total = html_print_image(
                'images/error_red.png',
                true,
                [
                    'title' => __('Total number of events in this node reached'),
                    'class' => 'forced-title',
                ]
            );
        }

        if (isset($buffers['error'][$node]) === true) {
            $alert = true;
            $class_total .= ' danger';
            $str_total = html_print_image(
                'images/error_red.png',
                true,
                [
                    'title' => $buffers['error'][$node],
                    'class' => 'forced-title',
                ]
            );
        }

        $content .= '<span class="'.$class_total.'">';
        $content .= $data;
        if (empty($str_total) === false) {
            $content .= '<span class="text">';
            $content .= ' '.$str_total;
            $content .= '</span>';
        }

        $content .= '</span>';

        $content .= '</li>';
    }

    $content .= '</ul>';

    $title = __('Total Events per node').': (';
    $title .= $buffers['settings']['total'].')';
    if ($alert === true) {
        $title .= html_print_image(
            'images/error_red.png',
            true,
            [
                'title' => __('Error'),
                'class' => 'forced-title',
                'style' => 'margin-top: -2px;',
            ]
        );
    }

    $output = ui_toggle(
        $content,
        $title,
        '',
        '',
        true,
        true,
        'white_box white_box_opened no_border',
        'no-border flex-row'
    );

    echo $output;
    return;
}

if ($drawConsoleSound === true) {
    echo ui_require_css_file('wizard', 'include/styles/', true);
    echo ui_require_css_file('discovery', 'include/styles/', true);
    echo ui_require_css_file('sound_events', 'include/styles/', true);
    $output = '<div id="tabs-sound-modal">';
        // Header tabs.
        $output .= '<ul class="tabs-sound-modal-options">';
            $output .= '<li>';
            $output .= '<a href="#tabs-sound-modal-1">';
            $output .= html_print_image(
                'images/gear.png',
                true,
                [
                    'title' => __('Options'),
                    'class' => 'invert_filter',
                ]
            );
            $output .= '</a>';
            $output .= '</li>';
            $output .= '<li>';
            $output .= '<a href="#tabs-sound-modal-2">';
            $output .= html_print_image(
                'images/list.png',
                true,
                [
                    'title' => __('Events list'),
                    'class' => 'invert_filter',
                ]
            );
            $output .= '</a>';
            $output .= '</li>';
        $output .= '</ul>';

        // Content tabs.
        $output .= '<div id="tabs-sound-modal-1">';
        $output .= '<h3 class="console-configuration">';
        $output .= __('Console configuration');
        $output .= '</h3>';
            $inputs = [];

            // Load filter.
            $fields = \events_get_event_filter_select();
            $inputs[] = [
                'label'     => \__('Set condition'),
                'arguments' => [
                    'type'          => 'select',
                    'fields'        => $fields,
                    'name'          => 'filter_id',
                    'selected'      => 0,
                    'return'        => true,
                    'nothing'       => \__('All new events'),
                    'nothing_value' => 0,
                    'class'         => 'fullwidth',
                ],
            ];

            $times_interval = [
                10 => '10 '.__('seconds'),
                15 => '15 '.__('seconds'),
                30 => '30 '.__('seconds'),
                60 => '60 '.__('seconds'),
            ];

            $times_sound = [
                2  => '2 '.__('seconds'),
                5  => '5 '.__('seconds'),
                10 => '10 '.__('seconds'),
                15 => '15 '.__('seconds'),
                30 => '30 '.__('seconds'),
                60 => '60 '.__('seconds'),
            ];

            $inputs[] = [
                'class'         => 'interval-sounds',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Interval'),
                        'arguments' => [
                            'type'     => 'select',
                            'fields'   => $times_interval,
                            'name'     => 'interval',
                            'selected' => 10,
                            'return'   => true,
                        ],
                    ],
                    [
                        'label'     => __('Sound duration'),
                        'arguments' => [
                            'type'     => 'select',
                            'fields'   => $times_sound,
                            'name'     => 'time_sound',
                            'selected' => 10,
                            'return'   => true,
                        ],
                    ],
                ],
            ];

            $sounds = [
                'aircraftalarm.wav'                  => 'Air craft alarm',
                'air_shock_alarm.wav'                => 'Air shock alarm',
                'alien_alarm.wav'                    => 'Alien alarm',
                'alien_beacon.wav'                   => 'Alien beacon',
                'bell_school_ringing.wav'            => 'Bell school ringing',
                'Door_Alarm.wav'                     => 'Door alarm',
                'EAS_beep.wav'                       => 'EAS beep',
                'Firewarner.wav'                     => 'Fire warner',
                'HardPCMAlarm.wav'                   => 'Hard PCM Alarm',
                'negativebeep.wav'                   => 'Negative beep',
                'Star_Trek_emergency_simulation.wav' => 'StarTrek emergency simulation',
            ];

            $eventsounds = db_get_all_rows_sql('SELECT * FROM tevent_sound WHERE active = 1');
            foreach ($eventsounds as $key => $row) {
                $sounds[$row['sound']] = $row['name'];
            }

            $inputs[] = [
                'class'         => 'test-sounds',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => \__('Sound melody'),
                        'arguments' => [
                            'type'     => 'select',
                            'fields'   => $sounds,
                            'name'     => 'sound_id',
                            'selected' => 'Star_Trek_emergency_simulation.wav',
                            'return'   => true,
                            'class'    => 'fullwidth',
                        ],
                    ],
                    [
                        'arguments' => [
                            'type'       => 'button',
                            'name'       => 'melody_sound',
                            'label'      => __('Test sound'),
                            'attributes' => ['icon' => 'sound'],
                            'return'     => true,
                        ],
                    ],
                ],
            ];

            // Print form.
            $output .= HTML::printForm(
                [
                    'form'   => [
                        'action' => '',
                        'method' => 'POST',
                    ],
                    'inputs' => $inputs,
                ],
                true,
                false
            );
        $output .= '</div>';

        $output .= '<div id="tabs-sound-modal-2">';
            $output .= '<h3 class="title-discovered-alerts">';
            $output .= __('Discovered alerts');
            $output .= '</h3>';
            $output .= '<div class="empty-discovered-alerts">';
            $output .= html_print_image(
                'images/no-alerts-discovered.png',
                true,
                [
                    'title' => __('No alerts discovered'),
                    'class' => 'invert_filter',
                ]
            );
            $output .= '<span class="text-discovered-alerts">';
            $output .= __('Congrats! there’s nothing to show');
            $output .= '</span>';
            $output .= '</div>';
            $output .= '<div class="elements-discovered-alerts"><ul></ul></div>';
            $output .= html_print_input_hidden(
                'ajax_file_sound_console',
                ui_get_full_url('ajax.php', false, false, false),
                true
            );
            $output .= html_print_input_hidden(
                'meta',
                is_metaconsole(),
                true
            );
            $output .= '<div id="sound_event_details_window"></div>';
            $output .= '<div id="sound_event_response_window"></div>';
        $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="actions-sound-modal">';
        $output .= '<div id="progressbar_time"></div>';
        $output .= '<div class="buttons-sound-modal">';
            $output .= '<div class="container-button-play">';
            $output .= html_print_input(
                [
                    'label'      => __('Start'),
                    'type'       => 'button',
                    'name'       => 'start-search',
                    'attributes' => [ 'class' => 'play' ],
                    'return'     => true,
                ],
                'div',
                true
            );
            $output .= '</div>';
            $output .= '<div class="container-button-alert">';
            $output .= html_print_input(
                [
                    'type'       => 'button',
                    'name'       => 'no-alerts',
                    'label'      => __('No alerts'),
                    'attributes' => ['class' => 'secondary alerts'],
                    'return'     => true,
                ],
                'div',
                true
            );
            $output .= '</div>';

            $output .= html_print_input(
                [
                    'type'   => 'hidden',
                    'name'   => 'mode_alert',
                    'value'  => 0,
                    'return' => true,
                ],
                'div',
                true
            );
        $output .= '</div>';
    $output .= '</div>';

    echo $output;
    return;
}

if ($get_events_fired) {
    global $config;
    $filter_id = (int) get_parameter('filter_id', 0);
    $interval = (int) get_parameter('interval', 10);

    if (empty($filter_id) === true) {
        $filter = [
            'id_group'                => 0,
            'event_type'              => '',
            'severity'                => -1,
            'status'                  => -1,
            'search'                  => '',
            'not_search'              => 0,
            'text_agent'              => '',
            'id_agent'                => 0,
            'id_agent_module'         => 0,
            'pagination'              => 0,
            'id_user_ack'             => 0,
            'group_rep'               => EVENT_GROUP_REP_ALL,
            'tag_with'                => [],
            'tag_without'             => [],
            'filter_only_alert'       => -1,
            'search_secondary_groups' => 0,
            'search_recursive_groups' => 0,
            'source'                  => '',
            'id_extra'                => '',
            'user_comment'            => '',
            'id_source_event'         => 0,
            'server_id'               => 0,
            'custom_data'             => '',
            'custom_data_filter_type' => 0,
        ];
    } else {
        $filter = events_get_event_filter($filter_id);
    }

    if (is_metaconsole() === true) {
        $servers = metaconsole_get_servers();
        if (is_array($servers) === true) {
            $servers = array_reduce(
                $servers,
                function ($carry, $item) {
                    $carry[$item['id']] = $item['server_name'];
                    return $carry;
                }
            );
        } else {
            $servers = [];
        }

        if ($filter['server_id'] === '') {
            $filter['server_id'] = array_keys($servers);
        } else {
            if (is_array($filter['server_id']) === false) {
                if (is_numeric($filter['server_id']) === true) {
                    if ($filter['server_id'] !== 0) {
                        $filter['server_id'] = [$filter['server_id']];
                    } else {
                        $filter['server_id'] = array_keys($servers);
                    }
                } else {
                    $filter['server_id'] = explode(',', $filter['server_id']);
                }
            }
        }
    }

    // Set time.
    $filter['event_view_hr'] = 0;

    $start = (time() - $interval);
    $end = time();

    $filter['date_from'] = date('Y-m-d', $start);
    $filter['date_to'] = date('Y-m-d', $end);
    $filter['time_from'] = date('H:i:s', $start);
    $filter['time_to'] = date('H:i:s', $end);
    $data = events_get_all(
        ['te.*'],
        $filter
    );

    $return = [];
    if (empty($data) === false) {
        foreach ($data as $event) {
            $return[] = array_merge(
                $event,
                [
                    'fired'     => $event['id_evento'],
                    'message'   => ui_print_string_substr(
                        strip_tags(io_safe_output($event['evento'])),
                        75,
                        true,
                        '9'
                    ),
                    'priority'  => ui_print_event_priority($event['criticity'], true, true),
                    'type'      => events_print_type_img(
                        $event['event_type'],
                        true
                    ),
                    'timestamp' => ui_print_timestamp(
                        $event['timestamp'],
                        true,
                        ['style' => 'font-size: 9pt; letter-spacing: 0.3pt;']
                    ),
                ]
            );
        }
    }

    echo io_safe_output(io_json_mb_encode($return));
    return;
}

if ($draw_row_response_info === true) {
    $event_response = json_decode(
        io_safe_output(
            get_parameter('response', '')
        ),
        true
    );

    $massive = (bool) get_parameter('massive', false);

    $output .= '';
    if ($massive === true) {
        $output .= '<div>';
        $output .= '<h5>';
        $output .= $event_response['description'];
        $output .= '</h5>';
        $output .= '</div>';
    } else {
        $output .= '<tr class="params_rows">';
        $output .= '<td>';
        $output .= __('Description');
        $output .= '</td>';
        $output .= '<td class="height_30px" colspan="2">';
        $output .= $event_response['description'];
        $output .= '</td>';
        $output .= '</tr>';
    }

    if (empty($event_response['params']) === false) {
        $response_params = explode(',', $event_response['params']);
        if (is_array($response_params) === true) {
            if ($massive === true) {
                $output .= '<div>';
            } else {
                $output .= '<tr class="params_rows">';
                $output .= '<td class="left pdd_l_20px height_30px" colspan="3">';
                $output .= __('Parameters');
                $output .= '</td>';
                $output .= '</tr>';
            }

            foreach ($response_params as $param) {
                $param = trim(io_safe_output($param));
                if ($massive === true) {
                    $output .= '<div>';
                    $output .= '<label>';
                    $output .= $param;
                    $output .= '</label>';
                    $output .= '<input type="text" name="values_params_'.$param.'" />';
                    $output .= '</div>';
                } else {
                    $output .= '<tr class="params_rows">';
                    $output .= '<td style="text-align:left; padding-left:40px; font-weight: normal; font-style: italic;">';
                    $output .= $param;
                    $output .= '</td>';
                    $output .= '<td style="text-align:left" colspan="2">';
                    $output .= '<input type="text" name="values_params_'.$param.'" />';
                    $output .= '</td>';
                    $output .= '</tr>';
                }
            }

            if ($massive === true) {
                $output .= '</div>';
            }
        }
    }

    echo $output;
    return;
}
