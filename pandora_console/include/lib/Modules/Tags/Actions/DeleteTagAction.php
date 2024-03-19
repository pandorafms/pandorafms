<?php

namespace PandoraFMS\Modules\Tags\Actions;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Services\DeleteTagService;

final class DeleteTagAction
{
    public function __construct(
        private DeleteTagService $deleteTagService
    ) {
    }

    public function __invoke(Tag $tag): void
    {
        $this->deleteTagService->__invoke($tag);
    }
}
