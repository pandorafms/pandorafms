<?php

namespace PandoraFMS\Modules\Tags\Services;

use PandoraFMS\Modules\Tags\Entities\TagFilter;
use PandoraFMS\Modules\Tags\Repositories\TagRepository;

final class ListTagService
{
    public function __construct(
        private TagRepository $tagRepository,
    ) {
    }

    public function __invoke(TagFilter $tagFilter): array
    {
        return $this->tagRepository->list($tagFilter);
    }
}
