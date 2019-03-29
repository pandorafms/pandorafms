<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;
use Models\Model;

/**
 * Model of a events history item of the Visual Console.
 */
final class EventsHistory extends Item
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
        $return['data'] = $this->extractData($data);
        return $return;
    }


    /**
     * Extract the value of maxTime and
     * return a integer or null.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed
     */
    private function extractMaxTime(array $data)
    {
        $maxTime = Model::parseIntOr(
            Model::issetInArray($data, ['maxTime', 'period']),
            null
        );
        return $maxTime;
    }


    /**
     * Extract the value of data and
     * return an array.
     *
     * @param array $data Unknown input data structure.
     *
     * @return array
     */
    private function extractData(array $data): array
    {
        $array = [];
        if (isset($data['data']) && \is_array($data['data'])) {
            $array = $data['data'];
        }

        return $array;
    }


}
