<?php
/**
 * Events Mobile.
 *
 * @category   Events
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

use PandoraFMS\Enterprise\Metaconsole\Node;
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Event class mobile.
 */
class Events
{

    /**
     * Acl.
     *
     * @var boolean
     */
    private $correct_acl = false;

    /**
     * Acl mode.
     *
     * @var string
     */
    private $acl = 'ER';

    /**
     * Default
     *
     * @var boolean
     */
    private $default = true;

    /**
     * Default filters.
     *
     * @var array
     */
    private $default_filters = [];

    /**
     * Search.
     *
     * @var string
     */
    private $free_search = '';

    /**
     * Hours.
     *
     * @var integer
     */
    private $hours_old = 8;

    /**
     * Status.
     *
     * @var integer
     */
    private $status = 3;

    /**
     * Type.
     *
     * @var string
     */
    private $type = 'all';

    /**
     * Severity.
     *
     * @var integer
     */
    private $severity = -1;

    /**
     * Filter.
     *
     * @var integer
     */
    private $filter = 0;

    /**
     * Group.
     *
     * @var integer
     */
    private $group = 0;

    /**
     * Agent.
     *
     * @var integer
     */
    private $id_agent = 0;

    /**
     * Columns.
     *
     * @var [type]
     */
    private $columns = null;


    /**
     * Construcy
     */
    public function __construct()
    {
        $system = System::getInstance();

        $this->columns = ['agent' => 1];

        if ($system->checkACL($this->acl)) {
            $this->correct_acl = true;
        } else {
            $this->correct_acl = false;
        }
    }


    /**
     * Set read only
     *
     * @return void
     */
    public function setReadOnly()
    {
        $this->readOnly = true;
    }


    /**
     * Ajax function.
     *
     * @param boolean $parameter2 Rarameters.
     *
     * @return void
     */
    public function ajax($parameter2=false)
    {
        $system = System::getInstance();

        if ($this->correct_acl === false) {
            return;
        } else {
            switch ($parameter2) {
                case 'get_events':
                    if ($system->getRequest('agent_events', '0') == 1) {
                        $this->disabledColumns(['agent']);
                        $filters = ['id_agent' => $system->getRequest('id_agent', 0)];
                        $this->setFilters($filters);
                        $this->setReadOnly();
                    }

                    $this->eventsGetFilters();

                    $page = $system->getRequest('page', 0);

                    $system = System::getInstance();

                    $listEvents = $this->getListEvents($page);
                    $events_db = $listEvents['events'];
                    $total_events = $listEvents['total'];

                    $events = [];
                    $end = 1;

                    foreach ($events_db as $event) {
                        $end = 0;

                        switch ($event['estado']) {
                            case 0:
                                $img_st = 'images/star-dark.svg';
                            break;

                            case 1:
                                $img_st = 'images/validate.svg';
                            break;

                            case 2:
                                $img_st = 'images/clock.svg';
                            break;

                            default:
                                // Not possible.
                            break;
                        }

                        if ($event['criticity'] === EVENT_CRIT_WARNING
                            || $event['criticity'] === EVENT_CRIT_MAINTENANCE
                            || $event['criticity'] === EVENT_CRIT_MINOR
                        ) {
                            $img_st = str_replace('white.png', 'dark.png', $img_st);
                        }

                        $status_icon = html_print_image(
                            $img_st,
                            true,
                            ['class' => 'main_menu_icon'],
                            false,
                            false,
                            false,
                            true
                        );

                        if (isset($event['server_id']) === false) {
                            $event['server_id'] = 0;
                        }

                        $row = [];
                        $row_0 = '<b class="ui-table-cell-label">';
                        $row_0 .= __('Event Name');
                        $row_0 .= '</b><a href="#" onclick="openDetails('.$event['id_evento'].','.$event['server_id'].')">';
                        $row_0 .= '<div class="event_name">';
                        $row_0 .= io_safe_output(
                            str_replace(['&nbsp;', '&#20;'], ' ', $event['evento'])
                        );
                        $row_0 .= '</div></a>';

                        $row[] = $row_0;

                        if ($event['id_agente'] == 0) {
                            $agent_name = __('System');
                        } else {
                            $agent_name = '<span class="nobold">';
                            $agent_name .= ui_print_agent_name(
                                $event['id_agente'],
                                true,
                                'agent_small',
                                '',
                                false,
                                '',
                                '',
                                false,
                                false
                            );
                            $agent_name .= '</span>';
                        }

                        $row_1 = '<span class="events_agent">'.$agent_name.'</span>';
                        $row_1 .= '<span class="events_timestamp">';
                        $row_1 .= human_time_comparation($event['timestamp_last'], 'tiny');
                        $row_1 .= $status_icon;
                        $row_1 .= '</span>';

                        $row[] = $row_1;

                        $row[] = get_priority_class($event['criticity']);
                        $events[$event['id_evento']] = $row;
                    }

                    echo json_encode(['end' => $end, 'events' => $events]);
                break;

                case 'get_detail_event':
                    $system = System::getInstance();

                    $id_event = $system->getRequest('id_event', 0);
                    $server_id = $system->getRequest('server_id', 0);

                    try {
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node = new Node($server_id);
                            $node->connect();
                        }

                        $event = events_get_event($id_event, false);

                        if ($event !== false
                            && empty($event) === false
                        ) {
                            // Check if it is a event from module.
                            if ($event['id_agentmodule'] > 0) {
                                $event['module_graph_link'] = '<a data-ajax="false" href="index.php?page=module_graph&id='.$event['id_agentmodule'].'">'.html_print_image('images/chart_curve.png', true, ['style' => 'vertical-align: middle;'], false, false, false, true).'</a>';
                            } else {
                                $event['module_graph_link'] = '<i>'.__('N/A').'</i>';
                            }

                            if ($event['id_agente'] > 0) {
                                $event['agent'] = "<a class='black'";
                                $event['agent'] .= "href='index.php?page=agent&id=";
                                $event['agent'] .= $event['id_agente']."'>";
                                $event['agent'] .= agents_get_alias(
                                    $event['id_agente']
                                );
                                $event['agent'] .= '</a>';
                            } else {
                                $event['agent'] = '<i>'.__('N/A').'</i>';
                            }

                            $event['evento'] = io_safe_output(
                                $event['evento']
                            );

                            $event['clean_tags'] = events_clean_tags(
                                $event['tags']
                            );
                            $event['timestamp'] = human_time_comparation($event['utimestamp'], 'tiny');
                            if (empty($event['owner_user']) === true) {
                                $event['owner_user'] = '<i>'.__('N/A').'</i>';
                            } else {
                                $user_owner = db_get_value(
                                    'fullname',
                                    'tusuario',
                                    'id_user',
                                    $event['owner_user']
                                );
                                if (empty($user_owner) === true) {
                                    $user_owner = $event['owner_user'];
                                }

                                $event['owner_user'] = $user_owner;
                            }

                            $event['event_type'] = events_print_type_description(
                                $event['event_type'],
                                true
                            );
                            $event['event_type'] .= ' ';
                            $event['event_type'] .= events_print_type_img(
                                $event['event_type'],
                                true
                            );

                            if (isset($group_rep) === false) {
                                $group_rep = EVENT_GROUP_REP_ALL;
                            }

                            if ((int) $group_rep !== EVENT_GROUP_REP_ALL) {
                                if ($event['event_rep'] <= 1) {
                                    $event['event_repeated'] = '<i>'.__('No').'</i>';
                                } else {
                                    $event['event_repeated'] = sprintf(
                                        '%d Times',
                                        $event['event_rep']
                                    );
                                }
                            } else {
                                $event['event_repeated'] = '<i>'.__('No').'</i>';
                            }

                            $event_criticity = get_priority_name(
                                $event['criticity']
                            );

                            switch ($event['criticity']) {
                                default:
                                case 0:
                                    $img_sev = 'images/status_sets/default/severity_maintenance_rounded.png';
                                break;
                                case 1:
                                    $img_sev = 'images/status_sets/default/severity_informational_rounded.png';
                                break;

                                case 2:
                                    $img_sev = 'images/status_sets/default/severity_normal_rounded.png';
                                break;

                                case 3:
                                    $img_sev = 'images/status_sets/default/severity_warning_rounded.png';
                                break;

                                case 4:
                                    $img_sev = 'images/status_sets/default/severity_critical_rounded.png';
                                break;

                                case 5:
                                    $img_sev = 'images/status_sets/default/severity_minor_rounded.png';
                                break;

                                case 6:
                                    $img_sev = 'images/status_sets/default/severity_major_rounded.png';
                                break;
                            }

                            $event['criticity'] = ' '.$event_criticity;
                            $event['criticity'] .= html_print_image(
                                $img_sev,
                                true,
                                [
                                    'width'  => 30,
                                    'height' => 15,
                                    'title'  => $event_criticity,
                                ],
                                false,
                                false,
                                false,
                                true
                            );

                            if ((int) $event['estado'] === 1) {
                                $user_ack = db_get_value(
                                    'fullname',
                                    'tusuario',
                                    'id_user',
                                    $event['id_usuario']
                                );
                                if (empty($user_ack) === true) {
                                    $user_ack = $event['id_usuario'];
                                }

                                $date_ack = date(
                                    $system->getConfig('date_format'),
                                    $event['ack_utimestamp']
                                );
                                $event['acknowledged_by'] = $user_ack.' ('.$date_ack.')';
                            } else {
                                $event['acknowledged_by'] = '<i>'.__('N/A').'</i>';
                            }

                            // Get Status.
                            switch ($event['estado']) {
                                case 0:
                                    $img_st = 'images/star-dark.svg';
                                    $title_st = __('New event');
                                break;

                                case 1:
                                    $img_st = 'images/validate.svg';
                                    $title_st = __('Event validated');
                                break;

                                case 2:
                                    $img_st = 'images/clock.svg';
                                    $title_st = __('Event in process');
                                break;

                                default:
                                    // Not posible.
                                break;
                            }

                            $event['status'] = $title_st;
                            $event['status'] .= ' ';
                            $event['status'] .= html_print_image(
                                $img_st,
                                true,
                                false,
                                false,
                                false,
                                false,
                                true
                            );

                            $event['group'] = groups_get_name(
                                $event['id_grupo'],
                                true
                            );
                            $event['group'] .= ui_print_group_icon(
                                $event['id_grupo'],
                                true
                            );

                            $event['tags'] = tags_get_tags_formatted(
                                $event['tags']
                            );
                            if (empty($event['tags']) === true) {
                                $event['tags'] = '<i>'.__('N/A').'</i>';
                            }

                            $event_comments = db_get_value(
                                'user_comment',
                                'tevento',
                                'id_evento',
                                $id_event
                            );
                            $event_comments_array = [];
                            $event_comments_array = json_decode(
                                $event_comments,
                                true
                            );
                            // Support for new format only.
                            if (empty($event_comments_array) === true) {
                                $comment = '<i>'.__('N/A').'</i>';
                            } else {
                                $comment = '';
                                $event_comments_array = array_reverse(
                                    $event_comments_array
                                );
                                foreach ($event_comments_array as $c) {
                                    $comment .= date(
                                        $system->getConfig(
                                            'date_format'
                                        ),
                                        $c['utimestamp']
                                    ).' ('.$c['id_user'].')';
                                    $c['comment'] = io_safe_output(
                                        $c['comment']
                                    );
                                    $c['comment'] = str_replace(
                                        "\n",
                                        '<br>',
                                        $c['comment']
                                    );
                                    $comment .= '<br>'.$c['comment'].'<br>';
                                }
                            }

                            $event['comments'] = $comment;

                            echo json_encode(['correct' => 1, 'event' => $event]);
                        } else {
                            echo json_encode(['correct' => 0, 'event' => []]);
                        }
                    } catch (\Exception $e) {
                        // Unexistent agent.
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node->disconnect();
                        }

                        echo json_encode(['correct' => 0, 'event' => []]);
                    } finally {
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node->disconnect();
                        }
                    }
                break;

                case 'validate_event':
                    $system = System::getInstance();

                    $id_event = $system->getRequest('id_event', 0);
                    $server_id = $system->getRequest('server_id', 0);

                    try {
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node = new Node($server_id);
                            $node->connect();
                        }

                        if (events_change_status($id_event, EVENT_VALIDATE) === true) {
                            echo json_encode(['correct' => 1]);
                        } else {
                            echo json_encode(['correct' => 0]);
                        }
                    } catch (\Exception $e) {
                        // Unexistent agent.
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node->disconnect();
                        }

                        echo json_encode(['correct' => 0]);
                    } finally {
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node->disconnect();
                        }
                    }
                break;

                case 'process_event':
                    $system = System::getInstance();

                    $id_event = $system->getRequest('id_event', 0);
                    $server_id = $system->getRequest('server_id', 0);

                    try {
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node = new Node($server_id);
                            $node->connect();
                        }

                        if (events_change_status($id_event, EVENT_PROCESS) === true) {
                            echo json_encode(['correct' => 1]);
                        } else {
                            echo json_encode(['correct' => 0]);
                        }
                    } catch (\Exception $e) {
                        // Unexistent agent.
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node->disconnect();
                        }

                        echo json_encode(['correct' => 0]);
                    } finally {
                        if (is_metaconsole() === true
                            && $server_id > 0
                        ) {
                            $node->disconnect();
                        }
                    }
                break;

                default:
                    // Not possible.
                break;
            }
        }
    }


    /**
     * Disable columns.
     *
     * @param array $columns Columns.
     *
     * @return void
     */
    public function disabledColumns($columns=null)
    {
        if (empty($columns) === false) {
            foreach ($columns as $column) {
                $this->columns[$column] = 1;
            }
        }
    }


    /**
     * Events get filters.
     *
     * @return void
     */
    private function eventsGetFilters()
    {
        $system = System::getInstance();
        $user = User::getInstance();

        $this->default_filters['severity'] = true;
        $this->default_filters['group'] = true;
        $this->default_filters['type'] = true;
        $this->default_filters['status'] = true;
        $this->default_filters['free_search'] = true;
        $this->default_filters['hours_old'] = true;

        $this->hours_old = $system->getRequest('hours_old', 8);
        if ($this->hours_old != 8) {
            $this->default = false;
            $this->default_filters['hours_old'] = false;
        }

        $this->free_search = $system->getRequest('free_search', '');
        if ($this->free_search != '') {
            $this->default = false;
            $this->default_filters['free_search'] = false;
        }

        $this->status = $system->getRequest('status', __('Status'));
        if (($this->status === __('Status')) || ($this->status == 3)) {
            $this->status = 3;
        } else {
            $this->status = (int) $this->status;
            $this->default = false;
            $this->default_filters['status'] = false;
        }

        $this->type = $system->getRequest('type', __('Type'));
        if ($this->type === __('Type')) {
            $this->type = 'all';
        } else {
            $this->default = false;
            $this->default_filters['type'] = false;
        }

        $this->group = $system->getRequest('group', __('Group'));
        if (!$user->isInGroup($this->acl, $this->group)) {
            $this->group = 0;
        }

        if (($this->group === __('Group')) || ($this->group == 0)) {
            $this->group = 0;
        } else {
            $this->default = false;
            $this->default_filters['group'] = false;
        }

        $this->severity = $system->getRequest('severity', __('Severity'));
        if ($this->severity === __('Severity')) {
            $this->severity = -1;
        } else {
            $this->default = false;
            $this->default_filters['severity'] = false;
        }

        $this->filter = $system->getRequest('filter', 0);

        if ($this->filter != 0) {
            $this->default = false;
        }

        // The user set a preset filter.
        if ($this->filter > 0) {
            // $this->loadPresetFilter();
        }

        $this->limit = $system->getRequest('limit', -1);
    }


    /**
     * Set filters.
     *
     * @param array $filters Filters.
     *
     * @return void
     */
    public function setFilters($filters)
    {
        if (isset($filters['id_agent']) === true) {
            $this->id_agent = $filters['id_agent'];
        }

        if (isset($filters['all_events']) === true) {
            $this->all_events = $filters['all_events'];
        }
    }


    /**
     * Show.
     *
     * @return void
     */
    public function show()
    {
        if (!$this->correct_acl) {
            $this->showFailAcl();
        } else {
            $this->eventsGetFilters();
            $this->showEvents();
        }
    }


    /**
     * Show fail.
     *
     * @return void
     */
    private function showFailAcl()
    {
        $error['type'] = 'onStart';
        $error['title_text'] = __('You don\'t have access to this page');
        $error['content_text'] = System::getDefaultACLFailText();
        if (class_exists('HomeEnterprise') === true) {
            $home = new HomeEnterprise();
        } else {
            $home = new Home();
        }

        $home->show($error);
    }


    /**
     * Event dialog error.
     *
     * @param array $options Options.
     *
     * @return array Array.
     */
    public function getEventDialogErrorOptions($options)
    {
        $options['type'] = 'hidden';

        $options['dialog_id'] = 'detail_event_dialog_error';
        $options['title_text'] = __('ERROR: Event detail');
        $options['content_text'] = '<span class="color_ff0">'.__('Error connecting to DB.').'</span>';

        return $options;
    }


    /**
     * Event dialog options.
     *
     * @return array
     */
    public function getEventDialogOptions()
    {
        $ui = Ui::getInstance();

        $options['type'] = 'hidden';

        $options['dialog_id'] = 'detail_event_dialog';

        $options['title_close_button'] = true;
        $options['title_text'] = __('Event detail');

        // Content.
        ob_start();
        ?>
        <table class="pandora_responsive event_details">
            <tbody>
                <tr class="event_name">
                    <td class="cell_event_name" colspan="2"></td>
                </tr>
                <tr class="event_id">
                    <th><?php echo __('Event ID'); ?></th>
                    <td class="cell_event_id"></td>
                </tr>
                <tr class="event_timestamp">
                    <th><?php echo __('Timestamp'); ?></th>
                    <td class="cell_event_timestamp"></td>
                </tr>
                <tr class="event_owner">
                    <th><?php echo __('Owner'); ?></th>
                    <td class="cell_event_owner"></td>
                </tr>
                <tr class="event_type">
                    <th><?php echo __('Type'); ?></th>
                    <td class="cell_event_type"></td>
                </tr>
                <tr class="event_repeated">
                    <th><?php echo __('Repeated'); ?></th>
                    <td class="cell_event_repeated"></td>
                </tr>
                <tr class="event_severity">
                    <th><?php echo __('Severity'); ?></th>
                    <td class="cell_event_severity"></td>
                </tr>
                <tr class="event_status">
                    <th><?php echo __('Status'); ?></th>
                    <td class="cell_event_status"></td>
                </tr>
                <tr class="event_acknowledged_by">
                    <th><?php echo __('Acknowledged by'); ?></th>
                    <td class="cell_event_acknowledged_by"></td>
                </tr>
                <tr class="event_group">
                    <th><?php echo __('Group'); ?></th>
                    <td class="cell_event_group"></td>
                </tr>
                </tr>
                <tr class="event_module_graph">
                    <th><?php echo __('Module Graph'); ?></th>
                    <td class="cell_module_graph"></td>
                </tr>
                <tr class="event_agent">
                    <th><?php echo __('Agent'); ?></th>
                    <td class="cell_agent"></td>
                </tr>
                <tr class="event_tags">
                    <th><?php echo __('Tags'); ?></th>
                    <td class="cell_event_tags"></td>
                </tr>
                <tr class="event_comments">
                    <th><?php echo __('Comments'); ?></th>
                    <td class="cell_event_comments"></td>
                </tr>
            </tbody>
        </table>
        <?php
        $options['content_text'] = ob_get_clean();

        $options_button = [
            'text' => __('Validate'),
            'id'   => 'validate_button',
            'href' => 'javascript: validateEvent();',
        ];
        $options['content_text'] .= $ui->createButton($options_button);

        $options_button = [
            'text' => __('In process'),
            'id'   => 'process_button',
            'href' => 'javascript: processEvent();',
        ];
        $options['content_text'] .= $ui->createButton($options_button);

        $options_hidden = [
            'id'    => 'event_id',
            'value' => 0,
            'type'  => 'hidden',
        ];
        $options['content_text'] .= $ui->getInput($options_hidden);
        $options_hidden = [
            'id'    => 'server_id',
            'value' => 0,
            'type'  => 'hidden',
        ];
        $options['content_text'] .= $ui->getInput($options_hidden);
        $options['content_text'] .= '<div id="validate_button_loading" class="invisible center">
			<img src="images/ajax-loader.gif" /></div>';
        $options['content_text'] .= '<div id="validate_button_correct" class="invisible center">
			<h3>'.__('Sucessful validate').'</h3></div>';
        $options['content_text'] .= '<div id="validate_button_fail" class="invisible center">
			<h3 class="color_ff0">'.__('Fail validate').'</h3></div>';

        $options['content_text'] .= '<div id="process_button_loading" class="invisible center">
			<img src="images/ajax-loader.gif" /></div>';
        $options['content_text'] .= '<div id="process_button_correct" class="invisible center">
			<h3>'.__('Sucessful in process').'</h3></div>';
        $options['content_text'] .= '<div id="process_button_fail" class="invisible center">
			<h3 class="color_ff0">'.__('Fail in process').'</h3></div>';

        $options['button_close'] = true;

        return $options;
    }


    /**
     * Show events.
     *
     * @return void
     */
    private function showEvents()
    {
        $ui = Ui::getInstance();

        $ui->createPage();

        $options = $this->getEventDialogOptions();

        $ui->addDialog($options);

        $options = $this->getEventDialogErrorOptions($options);

        $ui->addDialog($options);

        $ui->createDefaultHeader(
            __('Events'),
            $ui->createHeaderButton(
                [
                    'icon'  => 'ui-icon-back',
                    'pos'   => 'left',
                    'text'  => __('Back'),
                    'href'  => 'index.php?page=home',
                    'class' => 'header-button-left',
                ]
            )
        );
        $ui->showFooter(false);
        $ui->beginContent();
        $ui->contentAddHtml("<a id='detail_event_dialog_hook' href='#detail_event_dialog' class='invisible'>detail_event_hook</a>");
        $ui->contentAddHtml("<a id='detail_event_dialog_error_hook' href='#detail_event_dialog_error' class='invisible'>detail_event_dialog_error_hook</a>");

        $filter_title = sprintf(__('Filter Events by %s'), $this->filterEventsGetString());
        $ui->contentBeginCollapsible($filter_title, 'filter-collapsible');
        $ui->beginForm('index.php?page=events');
        $items = db_get_all_rows_in_table('tevent_filter');
        $items[] = [
            'id_filter' => 0,
            'id_name'   => __('None'),
        ];
        $options = [
            'name'       => 'filter',
            'title'      => __('Preset Filters'),
            'label'      => __('Preset Filters'),
            'items'      => $items,
            'item_id'    => 'id_filter',
            'item_value' => 'id_name',
            'selected'   => $this->filter,
        ];
        $ui->formAddSelectBox($options);

        $system = System::getInstance();
        $groups = users_get_groups_for_select(
            $system->getConfig('id_user'),
            'ER',
            true,
            true,
            false,
            'id_grupo'
        );
        $options = [
            'name'     => 'group',
            'title'    => __('Group'),
            'label'    => __('Group'),
            'items'    => $groups,
            'selected' => $this->group,
        ];
        $ui->formAddSelectBox($options);

        $options = [
            'name'     => 'status',
            'title'    => __('Status'),
            'label'    => __('Status'),
            'items'    => events_get_all_status(),
            'selected' => $this->status,
        ];
        $ui->formAddSelectBox($options);

        $options = [
            'name'     => 'type',
            'title'    => __('Type'),
            'label'    => __('Type'),
            'items'    => array_merge(['all' => __('All')], get_event_types()),
            'selected' => $this->type,
        ];

        $ui->formAddSelectBox($options);

        $options = [
            'name'     => 'severity',
            'title'    => __('Severity'),
            'label'    => __('Severity'),
            'items'    => (['-1' => __('All')] + get_priorities()),
            'selected' => $this->severity,
        ];
        $ui->formAddSelectBox($options);

        $options = [
            'name'        => 'free_search',
            'value'       => $this->free_search,
            'placeholder' => __('Free search'),
        ];
        $ui->formAddInputSearch($options);

        $options = [
            'label' => __('Max. hours old'),
            'name'  => 'hours_old',
            'value' => $this->hours_old,
            'min'   => 0,
            'max'   => (24 * 7),
            'step'  => 8,
        ];
        $ui->formAddSlider($options);

        $options = [
            'icon'     => 'refresh',
            'icon_pos' => 'right',
            'text'     => __('Apply Filter'),
        ];
        $ui->formAddSubmitButton($options);
        $html = $ui->getEndForm();
        $ui->contentCollapsibleAddItem($html);
        $ui->contentEndCollapsible();
        $ui->contentAddHtml('<div class="hr-full"></div>');
        $ui->contentAddHtml('<div class="white-card">');
        $this->listEventsHtml();
        $ui->contentAddHtml('</div>');
        $ui->endContent();
        $ui->showPage();
    }


    /**
     * List events.
     *
     * @param integer $page Page.
     *
     * @return array
     */
    private function getListEvents($page=0)
    {
        $system = System::getInstance();

        $filters = [];

        // Status.
        if (empty($this->status) === false) {
            $filters['status'] = $this->status;
        }

        // Filter search.
        if (empty($this->free_search) === false) {
            $filters['search'] = $this->free_search;
        }

        // Severity.
        if (empty($this->severity) === false) {
            $filters['severity'] = $this->severity;
        }

        // Hours.
        if (empty($this->hours_old) === false) {
            $filters['event_view_hr'] = $this->hours_old;
        } else {
            $filters['event_view_hr'] = 8;
        }

        // Type.
        if (empty($this->type) === false) {
            $filters['event_type'] = $this->type;
        }

        // ID group.
        if (empty($this->group) === false) {
            $filters['id_group_filter'] = $this->group;
        }

        // Id Agent.
        if ($this->id_agent !== false && empty($this->id_agent) === false) {
            $filters['id_agent'] = $this->id_agent;
        }

        $filters['group_rep'] = EVENT_GROUP_REP_EVENTS;

        if (isset($this->limit) === true
            && $this->limit !== -1
        ) {
            $offset = 0;
            $pagination = $this->limit;
        } else {
            $offset = ($page * $system->getPageSize());
            $pagination = $system->getPageSize();
        }

        $events_db = events_get_all(
            // Fields.
            [
                'te.*',
                'ta.alias',
            ],
            // Filter.
            $filters,
            // Offset.
            $offset,
            // Limit.
            $pagination,
            // Order.
            'desc',
            // Sort field.
            'timestamp'
        );

        if (is_metaconsole() === false) {
            $count = events_get_all(
                'count',
                $filters
            );

            if ($count !== false) {
                $total_events = $count['0']['nitems'];
            }
        } else {
            $total_events = $events_db['total'];
            $events_db = $events_db['data'];
        }

        if (empty($events_db) === true) {
            $events_db = [];
        }

        return [
            'events' => $events_db,
            'total'  => $total_events,
        ];
    }


    /**
     * Undocumented function
     *
     * @param integer $page     Page.
     * @param boolean $return   Return.
     * @param string  $id_table Id table.
     *
     * @return mixed
     */
    public function listEventsHtml($page=0, $return=false, $id_table='list_events')
    {
        $system = System::getInstance();
        $ui = Ui::getInstance();

        // Create an empty table to be filled from ajax.
        $table = new Table();
        $table->id = $id_table;

        $no_events = '<p id="empty_advice_events" class="no-data invisible" class="invisible">'.__('No events').'</p>';

        if (!$return) {
            $ui->contentAddHtml($table->getHTML());

            $ui->contentAddHtml(
                '<div id="loading_rows">'.html_print_image('images/spinner.gif', true, false, false, false, false, true).' '.__('Loading...').'</div>'.$no_events
            );

            $this->addJavascriptAddBottom();

            $this->addJavascriptDialog();
        } else {
            $this->addJavascriptAddBottom();

            return ['table' => $table->getHTML().$no_events];
        }

        $ui->contentAddLinkListener('list_events');
    }


    /**
     * Events table.
     *
     * @param integer $id_agent Id agent.
     *
     * @return string
     */
    public function putEventsTableJS($id_agent)
    {
        return '<script type="text/javascript">
					ajax_load_latest_agent_events('.$id_agent.', 10);
				</script>';
    }


    /**
     * Js dialog.
     *
     * @return void
     */
    public function addJavascriptDialog()
    {
        $ui = Ui::getInstance();

        $ui->contentAddHtml(
            '
			<script type="text/javascript">
				function openDetails(id_event, server_id) {
                    $.mobile.loading("show");
					postvars = {};
					postvars["action"] = "ajax";
					postvars["parameter1"] = "events";
					postvars["parameter2"] = "get_detail_event";
					postvars["id_event"] = id_event;
                    postvars["server_id"] = server_id;

					$.ajax ({
						type: "POST",
						url: "index.php",
						dataType: "json",
						data: postvars,
						success:
							function (data) {
                                if (data.correct) {
                                    event = data.event;
									//Fill the dialog
                                    $("#detail_event_dialog h1.dialog_title")
										.html(event["evento"]);
									$("#detail_event_dialog .cell_event_id")
										.html(id_event);
									$("#detail_event_dialog .cell_event_timestamp")
										.html(event["timestamp"]);
									$("#detail_event_dialog .cell_event_owner")
										.html(event["owner_user"]);
									$("#detail_event_dialog .cell_event_type")
										.html(event["event_type"]);
									$("#detail_event_dialog .cell_event_repeated")
										.html(event["event_repeated"]);
									$("#detail_event_dialog .cell_event_severity")
										.html(event["criticity"]);
									$("#detail_event_dialog .cell_event_status")
										.html(event["status"]);
									$("#detail_event_dialog .cell_event_status img")
										.addClass("main_menu_icon");
									$("#detail_event_dialog .cell_event_acknowledged_by")
										.html(event["acknowledged_by"]);
									$("#detail_event_dialog .cell_event_group")
										.html(event["group"]);
									$("#detail_event_dialog .cell_event_tags")
										.html(event["tags"]);
									$("#detail_event_dialog .cell_event_comments")
                                        .html(event["comments"]);
									$("#detail_event_dialog .cell_agent")
										.html(event["agent"]);

									//The link to module graph
									$(".cell_module_graph").html(event["module_graph_link"]);

									$("#event_id").val(id_event);
                                    $("#server_id").val(server_id);

									if (event["estado"] != 1) {
										$("#validate_button").show();
									}
									else {
										//The event is validated.
                                        $("#validate_button").hide();
									}

                                    if (event["status"].indexOf("clock") >= 0) {
                                        $("#process_button").hide();
                                    }

									$("#validate_button_loading").hide();
									$("#validate_button_fail").hide();
									$("#validate_button_correct").hide();
                                    $.mobile.loading( "hide" );
									$("#detail_event_dialog_hook").click();

                                    $("#detail_event_dialog-button_close").html("");
                                    $("#detail_event_dialog-button_close").addClass("close-button-dialog");
                                    $(".dialog_title").parent().addClass("flex align-items-center space-between");
                                    $(".dialog_title").parent().append($("#detail_event_dialog-button_close"));
								}
								else {
                                    $.mobile.loading( "hide" );
									$("#detail_event_dialog_error_hook").click();
								}
							},
						error:
							function (jqXHR, textStatus, errorThrown) {
                                $.mobile.loading( "hide" );
								$("#detail_event_dialog_error_hook").click();
							}
						});
				}

				function validateEvent() {
					id_event = $("#event_id").val();
                    server_id = $("#server_id").val();

					$("#validate_button").hide();
					$("#validate_button_loading").show();

					//Hide the button to close
					$("#detail_event_dialog div.ui-header a.ui-btn-right")
						.hide();

					postvars = {};
					postvars["action"] = "ajax";
					postvars["parameter1"] = "events";
					postvars["parameter2"] = "validate_event";
					postvars["id_event"] = id_event;
                    postvars["server_id"] = server_id;

					$.ajax ({
						type: "POST",
						url: "index.php",
						dataType: "json",
						data: postvars,
						success:
							function (data) {
								$("#validate_button_loading").hide();

								if (data.correct) {
									$("#validate_button_correct").show();
								}
								else {
									$("#validate_button_fail").show();
								}

								$("#detail_event_dialog div.ui-header a.ui-btn-right")
									.show();
							},
						error:
							function (jqXHR, textStatus, errorThrown) {
								$("#validate_button_loading").hide();
								$("#validate_button_fail").show();
								$("#detail_event_dialog div.ui-header a.ui-btn-right")
									.show();
							}
					});
				}

				function processEvent() {
					id_event = $("#event_id").val();
                    server_id = $("#server_id").val();

					$("#process_button").hide();
					$("#process_button_loading").show();

					//Hide the button to close
					$("#detail_event_dialog div.ui-header a.ui-btn-right")
						.hide();

					postvars = {};
					postvars["action"] = "ajax";
					postvars["parameter1"] = "events";
					postvars["parameter2"] = "process_event";
					postvars["id_event"] = id_event;
                    postvars["server_id"] = server_id;

					$.ajax ({
						type: "POST",
						url: "index.php",
						dataType: "json",
						data: postvars,
						success:
							function (data) {
								$("#process_button_loading").hide();

								if (data.correct) {
									$("#process_button_correct").show();
								}
								else {
									$("#process_button_fail").show();
								}

								$("#detail_event_dialog div.ui-header a.ui-btn-right")
									.show();
							},
						error:
							function (jqXHR, textStatus, errorThrown) {
								$("#process_button_loading").hide();
								$("#process_button_fail").show();
								$("#detail_event_dialog div.ui-header a.ui-btn-right")
									.show();
							}
					});
				}
			</script>'
        );
    }


    /**
     * Js button.
     *
     * @return void
     */
    private function addJavascriptAddBottom()
    {
        $ui = Ui::getInstance();

        $ui->contentAddHtml(
            "<script type=\"text/javascript\">
				var load_more_rows = 1;
				var page = 0;
				function add_rows(data, table_id) {
					if (data.end) {
						$(\"#loading_rows\").hide();
					}
					else {
						var new_rows = \"\";
						$.each(data.events, function(key, event) {
							new_rows = \"<tr class='events \" + event[2] + \"'>\" +
									\"<td class='cell_0' class='vertical_middle'>\" +
										event[0] +
									\"</td>\" +
									\"<td class='vertical_middle'>\" + event[1] + \"</td>\" +
								\"</tr>\" + new_rows;
							});

						$(\"table#\"+table_id+\" tbody\").append(new_rows);

						// load_more_rows = 0;
						refresh_link_listener_list_events();
					}
				}

				function ajax_load_rows() {
                    if (load_more_rows) {
						// load_more_rows = 0;
						postvars = {};
						postvars[\"action\"] = \"ajax\";
						postvars[\"parameter1\"] = \"events\";
						postvars[\"parameter2\"] = \"get_events\";
						postvars[\"filter\"] = $(\"select[name='filter']\").val();
						postvars[\"group\"] = $(\"select[name='group']\").val();
						postvars[\"status\"] = $(\"select[name='status']\").val();
						postvars[\"type\"] = $(\"select[name='type']\").val();
						postvars[\"severity\"] = $(\"select[name='severity']\").val();
						postvars[\"free_search\"] = $(\"input[name='free_search']\").val();
						postvars[\"hours_old\"] = $(\"input[name='hours_old']\").val();
						postvars[\"page\"] = page;
						page++;

						$.post(\"index.php\",
							postvars,
							function (data) {
								add_rows(data, 'list_events');

								if($('#list_events').offset() != undefined) {
									//For large screens load the new events
									//Check if the end of the event list tables is in the client limits
									var table_end = $('#list_events').offset().top + $('#list_events').height();
									if (table_end < document.documentElement.clientHeight) {
										// ajax_load_rows();
									}
								}

								if (data.events.length == 0 && page == 1) {
									$('#empty_advice_events').show();
								}
							},
							\"json\");
					}
				}

				function ajax_load_latest_agent_events(id_agent, limit) {
					postvars = {};
					postvars[\"action\"] = \"ajax\";
					postvars[\"parameter1\"] = \"events\";
					postvars[\"parameter2\"] = \"get_events\";
					postvars[\"agent_events\"] = \"1\";
					postvars[\"id_agent\"] = id_agent;
					postvars[\"limit\"] = limit;

					$.post(\"index.php\",
						postvars,
						function (data) {
							add_rows(data, 'last_agent_events');
							if (data.events.length == 0) {
								$('#last_agent_events').css('visibility', 'hidden');
								$('#empty_advice_events').show();
							} else {
                                $('#empty_advice_events').hide();
                            }
						},
						\"json\");
				}

                let intervalId;
                let count = 0;
                function getFreeSpace() {
                    let headerHeight = $('div[data-role=\"header\"].ui-header').outerHeight();
                    let contentHeight = $('div[data-role=\"content\"].ui-content').outerHeight();
                    let windowHeight = $(window).height();

                    let freeSpace = windowHeight - (headerHeight + contentHeight);

                    if (freeSpace > 0 && count < 50) {
                        custom_scroll();
                    } else {
                        clearInterval(intervalId);
                    }

                    count++;
                }

				$(document).ready(function() {
                    intervalId = setInterval(getFreeSpace, 500);
                    ajax_load_rows();
                    $(window).bind(\"scroll\", function () {
                        custom_scroll();
                    });
                    $(window).on(\"touchmove\", function(event) {
                        custom_scroll();
                    });
				});

				function custom_scroll() {
					if ($(this).scrollTop() + $(this).height()
                        >= ($(document).height() - 100)) {
                        ajax_load_rows();
                    }
				}
			</script>"
        );
    }


    /**
     * Filter events.
     *
     * @return string
     */
    private function filterEventsGetString()
    {
        global $system;

        if ($this->default) {
            return __('(Default)');
        } else {
            if ($this->filter) {
                $filter = db_get_row('tevent_filter', 'id_filter', $this->filter);

                return sprintf(__('Filter: %s'), $filter['id_name']);
            } else {
                $filters_to_serialize = [];

                if ($this->severity == -1) {
                    $severity = __('All');
                } else {
                    $severity = get_priorities($this->severity);
                }

                if (!$this->default_filters['severity']) {
                    $filters_to_serialize[] = sprintf(
                        __('Severity: %s'),
                        $severity
                    );
                }

                if (!$this->default_filters['group']) {
                    $groups = users_get_groups_for_select(
                        $system->getConfig('id_user'),
                        'ER',
                        true,
                        true,
                        false,
                        'id_grupo'
                    );

                    $filters_to_serialize[] = sprintf(
                        __('Group: %s'),
                        $groups[$this->group]
                    );
                }

                if ($this->type == 'all') {
                    $type = __('All');
                } else {
                    $type = get_event_types($this->type);
                }

                if (!$this->default_filters['type']) {
                    $filters_to_serialize[] = sprintf(
                        __('Type: %s'),
                        $type
                    );
                }

                if (!$this->default_filters['status']) {
                    $filters_to_serialize[] = sprintf(
                        __('Status: %s'),
                        events_get_status($this->status)
                    );
                }

                if (!$this->default_filters['free_search']) {
                    $filters_to_serialize[] = sprintf(
                        __('Free search: %s'),
                        $this->free_search
                    );
                }

                if (!$this->default_filters['hours_old']) {
                    $filters_to_serialize[] = sprintf(
                        __('Hours: %s'),
                        $this->hours_old
                    );
                }

                $string = '('.implode(' - ', $filters_to_serialize).')';

                return $string;
            }
        }
    }


}


