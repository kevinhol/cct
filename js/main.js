	var boostrapAlertTypes = [ 'success', 'info', 'warning', 'danger' ];
	var bootstrapIconTypes = [ 'glyphicon glyphicon-ok-sign',
			'glyphicon glyphicon-info-sign', 'glyphicon glyphicon-warning-sign',
			'glyphicon-exclamation-sign' ];
	
	$(document).ready(
	
	function() {
	
		$('#logoutBtn').click(function(){
			window.location.href="/logout";
		});
		
		initFormValidations();
	
		fireUrlRewrite()
	
		setupEditSubscriberListener();
	
		setupPeopleSearch();
	
		setupCommunitiesActionsListener();
	});
	
	function initFormValidations() {
	
		if (document.forms.length > 0) {
	
			var hasFormUsingValidation = false;
	
			$('input').each(
					function() {
						var attr = $(this).attr('data-validation');
	
						if (typeof attr !== typeof undefined && attr !== false) {
							console.log(attr.name);
							hasFormUsingValidation = true;
							console.log("found a form requiring validation: "
									+ hasFormUsingValidation);
							return false;
						}
					});
			console.log("continue fuction ");
			if (hasFormUsingValidation) {
				$.formUtils
						.addValidator({
							name : 'exactly26chars',
							validatorFunction : function(value, $el, config,
									language, $form) {
	
								return (value.length == 26) ? true : false;
							},
							errorMessage : 'The Client Id input value must be exactly 26 characters',
							errorMessageKey : 'badCharNumber'
						});
	
				$.formUtils
						.addValidator({
							name : 'appIdRegex',
							validatorFunction : function(value, $el, config,
									language, $form) {
								var pattern = new RegExp('app_[0-9]{8}_[0-9]{13}');
								return pattern.test(value);
							},
							errorMessage : 'The Client Id input is incorrect or contains illegal characters',
							errorMessageKey : 'badAppChars'
						});
	
				$.formUtils
						.addValidator({
							name : 'max256Chars',
							validatorFunction : function(value, $el, config,
									language, $form) {
								return (value.length <= 256) ? true : false;
							},
							errorMessage : 'The Client Secret  input value cannot exceed 256 characters',
							errorMessageKey : 'badCharNumber'
						});
	
				$.formUtils
						.addValidator({
							name : 'appSecRegex',
							validatorFunction : function(value, $el, config,
									language, $form) {
								var pattern = new RegExp('^[a-z0-9]+$');
								return pattern.test(value);
							},
							errorMessage : 'The Client Secret is incorrect or contains illegal characters',
							errorMessageKey : 'badSecChars'
						});
	
				// Initiate form validation
				console.log('calling validate() for form');
				$.validate({
					modules : 'security, sanitize toggleDisabled',
					disabledFormFilter : 'form.toggle-disabled'
				});
			}
		}
	}
	
	/* window url rewrite to remove alert message */
	function fireUrlRewrite() {
	
		// https://stackoverflow.com/questions/19491336/get-url-parameter-jquery-or-how-to-get-query-string-values-in-js
		$.urlParam = function(name) {
			var results = new RegExp('[\?&]' + name + '=([^&#]*)')
					.exec(window.location.href);
			if (results == null) {
				return null;
			} else {
				return decodeURI(results[1]) || 0;
			}
		}
	
		var msg = $.urlParam('msg');
		var msgType = $.urlParam('msgtype');
	
		if (msg != null) {
	
			var alertArrayIndex = $.inArray(msgType, boostrapAlertTypes);
			var glyphicon = bootstrapIconTypes[alertArrayIndex];
	
			if (msgType == null || alertArrayIndex == -1) {
				// default to info alert type
				msgType = boostrapAlertTypes[1];
				glyphicon = bootstrapIconTypes[1];
			}
	
			var alertIconHtml = "<span class='glyphicon " + glyphicon
					+ "' aria-hidden='true'></span>";
			var alertHtml = alertIconHtml + decodeURIComponent(msg);
	
			$('#dismissAlert').addClass("alert-" + msgType);
			$('#dismissAlertBody').html(alertHtml);
			$('#dismissAlert').show();
	
			var url = window.location.search.replace("msg=" + encodeURI(msg), "")
					.replace("msgtype=" + msgType, "");
	
			window.history.pushState({}, "", url);
		}
	}
	
	function setupEditSubscriberListener() {
	
		$('#subscriberListTable .subscriberOptions').each(function() {
	
			var row = $(this).closest('tr');
			var id = row.attr('data-subscriberId');
	
			$(this).html(buildsubscriberOptionsPopup(id, row));
		});
	
		$('#subscriberListTable .subscriberOptions .dropdown li a').on(
				'click',
				function() {
	
					var subscriberName = $(this).closest('tr').find("td").eq(1)
							.html();
	
					var arr = $(this).attr("data-action").split(":");
					var action = arr[0];
					var subscriberId = arr[1];
	
					window[action](subscriberId, subscriberName);
				});
	
	}
	
	function buildsubscriberOptionsPopup(id, row) {
	
		var state = row.find("td").eq(4).html();
		var html = '';
	
		html += "				<div class='dropdown'>"
		html += "					<div class='dropdown-toggle' data-toggle='dropdown'>"
		html += "					<i class='glyphicon glyphicon-option-vertical'></i>"
		html += "					</div>"
		html += "					<ul class='dropdown-menu'>"
		html += "						<li><a href='#' data-toggle='modal'  data-target='#basicModal' data-action='editUser:"
				+ id + "'>Edit Account</a></li>"
		if (adminID != id) {
			html += "						<li><a href='#' data-toggle='modal'  data-target='#basicModal'  data-action='deleteUser:"
					+ id + "'>Delete</a></li>";
			if (state == 'Active') {
				html += "						<li><a href='#' data-toggle='modal'  data-target='#basicModal' data-action='suspendUser:"
						+ id + "'>Suspend</a></li>";
			} else if (state == 'Suspended') {
				html += "						<li><a href='#' data-toggle='modal' data-target='#basicModal'  data-action='unsuspendUser:"
						+ id + "'>Unsuspend</a></li>";
			} else if (state == 'Trash') {
				html += "						<li><a href='#' data-toggle='modal' data-target='#basicModal'  data-action='restoreUser:"
						+ id + "'>Restore</a></li>";
			}
		}
		html += "						<li><a href='#' data-toggle='modal'  data-target='#basicModal' data-action='resetUserPwd:"
				+ id + "'>Reset Password</a></li>"
		html += "					</ul>"
		html += "				</div>";
	
		return html;
	}
	
	function deleteUser(id, name) {
		showModal("Delete user",
				"<strong>Action: </strong>Are you sure to delete user '" + name
						+ "'?", "Cancel", "", "btn-danger", id);
		addClauseToModal(createSoftDeleteHtml());
	
		var fullDelete = false;
	
		$('#deleteopts input').change(function() {
			fullDelete = $('#fullyDeleteUseropt').is(':checked')
			console.log("FD: " + fullDelete);
			if (fullDelete) {
				$('#deleteUserWarning').show();
			} else {
				$('#deleteUserWarning').hide();
			}
		});
	
		$('#confirmDialogAction')
				.click(
						function() {
	
							var alertType;
							var msg;
	
							var jqxhr = $
									.ajax({
										url : ajaxEndpointUrl,
										type : "POST",
										data : {
											"action" : "deleteUser",
											"subscriberId" : id,
											"fullDelete" : fullDelete
										},
										dataType : "text",
										success : function(resultData) {
											console.log(resultData);
											return;
	
											var result = $.parseJSON(resultData);
											if (result.success == true) {
												alertType = "alert-success";
												msg = "User "
														+ name
														+ " has been successfully deleted"
	
												// update the table cell with the
												// user state
												if (fullDelete) {
													$(
															"#subscriberListTable tr[data-subscriberid='"
																	+ id + "']")
															.eq(0).fadeOut();
												} else {
													$(
															"#subscriberListTable tr[data-subscriberid='"
																	+ id + "']")
															.eq(0).find('td').eq(4)
															.html("Trashed");
													// update the user options
													var actionLink = $("#subscriberListTable a[data-action='deleteUser:"
															+ id + "']");
													actionLink.html("Restore");
													actionLink.attr("data-action",
															"restoreUser:" + id);
	
												}
											} else {
												alertType = "alert-warning";
												msg = (typeof result.message !== 'undefined' && result.message.length > 0) ? result.message
														: "This action failed to process completely. An unknown error occurred";
	
												$('#basicModal .modal-body').eq(0)
														.html(
																buildAlert(
																		alertType,
																		msg));
												$('#basicModal .modal-footer')
														.eq(0).remove();
	
												if (result.action == 'logout') {
													setTimeout(
															function() {
																window.location.href = "/logout";
															}, 3000);
													return;
												}
											}
	
											$('#infoBar').html(
													buildAlert(alertType, msg));
											$('#basicModal button.close').click();
										},
										error : function(xhr, tst, err) {
											console.log(err);
											$('#infoBar').html();
											$('#basicModal .modal-body')
													.eq(0)
													.html(
															buildAlert(
																	"alert-warning",
																	"An unknown error occurred"));
											$('#basicModal .modal-footer').eq(0)
													.remove();
										}
									});
						});
	
	}
	
	function restoreUser(id, name) {
		showModal("Restore user",
				"<strong>Action: </strong>Are you sure to restore user '" + name
						+ "'?", "Cancel", "", "btn-info", id);
		$('#confirmDialogAction')
				.click(
						function() {
							var alertType;
							var msg;
	
							var jqxhr = $
									.ajax({
										url : ajaxEndpointUrl,
										type : "POST",
										data : {
											"action" : "restoreUser",
											"subscriberId" : id
										},
										dataType : "text",
										success : function(resultData) {
											console.log(resultData);
	
											var result = $.parseJSON(resultData);
											if (result.success == true) {
												alertType = "alert-success";
												msg = "User "
														+ name
														+ " has been successfully restored"
	
												// update the table cell with the
												// user state
												$(
														"#subscriberListTable tr[data-subscriberid='"
																+ id + "']").eq(0)
														.find('td').eq(4).html(
																"****");
	
												// update the user options
												// var actionLink =
												// $("#subscriberListTable
												// a[action='suspendUser:"+ id
												// +"']");
												// actionLink.html("Unsuspend");
												// actionLink.attr("action",
												// "unsuspendUser:"+ id);
											} else {
												alertType = "alert-warning";
												msg = (typeof result.message !== 'undefined' && result.message.length > 0) ? result.message
														: "This action failed to process completely. An unknown error occurred";
	
												$('#basicModal .modal-body').eq(0)
														.html(
																buildAlert(
																		alertType,
																		msg));
												$('#basicModal .modal-footer')
														.eq(0).remove();
	
												if (result.action == 'logout') {
													setTimeout(
															function() {
																window.location.href = "/logout";
															}, 3000);
													return;
												}
											}
	
											$('#infoBar').html(
													buildAlert(alertType, msg));
											$('#basicModal button.close').click();
										},
										error : function(xhr, tst, err) {
											console.log(err);
											$('#infoBar').html();
											$('#basicModal .modal-body')
													.eq(0)
													.html(
															buildAlert(
																	"alert-warning",
																	"An unknown error occurred"));
											$('#basicModal .modal-footer').eq(0)
													.remove();
										}
									});
						});
	
	}
	
	function editUser(id, name) {
		showModal("Edit user",
				"<strong>Action: </strong>Are you sure to edit user '" + name
						+ "'?", "Cancel", "", "btn-primary", id);
		$('#confirmDialogAction').click(function() {
			console.log("GO");
		});
	}
	
	function suspendUser(id, name) {
		showModal("Suspend user",
				"<strong>Action: </strong>Are you sure to suspend user '" + name
						+ "'?", "Cancel", "", "btn-primary", id);
	
		$('#confirmDialogAction')
				.click(
						function() {
							var alertType;
							var msg;
							var jqxhr = $
									.ajax({
										url : ajaxEndpointUrl,
										type : "POST",
										data : {
											"action" : "suspendUser",
											"subscriberId" : id
										},
										dataType : "text",
										success : function(resultData) {
	
											var result = $.parseJSON(resultData);
											if (result.success == true) {
												alertType = "alert-success";
												msg = "User "
														+ name
														+ " has been successfully suspended"
	
												// update the table cell with the
												// user state
												$(
														"#subscriberListTable tr[data-subscriberid='"
																+ id + "']").eq(0)
														.find('td').eq(4).html(
																"Suspended");
	
												// update the user options
												var actionLink = $("#subscriberListTable a[data-action='suspendUser:" + id + "']");
												actionLink.html("Unsuspend");
												actionLink.attr("data-action",
														"unsuspendUser:" + id);
											} else {
												alertType = "alert-warning";
												msg = (typeof result.message !== 'undefined' && result.message.length > 0) ? result.message
														: "This action failed to process completely. An unknown error occurred";
	
												$('#basicModal .modal-body').eq(0)
														.html(
																buildAlert(
																		alertType,
																		msg));
												$('#basicModal .modal-footer')
														.eq(0).remove();
	
												if (result.action == 'logout') {
													setTimeout(
															function() {
																window.location.href = "/logout";
															}, 3000);
													return;
												}
											}
	
											$('#infoBar').html(
													buildAlert(alertType, msg));
											$('#basicModal button.close').click();
										},
										error : function(xhr, tst, err) {
											console.log(err);
											$('#infoBar').html();
											$('#basicModal .modal-body')
													.eq(0)
													.html(
															buildAlert(
																	"alert-warning",
																	"An unknown error occurred"));
											$('#basicModal .modal-footer').eq(0)
													.remove();
										}
									});
						});
	}
	
	function unsuspendUser(id, name) {
		showModal("Unsuspend user",
				"<strong>Action: </strong>Are you sure to unsuspend user '" + name
						+ "'?", "Cancel", "", "btn-primary", id);
	
		$('#confirmDialogAction')
				.click(
						function() {
							var alertType;
							var msg;
							var jqxhr = $
									.ajax({
										url : ajaxEndpointUrl,
										type : "POST",
										data : {
											"action" : "unsuspendUser",
											"subscriberId" : id
										},
										dataType : "text",
										success : function(resultData) {
	
											var result = $.parseJSON(resultData);
											if (result.success == true) {
												alertType = "alert-success";
												msg = "User "
														+ name
														+ " has been successfully reactivated"
	
												// update the table cell with the
												// user state
												$(
														"#subscriberListTable tr[data-subscriberid='"
																+ id + "']").eq(0)
														.find('td').eq(4).html(
																"Active");
	
												// update the user options
												var actionLink = $("#subscriberListTable a[data-action='suspendUser:" + id + "']");
												actionLink.html("Suspend");
												actionLink.attr("data-action", "suspendUser:" + id);
											} else {
												alertType = "alert-warning";
												msg = (typeof result.message !== 'undefined' && result.message.length > 0) ? result.message
														: "This action failed to process completely. An unknown error occurred";
	
												$('#basicModal .modal-body').eq(0)
														.html(
																buildAlert(
																		alertType,
																		msg));
												$('#basicModal .modal-footer')
														.eq(0).remove();
	
												if (result.action == 'logout') {
													setTimeout(
															function() {
																window.location.href = "/logout";
															}, 3000);
													return;
												}
											}
	
											$('#infoBar').html(
													buildAlert(alertType, msg));
											$('#basicModal button.close').click();
										},
										error : function(xhr, tst, err) {
											console.log(err);
											$('#infoBar').html();
											$('#basicModal .modal-body')
													.eq(0)
													.html(
															buildAlert(
																	"alert-warning",
																	"An unknown error occurred"));
											$('#basicModal .modal-footer').eq(0)
													.remove();
										}
									});
						});
	}
	
	function showModal(action, msg, btn1text, btn2text, btn2style, ref_id) {
	
		var html = "";
		html += "						<div class='modal fade' id='basicModal' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'>";
		html += "							<div class='modal-dialog'>";
		html += "								<div class='modal-content'>";
		html += "						    		<div class='modal-header'>";
		html += "										<button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>";
		html += "										<h4 class='modal-title' id='myModalLabel'>" + action
				+ "</h4>";
		html += "									</div>";
		html += "								    <div class='modal-body'>";
		if (typeof msg !== 'undefined' && msg.length > 0) {
			html += "										<p>" + msg + "</p>";
		}
		html += "									</div>";
		html += "						        	<div class='modal-footer'>";
		if (typeof btn1text !== 'undefined' && btn1text.length > 0) {
			html += "										<button type='button' class='btn btn-default' data-dismiss='modal'>"
					+ btn1text + " </button>";
		}
		if (typeof btn2text !== 'undefined' && btn2text.length > 0) {
			html += "						    	        <a class='btn " + btn2style + " btn-ok'>"
					+ btn2text + "</a>";
		} else {
			html += "						    	        <a class='btn " + btn2style
					+ " btn-ok' id='confirmDialogAction'>Confirm</a>";
		}
		html += "									</div>";
		html += "								</div>";
		html += "							</div>";
		html += "						</div>";
	
		//remove any old modal
		$('#basicModal').remove();
		
		//add the new created modal
		$('body').append(html);
		
		// show it
		$('#basicModal').fadeIn();
	}
	
	function buildAlert(alertStyle, msgHtml) {
		// basic dismissable Modal
		return "<div class='alert "
				+ alertStyle
				+ "  alert-dismissible' role='alert'><button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>"
				+ msgHtml + "</div>";
	}
	
	function setupPeopleSearch() {
	
		if ($("#dataSearch").length) {
			var search = $("#dataSearch");
			var defaultTxt = search.attr("placeholder");
			var spinnerSpan = $('#spinnerSpan');
			var contentArea = $('#search-result-list');
			var srTotal = $('#sr-total');
			var loading = $("#loading-addon");
			var contentDiv = $('#search-popover-content');
			var msgErr = " An unknown error has occurred. ";
			var msgActFail = " This search action has failed. ";
	
			search
					.keyup(function() {
						spinnerSpan.removeClass("loader");
	
						contentArea.html('');
	
						var txt = search.val();
	
						if (txt.length >= 3) {
							spinnerSpan.addClass("loader");
	
							// do ajax call
							var jqxhr = $
									.ajax({
										url : ajaxEndpointUrl,
										type : "GET",
										data : {
											"action" : "searchUser",
											"dataString" : txt
										},
										dataType : "text",
										success : function(resultData) {
	
											spinnerSpan.removeClass("loader");
	
											var result = $.parseJSON(resultData);
	
											console.log(result);
	
											if (typeof result.success === 'undefined'
													|| result.success == false) {
	
												var msg = msgActFail;
	
												if (result.action == 'logout') {
													if (typeof result.message === 'undefined'
															|| result.message.length < 5) {
														msg += "You will be redirected to login in to the service";
													} else {
														msg += result.message;
													}
													setTimeout(
															function() {
																window.location.href = "/logout";
															}, 3000);
												} else {
													msg += msgErr;
												}
												contentArea.html(buildAlert(
														"alert-warning", msg));
												srTotal.html('0');
	
											} else if (result.success == true) {
												var totalResults = result.suggestions['totalResults'];
												srTotal.html(totalResults);
	
												var startIndex = result.suggestions.startIndex;
												var numResultsInCurrentPage = result.suggestions.numResultsInCurrentPage;
												var persons = result.suggestions.persons;
	
												contentArea.html(buildPeopleSearchResultsContent(persons, startIndex, numResultsInCurrentPage));
											}
	
										},
										error : function(xhr, tst, err) {
											contentArea.html(buildAlert(
													"alert-danger", msgActFail
															+ msgErr));
											spinnerSpan.removeClass("loader");
											srTotal.html('0');
											console.log(err);
										},
										complete : function() {
											contentDiv.fadeIn();
										}
									});
						} else {
							spinnerSpan.removeClass("loader");
	
							if (txt.length == 0) {
								search.attr("placeholder", defaultTxt);
								contentDiv.fadeOut();
							}
						}
	
					});
	
			search.blur(function() {
				spinnerSpan.removeClass("loader");
	
				if (search.val().length == 0) {
					search.attr("placeholder", defaultTxt);
					contentDiv.fadeOut();
				}
			});
	
			var closeBtn = $('#search-popover-content .close').eq(0);
	
			closeBtn.click(function() {
	
				contentDiv.fadeOut();
				search.val('');
				srTotal.html('0');
			});
		}
	}
	
	function buildPeopleSearchResultsContent(persons, startIndex, numResultsInCurrentPage) {
		var html = "";
		console.log(persons);
		if (persons.length > 0) {
			
			html += "<thead class='thead-light'> <tr> <th scope='col'></th> <th scope='col'>Name</th> <th scope='col'>Job</th> <th scope='col'>Email</th> <th scope='col'>Type</th> </tr> </thead>";
			html += "<tbody>";
	
			$.each(persons, function(i, item) {

								html += "<tr data-subscriberId='" + persons[i].id
										+ "'>"
										+ "<td scope='row' class='subscriberOptions'><i class='glyphicon glyphicon-option-vertical'>&nbsp;</i></td>"
										+ "<td class='name'>" + persons[i].name
										+ "</td>" + "<td class='job'>"
										+ persons[i].jobResponsibility + "</td>"
										+ "<td class='email'>" + persons[i].email
										+ "</td>" + "<td class='usertype'>"
										+ persons[i].userType + "</td>" + "</tr>";
								console.log(persons[i]);
							});
	
			html += "</tbody>";
		}
		return html;
	}
	
	function createSoftDeleteHtml() {
		var html = ""
				+ "<div class='checkbox' id='deleteopts'> "
				+ "<label class='radio'><input type='radio' name='optradio' checked='checked'>Move to Trash</label>"
				+ " </br> "
				+ "<label class='radio'><input type='radio' name='optradio' id='fullyDeleteUseropt'>Fully delete user</label>"
				+ "<div class='alert alert-danger' role='alert' id='deleteUserWarning'>"
				+ "	<p>"
				+ "		<strong> "
				+ "			<span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span> This will permantely delete this account. "
				+ "			The account cannot be restored after." + "		</strong>"
				+ "	</p>" + "</div>" + " </div>";
		return html;
	}
	
	function createExportMembersOptionsHtml() {
		var html = ""
				+ "<div class='checkbox' id='memberopts'> "
				+ "<label><input type='checkbox' name='csvFields[]' value='owner' checked='checked'>Owners</label>"
				+ " </br> "
				+ "<label><input type='checkbox' name='csvFields[]' value='member' checked='checked'>Members</label>"
				+ " </br> "
				+ "<label><input type='checkbox' name='csvFields[]' value='email' checked=''>Email addresses</label>"
				+ " </br> "
				+ "<label><input type='checkbox' name='csvFields[]' value='userState' checked=''>User state</label>"
				+ " </br> "
				+ "<label><input type='checkbox' name='csvFields[]' value='userid' checked=''>User ID</label>"
				+ " </br> "
				+ "<label><input type='checkbox' name='csvFields[]' value='isExternal' checked='checked'>External Member flag</label>"
				+ " </br> "
				+ " </div>";
		return html;
	}


	function addClauseToModal(html) {
		$('#basicModal .modal-body').append(html);
	}
	
	function setupCommunitiesActionsListener() {
	
		$('#communitiesListTable .communityOptions').each(function() {
	
			var row = $(this).closest('tr');
			var id = row.attr('data-communityUuid');
			
			$(this).html(buildCommunityOptionsPopup(id, row));
		});
	
		// $('#communitiesListTable .communityOptions .dropdown li a').on('click',
		// function() {
		//		
		// var commName = $(this).closest('tr').find("h4 a").eq(0).html();
		// console.log(commName);
		// var arr = $(this).attr("action").split(":");
		// console.log(arr);
		// var action = arr[0];
		// var commId = arr[1];
		//		
		// window[action](commId, commName);
		// });
	
		$('#exportCommMembersCSVbtn').click(function() {

			var arr = $(this).attr('data-action').split(":");
			var action = arr[0];
			var commId = arr[1];
			
			showModal("Export Community Members", "<strong>Action: </strong>Please select fields to include", "Cancel", "", "btn-primary", commId);
			
			addClauseToModal(createExportMembersOptionsHtml());

			$('#confirmDialogAction').click(function() {
				
				var fields = $('#memberopts input:checked').map(function(){
					return $(this).val();
			    }).get().join( "," );

				
				var url = "/downloadCSV?action=" + action + "&commUuid=" + commId + "&fields=" + fields;
				
				console.log(url)
				window.location.href = url;

				$("#basicModal .close").click();
			});
		});
			
		$('#exportCommCSVbtn').click(function() {

			var arr = $(this).attr('data-action').split(":");
			var action = arr[0];
			var commId = arr[1];
			
			showModal("Export Community List", "<strong>Action: </strong>Please select fields to include", "Cancel", "", "btn-primary", commId);
			
			addClauseToModal(createExportMembersOptionsHtml());

			$('#confirmDialogAction').click(function() {
				
				var fields = $('#memberopts input:checked').map(function(){
					return $(this).val();
			    }).get().join( "," );

				
				var url = "/downloadCSV?action=" + action + "&commUuid=" + commId + "&fields=" + fields;
				
				console.log(url)
				window.location.href = url;

				$("#basicModal .close").click();
			});

		});
	
	}
	
	function buildCommunityOptionsPopup(id, row) {
	
		var html = '';
	
		html += "				<div class='dropdown'>"
		html += "					<div class='dropdown-toggle' data-toggle='dropdown'>"
		html += "					<i class='glyphicon glyphicon-option-vertical'></i>"
		html += "					</div>"
		html += "					<ul class='dropdown-menu'>"
		html += "						<li><a href='/Community/Members/" + id + "'>View Members</a></li>"
		html += "						<li><a href='/Community/Sub/" + id + "'>View Subcommunities</a></li>"
		html += "						<li><a href='/Community/Events/" + id + "'>View Events</a></li>"
		html += "					</ul>"
		html += "				</div>";
	
		return html;
	}
	
	
	function updateProgress(percentage) {
		if (percentage > 100)
			percentage = 100;
		$('#progressBar').css('width', percentage + '%');
		$('#progressBar').html(percentage + '%');
	}
	
