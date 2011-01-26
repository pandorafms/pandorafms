#!/bin/bash

# **********************************************************************
# Pandora FMS Agent Simulator (MetaAgent)
# (c) 2009 Artica Soluciones Tecnológicas SL
# with the help of many people. Please see http://pandorafms.org
# This code is licensed under GPL 2.0 license.
# **********************************************************************

IFS=$'\n'
AGENT_VERSION=1.0_ma
# Begin cycle for adquire primary config tokens
TIMESTAMP=`date +"%Y/%m/%d %H:%M:%S"`

if [ -z "$1" ]
then
	echo " "
	echo "FATAL ERROR: I need an argument to PANDORA AGENT home path"
	echo " "
	echo " example:   /usr/share/pandora_ng/pandora_agent_sim.sh /usr/share/pandora_ng  "
	echo " "
	exit -1
else
	PANDORA_HOME=$1
fi

while [ "1" == "1" ]
do
sleep 5
for b in `ls $PANDORA_HOME/pandora_agent_?.conf`
do

    echo "$TIMESTAMP - Reading general config parameters from .conf file" >> $PANDORA_HOME/pandora.log
    for a in `cat $b | grep -v -e "^#" | grep -v -e "^module" `
    do
        a=`echo $a | tr -s " " " "`

        # Get general configuration parameters from config file
        if [ ! -z "`echo $a | grep -e '^server_ip'`" ]
        then
                SERVER_IP=`echo $a | cut -f 2 -d " "`
                echo "$TIMESTAMP - [SETUP] - Server IP Address is $SERVER_IP" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep -e '^server_path'`" ]
        then
                SERVER_PATH=`echo $a | cut -f 2 -d " "`
                echo "$TIMESTAMP - [SETUP] - Server Path is $SERVER_PATH" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep -e '^temporal'`" ]
        then
                TEMP=`echo $a | cut -f 2 -d " "`
                echo "$TIMESTAMP - [SETUP] - Temporal Path is $TEMP" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep -e '^interval'`" ]
        then
                INTERVAL=`echo $a | cut -f 2 -d " "`
                echo "$TIMESTAMP - [SETUP] - Interval is $INTERVAL seconds" >> $PANDORA_HOME/pandora.log
        fi
	if [ ! -z "`echo $a | grep -e '^agent_name'`" ]
        then
              	NOMBRE_HOST=`echo $a | cut -f 2 -d " "`
	        echo "$TIMESTAMP - [SETUP] - Agent name is $NOMBRE_HOST " >> $PANDORA_HOME/pandora.log
        fi
	if [ ! -z "`echo $a | grep -e '^agent_os'`" ]
        then
                OS_NAME=`echo $a | cut -f 2 -d " "`
                echo "$TIMESTAMP - [SETUP] - Agent SO is $OS_NAME " >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep -e '^agent_os_version'`" ]
        then
                OS_VERSION=`echo $a | cut -f 2 -d " "`
                echo "$TIMESTAMP - [SETUP] - Agent SO version is $OS_VERSION " >> $PANDORA_HOME/pandora.log
        fi
    done


# MAIN Program loop begin

# OS Data
if [ -z "$OS_VERSION" ]
then
   OS_VERSION=`uname -r`
   OS_NAME=`uname -s`
fi

# Hostname
if [ -z "$NOMBRE_HOST" ] 
then 
	NOMBRE_HOST=`/bin/hostname`
fi

	# Fecha y hora. Se genera un serial (numero de segundos desde 1970) para cada paquete generado.
	TIMESTAMP=`date +"%Y/%m/%d %H:%M:%S"`
	SERIAL=`date +"%s"`
	
	# Nombre de los archivos
	DATA=$TEMP/$NOMBRE_HOST.$SERIAL.data
	CHECKSUM=$TEMP/$NOMBRE_HOST.$SERIAL.checksum
	PANDORA_FILES="$TEMP/$NOMBRE_HOST.$SERIAL.*"

	# Makes data packet
	echo "<agent_data os_name='$OS_NAME' os_version='$OS_VERSION' interval='$INTERVAL' version='$AGENT_VERSION' timestamp='$TIMESTAMP' agent_name='$NOMBRE_HOST'>" > $DATA
	echo "$TIMESTAMP - Reading module adquisition data from .conf file" >> $PANDORA_HOME/pandora.log
	
	for a in `cat $b | grep -v -e "^#" | grep -e "^module" ` 
	do
		a=`echo $a | tr -s " " " "`
	
	        if [ ! -z "`echo $a | grep -e '^module_exec'`" ]
	        then
	           execution=`echo $a | cut -c 13- `
	           res=`eval $execution 2> /dev/null`
	           if [ -z "$flux_string" ]
	           then
	           	res=`eval expr $res 2> /dev/null`
		   fi
	           echo "<data>$res</data>" >> $DATA
	        fi
	
	        if [ ! -z "`echo $a | grep -e '^module_name'`" ]
	        then
	           name=`echo $a | cut -c 13- `
		   echo "<name>$name</name>" >> $DATA
	        fi
	
	        if [ ! -z "`echo $a | grep -e '^module_begin'`" ]
	        then
	           echo "<module>" >> $DATA
	        fi

		if [ ! -z "`echo $a | grep -e '^module_max' `" ]
		then
		   max=`echo $a | awk '{ print $2 }' `
		   echo "<max>$max</max>" >> $DATA
		fi
		if [ ! -z "`echo $a | grep -e '^module_min'`" ]
		then
		   min=`echo $a | awk '{ print $2 }' `
		   echo "<min>$min</min>" >> $DATA
		fi
		if [ ! -z "`echo $a | grep -e '^module_description'`" ]
		then
		   desc=`echo $a | cut -c 20- `
		   echo "<description>$desc</description>" >> $DATA
		fi
		
	        if [ ! -z "`echo $a | grep -e '^module_end'`" ]
	        then
	           echo "</module>" >> $DATA
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
	           echo "<type>$mtype</type>" >> $DATA
        	fi
	done

	# Call for user-defined script for data adquisition
	USER_EXT="_user"
	USER_FILE=$b$USER_EXT
	if [ -f "$USER_FILE" ]
	then
	   /bin/bash $USER_FILE >> $DATA
	fi

	# Finish data packet
	echo "</agent_data>" >> $DATA
	echo "$TIMESTAMP - Finish writing XML $DATA" >> $PANDORA_HOME/pandora.log

	# Calculate Checksum and prepare MD5 file
	CHECKSUM_DATA=`/usr/bin/md5sum $DATA`
        echo $CHECKSUM_DATA > $CHECKSUM
	# Send packets to server and detele it
	mv $PANDORA_FILES $SERVER_PATH > /dev/null 2> /dev/null
        echo "$TIMESTAMP - Copying $PANDORA_FILES to $SERVER_IP:$SERVER_PATH" >> $PANDORA_HOME/pandora.log
done
	sleep $INTERVAL
done 
# forever! 
