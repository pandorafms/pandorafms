#!/bin/bash

# This script check for currently implemented ENGLISH help files and detect missing help files in other languages (passed as command line parameter)

if [ -z "$1" ]
then
	echo "Check missing help files. Needed language code for search"
	echo "For example: es "
	exit
fi

if [ ! -d "include/help/en" ]
then
	echo "I need to run in Pandora FMS Console root directory"
	exit
fi

mkdir "/tmp/$1" >> /dev/null 2> /dev/null
for a in `ls include/help/en`
do 
	ESTA=`find include/help/$1/$a 2> /dev/null | wc -l`
	if [ $ESTA == 0 ]
	then 
		echo "Missing $a, and copying to /tmp/$1"
		cp include/help/en/$a "/tmp/$1"
       	fi 
done 

