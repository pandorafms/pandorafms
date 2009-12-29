#!/bin/bash
# WEB Content Stampg server pluin
# (c) Sancho Lerena 2010


# Default values
URL=""
HASH=""
TEST=0
TIMEOUT=10

function help {
	echo -e ""
	echo -e "WEB content stamp Plugin for Pandora FMS Plugin server. http://pandorafms.com" 
	echo -e "Syntax:" 
	echo -e "\t\t-u url		For example: http://pandorafms.org/index.html"
	echo -e "\t\t-m md5hash 	MD5 hash passed as parameter to check remote content"
	echo -e "\t\t-g			Use this parameter to get MD5 on command line"
	echo -e "Samples:\n"
	echo "   ./webcheck_plugin.sh -u http://pandorafms.org -m 79ea72005e5505d99d2548e1b2189857"
	echo ""
	echo -e "Please note that -g parameter is used only in command line to get the valid MD5sum to use in the check"
	exit
}

if [ $# -eq 0 ]
then
	help
fi

# Sample of full exec
# wget http://192.168.70.103/pandora_console/index.php -O /dev/stdout -o /dev/null  | md5sum

# Main parsing code

while getopts ":h:gm:u:" optname
  do
    case "$optname" in
      "h")
	        help
	;;
      "m")
		HASH=$OPTARG
	;;
      "u")
		URL=$OPTARG
        ;;
      "g")
		TEST=1
        ;;
       ?)
		help
		;;
      default) 
		help
	;;
     
    esac
done

if [ -z "$URL" ]
then
	help
fi

if [ $TEST == 1 ]
then
	wget $URL -T $TIMEOUT -O /dev/stdout -o /dev/null | md5sum  | awk '{ print $1 }'
	exit
fi
	

# execution
REAL_HASH=`wget $URL -T $TIMEOUT -O /dev/stdout -o /dev/null | md5sum  | awk '{ print $1 }'`
if [ "$REAL_HASH" == "$HASH" ]
then
	echo 1
else
	echo 0
fi

