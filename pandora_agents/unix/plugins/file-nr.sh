#!/bin/bash
###############################################################################
#
# Copyright (c) 2016  Ramon Novoa  <rnovoa@artica.es>
# Copyright (c) 2016  Artica Soluciones Tecnologicas S.L.
#
# sockstat.sh Pandora FMS agent plug-in to retrieve file handle statistics.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 of the License.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.	
#
###############################################################################
OUT=`cat /proc/sys/fs/file-nr`
ALLOC=`echo $OUT | cut -d' ' -f 1`
UNUSED=`echo $OUT | cut -d' ' -f 2`
MAX=`echo $OUT | cut -d' ' -f 3`

echo '<module>'
echo '<name>File handles allocated</name>'
echo '<type>generic_data</type>'
echo "<data>$ALLOC</data>"
echo '</module>'

echo '<module>'
echo '<name>File handles unused</name>'
echo '<type>generic_data</type>'
echo "<data>$UNUSED</data>"
echo '</module>'

echo '<module>'
echo '<name>File handles maximum</name>'
echo '<type>generic_data</type>'
echo "<data>$MAX</data>"
echo '</module>'
