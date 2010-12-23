#!/bin/bash
CODEHOME=~/code/pandora/trunk
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages
VERSION=$(grep 'my $pandora_version =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
KEYGEN_VERSION=$(grep "%define version" $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec | awk '{print $3}')

if [ ! -d $RPMHOME/SOURCES ]; then
	mkdir -p $RPMHOME/SOURCES || exit 1
fi

echo "Creating source tarballs in $RPMHOME/SOURCES"

# Console
cd $CODEHOME && tar zcvf $RPMHOME/SOURCES/pandorafms_console-$VERSION.tar.gz --exclude \.svn --exclude config.php --exclude enterprise --exclude \*\.po pandora_console || exit 1

# Server
cd $CODEHOME && tar zcvf $RPMHOME/SOURCES/pandorafms_server-$VERSION.tar.gz --exclude \.svn pandora_server || exit 1

# Linux and Unix agents
cd $CODEHOME/pandora_agents && tar zcvf $RPMHOME/SOURCES/pandorafms_agent-$VERSION.tar.gz --exclude \.svn --exclude nohup linux && tar zvcf $RPMHOME/SOURCES/pandorafms_agent_unix-$VERSION.tar.gz --exclude \.svn --exclude nohup --exclude NT4 unix || exit 1

# Enterprise console
cd $CODEHOME_ENT/pandora/trunk/pandora_console && tar zcvf $RPMHOME/SOURCES/pandorafms_console_enterprise-$VERSION.tar.gz --exclude \.svn enterprise/* || exit 1

# Enterprise server
cd $CODEHOME_ENT/pandora/trunk/pandora_server/ && tar zcvf $RPMHOME/SOURCES/pandorafms_server_enterprise-$VERSION.tar.gz --exclude \.svn  PandoraFMS-Enterprise || exit 1

# Updatemanager keygen
cd $CODEHOME_ENT/updatemanager/keygen && tar cvzf $RPMHOME/SOURCES/pandorafms_keygen-$KEYGEN_VERSION.tar.gz --exclude .svn --exclude keygen --exclude keygen.i386.static --exclude pandora_keygen.spec pandora || exit 1

exit 0

