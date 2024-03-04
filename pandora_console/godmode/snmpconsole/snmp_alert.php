<?php
/**
 * SNMP Alert Management view.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage SNMP Alert Management
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

// Load global vars.
if ((bool) check_acl($config['id_user'], 0, 'LW') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access SNMP Alert Management'
    );
    include 'general/noaccess.php';
    return;
}

$trap_types = [
    SNMP_TRAP_TYPE_NONE                   => __('None'),
    SNMP_TRAP_TYPE_COLD_START             => __('Cold start (0)'),
    SNMP_TRAP_TYPE_WARM_START             => __('Warm start (1)'),
    SNMP_TRAP_TYPE_LINK_DOWN              => __('Link down (2)'),
    SNMP_TRAP_TYPE_LINK_UP                => __('Link up (3)'),
    SNMP_TRAP_TYPE_AUTHENTICATION_FAILURE => __('Authentication failure (4)'),
    SNMP_TRAP_TYPE_OTHER                  => __('Other'),
];

// Form submitted.
$update_alert = (bool) get_parameter('update_alert', false);
$create_alert = (bool) get_parameter('create_alert', false);
$save_alert = (bool) get_parameter('save_alert', false);
$modify_alert = (bool) get_parameter('modify_alert', false);
$delete_alert = (bool) get_parameter('delete_alert', false);
$multiple_delete = (bool) get_parameter('multiple_delete', false);
$add_action = (bool) get_parameter('add_alert', false);
$delete_action = get_parameter('delete_action', 0);
$duplicate_alert = get_parameter('duplicate_alert', 0);

if ($add_action === true) {
    $values['id_alert_snmp'] = (int) get_parameter('id_alert_snmp');
    $values['alert_type'] = (int) get_parameter('alert_type');
    $values[db_escape_key_identifier('al_field1')] = get_parameter('field1_value');
    $values[db_escape_key_identifier('al_field2')] = get_parameter('field2_value');
    $values[db_escape_key_identifier('al_field3')] = get_parameter('field3_value');
    $values[db_escape_key_identifier('al_field4')] = get_parameter('field4_value');
    $values[db_escape_key_identifier('al_field5')] = get_parameter('field5_value');
    $values[db_escape_key_identifier('al_field6')] = get_parameter('field6_value');
    $values[db_escape_key_identifier('al_field7')] = get_parameter('field7_value');
    $values[db_escape_key_identifier('al_field8')] = get_parameter('field8_value');
    $values[db_escape_key_identifier('al_field9')] = get_parameter('field9_value');
    $values[db_escape_key_identifier('al_field10')] = get_parameter('field10_value');
    $values[db_escape_key_identifier('al_field11')] = get_parameter('field11_value');
    $values[db_escape_key_identifier('al_field12')] = get_parameter('field12_value');
    $values[db_escape_key_identifier('al_field13')] = get_parameter('field13_value');
    $values[db_escape_key_identifier('al_field14')] = get_parameter('field14_value');
    $values[db_escape_key_identifier('al_field15')] = get_parameter('field15_value');
    $values[db_escape_key_identifier('al_field16')] = get_parameter('field16_value');
    $values[db_escape_key_identifier('al_field17')] = get_parameter('field17_value');
    $values[db_escape_key_identifier('al_field18')] = get_parameter('field18_value');
    $values[db_escape_key_identifier('al_field19')] = get_parameter('field19_value');
    $values[db_escape_key_identifier('al_field20')] = get_parameter('field20_value');

    $result = db_process_sql_insert('talert_snmp_action', $values);
}

if ($delete_action) {
    $action_id = get_parameter('action_id');

    $result = db_process_sql_delete('talert_snmp_action', ['id' => $action_id]);
}

if ($update_alert || $modify_alert) {
    $subTitle = __('Update alert');
    $helpString = 'snmp_alert_update_tab';
} else if ($create_alert || $save_alert) {
    $subTitle = __('Create alert');
    $helpString = 'snmp_alert_overview_tab';
} else {
    $subTitle = __('Alert overview');
    $helpString = '';
}

ui_print_standard_header(
    $subTitle,
    'images/op_snmp.png',
    false,
    $helpString,
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Alerts'),
        ],
        [
            'link'  => '',
            'label' => __('SNMP Console'),
        ],
    ]
);

if ($save_alert || $modify_alert) {
    $id_as = (int) get_parameter('id_alert_snmp', -1);

    $source_ip = (string) get_parameter_post('source_ip');
    $alert_type = (int) get_parameter_post('alert_type');
    // Event, e-mail.
    $description = (string) get_parameter_post('description');
    $oid = (string) get_parameter_post('oid');
    $custom_value = (string) get_parameter_post('custom_value');
    $time_threshold = (int) get_parameter_post('time_threshold', SECONDS_5MINUTES);
    $time_other = (int) get_parameter_post('time_other', -1);
    $al_field1 = (string) get_parameter_post('field1_value');
    $al_field2 = (string) get_parameter_post('field2_value');
    $al_field3 = (string) get_parameter_post('field3_value');
    $al_field4 = (string) get_parameter_post('field4_value');
    $al_field5 = (string) get_parameter_post('field5_value');
    $al_field6 = (string) get_parameter_post('field6_value');
    $al_field7 = (string) get_parameter_post('field7_value');
    $al_field8 = (string) get_parameter_post('field8_value');
    $al_field9 = (string) get_parameter_post('field9_value');
    $al_field10 = (string) get_parameter_post('field10_value');
    $al_field11 = (string) get_parameter_post('field11_value');
    $al_field12 = (string) get_parameter_post('field12_value');
    $al_field13 = (string) get_parameter_post('field13_value');
    $al_field14 = (string) get_parameter_post('field14_value');
    $al_field15 = (string) get_parameter_post('field15_value');
    $al_field16 = (string) get_parameter_post('field16_value');
    $al_field17 = (string) get_parameter_post('field17_value');
    $al_field18 = (string) get_parameter_post('field18_value');
    $al_field19 = (string) get_parameter_post('field19_value');
    $al_field20 = (string) get_parameter_post('field20_value');
    $max_alerts = (int) get_parameter_post('max_alerts', 1);
    $min_alerts = (int) get_parameter_post('min_alerts', 0);
    $priority = (int) get_parameter_post('priority', 0);
    $custom_oid_data_1 = (string) get_parameter('custom_oid_data_1');
    $custom_oid_data_2 = (string) get_parameter('custom_oid_data_2');
    $custom_oid_data_3 = (string) get_parameter('custom_oid_data_3');
    $custom_oid_data_4 = (string) get_parameter('custom_oid_data_4');
    $custom_oid_data_5 = (string) get_parameter('custom_oid_data_5');
    $custom_oid_data_6 = (string) get_parameter('custom_oid_data_6');
    $custom_oid_data_7 = (string) get_parameter('custom_oid_data_7');
    $custom_oid_data_8 = (string) get_parameter('custom_oid_data_8');
    $custom_oid_data_9 = (string) get_parameter('custom_oid_data_9');
    $custom_oid_data_10 = (string) get_parameter('custom_oid_data_10');
    $custom_oid_data_11 = (string) get_parameter('custom_oid_data_11');
    $custom_oid_data_12 = (string) get_parameter('custom_oid_data_12');
    $custom_oid_data_13 = (string) get_parameter('custom_oid_data_13');
    $custom_oid_data_14 = (string) get_parameter('custom_oid_data_14');
    $custom_oid_data_15 = (string) get_parameter('custom_oid_data_15');
    $custom_oid_data_16 = (string) get_parameter('custom_oid_data_16');
    $custom_oid_data_17 = (string) get_parameter('custom_oid_data_17');
    $custom_oid_data_18 = (string) get_parameter('custom_oid_data_18');
    $custom_oid_data_19 = (string) get_parameter('custom_oid_data_19');
    $custom_oid_data_20 = (string) get_parameter('custom_oid_data_20');
    $order_1 = (int) get_parameter('order_1', 1);
    $order_2 = (int) get_parameter('order_2', 2);
    $order_3 = (int) get_parameter('order_3', 3);
    $order_4 = (int) get_parameter('order_4', 4);
    $order_5 = (int) get_parameter('order_5', 5);
    $order_6 = (int) get_parameter('order_6', 6);
    $order_7 = (int) get_parameter('order_7', 7);
    $order_8 = (int) get_parameter('order_8', 8);
    $order_9 = (int) get_parameter('order_9', 9);
    $order_10 = (int) get_parameter('order_10', 10);
    $order_11 = (int) get_parameter('order_11', 11);
    $order_12 = (int) get_parameter('order_12', 12);
    $order_13 = (int) get_parameter('order_13', 13);
    $order_14 = (int) get_parameter('order_14', 14);
    $order_15 = (int) get_parameter('order_15', 15);
    $order_16 = (int) get_parameter('order_16', 16);
    $order_17 = (int) get_parameter('order_17', 17);
    $order_18 = (int) get_parameter('order_18', 18);
    $order_19 = (int) get_parameter('order_19', 19);
    $order_20 = (int) get_parameter('order_20', 20);

    $trap_type = (int) get_parameter('trap_type', -1);
    $single_value = (string) get_parameter('single_value');
    $position = (int) get_parameter('position');
    $disable_event = (int) get_parameter('disable_event');
    $group = (int) get_parameter('group');

    if ($time_threshold == -1) {
        $time_threshold = $time_other;
    }

    if ($save_alert) {
        $values = [
            'id_alert'                             => $alert_type,
            'al_field1'                            => $al_field1,
            'al_field2'                            => $al_field2,
            'al_field3'                            => $al_field3,
            'al_field4'                            => $al_field4,
            'al_field5'                            => $al_field5,
            'al_field6'                            => $al_field6,
            'al_field7'                            => $al_field7,
            'al_field8'                            => $al_field8,
            'al_field9'                            => $al_field9,
            'al_field10'                           => $al_field10,
            'al_field11'                           => $al_field11,
            'al_field12'                           => $al_field12,
            'al_field13'                           => $al_field13,
            'al_field14'                           => $al_field14,
            'al_field15'                           => $al_field15,
            'al_field16'                           => $al_field16,
            'al_field17'                           => $al_field17,
            'al_field18'                           => $al_field18,
            'al_field19'                           => $al_field19,
            'al_field20'                           => $al_field20,
            'description'                          => $description,
            'agent'                                => $source_ip,
            'custom_oid'                           => $custom_value,
            'oid'                                  => $oid,
            'time_threshold'                       => $time_threshold,
            'max_alerts'                           => $max_alerts,
            'min_alerts'                           => $min_alerts,
            'priority'                             => $priority,
            db_escape_key_identifier('_snmp_f1_')  => $custom_oid_data_1,
            db_escape_key_identifier('_snmp_f2_')  => $custom_oid_data_2,
            db_escape_key_identifier('_snmp_f3_')  => $custom_oid_data_3,
            db_escape_key_identifier('_snmp_f4_')  => $custom_oid_data_4,
            db_escape_key_identifier('_snmp_f5_')  => $custom_oid_data_5,
            db_escape_key_identifier('_snmp_f6_')  => $custom_oid_data_6,
            db_escape_key_identifier('_snmp_f7_')  => $custom_oid_data_7,
            db_escape_key_identifier('_snmp_f8_')  => $custom_oid_data_8,
            db_escape_key_identifier('_snmp_f9_')  => $custom_oid_data_9,
            db_escape_key_identifier('_snmp_f10_') => $custom_oid_data_10,
            db_escape_key_identifier('_snmp_f11_') => $custom_oid_data_11,
            db_escape_key_identifier('_snmp_f12_') => $custom_oid_data_12,
            db_escape_key_identifier('_snmp_f13_') => $custom_oid_data_13,
            db_escape_key_identifier('_snmp_f14_') => $custom_oid_data_14,
            db_escape_key_identifier('_snmp_f15_') => $custom_oid_data_15,
            db_escape_key_identifier('_snmp_f16_') => $custom_oid_data_16,
            db_escape_key_identifier('_snmp_f17_') => $custom_oid_data_17,
            db_escape_key_identifier('_snmp_f18_') => $custom_oid_data_18,
            db_escape_key_identifier('_snmp_f19_') => $custom_oid_data_19,
            db_escape_key_identifier('_snmp_f20_') => $custom_oid_data_20,
            'order_1'                              => $order_1,
            'order_2'                              => $order_2,
            'order_3'                              => $order_3,
            'order_4'                              => $order_4,
            'order_5'                              => $order_5,
            'order_6'                              => $order_6,
            'order_7'                              => $order_7,
            'order_8'                              => $order_8,
            'order_9'                              => $order_9,
            'order_10'                             => $order_10,
            'order_11'                             => $order_11,
            'order_12'                             => $order_12,
            'order_13'                             => $order_13,
            'order_14'                             => $order_14,
            'order_15'                             => $order_15,
            'order_16'                             => $order_16,
            'order_17'                             => $order_17,
            'order_18'                             => $order_18,
            'order_19'                             => $order_19,
            'order_20'                             => $order_20,
            'trap_type'                            => $trap_type,
            'single_value'                         => $single_value,
            'position'                             => $position,
            'disable_event'                        => $disable_event,
            'id_group'                             => $group,
        ];

        $result = db_process_sql_insert('talert_snmp', $values);

        if ((bool) $result === false) {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                'Fail try to create snmp alert'
            );
            ui_print_error_message(__('There was a problem creating the alert'));
        } else {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                sprintf(
                    'Create snmp alert #%s',
                    $result
                )
            );
            ui_print_success_message(__('Successfully created'));
        }
    } else {
        $sql = sprintf(
            "UPDATE talert_snmp SET
			priority = %d, id_alert = %d, al_field1 = '%s',
			al_field2 = '%s', al_field3 = '%s', al_field4 = '%s',
			al_field5 = '%s', al_field6 = '%s',al_field7 = '%s',
			al_field8 = '%s', al_field9 = '%s',al_field10 = '%s',
			al_field11 = '%s', al_field12 = '%s', al_field13 = '%s',
			al_field14 = '%s', al_field15 = '%s', al_field16 = '%s',
            al_field17 = '%s', al_field18 = '%s', al_field19 = '%s',
            al_field20 = '%s',
			description = '%s',
			agent = '%s', custom_oid = '%s', oid = '%s',
			time_threshold = %d, max_alerts = %d, min_alerts = %d,
			".db_escape_key_identifier('_snmp_f1_')."= '%s',
			".db_escape_key_identifier('_snmp_f2_')."= '%s',
			".db_escape_key_identifier('_snmp_f3_')."= '%s',
			".db_escape_key_identifier('_snmp_f4_')."= '%s',
			".db_escape_key_identifier('_snmp_f5_')."= '%s',
			".db_escape_key_identifier('_snmp_f6_')."= '%s',
			".db_escape_key_identifier('_snmp_f7_')."= '%s',
			".db_escape_key_identifier('_snmp_f8_')."= '%s',
			".db_escape_key_identifier('_snmp_f9_')."= '%s',
			".db_escape_key_identifier('_snmp_f10_')." = '%s',
			".db_escape_key_identifier('_snmp_f11_')." = '%s',
			".db_escape_key_identifier('_snmp_f12_')." = '%s',
			".db_escape_key_identifier('_snmp_f13_')." = '%s',
			".db_escape_key_identifier('_snmp_f14_')." = '%s',
			".db_escape_key_identifier('_snmp_f15_')." = '%s',
			".db_escape_key_identifier('_snmp_f16_')." = '%s',
			".db_escape_key_identifier('_snmp_f17_')." = '%s',
			".db_escape_key_identifier('_snmp_f18_')." = '%s',
			".db_escape_key_identifier('_snmp_f19_')." = '%s',
			".db_escape_key_identifier('_snmp_f20_')." = '%s',
			order_1 = '%d',
			order_2 = '%d', order_3 = '%d', order_4 = '%d',
			order_5 = '%d', order_6 = '%d', order_7 = '%d',
			order_8 = '%d', order_9 = '%d', order_10 = '%d',
			order_11 = '%d', order_12 = '%d', order_13 = '%d',
			order_14 = '%d', order_15 = '%d', order_16 = '%d',
			order_17 = '%d', order_18 = '%d', order_19 = '%d',
            order_20 = '%d', trap_type = %d, single_value = '%s',
            position = '%s', disable_event = %d, id_group ='%s'
			WHERE id_as = %d",
            $priority,
            $alert_type,
            $al_field1,
            $al_field2,
            $al_field3,
            $al_field4,
            $al_field5,
            $al_field6,
            $al_field7,
            $al_field8,
            $al_field9,
            $al_field10,
            $al_field11,
            $al_field12,
            $al_field13,
            $al_field14,
            $al_field15,
            $al_field16,
            $al_field17,
            $al_field18,
            $al_field19,
            $al_field20,
            $description,
            $source_ip,
            $custom_value,
            $oid,
            $time_threshold,
            $max_alerts,
            $min_alerts,
            $custom_oid_data_1,
            $custom_oid_data_2,
            $custom_oid_data_3,
            $custom_oid_data_4,
            $custom_oid_data_5,
            $custom_oid_data_6,
            $custom_oid_data_7,
            $custom_oid_data_8,
            $custom_oid_data_9,
            $custom_oid_data_10,
            $custom_oid_data_11,
            $custom_oid_data_12,
            $custom_oid_data_13,
            $custom_oid_data_14,
            $custom_oid_data_15,
            $custom_oid_data_16,
            $custom_oid_data_17,
            $custom_oid_data_18,
            $custom_oid_data_19,
            $custom_oid_data_20,
            $order_1,
            $order_2,
            $order_3,
            $order_4,
            $order_5,
            $order_6,
            $order_7,
            $order_8,
            $order_9,
            $order_10,
            $order_11,
            $order_12,
            $order_13,
            $order_14,
            $order_15,
            $order_16,
            $order_17,
            $order_18,
            $order_19,
            $order_20,
            $trap_type,
            $single_value,
            $position,
            $disable_event,
            $group,
            $id_as
        );

        $result = db_process_sql($sql);

        if ((bool) $result === false) {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                sprintf(
                    'Fail try to update snmp alert #%s',
                    $id_as
                )
            );
            ui_print_error_message(__('There was a problem updating the alert'));
        } else {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                sprintf(
                    'Update snmp alert #%s',
                    $id_as
                )
            );
            ui_print_success_message(__('Successfully updated'));
        }
    }
}

// From variable init.
if ($update_alert || $duplicate_alert) {
    $id_as = (int) get_parameter('id_alert_snmp', -1);

    $alert = db_get_row('talert_snmp', 'id_as', $id_as);
    $id_as = $alert['id_as'];
    $id_alert = $alert['id_alert'];
    $source_ip = $alert['agent'];
    $alert_type = $alert['id_alert'];
    $description = $alert['description'];
    $oid = $alert['oid'];
    $custom_value = $alert['custom_oid'];
    $time_threshold = $alert['time_threshold'];
    $times_fired = $alert['times_fired'];
    $last_fired = $alert['last_fired'];
    $internal_counter = $alert['internal_counter'];
    $al_field1 = $alert['al_field1'];
    $al_field2 = $alert['al_field2'];
    $al_field3 = $alert['al_field3'];
    $al_field4 = $alert['al_field4'];
    $al_field5 = $alert['al_field5'];
    $al_field6 = $alert['al_field6'];
    $al_field7 = $alert['al_field7'];
    $al_field8 = $alert['al_field8'];
    $al_field9 = $alert['al_field9'];
    $al_field10 = $alert['al_field10'];
    $al_field11 = $alert['al_field11'];
    $al_field12 = $alert['al_field12'];
    $al_field13 = $alert['al_field13'];
    $al_field14 = $alert['al_field14'];
    $al_field15 = $alert['al_field15'];
    $al_field16 = $alert['al_field16'];
    $al_field17 = $alert['al_field17'];
    $al_field18 = $alert['al_field18'];
    $al_field19 = $alert['al_field19'];
    $al_field20 = $alert['al_field20'];
    $max_alerts = $alert['max_alerts'];
    $min_alerts = $alert['min_alerts'];
    $priority = $alert['priority'];
    $custom_oid_data_1 = $alert['_snmp_f1_'];
    $custom_oid_data_2 = $alert['_snmp_f2_'];
    $custom_oid_data_3 = $alert['_snmp_f3_'];
    $custom_oid_data_4 = $alert['_snmp_f4_'];
    $custom_oid_data_5 = $alert['_snmp_f5_'];
    $custom_oid_data_6 = $alert['_snmp_f6_'];
    $custom_oid_data_7 = $alert['_snmp_f7_'];
    $custom_oid_data_8 = $alert['_snmp_f8_'];
    $custom_oid_data_9 = $alert['_snmp_f9_'];
    $custom_oid_data_10 = $alert['_snmp_f10_'];
    $custom_oid_data_11 = $alert['_snmp_f11_'];
    $custom_oid_data_12 = $alert['_snmp_f12_'];
    $custom_oid_data_13 = $alert['_snmp_f13_'];
    $custom_oid_data_14 = $alert['_snmp_f14_'];
    $custom_oid_data_15 = $alert['_snmp_f15_'];
    $custom_oid_data_16 = $alert['_snmp_f16_'];
    $custom_oid_data_17 = $alert['_snmp_f17_'];
    $custom_oid_data_18 = $alert['_snmp_f18_'];
    $custom_oid_data_19 = $alert['_snmp_f19_'];
    $custom_oid_data_20 = $alert['_snmp_f20_'];
    $order_1 = $alert['order_1'];
    $order_2 = $alert['order_2'];
    $order_3 = $alert['order_3'];
    $order_4 = $alert['order_4'];
    $order_5 = $alert['order_5'];
    $order_6 = $alert['order_6'];
    $order_7 = $alert['order_7'];
    $order_8 = $alert['order_8'];
    $order_9 = $alert['order_9'];
    $order_10 = $alert['order_10'];
    $order_11 = $alert['order_11'];
    $order_12 = $alert['order_12'];
    $order_13 = $alert['order_13'];
    $order_14 = $alert['order_14'];
    $order_15 = $alert['order_15'];
    $order_16 = $alert['order_16'];
    $order_17 = $alert['order_17'];
    $order_18 = $alert['order_18'];
    $order_19 = $alert['order_19'];
    $order_20 = $alert['order_20'];
    $trap_type = $alert['trap_type'];
    $single_value = $alert['single_value'];
    $position = $alert['position'];
    $disable_event = $alert['disable_event'];
    $group = $alert['id_group'];

    if (!check_acl_restricted_all($config['id_user'], $group, 'LW')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access SNMP Alert Management'
        );
        include 'general/noaccess.php';
        return;
    }
} else if ($create_alert) {
    // Variable init.
    $id_as = -1;
    $source_ip = '';
    $alert_type = 1;
    // Event, e-mail.
    $description = '';
    $oid = '';
    $custom_value = '';
    $time_threshold = SECONDS_5MINUTES;
    $al_field1 = '';
    $al_field2 = '';
    $al_field3 = '';
    $al_field4 = '';
    $al_field5 = '';
    $al_field6 = '';
    $al_field7 = '';
    $al_field8 = '';
    $al_field9 = '';
    $al_field10 = '';
    $al_field11 = '';
    $al_field12 = '';
    $al_field13 = '';
    $al_field14 = '';
    $al_field15 = '';
    $al_field16 = '';
    $al_field17 = '';
    $al_field18 = '';
    $al_field19 = '';
    $al_field20 = '';
    $max_alerts = 1;
    $min_alerts = 0;
    $priority = 0;
    $custom_oid_data_1 = '';
    $custom_oid_data_2 = '';
    $custom_oid_data_3 = '';
    $custom_oid_data_4 = '';
    $custom_oid_data_5 = '';
    $custom_oid_data_6 = '';
    $custom_oid_data_7 = '';
    $custom_oid_data_8 = '';
    $custom_oid_data_9 = '';
    $custom_oid_data_10 = '';
    $custom_oid_data_11 = '';
    $custom_oid_data_12 = '';
    $custom_oid_data_13 = '';
    $custom_oid_data_14 = '';
    $custom_oid_data_15 = '';
    $custom_oid_data_16 = '';
    $custom_oid_data_17 = '';
    $custom_oid_data_18 = '';
    $custom_oid_data_19 = '';
    $custom_oid_data_20 = '';
    $order_1 = 1;
    $order_2 = 2;
    $order_3 = 3;
    $order_4 = 4;
    $order_5 = 5;
    $order_6 = 6;
    $order_7 = 7;
    $order_8 = 8;
    $order_9 = 9;
    $order_10 = 10;
    $order_11 = 11;
    $order_12 = 12;
    $order_13 = 13;
    $order_14 = 14;
    $order_15 = 15;
    $order_16 = 16;
    $order_17 = 17;
    $order_18 = 18;
    $order_19 = 19;
    $order_20 = 20;
    $trap_type = -1;
    $single_value = '';
    $position = 0;
    $disable_event = 0;
    $group = 0;
}

// Duplicate alert snmp.
if ($duplicate_alert) {
    $values_duplicate = $alert;
    if (empty($values_duplicate) === false) {
        unset($values_duplicate['id_as']);
        $result = db_process_sql_insert('talert_snmp', $values_duplicate);

        if (!$result) {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                sprintf(
                    'Fail try to duplicate snmp alert #%s',
                    $id_as
                )
            );
            ui_print_error_message(__('There was a problem duplicating the alert'));
        } else {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                sprintf(
                    'Duplicate snmp alert #%s',
                    $id_as
                )
            );
            ui_print_success_message(__('Successfully Duplicate'));
        }
    } else {
        db_pandora_audit(
            AUDIT_LOG_SNMP_MANAGEMENT,
            sprintf(
                'Fail try to duplicate snmp alert #%s',
                $id_as
            )
        );
        ui_print_error_message(__('There was a problem duplicating the alert'));
    }
}

if ($delete_alert) {
    // Delete alert.
    $alert_delete = (int) get_parameter_get('delete_alert', 0);

    $result = db_process_sql_delete(
        'talert_snmp',
        ['id_as' => $alert_delete]
    );

    if ($result === false) {
        db_pandora_audit(
            AUDIT_LOG_SNMP_MANAGEMENT,
            sprintf(
                'Fail try to delete snmp alert #%s',
                $alert_delete
            )
        );
        ui_print_error_message(__('There was a problem deleting the alert'));
    } else {
        db_pandora_audit(
            AUDIT_LOG_SNMP_MANAGEMENT,
            sprintf(
                'Delete snmp alert #%s',
                $alert_delete
            )
        );
        ui_print_success_message(__('Successfully deleted'));
    }
}

if ($multiple_delete) {
    $delete_ids = get_parameter('delete_ids', []);

    $total = count($delete_ids);

    $count = 0;
    foreach ($delete_ids as $alert_delete) {
        $result = db_process_sql_delete(
            'talert_snmp',
            ['id_as' => $alert_delete]
        );

        if ($result !== false) {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                sprintf(
                    'Delete snmp alert #%s',
                    $alert_delete
                )
            );
            $count++;
        } else {
            db_pandora_audit(
                AUDIT_LOG_SNMP_MANAGEMENT,
                sprintf(
                    'Fail try to delete snmp alert #%s',
                    $alert_delete
                )
            );
        }
    }

    if ($count === $total) {
        ui_print_success_message(
            __('Successfully deleted alerts (%s / %s)', $count, $total)
        );
    } else {
        ui_print_error_message(
            __('Unsuccessfully deleted alerts (%s / %s)', $count, $total)
        );
    }
}

$user_groups = users_get_groups($config['id_user'], 'AR', true);
$str_user_groups = '';
$i = 0;
foreach ($user_groups as $id => $name) {
    if ($i === 0) {
        $str_user_groups .= $id;
    } else {
        $str_user_groups .= ','.$id;
    }

    $i++;
}

// Alert form.
if ($create_alert || $update_alert) {
    // The update_alert means the form should be displayed. If update_alert > 1 then an existing alert is updated.
    echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert" 
    class="max_floating_element_size">';

    html_print_input_hidden('id_alert_snmp', $id_as);

    if ($create_alert) {
        html_print_input_hidden('save_alert', 1);
    }

    if ($update_alert) {
        html_print_input_hidden('modify_alert', 1);
    }

    // SNMP alert filters.
    echo '<table cellpadding="0" cellspacing="0" width="100%" class="databox filter filter-table-adv">';

    // 1.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Description'),
        html_print_textarea(
            'description',
            2,
            2,
            $description,
            'class="w100p"',
            true
        )
    );
    echo '</td>';

    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Custom Value/OID'),
        html_print_textarea(
            'custom_value',
            2,
            2,
            $custom_value,
            'class="w100p" required="required"',
            true
        )
    );
    echo '</td>';
    echo '</tr>';

    // 2.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Enterprise String').ui_print_help_tip(__('Matches substrings. End the string with $ for exact matches.'), true),
        html_print_input_text(
            'oid',
            $oid,
            '',
            50,
            255,
            true,
            false,
            true
        )
    );
    echo '</td>';

    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('SNMP Agent').' (IP)',
        html_print_input_text(
            'source_ip',
            $source_ip,
            '',
            20,
            255,
            true,
            false,
            true
        )
    );
    echo '</td>';
    echo '</tr>';

    $return_all_group = false;

    if (users_can_manage_group_all('LW') === true) {
        $return_all_group = true;
    }

    // 3.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Group'),
        html_print_select_groups(
            $config['id_user'],
            'AR',
            $return_all_group,
            'group',
            $group,
            '',
            '',
            0,
            true,
            false,
            false,
            '',
            false,
            false,
            false,
            false,
            'id_grupo',
            false
        )
    );
    echo '</td>';

    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Trap type'),
        html_print_select(
            $trap_types,
            'trap_type',
            $trap_type,
            '',
            '',
            '',
            true,
            false,
            false,
            'w100p'
        )
    );
    echo '</td>';
    echo '</tr>';

    // 4.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Single value'),
        html_print_input_text(
            'single_value',
            $single_value,
            '',
            20,
            255,
            true
        )
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo '</td>';
    echo '</tr>';

    // #1 #2.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_1',
            $order_1,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_1',
            $custom_oid_data_1,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_2',
            $order_2,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_2',
            $custom_oid_data_2,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #3 #4.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_3',
            $order_3,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_3',
            $custom_oid_data_3,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_4',
            $order_4,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_4',
            $custom_oid_data_4,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #5 #6.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_5',
            $order_5,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_5',
            $custom_oid_data_5,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_6',
            $order_6,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_6',
            $custom_oid_data_6,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #7 #8.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_7',
            $order_7,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_7',
            $custom_oid_data_7,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_8',
            $order_8,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_8',
            $custom_oid_data_8,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #9 #10.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_9',
            $order_9,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_9',
            $custom_oid_data_9,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_10',
            $order_10,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_10',
            $custom_oid_data_10,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #11 #12.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_11',
            $order_11,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_11',
            $custom_oid_data_11,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_12',
            $order_12,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_12',
            $custom_oid_data_12,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #13 #14.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_13',
            $order_13,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_13',
            $custom_oid_data_13,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_14',
            $order_14,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_14',
            $custom_oid_data_14,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #15 #16.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_15',
            $order_15,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_15',
            $custom_oid_data_15,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_16',
            $order_16,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_16',
            $custom_oid_data_16,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #17 #18.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_17',
            $order_17,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_17',
            $custom_oid_data_17,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_18',
            $order_18,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_18',
            $custom_oid_data_18,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // #19 #20.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_19',
            $order_19,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_19',
            $custom_oid_data_19,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Variable bindings/Data'),
        '<div class="flex-inline">#'.html_print_input_text(
            'order_20',
            $order_20,
            '',
            4,
            255,
            true,
            false,
            false,
            '',
            'w10p'
        ).html_print_input_text(
            'custom_oid_data_20',
            $custom_oid_data_20,
            '',
            60,
            255,
            true,
            false,
            false,
            '',
            'w88p'
        ).'</div>'
    );
    echo '</td>';
    echo '</tr>';

    // Alert fields.
    $al = [
        'al_field1'  => $al_field1,
        'al_field2'  => $al_field2,
        'al_field3'  => $al_field3,
        'al_field4'  => $al_field4,
        'al_field5'  => $al_field5,
        'al_field6'  => $al_field6,
        'al_field7'  => $al_field7,
        'al_field8'  => $al_field8,
        'al_field9'  => $al_field9,
        'al_field10' => $al_field10,
        'al_field11' => $al_field11,
        'al_field12' => $al_field12,
        'al_field13' => $al_field13,
        'al_field14' => $al_field14,
        'al_field15' => $al_field15,
        'al_field16' => $al_field16,
        'al_field17' => $al_field17,
        'al_field18' => $al_field18,
        'al_field19' => $al_field19,
        'al_field20' => $al_field20,
    ];

    // Hidden div with help hint to fill with javascript.
    html_print_div(['id' => 'help_snmp_alert_hint', 'content' => ui_print_help_icon('snmp_alert_field1', true), 'hidden' => true]);

    for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
        echo '<tr id="table_macros-field'.$i.'"><td class="datos" valign="top">'.html_print_image('images/spinner.gif', true);
        echo '<td class="datos">'.html_print_image('images/spinner.gif', true);
        html_print_input_hidden('field'.$i.'_value', isset($al['al_field'.$i]) ? $al['al_field'.$i] : '');
        echo '</td></tr>';
    }

    // Max, Min.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Min. number of alerts'),
        html_print_input_text(
            'min_alerts',
            $min_alerts,
            '',
            3,
            255,
            true,
            false,
            false,
            '',
            'w100p'
        )
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Max. number of alerts'),
        html_print_input_text(
            'max_alerts',
            $max_alerts,
            '',
            3,
            255,
            true,
            false,
            false,
            '',
            'w100p'
        )
    );
    echo '</td>';
    echo '</tr>';

    $fields = [];
    $fields[$time_threshold] = human_time_description_raw($time_threshold);
    $fields[SECONDS_5MINUTES] = human_time_description_raw(SECONDS_5MINUTES);
    $fields[SECONDS_10MINUTES] = human_time_description_raw(SECONDS_10MINUTES);
    $fields[SECONDS_15MINUTES] = human_time_description_raw(SECONDS_15MINUTES);
    $fields[SECONDS_30MINUTES] = human_time_description_raw(SECONDS_30MINUTES);
    $fields[SECONDS_1HOUR] = human_time_description_raw(SECONDS_1HOUR);
    $fields[SECONDS_2HOUR] = human_time_description_raw(SECONDS_2HOUR);
    $fields[SECONDS_5HOUR] = human_time_description_raw(SECONDS_5HOUR);
    $fields[SECONDS_12HOURS] = human_time_description_raw(SECONDS_12HOURS);
    $fields[SECONDS_1DAY] = human_time_description_raw(SECONDS_1DAY);
    $fields[SECONDS_1WEEK] = human_time_description_raw(SECONDS_1WEEK);
    $fields[-1] = __('Other value');

    // Time Threshold, Priority.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Time threshold'),
        html_print_select(
            $fields,
            'time_threshold',
            $time_threshold,
            '',
            '',
            '0',
            true,
            false,
            false,
            '" class="mrgn_right_60px'
        ).'<div id="div-time_other" class="invisible">'.html_print_input_text(
            'time_other',
            0,
            '',
            6,
            255,
            true
        ).' '.__('seconds').'</div>'
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Priority'),
        html_print_select(
            get_priorities(),
            'priority',
            $priority,
            '',
            '',
            '0',
            true,
            false,
            false
        )
    );
    echo '</td>';
    echo '</tr>';

    // Alert type (e-mail, event etc.), Position.
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Alert action'),
        html_print_select_from_sql(
            'SELECT id, name
            FROM talert_actions
            ORDER BY name',
            'alert_type',
            $alert_type,
            '',
            '',
            0,
            true,
            false,
            false
        )
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Position'),
        html_print_input_text(
            'position',
            $position,
            '',
            3,
            255,
            true,
            false,
            false,
            '',
            'w100p'
        )
    );
    echo '</td>';
    echo '</tr>';

    // Disable event, .
    echo '<tr>';
    echo '<td class="w50p">';
    echo html_print_label_input_block(
        __('Disable event'),
        html_print_checkbox(
            'disable_event',
            1,
            $disable_event,
            true
        )
    );
    echo '</td>';
    echo '<td class="w50p">';
    echo '</td>';
    echo '</tr>';
    echo '</table>';


    $back_button = html_print_button(
        __('Back'),
        'button_back',
        false,
        '',
        [
            'class' => 'secondary',
            'icon'  => 'back',
        ],
        true
    );
    if ($id_as > 0) {
        $submit_button = html_print_submit_button(
            __('Update'),
            'submit',
            false,
            [
                'class' => '',
                'icon'  => 'wand',
            ],
            true
        );
    } else {
        $submit_button = html_print_submit_button(
            __('Create'),
            'submit',
            false,
            [
                'class' => '',
                'icon'  => 'wand',
            ],
            true
        );
    }

    html_print_action_buttons($submit_button.$back_button);
    echo '</form>';
} else {
    include_once 'include/functions_alerts.php';

    $free_search = (string) get_parameter('free_search', '');
    $trap_type_filter = (int) get_parameter('trap_type_filter', SNMP_TRAP_TYPE_NONE);
    $priority_filter = (int) get_parameter('priority_filter', -1);
    $filter_param = (bool) get_parameter('filter', false);
    $offset = (int) get_parameter('offset');

    $table_filter = new stdClass();
    $table_filter->width = '100%';
    $table_filter->class = 'databox filters filter-table-adv';
    $table_filter->data = [];
    $table_filter->size[0] = '33%';
    $table_filter->size[1] = '33%';
    $table_filter->size[2] = '33%';

    $table_filter->data[0][0] = html_print_label_input_block(
        __('Free search').ui_print_help_tip(
            __('Search by these fields description, OID, Custom Value, SNMP Agent (IP), Single value, each Variable bindings/Datas.'),
            true
        ),
        html_print_input_text(
            'free_search',
            $free_search,
            '',
            30,
            100,
            true
        )
    );

    $table_filter->data[0][1] = html_print_label_input_block(
        __('Trap type'),
        html_print_select(
            $trap_types,
            'trap_type_filter',
            $trap_type_filter,
            '',
            '',
            '',
            true,
            false,
            false,
            'w100p',
            false,
            'width: 100%;'
        )
    );

    $table_filter->data[0][2] = html_print_label_input_block(
        __('Priority'),
        html_print_select(
            get_priorities(),
            'priority_filter',
            $priority_filter,
            '',
            __('None'),
            '-1',
            true,
            false,
            false,
            'w100p',
            false,
            'width: 100%;'
        )
    );

    $form_filter = '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert">';
    $form_filter .= html_print_input_hidden('filter', 1, true);
    $form_filter .= html_print_table($table_filter, true);
    $form_filter .= '<div class="float-right mrgn_right_25px">';
    $form_filter .= html_print_submit_button(
        __('Filter'),
        'filter_button',
        false,
        [
            'class' => 'mini',
            'icon'  => 'search',
        ],
        true
    );
    $form_filter .= '</div>';
    $form_filter .= '</form>';

    ui_toggle(
        $form_filter,
        '<span class="subsection_header_title">'.__('Alert SNMP control filter').'</span>',
        __('Toggle filter(s)'),
        'filter',
        true,
        false,
        '',
        'white-box-content no_border',
        'filter-datatable-main box-flat white_table_graph fixed_filter_bar'
    );

    $filter = [];
    $offset = (int) get_parameter('offset');
    $limit = (int) $config['block_size'];
    if ($filter_param) {
        // Move the first page.
        $offset = 0;

        $url_pagination = 'index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&free_search='.$free_search.'&trap_type_filter='.$trap_type_filter.'&priority_filter='.$priority_filter;
    } else {
        $url_pagination = 'index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&free_search='.$free_search.'&trap_type_filter='.$trap_type_filter.'&priority_filter='.$priority_filter.'&offset='.$offset;
    }


    $where_sql = '';
    if (empty($free_search) === false) {
        if ($trap_type_filter != SNMP_TRAP_TYPE_NONE) {
            $where_sql .= ' AND `trap_type` = '.$trap_type_filter;
        }

        if ($priority_filter != -1) {
            $where_sql .= ' AND `priority` = '.$priority_filter;
        }

        $where_sql .= " AND (`single_value` LIKE '%".$free_search."%'
            OR `_snmp_f10_` LIKE '%".$free_search."%'
            OR `_snmp_f9_` LIKE '%".$free_search."%'
            OR `_snmp_f8_` LIKE '%".$free_search."%'
            OR `_snmp_f7_` LIKE '%".$free_search."%'
            OR `_snmp_f6_` LIKE '%".$free_search."%'
            OR `_snmp_f5_` LIKE '%".$free_search."%'
            OR `_snmp_f4_` LIKE '%".$free_search."%'
            OR `_snmp_f3_` LIKE '%".$free_search."%'
            OR `_snmp_f2_` LIKE '%".$free_search."%'
            OR `_snmp_f1_` LIKE '%".$free_search."%'
            OR `oid` LIKE '%".$free_search."%'
            OR `custom_oid` LIKE '%".$free_search."%'
            OR `agent` LIKE '%".$free_search."%'
            OR `description` LIKE '%".$free_search."%')";
    }

    $count = (int) db_get_value_sql(
        'SELECT COUNT(*)
		FROM talert_snmp WHERE id_group IN ('.$str_user_groups.') '.$where_sql
    );

    $result = [];

    // Overview.
    if ($count === 0) {
        $result = [];
        ui_print_info_message(['no_close' => true, 'message' => __('There are no SNMP alerts') ]);
    } else {
        $where_sql .= ' LIMIT '.$limit.' OFFSET '.$offset;
        $result = db_get_all_rows_sql(
            'SELECT *
            FROM talert_snmp
            WHERE id_group IN ('.$str_user_groups.') '.$where_sql
        );
    }

    $table = new stdClass();
    $table->data = [];
    $table->head = [];
    $table->size = [];
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '100%';
    $table->class = 'info_table';
    $table->align = [];

    $table->head[0] = '<span title="'.__('Position').'">'.__('P.').'</span>';
    $table->align[0] = 'left';

    $table->head[1] = __('Alert action');

    $table->head[2] = __('SNMP Agent');
    $table->size[2] = '90px';
    $table->align[2] = 'left';

    $table->head[3] = __('Enterprise String');
    $table->align[3] = 'left';

    $table->head[4] = __('Custom Value/Enterprise String');
    $table->align[4] = 'left';

    $table->head[5] = __('Description');

    $table->head[6] = '<span title="'.__('Times fired').'">'.__('TF.').'</span>';
    $table->size[6] = '50px';
    $table->align[6] = 'left';

    $table->head[7] = __('Last fired');
    $table->align[7] = 'left';

    $table->head[8] = __('Action');
    $table->size[8] = '120px';
    $table->align[8] = 'left';

    $table->head[9] = html_print_checkbox('all_delete_box', '1', false, true);
    $table->size[9] = '10px';
    $table->align[9] = 'left';

    foreach ($result as $row) {
        $data = [];
        $data[0] = $row['position'];

        $url = 'index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&id_alert_snmp='.$row['id_as'].'&update_alert=1';
        $data[1] = '<table>';
        $data[1] .= '<tr>';

        if (check_acl_restricted_all($config['id_user'], $row['id_group'], 'LW')) {
            $data[1] .= '<a href="'.$url.'">'.alerts_get_alert_action_name($row['id_alert']).'</a>';
        } else {
            $data[1] .= alerts_get_alert_action_name($row['id_alert']);
        }

        $other_actions = db_get_all_rows_filter('talert_snmp_action', ['id_alert_snmp' => $row['id_as']]);
        $data[1] .= '</tr>';


        if ($other_actions != false) {
            foreach ($other_actions as $action) {
                $data[1] .= '<tr>';
                $data[1] .= '<td>'.alerts_get_alert_action_name($action['alert_type']).'</td>';
                $data[1] .= '<td> <a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&delete_action=1&action_id='.$action['id'].'" onClick="javascript:return confirm(\''.__('Are you sure?').'\')">'.html_print_image(
                    'images/delete.svg',
                    true,
                    [
                        'border' => '0',
                        'alt'    => __('Delete'),
                        'class'  => 'invert_filter main_menu_icon',
                    ]
                ).'</a> </td>';
                $data[1] .= '</tr>';
            }
        }

        $data[1] .= '</table>';
        $data[2] = $row['agent'];
        $data[3] = $row['oid'];
        $data[4] = $row['custom_oid'];
        $data[5] = $row['description'];
        $data[6] = $row['times_fired'];

        if (($row['last_fired'] !== '1970-01-01 00:00:00') && ($row['last_fired'] !== '01-01-1970 00:00:00')) {
            $data[7] = ui_print_timestamp($row['last_fired'], true);
        } else {
            $data[7] = __('Never');
        }

        $data[8] = '';
        $data[9] = '';

        if ((bool) check_acl_restricted_all($config['id_user'], $row['id_group'], 'LW') === true) {
                $data[8] = html_print_anchor(
                    [
                        'href'    => sprintf(
                            'index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&duplicate_alert=1&id_alert_snmp=%s',
                            $row['id_as']
                        ),
                        'content' => html_print_image(
                            'images/copy.svg',
                            true,
                            [
                                'alt'   => __('Duplicate'),
                                'title' => __('Duplicate'),
                                'class' => 'main_menu_icon',
                            ]
                        ),
                    ],
                    true
                );
                $data[8] .= html_print_anchor(
                    [
                        'href'    => sprintf(
                            'index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&update_alert=1&id_alert_snmp=%s',
                            $row['id_as']
                        ),
                        'content' => html_print_image(
                            'images/edit.svg',
                            true,
                            [
                                'alt'    => __('Update'),
                                'border' => 0,
                                'class'  => 'main_menu_icon',
                                'title'  => __('Edit'),
                            ]
                        ),
                    ],
                    true
                );
                $data[8] .= html_print_anchor(
                    [
                        'href'    => sprintf(
                            'javascript:show_add_action_snmp(\'%s\');"',
                            $row['id_as']
                        ),
                        'content' => html_print_image(
                            'images/add.png',
                            true,
                            [
                                'title' => __('Add action'),
                            ]
                        ),
                    ],
                    true
                );
                $data[8] .= html_print_anchor(
                    [
                        'href'    => 'javascript: ',
                        'content' => html_print_image(
                            'images/delete.svg',
                            true,
                            [
                                'title' => __('Delete action'),
                                'class' => 'main_menu_icon',
                            ]
                        ),
                        'onClick' => 'delete_snmp_alert('.$row['id_as'].')',
                    ],
                    true
                );

                $data[9] = html_print_checkbox_extended(
                    'delete_ids[]',
                    $row['id_as'],
                    false,
                    false,
                    false,
                    'class="chk_delete"',
                    true
                );
        }

        $idx = count($table->data);
        // The current index of the table is 1 less than the count of table data so we count before adding to table->data.
        array_push($table->data, $data);

        $table->rowclass[$idx] = get_priority_class($row['priority']);
    }

    // DIALOG ADD MORE ACTIONS.
    echo '<div id="add_action_snmp-div" class="invisible">';

        echo '<form id="add_action_form" method="post">';
            echo '<table class="w100p">';
                echo '<tr>';
                    echo '<td class="datos bolder w20p">';
                        echo __('ID Alert SNMP');
                    echo '</td>';
                    echo '<td class="datos">';
                        html_print_input_text('id_alert_snmp', '', '', 3, 10, false, true);
                    echo '</td>';
                echo '</tr>';
                echo '<tr class="datos">';
                    echo '<td class="datos bolder w20p">';
                        echo __('Action');
                    echo '</td>';
                    echo '<td class="datos">';

                    html_print_select_from_sql(
                        'SELECT id, name
                        FROM talert_actions
                        ORDER BY name',
                        'alert_type',
                        ($alert_type ?? ''),
                        '',
                        '',
                        0,
                        false,
                        false,
                        false
                    );

                    echo '</td>';
                echo '</tr>';

                $al = [
                    'al_field1'  => ($al_field1 ?? ''),
                    'al_field2'  => ($al_field2 ?? ''),
                    'al_field3'  => ($al_field3 ?? ''),
                    'al_field4'  => ($al_field4 ?? ''),
                    'al_field5'  => ($al_field5 ?? ''),
                    'al_field6'  => ($al_field6 ?? ''),
                    'al_field7'  => ($al_field7 ?? ''),
                    'al_field8'  => ($al_field8 ?? ''),
                    'al_field9'  => ($al_field9 ?? ''),
                    'al_field10' => ($al_field10 ?? ''),
                    'al_field11' => ($al_field11 ?? ''),
                    'al_field12' => ($al_field12 ?? ''),
                    'al_field13' => ($al_field13 ?? ''),
                    'al_field14' => ($al_field14 ?? ''),
                    'al_field15' => ($al_field15 ?? ''),
                    'al_field16' => ($al_field16 ?? ''),
                    'al_field17' => ($al_field17 ?? ''),
                    'al_field18' => ($al_field18 ?? ''),
                    'al_field19' => ($al_field19 ?? ''),
                    'al_field20' => ($al_field20 ?? ''),
                ];

                for ($i = 1; $i <= $config['max_macro_fields']; $i++) {
                    echo '<tr id="table_macros-field'.$i.'"><td class="datos" valign="top">'.html_print_image('images/spinner.gif', true);
                    echo '<td class="datos">'.html_print_image(
                        'images/spinner.gif',
                        true
                    );
                    html_print_input_hidden('field'.$i.'_value', isset($al['al_field'.$i]) ? $al['al_field'.$i] : '');
                    echo '</td>';
                    echo '</tr>';
                }

                html_print_div(['id' => 'help_snmp_alert_hint', 'content' => ui_print_help_icon('snmp_alert_field1', true), 'hidden' => true]);

                echo '</table>';
                html_print_input_hidden('add_alert', 1);
                echo html_print_submit_button(
                    __('Add'),
                    'addbutton',
                    false,
                    [
                        'class' => 'mini',
                        'icon'  => 'wand',
                        'style' => 'float:right',
                    ],
                    true
                );
                echo '</form>';
                echo '</div>';
                // END DIALOG ADD MORE ACTIONS.
                $pagination = '';
                $deleteButton = '';
    if (empty($table->data) === false) {
        echo '<form id="delete_selected_form" name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert">';
        html_print_table($table);

        $pagination = ui_pagination($count, $url_pagination, 0, 0, true, 'offset', false);

        echo '<div class="right mrgn_lft_10px;">';
        html_print_input_hidden('multiple_delete', 1);
        $deleteButton = html_print_button(
            __('Delete selected'),
            'delete_button',
            false,
            'delete_selected_snmp_alerts()',
            [
                'class' => 'secondary',
                'icon'  => 'delete',
            ],
            true
        );
        echo '</div>';
        echo '</form>';
    }

    $legend = '<table id="legend_snmp_alerts"class="w100p"><td><div class="snmp_view_div w100p legend_white">';
    $legend .= '<div class="display-flex"><div class="flex-50">';
    $priorities = get_priorities();
    $half = (count($priorities) / 2);
    $count = 0;
    foreach ($priorities as $num => $name) {
        if ($count == $half) {
            $legend .= '</div><div class="mrgn_lft_5px flex-50">';
        }

        $legend .= '<span class="'.get_priority_class($num).'">'.$name.'</span>';
        $legend .= '<br />';
        $count++;
    }

    $legend .= '</div></div></div></td></tr></table>';

    ui_toggle($legend, __('Legend'));

    unset($table);

    echo '<div class="right">';
    echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert">';
    html_print_input_hidden('create_alert', 1);
    $submitButton = html_print_submit_button(
        __('Create'),
        'alert',
        false,
        ['icon' => 'wand'],
        true
    );
    html_print_action_buttons($submitButton.$deleteButton, ['right_content' => $pagination]);
    echo '</form></div>';
}

ui_require_javascript_file('pandora', 'include/javascript/', true);
ui_require_javascript_file('tinymce', 'vendor/tinymce/tinymce/');
ui_require_javascript_file('alert');
?>
<script language="javascript" type="text/javascript">

function time_changed () {
    var time = this.value;
    if (time == -1) {
        $('#time_threshold').fadeOut ('normal', function () {
            $('#div-time_other').fadeIn ('normal');
        });
    }
}

function delete_snmp_alert(id) {
    confirmDialog({
    title: "<?php echo __('Confirmation'); ?>",
    message: "<?php echo __('Do you want delete this alert?'); ?>",
    cancel: "<?php echo __('Cancel'); ?>",
    ok: "<?php echo __('Ok'); ?>",
    onAccept: function() {
      window.location.href = 'index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert&delete_alert='+id;
    },
    onDeny: function() {
      return false;
    }
  });
}

function delete_selected_snmp_alerts() {
    confirmDialog({
    title: "<?php echo __('Confirmation'); ?>",
    message: "<?php echo __('Do you want delete the selected alerts?'); ?>",
    cancel: "<?php echo __('Cancel'); ?>",
    ok: "<?php echo __('Ok'); ?>",
    onAccept: function() {
      $('#delete_selected_form').submit();
    },
    onDeny: function() {
      return false;
    }
  });
}

$(document).ready (function () {
    $('#time_threshold').change (time_changed);
    $("input[name=all_delete_box]").change (function() {
        if ($(this).is(":checked")) {
            $("input[name='delete_ids[]']").check();
        }
        else {
            $("input[name='delete_ids[]']").uncheck();
        }
    });

    $("#alert_type").change (function () {
        values = Array ();
        values.push ({
            name: "page",
            value: "godmode/alerts/alert_commands"
        });
        values.push ({
            name: "get_alert_command",
            value: "1"
        });
        values.push ({
            name: "id_action",
            value: this.value
        });
        values.push ({
            name: "get_recovery_fields",
            value: "0"
        });
        values.push({
            name: "content_type",
            value: "<?php echo ($al_field4 ?? ''); ?>"
        })

        jQuery.post (<?php echo "'".ui_get_full_url('ajax.php', false, false, false)."'"; ?>,
            values,
            function (data, status) {
                var max_fields = parseInt('<?php echo $config['max_macro_fields']; ?>');
                original_command = js_html_entity_decode (data["command"]);
                command_description = js_html_entity_decode (data["description"]);
                for (i = 1; i <= max_fields; i++) {
                    var old_value = '';
                    // Only keep the value if is provided from hidden (first time).
                    var id_field = $("[name=field" + i + "_value]").attr('id');

                    if (id_field == "hidden-field" + i + "_value") {
                        old_value = $("[name=field" + i + "_value]").val();
                    }

                    // If the row is empty, hide de row
                    if (data["fields_rows"][i] == '') {
                        $('#table_macros-field' + i).hide();
                    }
                    else {
                        $('#table_macros-field' + i).replaceWith(data["fields_rows"][i]);

                        // The row provided has a predefined class. We delete it.
                        $('#table_macros-field' + i)
                            .removeAttr('class');
                        if(old_value){
                            $("[name=field" + i + "_value]").val(old_value).trigger('change');
                        }
                        $('#table_macros-field').show();
                    }

                    if($("#alert_type option:selected").text() === "Create Pandora ITSM ticket") {
                        // At start hide all rows and inputs corresponding to custom fields, regardless of what its type is.
                        if (i>=8) {
                            $('[name=field'+i+'_value\\[\\]').hide();
                            $('[name=field'+i+'_recovery_value\\[\\]').hide();
                            $('#table_macros-field'+i).hide();
                            $('[name=field'+i+'_value_container').hide();
                            $('[name=field'+i+'_recovery_value_container').hide();
                        }

                        if ($('#field5_value').val() !== '') {
                            ajax_get_integria_custom_fields($('#field5_value').val(), [], [], max_fields);
                            $('#field5_value').trigger('change');
                        }

                        $('#field5_value').on('change', function() {
                            ajax_get_integria_custom_fields($(this).val(), [], [], max_fields);
                        });
                    }
                }
            },
            "json"
        );
    });

    // Charge the fields of the action.
    $("#alert_type").trigger('change');

    $("#submit-delete_button").click (function () {
        confirmation = confirm("<?php echo __('Are you sure?'); ?>");
        if (!confirmation) {
            return;
        }
    });

    defineTinyMCE('textarea.tiny-mce-editor');

    $('#button-button_back').on('click', function(){
        window.location = '<?php echo ui_get_full_url('index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_alert'); ?>';
    });
});

function show_add_action_snmp(id_alert_snmp) {
    $("#add_action_snmp-div")
        .hide()
        .dialog ({
            resizable: true,
            draggable: true,
            title: '<?php echo __('Add action '); ?>',
            modal: true,
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 600,
            height: 600,
            autoOpen: true,
            open: function() {
                if (
                $.ui &&
                $.ui.dialog &&
                $.ui.dialog.prototype._allowInteraction
                ) {
                var ui_dialog_interaction =
                    $.ui.dialog.prototype._allowInteraction;
                $.ui.dialog.prototype._allowInteraction = function(e) {
                    if ($(e.target).closest(".select2-dropdown").length)
                    return true;
                    return ui_dialog_interaction.apply(this, arguments);
                };
                }
            },
            _allowInteraction: function(event) {
                return !!$(event.target).is(".select2-input") || this._super(event);
            }

        })
        .show ();
        $("#text-id_alert_snmp").val(id_alert_snmp);
}
</script>
