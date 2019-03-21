<?php

declare(strict_types=1);

namespace Models\VisualConsole\items;
use Models\VisualConsole\Item;
use Models\Model;

final class Box extends Item
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
        $return['type'] = BOX_ITEM;
        $return['parentId'] = null;
        $return['aclGroupId'] = null;
        $return['borderWidth'] = $this->extractBorderWidth($data);
        $return['borderColor'] = $this->extractBorderColor($data);
        $return['fillColor'] = $this->extractFillColor($data);
        return $return;
    }


    /**
     * Extract the value of borderWidth and
     * return a integer.
     *
     * @param mixed $data
     *
     * @return integer
     */
    private function extractBorderWidth(array $data): int
    {
        $borderWidth = Model::parseIntOr(
            Model::issetInArray($data, ['borderWidth', 'border_width']),
            0
        );
        if ($borderWidth >= 0) {
            return $borderWidth;
        } else {
            return 0;
        }
    }


    /**
     * Extract the value of borderColor and
     * return to not empty string or null.
     *
     * @param mixed $data
     *
     * @return void
     */
    private function extractBorderColor(array $data)
    {
        $borderColor = Model::notEmptyStringOr(
            Model::issetInArray($data, ['borderColor', 'border_color']),
            null
        );
        return $borderColor;
    }


    /**
     * Extract the value of fillColor and
     * return to not empty string or null.
     *
     * @param mixed $data
     *
     * @return void
     */
    private function extractFillColor(array $data)
    {
        $borderColor = Model::notEmptyStringOr(
            Model::issetInArray($data, ['fillColor', 'fill_color']),
            null
        );
        return $borderColor;
    }


}
