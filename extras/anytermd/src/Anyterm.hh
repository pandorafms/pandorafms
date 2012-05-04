// daemon/Anyterm.hh
// This file is part of Anyterm; see http://anyterm.org/
// (C) 2005-2007 Philip Endecott

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




#ifndef Anyterm_hh
#define Anyterm_hh

#include <map>
#include <string>

#include <boost/shared_ptr.hpp>

#include "Locked.hh"

#include "Session.hh"
#include "config.hh"
#include "HttpRequest.hh"


class Anyterm {

private:
  const std::string def_charset;
  const bool diff;
  const int max_sessions;
  typedef boost::shared_ptr<Session> session_ptr_t;
  typedef std::map<SessionId, session_ptr_t> sessions_t;
  typedef pbe::Locked<sessions_t> locked_sessions_t;
  locked_sessions_t sessions;
  Session::activityfactory_t activityfactory;
  bool reaper_running;

public:

  Anyterm(std::string command, std::string device, std::string charset, bool diff,
          int max_sessions_);

  struct response_t {
    std::string type;
    std::string body;
    response_t(std::string t, std::string b): type(t), body(b) {};
  };

  response_t process_request(const pbe::HttpRequest& request);

  void reap_timed_out_sessions(void);
  void run_reaper_thread(void);

};




#endif
