<?php

use SkyVerge\WooCommerce\Facebook;
use SkyVerge\WooCommerce\Facebook\Admin;

/**
 * Tests the Admin class.
 */
class Admin_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var Admin */
	protected $admin;

	/** @var \WC_Facebookcommerce_Integration */
	protected $integration;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		if ( ! class_exists( Admin::class ) ) {
			require_once 'includes/Admin.php';
		}

		$this->integration = facebook_for_woocommerce()->get_integration();

		// simulate a complete plugin configuration so that actions and filters callbacks are setup
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( '1234' );
		$this->integration->update_product_catalog_id( '1234' );

		$this->admin = new Admin();
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Facebook\Admin::validate_cart_url() */
	public function test_validate_cart_url() {

		$this->assertTrue( (bool) has_action( 'admin_notices', [ $this->admin, 'validate_cart_url' ] ) );
	}


	/** @see Facebook\Admin::add_product_list_table_column() */
	public function test_add_product_list_table_columns() {

		$this->assertTrue( (bool) has_action( 'manage_product_posts_columns', [ $this->admin, 'add_product_list_table_columns' ] ) );

		$columns = $this->admin->add_product_list_table_columns( [] );

		$this->assertIsArray( $columns );
		$this->assertArrayHasKey( 'facebook_sync', $columns );
	}


	/** @see Facebook\Admin::add_product_list_table_column_content() */
	public function test_add_product_list_table_columns_content() {

		$this->assertTrue( (bool) has_action( 'manage_product_posts_custom_column', [ $this->admin, 'add_product_list_table_columns_content' ] ) );
	}


	/** @see Facebook\Admin::add_products_by_sync_enabled_input_filter() */
	public function test_add_products_by_sync_enabled_input_filter() {

		$this->assertTrue( (bool) has_action( 'restrict_manage_posts', [ $this->admin, 'add_products_by_sync_enabled_input_filter' ] ) );
	}


	/** @see Facebook\Admin::filter_products_by_sync_enabled() */
	public function test_filter_products_by_sync_enabled() {

		$this->assertTrue( (bool) has_action( 'request', [ $this->admin, 'filter_products_by_sync_enabled' ] ) );

		$_REQUEST['fb_sync_enabled'] = 'yes';

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		$this->assertArrayHasKey( 'meta_query', $vars );
		$this->assertIsArray( $vars['meta_query'] );
		$this->assertArrayHasKey( 'relation', $vars['meta_query'] );
		$this->assertEquals( 'OR', $vars['meta_query']['relation'] );

		$_REQUEST['fb_sync_enabled'] = 'no';

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		$this->assertArrayHasKey( 'meta_query', $vars );
		$this->assertIsArray( $vars['meta_query'] );
		$this->assertArrayNotHasKey( 'relation', $vars );
	}


	/** @see Facebook\Admin::filter_products_by_sync_enabled */
	public function test_filter_products_by_sync_enabled_checks_taxonomies() {

		$_REQUEST['fb_sync_enabled'] = 'yes';

		$excluded_categories = [ 1, 2, 3 ];
		$excluded_tags       = [ 4, 5, 6 ];

		$options = [
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS => $excluded_categories,
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS      => $excluded_tags,
		];

		update_option( 'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_settings', $options );

		// force integration to load settings from the database
		$this->integration->init_settings();

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		$this->assertArrayHasKey( 'tax_query', $vars );

		$this->assertEquals( 'product_cat', $vars['tax_query'][0]['taxonomy'] );
		$this->assertEquals( 'term_id', $vars['tax_query'][0]['field'] );
		$this->assertEquals( 'NOT IN', $vars['tax_query'][0]['operator'] );
		$this->assertSame( $excluded_categories, $vars['tax_query'][0]['terms'] );

		$this->assertEquals( 'product_tag', $vars['tax_query'][1]['taxonomy'] );
		$this->assertEquals( 'term_id', $vars['tax_query'][1]['field'] );
		$this->assertEquals( 'NOT IN', $vars['tax_query'][1]['operator'] );
		$this->assertSame( $excluded_tags, $vars['tax_query'][1]['terms'] );
	}


	/** @see Facebook\Admin::filter_products_by_sync_enabled */
	public function test_filter_products_by_sync_disabled_checks_taxonomies() {

		$_REQUEST['fb_sync_enabled'] = 'no';

		$excluded_categories = [ 1, 2, 3 ];
		$excluded_tags       = [ 4, 5, 6 ];

		$options = [
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS => $excluded_categories,
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS      => $excluded_tags,
		];

		update_option( 'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_settings', $options );

		// force integration to load settings from the database
		$this->integration->init_settings();

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		// if terms are excluded, products are filtered using post__not_in, that way
		// we overcome the limitation of not being able to use the same query to
		// retrieve products that have a meta key OR belong to a particular taxonmy
		$this->assertArrayNotHasKey( 'tax_query', $vars );
		$this->assertEmpty( $vars['meta_query'] );
		$this->assertArrayHasKey( 'post__not_in', $vars );
	}


	/** @see Facebook\Admin::add_products_sync_bulk_actions() */
	public function test_add_products_sync_bulk_actions() {

		$this->assertTrue( (bool) has_action( 'bulk_actions-edit-product', [ $this->admin, 'add_products_sync_bulk_actions' ] ) );

		$actions = $this->admin->add_products_sync_bulk_actions( [] );

		$this->assertIsArray( $actions );
		$this->assertArrayHasKey( 'facebook_include', $actions );
		$this->assertArrayHasKey( 'facebook_exclude', $actions );
	}


	/** @see Facebook\Admin::handle_products_sync_bulk_actions() */
	public function test_handle_products_sync_bulk_actions() {

		$this->assertTrue( (bool) has_action( 'handle_bulk_actions-edit-product', [ $this->admin, 'handle_products_sync_bulk_actions' ] ) );
	}


}

