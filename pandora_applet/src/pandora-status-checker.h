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

#ifndef STATUS_CHECKER_H
#define STATUS_CHECKER_H

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <glib-object.h>

#include "pandora-setup.h"
#include "pandora-status.h"

#define PANDORA_STATUS_CHECKER_TYPE		 (pandora_status_checker_get_type())
#define PANDORA_STATUS_CHECKER(object)		 (G_TYPE_CHECK_INSTANCE_CAST((object), PANDORA_STATUS_CHECKER_TYPE, PandoraStatusChecker))
#define PANDORA_STATUS_CHECKER_CLASS(klass)	 (G_TYPE_CHECK_CLASS_CAST((klass), PANDORA_STATUS_CHECKER_TYPE, PandoraStatusCheckerClass))
#define PANDORA_IS_STATUS_CHECKER(object)	 (G_TYPE_CHECK_INSTANCE_TYPE((object), PANDORA_STATUS_CHECKER_TYPE))
#define PANDORA_IS_STATUS_CHECKER_CLASS(klass)	 (G_TYPE_CHECK_CLASS_TYPE((klass), PANDORA_STATUS_CHECKER_TYPE))
#define PANDORA_STATUS_CHECKER_GET_CLASS(object) (G_TYPE_INSTANCE_GET_CLASS((object), PANDORA_STATUS_CHECKER_TYPE, PandoraStatusCheckerClass))

typedef struct _PandoraStatusCheckerPrivate PandoraStatusCheckerPrivate;

typedef struct {
	GObjectClass parent_class;
} PandoraStatusCheckerClass; 

typedef struct {
	GObject                      parent;
	
	PandoraStatusCheckerPrivate *priv;
} PandoraStatusChecker;

PandoraStatusChecker *pandora_status_checker_new  (PandoraSetup         *setup,
						   PandoraStatus        *status);

void                  pandora_status_checker_run  (PandoraStatusChecker *checker);
void                  pandora_status_checker_stop (PandoraStatusChecker *checker);

#endif
