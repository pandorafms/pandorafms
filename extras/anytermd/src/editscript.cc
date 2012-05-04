// common/editscript.cc
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


#include "editscript.hh"

#include "diff.hh"

#include <string>
#include <sstream>

#include <boost/lexical_cast.hpp>


using namespace std;


static ucs4_string ucs4_int(int i)
{
  ucs4_string s;
  do {
    s = ucs4_string(1,(i%10)+L'0') + s;
    i = i/10;
  } while (i);
  return s;
}


void simplify_editscript(const DiffAlgo::ucs4_string_fragment_seq& in,
                         DiffAlgo::ucs4_string_fragment_seq& out)
{
  ucs4_string a_cf;  // A data to carry forward
  ucs4_string b_cf;  // B data to carry forward

  for ( DiffAlgo::ucs4_string_fragment_seq::const_iterator i = in.begin();
	i != in.end(); ++i ) {
    switch (i->first) {
      case DiffAlgo::from_a:
        a_cf.append(i->second);
        break;

      case DiffAlgo::from_b:
        b_cf.append(i->second);
        break;

      case DiffAlgo::common:
        if (i->second.size() > 4) {
          if (!a_cf.empty()) {
            out.push_back(make_pair(DiffAlgo::from_a,a_cf));
            a_cf.clear();
          }
          if (!b_cf.empty()) {
            out.push_back(make_pair(DiffAlgo::from_b,b_cf));
            b_cf.clear();
          }
          out.push_back(*i);
        } else {
          a_cf.append(i->second);
          b_cf.append(i->second);
        }
        break;
    }
  }
  if (!a_cf.empty()) {
    out.push_back(make_pair(DiffAlgo::from_a,a_cf));
  }
  if (!b_cf.empty()) {
    out.push_back(make_pair(DiffAlgo::from_b,b_cf));
  }
}


ucs4_string make_editscript(ucs4_string o, ucs4_string n)
{
  DiffAlgo::ucs4_string_fragment_seq e;

  DiffAlgo::ucs4_string_diff(o,n,e);

  DiffAlgo::ucs4_string_fragment_seq simp_e;
  simplify_editscript(e,simp_e);

  ucs4_string editscript;
  ucs4_string editscript_r = L"R";
  bool any_common = false;
  bool any_change = false;

  for ( DiffAlgo::ucs4_string_fragment_seq::const_iterator i = simp_e.begin();
	i != simp_e.end(); ++i ) {
    unsigned int len = i->second.length();
    switch (i->first) {
    case DiffAlgo::from_a:
      editscript += L'd';
      editscript += ucs4_int(len);
      editscript += ':';
      any_change = true;
      break;
    case DiffAlgo::from_b:
      editscript += L'i';
      editscript += ucs4_int(len);
      editscript += ':';
      editscript += i->second;
      editscript_r += i->second;
      any_change = true;
      break;
    case DiffAlgo::common:
      editscript += L'k';
      editscript += ucs4_int(len);
      editscript += ':';
      any_common = true;
      break;
    }
  }

  if (!any_change) {
    return L"n";
  } else if (any_common) {
    return editscript;
  } else {
    return editscript_r;
  }
}
