<?php

declare(strict_types=1);

namespace Models\VisualConsole;
use Models\Model;

class Item extends Model
{


    /**
     * Instance the class with the input data.
     *
     * @param mixed $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }


    /**
     * Validate the input data.
     *
     * @param mixed $data
     *
     * @return void
     */
    protected function validateData(array $data): void
    {
        if (isset($data['id']) === false
            || \is_numeric($data['id']) === false
        ) {
            throw new \InvalidArgumentException(
                'the Id property is required and should be integer'
            );
        }

        if (isset($data['type']) === false
            || \is_numeric($data['type']) === false
        ) {
            throw new \InvalidArgumentException(
                'the Type property is required and should be integer'
            );
        }

        if (isset($data['width']) === false
            || \is_numeric($data['width']) === false
            || $data['width'] < 0
        ) {
            throw new \InvalidArgumentException(
                'the width property is required and should greater than 0'
            );
        }

        if (isset($data['height']) === false
            || \is_numeric($data['height']) === false
            || $data['height'] < 0
        ) {
            throw new \InvalidArgumentException(
                'the height property is required and should greater than 0'
            );
        }
    }


    /**
     * Returns a valid data structure.
     *
     * @param mixed $data
     *
     * @return array
     */
    protected function decode(array $data): array
    {
        return [
            'id'            => (int) $data['id'],
            'type'          => (int) $data['type'],
            'label'         => $this->extractLabel($data),
            'labelPosition' => $this->extractLabelPosition($data),
            'isLinkEnabled' => $this->extractIsLinkEnabled($data),
            'isOnTop'       => $this->extractIsOnTop($data),
            'parentId'      => $this->extractParentId($data),
            'aclGroupId'    => $this->extractAclGroupId($data),
            'width'         => (int) $data['width'],
            'height'        => (int) $data['height'],
            'x'             => $this->extractX($data),
            'y'             => $this->extractY($data),
        ];
    }


    /**
     * extractX
     *
     * @param mixed $data
     *
     * @return integer
     */
    private function extractX(array $data): int
    {
        if (isset($data['pos_x']) === true
            && \is_numeric($data['pos_x']) === true
        ) {
            return $data['pos_x'];
        } else if (isset($data['x']) === true
            && \is_numeric($data['x']) === true
        ) {
            return $data['x'];
        }

        return 0;
    }


    /**
     * extractY
     *
     * @param mixed $data
     *
     * @return integer
     */
    private function extractY(array $data): int
    {
        if (isset($data['pos_y']) === true
            && \is_numeric($data['pos_y']) === true
        ) {
            return $data['pos_y'];
        } else if (isset($data['y']) === true
            && \is_numeric($data['y']) === true
        ) {
            return $data['y'];
        }

        return 0;
    }


    /**
     * Extract the value of id_group and
     * return a integer or null.
     *
     * @param mixed $data
     *
     * @return void
     */
    private function extractAclGroupId(array $data)
    {
        $aclGroupId = Model::parseIntOr(
            Model::issetInArray($data, ['aclGroupId', 'id_group']),
            null
        );
        if ($aclGroupId >= 0) {
            return $aclGroupId;
        } else {
            return null;
        }
    }


    /**
     * Extract the value of parentId and
     * return a integer or null.
     *
     * @param mixed $data
     *
     * @return void
     */
    private function extractParentId(array $data)
    {
        $parentId = Model::parseIntOr(
            Model::issetInArray($data, ['parentId', 'parent_item']),
            null
        );
        if ($parentId >= 0) {
            return $parentId;
        } else {
            return null;
        }
    }


    /**
     * Extract the value of isOnTop and
     * return a Boolean.
     *
     * @param mixed $data
     *
     * @return boolean
     */
    private function extractIsOnTop(array $data): bool
    {
        $isOnTop = Model::parseBool(
            Model::issetInArray($data, ['isOnTop', 'show_on_top']),
            null
        );
        return $isOnTop;
    }


    /**
     * Extract the value of isLinkEnabled and
     * return a Boolean.
     *
     * @param mixed $data
     *
     * @return boolean
     */
    private function extractIsLinkEnabled(array $data): bool
    {
        $isLinkEnabled = Model::parseBool(
            Model::issetInArray($data, ['isLinkEnabled', 'enable_link']),
            null
        );
        return $isLinkEnabled;
    }


    /**
     * Extract the value of label and
     * return to not empty string or null.
     *
     * @param mixed $data
     *
     * @return void
     */
    private function extractLabel(array $data)
    {
        $label = Model::notEmptyStringOr(
            Model::issetInArray($data, ['label']),
            null
        );
        return $label;
    }


    /**
     * Extract the value of labelPosition and
     * return to not empty string or null.
     *
     * @param mixed $data
     *
     * @return string
     */
    private function extractLabelPosition(array $data): string
    {
        $labelPosition = Model::notEmptyStringOr(
            Model::issetInArray($data, ['labelPosition', 'label_position']),
            null
        );

        switch ($labelPosition) {
            case 'up':
            case 'right':
            case 'left':
            return $labelPosition;

            default:
            return 'down';
        }
    }


}
