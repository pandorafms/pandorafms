#!/bin/bash
# Generic SSH Exec Pandora FMS Server plugin
# (c) Sancho Lerena 2008-2009

# Default values
USER="root"
HOST=""
COMMAND=""
PORT=22

function help {
	echo -e "Generic SSH Execution plugion for Pandora FMS Plugin server. http://pandorafms.com" 
	echo -e "Syntax:" 
	echo -e "\t\t-u user"
	echo -e "\t\t-p SSH port (by default 22)"
	echo -e "\t\t-t Hostname / Target IP Address"
	echo -e "\t\t-c Commnand"
	echo -e "Samples:"
	echo "   ./ssh_pandoraplugin.sh -t 192.168.5.20 -u root -c \"ls -la /etc/myfile.conf | wc -l\""
	echo ""
	echo -e "Please note that before use this plugin you need to export user publickey of "
	echo -e "Pandora running user on the server destination of the command, and make the host"
	echo -e "key autenthication first"
	exit
}

if [ $# -eq 0 ]
then
	help
fi


# Main parsing code

while getopts ":hp:t:c:u:" optname
  do
    case "$optname" in
      "h")
	        help
	;;
      "u")
		USER=$OPTARG
        ;;
      "t")
		HOST=$OPTARG
        ;;
      "c")
		COMMAND=$OPTARG
        ;;
      "p")
		PORT=$OPTARG
        ;;
       ?)
		help
		;;
      default) 
		help
	;;
     
    esac
done


ssh -p $PORT $USER@$HOST $COMMAND 2> /dev/null

