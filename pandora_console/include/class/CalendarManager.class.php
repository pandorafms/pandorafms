<?php
/**
 * Calendar controller.
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

// Begin.
namespace PandoraFMS;

use PandoraFMS\Calendar;
use PandoraFMS\SpecialDay;

/**
 * Controller for CalendarManager.
 */
class CalendarManager
{

    /**
     * Base url for ajax calls.
     *
     * @var string
     */
    private const BASE_AJAX_PAGE = '/godmode/alerts/alert_special_days.php';

    /**
     * Base url.
     *
     * @var string
     */
    private $url;

    /**
     * Ajax url.
     *
     * @var string
     */
    private $ajaxUrl;

    /**
     * Some error message.
     *
     * @var string
     */
    private $message;

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public static $AJAXMethods = [
        'drawListCalendar',
        'drawAlertTemplates',
        'dataAlertTemplates',
    ];


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public static function ajaxMethod(string $method):bool
    {
        return in_array($method, self::$AJAXMethods);
    }


    /**
     * Generates a JSON error.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public function error(string $msg)
    {
        echo json_encode(
            ['error' => $msg]
        );
    }


    /**
     * Instantiate controller.
     *
     * @param string      $url      Utility url.
     * @param string|null $ajax_url Ajax url.
     */
    public function __construct(string $url, ?string $ajax_url=null)
    {
        global $config;
        $this->access = 'LM';

        check_login();
        // ACL Check.
        if ((bool) check_acl($config['id_user'], 0, $this->access) !== true) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Alert calendar view'
            );
            include 'general/noaccess.php';
            exit;
        }

        $this->url = $url;
        $this->ajaxUrl = self::BASE_AJAX_PAGE;

        if (empty($ajax_url) === false) {
            $this->ajaxUrl = $ajax_url;
        }
    }


    /**
     * Retrieves a list of.
     *
     * @param string $tab Active tab.
     *
     * @return array
     */
    public function getTabs(string $tab='list')
    {
        global $config;

        $buttons = [];

        $buttons['list'] = [
            'active' => false,
            'text'   => '<a href="'.ui_get_full_url(
                $this->url.'&tab_calendar=list'
            ).'&pure='.(int) $config['pure'].'">'.html_print_image(
                'images/logs@svg.svg',
                true,
                [
                    'title' => __('Alert calendar list'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).'</a>',
        ];

        $id_calendar = get_parameter('id_calendar', 0);
        $id = get_parameter('id', 0);

        $op = get_parameter('op', '');
        $action = get_parameter('action', '');

        if (($id_calendar !== 0 || $id !== 0)) {
            $id = ($id_calendar === 0) ? $id : $id_calendar;
            $buttons['list_edit'] = [
                'active' => false,
                'text'   => '<a href="'.ui_get_full_url(
                    $this->url.'&tab_calendar=list&op=edit&id='.$id
                ).'&pure='.(int) $config['pure'].'">'.html_print_image(
                    'images/edit.svg',
                    true,
                    [
                        'title' => __('Edit calendar'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>',
            ];

            $buttons['special_days'] = [
                'active' => false,
                'text'   => '<a href="'.ui_get_full_url(
                    $this->url.'&tab_calendar=special_days&id_calendar='.$id
                ).'&pure='.(int) $config['pure'].'">'.html_print_image(
                    'images/templates.png',
                    true,
                    [
                        'title' => __('Alert special days'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>',
            ];
        }

        if ($op === 'edit' && $action === '') {
            $tab = 'list_edit';
        }

        $buttons[$tab]['active'] = true;

        return $buttons;
    }


    /**
     * Execute page and show interface.
     *
     * @return void
     */
    public function run()
    {
        \ui_require_css_file('alert');
        $op = get_parameter('op');
        $tab = get_parameter('tab_calendar');
        switch ($tab) {
            case 'special_days':
                if ($op === 'edit') {
                    if ($this->showSpecialDaysEdition() !== true) {
                        return;
                    }
                } else if ($op === 'delete') {
                    $this->deleteSpecialDay();
                } else if ($op === 'upload_ical') {
                    $this->iCalendarSpecialDay();
                }

                $this->showSpecialDays();
            break;

            case 'list':
            default:
                if ($op === 'edit') {
                    if ($this->showCalendarEdition() !== true) {
                        return;
                    }
                } else if ($op === 'delete') {
                    $this->deleteCalendar();
                }

                $this->showCalendarList();
            break;
        }

    }


    /**
     * Delete calendar
     *
     * @return void
     */
    public function deleteCalendar()
    {
        global $config;

        $id = (int) get_parameter('id');
        try {
            $calendar = new Calendar($id);
            if ($id === 0) {
                return;
            }
        } catch (\Exception $e) {
            if ($id > 0) {
                $this->message = \ui_print_error_message(
                    \__('Calendar not found: %s', $e->getMessage()),
                    '',
                    true
                );
            }

            return;
        }

        if (is_numeric($id) === true) {
            if ((bool) check_acl(
                $config['id_user'],
                $calendar->id_group(),
                'LM'
            ) === false
            ) {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to access calendar delete'
                );
                include 'general/noaccess.php';
                exit;
            }
        }

        // Remove.
        $calendar->delete();
        $this->message = \ui_print_success_message(
            \__('Calendar successfully deleted'),
            '',
            true
        );
    }


    /**
     * Delete special day.
     *
     * @return void
     */
    public function deleteSpecialDay()
    {
        $id = (int) get_parameter('id');
        try {
            $specialDay = new SpecialDay($id);
            if ($id === 0) {
                return;
            }
        } catch (\Exception $e) {
            if ($id > 0) {
                $this->message = \ui_print_error_message(
                    \__('Special day not found: %s', $e->getMessage()),
                    '',
                    true
                );
            }

            return;
        }

        // Remove.
        $specialDay->delete();
        $this->message = \ui_print_success_message(
            \__('Special day successfully deleted'),
            '',
            true
        );
    }


    /**
     * Icalendar.
     *
     * @return void
     */
    public function iCalendarSpecialDay()
    {
        include_once 'include/ics-parser/class.iCalReader.php';

        $day_code = (string) get_parameter('day_code');
        $overwrite = (bool) get_parameter('overwrite', 0);
        $values = [];
        $values['id_group'] = (string) get_parameter('id_group');
        $values['id_calendar'] = get_parameter('id_calendar');
        $values['day_code'] = $day_code;
        $error = $_FILES['ical_file']['error'];
        $extension = substr($_FILES['ical_file']['name'], -3);

        if ($error == 0 && strcasecmp($extension, 'ics') == 0) {
            $result = true;
            $skipped_dates = '';
            $this_month = date('Ym');
            $ical = new \ICal($_FILES['ical_file']['tmp_name']);
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
                    $filter['id_calendar'] = $values['id_calendar'];
                    $date_check = db_get_value_filter(
                        'date',
                        'talert_special_days',
                        $filter
                    );

                    if ($date_check === $date) {
                        if ($overwrite === true) {
                            $id_special_day = db_get_value_filter(
                                'id',
                                'talert_special_days',
                                $filter
                            );
                            try {
                                $specialDay = new SpecialDay($id_special_day);
                                $specialDay->date($values['date']);
                                $specialDay->id_group($values['id_group']);
                                $specialDay->day_code($values['day_code']);
                                $specialDay->description($values['description']);
                                $specialDay->id_calendar($values['id_calendar']);

                                if ($specialDay->save() === true) {
                                    $result = true;
                                } else {
                                    $result = false;
                                }
                            } catch (\Exception $e) {
                                $result = false;
                            }
                        } else {
                            if ($skipped_dates == '') {
                                $skipped_dates = __('Skipped dates: ');
                            }

                            $skipped_dates .= $date.' ';
                        }
                    } else {
                        try {
                            $specialDay = new SpecialDay();
                            $specialDay->date($values['date']);
                            $specialDay->id_group($values['id_group']);
                            $specialDay->day_code($values['day_code']);
                            $specialDay->description($values['description']);
                            $specialDay->id_calendar($values['id_calendar']);

                            if ($specialDay->save() === true) {
                                $result = true;
                            } else {
                                $result = false;
                            }
                        } catch (\Exception $e) {
                            $result = false;
                        }
                    }
                }
            }
        } else {
            $result = false;
        }

        if ($result === true) {
            db_pandora_audit(
                AUDIT_LOG_SYSTEM,
                'Special Days. Upload iCalendar '.$_FILES['ical_file']['name']
            );
        }

        $this->message = \ui_print_result_message(
            $result,
            \__('Success to upload iCalendar').'<br />'.$skipped_dates,
            \__('Fail to upload iCalendar')
        );
    }


    /**
     * Show a list of models registered in this system.
     *
     * @return void
     */
    public function showCalendarList()
    {
        View::render(
            'calendar/list',
            [
                'ajax_url' => $this->ajaxUrl,
                'url'      => $this->url,
                'tabs'     => $this->getTabs('list'),
                'message'  => $this->message,
            ]
        );
    }


    /**
     * Show a list of network configuration templates.
     *
     * @return boolean Continue showing list or not.
     */
    public function showCalendarEdition()
    {
        global $config;
        $id = (int) get_parameter('id');
        $new = false;
        try {
            $calendar = new Calendar($id);
            if ($id === 0) {
                $new = true;
            }
        } catch (\Exception $e) {
            if ($id > 0) {
                $this->message = \ui_print_error_message(
                    \__('Calendar not found: %s', $e->getMessage()),
                    '',
                    true
                );
            }

            $calendar = new Calendar();
            $new = true;
        }

        $group_id = null;

        if ($new === true) {
            if (is_numeric(get_parameter('id_group')) === true) {
                $group_id = get_parameter('id_group');
            }
        } else {
            if (is_numeric($calendar->id_group()) === true) {
                $group_id = $calendar->id_group();
            }
        }

        if (is_numeric($group_id) === true) {
            // Check for permissions before rendering edit view or performing save action.
            if ((bool) check_acl(
                $config['id_user'],
                $group_id,
                'LM'
            ) === false
            ) {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to access calendar editor'
                );
                include 'general/noaccess.php';
                exit;
            }
        }

        $action = get_parameter('action');
        if ($action === 'save') {
            $success = false;

            $msg_ok = \__('Successfully updated');
            $msg_err = \__('Failed to update');
            if ($new === true) {
                $msg_ok = \__('Successfully created');
                $msg_err = \__('Failed to create');
            }

            try {
                $name = get_parameter('name', null);
                $change_name = true;
                if ($new === false && $name === $calendar->name()) {
                    $change_name = false;
                }

                $calendar->name($name);
                $calendar->id_group(get_parameter('id_group', null));
                $calendar->description(get_parameter('description', null));

                if ($change_name === true && empty($calendar->search(['name' => $calendar->name()])) === false) {
                    $reason = \__(
                        'Failed saving calendar: name exists',
                        $config['dbconnection']->error
                    );
                } else {
                    // Save template.
                    if ($calendar->save() === true) {
                        $success = true;
                    } else {
                        $reason = \__(
                            'Failed saving calendar: ',
                            $config['dbconnection']->error
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->message = \ui_print_error_message(
                    \__('Error: %s', $e->getMessage()),
                    '',
                    true
                );
                $success = false;
            }

            $this->message .= \ui_print_result_message(
                $success,
                $msg_ok,
                sprintf('%s%s', $msg_err, $reason),
                '',
                true
            );

            if ($success === true) {
                return $success;
            }
        }

        View::render(
            'calendar/edit',
            [
                'ajax_url' => $this->ajaxUrl,
                'url'      => $this->url.'&op=edit&tab_calendar=list',
                'tabs'     => $this->getTabs('list'),
                'calendar' => $calendar,
                'message'  => $this->message,
                'create'   => $new,
            ]
        );

        return false;
    }


    /**
     * AJAX Method, draws calendar list.
     *
     * @return void
     */
    public function drawListCalendar()
    {
        global $config;

        // Datatables offset, limit and order.
        $filter = get_parameter('filter', []);
        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);

        try {
            ob_start();

            $fields = ['`talert_calendar`.*'];

            // Retrieve data.
            $data = Calendar::calendars(
                // Fields.
                $fields,
                // Filter.
                $filter,
                // Count.
                false,
                // Offset.
                $start,
                // Limit.
                $length,
                // Order.
                $order['direction'],
                // Sort field.
                $order['field']
            );

            // Retrieve counter.
            $count = Calendar::calendars(
                $fields,
                $filter,
                true
            )['count'];

            $is_management_allowed = \is_management_allowed();

            if ((bool) $data === true) {
                $user_id = $config['id_user'];

                $data = array_reduce(
                    $data,
                    function ($carry, $item) use ($user_id, $is_management_allowed) {
                        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        // Users must only be able to manage items that belong to their groups.
                        // IMPORTANT: if user does not have permission over 'All' group, items belonging to such
                        // group must be listed but they must not allow for edition.
                        $manage = check_acl_restricted_all(
                            $user_id,
                            $item['id_group'],
                            'LM'
                        );

                        $tmp = (object) $item;

                        if ((bool) $manage === true) {
                            $name = '<b><a href="';
                            $name .= ui_get_full_url(
                                $this->url.'&op=special_days&tab_calendar=special_days&id_calendar='.$tmp->id
                            );
                            $name .= '">';
                            $name .= $tmp->name;
                            $name .= '</a></b>';
                            $tmp->name = $name;
                        }

                        $tmp->id_group = \ui_print_group_icon(
                            $tmp->id_group,
                            true
                        );

                        // Options. View.
                        $tmp->options = '';
                        if ((bool) $manage === true) {
                            if ($is_management_allowed === true) {
                                // Options. Edit.
                                $tmp->options .= '<a href="';
                                $tmp->options .= ui_get_full_url(
                                    $this->url.'&op=edit&id='.$tmp->id
                                );
                                $tmp->options .= '">';
                                $tmp->options .= html_print_image(
                                    'images/edit.svg',
                                    true,
                                    [
                                        'title' => __('Edit'),
                                        'class' => 'invert_filter',
                                    ]
                                );
                                $tmp->options .= '</a>';
                            }

                            // Options. Especial days.
                            $tmp->options .= '<a href="';
                            $tmp->options .= ui_get_full_url(
                                $this->url.'&op=special_days&tab_calendar=special_days&id_calendar='.$tmp->id
                            );
                            $tmp->options .= '">';
                            $tmp->options .= html_print_image(
                                'images/add.png',
                                true,
                                [
                                    'title' => __('Special days'),
                                    'class' => 'invert_filter',
                                ]
                            );
                            $tmp->options .= '</a>';

                            if ($is_management_allowed === true && $tmp->id != 1) {
                                // Options. Delete.
                                $tmp->options .= '<a href="';
                                $tmp->options .= ui_get_full_url(
                                    $this->url.'&op=delete&id='.$tmp->id
                                );
                                $tmp->options .= '">';
                                $tmp->options .= html_print_image(
                                    'images/delete.svg',
                                    true,
                                    [
                                        'title' => __('Delete'),
                                        'class' => 'invert_filter',
                                    ]
                                );
                                $tmp->options .= '</a>';
                            }
                        }

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }

            // Datatables format: RecordsTotal && recordsfiltered.
            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $count,
                    'recordsFiltered' => $count,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        // If not valid, show error with issue.
        json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            // If valid dump.
            echo $response;
        } else {
            echo json_encode(
                ['error' => $response]
            );
        }

    }


    /**
     * Show a list of models registered in this system.
     *
     * @return void
     */
    public function showSpecialDays()
    {
        global $config;
        $id_calendar = (int) get_parameter('id_calendar');

        $display_range = (int) get_parameter('display_range', 0);
        try {
            // Datatables offset, limit and order.
            if ($display_range === 0) {
                $date = date('Y').'-'.date('m').'-1';
            } else {
                $date = $display_range.'-1-1';
            }

            $futureDate = date('Y-m-d', strtotime('+1 year', strtotime($date)));

            $filter = [];
            $filter['date'] = $date;
            $filter['futureDate'] = $futureDate;
            $filter['id_calendar'] = $id_calendar;
            if (!is_user_admin($config['id_user'])) {
                $filter['id_group'] = array_keys(
                    users_get_groups(false, 'LM')
                );
            }

            $fields = ['`talert_special_days`.*'];

            $specialDays = specialDay::specialDays(
                // Fields.
                $fields,
                // Filter.
                $filter,
                // Count.
                false,
                // Offset.
                null,
                // Limit.
                null,
                // Order.
                null,
                // Sort field.
                null,
                // Reduce array.
                true
            );
        } catch (\Exception $e) {
            if ($id_calendar > 0) {
                $this->message = \ui_print_error_message(
                    \__('Special days not found: %s', $e->getMessage()),
                    '',
                    true
                );
            }
        }

        View::render(
            'calendar/special_days',
            [
                'ajax_url'      => $this->ajaxUrl,
                'url'           => $this->url.'&tab_calendar=special_days',
                'tabs'          => $this->getTabs('special_days'),
                'message'       => $this->message,
                'specialDays'   => $specialDays,
                'id_calendar'   => $id_calendar,
                'display_range' => $display_range,
            ]
        );
    }


    /**
     * Show form special day.
     *
     * @return boolean Continue showing list or not.
     */
    public function showSpecialDaysEdition()
    {
        global $config;

        $id = (int) get_parameter('id');
        $new = false;
        try {
            $specialDay = new SpecialDay($id);
            if ($id === 0) {
                $specialDay->date(get_parameter('date', null));
                $specialDay->id_calendar(get_parameter('id_calendar', null));
                $new = true;
            }
        } catch (\Exception $e) {
            if ($id > 0) {
                $this->message = \ui_print_error_message(
                    \__('SpecialDay not found: %s', $e->getMessage()),
                    '',
                    true
                );
            }

            $specialDay = new SpecialDay();
            $new = true;
        }

        $action = get_parameter('action');
        if ($action === 'save') {
            $success = false;

            $msg_ok = \__('Successfully updated');
            $msg_err = \__('Failed to update');
            if ($new === true) {
                $msg_ok = \__('Successfully created');
                $msg_err = \__('Failed to create');
            }

            try {
                $date = get_parameter('date', null);
                $id_group = get_parameter('id_group', null);
                $day_code = get_parameter('day_code', null);
                $id_calendar = get_parameter('id_calendar', null);
                $description = io_safe_input(get_parameter('description', null));
                $change = true;
                if ($new === false
                    && ($date === $specialDay->date()
                    || $id_group === $specialDay->id_group()
                    || $day_code === $specialDay->day_code())
                ) {
                    $change = false;
                }

                $specialDay->date($date);
                $specialDay->id_group($id_group);
                $specialDay->day_code($day_code);
                $specialDay->description($description);
                $specialDay->id_calendar($id_calendar);

                $search = specialDay::specialDays(
                    [ '`talert_special_days`.*' ],
                    [
                        'date_match' => $specialDay->date(),
                        'id_group'   => [$specialDay->id_group()],
                        'day_code'   => $specialDay->day_code(),
                    ]
                );

                if ($change === true && empty($search) === false) {
                    $reason = \__(
                        'Failed saving calendar: already exists',
                        $config['dbconnection']->error
                    );
                } else {
                    // Save template.
                    if ($specialDay->save() === true) {
                        $success = true;
                    } else {
                        $reason = \__(
                            'Failed saving special day: ',
                            $config['dbconnection']->error
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->message = \ui_print_error_message(
                    \__('Error: %s', $e->getMessage()),
                    '',
                    true
                );
                $success = false;
            }

            $this->message .= \ui_print_result_message(
                $success,
                $msg_ok,
                sprintf('%s%s', $msg_err, $reason),
                '',
                true
            );

            if ($success === true) {
                return $success;
            }
        }

        View::render(
            'calendar/special_days_edit',
            [
                'ajax_url'    => $this->ajaxUrl,
                'url'         => $this->url.'&id_calendar='.$specialDay->id_calendar().'&op=edit&tab_calendar=special_days',
                'tabs'        => $this->getTabs('special_days'),
                'specialDay'  => $specialDay,
                'message'     => $this->message,
                'create'      => $new,
                'id_calendar' => $specialDay->id_calendar(),
            ]
        );

        return false;
    }


    /**
     * AJAX Method, draw Alert Template list.
     *
     * @return void
     */
    public function drawAlertTemplates()
    {
        global $config;
        $date = get_parameter('date', '');
        $id_group = get_parameter('id_group', 0);
        $day_code = get_parameter('day_code', '');
        $id_calendar = get_parameter('id_calendar', 0);

        $weekdays = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
            8 => 'holidays',
        ];

        $output = '<h4>'.__('Same as %s', $weekdays[$day_code]);
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
                    'style'               => 'width: 99%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => 'godmode/alerts/alert_special_days',
                    'ajax_data'           => [
                        'method'      => 'dataAlertTemplates',
                        'day_code'    => $day_code,
                        'id_calendar' => $id_calendar,
                        'id_group'    => $id_group,
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
                        'class'  => 'no_border',
                    ],
                    'filter_main_class'   => 'box-flat white_table_graph',
                ]
            );
        } catch (Exception $e) {
            $output .= $e->getMessage();
        }

        echo $output;

        return;
    }


    /**
     * AJAX Method, draw Alert Template list.
     *
     * @return array
     */
    public function dataAlertTemplates()
    {
        global $config;
        $filters = get_parameter('filter', []);
        if (empty($filters['type']) === false) {
            $filter['type'] = $filters['type'];
        }

        if (empty($filters['name']) === false) {
            $filter[] = "name LIKE '%".$filters['name']."%'";
        }

        $id_calendar = (int) get_parameter('id_calendar', 0);
        $id_group = (int) get_parameter('id_group', 0);
        $filter['special_day'] = $id_calendar;

        if ($id_group !== 0) {
            $filter['id_group'] = $id_group;
        }

        $templates = alerts_get_alert_templates($filter);

        $count = alerts_get_alert_templates($filter, ['COUNT(*) AS total']);

        $day_code = get_parameter('day_code', '');

        $weekdays = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
        ];

        $data = [];
        if (empty($templates) === false) {
            foreach ($templates as $template) {
                if ((bool) $template[$weekdays[$day_code]] === false) {
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


}
