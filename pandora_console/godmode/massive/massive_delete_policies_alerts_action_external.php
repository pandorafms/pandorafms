<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Community
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

check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive alert deletion'
    );
    include 'general/noaccess.php';
    return;
}

enterprise_include_once('include/functions_policies.php');

if (is_ajax() === true) {
    $load_policies = get_parameter('load_policies', 0);
    $load_alerts_policies = get_parameter('load_alerts_policies', 0);
    $load_actions_alerts = get_parameter('load_actions_alerts', 0);

    if ($load_policies) {
        $id_group = get_parameter('id_group', 0);
        if ($id_group !== '0') {
            $filter['force_id_group'] = $id_group;
            $arr_policies = policies_get_policies($filter);
        } else {
            $arr_policies = policies_get_policies();
        }

        $policies = [];
        foreach ($arr_policies as $row) {
            $policies[$row['id']] = $row['name'];
        }

        echo json_encode($policies, true);
        return;
    }

    if ($load_alerts_policies) {
        $ids_policies = get_parameter('policies', []);

        $alerts = [];
        foreach ($ids_policies as $policie) {
            foreach (policies_get_alerts($policie, ['id_policy_module' => '0']) as $row) {
                $name = io_safe_output(alerts_get_alert_template_name($row['id_alert_template']).' - '.$row['name_extern_module']);
                $alerts[$row['id'].'__'.policies_get_name($policie).' - '.$name] = $name;
            }
        }

        echo json_encode($alerts, true);
        return;
    }

    if ($load_actions_alerts) {
        $array_alerts = get_parameter('id_alerts', []);

        $actions = [];
        foreach ($array_alerts as $alert) {
            $alert_policie = explode('__', $alert);
            $alert_id = $alert_policie[0];
            $alert_name = $alert_policie[1];
            $array_actions = db_get_all_rows_filter(
                'tpolicy_alerts_actions',
                ['id_policy_alert' => $alert]
            );
            foreach ($array_actions as $row) {
                $action = db_get_row_filter(
                    'talert_actions',
                    ['id' => $row['id_alert_action']]
                );
                $actions[$row['id']] = $alert_name.' - '.$action['name'];
            }
        }

        echo json_encode($actions, true);
        return;
    }
}

$delete = (bool) get_parameter_post('delete');

if ($delete) {
    $array_actions = get_parameter('id_actions');
    foreach ($array_actions as $id_action) {
        $result = policies_delete_action_alert($id_action);
    }

    ui_print_result_message($result, __('Deleted action successfully'), __('Could not be deleted'), '');
}


$table = new stdClass();
$table->id = 'add_table';
$table->class = 'databox filters filter-table-adv';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold; vertical-align:top';
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

$table->data = [];

$table->data[0][0] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        false,
        'AW',
        true,
        'id_group',
        0,
        '',
        'All',
        0,
        true,
        false,
        true,
        '',
        false,
        'width:180px;'
    )
);

$table->data[0][1] = html_print_label_input_block(
    __('Group recursion'),
    html_print_checkbox('recursion', 1, $recursion, true, false, '', true)
);

$arr_policies = policies_get_policies();
$policies = [];
foreach ($arr_policies as $row) {
    $policies[$row['id']] = $row['name'];
}

$table->data[1][0] = html_print_label_input_block(
    __('Policies'),
    html_print_select(
        $policies,
        'id_policies[]',
        '',
        '',
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'width:100%;'
    )
);

$table->data[1][1] = html_print_label_input_block(
    __('Alerts'),
    html_print_select(
        [],
        'id_alerts[]',
        '',
        '',
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'width:100%;'
    )
);

$table->colspan[2][0] = 2;
$table->data[2][0] = html_print_label_input_block(
    __('Actions'),
    html_print_select(
        [],
        'id_actions[]',
        '',
        '',
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'width:100%;'
    )
);

echo '<form method="post" id="form_alerts" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=add_alerts">';
html_print_table($table);

attachActionButton('delete', 'delete', $table->width, false, $SelectAction);

echo '</form>';

?>

<script type="text/javascript">

var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;

$(document).ready (function () {

    $('#id_group').change(function(){
        var data = $(this).val();
        $.ajax({
            type: "POST",
            url: "ajax.php",
            data: {
                page: 'godmode/massive/massive_delete_policies_alerts_action_external',
                load_policies: 1,
                id_group: data,
            },
            success: function(data) {
                var data = $.parseJSON(data);
                var options = '';
                $.each( data, function( id, name ) {
                    options += '<option value="'+id+'">'+name+'</option>';
                });
                if (options!== ''){
                    $('#id_policies').html(options);
                } else {
                    $('#id_policies').html('<option value="0"><?php echo __('None'); ?></option>');
                }
                $('#id_policies').trigger('change');
            }
        });
    });


    $('#id_policies').change(function(){
        var data = $(this).val();
        $.ajax({
            type: "POST",
            url: "ajax.php",
            data: {
                page: 'godmode/massive/massive_delete_policies_alerts_action_external',
                load_alerts_policies: 1,
                policies: data,
            },
            success: function(data) {
                var data = $.parseJSON(data);
                var options = '';
                $.each( data, function( id, name ) {
                    options += '<option value="'+id+'">'+name+'</option>';
                });
                if (options!== ''){
                    $('#id_alerts').html(options);
                } else {
                    $('#id_alerts').html('<option value="0"><?php echo __('None'); ?></option>');
                }
            }
        });
    })

    $('#id_alerts').change(function(){
        var data = $(this).val();
        if (data !== 0){
            $.ajax({
                type: "POST",
                url: "ajax.php",
                data: {
                    page: 'godmode/massive/massive_delete_policies_alerts_action_external',
                    load_actions_alerts: 1,
                    id_alerts: data,
                },
                success: function(data) {
                    var data = $.parseJSON(data);
                    var options = '';
                    $.each( data, function( id, name ) {
                        options += '<option value="'+id+'">'+name+'</option>';
                    });
                    if (options!== ''){
                        $('#id_actions').html(options);
                    } else {
                        $('#id_actions').html('<option value="0"><?php echo __('None'); ?></option>');
                    }
                }
            });
        }
    })

    $("form").submit(function(e){
        var id_policies = $('#id_policies :selected').val();
        var id_alerts = $('#id_alerts :selected').val();
        var id_actions = $('#id_actions :selected').val();

        if ($.isEmptyObject(id_policies) || $.isEmptyObject(id_alerts) || $.isEmptyObject(id_actions) || id_policies === '0' || id_alerts === '0'){
            e.preventDefault();
            alert('<?php echo __('Policies, Alerts and Action must to be selected'); ?>');
        }
    })
});

</script>
