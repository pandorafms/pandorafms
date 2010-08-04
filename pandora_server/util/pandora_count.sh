#!/bin/bash

echo "Small tool to measure data processing throughput for a Pandora FMS data server"
echo "(c) 2010 Sancho Lerena, slerena@gmail.com"

ANT=0
while [ 1 ]
do 
	ACT=`find /var/spool/pandora/data_in | wc -l`
	if [ $ANT != 0 ]
	then	
		RES=`expr $ANT - $ACT`
		RES2=`expr $RES / 10`
		echo $RES2 xmlfiles per second
	fi
	ANT=$ACT
	sleep 10
done

