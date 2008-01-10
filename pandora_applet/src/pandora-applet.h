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

#ifndef APPLET_H
#define APPLET_H

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <gtk/gtk.h>
#include "eggtrayicon.h"

#define PANDORA_TYPE_APPLET		 (pandora_applet_get_type())
#define PANDORA_APPLET(object)		 (G_TYPE_CHECK_INSTANCE_CAST((object), PANDORA_TYPE_APPLET, PandoraApplet))
#define PANDORA_APPLET_CLASS(klass)	 (G_TYPE_CHECK_CLASS_CAST((klass), PANDORA_TYPE_APPLET, PandoraAppletClass))
#define PANDORA_IS_APPLET(object)	 (G_TYPE_CHECK_INSTANCE_TYPE((object), PANDORA_TYPE_APPLET))
#define PANDORA_IS_APPLET_CLASS(klass)	 (G_TYPE_CHECK_CLASS_TYPE((klass), PANDORA_TYPE_APPLET))
#define PANDORA_APPLET_GET_CLASS(object) (G_TYPE_INSTANCE_GET_CLASS((object), PANDORA_TYPE_APPLET, PandoraAppletClass))

typedef struct _PandoraAppletPrivate PandoraAppletPrivate;

typedef struct {
	EggTrayIconClass	parent_class;
} PandoraAppletClass; 

/*
 * Applet instance data
 *
 */
typedef struct {
	EggTrayIcon           parent;
	PandoraAppletPrivate *priv;
} PandoraApplet;

PandoraApplet *pandora_applet_new  (void);

GdkPixbuf     *load_icon_from_disk (gchar *icon_name);

#endif
