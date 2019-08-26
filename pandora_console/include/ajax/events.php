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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
global $config;

require_once 'include/functions_events.php';
require_once 'include/functions_agents.php';
require_once 'include/functions_ui.php';
require_once 'include/functions_db.php';
require_once 'include/functions_io.php';
require_once 'include/functions.php';
enterprise_include_once('meta/include/functions_events_meta.php');
enterprise_include_once('include/functions_metaconsole.php');

// Check access.
check_login();

if (! check_acl($config['id_user'], 0, 'ER')
    && ! check_acl($config['id_user'], 0, 'EW')
    && ! check_acl($config['id_user'], 0, 'EM')
) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access event viewer'
    );
    include 'general/noaccess.php';
    return;
}

$get_events_details = (bool) get_parameter('get_events_details');
$get_list_events_agents = (bool) get_parameter('get_list_events_agents');
$get_extended_event = (bool) get_parameter('get_extended_event');
$change_status = (bool) get_parameter('change_status');
$change_owner = (bool) get_parameter('change_owner');
$add_comment = (bool) get_parameter('add_comment');
$dialogue_event_response = (bool) get_parameter('dialogue_event_response');
$perform_event_response = (bool) get_parameter('perform_event_response');
$get_response = (bool) get_parameter('get_response');
$get_response_target = (bool) get_parameter('get_response_target');
$get_response_params = (bool) get_parameter('get_response_params');
$get_response_description = (bool) get_parameter('get_response_description');
$get_event_name = (bool) get_parameter('get_event_name');
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
$in_process_event = get_parameter('in_process_event', 0);
$validate_event = get_parameter('validate_event', 0);
$delete_event = get_parameter('delete_event', 0);
$get_event_filters = get_parameter('get_event_filters', 0);
$get_comments = get_parameter('get_comments', 0);
$get_events_fired = (bool) get_parameter('get_events_fired');

if ($get_comments) {
    $event = get_parameter('event', false);
    $filter = get_parameter('filter', false);

    if ($event === false) {
        return __('Failed to retrieve comments');
    }

    if ($filter['group_rep'] == 1) {
        $events = events_get_all(
            ['te.*'],
            // Filter.
            $filter,
            // Offset.
            null,
            // Limit.
            null,
            // Order.
            null,
            // Sort_field.
            null,
            // History.
            $filter['history'],
            // Return_sql.
            false,
            // Having.
            sprintf(
                ' HAVING max_id_evento = %d',
                $event['id_evento']
            )
        );
        if ($events !== false) {
            $event = $events[0];
        }
    } else {
        $events = events_get_event(
            $event['id_evento'],
            false,
            $meta,
            $history
        );

        if ($events !== false) {
            $event = $events[0];
        }
    }

    echo events_page_comments($event, true);

    return;
}

if ($get_event_filters) {
    $event_filter = events_get_event_filter_select();

    echo io_json_mb_encode($event_filter);
    return;
}

// Delete event (filtered or not).
if ($delete_event) {
    $filter = get_parameter('filter', []);
    $id_evento = get_parameter('id_evento', 0);
    $event_rep = get_parameter('event_rep', 0);

    if ($event_rep === 0) {
        // Disable group by when there're result is unique.
        $filter['group_rep'] = 0;
    }

    // Check acl.
    if (! check_acl($config['id_user'], 0, 'EM')) {
        echo 'unauthorized';
        return;
    }

    $r = events_delete($id_evento, $filter);
    if ($r === false) {
        echo 'Failed';
    } else {
        echo $r;
    }

    return;
}

// Validates an event (filtered or not).
if ($validate_event) {
    $filter = get_parameter('filter', []);
    $id_evento = get_parameter('id_evento', 0);
    $event_rep = get_parameter('event_rep', 0);

    if ($event_rep === 0) {
        // Disable group by when there're result is unique.
        $filter['group_rep'] = 0;
    }

    // Check acl.
    if (! check_acl($config['id_user'], 0, 'EW')) {
        echo 'unauthorized';
        return;
    }

    $r = events_update_status($id_evento, EVENT_VALIDATE, $filter);
    if ($r === false) {
        echo 'Failed';
    } else {
        echo $r;
    }

    return;
}

// Sets status to in progress.
if ($in_process_event) {
    $filter = get_parameter('filter', []);
    $id_evento = get_parameter('id_evento', 0);
    $event_rep = get_parameter('event_rep', 0);

    if ($event_rep === 0) {
        // Disable group by when there're result is unique.
        $filter['group_rep'] = 0;
    }

    // Check acl.
    if (! check_acl($config['id_user'], 0, 'EW')) {
        echo 'unauthorized';
        return;
    }

    $r = events_update_status($id_evento, EVENT_PROCESS, $filter);
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
    $values['severity'] = get_parameter('severity');
    $values['status'] = get_parameter('status');
    $values['search'] = get_parameter('search');
    $values['text_agent'] = get_parameter('text_agent');
    $values['id_agent'] = get_parameter('id_agent');
    $values['id_agent_module'] = get_parameter('id_agent_module');
    $values['pagination'] = get_parameter('pagination');
    $values['event_view_hr'] = get_parameter('event_view_hr');
    $values['id_user_ack'] = get_parameter('id_user_ack');
    $values['group_rep'] = get_parameter('group_rep');
    $values['tag_with'] = get_parameter('tag_with', io_json_mb_encode([]));
    $values['tag_without'] = get_parameter(
        'tag_without',
        io_json_mb_encode([])
    );
    $values['filter_only_alert'] = get_parameter('filter_only_alert');
    $values['id_group_filter'] = get_parameter('id_group_filter');
    $values['date_from'] = get_parameter('date_from');
    $values['date_to'] = get_parameter('date_to');
    $values['source'] = get_parameter('source');
    $values['id_extra'] = get_parameter('id_extra');
    $values['user_comment'] = get_parameter('user_comment');

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
    $values['severity'] = get_parameter('severity');
    $values['status'] = get_parameter('status');
    $values['search'] = get_parameter('search');
    $values['text_agent'] = get_parameter('text_agent');
    $values['id_agent'] = get_parameter('id_agent');
    $values['id_agent_module'] = get_parameter('id_agent_module');
    $values['pagination'] = get_parameter('pagination');
    $values['event_view_hr'] = get_parameter('event_view_hr');
    $values['id_user_ack'] = get_parameter('id_user_ack');
    $values['group_rep'] = get_parameter('group_rep');
    $values['tag_with'] = get_parameter('tag_with', io_json_mb_encode([]));
    $values['tag_without'] = get_parameter(
        'tag_without',
        io_json_mb_encode([])
    );
    $values['filter_only_alert'] = get_parameter('filter_only_alert');
    $values['id_group_filter'] = get_parameter('id_group_filter');
    $values['date_from'] = get_parameter('date_from');
    $values['date_to'] = get_parameter('date_to');
    $values['source'] = get_parameter('source');
    $values['id_extra'] = get_parameter('id_extra');
    $values['user_comment'] = get_parameter('user_comment');

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
            'status'        => EVENT_NO_VALIDATED,
            'event_view_hr' => $config['event_view_hr'],
            'group_rep'     => 1,
            'tag_with'      => [],
            'tag_without'   => [],
            'history'       => false,
        ];
    }

    $event_filter['search'] = io_safe_output($event_filter['search']);
    $event_filter['id_name'] = io_safe_output($event_filter['id_name']);
    $event_filter['tag_with'] = base64_encode(
        io_safe_output($event_filter['tag_with'])
    );
    $event_filter['tag_without'] = base64_encode(
        io_safe_output($event_filter['tag_without'])
    );

    echo io_json_mb_encode($event_filter);
}

if ($load_filter_modal) {
    $current = get_parameter('current_filter', '');
    $filters = events_get_event_filter_select();
    $user_groups_array = users_get_groups_for_select(
        $config['id_user'],
        $access,
        true,
        true,
        false
    );

    echo '<div id="load-filter-select" class="load-filter-modal">';
    $table = new StdClass;
    $table->id = 'load_filter_form';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'databox';
    if (is_metaconsole()) {
        $table->cellspacing = 0;
        $table->cellpadding = 0;
        $table->class = 'databox filters';
    }

    $table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
    if (!is_metaconsole()) {
        $table->style[0] = 'width: 50%; width:50%;';
    }

    $data = [];
    $table->rowid[3] = 'update_filter_row1';
    $data[0] = __('Load filter').$jump;
    $data[0] .= html_print_select(
        $filters,
        'filter_id',
        $current,
        '',
        __('None'),
        0,
        true
    );
    $data[1] = html_print_submit_button(
        __('Load filter'),
        'load_filter',
        false,
        'class="sub upd" onclick="load_form_filter();"',
        true
    );
    $table->data[] = $data;
    $table->rowclass[] = '';

    html_print_table($table);
    echo '</div>';
    ?>
<script type="text/javascript">
function show_filter() {
    $("#load-filter-select").dialog({
        resizable: true,
        draggable: true,
        modal: false,
        closeOnEscape: true
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
                if (i == 'id_group')
                    $("#id_group").val(val);
                if (i == 'event_type')
                    $("#event_type").val(val);
                if (i == 'severity')
                    $("#severity").val(val);
                if (i == 'status')
                    $("#status").val(val);
                if (i == 'search')
                    $("#text-search").val(val);
                if (i == 'text_agent')
                    $("#text_id_agent").val(val);
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
                if (i == 'group_rep')
                    $("#group_rep").val(val);
                if (i == 'tag_with')
                    $("#hidden-tag_with").val(val);
                if (i == 'tag_without')
                    $("#hidden-tag_without").val(val);
                if (i == 'filter_only_alert')
                    $("#filter_only_alert").val(val);
                if (i == 'id_group_filter')
                    $("#id_group_filter").val(val);
                if (i == 'source')
                    $("#text-source").val(val);
                if (i == 'id_extra')
                    $("#text-id_extra").val(val);
                if (i == 'user_comment')
                    $("#text-user_comment").val(val);
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
    dt_events.draw(false);
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
        if (is_metaconsole()) {
            $table->class = 'databox filters';
            $table->cellspacing = 0;
            $table->cellpadding = 0;
        }

        $table->styleTable = 'font-weight: bold; text-align:left;';
        if (!is_metaconsole()) {
            $table->style[0] = 'width: 50%; width:50%;';
        }

        $data = [];
        $table->rowid[0] = 'update_save_selector';
        $data[0] = html_print_radio_button(
            'filter_mode',
            'new',
            '',
            true,
            true
        ).__('New filter').'';

        $data[1] = html_print_radio_button(
            'filter_mode',
            'update',
            '',
            false,
            true
        ).__('Update filter').'';

        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $table->rowid[1] = 'save_filter_row1';
        $data[0] = __('Filter name').$jump;
        $data[0] .= html_print_input_text('id_name', '', '', 15, 255, true);
        if (is_metaconsole()) {
            $data[1] = __('Save in Group').$jump;
        } else {
            $data[1] = __('Filter group').$jump;
        }

        $user_groups_array = users_get_groups_for_select(
            $config['id_user'],
            'EW',
            users_can_manage_group_all(),
            true
        );

        $data[1] .= html_print_select(
            $user_groups_array,
            'id_group_filter',
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
            true
        );
        $data[1] = html_print_submit_button(
            __('Update filter'),
            'update_filter',
            false,
            'class="sub upd" onclick="save_update_filter();"',
            true
        );

        $table->data[] = $data;
        $table->rowclass[] = '';

        html_print_table($table);
        echo '<div>';
            echo html_print_submit_button(
                __('Save filter'),
                'save_filter',
                false,
                'class="sub upd" style="float:right;" onclick="save_new_filter();"',
                true
            );
        echo '</div>';
    } else {
        include 'general/noaccess.php';
    }

    echo '</div>';
    ?>
<script type="text/javascript">
function show_save_filter() {
    $('#save_filter_row1').show();
    $('#save_filter_row2').show();
    $('#update_filter_row1').hide();
    // Filter save mode selector
    $("[name='filter_mode']").click(function() {
        if ($(this).val() == 'new') {
            $('#save_filter_row1').show();
            $('#save_filter_row2').show();
            $('#submit-save_filter').show();
            $('#update_filter_row1').hide();
        }
        else {
            $('#save_filter_row1').hide();
            $('#save_filter_row2').hide();
            $('#update_filter_row1').show();
            $('#submit-save_filter').hide();
        }
    });
    $("#save-filter-select").dialog({
        resizable: true,
        draggable: true,
        modal: false,
        closeOnEscape: true
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
            "id_group" : $("select#id_group").val(),
            "event_type" : $("#event_type").val(),
            "severity" : $("#severity").val(),
            "status" : $("#status").val(),
            "search" : $("#text-search").val(),
            "text_agent" : $("#text_id_agent").val(),
            "id_agent" : $('input:hidden[name=id_agent]').val(),
            "id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
            "pagination" : $("#pagination").val(),
            "event_view_hr" : $("#text-event_view_hr").val(),
            "id_user_ack" : $("#id_user_ack").val(),
            "group_rep" : $("#group_rep").val(),
            "tag_with": Base64.decode($("#hidden-tag_with").val()),
            "tag_without": Base64.decode($("#hidden-tag_without").val()),
            "filter_only_alert" : $("#filter_only_alert").val(),
            "id_group_filter": $("#id_group_filter").val(),
            "date_from": $("#text-date_from").val(),
            "date_to": $("#text-date_to").val(),
            "source": $("#text-source").val(),
            "id_extra": $("#text-id_extra").val(),
            "user_comment": $("#text-user_comment").val()
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
        "id_group" : $("select#id_group").val(),
        "event_type" : $("#event_type").val(),
        "severity" : $("#severity").val(),
        "status" : $("#status").val(),
        "search" : $("#text-search").val(),
        "text_agent" : $("#text_id_agent").val(),
        "id_agent" : $('input:hidden[name=id_agent]').val(),
        "id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
        "pagination" : $("#pagination").val(),
        "event_view_hr" : $("#text-event_view_hr").val(),
        "id_user_ack" : $("#id_user_ack").val(),
        "group_rep" : $("#group_rep").val(),
        "tag_with" : Base64.decode($("#hidden-tag_with").val()),
        "tag_without" : Base64.decode($("#hidden-tag_without").val()),
        "filter_only_alert" : $("#filter_only_alert").val(),
        "id_group_filter": $("#id_group_filter").val(),
        "date_from": $("#text-date_from").val(),
        "date_to": $("#text-date_to").val(),
        "source": $("#text-source").val(),
        "id_extra": $("#text-id_extra").val(),
        "user_comment": $("#text-user_comment").val()
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


if ($get_event_name) {
    $event_id = get_parameter('event_id');

    if ($meta) {
        $name = events_meta_get_event_name($event_id, $history);
    } else {
        $name = db_get_value('evento', 'tevento', 'id_evento', $event_id);
    }

    if ($name === false) {
        return;
    }

    ui_print_truncate_text(strip_tags(io_safe_output($name)), 75, false, false, false, '...');

    return;
}

if ($get_response_description) {
    $response_id = get_parameter('response_id');

    $description = db_get_value('description', 'tevent_response', 'id', $response_id);

    if ($description === false) {
        return;
    }

    $description = io_safe_output($description);
    $description = str_replace("\r\n", '<br>', $description);

    echo $description;

    return;
}

if ($get_response_params) {
    $response_id = get_parameter('response_id');

    $params = db_get_value('params', 'tevent_response', 'id', $response_id);

    if ($params === false) {
        return;
    }

    echo json_encode(explode(',', $params));

    return;
}

if ($get_response_target) {
    $response_id = (int) get_parameter('response_id');
    $event_id = (int) get_parameter('event_id');
    $server_id = (int) get_parameter('server_id');

    $event_response = db_get_row('tevent_response', 'id', $response_id);

    if (empty($event_response)) {
        return;
    }

    echo events_get_response_target($event_id, $response_id, $server_id);

    return;
}

if ($get_response) {
    $response_id = get_parameter('response_id');

    $event_response = db_get_row('tevent_response', 'id', $response_id);

    if (empty($event_response)) {
        return;
    }

    echo json_encode($event_response);

    return;
}

if ($perform_event_response) {
    global $config;

    $command = get_parameter('target', '');

    $response_id = get_parameter('response_id');

    $event_response = db_get_row('tevent_response', 'id', $response_id);

    if (enterprise_installed()) {
        if ($event_response['server_to_exec'] != 0 && $event_response['type'] == 'command') {
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

            if (in_array(strtolower($command), $commandExclusions)) {
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

                system('ssh pandora_exec_proxy@'.$server_data['ip_address'].' "'.$timeout_bin.' 90 '.io_safe_output($command).' 2>&1"', $ret_val);
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

            system($timeout_bin.' 90 '.io_safe_output($command).' 2>&1');
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

        system($timeout_bin.' 90 '.io_safe_output($command).' 2>&1');
    }

    return;
}

if ($dialogue_event_response) {
    global $config;

    $event_id = get_parameter('event_id');
    $response_id = get_parameter('response_id');
    $command = get_parameter('target');
    $massive = get_parameter('massive');
    $end = get_parameter('end');
    $show_execute_again_btn = get_parameter('show_execute_again_btn');
    $out_iterator = get_parameter('out_iterator');
    $event_response = db_get_row('tevent_response', 'id', $response_id);

    $event = db_get_row('tevento', 'id_evento', $event_id);

    $prompt = '<br>> ';
    switch ($event_response['type']) {
        case 'command':
            if ($massive) {
                echo "<div style='text-align:left'>";
                echo $prompt.sprintf(
                    '(Event #'.$event_id.') '.__(
                        'Executing command: %s',
                        $command
                    )
                );
                echo '</div><br>';

                echo "<div id='response_loading_command_".$out_iterator."' style='display:none'>";
                echo html_print_image(
                    'images/spinner.gif',
                    true
                );
                echo '</div>';
                echo "<br><div id='response_out_".$out_iterator."' style='text-align:left'></div>";

                if ($end) {
                    echo "<br><div id='re_exec_command_".$out_iterator."' style='display:none;'>";
                    html_print_button(
                        __('Execute again'),
                        'btn_str',
                        false,
                        'execute_event_response(false);',
                        "class='sub next'"
                    );
                    echo "<span id='execute_again_loading' style='display:none'>";
                    echo html_print_image(
                        'images/spinner.gif',
                        true
                    );
                    echo '</span>';
                    echo '</div>';
                }
            } else {
                echo "<div style='text-align:left'>";
                echo $prompt.sprintf(__('Executing command: %s', $command));
                echo '</div><br>';

                echo "<div id='response_loading_command' style='display:none'>".html_print_image('images/spinner.gif', true).'</div>';
                echo "<br><div id='response_out' style='text-align:left'></div>";

                echo "<br><div id='re_exec_command' style='display:none;'>";
                html_print_button(__('Execute again'), 'btn_str', false, 'perform_response(\''.$command.'\', '.$response_id.');', "class='sub next'");
                echo '</div>';
            }
        break;

        case 'url':
            $command = str_replace('localhost', $_SERVER['SERVER_NAME'], $command);
            echo "<iframe src='".$command."' id='divframe' style='width:100%;height:90%;'></iframe>";
        break;

        default:
            // Ignore.
        break;
    }
}

if ($add_comment) {
    $comment = get_parameter('comment');
    $event_id = get_parameter('event_id');

    $return = events_comment($event_id, $comment, 'Added comment', $meta, $history);

    if ($return) {
        echo 'comment_ok';
    } else {
        echo 'comment_error';
    }

    return;
}

if ($change_status) {
    $event_ids = get_parameter('event_ids');
    $new_status = get_parameter('new_status');

    $return = events_change_status(explode(',', $event_ids), $new_status, $meta, $history);

    if ($return) {
        echo 'status_ok';
    } else {
        echo 'status_error';
    }

    return;
}

if ($change_owner) {
    $new_owner = get_parameter('new_owner');
    $event_id = get_parameter('event_id');
    $similars = true;

    if ($new_owner == -1) {
        $new_owner = '';
    }

    $return = events_change_owner($event_id, $new_owner, true, $meta, $history);

    if ($return) {
        echo 'owner_ok';
    } else {
        echo 'owner_error';
    }

    return;
}


// Generate a modal window with extended information of given event.
if ($get_extended_event) {
    global $config;

    $event = get_parameter('event', false);
    $filter = get_parameter('filter', false);

    if ($event === false) {
        return;
    }

    $event_id = $event['id_evento'];

    $readonly = false;
    if (!$meta
        && isset($config['event_replication'])
        && $config['event_replication'] == 1
        && $config['show_events_in_local'] == 1
    ) {
        $readonly = true;
    }

    // Clean url from events and store in array.
    $event['clean_tags'] = events_clean_tags($event['tags']);

    // If the event is not found, we abort.
    if (empty($event)) {
        ui_print_error_message('Event not found');
        return false;
    }

    $dialog_page = get_parameter('dialog_page', 'general');
    $filter = get_parameter('filter', []);
    $similar_ids = get_parameter('similar_ids', $event_id);
    $group_rep = $filter['group_rep'];
    $event_rep = $event['event_rep'];
    $timestamp_first = $event['min_timestamp'];
    $timestamp_last = $event['max_timestamp'];
    $server_id = $event['server_id'];
    $comments = $event['comments'];

    $event['similar_ids'] = $similar_ids;

    if (!isset($comments)) {
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

    if ($event === false) {
        return;
    }

    // Tabs.
    $tabs = "<ul class=''>";
    $tabs .= "<li><a href='#extended_event_general_page' id='link_general'>".html_print_image('images/lightning_go.png', true).'<span>'.__('General').'</span></a></li>';
    if (events_has_extended_info($event['id_evento']) === true) {
        $tabs .= "<li><a href='#extended_event_related_page' id='link_related'>".html_print_image('images/zoom.png', true).'<span>'.__('Related').'</span></a></li>';
    }

    $tabs .= "<li><a href='#extended_event_details_page' id='link_details'>".html_print_image('images/zoom.png', true).'<span>'.__('Details').'</span></a></li>';
    $tabs .= "<li><a href='#extended_event_custom_fields_page' id='link_custom_fields'>".html_print_image('images/custom_field_col.png', true).'<span>'.__('Agent fields').'</span></a></li>';
    $tabs .= "<li><a href='#extended_event_comments_page' id='link_comments'>".html_print_image('images/pencil.png', true).'<span>'.__('Comments').'</span></a></li>';

    if (!$readonly
        && ((tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'EM',
            $event['clean_tags'],
            $childrens_ids
        )) || (tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'EW',
            $event['clean_tags'],
            $childrens_ids
        )))
    ) {
        $tabs .= "<li><a href='#extended_event_responses_page' id='link_responses'>".html_print_image('images/event_responses_col.png', true).'<span>'.__('Responses').'</span></a></li>';
    }

    if ($event['custom_data'] != '') {
        $tabs .= "<li><a href='#extended_event_custom_data_page' id='link_custom_data'>".html_print_image('images/custom_field_col.png', true).'<span>'.__('Custom data').'</span></a></li>';
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
            $childrens_ids
        )) || (tags_checks_event_acl(
            $config['id_user'],
            $event['id_grupo'],
            'EW',
            $event['clean_tags'],
            $childrens_ids
        )))
    ) {
        $responses = events_page_responses($event);
    } else {
        $responses = '';
    }

    $console_url = '';
    // If metaconsole switch to node to get details and custom fields.
    if ($meta) {
        $server = metaconsole_get_connection_by_id($server_id);
        metaconsole_connect($server);
    } else {
        $server = '';
    }

    $details = events_page_details($event, $server);

    if ($meta) {
        metaconsole_restore_db();
    }

    if (events_has_extended_info($event['id_evento']) === true) {
        $related = events_page_related($event, $server);
    }

    if ($meta) {
        $server = metaconsole_get_connection_by_id($server_id);
            metaconsole_connect($server);
    }

    $custom_fields = events_page_custom_fields($event);

    $custom_data = events_page_custom_data($event);

    if ($meta) {
        metaconsole_restore_db();
    }

    $general = events_page_general($event);

    $comments = '<div id="extended_event_comments_page" class="extended_event_pages"></div>';

    $notifications = '<div id="notification_comment_error" style="display:none">'.ui_print_error_message(__('Error adding comment'), '', true).'</div>';
    $notifications .= '<div id="notification_comment_success" style="display:none">'.ui_print_success_message(__('Comment added successfully'), '', true).'</div>';
    $notifications .= '<div id="notification_status_error" style="display:none">'.ui_print_error_message(__('Error changing event status'), '', true).'</div>';
    $notifications .= '<div id="notification_status_success" style="display:none">'.ui_print_success_message(__('Event status changed successfully'), '', true).'</div>';
    $notifications .= '<div id="notification_owner_error" style="display:none">'.ui_print_error_message(__('Error changing event owner'), '', true).'</div>';
    $notifications .= '<div id="notification_owner_success" style="display:none">'.ui_print_success_message(__('Event owner changed successfully'), '', true).'</div>';

    $loading = '<div id="response_loading" style="display:none">'.html_print_image('images/spinner.gif', true).'</div>';

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
                    filter: '.json_encode($filter).'
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

if ($get_events_details) {
    $event_ids = explode(',', get_parameter('event_ids'));
    $events = db_get_all_rows_filter(
        'tevento',
        [
            'id_evento' => $event_ids,
            'order'     => 'utimestamp ASC',
        ],
        [
            'evento',
            'utimestamp',
            'estado',
            'criticity',
            'id_usuario',
        ],
        'AND',
        true
    );

    $out = '<table class="eventtable" style="width:100%;height:100%;padding:0px 0px 0px 0px; border-spacing: 0px; margin: 0px 0px 0px 0px;">';
    $out .= '<tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td></td></tr>';
    foreach ($events as $event) {
        switch ($event['estado']) {
            case 0:
                $img = ui_get_full_url('images/star.png', false, false, false);
                $title = __('New event');
            break;

            case 1:
                $img = ui_get_full_url('images/tick.png', false, false, false);
                $title = __('Event validated');
            break;

            case 2:
                $img = ui_get_full_url('images/hourglass.png', false, false, false);
                $title = __('Event in process');
            break;

            default:
                // Ignore.
            break;
        }

        $out .= '<tr class="'.get_priority_class($event['criticity']).'" style="height: 25px;">';
        $out .= '<td class="'.get_priority_class($event['criticity']).'" style="font-size:7pt" colspan=2>';
        $out .= io_safe_output($event['evento']);
        $out .= '</td></tr>';

        $out .= '<tr class="'.get_priority_class($event['criticity']).'" style="font-size:0px; height: 25px;">';
        $out .= '<td class="'.get_priority_class($event['criticity']).'" style="width: 18px; text-align:center;">';
        $out .= html_print_image(ui_get_full_url('images/clock.png', false, false, false), true, ['title' => __('Timestamp')], false, true);

        $out .= '</td>';
        $out .= '<td class="'.get_priority_class($event['criticity']).'" style="font-size:7pt">';
        $out .= date($config['date_format'], $event['utimestamp']);
        $out .= '</td></tr>';

        $out .= '<tr class="'.get_priority_class($event['criticity']).'" style="font-size:0px; height: 25px;">';
        $out .= '<td class="'.get_priority_class($event['criticity']).'" style="width: 18px; text-align:center;">';
        $out .= html_print_image($img, true, ['title' => $title], false, true);
        $out .= '</td>';
        $out .= '<td class="'.get_priority_class($event['criticity']).'" style="font-size:7pt">';
        $out .= $title;
        if ($event['estado'] == 1) {
            if (empty($event['id_usuario'])) {
                $ack_user = '<i>'.__('Auto').'</i>';
            } else {
                $ack_user = $event['id_usuario'];
            }

            $out .= ' ('.$ack_user.')';
        }

        $out .= '</td></tr>';

        $out .= '<tr style="font-size:0px; heigth: 0px; background: #999;"><td></td><td>';
        $out .= '</td></tr><tr style="font-size:0px; heigth: 0px; background: #ccc;"><td></td><td>';
        $out .= '</td></tr>';
    }

    $out .= '</table>';

    echo $out;
}

if ($table_events) {
    include_once 'include/functions_events.php';
    include_once 'include/functions_graph.php';

    $id_agente = (int) get_parameter('id_agente', 0);
    $all_events_24h = (int) get_parameter('all_events_24h', 0);

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
    echo '<div style="display: flex;" id="div_all_events_24h">';
        echo '<label style="margin: 0 1em 0 2em;"><b>'.__('Show all Events 24h').'</b></label>';
        echo html_print_switch(
            [
                'name'  => 'all_events_24h',
                'value' => $all_events_24h,
                'id'    => 'checkbox-all_events_24h',
            ]
        );
    echo '</div>';
    $date_subtract_day = (time() - (24 * 60 * 60));

    if ($all_events_24h) {
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

if ($get_list_events_agents) {
    global $config;

    $id_agent = get_parameter('id_agent');
    $server_id = get_parameter('server_id');
    $event_type = get_parameter('event_type');
    $severity = get_parameter('severity');
    $status = get_parameter('status');
    $search = get_parameter('search');
    $id_agent_module = get_parameter('id_agent_module');
    $event_view_hr = get_parameter('event_view_hr');
    $id_user_ack = get_parameter('id_user_ack');
    $tag_with = get_parameter('tag_with');
    $tag_without = get_parameter('tag_without');
    $filter_only_alert = get_parameter('filter_only_alert');
    $date_from = get_parameter('date_from');
    $date_to = get_parameter('date_to');
    $id_user = $config['id_user'];

    $returned_sql = events_sql_events_grouped_agents(
        $id_agent,
        $server_id,
        $event_type,
        $severity,
        $status,
        $search,
        $id_agent_module,
        $event_view_hr,
        $id_user_ack,
        $tag_with,
        $tag_without,
        $filter_only_alert,
        $date_from,
        $date_to,
        $id_user
    );

    $returned_list = events_list_events_grouped_agents($returned_sql);

    echo $returned_list;
    return;
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

    $prueba = grafico_eventos_total('', 280, 150, false, true);
    echo $prueba;
    return;
}

if ($graphic_event_group) {
    global $config;

    include_once $config['homedir'].'/include/functions_graph.php';

    $prueba = grafico_eventos_grupo(280, 150, '', false, true);
    echo $prueba;
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
        'class="sub next" style="float:right; margin-top:15px; margin-right:25px;"',
        true
    );

    return;
}

if ($get_events_fired) {
    $id = get_parameter('id_row');
    $idGroup = get_parameter('id_group');
    $agents = get_parameter('agents', null);

    $query = ' AND id_evento > '.$id;

    $type = [];
    $alert = get_parameter('alert_fired');
    if ($alert == 'true') {
        $resultAlert = alerts_get_event_status_group(
            $idGroup,
            [
                'alert_fired',
                'alert_ceased',
            ],
            $query,
            $agents
        );
    }

    $critical = get_parameter('critical');
    if ($critical == 'true') {
        $resultCritical = alerts_get_event_status_group(
            $idGroup,
            'going_up_critical',
            $query,
            $agents
        );
    }

    $warning = get_parameter('warning');
    if ($warning == 'true') {
        $resultWarning = alerts_get_event_status_group(
            $idGroup,
            'going_up_warning',
            $query,
            $agents
        );
    }

    $unknown = get_parameter('unknown');
    if ($unknown == 'true') {
        $resultUnknown = alerts_get_event_status_group(
            $idGroup,
            'going_unknown',
            $query,
            $agents
        );
    }

    if ($resultAlert) {
        $return = [
            'fired' => $resultAlert,
            'sound' => $config['sound_alert'],
        ];
        $event = events_get_event($resultAlert);

        $module_name = modules_get_agentmodule_name($event['id_agentmodule']);
        $agent_name = agents_get_alias($event['id_agente']);

        $return['message'] = io_safe_output($agent_name).' - ';
        $return['message'] .= __('Alert fired in module ');
        $return['message'] .= io_safe_output($module_name).' - ';
        $return['message'] .= $event['timestamp'];
    } else if ($resultCritical) {
        $return = [
            'fired' => $resultCritical,
            'sound' => $config['sound_critical'],
        ];
        $event = events_get_event($resultCritical);

        $module_name = modules_get_agentmodule_name($event['id_agentmodule']);
        $agent_name = agents_get_alias($event['id_agente']);

        $return['message'] = io_safe_output($agent_name).' - ';
        $return['message'] .= __('Module ').io_safe_output($module_name);
        $return['message'] .= __(' is going to critical').' - ';
        $return['message'] .= $event['timestamp'];
    } else if ($resultWarning) {
        $return = [
            'fired' => $resultWarning,
            'sound' => $config['sound_warning'],
        ];
        $event = events_get_event($resultWarning);

        $module_name = modules_get_agentmodule_name($event['id_agentmodule']);
        $agent_name = agents_get_alias($event['id_agente']);

        $return['message'] = io_safe_output($agent_name).' - ';
        $return['message'] .= __('Module ').io_safe_output($module_name);
        $return['message'] .= __(' is going to warning').' - ';
        $return['message'] .= $event['timestamp'];
    } else if ($resultUnknown) {
        $return = [
            'fired' => $resultUnknown,
            'sound' => $config['sound_alert'],
        ];
        $event = events_get_event($resultUnknown);

        $module_name = modules_get_agentmodule_name($event['id_agentmodule']);
        $agent_name = agents_get_alias($event['id_agente']);

        $return['message'] = io_safe_output($agent_name).' - ';
        $return['message'] .= __('Module ').io_safe_output($module_name);
        $return['message'] .= __(' is going to unknown').' - ';
        $return['message'] .= $event['timestamp'];
    } else {
        $return = ['fired' => 0];
    }

    echo io_json_mb_encode($return);
}
