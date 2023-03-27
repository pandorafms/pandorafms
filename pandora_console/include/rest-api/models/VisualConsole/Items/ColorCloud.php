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
      * Encode the ranges color value.
      *
      * @param array $data Unknown input data structure.
      *
      * @return array Ranges color.
      */
    private static function encodeColorRanges(array $data): array
    {
        $colorRangeArray = [];

        if (isset($data['colorRanges']) === true
            && is_array($data['colorRanges']) === true
        ) {
            if (empty($data['colorRanges']) === false) {
                foreach ($data['colorRanges'] as $colorRange) {
                    if (\is_numeric($colorRange['fromValue']) === true
                        && \is_numeric($colorRange['toValue']) === true
                        && static::notEmptyStringOr(
                            $colorRange['color'],
                            null
                        ) !== null
                    ) {
                        $colorRangeArray[] = [
                            'color'      => $colorRange['color'],
                            'from_value' => (float) $colorRange['fromValue'],
                            'to_value'   => (float) $colorRange['toValue'],
                        ];
                    }
                }
            } else {
                $colorRangeArray = [];
            }
        }

        return $colorRangeArray;
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

        $colorRanges = null;

        $defaultColor = null;

        if (isset($data['defaultColor']) === true) {
            $defaultColor = static::extractDefaultColor($data);
        }

        if (isset($data['colorRanges']) === true) {
            $colorRanges = static::encodeColorRanges($data);
        }

        if (empty($data['id']) === true) {
            $return['label'] = json_encode(
                [
                    'default_color' => $defaultColor,
                    'color_ranges'  => $colorRanges,
                ]
            );
        } else {
            $prevData = $data;
            $prevDataDefaultColor = static::extractDefaultColor(
                ['defaultColor' => $prevData['defaultColor']]
            );
            $prevDataColorRanges = static::encodeColorRanges(
                ['colorRanges' => $prevData['colorRanges']]
            );

            $return['label'] = json_encode(
                [
                    'default_color' => ($defaultColor !== null) ? $defaultColor : $prevDataDefaultColor,
                    'color_ranges'  => ($colorRanges !== null) ? $colorRanges : $prevDataColorRanges,
                ]
            );
        }

        return $return;
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
        include_once $config['homedir'].'/include/functions_modules.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Get the linked module Id.
        $linkedModule = static::extractLinkedModule($data);
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

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
                '[ColorCloud]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // Autocomplete agents.
            $inputs[] = [
                'label'     => __('Agent'),
                'arguments' => [
                    'type'               => 'autocomplete_agent',
                    'name'               => 'agentAlias',
                    'id_agent_hidden'    => $values['agentId'],
                    'name_agent_hidden'  => 'agentId',
                    'server_id_hidden'   => $values['metaconsoleId'],
                    'name_server_hidden' => 'metaconsoleId',
                    'return'             => true,
                    'module_input'       => true,
                    'module_name'        => 'moduleId',
                    'module_none'        => false,
                ],
            ];

            // Autocomplete module.
            $inputs[] = [
                'label'     => __('Module'),
                'arguments' => [
                    'type'           => 'autocomplete_module',
                    'fields'         => $fields,
                    'name'           => 'moduleId',
                    'selected'       => $values['moduleId'],
                    'return'         => true,
                    'sort'           => false,
                    'agent_id'       => $values['agentId'],
                    'metaconsole_id' => $values['metaconsoleId'],
                ],
            ];

            // Default color.
            $inputs[] = [
                'label'     => __('Default color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'defaultColor',
                    'type'    => 'color',
                    'value'   => $values['defaultColor'],
                    'return'  => true,
                ],
            ];

            // Label.
            $inputs[] = [
                'label' => __('Add new range').':',
            ];

            $baseUrl = ui_get_full_url('/', false, false, false);
            // Default ranges.
            $inputs[] = [
                'block_id'      => 'default-ranges',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('From'),
                        'arguments' => [
                            'id'     => 'rangeDefaultFrom',
                            'name'   => 'rangeDefaultFrom',
                            'type'   => 'number',
                            'value'  => 0,
                            'return' => true,
                            'min'    => 0,
                        ],
                    ],
                    [
                        'label'     => __('To'),
                        'arguments' => [
                            'id'     => 'rangeDefaultTo',
                            'name'   => 'rangeDefaultTo',
                            'type'   => 'number',
                            'value'  => 0,
                            'return' => true,
                            'min'    => 0,
                        ],
                    ],
                    [
                        'label'     => __('Color'),
                        'arguments' => [
                            'wrapper' => 'div',
                            'name'    => 'rangeDefaultColor',
                            'type'    => 'color',
                            'value'   => '#000000',
                            'return'  => true,
                        ],
                    ],
                    [
                        'arguments' => [
                            'name'       => 'add',
                            'label'      => __('Add'),
                            'type'       => 'button',
                            'attributes' => [
                                'mode' => 'mini secondary',
                                'icon' => 'next',
                            ],
                            'return'     => true,
                            'script'     => 'createColorRange("'.$baseUrl.'","'.$values['vCId'].'")',
                        ],
                    ],
                ],
            ];

            // Label.
            $inputs[] = [
                'label' => __('Current ranges').':',
            ];

            if (isset($values['colorRanges']) === true
                && is_array($values['colorRanges']) === true
                && empty($values['colorRanges']) === false
            ) {
                foreach ($values['colorRanges'] as $k => $v) {
                    $uniqId = \uniqid();
                    $inputs[] = [
                        'block_id'      => $uniqId,
                        'class'         => 'interval-color-ranges flex-row flex-start w100p',
                        'direct'        => 1,
                        'block_content' => [
                            [
                                'label'     => __('From'),
                                'arguments' => [
                                    'name'   => 'rangeFrom[]',
                                    'type'   => 'number',
                                    'value'  => $v['fromValue'],
                                    'return' => true,
                                    'min'    => 0,
                                ],
                            ],
                            [
                                'label'     => __('To'),
                                'arguments' => [
                                    'name'   => 'rangeTo[]',
                                    'type'   => 'number',
                                    'value'  => $v['toValue'],
                                    'return' => true,
                                    'min'    => 0,
                                ],
                            ],
                            [
                                'label'     => __('Color'),
                                'arguments' => [
                                    'wrapper' => 'div',
                                    'id'      => 'rangeColor'.$uniqId,
                                    'name'    => 'rangeColor[]',
                                    'type'    => 'color',
                                    'value'   => $v['color'],
                                    'return'  => true,
                                ],
                            ],
                            [
                                'arguments' => [
                                    'name'       => 'remove-'.$uniqId,
                                    'label'      => __('Remove'),
                                    'type'       => 'button',
                                    'attributes' => [
                                        'mode' => 'mini secondary',
                                        'icon' => 'delete',
                                    ],
                                    'return'     => true,
                                    'script'     => 'removeColorRange("'.$uniqId.'")',
                                ],
                            ],
                        ],
                    ];
                }
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
            $values['height'] = 180;
        }

        return $values;
    }


}
