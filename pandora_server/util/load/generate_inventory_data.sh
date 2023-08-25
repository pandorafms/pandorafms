#!/bin/bash
# (c) 2023 Pandora FMS

if [ ! -e pandora_xml_stress.agents ]
then
   echo "Error, cant find pandora_xml_stress.agets file"
   exit
fi

linux_inventory=1
windows_inventory=1

# Variables
agent_count=30

data_in=/var/spool/pandora/data_in/
description='Demo data Agent'
group='Servers'
current_date=`date +"%Y/%m/%d %H:%M:%S"`
current_utimestamp=`date +%s`

if [ $linux_inventory -eq 1 ] ; then

    if [ ! -e templates/inventory_linux.template ]; then
        echo "Error, cant find inventory linux template"
        exit
    fi 
    
    echo "Enable linux invetory: adding invetory data to ${agent_count} linux agent"

    for agent_name in $(cat pandora_xml_stress.agents | head -n ${agent_count}); do
        echo " - Adding invetory data to ${agent_name} linux agent"
        ip_add="10.0.0.$(( RANDOM % 255 + 1 ))"
        rand_number=$(( RANDOM % 10 + 1 ))
        cat "templates/inventory_linux.template" \
            | sed -e "s/{{description}}/${description}/g" \
            -e "s/{{group}}/${group}/g" \
            -e "s/{{agent_name}}/${agent_name}/g" \
            -e "s|{{date}}|${current_date}|g" \
            -e "s|{{ip_address}}|${ip_add}|g" \
            -e "s|{{rand_number}}|${rand_number}|g" \
            > /${data_in}/${agent_name}.${current_utimestamp}.data
    done
fi

if [ $windows_inventory -eq 1 ]; then
    if [ ! -e templates/inventory_windows.template ]; then
        echo "Error, cant find inventory Windows template"
        exit
    fi 
   echo "Enable Windows invetory: adding invetory data to ${agent_count} Windows agent"

    for agent_name in $(cat pandora_xml_stress.agents | tail -n ${agent_count}); do
        echo " - Adding invetory data to ${agent_name} windows agent"
        ip_add="172.16.5.$(( RANDOM % 255 + 1 ))"
        rand_number=$(( RANDOM % 100 + 1 ))
        cat "templates/inventory_windows.template" \
            | sed -e "s/{{description}}/${description}/g" \
            -e "s/{{group}}/${group}/g" \
            -e "s/{{agent_name}}/${agent_name}/g" \
            -e "s|{{date}}|${current_date}|g" \
            -e "s|{{rand_number}}|${rand_number}|g" \
            -e "s|{{ip_address}}|${ip_add}|g" \
            > /${data_in}/${agent_name}.${current_utimestamp}.data
    done
fi