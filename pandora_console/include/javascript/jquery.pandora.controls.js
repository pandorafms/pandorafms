/* globals jQuery, js_html_entity_decode */
(function($) {
  var dummyFunc = function() {
    return true;
  };

  var _pandoraSelectGroupAgent = function(disabled) {
    var that = this;

    this.defaults = {
      agentSelect: "select#id_agent",
      status_agents: -1,
      recursion: 0,
      filter_agents_json: "",
      loading: "#agent_loading",
      callbackBefore: dummyFunc,
      callbackPre: dummyFunc,
      callbackPost: dummyFunc,
      callbackAfter: dummyFunc,
      debug: false,
      disabled: disabled || false,
      privilege: "",
      serialized: false,
      serialized_separator: "",
      nodes: []
    };

    /* public methods */
    this.construct = function(settings) {
      return this.each(function() {
        this.config = {};

        this.config = $.extend(this.config, that.defaults, settings);
        var config = this.config;

        $(this).change(function() {
          var $select = $(config.agentSelect).disable();
          $(config.loading).show();
          $("option[value!=0]", $select).remove();
          if (!config.callbackBefore(this)) return;

          var recursion_value;
          if (typeof config.recursion === "function") {
            // Perform this for those cases where recursion parameter
            // is obtained through a function that returns a variable
            // that is set in the lexical environment
            // where this constructor is called.
            recursion_value = config.recursion();

            if (typeof recursion_value === "boolean") {
              recursion_value = recursion_value ? 1 : 0;
            }
          } else {
            recursion_value = config.recursion;
          }

          var opts = {
            page: "godmode/groups/group_list",
            get_group_agents: 1,
            id_group: this.value,
            recursion: recursion_value,
            filter_agents_json: config.filter_agents_json,
            disabled:
              typeof config.disabled === "function"
                ? config.disabled()
                : config.disabled,
            status_agents: config.status_agents,
            add_alert_bulk_op: config.add_alert_bulk_op,
            privilege: config.privilege,
            // Add a key prefix to avoid auto sorting in js object conversion
            keys_prefix: "_",
            serialized: config.serialized,
            serialized_separator: config.serialized_separator,
            nodes:
              typeof config.nodes === "function"
                ? config.nodes()
                : config.disabled
          };

          jQuery.post(
            "ajax.php",
            opts,
            function(data) {
              jQuery.each(data, function(id, value) {
                // Remove keys_prefix from the index.
                id = id.substring(1);
                if (id !== "keycount") {
                  config.callbackPre();
                  var option = $("<option></option>")
                    .attr("value", id)
                    .html(value);
                  config.callbackPost(id, value, option);
                  $(config.agentSelect).append(option);
                }
              });
              $(config.loading).hide();
              $select.enable();
              config.callbackAfter();
            },
            "json"
          );
        });
      });
    };
  };

  $.extend({
    pandoraSelectGroupAgent: new _pandoraSelectGroupAgent(),
    pandoraSelectGroupAgentDisabled: new _pandoraSelectGroupAgent(true)
  });

  $.extend({
    pandoraSelectAgentModule: new (function() {
      this.defaults = {
        moduleSelect: "select#id_agent_module",
        loading: "#module_loading",
        callbackBefore: dummyFunc,
        callbackPre: dummyFunc,
        callbackPost: dummyFunc,
        callbackAfter: dummyFunc,
        moduleFilter: {
          disabled: 0,
          deletePending: 0
        },
        debug: false
      };

      /* public methods */
      this.construct = function(settings) {
        return this.each(function() {
          this.config = {};

          this.config = $.extend(
            this.config,
            $.pandoraSelectAgentModule.defaults,
            settings
          );

          var config = this.config;
          $(this).change(function() {
            var $select = $(config.moduleSelect).disable();
            $(config.loading).show();
            $("option[value!=0]", $select).remove();
            if (!config.callbackBefore(this)) return;

            jQuery.post(
              "ajax.php",
              {
                page: "operation/agentes/ver_agente",
                get_agent_modules_json: 1,
                id_agent: this.value,
                disabled: config.moduleFilter.disabled,
                delete_pending: config.moduleFilter.deletePending
              },
              function(data) {
                jQuery.each(data, function(i, value) {
                  config.callbackPre();
                  // Get the selected item from hidden field
                  var selected = $(
                    "#hidden-" + config.moduleSelect.attr("id") + "_selected"
                  ).val();
                  var option;
                  if (selected == i) {
                    option = $("<option></option>")
                      .prop("selected", true)
                      .attr("value", value["id_agente_modulo"])
                      .html(js_html_entity_decode(value["nombre"]));
                  } else {
                    option = $("<option></option>")
                      .attr("value", value["id_agente_modulo"])
                      .html(js_html_entity_decode(value["nombre"]));
                  }
                  config.callbackPost(i, value, option);
                  $(config.moduleSelect).append(option);
                });
                $(config.loading).hide();
                $select.enable();
                config.callbackAfter();
              },
              "json"
            );
          });
        });
      };
    })()
  });

  $.extend({
    pandoraSelectAgentAlert: new (function() {
      this.defaults = {
        alertSelect: "select#id_agent_module",
        loading: "#alert_loading",
        callbackBefore: dummyFunc,
        callbackPre: dummyFunc,
        callbackPost: dummyFunc,
        callbackAfter: dummyFunc,
        debug: false
      };

      /* public methods */
      this.construct = function(settings) {
        return this.each(function() {
          this.config = {};

          this.config = $.extend(
            this.config,
            $.pandoraSelectAgentAlert.defaults,
            settings
          );

          var config = this.config;

          $(this).change(function() {
            var $select = $(config.alertSelect).disable();
            $(config.loading).show();
            $("option[value!=0]", $select).remove();
            if (!config.callbackBefore(this)) return;

            jQuery.post(
              "ajax.php",
              {
                page: "include/ajax/alert_list.ajax",
                get_agent_alerts_simple: 1,
                id_agent: this.value
              },
              function(data) {
                jQuery.each(data, function(i, value) {
                  config.callbackPre();
                  var option = $("<option></option>")
                    .attr("value", value["id"])
                    .html(js_html_entity_decode(value["template"]["name"]))
                    .append(
                      " (" + js_html_entity_decode(value["module_name"]) + ")"
                    );
                  config.callbackPost(i, value, option);
                  $(config.alertSelect).append(option);
                });
                $(config.loading).hide();
                $select.enable();
                config.callbackAfter();
              },
              "json"
            );
          });
        });
      };
    })()
  });

  $.extend({
    pandoraSelectOS: new (function() {
      this.defaults = {
        alertSelect: "select#id_os",
        spanPreview: "#os_preview",
        debug: false
      };

      /* public methods */
      this.construct = function(settings) {
        return this.each(function() {
          this.config = {};
          this.config = $.extend(
            this.config,
            $.pandoraSelectOS.defaults,
            settings
          );

          var config = this.config;

          $(this).change(function() {
            var id_os = this.value;
            $("select#id_os").select2("close");

            var home_url;
            if (typeof settings == "undefined") home_url = "./";
            else home_url = settings.home_url;

            $(config.spanPreview).fadeOut("fast", function() {
              $("img", config.spanPreview).remove();
              jQuery.post(
                home_url + "ajax.php",
                { page: "godmode/setup/setup", get_os_icon: 1, id_os: id_os },
                function(data) {
                  $(config.spanPreview)
                    .append(data)
                    .fadeIn("fast");
                },
                "html"
              );
            });
          });
        });
      };
    })()
  });

  $.extend({
    pandoraSelectGroupIcon: new (function() {
      this.defaults = {
        alertSelect: "select#grupo",
        spanPreview: "#group_preview",
        debug: false
      };

      /* public methods */
      this.construct = function(settings) {
        return this.each(function() {
          this.config = {};
          this.config = $.extend(
            this.config,
            $.pandoraSelectGroupIcon.defaults,
            settings
          );

          var config = this.config;

          $(this).change(function() {
            var id_group = this.value;
            let href = $("a", config.spanPreview).attr("href");
            let hrefPosition = href.search("group_id=");
            let hrefNew = href.slice(0, hrefPosition) + "group_id=" + id_group;

            jQuery.post(
              "ajax.php",
              {
                page: "godmode/groups/group_list",
                get_group_json: 1,
                id_group: id_group
              },
              function(data) {
                $("img", config.spanPreview).attr(
                  "src",
                  "images/" + data["icon"]
                );
                $("a", config.spanPreview).attr("href", hrefNew);
              },
              "json"
            );
          });
        });
      };
    })()
  });

  $.fn.extend({
    pandoraSelectGroupAgent: $.pandoraSelectGroupAgent.construct,
    pandoraSelectGroupAgentDisabled:
      $.pandoraSelectGroupAgentDisabled.construct,
    pandoraSelectAgentModule: $.pandoraSelectAgentModule.construct,
    pandoraSelectAgentAlert: $.pandoraSelectAgentAlert.construct,
    pandoraSelectOS: $.pandoraSelectOS.construct,
    pandoraSelectGroupIcon: $.pandoraSelectGroupIcon.construct
  });
})(jQuery);
