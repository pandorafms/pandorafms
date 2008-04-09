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

#include "pandora_module_odbc.h"
#include "../pandora_strutils.h"
#include "../pandora_agent_conf.h"
#include <odbc++/setup.h>
#include <odbc++/drivermanager.h>
#include <odbc++/resultset.h>
#include <odbc++/resultsetmetadata.h>
#include <odbc++/preparedstatement.h>

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

/** 
 * Creates a Pandora_Module_Odbc object.
 *
 * @param name Module name
 * @param dsn ODBC dsn string
 * @param query SQL query to do
 */
Pandora_Module_Odbc::Pandora_Module_Odbc (string name,
					  string dsn,
					  string query)
	: Pandora_Module (name) {

	Pandora_Agent_Conf::Pandora_Agent_Conf *conf;

	conf = Pandora_Agent_Conf::getInstance ();
	
	this->setKind (module_odbc_str);
        this->dsn      = dsn;
        this->username = conf->getValue ("odbc_" + dsn + "_username");
	this->password = conf->getValue ("odbc_" + dsn + "_password");
	this->query    = query;
}

/** 
 * Set DSN ODBC connection
 * 
 * @param dsn DSN to set.
 */
void
Pandora_Module_Odbc::setDsn (string dsn) {
	this->dsn = dsn;
}

/** 
 * Set ODBC username.
 * 
 * @param username Username to set.
 */
void
Pandora_Module_Odbc::setUsername (string username) {
        this->username = username;
}

/** 
 * Set ODBC password.
 * 
 * @param password Password to set.
 */
void
Pandora_Module_Odbc::setPassword (string password) {
	this->password = password;
}

/** 
 * Set module SQL query to ODBC connection
 * 
 * @param query SQL query to launch.
 */
void
Pandora_Module_Odbc::setQuery (string query) {
	this->query = query;
}

/** 
 * Get DSN ODBC string.
 * 
 * @return DSN ODBC string.
 */
string
Pandora_Module_Odbc::getDsn () {
	return this->dsn;
}

/** 
 * Get ODBC password.
 * 
 * @return Connection password
 */
string
Pandora_Module_Odbc::getPassword () {
	return this->password;
}

/** 
 * Get ODBC username.
 * 
 * @return 
 */
string
Pandora_Module_Odbc::getUsername () {
	return this->username;
}

/** 
 * Get SQL module query.
 * 
 * @return SQL module query
 */
string
Pandora_Module_Odbc::getQuery () {
	return this->query;
}

/** 
 * Perform the query into the database and set the output value of the
 * module.
 */
void
Pandora_Module_Odbc::doQuery () {
	string retval;
	auto_ptr<Statement> statement;
	auto_ptr<ResultSet> results;
	ResultSetMetaData  *metadata;
	int                 columns;
	
	statement = auto_ptr<Statement> (this->con->createStatement ());
	results = auto_ptr<ResultSet> (statement->executeQuery (query));
	metadata = results->getMetaData ();
	columns = metadata->getColumnCount ();
	
	if (results->next ()) {
		if (this->getTypeInt () == TYPE_GENERIC_DATA_STRING) {
			string output;
			for (int i = 1; i <= columns; i++) {
				output +=  results->getString (i);
				if (i + 1 <= columns)
					output += " | ";
			}

			this->setOutput (output);
		} else {
			this->setOutput (longtostr (results->getLong (1)));
		}
	}
}

void
Pandora_Module_Odbc::run () {
	try {
                Pandora_Module::run ();
        } catch (Interval_Not_Fulfilled e) {
                return;
        }

	if (this->query == "") {
		pandoraLog ("Error on module ODBC '%s': No query to execute",
			    this->module_name.c_str ());
		return;
	}

	if (this->dsn == "") {
		pandoraLog ("Error on module ODBC '%s': No DSN to connect to",
			    this->module_name.c_str ());
		return;
	}

	if (this->username == "") {
		pandoraLog ("Error on module ODBC '%s': No username to connect to DSN %s. "
			    "Add %s_username parameter to configuration file",
			    this->module_name.c_str (), this->dsn.c_str (), this->dsn.c_str ());
		return;
	}
	
	try {
		pandoraLog ("Module ODBC connecting to dsn=%s, uid=%s, pwd=****",
			    this->dsn.c_str (), this->username.c_str ());
		this->con = DriverManager::getConnection (this->dsn, this->username, this->password);

		this->doQuery ();
		delete this->con;
		DriverManager::shutdown();
	} catch (SQLException &e) {
		pandoraLog ("Error on module ODBC '%s': %s", this->module_name.c_str (), e.getMessage().c_str ());
		this->has_output = false;
	} catch (Pandora_Exception e) {
		this->has_output = false;
	}
}
