#!/bin/sh

OS_NAME=`uname`

if [ "$OS_NAME" = "AIX" ]
then

	COMANDO=`netstat -i | tail +2 | grep -v lo | grep "." | awk '{print $(NF-2)"\n"$(NF-3)}' | sort -n`

fi

if [ "$OS_NAME" = "HP-UX" ]
then

        COMANDO=`netstat -i | tail +2 | grep -v lo | grep "." | awk '{print $NF"\n"$(NF-1)}' | sort -n`

fi

if [ "$OS_NAME" = "SunOS" ]
then

	COMANDO=`netstat -i | tail +2 | grep -v loopback | grep "." | awk '{print $(NF-3)"\n"$(NF-5)}' | sort -n`

fi

if [ "$OS_NAME" = "Linux" ]
then

	COMANDO=`ifconfig -a | grep error | awk '{print $2}' | awk -F ":" '{print $NF}' | sort -n`
	COMANDOB=`ifconfig lo | grep error | awk '{print $2}' | awk -F ":" '{print $NF}' | sort -n`

fi

for line in $COMANDO
do
	if [ "$PKTVALUE" = "" ]
	then
		PKTVALUE=$line
		PKTCHECK=$line
	else
		if [ "$line" != "$PKTCHECK" ]
		then
			PKTVALUE=`echo "$PKTVALUE + $line" | bc`
			PKTCHECK=$line
		fi
	fi
done

if [ "$OS_NAME" = "Linux" ]
then

	for linea in $COMANDOB
	do
		if [ "$PKTVALUEF" = "" ]
		then
			PKTVALUEF=$linea
			PKTCHECKF=$linea
		else
			if [ "$linea" != "$PKTCHECKF" ]
			then
				PKTVALUEF=`echo "$PKTVALUEF + $linea" | bc`
				PKTCHECKF=$linea
			fi
		fi
	done

fi

if [[ "$OS_NAME" = "SunOS" || "$OS_NAME" = "HP-UX" || "$OS_NAME" = "AIX" ]]
then

	PKTREALVALUE=$PKTVALUE

fi

if [ "$OS_NAME" = "Linux" ]
then

	PKTREALVALUE=`echo "$PKTVALUE - $PKTVALUEF" | bc`

fi

echo $PKTREALVALUE

