#!/bin/bash
CODEHOME=/root/code/pandorafms
CODEHOME_ENT=/root/code/pandora_enterprise
PANDHOME_ENT=$CODEHOME_ENT
RPMHOME=/usr/src/packages
VERSION=$(grep 'my $pandora_version =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
BUILD=$(grep 'my $pandora_build =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
X86_64=`uname -m | grep x86_64`
CONSOLEHOME=$CODEHOME/pandora_console
CONSOLEHOME_ENT=$CODEHOME_ENT/pandora_console

function get_current_branch {
	echo `cd "$CODEHOME" && git branch 2>/dev/null | grep \* | awk '{print $2}'`
}

