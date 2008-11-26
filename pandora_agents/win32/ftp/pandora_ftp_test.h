/* Test module to prove FTP connection.

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

#include "pandora_ftp_client.h"
#include "../pandora_agent_conf.h"

using namespace Pandora;

namespace FTP {
	/**
	 * Class to perform a test of the FTP configuration.
	 *
	 * An object of this class will read the configuration file
	 * and copy a blank xml file into remote server path.
	 */
	class Pandora_FTP_Test {
	private:
		Pandora_Ftp_Client *ftp_client;
		Pandora_Agent_Conf *conf;
	public:
		Pandora_FTP_Test  ();
		~Pandora_FTP_Test ();
		void test       ();
	};
}
