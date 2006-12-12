/* 
   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.

   Based on NetworkManager Wireless Applet 
   GNOME Wireless Applet Authors:
    * Eskil Heyn Olsen <eskil@eskil.dk>
    * Bastien Nocera <hadess@hadess.net> (Gnome2 port)

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

#include <stdlib.h>
#include <glib/gi18n-lib.h>

#include "pandora-applet.h"
#include "pandora-info-window.h"
#include "pandora-status-checker.h"
#include "pandora-status.h"
#include "pandora-setup-dialog.h"
#include "pandora-setup.h"

struct _PandoraAppletPrivate {
        GtkImage             *image;
	GdkPixbuf            *icon_good;
	GdkPixbuf            *icon_bad;
	GdkPixbuf            *icon_unknown;
	GtkWidget            *event_box;
	GtkWidget            *context_menu;

	gchar                *userdir;
	gchar                *userfile;
	
	PandoraInfoWindow    *info_window;
	PandoraSetupDialog   *setup_dialog;
	PandoraSetup         *setup;
	PandoraStatusChecker *checker;
	PandoraStatus        *status;
};

#define PANDORA_APPLET_GET_PRIVATE(object) \
        (G_TYPE_INSTANCE_GET_PRIVATE ((object), PANDORA_TYPE_APPLET, PandoraAppletPrivate))

static gboolean   pandora_applet_create_setup_file             (PandoraApplet *applet);
static void       pandora_applet_create_user_dir               (PandoraApplet *applet);
static void       pandora_applet_set_image_from_pixbuf         (PandoraApplet *applet,
								GdkPixbuf *icon);
static void       pandora_applet_set_image_from_stock          (PandoraApplet *applet,
							        gchar *stock_id);
static gboolean   pandora_applet_toplevel_menu_button_press_cb (GtkWidget *widget,
								GdkEventButton *event,
								gpointer data);
static void       pandora_applet_changed_status_cb             (GObject *object,
								gpointer *data);
static GtkWidget *pandora_applet_context_menu_create           (PandoraApplet *applet);

static GObject   *pandora_applet_constructor                   (GType type, guint n_props,
								GObjectConstructParam *props);
static GtkWidget *pandora_applet_get_instance                  (PandoraApplet *applet);
static void       pandora_applet_destroy                       (PandoraApplet *applet);
static GType      pandora_applet_get_type                      (void); /* for G_DEFINE_TYPE */

G_DEFINE_TYPE (PandoraApplet, pandora_applet, EGG_TYPE_TRAY_ICON)

static void
pandora_applet_init (PandoraApplet *applet)
{
	applet->priv = PANDORA_APPLET_GET_PRIVATE (applet);

	applet->priv->icon_good = load_icon_from_disk ("pandora-applet-good.png");
	applet->priv->icon_bad = load_icon_from_disk ("pandora-applet-bad.png");
	applet->priv->icon_unknown = load_icon_from_disk ("pandora-applet-unknown.png");

	applet->priv->userdir = g_build_filename (g_get_home_dir (), ".pandora", NULL);
	applet->priv->userfile = g_build_filename (applet->priv->userdir, "config.xml",
						   NULL);
	pandora_applet_create_user_dir (applet);

	g_signal_connect (applet, "destroy", G_CALLBACK (pandora_applet_destroy), NULL);

        /* Event box is the main applet widget */
        applet->priv->event_box = gtk_event_box_new ();
        gtk_container_set_border_width (GTK_CONTAINER (applet->priv->event_box),
					0);

        applet->priv->image = GTK_IMAGE (gtk_image_new ());
        gtk_container_add (GTK_CONTAINER (applet->priv->event_box),
			   GTK_WIDGET (applet->priv->image));
        gtk_container_add (GTK_CONTAINER (applet), applet->priv->event_box);

	g_signal_connect (applet->priv->event_box, "button_press_event",
			  G_CALLBACK (pandora_applet_toplevel_menu_button_press_cb),
			  applet);

	pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_unknown);
	
	applet->priv->context_menu = pandora_applet_context_menu_create (applet);

	/* Init setup */
	applet->priv->setup = pandora_setup_new (applet->priv->userfile);
	
	/* Init windows and dialogs */
	applet->priv->info_window = PANDORA_INFO_WINDOW (pandora_info_window_new ());

	applet->priv->setup_dialog = PANDORA_SETUP_DIALOG (pandora_setup_dialog_new ());
	pandora_setup_dialog_set_setup (applet->priv->setup_dialog,
					applet->priv->setup);

	/* Init status and checker */
	applet->priv->status = PANDORA_STATUS (pandora_status_new ());

	pandora_info_window_set_status (applet->priv->info_window,
					applet->priv->status);
	
	applet->priv->checker = pandora_status_checker_new (applet->priv->setup,
							    applet->priv->status);

	gtk_widget_show_all (GTK_WIDGET (applet));
	
	g_signal_connect (G_OBJECT (applet->priv->status), "changed",
			  G_CALLBACK (pandora_applet_changed_status_cb),
			  (gpointer) applet);

	pandora_status_checker_run (applet->priv->checker);
}

static void
pandora_applet_class_init (PandoraAppletClass *klass)
{
	GObjectClass *object_class;

	g_type_class_add_private (klass, sizeof (PandoraAppletPrivate));
	
	object_class = G_OBJECT_CLASS (klass);
	object_class->constructor = pandora_applet_constructor;
}

static GObject *
pandora_applet_constructor (GType type, guint n_props,
			    GObjectConstructParam *props)
{
	GObject            *obj;
	PandoraApplet      *applet;
	PandoraAppletClass *klass;

	klass = PANDORA_APPLET_CLASS (g_type_class_peek (type));
	obj = G_OBJECT_CLASS (pandora_applet_parent_class)->constructor (type,
									 n_props,
									 props);
	applet = PANDORA_APPLET (obj);

	return obj;
}

static GtkWidget *
pandora_applet_get_instance (PandoraApplet *applet)
{
	gtk_widget_hide (GTK_WIDGET (applet));

	return GTK_WIDGET (applet);
}

static void
pandora_applet_destroy (PandoraApplet *applet)
{
	gtk_widget_hide (GTK_WIDGET (applet));

	gtk_widget_destroy (GTK_WIDGET (applet->priv->info_window));
	gtk_widget_destroy (GTK_WIDGET (applet->priv->setup_dialog));
	
	pandora_status_checker_stop (applet->priv->checker);
	
	exit_app ();
}

PandoraApplet *
pandora_applet_new ()
{
	return g_object_new (PANDORA_TYPE_APPLET, "title", "Pandora", NULL);
}

static void
pandora_applet_quit_cb (GtkMenuItem *mi, PandoraApplet *applet)
{
	gtk_widget_hide (GTK_WIDGET (applet));
	gtk_widget_destroy (GTK_WIDGET (applet));
}

static void
pandora_applet_about_cb (GtkMenuItem *mi, PandoraApplet *applet)
{
	GdkPixbuf          *pixbuf;
        gchar              *pixbuf_path;
        static const gchar *authors[] =
        {
                "Artica Soluciones Tecnológicas:\n",
                "Esteban Sánchez <estebans@artica.es>",
                NULL
        };
	static const gchar *artists[] =
        {
		"Esteban Sánchez <estebans@artica.es>\n"
                "Icons from Tango Desktop Project <http://tango.freedesktop.org/>",
                NULL
        };


	
	pixbuf_path = g_build_filename (PIXMAPS_DIR, "pandora.png", NULL);
        pixbuf = gdk_pixbuf_new_from_file (pixbuf_path, NULL);
        g_free (pixbuf_path);

        gtk_show_about_dialog (NULL,
                               "name", _("Pandora Enterprise monitor applet"),
                               "version", VERSION,
                               "copyright", _("Copyright \xc2\xa9 2006 Artica ST.\n"),
                               "comments", _("Notification area applet for monitoring your Pandora system."),
                               "website", "http://pandora.sourceforge.net/",
                               "authors", authors,
			       "artists", artists,
                               "translator-credits", _("translator-credits"),
                               "logo", pixbuf,
                               NULL);
	
	if (pixbuf) {
                g_object_unref (pixbuf);
	}

}

static void
pandora_applet_show_info_cb (GtkMenuItem *mi, PandoraApplet *applet)
{
	gtk_window_present (GTK_WINDOW (applet->priv->info_window));
}

static void
pandora_applet_show_setup_cb (GtkMenuItem *mi, PandoraApplet *applet)
{
	gint result;
	
	gtk_widget_show_all (GTK_WIDGET (applet->priv->setup_dialog));
	
	result = gtk_dialog_run (GTK_DIALOG (applet->priv->setup_dialog));

	gtk_widget_hide (GTK_WIDGET (applet->priv->setup_dialog));

	while (gtk_events_pending ())
		gtk_main_iteration ();
	
	switch (result) {
	case GTK_RESPONSE_ACCEPT:
		pandora_setup_dialog_apply_changes (applet->priv->setup_dialog);
		pandora_setup_save_to_disk (applet->priv->setup);
				
		pandora_status_checker_stop (applet->priv->checker);
		pandora_status_checker_run (applet->priv->checker);
		break;
	default:
		break;
	}
}

static void
pandora_applet_changed_status_cb (GObject *object,
				  gpointer *data)
{
	PandoraApplet *applet = PANDORA_APPLET (data);
	
	switch (pandora_status_get_alerts (PANDORA_STATUS (object))) {
	case STATE_BAD:
		pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_bad);
		return;
		break;
	case STATE_OK:
		break;
	default:
		pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_unknown);
		return;
	}

	switch (pandora_status_get_servers (PANDORA_STATUS (object))) {
	case STATE_BAD:
		pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_bad);
		return;
		break;
	case STATE_OK:
		break;
	default:
		pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_unknown);
		return;
	}

	switch (pandora_status_get_agents (PANDORA_STATUS (object))) {
	case STATE_BAD:
		pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_bad);
		return;
		break;
	case STATE_OK:
		break;
	default:
		pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_unknown);
		return;
	}
	
	pandora_applet_set_image_from_pixbuf (applet, applet->priv->icon_good);
}

static GtkWidget *
pandora_applet_context_menu_create (PandoraApplet *applet)
{
        GtkWidget *menu;
        GtkWidget *menu_item;
        GtkWidget *image;

        g_return_val_if_fail (applet != NULL, NULL);

        menu = gtk_menu_new ();

        /* Show status item */
        menu_item = gtk_image_menu_item_new_with_mnemonic (_("_Show main window"));
        g_signal_connect (G_OBJECT (menu_item), "activate",
			  G_CALLBACK (pandora_applet_show_info_cb), applet);
        image = gtk_image_new_from_stock (GTK_STOCK_INFO, GTK_ICON_SIZE_MENU);
        gtk_image_menu_item_set_image (GTK_IMAGE_MENU_ITEM (menu_item),
				       image);
        gtk_menu_shell_append (GTK_MENU_SHELL (menu), menu_item);

        /* Preferences item */
        menu_item = gtk_image_menu_item_new_with_mnemonic (_("_Preferences"));
        g_signal_connect (G_OBJECT (menu_item), "activate",
			  G_CALLBACK (pandora_applet_show_setup_cb), applet);
        image = gtk_image_new_from_stock (GTK_STOCK_PREFERENCES, GTK_ICON_SIZE_MENU);
        gtk_image_menu_item_set_image (GTK_IMAGE_MENU_ITEM (menu_item),
				       image);
        gtk_menu_shell_append (GTK_MENU_SHELL (menu), menu_item);
	
        /* Separator */
        menu_item = gtk_separator_menu_item_new ();
        gtk_menu_shell_append (GTK_MENU_SHELL (menu), menu_item);
        gtk_widget_show (menu_item);
	
        /* About item */
        menu_item = gtk_image_menu_item_new_with_mnemonic (_("_About"));
        g_signal_connect (G_OBJECT (menu_item), "activate",
			  G_CALLBACK (pandora_applet_about_cb), applet);
        image = gtk_image_new_from_stock (GTK_STOCK_ABOUT, GTK_ICON_SIZE_MENU);
        gtk_image_menu_item_set_image (GTK_IMAGE_MENU_ITEM (menu_item), image);
        gtk_menu_shell_append (GTK_MENU_SHELL (menu), menu_item);

	/* Quit item */
        menu_item = gtk_image_menu_item_new_with_mnemonic (_("_Quit"));
        g_signal_connect (G_OBJECT (menu_item), "activate",
			  G_CALLBACK (pandora_applet_quit_cb), applet);
        image = gtk_image_new_from_stock (GTK_STOCK_QUIT, GTK_ICON_SIZE_MENU);
        gtk_image_menu_item_set_image (GTK_IMAGE_MENU_ITEM (menu_item), image);
        gtk_menu_shell_append (GTK_MENU_SHELL (menu), menu_item);

        gtk_widget_show_all (menu);

        return menu;
}

static void
pandora_applet_menu_position_func (GtkMenu *menu G_GNUC_UNUSED,
				   int *x, int *y, gboolean *push_in,
				   gpointer user_data)
{
        int screen_w, screen_h, button_x, button_y, panel_w, panel_h;
        GtkRequisition requisition;
        GdkScreen *screen;
        PandoraApplet *applet = (PandoraApplet *)user_data;

        screen = gtk_widget_get_screen (applet->priv->event_box);
        screen_w = gdk_screen_get_width (screen);
        screen_h = gdk_screen_get_height (screen);

        gdk_window_get_origin (applet->priv->event_box->window, &button_x, &button_y);
        gtk_window_get_size (GTK_WINDOW (gtk_widget_get_toplevel (applet->priv->event_box)),
			     &panel_w, &panel_h);

        *x = button_x;

        /* Check to see if we would be placing the menu off of the end of the screen. */
        gtk_widget_size_request (GTK_WIDGET (menu), &requisition);
        if (button_y + panel_h + requisition.height >= screen_h)
                *y = button_y - requisition.height;
        else
                *y = button_y + panel_h;

        *push_in = TRUE;
}

static gboolean
pandora_applet_toplevel_menu_button_press_cb (GtkWidget *widget,
					      GdkEventButton *event,
					      gpointer data)
{

	PandoraApplet *applet;

	applet = PANDORA_APPLET (data);

        g_return_val_if_fail (applet != NULL, FALSE);
	g_return_val_if_fail (PANDORA_IS_APPLET (applet), FALSE);

        switch (event->button) {
                case 3:
                        gtk_menu_popup (GTK_MENU (applet->priv->context_menu),
					NULL, NULL,
					pandora_applet_menu_position_func,
					applet, event->button, event->time);
                        return TRUE;
                default:
                        g_signal_stop_emission_by_name (widget, "button_press_event");
                        return FALSE;
        }

        return FALSE;
}

static void
pandora_applet_setup_widgets (PandoraApplet *applet)
{
}

static void
pandora_applet_set_image_from_stock (PandoraApplet *applet,
				     gchar *stock_id)
{
	gtk_image_set_from_stock (GTK_IMAGE (applet->priv->image),
				  stock_id, GTK_ICON_SIZE_MENU);
}

static void
pandora_applet_set_image_from_pixbuf (PandoraApplet *applet, GdkPixbuf *icon)
{
        g_return_if_fail (PANDORA_IS_APPLET (applet));
        g_return_if_fail (icon != NULL);

        gtk_image_set_from_pixbuf (GTK_IMAGE (applet->priv->image), icon);
}

GdkPixbuf *
load_icon_from_disk (gchar *icon_name)
{
	GError    *err = NULL;
	gchar     *icon_path;
	GdkPixbuf *pixbuf;

	icon_path = g_build_filename (PIXMAPS_DIR, icon_name, NULL);
	pixbuf = gdk_pixbuf_new_from_file (icon_path, &err);
        g_free (icon_path);
	
	if (pixbuf == NULL) {
		g_warning ("Icon %s missing: %s", icon_name, err->message);
		g_error_free (err);
		return NULL;
	}

	return pixbuf;
}

static gboolean
pandora_applet_create_setup_file (PandoraApplet *applet)
{
	const gchar *contents = "<?xml version=\"1.0\"?>\n<config>\n</config>\n";

#if GTK_CHECK_VERSION(2,8,0)
        return g_file_set_contents (applet->priv->userfile, contents, -1, NULL);
#else
        gint fd;

        if ((fd = open (applet->priv->userfile, O_CREAT | O_WRONLY, 0644)) < 0) {
                return FALSE;
        }

        if (write (fd, contents, strlen (contents)) < 0) {
                close (fd);
                return FALSE;
        }

        if (close (fd) < 0) {
                return FALSE;
        }

        return TRUE;
#endif
}

static void
pandora_applet_create_user_dir (PandoraApplet *applet)
{
        if (!g_file_test (applet->priv->userdir, G_FILE_TEST_IS_DIR)) {
                if (g_mkdir (applet->priv->userdir, 0755) != 0) {
                        g_error ("Cannot create user's directory");
                }
        }

        if (!g_file_test (applet->priv->userfile, G_FILE_TEST_IS_REGULAR)) {
                if (!pandora_applet_create_setup_file (applet)) {
                        g_error ("Cannot create user's configuration file");
                }
        }
}
