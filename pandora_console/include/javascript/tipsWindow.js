/* globals $, idTips, totalTips, idTips, url, page */
$(".carousel").ready(function() {
  function render({ title, text, url, files }) {
    $("#title_tip").html(title);
    $("#text_tip").html(text);
    $("#url_tip").attr("href", url);
    $(".carousel .images").empty();

    if (files) {
      files.forEach(file => {
        $(".carousel .images").append(`<img src="${file.filename}" />`);
      });
      $(".carousel").removeClass("invisible");
    } else {
      $(".carousel").addClass("invisible");
    }
  }

  $("#next_tip").on("click", function() {
    if (idTips.length >= totalTips) {
      idTips = [];
    }
    $.ajax({
      method: "POST",
      url: url,
      dataType: "json",
      data: {
        page: page,
        method: "getRandomTip",
        exclude: JSON.stringify(idTips)
      },
      success: function({ success, data }) {
        if (success) {
          idTips.push(parseInt(data.id));
          render(data);
        } else {
          //TODO control error
        }
      }
    });
  });
});
