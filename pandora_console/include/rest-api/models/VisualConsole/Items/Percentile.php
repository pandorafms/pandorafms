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
     * Encode type item.
     *
     * @param array $data Data for encode.
     *
     * @return string Return 'PERCENTILE_BAR', 'PERCENTILE_BUBBLE',
     * 'CIRCULAR_PROGRESS_BAR' or 'CIRCULAR_INTERIOR_PROGRESS_BAR'.
     * 'PERCENTILE_BAR' by default.
     */
    protected function encodePercentileType(array $data): ?int
    {
        $type = null;
        if (isset($data['percentileType']) === true) {
            switch ($data['percentileType']) {
                case 'bubble':
                    $type = PERCENTILE_BUBBLE;
                break;

                case 'circular-progress-bar':
                    $type = CIRCULAR_PROGRESS_BAR;
                break;

                case 'circular-progress-bar-alt':
                    $type = CIRCULAR_INTERIOR_PROGRESS_BAR;
                break;

                default:
                case 'progress-bar':
                    $type = PERCENTILE_BAR;
                break;
            }
        }

        return $type;
    }


    /**
     * Encode type item.
     *
     * @param array $data Data for encode.
     *
     * @return string Return 'PERCENTILE_BAR', 'PERCENTILE_BUBBLE',
     * 'CIRCULAR_PROGRESS_BAR' or 'CIRCULAR_INTERIOR_PROGRESS_BAR'.
     * 'PERCENTILE_BAR' by default.
     */
    protected function encodeValueType(array $data): ?string
    {
        $valueType = null;
        if (isset($data['valueType']) === true) {
            switch ($data['valueType']) {
                case 'percent':
                case 'value':
                    $valueType = $data['valueType'];
                break;

                default:
                    $valueType = 'percent';
                break;
            }
        }

        return $valueType;
    }


    /**
     * Encode type item.
     *
     * @param array $data Data for encode.
     *
     * @return string Return 'PERCENTILE_BAR', 'PERCENTILE_BUBBLE',
     * 'CIRCULAR_PROGRESS_BAR' or 'CIRCULAR_INTERIOR_PROGRESS_BAR'.
     * 'PERCENTILE_BAR' by default.
     */
    protected function encodeLabelColor(array $data): ?string
    {
        $labelColor = null;
        if (isset($data['labelColor']) === true) {
            switch ($data['labelColor']) {
                case 'fillColor':
                case 'fill_color':
                case 'labelColor':
                    $labelColor = $data['labelColor'];
                break;

                default:
                    $labelColor = '#444444';
                break;
            }
        }

        return $labelColor;
    }


    /**
     * Encode type item.
     *
     * @param array $data Data for encode.
     *
     * @return string Return 'PERCENTILE_BAR', 'PERCENTILE_BUBBLE',
     * 'CIRCULAR_PROGRESS_BAR' or 'CIRCULAR_INTERIOR_PROGRESS_BAR'.
     * 'PERCENTILE_BAR' by default.
     */
    protected function encodeColor(array $data): ?string
    {
        $color = null;
        if (isset($data['color']) === true) {
            switch ($data['color']) {
                case 'borderColor':
                case 'border_color':
                case 'gridColor':
                case 'color':
                case 'legendBackgroundColor':
                    $color = $data['color'];
                break;

                default:
                    $color = '#F0F0F0';
                break;
            }
        }

        return $color;
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
    protected function encode(array $data): array
    {
        $return = parent::encode($data);

        $max_value = static::parseIntOr(
            static::issetInArray($data, ['maxValue']),
            null
        );
        if ($max_value !== null) {
            // TODO: XXX.
            // $return['height'] = $max_value;
        }

        $percentileType = static::encodePercentileType($data);
        if ($percentileType !== null) {
            $return['type'] = (int) $percentileType;
        }

        $valueType = static::encodeValueType($data);
        if ($valueType !== null) {
            $return['image'] = (string) $valueType;
        }

        $color = static::encodeColor($data);
        if ($border_color !== null) {
            $result['border_color'] = $color;
        }

        $labelColor = static::encodeLabelColor($data);
        if ($labelColor !== null) {
            $result['fill_color'] = $labelColor;
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
     * @overrides Item::decode.
     */
    protected function decode(array $data): array
    {
        $return = parent::decode($data);
        $return['type'] = (int) $data['type'];
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

        if ($moduleId !== null && $moduleId !== 0) {
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
        } else {
            $moduleValue = 0;
        }

        // Store the module value.
        $data['value'] = (float) \number_format(
            (float) $moduleValue,
            (int) $config['graph_precision'],
            '.',
            ''
        );
        $unit = '';
        if ($moduleId !== null && $moduleId !== 0) {
            $unit = \modules_get_unit($moduleId);
            if (empty($unit) === false) {
                $data['unit'] = \io_safe_output($unit);
            }
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
        // Retrieve global - common inputs.
        $inputs = Item::getFormInputs($values);

        if (is_array($inputs) !== true) {
            throw new Exception(
                '[Percentile]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // Type percentile.
            $fields = [
                'progress-bar'              => __('Percentile'),
                'bubble'                    => __('Bubble'),
                'circular-progress-bar'     => __('Circular porgress bar'),
                'circular-progress-bar-alt' => __(
                    'Circular progress bar (interior)'
                ),
            ];

            $inputs[] = [
                'label'     => __('Type'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'percentileType',
                    'selected' => $values['percentileType'],
                    'return'   => true,
                    'sort'     => false,
                ],
            ];

            // TODO: QUIT WIDTH FORM GENERAL PAGE.
            // Diameter.
            $diameter = (isset($values['width']) === true) ? $values['width'] : 200;
            $inputs[] = [
                'label'     => __('Diameter'),
                'arguments' => [
                    'name'   => 'width',
                    'type'   => 'number',
                    'value'  => $diameter,
                    'return' => true,
                ],
            ];

            // TODO: ADD bbdd.
            // Min Value.
            $inputs[] = [
                'label'     => __('Min. Value'),
                'arguments' => [
                    'name'   => 'minValue',
                    'type'   => 'number',
                    'value'  => $values['minValue'],
                    'return' => true,
                ],
            ];

            // TODO: ADD bbdd.
            // Max Value.
            $inputs[] = [
                'label'     => __('Max. Value'),
                'arguments' => [
                    'name'   => 'maxValue',
                    'type'   => 'number',
                    'value'  => $values['maxValue'],
                    'return' => true,
                ],
            ];

            // Value to show.
            $fields = [
                'percent' => __('Percent'),
                'value'   => __('Value'),
            ];

            $inputs[] = [
                'label'     => __('Value to show'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'valueType',
                    'selected' => $values['valueType'],
                    'return'   => true,
                    'sort'     => false,
                ],
            ];

            // Element color.
            $inputs[] = [
                'label'     => __('Element color'),
                'arguments' => [
                    'name'   => 'color',
                    'type'   => 'color',
                    'value'  => $values['color'],
                    'return' => true,
                ],
            ];

            // Value color.
            $inputs[] = [
                'label'     => __('Value color'),
                'arguments' => [
                    'name'   => 'labelColor',
                    'type'   => 'color',
                    'value'  => $values['labelColor'],
                    'return' => true,
                ],
            ];

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
                    'module_none'        => 'false',
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

            // TODO:XXXX LinkConsoleInputGroup
        }

        return $inputs;
    }


}
