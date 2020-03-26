<?php
/**
 * Widget System group status Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget System group status
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
 * System group status Widgets.
 */
class SystemGroupStatusWidget extends Widget
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
        $this->title = __('Groups status');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'system_group_status';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['groupId']) === true) {
            $this->configurationRequired = true;
        }

        $this->overflow_scrollbars = false;
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

        // Restrict access to group.
        $inputs[] = [
            'label'     => __('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId[]',
                'returnAllGroup' => true,
                'privilege'      => 'ER',
                'selected'       => explode(',', $values['groupId'][0]),
                'return'         => true,
                'multiple'       => true,
            ],
        ];

        // Graph Type.
        $fields = [
            AGENT_STATUS_NORMAL   => __('Normal'),
            AGENT_STATUS_WARNING  => __('Warning'),
            AGENT_STATUS_CRITICAL => __('Critical'),
            4                     => __('Alert Fired'),
        ];

        $inputs[] = [
            'label'     => __('Status'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'status[]',
                'selected' => explode(',', $values['status'][0]),
                'return'   => true,
                'multiple' => true,
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

        $values['groupId'] = \get_parameter('groupId', []);
        $values['status'] = \get_parameter('status', []);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        include_once 'include/functions_groupview.php';

        // ACL Check.
        $agent_a = \check_acl($config['id_user'], 0, 'AR');
        $agent_w = \check_acl($config['id_user'], 0, 'AW');

        if ($agent_a === 0 && $agent_w === 0) {
            \db_pandora_audit(
                'ACL Violation',
                'Trying to access Agent view (Grouped)'
            );
            include 'general/noaccess.php';
            exit;
        }

        // Groups and tags.
        $result_groups_info = \groupview_get_groups_list(
            $config['id_user'],
            ($agent_a === 1) ? 'AR' : (($agent_w === 1) ? 'AW' : 'AR')
        );

        $result_groups = $result_groups_info['groups'];
        $result_groups = array_reduce(
            $result_groups,
            function ($carry, $item) {
                $carry[$item['_id_']] = $item;
                return $carry;
            },
            []
        );

        $this->values['groupId'] = explode(',', $this->values['groupId'][0]);

        if (count($this->values['groupId']) === 1
            && in_array(0, $this->values['groupId']) === true
        ) {
            $this->values['groupId'] = [];
            foreach ($result_groups as $key => $value) {
                $this->values['groupId'][] = $key;
            }
        }

        $this->values['status'] = explode(',', $this->values['status'][0]);

        $style = 'font-size: 12px; text-align: center;';

        $table = new \stdClass();
        $table->class = 'group_modules_status_box';
        $table->cellpadding = '0';
        $table->cellspacing = '0';
        $table->width = '90%';
        $table->data = [];
        $table->size = [];
        $table->cellstyle = [];

        $i = 1;

        $show_normal = true;
        $show_warning = true;
        $show_critical = true;
        $show_alert_fired = true;
        $show_all = isset($this->values['status']) === false;
        if ($show_all === false) {
            $show_normal = in_array(
                AGENT_STATUS_NORMAL,
                $this->values['status']
            ) === true;
            $show_warning = in_array(
                AGENT_STATUS_WARNING,
                $this->values['status']
            ) === true;
            $show_critical = in_array(
                AGENT_STATUS_CRITICAL,
                $this->values['status']
            ) === true;
            $show_alert_fired = in_array(
                4,
                $this->values['status']
            ) === true;
        }

        $flag_groups = false;
        foreach ($this->values['groupId'] as $groupId) {
            if (isset($result_groups[$groupId]) === true) {
                $group = $result_groups[$groupId];
            } else {
                $group = [
                    '_monitors_critical_'     => 0,
                    '_monitors_warning_'      => 0,
                    '_monitors_unknown_'      => 0,
                    '_monitors_not_init_'     => 0,
                    '_monitors_ok_'           => 0,
                    '_monitor_checks_'        => 0,
                    '_monitors_alerts_fired_' => 0,
                    '_agents_critical_'       => 0,
                    '_agents_warning_'        => 0,
                    '_agents_unknown_'        => 0,
                    '_agents_not_init_'       => 0,
                    '_agents_ok_'             => 0,
                    '_total_agents_'          => 0,
                    '_name_'                  => groups_get_name($groupId),
                    '_id_'                    => $groupId,
                    '_icon_'                  => groups_get_icon($groupId),
                    '_monitor_not_normal_'    => 0,
                ];
            }

            if ($group['_id_'] === 0) {
                continue;
            }

            $flag_groups = true;

            if ((in_array($group['_id_'], $this->values['groupId'])) === true) {
                $table->data[$i][] = '<span>'.$group['_name_'].'</span>';

                $url = $config['homeurl'].'index.php';
                $url .= '?sec=estado&sec2=operation/agentes/status_monitor';
                $url .= '&ag_group='.$group['_id_'];

                if ($show_normal === true) {
                    $outputLine = '<div style="background-color:#82b92e">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="'.__('Modules in normal status');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= '" href="'.$url;
                    $outputLine .= '&status='.AGENT_STATUS_NORMAL.'">';
                    $outputLine .= $group['_monitors_ok_'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                if ($show_warning === true) {
                    $outputLine = '<div style="background-color:#ffd036">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="'.__('Modules in warning status');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= '" href="'.$url;
                    $outputLine .= '&status='.AGENT_STATUS_WARNING.'">';
                    $outputLine .= $group['_monitors_warning_'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                if ($show_critical === true) {
                    $outputLine = '<div style="background-color:#ff5653">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="';
                    $outputLine .= __('Modules in critical status');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= '" href="'.$url;
                    $outputLine .= '&status='.AGENT_STATUS_CRITICAL.'">';
                    $outputLine .= $group['_monitors_critical_'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                if ($show_alert_fired === true) {
                    $outputLine = '<div style="background-color:#ff9e39">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="'.__('Alerts fired');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= '" href="'.$url;
                    $outputLine .= '&filter=fired">';
                    $outputLine .= $group['_monitors_alerts_fired_'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                $i++;
            }
        }

        $height = (count($result_groups) * 30);
        $style = 'min-width:200px; min-height:'.$height.'px;';
        $output = '<div class="container-center" style="'.$style.'">';
        if ($flag_groups === true) {
            $output .= html_print_table($table, true);
        } else {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Not modules in this groups'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        $output .= '</div>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Groups status');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'system_group_status';
    }


}
