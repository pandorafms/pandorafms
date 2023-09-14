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
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
enterprise_include_once('include/functions_audit.php');

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

$save_filter_modal = get_parameter('save_filter_modal', 0);
$load_filter_modal = get_parameter('load_filter_modal', 0);
$get_filter_values = get_parameter('get_filter_values', 0);
$update_log_filter = get_parameter('update_log_filter', 0);
$save_log_filter = get_parameter('save_log_filter', 0);
$recover_aduit_log_select = get_parameter('recover_aduit_log_select', 0);


// Saves an event filter.
if ($save_log_filter) {
    $values = [];
    $values['id_name'] = get_parameter('id_name');
    $values['text'] = get_parameter('text', '');
    $values['custom_date'] = get_parameter('custom_date');
    $values['date'] = get_parameter('date');
    $values['date_text'] = get_parameter('date_text');
    $values['date_units'] = get_parameter('date_units');
    $values['date_init'] = get_parameter('date_init');
    $values['time_init'] = get_parameter('time_init');
    $values['date_end'] = get_parameter('date_end');
    $values['time_end'] = get_parameter('time_end');
    $values['ip'] = get_parameter('ip', '');
    $values['type'] = get_parameter('type', -1);
    $values['user'] = get_parameter('user', -1);

    $exists = (bool) db_get_value_filter(
        'id_filter',
        'tsesion_filter',
        ['id_name' => $values['id_name']]
    );

    if ($exists) {
        echo 'duplicate';
    } else {
        $result = db_process_sql_insert('tsesion_filter', $values);

        if ($result === false) {
            echo 'error';
        } else {
            echo $result;
        }
    }
}


if ($recover_aduit_log_select) {
    echo json_encode(audit_get_audit_filter_select_fix_order());
}

if ($update_log_filter) {
    $values = [];
    $id = get_parameter('id');
    $values['text'] = get_parameter('text', '');
    $values['custom_date'] = get_parameter('custom_date');
    $values['date'] = get_parameter('date');
    $values['date_text'] = get_parameter('date_text');
    $values['date_units'] = get_parameter('date_units');
    $values['date_init'] = get_parameter('date_init');
    $values['time_init'] = get_parameter('time_init');
    $values['date_end'] = get_parameter('date_end');
    $values['time_end'] = get_parameter('time_end');
    $values['ip'] = get_parameter('ip', '');
    $values['type'] = get_parameter('type', -1);
    $values['user'] = get_parameter('user', -1);

    $result = db_process_sql_update(
        'tsesion_filter',
        $values,
        ['id_filter' => $id]
    );

    if ($result === false) {
        echo 'error';
    } else {
        echo 'ok';
    }
}


if ($get_filter_values) {
    $id_filter = get_parameter('id');

    $event_filter = audit_get_audit_log_filter($id_filter);
    echo json_encode($event_filter);
}


if ($load_filter_modal) {
    $filters = audit_get_audit_filter_select();
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
    $table->class = 'databox no_border';
    if (is_metaconsole()) {
        $table->cellspacing = 0;
        $table->cellpadding = 0;
        $table->class = 'databox filters no_border';
    }

    $table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
    $filter_id_width = 'w100p';

    $data = [];
    $table->rowid[3] = 'update_filter_row1';
    $data[0] = __('Load filter').$jump;
    $data[0] .= html_print_select(
        $filters,
        'filter_id',
        '',
        '',
        __('None'),
        0,
        true,
        false,
        true,
        '',
        false,
        'width:'.$filter_id_width.';'
    );

    $table->rowclass[] = 'display-grid';
    $data[1] = html_print_submit_button(
        __('Load filter'),
        'load_filter',
        false,
        [
            'class'   => 'mini w30p',
            'icon'    => 'load',
            'style'   => 'margin-left: 208px; width: 130px;',
            'onclick' => 'load_filter_values();',
        ],
        true
    );
    $data[1] .= html_print_input_hidden('load_filter', 1, true);
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
        closeOnEscape: true,
        width: "auto"
    });
}


function load_filter_values() {
    $.ajax({
        method: 'POST',
        url: '<?php echo ui_get_full_url('ajax.php'); ?>',
        dataType: 'json',
        data: {
            page: 'include/ajax/audit_log',
            get_filter_values: 1,
            "id" : $('#filter_id :selected').val()
        },
        success: function(data) {
            var options = "";
            console.log(data);
            $.each(data,function(i,value){
                if (i == 'text'){
                    $("#text-filter_text").val(value);
                } else if (i == 'ip'){
                    $("#text-filter_ip").val(value);
                } else if (i == 'type'){
                    $("#filter_type").val(value).change();
                } else if (i == 'user'){
                    $("#filter_user").val(value).change();
                } else if (i == 'custom_date'){
                    $('#hidden-custom_date').val(value).change();
                    if ($('#hidden-custom_date').val()==='0'){
                        $('#date_default').show();
                        $('#date_range').hide();
                        $('#date_extend').hide();
                        $('#date').val('".SECONDS_1DAY."').trigger('change');
                    } else if ($('#hidden-custom_date').val()==='1'){
                        $('#date_range').show();
                        $('#date_default').hide();
                        $('#date_extend').hide();
                    } else {
                        $('#date_range').hide();
                        $('#date_default').hide();
                        $('#date_extend').show();
                    }
                } else if (i == 'date'){
                    $('#date').val(value).change();
                }  else if (i == 'date_end'){
                    $('#text-date_end').val(value);
                } else if (i == 'date_init'){
                    $('#text-date_init').val(value);
                } else if (i == 'date_text'){
                    $('#text-date_text').val(value);
                } else if (i == 'date_units'){
                    $('#date_units').val(value).change();
                } else if (i == 'time_end'){
                    $('#text-time_end').val(value);
                } else if (i == 'time_init'){
                    $('#text-time_init').val(value);
                }
            });
        }
    });

    // Close dialog.
    $("#load-filter-select").dialog('close');
}

$(document).ready (function() {
    show_filter();
})

</script>
    <?php
    return;
}


if ($save_filter_modal) {
    echo '<div id="save-filter-select" style="width:600px;">';

    if (check_acl($config['id_user'], 0, 'EW') === 1 || check_acl($config['id_user'], 0, 'EM') === 1) {
        echo '<div id="info_box"></div>';
        $table = new StdClass;
        $table->id = 'save_filter_form';
        $table->width = '100%';
        $table->cellspacing = 4;
        $table->cellpadding = 4;
        $table->class = 'databox no_border';
        if (is_metaconsole()) {
            $table->class = 'databox filters no_border';
            $table->cellspacing = 0;
            $table->cellpadding = 0;
        }

        $table->styleTable = 'font-weight: bold; text-align:left;';
        if (!is_metaconsole()) {
            $table->style[0] = 'width: 50%; width:50%;';
        }

        $data = [];
        $table->rowid[0] = 'update_save_selector';
        $data[0] = html_print_div(
            [
                'style'   => 'display: flex;',
                'content' => html_print_radio_button(
                    'filter_mode',
                    'new',
                    __('New filter'),
                    true,
                    true
                ),
            ],
            true
        );

        $data[1] = html_print_div(
            [
                'style'   => 'display: flex;',
                'content' => html_print_radio_button(
                    'filter_mode',
                    'update',
                    __('Update filter'),
                    false,
                    true
                ),
            ],
            true
        );

        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $table->rowid[1] = 'save_filter_row1';
        $data[0] = __('Filter name').$jump;
        $data[0] .= html_print_input_text('id_name', '', '', 15, 255, true);

        $data[1] = html_print_submit_button(
            __('Save filter'),
            'save_filter',
            false,
            [
                'class'   => 'mini ',
                'icon'    => 'save',
                'style'   => 'margin-left: 175px; width: 125px;',
                'onclick' => 'save_new_filter();',
            ],
            true
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

        $_filters_update = audit_get_audit_filter_select();

        $data[0] .= html_print_select(
            $_filters_update,
            'overwrite_filter',
            '',
            '',
            '',
            0,
            true
        );
        $table->rowclass[] = 'display-grid';
        $data[1] = html_print_submit_button(
            __('Update filter'),
            'update_filter',
            false,
            [
                'class'   => 'mini ',
                'icon'    => 'save',
                'style'   => 'margin-left: 155px; width: 145px;',
                'onclick' => 'save_update_filter();',
            ],
            true
        );

        $table->data[] = $data;
        $table->rowclass[] = '';

        html_print_table($table);
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
        closeOnEscape: true,
        width: 380
    });
}

function save_new_filter() {

    // If the filter name is blank show error
    if ($('#text-id_name').val() == '') {
        $('#info_box').html("<h3 class='error'><?php echo __('Filter name cannot be left blank'); ?></h3>");
        return false;
    }

    var id_filter_save;

    jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {
            "page" : "include/ajax/audit_log",
            "save_log_filter" : 1,
            "id_name" : $("#text-id_name").val(),
            "text" : $("#text-filter_text").val(),
            "custom_date": $('#hidden-custom_date').val(),
            "date": $('#date option:selected').val(),
            "date_text": $('#text-date_text').val(),
            "date_units": $('#date_units option:selected').val(),
            "date_init": $('#text-date_init').val(),
            "time_init": $('#text-time_init').val(),
            "date_end": $('#text-date_end').val(),
            "time_end": $('#text-time_end').val(),
            "ip" : $('#text-filter_ip').val(),
            "type" : $('#filter_type :selected').val(),
            "user" : $('#filter_user :selected').val(),
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
            } else  if (data == 'duplicate') {
                $('#info_box').html("<h3 class='error'><?php echo __('Filter name already on use'); ?></h3>");
                $('#info_box').show();
            } else {
                // Close dialog.
                $("#save-filter-select").dialog('close');
            }
        }
    );
}

// This updates an event filter
function save_update_filter() {
    var id_filter_update =  $("#overwrite_filter").val();
    var name_filter_update = $("#overwrite_filter option[value='"+id_filter_update+"']").text();

    jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        {"page" : "include/ajax/audit_log",
        "update_log_filter" : 1,
        "id" : $("#overwrite_filter :selected").val(),
        "text" : $("#text-filter_text").val(),
        "custom_date": $('#hidden-custom_date').val(),
        "date": $('#date option:selected').val(),
        "date_text": $('#text-date_text').val(),
        "date_units": $('#date_units option:selected').val(),
        "date_init": $('#text-date_init').val(),
        "time_init": $('#text-time_init').val(),
        "date_end": $('#text-date_end').val(),
        "time_end": $('#text-time_end').val(),
        "ip" : $('#text-filter_ip').val(),
        "type" : $('#filter_type :selected').val(),
        "user" : $('#filter_user :selected').val(),
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

        // Close dialog
        $('.ui-dialog-titlebar-close').trigger('click');
        return false;
}


$(document).ready(function (){
    show_save_filter();
});
</script>
    <?php
    return;
}
