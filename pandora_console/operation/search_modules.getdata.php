<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

enterprise_include_once('include/functions_policies.php');
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';

$searchModules = check_acl($config['id_user'], 0, 'AR');

if ($config['style'] === 'pandora_black') {
    $selectModuleNameUp = '';
    $selectModuleNameDown = '';
    $selectAgentNameUp = '';
    $selectAgentNameDown = '';
} else {
    $selectModuleNameUp = '_black';
    $selectModuleNameDown = '_black';
    $selectAgentNameUp = '_black';
    $selectAgentNameDown = '_black';
}

$is_admin = (bool) db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);

switch ($sortField) {
    case 'module_name':
        switch ($sort) {
            case 'up':
                $selectModuleNameUp = $selected_module;
                $order = [
                    'field' => 'module_name',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectModuleNameDown = $selected_module;
                $order = [
                    'field' => 'module_name',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'agent_name':
        switch ($sort) {
            case 'up':
                $selectAgentNameUp = $selected_module;
                $order = [
                    'field' => 'agent_name',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectAgentNameDown = $selected_module;
                $order = [
                    'field' => 'agent_name',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    default:
        $selectModuleNameUp = $selected_module;
        $order = [
            'field' => 'module_name',
            'order' => 'ASC',
        ];
    break;
}


$modules = false;
if ($searchModules) {
    $userGroups = users_get_groups($config['id_user'], 'AR', false);
    $id_userGroups = array_keys($userGroups);

    $tags = tags_get_tags_for_module_search();
    $sql_tags = "'no_check_tags' = 'no_check_tags'";
    if (!empty($tags)) {
        if ($is_admin) {
            $sql_tags = '1=1';
        } else {
            $sql_tags = '
			(
				t1.id_agente_modulo IN
				(
					SELECT tt.id_agente_modulo
					FROM ttag_module AS tt
					WHERE id_tag IN ('.implode(',', array_keys($tags)).')
				)
				
				OR
				
				t1.id_agente_modulo IN (
					SELECT id_agente_modulo
					FROM ttag_module
				)
			)
			';
        }
    }

    switch ($config['dbtype']) {
        case 'mysql':
            $chunk_sql = '
				FROM tagente_modulo t1
					INNER JOIN tagente t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE
					'.$sql_tags.'
					
					AND
					
					(t2.id_grupo IN ('.implode(',', $id_userGroups).')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = "'.$config['id_user'].'"
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					)
					AND
					(REPLACE(t1.nombre, "&#x20;", " ") LIKE "%'.$stringSearchSQL.'%" OR
					REPLACE(t3.nombre, "&#x20;", " ") LIKE "%'.$stringSearchSQL.'%") 
					AND t1.disabled = 0';
        break;

        case 'postgresql':
            $chunk_sql = '
				FROM tagente_modulo t1
					INNER JOIN tagente t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE
					'.$sql_tags.'
					
					AND
					
					(t2.id_grupo IN ('.implode(',', $id_userGroups).')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = \''.$config['id_user'].'\'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					(REPLACE(t1.nombre, "&#x20;", " ") LIKE \'%'.$stringSearchSQL.'%\' OR
					REPLACE(t3.nombre, "&#x20;", " ") LIKE \'%'.$stringSearchSQL.'%\')';
        break;

        case 'oracle':
            $chunk_sql = '
				FROM tagente_modulo t1
					INNER JOIN tagente t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE
					'.$sql_tags.'
					
					AND
					
					(t2.id_grupo IN ('.implode(',', $id_userGroups).')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = \''.$config['id_user'].'\'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					(LOWER(REPLACE(t1.nombre, "&#x20;", " ")) LIKE \'%'.strtolower($stringSearchSQL).'%\' OR
					LOWER(REPLACE(t3.nombre, "&#x20;", " ")) LIKE \'%'.strtolower($stringSearchSQL).'%\')';
        break;
    }

    $totalModules = db_get_value_sql('SELECT COUNT(t1.id_agente_modulo) AS count_modules '.$chunk_sql);

    if (!$only_count) {
        $select = 'SELECT t1.*, t1.nombre AS module_name, t2.nombre AS agent_name ';
        $order_by = ' ORDER BY '.$order['field'].' '.$order['order'];
        $limit = ' LIMIT '.$config['block_size'].' OFFSET '.(int) get_parameter('offset');

        $query = $select.$chunk_sql.$order_by;

        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $query .= $limit;
            break;

            case 'oracle':
                $set = [];
                $set['limit'] = $config['block_size'];
                $set['offset'] = (int) get_parameter('offset');

                $query = oracle_recode_query($query, $set);
            break;
        }

        $modules = db_get_all_rows_sql($query);
    }
}
