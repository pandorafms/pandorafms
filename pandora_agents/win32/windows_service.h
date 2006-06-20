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

class Windows_Service {
protected:
        char     *service_name;
        char     *service_display_name;
        char     *service_description;
        HANDLE    stop_event;
        int       sleep_time;
        SC_HANDLE sc_service;
        /* Pointer to functions that will run the service */
        void (Windows_Service::*run_function)  ();
        void (Windows_Service::*init_function) ();
public:
        Windows_Service ();
        
        /* Set the values of the service to run.
           Notice: A program can have ONLY ONE object of this class. */
        Windows_Service  (const char * svc_name, const char * svc_display_name,
                          const char * svc_description);
        
        ~Windows_Service ();
        
        /* Install the service in the Windows registry. */
        void install    (LPCTSTR application_binary_path);
        
        /* Uninstall the service, removint the key in the Windows registry. */
        void uninstall  ();
        
        /* Run the service, which has to be installed previously.
         * The service will execute the function indicated in the 
         * setRunFunction */
        int  run              ();
        
        /* Set the function that will be called on service start. This
         * is called the "run function" */
        void setRunFunction (void (Windows_Service::*f) ());
        
        /* Set the function that will be called on service init.  This
         * is called the "init function" */
        void setInitFunction (void (Windows_Service::*f) ());

        /* Calls the functions. There's no need to be called from the outside of this
         * class. They are used in a internal method because of another Windows 
         * matter... */
        void execRunFunction ();
        void execInitFunction ();
        
        LPSTR getServiceName  ();
        
        /* Set the time that the service will sleep between each execution of
         * the work funtion. If this value is not set, the service will run this
         * function once. */
        void setSleepTime     (unsigned int s);
};
#endif /* __WINDOWS_SERVICE_H__ */
