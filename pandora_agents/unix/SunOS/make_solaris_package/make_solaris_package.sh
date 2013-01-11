#!/bin/sh

# **********************************************************************
# Pandora FMS Agent package builder for Solaris
# (c) 2010-2013 Junichi Satoh <junichi@rworks.jp>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the  GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# **********************************************************************

AGENT_VERSION="4.0.3"

PKGFILE="PandoraFMS-agent-$AGENT_VERSION.pkg"

# make pkginfo file
DATE=`date '+%Y%m%d%H'`
echo "PKG=PandoraAgent" > pkginfo
echo "NAME=Pandora FMS agent" >> pkginfo
echo "ARCH=all" >> pkginfo
echo "CATEGORY=application" >> pkginfo
echo "VERSION=$AGENT_VERSION" >> pkginfo
echo "VENDOR=http://pandorafms.org/" >> pkginfo
echo "PSTAMP=$DATE" >> pkginfo
echo "CLASSES=none" >> pkginfo
echo "BASEDIR=/" >> pkginfo

# make work directory.
mkdir -p /tmp/pandora/usr/bin
mkdir -p /tmp/pandora/usr/man/man1
mkdir -p /tmp/pandora/etc/pandora
mkdir -p /tmp/pandora/etc/init.d
mkdir -p /tmp/pandora/usr/share/pandora_agent/plugins

# copy executables
cp ../../pandora_agent /tmp/pandora/usr/bin
cp ../../tentacle_client /tmp/pandora/usr/bin
cp ../../tentacle_server /tmp/pandora/usr/bin
cp ../../pandora_agent_daemon /tmp/pandora/etc/init.d
cp ../../pandora_agent_exec /tmp/pandora/usr/bin

# copy plugin files
cp ../../plugins/* /tmp/pandora/usr/share/pandora_agent/plugins

# copy configuration file
cp ../pandora_agent.conf /tmp/pandora/etc/pandora

# copy man pages
cp ../../man/man1/pandora_agent.1.gz /tmp/pandora/usr/man/man1
gunzip /tmp/pandora/usr/man/man1/pandora_agent.1.gz
cp ../../man/man1/tentacle_client.1.gz /tmp/pandora/usr/man/man1
gunzip /tmp/pandora/usr/man/man1/tentacle_client.1.gz

# make package.
pkgmk -o -r /tmp/pandora -d /tmp/pandora 
CURRENT_DIR=`pwd`
pkgtrans -s /tmp/pandora $CURRENT_DIR/$PKGFILE PandoraAgent

# delete work files
rm -rf /tmp/pandora

echo ""
echo "pkg file is created: $PKGFILE"
