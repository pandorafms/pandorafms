<?php
/**
 * Aux functions to manage plugins.
 *
 * @category   library
 * @package    Pandora FMS
 * @subpackage Plugins
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

use PandoraFMS\Enterprise\Metaconsole\Synchronizer;

// Begin.
global $config;


/**
 * Remove all modules associated to this plugin.
 *
 * @param integer $id_plugin Plugin id.
 *
 * @return array Of erros if any.
 */
function plugins_remove_modules(int $id_plugin)
{
    // Delete all resources related with this plugin.
    $plugin_modules = db_get_all_rows_filter(
        'tagente_modulo',
        [ 'id_plugin' => $id_plugin ],
        'id_agente_modulo'
    );

    $ret = [];

    foreach ($plugin_modules as $id) {
        try {
            $module = new PandoraFMS\Module($id);
            $module->delete();
        } catch (Exception $e) {
            $ret[] = __('Failed to erase module %d: %s', $id, $e->getMessage());
        }
    }

    if (enterprise_installed() === true) {
        enterprise_include_once('include/functions_policies.php');
        $policies_ids = db_get_all_rows_filter(
            'tpolicy_modules',
            ['id_plugin' => $id_plugin],
            'id'
        );

        foreach ($policies_ids as $id) {
            if (policies_change_delete_pending_module($id) !== true) {
                $ret[] = __('Failed to erase policy module: %d', $id);
            }
        }
    }

    return $ret;
}


/**
 * Effectively remove a plugin from the system.
 *
 * @param integer $id_plugin Plugin id.
 *
 * @return array Of errors, empty if no errors.
 */
function plugins_delete_plugin(int $id_plugin)
{
    $result = [];

    $problem = plugins_remove_modules($id_plugin) !== true;
    if (empty($problem) !== false) {
        $result = $problem;
    }

    // Remove all components related to this plugin.
    db_process_sql_delete(
        'tnetwork_component',
        [ 'id_plugin' => $id_plugin ]
    );

    if (is_metaconsole() === true && is_management_allowed() === true) {
        $sc = new Synchronizer();
        $problems = $sc->apply(
            function ($node) use ($id_plugin) {
                $rt = [];
                try {
                    $node->connect();

                    $rt = plugins_remove_modules($id_plugin);

                    $node->disconnect();
                } catch (Exception $e) {
                    $rt[] = $e->getMessage();
                }

                return $rt;
            },
            false
        );

        foreach ($problems as $prob) {
            $result = array_merge($result, $prob);
        }
    }

    return $result;
}
