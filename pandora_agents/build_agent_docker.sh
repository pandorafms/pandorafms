#!/bin/bash
source /root/code/pandorafms/extras/build_vars.sh

# Set tag for docker build
if [ "$1" == "nightly" ]; then
	LOCAL_VERSION="latest"
else
	LOCAL_VERSION=$VERSION
fi

# Build image with code
docker build --rm=true --pull --no-cache -t pandorafms/pandorafms-agent:$LOCAL_VERSION -f $CODEHOME/pandora_agents/Dockerfile $CODEHOME/pandora_agents/

# Push image
docker push pandorafms/pandorafms-agent:$LOCAL_VERSION

# Delete local image
docker image rm -f pandorafms/pandorafms-agent:$LOCAL_VERSION