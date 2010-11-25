#!/bin/bash

CODEHOME=~/code/pandora/branches/pandora_3.1
CODEHOME_ENT=~/code/artica/pandora/branches/3.1
CODEHOME_ENT_KEYGEN=~/code/artica/updatemanager/keygen
RPMHOME=/usr/src/packages

VERSION=$(grep 'my $pandora_version =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
KEYGEN_VERSION=$VERSION

echo "Creating source tarballs (/usr/src/rpm/SOURCES)"
sudo rm -Rf /usr/src/rpm/SOURCES/pandorafms_*.tar.gz

echo "Unix agent"
cd $CODEHOME/pandora_agents
sudo tar zcf $RPMHOME/SOURCES/pandorafms_agent_unix-$VERSION.tar.gz --exclude \.svn --exclude nohup unix

echo "Console OpenSource"
cd $CODEHOME
sudo tar zcf $RPMHOME/SOURCES/pandorafms_console-$VERSION.tar.gz --exclude \.svn --exclude config.php --exclude enterprise pandora_console

echo "Server Opensource"
cd $CODEHOME
sudo tar zcf $RPMHOME/SOURCES/pandorafms_server-$VERSION.tar.gz --exclude \.svn --exclude *.spec --exclude DEBIAN --exclude RHEL pandora_server
sudo cp $CODEHOME/pandora_server/RHEL/* $RPMHOME/SOURCES

echo "Generating Tarballs for enterprise version"

echo "Console Enterprise"
cd $CODEHOME_ENT/pandora_console
sudo tar zcf $RPMHOME/SOURCES/pandorafms_console_enterprise-$VERSION.tar.gz --exclude \.svn enterprise/*

echo "Server Enterprise"
cd $CODEHOME_ENT/pandora_server/
sudo tar zcf $RPMHOME/SOURCES/pandorafms_server_enterprise-$VERSION.tar.gz --exclude \.svn --exclude *.spec --exclude DEBIAN --exclude RHEL PandoraFMS-Enterprise

echo "Updatemanager Client keygen"
cd $CODEHOME_ENT_KEYGEN
sudo tar czf $RPMHOME/SOURCES/pandorafms_keygen-$KEYGEN_VERSION.tar.gz --exclude .svn --exclude keygen --exclude keygen.i386.static --exclude pandora_keygen.spec pandora

