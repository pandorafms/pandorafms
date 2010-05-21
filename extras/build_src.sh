#!/bin/bash

CODEHOME=~/code/pandora/trunk
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages
VERSION=$(grep 'my $pandora_version =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
KEYGEN_VERSION=$(grep "%define version" $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec | awk '{print $3}')

echo "Creating source tarballs (/usr/src/rpm/SOURCES)"
rm -Rf /usr/src/rpm/SOURCES/pandorafms_*.tar.gz

cd $CODEHOME/pandora_agents
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_agent-$VERSION.tar.gz --exclude \.svn --exclude nohup linux
sudo tar zvcf $RPMHOME/SOURCES/pandorafms_agent_unix-$VERSION.tar.gz --exclude \.svn --exclude nohup unix

cd $CODEHOME
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_server-$VERSION.tar.gz --exclude \.svn pandora_server

# Console OpenSource
cd $CODEHOME
tar zcvf $RPMHOME/SOURCES/pandorafms_console-$VERSION.tar.gz --exclude \.svn --exclude config.php --exclude enterprise pandora_console

# Console Enterprise
cd $CODEHOME_ENT/pandora/trunk/pandora_console
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_console_enterprise-$VERSION.tar.gz --exclude \.svn enterprise/*

# Server OpenSource
cd $CODEHOME_ENT/pandora/trunk/pandora_server/
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_server_enterprise-$VERSION.tar.gz --exclude \.svn  PandoraFMS-Enterprise

# Updatemanager Client keygen
cd $CODEHOME_ENT/updatemanager/keygen
sudo tar cvzf $RPMHOME/SOURCES/pandorafms_keygen-$KEYGEN_VERSION.tar.gz --exclude .svn --exclude keygen --exclude keygen.i386.static --exclude pandora_keygen.spec pandora

echo " "
echo " ABORTING RPM Creation, only tarball packages build "
exit

echo "Creating RPMs  at $RPMHOME/RPMS"
cd $CODEHOME
sudo rpmbuild -ba pandora_console/pandora_console.spec
sudo rpmbuild -ba pandora_agents/linux/pandora_agent.spec
sudo rpmbuild -ba pandora_agents/unix/pandora_agent.spec
sudo rpmbuild -ba pandora_server/pandora_server.spec
sudo rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_console/enterprise/pandora_console_enterprise.spec
sudo rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_server/PandoraFMS-Enterprise/pandora_server_enterprise.spec
sudo rpmbuild -ba $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec


