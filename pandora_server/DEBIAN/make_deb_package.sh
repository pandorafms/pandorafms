#!/bin/bash

# Pandora FMS- http://pandorafms.com
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

pandora_server_version="3.0.0"

echo "This script to make deb must run as root (because the dh-make-perl need this). Then test if you are root."
if [ `id -u` != 0 ]
then
	echo "You aren't root."
	exit 1
fi

cd ..

echo "Make a \"temp_package\" temp dir for job."
mkdir temp_package

echo "Make the perl of Pandora Server."
perl Makefile.PL
make

# Adjust Makefile to use our "fake" root dir to install libraries and also binaries"
cat Makefile | sed -e "s/PREFIX = \/usr/PREFIX = temp_package\/usr/" > Makefile.temp

# This is needed to create .DEB in OpenSUSE.

cat Makefile.temp | sed -e "s/INSTALLBIN = .*/INSTALLBIN = temp_package\/usr\/bin/" > Makefile
cat Makefile | sed -e "s/INSTALLSITEBIN = .*/INSTALLSITEBIN = temp_package\/usr\/bin/" > Makefile.temp
cat Makefile.temp | sed -e "s/INSTALLVENDORBIN = .*/INSTALLVENDORBIN = temp_package\/usr\/bin/" > Makefile
cat Makefile | sed -e "s/INSTALLSCRIPT = .*/INSTALLSCRIPT = temp_package\/usr\/bin/" > Makefile.temp
cat Makefile.temp | sed -e "s/INSTALLSITESCRIPT = .*/INSTALLSITESCRIPT = temp_package\/usr\/bin/" > Makefile
cat Makefile | sed -e "s/INSTALLVENDORSCRIPT = .*/INSTALLVENDORSCRIPT = temp_package\/usr\/bin/" > Makefile.temp

mv Makefile.temp Makefile

make install

echo "Copy other files in fake file."
cp -R DEBIAN temp_package/

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
	
	echo $item | grep "perllocal.pod" > /dev/null
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

echo "Calcule md5sum for md5sums file control of package."
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

echo "Make the package \"Pandorafms server\"."
dpkg-deb --build temp_package
mv temp_package.deb pandorafms.server_enterprise_$pandora_server_version.deb
chmod 777 pandorafms.server_enterprise_$pandora_server_version.deb

echo "Delete the \"temp_package\" temp dir for job."
rm Makefile
rm -rf blib
rm pm_to_blib
rm -rf temp_package
