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

pandora_version="4.1.1-140202"

package_pear=0
package_pandora=1

for param in $@
do
	if [ $param = "-h" -o $param = "--help" ]
	then
		echo "For only make packages of pear type +pear"
		echo "For not make packages of pear type -pear"
		exit 0
	fi

	if [ $param = "+pear" ]
	then
		package_pandora=0
	fi
	if [ $param = "-pear" ]
	then
		package_pear=0
	fi
done

if [ $package_pandora -eq 1 ]
then
	echo "Test if you have all the needed tools to make the packages."
	whereis dpkg-deb | cut -d":" -f2 | grep dpkg-deb > /dev/null
	if [ $? = 1 ]
	then
		echo "No found \"dpkg-deb\" aplication, please install."
		exit 1
	else
		echo "Found \"dpkg-debs\"."
	fi
fi

if [ $package_pear -eq 1 ]
then
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
mkdir -p temp_package
if [ $package_pandora -eq 1 ]
then
	mkdir -p temp_package/var/www/pandora_console

	echo "Make directory system tree for package."
	cp -R $(ls | grep -v temp_package | grep -v DEBIAN ) temp_package/var/www/pandora_console
	cp -R DEBIAN temp_package
	find temp_package/var/www/pandora_console -name ".svn" | xargs rm -Rf 
	rm -Rf temp_package/var/www/pandora_console/pandora_console.spec
	chmod 755 -R temp_package/DEBIAN
	touch temp_package/var/www/pandora_console/include/config.php
	
	
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
	mv temp_package.deb pandorafms.console_$pandora_version.deb
fi

if [ $package_pear -eq 1 ]
then
	echo "Make the package \"php-xml-rpc\"."
	cd temp_package
	dh-make-pear --maintainer "Miguel de Dios <miguel.dedios@artica.es>" XML_RPC
	cd php-xml-rpc-*
	dpkg-buildpackage -rfakeroot
	cd ..
	mv php-xml-rpc*.deb ..
	cd ..
fi


echo "Delete the \"temp_package\" temporary dir for job."
rm -Rf temp_package

echo "DONE: Package ready at: ../pandorafms.console_$pandora_version.deb"
