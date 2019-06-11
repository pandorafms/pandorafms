<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a simple value item of the Visual Console.
 */
final class SimpleValue extends Item
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
     * @overrides Item->validateData.
     */
    protected function validateData(array $data): void
    {
        parent::validateData($data);
        if (isset($data['value']) === false) {
            throw new \InvalidArgumentException(
                'the value property is required and should be string'
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
     * @overrides Item->decode.
     */
    protected function decode(array $data): array
    {
        $return = parent::decode($data);
        $return['type'] = SIMPLE_VALUE;
        $return['processValue'] = static::extractProcessValue($data);
        $return['valueType'] = static::extractValueType($data);
        $return['value'] = $data['value'];

        if ($return['processValue'] !== 'none') {
            $return['period'] = static::extractPeriod($data);
        }

        // Clear the size, as this element always have a dynamic size.
        $return['width'] = 0;
        $return['height'] = 0;

        return $return;
    }


    /**
     * Extract a process value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'none', 'avg', 'max' or 'min'. 'none' by default.
     */
    private static function extractProcessValue(array $data): string
    {
        if (isset($data['processValue'])) {
            switch ($data['processValue']) {
                case 'none':
                case 'avg':
                case 'max':
                case 'min':
                return $data['processValue'];

                default:
                return 'none';
            }
        } else {
            switch ($data['type']) {
                case SIMPLE_VALUE_MAX:
                return 'max';

                case SIMPLE_VALUE_MIN:
                return 'min';

                case SIMPLE_VALUE_AVG:
                return 'avg';

                default:
                return 'none';
            }
        }
    }


    /**
     * Extract the value of period.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer The period in seconds. 0 is the minimum value.
     */
    private static function extractPeriod(array $data): int
    {
        $period = static::parseIntOr(
            static::issetInArray($data, ['period']),
            0
        );
        return ($period >= 0) ? $period : 0;
    }


    /**
     * Extract a value type.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'string' or 'image'. 'string' by default.
     */
    private static function extractValueType(array $data): string
    {
        switch ($data['valueType']) {
            case 'string':
            case 'image':
            return $data['valueType'];

            default:
            return 'string';
        }
    }


    /**
     * Fetch a vc item data structure from the database using a filter.
     *
     * @param array $filter Filter of the Visual Console Item.
     *
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \InvalidArgumentException When a module Id cannot be found.
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

        // Get the formatted value.
        $value = \visual_map_get_simple_value(
            $data['type'],
            $moduleId,
            static::extractPeriod($data)
        );

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        // Some modules are image based. Extract the base64 image if needed.
        $matches = [];
        if (\preg_match('/src=\"(data:image.*)"/', $value, $matches) === 1) {
            $data['valueType'] = 'image';
            $data['value'] = $matches[1];
        } else {
            $data['valueType'] = 'string';
            $data['value'] = $value;
        }

        return $data;
    }


}
