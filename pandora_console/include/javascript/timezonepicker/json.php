<?php
// Script setup.
ini_set('error_reporting', E_ALL);
date_default_timezone_set('UTC');
require './includes/parser.inc';

$map_width = empty($_GET['w']) || !is_numeric($_GET['w']) || $_GET['w'] > 1280 ? 600 : (int) $_GET['w'];
$map_height = round($map_width / 2);
$timezones = timezone_picker_parse_files($map_width, $map_height, 'tz_world.txt', 'tz_islands.txt');

header('Content-Type: application/json');
header('Cache-Control: public, max-age: 3600');

print json_encode($timezones, (JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
