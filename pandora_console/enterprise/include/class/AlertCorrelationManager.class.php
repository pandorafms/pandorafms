<?php
/**
 * Alert Correlation.
 *
 * @category   Correlated Alerts Manager
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2019 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;

require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_io.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
enterprise_include_once('/include/lib/AlertCorrelation.class.php');


/**
 * Base class AlertCorrelationManager.
 */
class AlertCorrelationManager extends Wizard
{

    /**
     * Ajax controller page.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Page Wizard.
     *
     * @var integer
     */
    public $page;

    /**
     * Id alert.
     *
     * @var integer
     */
    public $id;

    /**
     * Alert Correlation object.
     *
     * @var alertCorrelation
     */
    public $aC;

    /**
     * Breadcrum titles.
     *
     * @var array
     */
    public $labels = [
        'Global alerts',
        'Configure',
        'Conditions',
        'Rules',
        'Fields',
        'Triggering',
    ];


    /**
     * Constructor.
     *
     * @param string $ajaxPage Page.
     */
    public function __construct(
        string $ajaxPage=ENTERPRISE_DIR.'/godmode/alerts/alert_correlation'
    ) {
        global $config;

        // Check access.
        check_login();

        $this->setBreadcrum([]);

        $this->help_url = [
            1 => 'alert_configure',
            2 => 'alert_configure',
            3 => 'alert_rules',
            4 => 'alert_fields',
            5 => 'alert_triggering',
        ];

        $this->page = (int) get_parameter('page', 0);

        $this->id = (int) get_parameter('id', 0);

        $this->access = 'LM';
        $this->ajaxController = $ajaxPage;
        $this->url = ui_get_full_url(
            'index.php?sec=galertas&sec2=enterprise/godmode/alerts/alert_correlation'
        );

        // Groups user access.
        $this->groups = array_keys(
            users_get_groups(
                $config['id_user'],
                'LM',
                true
            )
        );

        return $this;
    }


    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'addAlertActionForm',
        'addAlertAction',
        'deleteActionAlert',
        'addRowActionAjax',
        'standByAlert',
        'disabledAlert',
        'orderAlert',
        'createActionTableAjax',
    ];


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod(string $method):bool
    {
        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Show view Alert Correlation.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        // Include styles.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');
        ui_require_css_file('alert');

        if (empty($this->page) === true) {
            $this->viewAlertList();
        } else {
            $this->wizardAlert();
        }

        // Includes js files and document ready.
        echo $this->loadJS();

    }


    /**
     * Wizard alert.
     *
     * @return void
     */
    public function wizardAlert()
    {
        // Prepare header and breadcrums.
        $i = 0;
        $bc = [];
        $extra = '';

        if ($this->id !== null) {
            $extra = '&id='.$this->id;
        }

        foreach ($this->labels as $key => $label) {
            $bc[] = [
                'link'     => $this->url.(($i > 0) ? $extra.'&page='.$i : ''),
                'label'    => $label,
                'selected' => ($this->page == $key),
            ];

            $i++;
        }

        $this->prepareBreadcrum($bc);

        // Header.
        ui_print_page_header(
            $this->labels[$this->page],
            '',
            false,
            $this->help_url[$this->page],
            true,
            '',
            false,
            '',
            GENERIC_SIZE_TEXT,
            '',
            $this->printHeader(true)
        );

        if ($this->parse() === false) {
            // Header.
            ui_print_error_message(__($this->error));
            $this->printForm(
                [
                    'form'   => [
                        'method' => 'POST',
                        'action' => $this->url.'&page='.($this->page - 1).'&id='.$this->id,
                    ],
                    'inputs' => [
                        [
                            'arguments' => [
                                'name'       => 'submit',
                                'label'      => __('Go back'),
                                'type'       => 'submit',
                                'attributes' => 'class="sub cancel"',
                                'return'     => true,
                            ],
                        ],
                    ],
                ]
            );
            exit();
        }

        switch ($this->page) {
            case 1:
                echo $this->wizardAlertStep1();
            break;

            case 2:
                echo $this->wizardAlertStep2();
            break;

            case 3:
                echo $this->wizardAlertStep3();
            break;

            case 4:
                echo $this->wizardAlertStep4();
            break;

            case 5:
                echo $this->wizardAlertStep5();
            break;

            default:
                // Redirect users.
                $this->page = 1;
                echo $this->wizardAlertStep1();
            break;
        }

        $this->printForm(
            [
                'form'   => [
                    'method' => 'POST',
                    'action' => $this->url.'&page='.($this->page - 1).'&id='.$this->id,
                ],
                'inputs' => [
                    [
                        'arguments' => [
                            'name'       => 'submit',
                            'label'      => __('Go back'),
                            'type'       => 'submit',
                            'attributes' => 'class="sub cancel"',
                            'return'     => true,
                        ],
                    ],
                ],
            ]
        );

    }


    /**
     * Initialize agent correlation.
     *
     * @param string $values Values for instantiate object.
     *
     * @return void
     */
    public function getAlertCorrelationObject($values)
    {
        try {
            $this->aC = new AlertCorrelation($values);
        } catch (Exception $e) {
            $this->aC = null;
            $this->error = $e->getMessage();
        }
    }


    /**
     * Parse wizard form.
     *
     * @return boolean
     */
    public function parse()
    {
        global $config;

        if ($this->id !== 0) {
            // Retrieve RCMD object.
            $this->getAlertCorrelationObject($this->id);
            $this->aC->get();
        }

        if ($this->page === 1) {
            // Starting...
            return true;
        }

        if ($this->page === 2) {
            // Parse response from wizard, page 1.
            $name = get_parameter('name', '');
            $id_group = (int) get_parameter('id_group', 0);
            $description = get_parameter('description', '');
            $priority = get_parameter('priority', 1);

            if ($name === '' || $id_group === null) {
                if ((bool) $this->aC === false) {
                    // Accessing directly to item definition.
                    $this->error = __('Please follow the wizard.');
                    return false;
                }

                return true;
            } else {
                if (!check_acl(
                    $config['id_user'],
                    $this->access,
                    ($this->id === 0) ? $id_group : $this->aC->getIdGroup()
                )
                ) {
                    $this->error = __(
                        'You have no acess to edit this command.'
                    );
                    return false;
                }

                // Process update.
                $values = [
                    'name'        => $name,
                    'description' => $description,
                    'id_group'    => $id_group,
                    'priority'    => $priority,
                ];

                if ((bool) $this->aC === false) {
                    // Not defined yet. Create.
                    $this->getAlertCorrelationObject($values);
                    $result = $this->aC->set();
                } else {
                    // Update.
                    $result = $this->aC->put($values);
                }

                if (isset($result) === true
                    && is_array($result) === true
                    && $result['error'] === true
                ) {
                    $this->error = $result['msg_error'];
                    return false;
                } else {
                    $this->id = $this->aC->getId();

                    // Redirect to avoid double send.
                    $url = $this->url.'&page='.$this->page.'&id='.$this->id;
                    header(
                        'Location: '.$url
                    );

                    // Redirected. Success.
                    exit;
                }
            }
        }

        if ($this->page === 3) {
            if ((bool) $this->aC === false) {
                // Alert is not defined.
                $this->error = __('Alert not found.');
                return false;
            }

            $values = [
                'monday'         => get_parameter('days_week_mon', 0),
                'tuesday'        => get_parameter('days_week_tue', 0),
                'wednesday'      => get_parameter('days_week_wed', 0),
                'thursday'       => get_parameter('days_week_thu', 0),
                'friday'         => get_parameter('days_week_fri', 0),
                'saturday'       => get_parameter('days_week_sat', 0),
                'sunday'         => get_parameter('days_week_sun', 0),
                'special_days'   => get_parameter_switch('special_days', 0),
                'time_from'      => get_parameter('time_from', ''),
                'time_to'        => get_parameter('time_to', ''),
                'max_alerts'     => get_parameter('max_alerts', 0),
                'min_alerts'     => get_parameter('min_alerts', 0),
                'time_threshold' => get_parameter('threshold', 86400),
                'mode'           => get_parameter('evaluation', 'PASS'),
                'group_by'       => get_parameter('grouped', ''),
            ];

            if ($values['monday'] === 0
                && $values['tuesday'] === 0
                && $values['wednesday'] === 0
                && $values['thursday'] === 0
                && $values['friday'] === 0
                && $values['saturday'] === 0
                && $values['sunday'] === 0
                && $values['special_days'] === 0
                && $values['time_from'] === ''
                && $values['time_to'] === ''
                && $values['max_alerts'] === 0
                && $values['min_alerts'] === 0
                && $values['time_threshold'] === 86400
                && $values['mode'] === 'PASS'
                && $values['group_by'] === ''
            ) {
                // Default values. Check if user can edit this alert.
                return true;
            } else {
                // Update.
                $result = $this->aC->put($values);

                if (isset($result) === true
                    && is_array($result) === true
                    && $result['error'] === true
                ) {
                    $this->error = $result['msg_error'];
                    return false;
                } else {
                    return true;
                }
            }
        }

        if ($this->page === 4) {
            if ((bool) $this->aC === false) {
                // Alert is not defined.
                $this->error = __('Alert not found.');
                return false;
            }

            $rules = get_parameter('rule-stack', false);

            if ((bool) $rules !== false) {
                $rules = json_decode(
                    base64_decode($rules),
                    true
                );

                if (json_last_error() === JSON_ERROR_NONE) {
                    $rules = array_reduce(
                        $rules,
                        function ($carry, $item) {
                            $carry[$item['order']][] = io_safe_output($item);
                            return $carry;
                        }
                    );

                    if ($rules === null) {
                        $rules = [];
                    }

                    $this->aC->addRules($rules);

                    return true;
                } else {
                    $this->error = __('JSON decoding error. Please call support.');
                    $this->error .= json_last_error_msg();
                    return false;
                }
            } else {
                // No changes.
                return true;
            }
        }

        if ($this->page === 5) {
            if ((bool) $this->aC === false) {
                // Alert is not defined.
                $this->error = __('Alert not found.');
                return false;
            }

            $this->id_action = (int) \get_parameter('id_action', 0);
            $action = (int) \get_parameter('actions_alert');
            $fires_min = (int) \get_parameter('fires_min', 0);
            $fires_max = (int) \get_parameter('fires_max', 0);
            $threshold = (int) \get_parameter('threshold');

            // Delete Action.
            $delete_action = (int) \get_parameter('delete_action', 0);
            if ($delete_action === 1) {
                if ($this->id_action !== 0) {
                    $result = $this->aC->deleteActionAlert($this->id_action);

                    if (isset($result) === true
                        && is_array($result) === true
                        && $result['error'] === true
                    ) {
                        $this->error = $result['msg_error'];
                        return false;
                    } else {
                        // Restore value.
                        $this->id_action = 0;
                        return true;
                    }
                }

                return false;
            }

            // Create or update actions alerts.
            $add_action = \get_parameter('create-action', '');
            if (empty($add_action) === false) {
                if (isset($action) === false
                    || empty($action) === true
                ) {
                    return false;
                }

                $values = [];
                $values['fires_min'] = $fires_min;
                $values['fires_max'] = $fires_max;
                $values['module_action_threshold'] = $threshold;

                $result = $this->aC->setActionAlert($action, $values);
                $values['id_alert_action'] = $action;

                if (isset($result) === true
                    && is_array($result) === true
                    && $result['error'] === true
                ) {
                    $this->error = $result['msg_error'];
                    return false;
                } else {
                    return true;
                }

                return true;
            }

            $update_action = \get_parameter('update-action', '');
            if (empty($update_action) === false) {
                if (isset($action) === false
                    || empty($action) === true
                    || isset($this->id_action) === false
                    || empty($this->id_action) === true
                ) {
                    return false;
                }

                $values = [];
                $values['fires_min'] = $fires_min;
                $values['fires_max'] = $fires_max;
                $values['module_action_threshold'] = $threshold;
                $values['id_alert_action'] = $action;

                $result = $this->aC->putActionAlert($this->id_action, $values);

                if (isset($result) === true
                    && is_array($result) === true
                    && $result['error'] === true
                ) {
                    $this->error = $result['msg_error'];
                    return false;
                } else {
                    // Restore value.
                    $this->id_action = 0;
                    return true;
                }

                return true;
            }

            $values = [];
            for ($i = 1; $i <= 10; $i++) {
                $field = get_parameter('field'.$i, '');

                if ($field !== '') {
                    $values['field'.$i] = $field;
                }
            }

            if (count($values) > 0) {
                // Update.
                $result = $this->aC->put($values);

                if (isset($result) === true
                    && is_array($result) === true
                    && $result['error'] === true
                ) {
                    $this->error = $result['msg_error'];
                    return false;
                } else {
                    return true;
                }
            }

            return true;
        }

        $this->error = __('Page not found');
        return false;
    }


    /**
     * Wizard configuration.
     *
     * @return string
     */
    public function wizardAlertStep1():string
    {
        // Initialize vars.
        $name = '';
        $group = 0;
        $description = '';
        $priority = 1;
        if ($this->id !== 0) {
            // Retrieve RCMD object.
            $name = $this->aC->getName();
            $group = $this->aC->getIdGroup();
            $description = $this->aC->getDescription();
            $priority = $this->aC->getPriority();
        }

        $form = [
            'action' => $this->url.'&page=2&id='.$this->id,
            'method' => 'POST',
            'id'     => 'general_filters_alert',
        ];

        $inputs = [
            [
                'block_id'      => 'name-group',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Name'),
                        'arguments' => [
                            'type'     => 'text',
                            'id'       => 'name',
                            'name'     => 'name',
                            'value'    => $name,
                            'return'   => true,
                            'required' => true,
                        ],

                    ],
                    [
                        'label'     => __('Group'),
                        'arguments' => [
                            'type'     => 'select',
                            'id'       => 'id_group',
                            'name'     => 'id_group',
                            'fields'   => users_get_groups_for_select(
                                $config['id_user'],
                                'AR',
                                true,
                                true,
                                false
                            ),
                            'selected' => $group,
                        ],
                    ],
                ],

            ],
            [
                'block_id'      => 'description',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Description'),
                        'arguments' => [
                            'name'    => 'description',
                            'type'    => 'textarea',
                            'value'   => $description,
                            'return'  => true,
                            'rows'    => 1,
                            'columns' => 1,
                            'size'    => 25,
                        ],
                    ],
                ],

            ],
            [
                'block_id'      => 'priority',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Priority'),
                        'class'     => 'flex-item',
                        'arguments' => [
                            'type'     => 'select',
                            'id'       => 'priority',
                            'name'     => 'priority',
                            'fields'   => \get_priorities(),
                            'selected' => $priority,
                        ],
                    ],
                ],

            ],
            [
                'arguments' => [
                    'name'       => 'submit',
                    'label'      => __('Next'),
                    'type'       => 'submit',
                    'attributes' => 'class="sub next"',
                    'return'     => true,
                    'width'      => 'auto',
                ],
            ],
        ];

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true,
            true
        );

        return $output;
    }


    /**
     * Wizard condition.
     *
     * @return string
     */
    public function wizardAlertStep2():string
    {
        $id_template = 0;

        // Initialize vars.
        $monday = 1;
        $tuesday = 1;
        $wednesday = 1;
        $thursday = 1;
        $friday = 1;
        $saturday = 1;
        $sunday = 1;
        $special_days = 0;
        $time_from = '';
        $time_to  = '';
        $max_alerts = 0;
        $min_alerts = 0;
        $time_threshold = 86400;
        $mode = 'PASS';
        $group_by = '';
        if ($this->id !== 0) {
            // Retrieve RCMD object.
            $monday = $this->aC->getMonday();
            $tuesday = $this->aC->getTuesday();
            $wednesday = $this->aC->getWednesday();
            $thursday = $this->aC->getThursday();
            $friday = $this->aC->getFriday();
            $saturday = $this->aC->getSaturday();
            $sunday = $this->aC->getSunday();
            $special_days = $this->aC->getSpecialDays();
            $time_from = $this->aC->getTimeFrom();
            $time_to = $this->aC->getTimeTo();
            $max_alerts = $this->aC->getMaxAlerts();
            $min_alerts = $this->aC->getMinAlerts();
            $time_threshold = $this->aC->getTimeThreshold();
            $mode = $this->aC->getMode();
            $group_by = $this->aC->getGroupBy();
        }

        // Arrays for select.
        $templates = array_reduce(
            \alerts_get_alert_templates(false, ['id', 'name']),
            function ($carry, $item) {
                $carry[$item['id']] = $item['name'];
                return $carry;
            },
            []
        );
        $templates[0] = __('None');

        $evaluations = [
            'PASS' => __('Pass'),
            'DROP' => __('Drop'),
        ];

        $groupeds = [
            ''               => __('None'),
            'id_agente'      => __('Agent'),
            'id_agentmodule' => __('Module'),
            'id_alert_am'    => __('Module alert'),
            'id_grupo'       => __('Group'),
        ];

        // FORM.
        $form = [
            'action' => $this->url.'&page=3&id='.$this->id,
            'method' => 'POST',
            'id'     => 'conditions_filters_alert',
            'extra'  => 'novalidate',

        ];

        // TODO: Load from template.
        $inputs = [
        // [
        // 'block_id'      => 'load-template',
        // 'class'         => 'flex-row flex-start w100p',
        // 'direct'        => 1,
        // 'block_content' => [
        // [
        // 'label'     => __('Load from template'),
        // 'arguments' => [
        // 'type'     => 'select',
        // 'id'       => 'template',
        // 'name'     => 'template',
        // 'fields'   => $templates,
        // 'selected' => $id_template,
        // ],
        // ],
        // ],
        // ],
            [
                'block_id'      => 'days-week',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Days a week'),
                        'arguments' => [
                            'type'    => 'multicheck',
                            'id'      => 'days_week',
                            'name'    => 'days_week',
                            'data'    => [
                                'mon' => __('Mon'),
                                'tue' => __('Tue'),
                                'wed' => __('Wed'),
                                'thu' => __('Thu'),
                                'fri' => __('Fri'),
                                'sat' => __('Sat'),
                                'sun' => __('Sun'),
                            ],
                            'checked' => [
                                'mon' => $monday,
                                'tue' => $tuesday,
                                'wed' => $wednesday,
                                'thu' => $thursday,
                                'fri' => $friday,
                                'sat' => $saturday,
                                'sun' => $sunday,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'block_id'      => 'special-days-list',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Use special days list'),
                        'arguments' => [
                            'name'  => 'special_days',
                            'id'    => 'special-days',
                            'type'  => 'switch',
                            'value' => $special_days,
                        ],
                    ],
                ],
            ],
            [
                'block_id'      => 'time-from-to',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label' => __('Time'),
                    ],
                    [
                        'label'     => __('from'),
                        'arguments' => [
                            'name'  => 'time_from',
                            'id'    => 'time_from',
                            'type'  => 'text',
                            'value' => $time_from,
                            'size'  => 20,
                        ],
                    ],
                    [
                        'label'     => __('to'),
                        'arguments' => [
                            'name'  => 'time_to',
                            'id'    => 'time_to',
                            'type'  => 'text',
                            'value' => $time_to,
                            'size'  => 20,
                        ],
                    ],
                ],
            ],
            [
                'block_id'      => 'from-to-threshold',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label' => __('Execute alert'),
                    ],
                    [
                        'label'     => __('from'),
                        'arguments' => [
                            'name'  => 'min_alerts',
                            'id'    => 'min_alerts',
                            'type'  => 'number',
                            'value' => $min_alerts,
                            'size'  => 5,
                        ],
                    ],
                    [
                        'label'     => __('to'),
                        'arguments' => [
                            'name'  => 'max_alerts',
                            'id'    => 'max_alerts',
                            'type'  => 'number',
                            'value' => $max_alerts,
                            'size'  => 5,
                        ],
                    ],
                    [
                        'label'     => __('times in'),
                        'arguments' => [
                            'name'          => 'threshold',
                            'type'          => 'interval',
                            'value'         => $time_threshold,
                            'nothing'       => __('None'),
                            'nothing_value' => 0,
                        ],
                    ],
                    [
                        'label' => __('threshold').'.',
                    ],
                ],
            ],
            [
                'block_id'      => 'evaluation',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Rule evaluation mode'),
                        'arguments' => [
                            'type'     => 'select',
                            'id'       => 'evaluation',
                            'name'     => 'evaluation',
                            'fields'   => $evaluations,
                            'selected' => $mode,
                        ],
                    ],
                ],
            ],
            [
                'block_id'      => 'grouped',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Grouped by'),
                        'arguments' => [
                            'type'     => 'select',
                            'id'       => 'grouped',
                            'name'     => 'grouped',
                            'fields'   => $groupeds,
                            'selected' => $group_by,
                        ],
                    ],
                ],
            ],
            [
                'arguments' => [
                    'name'       => 'submit',
                    'label'      => __('Next'),
                    'type'       => 'submit',
                    'attributes' => 'class="sub next"',
                    'return'     => true,
                    'width'      => 'auto',
                ],
            ],
        ];

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true,
            true
        );

        return $output;
    }


    /**
     * Wizard Rules.
     *
     * @return string
     */
    public function wizardAlertStep3():string
    {
        global $config;

        $output = '<div class="white_box">';
        $output .= '<div class="flex-row">';
        $output .= '<ul class="sample">';
        $output .= '<li><span class="rule-title">'.__('Available items').'</span></li>';
        $output .= '<li class="flex-row"><label>'.__('Block').':</label>';
        $output .= '<div class="content">';

        $nexos = [
            [
                'title' => '(',
                'id'    => 'block-start',
            ],
            [
                'title' => ')',
                'id'    => 'block-end',
            ],
        ];

        foreach ($nexos as $key => $value) {
            // Attributes div.
            $attributes = 'draggable="true"';
            $attributes .= 'id="'.$value['id'].'"';
            $attributes .= 'ondragstart="drag(event)"';
            if ($value['id'] == 'block-end') {
                $attributes .= 'class="blocks block field opacityElements"';
            } else {
                $attributes .= 'class="blocks block field"';
            }

            $output .= '<div '.$attributes.'>';
            $output .= $value['title'];
            $output .= '</div>';
        }

        $output .= '</div>';

        $output .= '</li>';

        $output .= '<li class="flex-row">';
        $output .= '<label>';
        $output .= __('Fields').':';
        $output .= '</label>';
        $output .= '<div class="content">';

        $fields = [];

        if (is_metaconsole() !== true) {
            // Elasticsearch is not configured yet in Metaconsole.
            // We can initialize backend using multiples nodes, but
            // we are not fully sure how system will behave.
            $fields = [
                [
                    'title' => __('Log content'),
                    'type'  => 'log',
                    'id'    => 'fields-log-content',
                ],
                [
                    'title' => __('Log source'),
                    'type'  => 'log',
                    'id'    => 'fields-log-source',
                ],
                [
                    'title' => __('Log agent'),
                    'type'  => 'log',
                    'id'    => 'fields-log-agent',
                ],
            ];
        }

        // Groups for variable selections. Keep 'grp' for reverse resolution.
        $grp = users_get_groups_for_select(
            $config['id_user'],
            'AR',
            true,
            true,
            false
        );

        $groups = [];
        foreach ($grp as $k => $v) {
            $groups[] = [
                'id'    => $k,
                'title' => io_safe_output($v),
            ];
        }

        // Severities available for variable selection.
        $svr = get_priorities();
        $severity = [];
        foreach ($svr as $k => $v) {
            $severity[] = [
                'id'    => $k,
                'title' => io_safe_output($v),
            ];
        }

        // Event types.
        $evt_types = AlertCorrelation::getEventTypes();
        $event_types = [];
        foreach ($evt_types as $k => $v) {
            $event_types[] = [
                'id'    => $k,
                'title' => __($v),
            ];
        }

        // Tags names.
        $select_tags = tags_search_tag(false, false, true);
        $tags_types = [];
        foreach ($select_tags as $k => $v) {
            $tags_types[] = [
                'id'    => $k,
                'title' => __($v),
            ];
        }

        $fields = array_merge(
            $fields,
            [
                [
                    'title' => __('Event content'),
                    'type'  => 'event',
                    'id'    => 'fields-event-content',
                ],
                [
                    'title' => __('Event user comment'),
                    'type'  => 'event',
                    'id'    => 'fields-event-user-comment',
                ],
                [
                    'title' => __('Event agent'),
                    'type'  => 'event',
                    'id'    => 'fields-event-agent',
                ],
                [
                    'title' => __('Event module'),
                    'type'  => 'event',
                    'id'    => 'fields-event-module',
                ],
                [
                    'title' => __('Event module alerts'),
                    'type'  => 'event',
                    'id'    => 'fields-module-alerts',
                ],
                [
                    'title' => __('Event group'),
                    'type'  => 'event',
                    'id'    => 'fields-event-group',
                ],
                [
                    'title' => __('Event group Recursive'),
                    'type'  => 'event',
                    'id'    => 'fields-event-group-recursive',
                ],
                [
                    'title' => __('Event severity'),
                    'type'  => 'event',
                    'id'    => 'fields-event-severity',
                ],
                [
                    'title' => __('Event tag'),
                    'type'  => 'event',
                    'id'    => 'fields-event-tag',
                ],
                [
                    'title' => __('Event user'),
                    'type'  => 'event',
                    'id'    => 'fields-event-user',
                ],
                [
                    'title' => __('Event type'),
                    'type'  => 'event',
                    'id'    => 'fields-event-type',
                ],
            ]
        );

        foreach ($fields as $key => $value) {
            // Attributes div.
            $attributes = 'draggable="true"';
            $attributes .= 'id="'.$value['id'].'"';
            $attributes .= 'ondragstart="drag(event)"';
            $attributes .= 'class="fields field '.$value['type'].' opacityElements"';

            $output .= '<div '.$attributes.'>';
            $output .= $value['title'];
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</li>';

        $output .= '<li class="flex-row">';
        $output .= '<label>';
        $output .= __('Operators').':';
        $output .= '</label>';

        $operators = [
            [
                'title' => '&gt;',
                'id'    => 'operators-greater-than',
            ],
            [
                'title' => '&lt;',
                'id'    => 'operators-smaller than',
            ],
            [
                'title' => '&gt;=',
                'id'    => 'operators-greater-than-equal',
            ],
            [
                'title' => '&lt;=',
                'id'    => 'operators-greater-smaller-equal',
            ],
            [
                'title' => '==',
                'id'    => 'operators-equal',
            ],
            [
                'title' => '!=',
                'id'    => 'operators-different',
            ],
            [
                'title' => 'REGEX',
                'id'    => 'operators-contains',
            ],
            [
                'title' => 'NOT REGEX',
                'id'    => 'operators-not-contains',
            ],
        ];

        $output .= '<div class="content">';

        foreach ($operators as $key => $value) {
            // Attributes div.
            $attributes = 'draggable="false"';
            $attributes .= 'id="'.$value['id'].'"';
            $attributes .= 'ondragstart="drag(event)"';
            $attributes .= 'class="operators field opacityElements"';

            $output .= '<div '.$attributes.'>';
            $output .= $value['title'];
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</li>';

        $output .= '<li class="flex-row">';
        $output .= '<label>';
        $output .= __('Variables');
        $output .= '</label>';
        $output .= '<div class="content">';

        // Attributes div.
        $attributes = 'id="variable-text"';

        $attributes .= 'draggable="false"';
        $attributes .= 'ondragstart="drag(event)"';
        $attributes .= 'class="variables variable field opacityElements"';

        $output .= '<div '.$attributes.'>';
        $output .= __('Double click to assign value');
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</li>';

        $output .= '<li class="flex-row">';
        $output .= '<label>';
        $output .= __('Modifiers').':';
        $output .= '</label>';

        $modifiers = [
            [
                'title' => __('Time window'),
                'id'    => 'modifier-time-window',
            ],
            [
                'title' => __('Count'),
                'id'    => 'modifier-count',
            ],
        ];

        $output .= '<div class="content">';
        foreach ($modifiers as $key => $value) {
            // Attributes div.
            $attributes = 'draggable="false"';
            $attributes .= 'id="'.$value['id'].'"';
            $attributes .= 'ondragstart="drag(event)"';
            $attributes .= 'class="modifiers modifier field opacityElements"';

            $output .= '<div '.$attributes.'>';
            $output .= $value['title'];
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</li>';

        $output .= '<li class="flex-row"><label>Nexos:</label>';
        $output .= '<div class="content">';

        $nexos = [
            [
                'title' => 'AND',
                'id'    => 'nexo-and',
            ],
            [
                'title' => 'NAND',
                'id'    => 'nexo-nand',
            ],
            [
                'title' => 'OR',
                'id'    => 'nexo-or',
            ],
            [
                'title' => 'NOR',
                'id'    => 'nexo-nor',
            ],
            [
                'title' => 'XOR',
                'id'    => 'nexo-xor',
            ],
            [
                'title' => 'NXOR',
                'id'    => 'nexo-nxor',
            ],
        ];

        foreach ($nexos as $key => $value) {
            // Attributes div.
            $attributes = 'draggable="false"';
            $attributes .= 'id="'.$value['id'].'"';
            $attributes .= 'ondragstart="drag(event)"';
            $attributes .= 'class="nexos nexo field opacityElements"';

            $output .= '<div '.$attributes.'>';
            $output .= $value['title'];
            $output .= '</div>';
        }

        $output .= '</div>';

        $output .= '</li>';
        $output .= '</ul>';

        $stack = $this->aC->getRuleStack();

        $output .= '<div class="target">';
        $output .= '<span class="rule-title">'.__('Rule definition').'</span>';
        $output .= '<div id="rules" class="target flex" ondrop="drop(event)" ondragover="allowDrop(event)">';

        $i = 0;
        $current_block_order = 0;
        if (is_array($stack) === true) {
            foreach ($stack as $item) {
                // First nexo on a rule is global nexus.
                if ($item['order'] > 0
                    && $item['type'] == 'nexos'
                    && ($i++) === 0
                ) {
                    // We start block with NEXOS after first rule.
                    $output .= '<div id="'.$item['order'].'"';
                    $output .= ' class="div_parent target flex"';
                    $output .= ' name="div_parent">';
                } else if ($i === 0
                    && $item['id'] == 'block-start'
                ) {
                    $output .= '<div id="'.$item['order'].'"';
                    $output .= ' class="div_parent target flex"';
                    $output .= ' name="div_parent">';
                }

                // Calculate class.
                $class = $this->aC->extractClassItem($item).' rule_'.$item['order'];
                // Attributes div.
                $attributes = 'draggable="false"';

                $text = io_safe_output($item['value']);
                if ($item['type'] == 'fields') {
                    $text = $this->aC->getFieldText($item['value']);
                }

                /*
                 * Update attributes.
                 */

                $attributes .= ' id="'.$item['var_id'].'"';
                $add_action = 1;
                if ($item['type'] == 'variables') {
                    /*
                     * VARIABLES.
                     */

                    $attributes .= ' var_id="'.$item['var_id'].'"';
                    $text = str_replace(' ', '&nbsp;', $text);
                    $text = str_replace("\n", '<br />', $text);

                    // Set ID to ensure edition.
                    if ($latest['type'] == 'modifiers') {
                        // Variable FOR modifier.
                        $attributes .= ' id="'.strtolower($latest['value']);
                        $attributes .= '-'.$item['order'].'"';
                    } else {
                        // Variable FOR field.
                        $attributes .= ' id="'.$prev['value'];
                        $attributes .= '-'.$item['order'].'"';

                        if ($prev['value'] === 'fields-event-group'
                            || $prev['value'] === 'fields-event-group-recursive'
                        ) {
                            // Variable FOR group.
                            $text = io_safe_output($grp[$text]);
                        } else if ($prev['value'] === 'fields-event-severity') {
                            // Variable FOR severity.
                            $text = io_safe_output($svr[$text]);
                            if ($text === '') {
                                $text = __('Any');
                            }
                        } else if ($prev['value'] === 'fields-event-type') {
                            $text = __($evt_types[$text]);
                        } else if ($prev['value'] === 'fields-event-tag') {
                            $text = __($select_tags[$text]);
                        }
                    }
                } else if ($item['type'] == 'nexos' && $i > 0) {
                    // Only 'global' nexo could be updated.
                    // Blocks internal ones are always 'AND'.
                    $add_action = 0;
                } else {
                    // Avoid text select.
                    $class .= ' noselect';
                }

                $attributes .= ' class="'.$class.'" order='.$item['order'].' ';

                // Fire edit 'mode'.
                if ($add_action === 1) {
                    $attributes .= ' ondblclick="editMe(this,\''.$item['type'].'\');"';
                }

                if ($item['type'] == 'nexos') {
                    $output .= '<br />';
                }

                $output .= '<span><div '.$attributes.'>';
                $output .= $text;
                $output .= '</div></span>';

                if ($item['id'] == 'block-end') {
                    // Close div_parent (rule order).
                    $output .= '</div>';
                    $current_block_order = $item['order'];
                    $i = 0;
                }

                $prev = $latest;
                $latest = $item;
            }
        } else {
            $stack = [];
        }

        // Rules.
        $output .= '</div>';

        // Rule with title.
        $output .= '</div>';

        // Global.
        $output .= '</div>';

        $output .= '<div id="rules_undo" style="display:none;"></div>';

        $form = [
            'action' => $this->url.'&page=4&id='.$this->id,
            'method' => 'POST',
            'id'     => 'rule-builder',
        ];

        $output .= '</div>';

        $agents = agents_get_agents();
        $agents_select = [];
        foreach ($agents as $agent => $value) {
            $agents_select[$agent] = array_merge(
                $agents_select,
                [
                    'id'    => $value['id_agente'],
                    'title' => io_safe_output($value['nombre']),
                ]
            );
        }

        $modules = db_get_all_rows_sql('select * from tmodule');
        $modules_select = [];
        foreach ($modules as $module => $value) {
            $modules_select[$module] = array_merge(
                $modules_select,
                [
                    'id'    => $value['id_module'],
                    'title' => io_safe_output($value['name']),
                ]
            );
        }

        $inputs = [
            [
                'arguments' => [
                    'type'   => 'hidden_extended',
                    'name'   => 'rule-stack',
                    'id'     => 'rule-stack',
                    'value'  => base64_encode(json_encode($stack)),
                    'quotes' => true,
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'fields-select-content',
                    'value' => base64_encode(json_encode($fields)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'groups-select-content',
                    'value' => base64_encode(json_encode($groups)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'severity-select-content',
                    'value' => base64_encode(json_encode($severity)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'event-types-select-content',
                    'value' => base64_encode(json_encode($event_types)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'tags-types-select-content',
                    'value' => base64_encode(json_encode($tags_types)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'agents-select-content',
                    'value' => base64_encode(json_encode($agents_select)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'modules-select-content',
                    'value' => base64_encode(json_encode($modules_select)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'modules-alerts-select-content',
                    'value' => base64_encode(json_encode($event_modules_alerts)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'event_user-select-content',
                    'value' => base64_encode(json_encode($event_user)),
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'block-status',
                    'value' => '0',
                ],
            ],
            [
                'arguments' => [
                    'type'  => 'hidden_extended',
                    'id'    => 'block-order',
                    'value' => $current_block_order,
                ],
            ],
            [
                'class'         => 'action-buttons rule-builder-actions',
                'direct'        => false,
                'wrapper'       => 'div',
                'block_content' => [
                    [
                        'class'     => 'margin-right-2',
                        'arguments' => [
                            'name'       => 'removelast',
                            'label'      => __('Remove rule'),
                            'type'       => 'button',
                            'attributes' => 'class="sub delete"',
                            'return'     => true,
                            'script'     => 'removeLastRule()',
                        ],
                    ],
                    [
                        'class'     => 'margin-right-2',
                        'arguments' => [
                            'name'       => 'removelastitem',
                            'label'      => __('Remove item'),
                            'type'       => 'button',
                            'attributes' => 'class="sub delete"',
                            'return'     => true,
                            'script'     => 'removeLast(event)',
                        ],
                    ],
                    [
                        'class'     => 'margin-right-2',
                        'arguments' => [
                            'name'       => 'cleanup',
                            'label'      => __('Cleanup'),
                            'type'       => 'button',
                            'attributes' => 'class="sub delete"',
                            'return'     => true,
                            'script'     => 'paneCleanup()',
                        ],
                    ],
                    [
                        'arguments' => [
                            'name'       => 'undo',
                            'label'      => __('Reset'),
                            'type'       => 'button',
                            'attributes' => 'class="sub upd"',
                            'return'     => true,
                            'script'     => 'location.reload();',
                        ],
                    ],
                ],
            ],
            [
                'arguments' => [
                    'name'       => 'rule',
                    'label'      => __('Next'),
                    'type'       => 'submit',
                    'attributes' => 'class="sub next"',
                    'return'     => true,
                ],
            ],
        ];

        $output .= $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

        return $output;
    }


    /**
     * Wizard Actions.
     *
     * @return string
     */
    public function wizardAlertStep4():string
    {
        $fields = [];

        $form = [
            'action' => $this->url.'&page=5&id='.$this->id,
            'method' => 'POST',
            'id'     => 'advanced_filters_alert',
        ];

        for ($i = 1; $i <= 10; $i++) {
            if ($this->id !== 0) {
                $fields[$i] = $this->aC->{'getField'.$i}();
            }

            $inputs[] = [
                'block_id'      => 'field-'.$i,
                'class'         => 'flex-row-vcenter flex-space-around w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Field %s', $i).ui_print_help_icon('alert_macros', true),
                        'arguments' => [
                            'name'    => 'field'.$i,
                            'type'    => 'textarea',
                            'value'   => (isset($fields[$i]) === true) ? $fields[$i] : '',
                            'return'  => true,
                            'rows'    => 1,
                            'columns' => 1,
                            'size'    => 25,
                        ],
                    ],
                ],
            ];
        }

        $inputs[] = [
            'arguments' => [
                'name'       => 'submit',
                'label'      => __('Next'),
                'type'       => 'submit',
                'attributes' => 'class="sub next"',
                'return'     => true,
                'width'      => 'auto',
            ],
        ];

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true,
            true
        );

        return $output;
    }


    /**
     * Wizard triggering.
     *
     * @return string
     */
    public function wizardAlertStep5():string
    {
        $fields = [];
        if ($this->id < 0) {
            exit();
        }

        $mode = $this->aC->getMode();
        $group_by = $this->aC->getGroupBy();

        // Info Alert.
        $info_alert = '<div class="info-container">';
        $info_alert .= '<div class="info-item info-days">';
        $info_alert .= $this->getTableDaysAlert();
        $info_alert .= '</div>';
        $info_alert .= '<div class="info-item">';
        $info_alert .= $this->getTableTimeAlert();
        $info_alert .= '</div>';
        $info_alert .= '</div>';
        $info_alert .= '<div class="info-container">';
        $info_alert .= $this->getTableConditionsAlert();
        $info_alert .= '</div>';

        $output .= ui_print_toggle(
            [
                'name'           => __('Triggering Condition'),
                'content'        => $info_alert,
                'title'          => __('Triggering Condition'),
                'hidden_default' => false,
            ]
        );

        $output .= $this->formActionAlerts();

        $output .= $this->listActionAlerts();

        $output .= $this->showActionAlerts();

        $attr = 'class="mode_table mode_table_firing action_details"';
        $output .= '<div '.$attr.'></div>';

        $form = [
            'action' => $this->url.'&page=0',
            'method' => 'POST',
            'id'     => 'actions_filters_alert',
        ];

        $inputs[] = [
            'arguments' => [
                'name'       => 'submit',
                'label'      => __('Finish'),
                'type'       => 'submit',
                'attributes' => 'class="sub next"',
                'return'     => true,
                'width'      => 'auto',
            ],
        ];

        $output .= $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

        return $output;
    }


    /**
     * Print Info days Correlation alerts.
     *
     * @return string
     */
    public function getTableDaysAlert():string
    {
        $monday = $this->aC->getMonday();
        $tuesday = $this->aC->getTuesday();
        $wednesday = $this->aC->getWednesday();
        $thursday = $this->aC->getThursday();
        $friday = $this->aC->getFriday();
        $saturday = $this->aC->getSaturday();
        $sunday = $this->aC->getSunday();

        $table_days = new stdClass();
        $table_days->width = '100%';
        $table_days->class = 'info_table';
        $table_days->data = [];
        $table_days->styleTable = 'text-align: center;';
        $table_days->head[0] = __('Mon');
        $table_days->head[1] = __('Tue');
        $table_days->head[2] = __('Wed');
        $table_days->head[3] = __('Thu');
        $table_days->head[4] = __('Fri');
        $table_days->head[5] = __('Sat');
        $table_days->head[6] = __('Sun');

        $table_days->data[0] = array_fill(
            0,
            7,
            html_print_image('images/blade.png', true)
        );

        $days = [];
        if ($monday === 1) {
            $table_days->data[0][0] = html_print_image('images/tick.png', true);
        }

        if ($tuesday === 1) {
            $table_days->data[0][1] = html_print_image('images/tick.png', true);
        }

        if ($wednesday === 1) {
            $table_days->data[0][2] = html_print_image('images/tick.png', true);
        }

        if ($thursday === 1) {
            $table_days->data[0][3] = html_print_image('images/tick.png', true);
        }

        if ($friday === 1) {
            $table_days->data[0][4] = html_print_image('images/tick.png', true);
        }

        if ($saturday === 1) {
            $table_days->data[0][5] = html_print_image('images/tick.png', true);
        }

        if ($sunday === 1) {
            $table_days->data[0][6] = html_print_image('images/tick.png', true);
        }

        return \html_print_table($table_days, true);
    }


    /**
     * Print Time Correlation alerts.
     *
     * @return string
     */
    public function getTableTimeAlert():string
    {
        $time_from = $this->aC->getTimeFrom();
        $time_to = $this->aC->getTimeTo();

        $table_time = new stdClass();
        $table_time->width = '100%';
        $table_time->class = 'info_table';
        $table_time->styleTable = 'text-align: center;';
        $table_time->data = [];

        if ($time_from === $time_to) {
            $table_time->head[0] = '00:00:00 - 23:59:59';
            $table_time->data[0][0] = html_print_image(
                'images/tick.png',
                true
            );
        } else {
            $from_array = explode(':', $time_from);
            $hours = ($from_array[0] * SECONDS_1HOUR);
            $minutes = ($from_array[1] * SECONDS_1MINUTE);
            $from = ($hours + $minutes + $from_array[2]);
            $to_array = explode(':', $time_to);
            $hours_to = ($to_array[0] * SECONDS_1HOUR);
            $minutes_to = ($to_array[1] * SECONDS_1MINUTE);
            $to = ($hours_to + $minutes_to + $to_array[2]);
            if ($to > $from) {
                if ($time_from !== '00:00:00') {
                    $table_time->head[0] = '00:00:00 - '.$time_from;
                    $table_time->data[0][0] = html_print_image(
                        'images/blade.png',
                        true
                    );
                }

                $table_time->head[1] = $time_from.' - '.$time_to;
                $table_time->data[0][1] = html_print_image(
                    'images/tick.png',
                    true
                );

                if ($time_to !== '23:59:59') {
                    $table_time->head[2] = $time_to.' - 23:59:59';
                    $table_time->data[0][2] = html_print_image(
                        'images/blade.png',
                        true
                    );
                }
            } else {
                if ($time_to !== '00:00:00') {
                    $table_time->head[0] = '00:00:00 - '.$time_to;
                    $table_time->data[0][0] = html_print_image(
                        'images/tick.png',
                        true
                    );
                }

                $table_time->head[1] = $time_to.' - '.$time_from;
                $table_time->data[0][1] = html_print_image(
                    'images/blade.png',
                    true
                );

                if ($time_from !== '23:59:59') {
                    $table_time->head[2] = $time_from.' - 23:59:59';
                    $table_time->data[0][2] = html_print_image(
                        'images/tick.png',
                        true
                    );
                }
            }

            $data[1] = $time_from.' / '.$time_to;
        }

        return \html_print_table($table_time, true);
    }


    /**
     * Table info conditions Alerts.
     *
     * @return string
     */
    public function getTableConditionsAlert():string
    {
        $special_days = $this->aC->getSpecialDays();
        $max_alerts = $this->aC->getMaxAlerts();
        $min_alerts = $this->aC->getMinAlerts();
        $time_threshold = $this->aC->getTimeThreshold();

        $table_conditions = new stdClass();
        $table_conditions->width = '95%';
        $table_conditions->class = 'no-class';
        $table_conditions->data = [];
        $table_conditions->size = [];
        $table_conditions->size[0] = '30%';
        $table_conditions->data[] = $data;

        $data[0] = '<b>'.__('Use special days list').'</b>';
        $data[1] = (isset($special_days) && $special_days === 1) ? __('Yes') : __('No');
        $table_conditions->data[] = $data;

        $data[0] = '<b>'.__('Time threshold').'</b>';
        $data[1] = human_time_description_raw($time_threshold, true);
        $table_conditions->data[] = $data;

        $data[0] = '<b>';
        $data[0] .= __('Number of alerts').' ('.__('Min').'/'.__('Max').')';
        $data[0] .= '</b>';
        $data[1] = $min_alerts.'/'.$max_alerts;
        $table_conditions->data[] = $data;

        return \html_print_table($table_conditions, true);
    }


    /**
     * Send form create actions AJAX.
     *
     * @return string.
     */
    public function formActionAlerts():string
    {
        $form = [
            'action' => $this->url.'&page=5&id='.$this->id,
            'method' => 'POST',
            'id'     => 'add_actions_alerts',
        ];

        $filter_groups = implode(',', $this->groups);

        $actions = $this->aC->getAlertsActions(
            'id_group IN ('.$filter_groups.')',
            true
        );

        $inputs = $this->inputsActionAlerts($actions);

        $inputs[] = [
            'arguments' => [
                'name'       => ($this->id_action === 0) ? 'create-action' : 'update-action',
                'label'      => ($this->id_action === 0) ? __('Add') : __('Update'),
                'type'       => 'submit',
                'attributes' => 'class="sub next"',
                'return'     => true,
                'width'      => 'auto',
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'name'  => 'id_action',
                'type'  => 'hidden',
                'value' => $this->id_action,
            ],
        ];

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true,
            true
        );

        return $output;

    }


    /**
     * Print table actions.
     *
     * @return string
     */
    public function listActionAlerts():string
    {
        // Extract actions info for alert.
        $actions = AlertCorrelation::getActionsAlert($this->id);

        // Initialize table.
        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'info_table';
        $table->id = 'table-actions';

        // Size.
        $table->size = [];

        // Head.
        $table->head = [];
        $table->head[0] = '<b>'.__('Actions').'</b>';
        $table->head[1] = '<b>'.__('Firing').'</b>';

        $table->head[2] = '<b>'.__('Treshold').'</b>';
        $table->head[3] = '<b>'.__('Opions').'</b>';

        // Data.
        $table->data = [];
        if (empty($actions) === false) {
            foreach ($actions as $action) {
                $url = $this->url.'&page=5&id='.$this->id;
                $url .= '&id_action='.$action['id'];
                // Name action with link.
                $data[0] = '<a href="'.$url.'">';
                $data[0] .= $action['name'];
                $data[0] .= '</a>';

                // Check do not exceed limit defined in alert.
                if ($action['fires_min'] < $this->aC->getMinAlerts()) {
                    $action['fires_min'] = $this->aC->getMinAlerts();
                }

                if ($action['fires_max'] > $this->aC->getMaxAlerts()) {
                    $action['fires_max'] = $this->aC->getMaxAlerts();
                }

                $data[1] = '';
                $error = false;

                $action['escalation'] = alerts_get_action_escalation($action);

                $acum = null;
                $from = 1;
                $final = count($action['escalation']);
                foreach ($action['escalation'] as $k => $v) {
                    if (isset($acum) === false) {
                        $acum = $v;
                    } else if ($acum !== $v || $k === $final) {
                        if ($k === $final) {
                            $data[1] .= '( '.__('From').' '.$from.' '.__('to').' '.($k).' ) ';
                        } else if (($k - $from) === 1) {
                            $data[1] .= '( '.$from.' ) ';
                        } else {
                            $data[1] .= '( '.__('From').' '.$from.' '.__('to').' '.($k - 1).' ) ';
                        }

                        if ($acum > 0) {
                            $data[1] .= html_print_image(
                                'images/tick.png',
                                true
                            );
                        } else {
                            $data[1] .= html_print_image(
                                'images/blade.png',
                                true
                            );
                        }

                        $data[1] .= ' ';
                        $from = $k;
                        $acum = $v;
                    }
                }

                if ($data[1] === '') {
                    $data[1] = __('Always');
                }

                // Treshold.
                $data[2] = human_time_description_raw(
                    $action['module_action_threshold'],
                    true,
                    'tiny'
                );

                // Actions.
                $data[3] = '';
                // Edit Alert.
                $data[3] .= '<a href="'.$url.'">';
                $data[3] .= html_print_input_image(
                    'edit',
                    'images/config.png',
                    1,
                    '',
                    true,
                    ['title' => __('Edit')]
                );
                $data[3] .= '</a>';

                // Delete Alert.
                $data[3] .= '<a href="'.$url.'&delete_action=1">';
                $data[3] .= html_print_input_image(
                    'del',
                    'images/cross.png',
                    1,
                    '',
                    true,
                    ['title' => __('Delete')]
                );
                $data[3] .= '</a>';

                array_push($table->data, $data);
            }

            $return .= html_print_table($table, true);
        } else {
            $return = ui_print_info_message(
                __('There are no defined actions for this alert'),
                '',
                true
            );
        }

        return $return;

    }


    /**
     * Print inputs valid for triggering and modal global view.
     *
     * @param array $actions Actions select.
     *
     * @return array
     */
    private function inputsActionAlerts(array $actions):array
    {
        $id_alert_action = 0;
        $fires_min = null;
        $fire_max = null;
        $module_action_threshold = $this->aC->getTimeThreshold();
        if ($this->id_action !== 0) {
            $action = AlertCorrelation::getActionAlert(
                $this->id,
                $this->id_action
            );
            $id_alert_action = $action['id_alert_action'];
            $fires_min = $action['fires_min'];
            $fires_max = $action['fires_max'];
            $module_action_threshold = $action['module_action_threshold'];
        }

        return [
            [
                'class'         => 'flex-row-vcenter flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Actions'),
                        'arguments' => [
                            'type'          => 'select',
                            'id'            => 'actions_alert',
                            'name'          => 'actions_alert',
                            'fields'        => $actions,
                            'nothing'       => __('None'),
                            'nothing_value' => 0,
                            'selected'      => $id_alert_action,
                        ],
                    ],
                ],
            ],
            [
                'block_id'      => 'time-from-to',
                'class'         => 'flex-row-vcenter flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label' => __('Number of alerts match'),
                    ],
                    [
                        'arguments' => [
                            'name'  => 'fires_min',
                            'id'    => 'fires_min',
                            'type'  => 'number',
                            'size'  => 5,
                            'value' => $fires_min,
                        ],
                    ],
                    [
                        'label'     => __('to'),
                        'arguments' => [
                            'name'  => 'fires_max',
                            'id'    => 'fires_max',
                            'type'  => 'number',
                            'size'  => 5,
                            'value' => $fires_max,
                        ],
                    ],
                ],
            ],
            [
                'class'         => 'flex-row-vcenter flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Treshold'),
                        'arguments' => [
                            'name'          => 'threshold',
                            'type'          => 'interval',
                            'nothing'       => __('None'),
                            'nothing_value' => 0,
                            'value'         => $module_action_threshold,
                        ],
                    ],
                ],
            ],
        ];
    }


    /**
     * Show actions alerts.
     *
     * @return string
     */
    public function showActionAlerts():string
    {
        $filter_groups = implode(',', $this->groups);

        // Actions Correlated Alerts.
        $actions_correlated = AlertCorrelation::getActionsAlert($this->id);

        $actions = [];
        if (empty($actions_correlated) === false) {
            // Actions Alerts.
            $actions = $this->aC->getAlertsActions(
                'id_group IN ('.$filter_groups.')',
                true
            );

            $actions = array_reduce(
                $actions_correlated,
                function ($carry, $item) use ($actions) {
                    if (isset($actions[$item['id_alert_action']]) === true) {
                        $carry[$item['id_alert_action']] = $actions[$item['id_alert_action']];
                    }

                    return $carry;
                },
                []
            );
        }

        $modes = [];
        $modes['firing'] = __('Firing');
        $modes['recovering'] = __('Recovering');

        $form = [
            'action' => $this->url,
            'method' => 'POST',
            'id'     => 'filters_show_action_alerts',

        ];

        $inputs = [
            [
                'block_id'      => 'filters_show_action_alerts',
                'class'         => 'flex-row w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label' => __(
                            'Select the desired action and mode to view the Triggering fields for this action'
                        ),
                    ],
                ],
            ],
            [
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Action'),
                        'arguments' => [
                            'type'          => 'select',
                            'id'            => 'firing_action_select',
                            'name'          => 'firing_action_select',
                            'fields'        => $actions,
                            'selected'      => -1,
                            'nothing'       => __('Select the action'),
                            'nothing_value' => -1,
                            'script'        => 'firing_action_change(
                                \''.$this->id.'\',
                                \''.$this->ajaxController.'\',
                                \''.ui_get_full_url('ajax.php').'\'
                            );',
                        ],
                    ],
                ],
            ],
        ];

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true,
            true
        );

        return $output;
    }


    /**
     * Create table Ajax Actions with field.
     *
     * @return void
     */
    public function createActionTableAjax()
    {
        $firing_action = \get_parameter('firing_action', 0);
        $id_alert = \get_parameter('id_alert', 0);

        if (empty($id_alert) === true || empty($firing_action) === true) {
            return;
        }

        // Retrieve RCMD object.
        $this->getAlertCorrelationObject($id_alert);
        $alert = $this->aC->get();

        $fields = $this->aC->getAlertActionFields($firing_action);

        $table->class = 'info_table';
        $table->width = '100%';
        $table->head = [];
        $table->data = [];
        $table->size = [];
        $table->size[0] = '10%';
        $table->size[1] = '30%';
        $table->size[2] = '30%';
        $table->style = [];
        $table->style[3] = 'font-weight: bold;';

        $table->title = __('Firing fields');
        $table->title .= ui_print_help_tip(
            __('Fields passed to the command executed by this action when the alert is fired'),
            true
        );

        $table->head[0] = __('Field');
        $table->head[0] .= ui_print_help_tip(
            __('Fields configured on the command associated to the action'),
            true
        );
        $table->head[1] = __('Alerts fields');
        $table->head[1] .= ui_print_help_tip(
            __('Triggering fields configured in Alerts'),
            true
        );
        $table->head[2] = __('Action fields');
        $table->head[2] .= ui_print_help_tip(
            __('Triggering fields configured in action'),
            true
        );
        $table->head[3] = __('Executed on firing');
        $table->head[3] .= ui_print_help_tip(
            __('Fields used on execution when the alert is fired'),
            true
        );

        $firing_fields = [];

        $descriptions = json_decode($command['fields_descriptions'], true);
        $action_values = json_decode($command['fields_values'], true);

        $i = 1;
        foreach ($fields as $key => $value) {
            $field = $key;
            $data = [];

            // Data 0.
            $data[0] = $key;
            if (empty($data[0]) === false) {
                $data[0] = '<b>'.$data[0].'</b><br>';
            }

            $data[0] .= '<br><span style="font-size: xx-small;font-style:italic;">';
            $data[0] .= '('.sprintf(__('Field %s'), ($i)).')</span>';

            // Data 1.
            $data[1] = $alert[$key];

            // Data 2.
            $data[2] = $value;

            // Data 3.
            $data[3] = $alert[$key];
            if (empty($value) === false) {
                $data[3] = $value;
            }

            // UGLY.
            $first_level = $template[$key];
            $second_level = $action[$key];
            if (empty($second_level) === false
                || empty($first_level) === false
            ) {
                if (empty($second_level)) {
                    $table->cellclass[count($table->data)][1] = 'used_field';
                    $table->cellclass[count($table->data)][2] = 'empty_field';
                } else {
                    $table->cellclass[count($table->data)][1] = 'overrided_field';
                    $table->cellclass[count($table->data)][2] = 'used_field';
                }
            }

            $table->data[] = $data;
            $i++;
        }

        die(html_print_table($table));

    }


    /**
     * Clobal alerts view
     *
     * @return void
     */
    public function viewAlertList()
    {
        // Header.
        \ui_print_page_header(
            __('Correlated alerts'),
            'images/gm_alerts.png',
            false,
            'alert_correlation',
            true
        );

        // TODO: Improve.
        if ($this->page === 0) {
            if (get_parameter('delete', 0) !== 0
                && $this->id !== 0
            ) {
                // Delete correlated alert.
                $this->getAlertCorrelationObject($this->id);

                $result = [];
                if ((bool) $this->aC === true) {
                    $result = $this->aC->delete();
                }

                if ($result['error'] === true) {
                    echo ui_print_error_message($result['msg_error']);
                } else {
                    echo ui_print_success_message(__('Alert succesfully deleted'));
                }
            }

            if (get_parameter('validate', 0) !== 0) {
                // Validate correlated alert.
                $alerts = get_parameter('alert', []);

                if (AlertCorrelation::validateAlerts($alerts) === true) {
                    echo ui_print_success_message(__('Alerts validated'));
                } else {
                    echo ui_print_error_message(
                        __('Failed to process validation')
                    );
                }
            }
        }

        echo $this->viewAlertListForm();

        echo $this->viewAlertListTable();

    }


    /**
     * Global view filters.
     *
     * @return string
     */
    public function viewAlertListForm():string
    {
        global $config;

        // TODO:XXX.
        $search_string = (string) get_parameter('free_search', '');
        $search_group = (int) get_parameter('filter_id_group', 0);

        $form = [
            'action' => $this->url,
            'method' => 'POST',
            'id'     => 'filters_alert_list',

        ];

        $inputs = [
            [
                'block_id'      => 'filters-alert-list',
                'class'         => 'flex-row-vcenter flex-space-around w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Group'),
                        'arguments' => [
                            'type'     => 'select',
                            'id'       => 'filter_id_group',
                            'name'     => 'filter_id_group',
                            'fields'   => users_get_groups_for_select(
                                $config['id_user'],
                                'AR',
                                true,
                                true,
                                false
                            ),
                            'selected' => $search_group,
                        ],
                    ],
                    [
                        'label'     => __('Free Search'),
                        'arguments' => [
                            'type'   => 'text',
                            'id'     => 'free_search',
                            'name'   => 'free_search',
                            'value'  => $search_string,
                            'return' => true,
                        ],

                    ],
                    [
                        'arguments' => [
                            'button_class' => 'btn_filter',
                            'attributes'   => 'class="sub filter"',
                            'type'         => 'submit',
                            'id'           => 'filter_alert_list',
                            'label'        => __('Filter'),
                            'width'        => 'auto',
                        ],
                    ],
                ],

            ],
        ];

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

        $output = ui_toggle(
            $output,
            _('Filters'),
            '',
            '',
            true,
            true
        );

        return $output;
    }


    /**
     * Global view table.
     *
     * @return void
     */
    public function viewAlertListTable()
    {
        global $config;

        $filter['search_string'] = (string) get_parameter('free_search', '');
        $filter['search_group'] = (int) get_parameter('filter_id_group', 0);

        $alerts = AlertCorrelation::getAlerts($filter);

        // Initialize table.
        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'info_table';
        $table->id = 'sortable-alerts';
        $table->rowid = [];

        // Styles.
        $table->style = [];
        $table->style['checkbox'] = 'text-align: right';

        $table->headstyle = [];
        $table->headstyle['checkbox'] = 'text-align: right';

        // Size.
        $table->size = [];
        $table->size[0] = '5%';
        $table->size[1] = '30%';
        $table->size[2] = '5%';
        $table->size[3] = '5%';
        $table->size[4] = '5%';
        $table->size[5] = '45%';
        $table->size['checkbox'] = '10px';

        // Head.
        $table->head = [];
        $table->head[0] = __('Sort');
        $table->head[1] = __('Name');
        $table->head[2] = __('Group');
        $table->head[3] = __('Matched');
        $table->head[4] = __('Fired');
        $table->head[5] = __('Action');
        $table->head[6] = __('Options');
        $table->head['checkbox'] = $this->printInput(
            [
                'type'       => 'checkbox',
                'id'         => 'all_validate',
                'attributes' => 'class="chk" form="validation"',
            ]
        );

        // Data.
        $table->data = [];
        if (empty($alerts) === false) {
            foreach ($alerts as $alert) {
                $table->cellclass[][6] = ' action_buttons mw180px';
                $table->rowid[] = 'sortable-alerts-'.$alert['id'];
                // Sortable Items.
                $data[0] = \html_print_image(
                    'images/sortable.png',
                    true,
                    [
                        'title' => __('Sort elements'),
                        'class' => 'handle-alerts',
                    ]
                );

                // Name alert with link.
                $data[1] = '<a href="'.$this->url.'&page=1&id='.$alert['id'].'">';
                $data[1] .= $alert['name'];
                $data[1] .= '</a>';

                // Group Icon.
                $data[2] = \ui_print_group_icon($alert['id_group'], true);

                // Status Icon.
                $data[3] = '<span id="status-alert-'.$alert['id'].'">';
                $data[3] .= $this->addStatusIcon(
                    $alert['internal_counter'],
                    $alert['disabled'],
                    [
                        'fired'     => 'Alert matched %d time(s)',
                        'not_fired' => 'Alert not matched',
                    ]
                );
                $data[3] .= '</span>';

                // Times fired.
                $data[4] = '<span id="fired-alert-'.$alert['id'].'">';
                $data[4] .= $this->addStatusIcon(
                    $alert['times_fired'],
                    $alert['standby'],
                    [
                        'fired'     => 'Alert fired %d time(s)',
                        'not_fired' => 'Alert not fired',
                    ]
                );
                $data[4] .= '</span>';

                // Extract actions info for alert.
                $actions = AlertCorrelation::getActionsAlert($alert['id']);

                $data[5] = '<ul class="action_list" id="ul-al-'.$alert['id'].'">';
                if (empty($actions) === true) {
                    $data[5] .= '<li id="emptyli-al-'.$alert['id'].'">';
                    $data[5] .= __('No associated actions');
                    $data[5] .= '</li>';
                } else {
                    foreach ($actions as $action_id => $action) {
                        $data[5] .= $this->addRowAction($alert['id'], $action);
                    }

                    $data[5] .= '</ul>';
                }

                // Disbled Alert.
                $data[6] = '<span id="disabled-alert-'.$alert['id'].'">';
                $data[6] .= $this->addDisabledIcon(
                    $alert['id'],
                    $alert['disabled']
                );
                $data[6] .= '</span>';

                // Standby Alert.
                $data[6] .= '<span id="standby-alert-'.$alert['id'].'">';
                $data[6] .= $this->addStandbyIcon(
                    $alert['id'],
                    $alert['standby']
                );
                $data[6] .= '</span>';

                // Add actions alerts.
                $data[6] .= '<a href="#" onclick=\'';
                $data[6] .= 'add_alert_action('.json_encode(
                    [
                        'title'      => __('Add Actions'),
                        'btn_text'   => __('Ok'),
                        'btn_cancel' => __('Cancel'),
                        'url'        => $this->ajaxController,
                        'url_ajax'   => ui_get_full_url('ajax.php'),
                        'id'         => $alert['id'],
                    ]
                );
                $data[6] .= ')\'>';
                $data[6] .= html_print_input_image(
                    'add_action',
                    'images/add.png',
                    1,
                    '',
                    true,
                    ['title' => __('Add Actions')]
                );
                $data[6] .= '</a>';

                // Edit Alert.
                $data[6] .= '<a href="'.$this->url.'&page=1&id='.$alert['id'].'">';
                $data[6] .= html_print_input_image(
                    'edit',
                    'images/config.png',
                    1,
                    '',
                    true,
                    ['title' => __('Edit')]
                );
                $data[6] .= '</a>';

                // Delete Alert.
                $data[6] .= '<a href="'.$this->url.'&page=0&delete=1&id='.$alert['id'].'">';
                $data[6] .= html_print_input_image(
                    'del',
                    'images/cross.png',
                    1,
                    '',
                    true,
                    ['title' => __('Delete')]
                );
                $data[6] .= '</a>';

                $data['checkbox'] = $this->printInput(
                    [
                        'type'       => 'checkbox',
                        'value'      => $alert['id'],
                        'name'       => 'alert[]',
                        'attributes' => 'class="chk" form="validation"',
                    ]
                );

                array_push($table->data, $data);
            }

            echo html_print_table($table);
        } else {
            $return = ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __('There are no defined events alerts'),
                ]
            );
        }

        $form = [
            'form'   => [
                'method' => 'POST',
                'action' => $this->url.'&page=1',
            ],
            'inputs' => [
                [
                    'block_id'      => 'buttons-alert-list',
                    'class'         => 'flex-row-vcenter flex-end w100p',
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'arguments' => [
                                'name'       => 'submit',
                                'label'      => __('Create'),
                                'type'       => 'submit',
                                'attributes' => 'class="sub next"',
                                'return'     => true,
                                'width'      => 'auto',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->printForm(
            [
                'form'   => [
                    'method' => 'POST',
                    'action' => $this->url,
                    'id'     => 'validation',
                ],
                'inputs' => [
                    [
                        'block_id'      => 'buttons-alert-list',
                        'class'         => 'flex-row-vcenter flex-end w100p',
                        'direct'        => 1,
                        'block_content' => [
                            [
                                'arguments' => [
                                    'type'  => 'hidden',
                                    'name'  => 'validate',
                                    'value' => 1,
                                ],
                            ],
                            [
                                'arguments' => [
                                    'name'       => 'submit',
                                    'label'      => __('Validate'),
                                    'type'       => 'submit',
                                    'attributes' => 'class="sub upd" ',
                                    'return'     => true,
                                    'width'      => 'auto',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->printForm($form);

        // Div for modal add actions.
        echo '<div id="modal-add-action-form" style="display:none;"></div>';
        echo '<div id="msg-add-action" style="display: none"></div>';

    }


    /**
     * Icon status.
     *
     * @param integer $count    Alert counter fired.
     * @param integer $disabled Disabled alerts.
     * @param array   $titles   Custom messages.
     *
     * @return string Icon.
     */
    public function addStatusIcon(
        $count,
        $disabled=false,
        $titles=[]
    ) {
        if (empty($titles) === true) {
            $tiltes = [
                'fired'     => 'Alert fired %d time(s)',
                'not_fired' => 'Alert not fired',
            ];
        }

        // Status Alerts.
        $status = STATUS_ALERT_NOT_FIRED;
        $title = '';
        if ($count > 0) {
            $status = STATUS_ALERT_FIRED;
            $title = __(
                $titles['fired'],
                $count
            );
        } else if ($disabled > 0) {
            $status = STATUS_ALERT_DISABLED;
            $title = __('Alert disabled');
        } else {
            $status = STATUS_ALERT_NOT_FIRED;
            $title = __($titles['not_fired']);
        }

        $output = ui_print_status_image($status, $title, true);
        return $output;
    }


    /**
     * Icon disabled or eneabled alert.
     *
     * @param integer $alert_id Alerts ID.
     * @param integer $mode     Disabled or enabled.
     *
     * @return string Icon.
     */
    public function addDisabledIcon(int $alert_id, int $mode):string
    {
        $output = '';

        // Disable alert.
        $image_dis = 'images/lightbulb.png';
        $name_dis = 'disable';
        $title_dis = __('Disabled');
        $message_disabled = __('Are you sure you want to disable the alert');
        $message_disabled .= '?';
        if ((int) $mode === 1) {
            $image_dis = 'images/lightbulb_off.png';
            $name_dis = 'enable';
            $title_dis = __('Enabled');
            $message_disabled = __('Are you sure you want to enable the alert');
            $message_disabled .= '?';
        }

        $output .= '<a href="#" onclick=\'';
        $output .= 'disabled_alert('.json_encode(
            [
                'title'    => __('Disabled Alert'),
                'msg'      => $message_disabled,
                'page'     => $this->ajaxController,
                'url'      => ui_get_full_url('ajax.php'),
                'id_alert' => $alert_id,
                'disabled' => $mode,
            ]
        );
        $output .= ')\'>';
        $output .= html_print_input_image(
            $name_dis,
            $image_dis,
            1,
            '',
            true,
            ['title' => $title_dis]
        );
        $output .= '</a>';

        return $output;
    }


    /**
     * Icon standby alert.
     *
     * @param integer $alert_id Alerts ID.
     * @param integer $mode     Standby or play.
     *
     * @return string Icon.
     */
    public function addStandbyIcon(int $alert_id, int $mode):string
    {
        $output = '';

        // Standby alert.
        $image_standby = 'images/bell.png';
        $name_standby = 'standby_off';
        $title_standby = __('Standby off');
        $message_standby = __('Are you sure you want to standby the alert');
        $message_standby .= '?';
        if ($mode === 1) {
            $image_standby = 'images/bell_pause.png';
            $name_standby = 'standby_on';
            $title_standby = __('Standby on');
            $message_standby = __(
                'Are you sure you want to activate the alert'
            );
            $message_standby .= '?';
        }

        $output .= '<a href="#" onclick=\'';
        $output .= 'standby_alert('.json_encode(
            [
                'title'    => __('Standby Alert'),
                'msg'      => $message_standby,
                'page'     => $this->ajaxController,
                'url'      => ui_get_full_url('ajax.php'),
                'id_alert' => $alert_id,
                'standby'  => $mode,
            ]
        );
        $output .= ')\'>';
        $output .= html_print_input_image(
            $name_standby,
            $image_standby,
            1,
            '',
            true,
            ['title' => $title_standby]
        );
        $output .= '</a>';

        return $output;
    }


    /**
     * Add actions alerts.
     *
     * @param integer $id_alert Alerts ID.
     * @param array   $action   Values create action.
     *
     * @return string li.
     */
    public function addRowAction($id_alert, array $action):string
    {
        $output .= '<li id="li-al-'.$id_alert.'-act-'.$action['id'].'">';
        $output .= \ui_print_truncate_text(
            $action['name'],
            GENERIC_SIZE_TEXT,
            false
        );
        $output .= ' (';
        if ((int) $action['fires_min'] === (int) $action['fires_max']) {
            if ((int) $action['fires_min'] === 0) {
                $output .= __('Always');
            } else {
                $output .= __('On').' '.$action['fires_min'];
            }
        } else {
            if ((int) $action['fires_min'] === 0) {
                $output .= __('Until').' ';
                $output .= $action['fires_max'];
            } else {
                $output .= __('From').' ';
                $output .= $action['fires_min'].' ';
                $output .= __('to').' ';
                $output .= $action['fires_max'];
            }
        }

        if ((int) $action['module_action_threshold'] !== 0) {
            $output .= ' '.__('Threshold').' ';
            $output .= human_time_description_raw(
                $action['module_action_threshold']
            );
        }

        $output .= ')';

        // Delete actions alerts.
        $output .= '<a href="#" onclick=\'';
        $output .= 'delete_alert_action('.json_encode(
            [
                'title'     => __('Delete Actions'),
                'msg'       => __('Are you sure?'),
                'emptyli'   => __('No associated actions'),
                'page'      => $this->ajaxController,
                'url'       => ui_get_full_url('ajax.php'),
                'id_action' => $action['id'],
                'id_alert'  => $id_alert,
            ]
        );
        $output .= ')\'>';
        $output .= html_print_input_image(
            'delete',
            'images/cross.png',
            1,
            '',
            true,
            ['title' => __('Delete')]
        );
        $output .= '</a>';
        $output .= '</li>';

        return $output;
    }


    /**
     * Add actions alerts AJAX.
     *
     * @return void.
     */
    public function addRowActionAjax()
    {
        $id_alert = (int) \get_parameter('id_alert');
        $id_action = (int) \get_parameter('id_action');
        // Extract actions info for alert.
        $action = AlertCorrelation::getActionAlert($id_alert, $id_action);

        die($this->addRowAction($id_alert, $action));
    }


    /**
     * Send form create actions AJAX.
     *
     * @return void.
     */
    public function addAlertActionForm()
    {
        $extradata = json_decode(
            io_safe_output(get_parameter('extradata')),
            true
        );

        $form = [
            'action'   => '#',
            'id'       => 'modal_form_add_actions',
            'onsubmit' => 'return false;',
            'class'    => 'modal',
            'extra'    => 'novalidate',
        ];

        $filter_groups = implode(',', $this->groups);
        // TODO:XXX Change function alert correlation.
        $actions = \alerts_get_alert_actions_filter(
            true,
            'id_group IN ('.$filter_groups.')'
        );

        $inputs = [
            [
                'class'         => 'flex-row-vcenter flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Actions'),
                        'arguments' => [
                            'type'   => 'select',
                            'id'     => 'actions_alert',
                            'name'   => 'actions_alert',
                            'fields' => $actions,
                        ],
                    ],
                    [
                        'arguments' => [
                            'type'  => 'hidden',
                            'name'  => 'id_alert',
                            'value' => $extradata['id'],
                        ],
                    ],
                ],
            ],
            [
                'toggle'        => true,
                'toggle_name'   => 'Advanced',
                'block_content' => [
                    [
                        'label' => __('Number of alerts match'),
                    ],
                    [
                        'label'     => __('from'),
                        'arguments' => [
                            'name' => 'fires_min',
                            'id'   => 'fires_min',
                            'type' => 'number',
                            'size' => 5,
                        ],
                    ],
                    [
                        'label'     => __('to'),
                        'arguments' => [
                            'name' => 'fires_max',
                            'id'   => 'fires_max',
                            'type' => 'number',
                            'size' => 5,
                        ],
                    ],
                    [
                        'label'     => __('times in'),
                        'arguments' => [
                            'name'          => 'threshold',
                            'type'          => 'interval',
                            'nothing'       => __('None'),
                            'nothing_value' => 0,
                        ],
                    ],
                ],
            ],
        ];

        die(
            $this->printForm(
                [
                    'form'   => $form,
                    'inputs' => $inputs,
                ],
                true
            )
        );
    }


    /**
     * Add actions alerts AJAX.
     *
     * @return mixed.
     */
    public function addAlertAction()
    {
        $id_alert = (int) \get_parameter('id_alert');
        $id_action = (int) \get_parameter('actions_alert');

        if (isset($id_alert) === false
            || isset($id_action) === false
            || empty($id_alert) === true
            || empty($id_action) === true
        ) {
            return false;
        }

        $fires_min = (int) \get_parameter('fires_min', 0);
        $fires_max = (int) \get_parameter('fires_max', 0);
        $threshold = (int) \get_parameter('threshold');

        $values = [];
        $values['fires_min'] = $fires_min;
        $values['fires_max'] = $fires_max;
        $values['module_action_threshold'] = $threshold;

        // Retrieve alert correlation object.
        $this->getAlertCorrelationObject($id_alert);
        $result = $this->aC->setActionAlert($id_action, $values);

        $return = [
            'error'     => ($result['error'] === false) ? 0 : 1,
            'text'      => [
                $result['msg_error'],
                __('Successfully added action'),
            ],
            'id_alert'  => $id_alert,
            'id_action' => $result['result'],
            'url'       => ui_get_full_url('ajax.php'),
            'page'      => $this->ajaxController,
        ];

        exit(json_encode($return));
    }


    /**
     * Delete row action in global table.
     *
     * @return mixed
     */
    public function deleteActionAlert()
    {
        $id_alert = (int) \get_parameter('id_alert');
        $id_action = (int) \get_parameter('id_action');

        if (isset($id_alert) === false
            || isset($id_action) === false
            || empty($id_alert) === true
            || empty($id_action) === true
        ) {
            return false;
        }

        // Retrieve alert correlation object.
        $this->getAlertCorrelationObject($id_alert);
        $result = $this->aC->deleteActionAlert($id_action);

        $return = [
            'error' => ($result['error'] === false) ? 0 : 1,
            'text'  => [
                $result['msg_error'],
                __('Successfully delete action'),
            ],
        ];

        exit(json_encode($return));

    }


    /**
     * Standby alert.
     *
     * @return void
     */
    public function standByAlert()
    {
        $id_alert = (int) \get_parameter('id_alert');
        $standby = (int) \get_parameter('standby');

        // Retrieve alert correlation object.
        $this->getAlertCorrelationObject($id_alert);
        $result = $this->aC->setStandbyAlert($standby);

        $html = '';
        if ($result['error'] === false) {
            $html .= $this->addStandbyIcon(
                $id_alert,
                !$standby
            );
        }

        exit($html);
    }


    /**
     * Disabled alert.
     *
     * @return void
     */
    public function disabledAlert()
    {
        $id_alert = (int) \get_parameter('id_alert');
        $disabled = (int) \get_parameter('disabled');

        // Retrieve alert correlation object.
        $this->getAlertCorrelationObject($id_alert);
        $result = $this->aC->setDisabledAlert($disabled);

        $data = [];
        if ($result['error'] === false) {
            $data['disabled'] = $this->addDisabledIcon(
                $id_alert,
                !$disabled
            );

            $data['status'] = $this->addStatusIcon(
                $this->aC->getTimesFired(),
                !$disabled
            );
        }

        exit(json_encode($data));
    }


    /**
     * Order column in bbdd.
     *
     * @return void
     */
    public function orderAlert()
    {
        $new_order = \get_parameter('data', []);

        if (empty($new_order) === true) {
            exit();
        }

        $new_order = array_reverse($new_order);

        $i = 0;
        $updateOrder = [];
        foreach ($new_order as $key => $value) {
            $id = preg_replace('/sortable-alerts-/', '', $value);

            // Retrieve alert correlation object.
            $this->getAlertCorrelationObject($id);
            $this->aC->put(['order' => $i]);

            $i++;
        }

        exit();
    }


    /**
     * Loads JS content.
     *
     * @return string JS content.
     */
    public function loadJS()
    {
        // Javascript Alerts.
        ui_require_javascript_file(
            'correlated_alerts',
            ENTERPRISE_DIR.'/include/javascript/'
        );

        // Datepicker requirements.
        ui_require_css_file('datepicker');
        ui_include_time_picker();
        ui_require_jquery_file(
            'ui.datepicker-'.get_user_language(),
            'include/javascript/i18n/'
        );

        $settingsDatePicker = json_encode(
            [
                'timeFormat'    => TIME_FORMAT_JS,
                'timeOnlyTitle' => __('Choosetime'),
                'timeText'      => __('Time'),
                'hourText'      => __('Hour'),
                'minuteText'    => __('Minute'),
                'secondText'    => __('Second'),
                'currentText'   => __('Now'),
                'closeText'     => __('Close'),
                'dateFormat'    => DATE_FORMAT_JS,
                'regional'      => get_user_language(),
            ]
        );

        ob_start();

        // Javascript content.
        ?>
        <script type="text/javascript">
            $(document).ready(function(){
                // Panel managent.
                paneInitialization();

                $('#all_validate').click(function(){
                    checkAll($(this).prop('checked'));
                });

                // DatePicker.
                datetime_picker_callback('<?php echo $settingsDatePicker; ?>');

                // TODO:XXX change to a function.
                $('#sortable-alerts tbody').sortable(
                    {
                        handle: '.handle-alerts',
                        update: function (event, ui) {
                            var data = $(this).sortable('toArray');
                            console.log(data);
                            $.ajax({
                                method: "post",
                                url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                                data: {
                                    page: '<?php echo $this->ajaxController; ?>',
                                    method: "orderAlert",
                                    data: data,
                                },
                                dataType: "json",
                                success: function(data) {
                                    console.log(data)
                                },
                                error: function(error) {
                                    console.log(error);
                                }
                            });
                        }
                    }
                );

               
                
        });
        moveRules();
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
