// daemon/main.cc
// This file is part of Anyterm; see http://anyterm.org/
// (C) 2005-2006 Philip Endecott

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

#include "AnytermDaemon.hh"

#include <string>
#include <iostream>

#include <unistd.h>
#include <sys/types.h>
#include <stdlib.h>

#include "Exception.hh"
#include "segv_backtrace.hh"

using namespace pbe;
using namespace std;


static void usage()
{
  cerr << "Usage: anytermd [options]\n"
       << "Available options:\n"
       << "     --help                     Show this help message\n"
       << "  -c|--command <cmd>            Command to run in terminal (default /bin/bash)\n"
       << "  -d|--device <dev>             Device to connect to (e.g. serial port)\n"
       << "  -p|--port <port>              Port number to listen on (default 8080)\n"
       << "  -u|--user <user>              User to run as\n"
       << "  -a|--auth none|null|trivial   Authorisation scheme to use (default none)\n"
       << "  -s|--charset                  Character set to use (default ASCII)\n"
       << "  -f|--foreground               Run in foreground (by default, backgrounds itself)\n"
       << "     --diff                     Send only differences to browser (default)\n"
       << "  -n|--nodiff                   Send whole screen to browser each time\n"
       << "  -m|--max-sessions             Maximum number of simultaneous sessions (default 20)\n"
       << "     --max-http-connections     Maximum number of simultaneous HTTP connections (default unlimited)\n"
       << "     --local-only               Accept connections only from localhost\n"
       << "     --name                     Name used for logging and pid file (default anytermd)\n";
}


struct Options {
  bool background;
  short port;
  string user;
  string command;
  string device;
  string authname;
  string charset;
  bool diff;
  int max_sessions;
  int max_http_connections;
  bool local_only;
  string name;

  Options():
    background(true),
    port(8080),
    user(""),
    command("/bin/bash"),
    device(""),
    authname("none"),
    charset("ascii"),
    diff(true),
    max_sessions(20),
    max_http_connections(0),
    local_only(false),
    name("anytermd")
  {}
};


static Options parse_command_line(int argc, char* argv[])
{
  Options options;

  for (int i=1; i<argc; ++i) {
    string arg = argv[i];
    if (arg=="-help" || arg=="--help") {
      usage();
      exit(0);

    } else if (arg=="--foreground" || arg=="-f") {
      options.background = false;

    } else if (arg=="--diff") {
      options.diff = true;

    } else if (arg=="--nodiff" || arg=="-n") {
      options.diff = false;

    } else if (arg=="--local-only") {
      options.local_only = true;

    } else if (i==argc-1) {
      cerr << "Missing argument for option '" << arg << "'\n";
      exit(1);

    } else if (arg=="--command" || arg=="-c") {
      options.command = argv[++i];
      options.device.clear();

    } else if (arg=="--device" || arg=="-d") {
      options.command.clear();
      options.device = argv[++i];

    } else if (arg=="--port" || arg=="-p") {
      options.port = boost::lexical_cast<short>(argv[++i]);

    } else if (arg=="--user" || arg=="-u") {
      options.user = argv[++i];

    } else if (arg=="--auth" || arg=="-a") {
      options.authname = argv[++i];

    } else if (arg=="--charset" || arg=="-s") {
      options.charset = argv[++i];

    } else if (arg=="--max-sessions" || arg=="-m") {
      options.max_sessions = boost::lexical_cast<int>(argv[++i]);

    } else if (arg=="--max-http-connections") {
      options.max_http_connections = boost::lexical_cast<int>(argv[++i]);

    } else if (arg=="--name") {
      options.name = argv[++i];

    } else {
      cerr << "Unrecognised option '" << arg << "'\n";
      exit(1);
    }
  }

  return options;
}


int main(int argc, char* argv[])
{
  get_backtrace_on_segv();

  Options options = parse_command_line(argc,argv);

  if (getuid()==0 && options.user=="") {
    cerr << "Please specify a user to run as using --user.\n";
    exit(1);
  }

  try { try {

    AnytermDaemon d(options.port, options.user, options.command, options.device, options.name,
                    options.authname, options.charset, options.diff, options.max_sessions,
                    options.max_http_connections, options.local_only);
    d.run_as_daemon(options.background);

  } RETHROW_MISC_EXCEPTIONS }
  catch (Exception& E) {
    E.report(cerr);
    exit(E.exit_status);
  }
}
