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


    /**
     * Get the value of tableName.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }


    /**
     * Get the value of primaryKey.
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }


    /**
     * Get the value of tableRelated.
     */
    public function getTableRelated(): null|string
    {
        return $this->tableRelated;
    }


    /**
     * Set the value of tableRelated.
     */
    public function setTableRelated(null|string $tableRelated): self
    {
        $this->tableRelated = $tableRelated;

        return $this;
    }


    /**
     * Get the value of keyRelated.
     */
    public function getKeyRelated(): null|string
    {
        return $this->keyRelated;
    }


    /**
     * Set the value of keyRelated.
     */
    public function setKeyRelated(null|string $keyRelated): self
    {
        $this->keyRelated = $keyRelated;

        return $this;
    }


    /**
     * Get the value of searchFieldRelated.
     */
    public function getSearchFieldRelated(): null|string
    {
        return $this->searchFieldRelated;
    }


    /**
     * Set the value of searchFieldRelated.
     */
    public function setSearchFieldRelated(null|string $searchFieldRelated): self
    {
        $this->searchFieldRelated = $searchFieldRelated;

        return $this;
    }


    /**
     * Get the value of searchFieldValueRelated.
     */
    public function getSearchFieldValueRelated(): mixed
    {
        return $this->searchFieldValueRelated;
    }


    /**
     * Set the value of searchFieldValueRelated.
     */
    public function setSearchFieldValueRelated(mixed $searchFieldValueRelated): self
    {
        $this->searchFieldValueRelated = $searchFieldValueRelated;

        return $this;
    }


    public function getStringNameClass(): string
    {
        $strname = [
            'PandoraFMS\\Modules\\Users\\Entities\\User'                      => 'User',
            'PandoraFMS\\Modules\\Shared\\Workunits\\Entities\\Workunit'      => 'Workunit',
            'PandoraFMS\\Modules\\Shared\\Attachments\\Entities\\Attachment'  => 'Attachment',
            'PandoraFMS\\Modules\\Users\\UserProfiles\\Entities\\UserProfile' => 'Profile',
            'PandoraFMS\\Modules\\Profiles\\Entities\\Profile'                => 'Profile',
            'PandoraFMS\\Modules\\Groups\\Entities\\Group'                    => 'Group',
        ];

        $result = ($strname[$this->getClassName()] ?? '');

        return $result;
    }


}
