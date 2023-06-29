/* global $ jQuery */

/* Function get custom fields incidences */
// eslint-disable-next-line no-unused-vars
function getInputFieldsIncidenceType(idIncidenceType, fieldsData, ajaxUrl) {
  // Failed request handler.
  var handleFail = function(jqXHR, textStatus, errorThrown) {
    console.log(jqXHR, textStatus, errorThrown);
  };

  // Function which handle success case.
  var handleSuccess = function(data) {
    $(".object-type-fields").empty();
    $(".object-type-fields").append(data);
  };

  // Visual Console container request.
  jQuery
    .get(
      ajaxUrl,
      {
        page: "operation/ITSM/itsm",
        method: "getInputFieldsIncidenceType",
        idIncidenceType: idIncidenceType,
        fieldsData: fieldsData
      },
      "html"
    )
    .done(handleSuccess)
    .fail(handleFail);
}

/* Function get custom fields incidences */
// eslint-disable-next-line no-unused-vars
function downloadIncidenceAttachment(
  idIncidence,
  idAttachment,
  ajaxUrl,
  filename
) {
  $.ajax({
    type: "POST",
    url: ajaxUrl,
    data: {
      page: "operation/ITSM/itsm",
      method: "getDownloadIncidenceAttachment",
      idIncidence: idIncidence,
      idAttachment: idAttachment
    },
    dataType: "binary",
    xhrFields: {
      responseType: "arraybuffer"
    },
    success: function(data) {
      var blob = new Blob([data], { type: "application/octetstream" });
      var link = document.createElement("a");
      link.href = window.URL.createObjectURL(blob);
      link.download = filename;
      document.body.appendChild(link);
      link.click();
    },
    error: function(jqXHR, textStatus, message) {
      console.error(textStatus, message);
    }
  });
}
