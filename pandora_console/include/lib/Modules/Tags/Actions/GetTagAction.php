<?php

namespace PandoraFMS\Modules\Tags\Actions;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Services\GetTagService;

final class GetTagAction
{
    public function __construct(
        private GetTagService $getTagService
    ) {
    }

    public function __invoke(int $idTag): Tag
    {
        return $this->getTagService->__invoke($idTag);
    }
}
