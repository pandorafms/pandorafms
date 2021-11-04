<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
/**
 * @package    Include
 * @subpackage Migration
 */


function migration_open_networkmaps()
{
    global $config;

    include_once $config['homedir'].'/include/functions_maps.php';

    $old_networkmaps_open = db_get_all_rows_in_table('tnetwork_map');

    foreach ($old_networkmaps_open as $old_netw_open) {
        $new_networkmap = [];

        $new_networkmap['name'] = io_safe_output($old_netw_open['name']);
        $new_networkmap['id_user'] = $old_netw_open['id_user'];
        $new_networkmap['id_group'] = $old_netw_open['store_group'];
        $new_networkmap['source_period'] = MAP_REFRESH_TIME;

        switch ($old_netw_open['type']) {
            case 'radial_dynamic':
                $new_networkmap['type'] = MAP_TYPE_NETWORKMAP;
                $new_networkmap['subtype'] = MAP_SUBTYPE_RADIAL_DYNAMIC;
            break;

            case 'policies':
                $new_networkmap['type'] = MAP_TYPE_NETWORKMAP;
                $new_networkmap['subtype'] = MAP_SUBTYPE_POLICIES;
            break;

            case 'groups':
                $new_networkmap['type'] = MAP_TYPE_NETWORKMAP;
                $new_networkmap['subtype'] = MAP_SUBTYPE_GROUPS;
            break;

            case 'topology':
                $new_networkmap['type'] = MAP_TYPE_NETWORKMAP;
                $new_networkmap['subtype'] = MAP_SUBTYPE_TOPOLOGY;
            break;
        }

        // ---- Source -------------------------------------------------
        $new_networkmap['source'] = MAP_SOURCE_GROUP;
        $new_networkmap['source_data'] = $old_netw_open['id_group'];

        switch ($old_netw_open['layout']) {
            case 'radial':
                $new_networkmap['generation_method'] = MAP_GENERATION_RADIAL;
            break;
        }

        // ---- Filter -------------------------------------------------
        $filter = [];

        $filter['id_tag'] = 0;
        if ($old_netw_open['id_tag']) {
            $filter['id_tag'] = 1;
        }

        $filter['text'] = $old_netw_open['text_filter'];
        $filter['show_pandora_nodes'] = 0;
        // Only metaconsole
        switch ($old_netw_open['depth']) {
            case 'agents':
                $filter['show_modules'] = 0;
                $filter['show_agents'] = 1;
            break;

            case 'all':
                $filter['show_modules'] = 0;
                $filter['show_agents'] = 1;
            break;

            case 'groups':
                $filter['show_modules'] = 0;
                $filter['show_agents'] = 0;
            break;
        }

        $filter['only_modules_with_alerts'] = 0;
        if ($old_netw_open['only_modules_with_alerts']) {
            $filter['only_modules_with_alerts'] = 1;
        }

        $filter['show_module_group'] = 0;
        if ($old_netw_open['show_modulegroup']) {
            $filter['show_module_group'] = 1;
        }

        $filter['module_group'] = 0;
        if ($old_netw_open['id_module_group']) {
            $filter['module_group'] = 1;
        }

        $filter['only_policy_modules'] = 0;
        $filter['only_snmp_modules'] = 0;
        if ($old_netw_open['show_snmp_modules']) {
            $filter['only_snmp_modules'] = 1;
        }

        $new_networkmap['filter'] = json_encode($filter);
        // -------------------------------------------------------------
        maps_save_map($new_networkmap);
    }
}
