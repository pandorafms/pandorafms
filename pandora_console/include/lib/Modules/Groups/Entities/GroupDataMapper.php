<?php

namespace PandoraFMS\Modules\Groups\Entities;

use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class GroupDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'tgrupo';
    public const ID_GROUP = 'id_grupo';
    public const NAME = 'nombre';
    public const ICON = 'icon';
    public const PARENT = 'parent';
    public const IS_PROPAGATE = 'propagate';
    public const IS_DISABLED = 'disabled';
    public const CUSTOM_ID = 'custom_id';
    public const ID_SKIN = 'id_skin';
    public const DESCRIPTION = 'description';
    public const CONTACT = 'contact';
    public const OTHER = 'other';
    public const PASSWORD = 'password';
    public const MAX_AGENTS = 'max_agents';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_GROUP,
        );
    }

    public function getClassName(): string
    {
        return Group::class;
    }

    public function fromDatabase(array $data): Group
    {
        return $this->builder->build(new Group(), [
            'idGroup'     => $data[self::ID_GROUP],
            'name'        => $this->repository->safeOutput($data[self::NAME]),
            'icon'        => $data[self::ICON],
            'parent'      => $data[self::PARENT],
            'isPropagate' => $data[self::IS_PROPAGATE],
            'isAlertEnabled'  => $data[self::IS_DISABLED],
            'customId'    => $data[self::CUSTOM_ID],
            'idSkin'      => $data[self::ID_SKIN],
            'description' => $this->repository->safeOutput($data[self::DESCRIPTION]),
            'contact'     => $this->repository->safeOutput($data[self::CONTACT]),
            'other'       => $this->repository->safeOutput($data[self::OTHER]),
            'password'    => $data[self::PASSWORD],
            'maxAgents'   => $data[self::MAX_AGENTS],
        ]);
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var Group $data */
        return [
            self::ID_GROUP     => $data->getIdGroup(),
            self::NAME         => $this->repository->safeInput($data->getName()),
            self::ICON         => $data->getIcon(),
            self::PARENT       => $data->getParent(),
            self::IS_PROPAGATE => $data->getIsPropagate(),
            self::IS_DISABLED  => $data->getIsAlertEnabled(),
            self::CUSTOM_ID    => $data->getCustomId(),
            self::ID_SKIN      => $data->getIdSkin(),
            self::DESCRIPTION  => $this->repository->safeInput($data->getDescription()),
            self::CONTACT      => $this->repository->safeInput($data->getContact()),
            self::OTHER        => $this->repository->safeInput($data->getOther()),
            self::PASSWORD     => $this->repository->safeInput($data->getPassword()),
            self::MAX_AGENTS   => $data->getMaxAgents(),
        ];
    }
}
