#!/bin/bash

while [ 1 ]
do
	UNO=`ps aux | grep pandora_net | grep -v grep | awk '{ print $6 }'`
	DOS=`ps aux | grep pandora_snmp | grep -v grep | awk '{ print $6 }'`
	TRES=`ps aux | grep pandora_server.pl | grep -v grep | awk '{ print $6 }'`

	TIMESTAMP=`date +"%Y/%m/%d %H:%M:%S"`
	echo "Network: $UNO           SNMP: $DOS            Server $TRES"
	echo "$TIMESTAMP  Network: $UNO   SNMP: $DOS   Server $TRES" >> pandoramemtest.log
	sleep 5
done
 
