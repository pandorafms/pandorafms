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


function clippy_modules_not_init()
{
    $return_tours = [];
    $return_tours['first_step_by_default'] = true;
    $return_tours['help_context'] = true;
    $return_tours['tours'] = [];

    // ==================================================================
    // Help tour about the monitoring with a ping (step 3)
    // ------------------------------------------------------------------
    $return_tours['tours']['agent_out_of_limits'] = [];
    $return_tours['tours']['agent_out_of_limits']['steps'] = [];
    $return_tours['tours']['agent_out_of_limits']['steps'][] = [
        'init_step_context' => true,
        'intro'             => '<table>'.'<tr>'.'<td class="context_help_title">'.__('You have non initialized modules').'</td>'.'</tr>'.'<tr>'.'<td class="context_help_body">'.__('This happen when you have just created a module and it\'s not executed at first time. Usually in a few seconds should be initialized and you will be able to see in main view. If you keep non-init modules for more than 24hr (due a problem in it\'s execution or configuration) they will be automatically deleted by the system. Non-init are not visible in the “main view”, you can see/edit them in the module administration section, in the agent administrator.').'</td>'.'</tr>'.'</table>',
    ];
    $return_tours['tours']['agent_out_of_limits']['conf'] = [];
    $return_tours['tours']['agent_out_of_limits']['conf']['autostart'] = false;
    $return_tours['tours']['agent_out_of_limits']['conf']['show_bullets'] = 0;
    $return_tours['tours']['agent_out_of_limits']['conf']['show_step_numbers'] = 0;
    // ==================================================================
    return $return_tours;
}
