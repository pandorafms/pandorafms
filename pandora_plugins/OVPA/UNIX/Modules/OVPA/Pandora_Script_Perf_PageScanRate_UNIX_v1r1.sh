#!/bin/sh

OS_NAME=`uname`

if [ "$OS_NAME" = "HP-UX" ]
then

        PAGESCANRATE=`vmstat -s | grep "pages scanned for page out" | awk '{print $1}'`

fi

if [ "$OS_NAME" = "AIX" ]
then

	PAGESCANRATE=`vmstat -s | grep "pages examined by clock" | awk '{print $1}'`

fi

if [ "$OS_NAME" = "SunOS" ]
then

	PAGESCANRATE=`vmstat -s | grep "pages examined by the clock daemon" | awk '{print $1}'`

fi

if [ "$OS_NAME" = "Linux" ]
then

	PAGESCANRATE=`grep -R "pgscan_kswapd_normal" /proc/vmstat | awk '{print $NF}'`

fi

echo $PAGESCANRATE
