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
    .post(
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

/* Function check API */
// eslint-disable-next-line no-unused-vars
function testConectionApi(pass, host) {
  var hideLoadingImage = function() {
    $("span#ITSM-spinner").hide();
  };
  var showLoadingImage = function() {
    $("span#ITSM-spinner").show();
  };
  var hideSuccessImage = function() {
    $("span#ITSM-success").hide();
  };
  var showSuccessImage = function() {
    $("span#ITSM-success").show();
  };
  var hideFailureImage = function() {
    $("span#ITSM-failure").hide();
  };
  var showFailureImage = function() {
    $("span#ITSM-failure").show();
  };
  var hideMessage = function() {
    $("span#ITSM-message").hide();
  };
  var showMessage = function() {
    $("span#ITSM-message").show();
  };

  hideSuccessImage();
  hideFailureImage();
  hideMessage();
  showLoadingImage();

  var data = {
    page: "operation/ITSM/itsm",
    method: "checkConnectionApi",
    pass: pass,
    host: host
  };

  $.ajax({
    type: "POST",
    url: "ajax.php",
    dataType: "json",
    data: data
  })
    .done(function(data) {
      if (data.valid == 1) {
        showSuccessImage();
      } else {
        showFailureImage();
        showMessage();
      }
    })
    .fail(function() {
      showFailureImage();
      showMessage();
    })
    .always(function() {
      hideLoadingImage();
    });
}

/* Function check API */
// eslint-disable-next-line no-unused-vars
function testConectionApiItsmToPandora(path) {
  var hideLoadingImage = function() {
    $("span#ITSM-spinner-pandora").hide();
  };
  var showLoadingImage = function() {
    $("span#ITSM-spinner-pandora").show();
  };
  var hideSuccessImage = function() {
    $("span#ITSM-success-pandora").hide();
  };
  var showSuccessImage = function() {
    $("span#ITSM-success-pandora").show();
  };
  var hideFailureImage = function() {
    $("span#ITSM-failure-pandora").hide();
  };
  var showFailureImage = function() {
    $("span#ITSM-failure-pandora").show();
  };
  var hideMessage = function() {
    $("span#ITSM-message-pandora").hide();
  };
  var showMessage = function() {
    $("span#ITSM-message-pandora").show();
  };

  hideSuccessImage();
  hideFailureImage();
  hideMessage();
  showLoadingImage();

  var data = {
    page: "operation/ITSM/itsm",
    method: "checkConnectionApiITSMToPandora",
    path: path
  };

  $.ajax({
    type: "POST",
    url: "ajax.php",
    dataType: "json",
    data: data
  })
    .done(function(data) {
      if (data.valid == 1) {
        showSuccessImage();
      } else {
        showFailureImage();
        showMessage();
      }
    })
    .fail(function() {
      showFailureImage();
      showMessage();
    })
    .always(function() {
      hideLoadingImage();
    });
}
