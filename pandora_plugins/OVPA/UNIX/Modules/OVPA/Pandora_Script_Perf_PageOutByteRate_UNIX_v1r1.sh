#!/bin/sh

OS_NAME=`uname`
PAGESIZE=`getconf PAGESIZE`

UNIT=$1

if [ "$UNIT" = "K" ]
then

	PAGESOUT=`vmstat -s | grep "pages paged out" | awk '{print $1}'`
	PAGESIZEUNIT=`echo "scale=4; $PAGESIZE / 1024" | bc`

fi

if [ "$UNIT" = "M" ]
then

	PAGESOUT=`vmstat -s -S $UNIT | grep "pages paged out" | awk '{print $1}'`
	PAGESIZEUNIT=`echo "scale=4; $PAGESIZE / 1024 / 1024" | bc`

fi


echo "$PAGESOUT * $PAGESIZEUNIT" | bc

