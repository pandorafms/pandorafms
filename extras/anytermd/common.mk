# common.mk
# This file is part of Anyterm; see http://anyterm.org/
# (C) 2005-2009 Philip Endecott

# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

# You can have multiple build directories each with their own Makefiles.
# Those Makefiles include this common code; they can set variables to
# control things like optimisation level, debugging etc first.  You should
# make changes in those build Makefiles rather than here, unless your
# change shouls apply to all build variants.

# Note that paths in here are relative to the build directory.

default_target: anytermd

SRC_DIR=../src

VPATH=${SRC_DIR} .

UNAME_S=$(shell uname -s)

ifeq (${UNAME_S},Darwin)
else
HAVE_GNU_LD=1
endif

LIBPBE_DIR=../libpbe

CPP_FLAGS=

GCC_FLAGS=-pthread
#GCC_FLAGS=-D_REENTRANT

COMPILE_FLAGS=$(CPP_FLAGS) $(GCC_FLAGS) -W -Wall ${OPTIMISE_FLAGS} ${DEBUG_FLAGS}

CC_COMPILE_FLAGS=$(COMPILE_FLAGS)

LINK_FLAGS=${GCC_FLAGS} ${DEBUG_FLAGS} \
	-lutil

ifeq (${UNAME_S},OpenBSD)
LINK_FLAGS+=-liconv
endif

ifeq (${UNAME_S},Darwin)
LINK_FLAGS+=-liconv
endif

LIBPBE_MAKE_OPTIONS=
include ../libpbe.mk

CC_SRCS=$(sort $(notdir $(wildcard ${SRC_DIR}/*.cc)) static_content.cc)

BLOBFILES=anyterm.html anyterm.js anyterm.css copy.png paste.png copy.gif paste.gif

BLOBS=$(addsuffix .blob.o,$(BLOBFILES))

OBJS=$(addsuffix .o,$(notdir $(basename $(CC_SRCS))))

%.o: %.cc
	$(CXX) $(CC_COMPILE_FLAGS) -c $<

ifdef HAVE_GNU_LD
%.blob.o: ../browser/%
	cp $^ . ; $(LD) -r -b binary -o $@ $* ; rm $*

else
%.blob.c: ../browser/% ./mk_blob
	./mk_blob $(subst .,_,$*) < $< > $@

mk_blob: mk_blob.c
	$(CC) -o $@ $<
endif


anytermd: $(OBJS) $(BLOBS) $(LIBPBE_LIB)
	$(CXX) -o $@ $(OBJS) $(BLOBS) $(LINK_FLAGS)

%.d: %.cc
	$(CXX) -MM -MG -MT $@ -MT $(<:%.cc=%.o) $(CPP_FLAGS) $(GCC_FLAGS) -o $@ $<

DEPENDS=$(addsuffix .d,$(basename $(OBJS)))

-include $(DEPENDS)

install: FORCE
	install anytermd /usr/local/bin

clean: FORCE
	$(RM) -f *.o *.blob.c static_content.cc

veryclean: clean
	$(RM) *.d

.PHONY: default_target install FORCE


static_content.cc: ../scripts/mk_static_content.sh ../browser/*
	PATH="$${PATH}:../scripts" ../scripts/mk_static_content.sh $(BLOBFILES) > $@

static_content.o: CPP_FLAGS+=-I../src

