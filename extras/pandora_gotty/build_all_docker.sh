#Build RPM
docker build -t pandora_gotty_builder -f Dockerfile-RPM .
docker run --rm -it -v `pwd`:/root/pandora_gotty pandora_gotty_builder /root/pandora_gotty/build_rpm.sh