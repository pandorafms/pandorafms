<?php
/**
 * Widget Security hardening Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Security hardening
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

namespace PandoraFMS\Dashboard;

/**
 * Security hardening.
 */
class SecurityHardening extends Widget
{

    /**
     * Name widget.
     *
     * @var string
     */
    protected $name;

    /**
     * Title widget.
     *
     * @var string
     */
    protected $title;

    /**
     * Page widget;
     *
     * @var string
     */
    protected $page;

    /**
     * Class name widget.
     *
     * @var [type]
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var [type]
     */
    protected $values;

    /**
     * Configuration required.
     *
     * @var boolean
     */
    protected $configurationRequired;

    /**
     * Error load widget.
     *
     * @var boolean
     */
    protected $loadError;

    /**
     * Width.
     *
     * @var integer
     */
    protected $width;

    /**
     * Heigth.
     *
     * @var integer
     */
    protected $height;

    /**
     * Grid Width.
     *
     * @var integer
     */
    protected $gridWidth;

    /**
     * List elements of hardening.
     *
     * @var array
     */
    private $elements;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ) {
        global $config;

        // Includes.
        include_once ENTERPRISE_DIR.'/include/functions_security_hardening.php';
        include_once $config['homedir'].'/include/graphs/fgraph.php';
        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Options.
        $this->values = $this->getOptionsWidget();

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Security Hardening');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'security_hardening';
        }

        $this->elements = [
            'top_n_agents_sh'         => __('Top-N agents with the worst score'),
            'top_n_checks_failed'     => __('Top-N most frequent failed checks'),
            'top_n_categories_checks' => __('Top-N checks failed by category'),
            'vul_by_cat'              => __('Vulnerabilities by category'),
            'scoring'                 => __('Scoring by date'),
            'evolution'               => __('Evolution'),
        ];
    }


    /**
     * Generates inputs for form (specific).
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public function getFormInputs(): array
    {
        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();
        $categories = categories_of_cis();
        foreach ($categories as $id => $cat) {
            $categories[$id] = implode(' ', $cat);
        }

        $inputs[] = [
            'label'     => __('Data type'),
            'id'        => 'row_data_type',
            'arguments' => [
                'id'       => 'select_data_type',
                'name'     => 'data_type',
                'type'     => 'select',
                'script'   => 'selectData(this)',
                'fields'   => $this->elements,
                'selected' => $values['data_type'],
            ],
        ];

        $inputs[] = [
            'label'     => __('Group'),
            'id'        => 'row_group',
            'class'     => 'row_input',
            'arguments' => [
                'id'       => 'select_groups',
                'name'     => 'group',
                'type'     => 'select_groups',
                'selected' => (empty($values['group']) === false) ? $values['group'] : 0,
            ],
        ];

        $inputs[] = [
            'label'     => __('Limit'),
            'id'        => 'row_limit',
            'class'     => 'row_input',
            'arguments' => [
                'id'    => 'limit',
                'name'  => 'limit',
                'type'  => 'number',
                'value' => (empty($values['limit']) === false) ? $values['limit'] : 10,
            ],
        ];

        $inputs[] = [
            'label'     => __('Category'),
            'id'        => 'row_category',
            'class'     => 'row_input',
            'arguments' => [
                'id'       => 'select_categories',
                'name'     => 'category',
                'type'     => 'select',
                'fields'   => $categories,
                'selected' => (empty($values['category']) === false) ? $values['category'] : 6,
            ],
        ];

        $inputs[] = [
            'label'     => __('Ignore skipped'),
            'id'        => 'row_ignore_skipped',
            'class'     => 'row_input',
            'arguments' => [
                'id'     => 'ignore_skipped',
                'name'   => 'ignore_skipped',
                'type'   => 'switch',
                'class'  => 'invisible',
                'value'  => ($values['ignore_skipped'] === null) ? 1 : $values['ignore_skipped'],
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Date'),
            'id'        => 'row_date',
            'class'     => 'row_input',
            'arguments' => [
                'id'        => 'range',
                'name'      => 'range',
                'type'      => 'date_range',
                'selected'  => 'chose_range',
                'date_init' => date('Y/m/d', $values['date_init']),
                'time_init' => date('H:i:s', $values['date_init']),
                'date_end'  => date('Y/m/d', $values['date_end']),
                'time_end'  => date('H:i:s', $values['date_end']),
                'return'    => true,
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['data_type'] = \get_parameter('data_type', '');
        $values['group'] = \get_parameter('group', 0);
        $values['limit'] = \get_parameter('limit', 10);
        $values['category'] = \get_parameter('category', 6);
        $values['ignore_skipped'] = \get_parameter_switch('ignore_skipped', 0);
        $date = $this->getDateParameter();
        $values['date_init'] = $date['date_init'];
        $values['date_end'] = $date['date_end'];
        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string
     */
    public function load()
    {
        global $config;

        $output = '';

        $size = parent::getSize();
        $values = $this->values;
        $data_type = $this->values['data_type'];
        // If it is metaconsole we need to check it in the node.
        $id_groups = $this->checkAcl($values['group']);
        $output .= '<b>'.$this->elements[$data_type].'</b>';

        if (empty(parent::getPeriod()) === false) {
            $values['date_init'] = parent::getDateFrom();
            $values['date_end'] = parent::getDateTo();
        }

        switch ($data_type) {
            case 'top_n_agents_sh':
                $output .= $this->loadTopNAgentsSh($id_groups, $values['limit']);
            break;

            case 'top_n_checks_failed':
                $output .= $this->topNChecksFailed($id_groups, $values['limit']);
            break;

            case 'top_n_categories_checks':
                $output .= $this->topNCategoriesChecks($id_groups, $values['limit']);
            break;

            case 'vul_by_cat':
                $output .= $this->vulnerabilitiesByCategory($id_groups, $values['category'], (bool) $values['ignore_skipped']);
            break;

            case 'scoring':
                $output .= $this->scoring($id_groups, $values['date_init'], $values['date_end']);
            break;

            case 'evolution':
                $output .= $this->evolution($id_groups, $values['date_init'], $values['date_end']);
            break;

            default:
                $output .= \ui_print_info_message(_('Please, configure this widget before use'), '', true);
            break;
        }

        return $output;

    }


    /**
     * Returns the date in an object obtained by parameter.
     *
     * @return object Object with date_init, date_end and period.
     */
    private function getDateParameter()
    {
        $date_end = get_parameter('date_end', 0);
        $time_end = get_parameter('time_end');
        $datetime_end = strtotime($date_end.' '.$time_end);

        $custom_date = get_parameter('custom_date', 0);
        $range = get_parameter('range', SECONDS_1DAY);
        $date_text = get_parameter('range_text', SECONDS_1DAY);
        $date_init_less = (strtotime(date('Y/m/d')) - SECONDS_1DAY);
        $date_init = get_parameter('date_init', date(DATE_FORMAT, $date_init_less));
        $time_init = get_parameter('time_init', date(TIME_FORMAT, $date_init_less));
        $datetime_init = strtotime($date_init.' '.$time_init);
        if ($custom_date === '1') {
            if ($datetime_init >= $datetime_end) {
                $datetime_init = $date_init_less;
            }

            $date_init = date('Y/m/d H:i:s', $datetime_init);
            $date_end = date('Y/m/d H:i:s', $datetime_end);
            $period = ($datetime_end - $datetime_init);
        } else if ($custom_date === '2') {
            $date_units = get_parameter('range_units');
            $date_end = date('Y/m/d H:i:s');
            $date_init = date('Y/m/d H:i:s', (strtotime($date_end) - ((int) $date_text * (int) $date_units)));
            $period = (strtotime($date_end) - strtotime($date_init));
        } else if (in_array($range, ['this_week', 'this_month', 'past_week', 'past_month'])) {
            if ($range === 'this_week') {
                $monday = date('Y/m/d', strtotime('last monday'));

                $sunday = date('Y/m/d', strtotime($monday.' +6 days'));
                $period = (strtotime($sunday) - strtotime($monday));
                $date_init = $monday;
                $date_end = $sunday;
            } else if ($range === 'this_month') {
                $date_end = date('Y/m/d', strtotime('last day of this month'));
                $first_of_month = date('Y/m/d', strtotime('first day of this month'));
                $date_init = $first_of_month;
                $period = (strtotime($date_end) - strtotime($first_of_month));
            } else if ($range === 'past_month') {
                $date_end = date('Y/m/d', strtotime('last day of previous month'));
                $first_of_month = date('Y/m/d', strtotime('first day of previous month'));
                $date_init = $first_of_month;
                $period = (strtotime($date_end) - strtotime($first_of_month));
            } else if ($range === 'past_week') {
                $date_end = date('Y/m/d', strtotime('sunday', strtotime('last week')));
                $first_of_week = date('Y/m/d', strtotime('monday', strtotime('last week')));
                $date_init = $first_of_week;
                $period = (strtotime($date_end) - strtotime($first_of_week));
            }
        } else {
            $date_end = date('Y/m/d H:i:s');
            $date_init = date('Y/m/d H:i:s', (strtotime($date_end) - $range));
            $period = (strtotime($date_end) - strtotime($date_init));
        }

        return [
            'date_init' => strtotime($date_init),
            'date_end'  => strtotime($date_end),
            'period'    => $period,
        ];
    }


    /**
     * Check user's acl using group.
     *
     * @param string $group Group to check your acl.
     *
     * @return string Groups to which the user has access.
     */
    private function checkAcl($group)
    {
        global $config;

        $id_groups = explode(',', ($group ?? ''));
        if (in_array(0, $id_groups) === true) {
            $id_groups = array_keys(users_get_groups($config['id_user'], 'AR', false));
        }

        foreach ($id_groups as $key => $id_group) {
            if ((bool) check_acl_restricted_all($config['id_user'], $id_group, 'AR') === false) {
                unset($id_groups[$key]);
            }
        }

        $id_groups = implode(',', $id_groups);
        if ($id_groups === '') {
            $id_groups = -1;
        }

        return $id_groups;
    }


    /**
     * Get the hardening evolution.
     *
     * @param integer $group     Id of group for filter.
     * @param integer $date_init Date from which the data starts.
     * @param integer $date_end  Date from which the data finish.
     *
     * @return array $return html graph.
     */
    private function evolution($group, $date_init, $date_end)
    {
        if (is_metaconsole() === true) {
            $servers = metaconsole_get_servers();
            if (isset($servers) === true
                && is_array($servers) === true
            ) {
                $calculate = [
                    'passed' => [],
                    'failed' => [],
                ];
                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $evolution_node = get_hardening_evolution($group, $date_init, $date_end, true);
                        foreach ($evolution_node['passed'] as $key => $passed) {
                            if (key_exists($passed['utimestamp'], $calculate['passed'])) {
                                $calculate['passed'][$passed['utimestamp']] = [
                                    'sum'   => ($calculate['passed'][$passed['utimestamp']]['sum'] + $passed['sum']),
                                    'count' => ($calculate['passed'][$passed['utimestamp']]['count'] + $passed['count']),
                                ];
                            } else {
                                $calculate['passed'][$passed['utimestamp']] = [
                                    'sum'   => $passed['sum'],
                                    'count' => $passed['count'],
                                ];
                            }
                        }

                        foreach ($evolution_node['failed'] as $key => $failed) {
                            if (key_exists($failed['utimestamp'], $calculate['failed'])) {
                                $calculate['failed'][$failed['utimestamp']] = [
                                    'sum'   => ($calculate['failed'][$failed['utimestamp']]['sum'] + $failed['sum']),
                                    'count' => ($calculate['failed'][$failed['utimestamp']]['count'] + $failed['count']),
                                ];
                            } else {
                                $calculate['failed'][$failed['utimestamp']] = [
                                    'sum'   => $failed['sum'],
                                    'count' => $failed['count'],
                                ];
                            }
                        }
                    }

                    metaconsole_restore_db();
                }

                $evolution = [
                    'passed' => [],
                    'failed' => [],
                ];
                foreach ($calculate['passed'] as $key => $day) {
                    if (key_exists('count', $day) === true && $day['count'] > 0) {
                        $evolution['passed'][] = [
                            'utimestamp' => $key,
                            'datos'      => round(($day['sum'] / $day['count'])),
                        ];
                    }
                }

                foreach ($calculate['failed'] as $key => $day) {
                    if (key_exists('count', $day) === true && $day['count'] > 0) {
                        $evolution['failed'][] = [
                            'utimestamp' => $key,
                            'datos'      => round(($day['sum'] / $day['count'])),
                        ];
                    }
                }

                usort($evolution['passed'], fn($a, $b) => ($a['utimestamp'] - $b['utimestamp']));
                usort($evolution['failed'], fn($a, $b) => ($a['utimestamp'] - $b['utimestamp']));
            }
        } else {
            $evolution = get_hardening_evolution($group, $date_init, $date_end, false);
        }

        $dates = [];
        $dataset_passed = [];
        $dataset_failed = [];
        foreach ($evolution['passed'] as $key => $raw_data) {
            $dates[] = date('Y-m-d', $raw_data['utimestamp']);
            $dataset_passed[] = $raw_data['datos'];
        }

        foreach ($evolution['failed'] as $key => $raw_data) {
            $dataset_failed[] = $raw_data['datos'];
        }

        $options = ['labels' => $dates];

        $data = [
            [
                'label'                     => 'Passed',
                'backgroundColor'           => '#82b92e',
                'borderColor'               => '#82b92e',
                'pointBackgroundColor'      => '#82b92e',
                'pointBorderColor'          => '#fff',
                'pointHoverBackgroundColor' => '#fff',
                'pointHoverBorderColor'     => '#82b92e',
                'data'                      => $dataset_passed,
            ],
            [
                'label'                     => 'Failed',
                'backgroundColor'           => '#e63c52',
                'borderColor'               => '#e63c52',
                'pointBackgroundColor'      => '#e63c52',
                'pointBorderColor'          => '#fff',
                'pointHoverBackgroundColor' => '#fff',
                'pointHoverBorderColor'     => '#e63c52',
                'data'                      => $dataset_failed,
            ],
        ];

        $graph_area = line_graph($data, $options);

        return html_print_div(
            [
                'content' => $graph_area,
                'class'   => 'flex',
                'style'   => 'width: 90%; height: 90%;',
            ],
            true
        );
    }


    /**
     * Return all scoring of agents in range time.
     *
     * @param integer $group     Id of group for filter.
     * @param integer $date_init Date from which the data starts.
     * @param integer $date_end  Date from which the data finish.
     *
     * @return string Html table with scoring agents.
     */
    private function scoring($group, $date_init, $date_end)
    {
        global $config;
        if (is_metaconsole() === true) {
            $servers = metaconsole_get_servers();
            if (isset($servers) === true
                && is_array($servers) === true
            ) {
                $scoring_agents = [];
                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $scoring_agents = array_merge($scoring_agents, get_scoring_by_agent($group, $date_init, $date_end));
                    }

                    metaconsole_restore_db();
                }

                $data = $scoring_agents;
            }
        } else {
            $data = get_scoring_by_agent($group, $date_init, $date_end);
        }

        if (count($data) === 0) {
            return ui_print_info_message(__('No data found'), '', true);
        }

        $table = new \stdClass();

        $table->class = 'info_table';
        $table->width = '100%';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->size = [];
        $table->size[0] = '30%';
        $table->size[1] = '30%';
        $table->size[2] = '30%';

        $table->align = [];
        $table->align[0] = 'left';
        $table->align[1] = 'left';

        $table->head = [];
        $table->head[0] = __('Date');
        $table->head[1] = __('Agent');
        $table->head[2] = __('Scoring');

        $table->headstyle = [];
        $table->headstyle[0] = 'text-align:left;background-color: '.$this->values['background'];
        $table->headstyle[1] = 'text-align:left; background-color: '.$this->values['background'];
        $table->headstyle[2] = 'text-align:left; background-color: '.$this->values['background'];

        $table->style = [];
        $table->style[0] = 'padding: 0px 10px; background-color: '.$this->values['background'].';';
        $table->style[1] = 'padding: 0px 5px; background-color: '.$this->values['background'].';';
        $table->style[2] = 'text-align:left; background-color: '.$this->values['background'].'; font-weight: bolder;';

        foreach ($data as $id => $agent) {
            $row = [];
            $row[0] = date($config['date_format'], $agent['date']);
            $row[1] = $agent['agent'];
            $row[2] = $agent['scoring'].' %';

            $table->data[] = $row;
        }

        $output = html_print_table($table, true);

        return $output;
    }


    /**
     * Get all vunerabilties of category.
     *
     * @param integer $group          Id group for filter.
     * @param string  $category       Category Cis for filter.
     * @param boolean $ignore_skipped Boolean for ignore skipped elements.
     *
     * @return string Html ring graph.
     */
    private function vulnerabilitiesByCategory($group, $category, $ignore_skipped=true)
    {
        $labels = [
            __('Passed'),
            __('Failed'),
        ];

        if (is_metaconsole() === true) {
            $servers = metaconsole_get_servers();
            if (isset($servers) === true
                && is_array($servers) === true
            ) {
                $vulnerabilities = [
                    'fail'    => [],
                    'pass'    => [],
                    'skipped' => [],
                ];
                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $vulnerabilities_node = vulnerability_by_category($group, $category, (bool) $ignore_skipped);
                        if (key_exists('fail', $vulnerabilities_node) === true && count($vulnerabilities_node['fail']) > 0) {
                            $vulnerabilities['fail'] = ($vulnerabilities['fail'] + $vulnerabilities_node['fail']);
                        }

                        if (key_exists('pass', $vulnerabilities_node) === true && count($vulnerabilities_node['pass']) > 0) {
                            $vulnerabilities['pass'] = ($vulnerabilities['pass'] + $vulnerabilities_node['pass']);
                        }

                        if (key_exists('skipped', $vulnerabilities_node) === true && count($vulnerabilities_node['skipped']) > 0) {
                            $vulnerabilities['skipped'] = ($vulnerabilities['skipped'] + $vulnerabilities_node['skipped']);
                        }
                    }

                    metaconsole_restore_db();
                }

                if (count($vulnerabilities) > 0) {
                    $data = [
                        count($vulnerabilities['pass']),
                        count($vulnerabilities['fail']),
                    ];
                    $total = (count($vulnerabilities['pass']) + count($vulnerabilities['fail']));

                    if ((bool) $ignore_skipped === false && key_exists('skipped', $vulnerabilities) === true) {
                        $data[] = count($vulnerabilities['skipped']);
                        $total += count($vulnerabilities['skipped']);
                        $labels[] = __('Skipped');
                    }
                }
            }
        } else {
            $vulnerabilities = vulnerability_by_category($group, $category, $ignore_skipped);
            $data = [
                count($vulnerabilities['pass']),
                count($vulnerabilities['fail']),
            ];

            $total = (count($vulnerabilities['pass']) + count($vulnerabilities['fail']));

            if ($ignore_skipped === false) {
                $data[] = count($vulnerabilities['skipped']);
                $total += count($vulnerabilities['skipped']);
                $labels[] = __('Skipped');
            }
        }

        $pie = ring_graph(
            $data,
            [
                'legend'   => [
                    'display'  => true,
                    'position' => 'right',
                    'align'    => 'center',
                ],
                'elements' => [
                    'center' => [
                        'text'  => $total,
                        'color' => '#2c3e50',
                    ],
                ],
                'labels'   => $labels,
                'colors'   => [
                    '#82b92e',
                    '#e63c52',
                    '#E4E4E4',
                ],
            ]
        );

        return html_print_div(
            [
                'content' => $pie,
                'class'   => 'flex',
                'style'   => 'width: 80%; height: 90%;',
            ],
            true
        );
    }


    /**
     * Get top cheks failed by category.
     *
     * @param integer $group Id of group for filter.
     * @param integer $limit Limit of agents for show.
     *
     * @return string Html table with top n categories checks.
     */
    private function topNCategoriesChecks($group, $limit=10)
    {
        if (is_metaconsole() === true) {
            $servers = metaconsole_get_servers();
            $top_category = [];
            if (isset($servers) === true
                && is_array($servers) === true
            ) {
                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $top_n_category_fail_in_node = top_n_categories_checks($group, $limit);
                        foreach ($top_n_category_fail_in_node as $id => $check) {
                            if (array_key_exists($id, $top_category) === true) {
                                $top_category[$id]['total'] = ($top_category[$id]['total'] + $check['total']);
                            } else {
                                $top_category[$id] = $check;
                            }
                        }
                    }

                    metaconsole_restore_db();
                }

                usort(
                    $top_category,
                    function ($a, $b) {
                        if ($a['total'] == $b['total']) {
                            return 0;
                        }

                        return ($a['total'] > $b['total']) ? -1 : 1;
                    }
                );
                $data  = array_slice($top_category, 0, $limit);
            }
        } else {
            $data = top_n_categories_checks($group, $limit);
        }

        if (count($data) === 0) {
            return ui_print_info_message(__('No data found'), '', true);
        }

        $table = new \stdClass();

        $table->class = 'info_table';
        $table->width = '100%';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->size = [];
        $table->size[0] = '70%';
        $table->size[1] = '30%';

        $table->align = [];
        $table->align[0] = 'left';
        $table->align[1] = 'left';

        $table->head = [];
        $table->head[0] = __('Category');
        $table->head[1] = __('Total Failed');

        $table->headstyle = [];
        $table->headstyle[0] = 'text-align:left;background-color: '.$this->values['background'];
        $table->headstyle[1] = 'text-align:left; background-color: '.$this->values['background'];

        $table->style = [];
        $table->style[0] = 'padding: 0px 10px; background-color: '.$this->values['background'].';';
        $table->style[1] = 'background-color: '.$this->values['background'].'; font-weight: bolder;';

        foreach ($data as $id => $agent) {
            $row = [];
            $row[0] = $agent['category'];
            $row[1] = $agent['total'];

            $table->data[] = $row;
        }

        $output = html_print_table($table, true);

        return $output;
    }


    /**
     * Get top checks with more failed.
     *
     * @param integer $group Id of group for filter.
     * @param integer $limit Limit of agents for show.
     *
     * @return string Html table.
     */
    private function topNChecksFailed($group, $limit=10)
    {
        if (is_metaconsole() === true) {
            $servers = metaconsole_get_servers();
            $top_checks = [];
            if (isset($servers) === true
                && is_array($servers) === true
            ) {
                $numbers_of_nodes = count($servers);
                if ($limit > $numbers_of_nodes && $numbers_of_nodes > 0) {
                    $limit_in_node = ceil(($limit / $numbers_of_nodes));
                } else {
                    $limit_in_node = 1;
                }

                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $top_n_checks_in_node = top_n_checks_failed($group, $limit_in_node);
                        foreach ($top_n_checks_in_node as $id => $check) {
                            if (array_key_exists($id, $top_checks) === true) {
                                $top_checks[$id]['total'] = ($top_checks[$id]['total'] + $check['total']);
                            } else {
                                $top_checks[$id] = $check;
                            }
                        }
                    }

                    metaconsole_restore_db();
                }

                usort(
                    $top_checks,
                    function ($a, $b) {
                        if ($a['total'] == $b['total']) {
                            return 0;
                        }

                        return ($a['total'] < $b['total']) ? (-1) : 1;
                    }
                );

                $data  = array_slice($top_checks, 0, $limit);
            }
        } else {
            $data = top_n_checks_failed($group, $limit);
        }

        if (count($data) === 0) {
            return ui_print_info_message(__('No data found'), '', true);
        }

        $table = new \stdClass();

        $table->class = 'info_table';
        $table->width = '100%';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->size = [];
        $table->size[0] = '70%';
        $table->size[1] = '30%';

        $table->align = [];
        $table->align[0] = 'left';
        $table->align[1] = 'left';

        $table->head = [];
        $table->head[0] = __('Title');
        $table->head[1] = __('Total Failed');

        $table->headstyle = [];
        $table->headstyle[0] = 'text-align:left;background-color: '.$this->values['background'];
        $table->headstyle[1] = 'text-align:left; background-color: '.$this->values['background'];

        $table->style = [];
        $table->style[0] = 'padding: 0px 10px; background-color: '.$this->values['background'].';';
        $table->style[1] = 'background-color: '.$this->values['background'].'; font-weight: bolder;';

        foreach ($data as $id => $agent) {
            $row = [];
            $row[0] = $agent['title'];
            $row[1] = $agent['total'];

            $table->data[] = $row;
        }

        $output = html_print_table($table, true);

        return $output;
    }


    /**
     * Get top agents with worst score.
     *
     * @param integer $group Id of group for filter.
     * @param integer $limit Limit of agents for show.
     *
     * @return string Html table.
     */
    private function loadTopNAgentsSh($group, $limit=10)
    {
        global $config;
        if (is_metaconsole() === true) {
            $servers = metaconsole_get_servers();
            $top_agents = [];
            if (isset($servers) === true
                && is_array($servers) === true
            ) {
                $numbers_of_nodes = count($servers);
                if ($limit > $numbers_of_nodes && $numbers_of_nodes > 0) {
                    $limit_in_node = ceil(($limit / $numbers_of_nodes));
                } else {
                    $limit_in_node = 1;
                }

                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $top_agents = array_merge($top_agents, top_n_agents_worses_by_group($group, $limit_in_node));
                    }

                    metaconsole_restore_db();
                }

                usort(
                    $top_agents,
                    function ($a, $b) {
                        if ($a['datos'] == $b['datos']) {
                            return 0;
                        }

                        return ($a['datos'] < $b['datos']) ? (-1) : 1;
                    }
                );
                $data  = array_slice($top_agents, 0, $limit);
            }
        } else {
            $data = top_n_agents_worses_by_group($group, $limit);
        }

        if (count($data) === 0) {
            return ui_print_info_message(__('No data found'), '', true);
        }

        $table = new \stdClass();

        $table->class = 'info_table';
        $table->width = '100%';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->size = [];
        $table->size[0] = '35%';
        $table->size[1] = '32%';
        $table->size[2] = '32%';

        $table->align = [];
        $table->align[0] = 'left';
        $table->align[1] = 'left';
        $table->align[2] = 'left';

        $table->head = [];
        $table->head[0] = __('Alias');
        $table->head[1] = __('Last audit scan');
        $table->head[2] = __('Score');

        $table->headstyle = [];
        $table->headstyle[0] = 'text-align:left;background-color: '.$this->values['background'];
        $table->headstyle[1] = 'text-align:left; background-color: '.$this->values['background'];
        $table->headstyle[2] = 'text-align:left;background-color: '.$this->values['background'];

        $table->style = [];
        $table->style[0] = 'padding: 0px 10px; background-color: '.$this->values['background'].';';
        $table->style[1] = 'background-color: '.$this->values['background'].';';
        $table->style[2] = 'background-color: '.$this->values['background'].'; font-size: 1em; font-weight: bolder;';

        foreach ($data as $id => $agent) {
            $row = [];
            $row[0] = $agent['alias'];
            $row[1] = date($config['date_format'], $agent['utimestamp']);
            $row[2] = $agent['datos'].' %';

            $table->data[] = $row;
        }

        $output = html_print_table($table, true);

        return $output;
    }


    /**
     * Return aux javascript code for forms.
     *
     * @return string
     */
    public function getFormJS()
    {
        $id = uniqid();
        return '
        const dataTypes_'.$id.' = {
            "top_n_agents_sh": ["#row_group", "#row_limit"],
            "top_n_checks_failed": ["#row_group", "#row_limit"],
            "top_n_categories_checks": ["#row_group", "#row_limit"],
            "vul_by_cat": ["#row_group", "#row_category", "#row_ignore_skipped"],
            "scoring": ["#row_group", "#row_date"],
            "evolution": ["#row_group", "#row_date"],
        }
        function selectData(e){
            $(".row_input").hide();
            dataTypes_'.$id.'[e.value].forEach(element => {
                $(element).show();
            });
        }
        $(document).ready(function() {
            const input = $("#data_type")[0];
            selectData(input);
        });
    ';
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Security Hardening');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'security_hardening';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 400,
            'height' => 530,
        ];

        return $size;
    }


}
