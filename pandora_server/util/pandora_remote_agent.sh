#!/bin/bash
# (c) 2011 Sancho Lerena <slerena@artica.es>
# This is a remote agent script, to use local plugins from a distant server.
# You can run this from a local pandora agent.
# Run with -h to see more information

# Configurable tokens
TEMP=/tmp
OUTPUTDIR=/var/spool/pandora/data_in
OS_NAME=Linux
ENCODING="iso-8859-1"
INTERVAL=300

function help {
	echo -e ""
	echo -e "Remote agent script for Pandora FMS. http://pandorafms.com" 
	echo -e "Syntax:" 
	echo -e "\t-a agent		Agent name as will be presented in the output XML"
	echo -e "\t-f scriptfile 	        Script file to execute. It must generate the XML for modules itself"
	echo -e "\t-e encoding		Character encoding of the agent name and scriptfile output (default: $ENCODING)"
	echo -e "\t-h			This help"
	echo ""
	exit
}

if [ $# -eq 0 ]
then
	help
fi

AGENT=""
SCRIPTFILE=""

while getopts ":h:a:f:e:" optname
  do
    case "$optname" in
      "h")
	        help
	;;
      "a")
		AGENT=$OPTARG
	;;
      "f")
		SCRIPTFILE=$OPTARG
	;;
      "e")
		ENCODING=$OPTARG
	;;
       ?)
		help
		;;
      default) 
		help
	;;
     
    esac
done

if [ -z "$AGENT" ]
then
    help
    exit
fi

if [ -z "$SCRIPTFILE" ]
then
    help
    exit
fi


# Date and time, SERIAL is number of seconds since 1/1/1970, for every packet.
TIMESTAMP=`date +"%Y/%m/%d %H:%M:%S"`
SERIAL=`date +"%s"`

# File names
DATA=$TEMP/$AGENT.$$.$SERIAL.data

# Makes data packet
echo "<?xml version=\"1.0\" encoding=\"$ENCODING\"?> " > $DATA
echo "<agent_data os_name='$OS_NAME' interval='$INTERVAL' version='4.0-Remote' timestamp='$TIMESTAMP' agent_name='$AGENT'>" >> $DATA

# Execute the script file
eval $SCRIPTFILE >> $DATA 

# Finish data packet
echo "</agent_data>" >> $DATA
echo "" >> $DATA

# Moving to target directory
mv $DATA $OUTPUTDIR

