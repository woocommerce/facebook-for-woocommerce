<?php

use SkyVerge\WooCommerce\Facebook\Admin;
use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\Facebook\Products\Sync;

/**
 * Tests the Admin\Product_Categories class.
 */
class ProductCategoriesTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {
		require_once 'includes/Admin/Product_Categories.php';
		require_once 'includes/Admin/Google_Product_Category_Field.php';
		require_once 'includes/Admin/Enhanced_Catalog_Attribute_Fields.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for enqueue_assets()

	/** @see Product_Categories::render_add_enhanced_catalog_attributes_field() */
	public function test_render_add_enhanced_catalog_attributes_field() {

		global $wc_queued_js;

		$google_category_id = 167;
		ob_start();
		$this->get_product_categories_handler()->render_add_enhanced_catalog_attributes_field( $google_category_id );
		$html = trim( ob_get_clean() );
		$html = preg_replace( '/\s{2,}/', ' ', $html );

		$this->assertStringContainsString( '<label for="wc_facebook_enhanced_catalog_attributes_id">', $html );
		$this->assertStringContainsString( '<select name="wc_facebook_enhanced_catalog_attribute_gender" id="wc_facebook_enhanced_catalog_attribute_gender">', $html );
	}

	/** @see Product_Categories::test_render_edit_enhanced_catalog_attributes_field() */
	public function test_render_edit_enhanced_catalog_attributes_field() {

		global $wc_queued_js;

		$google_category_id = 167;
		$category					= wp_insert_term( 'New category', 'product_cat' );
		$category_term_id = $category['term_id'];
		$term             = get_term( $category_term_id, 'product_cat' );
		update_term_meta( $term->term_id, \SkyVerge\WooCommerce\Facebook\Products::GOOGLE_PRODUCT_CATEGORY_META_KEY, $google_category_id );

		ob_start();
		$this->get_product_categories_handler()->render_edit_enhanced_catalog_attributes_field( $term );
		$html = trim( ob_get_clean() );
		$html = preg_replace( '/\s{2,}/', ' ', $html );

		$this->assertStringContainsString( '<label for="wc_facebook_enhanced_catalog_attributes_id">', $html );
		$this->assertStringContainsString( '<select name="wc_facebook_enhanced_catalog_attribute_gender" id="wc_facebook_enhanced_catalog_attribute_gender">', $html );
	}

	/** @see Product_Categories::render_add_google_product_category_field() */
	public function test_render_add_google_product_category_field() {

		global $wc_queued_js;

		ob_start();
		$this->get_product_categories_handler()->render_add_google_product_category_field();
		$html = trim( ob_get_clean() );

		$this->assertStringContainsString( '<div class="form-field term-wc_facebook_google_product_category_id-wrap">', $html );
		$this->assertStringContainsString( '<label for="wc_facebook_google_product_category_id">', $html );
		$this->assertStringContainsString( '<span class="woocommerce-help-tip"', $html );
		$this->assertStringContainsString( '<input type="hidden" id="wc_facebook_google_product_category_id" name="wc_facebook_google_product_category_id"/>', preg_replace( '/\s{2,}/', ' ', $html ) );

		$this->assertStringContainsString( 'new WC_Facebook_Google_Product_Category_Fields', $wc_queued_js );
	}


	/** @see Product_Categories::render_edit_google_product_category_field() */
	public function test_render_edit_google_product_category_field() {

		global $wc_queued_js;
		$category					= wp_insert_term( 'New category', 'product_cat' );
		$category_term_id = $category['term_id'];
		$term             = get_term( $category_term_id, 'product_cat' );

		$term_data = wp_insert_term( 'term', 'product_cat' );

		$term = get_term( $term_data['term_id'] );

		ob_start();
		$this->get_product_categories_handler()->render_edit_google_product_category_field( $term );
		$html = trim( ob_get_clean() );

		$this->assertStringContainsString( '<tr class="form-field term-wc_facebook_google_product_category_id-wrap">', $html );
		$this->assertStringContainsString( '<label for="wc_facebook_google_product_category_id">', $html );
		$this->assertStringContainsString( '<span class="woocommerce-help-tip"', $html );
		$this->assertStringContainsString( '<input type="hidden" id="wc_facebook_google_product_category_id" name="wc_facebook_google_product_category_id" value=""/>', preg_replace( '/\s{2,}/', ' ', $html ) );

		$this->assertStringContainsString( 'new WC_Facebook_Google_Product_Category_Fields', $wc_queued_js );
	}


	/** @see Product_Categories::render_google_product_category_tooltip() */
	public function test_render_google_product_category_tooltip() {

		ob_start();
		$this->get_product_categories_handler()->render_google_product_category_tooltip();
		$html = trim( ob_get_clean() );

		$this->assertEquals( '<span class="woocommerce-help-tip" data-tip="Choose a default Google product category for products in this category. Products need at least two category levels defined for tax to be correctly applied."></span>', $html );
	}


	/** @see Product_Categories::get_google_product_category_field_title() */
	public function test_get_google_product_category_field_title() {

		$this->assertEquals( 'Default Google product category', $this->get_product_categories_handler()->get_google_product_category_field_title() );
	}


	/** @see Product_Categories::save_google_product_category() */
	public function test_save_google_product_category_and_enhanced_attributes() {

		$category                  = wp_insert_term( 'New category', 'product_cat' );
		$category_term_id          = $category['term_id'];
		$category_term_taxonomy_id = $category['term_taxonomy_id'];

		$simple_product = $this->tester->get_product();
		$simple_product->set_category_ids( [ $category_term_id ] );
		$simple_product->save();

		$variable_product = $this->tester->get_variable_product();
		$variable_product->set_category_ids( [ $category_term_id ] );
		$variable_product->save();

		$sync = $this
			->getMockBuilder( Sync::class )
			->onlyMethods( [ 'create_or_update_products' ] )
			->getMock();

		$expected_sync_ids = array_merge( [ $simple_product->get_id() ], $variable_product->get_children() );

		// test will fail if the method is not called with the correct param
		$sync->method( 'create_or_update_products' )
		     ->willReturn( \Codeception\Stub\Expected::once( $expected_sync_ids ) );

		// replace the sync handler property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'products_sync_handler' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $sync );

		$_POST[ Admin\Product_Categories::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ] = '1234';
		$enhanced_catalog_prefix = Admin\Enhanced_Catalog_Attribute_Fields::FIELD_ENHANCED_CATALOG_ATTRIBUTE_PREFIX;
		$_POST[ $enhanced_catalog_prefix . 'gender' ] = 'male';

		$this->get_product_categories_handler()->save_google_product_category_and_enhanced_attributes( $category_term_id, $category_term_taxonomy_id, 'product_cat' );
		$term_meta = get_term_meta( $category_term_id, Products::ENHANCED_CATALOG_ATTRIBUTES_META_KEY_PREFIX . 'gender', true );

		$this->assertEquals( '1234', get_term_meta( $category_term_id, Products::GOOGLE_PRODUCT_CATEGORY_META_KEY, true ) );
		$this->assertEquals( 'male',  $term_meta );
	}


	/** @see Product_Categories::save_google_product_category() */
	public function test_save_google_product_category_no_products() {

		$category                  = wp_insert_term( 'New category', 'product_cat' );
		$category_term_id          = $category['term_id'];
		$category_term_taxonomy_id = $category['term_taxonomy_id'];

		$sync = $this
			->getMockBuilder( Sync::class )
			->onlyMethods( [ 'create_or_update_products' ] )
			->getMock();

		// test will fail if the method is called
		$sync->method( 'create_or_update_products' )
		     ->willReturn( \Codeception\Stub\Expected::never() );

		// replace the sync handler property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'products_sync_handler' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $sync );

		$_POST[ Admin\Product_Categories::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ] = '1234';
		$this->get_product_categories_handler()->save_google_product_category_and_enhanced_attributes( $category_term_id, $category_term_taxonomy_id, 'product_cat' );

		$this->assertEquals( '1234', get_term_meta( $category_term_id, Products::GOOGLE_PRODUCT_CATEGORY_META_KEY, true ) );
	}


	/** Utility methods ***********************************************************************************************/


	/**
	 * Gets a handler instance.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return Admin\Product_Categories
	 */
	private function get_product_categories_handler() {

		return new Admin\Product_Categories();
	}


}
