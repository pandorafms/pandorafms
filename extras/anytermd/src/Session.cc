// daemon/Session.cc
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


#include "Session.hh"

#include <sstream>
#include <iomanip>

#include <unistd.h>
#include <signal.h>

#include <boost/bind.hpp>

#include "Lock.hh"

#include "config.hh"
#include "html.hh"
#include "editscript.hh"
#include "Error.hh"
#include "Iconver.hh"
#include "unicode.hh"


using namespace std;
using namespace pbe;


Session::Session(int r, int c, int sb,
                 string host, string user, string param,
                 int t,
                 activityfactory_t af,
                 string charset_,
                 bool diff_):
  rows(r),
  cols(c),
  scrollback(sb),
  time_out(t),
  charset(charset_),
  diff(diff_),
  utf8_to_charset("UTF-8",charset),
  charset_to_ucs4(charset,UCS4_NATIVE),
  ucs4_to_utf8(UCS4_NATIVE,"UTF-8"),
  screen(rows,cols,scrollback),
  error(false),
  term(rows,cols,screen),
  activity(af(boost::bind(&Session::process_output,this,_1),
              boost::bind(&Session::process_error,this,_1),
              host, user, param, r, c))
{
  dirty=true;
  touch();
}


Session::~Session()
{}


// Timeout unused sessions:

void Session::touch(void)
{
  last_access = time(NULL);
}


// Check if session has a backend error to report

void Session::report_any_backend_error(void)
{
  if (error) {
    error=false;
    throw Error(error_msg);
  }
}


// Handle Apache requests:

string int_to_string(int i)
{
  char b[32];
  snprintf(b,sizeof(b),"%d",i);
  return b;
}


void Session::send(string k)
{
  if (!k.empty()) {
    string dk = utf8_to_charset(k);
    activity->send(dk);
  }

  touch();
}



string escape_html(string s)
{
  string t;
  for(string::size_type i=0; i<s.length(); i++) {
    char c=s[i];
    switch(c) {
    case '<': t+="&lt;"; break;
    case '>': t+="&gt;"; break;
    case '&': t+="&amp;"; break;
    default:  t+=c; break;
    }
  }
  return t;
}


string Session::rcv(void)
{
  {
    Lock<screen_lock_t> l(screen_lock);
    if (!dirty && !error) {
      dirty_condition.timed_wait(l,10.0F);
    }
  }
  touch();

  report_any_backend_error();

  bool was_dirty;
  ucs4_string html_screen;
  {
    Lock<screen_lock_t> l(screen_lock);
    was_dirty = dirty;
    dirty=false;
    if (!was_dirty) {
      return "n";
    }
    html_screen = htmlify_screen(screen);
  }

  string utf8_editscript;
  if (diff) {
    ucs4_string editscript = make_editscript(old_html_screen,html_screen);
    old_html_screen=html_screen;
    utf8_editscript = ucs4_to_utf8(editscript);
  } else {
    utf8_editscript = "R";
    utf8_editscript.append(ucs4_to_utf8(html_screen));
  }

  return utf8_editscript;
}


void Session::process_output(string s)
{
  ucs4_string ucs4_s = charset_to_ucs4(s);
  {
    Lock<screen_lock_t> l(screen_lock);
    term.write(ucs4_s);
    dirty = true;
  }
  dirty_condition.notify_all();
}


void Session::process_error(string s)
{
  // We could have a lock here, but maybe we can trust that the assignment
  // to error is atomic.
  error_msg = s;
  error = true;
  dirty_condition.notify_all();
}


bool Session::timed_out(void)
{
  return time(NULL) - last_access > time_out;
}

