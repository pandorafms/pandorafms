// daemon/mk_blob.c
// This file is part of Anyterm; see http://anyterm.org/
// (C) 2008 Philip Endecott

// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.


#include <stdio.h>
#include <stdlib.h>


int main(int argc, char* argv[])
{
  if (argc!=2) {
    fprintf(stderr,"usage: mk_blob fn\n");
    exit(1);
  }
  const char* fn = argv[1];

  printf("const char _binary_%s_start[] = {\n",fn);
  int l=0;
  while (1) {
    int c = getchar();
    if (c==EOF) {
      break;
    }
    printf("0x%02x, ",c);
    ++l;
    if (l%32==0) {
      printf("\n");
    }
  }
  printf("\n};\n");
  printf("const char _binary_%s_end[] = {};\n",fn);
  exit(0);
}

