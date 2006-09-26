/*-----------------------------------------------------------------------------
 xc.h
 X Library Compiler
 Copyright 2004-2005 Michael Foster (Cross-Browser.com)
 Distributed under the terms of the GNU LGPL
-----------------------------------------------------------------------------*/

// Includes

#include <stdlib.h>
#include <stdio.h>
#include <io.h>
#include <string.h>

// Constants

#define XC_VER "0.28b"
#define X_VER_FILE "xversion"
#define X_GLB_FILE "globals"
#define XC_HDR_STR "\nX Library Compiler %s. Distributed under GNU LGPL\n"
#define X_HDR_STR "/* %s compiled from X %s with XC %s. Distributed under GNU LGPL. For copyrights, license, documentation and more visit Cross-Browser.com */\n"
#define HELP_STR \
  "\nUsage:\n\nxc prj_name\n" \
  "\nthe file 'prj_name.xcp' must be in the current directory.\n"

#define bool unsigned char
#define true 1
#define false 0

#define MAX_SYMBOLS 200
#define MAX_NAME_LEN 50
#define INVALID 0xFFFF
#define MAX_LINE_LEN 2000
#define MAX_PATH_LEN 250
#define MAX_APP_FILES MAX_SYMBOLS
#define LIB_FILE_MASK "x*.js"
#define LIB_EXT ".js"
#define PRJ_EXT ".xcp"
#define LOG_EXT ".log"

// end xc.h
