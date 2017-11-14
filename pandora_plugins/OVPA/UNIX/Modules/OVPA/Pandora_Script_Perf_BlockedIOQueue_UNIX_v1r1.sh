#!/bin/sh

BLOCKEDIOQUEUE=`vmstat 1 5 | tail -1 | awk '{print $2}'`

echo $BLOCKEDIOQUEUE
