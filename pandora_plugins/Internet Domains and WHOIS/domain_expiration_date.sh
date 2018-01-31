#!/bin/bash

if [ $# -ne 1 ]
then
	echo
    echo "Usage: domain_expiration_date.sh <domain_name>" 
    echo "Returns the number of days to expiration date"
    echo
    exit
fi

domain=$1

# Calculate days until expiration
expiration=`whois $domain |grep "Expiry Date:"| awk -F"Date:" '{print $2}'|cut -f 1`
expseconds=`date +%s --date="$expiration"`
nowseconds=`date +%s`
((diffseconds=expseconds-nowseconds))
expdays=$((diffseconds/86400))

echo $expdays

