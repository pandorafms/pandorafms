#!/bin/bash
# Copyright (c) 2005-2019 Artica ST
#
# /etc/init.d/websocket
#
# System startup script for Pandora FMS Console websocket engine
#
# Comments to support chkconfig on RedHat Linux
# chkconfig: 2345 90 10
# description: Pandora FMS Console webscoket engine startup script
#
# Comments to support LSB init script conventions
### BEGIN INIT INFO
# Provides:       websocket
# Required-Start: $syslog cron
# Should-Start:   $network cron mysql
# Required-Stop:  $syslog 
# Should-Stop:    $network 
# Default-Start:  2 3 5
# Default-Stop:   0 1 6
# Short-Description: Pandora FMS Console websocket engine startup script
# Description:    Pandora FMS Console websocket engine startup script
### END INIT INFO

if [ -x /lib/lsb/init-functions ]; then
. /lib/lsb/init-functions
fi

# If you want to run several pandora Console Websocket engines in this machine, just copy 
# this script to another name, editing PANDORA_HOME to the new .conf 

export WS_ENGINE="/var/www/html/pandora_console/ws.php"
export PHP=/usr/bin/php
export WS_LOG="/var/www/html/pandora_console/pandora_console.log"
export GOTTY="/tmp/"

# Environment variables
if [[ -z ${PANDORA_RB_PRODUCT_NAME} ]]; then
	PANDORA_RB_PRODUCT_NAME="Pandora FMS"
fi
if [[ -z ${PANDORA_RB_COPYRIGHT_NOTICE} ]]; then
	PANDORA_RB_COPYRIGHT_NOTICE="Artica ST"
fi

export PANDORA_RB_PRODUCT_NAME=$PANDORA_RB_PRODUCT_NAME
export PANDORA_RB_COPYRIGHT_NOTICE=$PANDORA_RB_COPYRIGHT_NOTICE

# Uses a wait limit before sending a KILL signal, before trying to stop
# Pandora FMS Console Websocket engine nicely. Some big systems need some time before close
# all pending tasks / threads.

export MAXWAIT=60

# Check for SUSE status scripts
if [ -f /etc/rc.status ]
then
	. /etc/rc.status
	rc_reset
else
	# Define part of rc functions for non-suse systems
	function rc_status () {
		RETVAL=$?
		case $1 in
			-v) RETVAL=0;;
		esac
	}
	function rc_exit () { exit $RETVAL; }
	function rc_failed () { RETVAL=${1:-1}; }
	RETVAL=0
fi
	
# This function replace pidof, not working in the same way in different linux distros

function pidof_pandora () {
	# This sets COLUMNS to XXX chars, because if command is run 
	# in a "strech" term, ps aux don't report more than COLUMNS
	# characters and this will not work. 
	COLUMNS=300
	PANDORA_PID=`ps aux | grep "$PHP $WS_ENGINE" | grep -v grep | tail -1 | awk '{ print $2 }'`
	echo $PANDORA_PID
}

# Main script

if [ ! -x $GOTTY ]
then
	echo "Gotty not found in $GOTTY"
	rc_failed 5 # program is not installed
	rc_exit
fi

if [ ! -f $PHP ]
then
	echo "$PHP not found, please install version >= 7.0"
	rc_failed 5 # program is not installed
	rc_exit
fi

case "$1" in
	start)
		PANDORA_PID=`pidof_pandora`
		if [ ! -z "$PANDORA_PID" ]
		then
			echo "$PANDORA_RB_PRODUCT_NAME Console Websocket engine is currently running on this machine with PID ($PANDORA_PID)."
			rc_exit # running start on a service already running
		fi

		export PERL_LWP_SSL_VERIFY_HOSTNAME=0
		$PHP $WS_ENGINE >> $WS_LOG 2>&1 &
		sleep 1

		PANDORA_PID=`pidof_pandora`
		
		if [ ! -z "$PANDORA_PID" ]
		then
			echo "$PANDORA_RB_PRODUCT_NAME Console Websocket engine is now running with PID $PANDORA_PID"
			rc_status -v
		else
			echo "Cannot start $PANDORA_RB_PRODUCT_NAME Console Websocket engine. Aborted."
			echo "Check $PANDORA_RB_PRODUCT_NAME log files at $WS_LOG"
			rc_failed 7 # program is not running
		fi
	;;
		
	stop)
		PANDORA_PID=`pidof_pandora`
		if [ -z "$PANDORA_PID" ]
		then
			echo "$PANDORA_RB_PRODUCT_NAME Console Websocket engine is not running, cannot stop it."
			rc_exit # running stop on a service already stopped or not running
		else
			echo "Stopping $PANDORA_RB_PRODUCT_NAME Console Websocket engine"
			kill $PANDORA_PID > /dev/null 2>&1
			COUNTER=0

			while [ $COUNTER -lt $MAXWAIT ]
	 		do
 				_PID=`pidof_pandora`
				if [ "$_PID" != "$PANDORA_PID" ]
 				then
					COUNTER=$MAXWAIT
				fi
				COUNTER=`expr $COUNTER + 1`
				sleep 1
			done
		
			# Send a KILL -9 signal to process, if it's alive after 60secs, we need
			# to be sure is really dead, and not pretending...
			if [ "$_PID" = "$PANDORA_PID" ]
			then
				kill -9 $PANDORA_PID   > /dev/null 2>&1
			fi
			rc_status -v
		fi
	;;
	status)
		PANDORA_PID=`pidof_pandora`
		if [ -z "$PANDORA_PID" ]
		then
			echo "$PANDORA_RB_PRODUCT_NAME Console Websocket engine is not running."
			rc_failed 7 # program is not running
		else
			echo "$PANDORA_RB_PRODUCT_NAME Console Websocket engine is running with PID $PANDORA_PID."
			rc_status
		fi
	;;
	force-reload|restart)
		$0 stop
		$0 start
		;;
	*)
		echo "Usage: $0 { start | stop | restart | status }"
		exit 1
esac
rc_exit
