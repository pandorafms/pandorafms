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

pandora_console_version="3.0.0.RC3"

echo "This script to make deb must run as root (because the dh-make-perl need this). Then test if you are root."
if [ `id -u` != 0 ]
then
	echo "You aren't root."
	exit 1
fi

echo "Test if you has the tools for to make the packages."
whereis dh-make-perl | cut -d":" -f2 | grep dh-make-perl > /dev/null
if [ $? = 1 ]
then
	echo "No found \"dh-make-perl\" aplication, please install."
	exit 1
else
	echo "Found \"dh-make-perl\"."
fi

cd ..

echo "Make a \"temp_package\" temp dir for job."
mkdir temp_package

echo "Make the fake tree system in \"temp_package\"."
mkdir -p temp_package/var/spool/pandora/data_in/conf
mkdir -p temp_package/var/spool/pandora/data_in/md5
mkdir -p temp_package/var/log/pandora
mkdir -p temp_package/etc/pandora
mkdir -p temp_package/etc/init.d/
mkdir -p temp_package/etc/logrotate.d
mkdir -p temp_package/usr/share/pandora_server
mkdir -p temp_package/usr/local/bin

echo "Make the perl of Pandora Server."
perl Makefile.PL
make
cat Makefile | sed -e "s/PREFIX = \/usr/PREFIX = temp_package\/usr/" > Makefile.temp
mv Makefile.temp Makefile
rm Makefile.temp
make install

echo "Copy other files in fake file."
cp util/pandora_logrotate temp_package/etc/logrotate.d/pandora

cp bin/tentacle_server temp_package/usr/local/bin
cp util/tentacle_serverd temp_package/etc/init.d/tentacle_serverd

cp conf/pandora_server.conf temp_package/etc/pandora/
cp util/pandora_server temp_package/etc/init.d/

cp -R util temp_package/usr/share/pandora_server
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
			echo  $md5" "$final_path >> temp_package/DEBIAN/md5sums
		fi
	fi
done

echo "END"

echo "Make the package \"Pandorafms server\"."
dpkg-deb --build temp_package
mv temp_package.deb pandorafms.server_$pandora_console_version.deb
chmod 777 pandorafms.server_$pandora_console_version.deb

echo "Make the package \"libnet-traceroute-pureperl-perl\"."
cd temp_package
dh-make-perl --build --cpan Net::Traceroute::PurePerl
chmod 777 libnet-traceroute-pureperl-perl*.deb
mv libnet-traceroute-pureperl-perl*.deb ..
cd ..

echo "Make the package \"libnet-traceroute-perl\"."
cd temp_package
dh-make-perl --build --cpan Net::Traceroute
chmod 777 libnet-traceroute-perl*.deb
mv libnet-traceroute-perl*.deb ..
cd ..

echo "Delete the \"temp_package\" temp dir for job."
rm -rf temp_package
