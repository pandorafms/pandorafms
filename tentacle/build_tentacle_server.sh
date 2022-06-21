#!/bin/bash

CODEHOME=$HOME/code/pandorafms

# Add build string for nightly builds
if [ "$1" == "nightly" ]; then
	LOCAL_VERSION="$VERSION-$BUILD"
else
	LOCAL_VERSION=$1
fi

if [ ! -d $CODEHOME/tentacle/dist ]; then
	mkdir -p $CODEHOME/tentacle/dist || exit 1
fi

echo "Creating source tarballs in $(pwd)/dist"

# Server
cd $CODEHOME && tar zcvf $CODEHOME/tentacle/dist/tentacle_server-$LOCAL_VERSION.tar.gz \
--exclude \.svn \
--exclude \.exe \
--exclude dist \
--exclude build_tentacle_server.sh \
tentacle || exit 1

exit 0