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
 * Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
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
require_once 'include/functions_reporting.php';
require_once 'include/config.php';


use PandoraFMS\Module;

/**
 * AgentWizard class
 */
class AgentsAlerts extends HTML
{

    /**
     * Var that contain very cool stuff
     *
     * @var string
     */
    private $ajaxController;

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
     * @var [type]
     */
    private $createAlert;

    /**
     * Full view parameter.
     */
    private $pure;


    /**
     * Constructor
     *
     * @param string $ajaxController Path.
     *
     * @return $this
     */
    public function __construct(string $ajaxController)
    {
        global $config;

        // Check access.
        check_login();

        if (! check_acl($config['id_user'], 0, 'AR')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access event viewer'
            );

            if (is_ajax() === true) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        // Capture all parameters before start.
        $this->ajaxController = $ajax_controller;
        // Refresh rate.
        $this->refreshSelectedRate = (string) get_parameter('refresh-rate', '30');
        // Show modules without alerts.
        $this->showWithoutAlertModules = (bool) get_parameter('show-modules', false);
        // Selected group.
        $this->groupId = (int) get_parameter('groupId', 0);
        // Create alert token.
        $this->createAlert = (int) get_parameter('create_alert', 0);
        // View token (for full screen view).
        $this->pure = (int) get_parameter('full-screen-action', 0);

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
        $this->loadJS();
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');
        // Add operation menu option.
        extensions_add_operation_menu_option(
            __('Agents/Alerts view'),
            'estado',
            null,
            'v1r1',
            'view'
        );
        // Load the header.
        // extensions_add_main_function($this->loadHeader());
        $this->loadHeader();
        // Load table.

    }


    /**
     * Creation of alerts
     *
     * @return void
     */
    private function createAlert()
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
     * Show headers and filters
     *
     * @return string
     */
    public function loadHeader()
    {
        global $config;
        // Update network modules for this group
        // Check for Network FLAG change request
        // Made it a subquery, much faster on both the database and server side
        // TODO
        if (isset($_GET['update_netgroup'])) {
            $group = get_parameter_get('update_netgroup', 0);
            if (check_acl($config['id_user'], $group, 'AW')) {
                $where = ['id_agente' => 'ANY(SELECT id_agente FROM tagente WHERE id_grupo = '.$group];

                db_process_sql_update('tagente_modulo', ['flag' => 1], $where);
            } else {
                db_pandora_audit('ACL Violation', 'Trying to set flag for groups');
                include 'general/noaccess.php';
                exit;
            }
        }

        $updated_info = '';

        if ($config['realtimestats'] == 0) {
            $updated_info = __('Last update').' : '.ui_print_timestamp(db_get_sql('SELECT min(utimestamp) FROM tgroup_stat'), true);
        }

        $updated_time = $updated_info;

        if ($create_alert) {
            $this->createAlert();
        }

        // Get parameters.
        $offset     = (int) get_parameter('offset', 0);
        $hor_offset = (int) get_parameter('hor_offset', 0);
        // Magic number?
        $block      = 20;
        $groups     = users_get_groups();

        // Breadcrums.
        $this->setBreadcrum([]);

        $this->prepareBreadcrum(
            [
                [
                    'link'     => '',
                    'label'    => __('Monitoring'),
                    'selected' => false,
                ],
                [
                    'link'     => '',
                    'label'    => __('Views'),
                    'selected' => true,
                ],
            ],
            true
        );

        ui_print_page_header(
            __('Agents/Alerts'),
            '',
            false,
            '',
            true,
            '',
            false,
            '',
            GENERIC_SIZE_TEXT,
            '',
            $this->printHeader(true)
        );

        // Start Filter form.
        $headerForm = [
            'action' => ui_get_full_url(),
            'id'     => 'form-refresh-rate',
            'method' => 'POST',
            'class'  => 'modal flex flex-row',
            'extra'  => '',
        ];

        $headerInputs = [];

        $headerInputs[] = [
            'label'     => __('Group'),
            'id'        => 'select-filter-groups',
            'arguments' => [
                'name'        => 'filter-groups',
                'type'        => 'select_groups',
                'input_class' => 'flex-row',
                'class'       => '',
                'privilege'   => 'AR',
                'nothing'     => false,
                'selected'    => $this->groupId,
                'return'      => true,
                'size'        => '100%',
            ],
        ];

        $headerInputs[] = [
            'label'     => __('Show modules without alerts'),
            'id'        => 'txt-use-agent-ip',
            'arguments' => [
                'name'        => 'slides_ids[]',
                'value'       => $this->showWithoutAlertModules,
                'input_class' => 'flex-row',
                'type'        => 'checkbox',
                'class'       => '',
                'return'      => true,
            ],
        ];

        $selectedFullScreen = get_parameter('btn-full-screen', null);
        $screenStatus = $config['pure'];

        if ($selectedFullScreen == null) {
            $selectedFullScreen = $screenStatus;
        }

        if ($selectedFullScreen == 0) {
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
                'value'  => $config['pure'],
                'return' => true,
            ],
        ];

        $headerInputs[] = [
            'label'     => __('Refresh'),
            'id'        => 'slc-refresh-rate',
            'class'     => ($refreshVisibility === true) ? '' : 'invisible',
            'arguments' => [
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

        $headerInputs[] = [
            'label'     => __('Full screen'),
            'id'        => 'img-full-screen',
            'arguments' => [
                'type'       => 'submit',
                'return'     => true,
                'name'       => 'pure',
                'label'      => $screenSwitchPure,
                'attributes' => [
                    'title' => $screenSwitchTitle,
                    'class' => 'full_screen_button '.$screenSwitchClass,
                ],
            ],
        ];

        $filterForm = $this->printForm(
            [
                'form'   => $headerForm,
                'inputs' => $headerInputs,
                true
            ],
            true
        );

        // Prints the header controls.
        $header = html_print_div(
            [
                'class'   => 'white_box',
                'content' => $filterForm,
            ],
            true
        );

        echo $header;

        if ($show_modules) {
            if ($group_id > 0) {
                $grupo = " AND tagente.id_grupo = $group_id";
            } else {
                $grupo = '';
            }

            $offset_modules = get_parameter('offset', 0);
            $sql_count = "SELECT COUNT(tagente_modulo.nombre) FROM tagente_modulo 
            INNER JOIN tagente ON tagente.id_agente = tagente_modulo.id_agente
            WHERE id_agente_modulo NOT IN (SELECT id_agent_module FROM talert_template_modules) 
            $grupo";
            $count_agent_module = db_get_all_rows_sql($sql_count);

            $sql = "SELECT tagente.alias, tagente_modulo.nombre, 
            tagente_modulo.id_agente_modulo FROM tagente_modulo 
            INNER JOIN tagente ON tagente.id_agente = tagente_modulo.id_agente
            WHERE id_agente_modulo NOT IN (SELECT id_agent_module FROM talert_template_modules) 
            $grupo LIMIT 20 OFFSET $offset_modules";
            $agent_modules = db_get_all_rows_sql($sql);

            ui_pagination(
                $count_agent_module[0]['COUNT(tagente_modulo.nombre)'],
                ui_get_url_refresh(),
                0,
                0,
                false,
                'offset',
                true,
                '',
                '',
                false,
                'alerts_modules'
            );

            $table->width = '100%';
            $table->class = 'databox data';
            $table->id = 'table_agent_module';
            $table->data = [];

            $table->head[0] = __('Agents');
            $table->head[1] = __('Modules');
            $table->head[2] = __('Actions');

            $table->style[0] = 'width: 25%;';
            $table->style[1] = 'width: 33%;';
            $table->style[2] = 'width: 33%;';

            foreach ($agent_modules as $agent_module) {
                $data[0] = io_safe_output($agent_module['alias']);
                $data[1] = io_safe_output($agent_module['nombre']);
                $uniqid = $agent_module['id_agente_modulo'];
                $data[2] = "<a title='".__('Create alert')."' href='javascript:show_add_alerts(\"$uniqid\")'>".html_print_image('images/add_mc.png', true).'</a>';
                array_push($table->data, $data);

                $table2->width = '100%';
                $table2->id = 'table_add_alert';
                $table2->class = 'databox filters';
                $table2->data = [];
                // $data[0] =
                $table2->data[0][0] = __('Actions');

                $groups_user = users_get_groups($config['id_user']);
                if (!empty($groups_user)) {
                    $groups = implode(',', array_keys($groups_user));
                    $sql = "SELECT id, name FROM talert_actions WHERE id_group IN ($groups)";
                    $actions = db_get_all_rows_sql($sql);
                }

                $table2->data[0][1] = html_print_select(
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
                    'width: 250px;'
                );
                $table2->data[0][1] .= '<span id="advanced_action" class="advanced_actions invisible"><br>';
                $table2->data[0][1] .= __('Number of alerts match from').' ';
                $table2->data[0][1] .= html_print_input_text('fires_min', '', '', 4, 10, true);
                $table2->data[0][1] .= ' '.__('to').' ';
                $table2->data[0][1] .= html_print_input_text('fires_max', '', '', 4, 10, true);
                $table2->data[0][1] .= ui_print_help_icon(
                    'alert-matches',
                    true,
                    ui_get_full_url(false, false, false, false)
                );
                $table2->data[0][1] .= '</span>';
                if (check_acl($config['id_user'], 0, 'LM')) {
                    $table2->data[0][1] .= '<a style="margin-left:5px;" href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action&pure='.$pure.'">';
                    $table2->data[0][1] .= html_print_image('images/add.png', true);
                    $table2->data[0][1] .= '<span style="margin-left:5px;vertical-align:middle;">'.__('Create Action').'</span>';
                    $table2->data[0][1] .= '</a>';
                }

                $table2->data[1][0] = __('Template');
                $own_info = get_user_info($config['id_user']);
                if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
                    $templates = alerts_get_alert_templates(false, ['id', 'name']);
                } else {
                    $usr_groups = users_get_groups($config['id_user'], 'LW', true);
                    $filter_groups = '';
                    $filter_groups = implode(',', array_keys($usr_groups));
                    $templates = alerts_get_alert_templates(['id_group IN ('.$filter_groups.')'], ['id', 'name']);
                }

                $table2->data[1][1] = html_print_select(
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
                    'width: 250px;'
                );
                $table2->data[1][1] .= ' <a class="template_details invisible" href="#">'.html_print_image('images/zoom.png', true, ['class' => 'img_help']).'</a>';
                if (check_acl($config['id_user'], 0, 'LM')) {
                    $table2->data[1][1] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&pure='.$pure.'">';
                    $table2->data[1][1] .= html_print_image('images/add.png', true);
                    $table2->data[1][1] .= '<span style="margin-left:5px;vertical-align:middle;">'.__('Create Template').'</span>';
                    $table2->data[1][1] .= '</a>';
                }

                $table2->data[2][0] = __('Threshold');
                $table2->data[2][1] = html_print_input_text('module_action_threshold', '0', '', 5, 7, true);
                $table2->data[2][1] .= ' '.__('seconds');

                $content2 = '<form class="add_alert_form" method="post">';
                $content2 .= html_print_table($table2, true);

                $content2 .= '<div class="action-buttons" style="width: '.$table2->width.'">';
                $content2 .= html_print_submit_button(__('Add alert'), 'add', false, 'class="sub wand"', true);
                $content2 .= html_print_input_hidden('create_alert', $uniqid, true);
                $content2 .= '</div></form>';

                $module_name = ui_print_truncate_text(io_safe_output($agent_module['nombre']), 40, false, true, false, '&hellip;', false);
                echo '<div id="add_alerts_dialog_'.$uniqid.'" title="'.__('Agent').': '.$agent_module['alias'].' / '.__('module').': '.$module_name.'" style="display:none">'.$content2.'</div>';
            }

            html_print_table($table);
        } else {
            $filter = [
                'offset' => (int) $offset,
                'limit'  => (int) $config['block_size'],
            ];
            $filter_count = [];

            if ($group_id > 0) {
                $filter['id_grupo'] = $group_id;
                $filter_count['id_grupo'] = $group_id;
            }

            // Get the id of all agents with alerts
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

            $filter['id_agente'] = $agents_with_alerts;
            $filter_count['id_agente'] = $agents_with_alerts;

            $agents = agents_get_agents($filter);

            $nagents = count(agents_get_agents($filter_count));

            if ($agents == false) {
                ui_print_info_message(
                    [
                        'no_close' => true,
                        'message'  => __('There are no agents with alerts'),
                    ]
                );

                return false;
            }

            $all_alerts = agents_get_alerts_simple();

            if ($config['pure'] == 1) {
                $block = count($all_alerts);
            }

            $templates = [];
            $agent_alerts = [];
            foreach ($all_alerts as $alert) {
                $templates[$alert['id_alert_template']] = '';
                $agent_alerts[$alert['agent_name']][$alert['id_alert_template']][] = $alert;
            }

            // Prepare pagination.
            ui_pagination(
                $nagents,
                false,
                0,
                3,
                false,
                'offset',
                true,
                '',
                '',
                [
                    'count'  => '',
                    'offset' => 'offset_param',
                ],
                'alerts_agents'
            );

            echo '<table cellpadding="4" cellspacing="4" border="0" style="width:100%;" class="agents_modules_table">';
            echo '<tr>';
            echo '<th style="text-align: right !important; padding-right:13px;">'.__('Agents').' / '.__('Alerts').'</th>';

            $templates_raw = [];
            if (!empty($templates)) {
                $sql = sprintf(
                    'SELECT id, name
                    FROM talert_templates
                    WHERE id IN (%s)',
                    implode(',', array_keys($templates))
                );

                $templates_raw = db_get_all_rows_sql($sql);
            }

            if (empty($templates_raw)) {
                $templates_raw = [];
            }

            $alerts = [];
            $ntemplates = 0;
            if ($hor_offset > 0) {
                $new_hor_offset = ($hor_offset - $block);
                echo "<th width='20px' style='' rowspan='".($nagents + 1)."'>
                        <a href='index.php?sec=extensions&sec2=extensions/agents_alerts&refr=0&hor_offset=".$new_hor_offset.'&offset='.$offset.'&group_id='.$group_id."'>".html_print_image('images/arrow_left_green.png', true, ['style' => 'float: right;', 'title' => __('Previous templates')]).'</a> </th>';
            }

            foreach ($templates_raw as $temp) {
                if (isset($templates[$temp['id']]) && $templates[$temp['id']] == '') {
                    $ntemplates++;
                    if ($ntemplates <= $hor_offset || $ntemplates > ($hor_offset + $block)) {
                        continue;
                    }

                    $templates[$temp['id']] = $temp['name'];

                    if (empty($temp['name']) === false) {
                        $outputLine = html_print_div(
                            [
                                'id'      => 'line_header_'.$temp['id'],
                                'class'   => 'rotate_text_module',
                                'content' => '<span title="'.io_safe_output($temp['name']).'">'.ui_print_truncate_text(io_safe_output($temp['name']), 20).'</span>',
                            ],
                            true
                        );

                        echo sprintf('<th style="width:30px;height:200px;">%s</th>', $outputLine);
                    }
                }
            }

            if (($hor_offset + $block) < $ntemplates) {
                $new_hor_offset = ($hor_offset + $block);
                echo "<th width='20px' style='' rowspan='".($nagents + 1)."'>";
                echo "<a href='index.php?sec=extensions&sec2=extensions/agents_alerts&hor_offset=".$new_hor_offset.'&offset='.$offset.'&group_id='.$group_id."'>".html_print_image('images/arrow_right_green.png', true, ['style' => 'float: right;', 'title' => __('More templates')]).'</a>';
                echo '</th>';
            }

            echo '</tr>';

            foreach ($agents as $agent) {
                $alias = db_get_row('tagente', 'id_agente', $agent['id_agente']);
                echo '<tr>';
                // Name of the agent.
                echo '<td style="font-weight:bold;text-align: right;">'.$alias['alias'].'</td>';

                // Alerts of the agent.
                $anyfired = false;
                foreach ($templates as $tid => $tname) {
                    if ($tname == '') {
                        continue;
                    }

                    echo '<td style="text-align: center;">';

                    if (isset($agent_alerts[$agent['nombre']][$tid])) {
                        foreach ($agent_alerts[$agent['nombre']][$tid] as $alert) {
                            if ($alert['times_fired'] > 0) {
                                $anyfired = true;
                            }
                        }

                        $cellstyle = '';
                        if ($anyfired) {
                            $cellstyle = 'background:'.COL_ALERTFIRED.';';
                        } else {
                            $cellstyle = 'background:'.COL_NORMAL.';';
                        }

                        $uniqid = uniqid();

                        $row = '<a href=\'javascript:show_alerts_details("'.$uniqid.'")\'>';

                        $row .= html_print_div(
                            [
                                'id'      => 'line_header_'.$temp['id'],
                                'class'   => 'status_rounded_rectangles text_inside',
                                'style'   => $cellstyle,
                                'content' => count($agent_alerts[$agent['nombre']][$tid]),
                            ],
                            true
                        );

                        $row .= '</a>';

                        echo $row;

                        $this->printAlertsSummaryModalWindow($uniqid, $agent_alerts[$agent['nombre']][$tid]);
                    }

                    echo '</td>';
                }

                echo '</tr>';
            }

            echo '</table>';
            // echo '</table>';
            ui_pagination(
                $nagents,
                false,
                0,
                0,
                false,
                'offset',
                true,
                'pagination-bottom',
                '',
                [
                    'count'  => '',
                    'offset' => 'offset_param',
                ],
                'alerts_agents'
            );
        }

        // return $header;

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
                $actionText = '<div style="margin-left: 10px;"><ul class="action_list">';
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
     * Show filters.
     *
     * @return void
     */
    public function loadFilter()
    {

    }


    /**
     * Show table with results.
     *
     * @return void
     */
    public function loadTable()
    {
        // $hor_offset
        // $block
        // $nagents
        // $modules_by_name
        // $group_id
        // $agents
        // $total_pagination
        //
        echo '<table cellpadding="4" cellspacing="4" border="0" style="width:100%;" class="agents_modules_table">';

        echo '<tr>';

        echo "<th width='140px' style='text-align: right !important; padding-right:13px;'>".__('Agents').' / '.__('Alerts').'</th>';

        if ($hor_offset > 0) {
            $new_hor_offset = ($hor_offset - $block);
            echo "<th width='20px' style='vertical-align: middle; text-align: center;' rowspan='".($nagents + 1)."'><a href='index.php?".'extension_in_menu=estado&sec=extensions&sec2=extensions/agents_modules&refr=0&save_serialize=1&selection_a_m='.$selection_a_m.'&hor_offset='.$new_hor_offset.'&offset='.$offset."'>".html_print_image(
                'images/arrow_left_green.png',
                true,
                ['title' => __('Previous modules')]
            ).'</a></th>';
        }

        $nmodules = 0;
        foreach ($modules_by_name as $module) {
            $nmodules++;

            if ($nmodules <= $hor_offset || $nmodules > ($hor_offset + $block)) {
                continue;
            }

            $text = ui_print_truncate_text(io_safe_output($module['name']), 'module_small');

            echo '<th align="center" width="20px" id="th_module_r_'.$nmodules.'" class="th_class_module_r">
                    <div style="width: 30px;">
                        <div id="div_module_r_'.$nmodules.'" style="display: none;padding-left:10px" class="rotate_text_module">'.$text.'</div>
                    </div>
                </th>';
        }

        if (($hor_offset + $block) < $nmodules) {
            $new_hor_offset = ($hor_offset + $block);
            echo "<th width='20px' style='vertical-align: middle; text-align: center;' rowspan='".($nagents + 1)."'><a href='index.php?".'extension_in_menu=estado&sec=extensions&sec2=extensions/agents_modules&save_serialize=1&selection_a_m='.$selection_a_m.'&hor_offset='.$new_hor_offset.'&offset='.$offset."'>".html_print_image(
                'images/arrow_right_green.png',
                true,
                ['title' => __('More modules')]
            ).'</a></th>';
        }

        echo '</tr>';

        $filter_agents = [
            'offset'   => (int) $offset,
            'disabled' => 0,
        ];
        if ($group_id > 0) {
            $filter_agents['id_grupo'] = $group_id;
        }

        // Prepare pagination.
        $url = 'index.php?extension_in_menu=estado&sec=extensions&sec2=extensions/agents_modules&save_serialize=1&hor_offset='.$hor_offset.'&selection_a_m='.$selection_a_m;
        ui_pagination($total_pagination, $url);

        foreach ($agents as $agent) {
            // Get stats for this group.
            $agent_status = agents_get_status($agent['id_agente']);
            $alias = db_get_row('tagente', 'id_agente', $agent['id_agente']);
            if (empty($alias['alias'])) {
                $alias['alias'] = $agent['nombre'];
            }

            switch ($agent_status) {
                case 4:
                    // Alert fired status.
                    $rowcolor = 'group_view_alrm';
                break;

                case 1:
                    // Critical status.
                    $rowcolor = 'group_view_crit';
                break;

                case 2:
                    // Warning status.
                    $rowcolor = 'group_view_warn';
                break;

                case 0:
                    // Normal status.
                    $rowcolor = 'group_view_ok';
                break;

                case 3:
                case -1:
                default:
                    // Unknown status.
                    $rowcolor = 'group_view_unk';
                break;
            }

            echo "<tr style='height: 25px;'>";

            echo "<td class='$rowcolor'>
                <a class='$rowcolor' href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$agent['id_agente']."'>".$alias['alias'].'</a></td>';
            // TODO TAGS agents_get_modules.
            $agent_modules = agents_get_modules($agent['id_agente'], false, $filter_module_group, true, true);

            $nmodules = 0;
            foreach ($modules_by_name as $module) {
                $nmodules++;

                if ($nmodules <= $hor_offset || $nmodules > ($hor_offset + $block)) {
                    continue;
                }

                $match = false;

                foreach ($module['id'] as $module_id) {
                    if (!$match && array_key_exists($module_id, $agent_modules)) {
                        $status = modules_get_agentmodule_status($module_id);
                        echo "<td style='text-align: center;'>";
                        $win_handle = dechex(crc32($module_id.$module['name']));
                        $graph_type = return_graphtype(modules_get_agentmodule_type($module_id));
                        $link = "winopeng_var('".'operation/agentes/stat_win.php?'."type=$graph_type&".'period='.SECONDS_1DAY.'&'.'id='.$module_id.'&'.'refresh='.SECONDS_10MINUTES."', 'day_".$win_handle."', 800, 480)";

                        echo '<a href="javascript:'.$link.'">';

                        $module_last_value = modules_get_last_value($module_id);
                        if (!is_numeric($module_last_value)) {
                            $module_last_value = htmlspecialchars($module_last_value);
                        }

                        switch ($status) {
                            case AGENT_MODULE_STATUS_NORMAL:
                                ui_print_status_image('module_ok.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                                ui_print_status_image('module_critical.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_WARNING:
                                ui_print_status_image('module_warning.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_UNKNOWN:
                                ui_print_status_image('module_unknown.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            case AGENT_MODULE_STATUS_WARNING_ALERT:
                            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                                ui_print_status_image('module_alertsfired.png', $module_last_value, false);
                            break;

                            case 4:
                                ui_print_status_image('module_no_data.png', $module_last_value, false);
                            break;
                        }

                        echo '</a>';
                        echo '</td>';
                        $match = true;
                    }
                }

                if (!$match) {
                    echo '<td></td>';
                }
            }

            echo '</tr>';
        }

        echo '</table>';
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
                //Get max width of name of modules
                max_width = 0;
                $.each($('.th_class_module_r'), function (i, elem) {
                    id = $(elem).attr('id').replace('th_module_r_', '');
                    
                    width = $("#div_module_r_" + id).width();
                    
                    if (max_width < width) {
                        max_width = width;
                    } 
                });
                
                $.each($('.th_class_module_r'), function (i, elem) {
                    id = $(elem).attr('id').replace('th_module_r_', '');
                    $("#th_module_r_" + id).height(($("#div_module_r_" + id).width() + 10) + 'px');
                    $("#div_module_r_" + id).css('margin-top', (max_width - 20) + 'px');
                    $("#div_module_r_" + id).show();
                });

                var refr = '<?php echo get_parameter('refresh', 0); ?>';
                var pure = '<?php echo get_parameter('pure', 0); ?>';
                var href =' <?php echo ui_get_url_refresh($ignored_params); ?>';

                if (pure) {
                    var startCountDown = function (duration, cb) {
                        $('div.vc-countdown').countdown('destroy');
                        if (!duration) return;
                        var t = new Date();
                        t.setTime(t.getTime() + duration * 1000);
                        $('div.vc-countdown').countdown({
                            until: t,
                            format: 'MS',
                            layout: '(%M%nn%M:%S%nn%S <?php echo __('Until next'); ?>) ',
                            alwaysExpire: true,
                            onExpiry: function () {
                                $('div.vc-countdown').countdown('destroy');
                                url = js_html_entity_decode( href ) + duration;
                                $(document).attr ("location", url);
                            }
                        });
                    }

                    if(refr>0){
                        startCountDown(refr, false);
                    }

                    var controls = document.getElementById('vc-controls');
                    autoHideElement(controls, 1000);
                    
                    $('select#refresh').change(function (event) {
                        refr = Number.parseInt(event.target.value, 10);
                        startCountDown(refr, false);
                    });
                }
                else {
                    
                    var agentes_id = $("#id_agents2").val();
                    var id_agentes = getQueryParam("full_agents_id");
                    if (agentes_id === null && id_agentes !== null) {
                        id_agentes = id_agentes.split(";")
                        id_agentes.forEach(function(element) {
                            $("#id_agents2 option[value="+ element +"]").attr("selected",true);
                        });
                        
                        selection_agent_module();
                    }
                    
                    $('#refresh').change(function () {
                        $('#hidden-vc_refr').val($('#refresh option:selected').val());
                    });
                }
                
                $("#group_id").change (function () {
                    jQuery.post ("ajax.php",
                        {"page" : "operation/agentes/ver_agente",
                            "get_agents_group_json" : 1,
                            "id_group" : this.value,
                            "privilege" : "AW",
                            "keys_prefix" : "_",
                            "recursion" : $('#checkbox-recursion').is(':checked')
                        },
                        function (data, status) {
                            $("#id_agents2").html('');
                            $("#module").html('');
                            jQuery.each (data, function (id, value) {
                                // Remove keys_prefix from the index
                                id = id.substring(1);
                                
                                option = $("<option></option>")
                                    .attr ("value", value["id_agente"])
                                    .html (value["alias"]);
                                $("#id_agents").append (option);
                                $("#id_agents2").append (option);
                            });
                        },
                        "json"
                    );
                });
                
                $("#checkbox-recursion").change (function () {
                    jQuery.post ("ajax.php",
                        {"page" : "operation/agentes/ver_agente",
                            "get_agents_group_json" : 1,
                            "id_group" :     $("#group_id").val(),
                            "privilege" : "AW",
                            "keys_prefix" : "_",
                            "recursion" : $('#checkbox-recursion').is(':checked')
                        },
                        function (data, status) {
                            $("#id_agents2").html('');
                            $("#module").html('');
                            jQuery.each (data, function (id, value) {
                                // Remove keys_prefix from the index
                                id = id.substring(1);
                                
                                option = $("<option></option>")
                                    .attr ("value", value["id_agente"])
                                    .html (value["alias"]);
                                $("#id_agents").append (option);
                                $("#id_agents2").append (option);
                            });
                        },
                        "json"
                    );
                });
                
                $("#modulegroup").change (function () {
                    jQuery.post ("ajax.php",
                        {"page" : "operation/agentes/ver_agente",
                            "get_modules_group_json" : 1,
                            "id_module_group" : this.value,
                            "id_agents" : $("#id_agents2").val(),
                            "selection" : $("#selection_agent_module").val()
                        },
                        function (data, status) {
                            $("#module").html('');
                            if(data){
                                jQuery.each (data, function (id, value) {
                                    option = $("<option></option>")
                                        .attr ("value", value["id_agente_modulo"])
                                        .html (value["nombre"]);
                                    $("#module").append (option);
                                });
                            }
                        },
                        "json"
                    );
                });

                $("#id_agents2").click (function(){
                    selection_agent_module();
                });

                $("#selection_agent_module").change(function() {
                    jQuery.post ("ajax.php",
                        {"page" : "operation/agentes/ver_agente",
                            "get_modules_group_json" : 1,
                            "id_module_group" : $("#modulegroup").val(),
                            "id_agents" : $("#id_agents2").val(),
                            "selection" : $("#selection_agent_module").val()
                        },
                        function (data, status) {
                            $("#module").html('');
                            if(data){
                                jQuery.each (data, function (id, value) {
                                    option = $("<option></option>")
                                        .attr ("value", value["id_agente_modulo"])
                                        .html (value["nombre"]);
                                    $("#module").append (option);
                                });
                            }
                        },
                        "json"
                    );
                });
            });

            function selection_agent_module() {
                jQuery.post ("ajax.php",
                    {"page" : "operation/agentes/ver_agente",
                        "get_modules_group_json" : 1,
                        "id_module_group" : $("#modulegroup").val(),
                        "id_agents" : $("#id_agents2").val(),
                        "selection" : $("#selection_agent_module").val()
                    },
                    function (data, status) {
                        $("#module").html('');
                        if(data){
                            jQuery.each (data, function (id, value) {
                                option = $("<option></option>")
                                    .attr ("value", value["id_agente_modulo"])
                                    .html (value["nombre"]);
                                $("#module").append (option);
                            });
                            
                            var id_modules = getQueryParam("full_modules_selected");
                            if(id_modules !== null) {
                                id_modules = id_modules.split(";");
                                id_modules.forEach(function(element) {
                                    $("#module option[value="+ element +"]").attr("selected",true);
                                });
                            }
                        }
                    },
                    "json"
                );
            }



            function getQueryParam (key) {  
                var pattern = "[?&]" + key + "=([^&#]*)";  
                var regex = new RegExp(pattern);
                var url = unescape(window.location.href);
                var results = regex.exec(url);
                if (results === null) {  
                    return null;  
                } else {  
                    return results[1];  
                } 
            }

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
                    height: 235,
                    width: 600,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    }
                });
            }
            
            // checkbox-slides_ids
            $(document).ready(function () {
                $('#checkbox-slides_ids').click(function(){
                    if ($('#checkbox-slides_ids').prop('checked')){
                        var url = location.href.replace("&show_modules=true", "");
                        location.href = url+"&show_modules=true";
                    } else {
                        var url = location.href.replace("&show_modules=true", "");
                        var re = /&offset=\d*/g;
                        location.href = url.replace(re, "");
                    }
                });
                
                $('#group_id').change(function(){
                    if(location.href.indexOf("extensions/agents_modules") == -1){
                        var regx = /&group_id=\d*/g;
                        var url = location.href.replace(regx, "");
                        location.href = url+"&group_id="+$("#group_id").val();
                    }
                });

            });
            
        </script>

        <?php
        // Get the JS script.
        $str = ob_get_clean();
        // Return the loaded JS.
        echo $str;
        return $str;

    }


}