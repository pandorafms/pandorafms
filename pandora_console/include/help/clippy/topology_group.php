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
 * @subpackage Clippy
 */


function clippy_topology_group()
{
    $return_tours = [];
    $return_tours['first_step_by_default'] = true;
    $return_tours['help_context'] = true;
    $return_tours['tours'] = [];

    // ==================================================================
    // Help tour about the monitoring with a ping (step 3)
    // ------------------------------------------------------------------
    $return_tours['tours']['topology_group'] = [];
    $return_tours['tours']['topology_group']['steps'] = [];
    $return_tours['tours']['topology_group']['steps'][] = [
        'init_step_context' => true,
        'intro'             => '<table>'.'<tr>'.'<td class="context_help_title">'.__('Topology Group').'</td>'.'</tr>'.'<tr>'.'<td class="context_help_body">'.__('Please note that group topology maps do not show the parent relationship between nodes, it only shows the group parentship and the agent distribution inside them. ').'</td>'.'</tr>'.'</table>',
    ];
    $return_tours['tours']['topology_group']['conf'] = [];
    $return_tours['tours']['topology_group']['conf']['autostart'] = false;
    $return_tours['tours']['topology_group']['conf']['show_bullets'] = 0;
    $return_tours['tours']['topology_group']['conf']['show_step_numbers'] = 0;
    // ==================================================================
    return $return_tours;
}
