#!/bin/bash

if [ -z $1 ]
then
	echo "I need two parameter: name of file with FULL lines, and name of file with less lines than first"  
	exit
fi


cat $1 |  grep "^\\$" | cut -f 2 -d "\"" > extract_newlines.tmp
TOTAL=`wc -l extract_newlines.tmp | awk '{ print $1 }'`
NEWLINES=0


for a in `cat extract_newlines.tmp`
do

	if [ -z "$(grep \"$a\" $2)" ]
	then
		echo "Newline for $a"
		grep \"$a\" $1 >> $2_newlines
		NEWLINES=`expr $NEWLINES + 1`
	fi	
done

echo ""
echo "TOTAL LINES=$TOTAL"
echo "NEW LINES=$NEWLINES"
echo "New lines written to $2_newlines"
echo ""

rm extract_newlines.tmp
