<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

$now = time();

// 1 day
$d1 = date("Y-m-d H:00:00", $now-28800);
// today + 1 hour (to purge all possible data)
$all_data = date("Y-m-d H:00:00", $now+3600);
// 3 days ago
$d3 = date("Y-m-d H:00:00", $now-86400);
// 1 week ago
$week = date("Y-m-d H:00:00", $now-604800);
// 2 weeks ago
$week2 = date("Y-m-d H:00:00", $now-1209600);
// 1 month ago
$month = date("Y-m-d H:00:00", $now-2592000);
// Three months ago
$month3 = date("Y-m-d H:00:00", $now-7257600);

unset($now);

?>
