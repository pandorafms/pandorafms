#!/bin/bash
# **********************************************************************
# Pandora FMS Generic Host Agent
# GNU/Linux version 
# (c) Sancho Lerena 2003-2006, <slerena@artica.es> 
# with the help of many people. Please see http://pandora.sourceforge.net
# Este codigo esta licenciado bajo la licencia GPL 2.0.
# This code is licensed under GPL 2.0 license.
# **********************************************************************
AGENT_VERSION=1.2
AGENT_BUILD=061123

IFS=$'\n'
# Begin cycle for adquire primary config tokens
TIMESTAMP=`date +"%Y/%m/%d %H:%M:%S"`

if [ -z "$1" ]
then
	echo " "
	echo "FATAL ERROR: I need an argument to PANDORA AGENT home path"
 	echo " "
 	echo " example:   /opt/pandora_ng/pandora_agent.sh /opt/pandora_ng  "
 	echo " "
 	exit -1
else
 	PANDORA_HOME=$1
fi

if [ ! -f $PANDORA_HOME/pandora_agent.conf ]
then
	echo " "
	echo "FATAL ERROR: Cannot load pandora_agent.conf"
	echo " "
	exit -1
fi

# Default values

CHECKSUM_MODE=1
DEBUG_MODE=0
CONTADOR=0
EXECUTE=1
MODULE_END=0

echo "$TIMESTAMP - Reading general config parameters from .conf file" >> $PANDORA_HOME/pandora.log
for a in `cat $PANDORA_HOME/pandora_agent.conf | grep -v -e "^#" | grep -v -e "^module" `
do
        a=`echo $a | tr -s " " " "`

        # Get general configuration parameters from config file
        if [ ! -z "`echo $a | grep -e '^server_ip'`" ]
        then
                SERVER_IP=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Server IP Address is $SERVER_IP" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep -e '^server_path'`" ]
        then
                SERVER_PATH=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Server Path is $SERVER_PATH" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep -e '^temporal'`" ]
        then
                TEMP=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Temporal Path is $TEMP" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep -e '^interval'`" ]
        then
                INTERVAL=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Interval is $INTERVAL seconds" >> $PANDORA_HOME/pandora.log
        fi
 	if [ ! -z "`echo $a | grep -e '^agent_name'`" ]
        then
               NOMBRE_HOST=`echo $a | awk '{ print $2 }' `
         	echo "$TIMESTAMP - [SETUP] - Agent name is $NOMBRE_HOST " >> $PANDORA_HOME/pandora.log
        fi
 	if [ ! -z "`echo $a | grep -e '^debug'`" ]
        then
               DEBUG_MODE=`echo $a | awk '{ print $2 }' `
         	echo "$TIMESTAMP - [SETUP] - Debug mode is $DEBUG_MODE " >> $PANDORA_HOME/pandora.log
        fi
 	if [ ! -z "`echo $a | grep -e '^checksum'`" ]
        then
               CHECKSUM_MODE=`echo $a | awk '{ print $2 }' `
         	echo "$TIMESTAMP - [SETUP] - Checksum is $CHECKSUM_MODE " >> $PANDORA_HOME/pandora.log
        fi
done


# MAIN Program loop begin

# Get Linux Distro type and version

# SUSE
if [ -f "/etc/SuSE-release" ]
then
  OS_VERSION=`cat /etc/SuSE-release | grep VERSION | cut -f 3 -d " "`
  LINUX_DISTRO=SUSE
else
    if [ -f "/etc/lsb-release" ]
    then
        OS_VERSION=`cat /etc/lsb-release | grep DISTRIB_RELEASE | cut -f 2 -d "="`
        LINUX_DISTRO=UBUNTU
    else
        if [ -f "/etc/debian_version" ]
        then
            OS_VERSION=`cat /etc/debian_version`
            OS_VERSION="DEBIAN $OS_VERSION"
            LINUX_DISTRO=DEBIAN
        else
            LINUX_DISTRO=GENERIC
            OS_VERSION=`uname -r`
	
		if [ -f "/etc/fedora-release" ]
			then
		   	OS_VERSION=`cat /etc/fedora-release | cut -f 4 -d " "`
		   	OS_VERSION="FEDORA $OS_VERSION"
		   	LINUX_DISTRO=FEDORA
           fi	   
        fi
    fi
fi
# OS Data
OS_NAME=`uname -s`

# Hostname
if [ -z "$NOMBRE_HOST" ] 
then 
 NOMBRE_HOST=`/bin/hostname`
fi

while [ "1" == "1" ]
do

 	# Date and time, SERIAL is number of seconds since 1/1/1970, for every packet.
 	TIMESTAMP=`date +"%Y/%m/%d %H:%M:%S"`
 	SERIAL=`date +"%s"`
 
 	# File names
 	DATA=$TEMP/$NOMBRE_HOST.$SERIAL.data
 	DATA2=$TEMP/$NOMBRE_HOST.$SERIAL.data_temp
 	CHECKSUM=$TEMP/$NOMBRE_HOST.$SERIAL.checksum
 	PANDORA_FILES="$TEMP/$NOMBRE_HOST.$SERIAL.*"
 
 	# Makes data packet
 	echo "<agent_data os_name='$OS_NAME' os_version='$OS_VERSION' interval='$INTERVAL' version='$AGENT_VERSION' timestamp='$TIMESTAMP' agent_name='$NOMBRE_HOST'>" > $DATA
 	if [ "$DEBUG_MODE" == "1" ]
 	then
  		echo "$TIMESTAMP - Reading module adquisition data from .conf file" >> $PANDORA_HOME/pandora.log
 	fi
 	for a in `cat $PANDORA_HOME/pandora_agent.conf | grep -v -e "^#" | grep -e "^module" ` 
 	do
  		a=`echo $a | tr -s " " " "`
 
         	if [ ! -z "`echo $a | grep -e '^module_exec'`" ]
         	then
			if [ $EXECUTE -eq 0 ]
			then
	            		execution=`echo $a | cut -c 13- `
            			res=`eval $execution`
            			if [ -z "$flux_string" ]
            			then
	             			res=`eval expr $res 2> /dev/null`
     				fi
            			echo "<data><![CDATA[$res]]></data>" >> $DATA2
			fi
         	fi
 
         	if [ ! -z "`echo $a | grep -e '^module_name'`" ]
         	then
            		name=`echo $a | cut -c 13- `
     			echo "<name>$name</name>" >> $DATA2
         	fi
		
		if [ ! -z "`echo $a | grep -e '^module_begin'`" ]
		then
			echo "<module>" >> $DATA2
			EXECUTE=0
		fi
		
		if [ ! -z "`echo $a | grep -e '^module_max' `" ]
		then
			max=`echo $a | awk '{ print $2 }' `
			echo "<max>$max</max>" >> $DATA2
		fi

		if [ ! -z "`echo $a | grep -e '^module_min'`" ]
		then
			min=`echo $a | awk '{ print $2 }' `
			echo "<min>$min</min>" >> $DATA2
		fi
		
		if [ ! -z "`echo $a | grep -e '^module_description'`" ]
		then
			desc=`echo $a | cut -c 20- `
			echo "<description>$desc</description>" >> $DATA2
		fi
  
         	if [ ! -z "`echo $a | grep -e '^module_end'`" ]
         	then
         	   	echo "</module>" >> $DATA2
			MODULE_END=1
		else
			MODULE_END=0
         	fi
 
         	if [ ! -z "`echo $a | grep -e '^module_type'`" ]
         	then
            		mtype=`echo $a | awk '{ print $2 }' `
            		if [ ! -z "`echo $mtype | grep 'generic_data_string'`" ]
     			then
   				flux_string=1
     			else
                 		flux_string=0
                 		unset flux_string
            		fi
            		echo "<type>$mtype</type>" >> $DATA2
         	fi
		
  		if [ ! -z "`echo $a | grep '^module_interval'`" ]
  		then
              		# Determine if execution is to be done
              		MODULEINTERVAL=`echo $a | awk '{ print $2 }'`
              		EXECUTE=`expr \( $CONTADOR + 1 \) % $MODULEINTERVAL`
  		fi

		# If module ends, and execute for this module is enabled
		# then write 

		if [ $MODULE_END -eq 1 ]
		then
			if [ $EXECUTE -eq 0 ]
			then
				cat $DATA2 >> $DATA
			fi
			rm -Rf $DATA2 > /dev/null 2> /dev/null
		fi
	done
	
	# Count number of agent runs
	CONTADOR=`expr $CONTADOR + 1`
	# Keep a limit of 100 for overflow reasons
	if [ $CONTADOR -eq 100 ]
	then
		CONTADOR=0
	fi

	# Call for user-defined script for data adquisition
	
	if [ -f "$PANDORA_HOME/pandora_user.conf" ]
	then
		/bin/bash $PANDORA_HOME/pandora_user.conf >> $DATA
	fi
	
	# Finish data packet
	echo "</agent_data>" >> $DATA
	if [ "$DEBUG_MODE" == "1" ]
	then
		echo "$TIMESTAMP - Finish writing XML $DATA" >> $PANDORA_HOME/pandora.log
	fi
	
	if [ "$CHECKSUM_MODE" == "1" ]
	then
		# Calculate Checksum and prepare MD5 file
		CHECKSUM_DATA=`/usr/bin/md5sum $DATA`
		echo $CHECKSUM_DATA > $CHECKSUM 
	else
		CHECKSUM_DATA="No valid checksum"
		echo $CHECKSUM_DATA > $CHECKSUM
	fi
	
	# Send packets to server and detele it
 	scp $PANDORA_FILES pandora@$SERVER_IP:$SERVER_PATH > /dev/null 2> /dev/null

	if [ "$DEBUG_MODE" == "1" ]
	then
		echo "$TIMESTAMP - Copying $PANDORA_FILES to $SERVER_IP:$SERVER_PATH" >> $PANDORA_HOME/pandora.log
 	else
		# Delete it
		rm -f $PANDORA_FILES> /dev/null 2> /dev/null
	fi
	
	# Go to bed :-)
	sleep $INTERVAL
	

done 
# This runs forever! 
