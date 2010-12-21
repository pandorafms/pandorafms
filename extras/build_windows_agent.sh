#!/bin/bash
CODEHOME=~/code/pandora/trunk
RPMHOME=/usr/src/packages

mkdir -p $RPMHOME/EXE

echo "Creating Pandora FMS Agent Windows installer in $RPMHOME/EXE"
rm -rf $CODEHOME/pandora_agents/win32/installer/output/*.exe
cd $CODEHOME/pandora_agents/win32
./build.sh
cp $CODEHOME/pandora_agents/win32/installer/output/*.exe $RPMHOME/EXE/

