<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a color cloud item of the Visual Console.
 */
final class ColorCloud extends Item
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
        $colorCloudData = parent::decode($data);
        $colorCloudData['type'] = COLOR_CLOUD;
        $colorCloudData['color'] = $this->extractColor($data);
        $colorCloudData['colorRanges'] = $this->extractColorRanges($data);
        $colorCloudData['label'] = null;
        return $colorCloudData;
    }


    /**
     * Extract a color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string
     */
    private function extractColor(array $data): string
    {
        $color = static::notEmptyStringOr(
            static::issetInArray($data, ['color']),
            null
        );

        if (empty($color) === true) {
            $color = static::notEmptyStringOr(
                static::issetInArray($data, ['label']),
                null
            );

            if (empty($color) === true) {
                throw new \InvalidArgumentException(
                    'the color property is required and should be string'
                );
            } else {
                $color_decode = \json_decode($color);
                if (empty($color_decode->default_color) === true) {
                    throw new \InvalidArgumentException(
                        'the color property is required and should be string'
                    );
                }

                return $color_decode->default_color;
            }
        } else {
            return $color;
        }
    }


    /**
     * Extract a color ranges value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string
     */
    private function extractColorRanges(array $data): array
    {
        if (isset($data['colorRanges']) && \is_array($data['colorRanges'])) {
            foreach ($data['colorRanges'] as $key => $value) {
                if ((!isset($value['fromValue']) || !\is_numeric($value['fromValue']))
                    || (!isset($value['toValue']) || !\is_numeric($value['toValue']))
                    || (!isset($value['color']) || !\is_string($value['color'])
                    || \strlen($value['color']) == 0)
                ) {
                    throw new \InvalidArgumentException(
                        'the fromValue, toValue and color properties is required'
                    );
                }
            }

            return $data['colorRanges'];
        } else if (isset($data['label']) === true) {
            $colorRanges_decode = \json_decode($data['label']);
            $array_out = [];
            if (!empty($colorRanges_decode->color_ranges)) {
                foreach ($colorRanges_decode->color_ranges as $key => $value) {
                    $array_aux = [];
                    $array_aux['fromValue'] = $value->from_value;
                    $array_aux['toValue'] = $value->to_value;
                    $array_aux['color'] = $value->color;
                    array_push($array_out, $array_aux);
                }
            }

            return $array_out;
        } else {
            return [];
        }
    }


}
