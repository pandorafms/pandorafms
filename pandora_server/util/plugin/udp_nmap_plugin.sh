#!/bin/bash
# This is called like -p xxx -h xxxx
HOST=$4
PORT=$2
nmap -T5 -p $PORT -sU $HOST | grep open | wc -l
