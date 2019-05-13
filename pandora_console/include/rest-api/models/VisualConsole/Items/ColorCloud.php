<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a color cloud item of the Visual Console.
 */
final class ColorCloud extends Item
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
        $decodedData = parent::decode($data);
        $decodedData['type'] = COLOR_CLOUD;
        $decodedData['label'] = null;
        $decodedData['defaultColor'] = static::extractDefaultColor($data);
        $decodedData['colorRanges'] = static::extractColorRanges($data);
        $decodedData['color'] = static::notEmptyStringOr(
            static::issetInArray($data, ['color']),
            null
        );

        return $decodedData;
    }


    /**
     * Extract the default color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string Default color.
     * @throws \InvalidArgumentException If the default color cannot be
     * extracted.
     */
    private static function extractDefaultColor(array $data): string
    {
        if (isset($data['defaultColor'])) {
            $defaultColor = static::notEmptyStringOr(
                $data['defaultColor'],
                null
            );

            if ($defaultColor === null) {
                throw new \InvalidArgumentException(
                    'the default color property is required and should be a not empty string'
                );
            }

            return $defaultColor;
        } else {
            $dynamicData = static::extractDynamicData($data);
            return $dynamicData['defaultColor'];
        }
    }


    /**
     * Extract a list of color ranges.
     *
     * @param array $data Unknown input data structure.
     *
     * @return array Color ranges list.
     * @throws \InvalidArgumentException If any of the color ranges is invalid.
     */
    private static function extractColorRanges(array $data): array
    {
        if (isset($data['colorRanges']) && \is_array($data['colorRanges'])) {
            // Validate the color ranges.
            foreach ($data['colorRanges'] as $colorRange) {
                if (\is_numeric($colorRange['fromValue']) === false
                    || \is_numeric($colorRange['toValue']) === false
                    || static::notEmptyStringOr($colorRange['color'], null) === null
                ) {
                    throw new \InvalidArgumentException('invalid color range');
                }
            }

            return $data['colorRanges'];
        } else if (isset($data['label']) === true) {
            $dynamicData = static::extractDynamicData($data);
            return $dynamicData['colorRanges'];
        } else {
            return [];
        }
    }


    /**
     * Extract a dynamic data structure from the 'label' field.
     *
     * @param array $data Unknown input data structure.
     *
     * @return array Dynamic data structure.
     * @throws \InvalidArgumentException If the structure cannot be built.
     *
     * @example [
     *     'defaultColor' => '#FFF',
     *     'colorRanges'  => [
     *         [
     *             'fromValue' => 50.0,
     *             'toValue'   => 150.5,
     *             'color'     => '#000',
     *         ],
     *         [
     *             'fromValue' => 200.0,
     *             'toValue'   => 300.5,
     *             'color'     => '#F0F0F0',
     *         ],
     *     ]
     * ]
     */
    private static function extractDynamicData(array $data): array
    {
        $dynamicDataEncoded = static::notEmptyStringOr($data['label'], null);

        if ($dynamicDataEncoded === null) {
            throw new \InvalidArgumentException('dynamic data not found');
        }

        $result = [];

        try {
            $dynamicData = \json_decode($dynamicDataEncoded, true);

            $result['defaultColor'] = $dynamicData['default_color'];
            $result['colorRanges'] = [];

            if (\is_array($dynamicData['color_ranges']) === true) {
                foreach ($dynamicData['color_ranges'] as $colorRange) {
                    if (\is_numeric($colorRange['from_value']) === true
                        && \is_numeric($colorRange['to_value']) === true
                        && static::notEmptyStringOr(
                            $colorRange['color'],
                            null
                        ) !== null
                    ) {
                        $result['colorRanges'][] = [
                            'color'     => $colorRange['color'],
                            'fromValue' => (float) $colorRange['from_value'],
                            'toValue'   => (float) $colorRange['to_value'],
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('invalid dynamic data');
        }

        return $result;
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
        include_once $config['homedir'].'/include/functions_modules.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Get the linked module Id.
        $linkedModule = static::extractLinkedModule($data);
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

        $dynamicData = static::extractDynamicData($data);
        // Set the initial color.
        $data['color'] = $dynamicData['defaultColor'];

        // Search for a matching color range.
        if (empty($dynamicData['colorRanges']) === false) {
            // Connect to node.
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

            // Fetch module value.
            $value = false;
            if ($metaconsoleId === null
                || ($metaconsoleId !== null && $nodeConnected)
            ) {
                $value = \modules_get_last_value($moduleId);
            }

            // Restore connection.
            if ($nodeConnected === true) {
                \metaconsole_restore_db();
            }

            // Value found.
            if ($value !== false) {
                /*
                 * TODO: It would be ok to give support to string values in the
                 * future?
                 *
                 * It can be done by matching the range value with the value
                 * if it is a string. I think the function to retrieve the value
                 * only supports numeric values.
                 */

                $value = (float) $value;
                foreach ($dynamicData['colorRanges'] as $colorRange) {
                    if ($colorRange['fromValue'] <= $value
                        && $colorRange['toValue'] >= $value
                    ) {
                        // Range matched. Use the range color.
                        $data['color'] = $colorRange['color'];
                        break;
                    }
                }
            }
        }

        return $data;
    }


}
