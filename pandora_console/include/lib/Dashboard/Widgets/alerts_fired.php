<?php
/**
 * Widget Alerts fired Pandora FMS Console.
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Alerts fired
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
 * Alerts fired Widgets.
 */
class AlertsFiredWidget extends Widget
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

        // Includes.
        include_once $config['homedir'].'/include/functions_users.php';
        include_once $config['homedir'].'/include/functions_alerts.php';

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
        $this->title = __('Triggered alerts report');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'alerts_fired';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (isset($this->values['groupId']) === false) {
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

        // Groups.
        $inputs[] = [
            'label'     => __('Group'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId',
                'returnAllGroup' => true,
                'privilege'      => 'AR',
                'selected'       => $values['groupId'],
                'return'         => true,
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

        $values['groupId'] = \get_parameter('groupId', 0);

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

        $output = '';

        if ($this->values['groupId'] === 0) {
            $groups = users_get_groups(false, 'AR', false);
        } else {
            $groups = [$this->values['groupId'] => ''];
        }

        if (isset($groups) === true && is_array($groups) === true) {
            $table = new \StdClass();
            $table->class = 'databox data';
            $table->cellspacing = '0';
            $table->width = '90%';
            $table->data = [];
            $table->size = [];

            $url = $config['homeurl'];
            $url .= 'index.php?sec=estado&sec2=operation/agentes/alerts_status';
            $url .= '&refr=60&filter=fired&filter_standby=all';

            $flag = false;
            foreach ($groups as $id_group => $name) {
                $alerts_group = get_group_alerts($id_group);
                if (isset($alerts_group['simple']) === true) {
                    $alerts_group = $alerts_group['simple'];
                }

                foreach ($alerts_group as $alert) {
                    $data = [];

                    if ($alert['times_fired'] == 0) {
                        continue;
                    }

                    $flag = true;

                    $data[0] = '<a href="'.$url.'&ag_group='.$id_group.'">';
                    $data[0] .= ui_print_group_icon(
                        $id_group,
                        true,
                        'groups_small',
                        '',
                        false
                    );
                    $data[0] .= '</a>';

                    $data[1] = '<a href="'.$url.'&free_search='.$alert['agent_name'].'">';
                    $data[1] .= $alert['agent_name'];
                    $data[1] .= '</a>';

                    $data[2] = $alert['agent_module_name'];

                    $data[3] = ui_print_timestamp($alert['last_fired'], true);

                    array_push($table->data, $data);
                }
            }

            if ($flag === true) {
                $height = (count($table->data) * 30);
                $style = 'min-width:300px; min-height:'.$height.'px;';
                $output .= '<div class="container-center" style="'.$style.'">';
                $output .= html_print_table($table, true);
                $output .= '</div>';
            } else {
                $output .= '<div class="container-center">';
                $output .= \ui_print_info_message(
                    __('Not alert fired'),
                    '',
                    true
                );
                $output .= '</div>';
            }
        } else {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('You must select some group'),
                '',
                true
            );
            $output .= '</div>';
        }

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Triggered alerts report');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'alerts_fired';
    }


}
