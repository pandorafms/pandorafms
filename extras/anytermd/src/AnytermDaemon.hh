// daemon/AnytermDaemon.hh
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


#ifndef AnytermDaemon_hh
#define AnytermDaemon_hh

#include "HttpDaemon.hh"
#include "HttpRequest.hh"
#include "HttpResponse.hh"
#include "Anyterm.hh"
#include "NullAuthenticator.hh"
#include "TrivialAuthenticator.hh"


static inline HttpAuthenticator* choose_authenticator(std::string authname) {
  if (authname=="none") {
    return NULL;
  } else if (authname=="null") {
    return new NullAuthenticator;
  } else if (authname=="trivial") {
    return new TrivialAuthenticator;
  } else {
    throw "Unrecognised auth type";
  }
}


class AnytermDaemon_base {
protected:
  boost::scoped_ptr<HttpAuthenticator> auth_p;

public:
  AnytermDaemon_base(std::string authname):
    auth_p(choose_authenticator(authname))
  {}
};


class AnytermDaemon: public AnytermDaemon_base, public pbe::HttpDaemon {
private:
  Anyterm anyterm;

public:
  AnytermDaemon(short port=80, std::string user="",
		std::string command="",
		std::string device="",
                std::string name="",
                std::string authname="none",
                std::string charset="ascii",
                bool diff=true,
                int max_sessions=20,
                int max_http_connections=60,
                bool accept_local_only=false):
    AnytermDaemon_base(authname),
    HttpDaemon(*auth_p.get(), port, (name=="") ? "anyterm" : name,
               user, true, max_http_connections, accept_local_only),
    anyterm(command, device, charset, diff, max_sessions)
  {}

  void session_start();
  void handle(const pbe::HttpRequest& req, pbe::HttpResponse& resp);

};


#endif
