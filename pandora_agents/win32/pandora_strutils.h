/* Misc utils for string.
   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.
   
   Stringtok (C) pedwards@jaj.com  May 1999
   
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

#ifndef	__STRUTILS_H__
#define	__STRUTILS_H__

#include <string>
#include <list>

using namespace std;

/**
 * Operations with strings.
 */
namespace Pandora_Strutils {
	/**
	 * String super-class exception.
	 */
	class String_Exception : Pandora::Pandora_Exception {};
	
	/**
	 * Exception throwed when a conversion could not be success.
	 */
	class Invalid_Conversion : Pandora_Strutils::String_Exception {};
	
	string             trim        (const string str);

	LPSTR              strUnicodeToAnsi (LPCWSTR s);
	
	string             inttostr    (const int i);
	string             longtostr   (const long i);
	string             longtohex   (const long i);
	
	int                strtoint    (const string str);
	double             strtodouble (const string str);
	unsigned long long strtoulong  (const string str);
	
	string             strreplace  (string in, string pattern, string rep);

	void
	stringtok (list<string> &l, string const &s,
		   char const * const separators = " \t\n");
}
#endif /* __STRUTILS_H__ */
