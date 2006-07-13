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

#define PATH_SIZE                         _MAX_PATH+1
#define SERVICE_INSTALL_CMDLINE_PARAM    "--install"
#define SERVICE_UNINSTALL_CMDLINE_PARAM  "--uninstall"
#define SSH_TEST_CMDLINE_PARAM           "--test-ssh"

int
main (int argc, char *argv[]) {
        Pandora_Windows_Service *service;
        char                     buffer[PATH_SIZE];
        string                   aux;
        unsigned int             pos;
        
        service = new Pandora_Windows_Service (Pandora::name, Pandora::display_name,
                                               Pandora::description);
                                               
        GetModuleFileName (NULL, buffer, MAX_PATH);
        aux = buffer;
        Pandora::setPandoraInstallPath (aux);
        pos = aux.rfind ("\\");
        aux.erase (pos + 1);
        Pandora::setPandoraInstallDir (aux);
        
        for (int i = 1; i < argc; i++) {
                if (_stricmp(argv[i], SERVICE_INSTALL_CMDLINE_PARAM) == 0) {
                        service->install (Pandora::getPandoraInstallPath ().c_str ());
                        
                        delete service;
                        return 0;
                } else if (_stricmp(argv[i], SERVICE_UNINSTALL_CMDLINE_PARAM) == 0) {
                        service->uninstall ();
                        
                        delete service;
                        return 0;
		} else if (_stricmp(argv[i], SSH_TEST_CMDLINE_PARAM) == 0) {
                        SSH::Pandora_SSH_Test ssh_test;
			
                        delete service;
                        
                        try {
                                ssh_test.test ();
                        } catch (Pandora_Exception e) {
                                return 1;
                        }
                        return 0;
                } else {
                        cerr << "Usage: " << argv[0] << "[" << SERVICE_INSTALL_CMDLINE_PARAM
                             << "] [" << SERVICE_UNINSTALL_CMDLINE_PARAM << "]" << endl;
                        return 1;
                }
        }
        service->run ();
	        
	delete service;
        return 0;
}
