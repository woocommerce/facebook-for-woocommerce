<?php

use SkyVerge\WooCommerce\Facebook\Admin;
use SkyVerge\WooCommerce\Facebook\Admin\Products;

/**
 * Tests the Admin\Products class.
 */
class ProductsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once WC()->plugin_path() . '/includes/admin/wc-meta-box-functions.php';

		require_once 'includes/Admin/Products.php';
		require_once 'includes/Admin/Enhanced_Catalog_Attribute_Fields.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for render_google_product_category_fields()


	/** @see Products::save_commerce_fields() */
	public function test_save_commerce_fields() {
		global $post;

		$product = $this->tester->get_product( [ 'attributes' => $this->tester->create_product_attributes() ] );
		$post    = get_post( $product->get_id() );

		$_POST[ Admin\Products::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ] = '1234';

		$enhanced_catalog_prefix = Admin\Enhanced_Catalog_Attribute_Fields::FIELD_ENHANCED_CATALOG_ATTRIBUTE_PREFIX;
		$_POST[ $enhanced_catalog_prefix . 'gender' ] = 'male';

		$this->get_products_handler()->save_commerce_fields( $product );
		$gender = \SkyVerge\WooCommerce\Facebook\Products::get_enhanced_catalog_attribute( 'gender', $product );

		$this->assertEquals( '1234', \SkyVerge\WooCommerce\Facebook\Products::get_google_product_category_id( $product ) );
		$this->assertEquals( 'male', $gender );
	}


	/** Utility methods ***********************************************************************************************/


	/**
	 * Gets a products handler instance.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return Admin\Products
	 */
	private function get_products_handler() {

		return new Admin\Products();
	}


}
