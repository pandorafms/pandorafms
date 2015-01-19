#!/bin/bash

# (c) Sancho Lerena 2014
# This is an script to generate random SNMP traps. Should be used to 
# test SNMP trap processing performance on Pandora FMS Server.
# Licensed under BSD licence, do with it whatever you want :-)

TRAPS=$1
TARGET=$2
OIDBASE="1.3.6.1.4.1"
SOURCE=$3

if [ $# -lt 2 ]
then
	echo " "
	echo "Syntax error: "
	echo "SNMP Trap generator use: ./snmptrap_gen.sh <# No traps> <target ip> [<source_ip>]"
	echo "If <source_ip> is not provided, it will forge fake IP's"
	echo " "
	exit -1
fi

COUNTER=0

while [ $COUNTER -lt $TRAPS ]
do

	RAND=`date +%N`
	SMALLRAND=`date +%N|cut -c 2-5`
	FAKEOID=""
	if [ "$SOURCE" == "" ]
	then
		FAKEIP=`date +%N | cut -c 2-3`.`date +%N | cut -c 2-3`.`date +%N | cut -c 2-3`.`date +%N | cut -c 2-3`
	else
		FAKEIP=$SOURCE
	fi

	# Create a fake OID with random data using Enterprise base OID
	for (( i=0; i<${#RAND}; i++ )); do
  		FAKEOID=${RAND:$i:1}.$FAKEOID
	done

 	FAKEOID=$OIDBASE.$FAKEOID"1"

	# Send the fake TRAP
	snmptrap -v 1 -c public $TARGET $FAKEOID $FAKEIP 6 $SMALLRAND $RAND $FAKEOID".$COUNTER" i $SMALLRAND
	echo "snmptrap -v 1 -c public $TARGET $FAKEOID $FAKEIP 6 $SMALLRAND $RAND $FAKEOID.$COUNTER i $SMALLRAND"

	COUNTER=`expr $COUNTER + 1`

done
