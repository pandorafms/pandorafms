var correct_install_progress = true;

function form_upload (homeurl) {
	var home_url = (typeof homeurl !== 'undefined') ? homeurl + '/' : '';
	
	//Thanks to: http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/
	var ul = $('#form-offline_update ul');
	
	$('#form-offline_update div')
		.prop("id", "drop_file");
	$('#drop_file')
		.html(drop_the_package_here_or +
			'&nbsp;&nbsp;&nbsp;<a>' + browse_it +'</a>' +
			'<input name="upfile" type="file" id="file-upfile" accept=".oum" class="sub file" />');
	$('#drop_file a').click(function() {
		// Simulate a click on the file input button to show the file browser dialog
		$(this).parent().find('input').click();
	});
	
	// Initialize the jQuery File Upload plugin
	$('#form-offline_update').fileupload({
		
		url: home_url + 'ajax.php?page=include/ajax/update_manager.ajax&upload_file=true',
		
		// This element will accept file drag/drop uploading
		dropZone: $('#drop_file'),
		
		// This function is called when a file is added to the queue;
		// either via the browse button, or via drag/drop:
		add: function (e, data) {
			$('#drop_file').slideUp();
			
			var tpl = $('<li>' +
					'<input type="text" id="input-progress" ' +
						'value="0" data-width="55" data-height="55" '+
						'data-fgColor="#80BA27" data-readOnly="1" ' +
						'data-bgColor="#3E4043" />' +
					'<p></p><span></span>' +
				'</li>');
			
			// Append the file name and file size
			tpl.find('p').text(data.files[0].name)
						.append('<i>' + formatFileSize(data.files[0].size) + '</i>');
			
			// Add the HTML to the UL element
			ul.html("");
			data.context = tpl.appendTo(ul);
			
			// Initialize the knob plugin
			tpl.find('input').val(0);
			tpl.find('input').knob({
				'draw' : function () {
					$(this.i).val(this.cv + '%')
				}
			});
			
			// Listen for clicks on the cancel icon
			tpl.find('span').click(function() {
				
				if (tpl.hasClass('working') && typeof jqXHR != 'undefined') {
					jqXHR.abort();
				}
				
				tpl.fadeOut(function() {
					tpl.remove();
					$('#drop_file').slideDown();
				});
				
			});
			
			// Automatically upload the file once it is added to the queue
			data.context.addClass('working');
			var jqXHR = data.submit();
		},
		
		progress: function(e, data) {
			
			// Calculate the completion percentage of the upload
			var progress = parseInt(data.loaded / data.total * 100, 10);
			
			// Update the hidden input field and trigger a change
			// so that the jQuery knob plugin knows to update the dial
			data.context.find('input').val(progress).change();
			
			if (progress == 100) {
				data.context.removeClass('working');
				// Class loading while the zip is extracted
				data.context.addClass('loading');
			}
		},
		
		fail: function(e, data) {
			// Something has gone wrong!
			data.context.removeClass('working');
			data.context.removeClass('loading');
			data.context.addClass('error');
		},
		
		done: function (e, data) {
			
			var res = JSON.parse(data.result);
			
			if (res.status == "success") {
				data.context.removeClass('loading');
				data.context.addClass('suc');
				
				ul.find('li').find('span').unbind("click");
				
				// Transform the file input zone to show messages
				$('#drop_file').prop('id', 'log_zone');
				
				// Success messages
				$('#log_zone').html("<div>" + the_package_has_been_uploaded_successfully + "</div>");
				$('#log_zone').append("<div>" + remember_that_this_package_will + "</div>");
				$('#log_zone').append("<div>" + click_on_the_file_below_to_begin + "</div>");
				
				// Show messages
				$('#log_zone').slideDown(400, function() {
					$('#log_zone').height(75);
					$('#log_zone').css("overflow", "auto");
				});
				
				// Bind the the begin of the installation to the package li
				ul.find('li').css("cursor", "pointer");
				ul.find('li').click(function () {
					
					ul.find('li').unbind("click");
					ul.find('li').css("cursor", "default");
					
					// Change the log zone to show the copied files
					$('#log_zone').html("");
					$('#log_zone').slideUp(200, function() {
						$('#log_zone').slideDown(200, function() {
							$('#log_zone').height(200);
							$('#log_zone').css("overflow", "auto");
						});
					});
					
					// Changed the data that shows the file li
					data.context.find('p').text(updating + "...");
					data.context.find('input').val(0).change();
					
					// Begin the installation
					install_package(res.package, homeurl);
				});
			}
			else {
				// Something has gone wrong!
				data.context.removeClass('loading');
				data.context.addClass('error');
				ul.find('li').find('span').click(
					function() { window.location.reload(); });
				
				// Transform the file input zone to show messages
				$('#drop_file').prop('id', 'log_zone');
				
				// Error messages
				$('#log_zone').html("<div>"+res.message+"</div>");
				
				// Show error messages
				$('#log_zone').slideDown(400, function() {
					$('#log_zone').height(75);
					$('#log_zone').css("overflow", "auto");
				});
			}
		}
		
	});
	
	// Prevent the default action when a file is dropped on the window
	$(document).on('drop_file dragover', function (e) {
		e.preventDefault();
	});
}

// Helper function that formats the file sizes
function formatFileSize(bytes) {
	if (typeof bytes !== 'number') {
		return '';
	}
	
	if (bytes >= 1000000000) {
		return (bytes / 1000000000).toFixed(2) + ' GB';
	}
	
	if (bytes >= 1000000) {
		return (bytes / 1000000).toFixed(2) + ' MB';
	}
	
	return (bytes / 1000).toFixed(2) + ' KB';
}

function install_package (package, homeurl) {
	var home_url = (typeof homeurl !== 'undefined') ? homeurl + '/' : '';
	
	$("<div id='pkg_apply_dialog' class= 'dialog ui-dialog-content'></div>").dialog ({
		resizable: true,
		draggable: true,
		modal: true,
		overlay: {
			opacity: 0.5,
			background: 'black'
		},
		width: 600,
		height: 250,
		buttons: {
			"Apply package": function () {
				$("#pkg_apply_dialog").dialog("close");

				var parameters = {};
				parameters['page'] = 'include/ajax/update_manager.ajax';
				parameters['search_minor'] = 1;
				
				$.ajax({
					type: 'POST',
					url: home_url + 'ajax.php',
					data: parameters,
					dataType: "json",
					success: function (data) {
						if (data['have_minor']) {
							$("<div id='mr_dialog2' class='dialog ui-dialog-content' title='Menor release available'></div>").dialog ({
								resizable: true,
								draggable: true,
								modal: true,
								overlay: {
									opacity: 0.5,
									background: 'black'
								},
								width: 600,
								height: 270,
								buttons: {
									"Apply minor releases": function () {
										var no_error = apply_minor_release(data['mr']);
										$("#apply_rr_button").remove();
										$("#cancel_rr_button").remove();
										if (no_error) {
											var parameters = {};
											parameters['page'] = 'include/ajax/update_manager.ajax';
											parameters['install_package'] = 1;
											parameters['package'] = package;
											parameters['accept'] = 1;
											
											$('#form-offline_update ul').find('li').removeClass('suc');
											$('#form-offline_update ul').find('li').addClass('loading');
											
											$.ajax({
												type: 'POST',
												url: home_url + 'ajax.php',
												data: parameters,
												dataType: "json",
												success: function (data) {
													$('#form-offline_update ul').find('li').removeClass('loading');
													if (data.status == "success") {
														$('#form-offline_update ul').find('li').addClass('suc');
														$('#form-offline_update ul').find('li').find('p').html(package_updated_successfully)
															.append("<i>" + if_there_are_any_database_change + "</i>");
													}
													else {
														$('#form-offline_update ul').find('li').addClass('error');
														$('#form-offline_update ul').find('li').find('p').html(package_not_updated)
															.append("<i>"+data.message+"</i>");
													}
													$('#form-offline_update ul').find('li').css("cursor", "pointer");
													$('#form-offline_update ul').find('li').click(function() {
														window.location.reload();
													});
												}
											});
											
											// Check the status of the update
											check_install_package(package, homeurl);
										}
										else {
											$('#form-offline_update ul').find('li').addClass('error');
											$('#form-offline_update ul').find('li').find('p').html(error_in_mr)
												.append("<i>"+data.message+"</i>");
										}
									},
									"Cancel": function () {
										$("#mr_dialog2").dialog("close");

										$("<div id='cancel_mr' class='dialog ui-dialog-content' title='Menor release available'></div>").dialog ({
											resizable: true,
														draggable: true,
														modal: true,
														overlay: {
															opacity: 0.5,
															background: 'black'
														},
														width: 600,
														height: 220,
														buttons: {
															"Ok": function () {
																$(this).dialog("close");
															}
														}
										});

										var dialog_cancel_mr_text = "<div>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>INFO</strong></h3>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "<p style='font-family:Verdana; font-size:12pt;'>These database changes will not apply.</p></div>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "</div>";
										
										$('#cancel_mr').html(dialog_cancel_mr_text);
										$('#cancel_mr').dialog('open');

										$("#box_online .loading").hide();
										$("#box_online .downloading_package").hide();
										$("#box_online .content").html("MR not accepted");
										$('#form-offline_update ul').find('li').addClass('error');
										$('#form-offline_update ul').find('li').find('p').html(error_in_mr_accept)
											.append("<i>"+data.message+"</i>");
									}
								}
							});

							$('button:contains(Apply MR)').attr("id","apply_rr_button");
							$('button:contains(Cancel)').attr("id","cancel_rr_button");
							
							var dialog_have_mr_mr_text = "<div>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>There are a DB changes</strong></h3>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<p style='font-family:Verdana; font-size:12pt;'>There are a new database changes available to apply. Do you want to start the DB update process?</p>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<p style='font-family:Verdana; font-size:12pt;'>We recommend launch a <a style='font-family:Verdana bold; font-size:12pt; color:#82B92E'href=\"index.php?sec=extensions&sec2=godmode/agentes/planned_downtime.list\">Planned downtime</a> to this process</p></div>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "</div>";
														
							$('#mr_dialog2').html(dialog_have_mr_mr_text);
							$('#mr_dialog2').dialog('open');
						}
						else {
							$("#pkg_apply_dialog").dialog("close");

							var parameters = {};
							parameters['page'] = 'include/ajax/update_manager.ajax';
							parameters['install_package'] = 1;
							parameters['package'] = package;
							parameters['accept'] = 1;
							
							$('#form-offline_update ul').find('li').removeClass('suc');
							$('#form-offline_update ul').find('li').addClass('loading');
							
							$.ajax({
								type: 'POST',
								url: home_url + 'ajax.php',
								data: parameters,
								dataType: "json",
								success: function (data) {
									$('#form-offline_update ul').find('li').removeClass('loading');
									if (data.status == "success") {
										$('#form-offline_update ul').find('li').addClass('suc');
										$('#form-offline_update ul').find('li').find('p').html(package_updated_successfully)
											.append("<i>" + if_there_are_any_database_change + "</i>");
									}
									else {
										$('#form-offline_update ul').find('li').addClass('error');
										$('#form-offline_update ul').find('li').find('p').html(package_not_updated)
											.append("<i>"+data.message+"</i>");
									}
									$('#form-offline_update ul').find('li').css("cursor", "pointer");
									$('#form-offline_update ul').find('li').click(function() {
										window.location.reload();
									});
								}
							});
							
							// Check the status of the update
							check_install_package(package, homeurl);
						}
					}
				});
			},
			"Cancel": function () {
				$(this).dialog("close");

				$("<div id='cancel_pkg' class='dialog ui-dialog-content' title='Menor release available'></div>").dialog ({
					resizable: true,
								draggable: true,
								modal: true,
								overlay: {
									opacity: 0.5,
									background: 'black'
								},
								width: 600,
								height: 220,
								buttons: {
									"Ok": function () {
										$(this).dialog("close");
									}
								}
				});

				var dialog_cancel_pkg_text = "<div>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>INFO</strong></h3>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "<p style='font-family:Verdana; font-size:12pt;'>These changes will not apply.</p></div>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "</div>";

				var parameters = {};
				parameters['page'] = 'include/ajax/update_manager.ajax';
				parameters['install_package'] = 1;
				parameters['package'] = package;
				parameters['accept'] = 0;
				
				$('#form-offline_update ul').find('li').removeClass('suc');
				$('#form-offline_update ul').find('li').addClass('loading');
				
				$.ajax({
					type: 'POST',
					url: home_url + 'ajax.php',
					data: parameters,
					dataType: "json",
					success: function (data) {
						$('#form-offline_update ul').find('li').removeClass('loading');
						if (data.status == "success") {
							$('#form-offline_update ul').find('li').addClass('suc');
							$('#form-offline_update ul').find('li').find('p').html(package_updated_successfully)
								.append("<i>" + if_there_are_any_database_change + "</i>");
						}
						else {
							$('#form-offline_update ul').find('li').addClass('error');
							$('#form-offline_update ul').find('li').find('p').html(package_not_updated)
								.append("<i>"+data.message+"</i>");
						}
						$('#form-offline_update ul').find('li').css("cursor", "pointer");
						$('#form-offline_update ul').find('li').click(function() {
							window.location.reload();
						});
					}
				});
				
				// Check the status of the update
				check_install_package(package, homeurl);
			}
		}
	});

	var dialog_text = "<div>";
	dialog_text = dialog_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
	dialog_text = dialog_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>There are a new update available</strong></h3>";
	dialog_text = dialog_text + "<p style='font-family:Verdana; font-size:12pt;'>There are a new update available to apply. Do you want to start the update process?</p></div>";
	dialog_text = dialog_text + "</div>";
	
	$('#pkg_apply_dialog').html(dialog_text);
	$('#pkg_apply_dialog').dialog('open');
}

function check_install_package(package, homeurl) {
	var home_url = (typeof homeurl !== 'undefined') ? homeurl += '/' : '';
	
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['check_install_package'] = 1;
	parameters['package'] = package;
	
	$.ajax({
		type: 'POST',
		url: home_url + 'ajax.php',
		data: parameters,
		dataType: "json",
		success: function(data) {
			// Print the updated files and take the scroll to the bottom
			$("#log_zone").html(data.info);
			$("#log_zone").scrollTop($("#log_zone").prop("scrollHeight"));
			
			// Change the progress bar
			if ($('#form-offline_update ul').find('li').hasClass('suc')) {
				$('#form-offline_update').find('ul').find('li').find('input').val(100).trigger('change');
			} else {
				$('#form-offline_update').find('ul').find('li').find('input').val(data['progress']).trigger('change');
			}
			
			// The class loading is present until the update ends
			var isInstalling = $('#form-offline_update ul').find('li').hasClass('loading');
			if (data.progress < 100 && isInstalling) {
				// Recursive call to check the update status
				check_install_package(package, homeurl);
			}
		}
	})
}

function check_online_free_packages(homeurl) {
	var home_url = (typeof homeurl !== 'undefined') ? homeurl + '/' : '';
	
	$("#box_online .checking_package").show();
	
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['check_online_free_packages'] = 1;
	
	jQuery.post(
		home_url + "ajax.php",
		parameters,
		function (data) {
			$("#box_online .checking_package").hide();
			
			$("#box_online .loading").hide();
			$("#box_online .content").html(data);
		},
		"html"
	);
}

function update_last_package(package, version, homeurl) {
	var home_url = (typeof homeurl !== 'undefined') ? homeurl + '/' : '';
	
	version_update = version;
	
	$("#box_online .content").html("");
	$("#box_online .loading").show();
	$("#box_online .download_package").show();
	
	$("<div id='pkg_apply_dialog' class= 'dialog ui-dialog-content'></div>").dialog ({
		resizable: true,
		draggable: true,
		modal: true,
		overlay: {
			opacity: 0.5,
			background: 'black'
		},
		width: 600,
		height: 250,
		buttons: {
			"OK": function () {
				$(this).dialog("close");

				var parameters = {};
				parameters['page'] = 'include/ajax/update_manager.ajax';
				parameters['search_minor'] = 1;
				
				jQuery.post(
					home_url + "ajax.php",
					parameters,
					function (data) {
						$("#box_online .loading").hide();
						$("#box_online .downloading_package").hide();
						
						if (data['have_minor']) {
							$("<div id='mr_dialog2' class='dialog ui-dialog-content' title='Menor release available'></div>").dialog ({
								resizable: true,
								draggable: true,
								modal: true,
								overlay: {
									opacity: 0.5,
									background: 'black'
								},
								width: 600,
								height: 270,
								buttons: {
									"Apply MR": function () {
										var no_error = apply_minor_release(data['mr']);

										if (no_error) {
											var parameters2 = {};
											parameters2['page'] = 'include/ajax/update_manager.ajax';
											parameters2['update_last_free_package'] = 1;
											parameters2['package'] = package;
											parameters2['version'] = version;
											parameters2['accept'] = 1;
											
											jQuery.post(
												home_url + "ajax.php",
												parameters2,
												function (data) {
													if (data['in_progress']) {
														$("#box_online .loading").hide();
														$("#box_online .download_package").hide();
														
														$("#box_online .content").html(data['message']);
														
														install_free_package(package, version, homeurl);
														setTimeout(function () {
															check_progress_update(homeurl);	
														}, 1000);
													}
													else {
														$("#box_online .content").html(data['message']);
													}
												},
												"json"
											);
										}
										else {
											$("#box_online .content").html("Error in MR file");
										}
									},
									"Cancel": function () {
										$(this).dialog("close");

										$("<div id='cancel_mr' class='dialog ui-dialog-content' title='Menor release available'></div>").dialog ({
											resizable: true,
														draggable: true,
														modal: true,
														overlay: {
															opacity: 0.5,
															background: 'black'
														},
														width: 600,
														height: 220,
														buttons: {
															"Ok": function () {
																$(this).dialog("close");
															}
														}
										});

										var dialog_cancel_mr_text = "<div>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>INFO</strong></h3>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "<p style='font-family:Verdana; font-size:12pt;'>These database changes will not apply.</p></div>";
										dialog_cancel_mr_text = dialog_cancel_mr_text + "</div>";
										
										$('#cancel_mr').html(dialog_cancel_mr_text);
										$('#cancel_mr').dialog('open');

										$("#box_online .loading").hide();
										$("#box_online .downloading_package").hide();
										$("#box_online .content").html("MR not accepted");
									}
								}
							});

							$('button:contains(Apply MR)').attr("id","apply_rr_button");
							$('button:contains(Cancel)').attr("id","cancel_rr_button");
							
							var dialog_have_mr_mr_text = "<div>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>There are a DB changes</strong></h3>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<p style='font-family:Verdana; font-size:12pt;'>There are a new database changes available to apply. Do you want to start the DB update process?</p>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "<p style='font-family:Verdana; font-size:12pt;'>We recommend launch a <a style='font-family:Verdana bold; font-size:12pt; color:#82B92E'href=\"index.php?sec=extensions&sec2=godmode/agentes/planned_downtime.list\">Planned downtime</a> to this process</p></div>";
							dialog_have_mr_mr_text = dialog_have_mr_mr_text + "</div>";
														
							$('#mr_dialog2').html(dialog_have_mr_mr_text);
							$('#mr_dialog2').dialog('open');
						}
						else {
							var parameters2 = {};
							parameters2['page'] = 'include/ajax/update_manager.ajax';
							parameters2['update_last_free_package'] = 1;
							parameters2['package'] = package;
							parameters2['version'] = version;
							parameters2['accept'] = 1;
							
							jQuery.post(
								home_url + "ajax.php",
								parameters2,
								function (data) {
									if (data['in_progress']) {
										$("#box_online .loading").hide();
										$("#box_online .download_package").hide();
										
										$("#box_online .content").html(data['message']);
										
										install_free_package(package, version, homeurl);
										setTimeout(function () {
											check_progress_update(homeurl);	
										}, 1000);
									}
									else {
										$("#box_online .content").html(data['message']);
									}
								},
								"json"
							);
						}
					}
				);
			},
			"Cancel": function () {
				$(this).dialog("close");

				$("<div id='cancel_pkg' class='dialog ui-dialog-content' title='Menor release available'></div>").dialog ({
					resizable: true,
								draggable: true,
								modal: true,
								overlay: {
									opacity: 0.5,
									background: 'black'
								},
								width: 600,
								height: 220,
								buttons: {
									"Ok": function () {
										$(this).dialog("close");
									}
								}
				});

				var dialog_cancel_pkg_text = "<div>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>INFO</strong></h3>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "<p style='font-family:Verdana; font-size:12pt;'>These changes will not apply.</p></div>";
				dialog_cancel_pkg_text = dialog_cancel_pkg_text + "</div>";
				
				$('#cancel_pkg').html(dialog_cancel_pkg_text);
				$('#cancel_pkg').dialog('open');

				var parameters = {};
				parameters['page'] = 'include/ajax/update_manager.ajax';
				parameters['update_last_free_package'] = 1;
				parameters['package'] = package;
				parameters['version'] = version;
				parameters['accept'] = 0;
				
				jQuery.post(
					home_url + "ajax.php",
					parameters,
					function (data) {
						if (data['in_progress']) {
							$("#box_online .loading").hide();
							$("#box_online .download_package").hide();
							
							$("#box_online .content").html(data['message']);
							
							install_free_package(package, version, homeurl);
							setTimeout(function () {
								check_progress_update(homeurl);	
							}, 1000);
						}
						else {
							$("#box_online .content").html(data['message']);
						}
					},
					"json"
				);
			}
		}
	});

	var dialog_text = "<div>";
	dialog_text = dialog_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_info_mr.png'></div>";
	dialog_text = dialog_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>There are a new update available</strong></h3>";
	dialog_text = dialog_text + "<p style='font-family:Verdana; font-size:12pt;'>There are a new update available to apply. Do you want to start the update process?</p></div>";
	dialog_text = dialog_text + "</div>";
	
	$('#pkg_apply_dialog').html(dialog_text);
	$('#pkg_apply_dialog').dialog('open');
}

function check_progress_update(homeurl) {
	var home_url = (typeof homeurl !== 'undefined') ? homeurl + '/' : '';
	
	if (stop_check_progress) {
		return;
	}
	
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['check_update_free_package'] = 1;
	
	jQuery.post(
		home_url + "ajax.php",
		parameters,
		function (data) {
			if (stop_check_progress) {
				return;
			}
			
			if (data['correct']) {
				if (data['end']) {
					//$("#box_online .content").html(data['message']);
				}
				else {
					$("#box_online .progressbar").show();
					
					$("#box_online .progressbar .progressbar_img").attr('src',
						data['progressbar']);
					
					setTimeout(function () {
						check_progress_update(homeurl);	
					}, 1000);
				}
			}
			else {
				correct_install_progress = false;
				$("#box_online .content").html(data['message']);
			}
		},
		"json"
	);
}

function install_free_package(package, version, homeurl) {
	var home_url = (typeof homeurl !== 'undefined') ? homeurl + '/' : '';
	
	var parameters = {};
	parameters['page'] = 'include/ajax/update_manager.ajax';
	parameters['install_free_package'] = 1;
	parameters['package'] = package;
	parameters['version'] = version;
	
	jQuery.ajax ({
		data: parameters,
		type: 'POST',
		url: home_url + "ajax.php",
		timeout: 600000,
		dataType: "json",
		error: function(data) {
			correct_install_progress = false;
			stop_check_progress = 1;
			
			$("#box_online .loading").hide();
			$("#box_online .progressbar").hide();
			$("#box_online .content").html(unknown_error_update_manager);
		},
		success: function (data) {
			if (correct_install_progress) {
				if (data["status"] == "success") {
					$("#box_online .loading").hide();
					$("#box_online .progressbar").hide();
					$("#box_online .content").html(data['message']);
					stop_check_progress = 1;
				}
				else {
					$("#box_online .loading").hide();
					$("#box_online .progressbar").hide();
					$("#box_online .content").html(data['message']);
					stop_check_progress = 1;
				}
			}
			else {
				stop_check_progress = 1;
			}
		}
	});
}

function apply_minor_release (n_mr) {
	var error = false;
	$('#mr_dialog2').empty();
	$.each(n_mr, function(i, mr) {
		var params = {};
		params["updare_rr"] = 1;
		params["number"] = mr;
		params["page"] = "include/ajax/rolling_release.ajax";

		jQuery.ajax ({
			data: params,
			async: false,
			dataType: "html",
			type: "POST",
			url: "ajax.php",
			success: function (data) {
				$('#mr_dialog2').append("</div style='max-height:50px'>");
				if (data != "") {
					$('#mr_dialog2').empty();
					$('#mr_dialog2').html(data);
					error = true;
				}
				else {
					$('#mr_dialog2').append("<p style='font-family:Verdana; font-size:12pt;'>- Applying DB MR #" + mr + "</p>");
				}
			}
		});
		
		if (error) {
			return false;
		}
	});
	$('#mr_dialog2').append("</div>");
	$(".ui-dialog-buttonset").empty();

	if (error) {
		return false;
	}
	else{
		$('#mr_dialog2').empty();
		var dialog_ok_mr_text = "<div>";
		dialog_ok_mr_text = dialog_ok_mr_text + "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_exito_mr.png'></div>";
		dialog_ok_mr_text = dialog_ok_mr_text + "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>SUCCESS</strong></h3>";
		dialog_ok_mr_text = dialog_ok_mr_text + "<p style='font-family:Verdana; font-size:12pt;'>Updated finished successfully.</p></div>";
		dialog_ok_mr_text = dialog_ok_mr_text + "</div>";
		$('#mr_dialog2').html(dialog_ok_mr_text);

		return true;
	}
}