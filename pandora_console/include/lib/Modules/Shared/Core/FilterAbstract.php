<?php

namespace PandoraFMS\Modules\Shared\Core;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\InvalidFilterException;
use PandoraFMS\Modules\Shared\Traits\GroupByFilterTrait;
use PandoraFMS\Modules\Shared\Traits\OrderFilterTrait;
use PandoraFMS\Modules\Shared\Traits\PaginationFilterTrait;

abstract class FilterAbstract extends SerializableAbstract
{
    use PaginationFilterTrait;
    use OrderFilterTrait;
    use GroupByFilterTrait;

    public const ASC = 'ASC';
    public const DESC = 'DESC';

    private ?int $limit = null;
    private ?int $offset = null;
    private ?string $defaultFieldOrder = null;
    private ?string $defaultDirectionOrder = null;
    private ?array $fields = null;
    private ?Entity $entityFilter = null;

    public function __construct()
    {
    }

    abstract public function fieldsTranslate(): array;

    public function getFieldsFreeSearch(): ?array
    {
        return [];
    }

    public function getMultipleSearch(): ?array
    {
        return [];
    }

    public function getMultipleSearchString(): ?array
    {
        return [];
    }

    public function getFieldAclGroupMysql(): ?string
    {
        return '';
    }

    public function getModeAclGroupMysql(): ?string
    {
        return null;
    }

    public function fromArray(array $params): static
    {
        $fails = $this->validate($params);
        if (empty($fails) === false) {
            throw new InvalidFilterException($fails);
        }

        foreach ($params as $field => $value) {
            if (method_exists($this, 'set'.ucfirst($field)) === false) {
                if ($this->getEntityFilter() === null || method_exists($this->getEntityFilter(), 'set'.ucfirst($field)) === false) {
                    throw new BadRequestException(__('Field').': '.$field.' '.__('is not a valid parameter'));
                }
            }

            if (method_exists($this, 'set'.ucfirst($field)) === true) {
                $this->{'set'.ucfirst($field)}($value ?? null);
            } elseif ($this->getEntityFilter() !== null && method_exists($this->getEntityFilter(), 'set'.ucfirst($field)) === true) {
                $this->getEntityFilter()->{'set'.ucfirst($field)}($value ?? null);
            }
        }

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function getDefaultFieldOrder(): ?string
    {
        return $this->defaultFieldOrder;
    }

    public function setDefaultFieldOrder(?string $defaultFieldOrder): self
    {
        $this->defaultFieldOrder = $defaultFieldOrder;

        return $this;
    }

    public function getDefaultDirectionOrder(): ?string
    {
        return $this->defaultDirectionOrder;
    }

    public function setDefaultDirectionOrder(?string $defaultDirectionOrder): self
    {
        $this->defaultDirectionOrder = $defaultDirectionOrder;

        return $this;
    }

    public function getEntityFilter(): ?Entity
    {
        return $this->entityFilter;
    }

    public function setEntityFilter(?Entity $entityFilter): self
    {
        $this->entityFilter = $entityFilter;

        return $this;
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }

    public function setFields(?array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }
}
