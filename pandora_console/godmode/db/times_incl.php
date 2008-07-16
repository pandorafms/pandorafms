<?php
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
