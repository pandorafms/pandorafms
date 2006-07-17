/* Misc utils for strings.
   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.
   
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2, or (at your option)
   any later version.
  
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
  
   You should have received a copy of the GNU General Public License along
   with this program; if not, write to the Free Software Foundation,
   Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

#include "pandora.h"
#include "pandora_strutils.h"
#include <string.h>
#include <iostream>
#include <sstream>
#include <stdexcept>
#include <cstring>    // for strchr

using namespace Pandora;

string
Pandora_Strutils::trim (const string str) {
        char *            delims = " \t\r\n";
        string            result = str;
        string::size_type index = result.find_last_not_of (delims);
        
        if(index != string::npos) {
                result.erase (++index);
        }
        
        index = result.find_first_not_of (delims);
        if(index != std::string::npos) {
                result.erase (0, index);
        } else {
                result.erase ();
        }
        
        return result;
}

string
Pandora_Strutils::inttostr (const int i) {
        return longtostr (i);
}

string
Pandora_Strutils::longtostr (const long i) {
	std::ostringstream o;
        
        o << i;
        
        return o.str();
}

string
Pandora_Strutils::longtohex (const long i) {
        std::ostringstream o;
        o << std::hex << i;
        
        return o.str();
}

int
Pandora_Strutils::strtoint (const string str) {
        int result;
        
        if (! std::sscanf (str.c_str (), "%d", &result)) {
                throw Invalid_Conversion ();
        }
        return result;
}

string
Pandora_Strutils::strreplace (string in, string pattern, string rep) {
        int i = in.find (pattern);
        int j;
        
        if (i < 0) {
                return in;
        }
        
        int plen = pattern.length ();
        int rlen = rep.length ();
        
        do {
                in.replace(i, plen, rep);
                i += rlen;
                string rest = in.substr (i, in.length () - i);
                j = rest.find (pattern);
                i += j;
        } while (j >= 0);
        
        return in;
}

inline bool
isseparator (char c, char const * const wstr) {
        return (strchr (wstr, c) != NULL);
}

void
Pandora_Strutils::stringtok (list<string> &l, string const &s, 
                           char const * const separators) {
    
        const string::size_type  strsize = s.size();
        string::size_type        i = 0;
        
        while (i < strsize) {
                /* eat leading whitespace */
                while ((i < strsize) && (isseparator (s[i], separators))) {
                        i++;
                }
                if (i == strsize) {
                        return;  /* nothing left but WS */
                }
                
                /* find end of word */
                string::size_type  j = i + 1;
                while ((j < strsize) && (!isseparator (s[j], separators))) {
                        j++;
                }
                
                /* add word */
                l.push_back (s.substr (i, j - i));
                
                /* set up for next loop */
                i = j + 1;
        }
}


