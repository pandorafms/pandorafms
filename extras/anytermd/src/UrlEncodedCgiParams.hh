// common/UrlEncodedCgiParams.hh
// This file is part of Anyterm; see http://anyterm.org/
// (C) 2006 Philip Endecott

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


#ifndef UrlEncodedCgiParams_hh
#define UrlEncodedCgiParams_hh

#include "CgiParams.hh"

#include <string>


class UrlEncodedCgiParams: public CgiParams {

public:
  UrlEncodedCgiParams(std::string query);

};


#endif
