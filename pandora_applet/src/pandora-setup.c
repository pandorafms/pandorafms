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

#include "pandora-setup.h"

#include <libxml/tree.h>
#include <libxml/encoding.h>
#include <libxml/xmlwriter.h>

enum {
        PROP_0,
        PROP_HOST,
        PROP_USERNAME,
        PROP_PASSWORD,
        PROP_DBNAME
};

struct _PandoraSetupPrivate {
	gchar *config_file;
	
	gchar *host;
	gchar *username;
	gchar *password;
	gchar *dbname;
};

#define PANDORA_SETUP_GET_PRIVATE(object) \
        (G_TYPE_INSTANCE_GET_PRIVATE ((object), PANDORA_TYPE_SETUP, \
				      PandoraSetupPrivate))

static GObjectClass *parent_class = NULL;

static void pandora_setup_read_config_file (PandoraSetup *setup, gchar *file);
static void pandora_setup_init             (PandoraSetup      *pandora_setup);
static void pandora_setup_class_init       (PandoraSetupClass *klass);
static void pandora_setup_finalize         (GObject        *object);
static void pandora_setup_get_property     (GObject        *object,
					    guint           prop_id,
					    GValue         *value,
					    GParamSpec     *pspec);
static void pandora_setup_set_property     (GObject        *object,
					    guint           prop_id,
					    const GValue   *value,
					    GParamSpec     *pspec);

GType
pandora_setup_get_type (void)
{
        static GType type = 0;

        if (!type) {
                static const GTypeInfo info = {
                        sizeof (PandoraSetupClass),
                        (GBaseInitFunc) NULL,
                        (GBaseFinalizeFunc) NULL,
                        (GClassInitFunc) pandora_setup_class_init,
                        NULL,
                        NULL,
                        sizeof (PandoraSetup),
                        0,
                        (GInstanceInitFunc) pandora_setup_init
                };

                type = g_type_register_static (G_TYPE_OBJECT, "PandoraSetup",
                                               &info, 0);
        }

        return type;
}

static void
pandora_setup_init (PandoraSetup *setup)
{
        setup->priv = PANDORA_SETUP_GET_PRIVATE (setup);

        setup->priv->host = NULL;
        setup->priv->username = NULL;
        setup->priv->password = NULL;
	setup->priv->dbname = NULL;
}

static void
pandora_setup_class_init (PandoraSetupClass *klass)
{
        GObjectClass *object_class = G_OBJECT_CLASS (klass);

        parent_class = g_type_class_peek_parent (klass);

        g_type_class_add_private (klass, sizeof (PandoraSetupPrivate));

        object_class->set_property = pandora_setup_set_property;
        object_class->get_property = pandora_setup_get_property;

        g_object_class_install_property (object_class,
                                         PROP_HOST,
                                         g_param_spec_string ("host",
                                                              "Host",
                                                              "Host to connect to",
                                                              NULL,
                                                              G_PARAM_READWRITE));
        g_object_class_install_property (object_class,
                                         PROP_USERNAME,
                                         g_param_spec_string ("username",
                                                              "Username",
                                                              "Username to use in the connection",
                                                              NULL,
                                                              G_PARAM_READWRITE));
	
	g_object_class_install_property (object_class,
                                         PROP_PASSWORD,
                                         g_param_spec_string ("password",
                                                              "Password",
                                                              "Password to use in the connection",
                                                              NULL,
                                                              G_PARAM_READWRITE));

	g_object_class_install_property (object_class,
					 PROP_DBNAME,
                                         g_param_spec_string ("dbname",
                                                              "Database name",
                                                              "Database name to connect to",
                                                              NULL,
                                                              G_PARAM_READWRITE));
	
        object_class->finalize = pandora_setup_finalize;
}

static void
pandora_setup_finalize (GObject *object)
{
        PandoraSetup *setup = PANDORA_SETUP (object);

	/* Make persistent */
	pandora_setup_save_to_disk (setup);
	
	if (setup->priv->config_file) {
		g_free (setup->priv->config_file);
                setup->priv->config_file = NULL;
        }
		
        if (setup->priv->host) {
                g_free (setup->priv->host);
                setup->priv->host = NULL;
        }

        if (setup->priv->username) {
                g_free (setup->priv->username);
                setup->priv->username = NULL;
        }

        if (setup->priv->password) {
                g_free (setup->priv->password);
                setup->priv->password = NULL;
        }

	if (setup->priv->dbname) {
                g_free (setup->priv->dbname);
                setup->priv->dbname = NULL;
        }

        if (G_OBJECT_CLASS (parent_class)->finalize)
                (* G_OBJECT_CLASS (parent_class)->finalize) (object);
}

static void
pandora_setup_set_property (GObject      *object,
			    guint         prop_id,
			    const GValue *value,
			    GParamSpec   *pspec)
{
        PandoraSetup *setup = PANDORA_SETUP (object);
        GDate     *date;

        switch (prop_id) {
        case PROP_HOST:
                g_free (setup->priv->host);
                setup->priv->host = g_value_dup_string (value);

                break;
        case PROP_USERNAME:
                g_free (setup->priv->username);
                setup->priv->username = g_value_dup_string (value);

                break;
        case PROP_PASSWORD:
                g_free (setup->priv->password);
                setup->priv->password = g_value_dup_string (value);

                break;
	case PROP_DBNAME:
                g_free (setup->priv->dbname);
                setup->priv->dbname = g_value_dup_string (value);

                break;
	default:
                G_OBJECT_WARN_INVALID_PROPERTY_ID (object, prop_id, pspec);
        }
}

static void
pandora_setup_get_property (GObject      *object,
			    guint         prop_id,
			    GValue       *value,
			    GParamSpec   *pspec)
{
        PandoraSetup *setup = PANDORA_SETUP (object);

        switch (prop_id) {
        case PROP_HOST:
                g_value_set_string (value, setup->priv->host);

                break;
        case PROP_USERNAME:
                g_value_set_string (value, setup->priv->username);

                break;
	case PROP_PASSWORD:
                g_value_set_string (value, setup->priv->password);

                break;
	case PROP_DBNAME:
                g_value_set_string (value, setup->priv->dbname);

                break;
	default:
                G_OBJECT_WARN_INVALID_PROPERTY_ID (object, prop_id, pspec);
        }
}

gchar *
pandora_setup_get_host (PandoraSetup *setup)
{
	gchar *host = NULL;

        g_return_val_if_fail (PANDORA_IS_SETUP (setup), NULL);

        g_object_get (G_OBJECT (setup), "host", &host, NULL);

        return host;
}

gchar *
pandora_setup_get_username (PandoraSetup *setup)
{
	gchar *username = NULL;

        g_return_val_if_fail (PANDORA_IS_SETUP (setup), NULL);

        g_object_get (G_OBJECT (setup), "username", &username, NULL);

        return username;
}

gchar *
pandora_setup_get_password (PandoraSetup *setup)
{
	gchar *password = NULL;

        g_return_val_if_fail (PANDORA_IS_SETUP (setup), NULL);

        g_object_get (G_OBJECT (setup), "password", &password, NULL);

        return password;
}

gchar *
pandora_setup_get_dbname (PandoraSetup *setup)
{
	gchar *dbname = NULL;

        g_return_val_if_fail (PANDORA_IS_SETUP (setup), NULL);

        g_object_get (G_OBJECT (setup), "dbname", &dbname, NULL);

        return dbname;
}

void
pandora_setup_set_host (PandoraSetup *setup, const gchar *host)
{
	g_return_if_fail (PANDORA_IS_SETUP (setup));
        g_return_if_fail (host != NULL);

        g_object_set (G_OBJECT (setup), "host", host, NULL);
}

void
pandora_setup_set_username (PandoraSetup *setup, const gchar *username)
{
	g_return_if_fail (PANDORA_IS_SETUP (setup));
        g_return_if_fail (username != NULL);

        g_object_set (G_OBJECT (setup), "username", username, NULL);

}

void
pandora_setup_set_password (PandoraSetup *setup, const gchar *password)
{
	g_return_if_fail (PANDORA_IS_SETUP (setup));
        g_return_if_fail (password != NULL);

        g_object_set (G_OBJECT (setup), "password", password, NULL);

}

void
pandora_setup_set_dbname (PandoraSetup *setup, const gchar *dbname)
{
	g_return_if_fail (PANDORA_IS_SETUP (setup));
	g_return_if_fail (dbname != NULL);

        g_object_set (G_OBJECT (setup), "dbname", dbname, NULL);

}

static void
pandora_setup_read_config_file (PandoraSetup *setup, gchar *file)
{
	xmlDocPtr  doc;
        xmlNodePtr root;
	gchar     *host;
	gchar     *username;
	gchar     *password;
	gchar     *dbname;
	
	doc = xmlParseFile (file);

	if (!doc) {
		return;
	}

	root = xmlDocGetRootElement (doc);

        if (!root) {
		xmlFreeDoc (doc);
                return;
        }

	if (g_ascii_strcasecmp ((const gchar*) root->name, "config") == 0) {
		host = (gchar *) xmlGetProp (root, (xmlChar *) "host");
		username = (gchar *) xmlGetProp (root, (xmlChar *) "username");
		password = (gchar *) xmlGetProp (root, (xmlChar *) "password");
		dbname = (gchar *) xmlGetProp (root, (xmlChar *) "dbname");
		
		g_object_set (setup,
			      "host",     host,
			      "username", username,
			      "password", password,
			      "dbname",   dbname,
			      NULL);
		
		g_free (host);
		g_free (username);
		g_free (password);
		g_free (dbname);
        }

	xmlFreeDoc (doc);
}

void
pandora_setup_save_to_disk (PandoraSetup *setup)
{
	xmlTextWriterPtr writer;

	writer = xmlNewTextWriterFilename (setup->priv->config_file, 0);

	xmlTextWriterStartDocument (writer, NULL, NULL, NULL);
        xmlTextWriterStartElement (writer, BAD_CAST "config");
	
	xmlTextWriterWriteAttribute (writer,
				     BAD_CAST "host",
				     BAD_CAST setup->priv->host);
	xmlTextWriterWriteAttribute (writer,
				     BAD_CAST "username",
				     BAD_CAST setup->priv->username);
	xmlTextWriterWriteAttribute (writer,
				     BAD_CAST "password",
				     BAD_CAST setup->priv->password);
	xmlTextWriterWriteAttribute (writer,
				     BAD_CAST "dbname",
				     BAD_CAST setup->priv->dbname);
	
	xmlTextWriterEndElement (writer);
	xmlTextWriterEndDocument (writer);
	xmlFreeTextWriter (writer);
}


PandoraSetup *
pandora_setup_new (gchar *config_file)
{
	PandoraSetup *setup;

        setup = PANDORA_SETUP (g_object_new (PANDORA_TYPE_SETUP, NULL));

	setup->priv->config_file = g_strdup (config_file);
	
	pandora_setup_read_config_file (setup, config_file);
	
        return setup;
}

