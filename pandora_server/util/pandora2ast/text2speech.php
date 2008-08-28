#!/usr/bin/php -q
<?php
/*
# This file is part of Pandora2Asterisk which is an external module to the
# Pandora Flexible Monitoring System.

# Pandora2Asterisk is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# Pandora2Asterisk is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with Pandora2Asterisk.  If not, see <http://www.gnu.org/licenses/>.
*/
error_reporting (0);
set_time_limit (30);
require ('phpagi/phpagi.php');
$agi = new AGI();
$agi->answer ();
$agi->text2wav ($argv[1]);
$agi->hangup ()
?>
