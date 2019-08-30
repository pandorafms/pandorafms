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
        // TODO: Add min value to the database.
        $return['minValue'] = static::parseFloatOr(
            static::issetInArray($data, ['minValue']),
            null
        );
        $return['maxValue'] = static::parseFloatOr(
            static::issetInArray($data, ['maxValue', 'height']),
            null
        );
        $return['color'] = static::extractColor($data);
        $return['labelColor'] = static::extractLabelColor($data);
        $return['value'] = static::parseFloatOr(
            static::issetInArray($data, ['value']),
            null
        );
        $return['unit'] = static::notEmptyStringOr(
            static::issetInArray($data, ['unit']),
            null
        );
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
        include_once $config['homedir'].'/include/functions_modules.php';
        include_once $config['homedir'].'/include/functions_io.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Get the linked module Id.
        $linkedModule = static::extractLinkedModule($data);
        $moduleId = static::parseIntOr($linkedModule['moduleId'], null);
        $metaconsoleId = static::parseIntOr(
            $linkedModule['metaconsoleId'],
            null
        );

        // Get the value type.
        $valueType = static::extractValueType($data);

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

        $moduleValue = \modules_get_last_value($moduleId);
        if ($moduleValue === false) {
            throw new \InvalidArgumentException(
                'error fetching the module value'
            );
        }

        // Store the module value.
        $data['value'] = (float) \number_format((float) $moduleValue, (int) $config['graph_precision'], '.', '');
        $unit = \modules_get_unit($moduleId);
        if (empty($unit) === false) {
            $data['unit'] = \io_safe_output($unit);
        }

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        return $data;
    }


}
