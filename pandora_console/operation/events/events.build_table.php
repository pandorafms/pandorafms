<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_ui.php';

$sort_field = get_parameter('sort_field', 'timestamp');
$sort = get_parameter('sort', 'down');

$response_id = get_parameter('response_id', '');

$table = new stdClass();
if (!isset($table->width)) {
    $table->width = '100%';
}

$table->id = 'eventtable';
$table->cellpadding = 4;
$table->cellspacing = 4;
if (!isset($table->class)) {
    $table->class = 'info_table';
}

$table->head = [];
$table->data = [];

$params = [
    // Pandora sections
    'sec'               => 'eventos',
    'sec2'              => 'operation/events/events',

    // Events query params
    'search'            => io_safe_input($search),
    'event_type'        => $event_type,
    'severity'          => $severity,
    'status'            => $status,
    'id_group'          => $id_group,
    'recursion'         => $recursion,
    'refr'              => (int) get_parameter('refr', 0),
    'id_agent'          => $id_agent,
    'id_agent_module'   => $id_agent_module,
    'pagination'        => $pagination,
    'group_rep'         => $group_rep,
    'event_view_hr'     => $event_view_hr,
    'id_user_ack'       => $id_user_ack,
    'tag_with'          => $tag_with_base64,
    'tag_without'       => $tag_without_base64,
    'filter_only_alert' => $filter_only_alert,
    'offset'            => $offset,
    'toogle_filter'     => 'no',
    'filter_id'         => $filter_id,
    'id_name'           => $id_name,
    'history'           => (int) $history,
    'section'           => $section,
    'open_filter'       => $open_filter,
    'date_from'         => $date_from,
    'date_to'           => $date_to,
    'pure'              => $config['pure'],

    // Display params
    'offset'            => $offset,
    'disabled'          => $disabled,
    'sort'              => $sort,
    'sort_field'        => $sort_field,
];

if ($group_rep == 2) {
    $table->class = 'databox filters data';
    $table->head[1] = __('Agent');
    $table->head[5] = __('More detail');

    // $url =  html_print_sort_arrows(
    // array_merge($params, array('sort_field' => 'status')),
    // 'sort'
    // );
    $params_sort_field_status = array_merge($params, ['sort_field' => 'status']);
    $url = 'index.php?'.http_build_query($params_sort_field_status, '', '&amp;');

    foreach ($result as $key => $res) {
        if ($res['event_type'] == 'alert_fired') {
            $table->rowstyle[$key] = 'background: #FFA631;';
        } else if ($res['event_type'] == 'going_up_critical' || $res['event_type'] == 'going_down_critical') {
            $table->rowstyle[$key] = 'background: #e63c52;';
        } else if ($res['event_type'] == 'going_up_warning' || $res['event_type'] == 'going_down_warning') {
            $table->rowstyle[$key] = 'background: #f3b200;';
        } else if ($res['event_type'] == 'going_up_normal' || $res['event_type'] == 'going_down_normal') {
            $table->rowstyle[$key] = 'background: #82b92e;';
        } else if ($res['event_type'] == 'going_unknown') {
            $table->rowstyle[$key] = 'background: #B2B2B2;';
        }


        if ($meta) {
            $table->data[$key][1] = __('The Agent: ').'"'.$res['id_agent'].'", '.__(' has ').$res['total'].__(' events.');
        } else {
            $table->data[$key][1] = __('The Agent: ').'"'.agents_get_alias($res['id_agent']).'", '.__(' has ').$res['total'].__(' events.');
        }

        $uniq = uniqid();
        if ($meta) {
            $table->data[$key][2] = '<img id="open_agent_groups" src=images/zoom_mc.png data-id="'.$table->id.'-'.$uniq.'-0" data-open="false"
				onclick=\'show_events_group_agent("'.$table->id.'-'.$uniq.'-0","'.$res['id_agent'].'",'.$res['id_server'].');\' />';
        } else {
            $table->data[$key][2] = '<img id="open_agent_groups" src="images/zoom_mc.png" data-id="'.$table->id.'-'.$uniq.'-0" data-open="false"
				onclick=\'show_events_group_agent("'.$table->id.'-'.$uniq.'-0",'.$res['id_agent'].',false);\'/>';
        }

        $table->cellstyle[$uniq][0] = 'display:none;';
        $table->data[$uniq][0] = false;
    }

    if ($result) {
        if ($allow_pagination) {
            ui_pagination($total_events, $url, $offset, $pagination);
        }

        html_print_table($table);

        if ($allow_pagination) {
            ui_pagination($total_events, $url, $offset, $pagination);
        }
    } else {
        echo '<div class="nf">'.__('No events').'</div>';
    }
} else {
    // fields that the user has selected to show
    if ($meta) {
        $show_fields = events_meta_get_custom_fields_user();
    } else {
        $show_fields = explode(',', $config['event_fields']);
    }

    // headers
    $i = 0;
    $table->head[$i] = __('ID').html_print_sort_arrows(
        array_merge($params, ['sort_field' => 'event_id']),
        'sort'
    );

    $table->align[$i] = 'left';

    $i++;
    foreach ($show_fields as $k_s => $fields) {
        if ($fields == 'server_name') {
            $table->head[$i] = __('Server');
            $table->align[$i] = 'left';
            $i++;
        }

        if ($fields == 'id_evento') {
            $table->head[$i] = __('Event ID').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'event_id']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'evento') {
            $table->head[$i] = __('Event Name').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'event_name']),
                'sort'
            );
            $table->align[$i] = 'left';
            $table->style[$i] = 'min-width: 200px; max-width: 350px; word-break: break-all;';
            $i++;
        }

        if ($fields == 'id_agente') {
            $table->head[$i] = __('Agent name').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'agent_id']),
                'sort'
            );
            $table->align[$i] = 'left';
            $table->style[$i] = 'max-width: 350px; word-break: break-all;';
            $i++;
        }

        if ($fields == 'timestamp') {
            $table->head[$i] = __('Timestamp').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'timestamp']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'id_usuario') {
            $table->head[$i] = __('User').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'user_id']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'owner_user') {
            $table->head[$i] = __('Owner').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'owner']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'id_grupo') {
            $table->head[$i] = __('Group').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'group_id']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'event_type') {
            $table->head[$i] = __('Event Type').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'event_type']),
                'sort'
            );
            $table->align[$i] = 'left';

            $table->style[$i] = 'min-width: 85px;';
            $i++;
        }

        if ($fields == 'id_agentmodule') {
            $table->head[$i] = __('Module Name').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'module_name']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'id_alert_am') {
            $table->head[$i] = __('Alert').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'alert_id']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'criticity') {
            $table->head[$i] = __('Severity').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'criticity']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'user_comment') {
            $table->head[$i] = __('Comment').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'comment']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'tags') {
            $table->head[$i] = __('Tags').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'tags']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'source') {
            $table->head[$i] = __('Source').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'source']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'id_extra') {
            $table->head[$i] = __('Extra ID').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'extra_id']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'ack_utimestamp') {
            $table->head[$i] = __('ACK Timestamp').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'ack_utimestamp']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'instructions') {
            $table->head[$i] = __('Instructions');
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'data') {
            $table->head[$i] = __('Data').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'data']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }

        if ($fields == 'module_status') {
            $table->head[$i] = __('Module Status').html_print_sort_arrows(
                array_merge($params, ['sort_field' => 'module_status']),
                'sort'
            );
            $table->align[$i] = 'left';

            $i++;
        }
    }

    if (in_array('estado', $show_fields)) {
        $table->head[$i] = '<span style="white-space: nowrap;">'.__('Status').html_print_sort_arrows(
            array_merge($params, ['sort_field' => 'status']),
            'sort'
        ).'</span>';
        $table->align[$i] = 'left';
        $table->style[$i] = 'white-space: nowrap !important; width: 1px !important;';
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
        } else {
            $table->head[$i] = '';
        }
    }

    if ($meta) {
        // Get info of the all servers to use it on hash auth
        $servers_url_hash = metaconsole_get_servers_url_hash();
        $servers = metaconsole_get_servers();
    }

    $show_delete_button = false;
    $show_validate_button = false;

    $idx = 0;

    if ($meta) {
        $alias_array = [];
    }

    // Arrange data. We already did ACL's in the query
    foreach ($result as $event) {
        $data = [];

        if ($meta) {
            $event['server_url_hash'] = $servers_url_hash[$event['server_id']];
            $event['server_url'] = $servers[$event['server_id']]['server_url'];
            $event['server_name'] = $servers[$event['server_id']]['server_name'];
        }

        // Clean url from events and store in array
        $event['clean_tags'] = events_clean_tags($event['tags']);

        // First pass along the class of this row
        $myclass = get_priority_class($event['criticity']);

        // print status
        $estado = $event['estado'];

        // Colored box
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
        }

        $i = 0;

        $data[$i] = '#'.$event['id_evento'];
        $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3; color: #111 !important;';

        // Pass grouped values in hidden fields to use it from modal window
        if ($group_rep) {
            $similar_ids = $event['similar_ids'];
            $timestamp_first = $event['timestamp_rep_min'];
            $timestamp_last = $event['timestamp_rep'];
        } else {
            $similar_ids = $event['id_evento'];
            $timestamp_first = $event['utimestamp'];
            $timestamp_last = $event['utimestamp'];
        }

        // Store group data to show in extended view
        $data[$i] .= html_print_input_hidden('similar_ids_'.$event['id_evento'], $similar_ids, true);
        $data[$i] .= html_print_input_hidden('timestamp_first_'.$event['id_evento'], $timestamp_first, true);
        $data[$i] .= html_print_input_hidden('timestamp_last_'.$event['id_evento'], $timestamp_last, true);
        $data[$i] .= html_print_input_hidden('childrens_ids', json_encode($childrens_ids), true);

        // Store server id if is metaconsole. 0 otherwise
        if ($meta) {
            $server_id = $event['server_id'];

            // If meta activated, propagate the id of the event on node (source id)
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
        // Store concat comments to show in extended view
        $data[$i] .= html_print_input_hidden('user_comment_'.$event['id_evento'], base64_encode($event['user_comment']), true);

        $i++;

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

        foreach ($show_fields as $k_s => $fields) {
            if ($fields == 'server_name') {
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

            if ($fields == 'id_evento') {
                $data[$i] = $event['id_evento'];
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'evento') {
                // Event description
                $data[$i] = '<span title="'.strip_tags(io_safe_output($event['evento'])).'" class="f9">';
                if ($allow_action) {
                    $data[$i] .= '<a href="javascript:" onclick="show_event_dialog('.$event['id_evento'].', '.$group_rep.');">';
                }

                $data[$i] .= '<span class="'.$myclass.'" style="font-size: 7.5pt;">'.ui_print_truncate_text(strip_tags(io_safe_output($event['evento'])), 160).'</span>';
                if ($allow_action) {
                    $data[$i] .= '</a>';
                }

                $data[$i] .= '</span>';
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'id_agente') {
                $data[$i] = '<span class="'.$myclass.'">';

                if ($event['id_agente'] > 0) {
                    // Agent name
                    if ($meta) {
                        if (!empty($event['agent_name'])) {
                            if (!array_key_exists($event['agent_name'], $alias_array)) {
                                $alias_array[$event['agent_name']] = db_get_value('alias', 'tmetaconsole_agent', 'nombre', $event['agent_name']);
                            }
                        }

                        $agent_link = '<a href="'.$event['server_url'].'/index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event['id_agente'].$event['server_url_hash'].'">';
                        if (can_user_access_node()) {
                            $data[$i] = '<b>'.$agent_link.$alias_array[$event['agent_name']].'</a></b>';
                        } else {
                            $data[$i] = $alias_array[$event['agent_name']];
                        }
                    } else {
                        $agent = db_get_row('tagente', 'id_agente', $event['id_agente']);
                        $data[$i] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$event['id_agente'].'" title='.$agent['nombre'].'>';
                        $data[$i] .= '<b>'.$agent['alias'].'</a></b>';
                    }
                } else {
                    $data[$i] .= '';
                }

                $data[$i] .= '</span>';
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'timestamp') {
                // Time
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

            if ($fields == 'id_usuario') {
                $user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
                if (empty($user_name)) {
                    $user_name = $event['id_usuario'];
                }

                $data[$i] = $user_name;
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'owner_user') {
                $owner_name = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
                if (empty($owner_name)) {
                    $owner_name = $event['owner_user'];
                }

                $data[$i] = $owner_name;
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'id_grupo') {
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

            if ($fields == 'event_type') {
                $data[$i] = events_print_type_description($event['event_type'], true);
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'id_agentmodule') {
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

            if ($fields == 'id_alert_am') {
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

            if ($fields == 'criticity') {
                $data[$i] = get_priority_name($event['criticity']);
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'user_comment') {
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
                    if ($myclass == 'datos_yellow') {
                        $comments_help_tip = ui_print_help_tip_border($event_user_comment_str, true);
                    } else {
                        $comments_help_tip = ui_print_help_tip($event_user_comment_str, true);
                    }
                }

                $data[$i] = '<span id="comment_header_'.$event['id_evento'].'">'.$comments_help_tip.'</span>';
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'tags') {
                $data[$i] = tags_get_tags_formatted($event['tags']);
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'source') {
                $data[$i] = $event['source'];
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'id_extra') {
                $data[$i] = $event['id_extra'];
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'ack_utimestamp') {
                if ($event['ack_utimestamp'] == 0) {
                    $data[$i] = '';
                } else {
                    $data[$i] = date($config['date_format'], $event['ack_utimestamp']);
                }

                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'instructions') {
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
                }

                if (!isset($data[$i])) {
                    $data[$i] = '';
                }

                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'data') {
                $data[$i] = $event['data'];
                if (($data[$i] % 1) == 0) {
                    $data[$i] = number_format($data[$i], 0);
                } else {
                    $data[$i] = number_format($data[$i], 2);
                }

                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }

            if ($fields == 'module_status') {
                $data[$i] = modules_get_modules_status($event['module_status']);
                $table->cellclass[count($table->data)][$i] = $myclass;
                $i++;
            }
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
                $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3; white-space: nowrap; width: 1px;';
                $i++;
        }

        if ($i != 0 && $allow_action) {
            // Actions
            $data[$i] = '<a href="javascript:" onclick="show_event_dialog('.$event['id_evento'].', '.$group_rep.');">';
            $data[$i] .= html_print_input_hidden('event_title_'.$event['id_evento'], '#'.$event['id_evento'].' - '.strip_tags(io_safe_output($event['evento'])), true);
            $data[$i] .= html_print_image(
                'images/eye.png',
                true,
                ['title' => __('Show more')]
            );
            $data[$i] .= '</a>';

            if (!$readonly) {
                // Validate event
                if (($event['estado'] != 1) && (tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EW', $event['clean_tags'], $childrens_ids))) {
                    $show_validate_button = true;
                    $data[$i] .= '<a href="javascript:validate_event_advanced('.$event['id_evento'].', 1)" id="validate-'.$event['id_evento'].'">';
                    $data[$i] .= html_print_image(
                        'images/ok.png',
                        true,
                        ['title' => __('Validate event')]
                    );
                    $data[$i] .= '</a>';
                    // Display the go to in progress status button
                    if ($event['estado'] != 2) {
                        $data[$i] .= '<a href="javascript:validate_event_advanced('.$event['id_evento'].', 2)" id="in-progress-'.$event['id_evento'].'">';
                        $data[$i] .= html_print_image(
                            'images/hourglass.png',
                            true,
                            ['title' => __('Change to in progress status')]
                        );
                        $data[$i] .= '</a>';
                    }
                }

                // Delete event
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
                            [
                                'title' => __('Is not allowed delete events in process'),
                                'id'    => 'delete-'.$event['id_evento'],
                            ]
                        );
                        $data[$i] .= '&nbsp;';
                    }
                }
            }

            $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';

            $i++;

            if (!$readonly) {
                if (tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EM', $event['clean_tags'], $childrens_ids) == 1) {
                    // Checkbox
                    // Class 'candeleted' must be the fist class to be parsed from javascript. Dont change
                    $data[$i] = html_print_checkbox_extended('validate_ids[]', $event['id_evento'], false, false, false, 'class="candeleted chk_val"', true);
                } else if (tags_checks_event_acl($config['id_user'], $event['id_grupo'], 'EW', $event['clean_tags'], $childrens_ids) == 1) {
                    // Checkbox
                    $data[$i] = html_print_checkbox_extended('validate_ids[]', $event['id_evento'], false, false, false, 'class="chk_val"', true);
                } else if (isset($table->header[$i]) || true) {
                    $data[$i] = html_print_checkbox_extended('validate_ids[]', $event['id_evento'], false, false, false, 'class="chk_val"', true);
                }
            }

            $table->cellstyle[count($table->data)][$i] = 'background: #F3F3F3;';
        }

        array_push($table->data, $data);

        $idx++;
    }

    echo '<div id="events_list">';
    if (!empty($table->data)) {
        if ($allow_pagination) {
            $params_to_paginate = $params;
            unset($params_to_paginate['offset']);
            $url_paginate = 'index.php?'.http_build_query($params_to_paginate, '', '&amp;');
            ui_pagination($total_events, $url_paginate, $offset, $pagination);
        }

        if ($allow_action) {
            echo '<form method="post" id="form_events" action="'.$url.'">';
            echo "<input type='hidden' name='delete' id='hidden_delete_events' value='0' />";
        }

        if (defined('METACONSOLE')) {
            echo '<div style="width: '.$table->width.';">';
        } else {
            echo '<div style="width: '.$table->width.'; overflow-x: auto;">';
        }

        html_print_table($table);
        if ($allow_pagination) {
            $params_to_paginate = $params;
            unset($params_to_paginate['offset']);
            $url_paginate = 'index.php?'.http_build_query($params_to_paginate, '', '&amp;');
            ui_pagination($total_events, $url_paginate, $offset, $pagination, false, 'offset', true, 'pagination-bottom');
        }

        echo '</div>';

        if ($allow_action) {
            echo '<div style="width:'.$table->width.';" class="action-buttons">';
            if (!$readonly && $show_validate_button) {
                $array_events_actions['in_progress_selected'] = 'In progress selected';
                $array_events_actions['validate_selected'] = 'Validate selected';
                // Fix: validated_selected JS function has to be included with the proper user ACLs
                ?>
                <script type="text/javascript">
                    function validate_selected(new_status) {
                        $(".chk_val").each(function() { 
                            if($(this).is(":checked")) {
                                validate_event_advanced($(this).val(), new_status);
                            }
                        });  
                        location.reload();
                    }
                </script>
                <?php
            }

            if (!$readonly && ($show_delete_button)) {
                $array_events_actions['delete_selected'] = 'Delete selected';
                ?>
                <script type="text/javascript">
                    function delete_selected() {
                        if(confirm('<?php echo __('Are you sure?'); ?>')) {
                            $("#hidden_delete_events").val(1);
                            $("#form_events").submit();
                        }
                    }
                </script>
                <?php
            }

            echo '</div>';
            echo '</form>';

            $sql_event_resp = "SELECT id, name FROM tevent_response WHERE type LIKE 'command'";
            $event_responses = db_get_all_rows_sql($sql_event_resp);

            foreach ($event_responses as $val) {
                $array_events_actions[$val['id']] = $val['name'];
            }

            if ($config['event_replication'] != 1) {
                echo '<div style="width:100%;text-align:right;">';
                echo '<form method="post" id="form_event_response">';
                html_print_select($array_events_actions, 'response_id', '', '', '', 0, false, false, false);
                echo '&nbsp&nbsp';
                html_print_button(__('Execute event response'), 'submit_event_response', false, 'execute_event_response(true);', 'class="sub next"');
                echo "<span id='response_loading_dialog' style='display:none'>".html_print_image('images/spinner.gif', true).'</span>';
                echo '</form>';
                echo '<span id="max_custom_event_resp_msg" style="display:none; color:#e63c52; line-height: 200%;">';
                echo __(
                    'A maximum of %s event custom responses can be selected',
                    $config['max_execution_event_response']
                ).'</span>';
                echo '<span id="max_custom_selected" style="display:none; color:#e63c52; line-height: 200%;">';
                echo __(
                    'Please, select an event'
                ).'</span>';
                echo '</div>';
            }
        }

        ?>
            <script type="text/javascript">

                function execute_event_response(event_list_btn) {

                    $('#max_custom_event_resp_msg').hide();
                    $('#max_custom_selected').hide();

                    var response_id = $('select[name=response_id]').val();


                    if (!isNaN(response_id)) { // It is a custom response

                        var response = get_response(response_id);

                        var counter=0;
                        var end=0;

                        // If cannot get response abort it
                        if (response == null) {
                            return;
                        }

                        var total_checked = $(".chk_val:checked").length;

                        // Check select an event.
                        if(total_checked == 0){
                            $('#max_custom_selected').show();
                            return;
                        }

                        // Limit number of events to apply custom responses
                        // to for performance reasons.
                        if (total_checked > <?php echo $config['max_execution_event_response']; ?> ) {
                            $('#max_custom_event_resp_msg').show();
                            return;
                        }

                        var response_command = [];
                        $(".response_command_input").each(function() {
                            response_command[$(this).attr("name")] = $(this).val();
                        });

                        if (event_list_btn) {
                            $('#button-submit_event_response').hide(function() {
                                $('#response_loading_dialog').show(function() {
                                    var check_params = get_response_params(
                                        response_id
                                    );

                                    if(check_params[0] !== ''){
                                        show_event_response_command_dialog(
                                            response_id,
                                            response,
                                            total_checked
                                        );
                                    }
                                    else{
                                        check_massive_response_event(
                                            response_id,
                                            response,
                                            total_checked,
                                            response_command
                                        );
                                    }
                                });
                            });
                        }
                        else {
                            $('#button-btn_str').hide(function() {
                                $('#execute_again_loading').show(function() {
                                    check_massive_response_event(
                                        response_id,
                                        response,
                                        total_checked,
                                        response_command
                                    );
                                });
                            });
                        }

                    }
                    else { // It is not a custom response
                        switch (response_id) {
                            case 'in_progress_selected':
                                validate_selected(2);
                                break;
                            case 'validate_selected':
                                validate_selected(1);
                            break;
                            case 'delete_selected':
                                delete_selected();
                            break;
                        }
                    }
                }

                function check_massive_response_event(
                    response_id,
                    response,
                    total_checked,
                    response_command
                ){
                    var counter=0;
                    var end=0;

                    $(".chk_val").each(function() {
                        if ($(this).is(":checked")) {
                            event_id = $(this).val();
                            server_id = $('#hidden-server_id_'+event_id).val();
                            response['target'] = get_response_target(
                                event_id,
                                response_id,
                                server_id,
                                response_command
                            );

                            if (total_checked-1 === counter)
                                end=1;

                            show_massive_response_dialog(
                                event_id,
                                response_id,
                                response,
                                counter,
                                end
                            );

                            counter++;
                        }
                    });
                }
            </script>
        <?php
    } else {
        echo '<div class="nf">'.__('No events').'</div>';
    }

    echo '</div>';
}
