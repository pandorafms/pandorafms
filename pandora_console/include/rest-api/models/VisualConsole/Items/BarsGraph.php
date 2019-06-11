<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a bars graph item of the Visual Console.
 */
final class BarsGraph extends Item
{

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked module.
     *
     * @var boolean
     */
    protected static $useLinkedModule = true;

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
        $return['type'] = BARS_GRAPH;
        $return['gridColor'] = $this->extractGridColor($data);
        $return['backgroundColor'] = $this->extractBackgroundColor($data);
        $return['typeGraph'] = $this->extractTypeGraph($data);
        return $return;
    }


    /**
     * Extract a grid color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the grid color (not empty) or null.
     */
    private function extractGridColor(array $data): string
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['gridColor', 'border_color']),
            '#000000'
        );
    }


    /**
     * Extract a background color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'white', 'black' or 'transparent'.
     * 'white' by default.
     */
    private function extractBackgroundColor(array $data): string
    {
        $backgroundColor = static::notEmptyStringOr(
            static::issetInArray($data, ['backgroundColor', 'image']),
            null
        );

        switch ($backgroundColor) {
            case 'black':
            case 'transparent':
            return $backgroundColor;

            default:
            return 'white';
        }
    }


    /**
     * Extract a type graph value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'vertical' or 'horizontal'. 'vertical' by default.
     */
    private function extractTypeGraph(array $data): string
    {
        $typeGraph = static::notEmptyStringOr(
            static::issetInArray($data, ['typeGraph', 'type_graph']),
            null
        );

        switch ($typeGraph) {
            case 'horizontal':
            return 'horizontal';

            default:
            return 'vertical';
        }
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

        // Load config.
        global $config;

        // Load side libraries.
        include_once $config['homedir'].'/include/functions_ui.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';
        include_once $config['homedir'].'/include/graphs/fgraph.php';

        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Extract needed properties.
        $gridColor = static::extractGridColor($data);
        $backGroundColor = static::extractBackgroundColor($data);
        $typeGraph = static::extractTypeGraph($data);

        // Get the linked agent and module Ids.
        $linkedModule = static::extractLinkedModule($data);
        $agentId = $linkedModule['agentId'];
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

        if ($agentId === null) {
            throw new \InvalidArgumentException('missing agent Id');
        }

        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

        // Add colors that will use the graphics.
        $color = [];

        $color[0] = [
            'border' => '#000000',
            'color'  => $config['graph_color1'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[1] = [
            'border' => '#000000',
            'color'  => $config['graph_color2'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[2] = [
            'border' => '#000000',
            'color'  => $config['graph_color3'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[3] = [
            'border' => '#000000',
            'color'  => $config['graph_color4'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[4] = [
            'border' => '#000000',
            'color'  => $config['graph_color5'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[5] = [
            'border' => '#000000',
            'color'  => $config['graph_color6'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[6] = [
            'border' => '#000000',
            'color'  => $config['graph_color7'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[7] = [
            'border' => '#000000',
            'color'  => $config['graph_color8'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[8] = [
            'border' => '#000000',
            'color'  => $config['graph_color9'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[9] = [
            'border' => '#000000',
            'color'  => $config['graph_color10'],
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[11] = [
            'border' => '#000000',
            'color'  => COL_GRAPH9,
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[12] = [
            'border' => '#000000',
            'color'  => COL_GRAPH10,
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[13] = [
            'border' => '#000000',
            'color'  => COL_GRAPH11,
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[14] = [
            'border' => '#000000',
            'color'  => COL_GRAPH12,
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];
        $color[15] = [
            'border' => '#000000',
            'color'  => COL_GRAPH13,
            'alpha'  => CHART_DEFAULT_ALPHA,
        ];

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

        $moduleData = \get_bars_module_data($moduleId);

        $waterMark = [
            'file' => $config['homedir'].'/images/logo_vertical_water.png',
            'url'  => \ui_get_full_url(
                'images/logo_vertical_water.png',
                false,
                false,
                false
            ),
        ];

        if ((int) $data['width'] === 0 || (int) $data['height'] === 0) {
            $width = 400;
            $height = 400;
        } else {
            $width = (int) $data['width'];
            $height = (int) $data['height'];
        }

        if ($typeGraph === 'horizontal') {
            $graph = \hbar_graph(
                $moduleData,
                $width,
                $height,
                $color,
                [],
                [],
                \ui_get_full_url(
                    'images/image_problem_area.png',
                    false,
                    false,
                    false
                ),
                '',
                '',
                $waterMark,
                $config['fontpath'],
                6,
                '',
                0,
                $config['homeurl'],
                $backGroundColor,
                $gridColor
            );
        } else {
            $graph = \vbar_graph(
                $moduleData,
                $width,
                $height,
                $color,
                [],
                [],
                \ui_get_full_url(
                    'images/image_problem_area.png',
                    false,
                    false,
                    false
                ),
                '',
                '',
                $waterMark,
                $config['fontpath'],
                6,
                '',
                0,
                $config['homeurl'],
                $backGroundColor,
                true,
                false,
                $gridColor
            );
        }

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        $data['html'] = $graph;

        return $data;
    }


}
