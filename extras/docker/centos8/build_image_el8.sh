#!/bin/bash

VERSION=$1
ENT="$HOME/code/pandora_enterprise"
OPEN="$HOME/code/pandorafms"
OS="Centos"
ARCH="x86_64"
EL="el7"
EXT="demo"
TARGET_URL="http://atlantis.artica.es"
DOCKER_PATH="$OPEN/extras/docker/centos8/"
OSTACK_IMAGE="pandorafms/pandorafms-open-stack-el8"
OBASE_IMAGE="pandorafms/pandorafms-open-base-el8"
PERCONA_IMAGE="pandorafms/pandorafms-percona-base"


function help {
	echo "To excute the builder you must declare 4 parameters: the version image, upload (push) tokens, build base (rebuild centos base image), build percona (rebuild percona base image)"
	echo ""
	echo "$0 <version> [ <push 0|1> ] [<build base 0|1>] [<build percona 0|1>]"
    echo "Ex creates a local image from 749 packages : $0 749 0 1 1" 
}

if [ "$1" == "" ] || [ "$1" == "-h" ] ; then
	help
    exit
fi

if [ "$2" == "1" ]; then
	UPDATE="1"
fi

if [ "$3" == "1" ]; then
	BASEBUILD="1"
fi

if [ "$4" == "1" ]; then
	DBBUILD="1"
fi

#Defining packages urls

oconsoleurl=$TARGET_URL/Releases/7.0NG.$VERSION/$OS/noarch/pandorafms_console-7.0NG.$VERSION.noarch.rpm
oserverurl=$TARGET_URL/Releases/7.0NG.$VERSION/$OS/noarch/pandorafms_server-7.0NG.$VERSION.noarch.rpm
url=$(curl -I -s $TARGET_URL/Releases/7.0NG.$VERSION/ 2> /dev/null | grep "200 OK" | wc -l)

# log in into docker acount to acces private repo.

# docker login -u $DOCKERUSER -p$DOCKERPASS

Check athlantis is reachable
if [ "$url" -lt 1 ] ; then
    echo "$url Athlantis unreachable ..."
    exit
fi

echo "Start"
# Removing old packages
cd $DOCKER_PATH/pandora-stack/sources
rm -rf ./pandorafms_*

# Downloading new packages
wget $oconsoleurl
wget $oserverurl

if [ "$BASEBUILD" == 1 ] ; then
	# Open Base image
	echo "building Base el8 image"
	cd $DOCKER_PATH/base
	docker build -t $OBASE_IMAGE:$VERSION -f $DOCKER_PATH/base/Dockerfile $DOCKER_PATH/base
	echo "Taging Open stack el8 latest image before upload"	
	docker tag $OBASE_IMAGE:$VERSION  $OBASE_IMAGE:latest
	echo -e ">>>> \n"
fi

if [ "$DBBUILD" == 1 ] ; then
	# Percona image
	echo "building Percona image"
	cd $OPEN/extras/docker/percona
	docker build -t $PERCONA_IMAGE:latest -f $OPEN/extras/docker/percona/Dockerfile $OPEN/extras/docker/percona
	echo -e ">>>> \n"
fi

#Open Stack image
echo "building Open el8 image"
cd $DOCKER_PATH/pandora-stack
docker build -t $OSTACK_IMAGE:$VERSION -f $DOCKER_PATH/pandora-stack/Dockerfile $DOCKER_PATH/pandora-stack
echo "Taging Open base latest image before upload"	
docker tag $OSTACK_IMAGE:$VERSION  $OSTACK_IMAGE:latest
echo -e ">>>> \n"

# Upload images

if [ "$UPDATE" == 1 ] ; then
	if [ "$BASEBUILD" == 1 ] ; then
	#Open base Images
		echo "Uploading Open $OBASE_IMAGE:$VERSION . . ."
		docker push $OBASE_IMAGE:$VERSION
		docker push $OBASE_IMAGE:latest
	fi

	if [ "$DBBUILD" == 1 ] ; then
		#Open base Images		
		echo "Uploading percona $PERCONA_IMAGE:latest . . ."
		docker push $PERCONA_IMAGE:latest
	fi

	#Open Stack Images
	echo "Uploading Open $OSTACK_IMAGE:$VERSION . . ."
	docker push $OSTACK_IMAGE:$VERSION
	docker push $OSTACK_IMAGE:latest
fi
