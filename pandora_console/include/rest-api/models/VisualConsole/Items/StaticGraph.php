<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a group item of the Visual Console.
 */
final class StaticGraph extends Item
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
        $return['type'] = STATIC_GRAPH;
        $return['imageSrc'] = $this->extractImageSrc($data);
        $return['showLastValueTooltip'] = $this->extractShowLastValueTooltip($data);
        return $return;
    }


    /**
     * Extract a image src value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     *
     * @throws \InvalidArgumentException When a valid image src can't be found.
     */
    private function extractImageSrc(array $data): string
    {
        $imageSrc = static::notEmptyStringOr(
            static::issetInArray($data, ['imageSrc', 'image']),
            null
        );

        if ($imageSrc === null || \strlen($imageSrc) === 0) {
            throw new \InvalidArgumentException(
                'the image src property is required and should be a non empty string'
            );
        }

        return $imageSrc;
    }


    /**
     * Extract the value of showLastValueTooltip and
     * return 'default', 'enabled' or 'disabled'.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string
     */
    private function extractShowLastValueTooltip(array $data): string
    {
        $showLastValueTooltip = static::notEmptyStringOr(
            static::issetInArray($data, ['showLastValueTooltip']),
            null
        );

        if ($showLastValueTooltip === null) {
            $showLastValueTooltip = static::parseIntOr(
                static::issetInArray($data, ['show_last_value']),
                null
            );
            switch ($showLastValueTooltip) {
                case 1:
                return 'enabled';

                case 2:
                return 'disabled';

                default:
                return 'default';
            }
        } else {
            switch ($showLastValueTooltip) {
                case 'enabled':
                return 'enabled';

                case 'disabled':
                return 'disabled';

                default:
                return 'default';
            }
        }
    }


}
