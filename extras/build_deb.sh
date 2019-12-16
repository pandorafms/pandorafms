#!/bin/bash
source build_vars.sh

if [ ! -d $RPMHOME/DEB ]; then
	mkdir -p $RPMHOME/DEB || exit 1
fi

echo "Creating DEB packages in $RPMHOME/DEB"

# Console
# Extra files to be added to rpm.
if [ "$X86_64" == "" ]; then
	# Fake gotty.
	echo 'Only x86_64 is supported' > $CODEHOME/pandora_console/gotty
	chmod +x pandora_console/gotty
else 
	cp /root/bin/winexe/x64/gotty $CODEHOME/pandora_console/
fi
cd $CODEHOME/pandora_console/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Cleanup.
rm -f pandora_console/gotty

# Server
cd $CODEHOME/pandora_server/DEBIAN  && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Unix agent
cd $CODEHOME/pandora_agents/unix/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Enterprise console
cd $PANDHOME_ENT/pandora_console/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

exit 0

