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
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for render_google_product_category_fields()


	/**
	 * @see Products::render_attribute_fields()
	 *
	 * TODO: add an acceptance test that checks the behavior of the attribute fields {WV 2020-09-18}
	 *
	 * @param string $constant_name the name of the input field
	 * @dataProvider provider_render_attribute_fields
	 */
	public function test_render_attribute_fields( $constant_name ) {
		global $post;

		$product = $this->tester->get_product();
		$post    = get_post( $product->get_id() );

		ob_start();

		Products::render_attribute_fields( $product );

		$output = ob_get_clean();

		$this->tester->assertStringContainsString( constant( Products::class . '::' . $constant_name ), $output );
	}


	/**
	 * This method cannot use Product's constants because the class is not loaded by the time the method is executed.
	 *
	 * @see test_render_attribute_fields()
	 */
	public function provider_render_attribute_fields() {

		return [
			[ 'FIELD_GENDER' ],
			[ 'FIELD_COLOR' ],
			[ 'FIELD_SIZE' ],
			[ 'FIELD_PATTERN' ],
		];
	}


	// TODO: add test for render_commerce_fields()


	/** @see Products::save_commerce_fields() */
	public function test_save_commerce_fields() {
		global $post;

		$product = $this->tester->get_product( [ 'attributes' => $this->tester->create_product_attributes() ] );
		$post    = get_post( $product->get_id() );

		$_POST[ Admin\Products::FIELD_COMMERCE_ENABLED ] = 'yes';
		$_POST[ Admin\Products::FIELD_GOOGLE_PRODUCT_CATEGORY_ID ] = '1234';
		$_POST[ Admin\Products::FIELD_GENDER ] = 'male';
		$_POST[ Admin\Products::FIELD_COLOR ] = 'color';
		$_POST[ Admin\Products::FIELD_SIZE ] = 'size';
		$_POST[ Admin\Products::FIELD_PATTERN ] = 'pattern';

		$this->get_products_handler()->save_commerce_fields( $product );

		$this->assertEquals( true, \SkyVerge\WooCommerce\Facebook\Products::is_commerce_enabled_for_product( $product ) );
		$this->assertEquals( '1234', \SkyVerge\WooCommerce\Facebook\Products::get_google_product_category_id( $product ) );
		$this->assertEquals( 'male', \SkyVerge\WooCommerce\Facebook\Products::get_product_gender( $product ) );
		$this->assertEquals( 'color', \SkyVerge\WooCommerce\Facebook\Products::get_product_color_attribute( $product ) );
		$this->assertEquals( 'size', \SkyVerge\WooCommerce\Facebook\Products::get_product_size_attribute( $product ) );
		$this->assertEquals( 'pattern', \SkyVerge\WooCommerce\Facebook\Products::get_product_pattern_attribute( $product ) );
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
