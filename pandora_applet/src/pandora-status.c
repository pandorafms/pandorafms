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

#include "pandora-status.h"

enum {
	NONE,
	INCIDENCE,
	CHANGED_ALERTS,
	CHANGED_AGENTS,
	CHANGED_SERVERS,
	N_SIGNALS
};

struct _PandoraStatusPrivate {
	PandoraState alerts;
	PandoraState agents;
	PandoraState servers;

	GMutex  *mutex;
};

static guint pandora_status_signals[N_SIGNALS];

#define PANDORA_STATUS_GET_PRIVATE(object) \
        (G_TYPE_INSTANCE_GET_PRIVATE ((object), PANDORA_STATUS_TYPE, \
				      PandoraStatusPrivate))

static void pandora_status_init       (PandoraStatus      *status);
static void pandora_status_class_init (PandoraStatusClass *klass);
static void pandora_status_finalize   (GObject            *object);

GType
pandora_status_get_type (void)
{
        static GType type = 0;

        if (!type) {
                static const GTypeInfo info = {
                        sizeof (PandoraStatusClass),
                        (GBaseInitFunc) NULL,
                        (GBaseFinalizeFunc) NULL,
                        (GClassInitFunc) pandora_status_class_init,
                        NULL,
                        NULL,
                        sizeof (PandoraStatus),
                        0,
                        (GInstanceInitFunc) pandora_status_init
                };

                type = g_type_register_static (G_TYPE_OBJECT, "PandoraStatus",
                                               &info, 0);
        }

        return type;
}

static void
pandora_status_init (PandoraStatus *status)
{
	status->priv = PANDORA_STATUS_GET_PRIVATE (status);
	status->priv->alerts  = FALSE;
	status->priv->agents  = FALSE;
	status->priv->servers = FALSE;

	status->priv->mutex = g_mutex_new ();
}

static void
pandora_status_class_init (PandoraStatusClass *klass)
{
        GObjectClass *object_class;

        g_type_class_add_private (klass, sizeof (PandoraStatusPrivate));

	object_class = G_OBJECT_CLASS (klass);
	object_class->finalize = pandora_status_finalize;

	pandora_status_signals[INCIDENCE] =
                g_signal_new ("incidence",
                              G_TYPE_FROM_CLASS (object_class),
                              G_SIGNAL_RUN_LAST | G_SIGNAL_ACTION,
                              G_STRUCT_OFFSET (PandoraStatusClass, changed),
                              NULL, NULL,
                              g_cclosure_marshal_VOID__INT,
                              G_TYPE_NONE, 1, G_TYPE_INT);
	
	pandora_status_signals[CHANGED_ALERTS] =
                g_signal_new ("changed_alerts",
                              G_TYPE_FROM_CLASS (object_class),
                              G_SIGNAL_RUN_LAST | G_SIGNAL_ACTION,
                              G_STRUCT_OFFSET (PandoraStatusClass, changed),
                              NULL, NULL,
                              g_cclosure_marshal_VOID__INT,
                              G_TYPE_NONE, 1, G_TYPE_INT);

	pandora_status_signals[CHANGED_AGENTS] =
                g_signal_new ("changed_agents",
                              G_TYPE_FROM_CLASS (object_class),
                              G_SIGNAL_RUN_LAST | G_SIGNAL_ACTION,
                              G_STRUCT_OFFSET (PandoraStatusClass, changed),
                              NULL, NULL,
                              g_cclosure_marshal_VOID__INT,
                              G_TYPE_NONE, 1, G_TYPE_INT);

	pandora_status_signals[CHANGED_SERVERS] =
                g_signal_new ("changed_servers",
                              G_TYPE_FROM_CLASS (object_class),
                              G_SIGNAL_RUN_LAST | G_SIGNAL_ACTION,
                              G_STRUCT_OFFSET (PandoraStatusClass, changed),
                              NULL, NULL,
                              g_cclosure_marshal_VOID__INT,
                              G_TYPE_NONE, 1, G_TYPE_INT);
}

static void
pandora_status_finalize (GObject *object)
{
	PandoraStatus *status;

	status = PANDORA_STATUS (object);
	
	if (status->priv->mutex) {
		g_mutex_free (status->priv->mutex);
		status->priv->mutex = NULL;
	}
}

PandoraStatus *
pandora_status_new (void)
{
        PandoraStatus *status;

        status = PANDORA_STATUS (g_object_new (PANDORA_STATUS_TYPE,
					       NULL));

        return status;
}

void
pandora_status_set_all (PandoraStatus *status,
			PandoraState   alerts,
			PandoraState   agents,
			PandoraState   servers)
{
	PandoraState incidence = STATE_INVALID;
	
	g_return_if_fail (PANDORA_IS_STATUS (status));

	g_mutex_lock (status->priv->mutex);

	if (status->priv->alerts != alerts) {
		status->priv->alerts = alerts;
					
		g_signal_emit (status, pandora_status_signals[CHANGED_ALERTS],
			       0, alerts);
		
		incidence = alerts;
	}

	if (status->priv->agents != agents) {
		status->priv->agents = agents;
					
		g_signal_emit (status, pandora_status_signals[CHANGED_AGENTS],
			       0, agents);

		/* Change value if it was invalid or OK, so
		 * "incidence" signal will be emmited only if there
		 * are any bad value or all are ok. */
		if (incidence == STATE_INVALID || incidence == STATE_OK)
			incidence = agents;
	}

	if (status->priv->servers != servers) {
		status->priv->servers = servers;
					
		g_signal_emit (status, pandora_status_signals[CHANGED_SERVERS],
			       0, servers);
		
		if (incidence == STATE_INVALID || incidence == STATE_OK)
			incidence = agents;
	}
	
	/* Emit incidence_any signal if necessary */
	if (incidence != STATE_INVALID)
		g_signal_emit (status, pandora_status_signals[INCIDENCE],
			       0, incidence);
	
	g_mutex_unlock (status->priv->mutex);
}

PandoraState
pandora_status_get_alerts (PandoraStatus *status)
{
	PandoraState value;

	g_mutex_lock (status->priv->mutex);
	value = status->priv->alerts;
	g_mutex_unlock (status->priv->mutex);
	
	return value;
}

PandoraState
pandora_status_get_agents (PandoraStatus *status)
{
	PandoraState value;

	g_mutex_lock (status->priv->mutex);
	value = status->priv->agents;
	g_mutex_unlock (status->priv->mutex);

	return value;
}

PandoraState
pandora_status_get_servers (PandoraStatus *status)
{
	PandoraState value;

	g_mutex_lock (status->priv->mutex);
	value = status->priv->servers;
	g_mutex_unlock (status->priv->mutex);
	
	return value;
}
