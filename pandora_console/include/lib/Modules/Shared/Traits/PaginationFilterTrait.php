<?php

namespace PandoraFMS\Modules\Shared\Traits;

trait PaginationFilterTrait
{
    private ?int $sizePage = null;
    private ?int $page = null;

    public function getSizePage(): ?int
    {
        return $this->sizePage;
    }

    public function setSizePage(?int $sizePage): self
    {
        $this->sizePage = $sizePage;

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): self
    {
        $this->page = $page;

        return $this;
    }
}
