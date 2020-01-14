<?php

use SkyVerge\WooCommerce\Facebook;

/**
 * Tests the Admin class.
 */
class Admin_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \SkyVerge\WooCommerce\Facebook\Admin */
	protected $admin;

	/** @var \WC_Facebookcommerce_Integration */
	protected $integration;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Admin.php';

		$this->admin = new \SkyVerge\WooCommerce\Facebook\Admin();
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
		$this->assertArrayHasKey( 'facebook_sync_enabled', $columns );
		$this->assertArrayHasKey( 'facebook_shop_visibility', $columns );
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

