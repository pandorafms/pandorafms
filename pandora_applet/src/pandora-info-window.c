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

#include "pandora-applet.h"
#include "pandora-info-window.h"

struct _PandoraInfoWindowPrivate {
	GtkWidget     *label_alerts;
	GtkWidget     *label_servers;
	GtkWidget     *label_agents;

	GtkImage      *image_alerts;
	GtkImage      *image_servers;
	GtkImage      *image_agents;
	
	GdkPixbuf     *pixbuf_alerts_ok;
	GdkPixbuf     *pixbuf_alerts_bad;
	GdkPixbuf     *pixbuf_alerts_unknown;
	
	GdkPixbuf     *pixbuf_servers_ok;
	GdkPixbuf     *pixbuf_servers_bad;
	GdkPixbuf     *pixbuf_servers_unknown;
	
	GdkPixbuf     *pixbuf_agents_ok;
	GdkPixbuf     *pixbuf_agents_bad;
	GdkPixbuf     *pixbuf_agents_unknown;

	PandoraState   state_alerts;
	PandoraState   state_servers;
	PandoraState   state_agents;
	
	PandoraStatus *status;
};

#define PANDORA_INFO_WINDOW_GET_PRIVATE(object) \
        (G_TYPE_INSTANCE_GET_PRIVATE ((object), PANDORA_INFO_WINDOW_TYPE, \
				      PandoraInfoWindowPrivate))

static void     pandora_info_window_init                  (PandoraInfoWindow      *window);
static void     pandora_info_window_class_init            (PandoraInfoWindowClass *klass);
static void     pandora_info_window_finalize              (GObject   *object);

static gboolean pandora_info_window_delete_cb             (GtkWidget *dialog,
							   GdkEvent  *event,
							   gpointer  data);

static gboolean pandora_info_window_close_cb              (GtkWidget *widget,
							   gpointer  data);

static void     pandora_info_window_alerts_changed_cb     (GObject  *object,
							   gint      data,
							   gpointer  user_data);

static void     pandora_info_window_agents_changed_cb     (GObject  *object,
							   gint      data,
							   gpointer  user_data);

static void     pandora_info_window_servers_changed_cb    (GObject  *object,
							   gint      data,
							   gpointer  user_data);

static void     pandora_info_window_status_update_all     (PandoraInfoWindow *window);

static void     pandora_info_window_status_update_alerts  (PandoraInfoWindow *window,
							   PandoraState       state);
static void     pandora_info_window_status_update_agents  (PandoraInfoWindow *window,
							   PandoraState       state);
static void     pandora_info_window_status_update_servers (PandoraInfoWindow *window,
							   PandoraState       state);

GType
pandora_info_window_get_type (void)
{
        static GType type = 0;

        if (!type) {
                static const GTypeInfo info = {
                        sizeof (PandoraInfoWindowClass),
                        (GBaseInitFunc) NULL,
                        (GBaseFinalizeFunc) NULL,
                        (GClassInitFunc) pandora_info_window_class_init,
                        NULL,
                        NULL,
                        sizeof (PandoraInfoWindow),
                        0,
                        (GInstanceInitFunc) pandora_info_window_init
                };

                type = g_type_register_static (GTK_TYPE_WINDOW, "PandoraInfoWindow",
                                               &info, 0);
        }

        return type;
}

static void
pandora_info_window_init (PandoraInfoWindow *window)
{
	GtkWidget *table;
	GtkWidget *image;
	GtkWidget *hbutton_box;
	GtkWidget *button_close;

	window->priv = PANDORA_INFO_WINDOW_GET_PRIVATE (window);
	
	window->priv->status = NULL;
	window->priv->state_alerts = STATE_INVALID;
	window->priv->state_servers = STATE_INVALID;
	window->priv->state_agents = STATE_INVALID;
	
	window->priv->pixbuf_alerts_ok = load_icon_from_disk ("pandora-alerts-ok.png");
	window->priv->pixbuf_alerts_bad = load_icon_from_disk ("pandora-alerts-bad.png");
	window->priv->pixbuf_alerts_unknown = load_icon_from_disk ("pandora-alerts-unknown.png");
	window->priv->image_alerts = GTK_IMAGE (gtk_image_new ());
	
	window->priv->pixbuf_servers_ok = load_icon_from_disk ("pandora-servers-ok.png");
	window->priv->pixbuf_servers_bad = load_icon_from_disk ("pandora-servers-bad.png");
	window->priv->pixbuf_servers_unknown = load_icon_from_disk ("pandora-servers-unknown.png");
	window->priv->image_servers = GTK_IMAGE (gtk_image_new ());
	
	window->priv->pixbuf_agents_ok = load_icon_from_disk ("pandora-agents-ok.png");
	window->priv->pixbuf_agents_bad = load_icon_from_disk ("pandora-agents-bad.png");
	window->priv->pixbuf_agents_unknown = load_icon_from_disk ("pandora-agents-unknown.png");
	window->priv->image_agents = GTK_IMAGE (gtk_image_new ());

	gtk_window_set_title (GTK_WINDOW (window), _("Pandora status"));
	gtk_container_set_border_width (GTK_CONTAINER (window), 5);
	gtk_window_set_icon_name (GTK_WINDOW (window),
				  GTK_STOCK_INFO);

	table = gtk_table_new (4, 2, FALSE);
	gtk_table_set_row_spacings (GTK_TABLE (table), 5);
	gtk_table_set_col_spacings (GTK_TABLE (table), 5);
	
	/* First row */
	window->priv->label_alerts = gtk_label_new (_("Alerts status."));
	gtk_misc_set_alignment (GTK_MISC (window->priv->label_alerts), 0, 0.5);

	gtk_image_set_from_pixbuf (window->priv->image_alerts,
				   window->priv->pixbuf_alerts_unknown);
	
	gtk_table_attach (GTK_TABLE (table), GTK_WIDGET (window->priv->image_alerts),
			  0, 1, 0, 1, GTK_FILL , GTK_FILL, 0, 0);
	gtk_table_attach (GTK_TABLE (table), window->priv->label_alerts,
			  1, 2, 0, 1, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);

	/* Second row */
	window->priv->label_agents = gtk_label_new (_("Agents status."));
	gtk_misc_set_alignment (GTK_MISC (window->priv->label_agents), 0, 0.5);

	gtk_image_set_from_pixbuf (window->priv->image_agents,
				   window->priv->pixbuf_agents_unknown);
	
	gtk_table_attach (GTK_TABLE (table), GTK_WIDGET (window->priv->image_agents),
			  0, 1, 1, 2, GTK_FILL , GTK_FILL, 0, 0);
	gtk_table_attach (GTK_TABLE (table), window->priv->label_agents,
			  1, 2, 1, 2, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);

	/* Third row */
	window->priv->label_servers = gtk_label_new (_("Servers status."));
	gtk_misc_set_alignment (GTK_MISC (window->priv->label_servers), 0, 0.5);

	gtk_image_set_from_pixbuf (window->priv->image_servers,
				   window->priv->pixbuf_servers_unknown);
	
	gtk_table_attach (GTK_TABLE (table), GTK_WIDGET (window->priv->image_servers),
			  0, 1, 2, 3, GTK_FILL , GTK_FILL, 0, 0);
	gtk_table_attach (GTK_TABLE (table), window->priv->label_servers,
			  1, 2, 2, 3, GTK_FILL | GTK_EXPAND, GTK_FILL, 0, 0);

	/* Buttons at the bottom */
	hbutton_box = gtk_hbutton_box_new ();
	gtk_button_box_set_layout (GTK_BUTTON_BOX (hbutton_box),
				   GTK_BUTTONBOX_END);
	
	button_close = gtk_button_new_from_stock (GTK_STOCK_CLOSE);
	g_signal_connect (G_OBJECT (button_close), "clicked",
			  G_CALLBACK (pandora_info_window_close_cb),
			  (gpointer) window);
		
	gtk_box_pack_start (GTK_BOX (hbutton_box), button_close,
			    FALSE, TRUE, 5);
	
	gtk_table_attach (GTK_TABLE (table), hbutton_box,
			  0, 2, 3, 4, GTK_FILL, 0, 0, 0);
	
	gtk_widget_show_all (table);

	gtk_container_add (GTK_CONTAINER (window), table);

	g_signal_connect (G_OBJECT (window), "delete-event",
			  G_CALLBACK (pandora_info_window_delete_cb), NULL);
}


static void
pandora_info_window_class_init (PandoraInfoWindowClass *klass)
{
        GObjectClass *object_class;

        g_type_class_add_private (klass, sizeof (PandoraInfoWindowPrivate));

	object_class = G_OBJECT_CLASS (klass);
	object_class->finalize = pandora_info_window_finalize;
}

static void
pandora_info_window_finalize (GObject *object)
{
	PandoraInfoWindow *window = PANDORA_INFO_WINDOW (object);

	if (window->priv->status) {
		g_object_unref (window->priv->status);
		window->priv->status = NULL;
	}

	if (window->priv->pixbuf_alerts_ok) {
		g_object_unref (window->priv->pixbuf_alerts_ok);
		window->priv->pixbuf_alerts_ok = NULL;
	}

	if (window->priv->pixbuf_servers_ok) {
		g_object_unref (window->priv->pixbuf_servers_ok);
		window->priv->pixbuf_servers_ok = NULL;
	}

	if (window->priv->pixbuf_agents_ok) {
		g_object_unref (window->priv->pixbuf_agents_ok);
		window->priv->pixbuf_agents_ok = NULL;
	}
}

GtkWidget *
pandora_info_window_new (void)
{
        GtkWidget *window;

        window = GTK_WIDGET (g_object_new (PANDORA_INFO_WINDOW_TYPE,
					   "type",          GTK_WINDOW_TOPLEVEL,
					   "default-width", 300,
					   "resizable",     FALSE,
					   NULL));

	gtk_window_set_position (GTK_WINDOW (window), GTK_WIN_POS_CENTER);
	
        return window;
}

static gboolean
pandora_info_window_close_cb (GtkWidget *widget,
			      gpointer data)
{
	GtkWidget *window;
	
	if (data != NULL) {
		window = GTK_WIDGET (data);
		gtk_widget_hide (window);
	}

	return TRUE;
}

static gboolean
pandora_info_window_delete_cb (GtkWidget *widget,
			       GdkEvent  *event,
			       gpointer   data)
{
	gtk_widget_hide (widget);

	return TRUE;
}

void
pandora_info_window_set_status (PandoraInfoWindow *window,
				PandoraStatus     *status)
{
	if (window->priv->status) {
		g_object_unref (window->priv->status);
	}

	window->priv->status = status;

	g_signal_connect (status, "changed_alerts",
			  G_CALLBACK (pandora_info_window_alerts_changed_cb),
			  (gpointer) window);
	g_signal_connect (status, "changed_agents",
			  G_CALLBACK (pandora_info_window_agents_changed_cb),
			  (gpointer) window);
	g_signal_connect (status, "changed_servers",
			  G_CALLBACK (pandora_info_window_servers_changed_cb),
			  (gpointer) window);

	pandora_info_window_status_update_all (window);
}

static void
pandora_info_window_status_update_alerts (PandoraInfoWindow *window,
					  PandoraState       state)
{
	switch (state) {
	case STATE_BAD:
		gtk_image_set_from_pixbuf (window->priv->image_alerts,
					   window->priv->pixbuf_alerts_bad);
		gtk_label_set_text (GTK_LABEL (window->priv->label_alerts),
				    _("There are agents with alerts."));

		break;
	case STATE_OK:
		gtk_image_set_from_pixbuf (window->priv->image_alerts,
					   window->priv->pixbuf_alerts_ok);
		gtk_label_set_text (GTK_LABEL (window->priv->label_alerts),
				    _("There are no alerts."));
		break;
	default:
		gtk_image_set_from_pixbuf (window->priv->image_alerts,
					   window->priv->pixbuf_alerts_unknown);
		gtk_label_set_text (GTK_LABEL (window->priv->label_alerts),
				    _("Alerts status unknown."));
		return;
	}
}

static void
pandora_info_window_status_update_agents (PandoraInfoWindow *window,
					  PandoraState       state)
{
	switch (state) {
	case STATE_BAD:
		gtk_image_set_from_pixbuf (window->priv->image_agents,
					   window->priv->pixbuf_agents_bad);
		gtk_label_set_text (GTK_LABEL (window->priv->label_agents),
				    _("There are agents down."));

		break;
	case STATE_OK:
		gtk_image_set_from_pixbuf (window->priv->image_agents,
					   window->priv->pixbuf_agents_ok);
		gtk_label_set_text (GTK_LABEL (window->priv->label_agents),
				    _("All the agents are running."));
		break;
	default:
		gtk_image_set_from_pixbuf (window->priv->image_agents,
					   window->priv->pixbuf_agents_unknown);
		gtk_label_set_text (GTK_LABEL (window->priv->label_agents),
				    _("Agents status unknown."));
		return;
	}
}

static void
pandora_info_window_status_update_servers (PandoraInfoWindow *window,
					   PandoraState       state)
{
	switch (state) {
	case STATE_BAD:
		gtk_image_set_from_pixbuf (window->priv->image_servers,
					   window->priv->pixbuf_servers_bad);
		gtk_label_set_text (GTK_LABEL (window->priv->label_servers),
				    _("There are servers down."));

		break;
	case STATE_OK:
		gtk_image_set_from_pixbuf (window->priv->image_servers,
					   window->priv->pixbuf_servers_ok);
		gtk_label_set_text (GTK_LABEL (window->priv->label_servers),
				    _("All the servers are running."));
		break;
	default:
		gtk_image_set_from_pixbuf (window->priv->image_servers,
					   window->priv->pixbuf_servers_unknown);
		gtk_label_set_text (GTK_LABEL (window->priv->label_servers),
				    _("Servers status unknown."));
		return;
	}
}

static void
pandora_info_window_status_update_all (PandoraInfoWindow *window)
{
	PandoraState state;
	
	if (window->priv->status == NULL) {
		pandora_info_window_status_update_alerts (window, TRUE);
		pandora_info_window_status_update_agents (window, TRUE);
		pandora_info_window_status_update_servers (window, TRUE);
	} else {
		state = pandora_status_get_alerts (window->priv->status);
		pandora_info_window_status_update_alerts (window, state);
		
		state = pandora_status_get_agents (window->priv->status);
		pandora_info_window_status_update_agents (window, state);
		
		state = pandora_status_get_servers (window->priv->status);
		pandora_info_window_status_update_servers (window, state);
	}
}

static void
pandora_info_window_alerts_changed_cb (GObject *object,
				       gint     data,
				       gpointer user_data)
{
	PandoraState       state = data;
	PandoraInfoWindow *window = PANDORA_INFO_WINDOW (user_data);
	
	pandora_info_window_status_update_alerts (window, state);
}

static void
pandora_info_window_agents_changed_cb (GObject *object,
				       gint     data,
				       gpointer user_data)
{
	PandoraState       state = data;
	PandoraInfoWindow *window = PANDORA_INFO_WINDOW (user_data);
	
	pandora_info_window_status_update_agents (window, state);
}

static void
pandora_info_window_servers_changed_cb (GObject *object,
					gint     data,
					gpointer user_data)
{
	PandoraState       state = data;
	PandoraInfoWindow *window = PANDORA_INFO_WINDOW (user_data);
	
	pandora_info_window_status_update_servers (window, state);
}
