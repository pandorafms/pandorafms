<?php

namespace PandoraFMS\Modules\Tags\Actions;

use PandoraFMS\Modules\Tags\Entities\TagFilter;
use PandoraFMS\Modules\Tags\Services\CountTagService;
use PandoraFMS\Modules\Tags\Services\ListTagService;

use PandoraFMS\Modules\Shared\Entities\PaginationData;

final class ListTagAction
{
    public function __construct(
        private ListTagService $listTagService,
        private CountTagService $countTagService
    ) {
    }

    public function __invoke(TagFilter $tagFilter): array
    {
        return (new PaginationData(
            $tagFilter->getPage(),
            $tagFilter->getSizePage(),
            $this->countTagService->__invoke($tagFilter),
            $this->listTagService->__invoke($tagFilter)
        ))->toArray();
    }
}
