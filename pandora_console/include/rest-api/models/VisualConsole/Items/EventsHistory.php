<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a events history item of the Visual Console.
 */
final class EventsHistory extends Item
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
        $return['type'] = AUTO_SLA_GRAPH;
        $return['maxTime'] = static::extractMaxTime($data);
        return $return;
    }


    /**
     * Extract a graph period value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The time in seconds of the graph period or null.
     */
    private static function extractMaxTime(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['maxTime', 'period']),
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

        // Default size.
        if ($data['width'] == 0 || $data['height'] == 0) {
            $data['width'] = 500;
            $data['height'] = 50;
        }

        // Use the same HTML output as the old VC.
        $html = \graph_graphic_moduleevents(
            $agentId,
            $moduleId,
            (int) $data['width'],
            (int) $data['height'],
            static::extractMaxTime($data),
            '',
            true
        );

        $data['html'] = $html;

        return $data;
    }


    /**
     * Generate a link to something related with the item.
     *
     * @param array $data Visual Console Item's data structure.
     *
     * @return mixed The link or a null value.
     *
     * @override Item::buildLink.
     */
    protected static function buildLink(array $data)
    {
        // Get the linked agent and module Ids.
        $linkedModule = static::extractLinkedModule($data);
        $agentId = static::parseIntOr($linkedModule['agentId'], null);
        $moduleId = static::parseIntOr($linkedModule['moduleId'], null);

        $baseUrl = \ui_get_full_url('index.php');

        return $baseUrl.'?'.http_build_query(
            [
                'sec'                  => 'eventos',
                'sec2'                 => 'operation/events/events',
                'id_agent'             => $agentId,
                'module_search_hidden' => $moduleId,
                'event_view_hr'        => (static::extractMaxTime($data) / 3600),
                'status'               => -1,
            ]
        );
    }


}
