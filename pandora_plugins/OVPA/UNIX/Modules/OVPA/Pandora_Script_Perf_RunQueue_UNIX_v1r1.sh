#!/bin/sh

LOADAVG1MINORIGINAL=`uptime | awk '{print $(NF-2)}'`

LOADAVGCHECK=`echo "$LOADAVG1MINORIGINAL" | grep "\." | wc -l | awk '{print $1}'`

if [ "$LOADAVGCHECK" = "0" ]
then

	LOADAVG1MIN=`echo "$LOADAVG1MINORIGINAL" | awk -F "," '{print $1"."$2}'`

else

	LOADAVG1MIN=`echo "$LOADAVG1MINORIGINAL" | tr -d ","`

fi

echo $LOADAVG1MIN

