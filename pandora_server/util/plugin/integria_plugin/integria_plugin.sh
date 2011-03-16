#!/bin/bash
# Integria Plugin for Pandora FMS
# This plugin uses Integria API
# (c) Dario Rodriguez 2011

INTEGRIA_CONSOLE_PATH=""
USER=""
KEY=""
REQUEST=""
PARAMS=""
TOKEN=","

# Help menu

function help {
	echo -e "Integria Plugin for Pandora FMS. http://pandorafms.com" 
	echo -e "Syntax:" 
	echo -e "\t\t-c   : integria console path"	
	echo -e "\t\t-u   : user"
	echo -e "\t\t[-k] : API key (required if key is set on integria console)"	
	echo -e "\t\t-r   : request"
	echo -e "\t\t[-f] : parameters (default '')"
	echo -e "\t\t[-s] : separator token (default ',')"
	echo -e "Samples:"
	echo "   ./integria_plugin.sh -c http://127.0.0.1/integria -u user -r get_stats -f total_incidents"
	echo ""
	exit
}

# Show help if there is no parameters

if [ $# -eq 0 ]
then
	help
fi

# Main parsing code

while getopts ":hc:u:k:r:f:s:" optname
  do
    case "$optname" in
      "h")
	        help
		;;
      "c")
		INTEGRIA_CONSOLE_PATH=$OPTARG
        ;;
      "u")
		USER=$OPTARG
        ;;
      "k")
		KEY=$OPTARG
        ;;
      "r")
		REQUEST=$OPTARG
        ;;
      "f")
		PARAMS=$OPTARG
        ;;                        
      "s")
		TOKEN=$OPTARG
        ;;        
       ?)
		help
		;;
      default) 
		help
	;;
     
    esac
done

# Create API call

API_CALL=$INTEGRIA_CONSOLE_PATH"/include/api.php?user="$USER"&pass="$KEY"&op="$REQUEST"&params="$PARAMS"&token="$TOKEN

# Execute call with wget
DATE=`date +%s%N`
FILE_OUTPUT="temp$DATE"

wget $API_CALL -o /dev/null -O "$FILE_OUTPUT"

# Check if wget was OK

if [ $? -eq 0 ]; then
 output=`cat "$FILE_OUTPUT"`
 
 # Check if API call returns some valuer not
 
 if [ "$output" != "" ]; then
	echo -n $output
 fi
 
fi

rm "$FILE_OUTPUT"
