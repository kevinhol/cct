$.formUtils.addValidator({
	name : 'exactly26chars',
	validatorFunction : function(value, $el, config, language, $form) {

		return (value.length == 26) ? true : false;
	},
	errorMessage : 'The Client Id input value must be exactly 26 characters',
	errorMessageKey : 'badCharNumber'
});

$.formUtils
		.addValidator({
			name : 'appIdRegex',
			validatorFunction : function(value, $el, config, language, $form) {
				var pattern = new RegExp('app_[0-9]{8}_[0-9]{13}');
				return pattern.test(value);
			},
			errorMessage : 'The Client Id input is incorrect or contains illegal characters',
			errorMessageKey : 'badAppChars'
		});

$.formUtils
		.addValidator({
			name : 'max256Chars',
			validatorFunction : function(value, $el, config, language, $form) {
				return (value.length <= 256) ? true : false;
			},
			errorMessage : 'The Client Secret  input value cannot exceed 256 characters',
			errorMessageKey : 'badCharNumber'
		});

$.formUtils
		.addValidator({
			name : 'appSecRegex',
			validatorFunction : function(value, $el, config, language, $form) {
				var pattern = new RegExp('^[a-z0-9]+$');
				return pattern.test(value);
			},
			errorMessage : 'The Client Secret is incorrect or contains illegal characters',
			errorMessageKey : 'badSecChars'
		});

// Initiate form validation
$.validate();

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

var boostrapAlertTypes = [ 'success', 'info', 'warning', 'danger' ];
var bootstrapIconTypes = [ 'glyphicon glyphicon-ok-sign',
		'glyphicon glyphicon-info-sign', 'glyphicon glyphicon-warning-sign',
		'glyphicon-exclamation-sign' ];

/* window url rewrite to remove alert message */
$(document).ready(
		function() {

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

				var url = window.location.search.replace(
						"msg=" + encodeURI(msg), "").replace(
						"&msgtype=" + msgType, "");

				window.history.pushState({}, "", url);
			}
		});