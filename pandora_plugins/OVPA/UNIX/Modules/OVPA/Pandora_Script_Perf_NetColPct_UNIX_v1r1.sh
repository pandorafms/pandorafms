#!/bin/sh

OS_NAME=`uname`

if [ "$OS_NAME" = "HP-UX" ]
then

        COMANDOCOUNT=`netstat -i | tail +2 | grep "." | grep -v lo | awk '{print $NF}' | wc -l`
	COLISIONS=0
	COUNT=0

	while [ "$COUNT" -lt "$COMANDOCOUNT" ]
	do

		for i in `echo lan ppa $COUNT display quit | /usr/sbin/lanadmin -t 2> /dev/null | grep -i collision | cut -d= -f2`
		do
			COLISIONS=`echo "$COLISIONS + $i" | bc`
		done

		COUNT=`echo "$COUNT + 1" | bc`

	done

	COMANDO=`netstat -i | tail +2 | grep "." | grep -v lo | awk '{print $NF}' | sort -n`

fi

if [ "$OS_NAME" = "SunOS" ]
then
	COMANDO=`netstat -i | tail +2 | grep "." | awk '{print $(NF-3)}' | sort -n`
	
	COLISIONS=`netstat -i | tail +2 | grep "." | awk '{print $(NF-1)}' | sort -n`
fi

if [ "$OS_NAME" = "AIX"	]
then
	COMANDO=`netstat -i | tail +2 | grep "." | awk '{print $(NF-2)}' | sort -n`

        COLISIONS=`netstat -i | tail +2 | grep "." | awk '{print $NF}' | sort -n`

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

	for linea in $COLISIONS
	do
        	if [ "$PKTVALUEF" = "" ]
        	then
                	PKTVALUEF=$linea
                	PKTCHECKF=$linea
        	else
                	if [ "$line" != "$PKTCHECKF" ]
                	then
                        	PKTVALUEF=`echo "$PKTVALUEF + $linea" | bc`
                        	PKTCHECKF=$linea
                	fi
        	fi
	done

fi

if [ "$OS_NAME" = "Linux" ]
then

	COMANDO=`ifconfig -a | grep TX | awk '{print $2}' | awk -F ":" '{print $NF}' | sort -n`

	COLISIONS=`ifconfig -a | grep col | awk '{print $1}' | awk -F ":" '{print $NF}' | sort -n`
fi

if [ "$OS_NAME" != "AIX" ]
then

	for line in $COMANDO
	do
		if [ "$PKTVALUE" = "" ]
		then
			PKTVALUE=$line
		else
			PKTVALUE=`echo "$PKTVALUE + $line" | bc`
		fi
	done

	#COMANDOB=`ifconfig lo | grep error | awk '{print $2}' | awk -F ":" '{print $NF}'`

	for linea in $COLISIONS
	do
		if [ "$PKTVALUEF" = "" ]
		then
			PKTVALUEF=$linea
		else
			PKTVALUEF=`echo "$PKTVALUEF + $linea" | bc`
		fi
	done

fi

PKTTOTALVALUE=`echo "$PKTVALUE + $PKTVALUEF" | bc`

PKTCOLPCT=`echo "scale=2; $PKTVALUEF * 100 / $PKTTOTALVALUE" | bc`

echo $PKTCOLPCT

