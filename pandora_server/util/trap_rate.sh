#!/bin/bash
# Calculate the rate of SNMP traps received by snmptrapd.
TEMP_FILE="/tmp/trap_rate.tmp"
COUNT="100"

# Parse command line arguments
if [ "$1" == "" ]; then
	echo "Usage: $0 <path to pandora_server.conf> [trap count]"
	exit 1
fi

if [ "$2" != "" ]; then
        COUNT="$2"
fi

# Read the SNMP log file and generate the temporary file
SNMP_LOG=`grep snmp_logfile $1 | cut -d' ' -f2`
if [ ! -f "$SNMP_LOG" ]; then
	echo "SNMP log file $SNMP_LOG does not exists or is not readable."
	exit 1
fi
grep "SNMPv" "$SNMP_LOG" | tail -$COUNT | cut -d']' -f 3 | cut -d'[' -f 1 > "$TEMP_FILE"

# Get the newest trap
START=`head -1 "$TEMP_FILE"`
if [ "$START" == "" ]; then
	echo "START: 0 END: 0 TRAPS RECEIVED: 0 RATE: 0 traps/s"
	exit 0
fi

# Get the oldest trap
END=`tail -1 "$TEMP_FILE"`
if [ "$END" == "" ]; then
	echo "START: 0 END: 0 TRAPS RECEIVED: 0 RATE: 0 traps/s"
	exit 0
fi

# Get the trap count
COUNT=`cat "$SNMP_LOG" | wc -l`

# Calculate the trap rate
START_UTIME=`date +"%s" -d"$START"`
END_UTIME=`date +"%s" -d"$END"`
ELAPSED=$(($END_UTIME - $START_UTIME))
RATE=`bc -l <<< "$COUNT / $ELAPSED"`

echo "START: $START END: $END TRAPS RECEIVED: $COUNT RATE: $RATE traps/s"
rm -f "$TEMP_FILE"

