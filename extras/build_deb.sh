#!/bin/bash
CODEHOME=~/code/pandora/branches/pandora_4.0
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages

if [ ! -d $RPMHOME/DEB ]; then
	mkdir -p $RPMHOME/DEB || exit 1
fi

echo "Creating DEB packages in $RPMHOME/DEB"

# Console
cd $CODEHOME/pandora_console/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Server
cd $CODEHOME/pandora_server/DEBIAN  && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Unix agent
cd $CODEHOME/pandora_agents/unix/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Enterprise console
cd $CODEHOME_ENT/pandora/branches/4.0/pandora_console/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Enterprise server
cd $CODEHOME_ENT/pandora/branches/4.0/pandora_server/PandoraFMS-Enterprise/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

exit 0

