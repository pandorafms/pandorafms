/* Pandora Windows agent main file.

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

#include "pandora.h"
#include "pandora_windows_service.h"
#include "ssh/pandora_ssh_test.h"
#include "ftp/pandora_ftp_test.h"

#define PATH_SIZE                         _MAX_PATH+1
#define SERVICE_INSTALL_CMDLINE_PARAM    "--install"
#define SERVICE_UNINSTALL_CMDLINE_PARAM  "--uninstall"
#define SSH_TEST_CMDLINE_PARAM           "--test-ssh"
#define FTP_TEST_CMDLINE_PARAM           "--test-ftp"
#define HELP_CMDLINE_PARAM               "--help"
#define PROCESS_CMDLINE_PARAM            "--process"

int
main (int argc, char *argv[]) {
	Pandora_Windows_Service *service;
	char                     buffer[PATH_SIZE];
	string                   aux;
	unsigned int             pos;
	bool                     process = false;

	service = Pandora_Windows_Service::getInstance ();
	service->setValues (Pandora::name, Pandora::display_name,
			    Pandora::description);
	service->start ();
	
	GetModuleFileName (NULL, buffer, MAX_PATH);
	aux = buffer;
	Pandora::setPandoraInstallPath (aux);
	pos = aux.rfind ("\\");
	aux.erase (pos + 1);
	Pandora::setPandoraInstallDir (aux);

	/* Check the parameters */
	for (int i = 1; i < argc; i++) {
		if (_stricmp(argv[i], SERVICE_INSTALL_CMDLINE_PARAM) == 0) {
			/* Install parameter */
			service->install (Pandora::getPandoraInstallPath ().c_str ());
		
			delete service;
		
			return 0;
		} else if (_stricmp(argv[i], SERVICE_UNINSTALL_CMDLINE_PARAM) == 0) {
			/* Uninstall parameter */
			service->uninstall ();
		
			delete service;
		
			return 0;
		} else if (_stricmp(argv[i], SSH_TEST_CMDLINE_PARAM) == 0) {
			/* SSH test parameter */
			SSH::Pandora_SSH_Test ssh_test;
		
			delete service;
		
			try {
				ssh_test.test ();
			} catch (Pandora_Exception e) {
				return 1;
			}
			
			return 0;
		} else if (_stricmp(argv[i], FTP_TEST_CMDLINE_PARAM) == 0) {
			/* SSH test parameter */
			FTP::Pandora_FTP_Test ftp_test;
			
			delete service;
		
			try {
				ftp_test.test ();
			} catch (Pandora_Exception e) {
				return 1;
			}
		
			return 0;
		} else if (_stricmp(argv[i], HELP_CMDLINE_PARAM) == 0) {
			/* Help parameter */
			cout << "Pandora agent for Windows. ";
			cout << "Version " << getPandoraAgentVersion () << endl;
			cout << "Usage: " << argv[0] << " [OPTION]" << endl << endl;
			cout << "Available options are:" << endl;
			cout << "\t" << SERVICE_INSTALL_CMDLINE_PARAM;
			cout <<	":  Install the Pandora Agent service." << endl;
			cout << "\t" << SERVICE_UNINSTALL_CMDLINE_PARAM;
			cout << ": Uninstall the Pandora Agent service." << endl;
			cout << "\t" << SSH_TEST_CMDLINE_PARAM;
			cout << ": Test the SSH Pandora Agent configuration." << endl;
			cout << "\t" << FTP_TEST_CMDLINE_PARAM;
			cout << ": Test the FTP Pandora Agent configuration." << endl;
			cout << "\t" << PROCESS_CMDLINE_PARAM;
			cout << ": Run the Pandora Agent as a user process instead of a service." << endl;
		
			return 0;
		} else if (_stricmp(argv[i], PROCESS_CMDLINE_PARAM) == 0) {
			process = true;
		} else {
			/* No parameter recognized */
			cout << "Pandora agent for Windows. ";
			cout << "Version " << getPandoraAgentVersion () << endl;
			cout << "Usage: " << argv[0] << " [" << SERVICE_INSTALL_CMDLINE_PARAM;
			cout << "] [" << SERVICE_UNINSTALL_CMDLINE_PARAM;
			cout << "] [" << SSH_TEST_CMDLINE_PARAM;
			cout << "] [" << FTP_TEST_CMDLINE_PARAM;
			cout << "] [" << PROCESS_CMDLINE_PARAM << "]";
			cout << endl << endl;
			cout << "Run " << argv[0] << " with " << HELP_CMDLINE_PARAM;
			cout << " parameter for more info." << endl;
		
			return 1;
		}
	}
	if (process) {
		cout << "Pandora agent is now running" << endl;
		service->pandora_init ();
		while (1) {
			service->pandora_run ();
			Sleep (service->interval / 1000);
		}
	} else {
		service->run ();
	}
	
	delete service;
	
	return 0;
}
