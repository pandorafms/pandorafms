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
     * Validate the received data structure to ensure if we can extract the
     * values required to build the model.
     *
     * @param array $data Input data.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If any input value is considered
     * invalid.
     *
     * @overrides Item::validateData.
     */
    protected function validateData(array $data): void
    {
        parent::validateData($data);

        if (static::notEmptyStringOr($data['encodedHtml'], null) === null
            && static::notEmptyStringOr($data['html'], null) === null
        ) {
            throw new \InvalidArgumentException(
                'the html property is required and should be string'
            );
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
        $return['type'] = AUTO_SLA_GRAPH;
        $return['maxTime'] = $this->extractMaxTime($data);
        $return['encodedHtml'] = $this->extractEncodedHtml($data);
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
     * Extract a encoded HTML representation of the item.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string The HTML representation in base64 encoding.
     */
    private static function extractEncodedHtml(array $data): string
    {
        if (isset($data['encodedHtml']) === true) {
            return $data['encodedHtml'];
        } else if (isset($data['html']) === true) {
            return \base64_encode($data['html']);
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

        // Use the same HTML output as the old VC.
        $html = '<div style="width:500px;">';
        $html .= \graph_graphic_moduleevents(
            $agentId,
            $moduleId,
            (int) $data['width'],
            (int) $data['height'],
            static::extractMaxTime($data),
            '',
            true
        );
        $html .= '</div>';

        $data['encodedHtml'] = \base64_encode($html);

        return $data;
    }


}
