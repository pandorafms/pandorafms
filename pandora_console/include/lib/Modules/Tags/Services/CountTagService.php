<?php

namespace PandoraFMS\Modules\Tags\Services;

use PandoraFMS\Modules\Tags\Entities\TagFilter;
use PandoraFMS\Modules\Tags\Repositories\TagRepository;

final class CountTagService
{
    public function __construct(
        private TagRepository $tagRepository,
    ) {
    }

    public function __invoke(TagFilter $tagFilter): int
    {
        return $this->tagRepository->count($tagFilter);
    }
}
