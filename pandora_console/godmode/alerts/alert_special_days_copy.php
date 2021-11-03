<?php
/**
 * Special days.
 *
 * @category   Alerts
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

// Load global vars.
global $config;

require_once 'include/functions_alerts.php';
require_once 'include/ics-parser/class.iCalReader.php';

check_login();

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

if (is_ajax() === true) {
    $get_alert_command = (bool) get_parameter('get_alert_command');
    if ($get_alert_command === true) {
        $id = (int) get_parameter('id');
        $command = alerts_get_alert_command($id);
        echo json_encode($command);
    }

    return;
}

// Header.
ui_print_page_header(
    __('Alerts').' &raquo; '.__('Special days list'),
    'images/gm_alerts.png',
    false,
    'alert_special_days',
    true
);

$update_special_day = (bool) get_parameter('update_special_day');
$create_special_day = (bool) get_parameter('create_special_day');
$delete_special_day = (bool) get_parameter('delete_special_day');
$upload_ical = (bool) get_parameter('upload_ical', 0);
$display_range = (int) get_parameter('display_range');

$url = 'index.php?sec=galertas&sec2=godmode/alerts/alert_special_days';
$url_alert = 'index.php?sec=galertas&sec2=';
$url_alert .= 'godmode/alerts/configure_alert_special_days';

if ($upload_ical === true) {
    $same_day = (string) get_parameter('same_day');
    $overwrite = (bool) get_parameter('overwrite', 0);
    $values = [];
    $values['id_group'] = (string) get_parameter('id_group');
    $values['same_day'] = $same_day;

    $error = $_FILES['ical_file']['error'];
    $extension = substr($_FILES['ical_file']['name'], -3);

    if ($error == 0 && strcasecmp($extension, 'ics') == 0) {
        $skipped_dates = '';
        $this_month = date('Ym');
        $ical = new ICal($_FILES['ical_file']['tmp_name']);
        $events = $ical->events();
        foreach ($events as $event) {
            $event_date = substr($event['DTSTART'], 0, 8);
            $event_month = substr($event['DTSTART'], 0, 6);
            if ($event_month >= $this_month) {
                $values['description'] = @$event['SUMMARY'];
                $values['date'] = $event_date;
                $date = date('Y-m-d', strtotime($event_date));
                $date_check = '';
                $filter['id_group'] = $values['id_group'];
                $filter['date'] = $date;
                $date_check = db_get_value_filter(
                    'date',
                    'talert_special_days',
                    $filter
                );
                if ($date_check == $date) {
                    if ($overwrite) {
                        $id_special_day = db_get_value_filter(
                            'id',
                            'talert_special_days',
                            $filter
                        );
                        alerts_update_alert_special_day(
                            $id_special_day,
                            $values
                        );
                    } else {
                        if ($skipped_dates == '') {
                            $skipped_dates = __('Skipped dates: ');
                        }

                        $skipped_dates .= $date.' ';
                    }
                } else {
                    alerts_create_alert_special_day($date, $same_day, $values);
                }
            }
        }

        $result = true;
    } else {
        $result = false;
    }

    if ($result === true) {
        db_pandora_audit(
            'Special days list',
            'Upload iCalendar '.$_FILES['ical_file']['name']
        );
    }

    ui_print_result_message(
        $result,
        __('Success to upload iCalendar').'<br />'.$skipped_dates,
        __('Fail to upload iCalendar')
    );
}

if ($create_special_day === true) {
    $date = (string) get_parameter('date');
    $same_day = (string) get_parameter('same_day');
    $values = [];
    $values['id_group'] = (string) get_parameter('id_group');
    $values['description'] = io_safe_input(
        strip_tags(io_safe_output((string) get_parameter('description')))
    );

    $aviable_description = true;
    if (preg_match('/script/i', $values['description'])) {
        $aviable_description = false;
    }

    $array_date = explode('-', $date);

    $year  = $array_date[0];
    $month = $array_date[1];
    $day   = $array_date[2];

    if ($year == '*') {
        $year = '0001';
        $date = $year.'-'.$month.'-'.$day;
    }

    if (!checkdate($month, $day, $year)) {
        $result = '';
    } else {
        $filter['id_group'] = $values['id_group'];
        $filter['same_day'] = $same_day;
        $filter['date'] = $date;
        $date_check = db_get_value_filter(
            'date',
            'talert_special_days',
            $filter
        );

        if ($date_check == $date) {
            $result = '';
            $messageAction = __('Could not be created, it already exists');
        } else {
            if ($aviable_description === true) {
                $result = alerts_create_alert_special_day(
                    $date,
                    $same_day,
                    $values
                );
                $info = '{"Date":"'.$date;
                $info .= '","Same day of the week":"'.$same_day;
                $info .= '","Description":"'.$values['description'].'"}';
            } else {
                $result = false;
            }
        }
    }

    if ($result) {
        db_pandora_audit(
            'Command management',
            'Create special day '.$result,
            false,
            false,
            $info
        );
    } else {
        db_pandora_audit(
            'Command management',
            'Fail try to create special day',
            false,
            false
        );
    }

    // Show errors.
    if (isset($messageAction) === false) {
        $messageAction = __('Could not be created');
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully created'),
        $messageAction
    );
}

if ($update_special_day === true) {
    $id = (int) get_parameter('id');
    $alert = alerts_get_alert_special_day($id);
    $date = (string) get_parameter('date');
    $date_orig = (string) get_parameter('date_orig');
    $same_day = (string) get_parameter('same_day');
    $description = io_safe_input(strip_tags(io_safe_output((string) get_parameter('description'))));
    $id_group = (string) get_parameter('id_group');
    $id_group_orig = (string) get_parameter('id_group_orig');

    $aviable_description = true;
    if (preg_match('/script/i', $description)) {
        $aviable_description = false;
    }

    $array_date = explode('-', $date);

    $year  = $array_date[0];
    $month = $array_date[1];
    $day   = $array_date[2];

    if ($year == '*') {
        // '0001' means every year.
        $year = '0001';
        $date = $year.'-'.$month.'-'.$day;
    }

    $values = [];
    $values['date'] = $date;
    $values['id_group'] = $id_group;
    $values['same_day'] = $same_day;
    $values['description'] = $description;

    if (!checkdate($month, $day, $year)) {
        $result = '';
    } else {
        $filter['id_group'] = $id_group;
        $filter['date'] = $date;
        $filter['same_day'] = $same_day;
        $date_check = db_get_value_filter('date', 'talert_special_days', $filter);
        if ($date_check == $date) {
            $result = '';
            $messageAction = __('Could not be updated, it already exists');
        } else {
            if ($aviable_description !== false) {
                $result = alerts_update_alert_special_day($id, $values);
                $info = '{"Date":"'.$date;
                $info .= '","Same day of the week":"'.$same_day;
                $info .= '","Description":"'.$description.'"}';
            }
        }
    }

    if ($result) {
        db_pandora_audit(
            'Command management',
            'Update special day '.$id,
            false,
            false,
            $info
        );
    } else {
        db_pandora_audit(
            'Command management',
            'Fail to update special day '.$id,
            false,
            false
        );
    }


    // Show errors.
    if (isset($messageAction) === false) {
        $messageAction = __('Could not be updated');
    }

    $messageAction = ui_print_result_message(
        $result,
        __('Successfully updated'),
        $messageAction
    );
}

if ($delete_special_day === true) {
    $id = (int) get_parameter('id');

    $result = alerts_delete_alert_special_day($id);

    if ($result) {
        db_pandora_audit(
            'Command management',
            'Delete special day '.$id
        );
    } else {
        db_pandora_audit(
            'Command management',
            'Fail to delete special day '.$id
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

ui_require_javascript_file('pandora_alerts');
?>
<script type="text/javascript">
$(document).ready (function () {
    $("#srcbutton").click (function (e) {
        e.preventDefault();
        load_templates_alerts_special_days({
            date: '',
            id_group: $("#id_group").val(),
            same_day: $("#same_day").val(),
            btn_ok_text: '<?php echo __('Create'); ?>',
            btn_cancel_text: '<?php echo __('Cancel'); ?>',
            title: '<?php echo __('Load calendar'); ?>',
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            page: "godmode/alerts/alert_special_days",
            loading: '<?php echo __('Loading, this operation might take several minutes...'); ?>',
            name_form: 'icalendar-special-days'
        });
    });
});
</script>
