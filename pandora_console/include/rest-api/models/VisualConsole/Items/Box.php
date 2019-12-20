<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a Box item of the Visual Console.
 */
final class Box extends Item
{


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
        $boxData = parent::decode($data);
        $boxData['type'] = BOX_ITEM;
        $boxData['parentId'] = null;
        $boxData['aclGroupId'] = null;
        $boxData['borderWidth'] = $this->extractBorderWidth($data);
        $boxData['borderColor'] = $this->extractBorderColor($data);
        $boxData['fillColor'] = $this->extractFillColor($data);
        return $boxData;
    }


    /**
     * Extract a border width value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid border width. 0 by default.
     */
    private function extractBorderWidth(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['borderWidth', 'border_width']),
            0
        );
    }


    /**
     * Extract a border color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the border color (not empty) or null.
     */
    private function extractBorderColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['borderColor', 'border_color']),
            null
        );
    }


    /**
     * Extract a fill color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the fill color (not empty) or null.
     */
    private function extractFillColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['fillColor', 'fill_color']),
            null
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
        // Retrieve global - common inputs.
        $inputs = Item::getFormInputs($values);

        if (is_array($inputs) !== true) {
            throw new Exception(
                '[Box]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // Border color.
            $inputs[] = [
                'label'     => __('Border color'),
                'arguments' => [
                    'name'   => 'borderColor',
                    'type'   => 'color',
                    'value'  => $values['borderColor'],
                    'return' => true,
                ],
            ];

            // Border Width.
            $inputs[] = [
                'label'     => __('Border Width'),
                'arguments' => [
                    'name'   => 'borderWidth',
                    'type'   => 'number',
                    'value'  => $values['borderWidth'],
                    'return' => true,
                ],
            ];

            // Fill color.
            $inputs[] = [
                'label'     => __('Fill color'),
                'arguments' => [
                    'name'   => 'fillColor',
                    'type'   => 'color',
                    'value'  => $values['fillColor'],
                    'return' => true,
                ],
            ];
        }

        return $inputs;
    }


}
