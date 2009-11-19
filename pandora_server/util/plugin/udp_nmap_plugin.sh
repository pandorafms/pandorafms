#!/bin/bash
# UDP Scan (using nmap) Pandora FMS Server plugin
# (c) Sancho Lerena 2008-2009


# Default values
PORT=""
HOST=""

function help {
	echo -e "UDP Port Plugin for Pandora FMS Plugin server. http://pandorafms.com" 
	echo -e "Syntax:" 
	echo -e "\t\t-p port"
	echo -e "\t\t-t hostname / target IP"
	echo -e "Samples:"
	echo "   ./udp_nmap_plugin.sh -p 137 -t 192.168.5.20"
	echo ""
	echo -e "Please note that -p accepts nmap multiport syntax (like: 135,138,139,200-300)\n\n"
	exit
}

if [ $# -eq 0 ]
then
	help
fi


# Main parsing code

while getopts ":hp:t:" optname
  do
    case "$optname" in
      "h")
	        help
	;;
      "p")
		PORT=$OPTARG
        ;;
      "t")
		HOST=$OPTARG
        ;;
       ?)
		help
		;;
      default) 
		help
	;;
     
    esac
done

# execution
nmap -T5 -p $PORT -sU $HOST | grep open | wc -l 2> /dev/null

