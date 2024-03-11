#!/bin/bash
# DNS Plugin Pandora FMS Server plugin

# Default variables
TIMEOUT_DURATION=15
IP_CHECK=""
DNS_CHECK=""
DOMAIN_CHECK=""
TIME_CHECK=0

# Regular expression to validate IP address
IP_REGEX="^(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)$"

# Function to display help with command-line options
function show_help {
    echo "DNS Plugin for Pandora FMS Plugin server. http://pandorafms.com"
    echo " "
    echo "This plugin is used to check if a specific domain returns a specific IP address,"
    echo "and to check how much time (in milliseconds) it takes the DNS to answer."
    echo " "
    echo "Syntax:"
    echo "    -d domain to check"
    echo "    -i IP address to check with the domain"
    echo "    -s DNS Server to check"
    echo "    -t Do a DNS time response check instead of a DNS resolve test"
    echo " "
    echo "Samples:"
    echo "    ./dns_plugin.sh -d example.com -i 192.168.1.1 -s 8.8.8.8"
    echo "    ./dns_plugin.sh -d example.com -t -s 8.8.8.8"
    exit 1
}

# Function to perform DNS query and get IP addresses
function do_dns_query {
    results=$(timeout "${TIMEOUT_DURATION}s" dig "@$DNS_CHECK" +nocmd "$DOMAIN_CHECK" +multiline +answer A)
    echo "$results"
}

# Command-line argument processing with getopts
while getopts ":htd:i:s:" opt; do
    case "$opt" in
        d)
            DOMAIN_CHECK=$OPTARG
            ;;
        i)
            # Validate the provided IP address
            if [[ $OPTARG =~ $IP_REGEX ]]; then
                IP_CHECK=$OPTARG
            else
                echo "The provided IP address is incorrect: $OPTARG" >&2
                echo "-1"
                exit 1
            fi
            ;;
        s)
            # Validate the DNS server IP address
            if [[ $OPTARG =~ $IP_REGEX ]]; then
                DNS_CHECK=$OPTARG
            else
                echo "The provided DNS server IP address is incorrect: $OPTARG" >&2
                echo "-1"
                exit 1
            fi
            ;;
        t)
            TIME_CHECK=1
            ;;
        ?)
            show_help
            ;;
    esac
done

# Check if all necessary values are provided
if [ -z "$DOMAIN_CHECK" ] || ([ -z "$IP_CHECK" ] && [ $TIME_CHECK -eq 0 ]) || [ -z "$DNS_CHECK" ]; then
    echo "Missing or incomplete arguments." >&2
    echo "-1"
    show_help
fi

# Check if time response check should be performed
if [ $TIME_CHECK -eq 1 ]; then
    results=$(do_dns_query)
    RETURN_TIME=$(echo "$results" | awk '/Query time:/ {print $4}')
    echo "$RETURN_TIME"
    exit 0
fi

# Check if IP address check should be performed
if [ -n "$IP_CHECK" ]; then
    results=$(do_dns_query)
    targets=$(echo "$results" | awk '{print $5}')

    found=0
    for x in $targets; do
        if [ "$x" == "$IP_CHECK" ]; then
            found=1
            break
        fi
    done

    if [ "$found" -eq 0 ]; then
        echo "0"
    else
        echo "1"
    fi
else
    # Show error if IP to check is not specified
    echo "No IP to check was specified for the domain: $DOMAIN_CHECK" >&2
    echo "-1"
    exit 1
fi

exit 0

