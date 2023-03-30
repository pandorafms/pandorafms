<?php
/**
 * Agents/Alerts Monitoring view Class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Agent Configuration
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

// Get global data.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';
// Required functions.
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_reporting.php';

/**
 * AgentWizard class
 */
class AgentsAlerts extends HTML
{

    /**
     * Selected refresh rate
     *
     * @var string
     */
    private $refreshSelectedRate;

    /**
     * If true, will show modules that not have defined alerts.
     *
     * @var boolean
     */
    private $showWithoutAlertModules;

    /**
     * Selected group.
     *
     * @var integer
     */
    private $groupId;

    /**
     * Create alert received parameter.
     *
     * @var integer
     */
    private $createAlert;

    /**
     * Full screen variable.
     *
     * @var integer
     */
    private $pure;

    /**
     * Config id user.
     *
     * @var string
     */
    private $idUser;

    /**
     * Undocumented variable
     *
     * @var integer
     */
    private $offset;

    /**
     * Undocumented variable
     *
     * @var integer
     */
    private $horOffset;


    /**
     * Constructor
     *
     * @return $this
     */
    public function __construct()
    {
        global $config;

        // Check access.
        check_login();

        if (! check_acl($config['id_user'], 0, 'AR')) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access event viewer'
            );

            if (is_ajax() === true) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        // Pure variable for full screen selection.
        $this->pure = $config['pure'];
        // Id user.
        $this->idUser = $config['id_user'];
        // Refresh rate.
        $this->refreshSelectedRate = (string) get_parameter('refresh-rate', '30');
        // Show Modules without alerts table.
        $this->showWithoutAlertModules = (isset($_POST['show-modules-without-alerts']))
            ? true
            : isset($_GET['show-modules-without-alerts']);
        // Selected group.
        $this->groupId = (int) get_parameter('group-id', 0);
        // Create alert token.
        $this->createAlert = (int) get_parameter('create_alert', 0);
        // Offset and hor-offset (for pagination).
        $this->offset    = (int) get_parameter('offset', 0);
        $this->horOffset = (int) get_parameter('hor_offset', 0);

        return $this;
    }


    /**
     * Run main page.
     *
     * @return void
     */
    public function run()
    {
        // Javascript.
        ui_require_jquery_file('pandora');
        // Load own javascript file.
        $this->loadJS();
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');
        ui_require_css_file('agent_alerts');
        // Update network modules for this group
        // Check for Network FLAG change request
        // Made it a subquery, much faster on both the database and server side
        // TODO. Check if this is used or necessary.
        if (isset($_GET['update_netgroup']) === true) {
            $group = get_parameter_get('update_netgroup', 0);
            if (check_acl($this->idUser, $group, 'AW')) {
                $where = ['id_agente' => 'ANY(SELECT id_agente FROM tagente WHERE id_grupo = '.$group];

                db_process_sql_update('tagente_modulo', ['flag' => 1], $where);
            } else {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to set flag for groups'
                );
                include 'general/noaccess.php';
                exit;
            }
        }

        // Load the header.
        $this->loadHeader();
        // If the petition wants to create alert.
        if ($this->createAlert) {
            $this->createAlertAction();
        }

        if ($this->showWithoutAlertModules === true) {
            $this->createAlertTable();
        } else {
            $this->loadMainAlertTable();
        }
    }


    /**
     * Show alert table.
     *
     * @return void
     */
    private function createAlertTable()
    {
        global $config;

        $table = new stdClass();

        if ($this->groupId > 0) {
            $grupo = ' AND tagente.id_grupo = '.$this->groupId;
        } else {
            $grupo = '';
        }

        $offset_modules = $this->offset;

        $sql_count = 'SELECT COUNT(tagente_modulo.nombre) FROM tagente_modulo
        INNER JOIN tagente ON tagente.id_agente = tagente_modulo.id_agente
        WHERE id_agente_modulo NOT IN (SELECT id_agent_module FROM talert_template_modules)'.$grupo;

        $count_agent_module = db_get_all_rows_sql($sql_count);

        $sql = 'SELECT tagente.alias, tagente_modulo.nombre,
        tagente_modulo.id_agente_modulo FROM tagente_modulo
        INNER JOIN tagente ON tagente.id_agente = tagente_modulo.id_agente
        WHERE id_agente_modulo NOT IN (SELECT id_agent_module FROM talert_template_modules) '.$grupo.' LIMIT '.$config['block_size'].' OFFSET '.$offset_modules;

        $agent_modules = db_get_all_rows_sql($sql);

        $tablePagination = ui_pagination(
            $count_agent_module[0]['COUNT(tagente_modulo.nombre)'],
            ui_get_url_refresh(),
            0,
            false,
            true,
            'offset',
            false,
            '',
            '',
            false,
            'alerts_modules'
        );

        $table->width    = '100%';
        $table->class    = 'info_table';
        $table->id       = 'table_agent_module';
        $table->data     = [];

        $table->head[0]  = __('Agents');
        $table->head[1]  = __('Modules');
        $table->head[2]  = __('Actions');

        $table->style[0] = 'width: 25%;';
        $table->style[1] = 'width: 70%;';
        $table->style[2] = 'width: 5%;';

        foreach ($agent_modules as $agent_module) {
            // Let's build the table.
            $data[0] = io_safe_output($agent_module['alias']);
            $data[1] = io_safe_output($agent_module['nombre']);
            $uniqid = $agent_module['id_agente_modulo'];
            $data[2] = html_print_anchor(
                [
                    'href'    => sprintf(
                        'javascript:show_add_alerts(\'%s\')',
                        $uniqid
                    ),
                    'content' => html_print_image(
                        'images/add_mc.png',
                        true,
                        ['class' => 'main_menu_icon invert_filter']
                    ),
                ],
                true
            );

            array_push($table->data, $data);

            $table2 = new stdClass();
            $table2->width  = '100%';
            $table2->id     = 'table_add_alert';
            $table2->class  = 'filter-table-adv';
            $table2->size = [];
            $table2->size[0] = '50%';
            $table2->size[1] = '50%';
            $table2->data   = [];

            $groups_user = users_get_groups($this->idUser);

            if (!empty($groups_user)) {
                $groups = implode(',', array_keys($groups_user));
                $sql = sprintf(
                    'SELECT id, name FROM talert_actions WHERE id_group IN (%s)',
                    $groups
                );

                $actions = db_get_all_rows_sql($sql);
            }

            $input_actions = html_print_select(
                index_array($actions, 'id', 'name'),
                'action_select',
                '',
                '',
                __('Default action'),
                '0',
                true,
                '',
                true,
                '',
                false,
                'width: 100%;'
            );

            $input_actions .= '<span id="advanced_action" class="advanced_actions invisible"><br>';
            $input_actions .= __('Number of alerts match from').' ';
            $input_actions .= html_print_input_text('fires_min', '', '', 4, 10, true);
            $input_actions .= ' '.__('to').' ';
            $input_actions .= html_print_input_text('fires_max', '', '', 4, 10, true);
            $input_actions .= ui_print_help_icon(
                'alert-matches',
                true,
                ui_get_full_url(false, false, false, false)
            );

            $input_actions .= '</span>';

            $table2->data[0][0] = html_print_label_input_block(
                __('Actions'),
                $input_actions
            );

            // Check ACLs for LM users.
            if (check_acl($this->idUser, 0, 'LM')) {
                $table2->data[0][1] = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action&pure='.$this->pure,
                        'class'   => 'mrgn_lft_5px',
                        'content' => html_print_image('images/add.png', true).'<span class="mrgn_lft_5px vertical_middle">'.__('Create Action').'</span>',
                    ],
                    true
                );
            }

            $own_info = get_user_info($this->idUser);
            if ($own_info['is_admin'] || check_acl($this->idUser, 0, 'PM')) {
                $templates = alerts_get_alert_templates(false, ['id', 'name']);
            } else {
                $usr_groups = users_get_groups($this->idUser, 'LW', true);
                $filter_groups = '';
                $filter_groups = implode(',', array_keys($usr_groups));
                $templates = alerts_get_alert_templates(['id_group IN ('.$filter_groups.')'], ['id', 'name']);
            }

            $table2->data[1][0] = html_print_label_input_block(
                __('Template'),
                html_print_select(
                    index_array($templates, 'id', 'name'),
                    'template',
                    '',
                    '',
                    __('Select'),
                    0,
                    true,
                    false,
                    true,
                    '',
                    false,
                    'width: 100%;'
                )
            );

            $table2->data[1][1] = html_print_anchor(
                [
                    'href'    => '#',
                    'class'   => 'template_details invisible',
                    'content' => html_print_image('images/zoom.png', true, ['class' => 'img_help']),
                ],
                true
            );

            // Check ACLs for LM users.
            if (check_acl($this->idUser, 0, 'LM')) {
                $table2->data[1][1] = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&pure='.$this->pure,
                        'style'   => 'margin-left:5px;',
                        'content' => html_print_image('images/add.png', true).'<span class="mrgn_lft_5px vertical_middle">'.__('Create Template').'</span>',
                    ],
                    true
                );
            }

            $table2->data[2][0] = html_print_label_input_block(
                __('Threshold'),
                html_print_input_text('module_action_threshold', '0', '', 5, 7, true)
            );

            $content2 = '<form class="add_alert_form" method="post">';
            $content2 .= html_print_table($table2, true);
            $content2 .= html_print_div(
                [
                    'class'   => 'action-buttons',
                    'content' => html_print_submit_button(
                        __('Add alert'),
                        'add',
                        false,
                        [
                            'icon' => 'add',
                            'mode' => 'mini',
                        ],
                        true
                    ).html_print_input_hidden('create_alert', $uniqid, true),
                ],
                true
            );

            $content2 .= '</form>';

            $module_name = ui_print_truncate_text(io_safe_output($agent_module['nombre']), 40, false, true, false, '&hellip;', false);

            html_print_div(
                [
                    'id'      => 'add_alerts_dialog_'.$uniqid,
                    'title'   => sprintf(
                        '%s: %s / %s: %s',
                        __('Agent'),
                        $agent_module['alias'],
                        __('Module'),
                        $module_name
                    ),
                    'style'   => 'display:none; height: auto; padding-top: 1.5em;',
                    'content' => $content2,
                ]
            );
        }

        html_print_table($table);

        html_print_action_buttons(
            '',
            ['right_content' => $tablePagination]
        );
    }


    /**
     * Creation of alerts
     *
     * @return void
     */
    private function createAlertAction()
    {
        $template2               = get_parameter('template');
        $module_action_threshold = get_parameter('module_action_threshold');
        $action_select           = get_parameter('action_select', 0);

        $id_alert = alerts_create_alert_agent_module($this->create_alert, $template2);

        if ($id_alert !== false) {
            if ($action_select != 0) {
                $values = [];
                $values['fires_min'] = 0;
                $values['fires_max'] = 0;
                $values['module_action_threshold'] = (int) get_parameter('module_action_threshold');

                alerts_add_alert_agent_module_action($id_alert, $action_select, $values);
            }
        }
    }


    /**
     * Load the main table.
     *
     * @return boolean
     */
    public function loadMainAlertTable()
    {
        global $config;

        $block = 10;

        $filter = [
            'offset' => (int) $this->offset,
            'limit'  => (int) $config['block_size'],
        ];

        $filter_count = [];

        if ($this->groupId > 0) {
            $filter['id_grupo']       = $this->groupId;
            $filter_count['id_grupo'] = $this->groupId;
        }

        // Get the id of all agents with alerts.
        $sql = 'SELECT DISTINCT(id_agente)
            FROM tagente_modulo
            WHERE id_agente_modulo IN
                (SELECT id_agent_module
                FROM talert_template_modules)';
        $agents_with_alerts_raw = db_get_all_rows_sql($sql);

        if ($agents_with_alerts_raw === false) {
            $agents_with_alerts_raw = [];
        }

        $agents_with_alerts = [];
        foreach ($agents_with_alerts_raw as $awar) {
            $agents_with_alerts[] = $awar['id_agente'];
        }

        $filter['id_agente']       = $agents_with_alerts;
        $filter_count['id_agente'] = $agents_with_alerts;

        $agents = agents_get_agents($filter);

        $nagents = count(agents_get_agents($filter_count));

        if ($agents === false || empty($agents_with_alerts) === true) {
            ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __('There are no agents with alerts'),
                ]
            );

            return false;
        }

        $all_alerts = agents_get_alerts_simple();

        if ($this->pure == 1) {
            $block = count($all_alerts);
        }

        $templates = [];
        $agent_alerts = [];
        foreach ($all_alerts as $alert) {
            $templates[$alert['id_alert_template']] = '';
            $agent_alerts[$alert['agent_name']][$alert['id_alert_template']][] = $alert;
        }

        // Prepare pagination.
        $tablePagination = ui_pagination(
            $nagents,
            false,
            0,
            $filter['limit'],
            true,
            'offset',
            false,
            '',
            '',
            [
                'count'  => '',
                'offset' => 'offset_param',
            ],
            'alerts_agents'
        );

        echo '<table cellpadding="4" cellspacing="4" border="0" class="info_table">';
        echo '<tr>';
        echo '<th class="header_table_principal_cell">'.__('Agents').' / '.__('Alerts').'</th>';

        $templates_raw = [];
        if (!empty($templates)) {
            $sql = sprintf(
                'SELECT id, name
                FROM talert_templates
                WHERE id IN (%s)',
                implode(',', array_keys($templates))
            );

            $templates_raw = db_get_all_rows_sql($sql);

            if (empty($templates_raw)) {
                $templates_raw = [];
            }
        };

        $alerts = [];
        $ntemplates = 0;
        if ($this->horOffset > 0) {
            $new_hor_offset = ($this->horOffset - $block);
            echo "<th class='next_previous_step' rowspan='".($nagents + 1)."'>";

            html_print_anchor(
                [
                    'href'    => sprintf(
                        'index.php?sec=extensions&sec2=extensions/agents_alerts&hor_offset=%s&offset=%s&group-id=%s&pure=%s',
                        $new_hor_offset,
                        $this->offset,
                        $this->groupId,
                        $this->pure
                    ),
                    'content' => html_print_image(
                        'images/arrow_left_green.png',
                        true,
                        [
                            'style' => 'display:flex;justify-content: center',
                            'title' => __('Previous alerts'),
                        ]
                    ),
                    'style'   => 'display:flex;justify-content: center',
                ]
            );
            echo '</th>';
        }

        // Dynamic Size.
        if ($this->pure == 1) {
            // Count of templates.
            $templateCount = count($templates_raw);
            // Define a dynamic size.
            $thSize = floor(80 / $templateCount).'%';
        } else {
            $thSize = '8%';
        }

        foreach ($templates_raw as $temp) {
            if (isset($templates[$temp['id']]) && $templates[$temp['id']] == '') {
                $ntemplates++;

                if ($ntemplates <= $this->horOffset || $ntemplates > ($this->horOffset + $block)) {
                    continue;
                }

                $templates[$temp['id']] = $temp['name'];
            }
        }

        foreach ($templates as $id => $name) {
            if (empty($name) === false) {
                $outputLine = html_print_div(
                    [
                        'id'      => 'line_header_'.$id,
                        'class'   => 'position_text_module',
                        'style'   => '',
                        'content' => '<div style="font-size: 7.5pt !important" title="'.io_safe_output($name).'">'.ui_print_truncate_text(io_safe_output($name), 20).'</div>',
                    ],
                    true
                );

                echo sprintf('<th class="th_class_module_r header_table_caption_cell" style="width:%s">%s</th>', $thSize, $outputLine);
            }
        }

        if (($this->horOffset + $block) < $ntemplates) {
            $new_hor_offset = ($this->horOffset + $block);
            echo "<th class='next_previous_step' rowspan='".($nagents + 1)."'>";
            html_print_anchor(
                [
                    'href'    => sprintf(
                        'index.php?sec=extensions&sec2=extensions/agents_alerts&hor_offset=%s&offset=%s&group-id=%s&pure=%s',
                        $new_hor_offset,
                        $this->offset,
                        $this->groupId,
                        $this->pure
                    ),
                    'content' => html_print_image(
                        'images/arrow_right_green.png',
                        true,
                        [
                            'style' => 'float: right;',
                            'title' => __('More alerts'),
                        ]
                    ),
                ]
            );
            echo '</th>';
        }

        echo '</tr>';

        foreach ($agents as $agent) {
            // Flag for fired alerts.
            $anyfired = false;
            // Get aliases.
            $alias = db_get_row('tagente', 'id_agente', $agent['id_agente']);
            echo '<tr>';
            // Name of the agent.
            echo '<td style="text-align: left;font-weight: bold;color: #3f3f3f;">'.$alias['alias'].'</td>';
            // Alerts of the agent.
            foreach ($templates as $tid => $tname) {
                $anyfired = 0;

                if ($tname == '') {
                    continue;
                }

                echo '<td class="center">';

                if (isset($agent_alerts[$alias['alias']][$tid])) {
                    $uniqid = uniqid();

                    html_print_anchor(
                        [
                            'href'    => sprintf('javascript:show_alerts_details(\'%s\')', $uniqid),
                            'content' => html_print_div(
                                [
                                    'id'      => 'line_header_'.$temp['id'],
                                    'style'   => 'font-size: 13pt;',
                                    'content' => count($agent_alerts[$alias['alias']][$tid]),
                                ],
                                true
                            ),
                        ]
                    );

                    $this->printAlertsSummaryModalWindow($uniqid, $agent_alerts[$alias['alias']][$tid]);
                }

                echo '</td>';
            }

            echo '</tr>';
        }

        echo '</table>';

        html_print_action_buttons(
            '',
            ['right_content' => $tablePagination]
        );
    }


    /**
     * Show headers and filters
     *
     * @return void
     */
    public function loadHeader()
    {
        if ($this->pure == 0) {
            // Header.
            ui_print_standard_header(
                __('Agents/Alerts'),
                '',
                false,
                '',
                false,
                [],
                [
                    [
                        'link'  => '',
                        'label' => __('Monitoring'),
                    ],
                    [
                        'link'  => '',
                        'label' => __('Views'),
                    ],
                ]
            );
        }

        // Start Header form.
        $headerForm = [
            'action'   => ui_get_full_url('index.php?sec=view&sec2=extensions/agents_alerts'),
            'id'       => 'form-header-filters',
            'method'   => 'POST',
            'class'    => 'modal flex flex-row',
            'extra'    => '',
            'onsubmit' => '',
        ];

        $headerInputs = [];

        $headerInputs[] = [
            'label'     => '<span class="font-title-font">'.__('Group').'</span>',
            'id'        => 'select-group-id',
            'arguments' => [
                'name'        => 'group-id',
                'type'        => 'select_groups',
                'input_class' => 'flex-row',
                'class'       => '',
                'privilege'   => 'AR',
                'nothing'     => false,
                'selected'    => $this->groupId,
                'return'      => true,
                'script'      => 'this.form.submit()',
                'size'        => '100%',
            ],
        ];

        $headerInputs[] = [
            'label'     => '<span class="font-title-font label-alert-agent">'.__('Show modules without alerts').'</span>',
            'id'        => 'txt-use-agent-ip',
            'class'     => 'display-grid mrgn_lft_15px mrgn_btn_5px',
            'arguments' => [
                'name'            => 'show-modules-without-alerts',
                'checked'         => $this->showWithoutAlertModules,
                'input_class'     => 'flex-row',
                'type'            => 'checkbox',
                'class'           => '',
                'disabled_hidden' => true,
                'return'          => true,
            ],
        ];

        /*
            if ($this->pure == 0) {
            $screenSwitchTitle  = __('Full screen mode');
            $screenSwitchClass  = 'pure_full';
            $screenSwitchPure   = 1;
            $refreshVisibility   = false;
            } else {
            $screenSwitchTitle  = __('Back to normal mode');
            $screenSwitchClass  = 'pure_normal';
            $screenSwitchPure   = 0;
            $refreshVisibility   = true;
            }

            $refreshComboRates = [
            '30'                       => __('30 seconds'),
            (string) SECONDS_1MINUTE   => __('1 minute'),
            (string) SECONDS_2MINUTES  => __('2 minutes'),
            (string) SECONDS_5MINUTES  => __('5 minutes'),
            (string) SECONDS_10MINUTES => __('10 minutes'),
            ];

            $headerInputs[] = [
            'id'        => 'pure',
            'arguments' => [
                'name'   => 'pure',
                'type'   => 'hidden',
                'value'  => $this->pure,
                'return' => true,
            ],
            ];

            $headerInputs[] = [
            'label'          => __('Full screen'),
            'id'             => 'img-full-screen',
            'surround_start' => '<div id="full_screen_refresh_box">',
            'attributes'     => 'style="margin-left: 0px"',
            'arguments'      => [
                'type'       => 'button',
                'return'     => true,
                'label'      => '',
                'name'       => 'pure',
                'attributes' => 'class="full_screen_button '.$screenSwitchClass.'" title="'.$screenSwitchTitle.'"',
            ],
            ];

            $headerInputs[] = [
            'label'        => __('Refresh'),
            'id'           => 'slc-refresh-rate',
            'class'        => ($refreshVisibility === true) ? '' : 'invisible',
            'surround_end' => '</div>',
            'arguments'    => [
                'name'        => 'refresh-rate',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'fields'      => $refreshComboRates,
                'selected'    => $this->refreshSelectedRate,
                'return'      => true,
                'script'      => 'this.form.submit()',
                'sort'        => false,
            ],
            ];

        */

        $filterForm = $this->printForm(
            [
                'form'   => $headerForm,
                'inputs' => $headerInputs,
                true
            ],
            true
        );

        $header = ui_toggle(
            $filterForm,
            '<span class="subsection_header_title">'.__('Filters').'</span>',
            'filter_form',
            '',
            true,
            true,
            '',
            'white-box-content',
            'box-flat white_table_graph fixed_filter_bar'
        );

        echo $header;
    }


    /**
     * Prints the modal window for summary of each alerts group.
     *
     * @param string $id     Id of the agent-alert.
     * @param array  $alerts Alerts.
     *
     * @return void
     */
    private function printAlertsSummaryModalWindow(string $id, array $alerts)
    {
        // Definition of table class.
        $table = new stdClass();

        $table->width = '98%';
        $table->class = 'info_table';
        $table->data = [];

        $table->head[0] = __('Module');
        $table->head[1] = __('Action');
        $table->head[2] = __('Last fired');
        $table->head[3] = __('Status');

        foreach ($alerts as $alert) {
            $data[0] = modules_get_agentmodule_name($alert['id_agent_module']);

            $actions = alerts_get_alert_agent_module_actions($alert['id']);

            $actionDefault = db_get_value_sql(
                '
                SELECT id_alert_action
                FROM talert_templates
                WHERE id = '.$alert['id_alert_template']
            );

            $actionText = '';

            if (!empty($actions)) {
                $actionText = '<div class="mrgn_lft_10px"><ul class="action_list">';
                foreach ($actions as $action) {
                    $actionText .= '<div><span class="action_name"><li>'.$action['name'];
                    if ($action['fires_min'] != $action['fires_max']) {
                        $actionText .= ' ('.$action['fires_min'].' / '.$action['fires_max'].')';
                    }

                    $actionText .= '</li></span><br /></div>';
                }

                $actionText .= '</ul></div>';
            } else {
                if (!empty($actionDefault)) {
                    $actionText = db_get_sql(
                        "SELECT name
                        FROM talert_actions
                        WHERE id = $actionDefault"
                    ).' <i>('.__('Default').')</i>';
                }
            }

            $data[1] = $actionText;
            $data[2] = ui_print_timestamp($alert['last_fired'], true);

            if ($alert['times_fired'] > 0) {
                $status = STATUS_ALERT_FIRED;
                $title = __('Alert fired').' '.$alert['internal_counter'].' '.__('time(s)');
            } else if ($alert['disabled'] > 0) {
                $status = STATUS_ALERT_DISABLED;
                $title = __('Alert disabled');
            } else {
                $status = STATUS_ALERT_NOT_FIRED;
                $title = __('Alert not fired');
            }

            $data[3] = ui_print_status_image($status, $title, true);

            array_push($table->data, $data);
        }

        $content = html_print_table($table, true);

        $agent = modules_get_agentmodule_agent_alias($alerts[0]['id_agent_module']);
        $template = alerts_get_alert_template_name($alerts[0]['id_alert_template']);

        // Prints the modal window hidden for default.
        echo html_print_div(
            [
                'id'      => 'alerts_details_'.$id,
                'title'   => sprintf(
                    '%s: %s / %s: %s',
                    __('Agent'),
                    $agent,
                    __('Template'),
                    $template
                ),
                'style'   => 'display:none;',
                'content' => $content,
            ],
            true
        );
    }


    /**
     * Load the JS.
     *
     * @return string Formed string with script.
     */
    public function loadJS()
    {
        $str = '';
        ob_start();
        ?>

        <script type='text/javascript'>
            $(document).ready(function () {
                var pure = $('#hidden-pure');
                var mainForm = $('#form-header-filters');
                var timeout;

                <?php if ($this->pure == 1) { ?>
                    $('.agents_alerts_header').addClass('agent_alerts_header_pure');
                    $('.agents_alerts_header').attr('style', 'display: none');

                    document.onmousemove = function(){
                        clearTimeout(timeout);
                        $('.agents_alerts_header').attr('style', 'display: block');
                        timeout = setTimeout(function(){
                            $('.agents_alerts_header').attr('style', 'display: none');
                        }, 1000)
                    }

                    setTimeout(function(){
                        mainForm.submit();
                    },
                    ($('#refresh-rate').val() * 1000));
                <?php } ?>

                $('.full_screen_button').click(function(){
                    if (pure.val() == '1') {
                        pure.val('0');
                    } else {
                        pure.val('1');
                    }
                    
                    mainForm.submit();
                });

                $('#checkbox-show-modules-without-alerts').click(function(){
                    mainForm.submit();
                });

            });

            function show_alerts_details(id) {
                $("#alerts_details_"+id).dialog({
                    resizable: true,
                    draggable: true,
                    modal: true,
                    height: 280,
                    width: 800,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    }
                });
            }
            
            function show_add_alerts(id) {
                $("#add_alerts_dialog_"+id).dialog({
                    resizable: true,
                    draggable: true,
                    modal: true,
                    height: 370,
                    width: 450,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    }
                });
            }       
        </script>

        <?php
        // Get the JS script.
        $str = ob_get_clean();
        // Return the loaded JS.
        echo $str;
        return $str;

    }


}