<?php
/**
 * Widget Module value Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Module value
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

global $config;

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';

/**
 * Module value Widgets.
 */
class ModuleValueWidget extends Widget
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
        $this->title = __('Module value');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'module_value';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['moduleId']) === true) {
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

        if (isset($decoder['label_'.$this->cellId]) === true) {
            $values['label'] = $decoder['label_'.$this->cellId];
        }

        if (isset($decoder['label']) === true) {
            $values['label'] = $decoder['label'];
        }

        if (isset($decoder['id_agent_'.$this->cellId]) === true) {
            $values['agentId'] = $decoder['id_agent_'.$this->cellId];
        }

        if (isset($decoder['agentId']) === true) {
            $values['agentId'] = $decoder['agentId'];
        }

        if (isset($decoder['metaconsoleId']) === true) {
            $values['metaconsoleId'] = $decoder['metaconsoleId'];
        }

        if (isset($decoder['id_module_'.$this->cellId]) === true) {
            $values['moduleId'] = $decoder['id_module_'.$this->cellId];
        }

        if (isset($decoder['moduleId']) === true) {
            $values['moduleId'] = $decoder['moduleId'];
        }

        if (isset($decoder['size_value_'.$this->cellId]) === true) {
            $values['sizeValue'] = $decoder['size_value_'.$this->cellId];
        }

        if (isset($decoder['sizeValue']) === true) {
            $values['sizeValue'] = $decoder['sizeValue'];
        }

        if (isset($decoder['size_label_'.$this->cellId]) === true) {
            $values['sizeLabel'] = $decoder['size_label_'.$this->cellId];
        }

        if (isset($decoder['sizeLabel']) === true) {
            $values['sizeLabel'] = $decoder['sizeLabel'];
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
        global $config;

        $values = $this->values;

        // Default values.
        if (isset($values['sizeLabel']) === false) {
            $values['sizeLabel'] = 20;
        }

        if (isset($values['sizeValue']) === false) {
            $values['sizeValue'] = 20;
        }

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        // Label.
        $inputs[] = [
            'label'     => __('Label'),
            'arguments' => [
                'name'   => 'label',
                'type'   => 'text',
                'value'  => $values['label'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        // Autocomplete agents.
        $inputs[] = [
            'label'     => __('Agent'),
            'arguments' => [
                'type'               => 'autocomplete_agent',
                'name'               => 'agentAlias',
                'id_agent_hidden'    => $values['agentId'],
                'name_agent_hidden'  => 'agentId',
                'server_id_hidden'   => $values['metaconsoleId'],
                'name_server_hidden' => 'metaconsoleId',
                'return'             => true,
                'module_input'       => true,
                'module_name'        => 'moduleId',
                'module_none'        => false,
                'size'               => 0,
            ],
        ];

        // Autocomplete module.
        $inputs[] = [
            'label'     => __('Module'),
            'arguments' => [
                'type'           => 'autocomplete_module',
                'fields'         => $fields,
                'name'           => 'moduleId',
                'selected'       => $values['moduleId'],
                'return'         => true,
                'sort'           => false,
                'agent_id'       => $values['agentId'],
                'metaconsole_id' => $values['metaconsoleId'],
                'style'          => 'width: inherit;',
                'filter_modules' => users_access_to_agent($values['agentId']) === false ? [$values['moduleId']] : [],
            ],
        ];

        // Text size of value in px.
        $inputs[] = [
            'label'     => __('Text size of value in px'),
            'arguments' => [
                'name'   => 'sizeValue',
                'type'   => 'number',
                'value'  => $values['sizeValue'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        // Text size of label in px.
        $inputs[] = [
            'label'     => __('Text size of label in px'),
            'arguments' => [
                'name'   => 'sizeLabel',
                'type'   => 'number',
                'value'  => $values['sizeLabel'],
                'return' => true,
                'min'    => 0,
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

        $values['label'] = \get_parameter('label', '');
        $values['agentId'] = \get_parameter('agentId', 0);
        $values['metaconsoleId'] = \get_parameter('metaconsoleId', 0);
        $values['moduleId'] = \get_parameter('moduleId', 0);
        $values['sizeValue'] = \get_parameter('sizeValue', 0);
        $values['sizeLabel'] = \get_parameter_switch('sizeLabel');

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

        $output .= '';

        $id_agent = $this->values['agentId'];
        $id_group = agents_get_agent_group($id_agent);

        $id_module = $this->values['moduleId'];

        $data_module = \modules_get_last_value($id_module);

        $label = $this->values['label'];
        $sizeLabel = (isset($this->values['sizeLabel']) === true) ? $this->values['sizeLabel'] : 40;
        $sizeValue = (isset($this->values['sizeValue']) === true) ? $this->values['sizeValue'] : 40;

        $output .= '<div class="container-center">';
        // General div.
        $output .= '<div class="container-icon">';
        // Div value.
        $output .= '<div style="flex: 0 1 '.$sizeValue.'px; font-size:'.$sizeValue.'px;">';

        if (is_numeric($data_module) === true) {
            $dataDatos = remove_right_zeros(
                number_format(
                    $data_module,
                    $config['graph_precision']
                )
            );
        } else {
            $dataDatos = trim($data_module);
        }

        $output .= $dataDatos;

        $output .= '</div>';

        if (empty($label) === false) {
            // Div Label.
            $output .= '<div style="flex: 1 1 '.$sizeLabel.'px; font-size:'.$sizeLabel.'px;">'.$label.'</div>';
        }

        $output .= '</div>';
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
        return __('Module value');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'module_value';
    }


}
