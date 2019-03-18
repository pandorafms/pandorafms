<?php

declare(strict_types=1);

namespace Models\VisualConsole;
use Models\Model;

final class Container extends Model
{


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
            'isFavorite'      => Model::parseBool($data['is_favourite'])
                || Model::parseBool($data['isFavorite']),
            'width'           => (int) $data['width'],
            'height'          => (int) $data['height'],
        ];
    }


    private function extractGroupId(array $data): number
    {
        if (isset($data['id_group']) === true
            && \is_numeric($data['id_group']) === true
        ) {
            return $data['id_group'];
        } else if (isset($data['groupId']) === true
            && \is_numeric($data['groupId']) === true
        ) {
            return $data['groupId'];
        }

        throw new \InvalidArgumentException(
            'the group Id property is required and should be integer'
        );
    }


    private function extractBackgroundUrl(array $data): mixed
    {
        $backgroundUrl = Model::notEmptyStringOr($data['background'], null);
        if ($backgroundUrl !== null) {
            return $backgroundUrl;
        }

        $backgroundUrl = Model::notEmptyStringOr($data['backgroundURL'], null);
        if ($backgroundUrl !== null) {
            return $backgroundUrl;
        }

        return null;
    }


    private function extractBackgroundColor(array $data): mixed
    {
        $backgroundColor = Model::notEmptyStringOr($data['background_color'], null);
        if ($backgroundColor !== null) {
            return $backgroundColor;
        }

        $backgroundColor = Model::notEmptyStringOr($data['backgroundColor'], null);
        if ($backgroundColor !== null) {
            return $backgroundColor;
        }

        return null;
    }


}
