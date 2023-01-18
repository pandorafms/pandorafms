<?php

namespace Artica\PHPChartJS\Renderer;

use Laminas\Json\Json as JsonHelper;

/**
 * Class Json
 *
 * @package Artica\PHPChartJS\Renderer
 */
class Json extends Renderer
{


    /**
     * Render the necessary JSON for the chart to function in the frontend.
     *
     * @param integer|false $flags
     *
     * @return string
     */
    public function render($flags=null)
    {
        $config = [
            'type' => constant(get_class($this->chart).'::TYPE'),
            'data' => [],
        ];

        $labels = $this->chart->labels()->jsonSerialize();
        if ($labels) {
            $config['data']['labels'] = $labels;
        }

        $dataSets = $this->chart->dataSets()->jsonSerialize();
        if ($dataSets) {
            $config['data']['datasets'] = $dataSets;
        }

        $options = $this->chart->options()->jsonSerialize();
        if (! empty($options)) {
            $config['options'] = $options;
        }

        $defaults = $this->chart->defaults()->jsonSerialize();
        if (! empty($defaults)) {
            $config['defaults'] = $defaults;
        }

        $plugins = $this->chart->plugins()->jsonSerialize();
        if (! empty($plugins)) {
            $config['plugins'] = $plugins;
        }

        $output = JsonHelper::encode($config, false, ['enableJsonExprFinder' => true]);
        if (($flags & Renderer::RENDER_PRETTY)) {
            $output = JsonHelper::prettyPrint($output);
        }

        return $output;
    }


}
