#!/bin/sh

OSNAME=`uname`

if [ "$OSNAME" = "AIX" ]
then

	IDLECPU=`vmstat 1 5 | tail -1 | awk '{print $(NF-1)}'`

fi

if [ "$OSNAME" = "SunOS" ]
then

	IDLECPU=`mpstat -a 1 5 | tail -1 | awk '{print $(NF-1)}'`

fi

if [ "$OSNAME" = "Linux" ]
then

	IDLECPU=`mpstat 5 1 | tail -1 | awk '{print $NF}' | sed -e s/,/\./g`

fi

if [ "$OSNAME" = "HP-UX" ]
then

	IDLECPU=`iostat -t | tail +3 | head -1 | awk '{print $NF}'`

fi

USEDCPU=`echo "scale=2; 100 - $IDLECPU" | bc`

echo $USEDCPU

