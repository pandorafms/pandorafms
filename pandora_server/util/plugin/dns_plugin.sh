#!/bin/bash
# DNS Plugin Pandora FMS Server plugin
# (c) Antonio Delgado, Sancho Lerena 2010

# Hint: Use this DNS servers as reference:
# Google: 8.8.8.8
# Telefonica: 194.179.1.101

function help {
        echo -e "DNS Plugin for Pandora FMS Plugin server. http://pandorafms.com" 
        echo " "
        echo "This plugin is used to check if a specific domain return a specific IP "
        echo -e "address, and to check how time (milisecs) takes the DNS to answer. \n"
        echo -e "Syntax:" 
        echo -e "\t\t-d domain to check"
        echo -e "\t\t-i IP address to check with domain"
        echo -e "\t\t-s DNS Server to check"
        echo -e "\t\t-t Do a DNS time response check instead DNS resolve test"
        echo -e "Samples:"
        echo "   ./dns_plugin.sh -d artica.es -i 69.163.176.17 -s 8.8.8.8"
        echo "   ./dns_plugin.sh -d artica.es -t -s 8.8.8.8"
        echo ""
        exit
}

if [ $# -eq 0 ]
then
        help
fi

TIMEOUT_CHECK=0
DOMAIN_CHECK=""
IP_CHECK=""
DNS_CHECK=""

# Main parsing code
while getopts ":htd:i:s:" optname
  do
    case "$optname" in
      "h")
                help
        ;;
      "d")
                DOMAIN_CHECK=$OPTARG
        ;;
      "t")
                TIMEOUT_CHECK=1
        ;;
      "i")
                IP_CHECK=$OPTARG
        ;;
      "s")
                DNS_CHECK=$OPTARG
        ;;

       ?)
                help
                ;;
      default)
                help
        ;;

    esac
done

if [ -z "$DNS_CHECK" ]
then
        help
fi


results=`dig @$DNS_CHECK +nocmd $DOMAIN_CHECK +multiline +noall +answer A`
targets=`echo "$results"| awk '{print $5}'`

for x in $targets; do
        if [ "$x" == "$IP_CHECK" ]; then
                echo 1
                exit 0
        fi
done

echo 0
exit 0
