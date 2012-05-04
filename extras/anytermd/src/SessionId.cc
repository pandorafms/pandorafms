// apachemod/SessionId.cc
// This file is part of Anyterm; see http://anyterm.org/
// (C) 2005 Philip Endecott


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

#include "SessionId.hh"

#include <string>
#include <sstream>
#include <fstream>
#include <iomanip>

using namespace std;


SessionId::SessionId()
{
  ifstream devurandom("/dev/urandom");
  devurandom.read(reinterpret_cast<char*>(&n),sizeof(n));
}

uint64_t SessionId::hexstr_to_uint64(string s) const
{
  stringstream ss;
  ss << s;
  uint64_t i;
  ss >> setbase(16) >> i;
  return i;
}


string SessionId::uint64_to_hexstr(uint64_t i) const
{
  stringstream ss;
  ss << setbase(16) << i;
  return ss.str();
}


