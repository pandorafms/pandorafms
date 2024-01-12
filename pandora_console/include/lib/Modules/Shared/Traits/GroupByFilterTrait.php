<?php

namespace PandoraFMS\Modules\Shared\Traits;

trait GroupByFilterTrait
{

    private ?array $groupByFields = null;


    /**
     * Get the value of groupByFields.
     *
     * @return ?array
     */
    public function getGroupByFields(): ?array
    {
        return $this->groupByFields;
    }


    /**
     * Set the value of groupByFields.
     *
     * @param array $groupByFields
     */
    public function setGroupByFields(?array $groupByFields): self
    {
        $this->groupByFields = $groupByFields;

        return $this;
    }


}
