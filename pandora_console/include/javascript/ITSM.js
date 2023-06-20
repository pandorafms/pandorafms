/* global $ jQuery */

/* Function get custom fields incidences */
// eslint-disable-next-line no-unused-vars
function getInputFieldsIncidenceType(idIncidenceType, fieldsData, ajaxUrl) {
  console.log(idIncidenceType);
  console.log(fieldsData);
  console.log(ajaxUrl);
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
