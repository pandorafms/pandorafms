#!/bin/bash

CODEHOME=~/code/pandora/trunk
CODEHOME_ENT=~/code/artica
RPMHOME=/usr/src/packages

mkdir -p $RPMHOME/DEB

echo "Creating DEB packages in $RPMHOME/DEB"
cd $CODEHOME/pandora_console/DEBIAN
sudo bash ./make_deb_package.sh 
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME/pandora_server/DEBIAN
sudo bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME/pandora_agents/unix/DEBIAN
sudo bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME_ENT/pandora/trunk/pandora_console/DEBIAN
sudo bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME_ENT/pandora/trunk/pandora_server/PandoraFMS-Enterprise/DEBIAN
sudo bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

