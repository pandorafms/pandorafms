<?php

namespace PandoraFMS\Modules\Tags\Services;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Repositories\TagRepository;

use PandoraFMS\Modules\Shared\Services\Audit;

final class DeleteTagService
{
    public function __construct(
        private Audit $audit,
        private TagRepository $tagRepository,
    ) {
    }

    public function __invoke(Tag $tag): void
    {
        $idTag = $tag->getIdTag();
        $nameTag = $tag->getName();
        $this->tagRepository->delete($idTag);

        $this->audit->write(
            AUDIT_LOG_TAG_MANAGEMENT,
            'Deleted tag '.$nameTag
        );
    }
}
