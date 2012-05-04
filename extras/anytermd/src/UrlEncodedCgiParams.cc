// common/UrlEncodedCgiParams.cc
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


#include "UrlEncodedCgiParams.hh"

#include "Exception.hh"


#include <string>
#include <list>
#include <boost/algorithm/string.hpp>
#include <boost/lambda/lambda.hpp>

using namespace std;
using namespace pbe;
using namespace boost::lambda;


// Parse x-www-form-urlencoded parameters as defined in HTML 4.01
// section 17.13.4:
//
// 1. Control names and values are escaped. Space characters are
// replaced by `+', and then reserved characters are escaped as
// described in [RFC1738], section 2.2: Non-alphanumeric characters
// are replaced by `%HH', a percent sign and two hexadecimal digits
// representing the ASCII code of the character. Line breaks are
// represented as "CR LF" pairs (i.e., `%0D%0A').
//
// 2. The control names/values are listed in the order they appear
// in the document. The name is separated from the value by `=' and
// name/value pairs are separated from each other by `&'.




static int hexchar(char c)
{
  if (c>='0' && c<='9') {
    return c-'0';
  } else if (c>='a' && c<='f') {
    return c-'a'+10;
  } else if (c>='A' && c<='F') {
    return c-'A'+10;
  } else {
    throw StrException(string("Invalid hex character '")+c+"'");
  }
}

static char decode_percent_escape(string s)
{
  return hexchar(s[1])*16 + hexchar(s[2]); 
}


static string decode_uri_escapes(string s)
{
  string t;
  for (string::size_type i=0; i<s.length(); ++i) {
    switch (s[i]) {
    case '+': t+=' '; break;
    case '%': t+=decode_percent_escape(s.substr(i,3)); i+=2; break;
    default:  t+=s[i]; break;
    }
  }
  return t;
}


UrlEncodedCgiParams::UrlEncodedCgiParams(string query)
{
  typedef list<string> name_value_pairs_t;
  name_value_pairs_t name_value_pairs;
  boost::split(name_value_pairs, query, _1=='&');

  for(name_value_pairs_t::const_iterator i = name_value_pairs.begin();
      i != name_value_pairs.end(); ++i) {

    string name_value_pair = *i;
    string::size_type equals_pos = name_value_pair.find('=');
    if (equals_pos==name_value_pair.npos) {
      throw StrException("Misformatted URL-encoded query string component '"
			 +name_value_pair+"' does not contain an '='");
    }
    string name = name_value_pair.substr(0,equals_pos);
    string value = name_value_pair.substr(equals_pos+1);

    insert(make_pair(decode_uri_escapes(name),decode_uri_escapes(value)));
  }
}
