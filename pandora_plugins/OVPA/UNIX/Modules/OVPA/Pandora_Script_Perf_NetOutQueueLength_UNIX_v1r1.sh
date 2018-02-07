#!/bin/sh

OS_NAME=`uname`

if [ "$OS_NAME" = "AIX" ]
then

	IFLIST=`ifconfig -a | grep ":" | grep -v inet6 | grep -v lo | awk -F ":" '{print $1}'`
	OUTQUEUELENGTH=0

	for line in $IFLIST
	do
		OUTQUEUELENGTHCOMMAND=`entstat $line | grep "Transmit Queue Length" | grep "S" | awk '{print $NF}'`
		OUTQUEUELENGTH=`echo "$OUTQUEUELENGTH + $OUTQUEUELENGTHCOMMAND" | bc`
	done

fi

if [ "$OS_NAME" = "HP-UX" ]
then

        COMANDOCOUNT=`netstat -i | tail +2 | grep "." | grep -v lo | awk '{print $NF}' | wc -l`
	OUTQUEUELENGTH=0
	COUNT=0

        while [ "$COUNT" -lt "$COMANDOCOUNT" ]
	do

                for i in `echo lan ppa $COUNT display quit | /usr/sbin/lanadmin -t 2> /dev/null | grep -i "Outbound queue length" | cut -d= -f2`
		do
                  	OUTQUEUELENGTH=`echo "$OUTQUEUELENGTH + $i" | bc`
		done

                COUNT=`echo "$COUNT + 1" | bc`

        done

fi

if [ "$OS_NAME" = "SunOS" ]
then
	OUTQUEUELENGTH=`netstat -i | tail +2 | grep "." | awk '{print $NF}'`
fi

if [ "$OS_NAME" = "Linux" ]
then

	OUTQUEUELENGTH=`ifconfig -a | grep col | awk '{print $NF}' | awk -F ":" '{print $NF}'`

fi

if [ "$OS_NAME" != "AIX" ]
then

	for line in $OUTQUEUELENGTH
	do
		if [ "$OUTQUEUELENGTHF" = "" ]
		then
			OUTQUEUELENGTHF=$line
		else
			OUTQUEUELENGTHF=`echo "$OUTQUEUELENGTHF + $line" | bc`
		fi
	done

else
	OUTQUEUELENGTHF=$OUTQUEUELENGTH

fi

echo $OUTQUEUELENGTHF

