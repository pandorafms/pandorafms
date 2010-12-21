#!/bin/bash
CODEHOME=~/code/pandora/trunk
CODEHOME_ENT=~/code/artica/code
RPMHOME=/usr/src/packages
START_DIR=`pwd`

mkdir -p $RPMHOME/DEB

echo "Creating DEB packages in $RPMHOME/DEB"
cd $CODEHOME/pandora_console/DEBIAN
bash ./make_deb_package.sh 
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME/pandora_server/DEBIAN
bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME/pandora_agents/unix/DEBIAN
bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME_ENT/pandora/trunk/pandora_console/DEBIAN
bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

cd $CODEHOME_ENT/pandora/trunk/pandora_server/PandoraFMS-Enterprise/DEBIAN
bash ./make_deb_package.sh
cd ..
mv *.deb $RPMHOME/DEB

cd $START_DIR

