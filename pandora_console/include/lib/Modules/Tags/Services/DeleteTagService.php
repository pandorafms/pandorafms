<?php

namespace PandoraFMS\Modules\Tags\Services;

use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Tags\Entities\Tag;

use PandoraFMS\Modules\Tags\Repositories\TagRepository;

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

        // TODO: XXX.
        \db_process_delete_temp('ttag_module', 'id_tag', $idTag);

        // TODO: XXX.
        \db_process_delete_temp('ttag_policy_module', 'id_tag', $idTag);

        $this->audit->write(
            AUDIT_LOG_TAG_MANAGEMENT,
            'Deleted tag '.$nameTag
        );
    }
}
