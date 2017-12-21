#!/bin/sh

OS_NAME=`uname`

if [ "$OS_NAME" = "AIX" ]
then

	USEDSWAP=`vmstat -s | grep "pages out" | awk '{print $1}'`

fi

if [[ "$OS_NAME" = "SunOS" || "$OS_NAME" = "HP-UX" ]]
then

	USEDSWAP=`vmstat -s | grep "swap outs" | awk '{print $1}'`

fi

if [ "$OS_NAME" = "Linux" ]
then

	UNIT=$1

	if [ ! $1 ]
	then

		USEDSWAP=`vmstat -s | grep "used swap" | awk '{print $1}'`

	else

		USEDSWAP=`vmstat -s -S $UNIT | grep "used swap" | awk '{print $1}'`

	fi

fi


echo $USEDSWAP

