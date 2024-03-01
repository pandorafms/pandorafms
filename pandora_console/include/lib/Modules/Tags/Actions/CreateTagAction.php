<?php

namespace PandoraFMS\Modules\Tags\Actions;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Services\CreateTagService;

final class CreateTagAction
{
    public function __construct(
        private CreateTagService $createTagService
    ) {
    }

    public function __invoke(Tag $tag): Tag
    {
        return $this->createTagService->__invoke($tag);
    }
}
