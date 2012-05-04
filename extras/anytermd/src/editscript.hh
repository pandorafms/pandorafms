// common/editscript.hh
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


#ifndef editscript_hh
#define editscript_hh

#include <string>
#include "unicode.hh"


// Create an edit script describing the difference between oc and
// nc.  Storage for the result is allocated by this function using
// malloc() and should be freed by the caller.  Syntax of edit
// script is a sequence of commands describing how to transform oc
// to nc:
//   k(num):      keep num characters
//   d(num):      delete num characters
//   i(num):text  insert num characters, supplied

ucs4_string make_editscript(ucs4_string o, ucs4_string n);

#endif
