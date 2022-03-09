<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;
/**
 * Model of a basic chart item of the Visual Console.
 */
final class BasicChart extends Item
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
        $return['type'] = BASIC_CHART;
        $return['period'] = $this->extractPeriod($data);
        $return['value'] = $this->extractValue($data);
        $return['status'] = $this->extractStatus($data);
        $return['moduleNameColor'] = $this->extractModuleNameColor($data);

        return $return;
    }


    /**
     * Extract a graph period value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The time in seconds of the graph period or null.
     */
    private static function extractPeriod(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['period']),
            null
        );
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
     * Extract label color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the grid color (not empty) or null.
     */
    private static function extractModuleNameColor(array $data): string
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['moduleNameColor', 'border_color']),
            '#3f3f3f'
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
        include_once $config['homedir'].'/include/functions_modules.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        $imageOnly = false;
        $period = static::extractPeriod($data);
        $linkedModule = static::extractLinkedModule($data);
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

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

        /*
         * About the 30 substraction to the graph height:
         * The function which generates the graph doesn't respect the
         * required height. It uses it for the canvas (the graph itself and
         * their axes), but then it adds the legend. One item of the legend
         * (one dataset) is about 30px, so we need to substract that height
         * from the canvas to try to fit the element's height.
         *
         * PD: The custom graphs can have more datasets, but we only substract
         * the height of one of it to replicate the legacy functionality.
         */

        $width = (int) $data['width'];
        $height = ((int) $data['height'] * 0.6);

        // Module graph.
        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

        $now = new \DateTime();
        $date_array = [];
        $date_array['period']     = $period;
        $date_array['final_date'] = $now->getTimestamp();
        $date_array['start_date'] = ($now->getTimestamp() - $period);

        $params = [
            'agent_module_id'    => $moduleId,
            'period'             => $period,
            'show_events'        => false,
            'width'              => $width,
            'height'             => $height,
            'title'              => \modules_get_agentmodule_name(
                $moduleId
            ),
            'unit'               => \modules_get_unit($moduleId),
            'only_image'         => $imageOnly,
            'menu'               => false,
            'vconsole'           => true,
            'return_img_base_64' => true,
            'show_legend'        => false,
            'show_title'         => false,
            'dashboard'          => true,
            'backgroundColor'    => 'transparent',
            'server_id'          => $metaconsoleId,
            'basic_chart'        => true,
        ];

        if ($imageOnly !== false) {
            $imgbase64 = 'data:image/png;base64,';
        }

        $imgbase64 .= \grafico_modulo_sparse($params);

        $data['html'] = $imgbase64;

        $data['value'] = \modules_get_last_value($moduleId);
        $data['status'] = \modules_get_color_status(modules_get_agentmodule_last_status($moduleId));

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
        // Default values.
        $values = static::getDefaultGeneralValues($values);

        // Retrieve global - common inputs.
        $inputs = Item::getFormInputs($values);

        if (is_array($inputs) !== true) {
            throw new \Exception(
                '[BasicChart]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // Default values.
            if (isset($values['period']) === false) {
                $values['period'] = 3600;
            }

            // Autocomplete agents.
            $inputs[] = [
                'id'        => 'BCautoCompleteAgent',
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
                'id'        => 'BCautoCompleteModule',
                'label'     => __('Module'),
                'arguments' => [
                    'type'           => 'autocomplete_module',
                    'name'           => 'moduleId',
                    'selected'       => $values['moduleId'],
                    'return'         => true,
                    'sort'           => false,
                    'agent_id'       => $values['agentId'],
                    'metaconsole_id' => $values['metaconsoleId'],
                ],
            ];

            // Period.
            $inputs[] = [
                'label'     => __('Period'),
                'arguments' => [
                    'name'          => 'period',
                    'type'          => 'interval',
                    'value'         => $values['period'],
                    'nothing'       => __('None'),
                    'nothing_value' => 0,
                ],
            ];

            // module name color.
            $inputs[] = [
                'label'     => __('Module name color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'moduleNameColor',
                    'type'    => 'color',
                    'value'   => $values['moduleNameColor'],
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


}
