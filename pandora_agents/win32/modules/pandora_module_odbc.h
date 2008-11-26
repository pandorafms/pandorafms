/* Pandora ODBC module. These modules check the free space in a
   logical drive.

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

#ifndef	__PANDORA_MODULE_ODBC_H__
#define	__PANDORA_MODULE_ODBC_H__

#include "pandora_module.h"
#include <odbc++/connection.h>

using namespace odbc;

namespace Pandora_Modules {
	/**
	 * Module to retrieve a value based on a SQL query to an ODBC
	 * connection.
	 */
	class Pandora_Module_Odbc : public Pandora_Module {
	private:
		string      dsn;
		string      username;
		string      password;
		string      query;
		Connection *con;
		
		void   doQuery ();
	public:
		Pandora_Module_Odbc (string name,
				     string dsn,
				     string query);

		void   setDsn       (string dsn);
		void   setUsername  (string username);
		void   setPassword  (string password);
		void   setQuery     (string query);

		string getDsn       ();
		string getPassword  ();
		string getUsername  ();
		string getQuery     ();
		
		void   run          ();
	};
}

#endif
