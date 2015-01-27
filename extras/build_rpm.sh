#!/bin/bash
source build_vars.sh

if [ ! -d $RPMHOME/RPMS ]; then
	mkdir -p $RPMHOME/RPMS || exit 1
fi

echo "Creating RPM packages in $RPMHOME/RPMS"

# Console
rpmbuild -ba $CODEHOME/pandora_console/pandora_console.spec || exit 1

# Server
rpmbuild -ba $CODEHOME/pandora_server/pandora_server.spec || exit 1

# Unix agent
rpmbuild -ba $CODEHOME/pandora_agents/unix/pandora_agent.spec || exit 1

# Enterprise console
rpmbuild -ba $PANDHOME_ENT/pandora_console/enterprise/pandora_console_enterprise.spec || exit 1

exit 0

