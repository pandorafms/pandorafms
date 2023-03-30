/* exported form_upload */
/* global $,ajax,cleanExit,preventExit,umConfirm,umErrorMsg */
/* global texts,ajaxPage,insecureMode */
/* global ImSureWhatImDoing */

/**
 *
 * @param {string} url
 * @param {string} auth
 * @param {string} current_package
 */
function form_upload(url, auth, current_package) {
  // Thanks to: http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/
  var ul = $("#form-offline_update ul");

  $("#form-offline_update div").prop("id", "drop_file");
  $("#drop_file").html(
    texts.dropZoneText +
      "&nbsp;&nbsp;&nbsp;<a>" +
      texts.browse +
      "</a>" +
      '<input name="upfile" type="file" id="file-upfile" accept=".oum, .tar.gz" class="sub file" />'
  );
  $("#drop_file a").click(function() {
    // Simulate a click on the file input button to show the file browser dialog
    $(this)
      .parent()
      .find("input")
      .click();
  });

  // Initialize the jQuery File Upload plugin
  $("#form-offline_update").fileupload({
    url: url,

    // This element will accept file drag/drop uploading
    dropZone: $("#drop_file"),

    // This function is called when a file is added to the queue;
    // either via the browse button, or via drag/drop:
    add: function(e, data) {
      let number_update = data.files[0].name.match(/package_(\d+\.*\d*)\.oum/);

      if (number_update === null) {
        number_update = data.files[0].name.match(
          /pandorafms_server.*NG\.(\d+\.*\d*?)_.*\.tar\.gz/
        );
      }

      if (number_update === null) {
        umConfirm({
          message:
            '<span class="warning"></span><p>' + texts.invalidPackage + "</p>",
          title: texts.warning,
          size: 700,
          onAccept: function() {
            location.reload();
          },
          onDeny: function() {
            cancelUpdate();
          }
        });
      } else {
        $("#drop_file").slideUp();
        var tpl = $(
          "<li>" +
            '<input type="text" id="input-progress" ' +
            'value="0" data-width="55" data-height="55" ' +
            'data-fgColor="#82b92e" data-readOnly="1" ' +
            'data-bgColor="#3E4043" />' +
            "<p></p><span></span>" +
            "</li>"
        );

        // Append the file name and file size
        tpl
          .find("p")
          .text(data.files[0].name)
          .append("<i>" + formatFileSize(data.files[0].size) + "</i>");

        // Add the HTML to the UL element
        ul.html("");
        data.context = tpl.appendTo(ul);

        // Initialize the knob plugin
        tpl.find("input").val(0);
        tpl.find("input").knob({
          draw: function() {
            $(this.i).val(this.cv + "%");
          }
        });

        // Listen for clicks on the cancel icon
        tpl.find("span").click(function() {
          if (tpl.hasClass("working") && typeof jqXHR != "undefined") {
            jqXHR.abort();
          }

          tpl.fadeOut(function() {
            tpl.remove();
            $("#drop_file").slideDown();
          });
        });

        // Automatically upload the file once it is added to the queue
        data.context.addClass("working");
        var jqXHR = data.submit();
      }
    },

    progress: function(e, data) {
      // Calculate the completion percentage of the upload
      var progress = parseInt((data.loaded / data.total) * 100, 10);

      // Update the hidden input field and trigger a change
      // so that the jQuery knob plugin knows to update the dial
      data.context
        .find("input")
        .val(progress)
        .change();

      if (progress == 100) {
        data.context.removeClass("working");
        // Class loading while the zip is extracted
        data.context.addClass("loading");
      }
    },

    fail: function(e, data) {
      // Something has gone wrong!
      data.context.removeClass("working");
      data.context.removeClass("loading");
      data.context.addClass("error");
      var response = data.response();
      if (response != null) {
        $("#drop_file").prop("id", "log_zone");

        // Success messages
        var log_zone = $("#log_zone");
        log_zone.html("<div>" + response.jqXHR.responseText + "</div>");
        $("#log_zone").slideDown(400, function() {
          $("#log_zone").height(200);
          $("#log_zone").css("overflow", "auto");
        });
      }
    },

    done: function(e, data) {
      var res;
      try {
        res = JSON.parse(data.result);
      } catch (e) {
        res = {
          status: "error",
          message: data.result
        };
      }

      if (res.status == "success") {
        data.context.removeClass("loading");
        data.context.addClass("suc");

        ul.find("li")
          .find("span")
          .unbind("click");

        // Transform the file input zone to show messages
        $("#drop_file").prop("id", "log_zone");

        // Success messages
        var log_zone = $("#log_zone");
        log_zone.html("<div>" + texts.uploadSuccess + "</div>");
        log_zone.append("<div>" + texts.uploadMessage + "</div>");
        log_zone.append("<div>" + texts.clickToStart + "</div>");

        if (res.files !== null) {
          var file_list =
            "<h2>" + texts.fileList + "</h2><div class='file_list'>";

          if (res.files) {
            res.files.forEach(function(e) {
              file_list += "<div class='file_entry'>" + e + "</div>";
            });
          }

          file_list += "</div>";
          log_zone.append(file_list);
        }

        // Show messages
        $("#log_zone").slideDown(400, function() {
          $("#log_zone").height(200);
          $("#log_zone").css("overflow", "auto");
        });

        // Bind the the begin of the installation to the package li
        ul.find("li").css("cursor", "pointer");
        ul.find("li").click(function() {
          ul.find("li").unbind("click");
          ul.find("li").css("cursor", "default");

          // Changed the data that shows the file li
          data.context.find("p").text(texts.updatingTo + res.version);
          data.context
            .find("input")
            .val(0)
            .change();

          let number_update = res.version;
          let server_update = res.server_update;
          let current_version = parseFloat(current_package);
          let target_version = Math.round(parseFloat(current_package)) + 1;
          let target_patch = parseFloat(current_package) + 0.1;

          if (number_update === null) {
            umConfirm({
              message:
                '<span class="warning"></span><p>' +
                texts.invalidPackage +
                "</p>",
              title: texts.warning,
              size: 535,
              onAccept: function() {
                location.reload();
              },
              onDeny: function() {
                cancelUpdate();
              }
            });
          } else if (
            parseFloat(number_update) != target_version &&
            parseFloat(number_update) != target_patch &&
            parseFloat(number_update) != current_version
          ) {
            if (ImSureWhatImDoing == undefined || ImSureWhatImDoing == false) {
              umConfirm({
                message:
                  '<span class="warning"></span><p>' +
                  (server_update
                    ? texts.notGoingToInstallUnoficialServerWarning
                    : texts.notGoingToInstallUnoficialWarning) +
                  "</p>",
                title: texts.warning,
                size: 535,
                onAccept: function() {
                  location.reload();
                },
                onDeny: function() {
                  cancelUpdate();
                }
              });
              return;
            }

            umConfirm({
              message:
                '<span class="warning"></span><p>' +
                (server_update
                  ? texts.unoficialServerWarning
                  : texts.unoficialWarning) +
                "</p>",
              title: texts.warning,
              size: 535,
              onAccept: function() {
                if (insecureMode) {
                  // Begin the installation
                  install_package(
                    url,
                    auth,
                    res.packageId,
                    res.version,
                    server_update
                  );
                } else {
                  // Verify sign. (optional)
                  umConfirm({
                    message:
                      "<p>" +
                      texts.verifysigns +
                      "<br><textarea id='signature'></textarea></p>",
                    title: texts.verifysigntitle,
                    cancelText: texts.ignoresign,
                    size: 535,
                    onAccept: function() {
                      // Send validation
                      ajax({
                        url: url,
                        cors: auth,
                        page: ajaxPage,
                        data: {
                          action: "validateUploadedOUM",
                          packageId: res.packageId,
                          signature: $("#signature").val()
                        },
                        success: function(d) {
                          var result = d.result;
                          if (result.status == "success") {
                            // Begin the installation
                            install_package(
                              url,
                              auth,
                              res.packageId,
                              res.version,
                              server_update
                            );
                          } else {
                            cancelUpdate(result.message);
                          }
                        },
                        error: function(e, request) {
                          cancelUpdate(e.error);
                          console.error(request);
                        }
                      });
                    },
                    onDeny: function() {
                      // Begin the installation
                      install_package(
                        url,
                        auth,
                        res.packageId,
                        res.version,
                        server_update
                      );
                    }
                  });
                }
              },
              onDeny: function() {
                cancelUpdate();
              }
            });
          } else {
            // Begin the installation
            install_package(
              url,
              auth,
              res.packageId,
              res.version,
              server_update
            );
          }
        });
      } else {
        // Something has gone wrong!
        data.context.removeClass("loading");
        data.context.addClass("error");
        ul.find("li")
          .find("span")
          .click(function() {
            window.location.reload();
          });

        // Transform the file input zone to show messages
        $("#drop_file").prop("id", "log_zone");

        // Error messages
        $("#log_zone").html("<div>" + res.message + "</div>");

        // Show error messages
        $("#log_zone").slideDown(400, function() {
          $("#log_zone").height(75);
          $("#log_zone").css("overflow", "auto");
        });
      }
    }
  });

  // Prevent the default action when a file is dropped on the window
  $(document).on("drop_file dragover", function(e) {
    e.preventDefault();
  });
}

// Helper function that formats the file sizes
function formatFileSize(bytes) {
  if (typeof bytes !== "number") {
    return "";
  }

  if (bytes >= 1000000000) {
    return (bytes / 1000000000).toFixed(2) + " GB";
  }

  if (bytes >= 1000000) {
    return (bytes / 1000000).toFixed(2) + " MB";
  }

  return (bytes / 1000).toFixed(2) + " KB";
}

/**
 * Start to install package.
 *
 * @param {string} url
 * @param {string} auth
 * @param {string} packageId
 * @param {boolean} serverUpdate
 */
function install_package(url, auth, packageId, version, serverUpdate) {
  var processed = 0;
  umConfirm({
    message:
      (serverUpdate ? texts.ensureServerUpdate : texts.ensureUpdate) +
      version +
      ". " +
      texts.ensure,
    title: texts.updatingTo + version,
    onAccept: function() {
      // Schedule update progress using notify from UMC.
      preventExit();
      var progressInterval = setInterval(function() {
        updateOfflineProgress(url, auth);
      }, 1000);

      ajax({
        url: url,
        cors: auth,
        page: ajaxPage,
        data: {
          action: "installUploadedOUM",
          packageId: packageId
        },
        success: function(d) {
          // Succesfully installed.
          clearInterval(progressInterval);
          cleanExit();

          var response = d.result;
          document.getElementById("log_zone").innerText = response.result;
          $("#input-progress")
            .val(100)
            .change();

          $("#result li").removeClass("error");
          $("#result li")
            .find("p")
            .text(response.result);
        },
        error: function(e, request) {
          clearInterval(progressInterval);
          cleanExit();
          console.log(e);
          console.log(request);
        }
      });
    },
    onDeny: function() {
      if (processed >= 1) {
        cancelUpdate();
      }
      processed += 1;
    }
  });
}

/**
 * Updates log_zone and installation progress.
 */
function updateOfflineProgress(url, auth) {
  var log_zone = $("log_zone");
  var general_progress = $("#input-progress");
  var general_label = $("#result li p");

  ajax({
    url: url,
    cors: auth,
    page: ajaxPage,
    data: {
      action: "status"
    },
    success: function(d) {
      $(general_progress)
        .val(d.result.percent)
        .change();

      general_label.innerText = d.result.processing;
      log_zone.innerText = d.result.message;
    },
    error: function(d) {
      log_zone.innerHTML = umErrorMsg(d.error);
    }
  });
}

/**
 * Cancel update.
 */
function cancelUpdate(reason = "") {
  console.error(reason);
  var taskStatusLogContainer = $("#result li");
  taskStatusLogContainer.addClass("error");
  taskStatusLogContainer.find("p").text(texts.rejectedUpdate + " " + reason);
  taskStatusLogContainer.find("span").on("click", function() {
    location.reload();
  });
}
