#!/bin/sh

OS_NAME=`uname`

if [[ "$OS_NAME" = "HP-UX" || "$OS_NAME" = "AIX" ]]
then

	DISKCOUNT=`sar -d 5 | tail +5 | grep "." | wc -l`
	RW_IOS_COMMAND=`sar -d 5 | tail +5 | grep "." | awk '{print $5}' | tr "," "."`

	for line in $RW_IOS_COMMAND
	do

		if [ "$RW_IOS" = "" ]
		then

			RW_IOS=$line

		else

			RW_IOS=`echo "$RW_IOS + $line" | bc`

		fi

	done

	FINAL_RW_IOS=`echo "scale=2; $RW_IOS / $DISKCOUNT" | bc`

fi

if [ "$OS_NAME" = "SunOS" ]
then

	READ_IOS_COMMAND=`kstat sd | grep reads | awk '{print $NF}'`
	WRITE_IOS_COMMAND=`kstat sd | grep writes | awk '{print $NF}'`

	for line in $READ_IOS_COMMAND
	do
		if [ "$READ_IOS" = "" ]
		then

			READ_IOS=$line

		else

			READ_IOS=`echo "$READ_IOS + $line" | bc`

		fi

	done

	for linea in $WRITE_IOS_COMMAND
	do
		if [ "$WRITE_IOS"  = ""  ]
		then

			WRITE_IOS=$linea

		else

			WRITE_IOS=`echo "$WRITE_IOS + $linea" | bc`

		fi

	done

fi

if [ "$OS_NAME" = "Linux" ]
then

	READ_IOS=`vmstat -D | grep "merged reads" | awk '{print $1}'`
	WRITE_IOS=`vmstat -D | grep "merged writes" | awk '{print $1}'`

fi

if [[ "$OS_NAME" = "Linux" || "$OS_NAME" = "SunOS" ]]
then

	FINAL_RW_IOS=`echo "$READ_IOS + $WRITE_IOS" | bc`

fi

echo $FINAL_RW_IOS
