<?php
// Pandora FMS - the Flexible monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Evi Vanoost, vanooste@rcbi.rochester.edu

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require "../../include/config.php";
require "../../include/functions.php";
require_once "../../include/functions_db.php";

$ev_group = get_parameter ("ev_group", 0); // group
$search = get_parameter ("search", ""); // free search
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 0); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1);
$id_event = (int) get_parameter ("id_event", -1); //This will allow to select only 1 event (eg. RSS)

$sql_post = "";
if ($ev_group > 1)
	$sql_post .= " AND `tevento`.`id_grupo` = $ev_group";
if ($status == 1)
	$sql_post .= " AND `tevento`.`estado` = 1";
if ($status == 0)
	$sql_post .= " AND `tevento`.`estado` = 0";
if ($search != "")
        $sql_post .= " AND `tevento`.`evento` LIKE '%$search%'";
if ($event_type != "")
        $sql_post .= " AND `tevento`.`event_type` = '$event_type'";
if ($severity != -1)
        $sql_post .= " AND `tevento`.`criticity` >= ".$severity;
if ($id_agent != -1)
	$sql_post .= " AND `tevento`.`id_agente` = ".$id_agent;
if ($id_event != -1)
	$sql_post .= " AND id_evento = ".$id_event;
													
$sql="SELECT `tevento`.`id_evento` AS event_id,
	`tagente`.`nombre` AS agent_name,
	`tevento`.`id_usuario` AS validated_by,
	`tevento`.`estado` AS validated,
	`tevento`.`evento` AS event_descr,
	`tevento`.`utimestamp` AS unix_timestamp 
	FROM tevento, tagente
	WHERE  `tevento`.`id_agente` = `tagente`.`id_agente` ".$sql_post."
	ORDER BY utimestamp DESC LIMIT 0 , 30";

$result= get_db_all_rows_sql ($sql);

//$url = "https://".$_SERVER['HTTP_HOST']."/pandora_console";

$url = 'http://'.$_SERVER['HTTP_HOST'].$config["homeurl"];
$selfurl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
$rss_feed = '<?xml version="1.0" encoding="utf-8" ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
$rss_feed .= '<channel><title>Pandora RSS Feed</title><description>Latest events on Pandora</description>';
$rss_feed .= '<lastBuildDate>'.date(DATE_RFC822, $result[0]['unix_timestamp']).'</lastBuildDate>';
$rss_feed .= '<link>'.$url.'</link>';
$rss_feed .= '<atom:link href="'.htmlentities ($selfurl).'" rel="self" type="application/rss+xml" />';

if ($result === false) {
	$result = array();
	$rss_feed .= '<item><guid>'.$url.'/index.php?sec=eventos&sec2=operation/events/events</guid><title>No results</title>';
	$rss_feed .= '<description>There are no results. Click on the link to see all Pending events</description>';
	$rss_feed .= '<link>'.$url.'/index.php?sec=eventos&sec2=operation/events/events</link></item>';
}

foreach ($result as $row) {
//This is mandatory
	$rss_feed .= '<item><guid>';
	$rss_feed .= htmlentities ($url . "/index.php?sec=eventos&sec2=operation/events/events&id_event=" . $row['event_id']);
	$rss_feed .= '</guid><title>';
	$rss_feed .= htmlentities ($row['agent_name']);
	$rss_feed .= '</title><description>';
	$rss_feed .= htmlentities ($row['event_descr']);
	if($row['validated'] == 1) {
		$rss_feed .= '<br /><br />Validated by ' . $row['validated_by'];
	}
	$rss_feed .= '</description><link>';
	$rss_feed .= htmlentities ($url . "/index.php?sec=eventos&sec2=operation/events/events&id_event=" . $row["event_id"]);
	$rss_feed .= '</link>';

//The rest is optional
	$rss_feed .= '<pubDate>' . date(DATE_RFC822, $row['unix_timestamp']) . '</pubDate>';
	
//This is mandatory again
	$rss_feed .= '</item>';
}

$rss_feed .= "</channel></rss>";

header("Content-Type: application/xml; charset=UTF-8"); 
echo $rss_feed;
?>
