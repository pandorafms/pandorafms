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
#include <config.h>
#endif

#include <glib.h>
#include <glib/gi18n.h>
#include <gtk/gtk.h>

#include "pandora-setup-dialog.h"

struct _PandoraSetupDialogPrivate {
	GtkWidget    *entry_host;
	GtkWidget    *entry_username;
	GtkWidget    *entry_password;
	gchar        *real_password;
	GtkWidget    *entry_dbname;

	PandoraSetup *setup;
};

#define PANDORA_SETUP_DIALOG_GET_PRIVATE(object) \
        (G_TYPE_INSTANCE_GET_PRIVATE ((object), PANDORA_SETUP_DIALOG_TYPE, \
				      PandoraSetupDialogPrivate))

static void     pandora_setup_dialog_init       (PandoraSetupDialog      *dialog);
static void     pandora_setup_dialog_class_init (PandoraSetupDialogClass *klass);
static void     pandora_setup_dialog_finalize   (GObject                *object);

static gboolean pandora_setup_close_cb          (GtkWidget *dialog, gpointer data);

GType
pandora_setup_dialog_get_type (void)
{
        static GType type = 0;

        if (!type) {
                static const GTypeInfo info = {
                        sizeof (PandoraSetupDialogClass),
                        (GBaseInitFunc) NULL,
                        (GBaseFinalizeFunc) NULL,
                        (GClassInitFunc) pandora_setup_dialog_class_init,
                        NULL,
                        NULL,
                        sizeof (PandoraSetupDialog),
                        0,
                        (GInstanceInitFunc) pandora_setup_dialog_init
                };

                type = g_type_register_static (GTK_TYPE_DIALOG, "PandoraSetupDialog",
                                               &info, 0);
        }

        return type;
}

static void
pandora_setup_dialog_init (PandoraSetupDialog *dialog)
{
	GtkWidget *table;
	GtkWidget *hbutton_box;
	GtkWidget *button_close;
	GtkWidget *frame;
	GtkWidget *label_title;
	GtkWidget *label;
	GtkWidget *alignment;
	gchar     *str;
	
	dialog->priv = PANDORA_SETUP_DIALOG_GET_PRIVATE (dialog);
	dialog->priv->setup = NULL;
	

	gtk_window_set_title (GTK_WINDOW (dialog), _("Preferences"));
	gtk_container_set_border_width (GTK_CONTAINER (dialog), 5);
	gtk_box_set_spacing (GTK_BOX (GTK_DIALOG (dialog)->vbox), 12);
	gtk_window_set_destroy_with_parent (GTK_WINDOW (dialog), TRUE);
	gtk_dialog_set_has_separator (GTK_DIALOG (dialog), FALSE);

	gtk_dialog_add_buttons (GTK_DIALOG (dialog),
				GTK_STOCK_CANCEL, GTK_RESPONSE_CANCEL,
				GTK_STOCK_OK, GTK_RESPONSE_ACCEPT,
				NULL);
	gtk_dialog_set_default_response (GTK_DIALOG (dialog),
                                         GTK_RESPONSE_CANCEL);

	gtk_window_set_icon_name (GTK_WINDOW (dialog),
				  GTK_STOCK_PREFERENCES);
	/* Frame */
	str = g_strdup_printf ("<b>%s:</b>", _("Database connection"));
	label_title = gtk_label_new (NULL);
	gtk_label_set_markup (GTK_LABEL (label_title), str);
        gtk_misc_set_alignment (GTK_MISC (label_title), 0, 0.5);

	frame = gtk_frame_new (NULL);
	gtk_frame_set_label_widget (GTK_FRAME (frame), label_title);
	gtk_container_set_border_width (GTK_CONTAINER (frame), 5);
	gtk_frame_set_shadow_type (GTK_FRAME (frame), GTK_SHADOW_NONE);

	/* Table inside frame */
	table = gtk_table_new (4, 2, FALSE);
	gtk_table_set_row_spacings (GTK_TABLE (table), 5);
	gtk_table_set_col_spacings (GTK_TABLE (table), 5);
	
	/* First row */
	label = gtk_label_new (_("Host"));
	gtk_misc_set_alignment (GTK_MISC (label), 0, 0.5);
	
	dialog->priv->entry_host = gtk_entry_new ();

	gtk_table_attach (GTK_TABLE (table), label,
			  0, 1, 0, 1, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);
	gtk_table_attach (GTK_TABLE (table), dialog->priv->entry_host,
			  1, 2, 0, 1, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);

	/* Second row */
	label = gtk_label_new (_("Username"));
	gtk_misc_set_alignment (GTK_MISC (label), 0, 0.5);
	
	dialog->priv->entry_username = gtk_entry_new ();

	gtk_table_attach (GTK_TABLE (table), label,
			  0, 1, 1, 2, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);
	gtk_table_attach (GTK_TABLE (table), dialog->priv->entry_username,
			  1, 2, 1, 2, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);
	
	/* Third row */
	label = gtk_label_new (_("Password"));
	gtk_misc_set_alignment (GTK_MISC (label), 0, 0.5);
	
	dialog->priv->entry_password = gtk_entry_new ();
	gtk_entry_set_invisible_char (GTK_ENTRY (dialog->priv->entry_password),
				      '*');
	gtk_entry_set_visibility (GTK_ENTRY (dialog->priv->entry_password),
				  FALSE);

	gtk_table_attach (GTK_TABLE (table), label,
			  0, 1, 2, 3, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);
	gtk_table_attach (GTK_TABLE (table), dialog->priv->entry_password,
			  1, 2, 2, 3, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);
	
	/* Fourth row */
	label = gtk_label_new (_("Database name"));
	gtk_misc_set_alignment (GTK_MISC (label), 0, 0.5);
	
	dialog->priv->entry_dbname = gtk_entry_new ();
	
	gtk_table_attach (GTK_TABLE (table), label,
			  0, 1, 3, 4, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);
	gtk_table_attach (GTK_TABLE (table), dialog->priv->entry_dbname,
			  1, 2, 3, 4, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);

	alignment = gtk_alignment_new (0.5, 0.5, 1.0, 1.0);
	gtk_alignment_set_padding (GTK_ALIGNMENT (alignment), 0, 0, 12, 0);
	gtk_container_add (GTK_CONTAINER (alignment), table);
	gtk_container_add (GTK_CONTAINER (frame), alignment);
	
	gtk_widget_show_all (frame);

	gtk_box_pack_start (GTK_BOX (GTK_DIALOG (dialog)->vbox), frame, FALSE, FALSE, 0);
}


static void
pandora_setup_dialog_class_init (PandoraSetupDialogClass *klass)
{
        GObjectClass *object_class;

        g_type_class_add_private (klass, sizeof (PandoraSetupDialogPrivate));

	object_class = G_OBJECT_CLASS (klass);
	object_class->finalize = pandora_setup_dialog_finalize;
}

static void
pandora_setup_dialog_finalize (GObject *object)
{
	PandoraSetupDialog *dialog;

	dialog = PANDORA_SETUP_DIALOG (object);
	
	if (dialog->priv->setup) {
		g_object_unref (dialog->priv->setup);
	}
}

GtkWidget *
pandora_setup_dialog_new (void)
{
        GtkWidget *dialog;

        dialog = GTK_WIDGET (g_object_new (PANDORA_SETUP_DIALOG_TYPE,
						       NULL));
        return dialog;
}

void
pandora_setup_dialog_set_setup (PandoraSetupDialog *dialog,
				PandoraSetup       *setup)
{
	gchar *value;
	
	if (dialog->priv->setup) {
		g_object_unref (dialog->priv->setup);
	}

	dialog->priv->setup = setup;
	g_object_ref (G_OBJECT (setup));

	value = pandora_setup_get_host (setup);
	if (value) {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_host),
				    value);
		g_free (value);
		value = NULL;
	} else {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_host),
				    "");
	}

	value = pandora_setup_get_username (setup);
	if (value) {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_username),
				    value);
		g_free (value);
	} else {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_username),
				    "");
	}

	value = pandora_setup_get_password (setup);
	if (value) {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_password),
				    NOT_SHOWN_PASSWORD);
		g_free (value);
	} else {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_password),
				    "");
	}

	value = pandora_setup_get_dbname (setup);
	if (value) {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_dbname),
				    value);
		g_free (value);
	} else {
		gtk_entry_set_text (GTK_ENTRY (dialog->priv->entry_dbname),
				    "");
	}
}

void
pandora_setup_dialog_apply_changes (PandoraSetupDialog *dialog)
{
	const gchar *host;
	const gchar *username;
	const gchar *password;
	const gchar *dbname;
	
	host = gtk_entry_get_text (GTK_ENTRY (dialog->priv->entry_host));
	username = gtk_entry_get_text (GTK_ENTRY (dialog->priv->entry_username));
	password = gtk_entry_get_text (GTK_ENTRY (dialog->priv->entry_password));
	dbname = gtk_entry_get_text (GTK_ENTRY (dialog->priv->entry_dbname));
	
	g_object_set (G_OBJECT (dialog->priv->setup),
		      "host",     host,
		      "username", username,
		      "dbname",   dbname,
		      NULL);

	if (g_ascii_strcasecmp (password, NOT_SHOWN_PASSWORD) != 0) {
		pandora_setup_set_password (dialog->priv->setup, password);
	}
}
