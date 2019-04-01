<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a group item of the Visual Console.
 */
final class Icon extends Item
{

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
        $return['type'] = ICON;
        $return['imageSrc'] = $this->extractImageSrc($data);
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
    private function extractImageSrc(array $data)
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


}
