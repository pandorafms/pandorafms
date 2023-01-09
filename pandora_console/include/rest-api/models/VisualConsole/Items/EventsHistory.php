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
        $return['legendColor'] = $this->extractLegendColor($data);
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
     * Extract legend color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the grid color (not empty) or null.
     */
    private static function extractLegendColor(array $data): string
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['legendColor', 'border_color']),
            '#000000'
        );
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
        $legendColor = static::extractLegendColor($data);

        if ($agentId === null) {
            throw new \InvalidArgumentException('missing agent Id');
        }

        if ((int) $data['width'] === 0 && (int) $data['height'] === 0) {
            $data['width'] = 420;
            $data['height'] = 80;

            if ($ratio != 0) {
                $data['width'] = ($data['width'] * $ratio);
                $data['height'] = ($data['height'] * $ratio);
            }
        }

        // $data['height'] = ($data['height'] - 20);
        if ((int) $data['width'] < 11) {
            $data['width'] = 11;
        }

        if ((int) $data['height'] < 11) {
            $data['height'] = 11;
        }

        if (empty($moduleId) === true) {
            $html = \graph_graphic_agentevents(
                $agentId,
                100,
                (int) $data['height'],
                static::extractMaxTime($data),
                '',
                true,
                false,
                500
            );
        } else {
            // Use the same HTML output as the old VC.
            $html = \graph_graphic_moduleevents(
                $agentId,
                $moduleId,
                100,
                (int) $data['height'],
                static::extractMaxTime($data),
                '',
                true,
                1,
                $data['width']
            );
        }

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
        $link = parent::buildLink($data);
        if ($link !== null) {
            return $link;
        }

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
                '[EventHistory]::getFormInputs parent class return is not an array'
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

            // Type percentile.
            $fields = [
                '86400' => __('24h'),
                '43200' => __('12h'),
                '28800' => __('8h'),
                '7200'  => __('2h'),
                '3600'  => __('1h'),
            ];

            $inputs[] = [
                'label'     => __('Max. Time'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'maxTime',
                    'selected' => $values['maxTime'],
                    'return'   => true,
                    'sort'     => false,
                ],
            ];

            // Legend color.
            $inputs[] = [
                'label'     => __('Legend color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'legendColor',
                    'type'    => 'color',
                    'value'   => $values['legendColor'],
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
            $values['width'] = 500;
        }

        if (isset($values['height']) === false) {
            $values['height'] = 70;
        }

        return $values;
    }


}
