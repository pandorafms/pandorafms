<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a percentile item of the Visual Console.
 */
final class Percentile extends Item
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

        if (static::notEmptyStringOr(static::issetInArray($data, ['encodedHtml']), null) === null
            && static::notEmptyStringOr(static::issetInArray($data, ['html']), null) === null
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
        $return['type'] = PERCENTILE_BAR;
        $return['percentileType'] = static::extractPercentileType($data);
        $return['valueType'] = static::extractValueType($data);
        $return['value'] = static::notEmptyStringOr(static::issetInArray($data, ['value']), null);
        $return['color'] = static::extractColor($data);
        $return['labelColor'] = static::extractLabelColor($data);
        return $return;
    }


    /**
     * Extract a percentile type value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string 'progress-bar', 'bubble', 'circular-progress-bar'
     * or 'circular-progress-bar-alt'. 'progress-bar' by default.
     */
    private static function extractPercentileType(array $data): string
    {
        if (isset($data['percentileType']) === true) {
            switch ($data['percentileType']) {
                case 'progress-bar':
                case 'bubble':
                case 'circular-progress-bar':
                case 'circular-progress-bar-alt':
                return $data['percentileType'];

                default:
                return 'progress-bar';
            }
        }

        switch ($data['type']) {
            case PERCENTILE_BUBBLE:
            return 'bubble';

            case CIRCULAR_PROGRESS_BAR:
            return 'circular-progress-bar';

            case CIRCULAR_INTERIOR_PROGRESS_BAR:
            return 'circular-progress-bar-alt';

            default:
            case PERCENTILE_BAR:
            return 'progress-bar';
        }
    }


    /**
     * Extract a value type value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string 'percent' or 'value'. 'percent' by default.
     */
    private static function extractValueType(array $data): string
    {
        $rawValueType = static::issetInArray($data, ['valueType', 'image']);

        switch ($rawValueType) {
            case 'percent':
            case 'value':
            return $rawValueType;

            default:
            return 'percent';
        }
    }


    /**
     * Extract a color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The color or null.
     */
    private static function extractColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['color', 'border_color']),
            null
        );
    }


    /**
     * Extract a label color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The label color or null.
     */
    private static function extractLabelColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['labelColor', 'fill_color']),
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

        // TODO: Use the same HTML output as the old VC.
        $html = '';

        $data['html'] = $html;

        return $data;
    }


}
