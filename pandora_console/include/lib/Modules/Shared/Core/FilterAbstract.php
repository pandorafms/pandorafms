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

    public const ASC = 'ascending';
    public const DESC = 'descending';

    private ?int $limit = null;

    private ?int $offset = null;

    private ?string $defaultFieldOrder = null;

    private ?string $defaultDirectionOrder = null;

    private ?array $fields = null;

    private ?Entity $entityFilter = null;


    public function __construct()
    {
    }


    // abstract public function getWhereClause(): string;
    abstract public function fieldsTranslate(): array;


    /**
     * Get the value of fieldsFreeSearch.
     *
     * @return ?array
     */
    public function getFieldsFreeSearch(): ?array
    {
        return [];
    }


    /**
     * Get the value of multipleSearch.
     *
     * @return ?array
     */
    public function getMultipleSearch(): ?array
    {
        return [];
    }


    /**
     * Get the value of multipleSearchString.
     *
     * @return ?array
     */
    public function getMultipleSearchString(): ?array
    {
        return [];
    }


    /**
     * Get the value of fieldsFreeSearch.
     *
     * @return ?string
     */
    public function getFieldAclGroupMysql(): ?string
    {
        return '';
    }


    /**
     * Get the value of fieldsFreeSearch.
     *
     * @return ?string
     */
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
            } else if ($this->getEntityFilter() !== null && method_exists($this->getEntityFilter(), 'set'.ucfirst($field)) === true) {
                $this->getEntityFilter()->{'set'.ucfirst($field)}($value ?? null);
            }
        }

        return $this;
    }


    /**
     * Get the value of limit.
     *
     * @return ?int
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }


    /**
     * Set the value of limit.
     *
     * @param integer $limit
     */
    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }


    /**
     * Get the value of offset.
     *
     * @return ?int
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }


    /**
     * Set the value of offset.
     *
     * @param integer $offset
     */
    public function setOffset(?int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }


    /**
     * Get the value of defaultFieldOrder.
     *
     * @return ?string
     */
    public function getDefaultFieldOrder(): ?string
    {
        return $this->defaultFieldOrder;
    }


    /**
     * Set the value of defaultFieldOrder.
     *
     * @param string $defaultFieldOrder
     */
    public function setDefaultFieldOrder(?string $defaultFieldOrder): self
    {
        $this->defaultFieldOrder = $defaultFieldOrder;

        return $this;
    }


    /**
     * Get the value of defaultDirectionOrder.
     *
     * @return ?string
     */
    public function getDefaultDirectionOrder(): ?string
    {
        return $this->defaultDirectionOrder;
    }


    /**
     * Set the value of defaultDirectionOrder.
     *
     * @param string $defaultDirectionOrder
     */
    public function setDefaultDirectionOrder(?string $defaultDirectionOrder): self
    {
        $this->defaultDirectionOrder = $defaultDirectionOrder;

        return $this;
    }


    /**
     * Get the value of entityFilter.
     *
     * @return ?Entity
     */
    public function getEntityFilter(): ?Entity
    {
        return $this->entityFilter;
    }


    /**
     * Set the value of entityFilter.
     *
     * @param Entity $entityFilter
     */
    public function setEntityFilter(?Entity $entityFilter): self
    {
        $this->entityFilter = $entityFilter;

        return $this;
    }


    /**
     * Get the value of fields.
     *
     * @return ?array
     */
    public function getFields(): ?array
    {
        return $this->fields;
    }


    /**
     * Set the value of fields.
     *
     * @param array $fields
     */
    public function setFields(?array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }


}
