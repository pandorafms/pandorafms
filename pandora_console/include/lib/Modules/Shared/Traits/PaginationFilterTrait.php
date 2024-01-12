<?php

namespace PandoraFMS\Modules\Shared\Traits;

trait PaginationFilterTrait
{

    private ?int $sizePage = null;

    private ?int $page = null;


    /**
     * Get the value of sizePage.
     *
     * @return ?int
     */
    public function getSizePage(): ?int
    {
        return $this->sizePage;
    }


    /**
     * Set the value of sizePage.
     *
     * @param integer $sizePage
     */
    public function setSizePage(?int $sizePage): self
    {
        $this->sizePage = $sizePage;

        return $this;
    }


    /**
     * Get the value of page.
     *
     * @return ?int
     */
    public function getPage(): ?int
    {
        return $this->page;
    }


    /**
     * Set the value of page.
     *
     * @param integer $page
     */
    public function setPage(?int $page): self
    {
        $this->page = $page;

        return $this;
    }


}
