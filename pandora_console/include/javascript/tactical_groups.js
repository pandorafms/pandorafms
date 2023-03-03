/* global $, load_modal */
function showInfoAgent(id_agent) {
  load_modal({
    target: $("#modal-info-agent"),
    url: "ajax.php",
    modal: {
      title: "Info agent",
      cancel: "close"
    },
    onshow: {
      page: "include/ajax/group",
      method: "loadInfoAgent",
      extradata: {
        idAgent: id_agent
      }
    }
  });
}
