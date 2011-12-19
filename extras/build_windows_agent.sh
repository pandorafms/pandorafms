#!/bin/bash
CODEHOME=~/code/pandora/branches/pandora_4.0
RPMHOME=/usr/src/packages

if [ ! -d $RPMHOME/EXE ]; then
	mkdir -p $RPMHOME/EXE || exit 1
fi

echo "Creating Pandora FMS Agent Windows installer in $RPMHOME/EXE"

# Windows agent
rm -rf $CODEHOME/pandora_agents/win32/installer/output/*.exe
cd $CODEHOME/pandora_agents/win32
./build.sh
cp $CODEHOME/pandora_agents/win32/installer/output/*.exe $RPMHOME/EXE/

