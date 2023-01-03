<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Admin;

use Automattic\WooCommerce\Admin\Features\Features as WooAdminFeatures;
use Automattic\WooCommerce\Admin\Features\Navigation\Menu as WooAdminMenu;
use WooCommerce\Facebook\Admin\Settings_Screens;
use WooCommerce\Facebook\Admin\Settings_Screens\Connection;
use WooCommerce\Facebook\Framework\Helper;
use WooCommerce\Facebook\Framework\Plugin\Compatibility;
use WooCommerce\Facebook\Framework\Plugin\Exception as PluginException;

defined( 'ABSPATH' ) || exit;

/**
 * Admin settings handler.
 *
 * @since 2.0.0
 */
class Settings {

	/** @var string base settings page ID */
	const PAGE_ID = 'wc-facebook';

	/**
	 * Submenu page ID
	 *
	 * @var string
	 */
	const SUBMENU_PAGE_ID = 'edit-tags.php?taxonomy=fb_product_set&post_type=product';

	/** @var Abstract_Settings_Screen[] */
	private $screens;

	/**
	 * Whether the new Woo nav should be used.
	 *
	 * @var bool
	 */
	public $use_woo_nav;

	/**
	 * Settings constructor.
	 *
	 * @param bool $is_connected is the state of the plugin connection to the Facebook Marketing API
	 * @since 2.0.0
	 */
	public function __construct( bool $is_connected ) {

		$this->screens = $this->build_menu_item_array( $is_connected );

		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_action( 'wp_loaded', array( $this, 'save' ) );
		add_filter( 'parent_file', array( $this, 'set_parent_and_submenu_file' ) );
	}

	/**
	 * Arranges the tabs. If the plugin is connected to FB, Advertise tab will be first, otherwise the Connection tab will be the first tab.
	 *
	 * @param bool $is_connected is Facebook connected
	 * @since 3.0.7
	 */
	private function build_menu_item_array( bool $is_connected ): array {
		$advertise  = [ Settings_Screens\Advertise::ID => new Settings_Screens\Advertise() ];
		$connection = [ Settings_Screens\Connection::ID => new Settings_Screens\Connection() ];

		$first = ( $is_connected ) ? $advertise : $connection;
		$last  = ( $is_connected ) ? $connection : $advertise;

		$screens = array(
			Settings_Screens\Product_Sync::ID => new Settings_Screens\Product_Sync(),
			Settings_Screens\Product_Sets::ID => new Settings_Screens\Product_Sets(),
			Settings_Screens\Messenger::ID    => new Settings_Screens\Messenger(),
		);

		return array_merge( array_merge( $first, $screens ), $last );
	}

	/**
	 * Adds the Facebook menu item.
	 *
	 * @since 2.0.0
	 */
	public function add_menu_item() {
		$root_menu_item       = 'woocommerce';
		$is_marketing_enabled = false;
		$this->use_woo_nav    = class_exists( WooAdminFeatures::class )
			&& class_exists( WooAdminMenu::class )
			&& WooAdminFeatures::is_enabled( 'navigation' );
		if ( Compatibility::is_enhanced_admin_available() ) {
			if ( class_exists( WooAdminFeatures::class ) ) {
				$is_marketing_enabled = WooAdminFeatures::is_enabled( 'marketing' );
			} else {
				$is_marketing_enabled = is_callable( '\Automattic\WooCommerce\Admin\Loader::is_feature_enabled' )
					&& \Automattic\WooCommerce\Admin\Loader::is_feature_enabled( 'marketing' );
			}
			if ( $is_marketing_enabled ) {
				$root_menu_item = 'woocommerce-marketing';
			}
		}
		add_submenu_page(
			$root_menu_item,
			__( 'Facebook for WooCommerce', 'facebook-for-woocommerce' ),
			__( 'Facebook', 'facebook-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_ID,
			[ $this, 'render' ],
			5
		);
		$this->connect_to_enhanced_admin( $is_marketing_enabled ? 'marketing_page_wc-facebook' : 'woocommerce_page_wc-facebook' );
		$this->register_woo_nav_menu_items();

		if ( $is_marketing_enabled ) {
			$this->add_fb_product_sets_to_marketing_menu();
		}
	}

	/**
	 * Checks for connection and if established adds Facebook Product Sets taxonomy page to the Marketing menu.
	 *
	 * @since 2.6.29
	 */
	private function add_fb_product_sets_to_marketing_menu() {
		$is_connected = facebook_for_woocommerce()->get_connection_handler()->is_connected();

		// If a connection is not established, do not add Facebook Product Sets to Marketing menu.
		if ( ! $is_connected ) {
			return;
		}

		add_submenu_page(
			'woocommerce-marketing',
			esc_html__( 'Facebook Product Sets', 'facebook-for-woocommerce' ),
			esc_html__( 'Facebook Product Sets', 'facebook-for-woocommerce' ),
			'manage_woocommerce',
			admin_url( self::SUBMENU_PAGE_ID ),
			'',
			10
		);
	}

	/**
	 * Set the parent and submenu file while accessing Facebook Product Sets in the marketing menu.
	 *
	 * @since 2.6.29
	 * @param string $parent_file The parent file.
	 * @return string
	 */
	public function set_parent_and_submenu_file( $parent_file ) {
		global $submenu_file, $current_screen;

		// The Facebook Product Set is now a submenu of woocommerce-marketing. Hence, we are overriding the $parent_file and $submenu_file when accessing the fb_product_set taxonomy page.
		if ( isset( $current_screen->taxonomy ) && 'fb_product_set' === $current_screen->taxonomy ) {
			$parent_file  = 'woocommerce-marketing';
			$submenu_file = admin_url( self::SUBMENU_PAGE_ID ); //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $parent_file;
	}

	/**
	 * Enables enhanced admin support for the main Facebook settings page.
	 *
	 * @since 2.2.0
	 *
	 * @param string $screen_id the ID to connect to
	 */
	private function connect_to_enhanced_admin( $screen_id ) {
		if ( is_callable( 'wc_admin_connect_page' ) ) {
			$crumbs = array(
				__( 'Facebook for WooCommerce', 'facebook-for-woocommerce' ),
			);
			if ( ! empty( $_GET['tab'] ) ) {
				switch ( $_GET['tab'] ) {
					case Connection::ID:
						$crumbs[] = __( 'Connection', 'facebook-for-woocommerce' );
						break;
					case Settings_Screens\Messenger::ID:
						$crumbs[] = __( 'Messenger', 'facebook-for-woocommerce' );
						break;
					case Settings_Screens\Product_Sync::ID:
						$crumbs[] = __( 'Product sync', 'facebook-for-woocommerce' );
						break;
					case Settings_Screens\Advertise::ID:
						$crumbs[] = __( 'Advertise', 'facebook-for-woocommerce' );
						break;
				}
			}
			wc_admin_connect_page(
				array(
					'id'        => self::PAGE_ID,
					'screen_id' => $screen_id,
					'path'      => add_query_arg( 'page', self::PAGE_ID, 'admin.php' ),
					'title'     => $crumbs,
				)
			);
		}
	}


	/**
	 * Renders the settings page.
	 *
	 * @since 2.0.0
	 */
	public function render() {
		$tabs        = $this->get_tabs();
		$current_tab = Helper::get_requested_value( 'tab' );
		if ( ! $current_tab ) {
			$current_tab = current( array_keys( $tabs ) );
		}
		$screen = $this->get_screen( $current_tab );
		?>
		<div class="wrap woocommerce">
			<?php if ( ! $this->use_woo_nav ) : ?>
				<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php foreach ( $tabs as $id => $label ) : ?>
						<a href="<?php echo esc_html( admin_url( 'admin.php?page=' . self::PAGE_ID . '&tab=' . esc_attr( $id ) ) ); ?>" class="nav-tab <?php echo $current_tab === $id ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<?php facebook_for_woocommerce()->get_message_handler()->show_messages(); ?>
			<?php if ( $screen ) : ?>
				<h1 class="screen-reader-text"><?php echo esc_html( $screen->get_title() ); ?></h1>
				<p><?php echo wp_kses_post( $screen->get_description() ); ?></p>
				<?php $screen->render(); ?>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Saves the settings page.
	 *
	 * @since 2.0.0
	 */
	public function save() {
		if ( ! is_admin() || Helper::get_requested_value( 'page' ) !== self::PAGE_ID ) {
			return;
		}
		$screen = $this->get_screen( Helper::get_posted_value( 'screen_id' ) );
		if ( ! $screen ) {
			return;
		}
		if ( ! Helper::get_posted_value( 'save_' . $screen->get_id() . '_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to save these settings.', 'facebook-for-woocommerce' ) );
		}
		check_admin_referer( 'wc_facebook_admin_save_' . $screen->get_id() . '_settings' );
		try {
			$screen->save();
			facebook_for_woocommerce()->get_message_handler()->add_message( __( 'Your settings have been saved.', 'facebook-for-woocommerce' ) );
		} catch ( PluginException $exception ) {
			facebook_for_woocommerce()->get_message_handler()->add_error(
				sprintf(
				/* translators: Placeholders: %s - user-friendly error message */
					__( 'Your settings could not be saved. %s', 'facebook-for-woocommerce' ),
					$exception->getMessage()
				)
			);
		}
	}


	/**
	 * Gets a settings screen object based on ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $screen_id desired screen ID
	 * @return Abstract_Settings_Screen|null
	 */
	public function get_screen( $screen_id ) {
		$screens = $this->get_screens();
		return ! empty( $screens[ $screen_id ] ) && $screens[ $screen_id ] instanceof Abstract_Settings_Screen ? $screens[ $screen_id ] : null;
	}


	/**
	 * Gets the available screens.
	 *
	 * @since 2.0.0
	 *
	 * @return Abstract_Settings_Screen[]
	 */
	public function get_screens() {
		/**
		 * Filters the admin settings screens.
		 *
		 * @since 2.0.0
		 *
		 * @param array $screens available screen objects
		 */
		$screens = (array) apply_filters( 'wc_facebook_admin_settings_screens', $this->screens, $this );
		// ensure no bogus values are added via filter
		$screens = array_filter(
			$screens,
			function( $value ) {
				return $value instanceof Abstract_Settings_Screen;
			}
		);
		return $screens;
	}


	/**
	 * Gets the tabs.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_tabs() {
		$tabs = [];
		foreach ( $this->get_screens() as $screen_id => $screen ) {
			$tabs[ $screen_id ] = $screen->get_label();
		}
		/**
		 * Filters the admin settings tabs.
		 *
		 * @since 2.0.0
		 *
		 * @param array $tabs tab data, as $id => $label
		 */
		return (array) apply_filters( 'wc_facebook_admin_settings_tabs', $tabs, $this );
	}

	/**
	 * Register nav items for new Woo nav.
	 *
	 * @since 2.3.3
	 */
	private function register_woo_nav_menu_items() {
		if ( ! $this->use_woo_nav ) {
			return;
		}
		WooAdminMenu::add_plugin_category(
			array(
				'id'         => 'facebook-for-woocommerce',
				'title'      => __( 'Facebook', 'facebook-for-woocommerce' ),
				'capability' => 'manage_woocommerce',
			)
		);
		$order = 1;
		foreach ( $this->get_screens() as $screen_id => $screen ) {
			$url = $screen instanceof Settings_Screens\Product_Sets
				? 'edit-tags.php?taxonomy=fb_product_set&post_type=product'
				: 'wc-facebook&tab=' . $screen->get_id();
			WooAdminMenu::add_plugin_item(
				array(
					'id'     => 'facebook-for-woocommerce-' . $screen->get_id(),
					'parent' => 'facebook-for-woocommerce',
					'title'  => $screen->get_label(),
					'url'    => $url,
					'order'  => $order,
				)
			);
			$order++;
		}
	}
}
