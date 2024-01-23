<?php

namespace PandoraFMS\Modules\Shared\Builders;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;

class Builder
{
    public function build(Entity $entity, array $data): Entity
    {
        foreach ($data as $field => $value) {
            if (method_exists($entity, 'set'.ucfirst($field)) === false) {
                throw new BadRequestException(__('Not exists method set %s', ucfirst($field)));
            }

            $entity->{'set'.ucfirst($field)}($value);
        }

        return $entity;
    }
}
