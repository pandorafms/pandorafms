<?php

namespace PandoraFMS\Modules\Tags\Actions;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Services\UpdateTagService;

final class UpdateTagAction
{
    public function __construct(
        private UpdateTagService $updateTagService
    ) {
    }

    public function __invoke(Tag $tag, Tag $oldTag): Tag
    {
        return $this->updateTagService->__invoke($tag, $oldTag);
    }
}
