<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * Plugin Name: Facebook for WooCommerce
 * Plugin URI: https://github.com/woocommerce/facebook-for-woocommerce/
 * Description: Grow your business on Facebook! Use this official plugin to help sell more of your products using Facebook. After completing the setup, you'll be ready to create ads that promote your products and you can also create a shop section on your Page where customers can browse your products on Facebook.
 * Author: Facebook
 * Author URI: https://www.facebook.com/
 * Version: 3.1.1
 * Requires at least: 5.6
 * Text Domain: facebook-for-woocommerce
 * Tested up to: 6.3
 * WC requires at least: 5.4
 * WC tested up to: 8.2
 *
 * @package FacebookCommerce
 */

require_once __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Grow\Tools\CompatChecker\v0_0_1\Checker;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

// HPOS compatibility declaration.
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( __FILE__ ), true );
		}
	}
);
/**
 * The plugin loader class.
 *
 * @since 1.10.0
 */
class WC_Facebook_Loader {

	/**
	 * @var string the plugin version. This must be in the main plugin file to be automatically bumped by Woorelease.
	 */
	const PLUGIN_VERSION = '3.1.1'; // WRCS: DEFINED_VERSION.

	// Minimum PHP version required by this plugin.
	const MINIMUM_PHP_VERSION = '7.4.0';

	// Minimum WordPress version required by this plugin.
	const MINIMUM_WP_VERSION = '4.4';

	// Minimum WooCommerce version required by this plugin.
	const MINIMUM_WC_VERSION = '5.3';

	// SkyVerge plugin framework version used by this plugin.
	const FRAMEWORK_VERSION = '5.10.0';

	// The plugin name, for displaying notices.
	const PLUGIN_NAME = 'Facebook for WooCommerce';


	/**
	 * This class instance.
	 *
	 * @var \WC_Facebook_Loader single instance of this class.
	 */
	private static $instance;

	/**
	 * Admin notices to add.
	 *
	 * @var array Array of admin notices.
	 */
	private $notices = array();


	/**
	 * Constructs the class.
	 *
	 * @since 1.10.0
	 */
	protected function __construct() {

		register_activation_hook( __FILE__, array( $this, 'activation_check' ) );

		add_action( 'admin_init', array( $this, 'check_environment' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// If the environment check fails, initialize the plugin.
		if ( $this->is_environment_compatible() ) {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		}
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.10.0
	 */
	public function __clone() {

		wc_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', get_class( $this ) ), '1.10.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.10.0
	 */
	public function __wakeup() {

		wc_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', get_class( $this ) ), '1.10.0' );
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.10.0
	 */
	public function init_plugin() {

		if ( ! Checker::instance()->is_compatible( __FILE__, self::PLUGIN_VERSION ) ) {
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'class-wc-facebookcommerce.php';

		// fire it up!
		if ( function_exists( 'facebook_for_woocommerce' ) ) {
			facebook_for_woocommerce();
		}
	}


	/**
	 * Gets the framework version in namespace form.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_framework_version_namespace() {
		return 'v' . str_replace( '.', '_', $this->get_framework_version() );
	}


	/**
	 * Gets the framework version used by this plugin.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	public function get_framework_version() {

		return self::FRAMEWORK_VERSION;
	}


	/**
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function activation_check() {

		if ( ! $this->is_environment_compatible() ) {

			$this->deactivate_plugin();

			wp_die( esc_html( self::PLUGIN_NAME . ' could not be activated. ' . $this->get_environment_message() ) );
		}
	}


	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function check_environment() {

		if ( ! $this->is_environment_compatible() && is_plugin_active( plugin_basename( __FILE__ ) ) ) {

			$this->deactivate_plugin();

			$this->add_admin_notice( 'bad_environment', 'error', self::PLUGIN_NAME . ' has been deactivated. ' . $this->get_environment_message() );
		}
	}


	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce versions.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function add_plugin_notices() {

		if ( ! $this->is_wp_compatible() ) {
			if ( current_user_can( 'update_core' ) ) {
				$this->add_admin_notice(
					'update_wordpress',
					'error',
					sprintf(
						/* translators: %1$s - plugin name, %2$s - minimum WordPress version required, %3$s - update WordPress link open, %4$s - update WordPress link close */
						esc_html__( '%1$s requires WordPress version %2$s or higher. Please %3$supdate WordPress &raquo;%4$s', 'facebook-for-woocommerce' ),
						'<strong>' . self::PLUGIN_NAME . '</strong>',
						self::MINIMUM_WP_VERSION,
						'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
						'</a>'
					)
				);
			}
		}

		// Notices to install and activate or update WooCommerce.
		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return; // Do not display the install/update/activate notice in the update plugin screen.
		}

		$plugin = 'woocommerce/woocommerce.php';
		// Check if WooCommerce is activated.
		if ( ! $this->is_wc_activated() ) {

			if ( $this->is_wc_installed() ) {
				// WooCommerce is installed but not activated. Ask the user to activate WooCommerce.
				if ( current_user_can( 'activate_plugins' ) ) {
					$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
					$message        = sprintf(
						/* translators: %1$s - Plugin Name, %2$s - activate WooCommerce link open, %3$s - activate WooCommerce link close. */
						esc_html__( '%1$s requires WooCommerce to be activated. Please %2$sactivate WooCommerce%3$s.', 'facebook-for-woocommerce' ),
						'<strong>' . self::PLUGIN_NAME . '</strong>',
						'<a href="' . esc_url( $activation_url ) . '">',
						'</a>'
					);
					$this->add_admin_notice(
						'activate_woocommerce',
						'error',
						$message
					);
				}
			} else {
				// WooCommerce is not installed. Ask the user to install WooCommerce.
				if ( current_user_can( 'install_plugins' ) ) {
					$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
					$message     = sprintf(
						/* translators: %1$s - Plugin Name, %2$s - install WooCommerce link open, %3$s - install WooCommerce link close. */
						esc_html__( '%1$s requires WooCommerce to be installed and activated. Please %2$sinstall WooCommerce%3$s.', 'facebook-for-woocommerce' ),
						'<strong>' . self::PLUGIN_NAME . '</strong>',
						'<a href="' . esc_url( $install_url ) . '">',
						'</a>'
					);
					$this->add_admin_notice(
						'install_woocommerce',
						'error',
						$message
					);
				}
			}
		} elseif ( ! $this->is_wc_compatible() ) { // If WooCommerce is activated, check for the version.
			if ( current_user_can( 'update_plugins' ) ) {
				$update_url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $plugin, 'upgrade-plugin_' . $plugin );
				$this->add_admin_notice(
					'update_woocommerce',
					'error',
					sprintf(
						/* translators: %1$s - Plugin Name, %2$s - minimum WooCommerce version, %3$s - update WooCommerce link open, %4$s - update WooCommerce link close, %5$s - download minimum WooCommerce link open, %6$s - download minimum WooCommerce link close. */
						esc_html__( '%1$s requires WooCommerce version %2$s or higher. Please %3$supdate WooCommerce%4$s to the latest version, or %5$sdownload the minimum required version &raquo;%6$s', 'facebook-for-woocommerce' ),
						'<strong>' . self::PLUGIN_NAME . '</strong>',
						self::MINIMUM_WC_VERSION,
						'<a href="' . esc_url( $update_url ) . '">',
						'</a>',
						'<a href="' . esc_url( 'https://downloads.wordpress.org/plugin/woocommerce.' . self::MINIMUM_WC_VERSION . '.zip' ) . '">',
						'</a>'
					)
				);
			}
		}
	}


	/**
	 * Determines if the required plugins are compatible.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	private function plugins_compatible() {
		return $this->is_wp_compatible() && $this->is_wc_compatible();
	}


	/**
	 * Determines if the WordPress compatible.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	private function is_wp_compatible() {

		if ( ! self::MINIMUM_WP_VERSION ) {
			return true;
		}

		return version_compare( get_bloginfo( 'version' ), self::MINIMUM_WP_VERSION, '>=' );
	}

	/**
	 * Query WooCommerce activation.
	 *
	 * @since 2.6.24
	 * @return bool
	 */
	private function is_wc_activated() {
		return class_exists( 'WooCommerce' ) ? true : false;
	}

	/**
	 * Determins if WooCommerce is installed.
	 *
	 * @since 2.6.24
	 * @return bool
	 */
	private function is_wc_installed() {
		$plugin            = 'woocommerce/woocommerce.php';
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $plugin ] );
	}

	/**
	 * Determines if the WooCommerce compatible.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	private function is_wc_compatible() {

		if ( ! self::MINIMUM_WC_VERSION ) {
			return true;
		}

		return defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::MINIMUM_WC_VERSION, '>=' );
	}


	/**
	 * Deactivates the plugin.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	protected function deactivate_plugin() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}


	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @since 1.10.0
	 *
	 * @param string $slug    The slug for the notice.
	 * @param string $class   The css class for the notice.
	 * @param string $message The notice message.
	 */
	private function add_admin_notice( $slug, $class, $message ) {

		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}


	/**
	 * Displays any admin notices added with \WC_Facebook_Loader::add_admin_notice()
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function admin_notices() {

		foreach ( (array) $this->notices as $notice_key => $notice ) {

			?>
			<div class="<?php echo esc_attr( $notice['class'] ); ?>">
				<p>
				<?php
				echo wp_kses(
					$notice['message'],
					array(
						'a'      => array(
							'href' => array(),
						),
						'strong' => array(),
					)
				);
				?>
				</p>
			</div>
			<?php
		}
	}


	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * Override this method to add checks for more than just the PHP version.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	private function is_environment_compatible() {
		return version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=' );
	}


	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	private function get_environment_message() {

		return sprintf( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', self::MINIMUM_PHP_VERSION, PHP_VERSION );
	}


	/**
	 * Gets the main \WC_Facebook_Loader instance.
	 *
	 * Ensures only one instance can be loaded.
	 *
	 * @since 1.10.0
	 *
	 * @return \WC_Facebook_Loader
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


}

// fire it up!
WC_Facebook_Loader::instance();
