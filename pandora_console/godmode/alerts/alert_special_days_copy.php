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

    $get_template_alerts = (bool) get_parameter('get_template_alerts');
    if ($get_template_alerts === true) {
        $filter['special_day'] = 1;
        $templates = alerts_get_alert_templates($filter);
        $date = get_parameter('date', '');
        $id_group = get_parameter('id_group', 0);
        $same_day = get_parameter('same_day', '');

        $output = '<h4>'.__('Same as %s', ucfirst($same_day));
        $output .= ' &raquo; ';
        $output .= __('Templates not being fired');
        $output .= '</h4>';

        $columns = [
            'name',
            'id_group',
            'type',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];

        $column_names = [
            __('Name'),
            __('Group'),
            __('Type'),
            __('Mon'),
            __('Tue'),
            __('Wed'),
            __('Thu'),
            __('Fri'),
            __('Sat'),
            __('Sun'),
        ];
        try {
            $output .= ui_print_datatable(
                [
                    'id'                  => 'templates_alerts_special_days',
                    'return'              => true,
                    'class'               => 'info_table',
                    'style'               => 'width: 100%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => 'godmode/alerts/alert_special_days',
                    'ajax_data'           => [
                        'get_template_alerts_data' => 1,
                        'same_day'                 => $same_day,
                    ],
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'name',
                        'direction' => 'asc',
                    ],
                    'search_button_class' => 'sub filter float-right',
                    'form'                => [
                        'inputs' => [
                            [
                                'label'         => __('Type'),
                                'type'          => 'select',
                                'name'          => 'type',
                                'fields'        => alerts_get_alert_templates_types(),
                                'selected'      => 0,
                                'nothing'       => 'None',
                                'nothing_value' => 0,
                            ],
                            [
                                'label' => __('Search'),
                                'type'  => 'text',
                                'class' => 'mw250px',
                                'id'    => 'name',
                                'name'  => 'name',
                            ],
                        ],
                    ],
                ]
            );
        } catch (Exception $e) {
            $output .= $e->getMessage();
        }

        echo $output;

        return;
    }

    $get_template_alerts_data = (bool) get_parameter('get_template_alerts_data');
    if ($get_template_alerts_data === true) {
        $filters = get_parameter('filter', []);
        if (empty($filters['type']) === false) {
            $filter['type'] = $filters['type'];
        }

        if (empty($filters['name']) === false) {
            $filter[] = "name LIKE '%".$filters['name']."%'";
        }

        $filter['special_day'] = 1;

        $templates = alerts_get_alert_templates($filter);
        $count = alerts_get_alert_templates($filter, ['COUNT(*) AS total']);

        $same_day = get_parameter('same_day', '');
        $data = [];
        if (empty($templates) === false) {
            foreach ($templates as $template) {
                if ((bool) $template[$same_day] === false) {
                    $data[] = [
                        'name'      => $template['name'],
                        'id_group'  => ui_print_group_icon(
                            $template['id_group'],
                            true
                        ),
                        'type'      => $template['type'],
                        'monday'    => (bool) $template['monday'] === true
                    ? html_print_image(
                        'images/tick.png',
                        true,
                        ['class' => 'invert_filter']
                    )
                    : '',
                        'tuesday'   => (bool) $template['tuesday'] === true
                    ? html_print_image(
                        'images/tick.png',
                        true,
                        ['class' => 'invert_filter']
                    )
                    : '',
                        'wednesday' => (bool) $template['wednesday'] === true
                    ? html_print_image(
                        'images/tick.png',
                        true,
                        ['class' => 'invert_filter']
                    )
                    : '',
                        'thursday'  => (bool) $template['thursday'] === true
                    ? html_print_image(
                        'images/tick.png',
                        true,
                        ['class' => 'invert_filter']
                    )
                    : '',
                        'friday'    => (bool) $template['friday'] === true
                    ? html_print_image(
                        'images/tick.png',
                        true,
                        ['class' => 'invert_filter']
                    )
                    : '',
                        'saturday'  => (bool) $template['saturday'] === true
                    ? html_print_image(
                        'images/tick.png',
                        true,
                        ['class' => 'invert_filter']
                    )
                    : '',
                        'sunday'    => (bool) $template['sunday'] === true
                    ? html_print_image(
                        'images/tick.png',
                        true,
                        ['class' => 'invert_filter']
                    )
                    : '',
                    ];
                }
            }
        }

        echo json_encode(
            [
                'data'            => $data,
                'recordsTotal'    => $count[0]['total'],
                'recordsFiltered' => count($data),
            ]
        );

        return $data;
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


echo "<table cellpadding='4' cellspacing='4' class='databox upload bolder margin-bottom-10' width='100%
'><tr>";
echo "<form id='icalendar-special-days' method='post' enctype='multipart/form-data' action='index.php?sec=gagente&sec2=godmode/alerts/alert_special_days'>";
echo '<td>';
echo __('iCalendar(.ics) file').'&nbsp;';
html_print_input_file('ical_file', false, false);
echo '</td><td>';
echo __('Same day of the week');
$days = [];
$days['monday'] = __('Monday');
$days['tuesday'] = __('Tuesday');
$days['wednesday'] = __('Wednesday');
$days['thursday'] = __('Thursday');
$days['friday'] = __('Friday');
$days['saturday'] = __('Saturday');
$days['sunday'] = __('Sunday');
html_print_select($days, 'same_day', $same_day, '', '', 0, false, false, false);
echo '</td><td>';
echo __('Group').'&nbsp;';
$own_info = get_user_info($config['id_user']);
if (!users_can_manage_group_all('LM')) {
        $can_manage_group_all = false;
} else {
    $can_manage_group_all = true;
}

echo '<div class="inline w250px">';
html_print_select_groups(
    false,
    'LM',
    $can_manage_group_all,
    'id_group',
    $id_group,
    false,
    '',
    0,
    false,
    false,
    true,
    '',
    false,
    'width:100px;'
);
echo '</div>';
echo '</td><td>';
echo __('Overwrite');
ui_print_help_tip(
    __('Check this box, if you want to overwrite existing same days.'),
    false
);
echo '&nbsp;';
html_print_checkbox('overwrite', 1, $overwrite, false, false, false, true);
echo '</td><td>';
html_print_input_hidden('upload_ical', 1);
echo "<input id='srcbutton' name='srcbutton' type='submit' class='sub next' value='".__('Upload')."'>";
echo '</td></form>';
echo '</tr></table>';


$this_year = date('Y');
$this_month = date('m');

$filter = [];
if (!is_user_admin($config['id_user'])) {
    $filter['id_group'] = array_keys(users_get_groups(false, 'LM'));
}

// Show display range.
$html = "<table cellpadding='4' cellspacing='4' width='100%' margin-bottom: 10px;'><tr><td>".__('Display range: ');
if ($display_range) {
    $html .= '<a href="'.$url.'">['.__('Default').']</a>&nbsp;&nbsp;';
    if ($display_range > 1970) {
        $html .= '<a href="'.$url.'&display_range=';
        $html .= ($display_range - 1);
        $html .= '">&lt;&lt;&nbsp;</a>';
    }

    $html .= '<a href="'.$url.'&display_range='.$display_range.'" class="bolder">['.$display_range.']</a>';
    $html .= '<a href="'.$url.'&display_range=';
    $html .= ($display_range + 1);
    $html .= '">&nbsp;&gt;&gt;</a>';
} else {
    $html .= '<a href="'.$url.'" class="bolder">['.__('Default').']</a>&nbsp;&nbsp;';
    $html .= '<a href="'.$url.'&display_range=';
    $html .= ($this_year - 1);
    $html .= '">&lt;&lt;&nbsp;</a>';
    $html .= '<a href="'.$url.'&display_range=';
    $html .= $this_year;
    $html .= '">[';
    $html .= $this_year;
    $html .= ']</a>';
    $html .= '<a href="'.$url.'&display_range=';
    $html .= ($this_year + 1);
    $html .= '">&nbsp;&gt;&gt;</a>';
}

$html .= '</td></tr>';
echo $html;

// Show calendar.
for ($month = 1; $month <= 12; $month++) {
    if ($display_range) {
        $display_month = $month;
        $display_year = $display_range;
    } else {
        $display_month = ($this_month + $month - 1);
        $display_year = $this_year;
    }

    if ($display_month > 12) {
        $display_month -= 12;
        $display_year++;
    }

    $cal_table = new stdClass();
    $cal_table->width = '100%';
    $cal_table->class = 'databox data';

    $cal_table->data = [];
    $cal_table->head = [];
    $cal_table->head[0] = __('Sun');
    $cal_table->head[1] = __('Mon');
    $cal_table->head[2] = __('Tue');
    $cal_table->head[3] = __('Wed');
    $cal_table->head[4] = __('Thu');
    $cal_table->head[5] = __('Fri');
    $cal_table->head[6] = __('Sat');
    $cal_table->cellstyle = [];
    $cal_table->size = [];
    $cal_table->size[0] = '14%';
    $cal_table->size[1] = '14%';
    $cal_table->size[2] = '14%';
    $cal_table->size[3] = '14%';
    $cal_table->size[4] = '14%';
    $cal_table->size[5] = '14%';
    $cal_table->size[6] = '14%';
    $cal_table->align = [];
    $cal_table->border = '1';
    $cal_table->titlestyle = 'text-align:center; font-weight: bold;';
    switch ($display_month) {
        case 1:
            $cal_table->title = __('January');
        break;

        case 2:
            $cal_table->title = __('February');
        break;

        case 3:
            $cal_table->title = __('March');
        break;

        case 4:
            $cal_table->title = __('April');
        break;

        case 5:
            $cal_table->title = __('May');
        break;

        case 6:
            $cal_table->title = __('June');
        break;

        case 7:
            $cal_table->title = __('July');
        break;

        case 8:
            $cal_table->title = __('August');
        break;

        case 9:
            $cal_table->title = __('September');
        break;

        case 10:
            $cal_table->title = __('October');
        break;

        case 11:
            $cal_table->title = __('November');
        break;

        case 12:
            $cal_table->title = __('December');
        break;

        default:
            // Not possible.
        break;
    }

    $cal_table->title .= ' / '.$display_year;

    $last_day = date('j', mktime(0, 0, 0, ($display_month + 1), 0, $display_year));
    $cal_line = 0;

    for ($day = 1; $day < ($last_day + 1); $day++) {
        $week = date('w', mktime(0, 0, 0, $display_month, $day, $display_year));
        if ($cal_line == 0 && $week != 0 && $day == 1) {
            for ($i = 0; $i < $week; $i++) {
                $cal_table->cellstyle[$cal_line][$i] = 'font-size: 18px;';
                $cal_table->data[$cal_line][$i] = '-';
            }
        }

        if ($week == 0 || $week == 6) {
             $cal_table->cellstyle[$cal_line][$week] = 'color: red;';
        }

        $date = sprintf('%04d-%02d-%02d', $display_year, $display_month, $day);
        $date_wildcard = sprintf('0001-%02d-%02d', $display_month, $day);
        $special_days = '';
        $filter['date'] = [
            $date,
            $date_wildcard,
        ];
        $filter['order']['field'] = 'date';
        $filter['order']['order'] = 'DESC';
        $special_days = db_get_all_rows_filter('talert_special_days', $filter);

        $cal_table->cellstyle[$cal_line][$week] .= 'font-size: 18px;';
        $cal_table->data[$cal_line][$week] = $day.'&nbsp;';

        $cal_table->data[$cal_line][$week] .= '<a href="'.$url_alert.'&create_special_day=1&date='.$date.'" title=';
        $cal_table->data[$cal_line][$week] .= __('Create');
        $cal_table->data[$cal_line][$week] .= '>'.html_print_image(
            'images/add_mc.png',
            true,
            ['class' => 'invert_filter']
        ).'</a>';

        if (empty($special_days) === false) {
            $cal_table->data[$cal_line][$week] .= '<br>';
            foreach ($special_days as $special_day) {
                // Only show description if is filled.
                $cal_table->data[$cal_line][$week] .= '<div class="note-special-day">';
                $cal_table->data[$cal_line][$week] .= '<div>';
                $cal_table->data[$cal_line][$week] .= ui_print_group_icon(
                    $special_day['id_group'],
                    true
                );

                if (empty($special_day['description']) === false) {
                    $cal_table->data[$cal_line][$week] .= ui_print_help_tip($special_day['description'], true);
                }

                if ($special_day['date'] == $date_wildcard) {
                    $cal_table->data[$cal_line][$week] .= '(';
                    $cal_table->data[$cal_line][$week] .= ui_print_help_tip(
                        'This is valid every year. However, this will be ignored if indivisual setting for the same group is available.',
                        true
                    );
                    $cal_table->data[$cal_line][$week] .= ') ';
                }

                $cal_table->data[$cal_line][$week] .= __('As ');
                switch ($special_day['same_day']) {
                    case 'monday':
                        $cal_table->data[$cal_line][$week] .= __('Monday');
                    break;

                    case 'tuesday':
                        $cal_table->data[$cal_line][$week] .= __('Tuesday');
                    break;

                    case 'wednesday':
                        $cal_table->data[$cal_line][$week] .= __('Wednesday');
                    break;

                    case 'thursday':
                        $cal_table->data[$cal_line][$week] .= __('Thursday');
                    break;

                    case 'friday':
                        $cal_table->data[$cal_line][$week] .= __('Friday');
                    break;

                    case 'saturday':
                        $cal_table->data[$cal_line][$week] .= __('Saturday');
                    break;

                    case 'sunday':
                        $cal_table->data[$cal_line][$week] .= __('Sunday');
                    break;

                    default:
                        // Not possible.
                    break;
                }

                $cal_table->data[$cal_line][$week] .= '</div>';
                $cal_table->data[$cal_line][$week] .= '<div>';
                if ($special_day['id_group'] || ($can_manage_group_all && $special_day['id_group'] == 0)) {
                    $script_delete = '';
                    $dateformat = date_create($special_day['date']);
                    $options_zoom = htmlspecialchars(
                        json_encode(
                            [
                                'date'            => $special_day['date'],
                                'id_group'        => $special_day['id_group'],
                                'same_day'        => $special_day['same_day'],
                                'btn_ok_text'     => __('Create'),
                                'btn_cancel_text' => __('Cancel'),
                                'title'           => date_format($dateformat, 'd M Y'),
                                'url'             => ui_get_full_url('ajax.php', false, false, false),
                                'page'            => 'godmode/alerts/alert_special_days',
                                'loading'         => __('Loading, this operation might take several minutes...'),
                            ]
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $onclick_zoom = 'load_templates_alerts_special_days('.$options_zoom.')';
                    $cal_table->data[$cal_line][$week] .= '<a href="#" onclick="'.$onclick_zoom.'"';
                    $cal_table->data[$cal_line][$week] .= 'title="';
                    $cal_table->data[$cal_line][$week] .= __('Show templates');
                    $cal_table->data[$cal_line][$week] .= '">';
                    $cal_table->data[$cal_line][$week] .= html_print_image(
                        'images/zoom.png',
                        true,
                        ['class' => 'invert_filter']
                    ).'</a>';
                    $cal_table->data[$cal_line][$week] .= '<a href="'.$url_alert.'&id='.$special_day['id'].'" title=';
                    $cal_table->data[$cal_line][$week] .= __('Edit');
                    $cal_table->data[$cal_line][$week] .= '>'.html_print_image(
                        'images/config.png',
                        true,
                        ['class' => 'invert_filter']
                    ).'</a> &nbsp;';
                    $url_delete = $url.'&delete_special_day=1&id='.$special_day['id'];
                    $script_delete = 'if (!confirm(\''.__('Are you sure?').'\')) return false;';
                    $cal_table->data[$cal_line][$week] .= '<a href="'.$url_delete.'"';
                    $cal_table->data[$cal_line][$week] .= ' onClick="'.$script_delete.'"';
                    $cal_table->data[$cal_line][$week] .= 'title="';
                    $cal_table->data[$cal_line][$week] .= __('Remove');
                    $cal_table->data[$cal_line][$week] .= '">';
                    $cal_table->data[$cal_line][$week] .= html_print_image(
                        'images/cross.png',
                        true,
                        ['class' => 'invert_filter']
                    ).'</a>';
                }

                $cal_table->data[$cal_line][$week] .= '</div>';
                $cal_table->data[$cal_line][$week] .= '</div>';
            }
        }

        if ($week == 6) {
            $cal_line++;
        }
    }

    for ($padding = ($week + 1); $padding <= 6; $padding++) {
        $cal_table->cellstyle[$cal_line][$padding] = 'font-size: 18px;';
        $cal_table->data[$cal_line][$padding] = '-';
    }

    html_print_table($cal_table);
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="'.$url_alert.'">';
html_print_submit_button(__('Create'), 'create', false, 'class="sub next"');
html_print_input_hidden('create_special_day', 1);
echo '</form>';
echo '</div>';
echo '<div id="modal-alert-templates" class="invisible"></div>';
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
