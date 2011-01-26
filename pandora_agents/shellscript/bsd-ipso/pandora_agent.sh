#!/bin/sh
# **********************************************************************
# Pandora FMS Generic IPSO/HPUX
# (c) 2009 Artica Soluciones Tecnológicas SL
# with the help of many people. Please see http://pandorafms.org
# This code is licensed under GPL 2.0 license.
# **********************************************************************

AGENT_VERSION=1.3
BUILD_VERSION=070801

OLDIFS=$IFS
# Stupid trick to use IFS in some unix ... doesnt work linux standard $'\n' :-?
NEWIFS="
"

# Begin cycle for adquire primary config tokens
TIMESTAMP=`date +"%Y/%m/%d %H:%M:%S"`

if [ -z "$1" ]
then
        echo " "
        echo "FATAL ERROR: I need an argument to PANDORA AGENT home path"
        echo " "
        echo " example:   /usr/share/pandora_agent/pandora_agent.sh /usr/share/pandora_ng  "
  echo " "
        exit
else
  PANDORA_HOME=$1
fi

if [ -z "`cat $PANDORA_HOME/pandora_agent.conf`" ]
then
        echo " "
        echo "FATAL ERROR: Cannot load $PANDORA_HOME/pandora_agent.conf"
        echo " "
        exit
fi

echo "$TIMESTAMP - Reading general config parameters from .conf file" >> $PANDORA_HOME/pandora.log

# Default values
CHECKSUM_MODE=0
DEBUG_MODE=0
PANDORA_HARMLESS=1
INTERVAL=300


IFS=$NEWIFS
for a in `cat $PANDORA_HOME/pandora_agent.conf | grep -v "^#" | grep -v "^module" `
do
	if [ "$PANDORA_HARMLESS" = "1" ]
	then
		sleep 1
	fi

	a=`echo $a | tr -s " " " "`
        # Get general configuration parameters from config file
        if [ ! -z "`echo $a | grep '^server_ip'`" ]
        then
                SERVER_IP=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Server IP Address is $SERVER_IP" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep '^server_path'`" ]
        then
                SERVER_PATH=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Server Path is $SERVER_PATH" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep '^temporal'`" ]
        then
                TEMP=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Temporal Path is $TEMP" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep '^interval'`" ]
        then
                INTERVAL=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Interval is $INTERVAL seconds" >> $PANDORA_HOME/pandora.log
        fi
        if [ ! -z "`echo $a | grep '^agent_name'`" ]
                then
                NOMBRE_HOST=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Agent name is $NOMBRE_HOST " >> $PANDORA_HOME/pandora.log
        fi
	if [ ! -z "`echo $a | grep '^debug'`" ]
                then
                DEBUG_MODE=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Debug mode is $DEBUG_MODE " >> $PANDORA_HOME/pandora.log
        fi
	if [ ! -z "`echo $a | grep '^checksum'`" ]
                then
                CHECKSUM_MODE=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Checksum mode is $CHECKSUM_MODE " >> $PANDORA_HOME/pandora.log
        fi
	if [ ! -z "`echo $a | grep -e '^harmless_mode'`" ]
        then
                PANDORA_HARMLESS=`echo $a | awk '{ print $2 }' `
                echo "$TIMESTAMP - [SETUP] - Pandora Harmless mode is $PANDORA_HARMLESS" >> $PANDORA_HOME/pandora.log
        fi
done

# MAIN Program loop begin
# OS Data
OS_VERSION=`uname -r`
OS_NAME=`uname -s`
# Hostname
if [ -z "$NOMBRE_HOST" ]
then
        NOMBRE_HOST=`/bin/hostname`
fi
CONTADOR=0

while [ "1" = "1" ]
do
        CONTADOR=`echo $CONTADOR | awk '{ print $1+1 }' `
        # Fecha y hora. Se genera un serial (numero de segundos desde 1970) para cada paquete generado.
        TIMESTAMP=`date "+%Y/%m/%d %H:%M:%S"`
        SERIAL=$CONTADOR

        # Nombre de los archivos
        DATA=$TEMP/$NOMBRE_HOST.$SERIAL.data
        CHECKSUM=$TEMP/$NOMBRE_HOST.$SERIAL.checksum
        PANDORA_FILES="$TEMP/$NOMBRE_HOST.$SERIAL.*"

        # Makes data packet
        echo "<agent_data os_name='$OS_NAME' os_version='$OS_VERSION' interval='$INTERVAL' version='$AGENT_VERSION' timestamp='$TIMESTAMP' agent_name='$NOMBRE_HOST'>" > $DATA
	if [ $DEBUG_MODE = 1 ]
	then 
		echo "$TIMESTAMP - Reading module adquisition data from .conf file" >> $PANDORA_HOME/pandora.log
	fi

        for a in `cat $PANDORA_HOME/pandora_agent.conf | grep -v "^#" | grep "^module" `
        do
		if [ "$PANDORA_HARMLESS" = "1" ]
        	then
               		sleep 1
        	fi

                a=`echo $a | tr -s " " " "`
                if [ ! -z "`echo $a | grep '^module_exec'`" ]
                then
                   execution=`echo $a | awk '{ print substr($0, 13)}' `
                   res=`eval $execution 2> /dev/null`
                   echo "<data>$res</data>" >> $DATA
                fi

                if [ ! -z "`echo $a | grep '^module_name'`" ]
                then
                   name=`echo $a | awk '{ print substr($0, 13)}' `
                   echo "<name>$name</name>" >> $DATA
                fi

                if [ ! -z "`echo $a | grep '^module_begin'`" ]
                then
                   echo "<module>" >> $DATA
                fi

		if [ "$PANDORA_HARMLESS" = "0" ]
		then
  			if [ ! -z "`echo $a | grep '^module_max' `" ]
	                then
        	           max=`echo $a | awk '{ print $2 }' `
               		    echo "<max>$max</max>" >> $DATA
                	fi

	                if [ ! -z "`echo $a | grep '^module_min'`" ]
        	        then
       		           min=`echo $a | awk '{ print $2 }' `
               	   	   echo "<min>$min</min>" >> $DATA
                	fi
		fi

                if [ ! -z "`echo $a | grep '^module_description'`" ]
                then
                   desc=`echo $a | awk '{ print substr($0, 20)}' `
                   echo "<description>$desc</description>" >> $DATA
                fi

                if [ ! -z "`echo $a | grep '^module_end'`" ]
                then
                   echo "</module>" >> $DATA
                fi
                if [ ! -z "`echo $a | grep '^module_type'`" ]
                then
                   mtype=`echo $a | awk '{ print substr($0, 13)}' `
                   echo "<type>$mtype</type>" >> $DATA
                fi
        done

        # Call for user-defined script for data adquisition
        if [ -e $PANDORA_HOME/pandora_user.conf ]
        then
           /bin/sh $PANDORA_HOME/pandora_user.conf >> $DATA
        fi

        # Finish data packet
        echo "</agent_data>" >> $DATA
        # Calculate Checksum and prepare MD5 file
        if [ $CHECKSUM_MODE = 0 ]
 	then
        	CHECKSUM_DATA="No valid checksum"
 	else
  		CHECKSUM_DATA=`cat $DATA | /sbin/md5 `
 	fi
        echo $CHECKSUM_DATA $DATA> $CHECKSUM
  
	# Send packets to server and detele it
 	scp -B $PANDORA_FILES pandora@$SERVER_IP:$SERVER_PATH > /dev/null 2> /dev/null 
  
 if [ $DEBUG_MODE = 1 ]
  then
         echo "$TIMESTAMP - Copying $PANDORA_FILES to $SERVER_IP:$SERVER_PATH" >> $PANDORA_HOME/pandora.log
  	exit
  fi
  
        rm -f $PANDORA_FILES> /dev/null
        sleep $INTERVAL
done
# forever!
