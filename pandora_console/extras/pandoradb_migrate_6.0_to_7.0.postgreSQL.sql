-- -----------------------------------------------------
-- Table "tlocal_component"
-- -----------------------------------------------------
-- tlocal_component is a repository of local modules for
-- physical agents on Windows / Unix physical agents
CREATE TABLE "tlocal_component" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" TEXT NOT NULL,
	"data" TEXT NOT NULL,
	"description" varchar(1024) default NULL,
	"id_os" INTEGER NOT NULL default 0,
	"os_version" varchar(100) default '',
	"id_network_component_group"  INTEGER NOT NULL default 0 REFERENCES tnetwork_component_group("id_sg") ON DELETE CASCADE ON UPDATE CASCADE,
	"type" SMALLINT NOT NULL default 6,
	"max" INTEGER NOT NULL default 0,
	"min" INTEGER NOT NULL default 0,
	"module_interval" INTEGER NOT NULL default 0,
	"id_module_group" INTEGER NOT NULL default 0,
	"history_data" INTEGER default '1',
	"min_warning" DOUBLE PRECISION default NULL,
	"max_warning" DOUBLE PRECISION default NULL,
	"str_warning" TEXT default '',
	"min_critical" DOUBLE PRECISION default NULL,
	"max_critical" DOUBLE PRECISION default NULL,
	"str_critical" TEXT default '',
	"min_ff_event" INTEGER NOT NULL default 0,
	"post_process" DOUBLE PRECISION default NULL,
	"unit" TEXT default '',
	"wizard_level" type_tlocal_component_wizard_level default 'nowizard',
	"macros" TEXT default '',
	"critical_instructions" TEXT default '',
	"warning_instructions" TEXT default '',
	"unknown_instructions" TEXT default '',
	"critical_inverse" SMALLINT default 0 NOT NULL,
	"warning_inverse" SMALLINT default 0 NOT NULL,
	"id_category" INTEGER NOT NULL default 0,
	"tags" TEXT default '',
	"disabled_types_event" TEXT default '',
	"min_ff_event_normal" INTEGER default 0,
	"min_ff_event_warning" INTEGER default 0,
	"min_ff_event_critical" INTEGER default 0,
	"dynamic_interval" INTEGER default 0,
	"dynamic_max" INTEGER default 0,
	"dynamic_min" INTEGER default 0,
	"dynamic_next" INTEGER default 0 NOT NULL,
	"dynamic_two_tailed" SMALLINT default 0,
	"each_ff" SMALLINT default 0,
	"ff_timeout" INTEGER default 0
);


-- -----------------------------------------------------
-- Table "tpolicy_modules"
-- -----------------------------------------------------
CREATE TABLE "tpolicy_modules" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_policy" INTEGER NOT NULL default 0,
	"configuration_data" TEXT NOT NULL,
	"id_tipo_modulo" SMALLINT NOT NULL default 0,
	"description" varchar(1024) NOT NULL default '',
	"name" varchar(200) NOT NULL default '',
	"unit" TEXT default '',
	"max" BIGINT NOT NULL default 0,
	"min" BIGINT NOT NULL default 0,
	"module_interval" INTEGER NOT NULL default 0,
	"tcp_port" INTEGER NOT NULL default 0,
	"tcp_send" text default '',
	"tcp_rcv" text default '',
	"snmp_community" varchar(100) default '',
	"snmp_oid" varchar(255) default '0',
	"id_module_group" INTEGER NOT NULL default 0,
	"flag" SMALLINT NOT NULL default 0,
	"id_module" INTEGER NOT NULL default 0,
	"disabled" SMALLINT NOT NULL default 0,
	"id_export" SMALLINT NOT NULL default 0,
	"plugin_user" text default '',
	"plugin_pass" text default '',
	"plugin_parameter" text,
	"id_plugin" INTEGER NOT NULL default 0,
	"post_process" DOUBLE PRECISION default NULL,
	"prediction_module" BIGINT NOT NULL default 0,
	"max_timeout" INTEGER NOT NULL default 0,
	"max_retries" INTEGER NOT NULL default 0,
	"custom_id" varchar(255) default '',
	"history_data" SMALLINT NOT NULL default 1,
	"min_warning" DOUBLE PRECISION default 0,
	"max_warning" DOUBLE PRECISION default 0,
	"str_warning" text default '',
	"min_critical" DOUBLE PRECISION default 0,
	"max_critical" DOUBLE PRECISION default 0,
	"str_critical" text default '',
	"min_ff_event" INTEGER NOT NULL default 0,
	"custom_string_1" text default '',
	"custom_string_2" text default '',
	"custom_string_3" text default '',
	"custom_integer_1" INTEGER NOT NULL default 0,
	"custom_integer_2" INTEGER NOT NULL default 0,
	"pending_delete" SMALLINT NOT NULL default 0,
	"critical_instructions" TEXT default '',
	"warning_instructions" TEXT default '',
	"unknown_instructions" TEXT default '',
	"critical_inverse" SMALLINT default 0 NOT NULL,
	"warning_inverse" SMALLINT default 0 NOT NULL,
	"id_category" INTEGER NOT NULL default 0,
	"module_ff_interval" INTEGER NOT NULL default 0,
	"quiet" SMALLINT NOT NULL default 0,
	"cron_interval" varchar(100) default '',
	"macros" text default '',
	"disabled_types_event" TEXT default '',
	"module_macros" TEXT default '',
	"min_ff_event_normal" INTEGER default 0,
	"min_ff_event_warning" INTEGER default 0,
	"min_ff_event_critical" INTEGER default 0,
	"each_ff" SMALLINT default 0,
	"ff_timeout" INTEGER default 0,
	"dynamic_interval" INTEGER default 0,
	"dynamic_max" INTEGER default 0,
	"dynamic_min" INTEGER default 0,
	"dynamic_next" INTEGER NOT NULL default 0,
	"dynamic_two_tailed" INTEGER default 0
);
CREATE UNIQUE INDEX "tpolicy_modules_id_policy_name_idx" ON "tpolicy_modules"("id_policy", "name");
CREATE INDEX "tpolicy_modules_id_policy_idx" ON "tpolicy_modules"("id_policy");


-- -----------------------------------------------------
-- Table "tpolicies"
-- -----------------------------------------------------
-- 'status' could be 0 (without changes, updated), 1 (needy update only database) or 2 (needy update database and conf files)
CREATE TABLE "tpolicies" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" text NOT NULL default '',
	"description" varchar(255) NOT NULL default '',
	"id_group" INTEGER NOT NULL default 0,
	"status" INTEGER NOT NULL default 0
);


-- -----------------------------------------------------
-- Table "tpolicy_alerts"
-- -----------------------------------------------------
CREATE TABLE "tpolicy_alerts" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_policy" INTEGER NOT NULL default 0  REFERENCES tpolicies("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_policy_module" INTEGER NOT NULL default 0,
	"id_alert_template" INTEGER NOT NULL default 0 REFERENCES talert_templates("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"name_extern_module" TEXT NOT NULL default '',
	"disabled" SMALLINT NOT NULL default 0,
	"standby" SMALLINT NOT NULL default 0,
	"pending_delete" SMALLINT NOT NULL default 0	
);


-- -----------------------------------------------------
-- Table "tpolicy_agents"
-- -----------------------------------------------------
CREATE TABLE "tpolicy_agents" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_policy" INTEGER NOT NULL default 0,
	"id_agent" INTEGER NOT NULL default 0,
	"policy_applied" SMALLINT NOT NULL default 0,
	"pending_delete" SMALLINT NOT NULL default 0,
	"last_apply_utimestamp" INTEGER NOT NULL default 0
);
CREATE UNIQUE INDEX "tpolicy_agents_id_policy_id_agent_idx" ON "tpolicy_agents"("id_policy", "id_agent");


-- -----------------------------------------------------
-- Table "tdashboard"
-- -----------------------------------------------------
CREATE TABLE "tdashboard" (
	"id" BIGSERIAL NOT NULL PRIMARY KEY,
	"name" varchar(60) NOT NULL default '',
	"id_user" varchar(60) NOT NULL default '',
	"id_group" INTEGER NOT NULL default 0,
	"active" SMALLINT NOT NULL default 0,
	"cells" text default ''
);


-- -----------------------------------------------------
-- Table "twidget"
-- -----------------------------------------------------
CREATE TABLE "twidget" (
	"id" BIGSERIAL NOT NULL PRIMARY KEY,
	"class_name" varchar(60) NOT NULL default '',
	"unique_name" varchar(60) NOT NULL default '',
	"description" text NOT NULL default '',
	"options" text NOT NULL default '',
	"page" varchar(120) NOT NULL default ''
);


-- -----------------------------------------------------
-- Table "twidget_dashboard"
-- -----------------------------------------------------
CREATE TABLE "twidget_dashboard" (
	"id" BIGSERIAL NOT NULL PRIMARY KEY,
	"options" text NOT NULL default '',
	"order" INTEGER NOT NULL default 0,
	"id_dashboard" BIGINT NOT NULL default 0 REFERENCES tdashboard("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_widget" BIGINT NOT NULL default 0,
	"prop_width" DOUBLE PRECISION NOT NULL default 0.32,
	"prop_height" DOUBLE PRECISION NOT NULL default 0.32,
);


-- -----------------------------------------------------
-- Table "tmodule_inventory"
-- -----------------------------------------------------
CREATE TABLE "tmodule_inventory" (
	"id_module_inventory"SERIAL NOT NULL PRIMARY KEY,
	"id_os" INTEGER default NULL REFERENCES tconfig_os("id_os") ON UPDATE CASCADE ON DELETE CASCADE,
	"name" varchar(100) default '',
	"description" varchar(100) default '',
	"interpreter" varchar(100) default '',
	"data_format" varchar(100) default '',
	"code" BYTEA NOT NULL
);


-- ---------------------------------------------------------------------
-- Table "tagent_module_inventory"
-- ---------------------------------------------------------------------
CREATE TABLE "tagent_module_inventory" (
	"id_agent_module_inventory" SERIAL NOT NULL PRIMARY KEY,
	"id_agente" INTEGER NOT NULL REFERENCES tagente("id_agente") ON UPDATE CASCADE ON DELETE CASCADE,
	"id_module_inventory" INTEGER NOT NULL REFERENCES tmodule_inventory("id_module_inventory") ON UPDATE CASCADE ON DELETE CASCADE,
	"target" varchar(100) default '',
	"interval" INTEGER NOT NULL default 3600,
	"username" varchar(100) default '',
	"password" varchar(100) default '',
	"data" BYTEA NOT NULL,
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"utimestamp" INTEGER NOT NULL default 0,
	"flag" SMALLINT NOT NULL default 1,
	"id_policy_module_inventory" INTEGER NOT NULL default 0
);


-- -----------------------------------------------------
-- Table "tagente_datos_inventory"
-- -----------------------------------------------------
CREATE TABLE "tagente_datos_inventory" (
	"id_agent_module_inventory" SERIAL NOT NULL,
	"data" BYTEA NOT NULL,
	"utimestamp" INTEGER NOT NULL default 0,
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00'
);
CREATE UNIQUE INDEX "tagente_datos_inventory_id" ON "tagente_datos_inventory"("id_agent_module_inventory");
CREATE UNIQUE INDEX "tagente_datos_inventory_ut" ON "tagente_datos_inventory"("utimestamp");


-- -----------------------------------------------------
-- Table "ttrap_custom_values"
-- -----------------------------------------------------
CREATE TABLE "ttrap_custom_values" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"oid" varchar(255) NOT NULL default '',
	"custom_oid" varchar(255) NOT NULL default '',
	"text" varchar(255) default '',
	"description" varchar(255) default '',
	"severity" INTEGER NOT NULL default 2
);


-- -----------------------------------------------------
-- Table "tmetaconsole_setup"
-- -----------------------------------------------------
--Table to store metaconsole sources
CREATE TABLE "tmetaconsole_setup" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"server_name" text default '',
	"server_url" text default '',
	"dbuser" text default '',
	"dbpass" text default '',
	"dbhost" text default '',
	"dbport" text default '',
	"dbname" text default '',
	"auth_token" text default '',
	"id_group" INTEGER NOT NULL default 0,
	"api_password" text default '',
	"disabled" SMALLINT NOT NULL default 0,
	"last_event_replication" BIGINT NOT NULL default 0
);


-- -----------------------------------------------------
-- Table "tprofile_view"
-- -----------------------------------------------------
--Table to define by each profile defined in Pandora, to which sec/page has access independently of its ACL (for showing in the console or not). By default have access to all pages allowed by ACL, if forbidden here, then pages are not shown.
CREATE TABLE "tprofile_view" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_profile" INTEGER NOT NULL default 0,
	"sec" text default '',
	"sec2" text default ''
);


-- ---------------------------------------------------------------------
-- Table "tservice"
-- ---------------------------------------------------------------------
--Table to define services to monitor
CREATE TABLE "tservice" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"description" text NOT NULL default '',
	"id_group" INTEGER NOT NULL default 0,
	"critical" DOUBLE PRECISION NOT NULL default 0,
	"warning" DOUBLE PRECISION NOT NULL default 0,
	"service_interval" DOUBLE PRECISION NOT NULL default 0,
	"service_value" DOUBLE PRECISION NOT NULL default 0,
	"status" INTEGER NOT NULL default -1,
	"utimestamp" INTEGER NOT NULL default 0,
	"auto_calculate" INTEGER NOT NULL default 1,
	"id_agent_module" INTEGER NOT NULL default 0,
	"sla_interval" DOUBLE PRECISION NOT NULL default 0,
	"sla_id_module" INTEGER NOT NULL default 0,
	"sla_value_id_module" INTEGER NOT NULL default 0,
	"sla_limit" DOUBLE PRECISION NOT NULL default 100,
	"id_template_alert_warning" INTEGER NOT NULL default 0,
	"id_template_alert_critical" INTEGER NOT NULL default 0,
	"id_template_alert_unknown" INTEGER NOT NULL default 0,
	"id_template_alert_critical_sla" INTEGER NOT NULL default 0
);


-- -----------------------------------------------------
-- Table "tservice_element"
-- -----------------------------------------------------
--Table to define the modules and the weights of the modules that define a service
CREATE TABLE "tservice_element" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_service" INTEGER NOT NULL,
	"weight_ok" DOUBLE PRECISION NOT NULL default 0,
	"weight_warning" DOUBLE PRECISION NOT NULL default 0,
	"weight_critical" DOUBLE PRECISION NOT NULL default 0,
	"weight_unknown" DOUBLE PRECISION NOT NULL default 0,
	"description" text NOT NULL default '',
	"id_agente_modulo" INTEGER NOT NULL default 0,
	"id_agent" INTEGER NOT NULL default 0,
	"id_service_child" INTEGER NOT NULL default 0,
	"id_server_meta" INTEGER NOT NULL default 0
);


-- ---------------------------------------------------------------------
-- Table "tcollection"
-- ---------------------------------------------------------------------
CREATE TABLE "tcollection" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"short_name" varchar(100) NOT NULL default '',
	"id_group" INTEGER NOT NULL default 0,
	"description" TEXT,
	"status" INTEGER NOT NULL default 0
);
-- status: 0 - Not apply
-- status: 1 - Applied


-- -----------------------------------------------------
-- Table "tpolicy_collections"
-- -----------------------------------------------------
CREATE TABLE "tpolicy_collections" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_policy" INTEGER NOT NULL default 0 REFERENCES "tpolicies" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_collection" INTEGER NOT NULL default 0  REFERENCES "tcollection" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"pending_delete" SMALLINT NOT NULL default 0
);


-- -----------------------------------------------------
-- Table "tpolicy_alerts_actions"
-- -----------------------------------------------------
CREATE TABLE "tpolicy_alerts_actions" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_policy_alert" INTEGER NOT NULL default 0 REFERENCES "tpolicy_alerts" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_alert_action" INTEGER NOT NULL default 0 REFERENCES "talert_actions" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"fires_min" INTEGER default 0,
	"fires_max" INTEGER default 0
);

-- ---------------------------------------------------------------------
-- Table "tpolicy_plugins"
-- ---------------------------------------------------------------------
CREATE TABLE "tpolicy_plugins" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_policy" INTEGER NOT NULL default 0,
	"plugin_exec" TEXT default '',
	"pending_delete" SMALLINT NOT NULL default 0
);

-- -----------------------------------------------------
-- Table "tsesion_extended"
-- -----------------------------------------------------
CREATE TABLE "tsesion_extended" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_sesion" INTEGER NOT NULL,
	"extended_info" TEXT default '',
	"hash" varchar(255) default ''
);


-- -----------------------------------------------------
-- Table `tskin`
-- -----------------------------------------------------
CREATE TABLE "tskin" ( 
	"id" SERIAL NOT NULL PRIMARY KEY, 
	"name" TEXT NOT NULL DEFAULT '',
	"relative_path" TEXT NOT NULL DEFAULT '', 
	"description" text NOT NULL DEFAULT '',
	"disabled" INTEGER NOT NULL default 0
);


-- ---------------------------------------------------------------------
-- Table `tpolicy_queue`
-- ---------------------------------------------------------------------
CREATE TABLE "tpolicy_queue" (
	"id" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_policy" INTEGER NOT NULL default 0,
	"id_agent" INTEGER NOT NULL default 0,
	"operation" varchar(15) NOT NULL default '',
	"progress" INTEGER NOT NULL default 0,
	"end_utimestamp" INTEGER NOT NULL default 0,
	"priority" INTEGER NOT NULL default 0
);


-- -----------------------------------------------------
-- Table `tmodule_synth`
-- -----------------------------------------------------
CREATE TYPE type_tmodule_synth_operation AS ENUM ('ADD', 'SUB', 'DIV', 'MUL', 'AVG', 'NOP');
CREATE TABLE "tmodule_synth" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_agent_module_source" INTEGER NOT NULL default 0,
	"id_agent_module_target" INTEGER NOT NULL default 0 REFERENCES tagente_modulo("id_agente_modulo") ON UPDATE CASCADE ON DELETE CASCADE,
	"fixed_value" float default NULL,
	"operation" type_tmodule_synth_operation NOT NULL default 'NOP',
	"order" INTEGER NOT NULL default 0
);


-- -----------------------------------------------------
-- Table `tevent_rule`
-- -----------------------------------------------------
CREATE TYPE type_tevent_rule_operation AS ENUM ('NOP', 'AND','OR','XOR','NAND','NOR','NXOR');
CREATE TYPE type_tevent_rule_event_type AS ENUM ('','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal');
CREATE TABLE "tevent_rule" (
	"id_event_rule" SERIAL NOT NULL PRIMARY KEY,
	"id_event_alert" INTEGER NOT NULL,
	"operation" type_tevent_rule_operation NOT NULL,
	"order" INTEGER default '0',
	"window" INTEGER NOT NULL default '0',
	"count" INTEGER NOT NULL default '1',
	"agent" text default '',
	"id_usuario" VARCHAR(100) NOT NULL default '',
	"id_grupo" INTEGER NOT NULL default '0',
	"evento" text NOT NULL default '',
	"event_type" type_tevent_rule_event_type default 'unknown',
	"module" text default '',
	"alert" text default '',
	"criticity" INTEGER NOT NULL default '0',
	"user_comment" text NOT NULL,
	"id_tag" INTEGER NOT NULL default '0',
	"name" text default ''
);
CREATE INDEX "tevent_rule_id_event_alert_idx" ON "tevent_rule"("id_event_alert");


-- -----------------------------------------------------
-- Table `tevent_alert`
-- -----------------------------------------------------
CREATE TYPE type_tevent_alert_mode AS ENUM ('PASS','DROP');
CREATE TYPE type_tevent_alert_group_by AS ENUM ('','id_agente','id_agentmodule','id_alert_am','id_grupo');
CREATE TABLE "tevent_alert" (
	"id" SERIAL  NOT NULL PRIMARY KEY,
	"name" text default '',
	"description" text,
	"order" INTEGER default 0,
	"mode" type_tevent_alert_mode,
	"field1" text NOT NULL default '',
	"field2" text NOT NULL default '',
	"field3" text NOT NULL default '',
	"field4" text NOT NULL default '',
	"field5" text NOT NULL default '',
	"field6" text NOT NULL default '',
	"field7" text NOT NULL default '',
	"field8" text NOT NULL default '',
	"field9" text NOT NULL default '',
	"field10" text NOT NULL default '',
	"time_threshold" INTEGER NOT NULL default '0',
	"max_alerts" INTEGER NOT NULL default '1',
	"min_alerts" INTEGER NOT NULL default '0',
	"time_from" time default '00:00:00',
	"time_to" time default '00:00:00',
	"monday" INTEGER default 1,
	"tuesday" INTEGER default 1,
	"wednesday" INTEGER default 1,
	"thursday" INTEGER default 1,
	"friday" INTEGER default 1,
	"saturday" INTEGER default 1,
	"sunday" INTEGER default 1,
	"recovery_notify" INTEGER default '0',
	"field2_recovery" text NOT NULL default '',
	"field3_recovery" text NOT NULL,
	"id_group" INTEGER NULL default 0,
	"internal_counter" INTEGER default '0',
	"last_fired" BIGINT NOT NULL default '0',
	"last_reference" BIGINT NOT NULL default '0',
	"times_fired" INTEGER NOT NULL default '0',
	"disabled" INTEGER default '0',
	"standby" INTEGER default '0',
	"priority" INTEGER default '0',
	"force_execution" INTEGER default '0',
	"group_by" type_tevent_alert_group_by default ''
);

-- -----------------------------------------------------
-- Table `tevent_alert_action`
-- -----------------------------------------------------
CREATE TABLE "tevent_alert_action" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_event_alert" INTEGER NOT NULL REFERENCES tevent_alert("id")  ON DELETE CASCADE ON UPDATE CASCADE,
	"id_alert_action" INTEGER NOT NULL REFERENCES talert_actions("id")  ON DELETE CASCADE ON UPDATE CASCADE,
	"fires_min" INTEGER default 0,
	"fires_max" INTEGER default 0,
	"module_action_threshold" INTEGER NOT NULL default '0',
	"last_execution" BIGINT NOT NULL default '0'
);


-- ---------------------------------------------------------------------
-- Table `tpolicy_modules_inventory`
-- ---------------------------------------------------------------------
CREATE TABLE "tpolicy_modules_inventory" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_policy" INTEGER NOT NULL REFERENCES tpolicies("id") ON UPDATE CASCADE ON DELETE CASCADE,
	"id_module_inventory" INTEGER NOT NULL REFERENCES tmodule_inventory("id_module_inventory") ON UPDATE CASCADE ON DELETE CASCADE,
	"interval" INTEGER NOT NULL default 3600,
	"username" varchar(100) default '',
	"password" varchar(100) default '',
	"pending_delete" SMALLINT NOT NULL default 0
);


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise`
-- -----------------------------------------------------
CREATE TABLE "tnetworkmap_enterprise" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(500) default '',
	"id_group" INTEGER NOT NULL default 0,
	"options" TEXT default ''
);


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise_nodes`
-- -----------------------------------------------------
CREATE TABLE "tnetworkmap_enterprise_nodes" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_networkmap_enterprise" INTEGER NOT NULL REFERENCES tnetworkmap_enterprise("id") ON UPDATE CASCADE ON DELETE CASCADE,
	"x" INTEGER NOT NULL default 0,
	"y" INTEGER NOT NULL default 0,
	"z" INTEGER NOT NULL default 0,
	"id_agent" INTEGER NOT NULL default 0,
	"id_module" INTEGER NOT NULL default 0,
	"id_agent_module" INTEGER NOT NULL default 0,
	"parent" INTEGER default 0,
	"options" text default '',
	"deleted" text default '',
	"state" TEXT default ''
);


-- -----------------------------------------------------
-- Table `tnetworkmap_ent_rel_nodes` (Before `tnetworkmap_enterprise_relation_nodes`)
-- -----------------------------------------------------
CREATE TABLE "tnetworkmap_ent_rel_nodes" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_networkmap_enterprise" INTEGER NOT NULL REFERENCES tnetworkmap_enterprise("id") ON UPDATE CASCADE ON DELETE CASCADE,
	"parent" INTEGER default 0,
	"parent_type" varchar(30) default 'node',
	"child" INTEGER default 0,
	"child_type" varchar(30) default 'node',
	"deleted" text default ''
);


-- -----------------------------------------------------
-- Table `treport_template`
-- -----------------------------------------------------
CREATE TABLE "treport_template" (
	"id_report" SERIAL NOT NULL PRIMARY KEY,
	"id_user" varchar(100) NOT NULL default '',
	"name" varchar(150) NOT NULL default '',
	"description" TEXT NOT NULL,
	"private" SMALLINT NOT NULL default 0,
	"id_group" SMALLINT NULL default NULL,
	"custom_logo" varchar(200)  default NULL,
	"header" TEXT  default NULL,
	"first_page" TEXT default NULL,
	"footer" TEXT default NULL,
	"custom_font" varchar(200) default NULL,
	"metaconsole" SMALLINT DEFAULT 0
);


-- -----------------------------------------------------
-- Table `treport_content_template`
-- -----------------------------------------------------
CREATE TABLE "treport_content_template" (
	"id_rc" SERIAL NOT NULL PRIMARY KEY,
	"id_report" INTEGER NOT NULL default 0 REFERENCES treport_template("id_report") ON DELETE CASCADE,
	"id_gs" INTEGER NULL default NULL,
	"text_agent_module" TEXT default NULL,
	"type" varchar(30) default 'simple_graph',
	"period" INTEGER NOT NULL default 0,
	"order" INTEGER NOT NULL default 0,
	"description" TEXT, 
	"text_agent" TEXT default '',
	"text" TEXT default NULL,
	"external_source" TEXT default NULL,
	"treport_custom_sql_id" INTEGER default 0,
	"header_definition" TEXT default NULL,
	"column_separator" TEXT default NULL,
	"line_separator" TEXT default NULL,
	"time_from" TIME without time zone default '00:00:00',
	"time_to" TIME without time zone default '00:00:00',
	"monday" SMALLINT NOT NULL default 1,
	"tuesday" SMALLINT NOT NULL default 1,
	"wednesday" SMALLINT NOT NULL default 1,
	"thursday" SMALLINT NOT NULL default 1,
	"friday" SMALLINT NOT NULL default 1,
	"saturday" SMALLINT NOT NULL default 1,
	"sunday" SMALLINT NOT NULL default 1,
	"only_display_wrong" SMALLINT not null default 0,
	"top_n" INTEGER NOT NULL default 0,
	"top_n_value" INTEGER NOT NULL default 10,
	"exception_condition" INTEGER NOT NULL default 0,
	"exception_condition_value" DOUBLE PRECISION NOT NULL default 0,
	"show_resume" INTEGER NOT NULL default 0,
	"order_uptodown" INTEGER NOT NULL default 0,
	"show_graph" INTEGER NOT NULL default 0,
	"group_by_agent" INTEGER NOT NULL default 0,
	"style" TEXT NOT NULL DEFAULT '',
	"id_group" INTEGER NOT NULL DEFAULT 0,
	"id_module_group" INTEGER NOT NULL DEFAULT 0,
	"server_name" text default '',
	"exact_match" SMALLINT default 0,
	"module_names" TEXT default NULL,
	"module_free_text" TEXT default NULL,
	"each_agent"  SMALLINT NOT NULL default 1
);


-- -----------------------------------------------------
-- Table `treport_content_sla_com_temp`
-- -----------------------------------------------------
CREATE TABLE "treport_content_sla_com_temp" (
	"id" SERIAL NOT NULL,
	"id_report_content" INTEGER NOT NULL REFERENCES treport_content_template("id_rc") ON DELETE CASCADE,
	"text_agent" TEXT default '',
	"text_agent_module" TEXT default '',
	"sla_max" DOUBLE PRECISION NOT NULL default 0,
	"sla_min" DOUBLE PRECISION NOT NULL default 0,
	"sla_limit" DOUBLE PRECISION NOT NULL default 0,
	"server_name" TEXT default '',
	"exact_match" SMALLINT default 0
);


-- -----------------------------------------------------
-- Table `treport_content_item_temp` (treport_content_item_template)
-- -----------------------------------------------------
CREATE TABLE "treport_content_item_temp" (
	"id" SERIAL NOT NULL,
	"id_report_content" INTEGER NOT NULL REFERENCES treport_content_template("id_rc") ON DELETE CASCADE, 
	"text_agent" TEXT default '',
	"text_agent_module" TEXT default '',
	"server_name" TEXT default '',
	"exact_match" SMALLINT default 0,
	"operation" TEXT
);


-- -----------------------------------------------------
-- Table `tgraph_template`
-- -----------------------------------------------------
CREATE TABLE "tgraph_template" (
	"id_graph_template" SERIAL NOT NULL PRIMARY KEY,
	"id_user" TEXT NOT NULL default '',
	"name" TEXT NOT NULL default '',
	"description" TEXT NOT NULL default '',
	"period" INTEGER NOT NULL default 0,
	"width" SMALLINT NOT NULL default 0,
	"height" SMALLINT NOT NULL default 0,
	"private" SMALLINT NOT NULL default 0,
	"events" SMALLINT NOT NULL default 0,
	"stacked" SMALLINT NOT NULL default 0,
	"id_group" INTEGER NOT NULL default 0
 );


-- -----------------------------------------------------
-- Table `tgraph_source_template`
-- -----------------------------------------------------
CREATE TABLE "tgraph_source_template" (
	"id_gs_template" SERIAL NOT NULL PRIMARY KEY,
	"id_template" INTEGER NOT NULL default 0,
	"agent" TEXT NOT NULL default '',
	"module" TEXT NOT NULL default '',
	"period" INTEGER NOT NULL default 0,
	"weight" DOUBLE PRECISION default 2.0,
	"exact_match" SMALLINT NOT NULL default 0
 );

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_event`
-- ---------------------------------------------------------------------
CREATE TYPE type_tmetaconsole_event_event AS ENUM ('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change');
CREATE TABLE "tmetaconsole_event" (
	"id_evento" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_source_event" BIGSERIAL NOT NULL,
	"id_agente" INTEGER NOT NULL default 0,
	"agent_name" varchar(600) NOT NULL default '',
	"id_usuario" varchar(100) NOT NULL default '0',
	"id_grupo" INTEGER NOT NULL default 0,
	"group_name" text NOT NULL default '',
	"estado" INTEGER NOT NULL default 0,
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"evento" text NOT NULL default '',
	"utimestamp" BIGINT NOT NULL default 0,
	"event_type" type_tmetaconsole_event_event default 'unknown',
	"id_agentmodule" INTEGER NOT NULL default 0,
	"module_name" TEXT NOT NULL default '',
	"id_alert_am" INTEGER NOT NULL default 0,
	"alert_template_name" text default '',
	"criticity" INTEGER NOT NULL default 0,
	"user_comment" text NOT NULL,
	"tags" text NOT NULL,
	"source" text NOT NULL default '',
	"id_extra" text NOT NULL default '',
	"critical_instructions" TEXT default '',
	"warning_instructions" TEXT default '',
	"unknown_instructions" TEXT default '',
	"owner_user" varchar(100) NOT NULL default '0',
	"ack_utimestamp" BIGINT NOT NULL default 0,
	"server_id" INTEGER NOT NULL,
	"custom_data" text NOT NULL
);
CREATE INDEX "tmetaconsole_event_id_1_idx" ON "tmetaconsole_event"("id_agente", "id_evento");
CREATE INDEX "tmetaconsole_event_id_agentmodule_idx" ON "tmetaconsole_event"("id_agentmodule");
CREATE INDEX "tmetaconsole_event_server_id_idx" ON "tmetaconsole_event"("server_id");
CREATE INDEX "tmetaconsole_event_id_grupo_idx" ON "tmetaconsole_event"("id_grupo");
CREATE INDEX "tmetaconsole_event_criticity_idx" ON "tmetaconsole_event"("criticity");
CREATE INDEX "tmetaconsole_event_estado_idx" ON "tmetaconsole_event"("estado");


-- ---------------------------------------------------------------------
-- Table `tmetaconsole_event_history`
-- ---------------------------------------------------------------------
CREATE TYPE type_tmetaconsole_event_event_h AS ENUM ('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change');
CREATE TABLE "tmetaconsole_event_history" (
	"id_evento" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_source_event" BIGSERIAL NOT NULL,
	"id_agente" INTEGER NOT NULL default 0,
	"agent_name" varchar(600) NOT NULL default '',
	"id_usuario" varchar(100) NOT NULL default '0',
	"id_grupo" INTEGER NOT NULL default 0,
	"group_name" text NOT NULL default '',
	"estado" INTEGER NOT NULL default 0,
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"evento" text NOT NULL default '',
	"utimestamp" BIGINT NOT NULL default 0,
	"event_type" type_tmetaconsole_event_event_h default 'unknown',
	"id_agentmodule" INTEGER NOT NULL default 0,
	"module_name" TEXT NOT NULL default '',
	"id_alert_am" INTEGER NOT NULL default 0,
	"alert_template_name" text default '',
	"criticity" INTEGER NOT NULL default 0,
	"user_comment" text NOT NULL,
	"tags" text NOT NULL,
	"source" text NOT NULL default '',
	"id_extra" text NOT NULL default '',
	"critical_instructions" TEXT default '',
	"warning_instructions" TEXT default '',
	"unknown_instructions" TEXT default '',
	"owner_user" varchar(100) NOT NULL default '0',
	"ack_utimestamp" BIGINT NOT NULL default 0,
	"server_id" INTEGER NOT NULL,
	"custom_data" text NOT NULL
);
CREATE INDEX "tmetaconsole_event_h_id_1_idx" ON "tmetaconsole_event_history"("id_agente", "id_evento");
CREATE INDEX "tmetaconsole_event_h_id_agentmodule_idx" ON "tmetaconsole_event_history"("id_agentmodule");
CREATE INDEX "tmetaconsole_event_h_server_id_idx" ON "tmetaconsole_event_history"("server_id");
CREATE INDEX "tmetaconsole_event_h_id_grupo_idx" ON "tmetaconsole_event_history"("id_grupo");
CREATE INDEX "tmetaconsole_event_h_criticity_idx" ON "tmetaconsole_event_history"("criticity");
CREATE INDEX "tmetaconsole_event_h_estado_idx" ON "tmetaconsole_event_history"("estado");

-- ---------------------------------------------------------------------
-- Table `tagent_module_log`
-- ---------------------------------------------------------------------
CREATE TABLE "tagent_module_log" (
	"id_agent_module_log" SERIAL NOT NULL PRIMARY KEY,
	"id_agent" INTEGER NOT NULL REFERENCES tagente("id_agente") ON UPDATE CASCADE ON DELETE CASCADE,
	"source" TEXT,
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"utimestamp" BIGINT NOT NULL default 0
);

-- ---------------------------------------------------------------------
-- Table `tevent_custom_field`
-- ---------------------------------------------------------------------
CREATE TABLE "tevent_custom_field" (
	"id_group" INTEGER NOT NULL default 0,
	"value" text NOT NULL default ''
);

-- ----------------------------------------------------------------------
-- Table `tagente_estado`
-- ----------------------------------------------------------------------

ALTER TABLE tagente_estado RENAME COLUMN last_known_status TO known_status;
ALTER TABLE tagente_estado ADD COLUMN last_known_status NUMBER(10, 0) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------------------------

ALTER TABLE tusuario ADD COLUMN id_filter int(10) unsigned default NULL;
ALTER TABLE tusuario ADD COLUMN CONSTRAINT fk_id_filter FOREIGN KEY (id_filter) REFERENCES tevent_filter(id_filter) ON DELETE SET NULL;
ALTER TABLE tusuario ADD COLUMN session_time int(10) NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_modulo ADD COLUMN dynamic_interval int(4) unsigned default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_max bigint(20) default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_min bigint(20) default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_next bigint(20) NOT NULL default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_two_tailed tinyint(1) unsigned default 0;

-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------
ALTER TABLE tnetwork_component ADD COLUMN dynamic_interval int(4) unsigned default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_max int(4) default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_min int(4) default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_next bigint(20) NOT NULL default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_two_tailed tinyint(1) unsigned default 0;

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
ALTER TABLE tagente ADD transactional_agent tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD remote tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD cascade_protection_module int(10) unsigned default '0';

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout ADD COLUMN background_color varchar(50) NOT NULL default '#FFF';

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout_data ADD COLUMN type_graph varchar(50) NOT NULL default 'area';
ALTER TABLE tlayout_data ADD COLUMN label_position varchar(50) NOT NULL default 'down';

-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------
ALTER TABLE talert_templates ADD COLUMN field11 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field12 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field13 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field14 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field15 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field11_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field12_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field13_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field14_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field15_recovery TEXT NOT NULL DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp ADD COLUMN al_field11 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field12 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field13 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field14 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field15 TEXT NOT NULL DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `talert_snmp_action`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp_action ADD COLUMN al_field11 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field12 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field13 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field14 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field15 TEXT NOT NULL DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `talert_actions`
-- ---------------------------------------------------------------------
ALTER TABLE talert_actions ADD COLUMN field11 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field12 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field13 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field14 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field15 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field11_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field12_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field13_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field14_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field15_recovery TEXT NOT NULL DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `tnetflow_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tnetflow_filter ADD COLUMN router_ip TEXT NOT NULL DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `tlocal_component`
-- ---------------------------------------------------------------------
ALTER TABLE tlocal_component ADD "dynamic_interval" INTEGER default 0;
ALTER TABLE tlocal_component ADD "dynamic_max" INTEGER default 0;
ALTER TABLE tlocal_component ADD "dynamic_min" INTEGER default 0;
ALTER TABLE tlocal_component ADD "dynamic_next" INTEGER default 0 NOT NULL;
ALTER TABLE tlocal_component ADD "dynamic_two_tailed" SMALLINT default 0;

-- ---------------------------------------------------------------------
-- Table `tpolicy_module`
-- ---------------------------------------------------------------------
ALTER TABLE tpolicy_modules ADD "dynamic_interval" INTEGER default 0;
ALTER TABLE tpolicy_modules ADD "dynamic_max" INTEGER default 0;
ALTER TABLE tpolicy_modules ADD "dynamic_min" INTEGER default 0;
ALTER TABLE tpolicy_modules ADD "dynamic_next" INTEGER default 0 NOT NULL;
ALTER TABLE tpolicy_modules ADD "dynamic_two_tailed" SMALLINT default 0;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent`
-- ---------------------------------------------------------------------
ALTER TABLE tmetaconsole_agent ADD "remote" INTEGER default 0 NOT NULL;
ALTER TABLE tmetaconsole_agent ADD "cascade_protection_module" INTEGER default 0;
ALTER TABLE tmetaconsole_agent ADD "transactional_agent" INTEGER default 0 NOT NULL;