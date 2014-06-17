#!/bin/bash
# Build the Pandora Agent installer.
# The installer will be placed in ./installer/output/.
# InstallJammer must be installed and in the PATH.

ARCH=`uname -m`
if [ "$ARCH" == "x86_64" ]; then
	HOST="x86_64-w64-mingw32"
else
	HOST="i686-w64-mingw32"
fi

#./autogen.sh && ./configure --host=$HOST && make clean && make && cp PandoraAgent.exe bin/ && installjammer --build installer/pandora.mpi
./autogen.sh && ./configure --host=$HOST && make clean && make && cp PandoraAgent.exe bin/

