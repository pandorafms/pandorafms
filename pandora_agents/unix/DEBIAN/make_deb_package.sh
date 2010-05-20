#!/bin/bash

#Pandora FMS- http:#pandorafms.com
# ==================================================
# Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
# Please see http:#pandorafms.org for full contribution list

# This program is free software; you can redistribute it and/or
# modify it under the terms of the  GNU Lesser General Public License
# as published by the Free Software Foundation; version 2

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

pandora_version="3.1rc1"

echo "Test if you has the tools for to make the packages."
whereis dpkg-deb | cut -d":" -f2 | grep dpkg-deb > /dev/null
if [ $? = 1 ]
then
	echo "No found \"dpkg-deb\" aplication, please install."
	exit 1
else
	echo "Found \"dpkg-debs\"."
fi

cd ..

echo "Make a \"temp_package\" temp dir for job."
#~ mkdir -p temp_package/usr/share/pandora_agent
#~ mkdir -p temp_package/var/spool/pandora/data_out
#~ mkdir -p temp_package/var/log/pandora/ 
#~ mkdir -p temp_package/etc/pandora
#~ mkdir -p temp_package/etc/init.d/
#~ mkdir -p temp_package/usr/bin

mkdir -p temp_package/usr
mkdir -p temp_package/usr/share/pandora_agent/
mkdir -p temp_package/usr/bin/
mkdir -p temp_package/usr/sbin/
mkdir -p temp_package/etc/pandora/
mkdir -p temp_package/etc/init.d/
mkdir -p temp_package/var/log/pandora/

echo "Make directory system tree for package."
cp DEBIAN temp_package -R
chmod 755 -R temp_package/DEBIAN

cp -aRf * temp_package/usr/share/pandora_agent/
cp -aRf tentacle_client temp_package/usr/bin/
cp -aRf pandora_agent temp_package/usr/bin/
cp -aRf pandora_agent_daemon temp_package/etc/init.d/pandora_agent_daemon
cp Linux/pandora_agent.conf temp_package/etc/pandora/


#~ PANDORA_LOG=temp_package/var/log/pandora/pandora_agent.log
#~ PANDORA_BIN=temp_package/usr/bin/pandora_agent
#~ PANDORA_HOME=temp_package/usr/share/pandora_agent
#~ TENTACLE=temp_package/usr/bin/tentacle_client
#~ PANDORA_CFG=temp_package/etc/pandora
#~ PANDORA_STARTUP=temp_package/etc/init.d/pandora_agent_daemon

# Create logfile
#~ if [ ! -z "`touch $PANDORA_LOG`" ]
#~ then
		#~ echo "Seems to be a problem generating logfile ($PANDORA_LOG) please check it";
		#~ exit
#~ else
		#~ echo "Creating logfile at $PANDORA_LOG..."
#~ fi

#~ # Copying agent
#~ echo "Copying Pandora FMS Agent to $PANDORA_BIN..."
#~ cp pandora_agent $PANDORA_BIN
#~ 
#~ echo "Copying Pandora FMS Agent contrib dir to $PANDORA_HOME/..."
#~ cp pandora_agent_daemon $PANDORA_HOME
#~ 
#~ echo "Copying default agent configuration to $PANDORA_HOME/pandora_agent.conf"
#~ cp pandora_agent.conf $PANDORA_HOME/pandora_agent.conf
#~ 
#~ echo "Copying Pandora FMS Agent plugins to $PANDORA_HOME/plugins..."
#~ cp -r plugins $PANDORA_HOME
#~ 
#~ echo "Copying tentacle client to $TENTACLE"
#~ cp tentacle_client $TENTACLE
#~ 
#~ echo "Linking start-up daemon script at $PANDORA_STARTUP";
#~ cp pandora_agent_daemon $PANDORA_STARTUP
#~ 
#~ touch $PANDORA_CFG/pandora_agent.conf

echo "Remove the SVN files and other temp files."
for item in `find temp_package`
do
	echo -n "."
	echo $item | grep "svn" > /dev/null
	#last command success
	if [ $? -eq 0 ]
	then
		rm -rf $item
	fi
	
	echo $item | grep "make_deb_package.sh" > /dev/null
	#last command success
	if [ $? -eq 0 ]
	then
		rm -rf $item
	fi
done
echo "END"

echo "Calcule md5sum for md5sums file control of package"
for item in `find temp_package`
do
	echo -n "."
	if [ ! -d $item ]
	then
		echo $item | grep "DEBIAN" > /dev/null
		#last command success
		if [ $? -eq 1 ]
		then
			md5=`md5sum $item | cut -d" " -f1`
			
			#delete "temp_package" in the path
			final_path=${item#temp_package}
			echo $md5" "$final_path >> temp_package/DEBIAN/md5sums
		fi
	fi
done
echo "END"

echo "Make the package \"Pandorafms console\"."
dpkg-deb --build temp_package
mv temp_package.deb pandorafms.agent_$pandora_version.deb

echo "Delete the \"temp_package\" temp dir for job."
rm -rf temp_package
