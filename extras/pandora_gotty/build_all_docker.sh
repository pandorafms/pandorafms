#Build RPM
docker build -t pandora_gotty_builder_rpm -f Dockerfile-RPM . || exit 1
docker run --rm -it -v `pwd`:/root/pandora_gotty pandora_gotty_builder_rpm /root/pandora_gotty/build_rpm.sh || exit 1

#Buikd DEB
docker build -t pandora_gotty_builder_deb -f Dockerfile-deb . || exit 1
docker run --rm -it -v `pwd`:/root/pandora_gotty pandora_gotty_builder_deb /root/pandora_gotty/build_deb.sh || exit 1

echo " - Done"
pwd
ls -l | grep -E "(\.deb|\.rpm)"