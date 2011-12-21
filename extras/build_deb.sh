#!/bin/bash
source build_vars.sh

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
cd $PANDHOME_ENT/pandora_console/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

# Enterprise server
cd $PANDHOME_ENT/pandora_server/PandoraFMS-Enterprise/DEBIAN && bash ./make_deb_package.sh && mv ../*.deb $RPMHOME/DEB || exit 1

exit 0

