/* globals $, uniqId, confirmDialog*/
// eslint-disable-next-line no-unused-vars
function dialogAgentModulesAffected(settings) {
  confirmDialog({
    title: settings.title,
    size: 500,
    message: function() {
      var id = "div-" + uniqId();
      var loading = settings.loadingText;
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: "godmode/agentes/planned_downtime.list",
          show_info_agents_modules_affected: 1,
          id: settings.id
        },
        dataType: "html",
        success: function(data) {
          $("#" + id)
            .empty()
            .append(data);
        },
        error: function(error) {
          console.error(error);
        }
      });

      return "<div id ='" + id + "'>" + loading + "</div>";
    }
  });
}
