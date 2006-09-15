/* 
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

#ifdef HAVE_CONFIG_H
# include <config.h>
#endif

#include <gtk/gtk.h>

#include "pandora-applet.h"

#include <glib.h>
#include <glib/gi18n-lib.h>

void
exit_app ()
{
        gtk_main_quit ();
}

int
main (int argc, char *argv[])
{
	PandoraApplet *applet;

	gtk_init (&argc, &argv);
	
        g_set_application_name (_("Pandora applet"));

	bindtextdomain (GETTEXT_PACKAGE, GNOMELOCALEDIR);
	bind_textdomain_codeset (GETTEXT_PACKAGE, "UTF-8");
	textdomain (GETTEXT_PACKAGE);
	
        /* Init threads */
        if (!g_thread_supported ()) {
                g_thread_init (NULL);
        }

	applet = pandora_applet_new ();
	
	if (!applet) {
		return 1;
	}
	
	gtk_main ();

	return 0;
}
