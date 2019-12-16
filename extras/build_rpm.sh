#!/bin/bash
source build_vars.sh

if [ ! -d $RPMHOME/RPMS ]; then
	mkdir -p $RPMHOME/RPMS || exit 1
fi

echo "Creating RPM packages in $RPMHOME/RPMS"

# Console
# Extra files to be added to rpm.
if [ "$X86_64" == "" ]; then
	# Fake gotty.
	echo 'Only x86_64 is supported' > $CODEHOME/pandora_console/gotty
	chmod +x pandora_console/gotty
else 
	cp /root/bin/winexe/x64/gotty $CODEHOME/pandora_console/
fi

rpmbuild -ba $CODEHOME/pandora_console/pandora_console.spec || exit 1

# Cleanup.
rm -f pandora_console/gotty

# Server
rpmbuild -ba $CODEHOME/pandora_server/pandora_server.spec || exit 1

# Unix agent
rpmbuild -ba $CODEHOME/pandora_agents/unix/pandora_agent.spec || exit 1

# Enterprise console
rpmbuild -ba $PANDHOME_ENT/pandora_console/enterprise/pandora_console_enterprise.spec || exit 1

exit 0

