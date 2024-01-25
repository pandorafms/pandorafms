<?php

namespace PandoraFMS\Modules\Events\Entities;

use PandoraFMS\Modules\Events\Validators\EventValidator;
use PandoraFMS\Modules\Shared\Core\FilterAbstract;


final class EventFilter extends FilterAbstract
{
    private ?string $freeSearch = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(EventDataMapper::UTIMESTAMP);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new Event());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idEvent'    => EventDataMapper::ID_EVENT,
            'utimestamp' => EventDataMapper::UTIMESTAMP,
        ];
    }

    public function fieldsReadOnly(): array
    {
        return [];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'freeSearch' => $this->getFreeSearch(),
        ];
    }

    public function getValidations(): array
    {
        $validations = [];
        if($this->getEntityFilter() !== null) {
            $validations = $this->getEntityFilter()->getValidations();
        }
        $validations['freeSearch'] = EventValidator::STRING;
        return $validations;
    }

    public function validateFields(array $filters): array
    {
        return (new EventValidator())->validate($filters);
    }

    /**
     * Get the value of freeSearch.
     *
     * @return ?string
     */
    public function getFreeSearch(): ?string
    {
        return $this->freeSearch;
    }

    /**
     * Set the value of freeSearch.
     *
     * @param ?string $freeSearch
     *
     */
    public function setFreeSearch(?string $freeSearch): self
    {
        $this->freeSearch = $freeSearch;
        return $this;
    }

    /**
     * Get the value of fieldsFreeSearch.
     *
     * @return ?array
     */
    public function getFieldsFreeSearch(): ?array
    {
        return [EventDataMapper::UTIMESTAMP];
    }

}
