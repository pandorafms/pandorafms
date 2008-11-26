/* Test module to prove SSH connection.

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

#ifndef __PANDORA_SSH_TEST__
#define __PANDORA_SSH_TEST__

#include "pandora_ssh_client.h"
#include "../pandora_agent_conf.h"

namespace SSH {
	/**
	 * Class to perform a test of the SSH configuration.
	 *
	 * An object of this class will read the configuration file
	 * and copy a blank xml file into remote server path.
	 */
	class Pandora_SSH_Test {
	private:
		Pandora_Ssh_Client          *ssh_client;
		Pandora::Pandora_Agent_Conf *conf;
	public:
		Pandora_SSH_Test  ();
		~Pandora_SSH_Test ();
		void test         ();
	};
}

#endif
