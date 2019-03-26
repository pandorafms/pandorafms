<?php

declare(strict_types=1);

namespace Models\VisualConsole\items;
use Models\VisualConsole\Item;
use Models\Model;

final class Group extends Item
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
        $return['type'] = GROUP_ITEM;
        $return['imageSrc'] = $this->extractImageSrc($data);
        $return['groupId'] = $this->extractGroupId($data);
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


    private function extractGroupId(array $data): int
    {
        $groupId = Model::parseIntOr(
            Model::issetInArray($data, ['groupId', 'id_group']),
            -1
        );
        if ($groupId < 0) {
            throw new \InvalidArgumentException(
                'the group Id property is required and should be integer'
            );
        } else {
            return $groupId;
        }
    }


}
