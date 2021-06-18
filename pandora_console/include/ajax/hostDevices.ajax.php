<?php
/**
 * Hook in Host&Devices for CSV import.
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices - CSV Import Agents
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ==========================================================
 * Copyright (c) 2004-2019 Artica Soluciones Tecnológicas S.L
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

$get_explanation = (bool) get_parameter('get_explanation', 0);
$get_recon_script_macros = get_parameter('get_recon_script_macros', 0);

if ($get_explanation) {
    $id = (int) get_parameter('id', 0);

    $explanation = db_get_value(
        'description',
        'trecon_script',
        'id_recon_script',
        $id
    );

    echo io_safe_output($explanation);

    return;
}

if ($get_recon_script_macros) {
    $id_recon_script = (int) get_parameter('id');
    $id_recon_task = (int) get_parameter('id_rt');

    if (!empty($id_recon_task) && empty($id_recon_script)) {
        $recon_script_macros = db_get_value(
            'macros',
            'trecon_task',
            'id_rt',
            $id_recon_task
        );
    } else if (!empty($id_recon_task)) {
        $recon_task_id_rs = (int) db_get_value(
            'id_recon_script',
            'trecon_task',
            'id_rt',
            $id_recon_task
        );

        if ($id_recon_script == $recon_task_id_rs) {
            $recon_script_macros = db_get_value(
                'macros',
                'trecon_task',
                'id_rt',
                $id_recon_task
            );
        } else {
            $recon_script_macros = db_get_value(
                'macros',
                'trecon_script',
                'id_recon_script',
                $id_recon_script
            );
        }
    } else if (!empty($id_recon_script)) {
        $recon_script_macros = db_get_value(
            'macros',
            'trecon_script',
            'id_recon_script',
            $id_recon_script
        );
    } else {
        $recon_script_macros = [];
    }

    $macros = [];
    $macros['base64'] = base64_encode($recon_script_macros);
    $macros['array'] = json_decode($recon_script_macros, true);

    echo io_json_mb_encode($macros);
    return;
}
