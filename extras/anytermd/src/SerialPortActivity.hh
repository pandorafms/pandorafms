// common/SerialPort.hh
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

#ifndef SerialPort_hh
#define SerialPort_hh

#include "Activity.hh"

#include <string>

#include "SerialPort.hh"


// This base class is just so that sp can be initialised before being Activity.
struct SerialPortActivity_base {
  pbe::SerialPort sp;
  SerialPortActivity_base(std::string fn, pbe::FileDescriptor::open_mode_t open_mode,
                          int baudrate, bool raw=true):
    sp(fn,open_mode,baudrate,raw)
  {}
};


class SerialPortActivity: private SerialPortActivity_base, public Activity {
public:
  SerialPortActivity(Activity::onOutput_t onOutput,
                     Activity::onError_t onError,
                     std::string fn, int baudrate);

  ~SerialPortActivity() {};
};


#endif
