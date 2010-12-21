#!/bin/bash
CODEHOME=~/code/pandora/trunk
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages

echo "Creating RPM packages in $RPMHOME/RPMS"

rpmbuild -ba $CODEHOME/pandora_console/pandora_console.spec
rpmbuild -ba $CODEHOME/pandora_agents/unix/pandora_agent.spec
rpmbuild -ba $CODEHOME/pandora_server/pandora_server.spec
rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_console/enterprise/pandora_console_enterprise.spec
rpmbuild -ba $CODEHOME_ENT/pandora/trunk/pandora_server/PandoraFMS-Enterprise/pandora_server_enterprise.spec
rpmbuild -ba $CODEHOME_ENT/updatemanager/keygen/pandora/pandora_keygen.spec

