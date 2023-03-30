<?php
/**
 * Calendar: Calendar list page.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Alert
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

global $config;

\ui_require_css_file('wizard');
\enterprise_include_once('meta/include/functions_alerts_meta.php');

if (\is_metaconsole() === true) {
    \alerts_meta_print_header($tabs);
} else {
    // Header.
    ui_print_standard_header(
        __('Alerts'),
        'images/gm_alerts.png',
        false,
        'alert_special_days',
        true,
        $tabs,
        [
            [
                'link'  => '',
                'label' => __('Alerts'),
            ],
            [
                'link'  => '',
                'label' => __('Special days'),
            ],
        ]
    );
}

$is_management_allowed = \is_management_allowed();
if ($is_management_allowed === false) {
    if (\is_metaconsole() === false) {
        $url_link = '<a target="_blank" href="'.ui_get_meta_url($url).'">';
        $url_link .= __('metaconsole');
        $url_link .= '</a>';
    } else {
        $url_link = __('any node');
    }

    \ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All alert calendar information is read only. Go to %s to manage it.',
            $url_link
        )
    );
}

if (empty($message) === false) {
    echo $message;
}

$inputs = [];

// Name.
$inputs[] = [
    'label'     => __('iCalendar(.ics) file'),
    'arguments' => [
        'name'    => 'ical_file',
        'type'    => 'file',
        'columns' => 25,
        'rows'    => 10,
        'options' => ['required' => 1],
    ],
    'class'     => 'flex flex_column',
];

$days = [];
$days['monday'] = __('Monday');
$days['tuesday'] = __('Tuesday');
$days['wednesday'] = __('Wednesday');
$days['thursday'] = __('Thursday');
$days['friday'] = __('Friday');
$days['saturday'] = __('Saturday');
$days['sunday'] = __('Sunday');

// Same day of the week.
$inputs[] = [
    'label'     => __('Same day of the week'),
    'arguments' => [
        'name'   => 'day_code',
        'type'   => 'select',
        'fields' => $days,
    ],
    'class'     => 'mrgn_right_20px',
];

// Group.
$inputs[] = [
    'label'     => __('Group'),
    'arguments' => [
        'type'           => 'select_groups',
        'returnAllGroup' => true,
        'name'           => 'id_group',
    ],
    'class'     => 'mrgn_right_20px',
];

// Group.
$inputs[] = [
    'label'     => __('Overwrite').ui_print_help_tip(
        __('Check this box, if you want to overwrite existing same days.'),
        true
    ),
    'arguments' => [
        'type'            => 'checkbox',
        'name'            => 'overwrite',
        'id'              => 'overwrite',
        'disabled_hidden' => true,
    ],
    'class'     => 'flex flex_column',
];

// Submit.
$inputs[] = [
    'arguments' => [
        'name'       => 'button',
        'label'      => __('Upload'),
        'type'       => 'submit',
        'attributes' => [
            'icon'  => 'wand',
            'class' => 'mini',
        ],
    ],
];

if ($is_management_allowed === true) {
    // Print form.
    $form_upload = HTML::printForm(
        [
            'form'   => [
                'action'  => $url.'&op=upload_ical&id='.$id_calendar,
                'method'  => 'POST',
                'id'      => 'icalendar-special-days',
                'enctype' => 'multipart/form-data',
                'class'   => 'calendar-upload-form',
            ],
            'inputs' => $inputs,
        ],
        true
    );

    ui_toggle(
        $form_upload,
        '<span class="subsection_header_title">'.__('Upload').'</span>',
        __('Upload'),
        'upload',
        true,
        false,
        '',
        'white-box-content no_border',
        'filter-datatable-main box-flat white_table_graph fixed_filter_bar  '
    );
}


$this_year = date('Y');
$this_month = date('m');

$filter = [];
if (!is_user_admin($config['id_user'])) {
    $filter['id_group'] = array_keys(users_get_groups(false, 'LM'));
}

$url = $url.'&id_calendar='.$id_calendar;

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
    $cal_table->class = 'databox data special-days-thead';

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
    $cal_table->titlestyle = 'text-align:center;';
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

        $cal_table->cellstyle[$cal_line][$week] .= 'font-size: 18px;';
        $cal_table->data[$cal_line][$week] = $day.'&nbsp;';

        if ($is_management_allowed === true) {
            $cal_table->data[$cal_line][$week] .= '<a href="'.$url.'&op=edit&date='.$date.'" title=';
            $cal_table->data[$cal_line][$week] .= __('Create');
            $cal_table->data[$cal_line][$week] .= '>'.html_print_image(
                'images/add_mc.png',
                true,
                ['class' => 'invert_filter']
            ).'</a>';
        }

        if (empty($specialDays) === false && isset($specialDays[$display_year][$display_month][$day]) === true) {
            $cal_table->data[$cal_line][$week] .= '<br>';
            foreach ($specialDays[$display_year][$display_month][$day] as $special_day) {
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
                switch ($special_day['day_code']) {
                    case '1':
                        $cal_table->data[$cal_line][$week] .= __('Monday');
                    break;

                    case '2':
                        $cal_table->data[$cal_line][$week] .= __('Tuesday');
                    break;

                    case '3':
                        $cal_table->data[$cal_line][$week] .= __('Wednesday');
                    break;

                    case '4':
                        $cal_table->data[$cal_line][$week] .= __('Thursday');
                    break;

                    case '5':
                        $cal_table->data[$cal_line][$week] .= __('Friday');
                    break;

                    case '6':
                        $cal_table->data[$cal_line][$week] .= __('Saturday');
                    break;

                    case '7':
                        $cal_table->data[$cal_line][$week] .= __('Sunday');
                    break;

                    case '8':
                        $cal_table->data[$cal_line][$week] .= __('Holidays');
                    break;

                    default:
                        // Not possible.
                    break;
                }

                $cal_table->data[$cal_line][$week] .= '</div>';
                $cal_table->data[$cal_line][$week] .= '<div>';
                if ($special_day['id_group'] || (users_can_manage_group_all('LM') === true && $special_day['id_group'] == 0)) {
                    $script_delete = '';
                    $dateformat = date_create($special_day['date']);
                    $options_zoom = htmlspecialchars(
                        json_encode(
                            [
                                'date'            => $special_day['date'],
                                'id_group'        => $special_day['id_group'],
                                'day_code'        => $special_day['day_code'],
                                'id_calendar'     => $special_day['id_calendar'],
                                'btn_ok_text'     => __('Create'),
                                'btn_cancel_text' => __('Cancel'),
                                'title'           => date_format($dateformat, 'd M Y'),
                                'url'             => ui_get_full_url('ajax.php', false, false, false),
                                'page'            => $ajax_url,
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

                    if ($is_management_allowed === true) {
                        $cal_table->data[$cal_line][$week] .= '<a href="'.$url.'&op=edit&id='.$special_day['id'].'" title=';
                        $cal_table->data[$cal_line][$week] .= __('Edit');
                        $cal_table->data[$cal_line][$week] .= '>'.html_print_image(
                            'images/edit.svg',
                            true,
                            ['class' => 'invert_filter']
                        ).'</a> &nbsp;';
                        $url_delete = $url.'&op=delete&id='.$special_day['id'];
                        $script_delete = 'if (!confirm(\''.__('Are you sure?').'\')) return false;';
                        $cal_table->data[$cal_line][$week] .= '<a href="'.$url_delete.'"';
                        $cal_table->data[$cal_line][$week] .= ' onClick="'.$script_delete.'"';
                        $cal_table->data[$cal_line][$week] .= 'title="';
                        $cal_table->data[$cal_line][$week] .= __('Remove');
                        $cal_table->data[$cal_line][$week] .= '">';
                        $cal_table->data[$cal_line][$week] .= html_print_image(
                            'images/delete.svg',
                            true,
                            ['class' => 'invert_filter']
                        ).'</a>';
                    }
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

if ((bool) check_acl($config['id_user'], 0, 'LM') === true) {
    $form_create = HTML::printForm(
        [
            'form'   => [
                'action' => $url.'&op=edit',
                'method' => 'POST',
            ],
            'inputs' => [
                [
                    'arguments' => [
                        'name'       => 'button',
                        'label'      => __('Create'),
                        'type'       => 'submit',
                        'attributes' => ['icon' => 'wand'],
                    ],
                ],
            ],
        ],
        true
    );
    html_print_action_buttons($form_create);
}

echo '<div id="modal-alert-templates" class="invisible"></div>';
ui_require_javascript_file('pandora_alerts');

?>
<script type="text/javascript">
$(document).ready (function () {
    $("#submit-button").click (function (e) {
        e.preventDefault();
        load_templates_alerts_special_days({
            date: '',
            id_group: $("#id_group").val(),
            day_code: $("#day_code").val(),
            id_calendar: '<?php echo $id_calendar; ?>',
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