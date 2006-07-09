/*-----------------------------------------------------------------------------
 xc.c
 X Library Compiler
 Copyright 2004-2005 Michael Foster (Cross-Browser.com)
 Distributed under the terms of the GNU LGPL

v0.28b,  8Aug05, now parses x symbols in quotes. thanks for bug report from Miguel Angel Alvarez
v0.24b, 25May05, removed all doc support for now
-----------------------------------------------------------------------------*/

#include "xc.h"

// Global Variables

struct
{
  char name[MAX_NAME_LEN];  // Symbol name, corresponds to an X lib file found in lib_path.
  int dep[MAX_SYMBOLS];     /* An array of dependencies for this symbol,
                               each array element is an index into the symbols array. */
  int dep_len;              // Length of the dep array.
  int inc;                  /* Indicates the number of times this symbol was found
                               in the app files. If zero then this X lib file will not
                               be included in the output js lib file. */
} symbols[MAX_SYMBOLS];
int symbols_len = 0;

struct
{
  bool cmp;  /* true = Compression applied to output lib js file. Default = true.
                Compression removes leading white space, new lines and blank lines.
                Also removes "//" comments but does not remove multi-line comments. */
  bool lws;  // true = Retain leading white space. Default = false.
  bool nln;  // true = Retain newline chars on non-blank lines. Default = false.
  bool bln;  // true = Retain blank lines. Default = false.
  bool log;  // true = Generate log file. Default = false.
  bool lib;  // true = Generate lib file. Default = true.
  bool glb;  // true = Include X_GLB_FILE.js. Default = true.
  bool dbg;  // true = Debug info in log file. Sets options.log to true. Default = false.
  bool dep;  /* true = Dependents included in output. Default = true.
                When false it is useful for creating a lib file from a list of X symbols.
                I use -dep to create x_core.js, x_event.js, etc. */
} options;

char x_ver[MAX_NAME_LEN]; // current X version string read from X_VER_FILE
char prj_name[MAX_PATH_LEN]; // The project name (with no extension) must be given on the command line.
char lib_path[MAX_PATH_LEN]; // Path to the X lib files.
char app_files[MAX_APP_FILES][MAX_PATH_LEN]; // Array of app file pathnames.
int app_files_len = 0;                       // Length of the app_files array.
FILE *log_fp = NULL;                         // Log file pointer.

// Function Prototypes

int main(int argc, char *argv[]);
bool read_prj_file(char *name);
bool get_x_ver();
bool get_valid_syms();
bool get_lib_file_deps(int sym_idx);
int get_sym_idx(char *symbol);
void set_dep(int sym_idx, int dep);
bool get_app_file_syms(char *fname);
void include_sym(int idx);
bool create_lib();
bool append_lib(FILE *out_fp, char *name);
void skip_ws(char **s);
void rtrim(char *s);
void write_post_log();

// Function Definitions

int main(int argc, char *argv[])
{
  int i;
  char log_name[MAX_PATH_LEN];

  printf(XC_HDR_STR, XC_VER);

  // Read project file.

  if (argc <= 1)
  {
    printf("\nError: No project name specified on command line.\n");
    printf(HELP_STR);
    return 1;
  }

  if (!read_prj_file(argv[1]))  // Expects argv[1] to be project name with no extension,    
  {                             // and expects to find prjName.PRJ_EXT in current directory.
    printf(HELP_STR);           
    return 2;
  }

  // Open log file.

  if (options.log)
  {
    strcpy(log_name, prj_name);
    strcat(log_name, LOG_EXT);
    if ((log_fp = fopen(log_name, "w")) == NULL)
    {
      printf("\nWarning: Could not open log file: %s\n", log_name);
    }
    else if (log_fp) fprintf(log_fp, XC_HDR_STR, XC_VER);
  }

  get_x_ver(); // Open X_VER_FILE and parse version string

  // Create symbol table.

  if (!get_valid_syms())
  {
    if (log_fp)
    {
      fclose(log_fp);
    }
    return 4;
  }

  for (i = 0; i < symbols_len; ++i)
  {
    if (!get_lib_file_deps(i))
    {
      if (log_fp)
      {
        fclose(log_fp);
      }
      return 5;
    }
  }

  // Get symbols from app files.

  for (i = 0; i < app_files_len; ++i)
  {
    if (!get_app_file_syms(app_files[i]))
    {
      if (log_fp)
      {
        fclose(log_fp);
      }
      return 6;
    }
  }

  // Create output lib.

  if (options.lib)
  {
    if (!create_lib())
    {
      if (log_fp)
      {
        fclose(log_fp);
      }
      return 7;
    }
  }

  // Report results.

  printf("\ncreated ");
  if (options.lib) printf("%s%s", prj_name, LIB_EXT);
  if (options.lib && log_fp) printf(" and ");
  if (log_fp) printf("%s%s", prj_name, LOG_EXT);
  printf("\n");

  if (log_fp)
  {
    write_post_log();
    fclose(log_fp);
  }

  return 0; // success
}

/*
  Reads options, libpath and appfiles from project file.
  See the xc_reference for project file details.
*/
bool read_prj_file(char *name)
{
  FILE *fp;
  bool opt;
  char *p, line[MAX_LINE_LEN], *t, token[MAX_PATH_LEN];

  strcpy(prj_name, name);
  strcpy(line, prj_name); // use 'line' temporarily
  strcat(line, PRJ_EXT);
  if ((fp = fopen(line, "r")) == NULL)
  {
    printf("\nError: Could not open project file: %s%s\n", name, PRJ_EXT);
    return false;
  }

  // option defaults
  options.glb = true;
  options.dep = true;
  options.log = false;
  options.lib = true;
  options.dbg = false;
  options.cmp = true;
  options.lws = false;
  options.nln = false;
  options.bln = false;

  while (fgets(line, sizeof(line), fp) != NULL )
  {
    p = line;
    skip_ws(&p);
    // skip newlines and comment lines
    if (*p == ';' || *p == '\n')
    {
      continue;
    }
    // expect directive as first token on line
    t = token;
    while (*p && *p != ' ' && *p != '\t' && *p != '\n' && *p != ';')
    {
      *t++ = *p++;
    }
    *t = 0;
    skip_ws(&p);
    // process directive
    if (!_stricmp(token, "libpath"))
    {
      t = token;
      while (*p && *p != '\n' && *p != ';')
      {
        *t++ = *p++;
      }
      *t = 0;
      rtrim(token);
      strcpy(lib_path, token);
    }
    else if (!_stricmp(token, "appfiles"))
    {
      // get app file pathnames (expects one per line)
      app_files_len = 0;
      while (fgets(line, sizeof(line), fp) != NULL )
      {
        p = line;
        skip_ws(&p);
        if (*p != ';' && *p != '\n')
        {
          rtrim(p);
          strcpy(app_files[app_files_len++], p);
        }
      }
    }
    else if (!_stricmp(token, "options"))
    {
      // parse space-separated options on this line
      while (*p && *p != '\n' && *p != ';')
      {
        t = token;
        while (*p && *p != ' ' && *p != '\t' && *p != '\n' && *p != ';')
        {
          *t++ = *p++;
        }
        *t = 0;
        skip_ws(&p);
        opt = *token == '-' ? false : true;
        if (strstr(token, "dep")) { options.dep = opt; }
        else if (strstr(token, "cmp")) { options.cmp = opt; }
        else if (strstr(token, "lws")) { options.lws = opt; }
        else if (strstr(token, "bln")) { options.bln = opt; }
        else if (strstr(token, "nln")) { options.nln = opt; }
        else if (strstr(token, "log")) { options.log = opt; }
        else if (strstr(token, "lib")) { options.lib = opt; }
        else if (strstr(token, "glb")) { options.glb = opt; }
        else if (strstr(token, "dbg")) { options.dbg = opt; }
      } // end while
      if (options.dbg) { options.log = true; }
    }
  }
  fclose(fp);
  return true;
}

/*
  Parse X version string from X_VER_FILE.js
*/
bool get_x_ver()
{
  int i;
  FILE *fp;
  char line[MAX_LINE_LEN], *p;

  *x_ver = 0;
  strcpy(line, lib_path);
  strcat(line, X_VER_FILE);
  strcat(line, LIB_EXT);
  if ((fp = fopen(line, "r")) == NULL)
  {
    printf("\nWarning: Could not find X version file: %s%s%s\n", lib_path, X_VER_FILE, LIB_EXT);
    if (log_fp) fprintf(log_fp, "\nWarning: Could not find X version file: %s%s%s\n", lib_path, X_VER_FILE, LIB_EXT);
    return false;
  }
  while (fgets(line, sizeof(line), fp) != NULL)
  {
    i = 0;
    p = strchr(line, '\"');
    if (p)
    {
      ++p;
      while (*p && *p != '\"')
      {
        x_ver[i++] = *p++;
      }
      x_ver[i] = 0;
    }
  }
  if (!*x_ver)
  {
    printf("\nWarning: Could not read X version from file: %s%s%s\n", lib_path, X_VER_FILE, LIB_EXT);
    if (log_fp) fprintf(log_fp, "\nWarning: Could not read X version from file: %s%s%s\n", lib_path, X_VER_FILE, LIB_EXT);
    return false;
  }
  else
  {
    printf("\ncompiling %s from X %s ...\n", prj_name, x_ver);
    if (log_fp) fprintf(log_fp, "\ncompiling %s from X %s ...\n", prj_name, x_ver);
  }
  return true;
}

/*
 All filenames, minus extensions, in the libpath directory
 which match LIB_FILE_MASK constitute the set of valid X symbols.
*/
bool get_valid_syms()
{
  int i;
  long hFile;
  bool status = false;
  struct _finddata_t fd;
  char dir[MAX_PATH_LEN];
  char *p;

  strcpy(dir, lib_path);
  strcat(dir, LIB_FILE_MASK);
  if ((hFile = _findfirst(dir, &fd)) == -1L)
  {
    printf("\nError: Could not find %s%s\n", lib_path, LIB_FILE_MASK);
    if (log_fp) fprintf(log_fp,"\nError: Could not find %s%s\n", lib_path, LIB_FILE_MASK);
    status = false;
  }
  else
  {
    do
    {
      strcpy(symbols[symbols_len].name, fd.name);
      p = strchr(symbols[symbols_len].name, '.');
      if (p) *p = 0; // remove '.js' extension
      symbols[symbols_len].dep_len = 0;
      for (i = 0; i < MAX_SYMBOLS; ++i)
      {
        symbols[symbols_len].dep[i] = INVALID;
      }
      ++symbols_len;
    } while (_findnext(hFile, &fd) == 0);
    _findclose(hFile);
    status = true;
  }
  return status;
}

/*
 Update symbol table with dependency info from the X lib file symbols[sym_idx].
 Excludes symbols found in "//" comments.
*/
bool get_lib_file_deps(int sym_idx)
{
  int ln = 0;
  FILE *fp;
  char line[MAX_LINE_LEN], *p, *del = " \n\t,.;:{}()[]=<>?!+-*/%~^|&";
  int dep;

  strcpy(line, lib_path);
  strcat(line, symbols[sym_idx].name);
  strcat(line, LIB_EXT);
  if ((fp = fopen(line, "r")) == NULL)
  {
    printf("\nError: Could not find library file: %s%s%s\n", lib_path, symbols[sym_idx].name, LIB_EXT);
    if (log_fp) fprintf(log_fp, "\nError: Could not find library file: %s%s%s\n", lib_path, symbols[sym_idx].name, LIB_EXT);
    return false;
  }

  if (options.dbg && log_fp) fprintf(log_fp, "\nX Symbols found in lib file %s:\n", line);

  while (fgets(line, sizeof(line), fp) != NULL )
  {
    ++ln; // line number
    p = strstr(line, "//");
    if (p)
    {
      *p = 0;
    }
    else
    {
      p = line;
    }
//    skip_ws(&p);
//    if (*p && *p != '\n' && (*p != '/' || *(p+1) != '/'))

    if (*p && *p != '\n')
    {
      p = strtok(line, del);
      while(p != NULL)
      {
        if (*p == 'x')
        {
          dep = get_sym_idx(p);
          if (options.dbg && log_fp) fprintf(log_fp, "%s(%i), ", p, dep);
          if (options.dep && dep != INVALID && dep != sym_idx)
          {
            set_dep(sym_idx, dep);
          }
        }
        p = strtok(NULL, del);
      }
    }
  }
  fclose(fp);
  return true;
}

/*
 Determine if symbol has a valid entry in the symbol table.
*/
int get_sym_idx(char *symbol)
{
  int i;
  int idx = INVALID;

  for (i = 0; i < symbols_len; ++i)
  {
    if (!_stricmp(symbol, symbols[i].name))
    {
      idx = i;
      break;
    }
  }
  return idx;
}

/*
 if the 'symbols[sym_idx].dep' array does not already contain 'dep'
 then assign 'dep' to the next available array element.
*/
void set_dep(int sym_idx, int dep)
{
  int i;

  for (i = 0; i < symbols[sym_idx].dep_len; ++i)
  {
    if (symbols[sym_idx].dep[i] == dep)
    {
      return;
    }
  }
  symbols[sym_idx].dep[symbols[sym_idx].dep_len++] = dep;
}

/*
 Determine which X lib files get included in the output library
 by searching fname for X symbols.
*/
bool get_app_file_syms(char *fname)
{
  int ln = 0;
  FILE *fp;
  char line[MAX_LINE_LEN], *p, *del = " \n\t,.;:{}()[]=<>?!+-*/%~^|&'\"";
  int sym_idx;

  if ((fp = fopen(fname, "r")) == NULL)
  {
    printf("\nError: Could not find application file: %s\n", fname);
    if (log_fp) fprintf(log_fp, "\nError: Could not find application file: %s\n", fname);
    return false;
  }

  if (options.dbg && log_fp) fprintf(log_fp, "\nX Symbols found in app file %s:\n", fname);
  while (fgets(line, sizeof(line), fp) != NULL )
  {
    ++ln; // app line number
    if (line[0] != '\n')
    {
      p = strtok(line, del);
      while(p != NULL)
      {
        if (p[0] == 'x')
        {
          sym_idx = get_sym_idx(p);
          if (options.dbg && log_fp) fprintf(log_fp, "%s(%i), ", p, sym_idx);
          if (sym_idx != INVALID)
          {
            include_sym(sym_idx);
          }
        }
        p = strtok(NULL, del);
      }
    }
  }
  fclose(fp);
  return true;
}

/*
 Indicate the X lib file symbols[sym_idx] (and all it's dependents)
 to be included in the output library file.
*/
void include_sym(int sym_idx)
{
  int i;

  if (!symbols[sym_idx].inc++)
  {
    for (i = 0; i < symbols[sym_idx].dep_len; ++i)
    {
      include_sym(symbols[sym_idx].dep[i]);
    }
  }
}

/*
 Create the output library file.
 For every symbol which has 'symbols[sym_idx].inc == true'
 include the corresponding X lib file in the output lib.
*/
bool create_lib()
{
  int sym_idx;
  FILE *out_fp;
  char out_file[MAX_PATH_LEN];

  strcpy(out_file, prj_name);
  strcat(out_file, LIB_EXT);
  if ((out_fp = fopen(out_file, "w")) == NULL)
  {
    printf("\nError: Could not create output lib file: %s%s\n", prj_name, LIB_EXT);
    if (log_fp) fprintf(log_fp, "\nError: Could not create output lib file: %s%s\n", prj_name, LIB_EXT);
    return false;
  }

  fprintf(out_fp, X_HDR_STR, out_file, x_ver, XC_VER);
  if (options.glb)
  {
    if (!append_lib(out_fp, X_GLB_FILE))
    {
      printf("\nWarning: Could not add %s%s to output\n", X_GLB_FILE, LIB_EXT);
      if (log_fp) fprintf(log_fp, "\nWarning: Could not add %s%s to output\n", X_GLB_FILE, LIB_EXT);
    }
  }

  for (sym_idx = 0; sym_idx < symbols_len; ++sym_idx)
  {
    if (symbols[sym_idx].inc)
    {
      if (!append_lib(out_fp, symbols[sym_idx].name))
      {
        printf("\nWarning: Could not add %s%s to output\n", symbols[sym_idx].name, LIB_EXT);
        if (log_fp) fprintf(log_fp, "\nWarning: Could not add %s%s to output\n", symbols[sym_idx].name, LIB_EXT);
      }
    }
  }
  fclose(out_fp);
  return true;
}

/*
 Appends name to the output library js file.
 Optionally applies compression, which does not
 remove multi-line comments nor sequential white-space.
*/
bool append_lib(FILE *out_fp, char *name)
{
  int i;
  FILE *lib_fp;
  char lib_name[MAX_PATH_LEN];
  char *p, line[MAX_LINE_LEN], buf[MAX_LINE_LEN];

  strcpy(lib_name, lib_path);
  strcat(lib_name, name);
  strcat(lib_name, LIB_EXT);
  if ((lib_fp = fopen(lib_name, "r")) == NULL)
  {
    printf("\nError: Could not find library file: %s\n", lib_name);
    if (log_fp) fprintf(log_fp, "\nError: Could not find library file: %s\n", lib_name);
    return false;
  }

  while (fgets(line, sizeof(line), lib_fp) != NULL )
  {
    if (!options.cmp)
    {
      if (fputs(line, out_fp) == EOF)
      {
        fclose(lib_fp);
        printf("\nError: Could not write to output library file: %s%s\n", prj_name, LIB_EXT);
        if (log_fp) fprintf(log_fp, "\nError: Could not write to library file: %s%s\n", prj_name, LIB_EXT);
        return false;
      }
    }
    else
    {
      p = line;
      if (!options.lws) // skip leading whitespace
      {
        while (*p && (*p == ' ' || *p == '\t'))
        {
          ++p;
        }
      }
      if (!options.bln && (!*p || *p == '\n')) // skip blank lines
      {
        continue;
      }
      if (*p)
      {
        // eat chars and skip newlines and skip from "//" thru rest of line
        i = 0;
        while (*p && (*p != '\n' || options.nln) && (*p != '/' || *(p+1) != '/'))
        {
          buf[i++] = *p++;
        }
        buf[i] = 0;
        // write to output file
        if (fputs(buf, out_fp) == EOF)
        {
          fclose(lib_fp);
          printf("\nError: Could not write to output library file: %s%s\n", prj_name, LIB_EXT);
          if (log_fp) fprintf(log_fp, "\nError: Could not write to output library file: %s%s\n", prj_name, LIB_EXT);
          return false;
        }
      }
    }
  }
  fclose(lib_fp);
  return true;
}

/*
 Increment *s past all white-space.
*/
void skip_ws(char **s)
{
  while (**s == ' ' || **s == '\t')
  {
    ++*s;
  }
}

/*
 Remove whitespace and newlines from end of s
*/
void rtrim(char *s)
{
  char *p;

  p = s + (strlen(s) - 1);
  while (*p == ' ' || *p == '\t' || *p == '\n')
  {
    --p;
  }
  ++p;
  *p = 0;
}

/*
 Write project info, app file list and symbol table to the log file.
*/
void write_post_log()
{
  int i, j;

  fprintf(log_fp, "\nProject Info:\n\n");
  fprintf(log_fp, "project file: %s%s\n", prj_name, PRJ_EXT);
  fprintf(log_fp, "output lib file: %s%s\n", prj_name, LIB_EXT);
  fprintf(log_fp, "library path: %s\n", lib_path);
  fprintf(log_fp, "options: cmp=%i, lws=%i, nln=%i, bln=%i, log=%i, lib=%i, glb=%i, dbg=%i, dep=%i\n",
          options.cmp,
          options.lws,
          options.nln,
          options.bln,
          options.log,
          options.lib,
          options.glb,
          options.dbg,
          options.dep);

  fprintf(log_fp, "\nApplication Files:\n\n");
  for (i = 0; i < app_files_len; ++i)
  {
    fprintf(log_fp, "%i: %s\n", i, app_files[i]);
  }

  fprintf(log_fp, "\nOutput Files:\n\n");
  if (options.lib) fprintf(log_fp, " %s%s", prj_name, LIB_EXT);
  if (log_fp) fprintf(log_fp, ", %s%s", prj_name, LOG_EXT);
  fprintf(log_fp, "\n");

  fprintf(log_fp, "\nSymbol Table:\n\n");
  for (i = 0; i < symbols_len; ++i)
  {
    fprintf(log_fp, "%i: %s, %i", i, symbols[i].name, symbols[i].inc);
    for (j = 0; j < symbols[i].dep_len; ++j)
    {
      if (options.dbg)
      { // by number
        fprintf(log_fp, ", %i", symbols[i].dep[j]);
      }
      else
      { // by name
        fprintf(log_fp, ", %s", symbols[symbols[i].dep[j]].name);
      }
    }
    fprintf(log_fp, "\n");
  }
}

// end xc.c
