# daemon/mimetype.sh
# This file is part of Anyterm; see http://anyterm.org/
# (C) 2005-2008 Philip Endecott

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


#!/bin/sh

case $# in
1) ;;
*) echo "Usage: $0 file"
   exit 1 ;;
esac

F=$1

EXT=`echo $F | sed 's/.*\.\(.*\)$/\1/'`

for mime_types in /etc/mime.types /private/etc/apache2/mime.types ""
do
  if [ -z ${mime_types} ]
  then
    echo "No mime.types file found" > /dev/stderr
    exit 1
  fi
  if [ -f ${mime_types} ]
  then
    break
  fi
done


awk '!/^#/ '"{for (i=2; i<=NF; i++) {if (\$i==\"$EXT\") {print \$1; exit;}}}" ${mime_types}


