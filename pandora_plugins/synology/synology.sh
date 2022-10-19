#!/bin/bash

function help {
        echo -e "Synology Plugin for Pandora FMS Plugin server. http://pandorafms.com"
        echo " "
        echo "This plugin is used to monitor a Synology device via SNMP checks"
        echo -e "Syntax:"
        echo -e "\t\tparameter 1 must be the IP address of the device"
	echo -e "\t\tparameter 2 must be the SNMP Community (only v2c)"
        echo -e "\t\tparameter 3 must be the Pandora FMS agent name"
        echo -e "Sample:"
        echo "   ./synology.sh 10.113.7.220 frcpass PPAMSSV020"
        echo ""
        exit
}

if [ $# -ne 3 ]
then
        help
fi

ip=$1
community=$2
agent=$3
temp="/tmp"
filename="synology$agent.123456.data"
file="$temp/$filename"
pandoradir="/var/spool/pandora/data_in/"

snmp=$(snmpwalk -v2c -c"$community" "$ip"  1.3.6.1.4.1.6574.1 2>&1)

if [[ "$snmp" == *"Timeout"* ]]
then
        echo "0"
	exit 0
else
        echo "1"
fi

echo "<?xml version='1.0' encoding='UTF-8'?><agent_data agent_name='$agent' interval='300'>" > $file

systemstatus=$(snmpwalk -v2c -c"$community" "$ip" -Ovq .1.3.6.1.4.1.6574.1.1.0)
systemtemp=$(snmpwalk -v2c -c"$community" "$ip" -Ovq .1.3.6.1.4.1.6574.1.2.0)
systempower=$(snmpwalk -v2c -c"$community" "$ip" -Ovq .1.3.6.1.4.1.6574.1.3.0)
systemfan=$(snmpwalk -v2c -c"$community" "$ip" -Ovq .1.3.6.1.4.1.6574.1.4.1.0)
cpufan=$(snmpwalk -v2c -c"$community" "$ip" -Ovq .1.3.6.1.4.1.6574.1.4.2.0)
model=$(snmpwalk -v2c -c"$community" "$ip" -Ovq .1.3.6.1.4.1.6574.1.5.1.0)
version=$(snmpwalk -v2c -c"$community" "$ip" -Ovq .1.3.6.1.4.1.6574.1.5.3.0)

echo "<module>" >> $file
echo "<name><![CDATA[System Status]]></name>" >> $file
echo "<type>generic_data</type>" >> $file
echo "<data><![CDATA[$systemstatus]]></data>" >> $file
echo "<description><![CDATA[Synology system status Each meanings of status represented describe below. Normal(1): System functionals normally. Failed(2): Volume has crashed.]]></description>" >> $file
echo "<min_warning><![CDATA[0]]></min_warning>" >> $file
echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
echo "<min_critical><![CDATA[2]]></min_critical>" >> $file
echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
echo "</module>" >> $file

echo "<module>" >> $file
echo "<name><![CDATA[System Temperature]]></name>" >> $file
echo "<type>generic_data</type>" >> $file
echo "<data><![CDATA[$systemtemp]]></data>" >> $file
echo "<description><![CDATA[Synology system temperature The temperature of Disk Station uses Celsius degree.]]></description>" >> $file
echo "<unit><![CDATA[Cº]]></unit>" >> $file
echo "<min_warning><![CDATA[50]]></min_warning>" >> $file
echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
echo "<min_critical><![CDATA[60]]></min_critical>" >> $file
echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
echo "</module>" >> $file

echo "<module>" >> $file
echo "<name><![CDATA[System Power]]></name>" >> $file
echo "<type>generic_data</type>" >> $file
echo "<data><![CDATA[$systempower]]></data>" >> $file
echo "<description><![CDATA[Synology power status Each meanings of status represented describe below. Normal(1): All power supplies functional normally. Failed(2): One of power supply has failed.]]></description>" >> $file
echo "<min_warning><![CDATA[0]]></min_warning>" >> $file
echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
echo "<min_critical><![CDATA[2]]></min_critical>" >> $file
echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
echo "</module>" >> $file

echo "<module>" >> $file
echo "<name><![CDATA[System Fan Status]]></name>" >> $file
echo "<type>generic_data</type>" >> $file
echo "<data><![CDATA[$systemfan]]></data>" >> $file
echo "<description><![CDATA[Synology system fan status Each meanings of status represented describe below. Normal(1): All Internal fans functional normally. Failed(2): One of internal fan stopped.]]></description>" >> $file
echo "<min_warning><![CDATA[0]]></min_warning>" >> $file
echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
echo "<min_critical><![CDATA[2]]></min_critical>" >> $file
echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
echo "</module>" >> $file

echo "<module>" >> $file
echo "<name><![CDATA[CPU Fan Status]]></name>" >> $file
echo "<type>generic_data</type>" >> $file
echo "<data><![CDATA[$cpufan]]></data>" >> $file
echo "<description><![CDATA[Synology cpu fan status Each meanings of status represented describe below. Normal(1): All CPU fans functional normally. Failed(2): One of CPU fan stopped.]]></description>" >> $file
echo "<min_warning><![CDATA[0]]></min_warning>" >> $file
echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
echo "<min_critical><![CDATA[2]]></min_critical>" >> $file
echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
echo "</module>" >> $file

echo "<module>" >> $file
echo "<name><![CDATA[Model name]]></name>" >> $file
echo "<type>generic_data_string</type>" >> $file
echo "<data><![CDATA[$model]]></data>" >> $file
echo "<description><![CDATA[The Model name of this NAS]]></description>" >> $file
echo "</module>" >> $file

echo "<module>" >> $file
echo "<name><![CDATA[OS version]]></name>" >> $file
echo "<type>generic_data_string</type>" >> $file
echo "<data><![CDATA[$version]]></data>" >> $file
echo "<description><![CDATA[The version of this DSM]]></description>" >> $file
echo "</module>" >> $file

for i in $(snmpwalk -v2c -c"$community" "$ip"  -Ovq SNMPv2-SMI::enterprises.6574.2.1.1.1)
do
	disk_id=$(snmpget -v2c -c"$community" "$ip"  -Ovq SNMPv2-SMI::enterprises.6574.2.1.1.2.$i | sed 's/"//g')
	disk_name=$(snmpget -v2c -c"$community" "$ip"  -Ovq SNMPv2-SMI::enterprises.6574.2.1.1.3.$i| sed 's/"//g')
	disk_status=$(snmpget -v2c -c"$community" "$ip"  -Ovq SNMPv2-SMI::enterprises.6574.2.1.1.5.$i)
	disk_temp=$(snmpget -v2c -c"$community" "$ip"  -Ovq SNMPv2-SMI::enterprises.6574.2.1.1.6.$i)
	echo "<module>" >> $file
        echo "<name><![CDATA[Disk "$disk_id"-"$disk_name" Status]]></name>" >> $file
        echo "<type>generic_data</type>" >> $file
        echo "<data><![CDATA[$disk_status]]></data>" >> $file
        echo "<description><![CDATA[Synology disk status Each meanings of status represented describe below. Normal(1): The hard disk functions normally. Initialized(2): The hard disk has system partition but no data. NotInitialized(3): The hard disk does not have system in system partition. SystemPartitionFailed(4): The system partitions on the hard disks are damaged. Crashed(5): The hard disk has damaged.]]></description>" >> $file
        echo "<min_warning><![CDATA[2]]></min_warning>" >> $file
        echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
        echo "<min_critical><![CDATA[3]]></min_critical>" >> $file
        echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
	echo "</module>" >> $file

	echo "<module>" >> $file
        echo "<name><![CDATA[Disk "$disk_id"-"$disk_name" Temperature]]></name>" >> $file
        echo "<type>generic_data</type>" >> $file
        echo "<data><![CDATA[$disk_temp]]></data>" >> $file
        echo "<description><![CDATA[Synology disk temperature The temperature of each disk uses Celsius degree. ]]></description>" >> $file
	echo "<unit><![CDATA[Cº]]></unit>" >> $file
        echo "<min_warning><![CDATA[50]]></min_warning>" >> $file
        echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
        echo "<min_critical><![CDATA[60]]></min_critical>" >> $file
        echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
        echo "</module>" >> $file
done

for i in $(snmpwalk -v2c -c"$community" "$ip" -Ovq SNMPv2-SMI::enterprises.6574.3.1.1.1)
do
        raid_name=$(snmpget -v2c -c"$community" "$ip"  -Ovq 1.3.6.1.4.1.6574.3.1.1.2.$i| sed 's/"//g')
        raid_status=$(snmpget -v2c -c"$community" "$ip"  -Ovq 1.3.6.1.4.1.6574.3.1.1.3.$i)
	raid_free=$(snmpget -v2c -c"$community" "$ip"  -Ovq 1.3.6.1.4.1.6574.3.1.1.4.$i)
	raid_total=$(snmpget -v2c -c"$community" "$ip"  -Ovq 1.3.6.1.4.1.6574.3.1.1.5.$i)
	perc_total=$(echo "scale=2; $raid_free * 100 / $raid_total" | bc)
        echo "<module>" >> $file
        echo "<name><![CDATA[RAID $raid_name Status]]></name>" >> $file
        echo "<type>generic_data</type>" >> $file
        echo "<data><![CDATA[$raid_status]]></data>" >> $file
        echo "<description><![CDATA[It shows the RAID status right now]]></description>" >> $file
        echo "<min_warning><![CDATA[2]]></min_warning>" >> $file
        echo "<max_warning><![CDATA[0]]></max_warning>" >> $file
        echo "<min_critical><![CDATA[11]]></min_critical>" >> $file
        echo "<max_critical><![CDATA[0]]></max_critical>" >> $file
        echo "</module>" >> $file

        echo "<module>" >> $file
        echo "<name><![CDATA[RAID $raid_name Free]]></name>" >> $file
        echo "<type>generic_data</type>" >> $file
        echo "<data><![CDATA[$perc_total]]></data>" >> $file
        echo "<description><![CDATA[Percentage of free space on RAID]]></description>" >> $file
        echo "<unit><![CDATA[%]]></unit>" >> $file
        echo "<min_warning><![CDATA[0]]></min_warning>" >> $file
        echo "<max_warning><![CDATA[10]]></max_warning>" >> $file
        echo "<min_critical><![CDATA[0]]></min_critical>" >> $file
        echo "<max_critical><![CDATA[5]]></max_critical>" >> $file
        echo "</module>" >> $file
done

echo "</agent_data>" >> $file

mv $file $pandoradir
