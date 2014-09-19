#!/bin/bash

# (c) 2014 Sancho Lerena
# (c) Artica Soluciones Tecnologicas

# Packet loss ICMP measurement remote plugin

# Remote plugin to measure remote packet loss using ping
# It requires root access, because use flood mode to send many pings.
# It's limited to 50 pings and 10 seconds, so it should not be nasty
# for your network :-)

if [ $# -eq 0 ] || [ $# -eq 1 ]
then
        echo "Syntax:  <max_timeout_insecs> <target_ip>"
        exit -1
fi

TIMEOUT=$1
DESTINATION=$2

echo `ping -W $1 -q -f -c 50 $2 | grep -o "[0-9\.]*. packet loss" | grep -o "[0-9.]*"`


