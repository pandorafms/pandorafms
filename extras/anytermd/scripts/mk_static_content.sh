# daemon/mk_static_content.sh
# This file is part of Anyterm; see http://anyterm.org/
# (C) 2005-2007 Philip Endecott

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

FILES="$@"

echo "#include \"static_content.hh\""
echo "#include <string>"
echo "using namespace std;"

echo 'extern "C" {'
for i in $FILES
do
    sym=`echo $i | sed 's/\./_/g'`
    echo "extern char _binary_${sym}_start[];"
    echo "extern char _binary_${sym}_end[];"
done
echo "};"

echo "bool get_static_content(string fn, string& mime_type, string& body) {"

for i in $FILES
do
    sym=`echo $i | sed 's/\./_/g'`
    FILE="../browser/$i"
    TYPE=`mimetype.sh $FILE`
    echo "  if (fn==\"/$i\") {"
    echo "    mime_type=\"$TYPE\";"
    echo "    body=string(_binary_${sym}_start,  _binary_${sym}_end);"
    echo "    return true;"
    echo "  };"

done

echo "  return false;"
echo "}"
