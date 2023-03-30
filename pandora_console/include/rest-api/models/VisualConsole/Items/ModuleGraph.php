<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a module graph item of the Visual Console.
 */
final class ModuleGraph extends Item
{

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked module.
     *
     * @var boolean
     */
    protected static $useLinkedModule = true;

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked visual console.
     *
     * @var boolean
     */
    protected static $useLinkedVisualConsole = true;

    /**
     * Used to enable validation, extraction and encodeing of the HTML output.
     *
     * @var boolean
     */
    protected static $useHtmlOutput = true;


    /**
     * Extract the "show Legend" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed If the statistics should be shown or not.
     */
    private static function getShowLegend(array $data)
    {
        return static::issetInArray($data, ['showLegend', 'show_statistics']);
    }


    /**
     * Return a valid representation of a record in database.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing a record in database.
     *
     * @overrides Item->encode.
     */
    protected static function encode(array $data): array
    {
        $return = parent::encode($data);

        $id_custom_graph = static::extractIdCustomGraph($data);
        if (!empty($id_custom_graph)) {
            if (\is_metaconsole()) {
                $explode_custom_graph = explode('|', $id_custom_graph);
                $return['id_custom_graph'] = $explode_custom_graph[0];
                $return['id_metaconsole'] = $explode_custom_graph[1];
            } else {
                $return['id_custom_graph'] = $id_custom_graph;
            }
        }

        $type_graph = static::getTypeGraph($data);
        if ($type_graph !== null) {
            $return['type_graph'] = $type_graph;
        }

        $show_legend = static::getShowLegend($data);
        if ($show_legend !== null) {
            $return['show_statistics'] = static::parseBool($show_legend);
        }

        return $return;
    }


    /**
     * Extract a custom id graph value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed Valid identifier of an agent.
     */
    private static function extractIdCustomGraph(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'id_custom_graph',
                    'idCustomGraph',
                    'customGraphId',
                ]
            ),
            null
        );
    }


    /**
     * Extract a type graph value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'vertical' or 'horizontal'. 'vertical' by default.
     */
    private static function getTypeGraph(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'typeGraph',
                    'type_graph',
                    'graphType',
                ]
            ),
            null
        );
    }


    /**
     * Returns a valid representation of the model.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing the model.
     *
     * @overrides Item::decode.
     */
    protected function decode(array $data): array
    {
        $return = parent::decode($data);
        $return['type'] = MODULE_GRAPH;
        $return['backgroundType'] = static::extractBackgroundType($data);
        $return['period'] = static::extractPeriod($data);
        $return['showLegend'] = static::extractShowLegend($data);

        $customGraphId = static::extractCustomGraphId($data);

        if (empty($customGraphId) === false) {
            $return['customGraphId'] = $customGraphId;
        } else {
            $return['graphType'] = static::extractGraphType($data);
        }

        return $return;
    }


    /**
     * Extract a background type value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string 'transparent', 'white' or 'black'.
     * 'transparent' by default.
     */
    private static function extractBackgroundType(array $data): string
    {
        $value = static::issetInArray($data, ['backgroundType', 'image']);

        switch ($value) {
            case 'transparent':
            case 'white':
            case 'black':
            return $value;

            default:
            return 'transparent';
        }
    }


    /**
     * Extract a graph period value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The time in seconds of the graph period or null.
     */
    private static function extractPeriod(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['period']),
            null
        );
    }


    /**
     * Extract the "show Legend" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the statistics should be shown or not.
     */
    private static function extractShowLegend(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['showLegend', 'show_statistics'])
        );
    }


    /**
     * Extract a custom graph Id value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The custom graph Id (int) or null.
     */
    private static function extractCustomGraphId(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['customGraphId', 'id_custom_graph']),
            null
        );
    }


    /**
     * Extract a graph type value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string 'line' or 'area'. 'line' by default.
     */
    private static function extractGraphType(array $data): string
    {
        $value = static::issetInArray($data, ['graphType', 'type_graph']);

        switch ($value) {
            case 'line':
            case 'area':
            return $value;

            default:
            return 'line';
        }
    }


    /**
     * Fetch a vc item data structure from the database using a filter.
     *
     * @param array $filter Filter of the Visual Console Item.
     *
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \InvalidArgumentException When an agent Id cannot be found.
     *
     * @override Item::fetchDataFromDB.
     */
    protected static function fetchDataFromDB(
        array $filter,
        ?float $ratio=0,
        ?float $widthRatio=0
    ): array {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $data = parent::fetchDataFromDB($filter, $ratio, $widthRatio);

        /*
         * Retrieve extra data.
         */

        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_graph.php';
        include_once $config['homedir'].'/include/functions_modules.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        $backgroundType = static::extractBackgroundType($data);
        $period = static::extractPeriod($data);
        $showLegend = static::extractShowLegend($data);

        $customGraphId = static::extractCustomGraphId($data);
        $graphType = static::extractGraphType($data);
        $linkedModule = static::extractLinkedModule($data);
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

        // Maybe connect to node.
        $nodeConnected = false;
        if (\is_metaconsole() === true && $metaconsoleId !== null) {
            $nodeConnected = \metaconsole_connect(
                null,
                $metaconsoleId
            ) === NOERR;

            if ($nodeConnected === false) {
                throw new \InvalidArgumentException(
                    'error connecting to the node'
                );
            }
        }

        /*
         * About the 30 substraction to the graph height:
         * The function which generates the graph doesn't respect the
         * required height. It uses it for the canvas (the graph itself and
         * their axes), but then it adds the legend. One item of the legend
         * (one dataset) is about 30px, so we need to substract that height
         * from the canvas to try to fit the element's height.
         *
         * PD: The custom graphs can have more datasets, but we only substract
         * the height of one of it to replicate the legacy functionality.
         */

        $width = (int) $data['width'];
        $height = (int) $data['height'];

        if ($height == 0) {
            $height = 15;
        }

        // Custom graph.
        if (empty($customGraphId) === false) {
            $customGraph = \db_get_row('tgraph', 'id_graph', $customGraphId);

            $sources = db_get_all_rows_field_filter(
                'tgraph_source',
                'id_graph',
                $customGraphId,
                'field_order'
            );

            $hackLegendHight = (28 * count($sources));

            // Trick for legend monstruosity.
            if ((int) $customGraph['stacked'] === CUSTOM_GRAPH_STACKED_LINE
                || (int) $customGraph['stacked'] === CUSTOM_GRAPH_STACKED_AREA
                || (int) $customGraph['stacked'] === CUSTOM_GRAPH_AREA
                || (int) $customGraph['stacked'] === CUSTOM_GRAPH_LINE
            ) {
                if ($width < 200 || $height < 200) {
                    $showLegend = false;
                } else {
                    $height = ($height - 10 - $hackLegendHight);
                    $showLegend = true;
                }
            } else if ((int) $customGraph['stacked'] === CUSTOM_GRAPH_VBARS) {
                $height = ($height - 40);
            }

            $params = [
                'period'             => $period,
                'width'              => $width,
                'height'             => $height,
                'title'              => '',
                'unit_name'          => null,
                'show_alerts'        => false,
                'only_image'         => false,
                'vconsole'           => true,
                'backgroundColor'    => $backgroundType,
                'show_legend'        => $showLegend,
                'return_img_base_64' => true,
                'show_title'         => false,
                'server_id'          => $metaconsoleId,
            ];

            $paramsCombined = [
                'id_graph'       => $customGraphId,
                'stacked'        => $customGraph['stacked'],
                'summatory'      => $customGraph['summatory_series'],
                'average'        => $customGraph['average_series'],
                'modules_series' => $customGraph['modules_series'],
            ];

            $chart = \graphic_combined_module(
                false,
                $params,
                $paramsCombined
            );
        } else {
            // Module graph.
            if ($moduleId === null) {
                throw new \InvalidArgumentException('missing module Id');
            }

            // Trick for legend monstruosity.
            if ($width < 200 || $height < 200) {
                $showLegend = false;
            } else {
                $height = ($height - 30);
                $showLegend = true;
            }

            $params = [
                'agent_module_id'    => $moduleId,
                'period'             => $period,
                'show_events'        => false,
                'width'              => $width,
                'height'             => $height,
                'title'              => \modules_get_agentmodule_name(
                    $moduleId
                ),
                'unit'               => \modules_get_unit($moduleId),
                'only_image'         => false,
                'menu'               => false,
                'backgroundColor'    => $backgroundType,
                'type_graph'         => $graphType,
                'vconsole'           => true,
                'return_img_base_64' => true,
                'show_legend'        => $showLegend,
                'show_title'         => false,
                'dashboard'          => true,
                'server_id'          => $metaconsoleId,
            ];

            $chart = \grafico_modulo_sparse($params);
        }

        $data['html'] = $chart;
        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        return $data;
    }


    /**
     * Return List custom graph.
     *
     * @return array
     */
    public static function getListCustomGraph():array
    {
        include_once 'include/functions_custom_graphs.php';
        enterprise_include_once('include/functions_metaconsole.php');
        $data = [];
        if (is_metaconsole() === true) {
            $data = metaconsole_get_custom_graphs(true);
        } else {
            $data = custom_graphs_get_user(
                $config['id_user'],
                false,
                true,
                'RR'
            );

            $data = array_reduce(
                $data,
                function ($carry, $item) {
                    $carry[$item['id_graph']] = $item['name'];
                    return $carry;
                },
                []
            );
        }

        return $data;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @param array $values Default values.
     *
     * @return array Of inputs.
     *
     * @throws \Exception On error.
     */
    public static function getFormInputs(array $values): array
    {
        // Default values.
        $values = static::getDefaultGeneralValues($values);

        // Retrieve global - common inputs.
        $inputs = Item::getFormInputs($values);

        if (is_array($inputs) !== true) {
            throw new \Exception(
                '[ModuleGraph]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // Default values.
            if (isset($values['period']) === false) {
                $values['period'] = 3600;
            }

            if (isset($values['showLegend']) === false) {
                $values['showLegend'] = true;
            }

            // Background color.
            $fields = [
                'white'       => __('White'),
                'black'       => __('Black'),
                'transparent' => __('Transparent'),
            ];

            $inputs[] = [
                'label'     => __('Background color'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'backgroundType',
                    'selected' => $values['backgroundType'],
                    'return'   => true,
                    'sort'     => false,
                ],
            ];

            $hiddenModule = false;
            $hiddenCustom = true;
            $checkedModule = true;
            $checkedCustom = false;
            if (isset($values['customGraphId']) === true
                && $values['customGraphId'] !== 0
            ) {
                $hiddenModule = true;
                $hiddenCustom = false;
                $checkedModule = false;
                $checkedCustom = true;
            }

            // Choose Type module graph if graph normal or custom.
            $inputs[] = [
                'wrapper'       => 'div',
                'class'         => 'flex',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Module Graph'),
                        'arguments' => [
                            'type'         => 'radio_button',
                            'attributes'   => 'class="btn mrgn_right_20px" style="flex: 1;"',
                            'name'         => 'choosetype',
                            'value'        => 'module',
                            'checkedvalue' => $checkedModule,
                            'script'       => 'typeModuleGraph(\'module\')',
                            'return'       => true,
                        ],
                    ],
                    [
                        'label'     => __('Custom Graph'),
                        'arguments' => [
                            'type'         => 'radio_button',
                            'attributes'   => 'class="btn" style="flex: 1;"',
                            'name'         => 'choosetype',
                            'value'        => 'custom',
                            'checkedvalue' => $checkedCustom,
                            'script'       => 'typeModuleGraph(\'custom\')',
                            'return'       => true,
                        ],
                    ],
                ],
            ];

            // Autocomplete agents.
            $inputs[] = [
                'id'        => 'MGautoCompleteAgent',
                'hidden'    => $hiddenModule,
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
                ],
            ];

            // Autocomplete module.
            $inputs[] = [
                'id'        => 'MGautoCompleteModule',
                'hidden'    => $hiddenModule,
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
                ],
            ];

            // Custom graph.
            $fields = self::getListCustomGraph();
            $selected_custom_graph = (\is_metaconsole() === true)
                ? $values['customGraphId'].'|'.$values['metaconsoleId']
                : $values['customGraphId'];
            $inputs[] = [
                'id'        => 'MGcustomGraph',
                'hidden'    => $hiddenCustom,
                'label'     => __('Custom graph'),
                'arguments' => [
                    'type'          => 'select',
                    'fields'        => $fields,
                    'name'          => 'customGraphId',
                    'selected'      => $selected_custom_graph,
                    'return'        => true,
                    'nothing'       => __('None'),
                    'nothing_value' => 0,
                ],
            ];

            // Period.
            $inputs[] = [
                'label'     => __('Period'),
                'arguments' => [
                    'name'          => 'period',
                    'type'          => 'interval',
                    'value'         => $values['period'],
                    'nothing'       => __('None'),
                    'nothing_value' => 0,
                ],
            ];

            // Graph Type.
            $fields = [
                'line' => __('Line'),
                'area' => __('Area'),
            ];

            $inputs[] = [
                'id'        => 'MGgraphType',
                'hidden'    => $hiddenModule,
                'label'     => __('Graph Type'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'graphType',
                    'selected' => $values['graphType'],
                    'return'   => true,
                ],
            ];

            // Show legend.
            $inputs[] = [
                'id'        => 'MGshowLegend',
                'hidden'    => $hiddenModule,
                'label'     => __('Show legend'),
                'arguments' => [
                    'name'  => 'showLegend',
                    'id'    => 'showLegend',
                    'type'  => 'switch',
                    'value' => $values['showLegend'],
                ],
            ];

            // Inputs LinkedVisualConsole.
            $inputsLinkedVisualConsole = self::inputsLinkedVisualConsole(
                $values
            );
            foreach ($inputsLinkedVisualConsole as $key => $value) {
                $inputs[] = $value;
            }
        }

        return $inputs;
    }


    /**
     * Default values.
     *
     * @param array $values Array values.
     *
     * @return array Array with default values.
     *
     * @overrides Item->getDefaultGeneralValues.
     */
    public static function getDefaultGeneralValues(array $values): array
    {
        // Retrieve global - common inputs.
        $values = parent::getDefaultGeneralValues($values);

        // Default values.
        if (isset($values['width']) === false) {
            $values['width'] = 300;
        }

        if (isset($values['height']) === false) {
            $values['height'] = 180;
        }

        return $values;
    }


}
