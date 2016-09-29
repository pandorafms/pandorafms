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
#include <stdio.h>
#include <string.h>
#include "openssl/sha.h"
#include "sha256.h"

void
sha256(const char *data, char hex_digest[SHA256_HEX_LENGTH + 1])
{
	int i, j;
    unsigned char hash[SHA256_DIGEST_LENGTH];
    SHA256_CTX sha256;

	// Calculate the SHA-256 hash.
    SHA256_Init(&sha256);
    SHA256_Update(&sha256, data, strlen(data));
    SHA256_Final(hash, &sha256);

	// Convert it to a hexadecimal string.
    for(i = 0, j = 0; i < SHA256_DIGEST_LENGTH, j < SHA256_HEX_LENGTH; i++, j+=2) {
        sprintf(&(hex_digest[j]), "%02x", hash[i]);
    }

	// Add a NULL terminator.
    hex_digest[SHA256_HEX_LENGTH] = 0;
}
