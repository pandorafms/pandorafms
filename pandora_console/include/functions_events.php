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
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
global $config;

require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_tags.php';
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_reporting.php';
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('meta/include/functions_events_meta.php');
enterprise_include_once('meta/include/functions_agents_meta.php');
enterprise_include_once('meta/include/functions_modules_meta.php');
if (is_metaconsole()) {
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

        case 'options':
        return __('Options');

        case 'mini_severity':
            if ($table_alias === true) {
                return 'S';
            } else {
                return __('Severity mini');
            }

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
    if (!isset($fields) || !is_array($fields)) {
        return [];
    }

    $names = [];
    foreach ($fields as $f) {
        if (is_array($f)) {
            $name = [];
            $name['text'] = events_get_column_name($f['text'], $table_alias);
            $name['class'] = $f['class'];
            $name['style'] = $f['style'];
            $name['extra'] = $f['extra'];
            $name['id'] = $f['id'];
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
 * @param integer $id_evento Master event.
 * @param array   $filter    Optional. Filter options.
 * @param boolean $history   Apply on historical table.
 *
 * @return integer Events validated or false if error.
 */
function events_delete($id_evento, $filter=null, $history=false)
{
    if (!isset($id_evento) || $id_evento <= 0) {
        return false;
    }

    if (!isset($filter) || !is_array($filter)) {
        $filter = ['group_rep' => 0];
    }

    $table = events_get_events_table(is_metaconsole(), $history);

    switch ($filter['group_rep']) {
        case '0':
        case '2':
        default:
            // No groups option direct update.
            $delete_sql = sprintf(
                'DELETE FROM %s
                 WHERE id_evento = %d',
                $table,
                $id_evento
            );
        break;

        case '1':
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

            $target_ids = db_get_all_rows_sql(
                sprintf(
                    'SELECT tu.id_evento FROM %s tu INNER JOIN ( %s ) tf
                    ON tu.estado = tf.estado
                    AND tu.evento = tf.evento
                    AND tu.id_agente = tf.id_agente
                    AND tu.id_agentmodule = tf.id_agentmodule
                    AND tf.max_id_evento = %d',
                    $table,
                    $sql,
                    $id_evento
                )
            );

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
                    'DELETE FROM %s WHERE id_evento IN (%s)',
                    $table,
                    join(', ', $target_ids)
                );
            }
        break;
    }

    return db_process_sql($delete_sql);
}


/**
 * Retrieves all events related to matching one.
 *
 * @param integer $id_evento Master event (max_id_evento).
 * @param array   $filter    Filters.
 * @param boolean $count     Count results or get results.
 * @param boolean $history   Apply on historical table.
 *
 * @return array Events or false in case of error.
 */
function events_get_related_events(
    $id_evento,
    $filter=null,
    $count=false,
    $history=false
) {
    global $config;

    if (!isset($id_evento) || $id_evento <= 0) {
        return false;
    }

    if (!isset($filter) || !is_array($filter)) {
        $filter = ['group_rep' => 0];
    }

    $table = events_get_events_table(is_metaconsole(), $history);
    $select = '*';
    if ($count === true) {
        $select = 'count(*) as n';
    };

    switch ($filter['group_rep']) {
        case '0':
        case '2':
        default:
            // No groups option direct update.
            $related_sql = sprintf(
                'SELECT %s FROM %s
                 WHERE id_evento = %d',
                $select,
                $table,
                $id_evento
            );
        break;

        case '1':
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
            $related_sql = sprintf(
                'SELECT %s FROM %s tu INNER JOIN ( %s ) tf
                WHERE tu.estado = tf.estado
                AND tu.evento = tf.evento
                AND tu.id_agente = tf.id_agente
                AND tu.id_agentmodule = tf.id_agentmodule
                AND tf.max_id_evento = %d',
                $select,
                $table,
                $sql,
                $id_evento
            );
        break;
    }

    if ($count === true) {
        $r = db_get_all_rows_sql($related_sql);

        return $r[0]['n'];
    }

    return db_get_all_rows_sql($related_sql);

}


/**
 * Validates all events matching target filter.
 *
 * @param integer $id_evento Master event.
 * @param integer $status    Target status.
 * @param array   $filter    Optional. Filter options.
 * @param boolean $history   Apply on historical table.
 *
 * @return integer Events validated or false if error.
 */
function events_update_status($id_evento, $status, $filter=null, $history=false)
{
    global $config;

    if (!$status) {
        error_log('No hay estado');
        return false;
    }

    if (!isset($id_evento) || $id_evento <= 0) {
        error_log('No hay id_evento');
        return false;
    }

    if (!isset($filter) || !is_array($filter)) {
        $filter = ['group_rep' => 0];
    }

    $table = events_get_events_table(is_metaconsole(), $history);

    switch ($filter['group_rep']) {
        case '0':
        case '2':
        default:
            // No groups option direct update.
            $update_sql = sprintf(
                'UPDATE %s
                 SET estado = %d
                 WHERE id_evento = %d',
                $table,
                $status,
                $id_evento
            );
        break;

        case '1':
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

            $target_ids = db_get_all_rows_sql(
                sprintf(
                    'SELECT tu.id_evento FROM %s tu INNER JOIN ( %s ) tf
                    ON tu.estado = tf.estado
                    AND tu.evento = tf.evento
                    AND tu.id_agente = tf.id_agente
                    AND tu.id_agentmodule = tf.id_agentmodule
                    AND tf.max_id_evento = %d',
                    $table,
                    $sql,
                    $id_evento
                )
            );

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
                    'UPDATE %s
                    SET estado = %d,
                        ack_utimestamp = %d,
                        id_usuario = "%s"
                    WHERE id_evento IN (%s)',
                    $table,
                    $status,
                    time(),
                    $config['id_user'],
                    join(',', $target_ids)
                );
            }
        break;
    }

    return db_process_sql($update_sql);
}


/**
 * Retrieve all events filtered.
 *
 * @param array   $fields     Fields to retrieve.
 * @param array   $filter     Filters to be applied.
 * @param integer $offset     Offset (pagination).
 * @param integer $limit      Limit (pagination).
 * @param string  $order      Sort order.
 * @param string  $sort_field Sort field.
 * @param boolean $history    Apply on historical table.
 * @param boolean $return_sql Return SQL (true) or execute it (false).
 * @param string  $having     Having filter.
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
    $having=''
) {
    global $config;

    $user_is_admin = users_is_admin();

    if (!is_array($filter)) {
        error_log('[events_get_all] Filter must be an array.');
        throw new Exception('[events_get_all] Filter must be an array.');
    }

    $count = false;
    if (!is_array($fields) && $fields == 'count') {
        $fields = ['te.*'];
        $count = true;
    } else if (!is_array($fields)) {
        error_log('[events_get_all] Fields must be an array or "count".');
        throw new Exception('[events_get_all] Fields must be an array or "count".');
    }

    if (isset($filter['date_from'])
        && !empty($filter['date_from'])
        && $filter['date_from'] != '0000-00-00'
    ) {
        $date_from = $filter['date_from'];
    }

    if (isset($filter['time_from'])) {
        $time_from = $filter['time_from'];
    }

    if (isset($date_from)) {
        if (!isset($time_from)) {
            $time_from = '00:00:00';
        }

        $from = $date_from.' '.$time_from;
        $sql_filters[] = sprintf(
            ' AND te.utimestamp >= %d',
            strtotime($from)
        );
    }

    if (isset($filter['date_to'])
        && !empty($filter['date_to'])
        && $filter['date_to'] != '0000-00-00'
    ) {
        $date_to = $filter['date_to'];
    }

    if (isset($filter['time_to'])) {
        $time_to = $filter['time_to'];
    }

    if (isset($date_to)) {
        if (!isset($time_to)) {
            $time_to = '23:59:59';
        }

        $to = $date_to.' '.$time_to;
        $sql_filters[] = sprintf(
            ' AND te.utimestamp <= %d',
            strtotime($to)
        );
    }

    if (!isset($from)) {
        if (isset($filter['event_view_hr']) && ($filter['event_view_hr'] > 0)) {
            $sql_filters[] = sprintf(
                ' AND utimestamp > UNIX_TIMESTAMP(now() - INTERVAL %d HOUR) ',
                $filter['event_view_hr']
            );
        }
    }

    if (isset($filter['id_agent']) && $filter['id_agent'] > 0) {
        $sql_filters[] = sprintf(
            ' AND te.id_agente = %d ',
            $filter['id_agent']
        );
    }

    if (!empty($filter['event_type']) && $filter['event_type'] != 'all') {
        if ($filter['event_type'] == 'warning'
            || $filter['event_type'] == 'critical'
            || $filter['event_type'] == 'normal'
        ) {
            $sql_filters[] = ' AND event_type LIKE "%'.$filter['event_type'].'%"';
        } else if ($filter['event_type'] == 'not_normal') {
            $sql_filters[] = ' AND (event_type LIKE "%warning%"
              OR event_type LIKE "%critical%"
              OR event_type LIKE "%unknown%")';
        } else {
            $sql_filters[] = ' AND event_type = "'.$filter['event_type'].'"';
        }
    }

    if (isset($filter['severity']) && $filter['severity'] > 0) {
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

    $groups = $filter['id_group_filter'];
    if (isset($groups) && $groups > 0) {
        $propagate = db_get_value(
            'propagate',
            'tgrupo',
            'id_grupo',
            $groups
        );

        if (!$propagate) {
            $sql_filters[] = sprintf(
                ' AND (te.id_grupo = %d OR tasg.id_group = %d)',
                $groups
            );
        } else {
            $children = groups_get_children($groups);
            $_groups = [ $groups ];
            if (!empty($children)) {
                foreach ($children as $child) {
                    $_groups[] = (int) $child['id_grupo'];
                }
            }

            $groups = $_groups;

            $sql_filters[] = sprintf(
                ' AND (te.id_grupo IN (%s) OR tasg.id_group IN (%s))',
                join(',', $groups),
                join(',', $groups)
            );
        }
    }

    // Skip system messages if user is not PM.
    if (!check_acl($config['id_user'], 0, 'PM')) {
        $sql_filters[] = ' AND te.id_grupo != 0 ';
    }

    if (isset($filter['status'])) {
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
                $sql_filters[] = sprintf(
                    ' AND (estado = %d OR estado = %d)',
                    EVENT_NEW,
                    EVENT_PROCESS
                );
            break;
        }
    }

    if (!$user_is_admin) {
        $ER_groups = users_get_groups($config['id_user'], 'ER', false);
        $EM_groups = users_get_groups($config['id_user'], 'EM', false, true);
        $EW_groups = users_get_groups($config['id_user'], 'EW', false, true);
    }

    if (!$user_is_admin && !users_can_manage_group_all('ER')) {
        // Get groups where user have ER grants.
        $sql_filters[] = sprintf(
            ' AND (te.id_grupo IN ( %s ) OR tasg.id_group IN (%s))',
            join(', ', array_keys($ER_groups)),
            join(', ', array_keys($ER_groups))
        );
    }

    $table = events_get_events_table(is_metaconsole(), $history);
    $tevento = sprintf(
        ' %s te',
        $table
    );

    // Prepare agent join sql filters.
    $agent_join_filters = [];
    $tagente_table = 'tagente';
    $tagente_field = 'id_agente';
    $conditionMetaconsole = '';
    if (is_metaconsole()) {
        $tagente_table = 'tmetaconsole_agent';
        $tagente_field = 'id_tagente';
        $conditionMetaconsole = ' AND ta.id_tmetaconsole_setup = te.server_id ';
    }

    // Agent alias.
    if (!empty($filter['agent_alias'])) {
        $agent_join_filters[] = sprintf(
            ' AND ta.alias = "%s" ',
            $filter['agent_alias']
        );
    }

    // Free search.
    if (!empty($filter['search'])) {
        if (isset($config['dbconnection']->server_version)
            && $config['dbconnection']->server_version > 50600
        ) {
            // Use "from_base64" requires mysql 5.6 or greater.
            $custom_data_search = 'from_base64(te.custom_data)';
        } else {
            // Custom data is JSON encoded base64, if 5.6 or lower,
            // user is condemned to use plain search.
            $custom_data_search = 'te.custom_data';
        }

        $sql_filters[] = vsprintf(
            ' AND (lower(ta.alias) like lower("%%%s%%")
                OR te.id_evento like "%%%s%%"
                OR lower(te.evento) like lower("%%%s%%")
                OR lower(te.user_comment) like lower("%%%s%%")
                OR lower(te.id_extra) like lower("%%%s%%")
                OR lower(te.source) like lower("%%%s%%") 
                OR lower('.$custom_data_search.') like lower("%%%s%%") )',
            array_fill(0, 7, $filter['search'])
        );
    }

    // Id extra.
    if (!empty($filter['id_extra'])) {
        $sql_filters[] = sprintf(
            ' AND lower(te.id_extra) like lower("%%%s%%") ',
            $filter['id_extra']
        );
    }

    if (is_metaconsole()) {
        // Id source event.
        if (!empty($filter['id_source_event'])) {
            $sql_filters[] = sprintf(
                ' AND lower(te.id_source_event) like lower("%%%s%%") ',
                $filter['id_source_event']
            );
        }
    }

    // User comment.
    if (!empty($filter['user_comment'])) {
        $sql_filters[] = sprintf(
            ' AND lower(te.user_comment) like lower("%%%s%%") ',
            $filter['user_comment']
        );
    }

    // Source.
    if (!empty($filter['source'])) {
        $sql_filters[] = sprintf(
            ' AND lower(te.source) like lower("%%%s%%") ',
            $filter['source']
        );
    }

    // Validated or in process by.
    if (!empty($filter['id_user_ack'])) {
        $sql_filters[] = sprintf(
            ' AND te.id_usuario like lower("%%%s%%") ',
            $filter['id_user_ack']
        );
    }

    $tag_names = [];
    // With following tags.
    if (!empty($filter['tag_with'])) {
        $tag_with = base64_decode($filter['tag_with']);
        $tags = json_decode($tag_with, true);
        if (is_array($tags) && !in_array('0', $tags)) {
            if (!$user_is_admin) {
                $user_tags = array_flip(tags_get_tags_for_module_search());
                if ($user_tags != null) {
                    foreach ($tags as $id_tag) {
                        // User cannot filter with those tags.
                        if (!array_search($id_tag, $user_tags)) {
                            return false;
                        }
                    }
                }
            }

            foreach ($tags as $id_tag) {
                if (!isset($tags_names[$id_tag])) {
                    $tags_names[$id_tag] = tags_get_name($id_tag);
                }

                $_tmp .= ' AND ( ';
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

                $_tmp .= ') ';
            }

            $sql_filters[] = $_tmp;
        }
    }

    // Without following tags.
    if (!empty($filter['tag_without'])) {
        $tag_without = base64_decode($filter['tag_without']);
        $tags = json_decode($tag_without, true);
        if (is_array($tags) && !in_array('0', $tags)) {
            foreach ($tags as $id_tag) {
                if (!isset($tags_names[$id_tag])) {
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
    if (isset($filter['filter_only_alert'])) {
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
            $ER_groups,
            // Access.
            'ER',
            // Return_mode.
            'event_condition',
            // Query_prefix.
            'AND',
            // Query_table.
            '',
            // Meta.
            is_metaconsole(),
            // Childrens_ids.
            [],
            // Force_group_and_tag.
            true,
            // Table tag for id_grupo.
            'te.',
            // Alt table tag for id_grupo.
            $user_admin_group_all
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
            is_metaconsole(),
            // Childrens_ids.
            [],
            // Force_group_and_tag.
            true,
            // Table tag for id_grupo.
            'te.',
            // Alt table tag for id_grupo.
            $user_admin_group_all
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
            is_metaconsole(),
            // Childrens_ids.
            [],
            // Force_group_and_tag.
            true,
            // Table tag for id_grupo.
            'te.',
            // Alt table tag for id_grupo.
            $user_admin_group_all
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
    if (is_metaconsole()) {
        $agentmodule_join = '';
    } else if (!empty($filter['module_search'])) {
        $agentmodule_join = 'INNER JOIN tagente_modulo am ON te.id_agentmodule = am.id_agente_modulo';
        $sql_filters[] = sprintf(
            ' AND am.nombre = "%s" ',
            $filter['module_search']
        );
    }

    // Order.
    $order_by = '';
    if (isset($order, $sort_field)) {
        $order_by = events_get_sql_order($sort_field, $order);
    }

    // Pagination.
    $pagination = '';
    if (isset($limit, $offset) && $limit > 0) {
        $pagination = sprintf(' LIMIT %d OFFSET %d', $limit, $offset);
    }

    $extra = '';
    if (is_metaconsole()) {
        $extra = ', server_id';
    }

    // Group by.
    $group_by = 'GROUP BY ';
    $tagente_join = 'LEFT';
    switch ($filter['group_rep']) {
        case '0':
        default:
            // All events.
            $group_by = '';
        break;

        case '1':
            // Group by events.
            $group_by .= 'te.estado, te.evento, te.id_agente, te.id_agentmodule';
            $group_by .= $extra;
        break;

        case '2':
            // Group by agents.
            $tagente_join = 'INNER';
            // $group_by .= 'te.id_agente, te.event_type';
            // $group_by .= $extra;
            $group_by = '';
            $order_by = events_get_sql_order('id_agente', 'asc');
            if (isset($order, $sort_field)) {
                $order_by .= ','.events_get_sql_order(
                    $sort_field,
                    $order,
                    0,
                    true
                );
            }
        break;
    }

    $tgrupo_join = 'LEFT';
    $tgrupo_join_filters = [];
    if (isset($groups)
        && (is_array($groups)
        || $groups > 0)
    ) {
        $tgrupo_join = 'INNER';
        if (is_array($groups)) {
            $tgrupo_join_filters[] = sprintf(
                ' AND (tg.id_grupo IN (%s) OR tasg.id_group IN (%s))',
                join(', ', $groups),
                join(', ', $groups)
            );
        } else {
            $tgrupo_join_filters[] = sprintf(
                ' AND (tg.id_grupo = %s OR tasg.id_group = %s)',
                $groups,
                $groups
            );
        }
    }

    $server_join = '';
    if (is_metaconsole()) {
        $server_join = ' LEFT JOIN tmetaconsole_setup ts
            ON ts.id = te.server_id';
        if (!empty($filter['server_id'])) {
            $server_join = sprintf(
                ' INNER JOIN tmetaconsole_setup ts
                  ON ts.id = te.server_id AND ts.id= %d',
                $filter['server_id']
            );
        }
    }

    // Secondary groups.
    $event_lj = '';
    if (!$user_is_admin || ($user_is_admin && isset($groups) && $groups > 0)) {
        db_process_sql('SET group_concat_max_len = 9999999');
        $event_lj = events_get_secondary_groups_left_join($table);
    }

    $group_selects = '';
    if ($group_by != '') {
        $group_selects = ',COUNT(id_evento) AS event_rep
        ,GROUP_CONCAT(DISTINCT user_comment SEPARATOR "<br>") AS comments,
        MAX(utimestamp) as timestamp_last,
        MIN(utimestamp) as timestamp_first,
        MAX(id_evento) as max_id_evento';

        if ($count === false) {
            $idx = array_search('te.user_comment', $fields);
            if ($idx !== false) {
                unset($fields[$idx]);
            }
        }
    } else {
        $idx = array_search('te.user_comment', $fields);
        if ($idx !== false) {
            $fields[$idx] = 'te.user_comment AS comments';
        }
    }

    $sql = sprintf(
        'SELECT %s
            %s
         FROM %s
         %s
         %s
         %s JOIN %s ta
           ON ta.%s = te.id_agente
           %s
           %s
         %s JOIN tgrupo tg
           ON te.id_grupo = tg.id_grupo
           %s
         %s
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
        $tagente_join,
        $tagente_table,
        $tagente_field,
        $conditionMetaconsole,
        join(' ', $agent_join_filters),
        $tgrupo_join,
        join(' ', $tgrupo_join_filters),
        $server_join,
        join(' ', $sql_filters),
        $group_by,
        $order_by,
        $pagination,
        $having
    );

    if (!$user_is_admin) {
        // XXX: Confirm there's no extra grants unhandled!.
        $can_manage = '0 as user_can_manage';
        if (!empty($EM_groups)) {
            $can_manage = sprintf(
                '(tbase.id_grupo IN (%s)) as user_can_manage',
                join(', ', array_keys($EM_groups))
            );
        }

        $can_write = '0 as user_can_write';
        if (!empty($EW_groups)) {
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

    if ($count) {
        $sql = 'SELECT count(*) as nitems FROM ('.$sql.') tt';
    }

    if ($return_sql) {
        return $sql;
    }

    return db_get_all_rows_sql($sql);
}


/**
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
    if ($filter['criticity'] == EVENT_CRIT_WARNING_OR_CRITICAL) {
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
    if (empty($id)) {
        return false;
    }

    global $config;

    if (is_array($fields)) {
        if (! in_array('id_grupo', $fields)) {
            $fields[] = 'id_grupo';
        }
    }

    $table = events_get_events_table($meta, $history);

    $event = db_get_row($table, 'id_evento', $id, $fields);
    if (! check_acl($config['id_user'], $event['id_grupo'], 'ER')) {
        return false;
    }

    return $event;
}


/**
 * Retrieve all events ungrouped.
 *
 * @param string  $sql_post   Sql_post.
 * @param integer $offset     Offset.
 * @param integer $pagination Pagination.
 * @param boolean $meta       Meta.
 * @param boolean $history    History.
 * @param boolean $total      Total.
 * @param boolean $history_db History_db.
 * @param string  $order      Order.
 *
 * @return mixed Array of events or false.
 */
function events_get_events_no_grouped(
    $sql_post,
    $offset=0,
    $pagination=1,
    $meta=false,
    $history=false,
    $total=false,
    $history_db=false,
    $order='ASC'
) {
    global $config;

    $table = events_get_events_table($meta, $history);

    $sql = 'SELECT * FROM '.$table.' te WHERE 1=1 '.$sql_post;

    $events = db_get_all_rows_sql($sql, $history_db);

    return $events;
}


/**
 * Return all events matching sql_post grouped.
 *
 * @param string  $sql_post   Sql_post.
 * @param integer $offset     Offset.
 * @param integer $pagination Pagination.
 * @param boolean $meta       Meta.
 * @param boolean $history    History.
 * @param boolean $total      Total.
 * @param boolean $history_db History_db.
 * @param string  $order      Order.
 * @param string  $sort_field Sort_field.
 *
 * @return mixed Array of events or false.
 */
function events_get_events_grouped(
    $sql_post,
    $offset=0,
    $pagination=1,
    $meta=false,
    $history=false,
    $total=false,
    $history_db=false,
    $order='down',
    $sort_field='utimestamp'
) {
    global $config;

    $table = events_get_events_table($meta, $history);

    if ($meta) {
        $groupby_extra = ', server_id';
    } else {
        $groupby_extra = '';
    }

    if (is_metaconsole()) {
            $id_source_event = get_parameter('id_source_event');
        if ($id_source_event != '') {
            $sql_post .= "AND id_source_event = $id_source_event";
        }
    }

    db_process_sql('SET group_concat_max_len = 9999999');
    $event_lj = events_get_secondary_groups_left_join($table);
    if ($total) {
        $sql = "SELECT COUNT(*) FROM (SELECT id_evento
            FROM $table te $event_lj
            WHERE 1=1 ".$sql_post.'
            GROUP BY estado, evento, id_agente, id_agentmodule'.$groupby_extra.') AS t';
    } else {
        $sql = "SELECT *, MAX(id_evento) AS id_evento,
            GROUP_CONCAT(DISTINCT user_comment SEPARATOR '<br>') AS user_comment,
            GROUP_CONCAT(DISTINCT id_evento SEPARATOR ',') AS similar_ids,
            COUNT(id_evento) AS event_rep, MAX(utimestamp) AS timestamp_rep, 
            MIN(utimestamp) AS timestamp_rep_min,
            (SELECT owner_user FROM $table WHERE id_evento = MAX(te.id_evento)) owner_user,
            (SELECT id_usuario FROM $table WHERE id_evento = MAX(te.id_evento)) id_usuario,
            (SELECT id_agente FROM $table WHERE id_evento = MAX(te.id_evento)) id_agente,
            (SELECT criticity FROM $table WHERE id_evento = MAX(te.id_evento)) AS criticity,
            (SELECT ack_utimestamp FROM $table WHERE id_evento = MAX(te.id_evento)) AS ack_utimestamp,
            (SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = te.id_agentmodule) AS module_name
            FROM $table te $event_lj
            WHERE 1=1 ".$sql_post.'
            GROUP BY estado, evento, id_agente, id_agentmodule'.$groupby_extra;
        $sql .= ' '.events_get_sql_order($sort_field, $order, 2);
        $sql .= ' LIMIT '.$offset.','.$pagination;
    }

    // Extract the events by filter (or not) from db
    $events = db_get_all_rows_sql($sql, $history_db);

    if ($total) {
        return reset($events[0]);
    } else {
        return $events;
    }
}


/**
 * Return count of events grouped.
 *
 * @param string  $sql_post Sql_post.
 * @param boolean $meta     Meta.
 * @param boolean $history  History.
 *
 * @return integer Number of events or false if failed.
 */
function events_get_total_events_grouped($sql_post, $meta=false, $history=false)
{
    return events_get_events_grouped($sql_post, 0, 0, $meta, $history, true);
}


/**
 * Get all the events ids similar to a given event id.
 *
 * An event is similar then the event text (evento) and the id_agentmodule are
 * the same.
 *
 * @param integer $id      Event id to get similar events.
 * @param boolean $meta    Metaconsole mode flag.
 * @param boolean $history History mode flag.
 *
 * @return array A list of events ids.
 */
function events_get_similar_ids($id, $meta=false, $history=false)
{
    $events_table = events_get_events_table($meta, $history);

    $ids = [];
    if ($meta) {
        $event = events_meta_get_event(
            $id,
            [
                'evento',
                'id_agentmodule',
            ],
            $history
        );
    } else {
        $event = events_get_event($id, ['evento', 'id_agentmodule']);
    }

    if ($event === false) {
        return $ids;
    }

    $events = db_get_all_rows_filter(
        $events_table,
        [
            'evento'         => $event['evento'],
            'id_agentmodule' => $event['id_agentmodule'],
        ],
        ['id_evento']
    );
    if ($events === false) {
        return $ids;
    }

    foreach ($events as $event) {
        $ids[] = $event['id_evento'];
    }

    return $ids;
}


/**
 * Delete events in a transresponse
 *
 * @param mixed   $id_event Event ID or array of events.
 * @param boolean $similar  Whether to delete similar events too.
 * @param boolean $meta     Metaconsole mode flag.
 * @param boolean $history  History mode flag.
 *
 * @return boolean Whether or not it was successful
 */
function events_delete_event(
    $id_event,
    $similar=true,
    $meta=false,
    $history=false
) {
    global $config;

    $table_event = events_get_events_table($meta, $history);

    // Cleans up the selection for all unwanted values also casts any single values as an array.
    $id_event = (array) safe_int($id_event, 1);

    // We must delete all events like the selected.
    if ($similar) {
        foreach ($id_event as $id) {
            $id_event = array_merge(
                $id_event,
                events_get_similar_ids($id, $meta, $history)
            );
        }

        $id_event = array_unique($id_event);
    }

    $errors = 0;

    foreach ($id_event as $event) {
        if ($meta) {
            $event_group = events_meta_get_group($event, $history);
        } else {
            $event_group = events_get_group($event);
        }

        if (check_acl($config['id_user'], $event_group, 'EM') == 0) {
            // Check ACL.
            db_pandora_audit('ACL Violation', 'Attempted deleting event #'.$event);
            $errors++;
        } else {
            $ret = db_process_sql_delete($table_event, ['id_evento' => $event]);

            if (!$ret) {
                $errors++;
            } else {
                db_pandora_audit('Event deleted', 'Deleted event #'.$event);
                // ACL didn't fail nor did return.
                continue;
            }
        }

        break;
    }

    if ($errors > 0) {
        return false;
    } else {
        return true;
    }
}


/**
 * Change the status of one or multiple events.
 *
 * @param mixed   $id_event   Event ID or array of events.
 * @param integer $new_status New status of the event.
 * @param boolean $meta       Metaconsole mode flag.
 * @param boolean $history    History mode flag.
 *
 * @return boolean Whether or not it was successful
 */
function events_change_status(
    $id_event,
    $new_status,
    $meta=false,
    $history=false
) {
    global $config;

    $event_table = events_get_events_table($meta, $history);

    // Cleans up the selection for all unwanted values also casts any single values as an array.
    $id_event = (array) safe_int($id_event, 1);

    // Update ack info if the new status is validated.
    if ($new_status == EVENT_STATUS_VALIDATED) {
        $ack_utimestamp = time();
        $ack_user = $config['id_user'];
    } else {
        $acl_utimestamp = 0;
        $ack_user = $config['id_user'];
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
        if ($meta) {
            $event_group = events_meta_get_group($id, $history);
            $event = events_meta_get_event($id, false, $history);
            $server_id = $event['server_id'];
        } else {
            $event_group = events_get_group($id);
            $event = events_get_event($id);
        }

        if ($event['id_alert_am'] > 0 && !in_array($event['id_alert_am'], $alerts)) {
            $alerts[] = $event['id_alert_am'];
        }

        if (check_acl($config['id_user'], $event_group, 'EW') == 0) {
            db_pandora_audit('ACL Violation', 'Attempted updating event #'.$id);

            unset($id_event[$k]);
        }
    }

    if (empty($id_event)) {
        return false;
    }

    $values = [
        'estado'         => $new_status,
        'id_usuario'     => $ack_user,
        'ack_utimestamp' => $ack_utimestamp,
    ];

    $ret = db_process_sql_update(
        $event_table,
        $values,
        ['id_evento' => $id_event]
    );

    if (($ret === false) || ($ret === 0)) {
        return false;
    }

    events_comment(
        $id_event,
        '',
        'Change status to '.$status_string,
        $meta,
        $history
    );

    if ($meta && !empty($alerts)) {
        $server = metaconsole_get_connection_by_id($server_id);
        metaconsole_connect($server);
    }

    // Put the alerts in standby or not depends the new status.
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

    if ($meta && !empty($alerts)) {
        metaconsole_restore_db();
    }

    return true;
}


/**
 * Change the owner of an event if the event hasn't owner.
 *
 * @param mixed   $id_event  Event ID or array of events.
 * @param string  $new_owner Id_user of the new owner. If is false, the current
 *                           owner will be setted.
 * @param boolean $force     Flag to force the change or not (not force is
 *                           change only when it hasn't owner).
 * @param boolean $meta      Metaconsole mode flag.
 * @param boolean $history   History mode flag.
 *
 * @return boolean Whether or not it was successful.
 */
function events_change_owner(
    $id_event,
    $new_owner=false,
    $force=false,
    $meta=false,
    $history=false
) {
    global $config;

    $event_table = events_get_events_table($meta, $history);

    // Cleans up the selection for all unwanted values also casts any single
    // values as an array.
    $id_event = (array) safe_int($id_event, 1);

    foreach ($id_event as $k => $id) {
        if ($meta) {
            $event_group = events_meta_get_group($id, $history);
        } else {
            $event_group = events_get_group($id);
        }

        if (check_acl($config['id_user'], $event_group, 'EW') == 0) {
            db_pandora_audit('ACL Violation', 'Attempted updating event #'.$id);
            unset($id_event[$k]);
        }
    }

    if (empty($id_event)) {
        return false;
    }

    // If no new_owner is provided, the current user will be the owner
    // * #2250: Comment this lines because if possible selected None owner.
    // if (empty($new_owner)) {
    // $new_owner = $config['id_user'];
    // }
    // Only generate comment when is forced (sometimes is owner changes when
    // comment).
    if ($force) {
        events_comment(
            $id_event,
            '',
            'Change owner to '.$new_owner,
            $meta,
            $history
        );
    }

    $values = ['owner_user' => $new_owner];

    $where = ['id_evento' => $id_event];

    // If not force, add to where if owner_user = ''.
    if (!$force) {
        $where['owner_user'] = '';
    }

    $ret = db_process_sql_update(
        $event_table,
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
 * Returns proper event table based on environment.
 *
 * @param boolean $meta    Metaconsole environment or not.
 * @param boolean $history Historical data or not.
 *
 * @return string Table name.
 */
function events_get_events_table($meta, $history)
{
    if ($meta) {
        if ($history) {
            $event_table = 'tmetaconsole_event_history';
        } else {
            $event_table = 'tmetaconsole_event';
        }
    } else {
        $event_table = 'tevento';
    }

    return $event_table;
}


/**
 * Comment events in a transresponse
 *
 * @param mixed   $id_event Event ID or array of events.
 * @param string  $comment  Comment to be registered.
 * @param string  $action   Action performed with comment. By default just add
 *                          a comment.
 * @param boolean $meta     Flag of metaconsole mode.
 * @param boolean $history  Flag of history mode.
 * @param boolean $similars Similars.
 *
 * @return boolean Whether or not it was successful
 */
function events_comment(
    $id_event,
    $comment='',
    $action='Added comment',
    $meta=false,
    $history=false,
    $similars=true
) {
    global $config;

    $event_table = events_get_events_table($meta, $history);

    // Cleans up the selection for all unwanted values also casts any single
    // values as an array.
    $id_event = (array) safe_int($id_event, 1);

    foreach ($id_event as $k => $id) {
        if ($meta) {
            $event_group = events_meta_get_group($id, $history);
        } else {
            $event_group = events_get_group($id);
        }

        if (check_acl($config['id_user'], $event_group, 'EW') == 0) {
            db_pandora_audit('ACL Violation', 'Attempted updating event #'.$id);

            unset($id_event[$k]);
        }
    }

    if (empty($id_event)) {
        return false;
    }

    // If the event hasn't owner, assign the user as owner.
    events_change_owner($id_event);

    // Get the current event comments.
    $first_event = $id_event;
    if (is_array($id_event)) {
        $first_event = reset($id_event);
    }

    $event_comments = mysql_db_process_sql(
        'SELECT user_comment FROM '.$event_table.' WHERE id_evento = '.$first_event,
        'affected_rows',
        '',
        false
    );

    $event_comments_array = [];

    if ($event_comments[0]['user_comment'] == '') {
        $comments_format = 'new';
    } else {
        // If comments are not stored in json, the format is old.
        $event_comments_array = json_decode($event_comments[0]['user_comment']);

        if (empty($event_comments_array)) {
            $comments_format = 'old';
        } else {
            $comments_format = 'new';
        }
    }

    switch ($comments_format) {
        case 'new':
            $comment_for_json['comment'] = $comment;
            $comment_for_json['action'] = $action;
            $comment_for_json['id_user'] = $config['id_user'];
            $comment_for_json['utimestamp'] = time();

            $event_comments_array[] = $comment_for_json;

            $event_comments = io_json_mb_encode($event_comments_array);

            // Update comment.
            $ret = db_process_sql_update(
                $event_table,
                ['user_comment' => $event_comments],
                ['id_evento' => implode(',', $id_event)]
            );
        break;

        case 'old':
            // Give old ugly format to comment. TODO: Change this method for
            // aux table or json.
            $comment = str_replace(["\r\n", "\r", "\n"], '<br>', $comment);

            if ($comment != '') {
                $commentbox = '<div style="border:1px dotted #CCC; min-height: 10px;">'.$comment.'</div>';
            } else {
                $commentbox = '';
            }

            // Don't translate 'by' word because if multiple users with
            // different languages make comments in the same console
            // will be a mess.
            $comment = '<b>-- '.$action.' by '.$config['id_user'].' ['.date($config['date_format']).'] --</b><br>'.$commentbox.'<br>';

            // Update comment.
            $sql_validation = sprintf(
                'UPDATE %s
                SET user_comment = concat("%s", user_comment)
                WHERE id_evento in (%s)',
                $event_table,
                $comment,
                implode(',', $id_event)
            );

            $ret = db_process_sql($sql_validation);
        break;

        default:
            // Ignore.
        break;
    }

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
    $id_extra=''
) {
    global $config;

    if ($source === false) {
        $source = get_product_name();
    }

    $table_events = 'tevento';
    if (defined('METACONSOLE')) {
        $table_events = 'tmetaconsole_event';

        $sql = sprintf(
            'INSERT INTO '.$table_events.' (id_agente, id_grupo, evento,
                timestamp, estado, utimestamp, id_usuario,
                event_type, criticity, id_agentmodule, id_alert_am,
                critical_instructions, warning_instructions,
                unknown_instructions, source, tags, custom_data,
                server_id, id_extra, data, module_status) 
            VALUES (%d, %d, "%s", NOW(), %d, UNIX_TIMESTAMP(NOW()),
                "%s", "%s", %d, %d, %d, "%s", "%s", "%s", "%s",
                "%s", "%s", %d, "%s", %d, %d)',
            $id_agent,
            $id_group,
            $event,
            $status,
            $id_user,
            $event_type,
            $priority,
            $id_agent_module,
            $id_aam,
            $critical_instructions,
            $warning_instructions,
            $unknown_instructions,
            $source,
            $tags,
            $custom_data,
            $server_id,
            $id_extra,
            $data,
            $module_status
        );
    } else {
        $sql = sprintf(
            'INSERT INTO '.$table_events.' (id_agente, id_grupo, evento,
                timestamp, estado, utimestamp, id_usuario,
                event_type, criticity, id_agentmodule, id_alert_am,
                critical_instructions, warning_instructions,
                unknown_instructions, source, tags, custom_data, id_extra, data, module_status) 
            VALUES (%d, %d, "%s", NOW(), %d, UNIX_TIMESTAMP(NOW()),
                "%s", "%s", %d, %d, %d, "%s", "%s", "%s", "%s", "%s", "%s", "%s", %d, %d)',
            $id_agent,
            $id_group,
            $event,
            $status,
            $id_user,
            $event_type,
            $priority,
            $id_agent_module,
            $id_aam,
            $critical_instructions,
            $warning_instructions,
            $unknown_instructions,
            $source,
            $tags,
            $custom_data,
            $id_extra,
            $data,
            $module_status
        );
    }

    return (int) db_process_sql($sql, 'insert_id');
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

    if ($agent_id == 0) {
        $agent_condition = '';
    } else {
        $agent_condition = ' id_agente = '.$agent_id.' AND ';
    }

    if ($filter == '') {
        $filter = '1 = 1';
    }

    $secondary_join = 'LEFT JOIN tagent_secondary_group tasg ON tevento.id_agente = tasg.id_agent';

    $sql = sprintf(
        'SELECT DISTINCT tevento.*
		FROM tevento %s
		WHERE %s %s
		ORDER BY utimestamp DESC LIMIT %d',
        $secondary_join,
        $agent_condition,
        $filter,
        $limit
    );

    $result = db_get_all_rows_sql($sql);

    if ($result === false) {
        if ($return) {
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
        $table->class = 'info_table no-td-padding';
        if (!$tactical_view) {
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
        $table->head[$i] = "<span title='".__('Severity')."'>".__('S.').'</span>';
        $table->headstyle[$i] = 'width: 1%;text-align: center;';
        $table->style[$i++] = 'text-align: center;';

        $table->head[$i] = __('Type');
        $table->headstyle[$i] = 'width: 3%;text-align: center;';
        $table->style[$i++] = 'text-align: center;';

        $table->head[$i] = __('Event name');
        $table->headstyle[$i] = '';
        $table->style[$i++] = 'word-break: break-word;';

        if ($agent_id == 0) {
            $table->head[$i] = __('Agent name');
            $table->headstyle[$i] = '';
            $table->style[$i++] = 'word-break: break-all;';
        }

        $table->head[$i] = __('Timestamp');
        $table->headstyle[$i] = 'width: 150px;';
        $table->style[$i++] = 'word-break: break-word;';

        $table->head[$i] = __('Status');
        $table->headstyle[$i] = 'width: 150px;text-align: center;';
        $table->style[$i++] = 'text-align: center;';

        $table->head[$i] = "<span title='".__('Validated')."'>".__('V.').'</span>';
        $table->headstyle[$i] = 'width: 1%;text-align: center;';
        $table->style[$i++] = 'text-align: center;';

        $all_groups = [];
        if ($agent_id != 0) {
            $all_groups = agents_get_all_groups_agent($agent_id);
        }

        foreach ($result as $event) {
            // Copy all groups of the agent and append the event group.
            $check_events = $all_groups;
            $check_events[] = $event['id_grupo'];
            if (! check_acl_one_of_groups($config['id_user'], $check_events, 'ER')) {
                continue;
            }

            $data = [];

            // Colored box.
            switch ($event['estado']) {
                case 0:
                    $img = 'images/star.png';
                    $title = __('New event');
                break;

                case 1:
                    $img = 'images/tick.png';
                    $title = __('Event validated');
                break;

                case 2:
                    $img = 'images/hourglass.png';
                    $title = __('Event in process');
                break;

                default:
                    // Ignore.
                break;
            }

            $i = 0;
            // Criticity.
            $data[$i++] = ui_print_event_priority($event['criticity'], true, true);

            // Event type.
            $data[$i++] = events_print_type_img($event['event_type'], true);

            // Event text.
            $data[$i++] = ui_print_string_substr(
                strip_tags(io_safe_output($event['evento'])),
                75,
                true,
                '7.5'
            );

            if ($agent_id == 0) {
                if ($event['id_agente'] > 0) {
                    // Agent name.
                    // Get class name, for the link color, etc.
                    $data[$i] = "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$event['id_agente']."'>".agents_get_alias($event['id_agente']).'</A>';
                    // For System or SNMP generated alerts.
                } else if ($event['event_type'] == 'system') {
                    $data[$i] = __('System');
                } else {
                    $data[$i] = __('Alert').'SNMP';
                }

                $i++;
            }

            // Timestamp.
            $data[$i++] = ui_print_timestamp($event['timestamp'], true, ['style' => 'font-size: 7.5pt; letter-spacing: 0.3pt;']);

            // Status.
            $data[$i++] = ui_print_event_type($event['event_type'], true);

            $data[$i++] = html_print_image(
                $img,
                true,
                [
                    'class' => 'image_status',
                    'title' => $title,
                ]
            );
            $table->data[] = $data;
        }

        $events_table = html_print_table($table, true);
        $out = $events_table;

        if (!$tactical_view) {
            $out .= '<table width="100%"><tr><td style="width: 90%; vertical-align: top; padding-top: 0px;">';
            if ($agent_id != 0) {
                $out .= '</td><td style="width: 200px; vertical-align: top;">';
                $out .= '<table cellpadding=0 cellspacing=0 class="databox"><tr><td>';
                $out .= '<fieldset class="databox tactical_set">
						<legend>'.__('Events -by module-').'</legend>'.graph_event_module(180, 100, $event['id_agente']).'</fieldset>';
                $out .= '</td></tr></table>';
            } else {
                $out .= '</td><td style="width: 200px; vertical-align: top;">';
                $out .= '<table cellpadding=0 cellspacing=0 class="databox"><tr><td>';
                $out .= '<fieldset class="databox tactical_set">
						<legend>'.__('Event graph').'</legend>'.grafico_eventos_total('', 180, 60).'</fieldset>';
                $out .= '<fieldset class="databox tactical_set">
						<legend>'.__('Event graph by agent').'</legend>'.grafico_eventos_grupo(180, 60).'</fieldset>';
                $out .= '</td></tr></table>';
            }

            $out .= '</td></tr></table>';
        }

        unset($table);

        if ($return) {
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

    switch ($type) {
        case 'alert_recovered':
            $icon = 'bell.png';
        break;

        case 'alert_manual_validation':
            $icon = 'ok.png';
        break;

        case 'going_down_critical':
        case 'going_up_critical':
            // This is to be backwards compatible.
            $icon = 'module_critical.png';
        break;

        case 'going_up_normal':
        case 'going_down_normal':
            // This is to be backwards compatible.
            $icon = 'module_ok.png';
        break;

        case 'going_up_warning':
        case 'going_down_warning':
            $icon = 'module_warning.png';
        break;

        case 'going_unknown':
            $icon = 'module_unknown.png';
        break;

        case 'alert_fired':
            $icon = 'bell_error.png';
        break;

        case 'system':
            $icon = 'cog.png';
        break;

        case 'recon_host_detected':
            $icon = 'recon.png';
        break;

        case 'new_agent':
            $icon = 'agent.png';
        break;

        case 'configuration_change':
            $icon = 'config.png';
        break;

        case 'unknown':
        default:
            $icon = 'lightning_go.png';
        break;
    }

    if ($only_url) {
        $output = $urlImage.'/images/'.$icon;
    } else {
        $output .= html_print_image(
            'images/'.$icon,
            true,
            ['title' => events_print_type_description($type, true)]
        );
    }

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
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param mixed   $begin                     Begin.
 * @param mixed   $result                    Result.
 * @param mixed   $id_group                  Group id to get events for.
 * @param integer $period                    Period in seconds to get events.
 * @param integer $date                      Beginning date to get events.
 * @param boolean $filter_event_validated    Filter_event_validated.
 * @param boolean $filter_event_critical     Filter_event_critical.
 * @param boolean $filter_event_warning      Filter_event_warning.
 * @param boolean $filter_event_no_validated Filter_event_no_validated.
 *
 * @return array An array with all the events happened.
 */
function events_get_group_events_steps(
    $begin,
    &$result,
    $id_group,
    $period,
    $date,
    $filter_event_validated=false,
    $filter_event_critical=false,
    $filter_event_warning=false,
    $filter_event_no_validated=false
) {
    global $config;

    $id_group = groups_safe_acl($config['id_user'], $id_group, 'ER');

    if (empty($id_group)) {
        // An empty array means the user doesn't have access.
        return false;
    }

    $datelimit = ($date - $period);

    $sql_where = ' AND 1 = 1 ';
    $criticities = [];
    if ($filter_event_critical) {
        $criticities[] = 4;
    }

    if ($filter_event_warning) {
        $criticities[] = 3;
    }

    if (!empty($criticities)) {
        $sql_where .= ' AND criticity IN ('.implode(', ', $criticities).')';
    }

    if ($filter_event_validated) {
        $sql_where .= ' AND estado = 1 ';
    }

    if ($filter_event_no_validated) {
        $sql_where .= ' AND estado = 0 ';
    }

    $sql = sprintf(
        'SELECT *,
			(SELECT t2.nombre
				FROM tagente t2
				WHERE t2.id_agente = t3.id_agente) AS agent_name,
			(SELECT t2.fullname
				FROM tusuario t2
				WHERE t2.id_user = t3.id_usuario) AS user_name
		FROM tevento t3
		WHERE utimestamp > %d AND utimestamp <= %d
			AND id_grupo IN (%s) '.$sql_where.'
		ORDER BY utimestamp ASC',
        $datelimit,
        $date,
        implode(',', $id_group)
    );

    return db_get_all_row_by_steps_sql($begin, $result, $sql);
}


/**
 * Get all the events happened in an Agent during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param integer $id_agent                   Agent id to get events.
 * @param integer $period                     Period in seconds to get events.
 * @param integer $date                       Beginning date to get events.
 * @param boolean $history                    History.
 * @param boolean $show_summary_group         Show_summary_group.
 * @param boolean $filter_event_severity      Filter_event_severity.
 * @param boolean $filter_event_type          Filter_event_type.
 * @param boolean $filter_event_status        Filter_event_status.
 * @param boolean $filter_event_filter_search Filter_event_filter_search.
 * @param boolean $id_group                   Id_group.
 * @param boolean $events_group               Events_group.
 * @param boolean $id_agent_module            Id_agent_module.
 * @param boolean $events_module              Events_module.
 * @param boolean $id_server                  Id_server.
 *
 * @return array An array with all the events happened.
 */
function events_get_agent(
    $id_agent,
    $period,
    $date=0,
    $history=false,
    $show_summary_group=false,
    $filter_event_severity=false,
    $filter_event_type=false,
    $filter_event_status=false,
    $filter_event_filter_search=false,
    $id_group=false,
    $events_group=false,
    $id_agent_module=false,
    $events_module=false,
    $id_server=false
) {
    global $config;

    if (!is_numeric($date)) {
        $date = time_w_fixed_tz($date);
    }

    if (is_metaconsole() && $events_group === false) {
        $id_server = true;
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    if ($events_group) {
        $id_group = groups_safe_acl($config['id_user'], $id_group, 'ER');

        if (empty($id_group)) {
            // An empty array means the user doesn't have access.
            return false;
        }
    }

    $datelimit = ($date - $period);

    $sql_where = '';
    $severity_all = 0;
    if (!empty($filter_event_severity)) {
        foreach ($filter_event_severity as $key => $value) {
            switch ($value) {
                case -1:
                    $severity_all = 1;
                break;

                case 34:
                    $filter_event_severity[$key] = '3, 4';
                break;

                case 20:
                    $filter_event_severity[$key] = '0, 1, 3, 4, 5, 6';
                break;

                case 21:
                    $filter_event_severity[$key] = '4, 2';
                break;

                default:
                    // Ignore.
                break;
            }
        }

        if (!$severity_all) {
            $sql_where .= ' AND criticity IN ('.implode(', ', $filter_event_severity).')';
        }
    }

    $status_all = 0;
    if (!empty($filter_event_status)) {
        foreach ($filter_event_status as $key => $value) {
            switch ($value) {
                case -1:
                    $status_all = 1;
                break;

                case 3:
                    $filter_event_status[$key] = ('0, 2');
                default:
                    // Ignore.
                break;
            }
        }

        if (!$status_all) {
            $sql_where .= ' AND estado IN ('.implode(
                ', ',
                $filter_event_status
            ).')';
        }
    }

    if (!empty($filter_event_type) && $filter_event_type[0] != 'all') {
        $sql_where .= ' AND (';
        $type = [];
        foreach ($filter_event_type as $event_type) {
            if ($event_type != '') {
                // If normal, warning, could be several (going_up_warning, going_down_warning... too complex.
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

        $sql_where .= implode(' OR ', $type).')';
    }

    if (!empty($filter_event_filter_search)) {
        $sql_where .= ' AND (evento LIKE "%'.io_safe_input($filter_event_filter_search).'%" OR id_evento LIKE "%'.io_safe_input($filter_event_filter_search).'%")';
    }

    if ($events_group) {
        $sql_where .= sprintf(
            ' AND id_grupo IN (%s) AND utimestamp > %d
			AND utimestamp <= %d ',
            implode(',', $id_group),
            $datelimit,
            $date
        );
    } else if ($events_module) {
        $sql_where .= sprintf(
            ' AND id_agentmodule = %d AND utimestamp > %d
			AND utimestamp <= %d ',
            $id_agent_module,
            $datelimit,
            $date
        );
    } else {
        $sql_where .= sprintf(
            ' AND id_agente = %d AND utimestamp > %d
			AND utimestamp <= %d ',
            $id_agent,
            $datelimit,
            $date
        );
    }

    if (is_metaconsole() && $id_server) {
        $sql_where .= ' AND server_id = '.$id_server;
    }

    if ($show_summary_group) {
        return events_get_events_grouped(
            $sql_where,
            0,
            1000,
            is_metaconsole(),
            false,
            false,
            $history
        );
    } else {
        return events_get_events_no_grouped(
            $sql_where,
            0,
            1000,
            (is_metaconsole() && $id_server) ? true : false,
            false,
            false,
            $history
        );
    }
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
    } else {
        $fields[-1] = __('All event');
        $fields[0]  = __('New');
        $fields[1]  = __('Validated');
        $fields[2]  = __('In process');
        $fields[3]  = __('Not Validated');
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
function events_check_event_filter_group($id_filter)
{
    global $config;

    $id_group = db_get_value('id_group_filter', 'tevent_filter', 'id_filter', $id_filter);
    $own_info = get_user_info($config['id_user']);
    // Get group list that user has access.
    $groups_user = users_get_groups($config['id_user'], 'EW', $own_info['is_admin'], true);

    // Permissions in any group allow to edit "All group" filters.
    if ($id_group == 0 && !empty($groups_user)) {
        return true;
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
		SELECT id_filter, id_name
		FROM tevent_filter
		WHERE id_group_filter IN (0, '.implode(',', array_keys($user_groups)).')';

    $event_filters = db_get_all_rows_sql($sql);

    if ($event_filters === false) {
        return [];
    } else {
        $result = [];
        foreach ($event_filters as $event_filter) {
            $result[$event_filter['id_filter']] = $event_filter['id_name'];
        }
    }

    return $result;
}


/**
 * Events pages functions to load modal window with advanced view of an event.
 * Called from include/ajax/events.php.
 *
 * @param mixed $event         Event.
 * @param array $childrens_ids Children_ids.
 *
 * @return string HTML.
 */
function events_page_responses($event, $childrens_ids=[])
{
    global $config;
    //
    // Responses.
    //
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

    if (tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EM', $event['clean_tags'], $childrens_ids)) {
        // Owner.
        $data = [];
        $data[0] = __('Change owner');
        // Owner change can be done to users that belong to the event group
        // with ER permission.
        $profiles_view_events = db_get_all_rows_filter('tperfil', ['event_view' => '1'], 'id_perfil');
        foreach ($profiles_view_events as $k => $v) {
            $profiles_view_events[$k] = reset($v);
        }

        // Juanma (05/05/2014) Fix : Propagate ACL.
        $_user_groups = array_keys(
            users_get_groups($config['id_user'], 'ER', users_can_manage_group_all())
        );
        $strict_user = db_get_value(
            'strict_acl',
            'tusuario',
            'id_user',
            $config['id_user']
        );
        if ($strict_user) {
            $user_name = db_get_value(
                'fullname',
                'tusuario',
                'id_user',
                $config['id_user']
            );

            $users = [];
            $users[0]['id_user'] = $config['id_user'];
            $users[0]['fullname'] = $user_name;
        } else {
            $users = groups_get_users(
                $_user_groups,
                ['id_perfil' => $profiles_view_events],
                true
            );
        }

        foreach ($users as $u) {
            $owners[$u['id_user']] = $u['fullname'];
        }

        if ($event['owner_user'] == '') {
            $owner_name = __('None');
        } else {
            $owner_name = db_get_value(
                'fullname',
                'tusuario',
                'id_user',
                $event['owner_user']
            );
            $owners[$event['owner_user']] = $owner_name;
        }

        $data[1] = html_print_select(
            $owners,
            'id_owner',
            $event['owner_user'],
            '',
            __('None'),
            -1,
            true
        );
        $data[2] .= html_print_button(
            __('Update'),
            'owner_button',
            false,
            'event_change_owner();',
            'class="sub next"',
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
        $event['clean_tags'],
        $childrens_ids
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

    if (!$status_blocked) {
        $data[2] .= html_print_button(
            __('Update'),
            'status_button',
            false,
            'event_change_status(\''.$event['similar_ids'].'\');',
            'class="sub next"',
            true
        );
    }

    $table_responses->data[] = $data;

    // Comments.
    $data = [];
    $data[0] = __('Comment');
    $data[1] = '';
    $data[2] = html_print_button(
        __('Add comment'),
        'comment_button',
        false,
        '$(\'#link_comments\').trigger(\'click\');',
        'class="sub next"',
        true
    );

    $table_responses->data[] = $data;

    if (tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EM',
        $event['clean_tags'],
        $childrens_ids
    )
    ) {
        // Delete.
        $data = [];
        $data[0] = __('Delete event');
        $data[1] = '';
        $data[2] = '<form method="post">';
        $data[2] .= html_print_button(
            __('Delete event'),
            'delete_button',
            false,
            'if(!confirm(\''.__('Are you sure?').'\')) { return false; } this.form.submit();',
            'class="sub cancel"',
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

    if (empty($event_responses)) {
        $data[1] = '<i>'.__('N/A').'</i>';
    } else {
        $responses = [];
        foreach ($event_responses as $v) {
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

        $data[2] .= html_print_button(
            __('Execute'),
            'custom_response_button',
            false,
            'execute_response('.$event['id_evento'].','.$server_id.')',
            "class='sub next'",
            true
        );
    }

    $table_responses->data[] = $data;

    $responses_js = "<script>
			$('#select_custom_response').change(function() {
				var id_response = $('#select_custom_response').val();
				var params = get_response_params(id_response);
				var description = get_response_description(id_response);
				$('.params_rows').remove();
				
				$('#responses_table')
					.append('<tr class=\"params_rows\"><td>".__('Description')."</td><td style=\"text-align:left; height:30px;\" colspan=\"2\">'+description+'</td></tr>');
				
				if (params.length == 1 && params[0] == '') {
					return;
				}
				
				$('#responses_table')
					.append('<tr class=\"params_rows\"><td style=\"text-align:left; padding-left:20px; height:30px;\" colspan=\"3\">".__('Parameters')."</td></tr>');
				
				for (i = 0; i < params.length; i++) {
					add_row_param('responses_table',params[i]);
				}
			});
			$('#select_custom_response').trigger('change');
			</script>";

    $responses = '<div id="extended_event_responses_page" class="extended_event_pages">'.html_print_table($table_responses, true).$responses_js.'</div>';

    return $responses;
}


/**
 * Replace macros in the target of a response and return it.
 * If server_id > 0, it's a metaconsole query.
 *
 * @param integer $event_id    Event identifier.
 * @param integer $response_id Event response identifier.
 * @param integer $server_id   Node identifier (for metaconsole).
 * @param boolean $history     Use the history database or not.
 *
 * @return string The response text with the macros applied.
 */
function events_get_response_target(
    int $event_id,
    int $response_id,
    int $server_id=0,
    bool $history=false
) {
    global $config;

    // If server_id > 0, it's a metaconsole query.
    $meta = $server_id > 0 || is_metaconsole();
    $event_table = events_get_events_table($meta, $history);
    $event = db_get_row($event_table, 'id_evento', $event_id);

    $event_response = db_get_row('tevent_response', 'id', $response_id);
    $target = io_safe_output($event_response['target']);

    // Substitute each macro.
    if (strpos($target, '_agent_address_') !== false) {
        if ($meta) {
            $agente_table_name = 'tmetaconsole_agent';
            $filter = [
                'id_tagente'            => $event['id_agente'],
                'id_tmetaconsole_setup' => $server_id,
            ];
        } else {
            $agente_table_name = 'tagente';
            $filter = ['id_agente' => $event['id_agente']];
        }

        $ip = db_get_value_filter('direccion', $agente_table_name, $filter);
        // If agent has not an IP, display N/A.
        if ($ip === false) {
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
            if ($meta) {
                $server = metaconsole_get_connection_by_id($server_id);
                metaconsole_connect($server);
            }

            $module = db_get_row('tagente_modulo', 'id_agente_modulo', $event['id_agentmodule']);
            if (empty($module['ip_target'])) {
                $module['ip_target'] = __('N/A');
            }

            $target = str_replace('_module_address_', $module['ip_target'], $target);
            if (empty($module['nombre'])) {
                $module['nombre'] = __('N/A');
            }

            $target = str_replace(
                '_module_name_',
                io_safe_output($module['nombre']),
                $target
            );

            if ($meta) {
                metaconsole_restore_db();
            }
        } else {
            $target = str_replace('_module_address_', __('N/A'), $target);
            $target = str_replace('_module_name_', __('N/A'), $target);
        }
    }

    if (strpos($target, '_event_id_') !== false) {
        $target = str_replace('_event_id_', $event['id_evento'], $target);
    }

    if (strpos($target, '_user_id_') !== false) {
        if (!empty($event['id_usuario'])) {
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
            groups_get_name($event['id_grupo'], true),
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
            date($config['date_format'], $event['utimestamp']),
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
            empty($event['is_alert_am']) ? __('N/A') : $event['is_alert_am'],
            $target
        );
    }

    if (strpos($target, '_event_severity_id_') !== false) {
        $target = str_replace('_event_severity_id_', $event['criticity'], $target);
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
        if (empty($event['id_extra'])) {
            $target = str_replace('_event_extra_id_', __('N/A'), $target);
        } else {
            $target = str_replace('_event_extra_id_', $event['id_extra'], $target);
        }
    }

    if (strpos($target, '_event_source_') !== false) {
        $target = str_replace('_event_source_', $event['source'], $target);
    }

    if (strpos($target, '_event_instruction_') !== false) {
        $target = str_replace(
            '_event_instruction_',
            events_display_instructions($event['event_type'], $event, false),
            $target
        );
    }

    if (strpos($target, '_owner_user_') !== false) {
        if (empty($event['owner_user'])) {
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
    if (!empty($event['custom_data'])) {
        $custom_data = json_decode(base64_decode($event['custom_data']));
        foreach ($custom_data as $key => $value) {
            $target = str_replace('_customdata_'.$key.'_', $value, $target);
        }
    }

    // This will replace the macro with the current logged user.
    if (strpos($target, '_current_user_') !== false) {
        $target = str_replace('_current_user_', $config['id_user'], $target);
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
 * @param array  $event  To be displayed.
 * @param string $server Server (if in metaconsole environment).
 *
 * @return string HTML to be displayed.
 */
function events_page_details($event, $server='')
{
    global $img_sev;
    global $config;

    // If server is provided, get the hash parameters.
    if (!empty($server) && is_metaconsole()) {
        $hashdata = metaconsole_get_server_hashdata($server);
        $hashstring = '&amp;loginhash=auto&loginhash_data='.$hashdata.'&loginhash_user='.str_rot13($config['id_user']);
        $serverstring = $server['server_url'].'/';

        if (metaconsole_connect($server) !== NOERR) {
            return ui_print_error_message(__('There was an error connecting to the node'), '', true);
        }
    } else {
        $hashstring = '';
        $serverstring = '';
    }

    // Details.
    $table_details = new stdClass;
    $table_details->width = '100%';
    $table_details->data = [];
    $table_details->head = [];
    $table_details->cellspacing = 0;
    $table_details->cellpadding = 0;
    $table_details->class = 'table_modal_alternate';

    /*
     * Useless switch.

        switch ($event['event_type']) {
        case 'going_unknown':
        case 'going_up_warning':
        case 'going_down_warning':
        case 'going_up_critical':
        case 'going_down_critical':
        default:
            // Ignore.
        break;
        }
     */

    if ($event['id_agente'] != 0) {
        $agent = db_get_row('tagente', 'id_agente', $event['id_agente']);
    } else {
        $agent = [];
    }

    $data[0] = __('Agent details');
    $data[1] = empty($agent) ? '<i>'.__('N/A').'</i>' : '';
    $table_details->data[] = $data;

    if (!empty($agent)) {
        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Name').'</div>';
        if (can_user_access_node()) {
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
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('IP Address').'</div>';
        $data[1] = empty($agent['direccion']) ? '<i>'.__('N/A').'</i>' : $agent['direccion'];
        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('OS').'</div>';
        $data[1] = ui_print_os_icon($agent['id_os'], true, true);
        if (!empty($agent['os_version'])) {
            $data[1] .= ' ('.$agent['os_version'].')';
        }

        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Last contact').'</div>';
        $data[1] = ($agent['ultimo_contacto'] == '1970-01-01 00:00:00') ? '<i>'.__('N/A').'</i>' : date_w_fixed_tz($agent['ultimo_contacto']);
        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Last remote contact').'</div>';
        $data[1] = ($agent['ultimo_contacto_remoto'] == '1970-01-01 00:00:00') ? '<i>'.__('N/A').'</i>' : date_w_fixed_tz($agent['ultimo_contacto_remoto']);
        $table_details->data[] = $data;

        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Custom fields').'</div>';
        $data[1] = html_print_button(
            __('View custom fields'),
            'custom_button',
            false,
            '$(\'#link_custom_fields\').trigger(\'click\');',
            'class="sub next"',
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
    $data[0] = __('Module details');
    $data[1] = empty($module) ? '<i>'.__('N/A').'</i>' : '';
    $table_details->data[] = $data;

    if (!empty($module)) {
        // Module name.
        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Name').'</div>';
        $data[1] = $module['nombre'];
        $table_details->data[] = $data;

        // Module group.
        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Module group').'</div>';
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
        $strict_user = (bool) db_get_value(
            'strict_acl',
            'tusuario',
            'id_user',
            $config['id_user']
        );

        if (!empty($agent['id_grupo'])) {
            $acl_graph = check_acl(
                $config['id_user'],
                $agent['id_grupo'],
                'RR'
            );
        }

        if ($acl_graph) {
            $data = [];
            $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Graph').'</div>';

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
                'label'   => base64_encode($module['nombre']),
                'refresh' => SECONDS_10MINUTES,
            ];

            if (defined('METACONSOLE')) {
                // Set the server id.
                $graph_params['server'] = $server['id'];
            }

            $graph_params_str = http_build_query($graph_params);

            $link = "winopeng('".$url.'?'.$graph_params_str."','".$win_handle."')";

            $data[1] = '<a href="javascript:'.$link.'">';
            $data[1] .= html_print_image('images/chart_curve.png', true);
            $data[1] .= '</a>';
            $table_details->data[] = $data;
        }
    }

    $data = [];
    $data[0] = __('Alert details');
    $data[1] = ($event['id_alert_am'] == 0) ? '<i>'.__('N/A').'</i>' : '';
    $table_details->data[] = $data;

    if ($event['id_alert_am'] != 0) {
        $data = [];
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Source').'</div>';
        $data[1] = '<a href="'.$serverstring.'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event['id_agente'].'&amp;tab=alert'.$hashstring.'">';
        $standby = db_get_value('standby', 'talert_template_modules', 'id', $event['id_alert_am']);
        if (!$standby) {
            $data[1] .= html_print_image(
                'images/bell.png',
                true,
                ['title' => __('Go to data overview')]
            );
        } else {
            $data[1] .= html_print_image(
                'images/bell_pause.png',
                true,
                ['title' => __('Go to data overview')]
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
        $data[0] = '<div style="font-weight:normal; margin-left: 20px;">'.__('Priority').'</div>';

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
    $data[1] = html_entity_decode(events_display_instructions($event['event_type'], $event, true));
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

    $details = '<div id="extended_event_details_page" class="extended_event_pages">'.html_print_table($table_details, true).'</div>';

    if (!empty($server) && is_metaconsole()) {
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

    $table->width = '100%';
    $table->data = [];
    $table->head = [];
    $table->class = 'table_modal_alternate';

    $json_custom_data = base64_decode($event['custom_data']);
    $custom_data = json_decode($json_custom_data);
    if ($custom_data === null) {
        return '<div id="extended_event_custom_data_page" class="extended_event_pages">'.__('Invalid custom data: %s', $json_custom_data).'</div>';
    }

    $i = 0;
    foreach ($custom_data as $field => $value) {
        $table->data[$i][0] = io_safe_output($field);
        $table->data[$i][1] = io_safe_output($value);
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
            'img'   => 'images/star.png',
            'title' => __('New event'),
        ];

        case 1:
        return [
            'img'   => 'images/tick.png',
            'title' => __('Event validated'),
        ];

        case 2:
        return [
            'img'   => 'images/hourglass.png',
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
 *
 * @return string Safe output.
 */
function events_display_instructions($event_type='', $inst=[], $italic=true)
{
    switch ($event_type) {
        case 'going_unknown':
            if ($inst['unknown_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['unknown_instructions']));
            }
        break;

        case 'going_up_warning':
        case 'going_down_warning':
            if ($inst['warning_instructions'] != '') {
                return str_replace("\n", '<br>', io_safe_output($inst['warning_instructions']));
            }
        break;

        case 'going_up_critical':
        case 'going_down_critical':
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

    /*
        Commented out (old)
        // $group_rep = $event['similar_ids'] == -1 ? 1 : count(explode(',',$event['similar_ids']));
    */

    global $group_rep;

    $secondary_groups = '';
    if (isset($event['id_agente']) && $event['id_agente'] > 0) {
        enterprise_include_once('include/functions_agents.php');
        $secondary_groups_selected = enterprise_hook('agents_get_secondary_groups', [$event['id_agente'], is_metaconsole()]);
        if (!empty($secondary_groups_selected)) {
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
    $data[1] = '#'.$event['id_evento'];
    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Event name');
    $data[1] = '<span style="word-break: break-word;">'.events_display_name($event['evento']).'</span>';
    $table_general->data[] = $data;

    $data = [];
    $data[0] = __('Timestamp');

    if ($group_rep == 1 && $event['event_rep'] > 1) {
        $data[1] = __('First event').': '.date($config['date_format'], $event['timestamp_first']).'<br>'.__('Last event').': '.date($config['date_format'], $event['timestamp_last']);
    } else {
        $data[1] = date($config['date_format'], $event['utimestamp']);
    }

    $table_general->data[] = $data;

    // $event['owner_user'] = $event['id_usuario'];
    $data = [];
    $data[0] = __('Owner');
    if (empty($event['owner_user'])) {
        $data[1] = '<i>'.__('N/A').'</i>';
    } else {
        $user_owner = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
        if (empty($user_owner)) {
            $user_owner = $event['owner_user'];
        }

        $data[1] = $user_owner;
    }

    $table_general->cellclass[3][1] = 'general_owner';

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
    $data[0] = __('Status');
    $data[1] = $event_st['title'];
    $data[2] = html_print_image($event_st['img'], true);
    $table_general->data[] = $data;

    // If event is validated, show who and when acknowleded it.
    $data = [];
    $data[0] = __('Acknowledged by');

    if ($event['estado'] == 1) {
        $user_ack = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
        if (empty($user_ack)) {
            $user_ack = $event['id_usuario'];
        }

        $date_ack = date($config['date_format'], $event['ack_utimestamp']);
        $data[1] = $user_ack.' ('.$date_ack.')';
    } else {
        $data[1] = '<i>'.__('N/A').'</i>';
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
    if (empty($contact)) {
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

    $table_data = $table_general->data;
    if (is_array($table_data)) {
        $table_data_total = count($table_data);
    } else {
        $table_data_total = -1;
    }

    for ($i = 0; $i <= $table_data_total; $i++) {
        if (is_array($table_data[$i]) && count($table_data[$i]) == 2) {
            $table_general->colspan[$i][1] = 2;
            $table_general->style[2] = 'text-align:center; width:10%;';
        }
    }

    $general = '<div id="extended_event_general_page" class="extended_event_pages">'.html_print_table($table_general, true).'</div>';

    return $general;
}


/**
 * Generate 'comments' page for event viewer.
 *
 * @param array $event Event.
 *
 * @return string HTML.
 */
function events_page_comments($event, $ajax=false)
{
    // Comments.
    global $config;

    $table_comments = new stdClass;
    $table_comments->width = '100%';
    $table_comments->data = [];
    $table_comments->head = [];
    $table_comments->class = 'table_modal_alternate';

    $comments = ($event['user_comment'] ?? '');

    if (empty($comments)) {
        $table_comments->style[0] = 'text-align:center;';
        $table_comments->colspan[0][0] = 2;
        $data = [];
        $data[0] = __('There are no comments');
        $table_comments->data[] = $data;
    } else {
        if (is_array($comments)) {
            foreach ($comments as $comm) {
                if (empty($comm)) {
                    continue;
                }

                $comments_array[] = io_safe_output(json_decode($comm, true));
            }
        } else {
            $comments = str_replace(["\n", '&#x0a;'], '<br>', $comments);
            // If comments are not stored in json, the format is old.
            $comments_array[] = io_safe_output(json_decode($comments, true));
        }

        foreach ($comments_array as $comm) {
            // Show the comments more recent first.
            if (is_array($comm)) {
                $comm = array_reverse($comm);
            }

            if (empty($comm)) {
                $comments_format = 'old';
            } else {
                $comments_format = 'new';
            }

            switch ($comments_format) {
                case 'new':
                    foreach ($comm as $c) {
                        $data[0] = '<b>'.$c['action'].' by '.$c['id_user'].'</b>';
                        $data[0] .= '<br><br><i>'.date($config['date_format'], $c['utimestamp']).'</i>';
                        $data[1] = '<p style="word-break: break-word;">'.$c['comment'].'</p>';
                        $table_comments->data[] = $data;
                    }
                break;

                case 'old':
                    $comm = explode('<br>', $comments);

                    // Split comments and put in table.
                    $col = 0;
                    $data = [];

                    foreach ($comm as $c) {
                        switch ($col) {
                            case 0:
                                $row_text = preg_replace('/\s*--\s*/', '', $c);
                                $row_text = preg_replace('/\<\/b\>/', '</i>', $row_text);
                                $row_text = preg_replace('/\[/', '</b><br><br><i>[', $row_text);
                                $row_text = preg_replace('/[\[|\]]/', '', $row_text);
                            break;

                            case 1:
                                $row_text = preg_replace("/[\r\n|\r|\n]/", '<br>', io_safe_output(strip_tags($c)));
                            break;

                            default:
                                // Ignore.
                            break;
                        }

                        $data[$col] = $row_text;

                        $col++;

                        if ($col == 2) {
                            $col = 0;
                            $table_comments->data[] = $data;
                            $data = [];
                        }
                    }
                break;

                default:
                    // Ignore.
                break;
            }
        }
    }

    if (((tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EM',
        $event['clean_tags'],
        $childrens_ids
    )) || (tags_checks_event_acl(
        $config['id_user'],
        $event['id_grupo'],
        'EW',
        $event['clean_tags'],
        $childrens_ids
    ))) && $config['show_events_in_local'] == false || $config['event_replication'] == false
    ) {
        $comments_form = '<br><div id="comments_form" style="width:98%;">';
        $comments_form .= html_print_textarea(
            'comment',
            3,
            10,
            '',
            'style="min-height: 15px; padding:0; width: 100%; disabled"',
            true
        );

        $comments_form .= '<br><div style="text-align:right; margin-top:10px;">';
        $comments_form .= html_print_button(
            __('Add comment'),
            'comment_button',
            false,
            'event_comment();',
            'class="sub next"',
            true
        );
        $comments_form .= '</div><br></div>';
    } else {
        $comments_form = ui_print_message(
            __('If event replication is ongoing, it won\'t be possible to enter comments here. This option is only to allow local pandora users to see comments, but not to operate with them. The operation, when event replication is enabled, must be done only in the Metaconsole.')
        );
    }

    if ($ajax) {
        return $comments_form.html_print_table($table_comments, true);
    }

    return '<div id="extended_event_comments_page" class="extended_event_pages">'.$comments_form.html_print_table($table_comments, true).'</div>';
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
    return explode(',', str_replace(' ', '', $event_tags));
}


/**
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param mixed   $id_group                   Group id to get events for.
 * @param integer $period                     Period  in seconds to get events.
 * @param integer $date                       Beginning date to get events.
 * @param boolean $filter_event_severity      Filter_event_severity.
 * @param boolean $filter_event_type          Filter_event_type.
 * @param boolean $filter_event_status        Filter_event_status.
 * @param boolean $filter_event_filter_search Filter_event_filter_search.
 * @param boolean $dbmeta                     Dbmeta.
 *
 * @return array An array with all the events happened.
 */
function events_get_count_events_by_agent(
    $id_group,
    $period,
    $date,
    $filter_event_severity=false,
    $filter_event_type=false,
    $filter_event_status=false,
    $filter_event_filter_search=false,
    $dbmeta=false
) {
    global $config;

    // Date.
    if (!is_numeric($date)) {
        $date = time_w_fixed_tz($date);
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    // Group.
    $id_group = groups_safe_acl($config['id_user'], $id_group, 'AR');

    if (empty($id_group)) {
        // An empty array means the user doesn't have access.
        return false;
    }

    $datelimit = ($date - $period);

    $sql_where = '';
    $severity_all = 0;
    if (!empty($filter_event_severity)) {
        foreach ($filter_event_severity as $key => $value) {
            switch ($value) {
                case -1:
                    $severity_all = 1;
                break;

                case 34:
                    $filter_event_severity[$key] = '3, 4';
                break;

                case 20:
                    $filter_event_severity[$key] = '0, 1, 3, 4, 5, 6';
                break;

                case 21:
                    $filter_event_severity[$key] = '4, 2';
                break;

                default:
                    // Ignore.
                break;
            }
        }

        if (!$severity_all) {
            $sql_where .= ' AND criticity IN ('.implode(', ', $filter_event_severity).')';
        }
    }

    $status_all = 0;
    if (!empty($filter_event_status)) {
        foreach ($filter_event_status as $key => $value) {
            switch ($value) {
                case -1:
                    $status_all = 1;
                break;

                case 3:
                    $filter_event_status[$key] = ('0, 2');
                default:
                    // Ignore.
                break;
            }
        }

        if (!$status_all) {
            $sql_where .= ' AND estado IN ('.implode(', ', $filter_event_status).')';
        }
    }

    if (!empty($filter_event_type) && $filter_event_type[0] != 'all') {
        $sql_where .= ' AND (';
        $type = [];
        foreach ($filter_event_type as $event_type) {
            if ($event_type != '') {
                // If normal, warning, could be several (going_up_warning, going_down_warning... too complex.
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

        $sql_where .= implode(' OR ', $type).')';
    }

    if (!empty($filter_event_filter_search)) {
        $sql_where .= ' AND (evento LIKE "%'.io_safe_input($filter_event_filter_search).'%" OR id_evento LIKE "%'.io_safe_input($filter_event_filter_search).'%")';
    }

    $tagente = 'tagente';
    $tevento = 'tevento';

    $sql = sprintf(
        'SELECT id_agente,
		(SELECT t2.alias
			FROM %s t2
			WHERE t2.id_agente = t3.id_agente) AS agent_name,
		COUNT(*) AS count
		FROM %s t3
		WHERE utimestamp > %d AND utimestamp <= %d
			AND id_grupo IN (%s) 
		GROUP BY id_agente',
        $tagente,
        $tevento,
        $datelimit,
        $date,
        implode(',', $id_group),
        $sql_where
    );

    $rows = db_get_all_rows_sql($sql);

    if ($rows == false) {
        $rows = [];
    }

    $return = [];
    foreach ($rows as $row) {
        $agent_name = $row['agent_name'];
        if (empty($row['agent_name'])) {
            $agent_name = __('Pandora System');
        }

        $return[$agent_name] = $row['count'];
    }

    return $return;
}


/**
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param array   $filter                     Use target filter.
 * @param integer $period                     Period in seconds to get events.
 * @param integer $date                       Beginning date to get events.
 * @param boolean $filter_event_severity      Filter_event_severity.
 * @param boolean $filter_event_type          Filter_event_type.
 * @param boolean $filter_event_status        Filter_event_status.
 * @param boolean $filter_event_filter_search Filter_event_filter_search.
 * @param boolean $dbmeta                     Dbmeta.
 *
 * @return array An array with all the events happened.
 */
function events_get_count_events_validated_by_user(
    $filter,
    $period,
    $date,
    $filter_event_severity=false,
    $filter_event_type=false,
    $filter_event_status=false,
    $filter_event_filter_search=false,
    $dbmeta=false
) {
    global $config;
    // Group.
    $sql_filter = ' AND 1=1 ';
    if (isset($filter['id_group'])) {
        $id_group = groups_safe_acl($config['id_user'], $filter['id_group'], 'AR');

        if (empty($id_group)) {
            // An empty array means the user doesn't have access.
            return false;
        }

        $sql_filter .= sprintf(' AND id_grupo IN (%s) ', implode(',', $id_group));
    }

    if (!empty($filter['id_agent'])) {
        $sql_filter .= sprintf(' AND id_agente = %d ', $filter['id_agent']);
    }

    if (!empty($filter['id_agentmodule'])) {
        $sql_filter .= sprintf(' AND id_agentmodule = %d ', $filter['id_agentmodule']);
    }

    // Date.
    if (!is_numeric($date)) {
        $date = time_w_fixed_tz($date);
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $sql_where = '';
    $severity_all = 0;
    if (!empty($filter_event_severity)) {
        foreach ($filter_event_severity as $key => $value) {
            switch ($value) {
                case -1:
                    $severity_all = 1;
                break;

                case 34:
                    $filter_event_severity[$key] = '3, 4';
                break;

                case 20:
                    $filter_event_severity[$key] = '0, 1, 3, 4, 5, 6';
                break;

                case 21:
                    $filter_event_severity[$key] = '4, 2';
                break;

                default:
                    // Ignore.
                break;
            }
        }

        if (!$severity_all) {
            $sql_where .= ' AND criticity IN ('.implode(', ', $filter_event_severity).')';
        }
    }

    $status_all = 0;
    if (!empty($filter_event_status)) {
        foreach ($filter_event_status as $key => $value) {
            switch ($value) {
                case -1:
                    $status_all = 1;
                break;

                case 3:
                    $filter_event_status[$key] = ('0, 2');
                default:
                    // Ignore.
                break;
            }
        }

        if (!$status_all) {
            $sql_where .= ' AND estado IN ('.implode(', ', $filter_event_status).')';
        }
    }

    if (!empty($filter_event_type) && $filter_event_type[0] != 'all') {
        $sql_where .= ' AND (';
        $type = [];
        foreach ($filter_event_type as $event_type) {
            if ($event_type != '') {
                // If normal, warning, could be several (going_up_warning, going_down_warning... too complex.
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

        $sql_where .= implode(' OR ', $type).')';
    }

    if (!empty($filter_event_filter_search)) {
        $sql_where .= ' AND (evento LIKE "%'.io_safe_input($filter_event_filter_search).'%" OR id_evento LIKE "%'.io_safe_input($filter_event_filter_search).'%")';
    }

    $tevento = 'tevento';

    $sql = sprintf(
        'SELECT id_usuario,
		(SELECT t2.fullname
			FROM tusuario t2
			WHERE t2.id_user = t3.id_usuario) AS user_name,
		COUNT(*) AS count
		FROM %s t3
		WHERE utimestamp > %d AND utimestamp <= %d
			%s %s
		GROUP BY id_usuario',
        $tevento,
        $datelimit,
        $date,
        $sql_filter,
        $sql_where
    );
    $rows = db_get_all_rows_sql($sql);

    if ($rows == false) {
        $rows = [];
    }

    $return = [];
    foreach ($rows as $row) {
        $user_name = $row['user_name'];
        if (empty($row['user_name'])) {
            $user_name = __('Unknown');
        }

        $return[$user_name] = $row['count'];
    }

    return $return;
}


/**
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param mixed   $filter                     Target filter.
 * @param integer $period                     Period in seconds to get events.
 * @param integer $date                       Beginning date to get events.
 * @param boolean $filter_event_severity      Filter_event_severity.
 * @param boolean $filter_event_type          Filter_event_type.
 * @param boolean $filter_event_status        Filter_event_status.
 * @param boolean $filter_event_filter_search Filter_event_filter_search.
 * @param boolean $dbmeta                     Dbmeta.
 *
 * @return array An array with all the events happened.
 */
function events_get_count_events_by_criticity(
    $filter,
    $period,
    $date,
    $filter_event_severity=false,
    $filter_event_type=false,
    $filter_event_status=false,
    $filter_event_filter_search=false,
    $dbmeta=false
) {
    global $config;

    $sql_filter = ' AND 1=1 ';
    if (isset($filter['id_group'])) {
        $id_group = groups_safe_acl($config['id_user'], $filter['id_group'], 'AR');

        if (empty($id_group)) {
            // An empty array means the user doesn't have access.
            return false;
        }

        $sql_filter .= sprintf(' AND id_grupo IN (%s) ', implode(',', $id_group));
    }

    if (!empty($filter['id_agent'])) {
        $sql_filter .= sprintf(' AND id_agente = %d ', $filter['id_agent']);
    }

    if (!empty($filter['id_agentmodule'])) {
        $sql_filter .= sprintf(' AND id_agentmodule = %d ', $filter['id_agentmodule']);
    }

    if (!is_numeric($date)) {
        $date = time_w_fixed_tz($date);
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $sql_where = '';
    $severity_all = 0;
    if (!empty($filter_event_severity)) {
        foreach ($filter_event_severity as $key => $value) {
            switch ($value) {
                case -1:
                    $severity_all = 1;
                break;

                case 34:
                    $filter_event_severity[$key] = '3, 4';
                break;

                case 20:
                    $filter_event_severity[$key] = '0, 1, 3, 4, 5, 6';
                break;

                case 21:
                    $filter_event_severity[$key] = '4, 2';
                break;

                default:
                    // Ignore.
                break;
            }
        }

        if (!$severity_all) {
            $sql_where .= ' AND criticity IN ('.implode(', ', $filter_event_severity).')';
        }
    }

    $status_all = 0;
    if (!empty($filter_event_status)) {
        foreach ($filter_event_status as $key => $value) {
            switch ($value) {
                case -1:
                    $status_all = 1;
                break;

                case 3:
                    $filter_event_status[$key] = ('0, 2');
                break;

                default:
                    // Ignored.
                break;
            }
        }

        if (!$status_all) {
            $sql_where .= ' AND estado IN ('.implode(', ', $filter_event_status).')';
        }
    }

    if (!empty($filter_event_type) && $filter_event_type[0] != 'all') {
        $sql_where .= ' AND (';
        $type = [];
        foreach ($filter_event_type as $event_type) {
            if ($event_type != '') {
                // If normal, warning, could be several (going_up_warning, going_down_warning... too complex.
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

        $sql_where .= implode(' OR ', $type).')';
    }

    if (!empty($filter_event_filter_search)) {
        $sql_where .= ' AND (evento LIKE "%'.io_safe_input($filter_event_filter_search).'%" OR id_evento LIKE "%'.io_safe_input($filter_event_filter_search).'%")';
    }

    $tevento = 'tevento';

    $sql = sprintf(
        'SELECT criticity,
		COUNT(*) AS count
		FROM %s
		WHERE utimestamp > %d AND utimestamp <= %d
			%s %s
		GROUP BY criticity',
        $tevento,
        $datelimit,
        $date,
        $sql_filter,
        $sql_where
    );

    $rows = db_get_all_rows_sql($sql);

    if ($rows == false) {
        $rows = [];
    }

    $return = [];
    foreach ($rows as $row) {
        $return[get_priority_name($row['criticity'])] = $row['count'];
    }

    return $return;
}


/**
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 *
 * @param mixed   $filter                     Target filter.
 * @param integer $period                     Period in seconds to get events.
 * @param integer $date                       Beginning date to get events.
 * @param boolean $filter_event_severity      Filter_event_severity.
 * @param boolean $filter_event_type          Filter_event_type.
 * @param boolean $filter_event_status        Filter_event_status.
 * @param boolean $filter_event_filter_search Filter_event_filter_search.
 * @param boolean $dbmeta                     Dbmeta.
 *
 * @return array An array with all the events happened.
 */
function events_get_count_events_validated(
    $filter,
    $period=null,
    $date=null,
    $filter_event_severity=false,
    $filter_event_type=false,
    $filter_event_status=false,
    $filter_event_filter_search=false,
    $dbmeta=false
) {
    global $config;

    // Group.
    $sql_filter = ' 1=1 ';
    if (isset($filter['id_group'])) {
        $id_group = groups_safe_acl(
            $config['id_user'],
            $filter['id_group'],
            'AR'
        );

        if (empty($id_group)) {
            // An empty array means the user doesn't have access.
            return false;
        }

        $sql_filter .= sprintf(
            ' AND id_grupo IN (%s) ',
            implode(',', $id_group)
        );
    }

    // Agent.
    if (!empty($filter['id_agent'])) {
        $sql_filter .= sprintf(
            ' AND id_agente = %d ',
            $filter['id_agent']
        );
    }

    // Module.
    if (!empty($filter['id_agentmodule'])) {
        $sql_filter .= sprintf(
            ' AND id_agentmodule = %d ',
            $filter['id_agentmodule']
        );
    }

    // Date.
    if (!is_numeric($date)) {
        $date = time_w_fixed_tz($date);
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    $date_filter = '';
    if (!empty($date) && !empty($period)) {
        $datelimit = ($date - $period);

        $date_filter .= sprintf(
            ' AND utimestamp > %d AND utimestamp <= %d ',
            $datelimit,
            $date
        );
    } else if (!empty($period)) {
        $date = time();
        $datelimit = ($date - $period);

        $date_filter .= sprintf(
            ' AND utimestamp > %d AND utimestamp <= %d ',
            $datelimit,
            $date
        );
    } else if (!empty($date)) {
        $date_filter .= sprintf(' AND utimestamp <= %d ', $date);
    }

    $sql_where = '';
    $severity_all = 0;
    if (!empty($filter_event_severity)) {
        foreach ($filter_event_severity as $key => $value) {
            switch ($value) {
                case -1:
                    $severity_all = 1;
                break;

                case 34:
                    $filter_event_severity[$key] = '3, 4';
                break;

                case 20:
                    $filter_event_severity[$key] = '0, 1, 3, 4, 5, 6';
                break;

                case 21:
                    $filter_event_severity[$key] = '4, 2';
                break;

                default:
                    // Ingore.
                break;
            }
        }

        if (!$severity_all) {
            $sql_where .= ' AND criticity IN ('.implode(', ', $filter_event_severity).')';
        }
    }

    $status_all = 0;
    if (!empty($filter_event_status)) {
        foreach ($filter_event_status as $key => $value) {
            switch ($value) {
                case -1:
                    $status_all = 1;
                break;

                case 3:
                    $filter_event_status[$key] = ('0, 2');
                break;

                default:
                    // Ignore.
                break;
            }
        }

        if (!$status_all) {
            $sql_where .= ' AND estado IN ('.implode(', ', $filter_event_status).')';
        }
    }

    if (!empty($filter_event_type) && $filter_event_type[0] != 'all') {
        $sql_where .= ' AND (';
        $type = [];
        foreach ($filter_event_type as $event_type) {
            if ($event_type != '') {
                // If normal, warning, could be several (going_up_warning, going_down_warning... too complex.
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

        $sql_where .= implode(' OR ', $type).')';
    }

    if (!empty($filter_event_filter_search)) {
        $sql_where .= ' AND (evento LIKE "%'.io_safe_input($filter_event_filter_search).'%" OR id_evento LIKE "%'.io_safe_input($filter_event_filter_search).'%")';
    }

    $tevento = 'tevento';

    $sql = sprintf('SELECT estado, COUNT(*) AS count FROM %s WHERE %s %s GROUP BY estado', $tevento, $sql_filter, $sql_where);

    $rows = db_get_all_rows_sql($sql);

    if ($rows == false) {
        $rows = [];
    }

    $return = array_reduce(
        $rows,
        function ($carry, $item) {
            $status = (int) $item['estado'];
            $count = (int) $item['count'];

            if ($status === 1) {
                $carry[__('Validated')] += $count;
            } else if ($status === 0) {
                $carry[__('Not validated')] += $count;
            }

            return $carry;
        },
        [
            __('Validated')     => 0,
            __('Not validated') => 0,
        ]
    );

    return $return;
}


/**
 * Check event tags.
 *
 * @param array $event_data Event.
 * @param array $acltags    Acl tags.
 *
 * @return boolean True or false.
 */
function events_checks_event_tags($event_data, $acltags)
{
    global $config;

    if (empty($acltags[$event_data['id_grupo']])) {
            return true;
    } else {
        $tags_arr_acl = explode(',', $acltags[$event_data['id_grupo']]);
        $tags_arr_event = explode(',', $event_data['tags']);

        foreach ($tags_arr_acl as $tag) {
            $tag_name = tags_get_name($tag);
            if (in_array($tag_name, $tags_arr_event)) {
                return true;
            } else {
                $has_tag = false;
            }
        }

        if (!$has_tag) {
            return false;
        }
    }

    return false;
}


/**
 * Retrieves events grouped by agent.
 *
 * @param string  $sql_post   Sql_post.
 * @param integer $offset     Offset.
 * @param integer $pagination Pagination.
 * @param boolean $meta       Meta.
 * @param boolean $history    History.
 * @param boolean $total      Total.
 *
 * @return array Data.
 */
function events_get_events_grouped_by_agent(
    $sql_post,
    $offset=0,
    $pagination=1,
    $meta=false,
    $history=false,
    $total=false
) {
    global $config;

    $table = events_get_events_table($meta, $history);

    if ($meta) {
        $fields_extra = ', agent_name, server_id';
        $groupby_extra = ', server_id';
    } else {
        $groupby_extra = '';
        $fields_extra = '';
    }

    $event_lj = events_get_secondary_groups_left_join($table);
    if ($total) {
        $sql = 'SELECT COUNT(*) FROM (select id_agente from '.$table.' '.$event_lj.' WHERE 1=1 
		    '.$sql_post.' GROUP BY id_agente, event_type'.$groupby_extra.' ORDER BY id_agente ) AS t';
    } else {
        $sql = 'select id_agente, count(*) as total'.$fields_extra.' from '.$table.' te '.$event_lj.'
			WHERE id_agente > 0 '.$sql_post.' GROUP BY id_agente'.$groupby_extra.' ORDER BY id_agente LIMIT '.$offset.','.$pagination;
    }

    $result = [];
    // Extract the events by filter (or not) from db.
    $events = db_get_all_rows_sql($sql);
    $result = [];

    if ($events) {
        foreach ($events as $event) {
            if ($meta) {
                $sql = 'SELECT event_type FROM '.$table.' te '.$event_lj."
								WHERE agent_name = '".$event['agent_name']."' ".$sql_post.' ORDER BY utimestamp DESC ';
                $resultado = db_get_row_sql($sql);

                $id_agente = $event['agent_name'];
                $result[] = [
                    'total'      => $event['total'],
                    'id_server'  => $event['server_id'],
                    'id_agent'   => $id_agente,
                    'event_type' => $resultado['event_type'],
                ];
            } else {
                $sql = 'SELECT event_type FROM '.$table.' te '.$event_lj.'
					WHERE id_agente = '.$event['id_agente'].' '.$sql_post.' ORDER BY utimestamp DESC ';
                $resultado = db_get_row_sql($sql);

                $id_agente = $event['id_agente'];
                $result[] = [
                    'total'      => $event['total'],
                    'id_agent'   => $id_agente,
                    'event_type' => $resultado['event_type'],
                ];
            }
        }
    }

    return $result;
}


/**
 * Return SQL query to group events by agents.
 *
 * @param mixed   $id_agent          Id_agent.
 * @param integer $server_id         Server_id.
 * @param string  $event_type        Event_type.
 * @param integer $severity          Severity.
 * @param integer $status            Status.
 * @param string  $search            Search.
 * @param integer $id_agent_module   Id_agent_module.
 * @param integer $event_view_hr     Event_view_hr.
 * @param boolean $id_user_ack       Id_user_ack.
 * @param array   $tag_with          Tag_with.
 * @param array   $tag_without       Tag_without.
 * @param boolean $filter_only_alert Filter_only_alert.
 * @param string  $date_from         Date_from.
 * @param string  $date_to           Date_to.
 * @param boolean $id_user           Id_user.
 * @param boolean $server_id_search  Server_id_search.
 *
 * @return string SQL.
 */
function events_sql_events_grouped_agents(
    $id_agent,
    $server_id=-1,
    $event_type='',
    $severity=-1,
    $status=3,
    $search='',
    $id_agent_module=0,
    $event_view_hr=8,
    $id_user_ack=false,
    $tag_with=[],
    $tag_without=[],
    $filter_only_alert=false,
    $date_from='',
    $date_to='',
    $id_user=false,
    $server_id_search=false
) {
    global $config;

    $sql_post = ' 1 = 1 ';

    $meta = false;
    if (is_metaconsole()) {
        $meta = true;
    }

    switch ($status) {
        case 0:
        case 1:
        case 2:
            $sql_post .= ' AND estado = '.$status;
        break;

        case 3:
            $sql_post .= ' AND (estado = 0 OR estado = 2)';
        break;

        default:
            // Ignore.
        break;
    }

    if ($search != '') {
        $sql_post .= " AND (evento LIKE '%".io_safe_input($search)."%' OR id_evento LIKE '%".$search."%')";
    }

    if ($event_type != '') {
        // If normal, warning, could be several (going_up_warning, going_down_warning... too complex
        // Shown to user only "warning, critical and normal".
        if ($event_type == 'warning' || $event_type == 'critical' || $event_type == 'normal') {
            $sql_post .= " AND event_type LIKE '%".$event_type."%' ";
        } else if ($event_type == 'not_normal') {
            $sql_post .= " AND (event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%') ";
        } else if ($event_type != 'all') {
            $sql_post .= " AND event_type = '".$event_type."'";
        }
    }

    if ($severity != -1) {
        switch ($severity) {
            case EVENT_CRIT_WARNING_OR_CRITICAL:
                $sql_post .= '
					AND (criticity = '.EVENT_CRIT_WARNING.' OR 
						criticity = '.EVENT_CRIT_CRITICAL.')';
            break;

            case EVENT_CRIT_OR_NORMAL:
                $sql_post .= '
					AND (criticity = '.EVENT_CRIT_NORMAL.' OR 
						criticity = '.EVENT_CRIT_CRITICAL.')';
            break;

            case EVENT_CRIT_NOT_NORMAL:
                $sql_post .= ' AND criticity != '.EVENT_CRIT_NORMAL;
            break;

            default:
                $sql_post .= ' AND criticity = '.$severity;
            break;
        }
    }

    // In metaconsole mode the agent search is performed by name.
    if ($meta) {
        if ($id_agent != __('All')) {
            $sql_post .= " AND agent_name LIKE '%".$id_agent."%'";
        }
    } else {
        switch ($id_agent) {
            case 0:
                // Ignore.
                $__invalid_value = 1;
            break;

            case -1:
                // Agent doesnt exist. No results will returned.
                $sql_post .= ' AND 1 = 0';
            break;

            default:
                $sql_post .= ' AND id_agente = '.$id_agent;
            break;
        }
    }

    // There is another filter for if ($meta).
    if (!$meta) {
        if (!empty($text_module)) {
            $sql_post .= " AND id_agentmodule IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE nombre = '".$text_module."'
				)";
        }
    }

    if ($id_user_ack != '0') {
        $sql_post .= " AND id_usuario = '".$id_user_ack."'";
    }

    if (!isset($date_from)) {
        $date_from = '';
    }

    if (!isset($date_to)) {
        $date_to = '';
    }

    if (($date_from == '') && ($date_to == '')) {
        if ($event_view_hr > 0) {
            $unixtime = (get_system_time() - ($event_view_hr * SECONDS_1HOUR));
            $sql_post .= ' AND (utimestamp > '.$unixtime.')';
        }
    } else {
        if ($date_from != '') {
            $udate_from = strtotime($date_from.' 00:00:00');
            $sql_post .= ' AND (utimestamp >= '.$udate_from.')';
        }

        if ($date_to != '') {
            $udate_to = strtotime($date_to.' 23:59:59');
            $sql_post .= ' AND (utimestamp <= '.$udate_to.')';
        }
    }

    // Search by tag.
    if (!empty($tag_with) && (io_safe_output($tag_with) != '[]') && (io_safe_output($tag_with) != '["0"]')) {
        $sql_post .= ' AND ( ';
        $first = true;
        foreach ($tag_with as $id_tag) {
            if ($first) {
                $first = false;
            } else {
                $sql_post .= ' OR ';
            }

            $sql_post .= "tags = '".tags_get_name($id_tag)."'";
        }

        $sql_post .= ' ) ';
    }

    if (!empty($tag_without) && (io_safe_output($tag_without) != '[]') && (io_safe_output($tag_with) != '["0"]')) {
        $sql_post .= ' AND ( ';
        $first = true;
        foreach ($tag_without as $id_tag) {
            if ($first) {
                $first = false;
            } else {
                $sql_post .= ' AND ';
            }

            $sql_post .= "tags <> '".tags_get_name($id_tag)."'";
        }

        $sql_post .= ' ) ';
    }

    // Filter/Only alerts.
    if (isset($filter_only_alert)) {
        if ($filter_only_alert == 0) {
            $sql_post .= " AND event_type NOT LIKE '%alert%'";
        } else if ($filter_only_alert == 1) {
            $sql_post .= " AND event_type LIKE '%alert%'";
        }
    }

    // Tags ACLS.
    if ($id_group > 0 && in_array($id_group, array_keys($groups))) {
        $group_array = (array) $id_group;
    } else {
        $group_array = array_keys($groups);
    }

    $tags_acls_condition = tags_get_acl_tags(
        $id_user,
        $group_array,
        'ER',
        'event_condition',
        'AND',
        '',
        $meta,
        [],
        true
    );
    // FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)".
    if (($tags_acls_condition != ERR_WRONG_PARAMETERS) && ($tags_acls_condition != ERR_ACL) && ($tags_acls_condition != -110000)) {
        $sql_post .= $tags_acls_condition;
    }

    // Metaconsole filters.
    if ($meta) {
        if ($server_id_search) {
            $sql_post .= ' AND server_id = '.$server_id_search;
        } else {
            $enabled_nodes = db_get_all_rows_sql(
                '
				SELECT id
				FROM tmetaconsole_setup
				WHERE disabled = 0'
            );

            if (empty($enabled_nodes)) {
                $sql_post .= ' AND 1 = 0';
            } else {
                if ($strict_user == 1) {
                    $enabled_nodes_id = [];
                } else {
                    $enabled_nodes_id = [0];
                }

                foreach ($enabled_nodes as $en) {
                    $enabled_nodes_id[] = $en['id'];
                }

                $sql_post .= ' AND server_id IN ('.implode(',', $enabled_nodes_id).')';
            }
        }
    }

    return $sql_post;
}


/**
 * Retrieve list of events grouped by agents.
 *
 * @param string $sql SQL.
 *
 * @return string HTML.
 */
function events_list_events_grouped_agents($sql)
{
    global $config;

    $table = events_get_events_table(is_metaconsole(), $history);

    $sql = sprintf(
        'SELECT * FROM %s 
	    LEFT JOIN tagent_secondary_group 
	       ON tagent_secondary_group.id_agent = id_agente
        WHERE %s',
        $table,
        $sql
    );

    $result = db_get_all_rows_sql($sql);
    $group_rep = 0;
    $meta = is_metaconsole();

    // Fields that the user has selected to show.
    if ($meta) {
        $show_fields = events_meta_get_custom_fields_user();
    } else {
        $show_fields = explode(',', $config['event_fields']);
    }

    // Headers.
    $i = 0;
    $table = new stdClass();
    if (!isset($table->width)) {
        $table->width = '100%';
    }

    $table->id = 'eventtable';
    $table->cellpadding = 4;
    $table->cellspacing = 4;
    if (!isset($table->class)) {
        $table->class = 'databox data';
    }

    $table->head = [];
    $table->data = [];

    $table->head[$i] = __('ID');
    $table->align[$i] = 'left';
    $i++;
    if (in_array('server_name', $show_fields)) {
        $table->head[$i] = __('Server');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('estado', $show_fields)) {
        $table->head[$i] = __('Status');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('id_evento', $show_fields)) {
        $table->head[$i] = __('Event ID');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('evento', $show_fields)) {
        $table->head[$i] = __('Event Name');
        $table->align[$i] = 'left';
        $table->style[$i] = 'min-width: 200px; max-width: 350px; word-break: break-all;';
        $i++;
    }

    if (in_array('id_agente', $show_fields)) {
        $table->head[$i] = __('Agent name');
        $table->align[$i] = 'left';
        $table->style[$i] = 'max-width: 350px; word-break: break-all;';
        $i++;
    }

    if (in_array('timestamp', $show_fields)) {
        $table->head[$i] = __('Timestamp');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('id_usuario', $show_fields)) {
        $table->head[$i] = __('User');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('owner_user', $show_fields)) {
        $table->head[$i] = __('Owner');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('id_grupo', $show_fields)) {
        $table->head[$i] = __('Group');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('event_type', $show_fields)) {
        $table->head[$i] = __('Event type');
        $table->align[$i] = 'left';
        $table->style[$i] = 'min-width: 85px;';
        $i++;
    }

    if (in_array('id_agentmodule', $show_fields)) {
        $table->head[$i] = __('Agent Module');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('id_alert_am', $show_fields)) {
        $table->head[$i] = __('Alert');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('criticity', $show_fields)) {
        $table->head[$i] = __('Severity');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('user_comment', $show_fields)) {
        $table->head[$i] = __('Comment');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('tags', $show_fields)) {
        $table->head[$i] = __('Tags');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('source', $show_fields)) {
        $table->head[$i] = __('Source');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('id_extra', $show_fields)) {
        $table->head[$i] = __('Extra ID');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('ack_utimestamp', $show_fields)) {
        $table->head[$i] = __('ACK Timestamp');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('instructions', $show_fields)) {
        $table->head[$i] = __('Instructions');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('data', $show_fields)) {
        $table->head[$i] = __('Data');
        $table->align[$i] = 'left';
        $i++;
    }

    if (in_array('module_status', $show_fields)) {
        $table->head[$i] = __('Module status');
        $table->align[$i] = 'left';
        $i++;
    }

    if ($i != 0 && $allow_action) {
        $table->head[$i] = __('Action');
        $table->align[$i] = 'left';
        $table->size[$i] = '90px';
        $i++;
        if (check_acl($config['id_user'], 0, 'EW') == 1 && !$readonly) {
            $table->head[$i] = html_print_checkbox('all_validate_box', '1', false, true);
            $table->align[$i] = 'left';
        }
    }

    if ($meta) {
        // Get info of the all servers to use it on hash auth.
        $servers_url_hash = metaconsole_get_servers_url_hash();
        $servers = metaconsole_get_servers();
    }

    $show_delete_button = false;
    $show_validate_button = false;

    $idx = 0;
    // Arrange data. We already did ACL's in the query.
    foreach ($result as $event) {
        $data = [];

        if ($meta) {
            $event['server_url_hash'] = $servers_url_hash[$event['server_id']];
            $event['server_url'] = $servers[$event['server_id']]['server_url'];
            $event['server_name'] = $servers[$event['server_id']]['server_name'];
        }

        // Clean url from events and store in array.
        $event['clean_tags'] = events_clean_tags($event['tags']);

        // First pass along the class of this row.
        $myclass = get_priority_class($event['criticity']);

        // Print status.
        $estado = $event['estado'];

        // Colored box.
        switch ($estado) {
            case EVENT_NEW:
                $img_st = 'images/star.png';
                $title_st = __('New event');
            break;

            case EVENT_VALIDATE:
                $img_st = 'images/tick.png';
                $title_st = __('Event validated');
            break;

            case EVENT_PROCESS:
                $img_st = 'images/hourglass.png';
                $title_st = __('Event in process');
            break;

            default:
                // Ignore.
            break;
        }

        $i = 0;

        $data[$i] = '#'.$event['id_evento'];
        $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3; color: #111 !important;';

        // Pass grouped values in hidden fields to use it from modal window.
        if ($group_rep) {
            $similar_ids = $event['similar_ids'];
            $timestamp_first = $event['timestamp_rep_min'];
            $timestamp_last = $event['timestamp_rep'];
        } else {
            $similar_ids = $event['id_evento'];
            $timestamp_first = $event['utimestamp'];
            $timestamp_last = $event['utimestamp'];
        }

        // Store group data to show in extended view.
        $data[$i] .= html_print_input_hidden('similar_ids_'.$event['id_evento'], $similar_ids, true);
        $data[$i] .= html_print_input_hidden('timestamp_first_'.$event['id_evento'], $timestamp_first, true);
        $data[$i] .= html_print_input_hidden('timestamp_last_'.$event['id_evento'], $timestamp_last, true);
        $data[$i] .= html_print_input_hidden('childrens_ids', json_encode($childrens_ids), true);

        // Store server id if is metaconsole. 0 otherwise.
        if ($meta) {
            $server_id = $event['server_id'];

            // If meta activated, propagate the id of the event on node (source id).
            $data[$i] .= html_print_input_hidden('source_id_'.$event['id_evento'], $event['id_source_event'], true);
            $table->cellclass[count($table->data)][$i] = $myclass;
        } else {
            $server_id = 0;
        }

        $data[$i] .= html_print_input_hidden('server_id_'.$event['id_evento'], $server_id, true);

        if (empty($event['event_rep'])) {
            $event['event_rep'] = 0;
        }

        $data[$i] .= html_print_input_hidden('event_rep_'.$event['id_evento'], $event['event_rep'], true);
        // Store concat comments to show in extended view.
        $data[$i] .= html_print_input_hidden('user_comment_'.$event['id_evento'], base64_encode($event['user_comment']), true);

        $i++;

        if (in_array('server_name', $show_fields)) {
            if ($meta) {
                if (can_user_access_node()) {
                    $data[$i] = "<a href='".$event['server_url'].'/index.php?sec=estado&sec2=operation/agentes/group_view'.$event['server_url_hash']."'>".$event['server_name'].'</a>';
                } else {
                    $data[$i] = $event['server_name'];
                }
            } else {
                $data[$i] = db_get_value('name', 'tserver');
            }

            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('estado', $show_fields)) {
            $data[$i] = html_print_image(
                $img_st,
                true,
                [
                    'class' => 'image_status',
                    'title' => $title_st,
                    'id'    => 'status_img_'.$event['id_evento'],
                ]
            );
            $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';
            $i++;
        }

        if (in_array('id_evento', $show_fields)) {
            $data[$i] = $event['id_evento'];
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        switch ($event['criticity']) {
            default:
            case 0:
                $img_sev = 'images/status_sets/default/severity_maintenance.png';
            break;
            case 1:
                $img_sev = 'images/status_sets/default/severity_informational.png';
            break;

            case 2:
                $img_sev = 'images/status_sets/default/severity_normal.png';
            break;

            case 3:
                $img_sev = 'images/status_sets/default/severity_warning.png';
            break;

            case 4:
                $img_sev = 'images/status_sets/default/severity_critical.png';
            break;

            case 5:
                $img_sev = 'images/status_sets/default/severity_minor.png';
            break;

            case 6:
                $img_sev = 'images/status_sets/default/severity_major.png';
            break;
        }

        if (in_array('evento', $show_fields)) {
            // Event description.
            $data[$i] = '<span title="'.$event['evento'].'" class="f9">';
            if ($allow_action) {
                $data[$i] .= '<a href="javascript:" onclick="show_event_dialog('.$event['id_evento'].', '.$group_rep.');">';
            }

            $data[$i] .= '<span class="'.$myclass.'" style="font-size: 7.5pt;">'.ui_print_truncate_text(io_safe_output($event['evento']), 160).'</span>';
            if ($allow_action) {
                $data[$i] .= '</a>';
            }

            $data[$i] .= '</span>';
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('id_agente', $show_fields)) {
            $data[$i] = '<span class="'.$myclass.'">';

            if ($event['id_agente'] > 0) {
                // Agent name.
                if ($meta) {
                    $agent_link = '<a href="'.$event['server_url'].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event['id_agente'].$event['server_url_hash'].'">';
                    if (can_user_access_node()) {
                        $data[$i] = '<b>'.$agent_link.$event['agent_name'].'</a></b>';
                    } else {
                        $data[$i] = $event['agent_name'];
                    }
                } else {
                    $data[$i] .= ui_print_agent_name($event['id_agente'], true);
                }
            } else {
                $data[$i] .= '';
            }

            $data[$i] .= '</span>';
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('timestamp', $show_fields)) {
            // Time.
            $data[$i] = '<span class="'.$myclass.'">';
            if ($group_rep == 1) {
                $data[$i] .= ui_print_timestamp($event['timestamp_rep'], true);
            } else {
                $data[$i] .= ui_print_timestamp($event['timestamp'], true);
            }

            $data[$i] .= '</span>';
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('id_usuario', $show_fields)) {
            $user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
            if (empty($user_name)) {
                $user_name = $event['id_usuario'];
            }

            $data[$i] = $user_name;
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('owner_user', $show_fields)) {
            $owner_name = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
            if (empty($owner_name)) {
                $owner_name = $event['owner_user'];
            }

            $data[$i] = $owner_name;
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('id_grupo', $show_fields)) {
            if ($meta) {
                $data[$i] = $event['group_name'];
            } else {
                $id_group = $event['id_grupo'];
                $group_name = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);
                if ($id_group == 0) {
                    $group_name = __('All');
                }

                $data[$i] = $group_name;
            }

            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('event_type', $show_fields)) {
            $data[$i] = events_print_type_description($event['event_type'], true);
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('id_agentmodule', $show_fields)) {
            if ($meta) {
                $module_link = '<a href="'.$event['server_url'].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event['id_agente'].$event['server_url_hash'].'">';
                if (can_user_access_node()) {
                    $data[$i] = '<b>'.$module_link.$event['module_name'].'</a></b>';
                } else {
                    $data[$i] = $event['module_name'];
                }
            } else {
                $module_name = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $event['id_agentmodule']);
                $data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event['id_agente'].'&amp;status_text_monitor='.io_safe_output($module_name).'#monitors">'.$module_name.'</a>';
            }

            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('id_alert_am', $show_fields)) {
            if ($meta) {
                $data[$i] = $event['alert_template_name'];
            } else {
                if ($event['id_alert_am'] != 0) {
                    $sql = 'SELECT name
						FROM talert_templates
						WHERE id IN (SELECT id_alert_template
							FROM talert_template_modules
							WHERE id = '.$event['id_alert_am'].');';

                    $templateName = db_get_sql($sql);
                    $data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event['id_agente'].'&amp;tab=alert">'.$templateName.'</a>';
                } else {
                    $data[$i] = '';
                }
            }

            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('criticity', $show_fields)) {
            $data[$i] = get_priority_name($event['criticity']);
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('user_comment', $show_fields)) {
            $safe_event_user_comment = strip_tags(io_safe_output($event['user_comment']));
            $line_breaks = [
                "\r\n",
                "\n",
                "\r",
            ];
            $safe_event_user_comment = str_replace($line_breaks, '<br>', $safe_event_user_comment);
            $event_user_comments = json_decode($safe_event_user_comment, true);
            $event_user_comment_str = '';

            if (!empty($event_user_comments)) {
                $last_key = key(array_slice($event_user_comments, -1, 1, true));
                $date_format = $config['date_format'];

                foreach ($event_user_comments as $key => $event_user_comment) {
                    $event_user_comment_str .= sprintf(
                        '%s: %s<br>%s: %s<br>%s: %s<br>',
                        __('Date'),
                        date($date_format, $event_user_comment['utimestamp']),
                        __('User'),
                        $event_user_comment['id_user'],
                        __('Comment'),
                        $event_user_comment['comment']
                    );
                    if ($key != $last_key) {
                        $event_user_comment_str .= '<br>';
                    }
                }
            }

            $comments_help_tip = '';
            if (!empty($event_user_comment_str)) {
                $comments_help_tip = ui_print_help_tip($event_user_comment_str, true);
            }

            $data[$i] = '<span id="comment_header_'.$event['id_evento'].'">'.$comments_help_tip.'</span>';
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('tags', $show_fields)) {
            $data[$i] = tags_get_tags_formatted($event['tags']);
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('source', $show_fields)) {
            $data[$i] = $event['source'];
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('id_extra', $show_fields)) {
            $data[$i] = $event['id_extra'];
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('ack_utimestamp', $show_fields)) {
            if ($event['ack_utimestamp'] == 0) {
                $data[$i] = '';
            } else {
                $data[$i] = date($config['date_format'], $event['ack_utimestamp']);
            }

            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('instructions', $show_fields)) {
            switch ($event['event_type']) {
                case 'going_unknown':
                    if (!empty($event['unknown_instructions'])) {
                        $data[$i] = html_print_image('images/page_white_text.png', true, ['title' => str_replace("\n", '<br>', io_safe_output($event['unknown_instructions']))]);
                    }
                break;

                case 'going_up_critical':
                case 'going_down_critical':
                    if (!empty($event['critical_instructions'])) {
                        $data[$i] = html_print_image('images/page_white_text.png', true, ['title' => str_replace("\n", '<br>', io_safe_output($event['critical_instructions']))]);
                    }
                break;

                case 'going_down_warning':
                    if (!empty($event['warning_instructions'])) {
                        $data[$i] = html_print_image('images/page_white_text.png', true, ['title' => str_replace("\n", '<br>', io_safe_output($event['warning_instructions']))]);
                    }
                break;

                case 'system':
                    if (!empty($event['critical_instructions'])) {
                        $data[$i] = html_print_image('images/page_white_text.png', true, ['title' => str_replace("\n", '<br>', io_safe_output($event['critical_instructions']))]);
                    } else if (!empty($event['warning_instructions'])) {
                        $data[$i] = html_print_image('images/page_white_text.png', true, ['title' => str_replace("\n", '<br>', io_safe_output($event['warning_instructions']))]);
                    } else if (!empty($event['unknown_instructions'])) {
                        $data[$i] = html_print_image('images/page_white_text.png', true, ['title' => str_replace("\n", '<br>', io_safe_output($event['unknown_instructions']))]);
                    }
                break;

                default:
                    // Ignore.
                break;
            }

            if (!isset($data[$i])) {
                $data[$i] = '';
            }

            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if (in_array('data', $show_fields)) {
                $data[$i] = $event['data'];
            if (($data[$i] % 1) == 0) {
                $data[$i] = number_format($data[$i], 0);
            } else {
                $data[$i] = number_format($data[$i], 2);
            }

                $table->cellclass[count($table->data)][$i] = $myclass;
                    $i++;
        }

        if (in_array('module_status', $show_fields)) {
            $data[$i] = modules_get_modules_status($event['module_status']);
            $table->cellclass[count($table->data)][$i] = $myclass;
            $i++;
        }

        if ($i != 0 && $allow_action) {
            // Actions.
            $data[$i] = '';

            if (!$readonly) {
                // Validate event.
                if (($event['estado'] != 1) && (tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EW', $event['clean_tags'], $childrens_ids))) {
                    $show_validate_button = true;
                    $data[$i] .= '<a href="javascript:validate_event_advanced('.$event['id_evento'].', 1)" id="validate-'.$event['id_evento'].'">';
                    $data[$i] .= html_print_image(
                        'images/ok.png',
                        true,
                        ['title' => __('Validate event')]
                    );
                    $data[$i] .= '</a>';
                }

                // Delete event.
                if ((tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EM', $event['clean_tags'], $childrens_ids) == 1)) {
                    if ($event['estado'] != 2) {
                        $show_delete_button = true;
                        $data[$i] .= '<a class="delete_event" href="javascript:" id="delete-'.$event['id_evento'].'">';
                        $data[$i] .= html_print_image(
                            'images/cross.png',
                            true,
                            [
                                'title' => __('Delete event'),
                                'id'    => 'delete_cross_'.$event['id_evento'],
                            ]
                        );
                        $data[$i] .= '</a>';
                    } else {
                        $data[$i] .= html_print_image(
                            'images/cross.disabled.png',
                            true,
                            ['title' => __('Is not allowed delete events in process')]
                        ).'&nbsp;';
                    }
                }
            }

            $data[$i] .= '<a href="javascript:" onclick="show_event_dialog('.$event['id_evento'].', '.$group_rep.');">';
            $data[$i] .= html_print_input_hidden('event_title_'.$event['id_evento'], '#'.$event['id_evento'].' - '.$event['evento'], true);
            $data[$i] .= html_print_image(
                'images/eye.png',
                true,
                ['title' => __('Show more')]
            );
            $data[$i] .= '</a>';

            $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';

            $i++;

            if (!$readonly) {
                if (tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EM', $event['clean_tags'], $childrens_ids) == 1) {
                    // Checkbox.
                    // Class 'candeleted' must be the fist class to be parsed from javascript. Dont change.
                    $data[$i] = html_print_checkbox_extended('validate_ids[]', $event['id_evento'], false, false, false, 'class="candeleted chk_val"', true);
                } else if (tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EW', $event['clean_tags'], $childrens_ids) == 1) {
                    // Checkbox.
                    $data[$i] = html_print_checkbox_extended('validate_ids[]', $event['id_evento'], false, false, false, 'class="chk_val"', true);
                } else if (isset($table->header[$i]) || true) {
                    $data[$i] = '';
                }
            }

            $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';
        }

        array_push($table->data, $data);

        $idx++;
    }

    return html_print_table($table, true);
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
function events_get_sql_order($sort_field='timestamp', $sort='DESC', $group_rep=0, $only_fields=false)
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
            $sort_field_translated = ($group_rep == 0) ? 'timestamp' : 'timestamp_rep';
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
            $sort_field_translated = 'user_comment';
        break;

        case 'extra_id':
            $sort_field_translated = 'id_extra';
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

    $meta = false;
    $event = db_get_row('tevento', 'id_evento', $event_id);

    // Replace each macro.
    if (strpos($value, '_agent_address_') !== false) {
        if ($meta) {
            $agente_table_name = 'tmetaconsole_agent';
            $filter = [
                'id_tagente'            => $event['id_agente'],
                'id_tmetaconsole_setup' => $server_id,
            ];
        } else {
            $agente_table_name = 'tagente';
            $filter = ['id_agente' => $event['id_agente']];
        }

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
            if ($meta) {
                $server = metaconsole_get_connection_by_id($server_id);
                metaconsole_connect($server);
            }

            $module = db_get_row('tagente_modulo', 'id_agente_modulo', $event['id_agentmodule']);
            if (empty($module['ip_target'])) {
                $module['ip_target'] = __('N/A');
            }

            $value = str_replace('_module_address_', $module['ip_target'], $value);
            if (empty($module['nombre'])) {
                $module['nombre'] = __('N/A');
            }

            if ($meta) {
                metaconsole_restore_db();
            }
        } else {
            $value = str_replace('_module_address_', __('N/A'), $value);
        }
    }

    if (strpos($value, '_module_name_') !== false) {
        if ($event['id_agentmodule'] != 0) {
            if ($meta) {
                $server = metaconsole_get_connection_by_id($server_id);
                metaconsole_connect($server);
            }

            $module = db_get_row('tagente_modulo', 'id_agente_modulo', $event['id_agentmodule']);
            if (empty($module['ip_target'])) {
                $module['ip_target'] = __('N/A');
            }

            $value = str_replace(
                '_module_name_',
                io_safe_output($module['nombre']),
                $value
            );

            if ($meta) {
                metaconsole_restore_db();
            }
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
            groups_get_name($event['id_grupo'], true),
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
            date($config['date_format'], $event['utimestamp']),
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
            events_display_instructions($event['event_type'], $event, false),
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
        $custom_data = json_decode(base64_decode($event['custom_data']));
        foreach ($custom_data as $key => $val) {
            $value = str_replace('_customdata_'.$key.'_', $val, $value);
        }
    }

    // This will replace the macro with the current logged user.
    if (strpos($value, '_current_user_') !== false) {
        $value = str_replace('_current_user_', $config['id_user'], $value);
    }

    return $value;

}


function events_get_instructions($event)
{
    if (!is_array($event)) {
        return '';
    }

    switch ($event['event_type']) {
        case 'going_unknown':
            if ($event['unknown_instructions'] != '') {
                $value = str_replace("\n", '<br>', io_safe_output($event['unknown_instructions']));
            }
        break;

        case 'going_up_warning':
        case 'going_down_warning':
            if ($event['warning_instructions'] != '') {
                $value = str_replace("\n", '<br>', io_safe_output($event['warning_instructions']));
            }
        break;

        case 'going_up_critical':
        case 'going_down_critical':
            if ($event['critical_instructions'] != '') {
                $value = str_replace("\n", '<br>', io_safe_output($event['critical_instructions']));
            }
        break;
    }

    if (!isset($value)) {
        return '';
    }

    $max_text_length = 300;
    $over_text = io_safe_output($value);
    if (strlen($over_text) > ($max_text_length + 3)) {
        $over_text = substr($over_text, 0, $max_text_length).'...';
    }

    $output  = '<div id="hidden_event_instructions_'.$event['id_evento'].'"';
    $output .= ' style="display: none; width: 100%; height: 100%; overflow: auto; padding: 10px; font-size: 14px; line-height: 16px; font-family: mono,monospace; text-align: left">';
    $output .= $value;
    $output .= '</div>';
    $output .= '<center>';
    $output .= '<span id="value_event_'.$event['id_evento'].'" style="white-space: nowrap;">';
    $output .= '<span id="value_event_text_'.$event['id_evento'].'"></span>';
    $output .= '<a href="javascript:show_instructions('.$event['id_evento'].')">';
    $output .= html_print_image('images/default_list.png', true, ['title' => $over_text]).'</a></span>';
    $output .= '</center>';

    return $output;
}
