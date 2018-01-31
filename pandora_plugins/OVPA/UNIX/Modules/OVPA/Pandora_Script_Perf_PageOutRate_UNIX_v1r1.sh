#!/bin/sh

PAGESOUT=`vmstat -s | grep "pages paged out" | awk '{print $1}'`

echo $PAGESOUT

