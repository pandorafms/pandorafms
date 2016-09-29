/* Pandora agents service for Win32.
   
   Copyright (C) 2016 Artica ST.
   Written by Ramon Novoa.
  
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

#ifndef _SHA256_H_
#define _SHA256_H_

// Length of the sha256 hex string.
#define SHA256_HEX_LENGTH 64

void sha256(const char *data, char hex_digest[SHA256_HEX_LENGTH + 1]);

#endif
