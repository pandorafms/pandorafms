var editor = ace.edit("elasticsearch_editor");
editor.setValue(`GET _search \n{\n  "query": {\n    "match_all": {}\n  }\n}`);
editor.clearSelection();

var view = ace.edit("elasticsearch_view");
view.setTheme("ace/theme/textmate");
view.session.setMode("ace/mode/json");
view.renderer.setShowGutter(false);
view.setReadOnly(true);
view.setShowPrintMargin(false);

$("#button-execute_query").click(function() {
  view.setValue("");
  let text;
  let selectText = editor.getSelectedText();
  if (selectText === "") {
    let allText = editor.getValue();
    if (allText === "") {
      return;
    }

    allText = allText.split("\n").join("");
    allText = allText.concat("\n");
    text = allText.match("(GET|PUT|POST)(.*?)({.*?}.*?)?(GET|POST|PUT|\n)");
  } else {
    selectText = selectText.split("\n").join("");
    selectText = selectText.concat("\n");
    text = selectText.match("(GET|PUT|POST)(.*?)({.*?}.*?)?(\n)");
  }

  if (
    text === null ||
    text === undefined ||
    text[2] === "" ||
    text[2] === undefined
  ) {
    view.setValue(`Syntax error`);
    view.clearSelection();
    return;
  }

  const head = text[1];
  let index = text[2].trim();
  if (index.match("^/") === null) {
    index = `/${index}`;
  }

  let json = text[3];
  if (json !== "" && json !== undefined) {
    json = json.match("^{.*}")[0];
  }

  jQuery.post(
    $("#pandora_full_url").text() + "ajax.php",
    {
      page: "enterprise/include/ajax/log_viewer.ajax",
      elasticsearch_curl: 1,
      head: head,
      index: index,
      json: json
    },
    function(data) {
      view.setValue(data);
      view.clearSelection();

      forced_title_callback();
    },
    "html"
  );
});
