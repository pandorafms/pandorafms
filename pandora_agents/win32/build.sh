#!/bin/bash
# Build the Pandora Agent installer.
# The installer will be placed in ./installer/output/.
# InstallJammer must be installed and in the PATH.

# ARCH may be set by other build scripts.
if [ "$ARCH" == "" ]; then
	ARCH=`uname -m`
fi

# Set the target host.
if [ "$ARCH" == "x86_64" ]; then
	HOST="x86_64-w64-mingw32"
else
	HOST="i686-w64-mingw32"
fi

# Compile and update the Pandora FMS Agent binary.
./autogen.sh && ./configure --host=$HOST && make clean && make && cp PandoraAgent.exe bin/

