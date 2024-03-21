#!/bin/bash

#Pandora FMS - https://pandorafms.com
# ==================================================
# Copyright (c) 2005-2023 Pandora FMS
# Please see http:#pandorafms.org for full contribution list

# This program is free software; you can redistribute it and/or
# modify it under the terms of the  GNU Lesser General Public License
# as published by the Free Software Foundation; version 2

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

pandora_version="7.0NG.776-240321"

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
		if [ "$DPKG_DEB" == "" ]; then
			echo "No found \"dpkg-deb\" aplication, please install."
			exit 1
		fi

		echo ">> Using dockerized version of dpkg-deb: "
		echo "  $DPKG_DEB"
		# Use dockerized app.
		USE_DOCKER_APP=1
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

	whereis dpkg-buildpackage | cut -d":" -f2 | grep dpkg-buildpackage > /dev/null
	if [ $? = 1 ]
	then
		echo " \"dpkg-buildpackage\" aplication not found, please install."
		exit 1
	else
		echo "Found \"dpkg-buildpackage\"."
	fi
fi

cd ..

echo "Make a \"temp_package\" temporary dir for job."
mkdir -p temp_package
if [ $package_pandora -eq 1 ]
then
	mkdir -p temp_package/var/www/html/pandora_console
	mkdir -p temp_package/var/log/pandora
	mkdir -p temp_package/etc/logrotate.d
	mkdir -p temp_package/etc/init.d

	echo "Make directory system tree for package."
	cp -R $(ls | grep -v temp_package | grep -v DEBIAN | grep -v pandorafms.console_$pandora_version.deb) temp_package/var/www/html/pandora_console
	cp -R DEBIAN temp_package
	cp -aRf pandora_console_logrotate_ubuntu temp_package/etc/logrotate.d/pandora_console
	cp -aRf pandora_websocket_engine temp_package/etc/init.d/
	find temp_package/var/www/html/pandora_console -name ".svn" | xargs rm -Rf 
	rm -Rf temp_package/var/www/html/pandora_console/pandora_console.spec
	chmod 755 -R temp_package/DEBIAN
	
	
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
	FILES=`find temp_package`
	while read item
	do
		echo -n "."
		if [ ! -d "$item" ]
		then
			echo "$item" | grep "DEBIAN" > /dev/null
			#last command success
			if [ $? -eq 1 ]
			then
				md5=`md5sum "$item" | cut -d" " -f1`
				
				#delete "temp_package" in the path
				final_path=${item#temp_package}
				echo  $md5" "$final_path >> temp_package/DEBIAN/md5sums
			fi
		fi
	done < <(echo "$FILES")
	echo "END"

	echo "Make the package \"Pandorafms console\"."
	if [ "$USE_DOCKER_APP" == "1" ]; then 
		eval $DPKG_DEB --root-owner-group --build temp_package
	else
		dpkg-deb --root-owner-group --build temp_package
	fi
	mv temp_package.deb pandorafms.console_$pandora_version.deb
fi

if [ $package_pear -eq 1 ]
then
	echo "Make the package \"php-xml-rpc\"."
	cd temp_package
	dh-make-pear --maintainer "Pandora FMS <info@pandorafms.com>" XML_RPC
	cd php-xml-rpc-*
	dpkg-buildpackage -rfakeroot
	cd ..
	mv php-xml-rpc*.deb ..
	cd ..
fi


echo "Delete the \"temp_package\" temporary dir for job."
rm -Rf temp_package

echo "DONE: Package ready at: ../pandorafms.console_$pandora_version.deb"
