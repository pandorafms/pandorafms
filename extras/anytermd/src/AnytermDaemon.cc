// daemon/AnytermDaemon.cc
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


#include "AnytermDaemon.hh"

#include "Anyterm.hh"
#include "static_content.hh"

#include <sstream>
#include <algorithm>

#include "segv_backtrace.hh"


using namespace std;
using namespace pbe;


/*virtual*/ void AnytermDaemon::session_start()
{
  get_backtrace_on_segv();
}

void AnytermDaemon::handle(const HttpRequest& req0, HttpResponse& resp)
{
  HttpRequest req = req0;

  //syslog(LOG_NOTICE,"anytermd handling %s",req.uri.c_str());

  resp.headers["Server"]="anytermd";

  authenticate(req);

  // Redirect a request for '/' to '/anyterm.html'.
  if (req.abs_path=="/") {
    string host = req.headers.find("Host")->second;
    // This is tricky if we're being proxied to because "host" is the post-proxy
    // hostname (e.g. locahost) while the browser needs to see the pre-proxy hostname.
    // Apache can fix this up for us if we use something like this:
    //   ProxyPassReverse http://localhost:8080
    resp.headers["Location"]="http://"+host+"/anyterm.html";
    resp.status_code=301;
    return;
  }

  if (get_static_content(req.abs_path, resp.headers["Content-Type"], resp.body)) {
    return;
  }

  Anyterm::response_t r = anyterm.process_request(req);
  resp.headers["Content-Type"] = r.type;
  resp.headers["Cache-Control"] = "no-cache, no-store";
  resp.headers["Pragma"] = "no-cache";
  resp.body = r.body;
}



