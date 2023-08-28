#!/bin/bash
# (c) 2023 Pandora FMS
# This script is used to install a set of tools to load data automatically
# by default it will creates a set of users, groups and agents
# then set a cronjob to insert fake monitoring data to agents each 5 min
# and inventory data once a day.

PREFIX=''

# Moving directory
init_dir=$(pwd)
# Get the directory where the script is located
script_dir="$(dirname "$0")"
# Change the working directory to the script's directory
cd "$script_dir" || exit 1

# Check needed file exists
echo ' [INFO] Checking file requirements:'
if [ -f $PREFIX/usr/share/pandora_server/util/pandora_xml_stress.pl ] && \
    [ -f $(pwd)/pandora_xml_stress.agents ] && \
    [ -f $(pwd)/pandora_xml_stress.conf ] && \
    [ -f $(pwd)/create_usersandgroups.sh ] && \ 
    [ -f $(pwd)/generate_inventory_data.sh ] && \ 
    [ -f $(pwd)/templates/inventory_linux.template ] && \ 
    [ -f $(pwd)/templates/inventory_windows.template] && \ 
    [ -f $(pwd)/pandora_xml_stress_module_source.txt ]; then
    echo ' [INFO] All file exist, continue'
else
    echo ' [ERROR] Missing files, please check.' && exit -1
fi
# Create a set of users and grups
echo ' [INFO] Creating demo users and groups:'
$(pwd)/generate_inventory_data.sh
echo ' [INFO] Waiting for inventory agents to be created:'
while [ $(ls $PREFIX/var/spool/pandora/data_in/ | wc -l) -ge 10 ]; do
    sleep 2
    echo -ne .
done
# Load init monitoring data
echo ' [INFO] Creating demo agent data:'
perl $PREFIX/usr/share/pandora_server/util/pandora_xml_stress.pl $(pwd)/pandora_xml_stress.conf ||   echo ' [ERROR] Generating agent data cant be completed'
echo ' [INFO] Waiting for agents to be created:'
while [ $(ls $PREFIX/var/spool/pandora/data_in/ | wc -l) -ge 10 ]; do
    sleep 2
    echo -ne .
done
# Create a set of users and grups
echo ' [INFO] Creating demo users and groups:'
$(pwd)/create_usersandgroups.sh 
# Set cronjobs in /etc/crotab
echo ' [INFO] Adding data and inventory data to cronjob'
echo "*/5 * * * * root cd $(pwd) && perl $PREFIX/usr/share/pandora_server/util/pandora_xml_stress.pl $(pwd)/pandora_xml_stress.conf " >> /etc/crontab
echo "0 0 * * * root  cd $(pwd) && $(pwd)/generate_inventory_data.sh" >> /etc/crontab
# Get back init directory
cd $init_dir