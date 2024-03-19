<?php
/**
 * Functions Token.
 *
 * @category   Users
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *
 * Pandora FMS - https://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2024 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

use PandoraFMS\Modules\Authentication\Actions\CreateTokenAction;
use PandoraFMS\Modules\Authentication\Actions\DeleteTokenAction;
use PandoraFMS\Modules\Authentication\Actions\GetTokenAction;
use PandoraFMS\Modules\Authentication\Actions\ListTokenAction;
use PandoraFMS\Modules\Authentication\Actions\UpdateTokenAction;
use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Entities\TokenFilter;


/**
 * Get token.
 *
 * @param integer $idToken Token ID.
 *
 * @return array
 */
function get_user_token(int $idToken): array
{
    global $container;
    $token = $container->get(GetTokenAction::class)->__invoke($idToken)->toArray();

    return $token;
}


/**
 * Get info tokens for user.
 *
 * @param integer     $page          Page.
 * @param integer     $pageSize      Size page.
 * @param string|null $sortField     Sort field.
 * @param string|null $sortDirection Sort direction.
 * @param array       $filters       Filters.
 *
 * @return array
 */
function list_user_tokens(
    int $page=0,
    int $pageSize=0,
    ?string $sortField=null,
    ?string $sortDirection=null,
    array $filters=[]
): array {
    global $config;
    global $container;

    $tokenFilter = new TokenFilter;
    $tokenFilter->setPage($page);
    $tokenFilter->setSizePage($pageSize);
    $tokenFilter->setSortField($sortField);
    $tokenFilter->setSortDirection($sortDirection);

    if (empty($filters['freeSearch']) === false) {
        $tokenFilter->setFreeSearch($filters['freeSearch']);
    }

    // phpcs:ignore
    /** @var Token $entityFilter */
    $entityFilter = $tokenFilter->getEntityFilter();

    if (empty($filters['idUser']) === false) {
        $entityFilter->setIdUser($filters['idUser']);
    }

    $result = $container->get(ListTokenAction::class)->__invoke($tokenFilter);

    return $result;
}


/**
 * Create token.
 *
 * @param array $params Params.
 *
 * @return array
 */
function create_user_token(array $params): array
{
    global $container;

    $token = new Token;
    $token->setIdUser($params['idUser']);
    $token->setLabel(io_safe_output($params['label']));
    $token->setValidity((empty($params['validity']) === false) ? io_safe_output($params['validity']) : null);
    $result = $container->get(CreateTokenAction::class)->__invoke($token)->toArray();

    return $result;
}


/**
 * Update token.
 *
 * @param integer $idToken Token ID.
 * @param array   $params  Params.
 *
 * @return array
 */
function update_user_token(int $idToken, array $params): array
{
    global $container;

    $token = $container->get(GetTokenAction::class)->__invoke($idToken);
    $oldToken = clone $token;

    $token->setIdUser($params['idUser']);
    $token->setLabel(io_safe_output($params['label']));
    $token->setValidity((empty($params['validity']) === false) ? io_safe_output($params['validity']) : null);

    $result = $container->get(UpdateTokenAction::class)->__invoke($token, $oldToken)->toArray();

    return $result;
}


/**
 * Delete token.
 *
 * @param integer $idToken Token ID.
 *
 * @return boolean
 */
function delete_user_token(int $idToken): bool
{
    global $container;

    $token = $container->get(GetTokenAction::class)->__invoke($idToken);
    $container->get(DeleteTokenAction::class)->__invoke($token);
    $result = true;

    return $result;
}
