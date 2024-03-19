<?php

namespace PandoraFMS\Modules\Tags\Repositories;

use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Tags\Entities\TagDataMapper;
use PandoraFMS\Modules\Tags\Entities\TagFilter;
use PandoraFMS\Modules\Shared\Repositories\Repository;

class TagRepository
{
    public function __construct(
        private Repository $repository,
        private TagDataMapper $tagDataMapper
    ) {
    }

    /**
     * @return Tag[],
    */
    public function list(TagFilter $tagFilter): array
    {
        return $this->repository->__list(
            $tagFilter,
            $this->tagDataMapper
        );
    }

    public function count(TagFilter $tagFilter): int
    {
        return $this->repository->__count(
            $tagFilter,
            $this->tagDataMapper
        );
    }

    public function getOne(TagFilter $tagFilter): Tag
    {
        return $this->repository->__getOne(
            $tagFilter,
            $this->tagDataMapper
        );
    }

    public function create(Tag $tag): Tag
    {
        $id = $this->repository->__create($tag, $this->tagDataMapper);
        return $tag->setIdTag($id);
    }

    public function update(Tag $tag): Tag
    {
        return $this->repository->__update(
            $tag,
            $this->tagDataMapper,
            $tag->getIdTag()
        );
    }

    public function delete(int $id): void
    {
        $this->repository->__delete($id, $this->tagDataMapper);
    }

}
