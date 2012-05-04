// common/SubProcess.hh
// This file is part of AnyTerm; see http://anyterm.org/
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

#ifndef SubProcess_hh
#define SubProcess_hh

#include "Activity.hh"

#include <string>

#include "FileDescriptor.hh"


// This base class is just so that fd can be initialised before Activity.
struct SubProcess_base {
  pbe::FileDescriptor fd;
  bool fd_open;
  ::pid_t pid;
  SubProcess_base(std::pair<int,::pid_t> fd_pid):
    fd(fd_pid.first, false), fd_open(true), pid(fd_pid.second)
  {}
  ~SubProcess_base() {
    if (fd_open) {
      // Normally fd is closed by ~SubProcess; this is here in case
      // e.g. Activity's ctor throws and ~SubProcess is not executed. 
      fd.close();
    }
  }
};


class SubProcess: private SubProcess_base, public Activity {

public:
  SubProcess(Activity::onOutput_t onOutput_,
             Activity::onError_t onError_,
	     std::string command,
	     int pty_rows=25, int pty_cols=80);

  ~SubProcess();
};


#endif
