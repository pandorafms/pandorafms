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

#ifndef INFO_WINDOW_H
#define INFO_WINDOW_H

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include "pandora-status.h"

#include <gtk/gtk.h>

#define PANDORA_INFO_WINDOW_TYPE		 (pandora_info_window_get_type())
#define PANDORA_INFO_WINDOW(object)		 (G_TYPE_CHECK_INSTANCE_CAST((object), PANDORA_INFO_WINDOW_TYPE, PandoraInfoWindow))
#define PANDORA_INFO_WINDOW_CLASS(klass)	 (G_TYPE_CHECK_CLASS_CAST((klass), PANDORA_INFO_WINDOW_TYPE, PandoraInfoWindowClass))
#define PANDORA_IS_INFO_WINDOW(object)	 (G_TYPE_CHECK_INSTANCE_TYPE((object), PANDORA_INFO_WINDOW_TYPE))
#define PANDORA_IS_INFO_WINDOW_CLASS(klass)	 (G_TYPE_CHECK_CLASS_TYPE((klass), PANDORA_INFO_WINDOW_TYPE))
#define PANDORA_INFO_WINDOW_GET_CLASS(object) (G_TYPE_INSTANCE_GET_CLASS((object), PANDORA_INFO_WINDOW_TYPE, PandoraInfoWindowClass))

typedef struct _PandoraInfoWindowPrivate PandoraInfoWindowPrivate;

typedef struct {
	GtkWindowClass	parent_class;
} PandoraInfoWindowClass; 

typedef struct {
	GtkWindow                 parent;
	PandoraInfoWindowPrivate *priv;
} PandoraInfoWindow;

GtkWidget *pandora_info_window_new        (void);
void       pandora_info_window_set_status (PandoraInfoWindow *window,
					   PandoraStatus     *status);

#endif
