#!/bin/bash
# Mysql remote Plugin for Pandora FMS Plugin server
# (c) ArticaST, Sancho Lerena 2008


# Default values
PASSWORD=""
SERVER=""
USER=""

function help {
	echo -e "MySQL Plugin for Pandora FMS Plugin server. http://pandorafms.com" 
	echo -e "Syntax:" 
	echo -e "\t\t-u username"
	echo -e "\t\t-p password"
	echo -e "\t\t-s server"
	echo -e "\t\t-q query string (global status), for example 'Aborted_connects'\n" 
	echo -e "Samples:"
	echo "   ./mysql_plugin.sh -u root -p none -s localhost -q Com_select"
	echo "   ./mysql_plugin.sh -u root -p none -s localhost -q Com_update"
	echo "   ./mysql_plugin.sh -u root -p none -s localhost -q Connections"
	echo "   ./mysql_plugin.sh -u root -p anypass -s 192.168.50.24 -q Innodb_rows_read"
	echo ""
	exit
}


if [ $# -eq 0 ]
then
	help
fi

# Main parsing code

while getopts ":hu:p:s:q:" optname
  do
    case "$optname" in
      "h")
	        help
	;;
      "u")
	        USER=$OPTARG
        ;;
      "p")
		PASSWORD=$OPTARG
        ;;
      "s")
		SERVER=$OPTARG
        ;;
      "q")
		QUERY=$OPTARG
		;;
        ?)
		help
		;;
      default) 
		help
	;;
     
    esac
done

# Execution

echo "show global status" | mysql -u $USER -p$PASSWORD -h$SERVER | grep "$QUERY" | awk '{ print $2 }'


