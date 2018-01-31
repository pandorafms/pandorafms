#!/bin/sh

PAGESOUT=`vmstat -s | grep "pages paged out" | awk '{print $1}'`
PAGESIN=`vmstat -s | grep "pages paged in" | awk '{print $1}'`

echo "$PAGESOUT + $PAGESIN" | bc

