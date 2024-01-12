<?php

namespace PandoraFMS\Modules\Shared\Entities;

use JsonSerializable;

class PaginationData implements JsonSerializable
{

    private ?int $totalPages = null;

    private ?int $sizePage = null;

    private ?int $currentPage = null;

    private ?int $totalRegisters = null;

    private ?int $totalRegistersPage = null;

    private ?array $data = null;


    public function __construct(
        $currentPage,
        $sizePage,
        $totalRegisters,
        $data,
    ) {
        $totalPages = empty($sizePage) === false ? ceil($totalRegisters / $sizePage) : 0;
        $this->setTotalPages($totalPages);
        $this->setSizePage($sizePage);
        $this->setCurrentPage($currentPage);
        $this->setSizePage($currentPage);
        $this->setTotalRegisters($totalRegisters);
        $this->setTotalRegistersPage(count($data));
        $this->setData($data);
    }


    public function toArray()
    {
        return $this->jsonSerialize();
    }


    public function jsonSerialize(): mixed
    {
        return [
            'paginationData' => [
                'totalPages'         => $this->getTotalPages(),
                'sizePage'           => $this->getSizePage(),
                'currentPage'        => $this->getCurrentPage(),
                'totalRegisters'     => $this->getTotalRegisters(),
                'totalRegistersPage' => $this->getTotalRegistersPage(),
            ],
            'data'           => $this->getData(),
        ];
    }


    /**
     * Get the value of totalPages.
     *
     * @return ?int
     */
    public function getTotalPages(): ?int
    {
        return $this->totalPages;
    }


    /**
     * Set the value of totalPages.
     *
     * @param integer $totalPages
     */
    public function setTotalPages(?int $totalPages): self
    {
        $this->totalPages = $totalPages;

        return $this;
    }


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
     * Get the value of currentPage.
     *
     * @return ?int
     */
    public function getCurrentPage(): ?int
    {
        return $this->currentPage;
    }


    /**
     * Set the value of currentPage.
     *
     * @param integer $currentPage
     */
    public function setCurrentPage(?int $currentPage): self
    {
        $this->currentPage = $currentPage;

        return $this;
    }


    /**
     * Get the value of totalRegisters.
     *
     * @return ?int
     */
    public function getTotalRegisters(): ?int
    {
        return $this->totalRegisters;
    }


    /**
     * Set the value of totalRegisters.
     *
     * @param integer $totalRegisters
     */
    public function setTotalRegisters(?int $totalRegisters): self
    {
        $this->totalRegisters = $totalRegisters;

        return $this;
    }


    /**
     * Get the value of totalRegistersPage.
     *
     * @return ?int
     */
    public function getTotalRegistersPage(): ?int
    {
        return $this->totalRegistersPage;
    }


    /**
     * Set the value of totalRegistersPage.
     *
     * @param integer $totalRegistersPage
     */
    public function setTotalRegistersPage(?int $totalRegistersPage): self
    {
        $this->totalRegistersPage = $totalRegistersPage;

        return $this;
    }


    /**
     * Get the value of data.
     *
     * @return ?array
     */
    public function getData(): ?array
    {
        return $this->data;
    }


    /**
     * Set the value of data.
     *
     * @param array $data
     */
    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }


}
