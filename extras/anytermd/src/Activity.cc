// common/Activity.cc
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

#include "Activity.hh"

#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <signal.h>

#include <boost/bind.hpp>

#include "select.hh"
#include "Exception.hh"

#include <sstream>

using namespace std;
using namespace pbe;


Activity::Activity(onOutput_t onOutput_, onError_t onError_, pbe::FileDescriptor& fd_):
  fd(fd_),
  terminating(false),
  onOutput(onOutput_),
  onError(onError_),
  output_processor(new Thread(boost::bind(&Activity::process_output,this)))
{}


Activity::~Activity()
{
  output_processor->join();
}


void Activity::send(string s)
{
  fd.writeall(s);
}


void Activity::process_output(void)
{
  try { try {
    while(!terminating) {
      string s = fd.readsome();
      onOutput(s);
    }
  } RETHROW_MISC_EXCEPTIONS }
  catch (IOError) {
    onError("Subprocess terminated");
    return;
  }
  catch (Exception& e) {
    ostringstream s;
    s << "Exception: ";
    e.report(s);
    onError(s.str());
    return;
  }
}
