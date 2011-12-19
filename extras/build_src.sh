#!/bin/bash
CODEHOME=~/code/pandora/branches/pandora_4.0
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages
VERSION=$(grep 'my $pandora_version =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
BUILD=$(grep 'my $pandora_build =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
KEYGEN_VERSION=$(grep "%define version" $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec | awk '{print $3}')

# Add build string for nightly builds
if [ "$1" == "nightly" ]; then
	LOCAL_VERSION="$VERSION-$BUILD"
else
	LOCAL_VERSION=$VERSION
fi

if [ ! -d $RPMHOME/SOURCES ]; then
	mkdir -p $RPMHOME/SOURCES || exit 1
fi

echo "Creating source tarballs in $RPMHOME/SOURCES"

# Console
cd $CODEHOME && tar zcvf $RPMHOME/SOURCES/pandorafms_console-$LOCAL_VERSION.tar.gz --exclude \.svn --exclude config.php --exclude enterprise pandora_console || exit 1

# Server
cd $CODEHOME && tar zcvf $RPMHOME/SOURCES/pandorafms_server-$LOCAL_VERSION.tar.gz --exclude \.svn pandora_server || exit 1

# Linux agent
cd $CODEHOME/pandora_agents/shellscript && tar zcvf $RPMHOME/SOURCES/pandorafms_agent-$LOCAL_VERSION.tar.gz --exclude \.svn --exclude nohup linux || exit 1

# Unix agent
cd $CODEHOME/pandora_agents && tar zvcf $RPMHOME/SOURCES/pandorafms_agent_unix-$LOCAL_VERSION.tar.gz --exclude \.svn --exclude nohup --exclude NT4 unix || exit 1

# Enterprise console
cd $CODEHOME_ENT/pandora/branches/4.0/pandora_console && tar zcvf $RPMHOME/SOURCES/pandorafms_console_enterprise-$LOCAL_VERSION.tar.gz --exclude \.svn enterprise/* || exit 1

# Enterprise server
cd $CODEHOME_ENT/pandora/branches/4.0/pandora_server/ && tar zcvf $RPMHOME/SOURCES/pandorafms_server_enterprise-$LOCAL_VERSION.tar.gz --exclude \.svn  PandoraFMS-Enterprise || exit 1

# Updatemanager keygen
cd $CODEHOME_ENT/updatemanager/keygen && tar cvzf $RPMHOME/SOURCES/pandorafms_keygen-$KEYGEN_VERSION.tar.gz --exclude .svn --exclude keygen --exclude keygen.i386.static --exclude pandora_keygen.spec pandora || exit 1

# Create symlinks needed to build RPM packages
if [ "$1" == "nightly" ]; then
	ln -s $RPMHOME/SOURCES/pandorafms_console-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/pandorafms_console-$VERSION.tar.gz || exit 1
	ln -s $RPMHOME/SOURCES/pandorafms_server-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/pandorafms_server-$VERSION.tar.gz || exit 1
	ln -s $RPMHOME/SOURCES/pandorafms_agent-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/pandorafms_agent-$VERSION.tar.gz || exit 1
	ln -s $RPMHOME/SOURCES/pandorafms_agent_unix-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/pandorafms_agent_unix-$VERSION.tar.gz || exit 1
	ln -s $RPMHOME/SOURCES/pandorafms_console_enterprise-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/pandorafms_console_enterprise-$VERSION.tar.gz || exit 1
	ln -s $RPMHOME/SOURCES/pandorafms_server_enterprise-$LOCAL_VERSION.tar.gz $RPMHOME/SOURCES/pandorafms_server_enterprise-$VERSION.tar.gz || exit 1
fi

exit 0

