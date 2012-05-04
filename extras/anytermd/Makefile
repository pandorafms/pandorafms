# Makefile
# This file is part of Anyterm; see http://anyterm.org/
# (C) 2009 Philip Endecott

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


# The Makefiles are organised so that you have have multiple build
# directories for different versions of the executable, i.e. debug vs.
# optimised builds, cross-compiled builds etc.  This top-level
# Makefile simply invokes make in the default build directory and
# copies the resulting executable to the top level.  If you want to
# build variants, create new build directories (e.g. build.debug)
# and copy build/Makefile into them.  Make any necessary changes in
# the Makefile copies.  These build directory Makefiles include a
# shared common.mk that does most of the work.


EXECUTABLE=anytermd

default_target: ${EXECUTABLE}

${EXECUTABLE}: FORCE
	${MAKE} -C build ${EXECUTABLE}
	cp build/${EXECUTABLE} $@

clean: FORCE
	${MAKE} -C build clean

veryclean: FORCE
	${MAKE} -C build veryclean

FORCE:

.PHONY: default_target clean veryclean

