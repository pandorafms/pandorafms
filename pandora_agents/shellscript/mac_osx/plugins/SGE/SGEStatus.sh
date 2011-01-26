#!/bin/bash
###############################################################################
#
# Copyright (c) 2008  Evi Vanoost  <vanooste@rcbi.rochester.edu>
#
# SGEStatus: A quick shell script that can be used as a Pandora plugin to
# 	display the SGE cluster status 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 3 of the License.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.  
#
###############################################################################


SGE_BIN="/RCBI/sge/bin/darwin-ppc"

PENDING=$[`$SGE_BIN/qstat -s p | grep " qw " | wc -l`+0]
RUNNING=$[`$SGE_BIN/qstat -s a | grep " r " | wc -l`+0]
HOSTS=$[`qhost | wc -l`-3]
QUEUES=`qstat -g c | grep -v '\-\-\-' | grep -v "CLUSTER QUEUE" | awk '{print $1}'`
NUMQUEUE=0

echo "<module>
	<name>Pending Jobs (Cluster)</name>
	<data>$PENDING</data>
	<type>generic_data</type></module>
	<module>
        <name>Running Jobs (Cluster)</name>
        <data>$RUNNING</data>
        <type>generic_data</type></module>
	<module>
        <name>Number of Hosts (Cluster)</name>
        <data>$HOSTS</data>
        <type>generic_data</type></module>"

for queue in $QUEUES
do
	NUMQUEUE=$[$NUMQUEUE+1]
	# CLUSTER QUEUE                   CQLOAD   USED  AVAIL  TOTAL aoACDS  cdsuE  
	# 64bit                             0.15      0      8     22      0     14 
	QINFO=`qstat -g c | grep $queue`
	AVGLOAD=`echo $QINFO | awk '{print $2}'`
	USEDSLOTS=`echo $QINFO | awk '{print $3}'`
	AVAILSLOTS=`echo $QINFO | awk '{print $4}'`
	TOTALSLOTS=`echo $QINFO | awk '{print $5}'`
	ADMSTATUS=`echo $QINFO | awk '{print $6}'`
	ERRSTATUS=`echo $QINFO | awk '{print $7}'`
	echo "<module>
		<name>Available Slots (Q: $queue)</name>
		<data>$AVAILSLOTS</data>
		<type>generic_data</type>
	</module>
	<module>
                <name>Used Slots (Q: $queue)</name>
                <data>$USEDSLOTS</data>
                <type>generic_data</type>
        </module>
	<module>
                <name>Total Slots (Q: $queue)</name>
                <data>$TOTALSLOTS</data>
                <type>generic_data</type>
        </module>
	<module>
                <name>Average load (Q: $queue)</name>
                <data>$AVGLOAD</data>
                <type>generic_data</type>
        </module>
        <module>
                <name>Slots in Status aoACDS (Q: $queue)</name>
                <data>$ADMSTATUS</data>
                <type>generic_data</type>
        </module>
	<module>
                <name>Slots in Status cdsuE (Q: $queue)</name>
                <data>$ERRSTATUS</data>
                <type>generic_data</type>
        </module>"
done

echo "<module>
        <name>Number of Queues</name>
        <data>$NUMQUEUE</data>
        <type>generic_data</type></module>"
