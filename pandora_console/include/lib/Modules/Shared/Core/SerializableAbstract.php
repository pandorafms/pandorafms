<?php

namespace PandoraFMS\Modules\Shared\Core;

use JsonSerializable;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\InvalidFilterException;

abstract class SerializableAbstract implements JsonSerializable
{
    public function __construct()
    {
    }

    abstract public function fieldsReadOnly(): array;

    abstract public function jsonSerialize(): mixed;

    abstract public function getValidations(): array;

    abstract public function validateFields(array $filters): array;

    public function toArray()
    {
        return $this->jsonSerialize();
    }

    public function validate(array $params): array
    {
        $filters = [];
        foreach ($this->getValidations() as $field => $type) {
            if (isset($params[$field]) === true) {
                $filters[$field] = [
                    'type'  => $type,
                    'value' => ($params[$field] ?? ''),
                ];
            }
        }

        return $this->validateFields($filters);
    }

    public function fromArray(array $params): static
    {
        $fails = $this->validate($params);
        if (empty($fails) === false) {
            throw new InvalidFilterException($fails);
        }

        foreach ($params as $field => $value) {
            // Not valid parameters.
            if (method_exists($this, 'set'.ucfirst($field)) === false) {
                throw new BadRequestException(__('Field').': '.$field.' '.__('is not a valid parameter'));
            }

            // Read only parameters.
            if (isset($this->fieldsReadOnly()[$field]) === true) {
                throw new BadRequestException(__('Field').': '.$field.' '.__('is a read only parameter'));
            }

            $this->{'set'.ucfirst($field)}($value ?? null);
        }

        return $this;
    }
}
