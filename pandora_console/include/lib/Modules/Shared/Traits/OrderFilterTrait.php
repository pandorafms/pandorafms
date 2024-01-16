<?php

namespace PandoraFMS\Modules\Shared\Traits;

trait OrderFilterTrait
{
    private ?string $sortField = null;
    private ?string $sortDirection = null;

    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    public function setSortField(?string $sortField): self
    {
        $this->sortField = $sortField;

        return $this;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(?string $sortDirection): self
    {
        $this->sortDirection = $sortDirection;

        return $this;
    }
}
