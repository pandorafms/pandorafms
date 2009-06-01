#!/bin/bash

# This script will collect information from your machine. This information will be written in
# /tmp/pandora_diag.datetime.data and could be used to analyze your current system status
# and performance in order to help Pandora FMS team to solve problems you may have.

TIMESTAMP=`date +"%Y%m%d_%H%M%S"`
MYHOST=`uname`
LINUXINFO=`uname -a`

# Output filename 
OUTFILE="/tmp/pandora_diag.$TIMESTAMP.data"

echo " "
echo "Pandora FMS Diagnostic Script v1.0 (c) ArticaST 2009"
echo "http://pandorafms.org. This script is licensed under GPL2 terms"                   
echo " "
echo "Please wait while this script is collecting data"

# Information gathering
echo "=========================================================================" >> $OUTFILE
echo "Information gathered at $TIMESTAMP" > $OUTFILE
echo $LINUXINFO >> $OUTFILE
echo "=========================================================================" >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "CPUINFO" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
cat /proc/cpuinfo >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "MEMINFO" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
cat /proc/meminfo >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "Other System Parameters" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
MYUPTIME="`uptime`"
echo "Uptime: $MYUPTIME" >> $OUTFILE


echo "-----------------------------------------------------------------" >> $OUTFILE
echo "PROC INFO (Pandora)" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
ps aux | grep pandora >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "MySQL Configuration file" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
cat /etc/mysql/my.cnf >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "Pandora FMS Server Configuration file" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
cat /etc/pandora/pandora_server.conf | grep -v "pass" >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "Pandora FMS Logfiles information" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
ls -la /var/log/pandora >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "System disk" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
df -kh >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "Vmstat (5 execs)" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
vmstat 1 5 >> $OUTFILE

echo "-----------------------------------------------------------------" >> $OUTFILE
echo "System dmesg" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
dmesg >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE
echo "END OF FILE" >> $OUTFILE
echo "-----------------------------------------------------------------" >> $OUTFILE

md5sum $OUTFILE >> $OUTFILE

echo " "
echo "Output file with all information is in '$OUTFILE'"
echo " "




