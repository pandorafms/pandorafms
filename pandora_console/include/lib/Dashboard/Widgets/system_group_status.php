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
        $this->values = $this->decoders($this->getOptionsWidget());

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
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder): array
    {
        $values = [];
        // Retrieve global - common inputs.
        $values = parent::decoders($decoder);

        if (isset($decoder['status']) === true) {
            if (is_array($decoder['status']) === true
                && count($decoder['status']) > 1
            ) {
                $compatibilityStatus = [];
                foreach ($decoder['status'] as $key => $value) {
                    switch ((int) $value) {
                        case 2:
                            $compatibilityStatus[] = AGENT_STATUS_WARNING;
                        break;

                        case 3:
                            $compatibilityStatus[] = AGENT_STATUS_CRITICAL;
                        break;

                        case 4:
                            $compatibilityStatus[] = 4;
                        break;

                        default:
                        case 1:
                            $compatibilityStatus[] = AGENT_STATUS_NORMAL;
                        break;
                    }
                }

                $decoder['status'][0] = implode(',', $compatibilityStatus);
            }

            $values['status'] = $decoder['status'];
        } else {
            $values['status'][0] = implode(
                ',',
                [
                    AGENT_STATUS_NORMAL,
                    AGENT_STATUS_WARNING,
                    AGENT_STATUS_CRITICAL,
                    4,
                ]
            );
        }

        if (isset($decoder['id_groups']) === true) {
            if (is_array($decoder['id_groups']) === true) {
                $decoder['id_groups'][0] = implode(',', $decoder['id_groups']);
            }

            $values['groupId'] = $decoder['id_groups'];
        }

        if (isset($decoder['groupId']) === true) {
            $values['groupId'] = $decoder['groupId'];
        }

        if (isset($decoder['groupRecursion']) === true) {
            $values['groupRecursion'] = $decoder['groupRecursion'];
        }

        return $values;
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

        // Default values.
        if (isset($values['status']) === false) {
            $values['status'][0] = implode(
                ',',
                [
                    AGENT_STATUS_NORMAL,
                    AGENT_STATUS_WARNING,
                    AGENT_STATUS_CRITICAL,
                    4,
                ]
            );
        }

        $return_all_group = false;

        // Restrict access to group.
        $selected_groups = [];
        if ($values['groupId']) {
            $selected_groups = explode(',', $values['groupId'][0]);

            if (users_can_manage_group_all('RM') === true
                || ($selected_groups[0] !== ''
                && in_array(0, $selected_groups) === true)
            ) {
                // Return all group if user has permissions
                // or it is a currently selected group.
                $return_all_group = true;
            }
        } else {
            if (users_can_manage_group_all('RM') === true) {
                $return_all_group = true;
            }
        }

        $inputs[] = [
            'label'     => __('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId[]',
                'returnAllGroup' => true,
                'privilege'      => 'ER',
                'selected'       => $selected_groups,
                'return'         => true,
                'multiple'       => true,
                'returnAllGroup' => $return_all_group,
                'required'       => true,
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

        $inputs[] = [
            'label'     => __('Group recursion'),
            'arguments' => [
                'name'  => 'groupRecursion',
                'id'    => 'groupRecursion',
                'type'  => 'switch',
                'value' => $values['groupRecursion'],
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
        $values['groupRecursion'] = (bool) \get_parameter_switch('groupRecursion', 0);

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
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Agent view (Grouped)'
            );
            include 'general/noaccess.php';
            exit;
        }

        $return_all_group = false;

        if (users_can_manage_group_all('AR') === true) {
            $return_all_group = true;
        }

        $user_groups = users_get_groups(false, 'AR', $return_all_group);

        $selected_groups = explode(',', $this->values['groupId'][0]);
        if (in_array(0, $selected_groups) === true) {
            $selected_groups = [];
            foreach (groups_get_all() as $key => $name_group) {
                $selected_groups[] = groups_get_id($name_group);
            }
        }

         // Recursion.
        if ($this->values['groupRecursion'] === true) {
            foreach ($selected_groups as $father) {
                $children = \groups_get_children_ids($father);
                $selected_groups = ($selected_groups + $children);
            }
        }

        if ($selected_groups[0] === '') {
            return false;
        }

        $all_counters = [];

        if (in_array(0, $selected_groups) === true) {
            $all_groups = db_get_all_rows_sql('select id_grupo from tgrupo');
            $all_groups_id = array_column($all_groups, 'id_grupo');

            $all_groups_counters = groupview_get_modules_counters(
                $all_groups_id
            );

            $all_counters['g'] = 0;
            $all_counters['name'] = __('All');

            $all_counters['total_module_normal'] = array_reduce(
                $all_groups_counters,
                function ($sum, $item) {
                    return $sum += $item['total_module_normal'];
                },
                0
            );

            $all_counters['total_module_warning'] = array_reduce(
                $all_groups_counters,
                function ($sum, $item) {
                    return $sum += $item['total_module_warning'];
                },
                0
            );

            $all_counters['total_module_critical'] = array_reduce(
                $all_groups_counters,
                function ($sum, $item) {
                    return $sum += $item['total_module_critical'];
                },
                0
            );

            $all_counters['total_module_alerts'] = array_reduce(
                $all_groups_counters,
                function ($sum, $item) {
                    return $sum += $item['total_module_alerts'];
                },
                0
            );

            $all_group_key = array_search(0, $selected_groups);

            unset($selected_groups[$all_group_key]);
        }

        $module_counters = groupview_get_modules_counters($selected_groups);
        $result_groups = [];
        if (empty($module_counters) === false) {
            foreach ($module_counters as $key => $item) {
                $module_counters[$key]['name'] = groups_get_name($item['g']);
            }

            $keys = array_column($module_counters, 'g');
            $values = array_values($module_counters);
            $result_groups = array_combine($keys, $values);

            if (empty($all_counters) === false) {
                $result_groups[0] = $all_counters;
            }
        }

        $this->values['groupId'] = $selected_groups;
        $this->values['status'] = explode(',', $this->values['status'][0]);

        $style = 'font-size: 1.5em; font-weight: bolder;text-align: center;';

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
                    'total_module_critical' => 0,
                    '_monitors_warning_'    => 0,
                    'total_module_normal'   => 0,
                    'total_module_alerts'   => 0,
                    'total_module_warning'  => 0,
                    'name'                  => groups_get_name($groupId),
                    'g'                     => $groupId,
                ];
            }

            $flag_groups = true;
            $show_link = array_key_exists($group['g'], $user_groups);

            if ((in_array($group['g'], $this->values['groupId'])) === true) {
                $table->data[$i][] = '<span class="legendLabel">'.$group['name'].'</span>';

                $url = $config['homeurl'].'index.php';
                $url .= '?sec=estado&sec2=operation/agentes/status_monitor';
                $url .= '&ag_group='.$group['g'];

                if ($show_normal === true) {
                    $outputLine = '<div class="bg_82B92E">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="'.__('Modules in normal status');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= ($show_link === true) ? '" href="'.$url : '';
                    $outputLine .= '&status='.AGENT_STATUS_NORMAL.'">';
                    $outputLine .= $group['total_module_normal'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                if ($show_warning === true) {
                    $outputLine = '<div class="bg_ffd">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="'.__('Modules in warning status');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= ($show_link === true) ? '" href="'.$url : '';
                    $outputLine .= '&status='.AGENT_STATUS_WARNING.'">';
                    $outputLine .= $group['total_module_warning'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                if ($show_critical === true) {
                    $outputLine = '<div class="bg_ff5">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="';
                    $outputLine .= __('Modules in critical status');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= ($show_link === true) ? '" href="'.$url : '';
                    $outputLine .= '&status='.AGENT_STATUS_CRITICAL.'">';
                    $outputLine .= $group['total_module_critical'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                if ($show_alert_fired === true) {
                    $url_alert = $config['homeurl'];
                    $url_alert .= 'index.php?sec=view&';
                    $url_alert .= 'sec2=operation/agentes/alerts_status';
                    $url_alert .= '&ag_group='.$group['g'];

                    $outputLine = '<div class="bg_ff9">';
                    $outputLine .= '<span>';
                    $outputLine .= '<a title="'.__('Alerts fired');
                    $outputLine .= '" class="group_view_data"';
                    $outputLine .= ' style="'.$style.'"';
                    $outputLine .= ($show_link === true) ? '" href="'.$url_alert : '';
                    $outputLine .= '&filter=fired">';
                    $outputLine .= $group['total_module_alerts'];
                    $outputLine .= '</a>';
                    $outputLine .= '</span>';
                    $outputLine .= '</div>';

                    $table->data[$i][] = $outputLine;
                }

                $i++;
            }
        }

        $height = (count($table->data) * 32);
        $style = 'min-width:200px; min-height:'.$height.'px;';
        $output = '<div class="container-center" style="'.$style.'">';
        if ($flag_groups === true) {
            $output .= html_print_table($table, true);
        } else {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('No modules in selected groups'),
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


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 450,
            'height' => 520,
        ];

        return $size;
    }


}
