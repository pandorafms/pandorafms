<?php

namespace Artica\PHPChartJS\Renderer;

use DOMDocument;

/**
 * Class Renderer
 *
 * @package Artica\PHPChartJS
 */
class Html extends Renderer
{
    /**
     * Renders the necessary HTML for the chart to function in the frontend.
     *
     * @param int|null $flags
     *
     * @return string
     */
    public function render($flags = null)
    {
        $dom = new DOMDocument();

        // Render canvas HTML element
        $canvas = $dom->createElement('canvas');
        $canvas->setAttribute('id', $this->chart->getId());

        // Add title, height and width if applicable
        if ($this->chart->getTitle()) {
            $canvas->setAttribute('title', $this->chart->getTitle());
        }
        if ($this->chart->getHeight()) {
            $canvas->setAttribute('height', $this->chart->getHeight());
        }
        if ($this->chart->getWidth()) {
            $canvas->setAttribute('width', $this->chart->getWidth());
        }

        $dom->appendChild($canvas);

        // Render JavaScript
        $scriptRenderer = new JavaScript($this->chart);
        $script         = $dom->createElement('script', $scriptRenderer->render($flags));
        $dom->appendChild($script);

        return $dom->saveHTML();
    }
}
