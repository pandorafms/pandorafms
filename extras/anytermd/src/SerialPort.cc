// common/SerialPortActivity.cc
// This file is part of SlugTerm; see http://chezphil.org/slugterm
// (C) 2006-2007 Philip Endecott

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

#include "SerialPortActivity.hh"

#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>
#include <signal.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <termios.h>
#include <unistd.h>

#include <boost/bind.hpp>
#include <boost/lexical_cast.hpp>

#include "select.hh"
#include "Exception.hh"


using namespace std;
using namespace pbe;


SerialPortActivity::SerialPortActivity(Activity::onOutput_t onOutput,
                                       Activity::onError_t onError,
                                       string fn, int baudrate):
  SerialPortActivity_base(fn,FileDescriptor::read_write,baudrate),
  Activity(onOutput, onError, sp)
{}




