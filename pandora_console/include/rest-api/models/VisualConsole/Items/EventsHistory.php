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
        $return['maxTime'] = $this->extractMaxTime($data);
        $return['encodedHtml'] = $this->extractEncodedHtml($data);
        return $return;
    }


    /**
     * Extract a graph period value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed The time in seconds of the graph period or null.
     */
    private function extractMaxTime(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['maxTime', 'period']),
            null
        );
    }


    /**
     * Extract a encoded HTML representation of the item.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string The HTML representation in base64 encoding.
     * @throws \InvalidArgumentException When an agent Id cannot be found.
     */
    private function extractEncodedHtml(array $data): string
    {
        if (isset($data['encodedHtml']) === true) {
            return $data['encodedHtml'];
        } else if (isset($data['html']) === true) {
            return \base64_encode($data['html']);
        }

        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_graph.php';

        // Get the linked agent and module Ids.
        $linkedModule = $this->extractLinkedModule($data);
        $agentId = static::parseIntOr($linkedModule['agentId'], null);
        $moduleId = static::parseIntOr($linkedModule['moduleId'], null);

        if ($agentId === null) {
            throw new \InvalidArgumentException('missing agent Id');
        }

        // Use the same HTML output as the old VC.
        $html = '<div style="width:500px;">';
        $html .= \graph_graphic_moduleevents(
            $agentId,
            $moduleId,
            (int) $data['width'],
            (int) $data['height'],
            $this->extractMaxTime($data),
            '',
            true
        );
        $html .= '</div>';

        return \base64_encode($html);
    }


}
