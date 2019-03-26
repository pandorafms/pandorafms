<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

final class Icon extends Item
{


    /**
     * Validate the input data.
     *
     * @param mixed $data
     *
     * @return void
     *
     * @override
     */
    protected function validateData(array $data): void
    {
        parent::validateData($data);
    }


    /**
     * Returns a valid data structure.
     *
     * @param mixed $data
     *
     * @return array
     *
     * @override
     */
    protected function decode(array $data): array
    {
        $return = parent::decode($data);
        $return['type'] = ICON;
        $return['imageSrc'] = $this->extractImageSrc($data);
        return $return;
    }


    /**
     * extractBackgroundUrl
     *
     * @param mixed $data
     *
     * @return void
     */
    private function extractImageSrc(array $data)
    {
        $imageSrc = Model::notEmptyStringOr(
            Model::issetInArray($data, ['imageSrc', 'image']),
            null
        );
        if ($imageSrc === null) {
            throw new \InvalidArgumentException(
                'the imageSrc property is required and should be string'
            );
        } else {
            return $imageSrc;
        }
    }


}
