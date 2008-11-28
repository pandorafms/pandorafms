/* Library to create a Windows service for Win32.

   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.
   Based on Snort code.
  
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

#ifndef	__WINDOWS_SERVICE_H__
#define	__WINDOWS_SERVICE_H__

#include <windows.h>
#include <winsvc.h>  /* for Service stuff */

/**
 * Class to install and use a Windows service easily.
 *
 * If you want to use it, create a child class that
 * set the init_function and run_function. Then use
 * install function to perform the service installation
 * and the run function on the main function to start it.
 * Notice: A program should have only one object of this class.
 */
class Windows_Service {
protected:
	char     *service_name;
	char     *service_display_name;
	char     *service_description;
private:
	HANDLE    stop_event;
	int       sleep_time;
	SC_HANDLE sc_service;
	
	void (Windows_Service::*run_function)  ();
	void (Windows_Service::*init_function) ();
public:
	Windows_Service        ();
	
	Windows_Service        (const char * svc_name,
				const char * svc_display_name,
				const char * svc_description);
	
	~Windows_Service       ();
	
	void  install          (LPCTSTR application_binary_path);
	void  uninstall        ();
	void  run              ();
	void  setRunFunction   (void (Windows_Service::*f) ());
	void  setInitFunction  (void (Windows_Service::*f) ());
	LPSTR getServiceName   ();
	void  setSleepTime     (unsigned int s);

	void  execRunFunction  ();
	void  execInitFunction ();
};
#endif /* __WINDOWS_SERVICE_H__ */
