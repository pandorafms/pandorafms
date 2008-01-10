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

#ifndef SETUP_H
#define SETUP_H

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <glib-object.h>

#define PANDORA_TYPE_SETUP		 (pandora_setup_get_type())
#define PANDORA_SETUP(object)		 (G_TYPE_CHECK_INSTANCE_CAST((object), PANDORA_TYPE_SETUP, PandoraSetup))
#define PANDORA_SETUP_CLASS(klass)	 (G_TYPE_CHECK_CLASS_CAST((klass), PANDORA_TYPE_SETUP, PandoraSetupClass))
#define PANDORA_IS_SETUP(object)	 (G_TYPE_CHECK_INSTANCE_TYPE((object), PANDORA_TYPE_SETUP))
#define PANDORA_IS_SETUP_CLASS(klass)	 (G_TYPE_CHECK_CLASS_TYPE((klass), PANDORA_TYPE_SETUP))
#define PANDORA_SETUP_GET_CLASS(object) (G_TYPE_INSTANCE_GET_CLASS((object), PANDORA_TYPE_SETUP, PandoraSetupClass))

typedef struct _PandoraSetupPrivate PandoraSetupPrivate;

typedef struct {
	GObjectClass parent_class;
} PandoraSetupClass; 

/*
 * Setup instance data
 *
 */
typedef struct {
	GObject              parent;
	PandoraSetupPrivate *priv;
} PandoraSetup;

PandoraSetup *pandora_setup_new          (gchar *config_file);

gchar        *pandora_setup_get_host     (PandoraSetup *setup);
gchar        *pandora_setup_get_username (PandoraSetup *setup);
gchar        *pandora_setup_get_password (PandoraSetup *setup);
gchar        *pandora_setup_get_dbname   (PandoraSetup *setup);

void          pandora_setup_set_host     (PandoraSetup *setup, const gchar *host);
void          pandora_setup_set_username (PandoraSetup *setup, const gchar *username);
void          pandora_setup_set_password (PandoraSetup *setup, const gchar *password);
void          pandora_setup_set_dbname   (PandoraSetup *setup, const gchar *dbname);

void          pandora_setup_save_to_disk (PandoraSetup *setup);

#endif
