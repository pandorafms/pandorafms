<?php

namespace PandoraFMS\Modules\Tags\Validations;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Services\ExistNameTagService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;

final class TagValidation
{
    public function __construct(
        private ExistNameTagService $existNameTagService
    ) {
    }

    public function __invoke(Tag $tag, ?Tag $oldTag = null): void
    {
        if (!$tag->getName()) {
            throw new BadRequestException(__('Name is missing'));
        }

        if($oldTag === null || $oldTag->getName() !== $tag->getName()) {
            if($this->existNameTagService->__invoke($tag->getName()) === true) {
                throw new BadRequestException(
                    __('Name %s is already exists', $tag->getName())
                );
            }
        }

        //if($tag->getIsAgentView() === null) {
        //    $tag->setIsAgentView(false);
        //}
    }
}
