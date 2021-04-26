<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Admin\Settings_Screens;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\Admin;
use SkyVerge\WooCommerce\Facebook\Products\FB_Feed_Generator;


/**
 * The Messenger settings screen object.
 */
class Feed_Status extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'feed_status';

	/**
	 * Connection constructor.
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Feed Status', 'facebook-for-woocommerce' );
		$this->title = $this->label;

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}


	/**
	 * Enqueues the assets.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function enqueue_assets() {

		if ( ! $this->is_current_screen_page() ) {
			return;
		}

		wp_enqueue_style( 'wc-facebook-admin-advertise-settings', facebook_for_woocommerce()->get_plugin_url() . '/assets/css/admin/facebook-for-woocommerce-feed-status.css', [], \WC_Facebookcommerce::VERSION );

		wp_enqueue_script( 'facebook-for-woocommerce-feed-status', plugins_url( '/facebook-for-woocommerce/assets/js/admin/facebook-for-woocommerce-settings-feed-status.js' ), [ 'jquery' ], \WC_Facebookcommerce::PLUGIN_VERSION );

		$settings = get_option( FB_Feed_Generator::RUNNING_FEED_SETTINGS, array() );

		wp_localize_script(
			'facebook-for-woocommerce-feed-status',
			'facebook_for_woocommerce_feed_status',
			array(
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'file'                   => get_option( FB_Feed_Generator::FEED_FILE_INFO, null ),
				'feed_generation_nonce'  => wp_create_nonce( FB_Feed_Generator::FEED_GENERATION_NONCE ),
				'generation_in_progress' => FB_Feed_Generator::is_generation_in_progress(),
				'generation_progress'    => $settings['total'] !== 0 ? intval( ( ( $settings['page'] * FB_Feed_Generator::FEED_GENERATION_LIMIT ) / $settings['total'] ) * 100 ) : 0,
				'i18n'                   => array(
					/* translators: Placeholders %s - html code for a spinner icon */
					'confirm_resync' => esc_html__( 'Your products will now be resynced to Facebook, this may take some time.', 'facebook-for-woocommerce' ),
				),
			)
		);
	}

	public function render() {
		$settings = get_option( FB_Feed_Generator::RUNNING_FEED_SETTINGS, array() );
		$feed_id  = facebook_for_woocommerce()->get_integration()->get_feed_id();
		$feed_schedule = FB_Feed_Generator::get_feed_update_schedule();
		?>
		<h1><?php esc_html_e( 'Feed Status', 'woocommerce' ); ?></h1>
		<div class="facebook-for-woocommerce-feed-status-wrapper">
			<form class="facebook-for-woocommerce-feed-generator">
				<header>
					<p><?php esc_html_e( 'This pages shows the status and statistics of the feed file generation', 'facebook-for-woocommerce' ); ?></p>
				</header>
				<hr>
				<section class="facebook-for-woocommerce-feed-status-catalog-info" >
					<?php esc_html_e( 'Facebook catalog feeed settings', 'facebook-for-woocommerce' ); ?>
					<p ><?php esc_html_e( 'Feed id: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-status-catalog-feed-id"> <?php echo $feed_id ?></span>
					</p>
					<p ><?php esc_html_e( 'Feed upload schedule info ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-status-catalog-feed-schedule">
						<pre>
<?php echo json_encode( json_decode( $feed_schedule ), JSON_PRETTY_PRINT ) ?></span>
						</pre>
					</p>
					<hr>
				</section>
				<section class="facebook-for-woocommerce-feed-status-is-generating" >
					<span class="spinner is-active"></span>
					<?php esc_html_e( 'Generating new feed file.', 'facebook-for-woocommerce' ); ?>
					<p ><?php esc_html_e( 'Total number of products to process: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-status-total"> <?php echo $settings['total'] ?></span>
					</p>
					<p><?php esc_html_e( 'Current batch number: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-status-batch"/>
					</p>
					<p><?php esc_html_e( 'Started timestamp: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-status-timestamp"/>
					</p>
					<p>
						<progress class="facebook-woocommerce-feed-generator-progress" max="100" value="0"></progress>
					</p>
					<hr>
				</section>

				<section class="facebook-for-woocommerce-feed-status-file-info" >
					<?php esc_html_e( 'Current feed file information', 'facebook-for-woocommerce' ); ?>
					<p>
						<?php esc_html_e( 'Started: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-file-started"></span><br>
						<?php esc_html_e( 'Ended: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-file-ended"></span><br>
						<?php esc_html_e( 'Processing took[minutes]: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-file-processing-duration"></span><br>
						<?php esc_html_e( 'Items in file: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-file-items-count"></span><br>
						<?php esc_html_e( 'File size[MB]: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-file-size"></span><br>
						<?php esc_html_e( 'File location: ', 'facebook-for-woocommerce' ); ?>
						<span class="facebook-for-woocommerce-feed-file-location"></span><br>
					</p>
					<hr>
				</section>
				<section>
					<p>
						<?php
						$next = as_next_scheduled_action( FB_Feed_Generator::FEED_SCHEDULE_ACTION );
						if ( $next ) {
							esc_html_e( 'Next feed generation scheduled at: ' . date( 'Y-m-d H:i:s', $next ), 'facebook-for-woocommerce' );
						}
						?>
					</p>
					<hr>
				</section>
				<div class="wc-actions">
					<button type="submit" class="facebook-woocommerce-feed-generator-button button button-primary" value="<?php esc_attr_e( 'Generate Feed', 'woocommerce' ); ?>"><?php esc_html_e( 'Generate Feed', 'woocommerce' ); ?></button>
				</div>
			</form>
		</div>
		<?php
		parent::render();
	}

	/**
	 * Gets the screen settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_settings() {
		return array();
	}
}
