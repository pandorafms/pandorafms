# Makefile for winexe.py.
.PHONY: all build_image pandora_security_win.py

all: build_image pandora_security_win.py

build_image:
	docker build -t pandora_security_win docker/

pandora_security_win.py:
	docker run --rm -t -v`pwd`:/pybuild pandora_security_win
