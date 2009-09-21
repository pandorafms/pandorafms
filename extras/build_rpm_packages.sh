#!/bin/bash

CODEHOME=/home/slerena/code/pandora/trunk
CODEHOME_ENT=/home/slerena/code/artica/code/
RPMHOME=/usr/src/rpm

echo "Creating source tarballs (/usr/src/rpm/SOURCES)"
rm -Rf /usr/src/rpm/SOURCES/pandorafms_*.tar.gz

cd $CODEHOME/pandora_agents
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_agent-3.0.0.tar.gz --exclude \.svn --exclude nohup linux
cd $CODEHOME
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_server-3.0.0.tar.gz --exclude \.svn pandora_server

# Console OpenSource
cd $CODEHOME
tar zcvf $RPMHOME/SOURCES/pandorafms_console-3.0.0.tar.gz --exclude \.svn --exclude config.php --exclude enterprise pandora_console
# Console Enterprise
cd $CODEHOME_ENT/pandora/trunk/pandora_console
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_console_enterprise-3.0.0.tar.gz --exclude \.svn --exclude tree_view* enterprise/*
# Server OpenSource
cd $CODEHOME_ENT/pandora/trunk/pandora_server/
sudo tar zcvf $RPMHOME/SOURCES/pandorafms_server_enterprise-3.0.0.tar.gz --exclude \.svn  PandoraFMS-Enterprise
# Updatemanager Client keygen
cd $CODEHOME_ENT/updatemanager/keygen
sudo tar cvzf $RPMHOME/SOURCES/pandorafms_keygen-1.0.0.tar.gz --exclude .svn --exclude keygen --exclude keygen.i386.static --exclude pandora_keygen.spec pandora


echo "Creating RPMs  at $RPMHOME/RPMS"
cd $CODEHOME
sudo rpmbuild -ba pandora_console/pandora_console.spec
sudo rpmbuild -ba pandora_agents/linux/pandora_agent.spec
sudo rpmbuild -ba pandora_server/pandora_server.spec
sudo rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_console/enterprise/pandora_console_enterprise.spec
sudo rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_server/PandoraFMS-Enterprise/pandora_server_enterprise.spec
sudo rpmbuild -ba $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec


