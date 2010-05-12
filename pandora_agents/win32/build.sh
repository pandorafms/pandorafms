#!/bin/bash
# Build the Pandora Agent installer.
# The installer will be placed in ./installer/output/.
# InstallJammer must be installed and in the PATH.

./autogen.sh && ./configure --host=i586-mingw32msvc && make clean && make && cp PandoraAgent.exe bin/ && installjammer --build installer/pandora.mpi

