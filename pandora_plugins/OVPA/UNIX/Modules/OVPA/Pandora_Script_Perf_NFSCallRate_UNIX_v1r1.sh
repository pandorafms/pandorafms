#!/bin/sh

OS_NAME=`uname`

if [[ "$OS_NAME" = "HP-UX" || "$OS_NAME" = "AIX" ]]
then

	NFSCOMMAND=`nfsstat | grep Version | awk '{print $(NF-1)}' | tr -d "\("`

fi

if [ "$OS_NAME" = "SunOS" ]
then

	NFSCOMMAND=`kstat -m nfs -s calls | grep calls | awk '{print $NF}'`

fi

if [ "$OS_NAME" = "Linux" ]
then

	NFSCOMMAND=`nfsstat | grep -v r | grep "." | awk '{print $1}'`

fi

for line in $NFSCOMMAND
do
	if [ "$NFSCALLS" = "" ]
	then
		NFSCALLS=$line
	else
		NFSCALLS=`echo "$NFSCALLS + $line" | bc`
	fi
done

echo $NFSCALLS

