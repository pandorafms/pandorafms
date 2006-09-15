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
#include <mysql.h>

#include "pandora-status-checker.h"

struct _PandoraStatusCheckerPrivate {
	GThread       *thread;
	GMutex        *state_mutex;
	
	gint           state;
	
	PandoraStatus *status;
	PandoraSetup  *setup;
	
	MYSQL         *connection;
};

#define PANDORA_STATUS_CHECKER_GET_PRIVATE(object) \
        (G_TYPE_INSTANCE_GET_PRIVATE ((object), PANDORA_STATUS_CHECKER_TYPE, \
				      PandoraStatusCheckerPrivate))

static gboolean pandora_status_checker_connect      (PandoraStatusChecker *checker);
static gboolean pandora_status_checker_disconnect   (PandoraStatusChecker *checker);

static PandoraState pandora_status_checker_check_alerts  (PandoraStatusChecker *checker);
static PandoraState pandora_status_checker_check_servers (PandoraStatusChecker *checker);
static PandoraState pandora_status_checker_check_agents  (PandoraStatusChecker *checker);

static void     pandora_status_checker_init         (PandoraStatusChecker      *checker);
static void     pandora_status_checker_class_init   (PandoraStatusCheckerClass *klass);
static void     pandora_status_checker_finalize     (GObject                   *object);

static gpointer pandora_status_checker_run_thread   (gpointer data);

GType
pandora_status_checker_get_type (void)
{
        static GType type = 0;

        if (!type) {
                static const GTypeInfo info = {
                        sizeof (PandoraStatusCheckerClass),
                        (GBaseInitFunc) NULL,
                        (GBaseFinalizeFunc) NULL,
                        (GClassInitFunc) pandora_status_checker_class_init,
                        NULL,
                        NULL,
                        sizeof (PandoraStatusChecker),
                        0,
                        (GInstanceInitFunc) pandora_status_checker_init
                };

                type = g_type_register_static (G_TYPE_OBJECT, "PandoraStatusChecker",
                                               &info, 0);
        }

        return type;
}

static void
pandora_status_checker_init (PandoraStatusChecker *checker)
{
	checker->priv = PANDORA_STATUS_CHECKER_GET_PRIVATE (checker);

	checker->priv->connection = NULL;
	
	checker->priv->state_mutex = g_mutex_new ();

	checker->priv->state = CHECKER_STATE_READY;
}


static void
pandora_status_checker_class_init (PandoraStatusCheckerClass *klass)
{
        GObjectClass *object_class;

        g_type_class_add_private (klass, sizeof (PandoraStatusCheckerPrivate));

	object_class = G_OBJECT_CLASS (klass);
	object_class->finalize = pandora_status_checker_finalize;
}

static void
pandora_status_checker_finalize (GObject *object)
{
        PandoraStatusChecker *checker;
	
	checker = PANDORA_STATUS_CHECKER (object);

	switch (checker->priv->state) {
        case CHECKER_STATE_RUNNING:
                pandora_status_checker_stop (checker);
                /* Do not break! */
        case CHECKER_STATE_STOPPING:
                g_thread_join (checker->priv->thread);
                break;
        default:
                break;
        }

	if (checker->priv->status) {
		g_object_unref (checker->priv->status);
		checker->priv->status = NULL;
	}


	if (checker->priv->state_mutex) {
		g_mutex_free (checker->priv->state_mutex);
		checker->priv->state_mutex = NULL;
	}
	
	if (checker->priv->setup) {
		g_object_unref (checker->priv->setup);
		checker->priv->setup = NULL;
	}

	if (checker->priv->connection) {
		mysql_close (checker->priv->connection);
		checker->priv->connection = NULL;
	}
}

PandoraStatusChecker *
pandora_status_checker_new (PandoraSetup *setup, PandoraStatus *status)
{
        PandoraStatusChecker *checker;

        checker = PANDORA_STATUS_CHECKER (g_object_new (PANDORA_STATUS_CHECKER_TYPE,
							NULL));
	checker->priv->setup = setup;
	g_object_ref (setup);

	checker->priv->status = status;
	g_object_ref (status);
	
        return checker;
}

static gboolean
pandora_status_checker_connect (PandoraStatusChecker *checker)
{
	MYSQL        *connection;
	PandoraSetup *setup;
	gchar        *host, *username, *password, *dbname;
	gboolean      retval = TRUE;

	connection = checker->priv->connection;
	setup      = checker->priv->setup;
	
	if (connection) {
		mysql_close (connection);
	}

	connection = mysql_init (NULL);

        if (connection == NULL) {
		return FALSE;
        }

	g_object_get (G_OBJECT (setup), "host", &host, "username", &username,
		      "password", &password, "dbname", &dbname, NULL);

	if (mysql_real_connect (connection, host, username,
				password, dbname, 3306, NULL, 0) == NULL)
        {
		
                g_print ("mysql_real_connect() failed. %s\n",
			 mysql_error (connection));
                mysql_close (connection);

		retval = FALSE;
	}
	
	checker->priv->connection = connection;
	return retval;
}

static gboolean
pandora_status_checker_disconnect (PandoraStatusChecker *checker)
{
	if (checker->priv->connection) {
		mysql_close (checker->priv->connection);
	}

	checker->priv->connection = NULL;
}

static PandoraState
pandora_status_checker_check_agents (PandoraStatusChecker *checker)
{
	MYSQL             *connection = NULL;
	MYSQL_RES         *result;
	MYSQL_ROW          row;
	const gchar const *query_time = "SELECT * FROM tagente "
		                        "WHERE UNIX_TIMESTAMP(ultimo_contacto_remoto)"
		                        "- intervalo > UNIX_TIMESTAMP(NOW())" ;
	const gchar const *query_status = "SELECT * FROM tagente_estado "
 		                          "LEFT JOIN tagente "
		                          "ON tagente_estado.id_agente = tagente.id_agente "
		                          "WHERE estado != 100 and datos = 0.0";
	connection = checker->priv->connection;
	g_return_val_if_fail (connection != NULL, FALSE);

	if (mysql_query (connection, query_time) != 0) {
		return STATE_UNKNOWN;
	}

	result = mysql_store_result (connection);

	if (mysql_num_rows (result) > 0) {
		return STATE_BAD;
	}

	mysql_free_result (result);

	if (mysql_query (connection, query_status) != 0) {
		return STATE_UNKNOWN;
	}

	result = mysql_store_result (connection);

	if (mysql_num_rows (result) > 0) {
		return STATE_BAD;
	}

	mysql_free_result (result);
	return STATE_OK;
}

static PandoraState
pandora_status_checker_check_servers (PandoraStatusChecker *checker)
{
	MYSQL             *connection = NULL;
	MYSQL_RES         *result;
	MYSQL_ROW          row;
	const gchar const *query = "SELECT * FROM tserver "
		                   "WHERE status = 0";
	
	connection = checker->priv->connection;
	g_return_val_if_fail (connection != NULL, FALSE);

	if (mysql_query (connection, query) != 0) {
		return STATE_UNKNOWN;
	}

	result = mysql_store_result (connection);

	if (mysql_num_rows (result) > 0)
		return STATE_BAD;

	mysql_free_result (result);
	return STATE_OK;
}

static PandoraState
pandora_status_checker_check_alerts (PandoraStatusChecker *checker)
{
	MYSQL             *connection = NULL;
	MYSQL_RES         *result1 = NULL, *result2 = NULL, *result3 = NULL;
	MYSQL_ROW          row;
	const gchar const *query_all_agents = "SELECT * FROM tagente "
		                              "WHERE disabled = 0";
	const gchar const *query_agent_state = "SELECT * FROM tagente_estado "
		                               "WHERE id_agente = %s";
	const gchar const *query_agent_alert = "SELECT * FROM talerta_agente_modulo "
		                               "WHERE id_agente_modulo = %s "
		                               "AND times_fired > 0";
	gchar             *query;
	
	connection = checker->priv->connection;
	g_return_val_if_fail (connection != NULL, FALSE);

	if (mysql_query (connection, query_all_agents) != 0) {
		return STATE_UNKNOWN;
	}

	result1 = mysql_store_result (connection);
	if (result1 == NULL)
		return STATE_UNKNOWN;

	if (mysql_num_rows (result1) == 0)
		return STATE_UNKNOWN;

	while (row = mysql_fetch_row (result1)) {
		query = g_strdup_printf (query_agent_state, row[0]);

		if (mysql_query (connection, query) == 0) {
			result2 = mysql_store_result (connection);
			
			if (mysql_num_rows (result2) > 0) {

				while (row = mysql_fetch_row (result2)) {
					
					g_free (query);
					query = g_strdup_printf (query_agent_alert,
								 row[1]);

					if (mysql_query (connection, query) == 0) {
						result3 = mysql_store_result (connection);
						
						if (mysql_num_rows (result3) > 0) {
							mysql_free_result (result1);
							mysql_free_result (result2);
							mysql_free_result (result3);
							
							g_free (query);
							
							return STATE_BAD;
						}
						
						mysql_free_result (result3);
					}
				}
				
				mysql_free_result (result2);
			}
		}
		
		g_free (query);
	}
	
	if (mysql_num_rows (result1) > 0)
		mysql_free_result (result1);
	
	return STATE_OK;
}

static gpointer
pandora_status_checker_run_thread (gpointer data)
{
	PandoraStatusChecker *checker = PANDORA_STATUS_CHECKER (data);
	PandoraState          alerts, servers, agents;

	g_mutex_lock (checker->priv->state_mutex);
        checker->priv->state = CHECKER_STATE_RUNNING;

	while (checker->priv->state == CHECKER_STATE_RUNNING) {
		g_mutex_unlock (checker->priv->state_mutex);

		if (!pandora_status_checker_connect (checker)) {
			pandora_status_checker_stop (checker);
			return NULL;
		}

		alerts = pandora_status_checker_check_alerts (checker);
		servers = pandora_status_checker_check_servers (checker);
		agents = pandora_status_checker_check_agents (checker);
		
		pandora_status_set_alerts (checker->priv->status, alerts);
		pandora_status_set_servers (checker->priv->status, servers);
		pandora_status_set_agents (checker->priv->status, agents);
		
		pandora_status_checker_disconnect (checker);

		g_usleep (G_USEC_PER_SEC * 10);
		
		g_mutex_lock (checker->priv->state_mutex);
	}

	g_mutex_unlock (checker->priv->state_mutex);

	return NULL;
}

void
pandora_status_checker_run (PandoraStatusChecker *checker)
{
	checker->priv->thread = g_thread_create (pandora_status_checker_run_thread,
						 (gpointer) checker, TRUE, NULL);
}

void
pandora_status_checker_stop (PandoraStatusChecker *checker)
{
	g_mutex_lock (checker->priv->state_mutex);
	checker->priv->state = CHECKER_STATE_RUNNING;
	g_mutex_unlock (checker->priv->state_mutex);
}
