#!/bin/bash
# (c) 2023 Pandora FMS, by Sancho Lerena
# This script is used to create a huge load of data
# It creates a group from each item in groupnames.txt
# It creates an user from each item in usernames.txt
# It gives an association to each user for a random group using a Read Only operator profile
# It moves each agent to a primary group, randomly from groupname.txt

if [ ! -e usernames.txt ]
then
   echo "Error, I cannot find usernames.txt"
   exit
fi

if [ ! -e groupnames.txt ]
then
   echo "Error, I cannot find groupnames.txt"
   exit
fi

# Create users from usernames.txt
for a in `cat usernames.txt`
do
    /usr/share/pandora_server/util/pandora_manage.pl /etc/pandora/pandora_server.conf --create_user $a $a 0 "Created by CLI"
done


# Create groups from groupnames.txt
for a in `cat groupnames.txt`
do
    /usr/share/pandora_server/util/pandora_manage.pl /etc/pandora/pandora_server.conf --create_group  $a
done

# Associate a group to each user
TOTAL_GROUPS=`cat groupnames.txt | wc -l`
for username in `cat usernames.txt`
do
    RAN=`echo $(($RANDOM % $TOTAL_GROUPS + 1))`
    GROUP_NAME=`cat groupnames.txt | tail -$RAN | head -1`

    /usr/share/pandora_server/util/pandora_manage.pl /etc/pandora/pandora_server.conf --add_profile $username "Operator (Read)" $GROUP_NAME
done

# Move each agent to a random group
TOTAL_GROUPS=`cat groupnames.txt | wc -l`
for agentname in `/usr/share/pandora_server/util/pandora_manage.pl /etc/pandora/pandora_server.conf --get_agents | cut -f 2 -d ","`
do
    RAN=`echo $(($RANDOM % $TOTAL_GROUPS + 1))`
    GROUP_NAME=`cat groupnames.txt | tail -$RAN | head -1`
    /usr/share/pandora_server/util/pandora_manage.pl /etc/pandora/pandora_server.conf --update_agent $agentname group_name $GROUP_NAME
done
