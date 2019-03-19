<?php

declare(strict_types=1);

namespace Models\VisualConsole;
use Models\Model;

final class Container extends Model
{


    public static function fromArray(array $data): self
    {
        return new self($data);
    }


    protected function validateData(array $data): void
    {
        if (isset($data['id']) === false
            || \is_numeric($data['id']) === false
        ) {
            throw new \InvalidArgumentException(
                'the Id property is required and should be integer'
            );
        }

        if (isset($data['name']) === false
            || \is_string($data['name']) === false
            || empty($data['name']) === true
        ) {
            throw new \InvalidArgumentException(
                'the name property is required and should be string'
            );
        }

        if (isset($data['width']) === false
            || \is_numeric($data['width']) === false
            || $data['width'] <= 0
        ) {
            throw new \InvalidArgumentException(
                'the width property is required and should greater than 0'
            );
        }

        if (isset($data['height']) === false
            || \is_numeric($data['height']) === false
            || $data['height'] <= 0
        ) {
            throw new \InvalidArgumentException(
                'the height property is required and should greater than 0'
            );
        }

        $this->extractGroupId($data);
    }


    protected function decode(array $data): array
    {
        return [
            'id'              => (int) $data['id'],
            'name'            => $data['name'],
            'groupId'         => $this->extractGroupId($data),
            'backgroundURL'   => $this->extractBackgroundUrl($data),
            'backgroundColor' => $this->extractBackgroundColor($data),
            'isFavorite'      => $this->extractFavorite($data),
            'width'           => (int) $data['width'],
            'height'          => (int) $data['height'],
        ];
    }


    private function extractGroupId(array $data): int
    {
        if (isset($data['id_group']) === true
            && \is_numeric($data['id_group']) === true
            && $data['id_group'] >= 0
        ) {
            return $data['id_group'];
        } else if (isset($data['groupId']) === true
            && \is_numeric($data['groupId']) === true
            && $data['groupId'] >= 0
        ) {
            return $data['groupId'];
        }

        throw new \InvalidArgumentException(
            'the group Id property is required and should be integer'
        );
    }


    private function extractBackgroundUrl(array $data)
    {
        $background = Model::notEmptyStringOr(
            Model::issetInArray($data, ['background', 'backgroundURL']),
            null
        );
        return $background;
    }


    private function extractBackgroundColor(array $data)
    {
        $backgroundColor = Model::notEmptyStringOr(
            Model::issetInArray($data, ['backgroundColor', 'background_color']),
            null
        );
        return $backgroundColor;
    }


    private function extractFavorite(array $data): bool
    {
        $favorite = Model::parseBool(
            Model::issetInArray($data, ['is_favourite', 'isFavorite']),
            null
        );
        return $favorite;
    }


}
