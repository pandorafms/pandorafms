<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;
/**
 * Model of a odometer item of the Visual Console.
 */
final class Odometer extends Item
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
    protected static $useHtmlOutput = false;


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
        $return['type'] = ODOMETER;
        $return['value'] = $this->extractValue($data);
        $return['status'] = $this->extractStatus($data);
        $return['odometerType'] = $this->extractOdometerType($data);
        $return['thresholds'] = $this->extractThresholds($data);
        $return['titleColor'] = $this->extractTitleColor($data);
        $return['title'] = $this->extractTitle($data);

        $return['titleModule'] = $return['moduleName'];

        if (strlen($return['moduleName']) >= 25) {
            $return['moduleName'] = substr($return['moduleName'], 0, 9).' ... '.substr($return['moduleName'], -9);
        }

        $return['minMaxValue'] = $this->extractMinMaxValue($data);

        return $return;
    }


    /**
     * Extract value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing value or null.
     */
    private static function extractValue(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                ['value']
            ),
            '0'
        );
    }


    /**
     * Extract status value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing status value or null.
     */
    private static function extractStatus(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                ['status']
            ),
            COL_UNKNOWN
        );
    }


    /**
     * Extract odometer type value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing status value or null.
     */
    private static function extractOdometerType(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                ['odomoterType']
            ),
            'percent'
        );
    }


    /**
     * Extract thresholds.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing value or null.
     */
    private static function extractThresholds(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                ['thresholds']
            ),
            ''
        );
    }


    /**
     * Extract label color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the grid color (not empty) or null.
     */
    private static function extractTitleColor(array $data): string
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['titleColor', 'border_color']),
            '#3f3f3f'
        );
    }


    /**
     * Extract title value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The label color or null.
     */
    private static function extractTitle(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['title', 'fill_color']),
            null
        );
    }


    /**
     * Extract min_max_value value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The label color or null.
     */
    private static function extractMinMaxValue(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['min_max_value']),
            ''
        );
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

        $title = static::extractTitle($data);
        if ($title !== '') {
            $return['title'] = $title;
        }

        return $return;
    }


    /**
     * Fetch a vc item data structure from the database using a filter.
     *
     * @param array $filter Filter of the Visual Console Item.
     * @param float $ratio  Ratio visual console in dashboards.
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

        include_once $config['homedir'].'/include/functions_modules.php';

        // Get the linked agent and module Ids.
        $linkedModule = static::extractLinkedModule($data);
        $agentId = static::parseIntOr($linkedModule['agentId'], null);
        $moduleId = static::parseIntOr($linkedModule['moduleId'], null);
        $title = static::extractTitle($data);

        if ($agentId === null) {
            throw new \InvalidArgumentException('missing agent Id');
        }

        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

        if ((int) $data['width'] === 0 && (int) $data['height'] === 0) {
            $data['width'] = 250;
            $data['height'] = 140;

            if ($ratio != 0) {
                $data['width'] = ($data['width'] * $ratio);
                $data['height'] = ($data['height'] * $ratio);
            }
        }

        $data['height'] = ($data['height'] - 20);

        if ((int) $data['width'] < 11) {
            $data['width'] = 11;
        }

        if ((int) $data['height'] < 11) {
            $data['height'] = 11;
        }

        if (isset($data['id_metaconsole']) === true
            && (bool) is_metaconsole() === true
        ) {
            $cnn = \enterprise_hook(
                'metaconsole_get_connection_by_id',
                [ $data['id_metaconsole'] ]
            );

            if (\enterprise_hook('metaconsole_connect', [$cnn]) !== NOERR) {
                throw new \Exception(__('Failed to connect to node'));
            }
        }

        $sql = sprintf(
            'SELECT min_warning, max_warning, min_critical, max_critical FROM %s 
            WHERE id_agente_modulo = %d',
            'tagente_modulo',
            $moduleId
        );
        $thresholds = \db_get_row_sql($sql);

        if (\modules_get_unit($moduleId) === '%') {
            $data['odometerType'] = 'percent';
        } else {
            $data['odometerType'] = 'numeric';
            // 2 days.
            $timeInit = (time() - (2 * 24 * 60 * 60));
            $minMax = \modules_get_min_max_data($moduleId, $timeInit);
            if (!empty($minMax)) {
                $minMax = $minMax[0];

                if ($minMax['min'] > 0) {
                    $minMax['min'] = 0;
                }

                $data['min_max_value'] = json_encode($minMax);

                if ($thresholds['min_warning'] != 0 && $thresholds['min_warning'] > $minMax['max']) {
                    $thresholds['min_warning'] = $minMax['max'];
                }

                if ($thresholds['max_warning'] != 0 && $thresholds['max_warning'] > $minMax['max']) {
                    $thresholds['max_warning'] = $minMax['max'];
                }

                if ($thresholds['min_critical'] != 0 && $thresholds['min_critical'] > $minMax['max']) {
                    $thresholds['min_critical'] = $minMax['max'];
                }

                if ($thresholds['max_critical'] != 0 && $thresholds['max_critical'] > $minMax['max']) {
                    $thresholds['max_critical'] = $minMax['max'];
                }
            }
        }

        $data['thresholds'] = json_encode($thresholds);

        $data['status'] = \modules_get_color_status(modules_get_agentmodule_last_status($moduleId));
        $data['value'] = \modules_get_last_value($moduleId);
        $data['title'] = $title;

        if (isset($data['id_metaconsole']) === true
            && (bool) is_metaconsole() === true
        ) {
            \enterprise_hook('metaconsole_restore_db');
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
                '[Odometer]::getFormInputs parent class return is not an array'
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
                    'module_none'        => true,
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
                    'nothing'        => '--',
                    'nothing_value'  => 0,
                ],
            ];

            $values['title'] = io_safe_input($values['title']);

            // Title.
            $inputs[] = [
                'label'     => __('Title'),
                'arguments' => [
                    'name'   => 'title',
                    'type'   => 'text',
                    'value'  => $values['title'],
                    'return' => true,
                    'size'   => 30,
                ],
            ];

            // Title color.
            $inputs[] = [
                'label'     => __('Title color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'titleColor',
                    'type'    => 'color',
                    'value'   => $values['titleColor'],
                    'return'  => true,
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
        if (isset($values['width']) === false) {
            $values['width'] = 300;
        }

        if (isset($values['height']) === false) {
            $values['height'] = 150;
        }

        return $values;
    }


}
