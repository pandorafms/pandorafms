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
        $return['color'] = $this->extractBorderColor($data);
        return $return;
    }


    /**
     * Extract a border color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the border color (not empty) or null.
     */
    private function extractBorderColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['color', 'border_color']),
            null
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
        include_once $config['homedir'].'/include/functions_graph.php';

        // Get the linked agent and module Ids.
        $linkedModule = static::extractLinkedModule($data);
        $agentId = static::parseIntOr($linkedModule['agentId'], null);
        $moduleId = static::parseIntOr($linkedModule['moduleId'], null);

        if ($agentId === null) {
            throw new \InvalidArgumentException('missing agent Id');
        }

        if (!empty($data['id_metaconsole'])) {
            $connection = db_get_row_filter('tmetaconsole_setup', $data['id_metaconsole']);
            if (metaconsole_load_external_db($connection) != NOERR) {
                throw new \InvalidArgumentException(
                    'error connecting to the node'
                );
            }
        }

        $is_string = db_get_value_filter(
            'id_tipo_modulo',
            'tagente_modulo',
            [
                'id_agente'        => $agentId,
                'id_agente_modulo' => $moduleId,
            ]
        );

        if (!empty($data['id_metaconsole'])) {
            metaconsole_restore_db();
        }

        if (($is_string === 17) || ($is_string === 23) || ($is_string === 3)
            || ($is_string === 10) || ($is_string === 33)
        ) {
            $donut_data = get_donut_module_data($moduleId);

            $img = d3_donut_graph(
                $data['id'],
                $data['width'],
                $data['width'],
                $donut_data,
                $data['border_color']
            );
        } else {
            if ($data['id_metaconsole'] !== 0) {
                $img = '<img src="../../images/console/signes/wrong_donut_graph.png">';
            } else {
                $img = '<img src="images/console/signes/wrong_donut_graph.png">';
            }
        }

        $data['html'] = $img;

        return $data;
    }


}
