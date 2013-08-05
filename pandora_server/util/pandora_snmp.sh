#!/bin/bash
TARGET=127.0.0.1
while [ 1 ]
do
   snmptrap -v 1 -c public $TARGET .1.3.6.1.4.1.2789.2005 192.168.5.2 6 "$RANDOM" 1233433 .1.3.6.1.4.1.2789.2005.1 s "$RANDOM"
done
