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
    public static $AJAXMethods = ['drawListCalendar'];


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
                'ACL Violation',
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
                $this->url.'&tab=list'
            ).'&pure='.(int) $config['pure'].'">'.html_print_image(
                'images/list.png',
                true,
                [
                    'title' => __('Alert calendar list'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ];

        if ($tab !== 'list') {
            $buttons['special_days'] = [
                'active' => false,
                'text'   => '<a href="'.ui_get_full_url(
                    $this->url.'&tab=special_days'
                ).'&pure='.(int) $config['pure'].'">'.html_print_image(
                    'images/templates.png',
                    true,
                    [
                        'title' => __('Alert special days'),
                        'class' => 'invert_filter',
                    ]
                ).'</a>',
            ];
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
        $op = get_parameter('op');
        $tab = get_parameter('tab');
        switch ($tab) {
            case 'special_days':
                if ($op === 'edit') {
                    if ($this->showSpecialDaysEdition() !== true) {
                        return;
                    }
                } else if ($op === 'delete') {
                    // $this->deleteVendor();
                    hd('delete special days');
                }

                echo 'WIP special list';
                $this->showSpecialDays();
            break;

            case 'list':
            default:
                if ($op === 'edit') {
                    if ($this->showCalendarEdition() !== true) {
                        return;
                    }
                } else if ($op === 'delete') {
                    // $this->deleteVendor();
                    hd('delete calendar');
                }

                $this->showCalendarList();
            break;
        }

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
                $calendar->name(get_parameter('name', null));
                $calendar->id_group(get_parameter('id_group', null));
                $calendar->description(get_parameter('description', null));

                // Save template.
                if ($calendar->save() === true) {
                    $success = true;
                } else {
                    global $config;
                    $reason = \__(
                        'Failed saving calendar: ',
                        $config['dbconnection']->error
                    );
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
                'url'      => $this->url.'&op=edit&tab=list',
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

            if ((bool) $data === true) {
                $manage = check_acl(
                    $config['id_user'],
                    0,
                    'LM',
                    true
                );

                $data = array_reduce(
                    $data,
                    function ($carry, $item) use ($manage) {
                        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        if ((bool) $manage === true) {
                            $name = '<b><a href="';
                            $name .= ui_get_full_url(
                                $this->url.'&op=edit&id='.$tmp->id
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
                            // Options. Edit.
                            $tmp->options .= '<a href="';
                            $tmp->options .= ui_get_full_url(
                                $this->url.'&op=edit&id='.$tmp->id
                            );
                            $tmp->options .= '">';
                            $tmp->options .= html_print_image(
                                'images/config.png',
                                true,
                                [
                                    'title' => __('Edit'),
                                    'class' => 'invert_filter',
                                ]
                            );
                            $tmp->options .= '</a>';

                            // Options. Especial days.
                            $tmp->options .= '<a href="';
                            $tmp->options .= ui_get_full_url(
                                $this->url.'&op=special_days&tab=special_days&id='.$tmp->id
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

                            // Options. Delete.
                            $tmp->options .= '<a href="';
                            $tmp->options .= ui_get_full_url(
                                $this->url.'&op=delete&id='.$tmp->id
                            );
                            $tmp->options .= '">';
                            $tmp->options .= html_print_image(
                                'images/cross.png',
                                true,
                                [
                                    'title' => __('Delete'),
                                    'class' => 'invert_filter',
                                ]
                            );
                            $tmp->options .= '</a>';
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
        $id = (int) get_parameter('id');
        $display_range = (int) get_parameter('display_range', date('Y'));
        try {
            // Datatables offset, limit and order.
            $date = $display_range.'-'.date('m').'-1';
            $futureDate = date('Y-m-d', strtotime('+1 year', strtotime($date)));
            $filter = [];

            $filter['date'] = $date;
            $filter['futureDate'] = $futureDate;
            $filter['id_calendar'] = $id;
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
                $filter
            );

            hd($specialDays);
        } catch (\Exception $e) {
            if ($id > 0) {
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
                'ajax_url'    => $this->ajaxUrl,
                'url'         => $this->url.'&tab=special_days',
                'tabs'        => $this->getTabs('special_days'),
                'message'     => $this->message,
                'specialDays' => $specialDays,
                'id_calendar' => $id,
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
        $id = (int) get_parameter('id');
        $new = false;
        try {
            $specialDay = new SpecialDay($id);
            if ($id === 0) {
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
                $specialDay->name(get_parameter('name', null));
                $specialDay->id_group(get_parameter('id_group', null));
                $specialDay->description(get_parameter('description', null));

                // Save template.
                if ($specialDay->save() === true) {
                    $success = true;
                } else {
                    global $config;
                    $reason = \__(
                        'Failed saving special day: ',
                        $config['dbconnection']->error
                    );
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
                'ajax_url'   => $this->ajaxUrl,
                'url'        => $this->url.'&op=edit&tab=special_days',
                'tabs'       => $this->getTabs('special_days'),
                'specialDay' => $specialDay,
                'message'    => $this->message,
                'create'     => $new,
            ]
        );

        return false;
    }


}
