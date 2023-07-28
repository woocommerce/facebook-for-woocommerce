/**
 * Copyright ( c ) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package
 */

jQuery(window).on('load', function () {
	const $ = jQuery;
	const viewItemsIdPrefix = 'woocommerce-facebook-settings-advertise-asc-';
	const ascTypeRetargeting = 'retargeting';
	const ascTypeNewBuyers = 'new-buyers';
	const defaultValues = {};
	const changes = {};
	const currentView = {};

	currentView[ascTypeNewBuyers] = false;
	currentView[ascTypeRetargeting] = false;

	function readDefaultValues() {
		defaultValues[ascTypeRetargeting] =
			readCurrentValues(ascTypeRetargeting);
		defaultValues[ascTypeNewBuyers] = readCurrentValues(ascTypeNewBuyers);
	}

	function showBusyIndicator(view, type) {
		$('#' + view + '-' + type + '-busy-indicator').addClass(
			'busy-indicator'
		);
	}

	function hideBusyIndicator(view, type) {
		$('#' + view + '-' + type + '-busy-indicator').removeClass(
			'busy-indicator'
		);
	}

	function createModal(link) {
		new $.WCBackboneModal.View({
			target: 'facebook-for-woocommerce-modal',
			string: {
				message: '<iframe src="' + link + '" ></iframe>',
			},
		});
	}

	function readCurrentValues(view) {
		try {
			let isValid = true;

			const prefix = viewItemsIdPrefix + view + '-';
			const p1 = $('#' + prefix + 'p1-input').is(':checked');

			let element = $('#' + prefix + 'p2');
			element
				.get(0)
				.setAttribute('aria-invalid', !element.get(0).checkValidity());
			isValid = isValid && element.get(0).checkValidity();
			const p2 = element.val();

			element = $('#' + prefix + 'p4');
			element
				.get(0)
				.setAttribute('aria-invalid', !element.get(0).checkValidity());
			isValid = isValid && element.get(0).checkValidity();
			const p4 = element.val();

			element = $('#' + prefix + 'p5');
			element
				.get(0)
				.setAttribute('aria-invalid', !element.get(0).checkValidity());
			isValid = isValid && element.get(0).checkValidity();
			const p5 = element.val();

			return {
				valid: isValid,
				p1: String(p1),
				p2: String(p2),
				p3: '',
				p4: String(p4),
				p5: String(p5),
			};
		} catch (error) {
			// skip
			return '';
		}
	}

	function checkForChanges(view) {
		hideError();

		const getDiffs = function (defValues, currentValues) {
			const returnValue = {};
			for (let i = 1; i <= 5; i++) {
				const prop = 'p' + i;
				if (currentValues[prop] !== defValues[prop]) {
					returnValue[prop] = currentValues[prop];
				}
			}

			return returnValue;
		};

		const currentValues = readCurrentValues(view);
		changes[view] = getDiffs(defaultValues[view], currentValues);

		togglePublishButton(
			view,
			currentValues.valid && !jQuery.isEmptyObject(changes[view])
		);
	}

	function togglePublishButton(type, state) {
		$('#publish-changes-' + type).prop('disabled', !state);
	}

	function getAdPreview(view) {
		let cont = true;
		const modal = new $.WCBackboneModal.View({
			target: 'facebook-for-woocommerce-modal',
			string: {
				message:
					'<div class="fb-asc-ads"><h2>Loading...</h2><br><div id="ad-preview-' +
					view +
					'-busy-indicator"></div></div>',
			},
		});

		showBusyIndicator('ad-preview', view);

		$(document.body).on('wc_backbone_modal_removed', function () {
			cont = false;
		});

		$.get(
			facebook_for_woocommerce_settings_advertise_asc.ajax_url,
			{
				action: 'wc_facebook_get_ad_preview',
				view,
			},
			function (response) {
				let message = '';
				try {
					if (!cont) {
						return;
					}

					const parsed = response.data;
					message =
						'<div class="fb-asc-ads" ><div class="horizontal-align">';
					for (const frame of parsed) {
						const src = $(frame).attr('src');
						message +=
							'<iframe src="' +
							src +
							'" scrolling="no"></iframe>';
					}
					message += '</div></div>';
				} finally {
					hideBusyIndicator('ad-preview', view);
				}

				modal.initialize({
					target: 'facebook-for-woocommerce-modal',
					string: {
						message,
					},
				});
			}
		);
	}

	function publishChanges(type, changeset) {
		const postData = {};
		postData.action = 'wc_facebook_advertise_asc_publish_changes';
		postData[ascTypeRetargeting] = changeset[ascTypeRetargeting];
		postData[ascTypeNewBuyers] = changeset[ascTypeNewBuyers];
		$.ajax({
			type: 'POST',
			url: facebook_for_woocommerce_settings_advertise_asc.ajax_url,
			async: true,
			data: postData,
			dataType: 'json',
			success(response) {
				publishChangesCallback(type, response);
			},
			error() {
				const response = {
					success: false,
					data: 'An unexpected error happened. Please try again later.',
				};
				publishChangesCallback(type, response);
			},
		});
	}

	function publishChangesCallback(type, result) {
		if (result.success) {
			readDefaultValues();
		} else {
			showError(type, result.data);
		}

		hideBusyIndicator('publish-changes', type);
	}

	function toggleInsightsView(type, checked) {
		const defaultViewSelector =
			'#' + viewItemsIdPrefix + 'default-view-' + type;
		const insightsViewSelector =
			'#' + viewItemsIdPrefix + 'insights-view-' + type;
		if (checked) {
			$(defaultViewSelector).hide();
			$(insightsViewSelector).show();
		} else {
			$(defaultViewSelector).show();
			$(insightsViewSelector).hide();
		}
	}

	function updateToggleInsightsText(type) {
		const id = '#' + viewItemsIdPrefix + 'ad-insights-' + type;
		const element = $(id);
		const dataOn = element.attr('data-on');
		if (!dataOn) {
			return;
		}
		const dataOff = element.attr('data-off');
		const content = element.text();
		element.text(content === dataOn ? dataOff : dataOn);
	}

	function toggleInsightsOnClick(type) {
		updateToggleInsightsText(type);
		currentView[type] = !currentView[type];
		toggleInsightsView(type, currentView[type]);
	}

	function publishChangesOnClick(type) {
		const element = $('#publish-changes-' + type);
		element.prop('disabled', true);
		const temporaryChanges = {};
		temporaryChanges[ascTypeNewBuyers] = {};
		temporaryChanges[ascTypeRetargeting] = {};
		temporaryChanges[type] = changes[type];

		showBusyIndicator('publish-changes', type);
		publishChanges(type, temporaryChanges);
	}

	function hideError(view) {
		$('#error-message-' + view).text('');
	}

	function showError(view, msg) {
		$('#error-message-' + view).text(msg);
	}

	if ($('.select2.wc-facebook').length) {
		$('.select2.wc-facebook')
			.select2()
			.addClass('visible')
			.attr('disabled', false);
	}

	$('#publish-changes-' + ascTypeRetargeting).click(function () {
		publishChangesOnClick(ascTypeRetargeting);
	});

	$('#publish-changes-' + ascTypeNewBuyers).click(function () {
		publishChangesOnClick(ascTypeNewBuyers);
	});

	$('#' + viewItemsIdPrefix + 'ad-insights-' + ascTypeRetargeting).click(
		function () {
			toggleInsightsOnClick(ascTypeRetargeting);
		}
	);

	$('#' + viewItemsIdPrefix + 'ad-insights-' + ascTypeNewBuyers).click(
		function () {
			toggleInsightsOnClick(ascTypeNewBuyers);
		}
	);

	$('#' + viewItemsIdPrefix + 'ad-preview-' + ascTypeRetargeting).click(
		function () {
			getAdPreview(ascTypeRetargeting);
		}
	);

	$('#' + viewItemsIdPrefix + 'ad-preview-' + ascTypeNewBuyers).click(
		function () {
			getAdPreview(ascTypeNewBuyers);
		}
	);

	$('#advertise-asc-form-' + ascTypeNewBuyers + ' :input').on(
		'change input',
		function () {
			checkForChanges(ascTypeNewBuyers);
		}
	);

	$('#advertise-asc-form-' + ascTypeRetargeting + ' :input').on(
		'change input',
		function () {
			checkForChanges(ascTypeRetargeting);
		}
	);

	$('.woocommerce-help-tip').tipTip({
		attribute: 'data-tip',
		fadeIn: 50,
		fadeOut: 50,
		delay: 200,
	});

	(function () {
		const elements = $('.fb-toggle-button');
		for (const item of elements) {
			const element = $(item);
			element.append(
				$(
					'<div class="fb-asc-ads" style="width:' +
						element.attr('width') +
						'; height:' +
						element.attr('height') +
						';"><label class="toggle-button"><input id="' +
						element.attr('id') +
						'-input" type="checkbox" ><span height="' +
						element.attr('height') +
						'" class="toggle-button-slider round"></span></label></div>'
				)
			);
			$('#' + element.attr('id') + '-input').prop(
				'checked',
				element.attr('data-status')
			);
			$('#' + element.attr('id') + '-input').change(function () {
				if (element.attr('id').includes(ascTypeNewBuyers)) {
					checkForChanges(ascTypeNewBuyers);
				} else {
					checkForChanges(ascTypeRetargeting);
				}
			});
		}
	})();

	readDefaultValues();

	window.createModal = createModal;

	if (
		$('#' + viewItemsIdPrefix + 'insights-view-' + ascTypeRetargeting).is(
			':visible'
		)
	) {
		currentView[ascTypeRetargeting] = true;
		updateToggleInsightsText(ascTypeRetargeting);
	}
});