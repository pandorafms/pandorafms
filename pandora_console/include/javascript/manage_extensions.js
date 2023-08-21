/* globals $, page, url, textsToTranslate, confirmDialog*/
$(document).ready(function() {
  function loading(status) {
    if (status) {
      $(".spinner-fixed").show();
      $("#button-upload_button").attr("disabled", "true");
    } else {
      $(".spinner-fixed").hide();
      $("#button-upload_button").removeAttr("disabled");
    }
  }

  $("#uploadExtension").submit(function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append("page", page);
    formData.append("method", "validateIniName");
    loading(true);
    $.ajax({
      method: "POST",
      url: url,
      data: formData,
      processData: false,
      contentType: false,
      success: function(data) {
        loading(false);
        data = JSON.parse(data);
        if (data.success) {
          if (data.warning) {
            confirmDialog({
              title: textsToTranslate["Warning"],
              message: data.message,
              strOKButton: textsToTranslate["Confirm"],
              strCancelButton: textsToTranslate["Cancel"],
              onAccept: function() {
                loading(true);
                $("#uploadExtension")[0].submit();
              },
              onDeny: function() {
                return false;
              }
            });
          } else {
            $("#uploadExtension")[0].submit();
          }
        } else {
          confirmDialog({
            title: textsToTranslate["Error"],
            message: data.message,
            ok: textsToTranslate["Ok"],
            hideCancelButton: true,
            onAccept: function() {
              return false;
            }
          });
        }
      },
      error: function() {
        loading(false);
        confirmDialog({
          title: textsToTranslate["Error"],
          message: textsToTranslate["Failed to upload extension"],
          ok: textsToTranslate["Ok"],
          hideCancelButton: true,
          onAccept: function() {
            return false;
          }
        });
      }
    });
  });
});
