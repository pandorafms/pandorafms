<?php

namespace PandoraFMS\Modules\Tags\Services;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Entities\TagFilter;
use PandoraFMS\Modules\Tags\Repositories\TagRepository;

final class GetTagService
{
    public function __construct(
        private TagRepository $tagRepository,
    ) {
    }

    public function __invoke(int $idTag): Tag
    {
        $tagFilter = new TagFilter();
        /** @var Tag $entityFilter */
        $entityFilter = $tagFilter->getEntityFilter();
        $entityFilter->setIdTag($idTag);

        return $this->tagRepository->getOne($tagFilter);
    }
}
