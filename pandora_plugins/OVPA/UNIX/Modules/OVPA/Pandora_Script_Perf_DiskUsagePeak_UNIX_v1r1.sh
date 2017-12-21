#!/bin/sh

OS_NAME=`uname -a | awk '{print $1}'`

if [ "$OS_NAME" = "AIX" ]
then

	USEDDISK=`iostat -d 1 5 | tail +3 | awk '{print $2}' | tr "," "." | sort -n | tail -1`

fi

if [ "$OS_NAME" = "HP-UX" ]
then

	DISKCOUNT=`sar -d 5 | tail +5 | wc -l`
	USEDDISK=`sar -d 5 | tail +5 | awk '{print $3}' | sort -n | tail -1`

fi

if [ "$OS_NAME" = "SunOS" ]
then

	USEDDISK=`iostat -Dr 1 5 | tail -1 | tr "," "\n" | grep "\." | sort -n | tail -1`

fi

if [ "$OS_NAME" = "Linux" ]
then

	USEDDISK=`iostat -dx | tail -n +4 | awk '{print $NF}' | sed -e s/,/\./g | sort -n | tail -1`

fi

echo $USEDDISK

