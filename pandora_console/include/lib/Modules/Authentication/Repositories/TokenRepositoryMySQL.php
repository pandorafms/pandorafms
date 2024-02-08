<?php

namespace PandoraFMS\Modules\Authentication\Repositories;

use InvalidArgumentException;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Repositories\RepositoryMySQL;
use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Entities\TokenDataMapper;
use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Shared\Services\Config;

final class TokenRepositoryMySQL extends RepositoryMySQL implements TokenRepository
{
    public function __construct(
        private TokenDataMapper $tokenDataMapper,
        private Config $config
    ) {
    }

    /**
     * @return Token[],
     */
    public function list(TokenFilter $tokenFilter): array
    {
        try {
            $sql = $this->getAuthenticationQuery($tokenFilter, $this->tokenDataMapper);
            $list = $this->dbGetAllRowsSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($list) === false) {
            throw new NotFoundException(__('%s not found', $this->tokenDataMapper->getStringNameClass()));
        }

        $result = [];
        foreach ($list as $fields) {
            $result[] = $this->tokenDataMapper->fromDatabase($fields);
        }

        return $result;
    }

    public function count(TokenFilter $tokenFilter): int
    {
        $sql = $this->getAuthenticationQuery($tokenFilter, $this->tokenDataMapper, true);
        try {
            $count = $this->dbGetValueSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        return (int) $count;
    }

    public function getOne(TokenFilter $tokenFilter): Token
    {
        try {
            $sql = $this->getAuthenticationQuery($tokenFilter, $this->tokenDataMapper);
            $result = $this->dbGetRowSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (empty($result) === true) {
            throw new NotFoundException(__('%s not found', $this->tokenDataMapper->getStringNameClass()));
        }

        return $this->tokenDataMapper->fromDatabase($result);
    }

    public function getExistToken(string $label): Token
    {
        try {
            $sql = sprintf('SELECT * FROM `ttoken` WHERE `label` = "%s"', $label);
            $result = $this->dbGetRowSql($sql);
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (empty($result) === true) {
            throw new NotFoundException(__('%s not found', $this->tokenDataMapper->getStringNameClass()));
        }

        return $this->tokenDataMapper->fromDatabase($result);
    }

    public function create(Token $token): Token
    {
        $idToken = $this->__create($token, $this->tokenDataMapper);
        return $token->setIdToken($idToken);
    }

    public function update(Token $token): Token
    {
        return $this->__update(
            $token,
            $this->tokenDataMapper,
            $token->getIdToken()
        );
    }

    public function delete(int $id): void
    {
        $this->__delete($id, $this->tokenDataMapper);
    }

    private function getAuthenticationQuery(
        FilterAbstract $filter,
        DataMapperAbstract $mapper,
        bool $count = false
    ): string {
        $pagination = '';
        $orderBy = '';
        $fields = 'COUNT(DISTINCT ttoken.id) as count';
        $filters = $this->buildQueryFilters($filter, $mapper);

        // Check ACL for user list.
        if (\users_is_admin() === false) {
            // No admin.
            $filters .= sprintf(
                ' AND ttoken.id_user = "%s"',
                $this->config->get('id_user')
            );
        }

        if ($count === false) {
            $pagination = $this->buildQueryPagination($filter);
            $orderBy = $this->buildQueryOrderBy($filter);
            if (empty($filter->getFields()) === true) {
                $fields = 'DISTINCT ttoken.*';
            } else {
                $buildFields = '';
                foreach ($filter->getFields() as $field) {
                    if (empty($buildFields) === false) {
                        $buildFields .= ' , ';
                    }

                    $buildFields .= $field;
                }

                $fields = $buildFields;
            }
        }

        $sql = sprintf(
            'SELECT %s
            FROM ttoken
            INNER JOIN tusuario
                ON tusuario.id_user = ttoken.id_user
            WHERE %s
            %s
            %s',
            $fields,
            $filters,
            $orderBy,
            $pagination
        );

        return $sql;
    }
}
