//     Pandora FMS Embedded Agent
//     (c) Pandora FMS 2011-2023
//     (c) Sancho Lerena <slerena@artica.es>

//     This program is free software; you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation; either version 2 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.


void 
pandora_log (int level, char *message, struct pandora_setup *pandorasetup );

void
pandora_free (void *pointer);

char *
rtrim(char* string, char junk);

char *
ltrim(char *string, char junk);

char *
trim (char * s);

int
isdatafile (char *filename);


char *
return_time (char *formatstring);
