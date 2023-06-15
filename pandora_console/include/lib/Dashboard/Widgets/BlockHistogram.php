<?php
/**
 * Widget block histogram modules Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget
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

namespace PandoraFMS\Dashboard;

use PandoraFMS\Enterprise\Metaconsole\Node;

global $config;

/**
 * URL Widgets
 */
class BlockHistogram extends Widget
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
     * Cell ID.
     *
     * @var integer
     */
    protected $cellId;


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

        // Include.
        include_once $config['homedir'].'/include/functions_reporting.php';

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Cell Id.
        $this->cellId = $cellId;

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
        $this->title = __('Block histogram');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'single_graph';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['moduleBlockHistogram']) === true) {
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

        $values['agentsBlockHistogram'] = [];
        if (isset($decoder['agentsBlockHistogram']) === true) {
            if (isset($decoder['agentsBlockHistogram'][0]) === true
                && empty($decoder['agentsBlockHistogram']) === false
            ) {
                $values['agentsBlockHistogram'] = explode(
                    ',',
                    $decoder['agentsBlockHistogram'][0]
                );
            }
        }

        if (isset($decoder['selectionBlockHistogram']) === true) {
            $values['selectionBlockHistogram'] = $decoder['selectionBlockHistogram'];
        }

        $values['moduleBlockHistogram'] = [];
        if (isset($decoder['moduleBlockHistogram']) === true) {
            if (empty($decoder['moduleBlockHistogram']) === false) {
                $values['moduleBlockHistogram'] = $decoder['moduleBlockHistogram'];
            }
        }

        if (isset($decoder['period']) === true) {
            $values['period'] = $decoder['period'];
        }

        if (isset($decoder['fontColor']) === true) {
            $values['fontColor'] = $decoder['fontColor'];
        }

        $values['label'] = 'module';
        if (isset($decoder['label']) === true) {
            $values['label'] = $decoder['label'];
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
        if (isset($values['period']) === false) {
            $values['period'] = SECONDS_1DAY;
        }

        if (empty($values['fontColor']) === true) {
            $values['fontColor'] = '#2c3e50';
        }

        $inputs[] = [
            'label'     => __('Font color'),
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'fontColor',
                'type'    => 'color',
                'value'   => $values['fontColor'],
                'return'  => true,
            ],
        ];

        // Type Label.
        $fields = [
            'module'       => __('Module'),
            'agent'        => __('Agent'),
            'agent_module' => __('Agent / module'),
        ];

        $inputs[] = [
            'label'     => __('Label'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'label',
                'selected' => $values['label'],
                'return'   => true,
            ],
        ];

        // Periodicity.
        $inputs[] = [
            'label'     => __('Interval'),
            'arguments' => [
                'name'          => 'period',
                'type'          => 'interval',
                'value'         => $values['period'],
                'nothing'       => __('None'),
                'nothing_value' => 0,
                'style_icon'    => 'flex-grow: 0',

            ],
        ];

        $inputs[] = [
            'arguments' => [
                'type'                   => 'select_multiple_modules_filtered_select2',
                'agent_values'           => agents_get_agents_selected(0),
                'agent_name'             => 'agentsBlockHistogram[]',
                'agent_ids'              => $values['agentsBlockHistogram'],
                'selectionModules'       => $values['selectionBlockHistogram'],
                'selectionModulesNameId' => 'selectionBlockHistogram',
                'modules_ids'            => $values['moduleBlockHistogram'],
                'modules_name'           => 'moduleBlockHistogram[]',
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

        $values['agentsBlockHistogram'] = \get_parameter(
            'agentsBlockHistogram',
            []
        );
        $values['selectionBlockHistogram'] = \get_parameter(
            'selectionBlockHistogram',
            0
        );

        $values['moduleBlockHistogram'] = \get_parameter(
            'moduleBlockHistogram'
        );

        $agColor = [];
        if (isset($values['agentsBlockHistogram'][0]) === true
            && empty($values['agentsBlockHistogram'][0]) === false
        ) {
            $agColor = explode(',', $values['agentsBlockHistogram'][0]);
        }

        $agModule = [];
        if (isset($values['moduleBlockHistogram'][0]) === true
            && empty($values['moduleBlockHistogram'][0]) === false
        ) {
            $agModule = explode(',', $values['moduleBlockHistogram'][0]);
        }

        $values['moduleBlockHistogram'] = get_same_modules_all(
            $agColor,
            $agModule
        );

        $values['period'] = \get_parameter('period', 0);

        $values['fontColor'] = \get_parameter('fontColor', '#2c3e50');

        $values['label'] = \get_parameter('label', 'agent');
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

        $size = parent::getSize();

        $output = '';

        if (is_metaconsole() === true) {
            $modules_nodes = array_reduce(
                $this->values['moduleBlockHistogram'],
                function ($carry, $item) {
                    $explode = explode('|', $item);
                    $carry[$explode[0]][] = $explode[1];
                    return $carry;
                },
                []
            );

            $modules = [];
            foreach ($modules_nodes as $n => $mod) {
                try {
                    $node = new Node((int) $n);
                    $node->connect();
                    $node_mods = $this->getInfoModules($mod);
                    if (empty($node_mods) === false) {
                        foreach ($node_mods as $value) {
                            $value['id_node'] = $n;
                            $value['server_name'] = $node->toArray()['server_name'];
                            $modules[] = $value;
                        }
                    }

                    $node->disconnect();
                } catch (\Exception $e) {
                    // Unexistent agent.
                    $node->disconnect();
                }
            }
        } else {
            $modules = $this->getInfoModules(
                $this->values['moduleBlockHistogram']
            );
        }

        if ($modules !== false && empty($modules) === false) {
            $total_modules = count($modules);
            $output .= '<div class="container-histograms" style="width: '.$size['width'].'px;">';
            $output .= '<table class="table-container-histograms" style="color:'.$this->values['fontColor'].'">';
            foreach ($modules as $key => $module) {
                $last = false;
                if (($total_modules - 1)  === $key) {
                    $last = true;
                }

                if (is_metaconsole() === true) {
                    try {
                        $node = new Node((int) $module['id_node']);
                        $node->connect();
                        $output .= $this->drawHistograms($module, $last);

                        $node->disconnect();
                    } catch (\Exception $e) {
                        // Unexistent agent.
                        $node->disconnect();
                    }
                } else {
                    $output .= $this->drawHistograms($module, $last);
                }
            }

            $output .= '</table>';
            $output .= '</div>';
        } else {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Not found modules'),
                '',
                true
            );
            $output .= '</div>';
        }

        return $output;
    }


    /**
     * Get info modules.
     *
     * @param array $modules Modules.
     *
     * @return array Data.
     */
    private function getInfoModules(array $modules): array
    {
        $where = sprintf(
            'tagente_modulo.id_agente_modulo IN (%s)
            AND tagente_modulo.delete_pending = 0',
            implode(',', $modules)
        );

        $sql = sprintf(
            'SELECT tagente_modulo.id_agente_modulo AS `id`,
                tagente_modulo.nombre AS `name`,
                tagente_modulo.unit AS `unit`,
                tagente.alias AS `agent_alias`,
                tagente.id_agente AS `agent_id`
            FROM tagente_modulo
            INNER JOIN tagente
                ON tagente_modulo.id_agente = tagente.id_agente
            WHERE %s',
            $where
        );

        $modules = db_get_all_rows_sql($sql);

        if ($modules === false) {
            $modules = [];
        }

        return $modules;
    }


    /**
     * Draw histogram module.
     *
     * @param array   $data Info module.
     * @param boolean $last Last histogram.
     *
     * @return string
     */
    private function drawHistograms(array $data, bool $last):string
    {
        global $config;

        $size = parent::getSize();

        // Desactive scroll bars only this item.
        $id_agent = $data['agent_id'];
        $id_module = $data['id'];
        $period = $this->values['period'];
        switch ($this->values['label']) {
            case 'module':
                $label = ui_print_truncate_text(
                    $data['name'],
                    25,
                    false,
                    true,
                    true,
                    '[&hellip;]',
                    ''
                );
            break;

            case 'agent_module':
                $label = ui_print_truncate_text(
                    $data['agent_alias'].' / '.$data['name'],
                    25,
                    false,
                    true,
                    true
                );
            break;

            default:
            case 'agent':
                $label = ui_print_truncate_text(
                    $data['agent_alias'],
                    25,
                    false,
                    true,
                    true,
                    '[&hellip;]',
                    ''
                );
            break;
        }

        $size_label = 10;

        $id_group = \agents_get_agent_group($id_agent);

        $height_graph = 30;
        if ($last === true) {
            if ($period > 86500) {
                $height_graph = 60;
            } else {
                $height_graph = 50;
            }
        }

        $content = [
            'id_agent_module' => $id_module,
            'period'          => $period,
            'time_from'       => '00:00:00',
            'time_to'         => '00:00:00',
            'id_group'        => $id_group,
            'sizeForTicks'    => ($size['width'] - 200),
            'showLabelTicks'  => ($last === true) ? true : false,
            'height_graph'    => $height_graph,
            [
                ['id_agent_module' => $id_module],
            ]
        ];

        $graph = \reporting_module_histogram_graph(
            ['datetime' => time()],
            $content
        );

        $style = 'min-width:200px;';
        if ($last === false) {
            if ($period > 86500) {
                $style .= 'width:calc(100% - 24px); margin-left: 12px;';
            } else {
                $style .= 'width:calc(100% - 16px); margin-left: 8px;';
            }
        } else {
            $style .= 'height:60px;';
        }

        $st = 'font-size:'.$size_label.'px;';
        if (is_metaconsole() === false) {
            $st .= 'height: 28px;';
        }

        $output = '<tr>';
            $output .= '<td style="width: 170px; vertical-align: initial;">';
            $output .= '<div class="widget-histogram-label" style="'.$st.'">';
                $output .= $label;
            $output .= '</div>';
            $output .= '</td>';
            $output .= '<td>';
            $output .= '<div class="widget-histogram-chart" style="'.$style.'">';
                $output .= $graph['chart'];
            $output .= '</div>';
            $output .= '</td>';
        $output .= '</tr>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Block histogram');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'BlockHistogram';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => (is_metaconsole() === true) ? 700 : 500,
            'height' => 670,
        ];

        return $size;
    }


}
