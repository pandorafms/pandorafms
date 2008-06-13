<?php
// Pandora FMS - the Free monitoring system
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

error_reporting(E_ALL);

require "../../include/config.php";
require "../../include/functions.php";
require "../../include/functions_db.php";

$constraints = "";


$sql="SELECT  `tevento`.`id_evento` AS event_id,  `tagente`.`nombre` AS agent_name,  `tevento`.`id_usuario` AS validated_by ,  `tevento`.`estado` AS validated,  `tevento`.`evento` AS event_descr ,  `tevento`.`utimestamp` AS unix_timestamp, `tgrupo`.`nombre` AS group_name, `tgrupo`.`icon` AS group_icon 
FROM tevento, tagente, tgrupo
WHERE  `tevento`.`id_agente` =  `tagente`.`id_agente` AND `tevento`.`id_grupo` = `tgrupo`.`id_grupo` $constraints
ORDER BY utimestamp DESC 
LIMIT 0 , 30";

$result=mysql_query($sql);

//$url = "https://".$_SERVER['HTTP_HOST']."/pandora_console";

$url = $config["homeurl"];

$rss_feed = '<?xml version="1.0" ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>
<title>Pandora RSS Feed</title>
<description>Latest events on Pandora</description>
<link>' . $url . '</link>
<atom:link href="' . $url . '/operation/events/events_rss.php" rel="self" type="application/rss+xml" />';

while($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
//This is mandatory
	$rss_feed .= '<item><guid>';
		$rss_feed .= $url . "/operation/events/view_event?id=" . $row['event_id'];
	$rss_feed .= '</guid><title>';
		$rss_feed .= htmlentities($row['agent_name']);
	$rss_feed .= '</title><description>';
		$rss_feed .= htmlentities($row['event_descr']);
		if($row['validated'] == 1) {
			$rss_feed .= '<br /><br />Validated by ' . $row['validated_by'];
		}
	$rss_feed .= '</description><link>';
		$rss_feed .= $url . "/operation/events/view_event?id=" . $row["event_id"];
	$rss_feed .= '</link>';
//The rest is optional
	$rss_feed .= '<pubDate>' . date(DATE_RFC822, $row['unix_timestamp']) . '</pubDate>';
 	$rss_feed .= '<image>';
		$rss_feed .= '<link>' . $url .  '</link>';
		$rss_feed .= '<title>' . $row['group_name'] . '</title>';
		$rss_feed .= '<url>' . $url . '/images/groups_small/' . $row['group_icon'] . '.png</url>';
	$rss_feed .= '</image>';	
	
//This is mandatory again
	$rss_feed .= '</item>';
}

$rss_feed .= "</channel></rss>";

header("Content-Type: application/xml; charset=UTF-8"); 
echo $rss_feed;
?>
