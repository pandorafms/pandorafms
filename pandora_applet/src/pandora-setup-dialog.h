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

#ifndef SETUP_DIALOG_H
#define SETUP_DIALOG_H

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <gtk/gtk.h>

#include "pandora-setup.h"

#define NOT_SHOWN_PASSWORD "nonenonenone"

#define PANDORA_SETUP_DIALOG_TYPE		 (pandora_setup_dialog_get_type())
#define PANDORA_SETUP_DIALOG(object)		 (G_TYPE_CHECK_INSTANCE_CAST((object), PANDORA_SETUP_DIALOG_TYPE, PandoraSetupDialog))
#define PANDORA_SETUP_DIALOG_CLASS(klass)	 (G_TYPE_CHECK_CLASS_CAST((klass), PANDORA_SETUP_DIALOG_TYPE, PandoraSetupDialogClass))
#define PANDORA_IS_SETUP_DIALOG(object)	 (G_TYPE_CHECK_INSTANCE_TYPE((object), PANDORA_SETUP_DIALOG_TYPE))
#define PANDORA_IS_SETUP_DIALOG_CLASS(klass)	 (G_TYPE_CHECK_CLASS_TYPE((klass), PANDORA_SETUP_DIALOG_TYPE))
#define PANDORA_SETUP_DIALOG_GET_CLASS(object) (G_TYPE_INSTANCE_GET_CLASS((object), PANDORA_SETUP_DIALOG_TYPE, PandoraSetupDialogClass))

typedef struct _PandoraSetupDialogPrivate PandoraSetupDialogPrivate;

typedef struct {
	GtkDialogClass	parent_class;
} PandoraSetupDialogClass; 

typedef struct {
	GtkDialog                 parent;
	PandoraSetupDialogPrivate *priv;
} PandoraSetupDialog;

GtkWidget *pandora_setup_dialog_new           (void);

void       pandora_setup_dialog_set_setup     (PandoraSetupDialog *dialog,
					       PandoraSetup       *setup);
void       pandora_setup_dialog_apply_changes (PandoraSetupDialog *dialog);

#endif
