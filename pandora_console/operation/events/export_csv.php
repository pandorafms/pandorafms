<?php
/**
 * Event CSV exporter.
 *
 * @category   Event CSV export
 * @package    Pandora FMS
 * @subpackage Community
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

// Load global vars.
global $config;

require_once '../../include/config.php';
require_once '../../include/auth/mysql.php';
require_once '../../include/functions.php';
require_once '../../include/functions_db.php';
require_once '../../include/functions_events.php';
require_once '../../include/functions_agents.php';
require_once '../../include/functions_groups.php';

$config['id_user'] = $_SESSION['id_usuario'];

if (! check_acl($config['id_user'], 0, 'ER')
    && ! check_acl($config['id_user'], 0, 'EW')
    && ! check_acl($config['id_user'], 0, 'EM')
) {
    exit;
}

// Loading l10n tables, because of being invoked not through index.php.
$l10n = null;
if (file_exists($config['homedir'].'/include/languages/'.$user_language.'.mo')) {
    $cfr = new CachedFileReader(
        $config['homedir'].'/include/languages/'.$user_language.'.mo'
    );
    $l10n = new gettext_reader($cfr);
    $l10n->load_tables();
}

$column_names = [
    'id_evento',
    'evento',
    'timestamp',
    'estado',
    'event_type',
    'utimestamp',
    'id_agente',
    'agent_name',
    'id_usuario',
    'id_grupo',
    'id_agentmodule',
    'id_alert_am',
    'criticity',
    'user_comment',
    'tags',
    'source',
    'id_extra',
    'critical_instructions',
    'warning_instructions',
    'unknown_instructions',
    'owner_user',
    'ack_utimestamp',
    'custom_data',
    'data',
    'module_status',
];

if (is_metaconsole() === true) {
    $fields = [
        'te.id_evento',
        'te.evento',
        'te.timestamp',
        'te.estado',
        'te.event_type',
        'te.utimestamp',
        'te.id_agente',
        'ta.alias as agent_name',
        'te.id_usuario',
        'te.id_grupo',
        'te.id_agentmodule',
        'te.id_alert_am',
        'te.criticity',
        'te.user_comment',
        'te.tags',
        'te.source',
        'te.id_extra',
        'te.critical_instructions',
        'te.warning_instructions',
        'te.unknown_instructions',
        'te.owner_user',
        'te.ack_utimestamp',
        'te.custom_data',
        'te.data',
        'te.module_status',
        'tg.nombre as group_name',
    ];
} else {
    $fields = [
        'te.id_evento',
        'te.evento',
        'te.timestamp',
        'te.estado',
        'te.event_type',
        'te.utimestamp',
        'te.id_agente',
        'ta.alias as agent_name',
        'te.id_usuario',
        'te.id_grupo',
        'te.id_agentmodule',
        'am.nombre as module_name',
        'te.id_alert_am',
        'te.criticity',
        'te.user_comment',
        'te.tags',
        'te.source',
        'te.id_extra',
        'te.critical_instructions',
        'te.warning_instructions',
        'te.unknown_instructions',
        'te.owner_user',
        'te.ack_utimestamp',
        'te.custom_data',
        'te.data',
        'te.module_status',
        'tg.nombre as group_name',
    ];
}

$now = date('Y-m-d');

// Download header.
header('Content-type: text/txt');
header('Content-Disposition: attachment; filename="export_events_'.$now.'.csv"');
setDownloadCookieToken();

try {
    $fb64 = get_parameter('fb64', null);
    $plain_filter = base64_decode($fb64);
    $filter = json_decode($plain_filter, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        throw new Exception('Invalid filter. ['.$plain_filter.']');
    }

    $filter['csv_all'] = true;

    $names = events_get_column_names($column_names);

    // Dump headers.
    foreach ($names as $n) {
        echo io_safe_output($n).$config['csv_divider'];
    }

    if (is_metaconsole() === true) {
        echo 'server_id'.$config['csv_divider'];
    }

    echo chr(13);

    // Dump events.
    $events_per_step = 1000;
    $step = 0;
    while (1) {
        $events = events_get_all(
            $fields,
            $filter,
            (($step++) * $events_per_step),
            $events_per_step,
            'desc',
            'timestamp'
        );

        if ($events === false || empty($events) === true) {
            break;
        }

        foreach ($events as $row) {
            foreach ($column_names as $val) {
                $key = $val;
                if ($val == 'id_grupo') {
                    $key = 'group_name';
                } else if ($val == 'id_agentmodule') {
                    $key = 'module_name';
                }

                switch ($key) {
                    case 'module_status':
                        echo events_translate_module_status(
                            $row[$key]
                        );
                    break;

                    case 'event_type':
                        echo events_translate_event_type(
                            $row[$key]
                        );
                    break;

                    case 'criticity':
                        echo events_translate_event_criticity(
                            $row[$key]
                        );
                    break;

                    case 'custom_data':
                        $custom_data_array = json_decode(
                            $row[$key],
                            true
                        );

                        $custom_data = '';
                        $separator = ($config['csv_divider'] === ';') ? ',' : ';';

                        if ($custom_data_array !== null) {
                            array_walk(
                                $custom_data_array,
                                function (&$value, $field) use ($separator) {
                                    if (is_array($value) === true) {
                                        $value = '['.implode($separator, $value).']';
                                    }

                                    $value = $field.'='.$value;
                                }
                            );

                            $custom_data = implode($separator, $custom_data_array);
                        }

                        echo io_safe_output($custom_data);
                    break;

                    default:
                        echo io_safe_output($row[$key]);
                    break;
                }

                echo $config['csv_divider'];
            }

            if (is_metaconsole() === true) {
                echo $row['server_id'].$config['csv_divider'];
            }

            echo chr(13);
        }
    }
} catch (Exception $e) {
    echo 'ERROR'.chr(13);
    echo $e->getMessage();
    exit;
}

exit;
