#!/bin/bash
# IPSLA remote Plugin for Pandora FMS
# (c) ArticaST 2014


# Default values
COMMUNITY="public"
TAG_TABLE_CACHE="/tmp/ipsla_cache"

function help {
	echo -e "Cisco IP SLA Server Plugin for Pandora FMS. http://pandorafms.com" 
	echo -e "Syntax:\n\n-c <community> -t <target> -v <version> [other options]\n"
	echo -e "\t\t-c community"
	echo -e "\t\t-t target"
        echo -e "\t\t-v version"
	echo -e "Other options\n";
	echo -e "\t\t-s show defined tags for cisco ipsla device and exit"
        echo -e "\t\t-l <auth-type> "
        echo -e "\t\t-u <user> "
        echo -e "\t\t-a <authentication> "
        echo -e "\t\t-A <authenticacion-password> "
        echo -e "\t\t-x <encryption> "
        echo -e "\t\t-X <encryption-pass> "
	echo -e "\t\t-g <id> "
	echo -e "\t\t-m <module>\n"
	echo -e "Available Modules list: \n"
	echo -e "\tICPIF - Calculated Planning Impairment Factor for specified tag"
	echo -e "\tMOS - Mean Opinion Score"
	echo -e "\tPacket_Out_of_Sequence - Packets arriving out of sequence "
	echo -e "\tPacket_Late_Arrival - Packets arriving late"
	echo -e "\tAverage_Jitter - Average jitter is the estimated average jitter observed in the last XX RTP packets"
	echo -e "\tPacketLossSD - Packet loss from source to destination"
	echo -e "\tPacketLossDS - Packet loss from destination to source"
	echo -e "\tPacketLost -  The number of packets that are lost for which we cannot determine the direction "
	echo -e "\tNegativesSD  - The sum of number of all negative jitter values from packets sent from source to destination "
	echo -e "\tNegativesDS  - The sum of number of all negative jitter values from packets sent from destination to source"
	echo -e "\tPositivesSD  - The sum of number of all positive jitter values from packets sent from source to destination"
	echo -e "\tPositivesDS  - The sum of number of all positive jitter values from packets sent from source to destination"
	echo -e "\tRTTMax  - Max Round Trip Time"
	echo -e "\tRTTMin  - Min Round Trip Time"
	echo -e "\tOperNumOfRTT - The number of successful round trips"
	echo -e "\tOperPacketLossSD - Packet loss from source to destination for jitter tests"
	echo -e "\tOperPacketLossDS - Packet loss from destination to source for jitter tests"
	echo -e "\tRttOperSense - A sense code for the completion status of the latest RTT operation."
	echo -e "\tRttOperCompletionTime - The completion time of the latest RTT operation successfully completed."
	echo -e "\tRttOperTime - The value of the agent system time at the time of the latest RTT operation."
	echo -e "\tRttOperAddress - A string which specifies the address of the target."
	echo -e "\tHTTPOperRTT - Round Trip Time taken to perform HTTP operation. This value is the sum of DNSRTT, TCPConnectRTT and TransactionRTT."
	echo -e "\tHTTPOperDNSRTT Round Trip Time taken to perform DNS query within the HTTP operation."
	echo -e "\tHTTPOperTCPConnectRTT - Round Trip Time taken to connect to the HTTP server."
	echo -e "\tIcmpJitterAvgJitter The average of positive and negative jitter values in Source-to-Destionation and Destination-to-Source direction."
	echo -e "\tHTTPOperTransactionRTT - Round Trip Time taken to download the object specified by the URL."
	echo -e ""
	echo -e " Example execution"
	echo -e " snmp version 3:     ./pandora_ipsla.sh -t <ip_target> -v 3 -l authPriv -u pandorafms -a MD5 -A pandorafms -x AES -X pandorafms -g jitter -m Average_Jitter"
	echo -e " snmp version 2c:    ./pandora_ipsla.sh -t <ip_target> -v 2c -c public -g jitter -m Average_Jitter"

			
	echo ""
	exit
}

function show_tags {
	local TAG_TABLE_CACHE=$TAG_TABLE_CACHE.$TARGET
	
        if [ $version != "3" ]
        then  
                snmpwalk -v 1 -Onq -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.1.1.3 > $TAG_TABLE_CACHE
        fi
        if [ $version == "3" ]
        #if snmp v3 snmpget with v3
        then
                if [ $auth == "authPriv" ]
                # if authpriv snmpget with all parameters
                then
                        snmpwalk -v 3 -Onq -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass  $TARGET 1.3.6.1.4.1.9.9.42.1.2.1.1.3 > $TAG_TABLE_CACHE
                fi
                if [ $auth == "authNoPriv" ]
                then

                        snmpget -v 3 -Onq -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.1.1.3 > $TAG_TABLE_CACHE
                fi
        fi
	
	cat $TAG_TABLE_CACHE 
	exit
}

function update_tags {
	local TAG_TABLE_CACHE=$TAG_TABLE_CACHE.$TARGET

        if [ $version != "3" ]
        then  
		snmpwalk -v 1 -Onq -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.1.1.3 > $TAG_TABLE_CACHE
        fi
        if [ $version == "3" ]
         #if snmp v3 snmpget with v3
        then
                if [ $auth == "authPriv" ]
                # if authpriv snmpget with all parameters
                then
                        snmpwalk -v 3 -Onq -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass  $TARGET 1.3.6.1.4.1.9.9.42.1.2.1.1.3 > $TAG_TABLE_CACHE
                fi
                if [ $auth == "authNoPriv" ]
                then
       
                        snmpget -v 3 -Onq -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.1.1.3 > $TAG_TABLE_CACHE
                fi
        fi

	
}

function get_index {
	cat $TAG_TABLE_CACHE.$TARGET | grep "\"$1\"" | grep -o "[0-9]*\s"
}

# This function requires two arguments. MODULE_TYPE TAG
function get_module {
	MODULE_TYPE=$1
        update_tags
	INDICE=$2

        if [ $version != "3" ]

        then
    
	if [ "$MODULE_TYPE" == "ICPIF" ]
        then
            VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.43.$INDICE`
        fi
        if [ "$MODULE_TYPE" == "MOS" ]
            then
                    VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.42.$INDICE`
            fi
        if [ "$MODULE_TYPE" == "Packet_Out_of_Sequence" ]
            then
                    VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.28.$INDICE`
            fi 
            
        if [ "$MODULE_TYPE" == "Packet_Late_Arrival" ]
            then
                    VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.30.$INDICE`
            fi    
            
        if [ "$MODULE_TYPE" == "Average_Jitter" ]
            then
                    VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.46.$INDICE`
            fi 
            
        if [ "$MODULE_TYPE" == "PacketLossSD" ]
            then
                    VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
            fi 
            
        if [ "$MODULE_TYPE" == "PacketLossDS" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                fi     
                    
        if [ "$MODULE_TYPE" == "PacketLost" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.29.$INDICE`
                fi     
                    
        if [ "$MODULE_TYPE" == "NegativesSD" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.12.$INDICE`
                fi      
                    
        if [ "$MODULE_TYPE" == "NegativesDS" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.22.$INDICE`
                fi       
                    
        if [ "$MODULE_TYPE" == "PositivesSD" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.7.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "PositivesDS" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.17.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "RTTMax" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.5.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "RTTMin" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.4.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "OperNumOfRTT" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.1.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "OperPacketLossSD" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "OperPacketLossDS" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "RttOperCompletionTime" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.1.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "RttOperSense" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.2.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "RttOperTime" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.5.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "RttOperAddress" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.6.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "HTTPOperRTT" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.1.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "HTTPOperDNSRTT" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.2.$INDICE`
                fi                
                    
        if [ "$MODULE_TYPE" == "HTTPOperTCPConnectRTT" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.3.$INDICE`
                fi                 
                    
        if [ "$MODULE_TYPE" == "IcmpJitterAvgJitter" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.4.1.44.$INDICE`
                fi                 
                    
        if [ "$MODULE_TYPE" == "HTTPOperTransactionRTT" ]
                then
                        VALOR=`snmpget -v $version -Oqv -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.4.$INDICE`
                fi 
        fi                

        if [ $version == "3" ]

        #if snmp v3 snmpget with v3
        then
                if [ $auth == "authPriv" ]
                # if authpriv snmpget with all parameters
                then
                        if [ "$MODULE_TYPE" == "ICPIF" ]
                        then
                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.43.$INDICE`
                        fi
                        if [ "$MODULE_TYPE" == "MOS" ]
                        then
                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.42.$INDICE`
                        fi
                        if [ "$MODULE_TYPE" == "Packet_Out_of_Sequence" ]
                        then
                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.28.$INDICE`
                        fi 
                        
                        if [ "$MODULE_TYPE" == "Packet_Late_Arrival" ]
                        then
                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.30.$INDICE`
                        fi    
                        
                        if [ "$MODULE_TYPE" == "Average_Jitter" ]
                        then
                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.46.$INDICE`
                        fi 
                        
                        if [ "$MODULE_TYPE" == "PacketLossSD" ]
                        then
                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
                        fi 
                        
                        if [ "$MODULE_TYPE" == "PacketLossDS" ]
                        then
                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                        fi     
                                
                        if [ "$MODULE_TYPE" == "PacketLost" ]
                        then
                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.29.$INDICE`
                        fi     
                                
                        if [ "$MODULE_TYPE" == "NegativesSD" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.12.$INDICE`
                                fi      
                                
                        if [ "$MODULE_TYPE" == "NegativesDS" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.22.$INDICE`
                                fi       
                                
                        if [ "$MODULE_TYPE" == "PositivesSD" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.7.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "PositivesDS" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.17.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "RTTMax" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.5.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "RTTMin" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.4.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "OperNumOfRTT" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.1.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "OperPacketLossSD" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "OperPacketLossDS" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "RttOperCompletionTime" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.1.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "RttOperSense" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.2.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "RttOperTime" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.5.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "RttOperAddress" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.6.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "HTTPOperRTT" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.1.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "HTTPOperDNSRTT" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.2.$INDICE`
                                fi                
                                
                        if [ "$MODULE_TYPE" == "HTTPOperTCPConnectRTT" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.3.$INDICE`
                                fi                 
                                
                        if [ "$MODULE_TYPE" == "IcmpJitterAvgJitter" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.4.1.44.$INDICE`
                                fi                 
                                
                        if [ "$MODULE_TYPE" == "HTTPOperTransactionRTT" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -x $hash2 -X $hash2pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.4.$INDICE`
                                fi
                fi  
                if [ $auth == "authNoPriv" ]
                then
                        if [ "$hash1" ]
                                then
                                if [ "$MODULE_TYPE" == "ICPIF" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.43.$INDICE`
                                fi
                                if [ "$MODULE_TYPE" == "MOS" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.42.$INDICE`
                                fi
                                if [ "$MODULE_TYPE" == "Packet_Out_of_Sequence" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.28.$INDICE`
                                fi 
                                
                                if [ "$MODULE_TYPE" == "Packet_Late_Arrival" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.30.$INDICE`
                                fi    
                                
                                if [ "$MODULE_TYPE" == "Average_Jitter" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.46.$INDICE`
                                fi 
                                
                                if [ "$MODULE_TYPE" == "PacketLossSD" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
                                fi 
                                
                                if [ "$MODULE_TYPE" == "PacketLossDS" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                                fi     
                                        
                                if [ "$MODULE_TYPE" == "PacketLost" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.29.$INDICE`
                                        fi     
                                        
                                if [ "$MODULE_TYPE" == "NegativesSD" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.12.$INDICE`
                                        fi      
                                        
                                if [ "$MODULE_TYPE" == "NegativesDS" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.22.$INDICE`
                                        fi       
                                        
                                if [ "$MODULE_TYPE" == "PositivesSD" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.7.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "PositivesDS" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.17.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RTTMax" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.5.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RTTMin" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.4.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "OperNumOfRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.1.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "OperPacketLossSD" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "OperPacketLossDS" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperCompletionTime" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.1.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperSense" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.2.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperTime" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.5.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperAddress" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.6.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.1.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperDNSRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.2.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperTCPConnectRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.3.$INDICE`
                                        fi                 
                                        
                                if [ "$MODULE_TYPE" == "IcmpJitterAvgJitter" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.4.1.44.$INDICE`
                                        fi                 
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperTransactionRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -a $hash1 -A $hash1pass -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.4.$INDICE`
                                        fi
                        else
                                if [ "$MODULE_TYPE" == "ICPIF" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.43.$INDICE`
                                fi
                                if [ "$MODULE_TYPE" == "MOS" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.42.$INDICE`
                                fi
                                if [ "$MODULE_TYPE" == "Packet_Out_of_Sequence" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.28.$INDICE`
                                fi 
                                
                                if [ "$MODULE_TYPE" == "Packet_Late_Arrival" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.30.$INDICE`
                                fi    
                                
                                if [ "$MODULE_TYPE" == "Average_Jitter" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.46.$INDICE`
                                fi 
                                
                                if [ "$MODULE_TYPE" == "PacketLossSD" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
                                fi 
                                
                                if [ "$MODULE_TYPE" == "PacketLossDS" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                                fi     
                                        
                                if [ "$MODULE_TYPE" == "PacketLost" ]
                                then
                                        VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.29.$INDICE`
                                fi     
                                        
                                if [ "$MODULE_TYPE" == "NegativesSD" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.12.$INDICE`
                                        fi      
                                        
                                if [ "$MODULE_TYPE" == "NegativesDS" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.22.$INDICE`
                                        fi       
                                        
                                if [ "$MODULE_TYPE" == "PositivesSD" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.7.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "PositivesDS" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.17.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RTTMax" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.5.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RTTMin" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.4.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "OperNumOfRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.1.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "OperPacketLossSD" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.26.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "OperPacketLossDS" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.2.1.27.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperCompletionTime" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.1.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperSense" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.2.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperTime" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.5.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "RttOperAddress" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.2.10.1.6.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.1.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperDNSRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.2.$INDICE`
                                        fi                
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperTCPConnectRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.3.$INDICE`
                                        fi                 
                                        
                                if [ "$MODULE_TYPE" == "IcmpJitterAvgJitter" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.4.1.44.$INDICE`
                                        fi                 
                                        
                                if [ "$MODULE_TYPE" == "HTTPOperTransactionRTT" ]
                                        then
                                                VALOR=`snmpget -v 3 -l $auth -u $user -c $COMMUNITY $TARGET 1.3.6.1.4.1.9.9.42.1.5.1.1.4.$INDICE`
                                        fi
                                
                                fi
                        
                fi              
    fi
      
	echo -n $VALOR | awk '{print $NF}'
	exit 0
}

if [ -z "`which snmpwalk`" ]
then
	echo "ERROR: snmpwalk is not in the path. Exiting..."
	exit -1
fi

if [ $# -eq 0 ]
then
	help
fi

# Main parsing code

while getopts ":hc:t:v:l:u:a:A:x:X:sm:g:" optname
  do
    case "$optname" in
      "h")
	        help
	;;
      "c")
	    COMMUNITY=$OPTARG
        ;;
      "t")
		TARGET=$OPTARG
        ;;
      "v")
		version=$OPTARG
        ;;
      "l")
		auth=$OPTARG
        ;;
      "u")
		user=$OPTARG
        ;;
      "a")
		hash1=$OPTARG
        ;;
      "A")
		hash1pass=$OPTARG
        ;;
      "x")
		hash2=$OPTARG
        ;;
      "X")
		hash2pass=$OPTARG
        ;;          
      "g")
		TAG=$OPTARG
	;;
      "s")
		SHOWTAGS=1
        ;;
      "m")
                MODULE=$OPTARG
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
[ "$SHOWTAGS" ] && echo "Showing avaliables  ipsla tags for the device $TARGET and OID 1.3.6.1.4.1.9.9.42.1.2.1.1.3" && show_tags

[ -z "$TARGET" ] && echo "Error missing target ip definition please use -t to difine it or -h to see help" && exit 1
[ -z "$MODULE" ] && echo "Error missing module definition please use -m to difine it or -h to see help" && exit 1
[ -z "$TAG" ] && echo "Error missing id definition please use -g to difine it or -h to see help" && exit 1
[ -z "$version" ] && echo "Error missing snmp version definition please use -v to difine it or -h to see help" && exit 1


get_module $MODULE $TAG
echo "DEBUG"

exit 0

#RttOperSense
# 0:other
# 1:ok
# 2:disconnected
# 3:overThreshold
# 4:timeout
# 5:busy
# 6:notConnected
# 7:dropped
# 8:sequenceError
# 9:verifyError
# 10:applicationSpecific
# 11:dnsServerTimeout
# 12:tcpConnectTimeout
# 13:httpTransactionTimeout
# 14:dnsQueryError
# 15:httpError
# 16:error
# 17:mplsLspEchoTxError
# 18:mplsLspUnreachable
# 19:mplsLspMalformedReq
# 20:mplsLspReachButNotFEC
# 21:enableOk
# 22:enableNoConnect
# 23:enableVersionFail
# 24:enableInternalError
# 25:enableAbort
# 26:enableFail
# 27:enableAuthFail
# 28:enableFormatError
# 29:enablePortInUse
# 30:statsRetrieveOk
# 31:statsRetrieveNoConnect
# 32:statsRetrieveVersionFail
# 33:statsRetrieveInternalError
# 34:statsRetrieveAbort
# 35:statsRetrieveFail
# 36:statsRetrieveAuthFail
# 37:statsRetrieveFormatError
# 38:statsRetrievePortInUse