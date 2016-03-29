#!/bin/bash
###############################################################################
#
# Copyright (c) 2016  Ramon Novoa  <rnovoa@artica.es>
# Copyright (c) 2016  Artica Soluciones Tecnologicas S.L.
#
# sockstat.sh Pandora FMS agent plug-in to retrieve socket statistics.
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
OUT=`cat /proc/net/sockstat | grep TCP`
MAX=`cat /proc/sys/net/ipv4/tcp_mem | cut -d'	' -f 3`
INUSE=`echo $OUT | cut -d' ' -f 3`
ORPHAN=`echo $OUT | cut -d' ' -f 5`
TW=`echo $OUT | cut -d' ' -f 7`
ALLOC=`echo $OUT | cut -d' ' -f 9`
MEM=`echo $OUT | cut -d' ' -f 11`

echo '<module>'
echo '<name>TCP sockets in use</name>'
echo '<type>generic_data</type>'
echo "<data>$INUSE</data>"
echo '</module>'

echo '<module>'
echo '<name>TCP orphan sockets</name>'
echo '<type>generic_data</type>'
echo "<data>$ORPHAN</data>"
echo '</module>'

echo '<module>'
echo '<name>TCP sockets in TIME_WAIT</name>'
echo '<type>generic_data</type>'
echo "<data>$TW</data>"
echo '</module>'

echo '<module>'
echo '<name>TCP sockets allocated</name>'
echo '<type>generic_data</type>'
echo "<data>$ALLOC</data>"
echo '</module>'

echo '<module>'
echo '<name>TCP pages allocated</name>'
echo '<type>generic_data</type>'
echo '<unit>pages</unit>'
echo "<data>$MEM</data>"
echo '</module>'

echo '<module>'
echo '<name>TCP pages maximum</name>'
echo '<type>generic_data</type>'
echo '<unit>pages</unit>'
echo "<data>$MAX</data>"
echo '</module>'
