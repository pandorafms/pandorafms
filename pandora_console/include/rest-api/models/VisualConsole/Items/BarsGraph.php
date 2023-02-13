<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a bars graph item of the Visual Console.
 */
final class BarsGraph extends Item
{

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked module.
     *
     * @var boolean
     */
    protected static $useLinkedModule = true;

    /**
     * Used to enable validation, extraction and encodeing of the HTML output.
     *
     * @var boolean
     */
    protected static $useHtmlOutput = true;


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

        $type_graph = static::getTypeGraph($data);
        if ($type_graph !== null) {
            $return['type_graph'] = $type_graph;
        }

        return $return;
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
        $return['type'] = BARS_GRAPH;
        $return['gridColor'] = $this->extractGridColor($data);
        $return['backgroundColor'] = $this->extractBackgroundColor($data);
        $return['typeGraph'] = $this->extractTypeGraph($data);
        return $return;
    }


    /**
     * Extract a grid color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the grid color (not empty) or null.
     */
    private static function extractGridColor(array $data): string
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['gridColor', 'border_color']),
            '#000000'
        );
    }


    /**
     * Extract a background color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'white', 'black' or 'transparent'.
     * 'white' by default.
     */
    private static function extractBackgroundColor(array $data): string
    {
        $backgroundColor = static::notEmptyStringOr(
            static::issetInArray($data, ['backgroundColor', 'image']),
            null
        );

        switch ($backgroundColor) {
            case 'black':
            case 'transparent':
            return $backgroundColor;

            default:
            return 'white';
        }
    }


    /**
     * Extract a type graph value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'vertical' or 'horizontal'. 'vertical' by default.
     */
    private static function extractTypeGraph(array $data): string
    {
        $typeGraph = static::notEmptyStringOr(
            static::issetInArray($data, ['typeGraph', 'type_graph']),
            null
        );

        switch ($typeGraph) {
            case 'horizontal':
            return 'horizontal';

            default:
            return 'vertical';
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

        // Load config.
        global $config;

        // Load side libraries.
        include_once $config['homedir'].'/include/functions_ui.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';
        include_once $config['homedir'].'/include/graphs/fgraph.php';

        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Extract needed properties.
        $gridColor = static::extractGridColor($data);
        $backGroundColor = static::extractBackgroundColor($data);
        $typeGraph = static::extractTypeGraph($data);

        // Get the linked agent and module Ids.
        $linkedModule = static::extractLinkedModule($data);
        $agentId = $linkedModule['agentId'];
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

        // Maybe connect to node.
        $nodeConnected = false;
        if (\is_metaconsole() === true && $metaconsoleId !== null) {
            $server = \metaconsole_get_connection_by_id($metaconsoleId);
            $nodeConnected = \metaconsole_connect(
                $server
            ) === NOERR;

            if ($nodeConnected === false) {
                throw new \InvalidArgumentException(
                    'error connecting to the node'
                );
            }
        }

        $width = (int) $data['width'];
        $height = (int) $data['height'];
        $moduleData = \get_bars_module_data($moduleId, $typeGraph);

        if (empty($moduleData) === true) {
            $graph = graph_nodata_image(['width' => $width, 'height' => $height]);
        } else {
            array_pop($moduleData['labels']);
            array_pop($moduleData['data']);

            $waterMark = [
                'file' => $config['homedir'].'/images/logo_vertical_water.png',
                'url'  => \ui_get_full_url(
                    'images/logo_vertical_water.png',
                    false,
                    false,
                    false
                ),
            ];

            if ((int) $data['width'] === 0 || (int) $data['height'] === 0) {
                $width = 400;
                $height = 400;
            } else {
                $width = (int) $data['width'];
                $height = (int) $data['height'];
            }

            if (empty($moduleData) === true) {
                $image = ui_get_full_url(
                    'images/image_problem_area.png',
                    false,
                    false,
                    false
                );
                $rc = file_get_contents($image);
                if ($rc !== false) {
                    $graph = base64_encode($rc);
                } else {
                    $graph = graph_nodata_image(['height' => $height]);
                }
            } else {
                $size = $config['font_size'];
                if ($ratio != 0) {
                    $size = ($config['font_size'] * $ratio);
                }

                $options = [
                    'width'     => $width,
                    'height'    => $height,
                    'waterMark' => $waterMark,
                    'legend'    => ['display' => false],
                    'scales'    => [
                        'x' => [
                            'grid'  => [
                                'display' => true,
                                'color'   => $gridColor,
                            ],
                            'ticks' => [
                                'fonts' => ['size' => $size],
                            ],
                        ],
                        'y' => [
                            'grid'  => [
                                'display' => true,
                                'color'   => $gridColor,
                            ],
                            'ticks' => [
                                'fonts' => ['size' => $size],
                            ],
                        ],
                    ],
                    'labels'    => $moduleData['labels'],
                ];

                if ($typeGraph === 'horizontal') {
                    $options['axis'] = 'y';
                }

                $graph = '<div style="background-color:'.$backGroundColor.'">';
                $graph .= vbar_graph($moduleData['data'], $options);
                $graph .= '</div>';
            }
        }

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        $data['html'] = $graph;

        return $data;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @param array $values Default values.
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public static function getFormInputs(array $values): array
    {
        // Default values.
        $values = static::getDefaultGeneralValues($values);

        // Retrieve global - common inputs.
        $inputs = Item::getFormInputs($values);

        if (is_array($inputs) !== true) {
            throw new Exception(
                '[BarsGraph]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
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
                    'name'     => 'backgroundColor',
                    'selected' => $values['backgroundColor'],
                    'return'   => true,
                    'sort'     => false,
                ],
            ];

            // Graph Type.
            $fields = [
                'horizontal' => __('Horizontal'),
                'vertical'   => __('Vertical'),
            ];

            $inputs[] = [
                'label'     => __('Graph Type'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'typeGraph',
                    'selected' => $values['typeGraph'],
                    'return'   => true,
                ],
            ];

            // Grid color.
            $inputs[] = [
                'label'     => __('Grid color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'gridColor',
                    'type'    => 'color',
                    'value'   => $values['gridColor'],
                    'return'  => true,
                ],
            ];

            // Autocomplete agents.
            $inputs[] = [
                'label'     => __('Agent'),
                'arguments' => [
                    'type'                    => 'autocomplete_agent',
                    'name'                    => 'agentAlias',
                    'id_agent_hidden'         => $values['agentId'],
                    'name_agent_hidden'       => 'agentId',
                    'server_id_hidden'        => $values['metaconsoleId'],
                    'name_server_hidden'      => 'metaconsoleId',
                    'return'                  => true,
                    'module_input'            => true,
                    'module_name'             => 'moduleId',
                    'module_none'             => false,
                    'get_only_string_modules' => true,
                ],
            ];

            // Autocomplete module.
            $inputs[] = [
                'label'     => __('Module'),
                'arguments' => [
                    'type'                    => 'autocomplete_module',
                    'fields'                  => $fields,
                    'name'                    => 'moduleId',
                    'selected'                => $values['moduleId'],
                    'return'                  => true,
                    'sort'                    => false,
                    'agent_id'                => $values['agentId'],
                    'metaconsole_id'          => $values['metaconsoleId'],
                    'get_only_string_modules' => true,
                ],
            ];
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
