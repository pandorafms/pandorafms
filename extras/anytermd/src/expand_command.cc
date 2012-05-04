// common/expand_command.cc
// This file is part of Anyterm; see http://anyterm.org/
// (C) 2007 Philip Endecott

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

#include <string>

using namespace std;


// Expand command string:
// %h -> remote hostname
// %u -> HTTP AUTH username
// %p -> parameter supplied from the Javascript
// %% -> %

string expand_command(string templ, string host, string user, string param)
{
  string::size_type p = templ.find('%');
  if (p==templ.npos || p==templ.length()-1) {
    return templ;
  }
  string v;
  switch(templ[p+1]) {
  case '%': v="%"; break;
  case 'h': v=host; break;
  case 'u': v=user; break;
  case 'p': v=param; break;
  default: v="?"; break;
  }

  return templ.substr(0,p) + v + expand_command(templ.substr(p+2),host,user,param);
}

