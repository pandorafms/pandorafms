<?php

namespace PandoraFMS\Modules\Tags\Services;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Repositories\TagRepository;
use PandoraFMS\Modules\Tags\Validations\TagValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class CreateTagService
{
    public function __construct(
        private Audit $audit,
        private TagRepository $tagRepository,
        private TagValidation $tagValidation
    ) {
    }

    public function __invoke(Tag $tag): Tag
    {
        $this->tagValidation->__invoke($tag);

        $tag = $this->tagRepository->create($tag);

        $this->audit->write(
            AUDIT_LOG_TAG_MANAGEMENT,
            'Create tag '.$tag->getName(),
            json_encode($tag->toArray())
        );

        return $tag;
    }
}