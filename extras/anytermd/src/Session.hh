// daemon/Session.hh
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


#ifndef Session_hh
#define Session_hh

//#define __STDC_LIMIT_MACROS
//#include <stdint.h>

#include "SessionId.hh"

#include "Mutex.hh"
#include "Condition.hh"

#include <vector>
#include <string>
#include <boost/scoped_ptr.hpp>
#include <boost/noncopyable.hpp>


#include "Screen.hh"
#include "Terminal.hh"
#include "Activity.hh"
#include "Iconver.hh"


class Session: boost::noncopyable {
public:

  const SessionId id;
  const int rows;
  const int cols;
  const int scrollback;
  const int time_out;
  const std::string charset;
  const bool diff;

private:
  pbe::Iconver<pbe::permissive,utf8_char,char> utf8_to_charset;
  pbe::Iconver<pbe::permissive,char,ucs4_char> charset_to_ucs4;
  pbe::Iconver<pbe::valid,ucs4_char,utf8_char> ucs4_to_utf8;

public:
  Screen screen;
  typedef pbe::Mutex<> screen_lock_t;
  screen_lock_t screen_lock;
  volatile bool dirty;
  typedef pbe::Condition dirty_condition_t;
  dirty_condition_t dirty_condition;
  /*volatile*/ std::string error_msg;
  volatile bool error;
  ucs4_string old_html_screen;
  volatile time_t last_access;

  Terminal term;
  boost::scoped_ptr<Activity> activity;

  typedef boost::function<Activity*(Activity::onOutput_t,
                                    Activity::onError_t,
                                    std::string, std::string,
                                    std::string, int, int)> activityfactory_t;
  Session(int r, int c, int sb,
          std::string host, std::string user, std::string param,
          int t,
          activityfactory_t activityfactory,
          std::string charset_,
          bool diff_);
  ~Session();

  void touch(void);
  void report_any_backend_error(void);
  void send(std::string k);
  std::string rcv(void);

  bool timed_out(void);

private:
  void process_output(std::string s);
  void process_error(std::string s);
};


#endif
