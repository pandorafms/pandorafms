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
     * Extract a Process value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'none' or 'avg' or 'max' or 'min'.
     * 'none' by default.
     */
    private static function encodeProcessValue(array $data)
    {
        $return = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                ['processValue']
            ),
            null
        );

        if ($return !== null) {
            switch ($return) {
                case 'avg':
                    $return = SIMPLE_VALUE_AVG;
                break;

                case 'max':
                    $return = SIMPLE_VALUE_MAX;
                break;

                case 'min':
                    $return = SIMPLE_VALUE_MIN;
                break;

                default:
                case 'none':
                    $return = SIMPLE_VALUE;
                break;
            }
        }

        return $return;
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
        $process_value = static::encodeProcessValue($data);
        if ($process_value !== null) {
            $return['type'] = $process_value;
        } else if (isset($data['processValue']) === true) {
            $return['type'] = $data['processValue'];
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
        $return = parent::decode($data);
        $return['type'] = SIMPLE_VALUE;
        $return['processValue'] = static::extractProcessValue($data);
        $return['valueType'] = static::extractValueType($data);
        $return['value'] = \io_safe_output($data['value']);

        if ($return['processValue'] !== 'none') {
            $return['period'] = static::extractPeriod($data);
        }

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
        if (isset($data['processValue']) === true) {
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
        include_once $config['homedir'].'/include/functions_visual_map.php';
        if (\is_metaconsole()) {
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
        $value = \io_safe_output(
            \visual_map_get_simple_value(
                $data['type'],
                $moduleId,
                static::extractPeriod($data)
            )
        );

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        // Some modules are image based. Extract the base64 image if needed.
        $matches = [];
        if (preg_match('/src=\"(data:image.*)"/', $value, $matches) === 1) {
            $data['valueType'] = 'image';
            $data['value'] = $matches[1];
        } else {
            $data['valueType'] = 'string';
            $data['value'] = $value;
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
                '[SimpleValue]::getFormInputs parent class return is not an array'
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

            // Process.
            $fields = [
                'none' => __('None'),
                'avg'  => __('Avg Value'),
                'max'  => __('Max Value'),
                'min'  => __('Min Value'),
            ];

            $inputs[] = [
                'label'     => __('Process'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'processValue',
                    'selected' => $values['processValue'],
                    'return'   => true,
                    'sort'     => false,
                    'script'   => 'simpleValuePeriod()',
                ],
            ];

            $hiddenPeriod = true;
            if (isset($values['processValue']) === true
                && $values['processValue'] !== 'none'
            ) {
                $hiddenPeriod = false;
            }

            // Period.
            $inputs[] = [
                'id'        => 'SVPeriod',
                'hidden'    => $hiddenPeriod,
                'label'     => __('Period'),
                'arguments' => [
                    'name'          => 'period',
                    'type'          => 'interval',
                    'value'         => $values['period'],
                    'nothing'       => __('None'),
                    'nothing_value' => 0,
                ],
            ];

            // Inputs LinkedVisualConsole.
            $inputsLinkedVisualConsole = self::inputsLinkedVisualConsole(
                $values
            );
            foreach ($inputsLinkedVisualConsole as $key => $value) {
                $inputs[] = $value;
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
        if (isset($values['label']) === false) {
            $values['label'] = '(_value_)';
        }

        return $values;
    }


}
