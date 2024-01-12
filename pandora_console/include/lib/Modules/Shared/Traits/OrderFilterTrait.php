<?php

namespace PandoraFMS\Modules\Shared\Traits;

trait OrderFilterTrait
{

    private ?string $sortField = null;

    private ?string $sortDirection = null;


    /**
     * Get the value of sortField.
     *
     * @return ?string
     */
    public function getSortField(): ?string
    {
        return $this->sortField;
    }


    /**
     * Set the value of sortField.
     *
     * @param string $sortField
     */
    public function setSortField(?string $sortField): self
    {
        $this->sortField = $sortField;

        return $this;
    }


    /**
     * Get the value of sortDirection.
     *
     * @return ?string
     */
    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }


    /**
     * Set the value of sortDirection.
     *
     * @param string $sortDirection
     */
    public function setSortDirection(?string $sortDirection): self
    {
        $this->sortDirection = $sortDirection;

        return $this;
    }


}
