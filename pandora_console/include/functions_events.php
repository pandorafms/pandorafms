<?php
/**
 * Events library.
 *
 * @category   Library
 * @package    Pandora FMS
 * @subpackage Events
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
global $config;

require_once $config['homedir'].'/vendor/autoload.php';

use Amp\Promise;
use PandoraFMS\Enterprise\Metaconsole\Node;
use PandoraFMS\Event;

use function Amp\ParallelFunctions\parallelMap;

require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_tags.php';
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_reporting.php';
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('meta/include/functions_events_meta.php');
enterprise_include_once('meta/include/functions_agents_meta.php');
enterprise_include_once('meta/include/functions_modules_meta.php');
if (is_metaconsole() === true) {
    $id_source_event = get_parameter('id_source_event');
}


/**
 * Translates a numeric value module_status into descriptive text.
 *
 * @param integer $status Module status.
 *
 * @return string Descriptive text.
 */
function events_translate_module_status($status)
{
    switch ($status) {
        case AGENT_MODULE_STATUS_NORMAL:
        return __('NORMAL');

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
        return __('CRITICAL');

        case AGENT_MODULE_STATUS_NO_DATA:
        return __('NOT INIT');

        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
        case AGENT_MODULE_STATUS_NORMAL_ALERT:
        case AGENT_MODULE_STATUS_WARNING_ALERT:
        return __('ALERT');

        case AGENT_MODULE_STATUS_WARNING:
        return __('WARNING');

        default:
        return __('UNKNOWN');
    }
}


/**
 * Translates a numeric value event_type into descriptive text.
 *
 * @param integer $event_type Event type.
 *
 * @return string Descriptive text.
 */
function events_translate_event_type($event_type)
{
    // Event type prepared.
    switch ($event_type) {
        case EVENTS_ALERT_FIRED:
        case EVENTS_ALERT_RECOVERED:
        case EVENTS_ALERT_CEASED:
        case EVENTS_ALERT_MANUAL_VALIDATION:
        return __('ALERT');

        case EVENTS_RECON_HOST_DETECTED:
        case EVENTS_SYSTEM:
        case EVENTS_ERROR:
        case EVENTS_NEW_AGENT:
        case EVENTS_CONFIGURATION_CHANGE:
        return __('SYSTEM');

        case EVENTS_GOING_UP_WARNING:
        case EVENTS_GOING_DOWN_WARNING:
        return __('WARNING');

        case EVENTS_GOING_DOWN_NORMAL:
        case EVENTS_GOING_UP_NORMAL:
        return __('NORMAL');

        case EVENTS_GOING_DOWN_CRITICAL:
        case EVENTS_GOING_UP_CRITICAL:
        return __('CRITICAL');

        case EVENTS_UNKNOWN:
        case EVENTS_GOING_UNKNOWN:
        default:
        return __('UNKNOWN');
    }
}


/**
 * Module status event_type into descriptive text.
 *
 * @param integer $event_type Event type.
 *
 * @return string Module status.
 */
function events_status_module_event_type($event_type)
{
    $module_status = '';
    switch ($event_type) {
        case 'alert_fired':
        case 'alert_recovered':
        case 'alert_ceased':
        case 'alert_manual_validation':
            $module_status = AGENT_MODULE_STATUS_CRITICAL_ALERT;
        break;

        case 'going_down_normal':
        case 'going_up_normal':
            $module_status = AGENT_MODULE_STATUS_NORMAL;
        break;

        case 'going_unknown':
        case 'unknown':
            $module_status = AGENT_MODULE_STATUS_UNKNOWN;
        break;

        case 'going_up_warning':
        case 'going_down_warning':
            $module_status = AGENT_MODULE_STATUS_WARNING;
        break;

        case 'going_up_critical':
        case 'going_down_critical':
            $module_status = AGENT_MODULE_STATUS_CRITICAL_BAD;
        break;

        case 'recon_host_detected':
        case 'system':
        case 'error':
        case 'new_agent':
        case 'configuration_change':
        default:
            $module_status = AGENT_MODULE_STATUS_NOT_INIT;
        break;
    }

    return $module_status;
}


/**
 * Translates a numeric value event_status into descriptive text.
 *
 * @param integer $status Event status.
 *
 * @return string Descriptive text.
 */
function events_translate_event_status($status)
{
    switch ($status) {
        case EVENT_STATUS_NEW:
        default:
        return __('NEW');

        case EVENT_STATUS_INPROCESS:
        return __('IN PROCESS');

        case EVENT_STATUS_VALIDATED:
        return __('VALIDATED');
    }
}


/**
 * Translates a numeric value criticity into descriptive text.
 *
 * @param integer $criticity Event criticity.
 *
 * @return string Descriptive text.
 */
function events_translate_event_criticity($criticity)
{
    switch ($criticity) {
        case EVENT_CRIT_CRITICAL:
        return __('CRITICAL');

        case EVENT_CRIT_MAINTENANCE:
        return __('MAINTENANCE');

        case EVENT_CRIT_INFORMATIONAL:
        return __('INFORMATIONAL');

        case EVENT_CRIT_MAJOR:
        return __('MAJOR');

        case EVENT_CRIT_MINOR:
        return __('MINOR');

        case EVENT_CRIT_NORMAL:
        return __('NORMAL');

        case EVENT_CRIT_WARNING:
        return __('WARNING');

        default:
        return __('UNKNOWN');
    }
}


/**
 * Return all header string for each event field.
 *
 * @return array
 */
function events_get_all_fields()
{
    $columns = [];

    $columns['id_evento'] = __('Event id');
    $columns['evento'] = __('Event name');
    $columns['id_agente'] = __('Agent name');
    $columns['id_usuario'] = __('User');
    $columns['id_grupo'] = __('Group');
    $columns['estado'] = __('Status');
    $columns['timestamp'] = __('Timestamp');
    $columns['event_type'] = __('Event type');
    $columns['id_agentmodule'] = __('Agent module');
    $columns['id_alert_am'] = __('Alert');
    $columns['criticity'] = __('Severity');
    $columns['user_comment'] = __('Comment');
    $columns['tags'] = __('Tags');
    $columns['source'] = __('Source');
    $columns['id_extra'] = __('Extra id');
    $columns['owner_user'] = __('Owner');
    $columns['ack_utimestamp'] = __('ACK Timestamp');
    $columns['instructions'] = __('Instructions');
    $columns['server_name'] = __('Server name');
    $columns['data'] = __('Data');
    $columns['module_status'] = __('Module status');
    $columns['module_custom_id'] = __('Module custom id');
    $columns['custom_data'] = __('Custom data');
    $columns['event_custom_id'] = __('Event Custom ID');

    return $columns;
}


/**
 * Same as events_get_column_names but retrieving only one result.
 *
 * @param string $field Raw field name.
 *
 * @return string Traduction.
 */
function events_get_column_name($field, $table_alias=false)
{
    switch ($field) {
        case 'id_evento':
        return __('Event Id');

        case 'evento':
        return __('Event Name');

        case 'id_agente':
        return __('Agent ID');

        case 'agent_name':
        return __('Agent name');

        case 'agent_alias':
        return __('Agent alias');

        case 'id_usuario':
        return __('User');

        case 'id_grupo':
        return __('Group');

        case 'estado':
        return __('Status');

        case 'timestamp':
        return __('Timestamp');

        case 'event_type':
        return __('Event Type');

        case 'id_agentmodule':
        return __('Module Name');

        case 'id_alert_am':
        return __('Alert');

        case 'criticity':
        return __('Severity');

        case 'user_comment':
        return __('Comment');

        case 'tags':
        return __('Tags');

        case 'source':
        return __('Source');

        case 'id_extra':
        return __('Extra Id');

        case 'owner_user':
        return __('Owner');

        case 'ack_utimestamp':
        return __('ACK Timestamp');

        case 'instructions':
        return __('Instructions');

        case 'server_name':
        return __('Server Name');

        case 'data':
        return __('Data');

        case 'module_status':
        return __('Module Status');

        case 'module_custom_id':
        return __('Module custom ID');

        case 'options':
        return __('Options');

        case 'mini_severity':
            if ($table_alias === true) {
                return 'S';
            } else {
                return __('Severity mini');
            }

        case 'direccion':
        return __('Agent IP');

        case 'custom_data':
        return __('Custom data');

        case 'event_custom_id':
        return __('Event Custom ID');

        default:
        return __($field);
    }
}


/**
 * Return column names from fields selected.
 *
 * @param array $fields Array of fields.
 *
 * @return array Names array.
 */
function events_get_column_names($fields, $table_alias=false)
{
    if (isset($fields) === false
        || is_array($fields) === false
    ) {
        return [];
    }

    $names = [];
    foreach ($fields as $f) {
        if (is_array($f) === true) {
            $name = [];
            $name['text'] = events_get_column_name($f['text'], $table_alias);
            $name['class'] = ($f['class'] ?? '');
            $name['style'] = ($f['style'] ?? '');
            $name['extra'] = ($f['extra'] ?? '');
            $name['id'] = ($f['id'] ?? '');
            $names[] = $name;
        } else {
            $names[] = events_get_column_name($f, $table_alias);
        }
    }

    return $names;

}


/**
 * Validates all events matching target filter.
 *
 * @param integer $id_evento  Master event.
 * @param array   $filter     Optional. Filter options.
 * @param boolean $history    Apply on historical table.
 * @param boolean $force_node Force node table.
 *
 * @return integer Events validated or false if error.
 */
function events_delete($id_evento, $filter=null, $history=false, $force_node=false)
{
    if (isset($id_evento) === false
        || $id_evento <= 0
    ) {
        return false;
    }

    if (isset($filter) === false
        || is_array($filter) === false
    ) {
        $filter = ['group_rep' => EVENT_GROUP_REP_ALL];
    }

    switch ($filter['group_rep']) {
        case EVENT_GROUP_REP_ALL:
        case EVENT_GROUP_REP_AGENTS:
        default:
            // No groups option direct update.
            $delete_sql = sprintf(
                'DELETE FROM tevento
                 WHERE id_evento = %d',
                $id_evento
            );
        break;

        case EVENT_GROUP_REP_EVENTS:
        case EVENT_GROUP_REP_EXTRAIDS:
            // Group by events.
            $sql = events_get_all(
                ['te.*'],
                $filter,
                // Offset.
                null,
                // Limit.
                null,
                // Order.
                null,
                // Sort_field.
                null,
                // Historical table.
                $history,
                // Return_sql.
                true
            );

            if ((int) $filter['group_rep'] === EVENT_GROUP_REP_EXTRAIDS) {
                $sql = sprintf(
                    'SELECT tu.id_evento FROM tevento tu INNER JOIN ( %s ) tf
                    ON tu.id_extra = tf.id_extra
                    AND tf.max_id_evento = %d',
                    $sql,
                    $id_evento
                );
            } else {
                $sql = sprintf(
                    'SELECT tu.id_evento FROM tevento tu INNER JOIN ( %s ) tf
                    ON tu.estado = tf.estado
                    AND tu.evento = tf.evento
                    AND tu.id_agente = tf.id_agente
                    AND tu.id_agentmodule = tf.id_agentmodule
                    AND tf.max_id_evento = %d',
                    $sql,
                    $id_evento
                );
            }

            $target_ids = db_get_all_rows_sql($sql);

            // Try to avoid deadlock while updating full set.
            if ($target_ids !== false && count($target_ids) > 0) {
                $target_ids = array_reduce(
                    $target_ids,
                    function ($carry, $item) {
                        $carry[] = $item['id_evento'];
                        return $carry;
                    }
                );

                $delete_sql = sprintf(
                    'DELETE FROM tevento WHERE id_evento IN (%s)',
                    join(', ', $target_ids)
                );
            }
        break;
    }

    return db_process_sql($delete_sql);
}


/**
 * Validates all events matching target filter.
 *
 * @param integer $id_evento Master event.
 * @param integer $status    Target status.
 * @param array   $filter    Optional. Filter options.
 *
 * @return integer Events validated or false if error.
 */
function events_update_status($id_evento, $status, $filter=null)
{
    global $config;

    if (!$status && $status !== 0) {
        return false;
    }

    if (isset($id_evento) === false || $id_evento <= 0) {
        return false;
    }

    if (isset($filter) === false || is_array($filter) === false) {
        $filter = ['group_rep' => EVENT_GROUP_REP_ALL];
    }

    switch ($filter['group_rep']) {
        case EVENT_GROUP_REP_ALL:
        case EVENT_GROUP_REP_AGENTS:
        default:
            // No groups option direct update.
            $update_sql = sprintf(
                'UPDATE tevento
                 SET estado = %d,
                    ack_utimestamp = %d,
                    id_usuario = "%s"
                 WHERE id_evento = %d',
                $status,
                time(),
                $config['id_user'],
                $id_evento
            );
        break;

        case EVENT_GROUP_REP_EVENTS:
        case EVENT_GROUP_REP_EXTRAIDS:
            // Group by events.
            $sql = events_get_all(
                ['te.*'],
                $filter,
                // Offset.
                null,
                // Limit.
                null,
                // Order.
                null,
                // Sort_field.
                null,
                // Historical table.
                false,
                // Return_sql.
                true
            );

            if ((int) $filter['group_rep'] === EVENT_GROUP_REP_EXTRAIDS) {
                $sql = sprintf(
                    'SELECT tu.id_evento FROM tevento tu INNER JOIN ( %s ) tf
                    ON tu.id_extra = tf.id_extra
                    AND tf.max_id_evento = %d',
                    $sql,
                    $id_evento
                );
            } else {
                $sql = sprintf(
                    'SELECT tu.id_evento FROM tevento tu INNER JOIN ( %s ) tf
                    ON tu.estado = tf.estado
                    AND tu.evento = tf.evento
                    AND tu.id_agente = tf.id_agente
                    AND tu.id_agentmodule = tf.id_agentmodule
                    AND tf.max_id_evento = %d',
                    $sql,
                    $id_evento
                );
            }

            $target_ids = db_get_all_rows_sql($sql);

            // Try to avoid deadlock while updating full set.
            if ($target_ids !== false && count($target_ids) > 0) {
                $target_ids = array_reduce(
                    $target_ids,
                    function ($carry, $item) {
                        $carry[] = $item['id_evento'];
                        return $carry;
                    }
                );

                $update_sql = sprintf(
                    'UPDATE tevento
                    SET estado = %d,
                        ack_utimestamp = %d,
                        id_usuario = "%s"
                    WHERE id_evento IN (%s)',
                    $status,
                    time(),
                    $config['id_user'],
                    join(',', $target_ids)
                );
            }
        break;
    }

    $result = db_process_sql($update_sql);

    if ($result !== false) {
        switch ($status) {
            case EVENT_STATUS_NEW:
                $status_string = 'New';
            break;

            case EVENT_STATUS_VALIDATED:
                events_change_owner(
                    $id_evento,
                    $config['id_user'],
                    false
                );

                $status_string = 'Validated';
            break;

            case EVENT_STATUS_INPROCESS:
                $status_string = 'In process';
            break;

            default:
                $status_string = '';
            break;
        }

        events_comment(
            $id_evento,
            '',
            'Change status to '.$status_string
        );
    }

    return $result;
}


/**
 * Get filter time.
 *
 * @param array $filter Filters.
 *
 * @return array conditions.
 */
function get_filter_date(array $filter)
{
    $sql_filters = [];
    if (isset($filter['date_from']) === true
        && empty($filter['date_from']) === false
        && $filter['date_from'] !== '0000-00-00'
    ) {
        $date_from = $filter['date_from'];
    }

    if (isset($filter['time_from']) === true) {
        $time_from = (empty($filter['time_from']) === true) ? '00:00:00' : $filter['time_from'];
    }

    if (isset($date_from) === true) {
        if (isset($time_from) === false) {
            $time_from = '00:00:00';
        }

        $from = $date_from.' '.$time_from;
        $sql_filters[] = sprintf(
            ' AND te.utimestamp >= %d',
            strtotime($from)
        );
    }

    if (isset($filter['date_to']) === true
        && empty($filter['date_to']) === false
        && $filter['date_to'] !== '0000-00-00'
    ) {
        $date_to = $filter['date_to'];
    }

    if (isset($filter['time_to']) === true) {
        $time_to = (empty($filter['time_to']) === true) ? '23:59:59' : $filter['time_to'];
    }

    if (isset($date_to) === true) {
        if (isset($time_to) === false) {
            $time_to = '23:59:59';
        }

        $to = $date_to.' '.$time_to;
        $sql_filters[] = sprintf(
            ' AND te.utimestamp <= %d',
            strtotime($to)
        );
    }

    if (isset($from) === false) {
        if (isset($filter['event_view_hr']) === true && ($filter['event_view_hr'] > 0)) {
            $sql_filters[] = sprintf(
                ' AND te.utimestamp > UNIX_TIMESTAMP(now() - INTERVAL %d HOUR) ',
                $filter['event_view_hr']
            );
        }
    }

    return $sql_filters;
}


/**
 * Retrieve all events filtered.
 *
 * @param array   $fields          Fields to retrieve.
 * @param array   $filter          Filters to be applied.
 * @param integer $offset          Offset (pagination).
 * @param integer $limit           Limit (pagination).
 * @param string  $order           Sort order.
 * @param string  $sort_field      Sort field.
 * @param boolean $history         Apply on historical table.
 * @param boolean $return_sql      Return SQL (true) or execute it (false).
 * @param string  $having          Having filter.
 * @param boolean $validatedEvents If true, evaluate validated events.
 * @param boolean $recursiveGroups If true, filtered groups and their children
 *                                 will be search.
 * @param boolean $nodeConnected   Already connected to node (uses tevento).
 *
 * Available filters:
 *  [
 *     'date_from'
 *     'time_from'
 *     'date_to'
 *     'time_to'
 *     'event_view_hr'
 *     'id_agent'
 *     'event_type'
 *     'severity'
 *     'id_group_filter'
 *     'status'
 *     'agent_alias'
 *     'search'
 *     'not_search'
 *     'id_extra'
 *     'id_source_event'
 *     'user_comment'
 *     'source'
 *     'id_user_ack'
 *     'owner_user'
 *     'tag_with'
 *     'tag_without'
 *     'filter_only_alert'
 *     'search_secondary_groups'
 *     'search_recursive_groups'
 *     'module_search'
 *     'group_rep'
 *     'server_id'
 *  ].
 *
 * @return array Events.
 * @throws Exception On error.
 */
function events_get_all(
    $fields,
    array $filter,
    $offset=null,
    $limit=null,
    $order=null,
    $sort_field=null,
    $history=false,
    $return_sql=false,
    $having='',
    $validatedEvents=false,
    $recursiveGroups=true,
    $nodeConnected=false
) {
    global $config;

    $user_is_admin = users_is_admin();

    if (is_array($filter) === false) {
        error_log('[events_get_all] Filter must be an array.');
        throw new Exception('[events_get_all] Filter must be an array.');
    }

    $count = false;
    if (is_array($fields) === false && $fields === 'count'
        || (is_array($fields) === true && $fields[0] === 'count')
    ) {
        $fields = ['te.*'];
        $count = true;
    } else if (is_array($fields) === false) {
        error_log('[events_get_all] Fields must be an array or "count".');
        throw new Exception(
            '[events_get_all] Fields must be an array or "count".'
        );
    }

    $sql_filters = get_filter_date($filter);

    if (isset($filter['id_event']) === true && $filter['id_event'] > 0) {
        $sql_filters[] = sprintf(
            ' AND te.id_evento = %d ',
            $filter['id_event']
        );
    }

    if (isset($filter['id_agent']) === true && $filter['id_agent'] > 0) {
        $sql_filters[] = sprintf(
            ' AND te.id_agente = %d ',
            $filter['id_agent']
        );
    }

    if (isset($filter['id_agentmodule']) === true && $filter['id_agentmodule'] > 0) {
        $sql_filters[] = sprintf(
            ' AND te.id_agentmodule = %d ',
            $filter['id_agentmodule']
        );
    }

    if (empty($filter['event_type']) === false && $filter['event_type'] !== 'all') {
        if (is_array($filter['event_type']) === true) {
            $type = [];
            if (in_array('all', $filter['event_type']) === false) {
                foreach ($filter['event_type'] as $event_type) {
                    if ($event_type != '') {
                        // If normal, warning, could be several
                        // (going_up_warning, going_down_warning... too complex.
                        // Shown to user only "warning, critical and normal".
                        if ($event_type == 'warning' || $event_type == 'critical' || $event_type == 'normal') {
                            $type[] = " event_type LIKE '%".$event_type."%' ";
                        } else if ($event_type == 'not_normal') {
                            $type[] = " (event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%') ";
                        } else if ($event_type != 'all') {
                            $type[] = " event_type = '".$event_type."'";
                        }
                    }
                }

                $sql_filters[] = ' AND ('.implode(' OR ', $type).')';
            }
        } else {
            if ($filter['event_type'] === 'warning'
                || $filter['event_type'] === 'critical'
                || $filter['event_type'] === 'normal'
            ) {
                $sql_filters[] = ' AND event_type LIKE "%'.$filter['event_type'].'%"';
            } else if ($filter['event_type'] === 'not_normal') {
                $sql_filters[] = ' AND (event_type LIKE "%warning%"
              OR event_type LIKE "%critical%"
              OR event_type LIKE "%unknown%")';
            } else {
                $sql_filters[] = ' AND event_type = "'.$filter['event_type'].'"';
            }
        }
    }

    if (isset($filter['severity']) === true && $filter['severity'] !== '' && (int) $filter['severity'] > -1) {
        if (is_array($filter['severity']) === true) {
            if (in_array(-1, $filter['severity']) === false) {
                $not_normal = array_search(EVENT_CRIT_NOT_NORMAL, $filter['severity']);
                if ($not_normal !== false) {
                    unset($filter['severity'][$not_normal]);
                    $sql_filters[] = sprintf(
                        ' AND criticity != %d',
                        EVENT_CRIT_NORMAL
                    );
                } else {
                    $critical_warning = array_search(EVENT_CRIT_WARNING_OR_CRITICAL, $filter['severity']);
                    if ($critical_warning !== false) {
                        unset($filter['severity'][$critical_warning]);
                        $filter['severity'][] = EVENT_CRIT_WARNING;
                        $filter['severity'][] = EVENT_CRIT_CRITICAL;
                    }

                    $critical_normal = array_search(EVENT_CRIT_OR_NORMAL, $filter['severity']);
                    if ($critical_normal !== false) {
                        unset($filter['severity'][$critical_normal]);
                        $filter['severity'][] = EVENT_CRIT_NORMAL;
                        $filter['severity'][] = EVENT_CRIT_CRITICAL;
                    }

                    if (empty($filter['severity']) === false) {
                        $filter['severity'] = implode(',', $filter['severity']);
                        $sql_filters[] = sprintf(
                            ' AND criticity IN (%s)',
                            $filter['severity']
                        );
                    }
                }
            }
        } else {
            switch ($filter['severity']) {
                case EVENT_CRIT_MAINTENANCE:
                case EVENT_CRIT_INFORMATIONAL:
                case EVENT_CRIT_NORMAL:
                case EVENT_CRIT_MINOR:
                case EVENT_CRIT_WARNING:
                case EVENT_CRIT_MAJOR:
                case EVENT_CRIT_CRITICAL:
                default:
                    $sql_filters[] = sprintf(
                        ' AND criticity = %d ',
                        $filter['severity']
                    );
                break;

                case EVENT_CRIT_WARNING_OR_CRITICAL:
                    $sql_filters[] = sprintf(
                        ' AND (criticity = %d OR criticity = %d)',
                        EVENT_CRIT_WARNING,
                        EVENT_CRIT_CRITICAL
                    );
                break;

                case EVENT_CRIT_NOT_NORMAL:
                    $sql_filters[] = sprintf(
                        ' AND criticity != %d',
                        EVENT_CRIT_NORMAL
                    );
                break;

                case EVENT_CRIT_OR_NORMAL:
                    $sql_filters[] = sprintf(
                        ' AND (criticity = %d OR criticity = %d)',
                        EVENT_CRIT_NORMAL,
                        EVENT_CRIT_CRITICAL
                    );
                break;
            }
        }
    }

    $groups = false;
    $filter_groups = false;
    if (isset($filter['id_group_filter']) === true
        && empty($filter['id_group_filter']) === false
    ) {
        $filter_groups = true;
        $groups = $filter['id_group_filter'];
    } else if ((bool) $user_is_admin === false) {
        $groups = array_keys(users_get_groups(false, 'AR'));
    }

    if (isset($groups) === true
        && (is_array($groups) === true || ($groups > 0))
    ) {
        if ($recursiveGroups === true
            || (isset($filter['search_recursive_groups']) === true
            && (bool) $filter['search_recursive_groups'] === true)
        ) {
            // Add children groups.
            $children = [];
            if (is_array($groups) === true) {
                foreach ($groups as $g) {
                    $children = array_merge(
                        groups_get_children($g),
                        $children
                    );
                }
            } else {
                $children = groups_get_children($groups);
            }

            if (is_array($groups) === true) {
                $_groups = $groups;
            } else {
                $_groups = [ $groups ];
            }

            if (empty($children) === false) {
                foreach ($children as $child) {
                    $_groups[] = (int) $child['id_grupo'];
                }
            }

            if ((bool) $user_is_admin === false) {
                $user_groups = users_get_groups(false, 'AR');
                $_groups = array_intersect(
                    $_groups,
                    array_keys($user_groups)
                );
            }

            $groups = $_groups;
        }

        if (is_array($groups) === false) {
            $groups = [ $groups ];
        }

        if ((bool) $filter['search_secondary_groups'] === true) {
            $sql_filters[] = sprintf(
                ' AND (te.id_grupo IN (%s) OR tasg.id_group IN (%s))',
                join(',', $groups),
                join(',', $groups)
            );
        } else {
            $sql_filters[] = sprintf(
                ' AND te.id_grupo IN (%s)',
                join(',', $groups)
            );
        }
    }

    // Skip system messages if user is not PM.
    if (!check_acl($config['id_user'], 0, 'PM')) {
        $sql_filters[] = ' AND te.id_grupo != 0 ';
    }

    if (isset($filter['status']) === true) {
        if (is_array($filter['status']) === true) {
            $status_all = 0;
            foreach ($filter['status'] as $key => $value) {
                switch ($value) {
                    case EVENT_ALL:
                        $status_all = 1;
                    break;

                    case EVENT_NO_VALIDATED:
                        $filter['status'][$key] = (EVENT_NEW.', '.EVENT_PROCESS);

                    case EVENT_NO_PROCESS:
                            $filter['status'][$key] = (EVENT_NEW.', '.EVENT_VALIDATE);
                    default:
                        // Ignore.
                    break;
                }
            }

            if ($status_all === 0) {
                $sql_filters[] = sprintf(
                    ' AND estado IN (%s)',
                    implode(', ', $filter['status'])
                );
            }
        } else {
            switch ($filter['status']) {
                case EVENT_ALL:
                default:
                    // Do not filter.
                break;

                case EVENT_NEW:
                case EVENT_VALIDATE:
                case EVENT_PROCESS:
                    $sql_filters[] = sprintf(
                        ' AND estado = %d',
                        $filter['status']
                    );
                break;

                case EVENT_NO_VALIDATED:
                    // Show comments in validated events.
                    $validatedState = '';
                    if ($validatedEvents === true) {
                        $validatedState = sprintf(
                            'OR estado = %d',
                            EVENT_VALIDATE
                        );
                    }

                    $sql_filters[] = sprintf(
                        ' AND (estado = %d OR estado = %d %s)',
                        EVENT_NEW,
                        EVENT_PROCESS,
                        $validatedState
                    );
                break;

                case EVENT_NO_PROCESS:
                    // Show comments in validated events.
                    $validatedState = '';
                    if ($validatedEvents === true) {
                        $validatedState = sprintf(
                            'OR estado = %d',
                            EVENT_VALIDATE
                        );
                    }

                    $sql_filters[] = sprintf(
                        ' AND (estado = %d OR estado = %d %s)',
                        EVENT_NEW,
                        EVENT_VALIDATE,
                        $validatedState
                    );
                break;
            }
        }
    }

    if (!$user_is_admin && users_can_manage_group_all('ER') === false) {
        $ER_groups = users_get_groups($config['id_user'], 'ER', true);
        $EM_groups = users_get_groups($config['id_user'], 'EM', true, true);
        $EW_groups = users_get_groups($config['id_user'], 'EW', true, true);

        // Get groups where user have ER grants.
        if ((bool) $filter['search_secondary_groups'] === true) {
            $sql_filters[] = sprintf(
                ' AND (te.id_grupo IN ( %s ) OR tasg.id_group IN (%s))',
                join(', ', array_keys($ER_groups)),
                join(', ', array_keys($ER_groups))
            );
        } else {
            $sql_filters[] = sprintf(
                ' AND te.id_grupo IN ( %s )',
                join(', ', array_keys($ER_groups))
            );
        }
    }

    // Prepare agent join sql filters.
    $table = 'tevento';
    $tevento = 'tevento te';
    $tagente_table = 'tagente';
    $tagente_field = 'id_agente';
    $conditionMetaconsole = '';

    // Agent alias.
    if (empty($filter['agent_alias']) === false) {
        $sql_filters[] = sprintf(
            ' AND ta.alias = "%s" ',
            $filter['agent_alias']
        );
    }

    // Free search.
    if (empty($filter['search']) === false && (bool) $filter['regex'] === false) {
        if (isset($config['dbconnection']->server_version) === true
            && $config['dbconnection']->server_version > 50600
        ) {
            // Use "from_base64" requires mysql 5.6 or greater.
            $custom_data_search = 'from_base64(te.custom_data)';
        } else {
            // Custom data is JSON encoded base64, if 5.6 or lower,
            // user is condemned to use plain search.
            $custom_data_search = 'te.custom_data';
        }

        $not_search = '';
        $nexo = 'OR';
        $array_search = [
            'te.id_evento',
            'lower(te.evento)',
            'lower(te.id_extra)',
            'lower(te.source)',
            'lower('.$custom_data_search.')',
        ];
        if (isset($filter['not_search']) === true
            && empty($filter['not_search']) === false
        ) {
            $not_search = 'NOT';
            $nexo = 'AND';
        } else {
            $array_search[] = 'lower(ta.alias)';
        }

        // Disregard repeated whitespaces when searching.
        $collapsed_spaces_search = preg_replace('/(&#x20;)+/', '&#x20;', $filter['search']);

        $sql_search = ' AND (';
        foreach ($array_search as $key => $field) {
            $sql_search .= sprintf(
                '%s LOWER(REGEXP_REPLACE(%s, "(&#x20;)+", "&#x20;")) %s like LOWER("%%%s%%")',
                ($key === 0) ? '' : $nexo,
                $field,
                $not_search,
                $collapsed_spaces_search
            );
            $sql_search .= ' ';
        }

        $sql_search .= ' )';

        $sql_filters[] = $sql_search;
    }

    // Free search exclude.
    if (empty($filter['search_exclude']) === false) {
        $sql_filters[] = vsprintf(
            ' AND (lower(ta.alias) not like lower("%%%s%%")
                AND te.id_evento not like "%%%s%%"
                AND lower(te.evento) not like lower("%%%s%%")
                AND lower(te.id_extra) not like lower("%%%s%%")
                AND lower(te.source) not like lower("%%%s%%") )',
            array_fill(0, 6, $filter['search_exclude'])
        );
    }

    // Id extra.
    if (empty($filter['id_extra']) === false) {
        $sql_filters[] = sprintf(
            ' AND lower(te.id_extra) like lower("%%%s%%") ',
            $filter['id_extra']
        );
    }

    // User comment.
    $event_comment_join = '';
    if (empty($filter['user_comment']) === false) {
        $event_comment_join = 'INNER JOIN tevent_comment ON te.id_evento = tevent_comment.id_event';
        $sql_filters[] = sprintf(
            ' AND (lower(tevent_comment.comment) like lower("%%%s%%")
                OR lower(tevent_comment.comment) like lower("%%%s%%"))',
            io_safe_input($filter['user_comment']),
            $filter['user_comment']
        );
    }

    // Source.
    if (empty($filter['source']) === false) {
        $sql_filters[] = sprintf(
            ' AND lower(te.source) like lower("%%%s%%") ',
            $filter['source']
        );
    }

    // Custom data.
    if (empty($filter['custom_data']) === false) {
        if (isset($config['dbconnection']->server_version) === true
            && $config['dbconnection']->server_version > 80000
        ) {
            if ($filter['custom_data_filter_type'] === '1') {
                $sql_filters[] = sprintf(
                    ' AND JSON_VALID(custom_data) = 1
                    AND (JSON_EXTRACT(custom_data, "$.*") LIKE lower("%%%s%%") COLLATE utf8mb4_0900_ai_ci) ',
                    io_safe_output_html($filter['custom_data'])
                );
            } else {
                $sql_filters[] = sprintf(
                    ' AND JSON_VALID(custom_data) = 1
                    AND (JSON_SEARCH(JSON_KEYS(custom_data), "all", lower("%%%s%%") COLLATE utf8mb4_0900_ai_ci) IS NOT NULL) ',
                    io_safe_output_html($filter['custom_data'])
                );
            }
        } else {
            if ($filter['custom_data_filter_type'] === '1') {
                $sql_filters[] = sprintf(
                    ' AND JSON_VALID(custom_data) = 1
                    AND cast(JSON_EXTRACT(custom_data, "$.*") as CHAR) LIKE lower("%%%s%%") ',
                    io_safe_output($filter['custom_data'])
                );
            } else {
                $sql_filters[] = sprintf(
                    ' AND JSON_VALID(custom_data) = 1
                    AND cast(JSON_KEYS(custom_data) as CHAR) REGEXP "%s" ',
                    io_safe_output($filter['custom_data'])
                );
            }
        }
    }

    // Validated or in process by.
    if (empty($filter['id_user_ack']) === false) {
        $sql_filters[] = sprintf(
            ' AND te.id_usuario like lower("%%%s%%") ',
            $filter['id_user_ack']
        );
    }

    // Owner by.
    if (empty($filter['owner_user']) === false) {
        $sql_filters[] = sprintf(
            ' AND te.owner_user like lower("%%%s%%") ',
            $filter['owner_user']
        );
    }

    $tag_names = [];
    // With following tags.
    if (empty($filter['tag_with']) === false) {
        $tag_with = base64_decode($filter['tag_with']);
        $tags = json_decode($tag_with, true);
        if (is_array($tags) === true && in_array('0', $tags) === false) {
            if (!$user_is_admin) {
                $getUserTags = tags_get_tags_for_module_search();
                // Prevent false value for array_flip.
                if ($getUserTags === false) {
                    $getUserTags = [];
                }

                $user_tags = array_flip($getUserTags);
                if ($user_tags != null) {
                    foreach ($tags as $id_tag) {
                        // User cannot filter with those tags.
                        if (array_search($id_tag, $user_tags) === false) {
                            return false;
                        }
                    }
                }
            }

            $_tmp = '';
            foreach ($tags as $id_tag) {
                if (isset($tags_names[$id_tag]) === false) {
                    $tags_names[$id_tag] = tags_get_name($id_tag);
                }

                if ($tags[0] === $id_tag) {
                    $_tmp .= ' AND (( ';
                } else {
                    $_tmp .= ' AND ( ';
                }

                $_tmp .= sprintf(
                    ' tags LIKE "%s" OR',
                    $tags_names[$id_tag]
                );

                $_tmp .= sprintf(
                    ' tags LIKE "%s,%%" OR',
                    $tags_names[$id_tag]
                );

                $_tmp .= sprintf(
                    ' tags LIKE "%%,%s" OR',
                    $tags_names[$id_tag]
                );

                $_tmp .= sprintf(
                    ' tags LIKE "%%,%s,%%" ',
                    $tags_names[$id_tag]
                );

                if ($tags[0] === $id_tag) {
                    $_tmp .= ')) ';
                } else {
                    $_tmp .= ') ';
                }
            }

            $sql_filters[] = $_tmp;
        }
    }

    // Without following tags.
    if (empty($filter['tag_without']) === false) {
        $tag_without = base64_decode($filter['tag_without']);
        $tags = json_decode($tag_without, true);
        if (is_array($tags) === true && in_array('0', $tags) === false) {
            if (!$user_is_admin) {
                $tags_module_search = tags_get_tags_for_module_search();
                if ($tags_module_search === false) {
                    $tags_module_search = [];
                }

                $user_tags = array_flip($tags_module_search);
                if ($user_tags != null) {
                    foreach ($tags as $key_tag => $id_tag) {
                        // User cannot filter with those tags.
                        if (!array_search($id_tag, $user_tags)) {
                            unset($tags[$key_tag]);
                            continue;
                        }
                    }
                }
            }

            foreach ($tags as $id_tag) {
                if (isset($tags_names[$id_tag]) === false) {
                    $tags_names[$id_tag] = tags_get_name($id_tag);
                }

                $_tmp .= sprintf(
                    ' AND tags NOT LIKE "%s" ',
                    $tags_names[$id_tag]
                );
                $_tmp .= sprintf(
                    ' AND tags NOT LIKE "%s,%%" ',
                    $tags_names[$id_tag]
                );
                $_tmp .= sprintf(
                    ' AND tags NOT LIKE "%%,%s" ',
                    $tags_names[$id_tag]
                );
                $_tmp .= sprintf(
                    ' AND tags NOT LIKE "%%,%s,%%" ',
                    $tags_names[$id_tag]
                );
            }

            $sql_filters[] = $_tmp;
        }
    }

    // Filter/ Only alerts.
    if (isset($filter['filter_only_alert']) === true) {
        if ($filter['filter_only_alert'] == 0) {
            $sql_filters[] = ' AND event_type NOT LIKE "%alert%"';
        } else if ($filter['filter_only_alert'] == 1) {
            $sql_filters[] = ' AND event_type LIKE "%alert%"';
        }
    }

    $user_admin_group_all = ($user_is_admin && $groups == 0) ? '' : 'tasg.';

    // TAgs ACLS.
    if (check_acl($config['id_user'], 0, 'ER')) {
        $tags_acls_condition = tags_get_acl_tags(
            // Id_user.
            $config['id_user'],
            // Id_group.
            ($ER_groups ?? ''),
            // Access.
            'ER',
            // Return_mode.
            'event_condition',
            // Query_prefix.
            'AND',
            // Query_table.
            '',
            // Meta.
            is_metaconsole() && $nodeConnected === false,
            // Childrens_ids.
            [],
            // Force_group_and_tag.
            true,
            // Table tag for id_grupo.
            'te.',
            // Alt table tag for id_grupo.
            $user_admin_group_all,
            (bool) (isset($filter['search_secondary_groups']) === true) ? $filter['search_secondary_groups'] : false
        );
        // FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)".
    } else if (check_acl($config['id_user'], 0, 'EW')) {
        $tags_acls_condition = tags_get_acl_tags(
            // Id_user.
            $config['id_user'],
            // Id_group.
            $EW_groups,
            // Access.
            'EW',
            // Return_mode.
            'event_condition',
            // Query_prefix.
            'AND',
            // Query_table.
            '',
            // Meta.
            is_metaconsole() && $nodeConnected === false,
            // Childrens_ids.
            [],
            // Force_group_and_tag.
            true,
            // Table tag for id_grupo.
            'te.',
            // Alt table tag for id_grupo.
            $user_admin_group_all,
            (bool) (isset($filter['search_secondary_groups']) === true) ? $filter['search_secondary_groups'] : false
        );
        // FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)".
    } else if (check_acl($config['id_user'], 0, 'EM')) {
        $tags_acls_condition = tags_get_acl_tags(
            // Id_user.
            $config['id_user'],
            // Id_group.
            $EM_groups,
            // Access.
            'EM',
            // Return_mode.
            'event_condition',
            // Query_prefix.
            'AND',
            // Query_table.
            '',
            // Meta.
            is_metaconsole() && $nodeConnected === false,
            // Childrens_ids.
            [],
            // Force_group_and_tag.
            true,
            // Table tag for id_grupo.
            'te.',
            // Alt table tag for id_grupo.
            $user_admin_group_all,
            (bool) (isset($filter['search_secondary_groups']) === true) ? $filter['search_secondary_groups'] : false
        );
        // FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)".
    }

    if (($tags_acls_condition != ERR_WRONG_PARAMETERS)
        && ($tags_acls_condition != ERR_ACL)
    ) {
        $sql_filters[] = $tags_acls_condition;
    }

    // Module search.
    $agentmodule_join = 'LEFT JOIN tagente_modulo am ON te.id_agentmodule = am.id_agente_modulo';
    if (empty($filter['module_search']) === false) {
        $agentmodule_join = 'INNER JOIN tagente_modulo am ON te.id_agentmodule = am.id_agente_modulo';
        $sql_filters[] = sprintf(
            ' AND am.nombre = "%s" ',
            $filter['module_search']
        );
    }

    // Order.
    $order_by = '';
    if (isset($order, $sort_field) === true) {
        if (isset($filter['group_rep']) === true
            && $filter['group_rep'] === EVENT_GROUP_REP_EVENTS
            && $filter['group_rep'] === EVENT_GROUP_REP_EXTRAIDS
        ) {
            $order_by = events_get_sql_order('MAX('.$sort_field.')', $order);
        } else {
            $order_by = events_get_sql_order($sort_field, $order);
        }
    }

    // Id server.
    $id_server = 0;
    if (empty($filter['id_server']) === false) {
        $id_server = $filter['id_server'];
    } else if (empty($filter['server_id']) === false) {
        $id_server = $filter['server_id'];
    }

    // Pagination.
    $pagination = '';
    if (is_metaconsole() === true
        && (empty($id_server) === true || is_array($id_server) === true)
        && isset($filter['csv_all']) === false
    ) {
        // TODO: XXX TIP. captura el error.
        $pagination = sprintf(
            ' LIMIT  %d',
            $config['max_number_of_events_per_node']
        );
    } else if (isset($limit, $offset) === true && empty($limit) === false && $limit > 0) {
        $pagination = sprintf(' LIMIT %d OFFSET %d', $limit, $offset);
    }

    // Group by.
    $group_by = 'GROUP BY ';
    $tagente_join = 'LEFT';
    if (isset($filter['group_rep']) === false) {
        $filter['group_rep'] = EVENT_GROUP_REP_ALL;
    }

    switch ($filter['group_rep']) {
        case EVENT_GROUP_REP_ALL:
        default:
            // All events.
            $group_by = '';
        break;

        case EVENT_GROUP_REP_EVENTS:
            // Group by events.
            $group_by .= 'te.evento, te.id_agente, te.id_agentmodule';
        break;

        case EVENT_GROUP_REP_AGENTS:
            // Group by agents.
            $tagente_join = 'INNER';
            $group_by = '';
            $order_by = events_get_sql_order('te.id_agente', 'asc');
            if (isset($order, $sort_field) === true) {
                $order_by .= ','.events_get_sql_order(
                    $sort_field,
                    $order,
                    0,
                    true
                );
            }
        break;

        case EVENT_GROUP_REP_EXTRAIDS:
            // Group by events and ignore null.
            $sql_filters[] = 'AND te.id_extra IS NOT NULL AND te.id_extra <> ""';
            $group_by .= 'te.id_extra';
        break;
    }

    $tgrupo_join = 'LEFT';
    $tgrupo_join_filters = [];

    if (isset($groups) === true
        && (is_array($groups) === true
        || $groups > 0)
    ) {
        if ($filter_groups === true) {
            $tgrupo_join = 'INNER';
        }

        if (is_array($groups) === true) {
            if ((bool) $filter['search_secondary_groups'] === true) {
                $tgrupo_join_filters[] = sprintf(
                    ' (te.id_grupo = tg.id_grupo AND tg.id_grupo IN (%s))
                    OR (tg.id_grupo = tasg.id_group AND tasg.id_group IN (%s))',
                    join(', ', $groups),
                    join(', ', $groups)
                );
            } else {
                $tgrupo_join_filters[] = sprintf(
                    ' (te.id_grupo = tg.id_grupo AND tg.id_grupo IN (%s))',
                    join(', ', $groups)
                );
            }
        } else {
            if ((bool) $filter['search_secondary_groups'] === true) {
                $tgrupo_join_filters[] = sprintf(
                    ' (te.id_grupo = tg.id_grupo AND tg.id_grupo = %s)
                    OR (tg.id_grupo = tasg.id_group AND tasg.id_group = %s)',
                    $groups,
                    $groups
                );
            } else {
                $tgrupo_join_filters[] = sprintf(
                    ' (te.id_grupo = tg.id_grupo AND tg.id_grupo = %s)',
                    $groups
                );
            }
        }
    } else {
        $tgrupo_join_filters[] = ' te.id_grupo = tg.id_grupo';
    }

    // Secondary groups.
    $event_lj = '';
    if (!$user_is_admin || ($user_is_admin && isset($groups) === true && $groups > 0)) {
        if ((bool) $filter['search_secondary_groups'] === true) {
            $event_lj = events_get_secondary_groups_left_join($table);
        }
    }

    $group_selects = '';
    if ($group_by != '') {
        if ($count === false) {
            $group_selects = sprintf(
                ',COUNT(id_evento) AS event_rep,
                MAX(te.utimestamp) as timestamp_last,
                MIN(te.utimestamp) as timestamp_first,
                MAX(id_evento) as max_id_evento'
            );

            $group_selects_trans = sprintf(
                ',tmax_event.event_rep,
                tmax_event.timestamp_last,
                tmax_event.timestamp_first,
                tmax_event.max_id_evento'
            );
        }
    }

    if (((int) $filter['group_rep'] === EVENT_GROUP_REP_EVENTS
        || (int) $filter['group_rep'] === EVENT_GROUP_REP_EXTRAIDS) && $count === false
    ) {
        $sql = sprintf(
            'SELECT %s
                %s
            FROM %s
            INNER JOIN (
                SELECT te.id_evento %s
                FROM %s
                %s
                %s
                %s
                %s JOIN %s ta
                    ON ta.%s = te.id_agente
                %s
                %s JOIN tgrupo tg
                    ON %s
                WHERE 1=1
                %s
                %s
                %s
                %s
                %s
            ) tmax_event
            ON te.id_evento = tmax_event.max_id_evento
            %s
            %s
            %s
            %s JOIN %s ta
                ON ta.%s = te.id_agente
            %s
            %s JOIN tgrupo tg
                ON %s 
            %s
            %s',
            join(',', $fields),
            $group_selects_trans,
            $tevento,
            $group_selects,
            $tevento,
            $event_lj,
            $agentmodule_join,
            $event_comment_join,
            $tagente_join,
            $tagente_table,
            $tagente_field,
            $conditionMetaconsole,
            $tgrupo_join,
            join(' ', $tgrupo_join_filters),
            join(' ', $sql_filters),
            $group_by,
            $order_by,
            $pagination,
            $having,
            $event_lj,
            $agentmodule_join,
            $event_comment_join,
            $tagente_join,
            $tagente_table,
            $tagente_field,
            $conditionMetaconsole,
            $tgrupo_join,
            join(' ', $tgrupo_join_filters),
            join(' ', $sql_filters),
            $order_by
        );
    } else {
        $sql = sprintf(
            'SELECT %s
                %s
            FROM %s
            %s
            %s
            %s
            %s JOIN %s ta
            ON ta.%s = te.id_agente
            %s
            %s JOIN tgrupo tg
            ON %s
            WHERE 1=1
            %s
            %s
            %s
            %s
            %s
            ',
            join(',', $fields),
            $group_selects,
            $tevento,
            $event_lj,
            $agentmodule_join,
            $event_comment_join,
            $tagente_join,
            $tagente_table,
            $tagente_field,
            $conditionMetaconsole,
            $tgrupo_join,
            join(' ', $tgrupo_join_filters),
            join(' ', $sql_filters),
            $group_by,
            $order_by,
            $pagination,
            $having
        );
    }

    if ($return_sql === true) {
        return $sql;
    }

    if (!$user_is_admin && users_can_manage_group_all('ER') === false) {
        $can_manage = '0 as user_can_manage';
        if (empty($EM_groups) === false) {
            $can_manage = sprintf(
                '(tbase.id_grupo IN (%s)) as user_can_manage',
                join(', ', array_keys($EM_groups))
            );
        }

        $can_write = '0 as user_can_write';
        if (empty($EW_groups) === false) {
            $can_write = sprintf(
                '(tbase.id_grupo IN (%s)) as user_can_write',
                join(', ', array_keys($EW_groups))
            );
        }

        $sql = sprintf(
            'SELECT
                tbase.*,
                %s,
                %s
            FROM
                (',
            $can_manage,
            $can_write
        ).$sql.') tbase';
    } else {
        $sql = 'SELECT
                tbase.*,
                1 as user_can_manage,
                1 as user_can_write
            FROM
                ('.$sql.') tbase';
    }

    if ($count === true
        && (is_metaconsole() === false
        || (is_metaconsole() === true
        && empty($filter['server_id']) === false
        && is_array($filter['server_id']) === false))
    ) {
        $sql = 'SELECT count(*) as nitems FROM ('.$sql.') tt';
    }

    if (is_metaconsole() === true) {
        $result_meta = [];
        $metaconsole_connections = metaconsole_get_names(['disabled' => 0]);
        if (isset($metaconsole_connections) === true
            && is_array($metaconsole_connections) === true
        ) {
            try {
                if (empty($id_server) === true) {
                    $metaconsole_connections = array_flip($metaconsole_connections);
                    $metaconsole_connections['meta'] = 0;
                } else {
                    if (is_array($id_server) === false) {
                        $only_id_server[$metaconsole_connections[$id_server]] = $id_server;
                        $metaconsole_connections = $only_id_server;
                    } else {
                        $metaConnections = [];
                        foreach ($id_server as $idser) {
                            if ((int) $idser === 0) {
                                $metaConnections['meta'] = 0;
                            } else {
                                $metaConnections[$metaconsole_connections[$idser]] = $idser;
                            }
                        }

                        $metaconsole_connections = $metaConnections;
                    }
                }

                $result_meta = Promise\wait(
                    parallelMap(
                        $metaconsole_connections,
                        function ($node_int) use ($sql, $history) {
                            try {
                                if (is_metaconsole() === true
                                    && (int) $node_int > 0
                                ) {
                                    $node = new Node($node_int);
                                    $node->connect();
                                }

                                $res = db_get_all_rows_sql($sql, $history);
                                if ($res === false) {
                                    $res = [];
                                }
                            } catch (\Exception $e) {
                                // Unexistent agent.
                                if (is_metaconsole() === true
                                    && $node_int > 0
                                ) {
                                    $node->disconnect();
                                }

                                error_log('[events_get_all]'.$e->getMessage());
                                return __('Could not connect: %s', $e->getMessage());
                            } finally {
                                if (is_metaconsole() === true
                                    && $node_int > 0
                                ) {
                                    $node->disconnect();
                                }
                            }

                            return $res;
                        }
                    )
                );
            } catch (\Exception $e) {
                $e->getReasons();
            }
        }

        $data = [];
        $buffers = [
            'settings' => [
                'total' => $config['max_number_of_events_per_node'],
            ],
            'data'     => [],
            'error'    => [],
        ];

        if (empty($result_meta) === false) {
            foreach ($result_meta as $node => $value) {
                if (is_array($value) === false) {
                    $buffers['error'][$node] = $value;
                    $buffers['data'][$node] = 0;
                } else {
                    $buffers['data'][$node] = count($value);
                    if (empty($value) === false) {
                        foreach ($value as $k => $v) {
                            $value[$k]['server_id'] = $metaconsole_connections[$node];
                            $value[$k]['server_name'] = $node;
                        }

                        $data = array_merge($data, $value);
                    }
                }
            }
        }

        if ($count === false) {
            if ($sort_field !== 'agent_name'
                && $sort_field !== 'server_name'
                && $sort_field !== 'timestamp'
            ) {
                $sort_field = (explode('.', $sort_field)[1] ?? $sort_field);
                if ($sort_field === 'user_comment') {
                    $sort_field = 'comments';
                }
            }

            usort(
                $data,
                function ($a, $b) use ($sort_field, $order) {
                    switch ($sort_field) {
                        default:
                        case 'utimestamp':
                        case 'criticity':
                        case 'estado':
                            if ((isset($a[$sort_field]) === true && isset($b[$sort_field]) === true) && $a[$sort_field] === $b[$sort_field]) {
                                $res = 0;
                            } else if ((isset($a[$sort_field]) === true && isset($b[$sort_field]) === true) && $a[$sort_field] > $b[$sort_field]) {
                                $res = ($order === 'asc') ? 1 : (-1);
                            } else {
                                $res = ($order === 'asc') ? (-1) : 1;
                            }
                        break;
                        case 'evento':
                        case 'agent_name':
                        case 'timestamp':
                        case 'tags':
                        case 'comments':
                        case 'server_name':
                            if ($order === 'asc') {
                                $res = strcasecmp($a[$sort_field], $b[$sort_field]);
                            } else {
                                $res = strcasecmp($b[$sort_field], $a[$sort_field]);
                            }
                        break;
                    }

                    return $res;
                }
            );

            if (isset($limit, $offset) === true
                && (int) $limit !== 0
                && isset($filter['csv_all']) === false
            ) {
                $count = count($data);
                // -1 For pagination 'All'.
                ((int) $limit === -1)
                    ? $end = count($data)
                    : $end = $limit;
                $finally = array_slice($data, $offset, $end, true);
                $return = [
                    'buffers' => $buffers,
                    'data'    => $finally,
                    'total'   => $count,
                ];
            } else {
                $return = array_slice(
                    $data,
                    0,
                    ($config['max_number_of_events_per_node'] * count($metaconsole_connections)),
                    true
                );
            }

            return $return;
        } else {
            return ['count' => count($data)];
        }
    }

    return db_get_all_rows_sql($sql, $history);
}


/**
 * @deprecated Use events_get_all instead.
 *
 * Get all rows of events from the database, that
 * pass the filter, and can get only some fields.
 *
 * @param mixed $filter Filters elements. It can be an indexed array
 *                      (keys would be the field name and value the expected
 *                      value, and would be joined with an AND operator) or a
 *                      string, including any SQL clause (without the WHERE
 *                      keyword). Example:
 *                <code>
 *                Both are similars:
 *                db_get_all_rows_filter ('table', ['disabled', 0]);
 *                db_get_all_rows_filter ('table', 'disabled = 0');
 *                Both are similars:
 *                db_get_all_rows_filter (
 *                    'table',
 *                    [
 *                         'disabled' => 0,
 *                         'history_data' => 0
 *                    ],
 *                    'name',
 *                    'OR'
 *                );
 *                db_get_all_rows_filter (
 *                    'table',
 *                    'disabled = 0 OR history_data = 0', 'name'
 *                );
 *                </code>.
 * @param mixed $fields Fields of the table to retrieve. Can be an array or a
 *                      coma separated string. All fields are retrieved by
 *                      default.
 *
 * @return mixed False in case of error or invalid values passed.
 *               Affected rows otherwise
 */
function events_get_events($filter=false, $fields=false)
{
    if (isset($filter['criticity']) === true
        && (int) $filter['criticity'] === EVENT_CRIT_WARNING_OR_CRITICAL
    ) {
        $filter['criticity'] = [
            EVENT_CRIT_WARNING,
            EVENT_CRIT_CRITICAL,
        ];
    }

    return db_get_all_rows_filter('tevento', $filter, $fields);
}


/**
 * Get the event with the id pass as parameter.
 *
 * @param integer $id      Event id.
 * @param mixed   $fields  The fields to show or by default all with false.
 * @param boolean $meta    Metaconsole environment or not.
 * @param boolean $history Retrieve also historical data.
 *
 * @return mixed False in case of error or invalid values passed.
 *               Event row otherwise.
 */
function events_get_event($id, $fields=false, $meta=false, $history=false)
{
    if (empty($id) === true) {
        return false;
    }

    global $config;

    if (is_array($fields) === true) {
        if (in_array('id_grupo', $fields) === false) {
            $fields[] = 'id_grupo';
        }
    }

    $event = db_get_row('tevento', 'id_evento', $id, $fields);
    if ((bool) check_acl($config['id_user'], $event['id_grupo'], 'ER') === false) {
        return false;
    }

    return $event;
}


/**
 * Change the status of one or multiple events.
 *
 * @param mixed   $id_event   Event ID or array of events.
 * @param integer $new_status New status of the event.
 *
 * @return boolean Whether or not it was successful
 */
function events_change_status(
    $id_event,
    $new_status
) {
    global $config;

    // Cleans up the selection for all unwanted
    // values also casts any single values as an array.
    $id_event = (array) safe_int($id_event, 1);

    // Update ack info if the new status is validated.
    $ack_utimestamp = 0;
    $ack_user = $config['id_user'];
    if ((int) $new_status === EVENT_STATUS_VALIDATED || (int) $new_status === EVENT_STATUS_INPROCESS) {
        $ack_utimestamp = time();
    }

    switch ($new_status) {
        case EVENT_STATUS_NEW:
            $status_string = 'New';
        break;

        case EVENT_STATUS_VALIDATED:
            $status_string = 'Validated';
        break;

        case EVENT_STATUS_INPROCESS:
            $status_string = 'In process';
        break;

        default:
            $status_string = '';
        break;
    }

    $alerts = [];

    foreach ($id_event as $k => $id) {
        $event_group = events_get_group($id);
        $event = events_get_event($id);
        if ($event['id_alert_am'] > 0
            && in_array($event['id_alert_am'], $alerts) === false
        ) {
            $alerts[] = $event['id_alert_am'];
        }

        if (check_acl($config['id_user'], $event_group, 'EW') == 0) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Attempted updating event #'.$id
            );

            unset($id_event[$k]);
        }
    }

    if (empty($id_event) === true) {
        return false;
    }

    $values = [
        'estado'         => $new_status,
        'id_usuario'     => $ack_user,
        'ack_utimestamp' => $ack_utimestamp,
    ];

    $ret = db_process_sql_update(
        'tevento',
        $values,
        ['id_evento' => $id_event]
    );

    if (($ret === false) || ($ret === 0)) {
        return false;
    }

    if ($new_status === EVENT_STATUS_VALIDATED) {
        events_change_owner(
            $id_event,
            $config['id_user'],
            false
        );
    }

    events_comment(
        $id_event,
        '',
        'Change status to '.$status_string
    );

    // Put the alerts in standby or not depends the new status.
    if (empty($alerts) === false) {
        foreach ($alerts as $alert) {
            switch ($new_status) {
                case EVENT_NEW:
                case EVENT_VALIDATE:
                    alerts_agent_module_standby($alert, 0);
                break;

                case EVENT_PROCESS:
                    alerts_agent_module_standby($alert, 1);
                break;

                default:
                    // Ignore.
                break;
            }
        }
    }

    return true;
}


/**
 * Change the owner of an event if the event hasn't owner.
 *
 * @param mixed   $id_event  Event ID or array of events.
 * @param string  $new_owner Id_user of the new owner. If is false, the current
 *                           owner will be set, if empty, will be cleaned.
 * @param boolean $force     Flag to force the change or not (not force is
 *                           change only when it hasn't owner).
 *
 * @return boolean Whether or not it was successful.
 */
function events_change_owner(
    $id_event,
    $new_owner=false,
    $force=false
) {
    global $config;
    // Cleans up the selection for all unwanted values also casts any single
    // values as an array.
    $id_event = (array) safe_int($id_event, 1);

    foreach ($id_event as $k => $id) {
        $event_group = events_get_group($id);

        if (check_acl($config['id_user'], $event_group, 'EW') == 0) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Attempted updating event #'.$id
            );
            unset($id_event[$k]);
        }
    }

    if (empty($id_event) === true) {
        return false;
    }

    if ($new_owner === false) {
        $new_owner = $config['id_user'];
    }

    // Only generate comment when is forced
    // (sometimes is owner changes when comment).
    if ($force === true) {
        events_comment(
            $id_event,
            '',
            'Change owner to '.get_user_fullname($new_owner).' ('.$new_owner.')'
        );
    }

    $values = ['owner_user' => $new_owner];

    $where = ['id_evento' => $id_event];

    // If not force, add to where if owner_user = ''.
    if ($force === false) {
        $where['owner_user'] = '';
    }

    $ret = db_process_sql_update(
        'tevento',
        $values,
        $where,
        'AND',
        false
    );

    if (($ret === false) || ($ret === 0)) {
        return false;
    }

    return true;
}


/**
 * Comment events in a transresponse
 *
 * @param mixed  $id_event Event ID or array of events.
 * @param string $comment  Comment to be registered.
 * @param string $action   Action performed with comment. By default just add
 *                         a comment.
 *
 * @return boolean Whether or not it was successful
 */
function events_comment(
    $id_event,
    $comment='',
    $action='Added comment'
) {
    global $config;
    // Cleans up the selection for all unwanted values also casts any single
    // values as an array.
    $id_event = (array) safe_int($id_event, 1);
    // Check ACL.
    foreach ($id_event as $k => $id) {
        $event_group = events_get_group($id);
        if (check_acl($config['id_user'], $event_group, 'EW') == 0) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Attempted updating event #'.$id
            );

            unset($id_event[$k]);
        }
    }

    if (empty($id_event) === true) {
        return false;
    }

    // Get the current event comments.
    $first_event = $id_event;
    if (is_array($id_event) === true) {
        $first_event = reset($id_event);
    }

    // Update comment.
    $ret = db_process_sql_insert(
        'tevent_comment',
        [
            'id_event'   => $first_event,
            'comment'    => $comment,
            'action'     => $action,
            'utimestamp' => time(),
            'id_user'    => $config['id_user'],
        ],
    );

    if (($ret === false) || ($ret === 0)) {
        return false;
    }

    return true;
}


/**
 * Get group id of an event.
 *
 * @param integer $id_event Event id.
 *
 * @return integer Group id of the given event.
 */
function events_get_group($id_event)
{
    return (int) db_get_value(
        'id_grupo',
        'tevento',
        'id_evento',
        (int) $id_event
    );
}


/**
 * Get description of an event.
 *
 * @param integer $id_event Event id.
 *
 * @return string Description of the given event.
 */
function events_get_description($id_event)
{
    return (string) db_get_value(
        'evento',
        'tevento',
        'id_evento',
        (int) $id_event
    );
}


/**
 * Insert a event in the event log system.
 *
 * @param integer $event                 Event.
 * @param integer $id_group              Id_group.
 * @param integer $id_agent              Id_agent.
 * @param integer $status                Status.
 * @param string  $id_user               Id_user.
 * @param string  $event_type            Event_type.
 * @param integer $priority              Priority.
 * @param integer $id_agent_module       Id_agent_module.
 * @param integer $id_aam                Id_aam.
 * @param string  $critical_instructions Critical_instructions.
 * @param string  $warning_instructions  Warning_instructions.
 * @param string  $unknown_instructions  Unknown_instructions.
 * @param boolean $source                Source.
 * @param string  $tags                  Tags.
 * @param string  $custom_data           Custom_data.
 * @param integer $server_id             Server_id.
 * @param string  $id_extra              Id_extra.
 *
 * @return integer Event id.
 */
function events_create_event(
    $event,
    $id_group,
    $id_agent,
    $status=0,
    $id_user='',
    $event_type='unknown',
    $priority=0,
    $id_agent_module=0,
    $id_aam=0,
    $critical_instructions='',
    $warning_instructions='',
    $unknown_instructions='',
    $source=false,
    $tags='',
    $custom_data='',
    $server_id=0,
    $id_extra='',
    $ack_utimestamp=0,
    $event_custom_id=null
) {
    if ($source === false) {
        $source = get_product_name();
    }

    // Get Timestamp.
    $timestamp = time();

    $values = [
        'id_agente'             => $id_agent,
        'id_usuario'            => $id_user,
        'id_grupo'              => $id_group,
        'estado'                => $status,
        'timestamp'             => date('Y-m-d H:i:s', $timestamp),
        'evento'                => $event,
        'utimestamp'            => $timestamp,
        'event_type'            => $event_type,
        'id_agentmodule'        => $id_agent_module,
        'id_alert_am'           => $id_aam,
        'criticity'             => $priority,
        'tags'                  => $tags,
        'source'                => $source,
        'id_extra'              => $id_extra,
        'critical_instructions' => $critical_instructions,
        'warning_instructions'  => $warning_instructions,
        'unknown_instructions'  => $unknown_instructions,
        'owner_user'            => '',
        'ack_utimestamp'        => $ack_utimestamp,
        'custom_data'           => $custom_data,
        'data'                  => '',
        'module_status'         => 0,
        'event_custom_id'       => $event_custom_id,
    ];

    return (int) db_process_sql_insert('tevento', $values);
}


/**
 * Prints a small event table.
 *
 * @param string  $filter        SQL WHERE clause.
 * @param integer $limit         How many events to show.
 * @param integer $width         How wide the table should be.
 * @param boolean $return        Prints out HTML if false.
 * @param integer $agent_id      Agent id if is the table of one agent.
 *                               0 otherwise.
 * @param boolean $tactical_view Be shown in tactical view or not.
 *
 * @return string HTML with table element.
 */
function events_print_event_table(
    $filter='',
    $limit=10,
    $width=440,
    $return=false,
    $agent_id=0,
    $tactical_view=false
) {
    global $config;

    ui_require_css_file('events');

    $agent_condition = ($agent_id === 0) ? '' : ' id_agente = '.$agent_id.' AND ';

    if (empty($filter) === true) {
        $filter = '1 = 1';
    }

    $sql = sprintf(
        'SELECT DISTINCT tevento.*
		FROM tevento
		WHERE %s %s
		ORDER BY utimestamp DESC LIMIT %d',
        $agent_condition,
        $filter,
        $limit
    );

    $result = db_get_all_rows_sql($sql);

    if ($result === false) {
        if ($return === true) {
            $returned = ui_print_info_message(__('No events'), '', true);
            return $returned;
        } else {
            echo ui_print_info_message(__('No events'));
        }
    } else {
        $table = new stdClass();
        $table->id = 'latest_events_table';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->width = $width;
        $table->class = 'tactical_table info_table no-td-padding';
        if ($tactical_view === false) {
            $table->title = __('Latest events');
        }

        $table->titleclass = 'tabletitle';
        $table->titlestyle = 'text-transform:uppercase;';
        $table->headclass = [];
        $table->head = [];
        $table->rowclass = [];
        $table->cellclass = [];
        $table->data = [];
        $table->align = [];
        $table->style = [];

        $i = 0;

        $table->head[$i] = '<span>'.__('Type').'</span>';
        $table->headstyle[$i] = 'width: 3%;text-align: left;';
        $table->style[$i++] = 'text-align: left;';

        $table->head[$i] = '<span>'.__('Event name').'</span>';
        $table->headstyle[$i] = '';
        $table->style[$i++] = 'padding: 0 5px;word-break: break-word';

        if ($agent_id === 0) {
            $table->head[$i] = '<span>'.__('Agent name').'</span>';
            $table->headstyle[$i] = '';
            $table->style[$i++] = 'word-break: break-all;';
        }

        $table->head[$i] = '<span>'.__('Timestamp').'</span>';
        $table->headstyle[$i] = 'width: 150px;';
        $table->style[$i++] = 'padding: 0 5px;word-break: break-word;';

        $table->head[$i] = '<span>'.__('Status').'</span>';
        $table->headstyle[$i] = 'width: 150px;text-align: left;';
        $table->style[$i++] = 'padding: 0 5px;text-align: left;';

        $table->head[$i] = "<span title='".__('Validated')."'>".__('V.').'</span>';
        $table->headstyle[$i] = 'width: 1%;text-align: left;';
        $table->style[$i++] = 'text-align: left;';

        $all_groups = [];
        if ($agent_id != 0) {
            $all_groups = agents_get_all_groups_agent($agent_id);
        }

        foreach ($result as $event) {
            // Copy all groups of the agent and append the event group.
            $check_events = $all_groups;
            $check_events[] = $event['id_grupo'];
            if ((bool) check_acl_one_of_groups($config['id_user'], $check_events, 'ER') === false) {
                continue;
            }

            $data = [];

            // Colored box.
            switch ($event['estado']) {
                case EVENT_STATUS_NEW:
                default:
                    $img = 'images/star@svg.svg';
                    $title = __('New event');
                break;

                case EVENT_STATUS_VALIDATED:
                    $img = 'images/validate.svg';
                    $title = __('Event validated');
                break;

                case EVENT_STATUS_INPROCESS:
                    $img = 'images/clock.svg';
                    $title = __('Event in process');
                break;
            }

            $i = 0;
            // Event type.
            $data[$i++] = events_print_type_img($event['event_type'], true);

            // Event text.
            $data[$i++] = ui_print_string_substr(
                strip_tags(io_safe_output($event['evento'])),
                75,
                true,
            );

            if ($agent_id === 0) {
                if ($event['id_agente'] > 0) {
                    // Agent name.
                    // Get class name, for the link color, etc.
                    $data[$i] = html_print_anchor(
                        [
                            'href'    => 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$event['id_agente'],
                            'content' => agents_get_alias($event['id_agente']),
                        ],
                        true
                    );
                    // For System or SNMP generated alerts.
                } else if ($event['event_type'] === 'system') {
                    $data[$i] = __('System');
                } else {
                    $data[$i] = '';
                }

                $i++;
            }

            // Timestamp.
            $data[$i++] = ui_print_timestamp($event['timestamp'], true, ['style' => 'letter-spacing: 0.3pt;']);

            // Status.
            $data[$i++] = ui_print_event_type($event['event_type'], true, true);

            $data[$i++] = html_print_image(
                $img,
                true,
                [
                    'class' => 'image_status invert_filter main_menu_icon',
                    'title' => $title,
                ]
            );
            $table->data[] = $data;
        }

        $events_table = html_print_table($table, true);
        $out = $events_table;

        unset($table);

        if ($return === true) {
            return $out;
        } else {
            echo $out;
        }
    }
}


/**
 * Prints the event type image.
 *
 * @param string  $type     Event type from SQL.
 * @param boolean $return   Whether to return or print.
 * @param boolean $only_url Flag to return only url of image, by default false.
 *
 * @return string HTML with img.
 */
function events_print_type_img(
    $type,
    $return=false,
    $only_url=false
) {
    global $config;

    $output = '';

    $urlImage = ui_get_full_url(false);
    $icon = '';
    $style = 'main_menu_icon';
    switch ($type) {
        case 'alert_recovered':
            $icon = 'images/alert_recovered@svg.svg';
        break;

        case 'alert_manual_validation':
            $icon = 'images/validate.svg';
        break;

        case 'going_down_critical':
        case 'going_up_critical':
            // This is to be backwards compatible.
            $icon = 'images/module_critical.png';
        break;

        case 'going_up_normal':
        case 'going_down_normal':
            // This is to be backwards compatible.
            $icon = 'images/module_ok.png';
        break;

        case 'going_up_warning':
            $icon = 'images/module_warning.png';
        case 'going_down_warning':
            $icon = 'images/module_warning.png';
        break;

        case 'going_unknown':
            $icon = 'images/module_unknown.png';
        break;

        case 'alert_fired':
            $icon = 'images/bell_error.png';
        break;

        case 'system':
            $style .= ' invert_filter';
            $icon = 'images/configuration@svg.svg';
        break;

        case 'recon_host_detected':
            $style .= ' invert_filter';
            $icon = 'images/recon.png';
        break;

        case 'new_agent':
            $style .= ' invert_filter';
            $icon = 'images/agents@svg.svg';
        break;

        case 'configuration_change':
            $style .= ' invert_filter';
            $icon = 'images/configuration@svg.svg';
        break;

        case 'unknown':
        default:
            $style .= ' invert_filter';
            $icon = 'images/event.svg';
        break;
    }

    if ($only_url) {
        $output = $urlImage.'/'.$icon;
    } else {
        $output .= html_print_image(
            $icon,
            true,
            [
                'title' => events_print_type_description($type, true),
                'class' => $style,
            ]
        );
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Prints the event type image.
 *
 * @param string  $type   Event type from SQL.
 * @param boolean $return Whether to return or print.
 *
 * @return string HTML with img.
 */
function events_print_type_img_pdf(
    $type,
    $return=false
) {
    $svg = '';

    switch ($type) {
        case 'alert_recovered':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / alert@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-alert" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M10,20 C11.4190985,20 12.5702076,18.8808594 12.5702076,17.5 L7.42979244,17.5 C7.42979244,18.8808594 8.5809015,20 10,20 Z M18.6540098,14.1519531 C17.8777645,13.3410156 16.425318,12.1210937 16.425318,8.125 C16.425318,5.08984375 14.2364028,2.66015625 11.2849029,2.0640625 L11.2849029,1.25 C11.2849029,0.559765625 10.7095493,0 10,0 C9.29045075,0 8.71509711,0.559765625 8.71509711,1.25 L8.71509711,2.0640625 C5.76359722,2.66015625 3.57468198,5.08984375 3.57468198,8.125 C3.57468198,12.1210938 2.12223547,13.3410156 1.3459902,14.1519531 C1.10492023,14.4039062 0.998045886,14.7050781 1.00002702,15 C1.00447442,15.640625 1.52156948,16.25 2.28977909,16.25 L17.7102209,16.25 C18.4784305,16.25 18.9959274,15.640625 18.999973,15 C19.0019541,14.7050781 18.8950798,14.4035156 18.6540098,14.1519531 L18.6540098,14.1519531 Z" id="Shape" fill="#82b92e"></path>
                </g>
            </svg>';
        break;

        case 'alert_manual_validation':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / validate@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-validate" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                    <g id="Group" transform="translate(1.000000, 1.000000)" stroke="#3F3F3F" stroke-width="2">
                        <circle id="Oval" cx="9" cy="9" r="9"></circle>
                        <polyline id="Path-10" points="4.93746567 8.98550486 7 12 12.9374657 7.03800583"></polyline>
                    </g>
                </g>
            </svg>';
        break;

        case 'going_down_critical':
        case 'going_up_critical':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / modules@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-modules" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M18.7929688,3.18164062 L10.8789062,0.162109375 C10.59375,0.0540234375 10.296875,0 10,0 C9.703125,0 9.40625,0.0540234375 9.12109375,0.161914062 L1.20664062,3.18164062 C0.480078125,3.45898438 0,4.15625 0,4.93359375 L0,15.0664062 C0,15.8441406 0.480078125,16.5410156 1.20664062,16.8183594 L9.12070313,19.8378906 C9.40625,19.9453125 9.703125,20 10,20 C10.296875,20 10.5949219,19.9459766 10.8777344,19.8380859 L18.7917969,16.8185547 C19.5195312,16.5429688 20,15.84375 20,15.0664062 L20,4.93359375 C20,4.15625 19.5195312,3.45898438 18.7929688,3.18164062 Z M10,2.50273437 L16.4921875,4.9796875 L10,7.4140625 L3.50664062,4.98046875 L10,2.50273437 Z M11.25,17.0273438 L11.25,9.6171875 L17.5,7.2734375 L17.5,14.6367188 L11.25,17.0273438 Z" id="Shape" fill="#e63c52"></path>
                </g>
            </svg>';
        break;

        case 'going_up_normal':
        case 'going_down_normal':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / modules@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-modules" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M18.7929688,3.18164062 L10.8789062,0.162109375 C10.59375,0.0540234375 10.296875,0 10,0 C9.703125,0 9.40625,0.0540234375 9.12109375,0.161914062 L1.20664062,3.18164062 C0.480078125,3.45898438 0,4.15625 0,4.93359375 L0,15.0664062 C0,15.8441406 0.480078125,16.5410156 1.20664062,16.8183594 L9.12070313,19.8378906 C9.40625,19.9453125 9.703125,20 10,20 C10.296875,20 10.5949219,19.9459766 10.8777344,19.8380859 L18.7917969,16.8185547 C19.5195312,16.5429688 20,15.84375 20,15.0664062 L20,4.93359375 C20,4.15625 19.5195312,3.45898438 18.7929688,3.18164062 Z M10,2.50273437 L16.4921875,4.9796875 L10,7.4140625 L3.50664062,4.98046875 L10,2.50273437 Z M11.25,17.0273438 L11.25,9.6171875 L17.5,7.2734375 L17.5,14.6367188 L11.25,17.0273438 Z" id="Shape" fill="#82b92e"></path>
                </g>
            </svg>';
        break;

        case 'going_up_warning':
        case 'going_down_warning':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / modules@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-modules" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M18.7929688,3.18164062 L10.8789062,0.162109375 C10.59375,0.0540234375 10.296875,0 10,0 C9.703125,0 9.40625,0.0540234375 9.12109375,0.161914062 L1.20664062,3.18164062 C0.480078125,3.45898438 0,4.15625 0,4.93359375 L0,15.0664062 C0,15.8441406 0.480078125,16.5410156 1.20664062,16.8183594 L9.12070313,19.8378906 C9.40625,19.9453125 9.703125,20 10,20 C10.296875,20 10.5949219,19.9459766 10.8777344,19.8380859 L18.7917969,16.8185547 C19.5195312,16.5429688 20,15.84375 20,15.0664062 L20,4.93359375 C20,4.15625 19.5195312,3.45898438 18.7929688,3.18164062 Z M10,2.50273437 L16.4921875,4.9796875 L10,7.4140625 L3.50664062,4.98046875 L10,2.50273437 Z M11.25,17.0273438 L11.25,9.6171875 L17.5,7.2734375 L17.5,14.6367188 L11.25,17.0273438 Z" id="Shape" fill="#fcab10"></path>
                </g>
            </svg>';
        break;

        case 'going_unknown':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / modules@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-modules" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M18.7929688,3.18164062 L10.8789062,0.162109375 C10.59375,0.0540234375 10.296875,0 10,0 C9.703125,0 9.40625,0.0540234375 9.12109375,0.161914062 L1.20664062,3.18164062 C0.480078125,3.45898438 0,4.15625 0,4.93359375 L0,15.0664062 C0,15.8441406 0.480078125,16.5410156 1.20664062,16.8183594 L9.12070313,19.8378906 C9.40625,19.9453125 9.703125,20 10,20 C10.296875,20 10.5949219,19.9459766 10.8777344,19.8380859 L18.7917969,16.8185547 C19.5195312,16.5429688 20,15.84375 20,15.0664062 L20,4.93359375 C20,4.15625 19.5195312,3.45898438 18.7929688,3.18164062 Z M10,2.50273437 L16.4921875,4.9796875 L10,7.4140625 L3.50664062,4.98046875 L10,2.50273437 Z M11.25,17.0273438 L11.25,9.6171875 L17.5,7.2734375 L17.5,14.6367188 L11.25,17.0273438 Z" id="Shape" fill="#808080"></path>
                </g>
            </svg>';
        break;

        case 'system':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / configuration@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-configuration" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M18.7953273,7.94064046 L18.0028475,7.94064046 C17.3563508,7.94064046 16.8141278,7.38349388 16.8141278,6.72220776 C16.8141278,6.38896121 16.9548972,6.08695652 17.205154,5.8630565 L17.7160949,5.36318667 C18.2218222,4.86331684 18.2218222,4.0458214 17.7160949,3.54595158 L16.5534436,2.39520958 C16.3240416,2.16610258 15.9851522,2.03072117 15.6410491,2.03072117 C15.296946,2.03072117 14.9632703,2.16610258 14.7286546,2.39520958 L14.2385684,2.88466545 C14.0039526,3.14501432 13.6911316,3.28560271 13.3522422,3.28560271 C12.6848908,3.28560271 12.1270267,2.74407706 12.1270267,2.10361885 L12.1270267,1.30695131 C12.1270267,0.604009373 11.5587353,0 10.8496744,0 L9.26471474,0 C8.55565385,0 7.99257608,0.598802395 7.99257608,1.30695131 L7.99257608,2.09841187 C7.99257608,2.73887009 7.434712,3.28039573 6.76736057,3.28039573 C6.43368486,3.28039573 6.12607756,3.13980734 5.90188919,2.89507941 L5.39616194,2.39520958 C5.16675988,2.1608956 4.82787048,2.03072117 4.48376741,2.03072117 C4.13966433,2.03072117 3.80598861,2.16610258 3.57137287,2.39520958 L2.39829419,3.5407446 C1.89778062,4.04061442 1.89778062,4.85810987 2.39829419,5.35277272 L2.8883804,5.84222859 C3.14906455,6.07654257 3.29504767,6.38896121 3.29504767,6.72220776 C3.29504767,7.38870086 2.75282464,7.94064046 2.10632794,7.94064046 L1.31384812,7.94064046 C0.599573548,7.94064046 0,8.49778703 0,9.20593595 L0,9.99739651 L0,10.7888571 C0,11.491799 0.599573548,12.0541526 1.31384812,12.0541526 L2.10632794,12.0541526 C2.75282464,12.0541526 3.29504767,12.6112991 3.29504767,13.2725853 C3.29504767,13.6058318 3.14906455,13.9182505 2.8883804,14.1525644 L2.39829419,14.6368133 C1.89778062,15.1366832 1.89778062,15.9541786 2.39829419,16.4488414 L3.56094551,17.6047904 C3.79034756,17.8391044 4.12923696,17.9692788 4.47334004,17.9692788 C4.81744312,17.9692788 5.15111883,17.8338974 5.38573457,17.6047904 L5.89146182,17.1049206 C6.11043651,16.8601927 6.42325749,16.7196043 6.75693321,16.7196043 C7.42428463,16.7196043 7.98214872,17.2611299 7.98214872,17.9015881 L7.98214872,18.6930487 C7.98214872,19.3959906 8.54522648,20 9.25950106,20 L10.8444607,20 C11.5535216,20 12.1165994,19.4011976 12.1165994,18.6930487 L12.1165994,17.9015881 C12.1165994,17.2611299 12.6744634,16.7196043 13.3418149,16.7196043 C13.6754906,16.7196043 13.9883116,16.8653996 14.228141,17.1205415 L14.7182272,17.6099974 C14.9528429,17.8391044 15.2865186,17.9744858 15.6306217,17.9744858 C15.9747248,17.9744858 16.3084005,17.8391044 16.5430163,17.6099974 L17.7056676,16.4540484 C18.2061811,15.9541786 18.2061811,15.1366832 17.7056676,14.6368133 L17.1947266,14.1369435 C16.9444698,13.9130435 16.8037004,13.6058318 16.8037004,13.2777922 C16.8037004,12.6112991 17.3459234,12.0593595 17.9924201,12.0593595 L18.7849,12.0593595 C19.4939608,12.0593595 19.9998464,11.502213 19.9998464,10.794064 L19.9998464,9.99739651 L19.9998464,9.20593595 C20.0101155,8.49778703 19.5043882,7.94064046 18.7953273,7.94064046 Z M14.2229273,9.99739651 L14.2229273,9.99739651 C14.2229273,12.2936735 12.3616425,14.1629784 10.0519809,14.1629784 C7.7423193,14.1629784 5.88103446,12.2936735 5.88103446,9.99739651 L5.88103446,9.99739651 L5.88103446,9.99739651 C5.88103446,7.7011195 7.7423193,5.83181463 10.0519809,5.83181463 C12.3616425,5.83181463 14.2229273,7.7011195 14.2229273,9.99739651 L14.2229273,9.99739651 Z" id="Path-2" fill="#3F3F3F"></path>
                </g>
            </svg>';
        break;

        case 'new_agent':
            $svg = html_print_image(
                '/images/agent_mc.png',
                true,
                [
                    'class' => 'image_status invert_filter',
                    'title' => 'agents',
                ]
            );
        break;

        case 'configuration_change':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <!-- Generator: Sketch 61.2 (89653) - https://sketch.com -->
                <title>Dark / 20 / configuration@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-configuration" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M18.7953273,7.94064046 L18.0028475,7.94064046 C17.3563508,7.94064046 16.8141278,7.38349388 16.8141278,6.72220776 C16.8141278,6.38896121 16.9548972,6.08695652 17.205154,5.8630565 L17.7160949,5.36318667 C18.2218222,4.86331684 18.2218222,4.0458214 17.7160949,3.54595158 L16.5534436,2.39520958 C16.3240416,2.16610258 15.9851522,2.03072117 15.6410491,2.03072117 C15.296946,2.03072117 14.9632703,2.16610258 14.7286546,2.39520958 L14.2385684,2.88466545 C14.0039526,3.14501432 13.6911316,3.28560271 13.3522422,3.28560271 C12.6848908,3.28560271 12.1270267,2.74407706 12.1270267,2.10361885 L12.1270267,1.30695131 C12.1270267,0.604009373 11.5587353,0 10.8496744,0 L9.26471474,0 C8.55565385,0 7.99257608,0.598802395 7.99257608,1.30695131 L7.99257608,2.09841187 C7.99257608,2.73887009 7.434712,3.28039573 6.76736057,3.28039573 C6.43368486,3.28039573 6.12607756,3.13980734 5.90188919,2.89507941 L5.39616194,2.39520958 C5.16675988,2.1608956 4.82787048,2.03072117 4.48376741,2.03072117 C4.13966433,2.03072117 3.80598861,2.16610258 3.57137287,2.39520958 L2.39829419,3.5407446 C1.89778062,4.04061442 1.89778062,4.85810987 2.39829419,5.35277272 L2.8883804,5.84222859 C3.14906455,6.07654257 3.29504767,6.38896121 3.29504767,6.72220776 C3.29504767,7.38870086 2.75282464,7.94064046 2.10632794,7.94064046 L1.31384812,7.94064046 C0.599573548,7.94064046 0,8.49778703 0,9.20593595 L0,9.99739651 L0,10.7888571 C0,11.491799 0.599573548,12.0541526 1.31384812,12.0541526 L2.10632794,12.0541526 C2.75282464,12.0541526 3.29504767,12.6112991 3.29504767,13.2725853 C3.29504767,13.6058318 3.14906455,13.9182505 2.8883804,14.1525644 L2.39829419,14.6368133 C1.89778062,15.1366832 1.89778062,15.9541786 2.39829419,16.4488414 L3.56094551,17.6047904 C3.79034756,17.8391044 4.12923696,17.9692788 4.47334004,17.9692788 C4.81744312,17.9692788 5.15111883,17.8338974 5.38573457,17.6047904 L5.89146182,17.1049206 C6.11043651,16.8601927 6.42325749,16.7196043 6.75693321,16.7196043 C7.42428463,16.7196043 7.98214872,17.2611299 7.98214872,17.9015881 L7.98214872,18.6930487 C7.98214872,19.3959906 8.54522648,20 9.25950106,20 L10.8444607,20 C11.5535216,20 12.1165994,19.4011976 12.1165994,18.6930487 L12.1165994,17.9015881 C12.1165994,17.2611299 12.6744634,16.7196043 13.3418149,16.7196043 C13.6754906,16.7196043 13.9883116,16.8653996 14.228141,17.1205415 L14.7182272,17.6099974 C14.9528429,17.8391044 15.2865186,17.9744858 15.6306217,17.9744858 C15.9747248,17.9744858 16.3084005,17.8391044 16.5430163,17.6099974 L17.7056676,16.4540484 C18.2061811,15.9541786 18.2061811,15.1366832 17.7056676,14.6368133 L17.1947266,14.1369435 C16.9444698,13.9130435 16.8037004,13.6058318 16.8037004,13.2777922 C16.8037004,12.6112991 17.3459234,12.0593595 17.9924201,12.0593595 L18.7849,12.0593595 C19.4939608,12.0593595 19.9998464,11.502213 19.9998464,10.794064 L19.9998464,9.99739651 L19.9998464,9.20593595 C20.0101155,8.49778703 19.5043882,7.94064046 18.7953273,7.94064046 Z M14.2229273,9.99739651 L14.2229273,9.99739651 C14.2229273,12.2936735 12.3616425,14.1629784 10.0519809,14.1629784 C7.7423193,14.1629784 5.88103446,12.2936735 5.88103446,9.99739651 L5.88103446,9.99739651 L5.88103446,9.99739651 C5.88103446,7.7011195 7.7423193,5.83181463 10.0519809,5.83181463 C12.3616425,5.83181463 14.2229273,7.7011195 14.2229273,9.99739651 L14.2229273,9.99739651 Z" id="Path-2" fill="#3F3F3F"></path>
                </g>
            </svg>';
        break;

        case 'unknown':
        break;

        case 'alert_fired':
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / alert@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-alert" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <path d="M10,20 C11.4190985,20 12.5702076,18.8808594 12.5702076,17.5 L7.42979244,17.5 C7.42979244,18.8808594 8.5809015,20 10,20 Z M18.6540098,14.1519531 C17.8777645,13.3410156 16.425318,12.1210937 16.425318,8.125 C16.425318,5.08984375 14.2364028,2.66015625 11.2849029,2.0640625 L11.2849029,1.25 C11.2849029,0.559765625 10.7095493,0 10,0 C9.29045075,0 8.71509711,0.559765625 8.71509711,1.25 L8.71509711,2.0640625 C5.76359722,2.66015625 3.57468198,5.08984375 3.57468198,8.125 C3.57468198,12.1210938 2.12223547,13.3410156 1.3459902,14.1519531 C1.10492023,14.4039062 0.998045886,14.7050781 1.00002702,15 C1.00447442,15.640625 1.52156948,16.25 2.28977909,16.25 L17.7102209,16.25 C18.4784305,16.25 18.9959274,15.640625 18.999973,15 C19.0019541,14.7050781 18.8950798,14.4035156 18.6540098,14.1519531 L18.6540098,14.1519531 Z" id="Shape" fill="#e63c52"></path>
                </g>
            </svg>';
        break;

        default:
            $svg = '<svg  viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <title>Dark / 20 / event@svg</title>
                <desc>Created with Sketch.</desc>
                <g id="Dark-/-20-/-event" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g>
                        <rect id="Rectangle" x="0" y="0" width="20" height="20"></rect>
                        <path d="M15.9503156,6.25 L10.901623,6.25 L12.7653518,1.1796875 C12.9403498,0.5859375 12.4372305,0 11.7503633,0 L5.45043493,0 C4.9254409,0 4.47919597,0.34765625 4.40919676,0.8125 L3.00921267,10.1875 C2.92608862,10.75 3.41608305,11.25 4.05045084,11.25 L9.24351682,11.25 L7.22666474,18.8476562 C7.06916653,19.4414062 7.57666077,20 8.24602816,20 C8.61352398,20 8.96352,19.828125 9.15601782,19.53125 L16.8559303,7.65625 C17.2628007,7.03515625 16.7596814,6.25 15.9503156,6.25 Z" id="Path" fill="#3F3F3F"></path>
                    </g>
                </g>
            </svg>';
        break;
    }

    $output = '<div style="width:20px;height:20px">'.$svg.'</div>';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Prints the event type description
 *
 * @param string  $type   Event type from SQL.
 * @param boolean $return Whether to return or print.
 *
 * @return string HTML with img
 */
function events_print_type_description($type, $return=false)
{
    $output = '';

    switch ($type) {
        case 'going_unknown':
            $output .= __('Going to unknown');
        break;

        case 'alert_recovered':
            $output .= __('Alert recovered');
        break;

        case 'alert_manual_validation':
            $output .= __('Alert manually validated');
        break;

        case 'going_up_warning':
            $output .= __('Going from critical to warning');
        break;

        case 'going_down_critical':
        case 'going_up_critical':
            // This is to be backwards compatible.
            $output .= __('Going up to critical state');
        break;

        case 'going_up_normal':
        case 'going_down_normal':
            // This is to be backwards compatible.
            $output .= __('Going up to normal state');
        break;

        case 'going_down_warning':
            $output .= __('Going down from normal to warning');
        break;

        case 'alert_fired':
            $output .= __('Alert fired');
        break;

        case 'system';
            $output .= __('SYSTEM');
        break;

        case 'recon_host_detected';
            $output .= __('Discovery server detected a new host');
        break;

        case 'new_agent';
            $output .= __('New agent created');
        break;

        case 'configuration_change';
            $output .= __('Configuration change');
        break;

        case 'alert_ceased';
            $output .= __('Alert ceased');
        break;

        case 'error';
            $output .= __('Error');
        break;

        case 'unknown':
        default:
            $output .= __('Unknown type:').': '.$type;
        break;
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Get all the events happened in an Agent during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param integer $id_agent                    Agent id to get events.
 * @param integer $period                      Period in seconds to get events.
 * @param integer $date                        Beginning date to get events.
 * @param boolean $history                     History.
 * @param boolean $show_summary_group          Show_summary_group.
 * @param array   $filter_event_severity       Filter_event_severity.
 * @param array   $filter_event_type           Filter_event_type.
 * @param array   $filter_event_status         Filter_event_status.
 * @param string  $filter_event_filter_search  Filter_event_filter_search.
 * @param boolean $id_group                    Id_group.
 * @param boolean $events_group                Events_group.
 * @param boolean $id_agent_module             Id_agent_module.
 * @param boolean $events_module               Events_module.
 * @param boolean $id_server                   Id_server.
 * @param boolean $filter_event_filter_exclude Filter_event_filter_exclude.
 *
 * @return array|false An array with all the events happened. False if something
 *                     failed.
 */
function events_get_agent(
    $id_agent,
    $period,
    $date=0,
    $history=false,
    $show_summary_group=false,
    $filter_event_severity=[],
    $filter_event_type=[],
    $filter_event_status=[],
    $filter_event_filter_search='',
    $id_group=false,
    $events_group=false,
    $id_agent_module=false,
    $events_module=false,
    $id_server=false,
    $filter_event_filter_exclude=false
) {
    global $config;

    $filters = [];
    // Id Agent.
    if ($id_agent !== false && empty($id_agent) === false) {
        $filters['id_agent'] = $id_agent;
    }

    // Date.
    if (is_numeric($date) === false) {
        $date = time_w_fixed_tz($date);
    }

    if (empty($date) === true) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);
    $filters['date_from'] = date('Y-m-d', $datelimit);
    $filters['date_to'] = date('Y-m-d', $date);
    $filters['time_from'] = date('H:i:s', $datelimit);
    $filters['time_to'] = date('H:i:s', $date);

    // Severity.
    if (empty($filter_event_severity) === false) {
        $filters['severity'] = $filter_event_severity;
    }

    // Type.
    if (empty($filter_event_type) === false) {
        $filters['event_type'] = $filter_event_type;
    }

    // Status.
    if (empty($filter_event_status) === false) {
        $filters['status'] = $filter_event_status;
    }

    // ID group.
    if (empty($id_group) === false) {
        $filters['id_group_filter'] = $id_group;
    }

    // Filter search.
    if (empty($filter_event_filter_search) === false) {
        $filters['search'] = $filter_event_filter_search;
    }

    // Filter search exclude.
    if (empty($filter_event_filter_exclude) === false) {
        $filters['search_exclude'] = $filter_event_filter_exclude;
    }

    if (empty($id_agent_module) === false) {
        $filters['module_search'] = modules_get_agentmodule_name($id_agent_module);
    }

    if (empty($id_server) === false) {
        $filters['id_server'] = $id_server;
    }

    // Group by agent.
    if ((bool) $show_summary_group === true) {
        $filters['group_rep'] = EVENT_GROUP_REP_EVENTS;
    } else {
        $filters['group_rep'] = EVENT_GROUP_REP_AGENTS;
    }

    $events = Event::search(
        [
            'te.*',
            'ta.alias',
        ],
        $filters,
        0,
        1000,
        'desc',
        'te.utimestamp'
    );

    if (is_metaconsole() === true) {
        $events = $events['data'];
    }

    return $events;
}


/**
 * Decode a numeric type into type description.
 *
 * @param integer $type_id Numeric type.
 *
 * @return string Type description.
 */
function events_get_event_types($type_id)
{
    $diferent_types = get_event_types();

    $type_desc = '';
    switch ($type_id) {
        case 'unknown':
            $type_desc = __('Unknown');
        break;

        case 'critical':
            $type_desc = __('Monitor Critical');
        break;

        case 'warning':
            $type_desc = __('Monitor Warning');
        break;

        case 'normal':
            $type_desc = __('Monitor Normal');
        break;

        case 'alert_fired':
            $type_desc = __('Alert fired');
        break;

        case 'alert_recovered':
            $type_desc = __('Alert recovered');
        break;

        case 'alert_ceased':
            $type_desc = __('Alert ceased');
        break;

        case 'alert_manual_validation':
            $type_desc = __('Alert manual validation');
        break;

        case 'recon_host_detected':
            $type_desc = __('Recon host detected');
        break;

        case 'system':
            $type_desc = __('System');
        break;

        case 'error':
            $type_desc = __('Error');
        break;

        case 'configuration_change':
            $type_desc = __('Configuration change');
        break;

        case 'not_normal':
            $type_desc = __('Not normal');
        break;

        case 'ncm':
            $type_desc = __('Network configuration manager');
        break;

        default:
            if (isset($config['text_char_long'])) {
                foreach ($diferent_types as $key => $type) {
                    if ($key == $type_id) {
                        $type_desc = ui_print_truncate_text($type, $config['text_char_long'], false, true, false);
                    }
                }
            }
        break;
    }

    return $type_desc;
}


/**
 * Decode a numeric severity into severity description.
 *
 * @param integer $severity_id Numeric severity.
 *
 * @return string Severity description.
 */
function events_get_severity_types($severity_id)
{
    $diferent_types = get_priorities();

    $severity_desc = '';
    switch ($severity_id) {
        case EVENT_CRIT_MAINTENANCE:
            $severity_desc = __('Maintenance');
        break;

        case EVENT_CRIT_INFORMATIONAL:
            $severity_desc = __('Informational');
        break;

        case EVENT_CRIT_NORMAL:
            $severity_desc = __('Normal');
        break;

        case EVENT_CRIT_WARNING:
            $severity_desc = __('Warning');
        break;

        case EVENT_CRIT_CRITICAL:
            $severity_desc = __('Critical');
        break;

        default:
            if (isset($config['text_char_long'])) {
                foreach ($diferent_types as $key => $type) {
                    if ($key == $severity_id) {
                        $severity_desc = ui_print_truncate_text(
                            $type,
                            $config['text_char_long'],
                            false,
                            true,
                            false
                        );
                    }
                }
            }
        break;
    }

    return $severity_desc;
}


/**
 * Return all descriptions of event status.
 *
 * @param boolean $report Show in report or not.
 *
 * @return array Status description array.
 */
function events_get_all_status($report=false)
{
    $fields = [];
    if (!$report) {
        $fields[-1] = __('All event');
        $fields[0]  = __('Only new');
        $fields[1]  = __('Only validated');
        $fields[2]  = __('Only in process');
        $fields[3]  = __('Only not validated');
        $fields[4]  = __('Only not in process');
    } else {
        $fields[-1] = __('All event');
        $fields[0]  = __('New');
        $fields[1]  = __('Validated');
        $fields[2]  = __('In process');
        $fields[3]  = __('Not Validated');
        $fields[4]  = __('Not in process');
    }

    return $fields;
}


/**
 * Decode a numeric status into status description.
 *
 * @param integer $status_id Numeric status.
 *
 * @return string Status description.
 */
function events_get_status($status_id)
{
    switch ($status_id) {
        case -1:
            $status_desc = __('All event');
        break;

        case 0:
            $status_desc = __('Only new');
        break;

        case 1:
            $status_desc = __('Only validated');
        break;

        case 2:
            $status_desc = __('Only in process');
        break;

        case 3:
            $status_desc = __('Only not validated');
        break;

        default:
            // Ignore.
        break;
    }

    return $status_desc;
}


/**
 * Checks if a user has permissions to see an event filter.
 *
 * @param integer $id_filter Id of the event filter.
 *
 * @return boolean True if the user has permissions or false otherwise.
 */
function events_check_event_filter_group($id_filter, $restrict_all_group=false)
{
    global $config;

    $id_group = db_get_value('id_group_filter', 'tevent_filter', 'id_filter', $id_filter);
    $own_info = get_user_info($config['id_user']);
    // Get group list that user has access.
    $groups_user = users_get_groups($config['id_user'], 'EW', $own_info['is_admin'], true);

    // Permissions in any group allow to edit "All group" filters.
    if ($id_group == 0 && !empty($groups_user)) {
        if ($restrict_all_group === true) {
            return false;
        } else {
            return true;
        }
    }

    $groups_id = [];
    $has_permission = false;

    foreach ($groups_user as $key => $groups) {
        if ($groups['id_grupo'] == $id_group) {
            return true;
        }
    }

    return false;
}


/**
 *  Get a event filter.
 *
 * @param integer $id_filter Filter id to be fetched.
 * @param array   $filter    Extra filter.
 * @param array   $fields    Fields to be fetched.
 *
 * @return array A event filter matching id and filter or false.
 */
function events_get_event_filter($id_filter, $filter=false, $fields=false)
{
    if (empty($id_filter)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
        $filter['id_filter'] = (int) $id_filter;
    }

    return db_get_row_filter('tevent_filter', $filter, $fields);
}


/**
 *  Get a event filters in select format.
 *
 * @param boolean $manage If event filters are used for manage/view operations
 *                        (non admin users can see group ALL for manage) # Fix.
 *
 * @return array A event filter matching id and filter or false.
 */
function events_get_event_filter_select($manage=true)
{
    global $config;

    $strict_acl = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

    if ($strict_acl) {
        $user_groups = users_get_strict_mode_groups(
            $config['id_user'],
            users_can_manage_group_all()
        );
    } else {
        $user_groups = users_get_groups(
            $config['id_user'],
            'ER',
            users_can_manage_group_all(),
            true
        );
    }

    if (empty($user_groups)) {
        return [];
    }

    $sql = '
		SELECT id_filter, id_name, private_filter_user
		FROM tevent_filter
		WHERE id_group_filter IN (0, '.implode(',', array_keys($user_groups)).')';

    $event_filters = db_get_all_rows_sql($sql);

    if ($event_filters === false) {
        return [];
    } else {
        $result = [];
        foreach ($event_filters as $event_filter) {
            $permission = users_is_admin($config['id_user']);
            if ($permission || $event_filter['private_filter_user'] === $config['id_user']) {
                if ($event_filter['private_filter_user'] !== null) {
                    $filter_name = $event_filter['id_name'].' (P)';
                } else {
                    $filter_name = $event_filter['id_name'];
                }

                $result[$event_filter['id_filter']] = $filter_name;
            }

            if ($event_filter['private_filter_user'] === null) {
                $result[$event_filter['id_filter']] = $event_filter['id_name'];
            }
        }
    }

    return $result;
}


/**
 * Events pages functions to load modal window with advanced view of an event.
 * Called from include/ajax/events.php.
 *
 * @param mixed $event Event.
 *
 * @return string HTML.
 */
function events_page_responses($event)
{
    global $config;
    $table_responses = new StdClass();
    $table_responses->cellspacing = 2;
    $table_responses->cellpadding = 2;
    $table_responses->id = 'responses_table';
    $table_responses->width = '100%';
    $table_responses->data = [];
    $table_responses->head = [];
    $table_responses->style[0] = 'height:30px';
    $table_responses->style[2] = 'text-align:right;';
    $table_responses->class = 'table_modal_alternate';

    $acl_tags_event_manager = tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EM',
        $event['clean_tags']
    );

    if ($acl_tags_event_manager === true) {
        // Owner.
        $data = [];
        $data[0] = __('Change owner');
        // Owner change can be done to users that belong to the event group
        // with ER permission.
        $profiles_view_events = db_get_all_rows_filter(
            'tperfil',
            ['event_view' => '1'],
            'id_perfil'
        );

        foreach ($profiles_view_events as $k => $v) {
            $profiles_view_events[$k] = reset($v);
        }

        $_user_groups = array_keys(
            users_get_groups(
                $config['id_user'],
                'ER',
                users_can_manage_group_all()
            )
        );
        $users = groups_get_users(
            $_user_groups,
            ['id_perfil' => $profiles_view_events],
            true
        );

        foreach ($users as $u) {
            $owners[$u['id_user']] = $u['id_user'];
            if (empty($u['fullname']) === false) {
                $owners[$u['id_user']] = $u['fullname'].' ('.$u['id_user'].')';
            }
        }

        $data[1] = html_print_select(
            $owners,
            'id_owner',
            $event['owner_user'],
            '',
            __('None'),
            -1,
            true,
            false,
            true,
            '',
            false,
            'width: 70%'
        );

        $data[2] = html_print_button(
            __('Update'),
            'owner_button',
            false,
            'event_change_owner('.$event['id_evento'].', '.$event['server_id'].');',
            [
                'icon' => 'next',
                'mode' => 'link',
            ],
            true
        );

        $table_responses->data[] = $data;
    }

    // Status.
    $data = [];
    $data[0] = __('Change status');

    $status_blocked = false;

    if (tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EM',
        $event['clean_tags']
    )
    ) {
        // If the user has manager acls, the status can be changed to all
        // possibilities always.
        $status = [
            0 => __('New'),
            2 => __('In process'),
            1 => __('Validated'),
        ];
    } else {
        switch ($event['estado']) {
            case 0:
                // If the user hasnt manager acls and the event is new.
                // The status can be changed.
                $status = [
                    2 => __('In process'),
                    1 => __('Validated'),
                ];
            break;

            case 1:
                // If the user hasnt manager acls and the event is validated.
                // The status cannot be changed.
                $status = [1 => __('Validated')];
                $status_blocked = true;
            break;

            case 2:
                // If the user hasnt manager acls and the event is in process.
                // The status only can be changed to validated.
                $status = [1 => __('Validated')];
            break;

            default:
                // Ignored.
            break;
        }
    }

    // The change status option will be enabled only when is possible change
    // the status.
    $data[1] = html_print_select(
        $status,
        'estado',
        $event['estado'],
        '',
        '',
        0,
        true,
        false,
        false,
        '',
        $status_blocked
    );

    if ($status_blocked === false) {
        if (isset($event['server_id']) === false) {
            $event['server_id'] = '0';
        }

        $data[2] = html_print_button(
            __('Update'),
            'status_button',
            false,
            'event_change_status("'.$event['similar_ids'].'",'.$event['server_id'].', '.$event['group_rep'].');',
            [
                'icon' => 'next',
                'mode' => 'link',
            ],
            true
        );
    }

    if ((tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EM',
        $event['clean_tags']
    )) || (tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EW',
        $event['clean_tags']
    ))
    ) {
        $table_responses->data[] = $data;

        // Comments.
        $data = [];
        $data[0] = __('Comment');
        $data[1] = '';
        $data[2] = html_print_button(
            __('Add comment'),
            'comment_button',
            false,
            '$("#link_comments").trigger("click");',
            [
                'icon' => 'next',
                'mode' => 'link',
            ],
            true
        );

        $table_responses->data[] = $data;
    }

    if (tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EM',
        $event['clean_tags']
    )
    ) {
        // Delete.
        $data = [];
        $data[0] = __('Delete event');
        $data[1] = '';
        $data[2] = '<form id="event_responses_delete" method="post">';
        $data[2] .= html_print_button(
            __('Delete event'),
            'delete_button',
            false,
            'if(!confirm("'.__('Are you sure?').'")) { return false; } this.form.submit();',
            [
                'icon' => 'cancel',
                'mode' => 'link',
            ],
            true
        );
        $data[2] .= html_print_input_hidden('delete', 1, true);
        $data[2] .= html_print_input_hidden(
            'validate_ids',
            $event['id_evento'],
            true
        );
        $data[2] .= '</form>';

        $table_responses->data[] = $data;
    }

    // Custom responses.
    $data = [];
    $data[0] = __('Custom responses');

    $id_groups = array_keys(users_get_groups(false, 'EW'));
    $event_responses = db_get_all_rows_filter(
        'tevent_response',
        ['id_group' => $id_groups]
    );

    if (empty($event_responses) || (!check_acl($config['id_user'], 0, 'EW') && !check_acl($config['id_user'], 0, 'EM'))) {
        $data[1] = '<i>'.__('N/A').'</i>';
    } else {
        $responses = [];
        foreach ($event_responses as $v) {
            if ((isset($config['ITSM_enabled']) === false || (bool) $config['ITSM_enabled'] === false)
                && $v['name'] === 'Create&#x20;ticket&#x20;in&#x20;Pandora&#x20;ITSM&#x20;from&#x20;event'
            ) {
                continue;
            }

            $responses[$v['id']] = $v['name'];
        }

        $data[1] = html_print_select(
            $responses,
            'select_custom_response',
            '',
            '',
            '',
            '',
            true,
            false,
            false
        );

        if (isset($event['server_id'])) {
            $server_id = $event['server_id'];
        } else {
            $server_id = 0;
        }

        $data[2] = html_print_button(
            __('Execute'),
            'custom_response_button',
            false,
            'execute_response('.$event['id_evento'].','.$server_id.',0)',
            ['mode' => 'link'],
            true
        );
    }

    $table_responses->data[] = $data;

    $responses_js = "<script>
			$('#select_custom_response').change(function() {
				var id_response = $('#select_custom_response').val();
                table_info_response_event(id_response,".$event['id_evento'].','.$event['server_id'].");
			});
			$('#select_custom_response').trigger('change');
			</script>";

    $responses = '<div id="extended_event_responses_page" class="extended_event_pages">';
    $responses .= html_print_table($table_responses, true);
    $responses .= $responses_js;
    $responses .= '</div>';

    return $responses;
}


/**
 * Replace macros in the target of a response and return it.
 *
 * @param integer      $event_id            Event identifier.
 * @param array        $event_response      Event Response.
 * @param array|null   $response_parameters If parameters response values.
 * @param integer|null $server_id           Server Id.
 * @param string|null  $server_name         Name server.
 *
 * @return string The response text with the macros applied.
 */
function events_get_response_target(
    int $event_id,
    array $event_response,
    ?array $response_parameters=null,
    ?int $server_id=0,
    ?string $server_name=''
) {
    global $config;

    include_once $config['homedir'].'/vendor/autoload.php';

    try {
        $eventObjt = new PandoraFMS\Event($event_id);
    } catch (Exception $e) {
        $eventObjt = new PandoraFMS\Event();
    }

    $event = db_get_row('tevento', 'id_evento', $event_id);
    $target = io_safe_output(db_get_value('target', 'tevent_response', 'id', $event_response['id']));

    // Replace parameters response.
    if (isset($response_parameters) === true
        && empty($response_parameters) === false
    ) {
        $response_parameters = array_reduce(
            $response_parameters,
            function ($carry, $item) {
                $carry[$item['name']] = $item['value'];
                return $carry;
            }
        );
    }

    if (empty($event_response['params']) === false) {
        $response_params = explode(',', $event_response['params']);
        if (is_array($response_params) === true) {
            foreach ($response_params as $param) {
                $param = trim(io_safe_output($param));
                $target = str_replace(
                    '_'.$param.'_',
                    $response_parameters['values_params_'.$param],
                    $target
                );
            }
        }
    }

    // Replace macros.
    if (strpos($target, '_agent_alias_') !== false) {
        $agente_table_name = 'tagente';
        $filter = ['id_agente' => $event['id_agente']];
        $alias = db_get_value_filter('alias', $agente_table_name, $filter);
        $target = str_replace('_agent_alias_', io_safe_output($alias), $target);
    }

    if (strpos($target, '_agent_name_') !== false) {
        $agente_table_name = 'tagente';
        $filter = ['id_agente' => $event['id_agente']];
        $name = db_get_value_filter('nombre', $agente_table_name, $filter);
        $target = str_replace('_agent_name_', io_safe_output($name), $target);
    }

    // Substitute each macro.
    if (strpos($target, '_agent_address_') !== false) {
        $agente_table_name = 'tagente';
        $filter = ['id_agente' => $event['id_agente']];
        $ip = db_get_value_filter('direccion', $agente_table_name, $filter);
        // If agent has not an IP, display N/A.
        if ($ip === false || $ip === '') {
            $ip = __('N/A');
        }

        $target = str_replace('_agent_address_', $ip, $target);
    }

    if (strpos($target, '_agent_id_') !== false) {
        $target = str_replace('_agent_id_', $event['id_agente'], $target);
    }

    if ((strpos($target, '_module_address_') !== false)
        || (strpos($target, '_module_name_') !== false)
    ) {
        if ($event['id_agentmodule'] !== 0) {
            $module = db_get_row(
                'tagente_modulo',
                'id_agente_modulo',
                $event['id_agentmodule']
            );
            if (empty($module['ip_target']) === true) {
                $module['ip_target'] = __('N/A');
            }

            $target = str_replace(
                '_module_address_',
                $module['ip_target'],
                $target
            );
            if (empty($module['nombre']) === true) {
                $module['nombre'] = __('N/A');
            }

            $target = str_replace(
                '_module_name_',
                io_safe_output($module['nombre']),
                $target
            );
        } else {
            $target = str_replace('_module_address_', __('N/A'), $target);
            $target = str_replace('_module_name_', __('N/A'), $target);
        }
    }

    if (strpos($target, '_event_id_') !== false) {
        $target = str_replace('_event_id_', $event['id_evento'], $target);
    }

    if (strpos($target, '_user_id_') !== false) {
        if (empty($event['id_usuario']) === false) {
            $target = str_replace('_user_id_', $event['id_usuario'], $target);
        } else {
            $target = str_replace('_user_id_', __('N/A'), $target);
        }
    }

    if (strpos($target, '_group_id_') !== false) {
        $target = str_replace('_group_id_', $event['id_grupo'], $target);
    }

    if (strpos($target, '_group_name_') !== false) {
        $target = str_replace(
            '_group_name_',
            io_safe_output(groups_get_name($event['id_grupo'], true)),
            $target
        );
    }

    if (strpos($target, '_group_contact_') !== false) {
        $info_groups = groups_get_group_by_id($event['id_grupo']);
        $target = str_replace(
            '_group_contact_',
            (isset($info_groups['contact']) === true) ? $info_groups['contact'] : 'N/A',
            $target
        );
    }

    if (strpos($target, '_event_utimestamp_') !== false) {
        $target = str_replace(
            '_event_utimestamp_',
            $event['utimestamp'],
            $target
        );
    }

    if (strpos($target, '_event_date_') !== false) {
        $target = str_replace(
            '_event_date_',
            io_safe_output(date($config['date_format'], $event['utimestamp'])),
            $target
        );
    }

    if (strpos($target, '_event_text_') !== false) {
        $target = str_replace(
            '_event_text_',
            events_display_name($event['evento']),
            $target
        );
    }

    if (strpos($target, '_event_type_') !== false) {
        $target = str_replace(
            '_event_type_',
            events_print_type_description($event['event_type'], true),
            $target
        );
    }

    if (strpos($target, '_alert_id_') !== false) {
        $target = str_replace(
            '_alert_id_',
            (empty($event['id_alert_am']) === true) ? __('N/A') : $event['id_alert_am'],
            $target
        );
    }

    if (strpos($target, '_event_severity_id_') !== false) {
        $target = str_replace(
            '_event_severity_id_',
            $event['criticity'],
            $target
        );
    }

    if (strpos($target, '_event_severity_text_') !== false) {
        $target = str_replace(
            '_event_severity_text_',
            get_priority_name($event['criticity']),
            $target
        );
    }

    if (strpos($target, '_module_id_') !== false) {
        $target = str_replace('_module_id_', $event['id_agentmodule'], $target);
    }

    if (strpos($target, '_event_tags_') !== false) {
        $target = str_replace('_event_tags_', $event['tags'], $target);
    }

    if (strpos($target, '_event_extra_id_') !== false) {
        if (empty($event['id_extra']) === true) {
            $target = str_replace(
                '_event_extra_id_',
                __('N/A'),
                $target
            );
        } else {
            $target = str_replace(
                '_event_extra_id_',
                $event['id_extra'],
                $target
            );
        }
    }

    if (strpos($target, '_event_source_') !== false) {
        $target = str_replace(
            '_event_source_',
            $event['source'],
            $target
        );
    }

    if (strpos($target, '_event_instruction_') !== false) {
        // Fallback to module instructions if not defined in event.
        $instructions = [];

        foreach ([
            'warning_instructions',
            'critical_instructions',
            'unknown_instructions',
        ] as $i) {
            $instructions[$i] = $event[$i];
            if (empty($instructions[$i]) === true
                && $eventObjt->module() !== null
            ) {
                try {
                    $instructions[$i] = $eventObjt->module()->{$i}();
                } catch (Exception $e) {
                    // Method not found.
                    $instructions[$i] = null;
                }
            }
        }

        $target = str_replace(
            '_event_instruction_',
            events_display_instructions(
                $event['event_type'],
                $instructions,
                false,
                $eventObjt->toArray()
            ),
            $target
        );
    }

    if (strpos($target, '_data_') !== false
        && $eventObjt !== null
        && $eventObjt->module() !== null
    ) {
        $target = str_replace(
            '_data_',
            $eventObjt->module()->lastValue(),
            $target
        );
    } else {
        $target = str_replace(
            '_data_',
            __('N/A'),
            $target
        );
    }

    if (strpos($target, '_moduledescription_') !== false
        && $eventObjt !== null
        && $eventObjt->module() !== null
    ) {
        $target = str_replace(
            '_moduledescription_',
            io_safe_output($eventObjt->module()->descripcion()),
            $target
        );
    } else {
        $target = str_replace(
            '_moduledescription_',
            __('N/A'),
            $target
        );
    }

    if (strpos($target, '_owner_user_') !== false) {
        if (empty($event['owner_user']) === true) {
            $target = str_replace('_owner_user_', __('N/A'), $target);
        } else {
            $target = str_replace('_owner_user_', $event['owner_user'], $target);
        }
    }

    if (strpos($target, '_event_status_') !== false) {
        $event_st = events_display_status($event['estado']);
        $target = str_replace('_event_status_', $event_st['title'], $target);
    }

    if (strpos($target, '_group_custom_id_') !== false) {
        $group_custom_id = db_get_value_sql(
            sprintf(
                'SELECT custom_id FROM tgrupo WHERE id_grupo=%s',
                $event['id_grupo']
            )
        );
        $event_st = events_display_status($event['estado']);
        $target = str_replace('_group_custom_id_', $group_custom_id, $target);
    }

    // Parse the event custom data.
    if (empty($event['custom_data']) === false) {
        $custom_data = json_decode($event['custom_data']);
        foreach ($custom_data as $key => $value) {
            if (is_array($value) === true) {
                foreach ($value as $k => $v) {
                    $target = str_replace('_customdata_'.$k.'_', $v, $target);
                }
            } else {
                $target = str_replace('_customdata_'.$key.'_', $value, $target);
            }
        }

        if (strpos($target, '_customdata_json_') !== false) {
            $target = str_replace('_customdata_json_', json_encode($custom_data), $target);
        }

        if (strpos($target, '_customdata_text_') !== false) {
            $text = '';
            foreach ($custom_data as $key => $value) {
                $text .= $key.': '.$value.PHP_EOL;
            }

            $target = str_replace('_customdata_text_', $text, $target);
        }
    }

    // This will replace the macro with the current logged user.
    if (strpos($target, '_current_user_') !== false) {
        $target = str_replace('_current_user_', $config['id_user'], $target);
    }

    // This will replace the macro with the command timeout value.
    if (strpos($target, '_command_timeout_') !== false) {
        $target = str_replace(
            '_command_timeout_',
            $event_response['command_timeout'],
            $target
        );
    }

    if (strpos($target, '_owner_username_') !== false) {
        if (empty($event['owner_user']) === false) {
            $fullname = users_get_user_by_id($event['owner_user']);
            $target = str_replace(
                '_owner_username_',
                io_safe_output($fullname['fullname']),
                $target
            );
        } else {
            $target = str_replace('_owner_username_', __('N/A'), $target);
        }
    }

    if (strpos($target, '_current_username_') !== false) {
        $fullname = users_get_user_by_id($config['id_user']);
        $target = str_replace(
            '_current_username_',
            io_safe_output($fullname['fullname']),
            $target
        );
    }

    if (is_metaconsole() === true
        && strpos($target, '_node_id_') !== false
    ) {
        $target = str_replace(
            '_node_id_',
            $server_id,
            $target
        );
    }

    if (is_metaconsole() === true
        && strpos($target, '_node_name_') !== false
    ) {
        $target = str_replace(
            '_node_name_',
            $server_name,
            $target
        );
    }

    return $target;
}


/**
 * Generates 'custom field' page for event viewer.
 *
 * @param array $event Event to be displayed.
 *
 * @return string HTML.
 */
function events_page_custom_fields($event)
{
    global $config;

    // Custom fields.
    $table = new stdClass;
    $table->cellspacing = 2;
    $table->cellpadding = 2;
    $table->width = '100%';
    $table->data = [];
    $table->head = [];
    $table->class = 'table_modal_alternate';

    $all_customs_fields = (bool) check_acl(
        $config['id_user'],
        $event['id_grupo'],
        'AW'
    );

    if ($all_customs_fields) {
        $fields = db_get_all_rows_filter('tagent_custom_fields');
    } else {
        $fields = db_get_all_rows_filter(
            'tagent_custom_fields',
            ['display_on_front' => 1]
        );
    }

    if ($event['id_agente'] == 0) {
        $fields_data = [];
    } else {
        $fields_data = db_get_all_rows_filter('tagent_custom_data', ['id_agent' => $event['id_agente']]);
        if (is_array($fields_data)) {
            $fields_data_aux = [];
            foreach ($fields_data as $fd) {
                $fields_data_aux[$fd['id_field']] = $fd['description'];
            }

            $fields_data = $fields_data_aux;
        }
    }

    foreach ($fields as $field) {
        // Owner.
        $data = [];
        $data[0] = $field['name'];

        if (empty($fields_data[$field['id_field']])) {
            $data[1] = '<i>'.__('N/A').'</i>';
        } else {
            if ($field['is_password_type']) {
                $data[1] = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
            } else {
                $data[1] = ui_bbcode_to_html($fields_data[$field['id_field']]);
            }
        }

        $field['id_field'];

        $table->data[] = $data;
    }

    $custom_fields = '<div id="extended_event_custom_fields_page" class="extended_event_pages">'.html_print_table($table, true).'</div>';

    return $custom_fields;
}


/**
 * Retrieves extended information of given event.
 *
 * @param integer $id_evento Target event.
 *
 * @return mixed array Of extended events or false if error.
 */
function events_get_extended_events(int $id_evento)
{
    return db_get_all_rows_sql(
        sprintf(
            'SELECT * FROM tevent_extended
            WHERE id_evento=%d ORDER BY utimestamp DESC',
            $id_evento
        )
    );

}


/**
 * Return if event has extended info or not.
 *
 * @param integer $id_event Target event.
 *
 * @return boolean Has extended info or not
 */
function events_has_extended_info(int $id_event)
{
    return (bool) db_get_value_sql(
        sprintf(
            'SELECT count(*) as "n" FROM (
                SELECT *
                FROM tevent_extended WHERE id_evento=%d LIMIT 1) t',
            $id_event
        )
    );
}


/**
 * Generates the 'related' page in event view.
 *
 * @param array  $event  To be displayed.
 * @param string $server Server (if in metaconsole environment).
 *
 * @return string HTML to be displayed.
 */
function events_page_related($event, $server='')
{
    $html = '<div id="extended_event_related_page" class="extended_event_pages">';
    $html .= '<h4>'.__('Extended information').'</h4>';
    $html .= '<div id="related_data"><p>'.__('Loading').'...</p></div>';
    $html .= '</div>';

    return $html;
}


/**
 * Generates the 'details' page in event view.
 *
 * @param array   $event  To be displayed.
 * @param integer $server Server (if in metaconsole environment).
 *
 * @return string HTML to be displayed.
 */
function events_page_details($event, $server_id=0)
{
    global $img_sev;
    global $config;

    // If metaconsole switch to node to get details and custom fields.
    $hashstring = '';
    $serverstring = '';
    if (is_metaconsole() === true && empty($server_id) === false) {
        $server = metaconsole_get_connection_by_id($server_id);
        $hashdata = metaconsole_get_server_hashdata($server);
        $hashstring = '&amp;loginhash=auto&loginhash_data='.$hashdata.'&loginhash_user='.str_rot13($config['id_user']);
        $serverstring = $server['server_url'].'/';

        if (metaconsole_connect($server) !== NOERR) {
            return ui_print_error_message(__('There was an error connecting to the node'), '', true);
        }
    }

    $table_class = 'table_modal_alternate';

    // Details.
    $table_details = new stdClass;
    $table_details->width = '100%';
    $table_details->data = [];
    $table_details->head = [];
    $table_details->cellspacing = 0;
    $table_details->cellpadding = 0;
    $table_details->class = $table_class;

    if ($event['id_agente'] != 0) {
        $agent = db_get_row('tagente', 'id_agente', $event['id_agente']);
    } else {
        $agent = [];
    }

    $data[0] = '<span class="subsection_header_title">'.__('Agent details').'</span>';
    $data[1] = empty($agent) ? '<i>'.__('N/A').'</i>' : '';
    $table_details->data[] = $data;

    if (empty($agent) === false) {
        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Name').'</div>';
        if (can_user_access_node() && is_metaconsole() && empty($event['server_id']) === true) {
            $data[1] = ui_print_truncate_text(
                $agent['alias'],
                'agent_medium',
                true,
                true,
                true
            ).ui_print_help_tip(__('This agent belongs to metaconsole, is not possible display it'), true);
        } else if (can_user_access_node() && is_metaconsole()) {
            // Workaround to pass login hash data in POST body instead of directly in the URL.
            parse_str($hashstring, $url_hash_array);
            $redirection_form = "<form id='agent-redirection' method='POST' action='".$serverstring.'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$event['id_agente']."'>";
            $redirection_form .= html_print_input_hidden(
                'loginhash',
                $url_hash_array['loginhash'],
                true
            );
            $redirection_form .= html_print_input_hidden(
                'loginhash_data',
                $url_hash_array['loginhash_data'],
                true
            );
            $redirection_form .= html_print_input_hidden(
                'loginhash_user',
                $url_hash_array['loginhash_user'],
                true
            );
            $redirection_form .= '</form>';

            $data[1] = $redirection_form;
            $data[1] .= "<a target=_blank onclick='event.preventDefault(); document.getElementById(\"agent-redirection\").submit();' href='#'>";
            $data[1] .= '<b>'.$agent['alias'].'</b>';
            $data[1] .= '</a>';
        } else if (can_user_access_node()) {
            $data[1] = ui_print_agent_name(
                $event['id_agente'],
                true,
                'agent_medium',
                '',
                false,
                $serverstring,
                $hashstring,
                $agent['alias']
            );
        } else {
            $data[1] = ui_print_truncate_text(
                $agent['alias'],
                'agent_medium',
                true,
                true,
                true
            );
        }

        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('IP Address').'</div>';
        $data[1] = empty($agent['direccion']) ? '<i>'.__('N/A').'</i>' : $agent['direccion'];
        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('OS').'</div>';
        $data[1] = '<div style="display:flex"><div class="main_menu_icon invert_filter">'.ui_print_os_icon($agent['id_os'], false, true).'</div>';
        $data[1] .= get_os_name($agent['id_os']);
        if (empty($agent['os_version']) === false) {
            $data[1] .= ' ('.$agent['os_version'].')';
        }

        $data[1] .= '</div>';

        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Last contact').'</div>';

        $user_timezone = users_get_user_by_id($_SESSION['id_usuario'])['timezone'];
        if (!$user_timezone) {
            $timezone = timezone_open(date_default_timezone_get());
            $datetime_eur = date_create('now', timezone_open($config['timezone']));
            $dif = timezone_offset_get($timezone, $datetime_eur);
            date($config['date_format'], $dif);
            if (!date('I')) {
                // For summer -3600sec.
                $dif -= 3600;
            }

            $total_sec = strtotime($agent['ultimo_contacto']);
            $total_sec += $dif;
            $last_contact = date($config['date_format'], $total_sec);
            $last_contact_value = ui_print_timestamp($last_contact, true);
        } else {
            $user_timezone = users_get_user_by_id($_SESSION['id_usuario'])['timezone'];
            date_default_timezone_set($user_timezone);

            $last_contact_value = human_time_comparation(strtotime($agent['ultimo_contacto']), 'large');
        }

        $data[1] = ($agent['ultimo_contacto'] == '1970-01-01 00:00:00') ? '<i>'.__('N/A').'</i>' : $last_contact_value;
        $table_details->data[] = $data;

        $user_timezone = users_get_user_by_id($_SESSION['id_usuario'])['timezone'];
        if (!$user_timezone) {
            $timezone = timezone_open(date_default_timezone_get());
            $datetime_eur = date_create('now', timezone_open($config['timezone']));
            $dif = timezone_offset_get($timezone, $datetime_eur);
            date($config['date_format'], $dif);
            if (!date('I')) {
                // For summer -3600sec.
                $dif -= 3600;
            }

            $total_sec = strtotime($agent['ultimo_contacto_remoto']);
            $total_sec += $dif;
            $lr_contact = date($config['date_format'], $total_sec);
        } else {
            $lr_contact = date($config['date_format'], strtotime($agent['ultimo_contacto_remoto']));
        }

        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Last remote contact').'</div>';
        $data[1] = ($agent['ultimo_contacto_remoto'] == '1970-01-01 00:00:00') ? '<i>'.__('N/A').'</i>' : $lr_contact;
        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Custom fields').'</div>';
        $data[1] = html_print_button(
            __('View custom fields'),
            'custom_button',
            false,
            '$("#link_custom_fields").trigger("click");',
            [ 'mode' => 'link' ],
            true
        );
        $table_details->data[] = $data;
    }

    if ($event['id_agentmodule'] != 0) {
        $module = db_get_row_filter(
            'tagente_modulo',
            [
                'id_agente_modulo' => $event['id_agentmodule'],
                'delete_pending'   => 0,
            ]
        );
    } else {
        $module = [];
    }

    $data = [];
    $data[0] = '<span class="subsection_header_title">'.__('Module details').'<span>';
    $data[1] = (empty($module) === true) ? '<i>'.__('N/A').'</i>' : '';
    $table_details->data[] = $data;

    if (empty($module) === false) {
        // Module name.
        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Name').'</div>';
        $data[1] = $module['nombre'];
        $table_details->data[] = $data;

        // Module group.
        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Module group').'</div>';
        $id_module_group = $module['id_module_group'];
        if ($id_module_group == 0) {
            $data[1] = __('No assigned');
        } else {
            $module_group = db_get_value(
                'name',
                'tmodule_group',
                'id_mg',
                $id_module_group
            );
            $data[1] = '<a href="'.$serverstring.'index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;status=-1&amp;modulegroup='.$id_module_group.$hashstring.'">';
            $data[1] .= $module_group;
            $data[1] .= '</a>';
        }

        $table_details->data[] = $data;

        // ACL.
        $acl_graph = false;
        if (empty($agent['id_grupo']) === false) {
            $acl_graph = check_acl(
                $config['id_user'],
                $agent['id_grupo'],
                'RR'
            );
        }

        if ($acl_graph) {
            $data = [];
            $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Graph').'</div>';

            $module_type = -1;
            if (isset($module['module_type'])) {
                $module_type = $module['module_type'];
            }

            $graph_type = return_graphtype($module_type);
            $url = ui_get_full_url(
                'operation/agentes/stat_win.php',
                false,
                false,
                false
            );
            $handle = dechex(
                crc32($module['id_agente_modulo'].$module['nombre'])
            );
            $win_handle = 'day_'.$handle;

            $graph_params = [
                'type'    => $graph_type,
                'period'  => SECONDS_1DAY,
                'id'      => $module['id_agente_modulo'],
                'refresh' => SECONDS_10MINUTES,
            ];

            if (is_metaconsole() === true && empty($server_id) === false) {
                // Set the server id.
                $graph_params['server'] = $server['id'];
            }

            $graph_params_str = http_build_query($graph_params);

            $link = 'winopeng_var("'.$url.'?'.$graph_params_str.'","'.$win_handle.'", 800, 480)';
            $data[1] = html_print_button(__('View graph'), 'view_graph_button', false, $link, ['mode' => 'link'], true);
            $table_details->data[] = $data;
        }
    }

    $data = [];
    $data[0] = __('Alert details');
    $data[1] = ($event['id_alert_am'] == 0) ? '<i>'.__('N/A').'</i>' : '';
    $table_details->data[] = $data;

    if ($event['id_alert_am'] != 0) {
        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Source').'</div>';
        $data[1] = '<a href="'.$serverstring.'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event['id_agente'].'&amp;tab=alert'.$hashstring.'">';
        $standby = db_get_value('standby', 'talert_template_modules', 'id', $event['id_alert_am']);
        if (!$standby) {
            $data[1] .= html_print_image(
                'images/alert@svg.svg',
                true,
                [
                    'title' => __('Go to data overview'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            );
        } else {
            $data[1] .= html_print_image(
                'images/alert@svg.svg',
                true,
                [
                    'title' => __('Go to data overview'),
                    'class' => 'invert_filter main_menu_icon',
                    'style' => 'opacity: .5',
                ]
            );
        }

        $sql = 'SELECT name
			FROM talert_templates
			WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = '.$event['id_alert_am'].');';

        $templateName = db_get_sql($sql);

        $data[1] .= $templateName;

        $data[1] .= '</a>';

        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div class="normal_weight mrgn_lft_20px">'.__('Priority').'</div>';

        $priority_code = db_get_value('priority', 'talert_template_modules', 'id', $event['id_alert_am']);
        $alert_priority = get_priority_name($priority_code);
        $data[1] = html_print_image(
            $img_sev,
            true,
            [
                'class'  => 'image_status',
                'width'  => 61,
                'height' => 28,
                'title'  => $alert_priority,
                'style'  => 'vertical-align:text-bottom',
            ]
        );
        $data[1] .= ' '.$alert_priority;

        $table_details->data[] = $data;
    }

    $data = [];
    $data[0] = __('Instructions');
    $data[1] = html_entity_decode(
        events_display_instructions(
            $event['event_type'],
            $event,
            true,
            $event
        )
    );
    $table_details->data[] = $data;

    $data = [];
    $data[0] = __('Extra id');
    if ($event['id_extra'] != '') {
        $data[1] = $event['id_extra'];
    } else {
        $data[1] = '<i>'.__('N/A').'</i>';
    }

    $table_details->data[] = $data;

    $data = [];
    $data[0] = __('Source');
    if ($event['source'] != '') {
        $data[1] = $event['source'];
    } else {
        $data[1] = '<i>'.__('N/A').'</i>';
    }

    $table_details->data[] = $data;
    $readonly = true;
    if (check_acl($config['id_user'], 0, 'EW')) {
        $readonly = false;
    }

    $data = [];
    $data[0] = __('Event Custom ID');
    $data[1] = '<div class="flex-row-center">'.html_print_input_text('event_custom_id', $event['event_custom_id'], '', false, 255, true, $readonly, false, '', 'w60p');
    if ($readonly === false) {
        $data[1] .= html_print_button(
            __('Update'),
            'update_event_custom_id',
            false,
            'update_event_custom_id('.$event['id_evento'].', '.$event['server_id'].');',
            [
                'icon' => 'next',
                'mode' => 'link',
            ],
            true
        );
    }

    $data[1] .= '</div>';
    $table_details->data[] = $data;

    $details = '<div id="extended_event_details_page" class="extended_event_pages">'.html_print_table($table_details, true).'</div>';

    if (is_metaconsole() === true && empty($server_id) === false) {
        metaconsole_restore_db();
    }

    return $details;
}


/**
 * Generates content for 'custom data' page in event viewer.
 *
 * @param array $event Event.
 *
 * @return string HTML.
 */
function events_page_custom_data($event)
{
    global $config;

    //
    // Custom data.
    //
    if ($event['custom_data'] == '') {
        return '';
    }

    $table = new stdClass();

    $table->width = '100%';
    $table->data = [];
    $table->head = [];
    $table->class = 'table_modal_alternate';

    $json_custom_data = $event['custom_data'];
    $custom_data = json_decode($json_custom_data);

    if ($custom_data === null) {
        // Try again because is possible that info not come coded.
        $custom_data = json_decode(io_safe_output($event['custom_data']));

        if ($custom_data === null) {
            return '<div id="extended_event_custom_data_page" class="extended_event_pages">'.__('Invalid custom data: %s', $json_custom_data).'</div>';
        }
    }

    $i = 0;
    foreach ($custom_data as $field => $value) {
        $table->data[$i][0] = ucfirst(io_safe_output($field));

        if (is_array($value) === true) {
            $table->data[$i][1] = '<ul>';
            foreach ($value as $individualValue) {
                $table->data[$i][1] .= sprintf('<li>%s</li>', $individualValue);
            }

            $table->data[$i][1] .= '</ul>';
        } else {
            $table->data[$i][1] = io_safe_output($value);
        }

        $i++;
    }

    $custom_data = '<div id="extended_event_custom_data_page" class="extended_event_pages">'.html_print_table($table, true).'</div>';

    return $custom_data;
}


/**
 * Get the event name from tevento and display it in console.
 *
 * @param string $db_name Target event name.
 *
 * @return string Event name.
 */
function events_display_name($db_name='')
{
    return io_safe_output(str_replace('&#x0a;', '<br>', $db_name));
}


/**
 * Get the image and status value of event.
 *
 * @param integer $status Status.
 *
 * @return string Image path.
 */
function events_display_status($status)
{
    switch ($status) {
        case 0:
        return [
            'img'   => 'images/star@svg.svg',
            'title' => __('New event'),
        ];

        case 1:
        return [
            'img'   => 'images/validate.svg',
            'title' => __('Event validated'),
        ];

        case 2:
        return [
            'img'   => 'images/clock.svg',
            'title' => __('Event in process'),
        ];

        default:
            // Ignore.
        break;
    }
}


/**
 * Get the instruction of an event.
 *
 * @param string  $event_type Type of event.
 * @param array   $inst       Array with unknown warning and critical
 *                            instructions.
 * @param boolean $italic     Display N/A between italic html marks if
 *                            instruction is not found.
 * @param array   $eventObj   Event object.
 *
 * @return string Safe output.
 */
function events_display_instructions($event_type='', $inst=[], $italic=true, $event=null)
{
    if ($event_type === 'alert_fired') {
        if ($event !== null) {
            // Retrieve alert template type.
            if ((bool) is_metaconsole() === true
                && $event['server_id'] > 0
            ) {
                 enterprise_include_once('include/functions_metaconsole.php');
                $r = enterprise_hook(
                    'metaconsole_connect',
                    [
                        null,
                        $event['server_id'],
                    ]
                );
            }

            $event_type = db_get_value_sql(
                sprintf(
                    'SELECT ta.type
                    FROM talert_templates ta
                    INNER JOIN talert_template_modules tam
                        ON ta.id=tam.id_alert_template
                    WHERE tam.id = %d',
                    $event['id_alert_am']
                )
            );

            if ((bool) is_metaconsole() === true
                && $event['server_id'] > 0
            ) {
                enterprise_hook('metaconsole_restore_db');
            }
        }
    }

    switch ($event_type) {
        case 'going_unknown':
        case 'unknown':
            if ($inst['unknown_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['unknown_instructions']));
            }
        break;

        case 'going_up_warning':
        case 'going_down_warning':
        case 'warning':
            if ($inst['warning_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['warning_instructions']));
            }
        break;

        case 'going_up_critical':
        case 'going_down_critical':
        case 'critical':
            if ($inst['critical_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['critical_instructions']));
            }
        break;

        case 'system':
            $data = [];
            if ($inst['critical_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['critical_instructions']));
            }

            if ($inst['warning_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['warning_instructions']));
            }

            if ($inst['unknown_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['unknown_instructions']));
            }
        break;

        default:
            // Ignore.
        break;
    }

    $na_return = ($italic === true) ? '<i>'.__('N/A').'</i>' : __('N/A');

    return $na_return;
}


/**
 * Generates 'general' page for events viewer.
 *
 * @param array $event Event.
 *
 * @return string HTML.
 */
function events_page_general($event)
{
    global $img_sev;
    global $config;
    global $group_rep;

    $secondary_groups = '';
    if (isset($event['id_agente']) && $event['id_agente'] > 0) {
        enterprise_include_once('include/functions_agents.php');
        $secondary_groups_selected = enterprise_hook('agents_get_secondary_groups', [$event['id_agente'], is_metaconsole()]);
        if (empty($secondary_groups_selected['for_select']) === false) {
            $secondary_groups = implode(', ', $secondary_groups_selected['for_select']);
        }
    }

    // General.
    $table_general = new stdClass;
    $table_general->cellspacing = 0;
    $table_general->cellpadding = 0;
    $table_general->width = '100%';
    $table_general->data = [];
    $table_general->head = [];
    $table_general->class = 'table_modal_alternate';

    $data = [];
    $data[0] = __('Event ID');
    $table_event_id = (isset($event['max_id_evento']) === true) ? $event['max_id_evento'] : $event['id_evento'];
    $data[1] = '#'.$table_event_id;
    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Event name');
    $data[1] = '<span class="break_word">'.events_display_name($event['evento']).'</span>';
    $table_general->data[] = $data;

    // Show server name in metaconsole.
    if (is_metaconsole() === true && $event['server_name'] !== '') {
        $data = [];
        $data[0] = __('Node');
        $data[1] = '<span class="break_word">'.$event['server_name'].'</span>';
        $table_general->data[] = $data;
    }

    $data = [];
    $data[0] = __('Timestamp');

    if ($event['event_rep'] > 1) {
        $data[1] = __('First event').': ';
        $data[1] .= date($config['date_format'], $event['timestamp_first']);
        $data[1] .= '<br>';
        $data[1] .= __('Last event').': ';
        $data[1] .= date($config['date_format'], $event['timestamp_last']);
    } else {
        $user_timezone = users_get_user_by_id($_SESSION['id_usuario'])['timezone'];
        if ($user_timezone) {
            date_default_timezone_set($user_timezone);
        } else {
            date_default_timezone_set($config['timezone']);
        }

        $data[1] = date($config['date_format'], $event['utimestamp']);
    }

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Owner');
    if ($event['owner_user'] == -1) {
        $data[1] = '<i>'.__('N/A').'</i>';
    } else {
        $user_owner = db_get_value(
            'fullname',
            'tusuario',
            'id_user',
            $event['owner_user']
        );
        if (empty($user_owner) === true) {
            $user_owner = $event['owner_user'];
        }

        $data[1] = $user_owner;
    }

    if (is_metaconsole() === true && $event['server_name'] !== '') {
        $table_general->cellclass[4][1] = 'general_owner';
    } else {
        $table_general->cellclass[3][1] = 'general_owner';
    }

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Type');
    $data[1] = events_print_type_description($event['event_type'], true);
    $data[2] = events_print_type_img(
        $event['event_type'],
        true
    );

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Repeated');
    if ($group_rep != 0) {
        if ($event['event_rep'] <= 1) {
            $data[1] = '<i>'.__('No').'</i>';
        } else {
            $data[1] = sprintf('%d Times', $event['event_rep']);
        }
    } else {
        $data[1] = '<i>'.__('No').'</i>';
    }

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Severity');
    $event_criticity = get_priority_name($event['criticity']);
    $data[1] = $event_criticity;
    $data[2] = html_print_image(
        $img_sev,
        true,
        [
            'class'  => 'image_status',
            'width'  => 61,
            'height' => 28,
            'title'  => $event_criticity,
        ]
    );
    $table_general->data[] = $data;

    // Get Status.
    $event_st = events_display_status($event['estado']);

    $data = [];

    $table_general->rowid[count($table_general->data)] = 'general_status';
    $table_general->cellclass[count($table_general->data)][1] = 'general_status';
    $data[0] = __('Status');
    $data[1] = $event_st['title'];
    $data[2] = html_print_image($event_st['img'], true, [ 'class' => 'invert_filter main_menu_icon']);
    $table_general->data[] = $data;

    // If event is validated, show who and when acknowleded it.
    $table_general->cellclass[count($table_general->data)][1] = 'general_acknowleded';

    $data = [];

    if (empty($event['server_id']) === false && (int) $event['server_id'] > 0
        && is_metaconsole() === true
    ) {
        $node_connect = new Node($event['server_id']);
        $node_connect->connect();
    }

    $data[0] = __('Acknowledged by');
    $data[1] = events_page_general_acknowledged($event['id_evento']);

    if (empty($event['server_id']) === false && (int) $event['server_id'] > 0
        && is_metaconsole() === true
    ) {
        $node_connect->disconnect();
    }

    $table_general->cellclass[7][1] = 'general_status';

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Group');
    $data[1] = groups_get_name($event['id_grupo']);
    if (!$config['show_group_name']) {
        $data[2] = ui_print_group_icon($event['id_grupo'], true);
    }

    $table_general->data[] = $data;

    if (!empty($secondary_groups)) {
        $data = [];
        $data[0] = __('Secondary groups');
        $data[1] = $secondary_groups;

        $table_general->data[] = $data;
    }

    $data = [];
    $data[0] = __('Contact');
    $data[1] = '';
    $contact = db_get_value('contact', 'tgrupo', 'id_grupo', $event['id_grupo']);
    if (empty($contact) === true) {
        $data[1] = '<i>'.__('N/A').'</i>';
    } else {
        $data[1] = $contact;
    }

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Tags');

    if ($event['tags'] != '') {
        $tags = tags_get_tags_formatted($event['tags']);

        $data[1] = $tags;
    } else {
        $data[1] = '<i>'.__('N/A').'</i>';
    }

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('ID extra');
    if ($event['id_extra'] != '') {
        $data[1] = $event['id_extra'];
    } else {
        $data[1] = '<i>'.__('N/A').'</i>';
    }

    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Module custom ID');
    if ($event['module_custom_id'] != '') {
        $data[1] = $event['module_custom_id'];
    } else {
        $data[1] = '<i>'.__('N/A').'</i>';
    }

    $table_general->data[] = $data;

    $table_data = $table_general->data;
    if (is_array($table_data) === true) {
        $table_data_total = count($table_data);
    } else {
        $table_data_total = -1;
    }

    for ($i = 0; $i <= $table_data_total; $i++) {
        if (isset($table_data[$i]) === true
            && is_array($table_data[$i]) === true
            && count($table_data[$i]) === 2
        ) {
            $table_general->colspan[$i][1] = 2;
            $table_general->style[2] = 'text-align:left; width:10%;';
        }
    }

    $general = '<div id="extended_event_general_page" class="extended_event_pages">';
    $general .= html_print_table($table_general, true);
    $general .= '</div>';

    return $general;
}


/**
 * Return Acknowledged by value
 *
 * @param integer $event_id Event_id to return Acknowledged.
 *
 * @return string String with user and date.
 */
function events_page_general_acknowledged($event_id)
{
    global $config;
    $Acknowledged = '';
    $event = db_get_row('tevento', 'id_evento', $event_id);
    if ($event !== false && ($event['estado'] == 1 || $event['estado'] == 2)) {
        if (empty($event['id_usuario']) === true) {
            $user_ack = __('Autovalidated');
        } else {
            $user_ack = db_get_value(
                'fullname',
                'tusuario',
                'id_user',
                $config['id_user']
            );

            if (empty($user_ack) === true) {
                $user_ack = $config['id_user'];
            }
        }

        $Acknowledged = $user_ack.'&nbsp;(&nbsp;';
        if ($event['ack_utimestamp'] !== false
            && $event['ack_utimestamp'] !== 'false'
        ) {
            $Acknowledged .= date(
                $config['date_format'],
                $event['ack_utimestamp']
            );
        }

        $Acknowledged .= '&nbsp;)&nbsp;';
    } else {
        $Acknowledged = '<i>'.__('N/A').'</i>';
    }

    return $Acknowledged;
}


/**
 * Generate 'comments' page for event viewer.
 *
 * @param array   $event           Event.
 * @param boolean $ajax            If the query come from AJAX.
 * @param boolean $groupedComments If the event must shown comments grouped.
 *
 * @return string HTML.
 */
function events_page_comments($event, $groupedComments=[], $filter=null)
{
    // Comments.
    global $config;

    $table_comments = new stdClass;
    $table_comments->width = '100%';
    $table_comments->data = [];
    $table_comments->head = [];
    $table_comments->class = 'table_modal_alternate';

    $comments = $groupedComments;
    if (empty($comments) === true) {
        $table_comments->style[0] = 'text-align:left;';
        $table_comments->colspan[0][0] = 2;
        $data = [];
        $data[0] = __('There are no comments');
        $table_comments->data[] = $data;
    } else {
        if (is_array($comments) === true) {
            $comments_array = $comments;
        } else {
            $comments = str_replace(["\n", '&#x0a;'], '<br>', $comments);
            // If comments are not stored in json, the format is old.
            $comments_array[] = io_safe_output(json_decode($comments, true));
        }

        foreach ($comments_array as $comm) {
            $eventIdExplanation = (empty($groupedComments) === false) ? sprintf(' (#%d)', $comm['id_event']) : '';
            $data[0] = sprintf(
                '<b>%s %s %s%s</b>',
                $comm['action'],
                __('by'),
                get_user_fullname(io_safe_input($comm['id_user'])).' ('.io_safe_input($comm['id_user']).')',
                $eventIdExplanation
            );

            $data[0] .= sprintf(
                '<br><br><i>%s</i>',
                date($config['date_format'], $comm['utimestamp'])
            );

            $data[1] = '<p class="break_word">'.stripslashes(str_replace(['\n', '\r'], '<br/>', $comm['comment'])).'</p>';

            $table_comments->data[] = $data;
        }
    }

    $comments_filter = '<div class="flex align-center">';
    $comments_filter .= html_print_label_input_block(
        null,
        html_print_extended_select_for_time(
            'comments_events_max_hours_old',
            $filter['event_view_hr_cs'],
            '',
            __('Default'),
            -2,
            false,
            true,
            false,
            true,
            '',
            false,
            [
                SECONDS_1HOUR   => __('1 hour'),
                SECONDS_6HOURS  => __('6 hours'),
                SECONDS_12HOURS => __('12 hours'),
                SECONDS_1DAY    => __('24 hours'),
                SECONDS_2DAY    => __('48 hours'),
            ],
            '',
            false,
            0,
            [ SECONDS_1HOUR => __('hours') ],
        )
    );

    $eventb64 = base64_encode(json_encode($event));
    $filterb64 = base64_encode(json_encode($filter));
    $comments_filter .= html_print_submit_button(
        __('Filter'),
        'filter_comments_button',
        false,
        [
            'class'   => 'mini mrgn_lft_15px',
            'icon'    => 'search',
            'onclick' => 'get_table_events_tabs("'.$eventb64.'","'.$filterb64.'")',
        ],
        true
    );
    $comments_filter .= '</div>';

    if (((tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EM',
        (isset($event['clean_tags']) === true) ? $event['clean_tags'] : [],
        []
    )) || (tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EW',
        (isset($event['clean_tags']) === true) ? $event['clean_tags'] : [],
        []
    )))
    ) {
        $event['evento'] = io_safe_output($event['evento']);
        $comments_form = '<br><div id="comments_form" style="width:98%;">';
        $comments_form .= html_print_textarea(
            'comment',
            3,
            10,
            '',
            'class="comments_form"',
            true
        );

        $comments_form .= '<br>';
        $comments_form .= '<div class="mrgn_top_10px container-filter-buttons">';
        $comments_form .= $comments_filter;
        $comments_form .= '<div>';
        $comments_form .= html_print_button(
            __('Add comment'),
            'comment_button',
            false,
            'event_comment("'.base64_encode(json_encode($event)).'");',
            [
                'icon' => 'next',
                'mode' => 'mini secondary',
            ],
            true
        );
        $comments_form .= '</div>';
        $comments_form .= '</div>';

        $comments_form .= '<br></div>';
    } else {
        $comments_form = $comments_filter;
    }

    return $comments_form.html_print_table($table_comments, true);
}


/**
 * Retrieve event tags (cleaned).
 *
 * @param string $tags Tags.
 *
 * @return array of Tags.
 */
function events_clean_tags($tags)
{
    if (empty($tags)) {
        return [];
    }

    $event_tags = tags_get_tags_formatted($tags, false);
    $event_tags = str_replace(' ', '', $event_tags);
    $event_tags = io_safe_input($event_tags);

    return explode(',', $event_tags);
}


/**
 * Get all the events happened in a group during a period of time.
 *
 * @param array $data Data.
 *
 * @return array An array with all the events happened.
 */
function events_get_count_events_validated_by_user($data)
{
    $data_graph_by_user = [];
    if (empty($data) === false) {
        foreach ($data as $value) {
            $k = $value['id_usuario'];

            if (empty($k) === true
                && ($value['estado'] == EVENT_VALIDATE
                || $value['status'] == EVENT_VALIDATE)
            ) {
                if (isset($data_graph_by_user['System']) === true) {
                    $data_graph_by_user['System']++;
                } else {
                    $data_graph_by_user['System'] = 1;
                }
            } else if (empty($k) === false) {
                if (isset($data_graph_by_user[$k]) === true) {
                    $data_graph_by_user[$k]++;
                } else {
                    $data_graph_by_user[$k] = 1;
                }
            }
        }

        if (empty($data_graph_by_user) === false) {
            $sql = sprintf(
                'SELECT fullname, id_user
                FROM tusuario
                WHERE id_user IN ("%s")',
                implode('","', array_keys($data_graph_by_user))
            );

            $fullnames = db_get_all_rows_sql($sql);

            if ($fullnames !== false
                && empty($fullnames) === false
            ) {
                foreach ($fullnames as $value) {
                    if (isset($data_graph_by_user[$value['id_user']]) === true) {
                        $data_graph_by_user[io_safe_output($value['fullname'])] = $data_graph_by_user[$value['id_user']];
                        unset($data_graph_by_user[$value['id_user']]);
                    }
                }
            }
        }
    }

    return $data_graph_by_user;
}


/**
 * Retrieves SQL for custom order.
 *
 * @param string  $sort_field  Field.
 * @param string  $sort        Order.
 * @param integer $group_rep   Group field.
 * @param boolean $only-fields Return only fields.
 *
 * @return string SQL.
 */
function events_get_sql_order($sort_field='timestamp', $sort='DESC', $group_rep=EVENT_GROUP_REP_ALL, $only_fields=false)
{
    $sort_field_translated = $sort_field;
    switch ($sort_field) {
        case 'event_id':
            $sort_field_translated = 'id_evento';
        break;

        case 'event_name':
            $sort_field_translated = 'evento';
        break;

        case 'status':
            $sort_field_translated = 'estado';
        break;

        case 'agent_id':
            $sort_field_translated = 'id_agente';
        break;

        case 'timestamp':
            $sort_field_translated = ($group_rep == EVENT_GROUP_REP_ALL) ? 'timestamp' : 'timestamp_last';
        break;

        case 'user_id':
            $sort_field_translated = 'id_usuario';
        break;

        case 'owner':
            $sort_field_translated = 'owner_user';
        break;

        case 'group_id':
            $sort_field_translated = 'id_grupo';
        break;

        case 'alert_id':
            $sort_field_translated = 'id_alert_am';
        break;

        case 'comment':
            $sort_field_translated = 'tevent_comment.comment';
        break;

        case 'extra_id':
            $sort_field_translated = 'id_extra';
        break;

        case 'agent_name':
            $sort_field_translated = 'ta.nombre';
        break;

        case 'module_custom_id':
            $sort_field_translated = 'am.custom_id';
        break;

        default:
            $sort_field_translated = $sort_field;
        break;
    }

    if (strtolower($sort) != 'asc' && strtolower($sort) != 'desc') {
        $dir = ($sort == 'up') ? 'ASC' : 'DESC';
    } else {
        $dir = $sort;
    }

    if ($only_fields) {
        return $sort_field_translated.' '.$dir;
    }

    return 'ORDER BY '.$sort_field_translated.' '.$dir;
}


/**
 * SQL left join of event queries to handle secondary groups.
 *
 * @param string $table Table to use based on environment.
 *
 * @return string With the query.
 */
function events_get_secondary_groups_left_join($table)
{
    if ($table == 'tevento') {
        return 'LEFT JOIN tagent_secondary_group tasg ON te.id_agente = tasg.id_agent';
    }

    return 'LEFT JOIN tmetaconsole_agent_secondary_group tasg
		ON te.id_agente = tasg.id_tagente AND te.server_id = tasg.id_tmetaconsole_setup';
}


/**
 * Replace macros in any string given an event id.
 * If server_id > 0, it's a metaconsole query.
 *
 * @param integer $event_id Event identifier.
 * @param integer $value    String value in which we want to apply macros.
 *
 * @return string The response text with the macros applied.
 */
function events_get_field_value_by_event_id(
    int $event_id,
    $value
) {
    global $config;

    $event = db_get_row('tevento', 'id_evento', $event_id);

    // Replace each macro.
    if (strpos($value, '_agent_address_') !== false) {
        $agente_table_name = 'tagente';
        $filter = ['id_agente' => $event['id_agente']];

        $ip = db_get_value_filter('direccion', $agente_table_name, $filter);
        // If agent does not have an IP, display N/A.
        if ($ip === false) {
            $ip = __('N/A');
        }

        $value = str_replace('_agent_address_', $ip, $value);
    }

    if (strpos($value, '_agent_id_') !== false) {
        $value = str_replace('_agent_id_', $event['id_agente'], $value);
    }

    if (strpos($value, '_module_address_') !== false) {
        if ($event['id_agentmodule'] != 0) {
            $module = db_get_row('tagente_modulo', 'id_agente_modulo', $event['id_agentmodule']);
            if (empty($module['ip_target'])) {
                $module['ip_target'] = __('N/A');
            }

            $value = str_replace('_module_address_', $module['ip_target'], $value);
            if (empty($module['nombre'])) {
                $module['nombre'] = __('N/A');
            }
        } else {
            $value = str_replace('_module_address_', __('N/A'), $value);
        }
    }

    if (strpos($value, '_module_name_') !== false) {
        if ($event['id_agentmodule'] != 0) {
            $module = db_get_row('tagente_modulo', 'id_agente_modulo', $event['id_agentmodule']);
            if (empty($module['ip_target'])) {
                $module['ip_target'] = __('N/A');
            }

            $value = str_replace(
                '_module_name_',
                io_safe_output($module['nombre']),
                $value
            );
        } else {
            $value = str_replace('_module_name_', __('N/A'), $value);
        }
    }

    if (strpos($value, '_event_id_') !== false) {
        $value = str_replace('_event_id_', $event['id_evento'], $value);
    }

    if (strpos($value, '_user_id_') !== false) {
        if (!empty($event['id_usuario'])) {
            $value = str_replace('_user_id_', $event['id_usuario'], $value);
        } else {
            $value = str_replace('_user_id_', __('N/A'), $value);
        }
    }

    if (strpos($value, '_group_id_') !== false) {
        $value = str_replace('_group_id_', $event['id_grupo'], $value);
    }

    if (strpos($value, '_group_name_') !== false) {
        $value = str_replace(
            '_group_name_',
            io_safe_output(groups_get_name($event['id_grupo'], true)),
            $value
        );
    }

    if (strpos($value, '_event_utimestamp_') !== false) {
        $value = str_replace(
            '_event_utimestamp_',
            $event['utimestamp'],
            $value
        );
    }

    if (strpos($value, '_event_date_') !== false) {
        $value = str_replace(
            '_event_date_',
            io_safe_output(
                date($config['date_format'], $event['utimestamp'])
            ),
            $value
        );
    }

    if (strpos($value, '_event_text_') !== false) {
        $value = str_replace(
            '_event_text_',
            events_display_name($event['evento']),
            $value
        );
    }

    if (strpos($value, '_event_type_') !== false) {
        $value = str_replace(
            '_event_type_',
            events_print_type_description($event['event_type'], true),
            $value
        );
    }

    if (strpos($value, '_alert_id_') !== false) {
        $value = str_replace(
            '_alert_id_',
            empty($event['is_alert_am']) ? __('N/A') : $event['is_alert_am'],
            $value
        );
    }

    if (strpos($value, '_event_severity_id_') !== false) {
        $value = str_replace('_event_severity_id_', $event['criticity'], $value);
    }

    if (strpos($value, '_event_severity_text_') !== false) {
        $value = str_replace(
            '_event_severity_text_',
            get_priority_name($event['criticity']),
            $value
        );
    }

    if (strpos($value, '_module_id_') !== false) {
        $value = str_replace('_module_id_', $event['id_agentmodule'], $value);
    }

    if (strpos($value, '_event_tags_') !== false) {
        $value = str_replace('_event_tags_', $event['tags'], $value);
    }

    if (strpos($value, '_event_extra_id_') !== false) {
        if (empty($event['id_extra'])) {
            $value = str_replace('_event_extra_id_', __('N/A'), $value);
        } else {
            $value = str_replace('_event_extra_id_', $event['id_extra'], $value);
        }
    }

    if (strpos($value, '_event_source_') !== false) {
        $value = str_replace('_event_source_', $event['source'], $value);
    }

    if (strpos($value, '_event_instruction_') !== false) {
        $value = str_replace(
            '_event_instruction_',
            events_display_instructions(
                $event['event_type'],
                $event,
                false,
                $event
            ),
            $value
        );
    }

    if (strpos($value, '_owner_user_') !== false) {
        if (empty($event['owner_user'])) {
            $value = str_replace('_owner_user_', __('N/A'), $value);
        } else {
            $value = str_replace('_owner_user_', $event['owner_user'], $value);
        }
    }

    if (strpos($value, '_event_status_') !== false) {
        $event_st = events_display_status($event['estado']);
        $value = str_replace('_event_status_', $event_st['title'], $value);
    }

    if (strpos($value, '_group_custom_id_') !== false) {
        $group_custom_id = db_get_value_sql(
            sprintf(
                'SELECT custom_id FROM tgrupo WHERE id_grupo=%s',
                $event['id_grupo']
            )
        );
        $event_st = events_display_status($event['estado']);
        $value = str_replace('_group_custom_id_', $group_custom_id, $value);
    }

    // Parse the event custom data.
    if (!empty($event['custom_data'])) {
        $custom_data = json_decode($event['custom_data']);
        foreach ($custom_data as $key => $val) {
            $value = str_replace('_customdata_'.$key.'_', $val, $value);
        }
    }

    // This will replace the macro with the current logged user.
    if (strpos($value, '_current_user_') !== false) {
        $value = str_replace('_current_user_', $config['id_user'], $value);
    }

    if (strpos($value, '_owner_username_') !== false) {
        if (empty($event['owner_user']) === false) {
            $fullname = users_get_user_by_id($event['owner_user']);
            $value = str_replace(
                '_owner_username_',
                io_safe_output($fullname['fullname']),
                $value
            );
        } else {
            $value = str_replace('_owner_username_', __('N/A'), $value);
        }
    }

    if (strpos($value, '_current_username_') !== false) {
        $fullname = users_get_user_by_id($config['id_user']);
        $value = str_replace(
            '_current_username_',
            io_safe_output($fullname['fullname']),
            $value
        );
    }

    return $value;

}


function events_get_instructions($event, $max_text_length=300)
{
    if (is_array($event) === false) {
        return '';
    }

    switch ($event['event_type']) {
        case 'going_unknown':
            if ($event['unknown_instructions'] != '') {
                $value = str_replace(
                    "\n",
                    '<br>',
                    io_safe_output($event['unknown_instructions'])
                );
            }
        break;

        case 'going_up_warning':
        case 'going_down_warning':
            if ($event['warning_instructions'] != '') {
                $value = str_replace(
                    "\n",
                    '<br>',
                    io_safe_output($event['warning_instructions'])
                );
            }
        break;

        case 'going_up_critical':
        case 'going_down_critical':
            if ($event['critical_instructions'] != '') {
                $value = str_replace(
                    "\n",
                    '<br>',
                    io_safe_output($event['critical_instructions'])
                );
            }
        break;

        default:
            // Not posible.
        break;
    }

    if (isset($value) === false) {
        return '';
    }

    $over_text = io_safe_output($value);
    if (strlen($over_text) > ($max_text_length + 3)) {
        $over_text = substr($over_text, 0, $max_text_length).'...';
    } else {
        return $value;
    }

    $output  = '<div id="hidden_event_instructions_'.$event['id_evento'].'"';
    $output .= ' class="event_instruction">';
    $output .= $value;
    $output .= '</div>';
    $output .= '<span id="value_event_'.$event['id_evento'].'" class="nowrap">';
    $output .= '<span id="value_event_text_'.$event['id_evento'].'"></span>';
    $output .= '<a href="javascript:show_instructions('.$event['id_evento'].')">';
    $output .= html_print_image(
        'images/default_list.png',
        true,
        ['title' => $over_text]
    ).'</a></span>';

    return $output;
}


/**
 * Return class name matching criticity received.
 *
 * @param integer $criticity Event's criticity.
 *
 * @return string
 */
function events_get_criticity_class($criticity)
{
    switch ($criticity) {
        case EVENT_CRIT_CRITICAL:
        return 'datos_red';

        case EVENT_CRIT_MAINTENANCE:
        return 'datos_grey';

        case EVENT_CRIT_INFORMATIONAL:
        return 'datos_blue';

        case EVENT_CRIT_MAJOR:
        return 'datos_pink';

        case EVENT_CRIT_MINOR:
        return 'datos_pink';

        case EVENT_CRIT_NORMAL:
        return 'datos_green';

        case EVENT_CRIT_WARNING:
        return 'datos_yellow';

        default:
        return 'datos_blue';
    }
}


/**
 * Draw row response events.
 *
 * @param array        $event_response Response.
 * @param integer|null $response_id    Id .
 * @param boolean      $end            End block.
 * @param integer|null $index          Index block.
 *
 * @return string Html output.
 */
function get_row_response_action(
    array $event_response,
    ?int $response_id,
    $end=false,
    $index=null
) {
    $output = '<div class="container-massive-events-response-cell">';
    $display_command = (bool) $event_response['display_command'];
    $command_str = ($display_command === true) ? $event_response['target'] : '';

    // String command.
    $output .= '<div class="container-massive-events-response-command">';
    $output .= '<b>';
    $output .= __('Event # %d', $event_response['event_id']);
    if (empty($command_str) === false) {
        $output .= ' ';
        $output .= __('Executing command: ');
    }

    $output .= '</b>';
    $output .= '<span>'.$command_str.'</span>';
    $output .= '</div>';

    // Spinner.
    $output .= '<div id="response_loading_command'.$index.'" style="display:none">';
    $output .= html_print_image(
        'images/spinner.gif',
        true
    );
    $output .= '</div>';

    // Output.
    $output .= '<div id="response_out'.$index.'" class="container-massive-events-response-output"></div>';

    // Butom.
    $output .= '<div id="re_exec_command'.$index.'" style="display:none" class="container-massive-events-response-execute">';
    $output .= html_print_button(
        __('Execute again'),
        'btn_str',
        false,
        'perform_response("'.base64_encode(json_encode($event_response)).'",'.$response_id.',"'.trim($index).'")',
        [
            'icon' => 'next',
            'mode' => 'mini secondary',
        ],
        true
    );
    $output .= '</div>';

    $output .= '</div>';

    return $output;
}


/**
 * Get evet get response target.
 *
 * @param integer $event_id       Id event.
 * @param array   $event_response Response.
 * @param integer $server_id      Server id.
 *
 * @return string
 */
function get_events_get_response_target(
    $event_id,
    $event_response,
    $server_id=0,
    $response_parameters=[]
) {
    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        return events_get_response_target(
            $event_id,
            $event_response,
            $response_parameters,
            $server_id,
            ($server_id !== 0) ? $node->server_name() : 'Metaconsole'
        );
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        return '';
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }
}


/**
 * Gets the count of events by criticity.
 *
 * @param integer $utimestamp  Utimestamp to search.
 * @param integer $eventType   Event type.
 * @param array   $groupId     Groups.
 * @param integer $eventStatus Event status.
 * @param array   $criticityId Criticity to search.
 *
 * @return array
 */
function get_count_event_criticity(
    $utimestamp,
    $eventType,
    $groupId,
    $eventStatus,
    $criticityId
) {
    $type = ' ';
    if ($eventType !== '0') {
        $type = 'AND event_type = "'.$eventType.'"';
    }

    $groups = ' ';
    if ((int) $groupId !== 0) {
        $groups = 'AND id_grupo IN ('.$groupId.')';
    }

    $status = ' ';
    if (empty($eventStatus) === false) {
        switch ($eventStatus) {
            case EVENT_ALL:
            default:
                // Do not filter.
            break;

            case EVENT_NEW:
            case EVENT_VALIDATE:
            case EVENT_PROCESS:
                $status = sprintf(
                    ' AND estado = %d',
                    $eventStatus
                );
            break;

            case EVENT_NO_VALIDATED:
                $status = sprintf(
                    ' AND (estado = %d OR estado = %d)',
                    EVENT_NEW,
                    EVENT_PROCESS
                );
            break;

            case EVENT_NO_PROCESS:
                $status = sprintf(
                    ' AND (estado = %d OR estado = %d)',
                    EVENT_NEW,
                    EVENT_VALIDATE
                );
            break;
        }
    }

    $criticity = ' ';
    if (empty($criticityId) === false) {
        $criticity = 'AND criticity IN ('.$criticityId.')';
    }

    $sql_meta = sprintf(
        'SELECT COUNT(id_evento) AS count,
        criticity
        FROM tevento
        WHERE utimestamp >= %d %s %s %s %s
        GROUP BY criticity',
        $utimestamp,
        $type,
        $groups,
        $status,
        $criticity
    );

    return db_get_all_rows_sql($sql_meta);
}


/**
 * Comments for this events.
 *
 * @param array   $event     Info event.
 * @param integer $mode      Mode group by.
 * @param integer $event_rep Events.
 *
 * @return array Comments.
 */
function event_get_comment($event, $filter=null)
{
    $whereGrouped = [];
    if (empty($filter) === false) {
        if (isset($filter['event_view_hr_cs']) === true && ($filter['event_view_hr_cs'] > 0)) {
            $whereGrouped[] = sprintf(
                ' AND tevent_comment.utimestamp > UNIX_TIMESTAMP(now() - INTERVAL %d SECOND) ',
                $filter['event_view_hr_cs']
            );
        } else if (isset($filter['event_view_hr']) === true && ($filter['event_view_hr'] > 0)) {
            $whereGrouped[] = sprintf(
                ' AND tevent_comment.utimestamp > UNIX_TIMESTAMP(now() - INTERVAL %d SECOND) ',
                ((int) $filter['event_view_hr'] * 3600)
            );
        }
    }

    $mode = (int) $filter['group_rep'];

    $eventsGrouped = [];
    // Consider if the event is grouped.
    if ($mode === EVENT_GROUP_REP_EVENTS) {
        // Default grouped message filtering (evento and estado).
        $whereGrouped[] = sprintf(
            'AND `tevento`.`evento` = "%s"',
            io_safe_input(io_safe_output($event['evento']))
        );

        // If id_agente is reported, filter the messages by them as well.
        if ((int) $event['id_agente'] > 0) {
            $whereGrouped[] = sprintf(
                ' AND `tevento`.`id_agente` = %d',
                (int) $event['id_agente']
            );
        }

        if ((int) $event['id_agentmodule'] > 0) {
            $whereGrouped[] = sprintf(
                ' AND `tevento`.`id_agentmodule` = %d',
                (int) $event['id_agentmodule']
            );
        }
    } else if ($mode === EVENT_GROUP_REP_EXTRAIDS) {
        $whereGrouped[] = sprintf(
            'AND `tevento`.`id_extra` = "%s"',
            io_safe_input(io_safe_output($event['id_extra']))
        );
    } else {
        $whereGrouped[] = sprintf('AND `tevento`.`id_evento` = %d', $event['id_evento']);
    }

    try {
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node = new Node($event['server_id']);
            $node->connect();
        }

        $sql = sprintf(
            'SELECT tevent_comment.*
            FROM tevento
            INNER JOIN tevent_comment
                ON tevento.id_evento = tevent_comment.id_event
            WHERE 1=1 %s
            ORDER BY tevent_comment.utimestamp DESC',
            implode(' ', $whereGrouped)
        );

        // Get grouped comments.
        $eventsGrouped = db_get_all_rows_sql($sql);
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node->disconnect();
        }

        $eventsGrouped = [];
    } finally {
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node->disconnect();
        }
    }

    return $eventsGrouped;
}


/**
 * Last comment for this event.
 *
 * @param array $event Info event.
 *
 * @return string Comment.
 */
function event_get_last_comment($event, $filter)
{
    $comments = event_get_comment($event, $filter);
    if (empty($comments) === false) {
        return $comments[0];
    }

    return '';
}


/**
 * Get counter events same extraid.
 *
 * @param array $event   Event data.
 * @param array $filters Filters.
 *
 * @return integer Counter.
 */
function event_get_counter_extraId(array $event, ?array $filters)
{
    $counters = 0;

    $where = get_filter_date($filters);

    $where[] = sprintf(
        'AND `te`.`id_extra` = "%s"',
        $event['id_extra']
    );

    try {
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node = new Node($event['server_id']);
            $node->connect();
        }

        $sql = sprintf(
            'SELECT count(*)
            FROM tevento te
            WHERE 1=1 %s',
            implode(' ', $where)
        );

        // Get grouped comments.
        $counters = db_get_value_sql($sql);
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node->disconnect();
        }

        $counters = 0;
    } finally {
        if (is_metaconsole() === true
            && $event['server_id'] > 0
        ) {
            $node->disconnect();
        }
    }

    return $counters;
}


/**
 * Update event detail custom field
 *
 * @param mixed  $id_event        Event ID or array of events.
 * @param string $event_custom_id Event custom ID to be update.
 *
 * @return boolean Whether or not it was successful
 */
function events_event_custom_id(
    $id_event,
    $event_custom_id,
) {
    global $config;
    // Cleans up the selection for all unwanted values also casts any single
    // values as an array.
    if (![$id_event]) {
        $id_event = (array) safe_int($id_event, 1);
    }

    // Check ACL.
    foreach ($id_event as $k => $id) {
        $event_group = events_get_group($id);
        if (check_acl($config['id_user'], $event_group, 'EW') == 0) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Attempted updating event #'.$id
            );

            unset($id_event[$k]);
        }
    }

    if (empty($id_event) === true) {
        return false;
    }

    // Get the current event comments.
    $first_event = $id_event;
    if (is_array($id_event) === true) {
        $first_event = reset($id_event);
    }

    // Update comment.
    $ret = db_process_sql_update(
        'tevento',
        ['event_custom_id' => $event_custom_id],
        ['id_evento' => $first_event]
    );

    if (($ret === false) || ($ret === 0)) {
        return false;
    }

    return true;
}


function event_print_graph(
    $filter,
    $graph_height=100,
) {
    global $config;
    $show_all_data = false;
    $events = events_get_all(['te.id_evento', 'te.timestamp', 'te.utimestamp'], $filter, null, null, 'te.utimestamp', true);

    if (empty($filter['date_from']) === false
        && empty($filter['time_from']) === false
        && empty($filter['date_to']) === false
        && empty($filter['time_to']) === false
    ) {
        $start_utimestamp = strtotime($filter['date_from'].' '.$filter['time_from']);
        $end_utimestamp = strtotime($filter['date_to'].' '.$filter['time_to']);
    } else if ($filter['event_view_hr'] !== '') {
        $start_utimestamp = strtotime('-'.$filter['event_view_hr'].' hours');
        $end_utimestamp = strtotime('now');
    } else {
        $show_all_data = true;
        $start_utimestamp = $events[0]['utimestamp'];
        $end_utimestamp = $events[array_key_last($events)]['utimestamp'];
    }

    $data_events = [];
    $control_timestamp = $start_utimestamp;
    $count = 0;
    foreach ($events as $event) {
        if ($event['utimestamp'] === $control_timestamp) {
            $count++;
        } else {
            $control_timestamp = $event['utimestamp'];
            $count = 1;
        }

        $data_events[$control_timestamp] = $count;
    }

    $num_data = count($data_events);

    $num_intervals = $num_data;

    $period = ($end_utimestamp - $start_utimestamp);

    if ($period <= SECONDS_6HOURS) {
        $chart_time_format = 'H:i:s';
    } else if ($period < SECONDS_1DAY) {
        $chart_time_format = 'H:i';
    } else if ($period < SECONDS_15DAYS) {
        $chart_time_format = 'M d H:i';
    } else if ($period < SECONDS_1MONTH) {
        $chart_time_format = 'M d H\h';
    } else {
        $chart_time_format = 'M d H\h';
    }

    $chart = [];
    $labels = [];
    $color = [];
    $count = 0;

    if ($show_all_data === true) {
        foreach ($events as $event) {
            if ($event['utimestamp'] === $control_timestamp) {
                $count++;
            } else {
                $control_timestamp = $event['utimestamp'];
                $count = 1;
            }

            $data_events[$control_timestamp] = $count;
        }

        $data_events = array_reverse($data_events, true);

        foreach ($data_events as $utimestamp => $count) {
            $labels[] = date($chart_time_format, $utimestamp);
            $chart[] = [
                'y' => $count,
                'x' => date($chart_time_format, $utimestamp),
            ];
            $color[] = '#82b92f';
        }
    } else {
        $interval_length = 0;

        if ($num_intervals > 0) {
            $interval_length = (int) ($period / $num_intervals);
        }

        $intervals = [];
        $intervals[0] = $start_utimestamp;
        for ($i = 0; $i < $num_intervals; $i++) {
            $intervals[($i + 1)] = ($intervals[$i] + $interval_length);
        }

        $control_data = [];

        foreach ($data_events as $utimestamp => $count_event) {
            for ($i = 0; $i < $num_intervals; $i++) {
                if ((int) $utimestamp > (int) $intervals[$i] && (int) $utimestamp < (int) $intervals[($i + 1)]) {
                    $control_data[(string) $intervals[$i]] += $count_event;
                }
            }
        }

        for ($i = 0; $i < $num_intervals; $i++) {
            $labels[] = date($chart_time_format, $intervals[$i]);
            $chart[] = [
                'y' => $control_data[$intervals[$i]],
                'x' => date($chart_time_format, $intervals[$i]),
            ];
            $color[] = '#82b92f';
        }
    }

    $water_mark = [
        'file' => $config['homedir'].'/images/logo_vertical_water.png',
        'url'  => ui_get_full_url('/images/logo_vertical_water.png'),
    ];

    $options = [
        'height'    => $graph_height,
        'waterMark' => $water_mark,
        'legend'    => ['display' => false],
        'colors'    => $color,
        'border'    => false,
        'scales'    => [
            'x' => [
                'grid' => ['display' => false],
            ],
            'y' => [
                'grid' => ['display' => false],
            ],
        ],
        'labels'    => $labels,
    ];

    $graph = '<div style="width:100%; height: '.$graph_height.'px;">';
    $graph .= vbar_graph($chart, $options);
    $graph .= '</div>';

    return $graph;
}


/**
 * Get comments of array events.
 *
 * @param array $events Array of events.
 * @param array $filter Filter of view events.
 *
 * @return array
 */
function reduce_events_comments($events, $filter=null)
{
    $group_by_server = [];
    foreach ($events as $key => $event) {
        if (isset($group_by_server[$event['server_id']]) === false) {
            $group_by_server[$event['server_id']] = [];
        }

        $group_by_server[$event['server_id']][] = $event;
    }

    $comments = [];
    foreach ($group_by_server as $server_id => $events) {
        $events_comments = event_get_comments_with_all_events($events, $filter, $server_id);
        foreach ($events_comments as $key => $comment) {
            $comments[$server_id.'_'.$comment['id_event']] = $comment;
        }
    }

    return $comments;
}


/**
 * Ge all coments of events grouped by server.
 *
 * @param array   $events    Array of events.
 * @param array   $filter    Filter of view events.
 * @param integer $server_id Id of server.
 *
 * @return array
 */
function event_get_comments_with_all_events($events, $filter=null, $server_id=0)
{
    $whereGrouped = [];
    if (empty($filter) === false) {
        if (isset($filter['event_view_hr_cs']) === true && ($filter['event_view_hr_cs'] > 0)) {
            $whereGrouped[] = sprintf(
                ' AND tevent_comment.utimestamp > UNIX_TIMESTAMP(now() - INTERVAL %d SECOND) ',
                $filter['event_view_hr_cs']
            );
        } else if (isset($filter['event_view_hr']) === true && ($filter['event_view_hr'] > 0)) {
            $whereGrouped[] = sprintf(
                ' AND tevent_comment.utimestamp > UNIX_TIMESTAMP(now() - INTERVAL %d SECOND) ',
                ((int) $filter['event_view_hr'] * 3600)
            );
        }
    }

    $mode = (int) ($filter['group_rep'] ?? 0);

    $eventsGrouped = [];
    $idEvents = [];
    $idExtras = [];
    $idAgentsModules = [];
    $idAgentes = [];
    $eventos = [];
    foreach ($events as $key => $event) {
        // Consider if the event is grouped.
        if ($mode === EVENT_GROUP_REP_EVENTS) {
            // Default grouped message filtering (evento and estado).
            $eventos[] = io_safe_input(io_safe_output($event['evento']));

            // If id_agente is reported, filter the messages by them as well.
            if ((int) $event['id_agente'] > 0) {
                $idAgentes[] = (int) $event['id_agente'];
            }

            if ((int) $event['id_agentmodule'] > 0) {
                $idAgentsModules[] = (int) $event['id_agentmodule'];
            }
        } else if ($mode === EVENT_GROUP_REP_EXTRAIDS) {
            $idExtras[] = io_safe_input(io_safe_output($event['id_extra']));
        } else {
            $idEvents[] = $event['id_evento'];
        }
    }

    if ($mode === EVENT_GROUP_REP_EVENTS) {
        // Default grouped message filtering (evento and estado).
        $whereGrouped[] = sprintf(
            'AND `tevento`.`evento` IN ("%s")',
            implode('","', $eventos)
        );

        // If id_agente is reported, filter the messages by them as well.
        if ((int) $event['id_agente'] > 0) {
            $whereGrouped[] = sprintf(
                ' AND `tevento`.`id_agente` IN (%s)',
                implode(',', $idAgentes)
            );
        }

        if ((int) $event['id_agentmodule'] > 0) {
            $whereGrouped[] = sprintf(
                ' AND `tevento`.`id_agentmodule` IN (%s)',
                implode(',', $idAgentsModules)
            );
        }
    } else if ($mode === EVENT_GROUP_REP_EXTRAIDS) {
        $whereGrouped[] = sprintf(
            'AND `tevento`.`id_extra` IN ("%s")',
            implode('","', $idExtras),
        );
    } else {
        $whereGrouped[] = sprintf('AND `tevento`.`id_evento` IN (%s)', implode(',', $idEvents));
    }

    try {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node = new Node($server_id);
            $node->connect();
        }

        $sql = sprintf(
            'SELECT tevent_comment.*
            FROM tevento
            INNER JOIN tevent_comment
                ON tevento.id_evento = tevent_comment.id_event
            JOIN(
                SELECT id_event, max(utimestamp) as utimestamp
                FROM tevent_comment a
                GROUP BY a.id_event
            ) max_ut ON max_ut.id_event = tevent_comment.id_event AND max_ut.utimestamp = tevent_comment.utimestamp
            WHERE 1=1 %s
            ORDER BY tevent_comment.utimestamp DESC',
            implode(' ', $whereGrouped)
        );

        // Get grouped comments.
        $eventsGrouped = db_get_all_rows_sql($sql);
    } catch (\Exception $e) {
        // Unexistent agent.
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }

        $eventsGrouped = [];
    } finally {
        if (is_metaconsole() === true
            && $server_id > 0
        ) {
            $node->disconnect();
        }
    }

    return $eventsGrouped;
}
