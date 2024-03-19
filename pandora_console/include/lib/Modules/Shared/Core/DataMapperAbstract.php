<?php

namespace PandoraFMS\Modules\Shared\Core;

abstract class DataMapperAbstract
{
    private null|string $tableRelated = null;
    private null|string $keyRelated = null;
    private null|string $searchFieldRelated = null;
    private mixed $searchFieldValueRelated = null;

    public function __construct(
        private string $tableName,
        private string $primaryKey
    ) {
    }

    abstract public function getClassName(): string;

    abstract public function fromDatabase(array $data): MappeableInterface;

    abstract public function toDatabase(MappeableInterface $data): array;

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getTableRelated(): null|string
    {
        return $this->tableRelated;
    }

    public function setTableRelated(null|string $tableRelated): self
    {
        $this->tableRelated = $tableRelated;

        return $this;
    }

    public function getKeyRelated(): null|string
    {
        return $this->keyRelated;
    }

    public function setKeyRelated(null|string $keyRelated): self
    {
        $this->keyRelated = $keyRelated;

        return $this;
    }

    public function getSearchFieldRelated(): null|string
    {
        return $this->searchFieldRelated;
    }

    public function setSearchFieldRelated(null|string $searchFieldRelated): self
    {
        $this->searchFieldRelated = $searchFieldRelated;

        return $this;
    }

    public function getSearchFieldValueRelated(): mixed
    {
        return $this->searchFieldValueRelated;
    }

    public function setSearchFieldValueRelated(mixed $searchFieldValueRelated): self
    {
        $this->searchFieldValueRelated = $searchFieldValueRelated;

        return $this;
    }

    public function getStringNameClass(): string
    {
        $strname = [
            'PandoraFMS\\Modules\\Users\\Entities\\User'                      => 'User',
            'PandoraFMS\\Modules\\Users\\UserProfiles\\Entities\\UserProfile' => 'UserProfile',
            'PandoraFMS\\Modules\\Profiles\\Entities\\Profile'                => 'Profile',
            'PandoraFMS\\Modules\\Events\\Entities\\Event'                    => 'Event',
            'PandoraFMS\\Modules\\Events\\Filters\\Entities\\EventFilter'     => 'EventFilter',
            'PandoraFMS\\Modules\\Groups\\Entities\\Group'                    => 'Group',
            'PandoraFMS\\Modules\\Tags\\Entities\\Tag'                        => 'Tag',
            'PandoraFMS\\Modules\\Authentication\\Entities\\Token'            => 'Token',
        ];

        $result = ($strname[$this->getClassName()] ?? '');

        return $result;
    }
}
