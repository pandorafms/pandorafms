docker build -t pandora_gotty_builder .
docker run --rm -it -v `pwd`:/root/pandora_gotty pandora_gotty_builder /root/pandora_gotty/build.sh