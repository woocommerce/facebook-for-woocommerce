<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Admin\Settings_Screens;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\API;
use WooCommerce\Facebook\Locale;
use WooCommerce\Facebook\Admin\Abstract_Settings_Screen;
use WooCommerce\Facebook\Framework\Plugin\Exception as PluginException;
use WooCommerce\Facebook\AdvertiseASC\AscNotSupportedException;
use WooCommerce\Facebook\AdvertiseASC\NonDiscriminationNotAcceptedException;
use WooCommerce\Facebook\AdvertiseASC\InvalidPaymentInformationException;
use WooCommerce\Facebook\AdvertiseASC\LWIeUserException;
/**
 * The Advertise settings screen object.
 */
class Advertise extends Abstract_Settings_Screen {

	/** @var string screen ID */
	const ID = 'advertise';

	/** @var string The prefix for the ids of the elements that are used for the ASC views */
	const ADVERTISE_ASC_ELEMENTS_ID_PREFIX	= "woocommerce-facebook-settings-advertise-asc-";

	/** @var string Ad Preview Ajax Action text */
	const ACTION_GET_AD_PREVIEW				= 'wc_facebook_get_ad_preview';

	/** @var string Publish Changes Ajax Action text */
	const ACTION_PUBLISH_AD_CHANGES			= 'wc_facebook_advertise_asc_publish_changes';

	/** @var string View name for the New-Buyers ASC campaign */
	const ASC_CAMPAIGN_TYPE_NEW_BUYERS 		= 'new-buyers';

	/** @var string View name for the Retargeting ASC campaign */
	const ASC_CAMPAIGN_TYPE_RETARGETING 	= 'retargeting';

	const STATUS_DISABLED					= 'disabled';

	/**
	 * Advertise settings constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->id    = self::ID;
		$this->label = esc_html__( 'Advertise', 'facebook-for-woocommerce' );
		$this->title = esc_html__( 'Advertise', 'facebook-for-woocommerce' );

		$this->add_hooks();
	}


	/**
	 * Adds hooks.
	 *
	 * @since 2.2.0
	 */
	private function add_hooks() {
		add_action( 'admin_head', array( $this, 'output_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'init', array( $this, 'add_frontend_hooks' ) );
	}

	public function add_frontend_hooks(){

		wp_enqueue_script(
			'wc_facebook_metabox_jsx',
			facebook_for_woocommerce()->get_asset_build_dir_url() . '/admin/metabox.js',
			array(),
			\WC_Facebookcommerce::PLUGIN_VERSION
		);

		wp_enqueue_script(
			'facebook-for-woocommerce-settings-advertise-asc',
			facebook_for_woocommerce()->get_asset_build_dir_url() . '/admin/settings-advertise-asc.js',
			array('jquery', 'select2', 'jquery-tiptip' ),
			\WC_Facebookcommerce::PLUGIN_VERSION
		);

		wp_localize_script(
			'facebook-for-woocommerce-settings-advertise-asc',
			'facebook_for_woocommerce_settings_advertise_asc',
			array(
				'ajax_url'               	=>	admin_url( 'admin-ajax.php' ),
				'get_ad_preview_nonce'   	=>	wp_create_nonce( self::ACTION_GET_AD_PREVIEW ),
				'publish_changes_nonce'		=>	wp_create_nonce( self::ACTION_PUBLISH_AD_CHANGES ),
			)
		);
	}


	/**
	 * Enqueues assets for the current screen.
	 *
	 * @internal
	 *
	 * @since 2.2.0
	 */
	public function enqueue_assets() {
		if ( ! $this->is_current_screen_page() ) {
			return;
		}
		wp_enqueue_style( 'wc-facebook-admin-advertise-settings', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/facebook-for-woocommerce-advertise.css', array(), \WC_Facebookcommerce::VERSION );
		wp_enqueue_style( 'wc-facebook-admin-advertise-settings-asc', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/facebook-for-woocommerce-advertise-asc.css', array(), \WC_Facebookcommerce::VERSION );
	}


	/**
	 * Outputs the LWI Ads script.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function output_scripts() {
		$connection_handler = facebook_for_woocommerce()->get_connection_handler();
		if ( ! $connection_handler || ! $connection_handler->is_connected() || ! $this->is_current_screen_page() ) {
			return;
		}

		?>
		<script>
			window.fbAsyncInit = function() {
				
				FB.init( {
					appId            : '<?php echo esc_js( $connection_handler->get_client_id() ); ?>',
					autoLogAppEvents : true,
					xfbml            : true,
					version          : '<?php echo esc_js( API::API_VERSION )?>',
				} );
			};
		</script>
		<?php
	}


	/**
	 * Gets the LWI Ads configuration to output the FB iframes.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function get_lwi_ads_configuration_data() {

		$connection_handler = facebook_for_woocommerce()->get_connection_handler();

		if ( ! $connection_handler || ! $connection_handler->is_connected() ) {
			return array();
		}

		return array(
			'business_config' => array(
				'business' => array(
					'name' => $connection_handler->get_business_name(),
				),
			),
			'setup'           => array(
				'external_business_id' => $connection_handler->get_external_business_id(),
				'timezone'             => $this->parse_timezone( wc_timezone_string(), wc_timezone_offset() ),
				'currency'             => get_woocommerce_currency(),
				'business_vertical'    => 'ECOMMERCE',
			),
			'repeat'          => false,
		);
	}


	/*
	 * Converts the given timezone string to a name if needed.
	 *
	 * @since 2.2.0
	 *
	 * @param string $timezone_string Timezone string
	 * @param int|float $timezone_offset Timezone offset
	 * @return string timezone string
	 */
	private function parse_timezone( $timezone_string, $timezone_offset = 0 ) {

		// no need to look for the equivalent timezone
		if ( false !== strpos( $timezone_string, '/' ) ) {
			return $timezone_string;
		}

		// look up the timezones list based on the given offset
		$timezones_list = timezone_abbreviations_list();

		foreach ( $timezones_list as $timezone ) {
			foreach ( $timezone as $city ) {
				if ( isset( $city['offset'], $city['timezone_id'] ) && (int) $city['offset'] === (int) $timezone_offset ) {
					return $city['timezone_id'];
				}
			}
		}

		// fallback to default timezone
		return 'Etc/GMT';
	}


	/**
	 * Gets the LWI Ads SDK URL.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	private function get_lwi_ads_sdk_url() {

		$locale = get_user_locale();

		if ( ! Locale::is_supported_locale( $locale ) ) {
			$locale = Locale::DEFAULT_LOCALE;
		}

		return "https://connect.facebook.net/{$locale}/sdk.js";
	}


	/**
	 * Renders the screen HTML.
	 *
	 * The contents of the Facebook box will be populated by the LWI Ads script through iframes.
	 *
	 * @since 2.2.0
	 */
	public function render() {

		$connection_handler = facebook_for_woocommerce()->get_connection_handler();

		if ( ! $connection_handler || ! $connection_handler->is_connected() ) {

			printf(
				/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s - closing </a> HTML link tag */
				esc_html__( 'Please %1$sconnect your store%2$s to Facebook to create ads.', 'facebook-for-woocommerce' ),
				'<a href="' . esc_url( add_query_arg( array( 'tab' => Connection::ID ), facebook_for_woocommerce()->get_settings_url() ) ) . '">',
				'</a>'
			);

			return;
		}

		$this->experimental_view_render();

		parent::render();
	}


	/*
	 * Renders the ASC Experimental view.
	 *
	 * @since x.x.x
	 *
	 */
	private function experimental_view_render() {

		if ( $this->can_try_experimental_view() ) {

			$this->try_render_experimental_view();

		} else {

			$this->render_lwi_view();

		}
	}


	private function can_try_experimental_view() {
		return true;
		return facebook_for_woocommerce()->get_integration()->get_advertise_asc_status() != self::STATUS_DISABLED;
	}


	private function try_render_experimental_view() {
		$title = "Advertise your products on Facebook and Instagram";
		$subtitle = "Launch campaigns to drive new buyers and bring back website visitors with just a few clicks";
		$translate = function( $text ) {
			return $this->get_escaped_translation( $text );
		};
		$translate_with_link = function( $hyperlink, $link_text, $pretext, $rest_of_text ) use(&$translate) {
			return $translate( $pretext ) . " <a href='<?php echo " . $hyperlink . "?>' >" . $translate( $link_text ) . "</a>" . $translate( $rest_of_text );
		};
		try {
			?>
				<div class="fb-asc-ads">
					<h1><?php echo $translate( $title ); ?></h1>
					<h2 style="margin-top: 0px; margin-bottom: 30px; font-weight: 400;"><?php echo $translate( $subtitle ); ?></h2>
					<table>
						<tr>
							<td>
							<hr>
							<form id='advertise-asc-form-new-buyers'>
							<?php $this->create_section_headers(esc_html__('New Customers', 'facebook-for-woocommerce') , esc_html__('Reach out to potential new buyers for your products', 'facebook-for-woocommerce') , self::ASC_CAMPAIGN_TYPE_NEW_BUYERS ); ?>
								<?php $this->create_cells(facebook_for_woocommerce()->get_advertise_asc_handler(self::ASC_CAMPAIGN_TYPE_NEW_BUYERS)); ?>
							</form>
							<hr>
							<form id='advertise-asc-form-retargeting'>
								<?php $this->create_section_headers (esc_html__('Retargeting', 'facebook-for-woocommerce') , esc_html__("Bring back visitors that visited your website and didn't complete their purchase", 'facebook-for-woocommerce') , self::ASC_CAMPAIGN_TYPE_RETARGETING ); ?>
								<?php $this->create_cells( facebook_for_woocommerce()->get_advertise_asc_handler( self::ASC_CAMPAIGN_TYPE_RETARGETING )); ?>
							</form>
							</td>
							<td>
								<div
									style="
									border: 1px solid #8c8f94;
									width: 90%;
									border-radius: 10px;
									padding: 10px;
									"
								>
									<h2><?php echo esc_html__('Quickstart Guide', 'facebook-for-woocommerce') ?></h2>
									
									<ul style="padding-left: 30px;">
										<li style="margin: 10px;">
											<b><?php echo esc_html__('Ad Message', 'facebook-for-woocommerce') ?></b> - <?php echo esc_html__('The text that will be shown above the
											product carousel post, telling people why they should buy these products. Highlight your unique value, a special discount or easy shipping and return policy.', 'facebook-for-woocommerce') ?>
										</li>

										<li style="margin: 10px;">
											<b><?php echo esc_html__('Daily Budget', 'facebook-for-woocommerce') ?></b> - <?php echo esc_html__("The amount you'd like to spend on your ad
											per day. Please note that there's a minimum daily budget, if you'll set your budget under it, we'll let you know.", 'facebook-for-woocommerce') ?><br>
										</li>

										<li style="margin: 10px;">
											<b><?php echo esc_html__('Country (For New Customers campaign)', 'facebook-for-woocommerce') ?></b> - <?php echo esc_html__("Country (or countries) you'd like your ad to be shown.", 'facebook-for-woocommerce') ?>
										</li>

										<li style="margin: 10px;">
											<b><?php echo esc_html__('Visit Period (For Retargeting campaign)', 'facebook-for-woocommerce') ?></b> - <?php echo esc_html__('Number of days people will be seeing this ad after their visit to your website.', 'facebook-for-woocommerce') ?>
										</li>
									
									</ul>
									
									<p>
									<?php echo esc_html__("Once you've set everything, make sure to turn your campaign on and publish changes.", 'facebook-for-woocommerce') ?>
									</p>

									<h2><?php echo esc_html__("Additional Features", 'facebook-for-woocommerce') ?></h2>
									
									<ul style="padding-left: 30px;">
										<li><b><?php echo esc_html__("Preview", 'facebook-for-woocommerce') ?></b> - <?php echo esc_html__("See how your ad will look like.", 'facebook-for-woocommerce') ?></li>
										<li><b><?php echo esc_html__("Insights", 'facebook-for-woocommerce') ?></b> - <?php echo esc_html__("See how your ad is performing.", 'facebook-for-woocommerce') ?></li>
									</ul>
								</div>
								</td>
						</tr>
					</table>
				</div>
			<?php

		} catch ( AscNotSupportedException $e ) {

			facebook_for_woocommerce()->get_integration()->set_advertise_asc_status( self::STATUS_DISABLED );
			?>
			<script>
				window.location.reload();
			</script>
			<?php

		} catch ( InvalidPaymentInformationException $ipie ) {

			$this->remove_rendered_when_exception_happened();

			$ad_acc_id = facebook_for_woocommerce()->get_connection_handler()->get_ad_account_id();
			$link = "https://business.facebook.com/ads/manager/account_settings/account_billing/?act=" . $ad_acc_id;

			?>
			<h2><?php echo $translate( "Your payment settings need to be updated before we can proceed." ); ?></h2>
			<h4><?php echo $translate( "Here's how:" ); ?></h3>
			<ul>
				<li><?php echo $translate_with_link( $link, "Click here", "1.", "to go to the \"Payment Settings\" section in your Ads Manager" ); ?></li>
				<li><?php echo $translate( "2. Click the \"Add payment method\" button and follow instructions to ad a payment method" ); ?></li>
				<li><?php echo $translate( "3. Go back to this screen and refresh it" ); ?></li>
			</ul>
			<?php
		} catch ( NonDiscriminationNotAcceptedException $nde ) {

			$this->remove_rendered_when_exception_happened();

			$link = "https://business.facebook.com/settings/system-users?business_id=" . facebook_for_woocommerce()->get_connection_handler()->get_business_manager_id();
			?>
			<h2><?php echo $translate( "A business Admin must review and accept our non-discrimination policy before you can run ads." ); ?></h2>
			<h4><?php echo $translate( "Here's how:" ); ?></h3>
			<ul>
				<li><?php echo $translate_with_link( $link, "Click here", "1.", "to go to the \"System Users\" section in your Business Manager" ); ?></li>
				<li><?php echo $translate( "2. Click the \"Add\" button to review our Discriminatory Practices policy" ); ?></li>
				<li><?php echo $translate( "3. Click the \"I accept\" button to confirm compliance on behalf of your system users" ); ?></li>
				<li><?php echo $translate( "4. Close the pop-up window by clicking on X or \"Done\"" ); ?></li>
				<li><?php echo $translate( "5. Go back to this screen and refresh it" ); ?></li>
			</ul>
			<?php

		} catch ( LWIeUserException $lwie ) {

			$this->remove_rendered_when_exception_happened();

			facebook_for_woocommerce()->get_integration()->set_advertise_asc_status( self::STATUS_DISABLED );
			?>
			<script>
				window.location.reload();
			</script>
			<?php

		} catch ( \Throwable $pe ) {
			
			$this->remove_rendered_when_exception_happened();
			
			$ad_account_id = facebook_for_woocommerce()->get_connection_handler()->get_ad_account_id();
			
			$subject = $ad_account_id . '_' . 'PluginException';
			$body = 'message: ' . $pe->getMessage() . '  stack-trace: ' . $pe->getTraceAsString();
			$body = urlencode($body);
			$link = 'mailto:woofeedback@meta.com?subject='.$subject.'&body='.$body;
			?>
			<h2><?php echo $translate_with_link( $link, "Click here", "An unexpected error happened.", " to mail us the bug report." ); ?></h2>
			<?php
			
		}
	}


	private function remove_rendered_when_exception_happened() {

		?>
		</table></form></div> <!-- This is to make sure the error message or anything after this won't be a part of the form in which error happened. -->
		 <script>
			jQuery( '.fb-asc-ads' ).remove();
		</script>
		<?php

	}


	/*
	 * Creates the headings for a section.
	 *
	 * @since x.x.x
	 *
	 * @param string $title The Title heading
	 * @param string $subtitle The Subtitle-Heading heading
	 * @param string $view The name of the view section it's going to belong to.
	 */
	private function create_section_headers( $title, $subtitle, $type ) {
		?>
		<div class='fb-asc-ads'>
			<table class='default-view-table'>
				<tr>
					<td>
						<h1 class='wp-heading-inline'><?php echo $this->get_escaped_translation( $title ); ?></h1>
					</td>
					<td style="vertical-align: middle;">

						<button id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX . 'ad-preview-' . $type ?>'
							type="button"
							title="Preview your ads"
							class="button button-large"
							width="70px" height="34px">
								<?php echo esc_html_e( 'Preview', 'facebook-for-woocommerce' ); ?>
						</button>

						<button id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX . 'ad-insights-' . $type ?>'
							type="button"
							title="View campaign performance"
							class='button button-large'
							height='34px' width='70px' data-on="Insights" data-off="Edit">Insights</button>

					</td>
					<td style='text-align: right; width: 70%; vertical-align: middle;'>
						<div>
							<button class='button button-primary'
								title="Save and publish your changes"
								type="button"
								disabled='true'
								id='<?php echo "publish-changes-".$type ?>'>
								<span>
									<?php echo esc_html_e( 'Publish Changes', 'facebook-for-woocommerce' ); ?>
									<div id='<?php echo "publish-changes-".$type.'-busy-indicator' ?>'></div>
								<span>
							</button>
						</div>
					</td>
				</tr>
				<tr width="100%">
					<td style="text-align:left;" colspan="3">
						<label class="form-table form-wrap th"><?php echo $this->get_escaped_translation( $subtitle ); ?></label>
					</td>
					<td style="text-align:right;">
						<label id='<?php echo "error-message-".$type ?>' class="form-table form-wrap error"></label>
					</td>
				</tr>
			</table>
		</div>
		<?php
		
	}


	/*
	 * Creates the tables and cells for a view.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $campaign_handler The handler for the corresponding section
	 */
	private function create_cells( $campaign_handler ) {
		$values		= $campaign_handler->get_info();
		$properties	= $campaign_handler->get_properties();
		$tooltips	= $campaign_handler->get_tooltips();
		$type		= $campaign_handler->get_campaign_type();

		if ( $campaign_handler->are_insights_available() ) {
			$reach		= $campaign_handler->get_insights_reach();
			$spend 		= $campaign_handler->get_insights_spend();
			$events		= $campaign_handler->get_insights_events();
		}
		?>
		<div class='fb-asc-ads'>
			<div id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX."default-view-".$type ?>' <?php echo ($type == self::ASC_CAMPAIGN_TYPE_RETARGETING && $campaign_handler->are_insights_available() ? "style='display:none;'" : ""); ?>>
				<?php $this->create_campaign_settings_section( $campaign_handler, $type, $values, $properties, $tooltips ); ?>
			</div>
			<div id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX.$type."-waiting-window" ?>' class='default-view-table waiting-window' style='display:none;'>
				<h2>Loading insights...</h2>
			</div>
			<div class='default-view-table top-pad' id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX."insights-view-".$type ?>' <?php echo ($type == self::ASC_CAMPAIGN_TYPE_RETARGETING && $campaign_handler->are_insights_available() ? "" : "style='display:none;'"); ?>>
				<?php
				if ( $campaign_handler->are_insights_available() ) {
					$this->create_insights( $type, $properties, $values, $spend, $reach, $events[ 'clicks' ], $events[ 'views' ], $events[ 'cart' ], $events[ 'purchases' ], $campaign_handler->get_currency() );
				} else {
					?>
					<p><?php echo $this->get_escaped_translation('Insights are not available yet. If your campaign is active, please check again at a later time.'); ?></p>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}


	/*
	 * Creates the html elements that handle the ASC campaign creation section
	 *
	 * @since x.x.x
	 *
	 * @param mixed $campaign_handler The handler for the corresponding section
	 * @param string $type The name of the view for the corresponding section
	 * @param array $values Current values for each table column
	 * @param array $parameter_names Heading of each column in the data table
	 */
	private function create_campaign_settings_section( $campaign_handler, $type, $values, $parameter_names, $tooltips ) {
		?>
		<div class="fb-asc-ads">
			<table class="default-view-table top-pad" height="200px;">
				<thead>
					<th ><div style='width:60px;'><?php echo $this->get_escaped_translation( $parameter_names['p1'] ); ?><span class="woocommerce-help-tip" data-tip="<?php echo $this->get_escaped_translation( $tooltips['p1'] ); ?>"></span></div></th>
					<th style='width:100%;'><label class="form-table form-wrap th"><?php echo $this->get_escaped_translation( $parameter_names['p2'] ); ?><span class="woocommerce-help-tip" data-tip="<?php echo $this->get_escaped_translation( $tooltips['p2'] ); ?>"></label></th>
					<th style='min-width:200px;max-width:400px;'><?php echo $this->get_escaped_translation( $parameter_names['p4'] ); ?><span class="woocommerce-help-tip" data-tip="<?php echo $this->get_escaped_translation( $tooltips['p4'] ); ?>"></th>
					<th style='width:100px;'><?php echo $this->get_escaped_translation( $parameter_names['p5'] ); ?><span class="woocommerce-help-tip" data-tip="<?php echo $this->get_escaped_translation( $tooltips['p5'] ); ?>"></th>
				</thead>
				<tbody>
					<tr>
						<td style='width:150px;'>
							<div id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX.$type.'-p1'?>'
								class='fb-toggle-button'
								data-status="<?php echo ($values['p1'] ? '1': '') ?>"
								width="40px" height="20px"></div>
						</td>
						<td>
							<textarea id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX.$type.'-p2'?>'
								rows="5"
								style="width:100%;"><?php echo $values['p2'] ?></textarea>
						</td>
						<td>
							<?php
								if ( $type == self::ASC_CAMPAIGN_TYPE_NEW_BUYERS ){
									$this->create_multiselect(self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX.$type.'-p4', $values['p4'], $campaign_handler->get_choices_for('p4') );
								} else {
									$this->create_selector(self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX.$type.'-p4', $values['p4'], $campaign_handler->get_choices_for('p4') );
								}
							 ?>
						</td>
						<td>
							<div class="horizontal-align" style="width:100%;">
								<input style="float:left;width:80px;"
									id='<?php echo self::ADVERTISE_ASC_ELEMENTS_ID_PREFIX.$type.'-p5' ?>'
									type='number'
									required
									min="<?php echo $campaign_handler->get_min_allowed_daily_budget() ?>"
									value='<?php echo $values['p5'] ?>' />

								<div style="padding-left:10px;">
									<?php echo $campaign_handler->get_currency(); ?>
								</div>

							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}


	/*
	 * Returns an escaped translation of the input text, in the realm of this plugin
	 *
	 * @since x.x.x
	 *
	 * @param string $text
	 * @returns string
	 */
	private function get_escaped_translation( $text ) {
		return esc_html__( $text, 'facebook-for-woocommerce' );
	}


	/*
	 * Creates the html element for a multiselect drop-down.
	 *
	 * @since x.x.x
	 *
	 * @param string $id Id for the html element
	 * @param string $default The default value
	 * @param array $options The selections for the dropdown
	 */
	private function create_multiselect( $id, $default, $options){
		?>
		<div class="fb-asc-ads">
			<select id="<?php echo $id ?>" multiple class="select2 wc-facebook visible select2-hidden-accessible" style="display: none;" tabindex="-1" aria-hidden="true">
			<?php
			foreach( $options as $key => $value ) {
				echo "<option ";

				if ( ( is_array( $default ) && in_array( $key, $default ) ) || $default == $key ) {
					echo " selected=\"selected\" ";
				}

				echo "value=\"$key\">";
				echo esc_html__( $value, 'facebook-for-woocommerce' );
				echo "</option> \n";
			}
			?>
			</select>
		</div>
		<?php
	}


	/*
	 * Creates the html element for a multiselect drop-down.
	 *
	 * @since x.x.x
	 *
	 * @param string $id Id for the html element
	 * @param string $default The default value
	 * @param array $options The selections for the dropdown
	 */
	private function create_insights( $type, $properties, $values, $spend, $reach, $clicks, $views, $add_to_carts, $purchases, $currency ) {
		$p4_value = is_array( $values[ 'p4' ] ) ? implode( ', ', $values[ 'p4' ] ) : $values[ 'p4' ];
		$reach_text = 0;
		$clicks_text = 0;
		$views_text = 0;
		$add_to_carts_text = 0;
		$purchases_text = 0;
		$status = $values['p1'] ? "Active" : "Paused";

		$sum = $reach + $clicks + $views + $add_to_carts + $purchases;
		if ( $sum == 0 ) {
			$sum = 1; // To prevent Divide by zero from happening further down the code.
		}

		$create_funnel_text = function ( $header, $percentage, $cost, $cur ) { return $this->get_formatted_number( $header, 0, ',') . chr( 0x0D ) . chr( 0x0A ) . '(' . $this->get_formatted_number( $percentage, 0 ) . '%, ' . $this->get_formatted_number( $cost, 0 ) . ' ' . $cur .')'; };

		if ( $reach > 0 ) {
			$reach_text = $create_funnel_text( $reach, 100, $spend / $reach, $currency );
		}

		if ( $reach > 0 && $clicks > 0 ) {
			$clicks_text = $create_funnel_text( $clicks, ( $clicks * 100.0 / $reach ), $spend / $clicks, $currency );
		}

		if ( $clicks > 0 && $views > 0 ) {
			$views_text = $create_funnel_text( $views, ( $views * 100.0 / $clicks ), $spend / $views, $currency );
		}

		if ( $views > 0 && $add_to_carts > 0 ) {
			$add_to_carts_text = $create_funnel_text( $add_to_carts, ( $add_to_carts * 100.0 / $views ), $spend / $add_to_carts, $currency );
		}

		if ( $add_to_carts > 0 && $purchases > 0 ) {
			$purchases_text = $create_funnel_text( $purchases, ( $purchases * 100.0 / $add_to_carts ), $spend / $purchases, $currency );
		}

		$vals = array($reach, $clicks, $views, $add_to_carts, $purchases);
		?>
		<div class="fb-asc-ads" style="height: 200px; width: 100%; ">
			<table width="100%">
				<td width="200px">
					<table>
						<tr><?php $this->create_summary_report_row( 'Duration', 'Last 30 Days', 'Spend', $this->get_formatted_number( $spend, 0, ',') . ' ' . $currency ) ?></tr>
						<tr><p></p></tr>
						<tr><?php $this->create_summary_report_row( 'Status', $status, 'Collection', 'All products' ) ?></tr>
						<tr><p></p></tr>
						<tr><?php $this->create_summary_report_row( $properties['p4'], $p4_value, 'Daily Budget', $this->get_formatted_number( $values['p5'], 0, ',') . ' ' . $currency ) ?></tr>
					</table>
				</td>
				<td style="vertical-align:bottom;">
					<table class="bar-chart">
						<thead style="height:90%">
						<th></th> <!--  This header is here so the tooltip placement aligns well with the rest of the table  -->
						<?php
							foreach ($vals as $value) {
								$height = ( 200.0 * $value / $sum );
								echo "<th><div style='height:" . $height . "px;'></div></th>";
							}
						?>
						</thead>
						<tbody style="height:10%;">
							<tr>
								<td><span class="woocommerce-help-tip" data-tip="<?php echo $this->get_escaped_translation( 'X-through rate, Cost per action' ); ?>"></span></td>
								<td><label>Reach <?php echo $reach_text ?></label></td>
								<td><label>Clicks <?php echo $clicks_text ?></label></td>
								<td><label>Views <?php echo $views_text ?></label></td>
								<td><label>Add to cart <?php echo $add_to_carts_text ?></label></td>
								<td><label>Purchase <?php echo $purchases_text ?></label></td>
							</tr>
						</tbody>
					</table>
				</td>
			</table>
		</div>
		<?php
	}

	private function get_formatted_number( $number, $floating_points, $separator = '') {
		return number_format( $number, $floating_points, '.', $separator);
	}

	private function create_summary_report_row( $title1, $value1, $title2, $value2 ) {
	?>
		<table style="text-align:left;">
			<thead>
				<th width="100px"><label style="font-weight:bold;"><?php echo $this->get_escaped_translation( $title1 ); ?></label></th>
				<th width="100px"><label style="font-weight:bold;"><?php echo $this->get_escaped_translation( $title2 ); ?></label></th>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $this->get_escaped_translation( $value1 ); ?></td>
					<td><?php echo $this->get_escaped_translation( $value2 ); ?></td>
				</tr>
			</tbody>
		<table>
	<?php
	}


	/*
	 * Creates the html element for a dropdown
	 *
	 * @since x.x.x
	 *
	 * @param string $id Id for the html element
	 * @param string $default The default value
	 * @param array $options The selections for the dropdown
	 */
	private function create_selector( $id, $default, $options ) {

		$option = "";

		foreach( $options as $key => $value ) {
			$option .= "<option ";

			if ( $default == $key ) {
				$option .= " selected=\"selected\" ";
			}

			$option .= "value=\"$key\">";
			$option .= esc_html__( $value, 'facebook-for-woocommerce' );
			$option .= "</option> \n";
		}
		?>

		<select style='float:left;min-width:200px;max-width:400px;' id='<?php echo $id ?>'>
			<?php echo $option ?>
		</select>

		<?php
	}


	/**
	 * Creates the html elements needed for the LWI-E view.
	 *
	 * @since x.x.x
	 *
	 */
	private function render_lwi_view() {

		$fbe_extras = wp_json_encode( $this->get_lwi_ads_configuration_data() );

		?>
		<script async defer src="<?php echo esc_url( $this->get_lwi_ads_sdk_url() ); ?>"></script>
		<div
			class="fb-lwi-ads-creation"
			data-hide-manage-button="true"
			data-fbe-extras="<?php echo esc_attr( $fbe_extras ); ?>"
			data-fbe-scopes="manage_business_extension"
			data-fbe-redirect-uri="https://mariner9.s3.amazonaws.com/"
			data-title="<?php esc_attr_e( 'If you are connected to Facebook but cannot display ads, please contact Facebook support.', 'facebook-for-woocommerce' ); ?>"></div>
		<div
			class="fb-lwi-ads-insights"
			data-fbe-extras="<?php echo esc_attr( $fbe_extras ); ?>"
			data-fbe-scopes="manage_business_extension"
			data-fbe-redirect-uri="https://mariner9.s3.amazonaws.com/"></div>
		<?php
	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public function get_settings() {
		return array();
	}
}