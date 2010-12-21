#!/bin/bash
CODEHOME=~/code/pandora/trunk
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages
VERSION=$(grep 'my $pandora_version =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
KEYGEN_VERSION=$(grep "%define version" $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec | awk '{print $3}')

echo "Creating source tarballs in $RPMHOME/SOURCES"
rm -Rf /usr/src/rpm/SOURCES/pandorafms_*.tar.gz

cd $CODEHOME/pandora_agents
tar zcvf $RPMHOME/SOURCES/pandorafms_agent-$VERSION.tar.gz --exclude \.svn --exclude nohup linux
tar zvcf $RPMHOME/SOURCES/pandorafms_agent_unix-$VERSION.tar.gz --exclude \.svn --exclude nohup --exclude NT4 unix

cd $CODEHOME
tar zcvf $RPMHOME/SOURCES/pandorafms_server-$VERSION.tar.gz --exclude \.svn pandora_server

# Console OpenSource
cd $CODEHOME
tar zcvf $RPMHOME/SOURCES/pandorafms_console-$VERSION.tar.gz --exclude \.svn --exclude config.php --exclude enterprise pandora_console

# Console Enterprise
cd $CODEHOME_ENT/pandora/trunk/pandora_console
tar zcvf $RPMHOME/SOURCES/pandorafms_console_enterprise-$VERSION.tar.gz --exclude \.svn enterprise/*

# Server OpenSource
cd $CODEHOME_ENT/pandora/trunk/pandora_server/
tar zcvf $RPMHOME/SOURCES/pandorafms_server_enterprise-$VERSION.tar.gz --exclude \.svn  PandoraFMS-Enterprise

# Updatemanager Client keygen
cd $CODEHOME_ENT/updatemanager/keygen
tar cvzf $RPMHOME/SOURCES/pandorafms_keygen-$KEYGEN_VERSION.tar.gz --exclude .svn --exclude keygen --exclude keygen.i386.static --exclude pandora_keygen.spec pandora

