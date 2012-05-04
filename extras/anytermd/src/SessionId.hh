// apachemod/SessionId.hh
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


// This file defines the 64-bit random ID used to identify sessions

#ifndef SessionId_hh
#define SessionId_hh

#include <stdint.h>

#include <string>
#include <iostream>


class SessionId {
public:
  SessionId(void);
  SessionId(uint64_t i): n(i) {}
  SessionId(std::string s): n(hexstr_to_uint64(s)) {}
  std::string str(void) const { return uint64_to_hexstr(n); }
  bool operator==(SessionId rhs) const { return n==rhs.n; }
  bool operator<(SessionId rhs) const { return n<rhs.n; }
  
private:
  uint64_t n;
  uint64_t hexstr_to_uint64(std::string s) const;
  std::string uint64_to_hexstr(uint64_t i) const;
};


inline std::ostream& operator<<(std::ostream& strm, SessionId sid)
{
  strm << sid.str();
  return strm;
}

#endif
