#!/bin/bash
# Copyright (c) 2005-2009 Artica ST
# Author: Sancho Lerena <slerena@artica.es> 2009
# Licence: GPL2
#
# daemon_watchdog
#
# Generic watchdog to detect if a daemon is running. If cannot restart, execute
# a custom-user defined command to notify daemon is down and continues in
# standby (without notifying / checking) until daemon is alive again.

# Default configuration is for Pandora FMS Server daemon

# =====================================================================
# Configuration begins here. Please use "" if data contain blank spaces

export DAEMON_WATCHDOG=pandora_watchdog.sh
# DAEMON_WATCHDOG: Name of this script. Used to check if its running already

export DAEMON_CHECK="/usr/bin/pandora_server /etc/pandora/pandora_server.conf"
# DAEMON_CHECK: Daemon monitored, please use full path and parameters like
#               are shown doing a ps aux of ps -Alf

export DAEMON_RESTART="/etc/init.d/pandora_server restart"
# DAEMON_RESTART: Command to try to restart the daemon

export DAEMON_DEADWAIT=90
# DAEMON_DEADWAIT: Time this script checks after detect that
#                  daemon is down before to consider is really down. 

export DAEMON_ALERT="/usr/bin/pandora_alert"
# DAEMON_ALERT: Command/Script executed if after detecting daemon is down,
#               and waiting DAEMON_DEADWAIT, and daemon continues down.

export DAEMON_LOOP=7
# DAEMON_LOOP: Interval within daemon_wathdog checks if daemon is alive.
#              DO NOT use values under 3-5 seconds or could be CPU consuming.
#              NEVER NEVER NEVER use 0 value or gets 100% CPU!.

# Configuration stop here
# =====================================================================

# Check if another instance of this script

RUNNING_CHECK=`ps aux | grep "$DAEMON_WATCHDOG" | grep -v grep |wc -l`
if [ $RUNNING_CHECK -gt 2 ]
then
        echo "Aborting, seems that there are more '$DAEMON_WATCHDOG' running in this system"
        logger $DAEMON_WATCHDOG aborted execution because another watchdog seems to be running
        exit -1
fi


# This value always must be 0 at start. Do not alter
export DAEMON_STANDBY=0

# This function replace pidof, not working in the same way in different linux distros
function pidof_daemon () (
        # This sets COLUMNS to XXX chars, because if command is run
        # in a "strech" term, ps aux don't report more than COLUMNS
        # characters and this will not work.
        COLUMNS=300
        DAEMON_PID=`ps aux | grep "$DAEMON_CHECK" | grep -v grep | tail -1 | awk '{ print $2 }'`
        echo $DAEMON_PID
)

# Main script

if [ ! -f `echo $DAEMON_CHECK | awk '{ print $1 }'` ]
then
        echo "Daemon you want to check is not present in the system. Aborting watchdog"
        exit
fi

while [ 1 ]
do

        DAEMON_PID=`pidof_daemon`
        if [ -z "$DAEMON_PID" ]
        then

echo "Checkpoint #1 $DAEMON_PID "

                if [ $DAEMON_STANDBY == 0 ]
                then

                        # Daemon down, first detection
                        # Restart it !

                        logger $DAEMON_WATCHDOG restarting $DAEMON_CHECK
                        $DAEMON_RESTART 2> /dev/null > /dev/null

                        # Just WAIT another DAEMON_DEADWAIT before consider it DEAD

echo "Going to DAEMON_DEADEWAIT"

                        sleep $DAEMON_DEADWAIT
                        DAEMON_PID=`pidof_daemon`

                        if [ -z "$DAEMON_PID" ]
                        then

                                # Is dead and can't be restarted properly. Execute alert

echo "I cannot startup again the process"

                                logger $DAEMON_WATCHDOG $DAEMON_CHECK is dead, alerting !
                                $DAEMON_ALERT  2> /dev/null > /dev/null

                                # Watchdog process puts in STANDBY mode until process get alive again
                                logger $DAEMON_WATCHDOG "Entering in Stabdby mode"

                                DAEMON_STANDBY=1
                        fi
                fi
        else

echo "Checkpoint #1B $DAEMON_PID "

                DAEMON_STANDBY=0
        fi

        sleep $DAEMON_LOOP
done
