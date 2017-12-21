#!/bin/sh

OS_NAME=`uname`

if [[ "$OS_NAME" = "HP-UX" || "$OS_NAME" = "AIX" ]]
then

	DISKCOUNT=`sar -d 5 | tail +5 | grep "." |  wc -l`
	DISKQUEUELENGTHS=`sar -d 5 | tail +5 | awk '{print $4}' | grep "." | tr "," "." | sort -n` 
fi

if [ "$OS_NAME" = "SunOS" ]
then
	DISKCOUNT=`iostat -dx | grep -v device | wc -l`
        DISKQUEUELENGTHS=`iostat -dx | grep -v device | awk '{print $(NF-4)}'`
fi

if [ "$OS_NAME" = "Linux" ]
then

	DISKCOUNT=`iostat -dx 1 5 | tac | grep Device -m 1 -B 200 | grep -v Device | tail -n +2 | wc -l`
        DISKQUEUELENGTHS=`iostat -dx 1 5 | tac | grep Device -m 1 -B 200 | grep -v Device | tail -n +2 | awk '{print $(NF-3)}' | sed -e s/,/\./g`

fi

for line in $DISKQUEUELENGTHS
do
	if [ "$AVGDISKQUEUELENGTH" = "" ]
	then
		AVGDISKQUEUELENGTH=$line
	else
		AVGDISKQUEUELENGTH=`echo "$AVGDISKQUEUELENGTH + $line" | bc`
	fi

done

echo "scale=2; $AVGDISKQUEUELENGTH / $DISKCOUNT" | bc
