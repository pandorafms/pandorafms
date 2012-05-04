// common/Activity.hh
// This file is part of AnyTerm; see http://anyterm.org/
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

#ifndef Activity_hh
#define Activity_hh

#include <string>

#include <boost/function.hpp>
#include <boost/scoped_ptr.hpp>

#include "FileDescriptor.hh"
#include "Thread.hh"


class Activity {

public:
  typedef boost::function<void(std::string)> onOutput_t;
  typedef boost::function<void(std::string)> onError_t;

protected:
  pbe::FileDescriptor& fd;
  volatile bool terminating;

private:
  const onOutput_t onOutput;
  const onError_t onError;
  boost::scoped_ptr<pbe::Thread> output_processor;
  void process_output(void);

public:
  Activity(onOutput_t onOutput_, onError_t onError_, pbe::FileDescriptor& fd_);

  virtual ~Activity();

  void send(std::string s);
};


#endif
