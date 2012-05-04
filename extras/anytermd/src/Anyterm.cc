// daemon/Anyterm.cc
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


#include "Anyterm.hh"

#include <string>
#include <list>

#include <signal.h>
#if defined(__OpenBSD__) || defined(__APPLE__)
// sigemptyset is provided as macros, but actual function is available
// if their names are undefined (with #undef name)
#undef sigemptyset
#endif

#include <sys/wait.h>
#include <sys/types.h>

#include "Error.hh"

#include "auto_CgiParams.hh"

#include "SubProcess.hh"
#include "SerialPortActivity.hh"
#include "expand_command.hh"
#include "unicode.hh"

#include "compiler_magic.hh"

#include <boost/bind.hpp>


using namespace std;
using namespace pbe;


struct SubProcessFactory {
  string command;
  SubProcessFactory(string command_): command(command_) {}
  Activity* operator()(Activity::onOutput_t onOutput,
                       Activity::onError_t onError,
                       string host, string user,
                       string param, int rows=25, int cols=80) {
    string exp_cmd = expand_command(command,host,user,param);
    return new SubProcess(onOutput,onError,exp_cmd,rows,cols);
  }
};

struct SerialPortFactory {
  string device;
  int baudrate;
  SerialPortFactory(string device_, int baudrate_):
    device(device_), baudrate(baudrate_) {}
  Activity* operator()(Activity::onOutput_t onOutput,
                       Activity::onError_t onError,
                       PBE_UNUSED_ARG(string host), PBE_UNUSED_ARG(string user),
                       PBE_UNUSED_ARG(string param), PBE_UNUSED_ARG(int rows), PBE_UNUSED_ARG(int cols)) {
    return new SerialPortActivity(onOutput,onError,device,baudrate);
  }
};


static void install_sigchld_handler(void);

static void reap_child(PBE_UNUSED_ARG(int sig))
{
  // Handler function for SIGCHLD.
  // Note that apparently you might get only one SIGCHLD for multiple
  // subprocesses if they terminate at about the same time.  So you
  // can't just have
  //    wait(NULL);

  ::pid_t rc;
  do {
    rc = ::waitpid(-1, NULL, WNOHANG);
  } while (rc>0);

  install_sigchld_handler();
}

static void install_sigchld_handler(void)
{
  struct sigaction sa;
  sa.sa_handler=&reap_child;
  ::sigemptyset(&sa.sa_mask);
  sa.sa_flags=SA_NOCLDSTOP;
  ::sigaction(SIGCHLD,&sa,NULL);  // should check return value
}


Anyterm::Anyterm(std::string command, std::string device, std::string charset, bool diff_,
                 int max_sessions_):
  def_charset(charset),
  diff(diff_),
  max_sessions(max_sessions_),
  reaper_running(false)
{
  if (command!="") {
    activityfactory=SubProcessFactory(command);
  } else if (device!="") {
    activityfactory=SerialPortFactory(device,9600);
  } else {
    throw "Neither command nor device specified";
  }

  try {
    Iconver<valid,char,char>      utf8_to_charset("UTF-8",def_charset);
    Iconver<valid,char,ucs4_char> charset_to_ucs4(def_charset,UCS4_NATIVE);
  }
  catch(...) {
    throw "Character set not supported by Iconv.  Try running iconv -l to find supported "
          "character sets.  It must be possible to convert from UTF-8 to CHARSET and from "
          "CHARSET to UCS-4.";
  }

  // We don't want child processes to become zombies when they terminate.
  // (It's not OK to signal(SIGCHLD,SIG_IGN) here because [posix exec]
  // "If the SIGCHLD signal is set to be ignored by the calling process image,
  // it is unspecified whether the SIGCHLD signal is set to be ignored or to
  // the default action in the new process image."  Installing a handler
  // that calls wait is safe because "Signals set to be caught by the calling
  // process image shall be set to the default action in the new process image."
  install_sigchld_handler();
}


static Anyterm::response_t text_resp(string s)
{
  return Anyterm::response_t("text/plain; charset=\"UTF-8\"", s);
}

Anyterm::response_t Anyterm::process_request(const HttpRequest& request)
{
  if (!reaper_running) {
    // We can't start the reaper thread from the constructor, because this Anyterm
    // object is constructed before the daemon forks itself into the background; child
    // threads don't survive the fork.  So we do it on the first call to process_request.
    // There's a race condition here if the first two request processing threads start
    // simultaneously.
    reaper_running = true;
    Thread timed_out_session_reaper 
      (boost::bind(&Anyterm::run_reaper_thread,this));
  }

  // Parse the arguments and call the appropriate function

  CgiParams params = auto_CgiParams(request);

  try {

    string action = params.get("a");

    if (action=="open") {
      reap_timed_out_sessions();

      {
        locked_sessions_t::reader sessions_rd(sessions);
        int n_sessions = distance(sessions_rd->begin(),sessions_rd->end());
        if (n_sessions>=max_sessions) {
          throw Error("The maximum number of concurrent sessions has been reached");
        }
      }

      session_ptr_t ses(new Session(params.get_as<int>("rows",25),
			  	    params.get_as<int>("cols",80),
                                    params.get_as<int>("sb",0),
                                    "[host unknown]",
                                    request.userinfo,
                                    params.get("p"),
				    ANYTERM_TIMEOUT,
				    activityfactory,
                                    params.get("ch",def_charset),
                                    diff));
      {
        locked_sessions_t::writer sessions_wr(sessions);
        (*sessions_wr)[ses->id]=ses;
      }
      return text_resp(ses->id.str());
    }

    string idstr = params.get("s");
    SessionId id(idstr);
    session_ptr_t ses;
    {
      locked_sessions_t::reader sessions_rd(sessions);
      sessions_t::const_iterator s = sessions_rd->find(id);
      if (s==sessions_rd->end()) {
        throw Error("no such session '"+idstr+"'");
      }
      ses = s->second;
    }
    
    if (action=="rcv") {
      return text_resp(ses->rcv());
      
    } else if (action=="send") {
      string k = params.get("k");
      ses->send(k);
      return text_resp("");
      
    } else if (action=="close") {
      {
        locked_sessions_t::writer sessions_wr(sessions);
        sessions_wr->erase(id);
      }
      return text_resp("");
      
    } else {
      throw Error("invalid query string '"+request.query+"'");
    }
  }
  
  catch (Error& E) {
    return text_resp("E"+E.get_msg());
  }
}


void Anyterm::reap_timed_out_sessions(void)
{
  list<session_ptr_t> timed_out_sessions;  // The timed out sessions are transfered to this list
                                           // which is destroyed after the lock on session has been released.
  locked_sessions_t::writer sessions_wr(sessions);
  for (sessions_t::iterator i = sessions_wr->begin();
       i != sessions_wr->end();) {
    sessions_t::iterator next = i; ++next;
    if (i->second->timed_out()) {
      timed_out_sessions.push_back(i->second);
      sessions_wr->erase(i);
    }
    i = next;
  }
}


void Anyterm::run_reaper_thread(void)
{
  while (1) {
    sleep(30);
    reap_timed_out_sessions();
  }
}

