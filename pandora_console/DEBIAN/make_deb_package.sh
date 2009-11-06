#!/bin/bash

#Pandora FMS- http:#pandorafms.com
# ==================================================
# Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
# Please see http:#pandorafms.org for full contribution list

# This program is free software; you can redistribute it and/or
# modify it under the terms of the  GNU Lesser General Public License
# as published by the Free Software Foundation; version 2

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

pandora_console_version="3.0.0.rc2"

echo "Test if you have all the needed tools to make the packages."
whereis dpkg-deb | cut -d":" -f2 | grep dpkg-deb > /dev/null
if [ $? = 1 ]
then
	echo "No found \"dpkg-deb\" aplication, please install."
	exit 1
else
	echo "Found \"dpkg-debs\"."
fi

whereis dh-make-pear | cut -d":" -f2 | grep dh-make-pear > /dev/null
if [ $? = 1 ]
then
	echo " \"dh-make-pear\" aplication not found, please install."
	exit 1
else
	echo "Found \"dh-make-pear\"."
fi

whereis fakeroot | cut -d":" -f2 | grep fakeroot > /dev/null
if [ $? = 1 ]
then
	echo " \"fakeroot\" aplication not found, please install."
	exit 1
else
	echo "Found \"fakeroot\"."
fi

whereis dpkg-buildpackage | cut -d":" -f2 | grep dpkg-buildpackage > /dev/null
if [ $? = 1 ]
then
	echo " \"dpkg-buildpackage\" aplication not found, please install."
	exit 1
else
	echo "Found \"dpkg-buildpackage\"."
fi

cd ..

echo "Make a \"temp_package\" temporary dir for job."
mkdir -p temp_package/var/www/pandora_console

echo "Make directory system tree for package."
for item in `ls `
do
	echo -n "."

	if [ $item != 'temp_package' -a $item != 'pandora_console.spec' ]
	then
		if [ $item = 'DEBIAN' ]
		then
			cp $item temp_package -R
		else
			cp $item temp_package/var/www/pandora_console -R
		fi
	fi
done
echo "END"

#	if [ $item != 'temp_package' -a $item != 'pandora_console.spec' -a $item != 'make_deb_package.sh' ]
#	then
#	fi

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

echo "Calculate md5sum for md5sums package control file."
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
			echo  $md5" "$final_path >> temp_package/DEBIAN/md5sums
		fi
	fi
done
echo "END"

echo "Make the package \"Pandorafms console\"."
dpkg-deb --build temp_package
mv temp_package.deb pandorafms.console_$pandora_console_version.deb

echo "Make the package \"php-xml-rpc\"."
cd temp_package
dh-make-pear --maintainer "Miguel de Dios <miguel.dedios@artica.es>" XML_RPC
cd php-xml-rpc-*
dpkg-buildpackage -rfakeroot
cd ..
mv php-xml-rpc*.deb ..
cd ..


echo "Delete the \"temp_package\" temporary dir for job."
rm -rf temp_package
