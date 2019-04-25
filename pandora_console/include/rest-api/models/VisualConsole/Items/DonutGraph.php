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
            '#000000'
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
    protected static function fetchDataFromDB(array $filter): array
    {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $data = parent::fetchDataFromDB($filter);

        /*
         * Retrieve extra data.
         */

        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_visual_map.php';
        include_once $config['homedir'].'/include/graphs/functions_d3.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Extract needed properties.
        $legendBackGroundColor = static::extractLegendBackgroundColor($data);
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

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        if ($isString === true) {
            $graphData = \get_donut_module_data($moduleId);

            $width = (int) $data['width'];
            $height = (int) $data['height'];

            // Default width.
            if ($width <= 0) {
                $width = 300;
            }

            // Default height.
            if ($height <= 0) {
                $height = 300;
            }

            $data['html'] = \d3_donut_graph(
                (int) $data['id'],
                $width,
                $height,
                $graphData,
                $legendBackGroundColor
            );
        } else {
            $src = 'images/console/signes/wrong_donut_graph.png';
            if (\is_metaconsole() === true && $metaconsoleId !== null) {
                $src = '../../'.$src;
            }

            $data['html'] = '<img src="'.$src.'">';
        }

        return $data;
    }


}
