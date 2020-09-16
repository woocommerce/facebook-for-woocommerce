<?php

use SkyVerge\WooCommerce\Facebook\Admin;

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
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for enqueue_assets()


	/** @see Product_Categories::render_add_google_product_category_field() */
	public function test_render_add_google_product_category_field() {

		global $wc_queued_js;

		ob_start();
		$this->get_product_categories_handler()->render_add_google_product_category_field();
		$html = trim( ob_get_clean() );

		$this->assertStringContainsString( '<div class="form-field term-wc_facebook_google_product_category_id-wrap">', $html );
		$this->assertStringContainsString( '<label for="wc_facebook_google_product_category_id">', $html );
		$this->assertStringContainsString( '<span class="woocommerce-help-tip"', $html );
		$this->assertStringContainsString( '<input type="hidden" id="wc_facebook_google_product_category_id"
				       name="wc_facebook_google_product_category_id"/>', $html );

		$this->assertStringContainsString( 'new WC_Facebook_Google_Product_Category_Fields', $wc_queued_js );
	}


	/** @see Product_Categories::render_edit_google_product_category_field() */
	public function test_render_edit_google_product_category_field() {

		global $wc_queued_js;

		ob_start();
		$this->get_product_categories_handler()->render_edit_google_product_category_field();
		$html = trim( ob_get_clean() );

		$this->assertStringContainsString( '<tr class="form-field term-wc_facebook_google_product_category_id-wrap">', $html );
		$this->assertStringContainsString( '<label for="wc_facebook_google_product_category_id">', $html );
		$this->assertStringContainsString( '<span class="woocommerce-help-tip"', $html );
		$this->assertStringContainsString( '<input type="hidden" id="wc_facebook_google_product_category_id"
					       name="wc_facebook_google_product_category_id"/>', $html );

		$this->assertStringContainsString( 'new WC_Facebook_Google_Product_Category_Fields', $wc_queued_js );
	}


	/** @see Product_Categories::render_google_product_category_tooltip() */
	public function test_render_google_product_category_tooltip() {

		ob_start();
		$this->get_product_categories_handler()->render_google_product_category_tooltip();
		$html = trim( ob_get_clean() );

		$this->assertEquals( '<span class="woocommerce-help-tip" data-tip="Choose a default Google product category for products in this category. Products need at least two category levels defined to be sold on Instagram."></span>', $html );
	}


	/** @see Product_Categories::get_google_product_category_field_title() */
	public function test_get_google_product_category_field_title() {

		$this->assertEquals( 'Default Google product category', $this->get_product_categories_handler()->get_google_product_category_field_title() );
	}


	// TODO: add test for save_google_product_category()


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
