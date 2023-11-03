<?php
/**
 * Widget Simple graph Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
use PandoraFMS\ITSM\ITSM;

global $config;

/**
 * URL Widgets
 */
class ITSMIncidences extends Widget
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
        $this->title = __('Pandora ITSM tickets');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'ITSMIncidences';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (isset($config['ITSM_enabled']) === false || (bool) $config['ITSM_enabled'] === false) {
            $this->configurationRequired = true;
        } else {
            if (empty($this->values['customSearch']) === true || empty($this->values['fields']) === true) {
                $this->configurationRequired = true;
            }
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

        if (isset($decoder['show_full_legend']) === true) {
            $values['showLegend'] = $decoder['show_full_legend'];
        }

        if (isset($decoder['fields']) === true) {
            if (is_array($decoder['fields']) === true) {
                $decoder['fields'] = implode(',', $decoder['fields']);
            }

            $values['fields'] = $decoder['fields'];
        }

        if (isset($decoder['limit']) === true) {
            $values['limit'] = $decoder['limit'];
        }

        if (isset($decoder['customSearch']) === true) {
            $values['customSearch'] = $decoder['customSearch'];
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

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        // Default values.
        if (isset($values['fields']) === false) {
            $values['fields'] = implode(
                ',',
                [
                    'idIncidence',
                    'title',
                    'priority',
                    'idCreator',
                ]
            );
        }

        if (isset($values['limit']) === false) {
            $values['limit'] = $config['block_size'];
        }

        $inputs[] = [
            'label'     => __('Limit'),
            'arguments' => [
                'type'   => 'number',
                'name'   => 'limit',
                'value'  => $values['limit'],
                'return' => true,
                'max'    => 100,
                'min'    => 0,
            ],
        ];

        $customSearches = [];
        if (isset($config['ITSM_enabled']) === true && (bool) $config['ITSM_enabled'] === true) {
            try {
                $ITSM = new ITSM();
                $customSearches = $ITSM->listCustomSearch();
            } catch (\Throwable $th) {
                $error = $th->getMessage();
            }
        }

        $inputs[] = [
            'label'     => __('Custom search'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $customSearches,
                'name'     => 'customSearch',
                'selected' => $values['customSearch'],
                'return'   => true,
                'sort'     => false,
            ],
        ];

        $fields = [
            'idIncidence'      => __('ID'),
            'title'            => __('Title'),
            'groupCompany'     => __('Group').'/'.__('Company'),
            'statusResolution' => __('Status').'/'.__('Resolution'),
            'priority'         => __('Priority'),
            'updateDate'       => __('Updated'),
            'startDate'        => __('Started'),
            'idCreator'        => __('Creator'),
            'owner'            => __('Owner'),
        ];

        $inputs[] = [
            'label'     => __('Fields to show'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'fields[]',
                'selected' => explode(',', $values['fields']),
                'return'   => true,
                'multiple' => true,
                'sort'     => false,
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

        $values['fields'] = \get_parameter('fields', []);
        $values['limit'] = \get_parameter('limit', 20);
        $values['customSearch'] = \get_parameter('customSearch', 20);

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
        \ui_require_css_file('pandoraitsm', 'include/styles/', true);

        $fields = [
            'idIncidence'      => __('ID'),
            'title'            => __('Title'),
            'groupCompany'     => __('Group').'/'.__('Company'),
            'statusResolution' => __('Status').'/'.__('Resolution'),
            'priority'         => __('Priority'),
            'updateDate'       => __('Updated'),
            'startDate'        => __('Started'),
            'idCreator'        => __('Creator'),
            'owner'            => __('Owner'),
        ];

        $fields_selected = explode(',', $this->values['fields']);
        if (is_array($fields_selected) === false && empty($fields_selected) === true) {
            $output = '';
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Not found fields selected'),
                '',
                true
            );
            $output .= '</div>';

            return $output;
        }

        $columns = $fields_selected;
        $column_names = [];
        foreach ($fields_selected as $field) {
            $column_names[] = $fields[$field];
        }

        $hash = get_parameter('auth_hash', '');
        $id_user = get_parameter('id_user', '');

        $tableId = 'ITSMIncidence_'.$this->dashboardId.'_'.$this->cellId;
        try {
            ui_print_datatable(
                [
                    'id'                 => $tableId,
                    'class'              => 'info_table table-widget-itsm',
                    'style'              => 'width: 99%',
                    'columns'            => $columns,
                    'column_names'       => $column_names,
                    'ajax_url'           => 'operation/ITSM/itsm',
                    'ajax_data'          => [
                        'method'       => 'getListTickets',
                        'customSearch' => $this->values['customSearch'],
                        'auth_hash'    => $hash,
                        'auth_class'   => 'PandoraFMS\Dashboard\Manager',
                        'id_user'      => $id_user,
                    ],
                    'order'              => [
                        'field'     => 'updateDate',
                        'direction' => 'desc',
                    ],
                    'csv'                => 0,
                    'dom_elements'       => 'frtip',
                    'default_pagination' => $this->values['limit'],
                ]
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Pandora ITSM tickets');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'ITSMIncidences';
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
            'height' => 430,
        ];

        return $size;
    }


}
