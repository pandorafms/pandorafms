#!/bin/bash
# Create Integria Incident Script for Pandora FMS
# This plugin uses Integria API
# (c) Dario Rodriguez 2011

INTEGRIA_CONSOLE_PATH=""
USER=""
REQUEST="create_incident"
KEY=""
TITLE=""
DESC=""
PRIORITY=""
GROUP=""
INVENTORY=""

# Help menu

function help {
	echo -e "Create Integria Incident Script for Pandora FMS. http://pandorafms.com" 
	echo -e "Syntax:" 
	echo -e "\t\t-c   : integria console path"	
	echo -e "\t\t-u   : user"
	echo -e "\t\t[-k] : API key (required if key is set on integria console)"	
	echo -e "\t\t-t   : Indicent title"
	echo -e "\t\t-d   : Indicent description"
	echo -e "\t\t-p   : Indicent priority"
	echo -e "\t\t-g   : ID indicent group"
	echo -e "\t\t-i   : ID indicent inventory"		
	echo -e "Samples:"
	echo "   ./create_integria_incident.sh -c http://127.0.0.1/integria -u user -t \"Incident title\" -d \"Incident description\" -p 4 -g 5 -i 8"
	echo ""
	exit
}

# Show help if there is no parameters

if [ $# -eq 0 ]
then
	help
fi

# Main parsing code

while getopts ":hc:u:k:t:d:p:g:i:" optname
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
      "t")
		TITLE=$OPTARG
        ;;
      "d")
		DESC=$OPTARG
        ;;                        
      "p")
		PRIORITY=$OPTARG
        ;;        
      "g")
		GROUP=$OPTARG
        ;;  
      "i")
		INVENTORY=$OPTARG
        ;;                  
       ?)
		help
		;;
      default) 
		help
	;;
     
    esac
done

# Create params for API call

PARAMS=$TITLE","$GROUP","$PRIORITY","$DESC","$INVENTORY

# Create API call
API_CALL=$INTEGRIA_CONSOLE_PATH"/include/api.php?user="$USER"&pass="$KEY"&op="$REQUEST"&params="$PARAMS

wget "$API_CALL" -o /dev/null -O /dev/null
