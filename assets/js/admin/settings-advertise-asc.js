/**
 * Copyright ( c ) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package
 */

jQuery(document).ready(function () {
	const $ = jQuery;
	const viewItemsIdPrefix = 'woocommerce-facebook-settings-advertise-asc-';
	const ascTypeRetargeting = 'retargeting';
	const ascTypeNewBuyers = 'new-buyers';

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

	function loadCampaignSetupUi(campaignType, isUpdate, campaignDetails) {
		$('#overlay-view-ui').removeClass('hidden_view');
		$('#base-view-row').addClass('hidden_view');

		window.campaignCreationUILoader('asc-overlay-root', {
			isUpdate,
			campaignType,
			campaignDetails
		}, function () {
			window.location.reload();
		});
	}

	function createInsights(rootElementId, props) {
		const el = document.getElementById(rootElementId);
		if (el) {
			window.insightsUILoader(rootElementId, props);
		}
	}
	createInsights('woocommerce-facebook-settings-advertise-asc-insights-placeholder-root-new-buyers', {});
	createInsights('woocommerce-facebook-settings-advertise-asc-insights-placeholder-root-retargeting', {});

	$('#new-buyers-create-campaign-img').prop('src', require('!!url-loader!./../../images/prospecting.png').default);
	$('#retargeting-create-campaign-img').prop('src', require('!!url-loader!./../../images/retargeting.png').default);

	function addCampaignSetupHooks() {

		const campaignCreationHook = (campaignType) => {
			$('#' + campaignType + '-create-campaign-btn').click(function () {
				loadCampaignSetupUi(campaignType, false, {
					minDailyBudget: $("#" + viewItemsIdPrefix + "min-ad-daily-budget-" + campaignType).val(),
					currency: $("#" + viewItemsIdPrefix + "currency-" + campaignType).val(),
					dailyBudget: $("#" + viewItemsIdPrefix + "ad-daily-budget-" + campaignType).val(),
				});
			});
			$('#' + campaignType + '-create-campaign-btn').prop('disabled', false);
		};

		const campaignEditHook = (campaignType) => {
			
			const selectedCountries = $("#" + viewItemsIdPrefix + "targeting-" + campaignType).val();
			
			$('#' + campaignType + '-edit-campaign-btn').click(function () {
				loadCampaignSetupUi(campaignType, true, {
					adMessage: $("#" + viewItemsIdPrefix + "ad-message-" + campaignType).val(),
					dailyBudget: $("#" + viewItemsIdPrefix + "ad-daily-budget-" + campaignType).val(),
					minDailyBudget: $("#" + viewItemsIdPrefix + "min-ad-daily-budget-" + campaignType).val(),
					selectedCountries: selectedCountries.split(','),
					currency: $("#" + viewItemsIdPrefix + "currency-" + campaignType).val(),
					status: $("#" + viewItemsIdPrefix + "ad-status-" + campaignType).val(),
				});
			});
			$('#' + campaignType + '-edit-campaign-btn').prop('disabled', false);
		};

		campaignCreationHook(ascTypeRetargeting);
		campaignCreationHook(ascTypeNewBuyers);

		campaignEditHook(ascTypeRetargeting);
		campaignEditHook(ascTypeNewBuyers);
	}

	function createModal(link) {
		new $.WCBackboneModal.View({
			target: 'facebook-for-woocommerce-modal',
			string: {
				message: '<iframe src="' + link + '" ></iframe>',
			},
		});
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

	function createModalWithContent(content) {
		new $.WCBackboneModal.View({
			target: 'facebook-for-woocommerce-modal',
			string: {
				message:
					content
			},
		});
	}

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

	$('.woocommerce-help-tip').tipTip({
		attribute: 'data-tip',
		fadeIn: 50,
		fadeOut: 50,
		delay: 200,
	});

	window.createModal = createModal;
	window.createModalWithContent = createModalWithContent;
	addCampaignSetupHooks();

});