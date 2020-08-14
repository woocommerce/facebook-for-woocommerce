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

		$_REQUEST['fb_sync_enabled'] = Admin::SYNC_MODE_SYNC_AND_SHOW;

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		$this->assertArrayHasKey( 'meta_query', $vars );
		$this->assertIsArray( $vars['meta_query'] );
		$this->assertArrayHasKey( 'relation', $vars['meta_query'] );
		// sync enabled AND visible
		$this->assertEquals( 'AND', $vars['meta_query']['relation'] );

		$this->assertArrayHasKey( 0, $vars['meta_query'] );
		$this->assertIsArray( $vars['meta_query'][0] );
		$this->assertArrayHasKey( 'relation', $vars['meta_query'][0] );
		// sync enabled set to yes or not set
		$this->assertEquals( 'OR', $vars['meta_query'][0]['relation'] );
		$this->assertArrayHasKey( 0, $vars['meta_query'][0] );
		$this->assertIsArray( $vars['meta_query'][0][0] );
		$this->assertArrayHasKey( 'key', $vars['meta_query'][0][0] );
		$this->assertEquals( '_wc_facebook_sync_enabled', $vars['meta_query'][0][0]['key'] );
		$this->assertArrayHasKey( 1, $vars['meta_query'][0] );
		$this->assertIsArray( $vars['meta_query'][0][1] );
		$this->assertArrayHasKey( 'key', $vars['meta_query'][0][1] );
		$this->assertEquals( '_wc_facebook_sync_enabled', $vars['meta_query'][0][1]['key'] );

		$this->assertArrayHasKey( 1, $vars['meta_query'] );
		$this->assertIsArray( $vars['meta_query'][1] );
		$this->assertArrayHasKey( 'relation', $vars['meta_query'][1] );
		// visibility set to yes or not set
		$this->assertEquals( 'OR', $vars['meta_query'][1]['relation'] );
		$this->assertArrayHasKey( 0, $vars['meta_query'][1] );
		$this->assertIsArray( $vars['meta_query'][1][0] );
		$this->assertArrayHasKey( 'key', $vars['meta_query'][1][0] );
		$this->assertEquals( 'fb_visibility', $vars['meta_query'][1][0]['key'] );
		$this->assertArrayHasKey( 1, $vars['meta_query'][1] );
		$this->assertIsArray( $vars['meta_query'][1][1] );
		$this->assertArrayHasKey( 'key', $vars['meta_query'][1][1] );
		$this->assertEquals( 'fb_visibility', $vars['meta_query'][1][1]['key'] );

		$_REQUEST['fb_sync_enabled'] = Admin::SYNC_MODE_SYNC_AND_HIDE;

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		$this->assertArrayHasKey( 'meta_query', $vars );
		$this->assertIsArray( $vars['meta_query'] );
		$this->assertArrayHasKey( 'relation', $vars['meta_query'] );
		// sync enabled AND hidden
		$this->assertEquals( 'AND', $vars['meta_query']['relation'] );

		$this->assertArrayHasKey( 0, $vars['meta_query'] );
		$this->assertIsArray( $vars['meta_query'][0] );
		$this->assertArrayHasKey( 'relation', $vars['meta_query'][0] );
		// sync enabled set to yes or not set
		$this->assertEquals( 'OR', $vars['meta_query'][0]['relation'] );
		$this->assertArrayHasKey( 0, $vars['meta_query'][0] );
		$this->assertIsArray( $vars['meta_query'][0][0] );
		$this->assertArrayHasKey( 'key', $vars['meta_query'][0][0] );
		$this->assertEquals( '_wc_facebook_sync_enabled', $vars['meta_query'][0][0]['key'] );
		$this->assertArrayHasKey( 1, $vars['meta_query'][0] );
		$this->assertIsArray( $vars['meta_query'][0][1] );
		$this->assertArrayHasKey( 'key', $vars['meta_query'][0][1] );
		$this->assertEquals( '_wc_facebook_sync_enabled', $vars['meta_query'][0][1]['key'] );

		$this->assertArrayHasKey( 1, $vars['meta_query'] );
		$this->assertIsArray( $vars['meta_query'][1] );
		$this->assertArrayNotHasKey( 'relation', $vars['meta_query'][1] );
		// visibility set to no
		$this->assertArrayHasKey( 'key', $vars['meta_query'][1] );
		$this->assertEquals( 'fb_visibility', $vars['meta_query'][1]['key'] );

		$_REQUEST['fb_sync_enabled'] = Admin::SYNC_MODE_SYNC_DISABLED;

		$sync_disabled_product = new \WC_Product_Simple();
		$sync_disabled_product->save();
		Facebook\Products::disable_sync_for_products( [ $sync_disabled_product ] );

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		// sync disabled products are filtered using post__in, so if there are no products with
		// sync disabled, the query is pretty empty
		$this->assertArrayNotHasKey( 'meta_query', $vars );
		$this->assertArrayHasKey( 'post__in', $vars );
		$this->assertContains( $sync_disabled_product->get_id(), $vars['post__in'] );
	}


	/** @see Facebook\Admin::filter_products_by_sync_enabled */
	public function test_filter_products_by_sync_enabled_checks_taxonomies() {

		$_REQUEST['fb_sync_enabled'] = Admin::SYNC_MODE_SYNC_AND_SHOW;

		$cat = wp_insert_term( 'excluded', 'product_cat' );
		$tag = wp_insert_term( 'excluded', 'product_tag' );

		$cat_term_taxonomy_id = $cat['term_taxonomy_id'];
		$tag_term_taxonomy_id = $tag['term_taxonomy_id'];

		$excluded_categories = [ 1, 2, 3, $cat_term_taxonomy_id ];
		$excluded_tags       = [ 4, 5, 6, $tag_term_taxonomy_id ];

		update_option( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, $excluded_categories );
		update_option( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS, $excluded_tags );

		$vars = $this->admin->filter_products_by_sync_enabled( [] );

		$this->assertArrayHasKey( 'tax_query', $vars );
		$this->assertIsArray( $vars['tax_query'] );
		$this->assertArrayHasKey( 'relation', $vars['tax_query'] );
		// not in an excluded category AND not in an excluded tag
		$this->assertEquals( 'AND', $vars['tax_query']['relation'] );

		$this->assertArrayHasKey( 0, $vars['tax_query'] );
		$this->assertIsArray( $vars['tax_query'][0] );
		$this->assertArrayHasKey( 'taxonomy', $vars['tax_query'][0] );
		$this->assertEquals( 'product_cat', $vars['tax_query'][0]['taxonomy'] );
		$this->assertArrayHasKey( 'operator', $vars['tax_query'][0] );
		$this->assertEquals( 'NOT IN', $vars['tax_query'][0]['operator'] );
		$this->assertArrayHasKey( 'terms', $vars['tax_query'][0] );
		$this->assertIsArray( $vars['tax_query'][0]['terms'] );
		$this->assertContains( 1, $vars['tax_query'][0]['terms'] );
		$this->assertContains( 2, $vars['tax_query'][0]['terms'] );
		$this->assertContains( 3, $vars['tax_query'][0]['terms'] );
		$this->assertContains( $cat_term_taxonomy_id, $vars['tax_query'][0]['terms'] );

		$this->assertArrayHasKey( 1, $vars['tax_query'] );
		$this->assertIsArray( $vars['tax_query'][1] );
		$this->assertArrayHasKey( 'taxonomy', $vars['tax_query'][1] );
		$this->assertEquals( 'product_tag', $vars['tax_query'][1]['taxonomy'] );
		$this->assertArrayHasKey( 'operator', $vars['tax_query'][1] );
		$this->assertEquals( 'NOT IN', $vars['tax_query'][1]['operator'] );
		$this->assertArrayHasKey( 'terms', $vars['tax_query'][1] );
		$this->assertIsArray( $vars['tax_query'][1]['terms'] );
		$this->assertContains( 4, $vars['tax_query'][1]['terms'] );
		$this->assertContains( 5, $vars['tax_query'][1]['terms'] );
		$this->assertContains( 6, $vars['tax_query'][1]['terms'] );
		$this->assertContains( $tag_term_taxonomy_id, $vars['tax_query'][1]['terms'] );

		$_REQUEST['fb_sync_enabled'] = Admin::SYNC_MODE_SYNC_DISABLED;

		$sync_disabled_product = new \WC_Product_Simple();
		$sync_disabled_product->save();
		Facebook\Products::disable_sync_for_products( [ $sync_disabled_product ] );

		$excluded_category_product = new \WC_Product_Simple();
		$excluded_category_product->save();
		wp_set_object_terms( $excluded_category_product->get_id(), $cat_term_taxonomy_id, 'product_cat' );

		$excluded_tag_product = new \WC_Product_Simple();
		$excluded_tag_product->save();
		wp_set_object_terms( $excluded_tag_product->get_id(), $tag_term_taxonomy_id, 'product_tag' );

		$vars = $this->admin->filter_products_by_sync_enabled( [ 'post_type' => 'product' ] );

		// sync disabled products are filtered using post__in, so if there are no products with
		// sync disabled or in excluded taxonomies, the query is pretty empty
		$this->assertArrayNotHasKey( 'tax_query', $vars );
		$this->assertArrayNotHasKey( 'meta_query', $vars );
		$this->assertArrayHasKey( 'post__in', $vars );
		$this->assertContains( $sync_disabled_product->get_id(), $vars['post__in'] );
		$this->assertContains( $excluded_category_product->get_id(), $vars['post__in'] );
		$this->assertContains( $excluded_tag_product->get_id(), $vars['post__in'] );
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

