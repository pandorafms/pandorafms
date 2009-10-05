/* Class to abstract an FTP client. It uses libcurl.

   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.
   
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2, or (at your option)
   any later version.
  
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
  
   You should have received a copy of the GNU General Public License along
   with this program; if not, write to the Free Software Foundation,
   Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

#ifndef	__PANDORA_FTP_CLIENT__
#define	__PANDORA_FTP_CLIENT__

#include <string>
#include "../pandora.h"
#include <curl.h>

using namespace std;

/**
 * FTP connection classes.
 */
namespace FTP {
	/**
	 * A FTP super-class exception.
	 */
	class FTP_Exception          : public Pandora::Pandora_Exception {
	};
	
	/**
	 * The FTP authentication fails when connecting.
	 */
	class Authentication_Failed  : public FTP::FTP_Exception {
	};

	/**
	 * The FTP host is unknown.
	 */
	class Unknown_Host           : public FTP::FTP_Exception {
	};
	
	/**
	 * Client to perform a FTP connection to a host.
	 */
	class Pandora_Ftp_Client {
	private:
		string   host;
		string   username;
		string   password;

		CURL    *curl;
		CURLcode result;
	public:
		Pandora_Ftp_Client     ();
		~Pandora_Ftp_Client    ();
	    
		void   connect         (const string host,
					const int    port,
					const string username,
					const string password);
		
		void   disconnect      ();
					     
		int   ftpFileFilename (const string remote_filename,
					const string filepath);

		string getError        ();
	};
}
#endif
