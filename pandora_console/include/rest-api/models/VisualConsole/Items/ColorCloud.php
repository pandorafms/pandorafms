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
        $colorCloudData['colorRanges'] = '';

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

            $color_decode = \json_decode($color);
            if (empty($color) === true || empty($color_decode->default_color) === true) {
                throw new \InvalidArgumentException(
                    'the color property is required and should be string'
                );
            } else {
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
                if ((!isset($data['colorRanges'][$key]['fromValue']) || !\is_float($data['colorRanges'][$key]['fromValue']))
                    || (!isset($data['colorRanges'][$key]['toValue']) || !\is_float($data['colorRanges'][$key]['toValue']))
                    || (!isset($data['colorRanges'][$key]['color']) | !\is_string($data['colorRanges'][$key]['color']) || strlen($data['colorRanges'][$key]['color']) == 0)
                ) {
                        throw new \InvalidArgumentException(
                            'the color property is required and should be string'
                        );
                }
            }

            return $data['colorRanges'];
        } else if (isset($data['label']) === true) {
            $colorRanges_decode = \json_decode($data['label']);
            return $colorRanges_decode->color_ranges;
        } else {
            return [];
        }
    }


}
