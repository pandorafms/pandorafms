# libpbe.mk
# This file is part of Anyterm; see http://anyterm.org/
# (C) 2006-2009 Philip Endecott

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


UNAME_S=$(shell uname -s)
UNAME_R=$(shell uname -r)

ifeq (${UNAME_S},Linux)
  KVER=$(subst ., ,${UNAME_R})
  ifeq ($(word 1,${KVER}),2)
    ifeq ($(word 2,${KVER}),4)
      SUPPORT_LINUX_2_4=1
    endif
  endif
endif

CPP_FLAGS+=-I$(LIBPBE_DIR)/include

ifdef SUPPORT_LINUX_2_4
CPP_FLAGS+=-DSUPPORT_LINUX_2_4
endif

LINK_FLAGS+=-L$(LIBPBE_DIR) -lpbe

LIBPBE_LIB=$(LIBPBE_DIR)/libpbe.a

$(LIBPBE_LIB): FORCE
	cd $(LIBPBE_DIR); $(MAKE) $(LIBPBE_MAKE_OPTIONS)

