<?php

namespace PandoraFMS\Modules\Shared\Traits;

trait GroupByFilterTrait
{
    private ?array $groupByFields = null;

    public function getGroupByFields(): ?array
    {
        return $this->groupByFields;
    }

    public function setGroupByFields(?array $groupByFields): self
    {
        $this->groupByFields = $groupByFields;

        return $this;
    }
}
