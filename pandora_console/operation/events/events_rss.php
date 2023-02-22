<?php
/**
 * Event RSS exporter.
 *
 * @category   Event RSS export
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

// Don't display other errors, messes up XML.
ini_set('display_errors', 0);

require_once '../../include/config.php';
require_once '../../include/functions.php';
require_once '../../include/functions_db.php';
require_once '../../include/functions_api.php';
require_once '../../include/functions_agents.php';
require_once '../../include/functions_users.php';
require_once '../../include/functions_tags.php';
require_once '../../include/functions_groups.php';


/**
 * Generates an xml entry.
 *
 * @param string $key   Key.
 * @param string $value Value.
 *
 * @return string XML entry.
 */
function xml_entry($key, $value)
{
    $output = '<'.xml_entities($key).'>';
    $output .= '<![CDATA['.io_safe_output($value).']]>';
    $output .= '</'.xml_entities($key).'>';
    return $output."\n";
}


/**
 * Escape entities for XML.
 *
 * @param string $str String.
 *
 * @return string Escaped string.
 */
function xml_entities($str)
{
    if (!is_string($str)) {
        return '';
    }

    if (preg_match_all('/(&[^;]+;)/', $str, $matches) != 0) {
        $matches = $matches[0];

        foreach ($matches as $entity) {
            $char = html_entity_decode($entity, (ENT_COMPAT | ENT_HTML401), 'UTF-8');

            $html_entity_numeric = '&#'.uniord($char).';';

            $str = str_replace($entity, $html_entity_numeric, $str);
        }
    }

    return $str;
}


/**
 * Undocumented function.
 *
 * @param string $u U.
 *
 * @return integer Ord.
 */
function uniord($u)
{
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));

    return ($k2 * 256 + $k1);
}


/**
 * Generate RSS header.
 *
 * @param integer $lastbuild Date, last build.
 *
 * @return string RSS header.
 */
function rss_header($lastbuild=0)
{
    $selfurl = ui_get_full_url('?'.$_SERVER['QUERY_STRING'], false, true);

    // ' <?php ' -- Fixes highlighters thinking that the closing tag is PHP
    $rss_feed = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
    $rss_feed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n";
    $rss_feed .= '<channel>'."\n";
    $rss_feed .= '<title>'.io_safe_output(get_product_name()).' Events Feed</title>'."\n";
    $rss_feed .= '<description>Latest events on '.get_product_name().'</description>'."\n";
    $rss_feed .= '<lastBuildDate>'.date(DATE_RFC822, $lastbuild).'</lastBuildDate>'."\n";
    // Last build date is the last event - that way readers won't mark it as having new posts.
    $rss_feed .= '<link>'.$url.'</link>'."\n";
    // Link back to the main Pandora page.
    $rss_feed .= '<atom:link href="'.xml_entities(io_safe_input($selfurl)).'" rel="self" type="application/rss+xml" />'."\n";

    return $rss_feed;
}


/**
 * RSS error handler.
 *
 * @param string $errno                   Errno.
 * @param string $errstr                  Errstr.
 * @param string $errfile                 Errfile.
 * @param string $errline                 Errline.
 * @param string $error_human_description Error_human_description.
 *
 * @return void
 */
function rss_error_handler($errno, $errstr, $errfile, $errline, $error_human_description=null)
{
    $url = ui_get_full_url(false);
    $selfurl = ui_get_full_url('?'.$_SERVER['QUERY_STRING'], false, true);

    // ' Fixes certain highlighters freaking out on the PHP closing tag.
    $rss_feed = rss_header(0);
    $rss_feed .= "\n";
    $rss_feed .= '<item>';
    $rss_feed .= "\n";
    $rss_feed .= '<guid>'.$url.'/index.php?sec=eventos&amp;sec2=operation/events/events</guid>';
    $rss_feed .= "\n";
    $rss_feed .= '<title>Error creating feed</title>';
    $rss_feed .= "\n";

    if (empty($error_human_description)) {
        $rss_feed .= '<description>There was an error creating the feed: '.$errno.' - '.$errstr.' in '.$errfile.' on line '.$errline.'</description>';
    } else {
        $rss_feed .= '<description>'.xml_entities(io_safe_input($error_human_description)).'</description>';
    }

    $rss_feed .= "\n";
    $rss_feed .= '<link>'.$url.'/index.php?sec=eventos&amp;sec2=operation/events/events</link>';
    $rss_feed .= "\n";
    $rss_feed .= '</item>';
    $rss_feed .= "\n";
    $rss_feed .= '</channel>';
    $rss_feed .= "\n";
    $rss_feed .= '</rss>';

    echo $rss_feed;
}


// Errors output as RSS.
set_error_handler('rss_error_handler', E_ERROR);

// Send header before starting to output.
header('Content-Type: application/xml; charset=UTF-8');

$ipOrigin = $_SERVER['REMOTE_ADDR'];

// Uncoment this to activate ACL on RSS Events.
if (!isInACL($ipOrigin)) {
    rss_error_handler(
        null,
        null,
        null,
        null,
        __('Your IP is not into the IP list with API access.')
    );

    exit;
}

// Check user credentials.
$user = get_parameter('user');
$hashup = get_parameter('hashup');

$pss = get_user_info($user);
$hashup2 = md5($user.$pss['password']);

if ($hashup != $hashup2) {
    rss_error_handler(
        null,
        null,
        null,
        null,
        __('The URL of your feed has bad hash.')
    );

    exit;
}

$reset_session = false;
if (empty($config['id_user'])) {
    $config['id_user'] = $user;
    $reset_session = true;
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


try {
    $fb64 = get_parameter('fb64', null);
    $plain_filter = base64_decode($fb64);
    $filter = json_decode($plain_filter, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        throw new Exception('Invalid filter. ['.$plain_filter.']');
    }

    // Dump events.
    $limit = get_parameter('limit', 20);
    $offset = get_parameter('offset', 0);
    $events = events_get_all(
        $fields,
        $filter,
        $offset,
        $limit,
        'desc',
        'timestamp',
        $filter['history']
    );

    $last_timestamp = 0;
    if (is_array($events)) {
        $last_timestamp = $events[0]['utimestamp'];
    }

    // Dump headers.
    $rss = rss_header($last_timestamp);
    $url = ui_get_full_url(false);

    if (is_array($events)) {
        foreach ($events as $row) {
            $rss .= '<item>';
            $rss .= xml_entry('title', $row['evento']);
            if (!empty($row['id_agente'])) {
                $rss .= xml_entry('link', $url.'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$row['id_agente']);
            }

            $rss .= xml_entry('author', $row['agent_name']);
            $rss .= xml_entry('comments', $row['']);
            $rss .= xml_entry('pubDate', $row['timestamp']);
            $rss .= xml_entry('category', $row['source']);
            foreach ($column_names as $val) {
                $key = $val;
                if ($val == 'id_grupo') {
                    $key = 'group_name';
                } else if ($val == 'id_agentmodule') {
                    $key = 'module_name';
                }

                switch ($key) {
                    case 'module_status':
                        $value = events_translate_module_status(
                            $row[$key]
                        );
                    break;

                    case 'event_type':
                        $value = events_translate_event_type(
                            $row[$key]
                        );
                    break;

                    case 'criticity':
                        $value = events_translate_event_criticity(
                            $row[$key]
                        );
                    break;

                    default:
                        $value = $row[$key];
                    break;
                }

                $rss .= xml_entry($key, $value);
            }

            $rss .= '</item>';
        }
    } else {
        $rss .= '<item><guid>'.xml_entities(io_safe_input($url.'/index.php?sec=eventos&sec2=operation/events/events')).'</guid><title>No results</title>';
        $rss .= '<description>There are no results. Click on the link to see all Pending events</description>';
        $rss .= '<link>'.xml_entities(io_safe_input($url.'/index.php?sec=eventos&sec2=operation/events/events')).'</link></item>'."\n";
    }

    $rss .= "</channel>\n</rss>\n";

    echo $rss;
} catch (Exception $e) {
    echo rss_error_handler(200, 'Controlled error', '', '', $e->getMessage());
}

if ($reset_session) {
    unset($config['id_user']);
}
