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

#ifndef STATUS_H
#define STATUS_H

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <glib-object.h>

typedef enum {
	STATE_INVALID,
	STATE_UNKNOWN,
	STATE_OK,
	STATE_BAD
} PandoraState;

#define PANDORA_STATUS_TYPE		 (pandora_status_get_type())
#define PANDORA_STATUS(object)		 (G_TYPE_CHECK_INSTANCE_CAST((object), PANDORA_STATUS_TYPE, PandoraStatus))
#define PANDORA_STATUS_CLASS(klass)	 (G_TYPE_CHECK_CLASS_CAST((klass), PANDORA_STATUS_TYPE, PandoraStatusClass))
#define PANDORA_IS_STATUS(object)	 (G_TYPE_CHECK_INSTANCE_TYPE((object), PANDORA_STATUS_TYPE))
#define PANDORA_IS_STATUS_CLASS(klass)	 (G_TYPE_CHECK_CLASS_TYPE((klass), PANDORA_STATUS_TYPE))
#define PANDORA_STATUS_GET_CLASS(object) (G_TYPE_INSTANCE_GET_CLASS((object), PANDORA_STATUS_TYPE, PandoraStatusClass))

typedef struct _PandoraStatusPrivate PandoraStatusPrivate;

typedef struct {
	GObject                      parent;
	
	PandoraStatusPrivate *priv;
} PandoraStatus;

typedef struct {
	GObjectClass parent_class;

	void (* changed) (PandoraStatus *view);
} PandoraStatusClass; 

PandoraStatus *pandora_status_new         (void);
void           pandora_status_set_alerts  (PandoraStatus *status, PandoraState value);
void           pandora_status_set_agents  (PandoraStatus *status, PandoraState value);
void           pandora_status_set_servers (PandoraStatus *status, PandoraState value);

PandoraState   pandora_status_get_alerts  (PandoraStatus *status);
PandoraState   pandora_status_get_agents  (PandoraStatus *status);
PandoraState   pandora_status_get_servers (PandoraStatus *status);


#endif
