<?php

namespace PandoraFMS\Modules\Tags\Services;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Entities\TagFilter;
use PandoraFMS\Modules\Tags\Repositories\TagRepository;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class ExistNameTagService
{
    public function __construct(
        private TagRepository $tagRepository,
    ) {
    }

    public function __invoke(string $name): bool
    {
        $tagFilter = new TagFilter();
        /** @var Tag $entityFilter */
        $entityFilter = $tagFilter->getEntityFilter();
        $entityFilter->setName($name);

        try {
            $this->tagRepository->getOne($tagFilter);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
