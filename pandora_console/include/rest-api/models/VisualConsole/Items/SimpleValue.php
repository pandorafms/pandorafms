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
     * @overrides Item::validateData.
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
        $return['type'] = SIMPLE_VALUE;
        $return['processValue'] = $this->extractProcessValue($data);
        if ($return['processValue'] !== 'none') {
            $return['period'] = $this->extractPeriod($data);
        }

        $return['valueType'] = $this->extractValueType($data);
        $return['value'] = $data['value'];
        return $return;
    }


    /**
     * Extract the value of processValue and
     * return 'avg', 'max', 'min' or 'none'.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string
     */
    private function extractProcessValue(array $data): string
    {
        $processValue = static::notEmptyStringOr(
            static::issetInArray($data, ['processValue']),
            null
        );

        if ($processValue === null) {
            $processValue = $data['type'];
        }

        switch ($processValue) {
            case SIMPLE_VALUE_AVG:
            case 'avg':
            return 'avg';

            case SIMPLE_VALUE_MAX:
            case 'max':
            return 'max';

            case SIMPLE_VALUE_MIN:
            case 'min':
            return 'min';

            default:
            return 'none';
        }
    }


    /**
     * Extract the value of period and
     * return a integer.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer
     */
    private function extractPeriod(array $data): int
    {
        $period = static::parseIntOr(
            static::issetInArray($data, ['period']),
            0
        );
        if ($period >= 0) {
            return $period;
        } else {
            return 0;
        }
    }


    /**
     * Extract the value of valueType and
     * return 'image' or 'string'.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string
     */
    private function extractValueType(array $data): string
    {
        $valueType = static::notEmptyStringOr(
            static::issetInArray($data, ['valueType']),
            null
        );

        switch ($valueType) {
            case 'image':
            return 'image';

            default:
            return 'string';
        }
    }


}
