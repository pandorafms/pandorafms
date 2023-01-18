#!/bin/bash

echo "Small tool to measure data processing throughput for a Pandora FMS Data server"
echo "(c) 2010-2023 Pandora FMS Team"

ANT=0
while [ 1 ]
do 
	ACT=`find /var/spool/pandora/data_in -maxdepth 1 -type f  | wc -l`
	if [ $ANT != 0 ]
	then	
		RES=`expr $ANT - $ACT`
		RES2=`expr $RES / 10`
		echo $RES2 xmlfiles per second
	fi
	ANT=$ACT
	sleep 10
done

