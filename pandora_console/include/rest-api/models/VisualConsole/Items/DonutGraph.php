<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a Donut Graph item of the Visual Console.
 */
final class DonutGraph extends Item
{

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked visual console.
     *
     * @var boolean
     */
    protected static $useLinkedVisualConsole = true;

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
        $return['type'] = DONUT_GRAPH;
        $return['legendBackgroundColor'] = static::extractLegendBackgroundColor(
            $data
        );
        return $return;
    }


    /**
     * Extract a border color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the border color (not empty) or null.
     */
    private static function extractLegendBackgroundColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'legendBackgroundColor',
                    'border_color',
                ]
            ),
            '#ffffff'
        );
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
        include_once $config['homedir'].'/include/functions_visual_map.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Extract needed properties.
        // Get the linked agent and module Ids.
        $linkedModule = static::extractLinkedModule($data);
        $agentId = $linkedModule['agentId'];
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

        if ($agentId === null) {
            throw new \InvalidArgumentException('missing agent Id');
        }

        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

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

        $sql = sprintf(
            'SELECT COUNT(tam.id_agente_modulo)
            FROM tagente_modulo tam
            INNER JOIN ttipo_modulo ttm
                ON tam.id_tipo_modulo = ttm.id_tipo
            WHERE tam.id_agente = %d
                AND tam.id_agente_modulo = %d
                AND ttm.nombre LIKE \'%%_string\'',
            $agentId,
            $moduleId
        );

        $isString = (bool) \db_get_value_sql($sql);

        $width = (int) $data['width'];
        $height = (int) $data['height'];

        if ($isString === true) {
            $graphData = \get_donut_module_data($moduleId);

            if (empty($graphData) === true) {
                $data['html'] = graph_nodata_image(['width' => $width, 'height' => $height]);
            } else {
                $options = [
                    'waterMark' => false,
                    'legend'    => [
                        'display'  => true,
                        'position' => 'right',
                        'align'    => 'center',
                    ],
                    'labels'    => $graphData['labels'],
                ];

                $data['html'] = \ring_graph($graphData['data'], $options);
            }
        } else {
            $data['html'] = graph_nodata_image(['width' => $width, 'height' => $height]);
        }

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
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
                '[DonutGraph]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
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
                    'name'                    => 'moduleId',
                    'selected'                => $values['moduleId'],
                    'return'                  => true,
                    'sort'                    => false,
                    'agent_id'                => $values['agentId'],
                    'metaconsole_id'          => $values['metaconsoleId'],
                    'get_only_string_modules' => true,
                ],
            ];

            // Resume data color.
            $inputs[] = [
                'label'     => __('Background color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'legendBackgroundColor',
                    'type'    => 'color',
                    'value'   => (($values['legendBackgroundColor']) ?? '#ffffff'),
                    'return'  => true,
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
            $values['height'] = 300;
        }

        return $values;
    }


}
