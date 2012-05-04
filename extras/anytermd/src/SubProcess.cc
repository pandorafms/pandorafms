// common/SubProcess.cc
// This file is part of SlugTerm; see http://chezphil.org/slugterm
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

#include "SubProcess.hh"

// For forkpty():
#if defined(__FreeBSD__)
#include <libutil.h>
#include <sys/ioctl.h>
#elif defined(__OpenBSD__)
#include <termios.h>
#include <util.h>
#elif defined(__APPLE__)
#include <util.h>
#else
#include <pty.h>
#endif

#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <signal.h>
#include <sys/wait.h>

#include <boost/bind.hpp>

#include "select.hh"
#include "Exception.hh"


using namespace std;
using namespace pbe;


static std::pair<int,::pid_t> open_subprocess(string command, int pty_rows, int pty_cols)
{
  struct winsize ws;
  ws.ws_row=pty_rows;
  ws.ws_col=pty_cols;
  ws.ws_xpixel=0;
  ws.ws_ypixel=0;

  int fd;
  int pid = forkpty(&fd, NULL, NULL, &ws);
  if (pid==-1) {
    throw_ErrnoException("forkpty()");
  }
  if (pid==0) {
    setenv("TERM","linux",1);
    struct termios t;
    tcgetattr(0,&t);  // Could fail, but where would we send the error?
    t.c_cc[VERASE]=8; // Make ctrl-H (backspace) the erase character.
    tcsetattr(0,TCSANOW,&t); // ditto.
    execl("/bin/sh","/bin/sh","-c",command.c_str(),NULL);
    throw_ErrnoException("execl(/bin/sh -c "+command+")");  // pointless.
  }
  return make_pair(fd,pid);
}


SubProcess::SubProcess(Activity::onOutput_t onOutput,
                       Activity::onError_t onError,
		       string command,
		       int pty_rows, int pty_cols):
  SubProcess_base(open_subprocess(command, pty_rows, pty_cols)),
  Activity(onOutput, onError, SubProcess_base::fd)
{}


SubProcess::~SubProcess()
{
  // We do two things to try to kill the subprocess: we close the fd,
  // which really ought to kill it, and we SIGHUP it.  The SIGHUP
  // by itself may not be sufficient if the process is nohup or
  // setuid or something.  The close by itself really should be
  // sufficient, but I'm keeping the SIGHUP because I'm paranoid.
  // The three results that we want are (a) the process dies,
  // (b) it does not become a zombie, and (c) the output processor
  // thread terminates so that ~Activity can join it.  For (c),
  // we hope that it will get an error when reading from the fd
  // and/or that it sees terminating set.  There's a danger that
  // we could close the fd and something else could open another
  // fd with the same number, which the output processor could read.
  // I hope that's not a high probability.

  terminating = true;

  fd_open = false;
  try {
    SubProcess_base::fd.close();
    // fd.close() can throw.
  } catch (...) {}
  kill(pid,SIGHUP);
}


