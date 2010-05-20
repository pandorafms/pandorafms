#!/bin/bash

CODEHOME=~/code/pandora/trunk
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages

echo "Creating RPMs  at $RPMHOME/RPMS"
cd $CODEHOME
sudo rpmbuild -ba pandora_console/pandora_console.spec
sudo rpmbuild -ba pandora_agents/unix/pandora_agent.spec
sudo rpmbuild -ba pandora_server/pandora_server.spec
sudo rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_console/enterprise/pandora_console_enterprise.spec
sudo rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_server/PandoraFMS-Enterprise/pandora_server_enterprise.spec
sudo rpmbuild -ba $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec


